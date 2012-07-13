<?php
/**
 * the database abstraction layer for forms
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Forms {

	/**
	 * clear cache entries for one item
	 *
	 * @param array item attributes
	 */
	public static function clear(&$item) {

		// where this item can be displayed
		$topics = array('forms');

		// clear this page
		if(isset($item['id']))
			$topics[] = 'form:'.$item['id'];

		// clear the cache
		Cache::clear($topics);

	}

	/**
	 * delete one form in the database
	 *
	 * @param int the id of the form to delete
	 * @return boolean TRUE on success, FALSE otherwise
	 *
	 * @see forms/delete.php
	 */
	public static function delete($id) {
		global $context;

		// id cannot be empty
		if(!$id || !is_numeric($id))
			return FALSE;

		// delete related items
		Anchors::delete_related_to('form:'.$id);

		// delete the record in the database
		$query = "DELETE FROM ".SQL::table_name('forms')." WHERE id = ".$id;
		if(SQL::query($query) === FALSE)
			return FALSE;

		// job done
		return TRUE;
	}

	/**
	 * get one form by id
	 *
	 * This function can be used to search for one form entry, either by id
	 * or submitting its nick name.
	 *
	 * @param int the id of the form, or its nick name
	 * @return the resulting $item array, with at least keys: 'id', 'title', etc.
	 */
	public static function &get($id) {
		global $context;

		// sanity check
		if(!$id) {
			$output = NULL;
			return $output;
		}

		// search by id
		if(is_numeric($id))
			$query = "SELECT * FROM ".SQL::table_name('forms')." AS forms"
				." WHERE (forms.id = ".SQL::escape((integer)$id).")";

		// or look for given name of handle
		else
			$query = "SELECT * FROM ".SQL::table_name('forms')." AS forms"
				." WHERE (forms.nick_name LIKE '".SQL::escape($id)."')"
				." ORDER BY edit_date DESC LIMIT 1";

		// do the job
		$output = SQL::query_first($query);
		return $output;
	}

	/**
	 * build a reference to a form
	 *
	 * Depending on parameter '[code]with_friendly_urls[/code]' and on action,
	 * following results can be observed:
	 *
	 * - view - forms/view.php?id=123 or forms/view.php/123 or form-123
	 *
	 * - other - forms/edit.php?id=123 or forms/edit.php/123 or form-edit/123
	 *
	 * @param int the id of the form to handle
	 * @param string the expected action ('view', 'print', 'edit', 'delete', ...)
	 * @param string additional data, such as page name, if any
	 * @return string a normalized reference
	 *
	 * @see control/configure.php
	 */
	public static function get_url($id, $action='view', $name=NULL) {
		global $context;

		// check the target action
		if(!preg_match('/^(delete|edit|view)$/', $action))
			return 'forms/'.$action.'.php?id='.urlencode($id).'&action='.urlencode($name);

		// normalize the link
		return normalize_url(array('forms', 'form'), $action, $id, $name);
	}

	/**
	 * list by title
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see forms/index.php
	 */
	public static function &list_by_title($offset=0, $count=10, $variant='full') {
		global $context;

		// select among active and restricted items
		$where = "forms.active='Y'";
		if(Surfer::is_member())
			$where .= " OR forms.active='R'";
		if(Surfer::is_associate())
			$where .= " OR forms.active='N'";

		// limit the scope of the request
		$query = 'SELECT forms.* FROM '.SQL::table_name('forms').' AS forms'
			.' WHERE ('.$where.')'
			.' ORDER BY forms.title, forms.edit_date DESC LIMIT '.$offset.','.$count;

		// the list of forms
		$output =& Forms::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list named forms
	 *
	 * This function lists all forms with the same nick name.
	 *
	 * This is used by the page locator to offer alternatives when several pages have the same nick names.
	 * It is also used to link a page to twins, these being, most of the time, translations.
	 *
	 * Most matching forms are returned, and we assume that access control to private forms
	 * is postponed to actual access to these forms.
	 *
	 * @param string the nick name
	 * @param int the id of the current page, which will not be listed
	 * @param mixed the layout, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	public static function &list_for_name($name, $exception=NULL, $layout='compact') {
		global $context;

		// gather constraints
		$where = '';

		// avoid exception, if any
		if($exception)
			$where .= " AND (forms.id != ".SQL::escape($exception).")";

		// forms by title -- no more than 100 pages with the same name
		$query = "SELECT forms.*"
			." FROM ".SQL::table_name('forms')." AS forms"
			." WHERE (forms.nick_name LIKE '".SQL::escape($name)."')".$where
			." ORDER BY forms.title LIMIT 100";

		$output =& Forms::list_selected(SQL::query($query), $layout);
		return $output;
	}

	/**
	 * list selected forms
	 *
	 * If variant is provided as a string, the functions looks for a script featuring this name.
	 * E.g., for variant 'compact', the file 'forms/layout_forms_as_compact.php' is loaded.
	 * If no file matches then the default 'forms/layout_forms.php' script is loaded.
	 *
	 * @param resource result of database query
	 * @param string 'full', etc or object, i.e., an instance of Layout_Interface
	 * @return NULL on error, else an ordered array with $url => array ($prefix, $label, $suffix, $type, $icon)
	 */
	public static function &list_selected($result, $variant='compact') {
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

		// no layout yet
		$layout = NULL;

		// separate options from layout name
		$attributes = explode(' ', $variant, 2);

		// instanciate the provided name
		if($attributes[0]) {
			$name = 'layout_forms_as_'.$attributes[0];
			if(is_readable($context['path_to_root'].'forms/'.$name.'.php')) {
				include_once $context['path_to_root'].'forms/'.$name.'.php';
				$layout = new $name;

				// provide parameters to the layout
				if(isset($attributes[1]))
					$layout->set_variant($attributes[1]);

			}
		}

		// use default layout
		if(!$layout) {
			include_once $context['path_to_root'].'forms/layout_forms.php';
			$layout = new Layout_forms();
			$layout->set_variant($variant);
		}

		// do the job
		$output = $layout->layout($result);
		return $output;

	}

	/**
	 * post a new form or an updated form
	 *
	 * @param array an array of fields
	 * @return the id of the new article, or FALSE on error
	 *
	 * @see forms/edit.php
	**/
	public static function post(&$fields) {
		global $context;

		// title cannot be empty
		if(!isset($fields['title']) || !$fields['title']) {
			Logger::error(i18n::s('No title has been provided.'));
			return FALSE;
		}

		// sanity filter
		$fields['title'] = strip_tags($fields['title'], '<br>');

		// anchor cannot be empty
		if(!isset($fields['anchor']) || !$fields['anchor'] || (!$anchor = Anchors::get($fields['anchor']))) {
			Logger::error(i18n::s('No anchor has been found.'));
			return FALSE;
		}

		// set default values for this editor
		Surfer::check_default_editor($fields);

		// set conservative default values
		if(!isset($fields['active']) || !$fields['active'])
			$fields['active'] = 'Y';

		// update the existing record
		if(isset($fields['id'])) {

			// id cannot be empty
			if(!is_numeric($fields['id'])) {
				Logger::error(i18n::s('No item has the provided id.'));
				return FALSE;
			}

			// update the existing record
			$query = "UPDATE ".SQL::table_name('forms')." SET "
				."active='".SQL::escape($fields['active'])."', "
				."anchor='".SQL::escape(isset($fields['anchor']) ? $fields['anchor'] : '')."', "
				."content='".SQL::escape($fields['content'])."', "
				."description='".SQL::escape($fields['description'])."', "
				."introduction='".SQL::escape($fields['introduction'])."', "
				."nick_name='".SQL::escape($fields['nick_name'])."',"
				."title='".SQL::escape($fields['title'])."'";

			// maybe a silent update
			if(!isset($fields['silent']) || ($fields['silent'] != 'Y')) {
				$query .= ", "
				."edit_name='".SQL::escape($fields['edit_name'])."', "
				."edit_id=".SQL::escape($fields['edit_id']).", "
				."edit_address='".SQL::escape($fields['edit_address'])."', "
				."edit_date='".SQL::escape($fields['edit_date'])."'";
			}

			$query .= " WHERE id = ".SQL::escape($fields['id']);

			// actual update
			if(SQL::query($query) === FALSE)
				return FALSE;

		// insert a new record
		} else {

			// always remember the date
			$query = "INSERT INTO ".SQL::table_name('forms')." SET "
				."active='".SQL::escape($fields['active'])."', "
				."anchor='".SQL::escape($fields['anchor'])."', "
				."content='".SQL::escape($fields['content'])."', "
				."description='".SQL::escape($fields['description'])."', "
				."introduction='".SQL::escape($fields['introduction'])."', "
				."nick_name='".SQL::escape($fields['nick_name'])."', "
				."title='".SQL::escape($fields['title'])."', "
				."edit_name='".SQL::escape($fields['edit_name'])."', "
				."edit_id=".SQL::escape($fields['edit_id']).", "
				."edit_address='".SQL::escape($fields['edit_address'])."', "
				."edit_date='".SQL::escape($fields['edit_date'])."'";

			// actual insert
			if(SQL::query($query) === FALSE)
				return FALSE;

			// remember the id of the new item
			$fields['id'] = SQL::get_last_id($context['connection']);

		}

		// clear the cache for forms
		Forms::clear($fields);

		// end of job
		return $fields['id'];
	}

	/**
	 * create or alter tables for forms
	 *
	 * @see control/setup.php
	 */
	public static function setup() {
		global $context;

		$fields = array();
		$fields['id']			= "MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT";
		$fields['active']		= "ENUM('Y','R','N') DEFAULT 'Y' NOT NULL";
		$fields['anchor']		= "VARCHAR(64) DEFAULT 'section:1' NOT NULL";
		$fields['content']		= "TEXT NOT NULL";
		$fields['description']	= "TEXT NOT NULL";
		$fields['edit_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_id']		= "MEDIUMINT DEFAULT 0 NOT NULL";
		$fields['edit_address'] = "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_date']	= "DATETIME";
		$fields['introduction'] = "TEXT NOT NULL";
		$fields['nick_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['title']		= "VARCHAR(128) DEFAULT '' NOT NULL";

		$indexes = array();
		$indexes['PRIMARY KEY'] 	= "(id)";
		$indexes['INDEX title'] 	= "(title)";
		$indexes['INDEX nick_name'] = "(nick_name)";
		$indexes['INDEX active']		= "(active)";
		$indexes['INDEX edit_id']	= "(edit_id)";
		$indexes['INDEX edit_date'] = "(edit_date)";
		$indexes['FULLTEXT INDEX']	= "full_text(title, introduction, description)";

		return SQL::setup_table('forms', $fields, $indexes);
	}

	/**
	 * get some statistics
	 *
	 * @return the resulting ($count, $min_date, $max_date) array
	 */
	public static function &stat() {
		global $context;

		// select among active and restricted items
		$where = "forms.active='Y'";
		if(Surfer::is_member())
			$where .= " OR forms.active='R'";
		if(Surfer::is_associate())
			$where .= " OR forms.active='N'";

		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(edit_date) as oldest_date, MAX(edit_date) as newest_date"
			.' FROM '.SQL::table_name('forms').' AS forms'
			.' WHERE ('.$where.')';

		$output = SQL::query_first($query);
		return $output;
	}

}

// load localized strings
if(is_callable(array('i18n', 'bind')))
	i18n::bind('forms');

?>
