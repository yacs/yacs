<?php
/**
 * layout tables
 *
 * This is the default layout for tables.
 *
 * @see tables/index.php
 * @see tables/tables.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_tables_as_compact extends Layout_interface {

	/**
	 * list tables
	 *
	 * Recognize following variants:
	 * - 'no_anchor' to list items attached to one particular anchor
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	 *
	 * @see layouts/layout.php
	**/
	function layout($result) {
		global $context;

		// we return an array of ($url => $attributes)
		$items = array();

		// empty list
		if(!SQL::count($result))
			return $items;

		// process all items in the list
		while($item = SQL::fetch($result)) {

			// initialize variables
			$prefix = $suffix = $icon = '';

			// the url to view this item
			$url = Tables::get_url($item['id']);

			// flag tables created or updated very recently
			if($item['create_date'] >= $context['fresh'])
				$suffix .= NEW_FLAG;
			elseif($item['edit_date'] >= $context['fresh'])
				$suffix .= UPDATED_FLAG;

			$label = Skin::strip($item['title'], 10);

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, 'table', $icon);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>