<?php
/**
 * the database abstraction for activities
 *
 * An activity relates one object of the database (designated by its reference) and one
 * surfer (retrieved by its id or by its name). It is qualified by an 'action' attribute,
 * which should be a verb (e.g., 'fetch') or a state transition (e.g., 'beginPresentation'),
 * and a 'count' attribute, that can be used to save some integer number.
 *
 * Typical actions recorded:
 * - 'file:123' - 'fetch' - for file download or streaming, in files/fetch.php
 * - 'file:123' - 'upload' - for file upload, in files/edit.php
 * - 'http://...' - 'click' - for clicks on external links, in links/links.php
 * - 'article:123' - 'notify' - plus the count of notifications sent by e-mail, related to this article
 * - 'section:123' - 'notify' - plus the count of notifications sent by e-mail, related to this section
 * - 'file:123' - 'notify' - plus the count of notifications sent by e-mail, related to this file
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
	 * @param string description of the action (e.g., 'post' or 'like')
	 * @return int total count of profiles for this anchor and action
	 */
	public static function count_at($anchor, $action=NULL) {
		global $context;

		// limit the query to one anchor
		$where = "(anchor LIKE '".SQL::escape($anchor)."')";

		// for some actions only
		if(is_array($action))
			$where .= " AND (action IN ('".implode("', '", $action)."'))";
		elseif($action)
			$where .= " AND (action LIKE '".SQL::escape($action)."')";

		// the list of activities
		$query = "SELECT id	FROM ".SQL::table_name('activities')." AS activities"
			." WHERE ".$where;

		// count records
		return SQL::query_count($query);
	}

	/**
	 * count users doing the same thing
	 *
	 * @param string reference of the handled object (e.g., 'article:123')
	 * @param string description of the action (e.g., 'post' or 'like')
	 * @return int total count of profiles for this anchor and action
	 */
	public static function count_users_at($anchor, $action=NULL) {
		global $context;

		// limit the query to one anchor
		$where = "(anchor LIKE '".SQL::escape($anchor)."')";

		// for some actions only
		if(is_array($action))
			$where .= " AND (action IN ('".implode("', '", $action)."'))";
		elseif($action)
			$where .= " AND (action LIKE '".SQL::escape($action)."')";

		// the list of activities
		$query = "SELECT DISTINCT edit_id	FROM ".SQL::table_name('activities')." AS activities"
			." WHERE ".$where." AND (edit_id > 0)";

		// count records
		return SQL::query_count($query);
	}

	/**
	 * list most recent activities
	 *
	 * @param string reference of the handled object (e.g., 'article:123')
	 * @param string description of the action (e.g., 'post' or 'like')
	 * @param int maximum number of activities to list
	 * @param string layout of matching records
	 * @return array list of matching user profiles
	 */
	public static function list_at($anchor, $action=NULL, $count=50, $variant='raw') {
		global $context;

		// limit the query to one anchor
		$where = "(anchor LIKE '".SQL::escape($anchor)."')";

		// for some actions only
		if(is_array($action))
			$where .= " AND (action IN ('".implode("', '", $action)."'))";
		elseif($action)
			$where .= " AND (action LIKE '".SQL::escape($action)."')";

		// the list of activities
		$query = "SELECT * FROM ".SQL::table_name('activities')." AS activities"
			." WHERE ".$where
			." GROUP BY activities.action ORDER BY activities.edit_date DESC LIMIT ".$count;

		// use existing listing facility
		$output =& Activities::list_selected(SQL::query($query), $variant);
		return $output;

	}

	/**
	 * list selected activities
	 *
	 * @param resource result of database query
	 * @param string 'compact', etc or object, i.e., an instance of Layout_Interface
	 * @return NULL on error, else an ordered array with $url => array ($prefix, $label, $suffix, $type, $icon)
	 */
	public static function &list_selected($result, $variant='raw') {
		global $context;

		// no result
		if(!$result) {
			$output = NULL;
			return $output;
		}

		// special layouts
		if(is_object($variant)) {
			$output = $variant->layout($result);
			return $output;
		}

		// one of regular layouts
		switch($variant) {

		case 'comma':
			$text = '';
			while($item = SQL::fetch($result)) {
				if($text)
					$text .= ', ';
				$text .= Skin::build_link(Users::get_url($item['edit_id'], 'view', $item['edit_name']), $item['edit_name'], 'user');
			}
			return $text;

		case 'raw':
		default:
			$items = array();
			while($item = SQL::fetch($result))
				$items[] = $item;
			return $items;

		}

	}

	/**
	 * list users related to some activities
	 *
	 * @param string reference of the handled object (e.g., 'article:123')
	 * @param string description of the action (e.g., 'post' or 'like')
	 * @param int maximum number of users to list
	 * @param string layout of matching records
	 * @return array list of matching user profiles
	 */
	public static function list_users_at($anchor, $action=NULL, $count=50, $variant='raw') {
		global $context;

		// limit the query to one anchor
		$where = "(anchor LIKE '".SQL::escape($anchor)."')";

		// for some actions only
		if(is_array($action))
			$where .= " AND (action IN ('".implode("', '", $action)."'))";
		elseif($action)
			$where .= " AND (action LIKE '".SQL::escape($action)."')";

		// the list of activities
		$query = "SELECT users.* FROM ".SQL::table_name('activities')." AS activities"
			.", ".SQL::table_name('users')." AS users"
			." WHERE ".$where
			." AND (users.id = activities.edit_id)"
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
	 * @param integer optional counter to be saved along the activity
	 * @return boolean TRUE on success, FALSE otherwise
	 *
	**/
	public static function post($anchor, $action='get', $count=1) {
		global $context;

		// update the database; do not report on error
		$query = "INSERT INTO ".SQL::table_name('activities')." SET"
			." action='".SQL::escape($action)."',"
			." anchor='".SQL::escape($anchor)."',"
			." count=".SQL::escape($count).","
			." edit_date='".SQL::escape($context['now'])."',"
			." edit_id='".SQL::escape(Surfer::get_id())."',"
			." edit_name='".SQL::escape(Surfer::get_name())."'";
		SQL::query($query, TRUE);

		// end of job
		return TRUE;
	}

	/**
	 * create table for activities
	 */
	public static function setup() {
		global $context;

		$fields = array();
		$fields['id']			= "MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT";
		$fields['action']		= "VARCHAR(64) DEFAULT 'view' NOT NULL";
		$fields['anchor']		= "VARCHAR(255) DEFAULT '' NOT NULL"; // can also be a web URL
		$fields['count'] 		= "MEDIUMINT UNSIGNED DEFAULT 1 NOT NULL";
		$fields['edit_date']	= "DATETIME";
		$fields['edit_id']		= "MEDIUMINT UNSIGNED DEFAULT 1 NOT NULL";
		$fields['edit_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";

		$indexes = array();
		$indexes['PRIMARY KEY'] 	= "(id)";
		$indexes['INDEX action']	= "(action)";
		$indexes['INDEX anchor']	= "(anchor)";
		$indexes['INDEX edit_date'] = "(edit_date)";
		$indexes['INDEX edit_id'] 	= "(edit_id)";

		$views = array();
		$views[] = "CREATE OR REPLACE VIEW ".SQL::table_name('activities_notifications_per_month')." AS"
			." SELECT"
			."  SUBSTRING(edit_date, 1, 7) AS Month,"
			."  edit_name AS 'Person',"
			."  SUM(count) AS 'Notifications'"
			." FROM ".SQL::table_name('activities')
			." WHERE (action = 'notify')"
			." GROUP BY Month, edit_name";

		return SQL::setup_table('activities', $fields, $indexes, $views);
	}

	/**
	 * get some statistics
	 *
	 * @return the resulting ($count, $min_date, $max_date) array
	 *
	 * @see control/index.php
	 */
	public static function &stat() {
		global $context;

		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(activities.edit_date) as oldest_date, MAX(activities.edit_date) as newest_date"
			." FROM ".SQL::table_name('activities')." AS activities";

		$output = SQL::query_first($query);
		return $output;
	}

}
?>
