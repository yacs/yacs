<?php
/**
 * layout dates as links to pages
 *
 * This is used to layout dates within cells of a table calendar.
 *
 * @see dates/dates.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_dates_as_links extends Layout_interface {

	/**
	 * list dates
	 *
	 * @param resource the SQL result
	 * @return array of $url => ($prefix, $label, $suffix, $type, $icon, $date)
	 *
	 * @see skins/layout.php
	**/
	function &layout(&$result) {
		global $context;

		// we return an array of ($url => $attributes)
		$items = array();

		// empty list
		if(!SQL::count($result))
			return $items;

		// process all items in the list
		while($item = SQL::fetch($result)) {

			// the url to use
			$url = Articles::get_permalink($item);

			// initialize variables
			$prefix = $suffix = $icon = '';

			// signal restricted and private dates/articles
			if(!isset($item['publish_date']) || ($item['publish_date'] <= NULL_DATE))
				$prefix .= DRAFT_FLAG;

			// signal restricted and private dates/articles
			if(!isset($item['active']))
				;
			elseif($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// flag new dates/articles
			if($item['edit_date'] >= $context['fresh'])
				$suffix .= NEW_FLAG;

			// build a valid label
			if(isset($item['title']))
				$label = Codes::beautify_title($item['title']);
			else
				$label = Skin::build_date($item['date_stamp'], 'day');

			// maybe this page has a thumbnail image
			$icon = $item['thumbnail_url'];

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, 'date', $icon, $item['date_stamp']);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>