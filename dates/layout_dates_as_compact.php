<?php
/**
 * layout dates as a compact list
 *
 * @see dates/dates.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_dates_as_compact extends Layout_interface {

	/**
	 * list dates
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	 *
	 * @see skins/layout.php
	**/
	function &layout(&$result) {
		global $context;

		// empty list
		if(!SQL::count($result)) {
			$output = array();
			return $output;
		}

		// we return an array of ($url => $attributes)
		$items = array();

		// flag dates updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// process all items in the list
		while($item =& SQL::fetch($result)) {

			// url to view the date
			$url = Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']);

			// initialize variables
			$prefix = $suffix = '';

			// flag new dates
			if($item['edit_date'] >= $dead_line)
				$suffix .= NEW_FLAG;

			// build a valid label
			$label = Codes::beautify_title($item['title']);

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, 'basic', NULL);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>