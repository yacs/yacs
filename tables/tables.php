<?php
/**
 * the database abstraction layer for tables
 *
 * Tables are MySQL queries saved into the database, used to build dynamic tables on-the-fly.
 * A nice feature to extend yacs with little effort.
 * Also a very powerful tool to be used in conjonction with overlays that update the database directly.
 *
 * @link http://www.456bereastreet.com/archive/200410/bring_on_the_tables/ Bring on the tables
 *
 * @author Bernard Paques
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
	 * @param object an instance of the Anchor interface, if any
	 * @param array a set of item attributes, if any
	 * @return boolean TRUE or FALSE
	 */
	function allow_creation($anchor=NULL, $item=NULL) {
		global $context;

		// tables are prevented in item
		if(isset($item['options']) && is_string($item['options']) && preg_match('/\bno_tables\b/i', $item['options']))
			return FALSE;

		// tables are prevented in anchor
		if(is_object($anchor) && is_callable(array($anchor, 'has_option')) && $anchor->has_option('no_tables'))
			return FALSE;

		// surfer is an associate
		if(Surfer::is_associate())
			return TRUE;

		// the default is to not allow for new tables
		return FALSE;
	}

	/**
	 * build one table
	 *
	 * Accept following variants:
	 * - csv - to provide a downloadable csv page
	 * - json - to provide all values in one column
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

		// split parameters
		$attributes = preg_split("/\s*,\s*/", $id, 3);
		$id = $attributes[0];

		// get the table object
		if(!($table =& Tables::get($id)))
			return NULL;

		// do the SELECT statement
		if(!$rows =& SQL::query($table['query'])) {
			Logger::error(sprintf(i18n::s('Error in table query %s'), $id).BR.htmlspecialchars($table['query']).BR.SQL::error());
			return NULL;
		}

		// build the resulting string
		$text = '';
		switch($variant) {

		// produce a table readable into MS-Excel
		case 'csv':

			// comma separated values
			$separator = ",";

			// one row for the title
			if($table['title']) {
				$label = preg_replace('/\s/', ' ', $table['title']);

				// encode to ASCII
				$label = utf8::to_ascii($label, ' =:/()<>"[]');

				$text .= '"'.$label.'"';
				$text .= "\n";
			}

			// one row for header fields
			$index = 0;
			while($field = SQL::fetch_field($rows)) {
				if($index++)
					$text .= $separator;
				$label = trim(preg_replace('/\s/', ' ', ucfirst($field->name)));

				// encode
				$label = utf8::to_ascii($label, ' =:/()<>[]');

				$text .= '"'.$label.'"';
			}
			$text .= "\n";

			// process every table row
			$row_index = 0;
			while($row =& SQL::fetch($rows)) {

				// one cell at a time
				$index = 0;
				foreach($row as $name => $value) {

					// glue cells
					if($index++)
						$text .= $separator;

					// remove HTML tags
					$value = strip_tags(str_replace('</', ' </', str_replace(BR, ' / ', $value)));

					// clean spaces
					$label = trim(preg_replace('/\s+/', ' ', $value));

					// encode
					$label = utf8::to_ascii($label, ' =:/()<>"[]');

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

		// a JSON set of values
		case 'json':

			// get header labels
			$labels = array();
			while($field = SQL::fetch_field($rows))
				$labels[] = trim(preg_replace('/[^\w]+/', '', ucfirst($field->name)));

			// all items
			$data = array();
			$data['items'] = array();
			while($row =& SQL::fetch_row($rows)) {

				// all rows
				$datum = array();
				$label = FALSE;
				$index = 0;
				$link = NULL;
				foreach($row as $name => $value) {
					$index++;

					// first column is only a link
					if(($index == 1) && ($table['with_zoom'] == 'Y')) {
						$link = $context['url_to_home'].$context['url_to_root'].$value;
						continue;
					}

					// adjust types to not fool the json encoder
					if(preg_match('/^(\+|-){0,1}[0-9]+$/', $value))
						$value = intval($value);
					elseif(preg_match('/^(\+|-){0,1}[0-9\.,]+$/', $value))
						$value = floatval($value);
					elseif(preg_match('/^(true|false)$/i', $value))
						$value = intval($value);

					// ensure we have some label for SIMILE Exhibit
					if(!$label)
						$label = $value;

					// combine first and second columns
					if(($index == 2) && $link)
						$value = Skin::build_link($link, $value, 'basic');

					// save this value
					$datum[ $labels[$name] ] = utf8::to_ascii($value, ' =:/()<>[]"');

				}

				if($label && !in_array($labels, 'label'))
					$datum['label'] = utf8::to_ascii($label);

				// add a tip, if any
				$data['items'][] = $datum;
			}

			include_once $context['path_to_root'].'included/json.php';
			$text .= json_encode2($data);
			return $text;

		// list of facets for SIMILE Exhibit
		case 'json-facets':

			// columns are actual facets
			$facets = array();
			$index = 0;
			while($field = SQL::fetch_field($rows)) {
				$index++;

				// first column is only a link
				if(($index == 1) && ($table['with_zoom'] == 'Y'))
					continue;

				// first column has a link
				if(($index == 2) && ($table['with_zoom'] == 'Y'))
					continue;

				// column is a facet
				$label = '.'.trim(preg_replace('/[^\w]+/', '', ucfirst($field->name)));
				$title = trim(str_replace(',', '', ucfirst($field->name)));
				$facets[] = '<div ex:role="facet" ex:expression="'.$label.'" ex:facetLabel="'.$title.'"></div>';

				// only last columns can be faceted
				if(count($facets) > 7)
					array_shift($facets);
			}

			// reverse facet order
			$facets = array_reverse($facets);

			// job done
			$text = join("\n", $facets);
			return $text;

		// list of columns for SIMILE Exhibit
		case 'json-labels':

			// get header labels
			$labels = array();
			$index = 0;
			while($field = SQL::fetch_field($rows)) {
				$index++;

				// first column is only a link
				if(($index == 1) && ($table['with_zoom'] == 'Y'))
					continue;

				// column id
				$labels[] = '.'.trim(preg_replace('/[^\w]+/', '', ucfirst($field->name)));

				// limit the number of columns put on screen
				if(count($labels) >= 7)
					break;
			}

			// job done
			$text = join(', ', $labels);
			return $text;

		// titles of columns for SIMILE Exhibit
		case 'json-titles':

			// get header labels
			$labels = array();
			$index = 0;
			while($field = SQL::fetch_field($rows)) {
				$index++;

				// first column is only a link
				if(($index == 1) && ($table['with_zoom'] == 'Y'))
					continue;

				// column header
				$labels[] = trim(str_replace(',', '', ucfirst($field->name)));
			}

			$text = join(', ', $labels);
			return $text;

		// produce an HTML table
		default:
		case 'inline':
		case 'sortable':

			// a tabke with a grid
			$text .= Skin::table_prefix('grid');

			// the title, with a menu to download the table into Excel
			if($variant == 'inline') {
				$item_bar = array();
				$item_bar += array(Tables::get_url($id) => $table['title']);
				$item_bar += array(Tables::get_url($id, 'fetch_as_csv') => 'CSV (Excel)');

				if(Surfer::is_associate())
					$item_bar += array(Tables::get_url($id, 'edit') => i18n::s('edit'));

				if(count($item_bar))
					$text .= '<caption>'.Skin::build_list($item_bar, 'menu')."</caption>\n";
			}

			// column headers are clickable links
			$cells = array();
			$index = 0;
			while($field = SQL::fetch_field($rows)) {
				if(($index++ != 0) || ($table['with_zoom'] != 'Y'))
					$cells[] = ucfirst($field->name);
			}
			$text .= "\t\t".Skin::table_row($cells, 'sortable');

			// the table body
			$count = 0;
			$row_index = 0;
			while($row =& SQL::fetch_row($rows)) {
				$cells = array();
				$link = '';
				for($index=0; $index < count($row); $index++) {
					if(($index == 0) && ($table['with_zoom'] == 'Y'))
						$link = $row[$index];
					elseif($link) {
						$cells[] = Skin::build_link($link, $row[$index]);
						$link = '';
					} else
						$cells[] = $row[$index];
				}
				$text .= "\t\t".Skin::table_row($cells, $count++);
			}

			$text .= Skin::table_suffix();
			return $text;

		// adapted to open chart flash
		case 'chart':

			// get title for y series
			$y_title = $y2_title = $y3_title = NULL;
			$y_index = $y2_index = $y3_index = 0;
			$index = 0;
			while($field = SQL::fetch_field($rows)) {

				// time will be used for x labels
				if(($index == 0) && ($table['with_zoom'] == 'T'))
					;

				// web links do not make good numbers
				elseif(($index == 0) && ($table['with_zoom'] == 'Y'))
					;

				// fill one title at a time
				elseif(!$y_title) {
					$y_title = '"'.ucfirst($field->name).'"';
					$y_index = $index;
				} elseif(!$y2_title) {
					$y2_title = '"'.ucfirst($field->name).'"';
					$y2_index = $index;
				} elseif(!$y3_title) {
					$y3_title = '"'.ucfirst($field->name).'"';
					$y3_index = $index;
					break;
				}
				$index++;
			}

			// process every table row
			$x_labels = array();
			$y_values = array();
			$y2_values = array();
			$y3_values = array();
			$y_min = $y_max = NULL;
			$count = 1;
			while($row =& SQL::fetch($rows)) {

				// one cell at a time
				$index = 0;
				foreach($row as $name => $value) {

					// clean spaces
					$label = trim(preg_replace('/\s/', ' ', $value));

					// encode in iso8859
					$label = utf8::to_iso8859($label);

					// escape quotes to preserve them in the data
					$label = str_replace('"', '""', $label);

					// quote data
					if(preg_match('/-*[^0-9\,\.]/', $label))
						$label = '"'.$label.'"';

					// x labels
					if($index == 0) {
						if($table['with_zoom'] == 'T')
							array_unshift($x_labels, $label);
						else
							$x_labels[] = $count++;

					// y value
					} elseif($index == $y_index) {
						if($table['with_zoom'] == 'T')
							array_unshift($y_values, $label);
						else
							$y_values[] = $label;

						if(!isset($y_min) || (intval($label) < $y_min))
							$y_min = intval($label);

						if(!isset($y_max) || (intval($label) > $y_max))
							$y_max = intval($label);

					// y2 value
					} elseif($index == $y2_index) {
						if($table['with_zoom'] == 'T')
							array_unshift($y2_values, $label);
						else
							$y_values[] = $label;

						if(!isset($y_min) || (intval($label) < $y_min))
							$y_min = intval($label);

						if(!isset($y_max) || (intval($label) > $y_max))
							$y_max = intval($label);

					// y3 value
					} elseif($index == $y3_index) {
						if($table['with_zoom'] == 'T')
							array_unshift($y3_values, $label);
						else
							$y_values[] = $label;

						if(!isset($y_min) || (intval($label) < $y_min))
							$y_min = intval($label);

						if(!isset($y_max) || (intval($label) > $y_max))
							$y_max = intval($label);

						// we won't process the rest
						break;
					}

					// next column
					$index++;
				}

			}


			// y minimum
			if($y_min > 0)
				$y_min = 0;

			// y maximum
			$y_max = strval($y_max);
			if(strlen($y_max) == 1)
				$y_max = 10;
			elseif(strlen($y_max) == 2)
				$y_max = (intval(substr($y_max, 0, 1))+1)*10;
			elseif(strlen($y_max) == 3)
				$y_max = (intval(substr($y_max, 0, 2))+1)*10;
			else
				$y_max = strval(intval(substr($y_max, 0, 2))+1).substr('0000000000000000000000000000000000000000000000000000', 0, strlen($y_max)-2);

			// data series
			$elements = array();
			if(count($y_values))
				$elements[] = '{ "type":"bar_glass", "colour":"#BF3B69", "values": [ '.join(',', $y_values).' ], "text": '.$y_title.', "font-size": 12 }';

			if(count($y2_values))
				$elements[] = '{ "type": "line", "width": 1, "colour": "#5E4725", "values": [ '.join(',', $y2_values).' ], "text": '.$y2_title.', "font-size": 12 }';

			if(count($y3_values))
				$elements[] = '{ "type":"bar_glass", "colour":"#5E0722", "values": [ '.join(',', $y3_values).' ], "text": '.$y3_title.', "font-size": 12 }';

			// the full setup
			$text = '{ "elements": [ '.join(',', $elements).' ], "x_axis": { "offset": false, "steps": 1, "labels": { "steps": 3, "rotate": 310, "labels": [ '.join(',', $x_labels).' ] } }, "y_axis": { "min": '.$y_min.', "max": '.$y_max.' } }';

			return $text;

		// first number
		case 'column':

			// comma separated values
			$separator = ",";

			// process every table row
			while($row =& SQL::fetch($rows)) {

				// not always the first column
				$index = 0;
				foreach($row as $name => $value) {
					$index++;

					// skip dates and links
					if(($index == 1) && ($table['with_zoom'] != 'N'))
						continue;

					// glue cells
					if($text)
						$text .= $separator;

					// clean spaces
					$label = trim(preg_replace('/\s/', ' ', $value));

					// encode in iso8859
					$label = utf8::to_iso8859($label);

					// escape quotes to preserve them in the data
					$label = str_replace('"', '""', $label);

					// quote data
					if(preg_match('/[^a-zA-Z0-9\,\.\-_]/', $label))
						$text .= '"'.$label.'"';
					else
						$text .= $label;

					// only first column
					break;
				}

			}

			return $text;

		// produce a raw table
		case 'raw':

			// comma separated values
			$separator = ",";

			// process every table row
			while($row =& SQL::fetch($rows)) {

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
					if(preg_match('/[^a-zA-Z0-9\-_]/', $label))
						$text .= '"'.$label.'"';
					else
						$text .= $label;
				}

				// new line
				$text .= "\n";
			}

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
				$text .= '	<item>'."\n";
				foreach($row as $name => $value) {
					$type = preg_replace('/[^a-z0-9]+/i', '_', $name);
					if(preg_match('/^[^a-z]/i', $type))
						$type = '_'.$type;
					$text .= '		<'.$type.'>'
						.preg_replace('/&(?!(amp|#\d+);)/i', '&amp;', utf8::transcode(str_replace(array('left=', 'right='), '', $value)))
						.'</'.$type.'>'."\n";
				}
				$text .= '	</item>'."\n\n";
			}

			return '<?xml version="1.0" encoding="'.$context['charset'].'"?>'."\n"
				.'<items>'."\n".$text.'</items>'."\n";
		}
	}

	/**
	 * clear cache entries for one item
	 *
	 * @param array item attributes
	 */
	function clear(&$item) {

		// where this item can be displayed
		$topics = array('tables');

		// clear anchor page
		if(isset($item['anchor']))
			$topics[] = $item['anchor'];

		// clear this page
		if(isset($item['id']))
			$topics[] = 'table:'.$item['id'];

		// clear the cache
		Cache::clear($topics);

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
				if($item['id'] = Tables::post($item)) {

					// more pairs of strings to transcode
					$transcoded[] = array('/\[table='.preg_quote($old_id, '/').'/i', '[table='.$item['id']);
					$transcoded[] = array('/\[table.bars='.preg_quote($old_id, '/').'/i', '[table.bars='.$item['id']);
					$transcoded[] = array('/\[table.chart='.preg_quote($old_id, '/').'/i', '[table.chart='.$item['id']);
					$transcoded[] = array('/\[table.filter='.preg_quote($old_id, '/').'/i', '[table.filter='.$item['id']);
					$transcoded[] = array('/\[table.line='.preg_quote($old_id, '/').'/i', '[table.line='.$item['id']);

					// duplicate elements related to this item
					Anchors::duplicate_related_to('table:'.$old_id, 'table:'.$item['id']);

					// stats
					$count++;
				}
			}

			// transcode in anchor
			if($anchor =& Anchors::get($anchor_to))
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
		$id = utf8::encode($id);

