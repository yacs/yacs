<?php
/**
 * the database abstraction layer for visits
 *
 * This class takes care of presence information. It receives probes generated
 * automatically in the background during site browsing, that mention the anchor
 * of the visited page.
 *
 * User profiles also store presence information, including date and place
 * of last click.
 *
 * @see users/users.php
 *
 * On the other hand, Visits are a convenient way to track
 * surfer presence continuously when several pages are jointly browsed,
 * which is the case with private conversations, and with channels.
 *
 * Probes are recorded and considered accurate for 1 minute and a half, that is,
 * 90 seconds. This value is quite high, but this is because someone can switch
 * to another page for some time, for example to upload a file, and then come
 * back to the monitored page.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Visits {

	/**
	 * check if one user is at one anchor
	 *
	 * @param int id of the visiting user
	 * @param string the anchor of the visited page (e.g., 'article:12')
	 * @param int maximum age of visit, in seconds
	 * @return TRUE on recent visit, FALSE otherwise
	 */
	function check_user_at_anchor($user_id, $anchor, $timeout=90) {
		global $context;

		// sanity check
		if(!$user_id || !$anchor)
			return FALSE;

		// only consider recent presence records
		$threshold = gmstrftime('%Y-%m-%d %H:%M:%S', time() - $timeout);

		// list matching visits
		$query = "SELECT id FROM ".SQL::table_name('visits')." AS visits"
			." WHERE (visits.user_id = ".SQL::escape($user_id).")"
			."	AND (visits.anchor LIKE '".SQL::escape($anchor)."')"
			."	AND (visits.edit_date >= '".SQL::escape($threshold)."')";

		// count rows
		if(SQL::query_count($query))
			return TRUE;
		else
			return FALSE;
	}

	/**
	 * list pages browsed by one user
	 *
	 * @param int id of the visiting user
	 * @param int the maximum size of the returned list
	 * @param int maximum age of visit, in seconds
	 * @return array a compact list of links, or NULL
	 */
	function &list_for_user($user, $count=3, $timeout=90) {
		global $context;

		// return by reference
		$output = NULL;

		// sanity check
		if(!$user)
			return $output;

		// only consider recent presence records
		$threshold = gmstrftime('%Y-%m-%d %H:%M:%S', time() - $timeout);

		// limit the scope of the request
		$where = "visits.active='Y'";
		if(Surfer::is_teased())
			$where .= " OR visits.active='R'";
		if(Surfer::is_associate())
			$where .= " OR visits.active='N'";

		// select matching links
		$query = "SELECT * FROM ".SQL::table_name('visits')." AS visits"
			." WHERE (visits.user_id = ".SQL::escape($user).")"
			."	AND (visits.edit_date >= '".SQL::escape($threshold)."')"
			."	AND (".$where.")"
			." ORDER BY visits.edit_date DESC LIMIT ".$count;
		if(!$result = SQL::query($query))
			return $output;

		// empty list
		if(!SQL::count($result))
			return $output;

		// process all items in the list
		$output = array();
		while($item =& SQL::fetch($result)) {

			// identify the visited page
			if(!$anchor =& Anchors::get($item['anchor']))
				continue;

			// ensure this one is visible
			if(!$anchor->is_viewable())
				continue;

			// url to the visited page
			$url = $anchor->get_url();;

			// title of the visited page
			$label = $anchor->get_title();

			// list all components for this item
			$output[$url] = $label;

		}

		// end of processing
		SQL::free($result);
		return $output;
	}

	/**
	 * list present users, based on last visits
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the maximum size of the returned list
	 * @param string the list variant, if any
	 * @param int maximum age of visit, in seconds
	 * @return array a compact list of user profiles
	 */
	function &list_users($offset=0, $count=30, $layout='compact', $timeout=90) {
		global $context;

		// return by reference
		$output = NULL;

		// limit the scope of the request
		$where = "users.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR users.active='R'";
		if(Surfer::is_associate())
			$where .= " OR users.active='N'";

		// only consider recent presence records
		$threshold = gmstrftime('%Y-%m-%d %H:%M:%S', time() - $timeout);

		// list matching users
		$query = "SELECT users.*, visits.edit_date as visit_date FROM ".SQL::table_name('visits')." AS visits"
			.", ".SQL::table_name('users')." AS users"
			." WHERE (visits.user_id = users.id)"
			."	AND (visits.edit_date >= '".SQL::escape($threshold)."')"
			."	AND (".$where.")"
			." ORDER BY users.nick_name LIMIT ".$offset.','.$count;

		// use existing listing facility
		$output =& Users::list_selected(SQL::query($query), $layout);
		return $output;
	}

	/**
	 * list users present at some anchor
	 *
	 * @param string the anchor of the visited page (e.g., 'article:12')
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the maximum size of the returned list
	 * @param string the list variant, if any
	 * @param int maximum age of visit, in seconds
	 * @return array a compact list of user profiles
	 */
	function &list_users_at_anchor($anchor, $offset=0, $count=30, $layout='compact', $timeout=90) {
		global $context;

		// return by reference
		$output = NULL;

		// sanity check
		if(!$anchor)
			return $output;

		// only consider recent presence records
		$threshold = gmstrftime('%Y-%m-%d %H:%M:%S', time() - $timeout);

		// limit the scope of the request
		$where = "users.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR users.active='R'";
		if(Surfer::is_associate())
			$where .= " OR users.active='N'";

		// list matching users
		$query = "SELECT users.*, visits.edit_date as visit_date FROM ".SQL::table_name('visits')." AS visits"
			.", ".SQL::table_name('users')." AS users"
			." WHERE (visits.user_id = users.id)"
			."	AND (visits.anchor LIKE '".SQL::escape($anchor)."')"
			."	AND (visits.edit_date >= '".SQL::escape($threshold)."')"
			."	AND (".$where.")"
			." ORDER BY users.nick_name LIMIT ".$offset.','.$count;

		// use existing listing facility
		$output =& Users::list_selected(SQL::query($query), $layout);
		return $output;
	}

	/**
	 * visits prove the presence of one user
	 *
	 * @param int id of the visiting user
	 * @param int maximum age of visit, in seconds
	 * @return TRUE if the user is present, FALSE otherwise
	 */
	function prove_presence_of($user, $timeout=90) {
		global $context;

		// sanity check
		if(!$user)
			return FALSE;

		// only consider recent presence records
		$threshold = gmstrftime('%Y-%m-%d %H:%M:%S', time() - $timeout);

		// select matching links
		$query = "SELECT id FROM ".SQL::table_name('visits')." AS visits"
			." WHERE (visits.user_id = ".SQL::escape($user).")"
			."	AND (visits.edit_date >= '".SQL::escape($threshold)."')"
			." LIMIT 1";
		if(!$result = SQL::query_count($query))
			return FALSE;

		// we have at least one recent record
		return TRUE;
	}

	/**
	 * kill visits of one user
	 *
	 * @param int user id to be purged
	 *
	 * @see shared/surfer.php
	 * @see users/logout.php
	 */
	function purge_for_user($user_id) {
		global $context;

		if(!$user_id)
			return;

		// kill last visits for this user
		$query = "DELETE FROM ".SQL::table_name('visits')." WHERE user_id = ".SQL::escape($user_id);
		SQL::query($query);

	}

	/**
	 * create table for visits
	 */
	function setup() {
		global $context;

		$fields = array();
		$fields['id']			= "MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT";
		$fields['active']		= "ENUM('Y','R','N') DEFAULT 'Y' NOT NULL"; 				// Yes, Restricted or No
		$fields['anchor']		= "VARCHAR(64) NOT NULL";
		$fields['edit_date']	= "DATETIME";
		$fields['user_id']		= "MEDIUMINT UNSIGNED";

		$indexes = array();
		$indexes['PRIMARY KEY'] 	= "(id)";
		$indexes['INDEX active']	= "(active)";
		$indexes['INDEX anchor']	= "(anchor)";
		$indexes['INDEX edit_date'] = "(edit_date)";
		$indexes['INDEX user_id']	= "(user_id)";

		return SQL::setup_table('visits', $fields, $indexes);
	}

	/**
	 * get some statistics
	 *
	 * @param int maximum age of visit, in seconds
	 * @return the resulting ($count, $min_date, $max_date) array
	 *
	 * @see control/index.php
	 */
	function &stat($timeout=90) {
		global $context;

		// only consider recent presence records
		$threshold = gmstrftime('%Y-%m-%d %H:%M:%S', time() - $timeout);

		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(visits.edit_date) as oldest_date, MAX(visits.edit_date) as newest_date"
			." FROM ".SQL::table_name('visits')." AS visits"
			." WHERE (visits.edit_date >= '".SQL::escape($threshold)."')";

		$output =& SQL::query_first($query);
		return $output;
	}

	/**
	 * get some statistics for one anchor
	 *
	 * @param the selected anchor (e.g., 'category:12')
	 * @param int maximum age of visit, in seconds
	 * @return the resulting ($count, $min_date, $max_date) array
	 */
	function &stat_for_anchor($anchor, $timeout=90) {
		global $context;

		// sanity check
		if(!$anchor)
			return $output;

		// only consider recent presence records
		$threshold = gmstrftime('%Y-%m-%d %H:%M:%S', time() - $timeout);

		// limit the scope of the request
		$where = "visits.active='Y'";
		if(Surfer::is_teased())
			$where .= " OR visits.active='R'";
		if(Surfer::is_associate())
			$where .= " OR visits.active='N'";

		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(visits.edit_date) as oldest_date, MAX(visits.edit_date) as newest_date"
			." FROM ".SQL::table_name('visits')." AS visits"
			." WHERE (visits.anchor LIKE '".SQL::escape($anchor)."')"
			."	AND (visits.edit_date >= '".SQL::escape($threshold)."')"
			."	AND (".$where.")";

		$output =& SQL::query_first($query);
		return $output;
	}

	/**
	 * remember visit at some page
	 *
	 * @param string the anchor of the visited page (e.g., 'article:12')
	 * @param string level of visibility for this anchor (e.g., 'Y', 'R' or 'N')
	 * @return boolean TRUE on success, FALSE otherwise
	**/
	function track($anchor, $active='Y') {
		global $context;

		// ensure regular operation of the server
		if(!file_exists($context['path_to_root'].'parameters/switch.on'))
			return FALSE;

		// anchor cannot be empty
		if(!$anchor)
			return FALSE;

		// surfer has to be logged
		if(!Surfer::get_id())
			return FALSE;

		// we need more than a HEAD
		if(!isset($_SERVER['REQUEST_METHOD']) || ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
			return FALSE;

		// Firefox pre-fetch is not a real visit
		if(isset($_SERVER['HTTP_X_MOZ']) && ($_SERVER['HTTP_X_MOZ'] == 'prefetch'))
			return FALSE;

		// delete obsoleted presence records as well
		$threshold = gmstrftime('%Y-%m-%d %H:%M:%S', time() - 90);

		// suppress previous record, if any --do not report on error, if any
		$query = "DELETE FROM ".SQL::table_name('visits')
			." WHERE ((anchor LIKE '".SQL::escape($anchor)."') AND (user_id = ".SQL::escape(Surfer::get_id())."))"
			."	OR (edit_date < '".SQL::escape($threshold)."')";
		SQL::query($query, TRUE);

		// update the database; do not report on error
		$query = "INSERT INTO ".SQL::table_name('visits')." SET"
			." anchor='".SQL::escape($anchor)."',"
			." active='".SQL::escape($active)."',"
			." user_id='".SQL::escape(Surfer::get_id())."',"
			." edit_date='".gmstrftime('%Y-%m-%d %H:%M:%S')."'";
		SQL::query($query, TRUE);

		// job done
		return TRUE;
	}

}
?>