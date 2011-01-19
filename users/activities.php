<?php
/**
 * the database abstraction for activities
 *
 * This script remembers user activities in the database.
 *
 * Typical actions recorded:
 * - 'add' - some content has been added to the anchor (new file, new comment, etc.)
 * - 'delete' - ?
 * - 'fetch' - file has been actually accessed
 * - 'edit' - ?
 * - 'view' - anchor has been actually browsed
 *
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Activities {

	/**
	 * count most recent activities
	 *
	 * @param string reference of the handled object (e.g., 'article:123')
	 * @param string description of the action (e.g., 'post' or 'get' or 'delete')
	 * @return int total count of profiles for this anchor and action
	 */
	function count_at($anchor, $action=NULL) {
		global $context;

		// limit the query to one anchor
		$where = "(anchor LIKE '".SQL::escape($anchor)."')";

		// for some actions only
		if(is_array($action))
			$where .= " AND (action IN ('".implode("', '", $action)."'))";
		elseif($action)
			$where .= " AND (action LIKE '".SQL::escape($action)."')";

		// the list of users
		$query = "SELECT users.id	FROM ".SQL::table_name('activities')." AS activities"
			.", ".SQL::table_name('users')." AS users"
			." WHERE (activities.user_id = users.id)"
			."	AND ".$where
			." GROUP BY users.id";

		// count records
		return SQL::query_count($query);
	}

	/**
	 * list most recent activities
	 *
	 * @param string reference of the handled object (e.g., 'article:123')
	 * @param string description of the action (e.g., 'post' or 'get' or 'delete')
	 * @param int maximum number of activities to list
	 * @param string layout of matching records
	 * @return array list of matching user profiles
	 */
	function list_at($anchor, $action=NULL, $count=50, $variant='compact') {
		global $context;

		// limit the query to one anchor
		$where = "(anchor LIKE '".SQL::escape($anchor)."')";

		// for some actions only
		if(is_array($action))
			$where .= " AND (action IN ('".implode("', '", $action)."'))";
		elseif($action)
			$where .= " AND (action LIKE '".SQL::escape($action)."')";

		// the list of users
		$query = "SELECT users.*	FROM ".SQL::table_name('activities')." AS activities"
			.", ".SQL::table_name('users')." AS users"
			." WHERE (activities.user_id = users.id)"
			."	AND ".$where
			." GROUP BY users.id ORDER BY activities.edit_date DESC LIMIT ".$count;

		// use existing listing facility
		$output =& Users::list_selected(SQL::query($query), $variant);
		return $output;

	}

	/**
	 * store a new activity
	 *
	 * @param string reference of the handled object (e.g., 'article:123')
	 * @param string description of the action (e.g., 'post' or 'get' or 'delete')
	 * @param int id of the user involved
	 * @param string date and time of the activity
	 * @return boolean TRUE on success, FALSE otherwise
	 *
	**/
	function post($anchor, $action='get', $user_id=NULL, $date=NULL) {
		global $context;

		// sanity check
		if(!$user_id)
			$user_id = Surfer::get_id();
		if(!$user_id)
			return FALSE;

		// stamp the activity
		if(!$date)
			$date = $context['now'];

		// update the database; do not report on error
		$query = "INSERT INTO ".SQL::table_name('activities')." SET"
			." action='".SQL::escape($action)."',"
			." anchor='".SQL::escape($anchor)."',"
			." edit_date='".SQL::escape($date)."',"
			." user_id=".SQL::escape($user_id);
		SQL::query($query, TRUE);

		// end of job
		return TRUE;
	}

	/**
	 * create table for activities
	 */
	function setup() {
		global $context;

		$fields = array();
		$fields['id']			= "MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT";
		$fields['action']		= "VARCHAR(64) DEFAULT 'view' NOT NULL";
		$fields['anchor']		= "VARCHAR(255) DEFAULT '' NOT NULL"; // can also be a web URL
		$fields['data'] 		= "TEXT";
		$fields['edit_date']	= "DATETIME";
		$fields['user_id']		= "MEDIUMINT NOT NULL";

		$indexes = array();
		$indexes['PRIMARY KEY'] 	= "(id)";
		$indexes['INDEX action']	= "(action)";
		$indexes['INDEX anchor']	= "(anchor)";
		$indexes['INDEX edit_date'] = "(edit_date)";
		$indexes['INDEX user_id'] 	= "(user_id)";

		return SQL::setup_table('activities', $fields, $indexes);
	}

	/**
	 * get some statistics
	 *
	 * @return the resulting ($count, $min_date, $max_date) array
	 *
	 * @see control/index.php
	 */
	function &stat() {
		global $context;

		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(activities.edit_date) as oldest_date, MAX(activities.edit_date) as newest_date"
			." FROM ".SQL::table_name('activities')." AS activities";

		$output =& SQL::query_first($query);
		return $output;
	}

}
?>
