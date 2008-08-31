<?php
/**
 * display one location in situation
 *
 * If several locations have been posted to a single anchor, a navigation bar will be built to jump
 * directly to previous and next neighbours.
 * This is displayed as a sidebar box in the extra panel.
 *
 * YACS displays nearest locations in the extra panel, if any.
 *
 * The extra panel also features top popular referrals in a sidebar box, if applicable.
 *
 * Access is granted only if the surfer is allowed to view the anchor page.
 *
 * Accept following invocations:
 * - view.php/12
 * - view.php?id=12
 *
 * If the anchor for this item specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'locations.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Locations::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor =& Anchors::get($item['anchor']);

// the anchor has to be viewable by this surfer
if(!is_object($anchor) || $anchor->is_viewable())
	$permitted = TRUE;
else
	$permitted = FALSE;

// load the skin, maybe with a variant
load_skin('locations', $anchor);

// the path to this page
if(is_object($anchor))
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'locations/' => i18n::s('Locations') );

// the title of the page
if($item['geo_place_name'])
	$context['page_title'] = $item['geo_place_name'];

// back to the anchor page
if(is_object($anchor))
	$context['page_menu'] = array_merge($context['page_menu'], array( $anchor->get_url() => i18n::s('Back to main page') ));

// commands for associates and editors
if(Surfer::is_associate() || (is_object($anchor) && $anchor->is_editable())) {
	$context['page_menu'] = array_merge($context['page_menu'], array( Locations::get_url($id, 'edit') => i18n::s('Edit') ));
	$context['page_menu'] = array_merge($context['page_menu'], array( Locations::get_url($id, 'delete') => i18n::s('Delete') ));

// commands for the author
} elseif(Surfer::is($item['edit_id'])) {
	$context['page_menu'] = array_merge($context['page_menu'],
		array( Locations::get_url($item['id'], 'edit') => i18n::s('Edit') ));
}

// not found -- help web crawlers
if(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No item has the provided id.'));

// display the location full size
} else {

	// initialize the rendering engine
	Codes::initialize(Locations::get_url($item['id']));

	// information on uploader
	$details = array();
	if(Surfer::is_member() && $item['edit_name'])
		$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

	// page details
	if(is_array($details))
		$context['page_details'] .= '<p class="details">'.ucfirst(implode(', ', $details))."</p>\n";

	// insert anchor prefix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_prefix();

	// geo position
	if($item['geo_position']) {

		// use google map where possible
		if(isset($context['google_api_key']) && $context['google_api_key']) {

			// a place holder for the dynamic map
			$context['text'] .= '<p>'.sprintf(i18n::s('Geographical coordinates: %s'), $item['geo_position'])."</p>\n"
				.'<div id="map" style="border: 1px solid #979797; background-color: #e5e3df; width: 500px; height: 300px; margin-right: auto; margin-top: 2em; margin-bottom: 2em">'."\n"
				.'	<div style="padding: 1em; color: gray">'.i18n::s('Loading...').'</div>'."\n"
				.'</div>'."\n";

			// ensure we have split coordinates
			if(!$item['latitude'] || !$item['longitude'])
				list($item['latitude'], $item['longitude']) = preg_split('/[\s,;]+/', $item['geo_position']);

			// link to anchor page
			$description = '';
			if($anchor =& Anchors::get($item['anchor']))
				$description .= Skin::build_link($anchor->get_url(), $anchor->get_title());

			// item type
			if(strpos($item['anchor'], 'user:') == 0)
				$type = 'user';
			else
				$type = 'other';

			// do the job
			$context['page_footer'] .= '	  <script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key='.$context['google_api_key'].'"'."\n"
				.'			  type="text/javascript"></script>'."\n"
				.'	  <script type="text/javascript">'."\n"
				.'	  //<![CDATA['."\n"
				."\n"
				.'	  var iconBlue = new GIcon();'."\n"
				.'	  iconBlue.image = \'http://labs.google.com/ridefinder/images/mm_20_blue.png\';'."\n"
				.'	  iconBlue.shadow = \'http://labs.google.com/ridefinder/images/mm_20_shadow.png\';'."\n"
				.'	  iconBlue.iconSize = new GSize(12, 20);'."\n"
				.'	  iconBlue.shadowSize = new GSize(22, 20);'."\n"
				.'	  iconBlue.iconAnchor = new GPoint(6, 20);'."\n"
				.'	  iconBlue.infoWindowAnchor = new GPoint(5, 1);'."\n"
				."\n"
				.'	  var iconRed = new GIcon();'."\n"
				.'	  iconRed.image = \'http://labs.google.com/ridefinder/images/mm_20_red.png\';'."\n"
				.'	  iconRed.shadow = \'http://labs.google.com/ridefinder/images/mm_20_shadow.png\';'."\n"
				.'	  iconRed.iconSize = new GSize(12, 20);'."\n"
				.'	  iconRed.shadowSize = new GSize(22, 20);'."\n"
				.'	  iconRed.iconAnchor = new GPoint(6, 20);'."\n"
				.'	  iconRed.infoWindowAnchor = new GPoint(5, 1);'."\n"
				."\n"
				.'	  var customIcons = [];'."\n"
				.'	  customIcons["other"] = iconBlue;'."\n"
				.'	  customIcons["user"] = iconRed;'."\n"
				."\n"
				.'	if (GBrowserIsCompatible()) {'."\n"
				.'	  var map = new GMap2($("map"));'."\n"
				.'	  map.addControl(new GSmallMapControl());'."\n"
				.'	  map.addControl(new GMapTypeControl());'."\n"
				."\n"
				.'	  map.setCenter(new GLatLng(parseFloat("'.$item['latitude'].'"), parseFloat("'.$item['longitude'].'")), 10);'."\n"
				."\n"
				.'	  var point = new GLatLng(parseFloat("'.$item['latitude'].'"), parseFloat("'.$item['longitude'].'"));'."\n"
				.'	  var type = "'.$type.'";'."\n"
				.'	  var html = "'.addcslashes($description, '\'\\"'."\n\r").'";'."\n"
				.'	  var marker = createMarker(point, type, html);'."\n"
				.'	  map.addOverlay(marker);'."\n"
				.'	  marker.openInfoWindowHtml(html);'."\n"
				."\n"
				.'	}'."\n"
				."\n"
				.'	  function createMarker(point, type, html) {'."\n"
				.'		var marker = new GMarker(point, customIcons[type]);'."\n"
				.'		GEvent.addListener(marker, \'click\', function() {'."\n"
				.'		  marker.openInfoWindowHtml(html);'."\n"
				.'		});'."\n"
				.'		return marker;'."\n"
				.'	  }'."\n"
				.'	  //]]>'."\n"
				.'	</script>'."\n";

		// or use a small dynamic image
		} else {
			$map = '';
			if( ($item['geo_position'] || ($item['longitude'] && $item['latitude']))
				&& file_exists($context['path_to_root'].'locations/images/earth_310.jpg'))
				$map .= BR.'<img src="'.$context['url_to_root'].Locations::get_url($item['id'], 'map_on_earth').'" width="310" height="155" alt="'.$item['geo_position'].'" />';

			$context['text'] .= '<p>'.sprintf(i18n::s('Geographical coordinates: %s'), $item['geo_position']).$map."</p>\n";
		}

	}

	// geo country
	if($item['geo_country'])
		$context['text'] .= '<p>'.sprintf(i18n::s('Regional position: %s'), $item['geo_country'])."</p>\n";

	// display the full text
	$context['text'] .= Skin::build_block($item['description'], 'description');

	// insert anchor suffix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_suffix();

	//
	// populate the extra panel
	//

	//
	// the navigation sidebar
	//
	$cache_id = 'locations/view.php?id='.$item['id'].'#navigation';
	if(!$text =& Cache::get($cache_id)) {

		// buttons to display previous and next pages, if any
		if(is_object($anchor)) {
			$neighbours = $anchor->get_neighbours('location', $item);
			$text .= Skin::neighbours($neighbours, 'sidebar');
		}

		// build a nice sidebar box
		if($text)
			$text =& Skin::build_box(i18n::s('Navigation'), $text, 'navigation', 'neighbours');

		// save in cache
		Cache::put($cache_id, $text, 'locations');
	}
	$context['extra'] .= $text;

	//
	// neighbours, if any
	//
	$cache_id = 'locations/view.php?id='.$item['id'].'#neighbours';
	if(!$text =& Cache::get($cache_id)) {

		// locate up to 5 neighbours
		$items = Locations::list_by_distance($item['latitude'], $item['longitude'], 1, COMPACT_LIST_SIZE);
		if(@count($items)) {
			$text .= Skin::build_box(i18n::s('Neighbours'), Skin::build_list($items, 'compact'), 'extra');

		// avoid subsequent queries
		} else
			$text = ' ';


		// save in cache for one hour 60 * 60 = 3600
		Cache::put($cache_id, $text, 'referrals', 3600);

	}
	$context['extra'] .= $text;

	//
	// the referrals, if any, in a sidebar
	//
	if(Surfer::is_associate() || (isset($context['with_referrals']) && ($context['with_referrals'] == 'Y'))) {

		$cache_id = 'locations/view.php?id='.$item['id'].'#referrals';
		if(!$text =& Cache::get($cache_id)) {

			// box content in a sidebar box
			include_once '../agents/referrals.php';
			if($text = Referrals::list_by_hits_for_url($context['url_to_root_parameter'].Locations::get_url($item['id'])))
				$text =& Skin::build_box(i18n::s('Referrals'), $text, 'navigation', 'referrals');

			// save in cache for one hour 60 * 60 = 3600
			Cache::put($cache_id, $text, 'referrals', 3600);

		}

		// in the extra panel
		$context['extra'] .= $text;
	}

}

// render the skin
render_skin();

?>