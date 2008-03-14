<?php
/**
 * layout locations
 *
 * This is the default layout for locations.
 *
 * @see locations/index.php
 * @see locations/locations.php
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
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
	 * @param string a variant, if any
	 * @return string the rendered text
	 *
	 * @see skins/layout.php
	**/
	function &layout(&$result, $variant='full') {
		global $context;

		// empty list
		if(!SQL::count($result)) {
			$output = array();
			return $output;
		}

		// load localized strings
		i18n::bind('locations');

		// flag locations updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// we return an array of ($url => $attributes)
		$items = array();

		// process all items in the list
		while($item =& SQL::fetch($result)) {

			// initialize variables
			$prefix = $suffix = $icon = '';

			// the url to view this item
			$url = Locations::get_url($item['id']);

			// reset the rendering engine between items
			if(is_callable(array('Codes', 'initialize')))
				Codes::initialize($url);

			// build a valid label
			if($item['geo_place_name'])
				$label = Skin::strip($item['geo_place_name'], 10);
			else
				$label = $item['latitude'].', '.$item['longitude'];

			// description
			if($item['description'])
				$suffix .= ' '.ucfirst(trim($item['description']));

			// the menu bar for associates and poster
			if(Surfer::is_empowered() || Surfer::is_creator($item['edit_id'])) {
				$menu = array( Locations::get_url($item['id'], 'edit') => i18n::s('Edit'),
					Locations::get_url($item['id'], 'delete') => i18n::s('Delete') );
				$suffix .= ' '.Skin::build_list($menu, 'menu');
			}

			// add a separator
			if($suffix)
				$suffix = ' - '.$suffix;

			// append details to the suffix
			$suffix .= BR.'<span class="details">';

			// details
			$details = array();

			// item poster
			if($variant != 'no_author') {
				if($item['edit_name'])
					$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

			} else
				$details[] = get_action_label($item['edit_action']);

			// show an anchor location
			if(($variant != 'no_anchor') && ($variant != 'no_author') && $item['anchor'] && ($anchor = Anchors::get($item['anchor']))) {
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