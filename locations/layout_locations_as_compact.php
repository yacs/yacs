<?php
/**
 * layout locations as a compact list
 *
 * @see locations/locations.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_locations_as_compact extends Layout_interface {

	/**
	 * list locations
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

		// flag locations updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// process all items in the list
		while($item =& SQL::fetch($result)) {

			// url to view the location
			$url = Locations::get_url($item['id']);

			// initialize variables
			$prefix = $suffix = '';

			// flag new locations
			if($item['edit_date'] >= $dead_line)
				$suffix .= NEW_FLAG;

			// build a valid label
			if($item['geo_place_name'])
				$label = Skin::strip($item['geo_place_name'], 10);
			else
				$label = $item['latitude'].', '.$item['longitude'];

			// the distance, if any
			$distance = '';
			if($item['distance'])
				$distance = ' '.round($item['distance'], -1).'&nbsp;km';

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, 'basic', NULL);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>