//		// strip extra text from enhanced ids '3-alfred' -> '3'
//		if($position = strpos($id, '-'))
//			$id = substr($id, 0, $position);

		// search by id
		if(is_numeric($id))
			$query = "SELECT * FROM ".SQL::table_name('tables')." AS tables"
				." WHERE (tables.id = ".SQL::escape((integer)$id).")";

		// or look for given name of handle
		else
			$query = "SELECT * FROM ".SQL::table_name('tables')." AS tables"
				." WHERE (tables.nick_name LIKE '".SQL::escape($id)."')"
				." ORDER BY edit_date DESC LIMIT 1";

		// do the job
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
		if(!preg_match('/^(delete|edit|fetch_as_csv|fetch_as_json|fetch_as_raw|fetch_as_xml|view)$/', $action))
			return 'tables/'.$action.'.php?id='.urlencode($id);

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
			." WHERE (edit_id = ".SQL::escape($author_id).")"
			." ORDER BY edit_date DESC, title LIMIT ".$offset.','.$count;

		// the list of tables
		$output =& Tables::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list selected tables
	 *
	 * If variant is provided as a string, the functions looks for a script featuring this name.
	 * E.g., for variant 'compact', the file 'tables/layout_tables_as_compact.php' is loaded.
	 * If no file matches then the default 'tables/layout_tables.php' script is loaded.
	 *
	 * @param resource result of database query
	 * @variant string 'compact' or nothing
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_selected(&$result, $variant='compact') {
		global $context;

		// no result
		if(!$result) {
			$output = NULL;
			return $output;
		}

		// special layout
		if(is_object($variant)) {
			$output =& $variant->layout($result);
			return $output;
		}

		// a regular layout
		switch($variant) {

		case 'compact':
			include_once $context['path_to_root'].'tables/layout_tables_as_compact.php';
			$layout = new Layout_tables_as_compact();
			$output =& $layout->layout($result);
			return $output;

		default:
			include_once $context['path_to_root'].'tables/layout_tables.php';
			$layout = new Layout_tables();
			$layout->set_variant($variant);
			$output =& $layout->layout($result);
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
	function post(&$fields) {
		global $context;

		// no query
		if(!isset($fields['query']) || !trim($fields['query'])) {
			Logger::error(i18n::s('Please add some SQL query.'));
			return FALSE;
		}

		// no anchor reference
		if(!isset($fields['anchor']) || !trim($fields['anchor'])) {
			Logger::error(i18n::s('No anchor has been found.'));
			return FALSE;
		}

		// get the anchor
		if(!isset($fields['anchor']) || (!$anchor =& Anchors::get($fields['anchor']))) {
			Logger::error(i18n::s('No anchor has been found.'));
			return FALSE;
		}

		// set default values
		if(!isset($fields['with_zoom']))
			$fields['with_zoom'] = 'N';

		// set default values for this editor
		Surfer::check_default_editor($fields);

		// maybe we have to modify an existing table
		if(isset($fields['id'])) {

			// id cannot be empty
			if(!isset($fields['id']) || !is_numeric($fields['id'])) {
				Logger::error(i18n::s('No item has the provided id.'));
				return FALSE;
			}

			// update the existing record
			$query = "UPDATE ".SQL::table_name('tables')." SET "
				."nick_name='".SQL::escape(isset($fields['nick_name']) ? $fields['nick_name'] : '')."',"
				."title='".SQL::escape(isset($fields['title']) ? $fields['title'] : '')."',"
				."description='".SQL::escape(isset($fields['description']) ? $fields['description'] : '')."',"
				."source='".SQL::escape(isset($fields['source']) ? $fields['source'] : '')."',"
				."query='".SQL::escape($fields['query'])."',"
				."with_zoom='".SQL::escape(isset($fields['with_zoom']) ? $fields['with_zoom'] : '')."',"
				."edit_name='".SQL::escape($fields['edit_name'])."',"
				."edit_id=".SQL::escape($fields['edit_id']).","
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
				."with_zoom='".SQL::escape(isset($fields['with_zoom']) ? $fields['with_zoom'] : '')."',"
				."edit_name='".($fields['edit_name'])."',"
				."edit_id=".($fields['edit_id']).","
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
		$fields['with_zoom']	= "ENUM('Y','T','N') DEFAULT 'N' NOT NULL";
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

		$text = SQL::setup_table('tables', $fields, $indexes);
		return $text;
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

// load localized strings
if(is_callable(array('i18n', 'bind')))
	i18n::bind('tables');

?>
