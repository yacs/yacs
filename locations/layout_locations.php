<?php
/**
 * layout locations
 *
 * This is the default layout for locations.
 *
 * @see locations/index.php
 * @see locations/locations.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_locations extends Layout_interface {

	/**
	 * list locations
	 *
	 * Recognize following variants:
	 * - 'no_anchor' to list items attached to one particular anchor
	 * - 'no_author' to list items attached to one user prolocation
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	 *
	 * @see layouts/layout.php
	**/
	function layout($result) {
		global $context;

		// empty list
		if(!SQL::count($result)) {
			$output = array();
			return $output;
		}

		// we return an array of ($url => $attributes)
		$items = array();

		// process all items in the list
		while($item = SQL::fetch($result)) {

			// initialize variables
			$prefix = $suffix = $icon = '';

			// the url to view this item
			$url = Locations::get_url($item['id']);

			// build a valid label
			if($item['geo_place_name'])
				$label = Skin::strip($item['geo_place_name'], 10);
			else
				$label = $item['latitude'].', '.$item['longitude'];

			// description
			if($item['description'])
				$suffix .= ' '.ucfirst(trim($item['description']));

			// the menu bar for associates and poster
			if(Surfer::is_empowered() || Surfer::is($item['edit_id'])) {
				$menu = array( Locations::get_url($item['id'], 'edit') => i18n::s('Edit'),
					Locations::get_url($item['id'], 'delete') => i18n::s('Delete') );
				$suffix .= ' '.Skin::build_list($menu, 'menu');
			}

			// add a separator
			if($suffix)
				$suffix = ' - '.$suffix;

			// append details to the suffix
			$suffix .= BR.'<span '.tag::_class('details').'>';

			// details
			$details = array();

			// item poster
			if(isset($this->layout_variant) && ($this->layout_variant != 'no_author')) {
				if($item['edit_name'])
					$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

			} else
				$details[] = Anchors::get_action_label($item['edit_action']);

			// show an anchor location
			if(isset($this->layout_variant) && ($this->layout_variant != 'no_anchor') && $item['anchor'] && ($anchor = Anchors::get($item['anchor']))) {
				$anchor_url = $anchor->get_url();
				$anchor_label = ucfirst($anchor->get_title());
				$details[] = sprintf(i18n::s('in %s'), Skin::build_link($anchor_url, $anchor_label, 'article'));
			}

			// all details
			if(count($details))
				$suffix .= ucfirst(implode(', ', $details))."\n";

			// end of details
			$suffix .= '</span>';

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, 'location', $icon);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>