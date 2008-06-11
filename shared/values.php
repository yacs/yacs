<?php
/**
 * store name-value pairs
 *
 * This script allows YACS to remember data over time.
 * Any other module may use it freely by calling member functions as described in the following example.
 *
 * For example, to store some value you would use:
 * [php]
 * // remember value for some id
 * Values::set('foo.tick', $processed_items);
 *
 * ...
 *
 * // retrieve the value afterwards
 * $value = Values::get('foo.tick');
 * [/php]
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Values {

	/**
	 * suppress a value
	 *
	 * @param string the id of the value to be removed
	 * @return boolean TRUE on success, FALSE otherwise
	 */
	function delete($id) {
		global $context;

		// database update; do not report on any error
		$query = "DELETE FROM ".SQL::table_name('values')." WHERE id LIKE '".SQL::escape($id)."'";
		if(SQL::query($query) === FALSE)
			return FALSE;

		// job done
		return TRUE;

	}

	/**
	 * retrieve some value by name
	 *
	 * @param string the id of the value to be retrieved
	 * @param string an optional default value
	 * @return string cached information, or NULL if the no accurate information is available for this id
	 */
	function &get($id, $default_value=NULL) {
		global $context;

		// get one attribute of the record
		if($item = Values::get_record($id, $default_value))
			$item = $item['value'];

		return $item;
	}

	/**
	 * retrieve one record by name
	 *
	 * You can access $result['value'] and $result['edit_date'] after one single
	 * fetch.
	 *
	 * @param string the id of the value to be retrieved
	 * @param string an optional default value
	 * @return string cached information, or NULL if the no accurate information is available for this id
	 */
	function &get_record($id, $default_value=NULL) {
		global $context;

		// sanity check
		if(!$id) {
			$output = NULL;
			return $output;
		}

		// select among available items -- exact match
		$query = "SELECT * FROM ".SQL::table_name('values')
			." WHERE id LIKE '".SQL::escape($id)."'";

		// do not report on error
		if(!$item =& SQL::query_first($query, TRUE)) {
			return $item;
		}

		// default value
		if(!isset($item['value']) || !$item['value'])
			$item['value'] = $default_value;

		// we have a valid item
		return $item;
	}

	/**
	 * retrieve date of last value update
	 *
	 * @param string the id of the value to be retrieved
	 * @return string modification date, or '0000-00-00'
	 */
	function &get_stamp($id) {
		global $context;

		// get one attribute of the record
		if($item = Values::get_record($id))
			$item = $item['edit_date'];
		else
			$item = NULL_DATE;

		return $item;
	}

	/**
	 * set or change some value
	 *
	 * @param string the id of this item
	 * @param string the related value
	 */
	function set($id, $value='') {
		global $context;

		// suppress existing content, if any
		$query = "DELETE FROM ".SQL::table_name('values')
			." WHERE id LIKE '".SQL::escape($id)."'";

		// do not report on error
		SQL::query($query, TRUE);

		// update the database
		$query = "INSERT INTO ".SQL::table_name('values')." SET"
			." id='".SQL::escape($id)."',"
			." value='".SQL::escape($value)."',"
			." edit_date='".SQL::escape(gmstrftime('%Y-%m-%d %H:%M:%S'))."'";

		// do not report on error
		SQL::query($query, TRUE);
	}

	/**
	 * create table for values
	 */
	function setup() {
		global $context;

		$fields = array();
		$fields['id']			= "VARCHAR(255) DEFAULT '' NOT NULL";						// up to 255 chars
		$fields['value']		= "MEDIUMTEXT NOT NULL";							// up to 16M chars
		$fields['edit_date']	= "DATETIME";

		$indexes = array();
		$indexes['PRIMARY KEY'] 		= "(id)";
		$indexes['INDEX edit_date'] 	= "(edit_date)";

		return SQL::setup_table('values', $fields, $indexes);
	}
}

?>