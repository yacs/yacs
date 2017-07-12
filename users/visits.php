<?php
/**
 * the database abstraction layer for visits
 *
 * This class takes care of visits. It receives probes generated
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
 * Visit are considered obsolete after three days = 259200 seconds
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
	public static function check_user_at_anchor($user_id, $anchor, $timeout=259200) {
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
	public static function list_for_user($user, $count=3, $timeout=259200) {
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
		if(Surfer::is_logged() || Surfer::is_teased())
			$where .= " OR visits.active='R'";
		if(Surfer::is_associate() || Surfer::is_teased())
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
		while($item = SQL::fetch($result)) {

			// identify the visited page
			if(!$anchor = Anchors::get($item['anchor']))
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
	 * list users present at some anchor
	 *
	 * @param string the anchor of the visited page (e.g., 'article:12')
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the maximum size of the returned list
	 * @param string the list variant, if any
	 * @param int maximum age of visit, in seconds
	 * @return array a compact list of user profiles
	 */
	public static function list_users_at_anchor($anchor, $offset=0, $count=30, $layout='compact', $timeout=259200) {
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
			." ORDER BY users.full_name LIMIT ".$offset.','.$count;

		// use existing listing facility
		$output = Users::list_selected(SQL::query($query), $layout);
		return $output;
	}

	/**
	 * visits prove the presence of one user
	 *
	 * @param int id of the visiting user
	 * @param int maximum age of visit, in seconds
	 * @return TRUE if the user is present, FALSE otherwise
	 */
	public static function prove_presence_of($user, $timeout=3600) {
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
	public static function purge_for_user($user_id) {
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
	public static function setup() {
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
	 * remember visit at some page
	 *
	 * @param string the anchor of the visited page (e.g., 'article:12')
	 * @param string level of visibility for this anchor (e.g., 'Y', 'R' or 'N')
	 * @return boolean TRUE on success, FALSE otherwise
	**/
	public static function track($anchor, $active='Y') {
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

		// delete visit records after 3 days = 3*24*60*60 = 259200
		$threshold = gmstrftime('%Y-%m-%d %H:%M:%S', time() - 259200);

		// update the database; do not report on error
		$query = "UPDATE ".SQL::table_name('visits')." SET"
			." active='".SQL::escape($active)."',"
			." edit_date='".gmstrftime('%Y-%m-%d %H:%M:%S')."'"
			." WHERE ((anchor LIKE '".SQL::escape($anchor)."') AND (user_id = ".SQL::escape(Surfer::get_id())."))";
		if(!SQL::query($query, TRUE)) {

			// no update took place; insert a new record in the database; do not report on error
			$query = "INSERT INTO ".SQL::table_name('visits')." SET"
				." anchor='".SQL::escape($anchor)."',"
				." active='".SQL::escape($active)."',"
				." user_id='".SQL::escape(Surfer::get_id())."',"
				." edit_date='".gmstrftime('%Y-%m-%d %H:%M:%S')."'";
			SQL::query($query, TRUE);

		}

		// job done
		return TRUE;
	}

}
?>
