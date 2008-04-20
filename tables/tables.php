<?php
/**
 * the database abstraction layer for tables
 *
 * @todo filter and sort http://www.eldenmalm.com/tableFilterNSort.jsp
 *
 * Tables are mySQL queries saved into the database, used to build dynamic tables on-the-fly.
 * A nice feature to extend yacs with little effort.
 * Also a very powerful tool to be used in conjonction with overlays that update the database directly.
 *
 * @link http://www.456bereastreet.com/archive/200410/bring_on_the_tables/ Bring on the tables
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author Florent
 * @author GnapZ
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Tables {

	/**
	 * check if new tables can be added
	 *
	 * This function returns TRUE if tables can be added to some place,
	 * and FALSE otherwise.
	 *
	 * The function prevents the creation of new tables when:
	 * - the global parameter 'users_without_submission' has been set to 'Y'
	 * - provided item has been locked
	 * - item has some option 'no_tables' that prevents new tables
	 * - the anchor has some option 'no_tables' that prevents new tables
	 *
	 * Then the function allows for new tables when:
	 * - surfer has been authenticated as a valid member
	 * - or parameter 'users_without_teasers' has not been set to 'Y'
	 *
	 * Then, ultimately, the default is not allow for the creation of new
	 * tables.
	 *
	 * @param object an instance of the Anchor interface, if any
	 * @param array a set of item attributes, if any
	 * @return TRUE or FALSE
	 */
	function are_allowed($anchor=NULL, $item=NULL) {
		global $context;

		// tables are prevented in anchor
		if(is_object($anchor) && is_callable(array($anchor, 'has_option')) && $anchor->has_option('no_tables'))
			return FALSE;

		// tables are prevented in item
		if(isset($item['options']) && is_string($item['options']) && preg_match('/\bno_tables\b/i', $item['options']))
			return FALSE;

		// surfer is an associate
		if(Surfer::is_associate())
			return TRUE;

		// submissions have been disallowed
		if(isset($context['users_without_submission']) && ($context['users_without_submission'] == 'Y'))
			return FALSE;

		// surfer has special privileges
		if(Surfer::is_empowered())
			return TRUE;

		// surfer screening
		if(isset($item['active']) && ($item['active'] == 'N') && !Surfer::is_empowered())
			return FALSE;
		if(isset($item['active']) && ($item['active'] == 'R') && !Surfer::is_logged())
			return FALSE;

		// anchor has been locked
		if(is_object($anchor) && is_callable(array($anchor, 'has_option')) && $anchor->has_option('locked'))
			return FALSE;

		// item has been locked
		if(isset($item['locked']) && is_string($item['locked']) && ($item['locked'] == 'Y'))
			return FALSE;

		// teasers are activated
		if(!isset($context['users_without_teasers']) || ($context['users_without_teasers'] != 'Y'))
			return TRUE;

		// the default is to not allow for new tables
		return FALSE;
	}

	/**
	 * build one table
	 *
	 * Accept following variants:
	 * - csv - to provide a downloadable csv page
	 * - inline - to render tables within articles
	 * - simple - the legacy fixed table
	 * - sortable - click on column to sort the row
	 *
	 * @param the id of the table to build
	 * @param string the variant to provide - default is 'simple'
	 * @return a displayable string
	 */
	function build($id, $variant='simple') {
		global $context;

		if(!($table =& Tables::get($id)))
			return NULL;

		if(!$rows =& SQL::query($table['query'])) {
			Skin::error(sprintf(i18n::s('Error in table query %s'), $id).BR.htmlspecialchars($table['query']).BR.SQL::error());
			return NULL;
		}

		$text = '';
		switch($variant) {

		// produce a table readable into MS-Excel
		case 'csv':

			$separator = ";";

			// one row for the title
			if($table['title']) {
				$label = preg_replace('/\s/', ' ', $table['title']);

				// encode in iso8859
				$label = utf8::to_iso8859($label);

				$text .= '"'.$label.'"';
				$text .= "\n";
			}

			// one row for header fields
			if($table['with_number'] == 'Y')
				$text .= '#'.$separator;
			$index = 0;
			while($field = SQL::fetch_field($rows)) {
				if($index++)
					$text .= $separator;
				$label = trim(preg_replace('/\s/', ' ', ucfirst($field->name)));

				// encode in iso8859
				$label = utf8::to_iso8859($label);

				$text .= '"'.$label.'"';
			}
			$text .= "\n";

			// process every table row
			$row_index = 0;
			while($row =& SQL::fetch($rows)) {

				// number lines
				if($table['with_number'] == 'Y')
					$text .= ++$row_index.$separator;

				// one cell at a time
				$index = 0;
				foreach($row as $name => $value) {

					// glue cells
					if($index++)
						$text .= $separator;

					// clean spaces
					$label = trim(preg_replace('/\s/', ' ', $value));

					// encode in iso8859
					$label = utf8::to_iso8859($label);

					// escape quotes to preserve them in the data
					$label = str_replace('"', '""', $label);

					// make a link if this is a reference
					if(($index == 0) && ($table['with_zoom'] == 'Y'))
						$label = $context['url_to_home'].$label;

					// quote data
					$text .= '"'.$label.'"';
				}

				// new line
				$text .= "\n";
			}

			return $text;

		// produce an HTML table
		default:
		case 'inline':
		case 'sortable':

			$index_offset = 0;
			if($table['with_number'] == 'Y')
				$index_offset += 1;

			$text .= Skin::table_prefix('table');

			// the title, with a menu to download the table into Excel
			if($variant == 'inline') {
				$text .= "\t\t".'<caption class="caption">'.$table['title'];
				$item_bar = array();
				if(Surfer::is_logged()) {
					$item_bar = array_merge($item_bar, array(Tables::get_url($id, 'fetch_as_csv') => i18n::s('CSV (Excel)')));
					$item_bar = array_merge($item_bar, array(Tables::get_url($id, 'fetch_as_xml') => i18n::s('XML')));
					$item_bar = array_merge($item_bar, array(Tables::get_url($id) => i18n::s('Zoom')));
				}
				if(Surfer::is_associate()) {
					$item_bar = array_merge($item_bar, array(Tables::get_url($id, 'edit') => i18n::s('Edit')));
				}
				if(count($item_bar)) {
					if($table['title'])
						$text .= BR;
					$text .= Skin::build_list($item_bar, 'menu');
				}
				$text .= "</caption>\n";
			}

			// column headers are clickable links
			$cells = array();
			if($table['with_number'] == 'Y')
				$cells[] = '#';
			$index = 0;
			while($field = SQL::fetch_field($rows)) {
				if(($index++ != 0) || ($table['with_zoom'] != 'Y'))
					$cells[] = ucfirst($field->name);
			}
			$text .= "\t\t".Skin::table_row($cells, 'sortable');

			// the table body
			$count = 0;
			$row_index = 0;
			$zoom = '';
			while($row =& SQL::fetch_row($rows)) {
				$cells = array();
				if($table['with_number'] == 'Y')
					$cells[] = ++$row_index;
				for($index=0; $index < count($row); $index++) {
					if(($index == 0) && ($table['with_zoom'] == 'Y'))
						$zoom = ' '.Skin::build_link($row[$index], MORE_IMG, 'zoom');
					else {
						$cells[] = $row[$index].$zoom;
						$zoom = '';
					}
				}
				$text .= "\t\t".Skin::table_row($cells, $count++);
			}

			$text .= Skin::table_suffix();
			return $text;

		// a simple table
		case 'simple':

			$text .= Skin::table_prefix('table');

			// columns headers
			$index = 0;
			while($field = SQL::fetch_field($rows))
				$cells[] = ucfirst($field->name);
			$text .= Skin::table_row($cells, 'header');

			// other rows
			while($row =& SQL::fetch_row($rows)) {
				$text .= Skin::table_row($row, $count++);
			}

			$text .= Skin::table_suffix();
			return $text;

		// xml table
		case 'xml':

			$text = '';
			while($row =& SQL::fetch($rows)) {
				$text .= '	<record>'."\n";
				foreach($row as $name => $value) {
					$type = preg_replace('/[^a-z0-9]+/i', '_', $name);
					if(preg_match('/^[^a-z]/i', $type))
						$type = '_'.$type;
					$text .= '		<'.$type.'>'
						.preg_replace('/&(?!(amp|#\d+);)/i', '&amp;', utf8::transcode(str_replace(array('left=', 'right='), '', $value)))
						.'</'.$type.'>'."\n";
				}
				$text .= '	</record>'."\n\n";
			}

			return '<?xml version="1.0" encoding="'.$context['charset'].'"?>'."\n"
				.'<table>'."\n".$text.'</table>'."\n";
		}
	}

	/**
	 * delete one table in the database
	 *
	 * @param int the id of the table to delete
	 * @return boolean TRUE on success, FALSE otherwise
	 *
	 * @see tables/delete.php
	 */
	function delete($id) {
		global $context;

		// id cannot be empty
		if(!$id || !is_numeric($id))
			return;

		// delete the record in the database
		$query = "DELETE FROM ".SQL::table_name('tables')." WHERE id = ".SQL::escape($id);
		if(SQL::query($query) === FALSE)
			return FALSE;

		// clear the cache for tables
		Cache::clear(array('tables', 'table:'.$id));

		// job done
		return TRUE;
	}

	/**
	 * delete all tables for a given anchor
	 *
	 * @param the anchor to check
	 *
	 * @see shared/anchors.php
	 */
	function delete_for_anchor($anchor) {
		global $context;

		// clear the cache for tables
		Cache::clear('tables');

		// delete all matching records in the database
		$query = "DELETE FROM ".SQL::table_name('tables')." WHERE anchor LIKE '".SQL::escape($anchor)."'";
		SQL::query($query);
	}

	/**
	 * duplicate all tables for a given anchor
	 *
	 * This function duplicates records in the database, and changes anchors
	 * to attach new records as per second parameter.
	 *
	 * @param string the source anchor
	 * @param string the target anchor
	 * @return int the number of duplicated records
	 *
	 * @see shared/anchors.php
	 */
	function duplicate_for_anchor($anchor_from, $anchor_to) {
		global $context;

		// look for records attached to this anchor
		$count = 0;
		$query = "SELECT * FROM ".SQL::table_name('tables')." WHERE anchor LIKE '".SQL::escape($anchor_from)."'";
		if(($result =& SQL::query($query)) && SQL::count($result)) {

			// the list of transcoded strings
			$transcoded = array();

			// process all matching records one at a time
			while($item =& SQL::fetch($result)) {

				// a new id will be allocated
				$old_id = $item['id'];
				unset($item['id']);

				// target anchor
				$item['anchor'] = $anchor_to;

				// actual duplication
				if($new_id = Tables::post($item)) {

					// more pairs of strings to transcode
					$transcoded[] = array('/\[table='.preg_quote($old_id, '/').'/i', '[table='.$new_id);

					// duplicate elements related to this item
					Anchors::duplicate_related_to('table:'.$old_id, 'table:'.$new_id);

					// stats
					$count++;
				}
			}

			// transcode in anchor
			if($anchor = Anchors::get($anchor_to))
				$anchor->transcode($transcoded);

			// clear the cache for tables
			Cache::clear(array('tables', 'table:'));

		}

		// number of duplicated records
		return $count;
	}

	/**
	 * get one table by id
	 *
	 * @param int the id of the table
	 * @return the resulting $row array, with at least keys: 'id', 'title', etc.
	 */
	function &get($id) {
		global $context;

		// sanity check
		if(!$id) {
			$output = NULL;
			return $output;
		}

		// ensure proper unicode encoding
		$id = (string)$id;
		$id = utf8::to_unicode($id);

		// strip extra text from enhanced ids '3-alfred' -> '3'
		if($position = strpos($id, '-'))
			$id = substr($id, 0, $position);

		// select among available items -- exact match -- 'AS tables' does not work because these are MySQL reserved words
		$query = "SELECT * FROM ".SQL::table_name('tables')
			." WHERE (id LIKE '".SQL::escape($id)."') OR (nick_name LIKE '".SQL::escape($id)."')";
		$output =& SQL::query_first($query);
		return $output;
	}

	/**
	 * build a reference to a table
	 *
	 * Depending on parameter '[code]with_friendly_urls[/code]' and on action,
	 * following results can be observed:
	 *
	 * - view - tables/view.php?id=123 or tables/view.php/123 or table-123
	 *
	 * - other - tables/edit.php?id=123 or tables/edit.php/123 or table-edit/123
	 *
	 * @param int the id of the table to handle
	 * @param string the expected action ('view', 'print', 'edit', 'delete', ...)
	 * @return string a normalized reference
	 *
	 * @see control/configure.php
	 */
	function get_url($id, $action='view') {
		global $context;

		// check the target action
		if(!preg_match('/^(delete|edit|fetch_as_csv|fetch_as_xml|view)$/', $action))
			$action = 'view';

		// normalize the link
		return normalize_url(array('tables', 'table'), $action, $id);
	}

	/**
	 * list newest tables
	 *
	 * This function never lists inactive tables. It is aiming to provide a simple list
	 * of the most recent tables to put in simple boxes.
	 *
	 * To build a simple box of the newest tables in your main index page, just use
	 * the following example:
	 * [php]
	 * // side bar with the list of most recent tables
	 * include_once 'tables/tables.php';
	 * $items = Tables::list_by_date(0, 10, '');
	 * $text = Skin::build_list($items, 'compact');
	 * $context['text'] .= Skin::build_box($title, $text, 'navigation');
	 * [/php]
	 *
	 * You can also display the newest table separately, using Tables::get_newest()
	 * In this case, skip the very first table in the list by using
	 * Tables::list_by_date(1, 10, '')
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1 - default is 0
	 * @param int the number of items to display - default is 10
	 * @param string the list variant, if any - default is 'full'
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 * @see tables/tables.php#list_selected for $variant description
	 */
	function &list_by_date($offset=0, $count=10, $variant='full') {
		global $context;

		// limit the scope of the request
		$query = "SELECT * FROM ".SQL::table_name('tables')
			." ORDER BY edit_date DESC, title LIMIT ".$offset.','.$count;

		// the list of tables
		$output =& Tables::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list newest tables for one anchor
	 *
	 * Example:
	 * [php]
	 * include_once 'tables/tables.php';
	 * $items = Tables::list_by_date_for_anchor(0, 10, '', 'section:12');
	 * $context['text'] .= Skin::build_list($items, 'compact');
	 * [/php]
	 *
	 * @param int the id of the anchor
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_by_date_for_anchor($anchor, $offset=0, $count=20, $variant='no_anchor') {
		global $context;

		// the request
		$query = "SELECT * FROM ".SQL::table_name('tables')
			." WHERE (anchor LIKE '".SQL::escape($anchor)."') "
			." ORDER BY edit_date DESC, title LIMIT ".$offset.','.$count;

		// the list of tables
		$output =& Tables::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list newest tables for one author
	 *
	 * Example:
	 * include_once 'tables/tables.php';
	 * $items = Tables::list_by_date_for_author(0, 10, '', 12);
	 * $context['text'] .= Skin::build_list($items, 'compact');
	 *
	 * @param int the id of the author of the table
	 * @param int the offset from the start of the list; usually, 0 or 1 - default is 0
	 * @param int the number of items to display - default is 20
	 * @param string the list variant, if any - default is 'date'
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 * @see tables/tables.php#list_selected for $variant description
	 */
	function &list_by_date_for_author($author_id, $offset=0, $count=20, $variant='date') {
		global $context;

		// limit the scope of the request
		$query = "SELECT * FROM ".SQL::table_name('tables')
			." WHERE (edit_id LIKE '".SQL::escape($author_id)."') "
			." ORDER BY edit_date DESC, title LIMIT ".$offset.','.$count;

		// the list of tables
		$output =& Tables::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list selected tables
	 *
	 * @param resource result of database query
	 * @variant string 'compact' or nothing
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_selected(&$result, $layout='compact') {
		global $context;

		// no result
		if(!$result) {
			$output = NULL;
			return $output;
		}

		// special layout
		if(is_object($layout)) {
			$output =& $layout->layout($result);
			return $output;
		}

		// a regular layout
		switch($layout) {

		case 'compact':
			include_once $context['path_to_root'].'tables/layout_tables_as_compact.php';
			$variant =& new Layout_tables_as_compact();
			$output =& $variant->layout($result);
			return $output;

		default:
			include_once $context['path_to_root'].'tables/layout_tables.php';
			$variant =& new Layout_tables();
			$output =& $variant->layout($result, $layout);
			return $output;

		}

	}

	/**
	 * post a new table or an updated table
	 *
	 * This function populates the error context, where applicable.
	 *
	 * @param array an array of fields
	 * @return the id of the new table, or FALSE on error
	 *
	 * @see tables/edit.php
	 * @see tables/populate.php
	**/
	function post($fields) {
		global $context;

		// no query
		if(!isset($fields['query']) || !trim($fields['query'])) {
			Skin::error(i18n::s('Please add some SQL query.'));
			return FALSE;
		}

		// no anchor reference
		if(!isset($fields['anchor']) || !trim($fields['anchor'])) {
			Skin::error(i18n::s('No anchor has been found.'));
			return FALSE;
		}

		// get the anchor
		if(!isset($fields['anchor']) || (!$anchor = Anchors::get($fields['anchor']))) {
			Skin::error(i18n::s('No anchor has been found.'));
			return FALSE;
		}

		// set default values
		if(!isset($fields['with_number']) || ($fields['with_number'] != 'Y'))
			$fields['with_number'] = 'N';
		if(!isset($fields['with_zoom']) || ($fields['with_zoom'] != 'Y'))
			$fields['with_zoom'] = 'N';

		// set default values for this editor
		$fields = Surfer::check_default_editor($fields);

		// maybe we have to modify an existing table
		if(isset($fields['id'])) {

			// id cannot be empty
			if(!isset($fields['id']) || !is_numeric($fields['id'])) {
				Skin::error(i18n::s('No item has the provided id.'));
				return FALSE;
			}

			// update the existing record
			$query = "UPDATE ".SQL::table_name('tables')." SET "
				."nick_name='".SQL::escape(isset($fields['nick_name']) ? $fields['nick_name'] : '')."',"
				."title='".SQL::escape(isset($fields['title']) ? $fields['title'] : '')."',"
				."description='".SQL::escape(isset($fields['description']) ? $fields['description'] : '')."',"
				."source='".SQL::escape(isset($fields['source']) ? $fields['source'] : '')."',"
				."query='".SQL::escape($fields['query'])."',"
				."with_number='".SQL::escape(isset($fields['with_number']) ? $fields['with_number'] : '')."',"
				."with_zoom='".SQL::escape(isset($fields['with_zoom']) ? $fields['with_zoom'] : '')."',"
				."edit_name='".SQL::escape($fields['edit_name'])."',"
				."edit_id='".SQL::escape($fields['edit_id'])."',"
				."edit_address='".SQL::escape($fields['edit_address'])."',"
				."edit_date='".SQL::escape($fields['edit_date'])."'"
				." WHERE id = ".SQL::escape($fields['id']);

		// insert a new record
		} else {

			$query = "INSERT INTO ".SQL::table_name('tables')." SET "
				."anchor='".SQL::escape($fields['anchor'])."',"
				."nick_name='".SQL::escape(isset($fields['nick_name']) ? $fields['nick_name'] : '')."',"
				."title='".SQL::escape(isset($fields['title']) ? $fields['title'] : '')."',"
				."description='".SQL::escape(isset($fields['description']) ? $fields['description'] : '')."',"
				."source='".SQL::escape(isset($fields['source']) ? $fields['source'] : '')."',"
				."query='".SQL::escape(isset($fields['query']) ? $fields['query'] : '')."',"
				."with_number='".SQL::escape(isset($fields['with_number']) ? $fields['with_number'] : '')."',"
				."with_zoom='".SQL::escape(isset($fields['with_zoom']) ? $fields['with_zoom'] : '')."',"
				."edit_name='".($fields['edit_name'])."',"
				."edit_id='".($fields['edit_id'])."',"
				."edit_address='".($fields['edit_address'])."',"
				."edit_date='".($fields['edit_date'])."'";

		}

		// actual insert
		if(SQL::query($query) === FALSE)
			return FALSE;

		// remember the id of the new item
		if(!isset($fields['id']))
			$fields['id'] = SQL::get_last_id($context['connection']);

		// clear the cache for tables
		if(isset($fields['id']))
			$topics = array('tables', 'table:'.$fields['id']);
		else
			$topics = 'tables';
		Cache::clear($topics);

		// return the id of the new item
		return $fields['id'];
	}

	/**
	 * create or alter tables for tables
	 */
	function setup() {
		global $context;

		$fields = array();
		$fields['id']			= "MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT";
		$fields['nick_name']	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['anchor']		= "VARCHAR(64) DEFAULT 'section:1' NOT NULL";
		$fields['title']		= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['description']	= "TEXT NOT NULL";
		$fields['source']		= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['query']		= "TEXT NOT NULL";
		$fields['with_number']	= "ENUM('Y','N') DEFAULT 'Y' NOT NULL";
		$fields['with_zoom']	= "ENUM('Y','N') DEFAULT 'Y' NOT NULL";
		$fields['edit_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_id']		= "MEDIUMINT DEFAULT 0 NOT NULL";
		$fields['edit_address'] = "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_date']	= "DATETIME";

		$indexes = array();
		$indexes['PRIMARY KEY'] 	= "(id)";
		$indexes['INDEX anchor']	= "(anchor)";
		$indexes['INDEX edit_date'] = "(edit_date)";
		$indexes['INDEX edit_id']	= "(edit_id)";
		$indexes['INDEX nick_name'] = "(nick_name)";
		$indexes['INDEX title'] 	= "(title(255))";
		$indexes['FULLTEXT INDEX']	= "full_text(title, source, description)";

		return SQL::setup_table('tables', $fields, $indexes);
	}

	/**
	 * get some statistics
	 *
	 * @return the resulting ($count, $min_date, $max_date) array
	 */
	function &stat() {
		global $context;

		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(edit_date) as oldest_date, MAX(edit_date) as newest_date"
			." FROM ".SQL::table_name('tables');

		$output =& SQL::query_first($query);
		return $output;
	}

	/**
	 * get some statistics for one anchor
	 *
	 * @param the selected anchor (e.g., 'article:12')
	 * @return the resulting ($count, $min_date, $max_date) array
	 */
	function &stat_for_anchor($anchor) {
		global $context;

		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(edit_date) as oldest_date, MAX(edit_date) as newest_date "
			." FROM ".SQL::table_name('tables')
			." WHERE anchor LIKE '".SQL::escape($anchor)."'";

		$output =& SQL::query_first($query);
		return $output;
	}

}

// ensure this library has been fully localized
i18n::bind('tables');

?>