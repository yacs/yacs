<?php
/**
 * set a new location or update an existing one
 *
 * A button-based editor is used for the description field.
 * It's aiming to introduce most common [link=codes]codes/index.php[/link] supported by YACS.
 *
 * See either [link=Address Map Coordinate (Lat/Long) Finder]http://www.batchgeocode.com/lookup/[/link]
 * or [link=Free Geocoding Service for 22 Countries]http://www.travelgis.com/geocode/Default.aspx[/link]
 * for more information.
 *
 * @link http://geotags.com/ GeoTags Search Engine
 * @link http://www.travelgis.com/geocode/Default.aspx Free Geocoding Service for 22 Countries
 *
 * This script attempts to validate the new or updated article description against a standard PHP XML parser.
 * The objective is to spot malformed or unordered HTML and XHTML tags. No more, no less.
 *
 * Restrictions apply on this page:
 * - anonymous (not-logged) surfer are invited to register to be able to post new locations
 * - members can post new locations, and modify their locations afterwards
 * - associates and editors can do what they want
 *
 * Accepted calls:
 * - edit.php?anchor=&lt;type&gt;:&lt;id&gt;	add a new location for the anchor
 * - edit.php/&lt;id&gt;					modify an existing location
 * - edit.php?id=&lt;id&gt; 			modify an existing location
 *
 * If the anchor for this item specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @author Vincent No&euml;l
 * @author GnapZ
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @tester Pat
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../shared/xml.php';	// input validation
include_once 'locations.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]) && !isset($context['arguments'][1]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Locations::get($id);

// look for the target anchor on item creation
$target_anchor = NULL;
if(isset($_REQUEST['anchor']))
	$target_anchor = $_REQUEST['anchor'];
elseif(isset($context['arguments'][1]))
	$target_anchor = $context['arguments'][0].':'.$context['arguments'][1];
$target_anchor = strip_tags($target_anchor);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']))
	$anchor =& Anchors::get($item['anchor']);
elseif($target_anchor)
	$anchor =& Anchors::get($target_anchor);

// do not always show the edition form
$with_form = FALSE;

// load the skin, maybe with a variant
load_skin('locations', $anchor);

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'locations/' => i18n::s('Locations') );

// the title of the page
if(isset($item['id']))
	$context['page_title'] = i18n::s('Edit a location');
else
	$context['page_title'] = i18n::s('Add a location');

// validate input syntax only if required
if(isset($_REQUEST['option_validate']) && ($_REQUEST['option_validate'] == 'Y')) {
	if(isset($_REQUEST['introduction']))
		xml::validate($_REQUEST['introduction']);
	if(isset($_REQUEST['description']))
		xml::validate($_REQUEST['description']);
}

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// anonymous users are invited to log in or to register
} elseif(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('locations/edit.php?id='.$id.'&anchor='.$_REQUEST['anchor']));

// anyone can modify a location he/she posted previously; associates and editors can modify everything
elseif(isset($item['id']) && ($item['edit_id'] != Surfer::get_id())
	&& !Surfer::is_associate() && is_object($anchor) && !$anchor->is_assigned()) {

	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// an anchor is mandatory
} elseif(!is_object($anchor)) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No anchor has been found.'));

// maybe posts are not allowed here
} elseif(!isset($item['id']) && is_object($anchor) && $anchor->has_option('locked') && !Surfer::is_empowered()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('This page has been locked.'));

// an error occured
} elseif(count($context['error'])) {
	$item = $_REQUEST;
	$with_form = TRUE;

// process uploaded data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// the follow-up page
	$next = $context['url_to_home'].$context['url_to_root'].$anchor->get_url();

	// display the form on error
	if(!$_REQUEST['id'] = Locations::post($_REQUEST)) {
		$item = $_REQUEST;
		$with_form = TRUE;

	// reward the poster for new posts
	} elseif(!isset($item['id'])) {

		// increment the post counter of the surfer
		Users::increment_posts(Surfer::get_id());

		// change page title
		$context['page_title'] = i18n::s('Thank you for your contribution');

		// show location attributes
		$attributes = array();
		if($_REQUEST['geo_place_name'])
			$attributes[] = $_REQUEST['geo_place_name'];
		if($_REQUEST['geo_position'])
			$attributes[] = $_REQUEST['geo_position'];
		if(is_array($attributes))
			$context['text'] .= '<p>'.implode(BR, $attributes)."</p>\n";

		// the action
		$context['text'] .= '<p>'.i18n::s('The location has been appended to the page.').'</p>';

		// touch the related anchor
		$anchor->touch('location:create', $_REQUEST['id'], isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y'));

		// clear cache
		Locations::clear($_REQUEST);

		// list persons that have been notified
		$context['text'] .= Mailer::build_recipients(i18n::s('Persons that have been notified'));

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu = array_merge($menu, array($anchor->get_url() => i18n::s('View the page')));
		$menu = array_merge($menu, array($anchor->get_url('edit') => i18n::s('Edit the page')));
		$follow_up .= Skin::build_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

		// log the submission by a non-associate
		if(!Surfer::is_associate() && is_object($anchor)) {
			$label = sprintf(i18n::c('New location in %s'), strip_tags($anchor->get_title()));
			$description = $_REQUEST['geo_place_name']."\n"
				.sprintf(i18n::c('at %s'), $context['url_to_home'].$context['url_to_root'].Locations::get_url($_REQUEST['id']));
			Logger::notify('locations/edit.php', $label, $description);
		}

	// update of an existing location
	} else {

		// increment the post counter of the surfer
		Users::increment_posts(Surfer::get_id());

		// touch the related anchor
		$anchor->touch('location:update', $_REQUEST['id'], isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y'));

		// clear cache
		Locations::clear($_REQUEST);

		// forward to the view page
		Safe::redirect($context['url_to_home'].$context['url_to_root'].Locations::get_url($_REQUEST['id']));

	}

// display the form on GET
} else
	$with_form = TRUE;

// display the form
if($with_form) {

	// reference the anchor page
	if(is_object($anchor) && $anchor->is_viewable())
		$context['text'] .= '<p>'.sprintf(i18n::s('On page %s'), Skin::build_link($anchor->get_url(), $anchor->get_title()))."</p>\n";

	// the form to edit an location
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form" name="main_form"><div>';

	// form fields
	$fields = array();

	// display info on current version
	if(isset($item['id'])) {

		// the last poster
		if(isset($item['edit_id']) && $item['edit_id']) {
			$text = Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id'])
				.' '.Skin::build_date($item['edit_date']);
			$fields[] = array(i18n::s('Posted by'), $text);
		}

	}

	// geo place name
	$label = i18n::s('Place name');
	$input = '<input type="text" name="geo_place_name" id="geo_place_name" size="40" value="'.encode_field($item['geo_place_name']).'" />';

	// geocoding based on Google service
	if(isset($context['google_api_key']) && $context['google_api_key']) {

		// encode on click
		$input .= '<button type="button" id="encode" onclick="lookupAddress($(\'geo_place_name\').value); return false;">'.encode_field(i18n::s('Encode this address')).'</button>'."\n"
			.'<script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key='.$context['google_api_key'].'" type="text/javascript"></script>'."\n"
			.JS_PREFIX
			.'var geocoder = null;'."\n"
			.'function lookupAddress(address) {'."\n"
			.'	if(!geocoder) {'."\n"
			.'		geocoder = new GClientGeocoder();'."\n"
			.'	}'."\n"
			.'	if(geocoder) {'."\n"
			.'		geocoder.getLatLng('."\n"
			.'			address,'."\n"
			.'			function(point) {'."\n"
			.'				if (!point) {'."\n"
			.'					alert("'.i18n::s('This address has not been found').'");'."\n"
			.'				} else {'."\n"
			.'					$(\'geo_position\').value = point.y.toString() + ", " + point.x.toString();'."\n"
			.'					alert("'.i18n::s('This address has been encoded as').'\n" + point.y.toString() + ", " + point.x.toString());'."\n"
			.'				}'."\n"
			.'			}'."\n"
			.'		)'."\n"
			.'	}'."\n"
			.'}'."\n"
			.JS_SUFFIX."\n";

	}

	$hint = i18n::s('Street address, city, country');
	$fields[] = array($label, $input, $hint);

	// geo location
	$label = i18n::s('Coordinates');
	$input = '<input type="text" id="geo_position" name="geo_position" size="40" value="'.encode_field($item['geo_position']).'" />';
	$hint = i18n::s('Latitude, Longitude -- west longitudes and south latitudes are negative');
	$fields[] = array($label, $input, $hint);

	// geo country
	$label = i18n::s('Country');
	$input = '<input type="text" name="geo_country" size="40" value="'.encode_field($item['geo_country']).'" />';
	$hint = i18n::s('For regional positioning');
	$fields[] = array($label, $input, $hint);

	// the description
	$label = i18n::s('Description');

	// use the editor if possible
	$input = Surfer::get_editor('description', $item['description']);
	$fields[] = array($label, $input);

	// build the form
	$context['text'] .= Skin::build_form($fields);
	$fields = array();

	// associates may decide to not stamp changes -- complex command
	if(Surfer::is_associate() && Surfer::has_all())
		$context['text'] .= '<p><input type="checkbox" name="silent" value="Y" /> '.i18n::s('Do not change modification date of the main page.').'</p>';

	// the submit button
	$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

	// validate page content
	$context['text'] .= '<p><input type="checkbox" name="option_validate" value="Y" checked="checked" /> '.i18n::s('Ensure this post is valid XHTML.').'</p>';

	// transmit the id as a hidden field
	if(isset($item['id']) && $item['id'])
		$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'" />';

	// other hidden fields
	$context['text'] .= '<input type="hidden" name="anchor" value="'.$anchor->get_reference().'" />';

	// end of the form
	$context['text'] .= '</div></form>';

	// the script used for form handling at the browser
	$context['text'] .= JS_PREFIX
		.'	// check that main fields are not empty'."\n"
		.'	func'.'tion validateDocumentPost(container) {'."\n"
		."\n"
		.'		// geo_place_name is mandatory'."\n"
		.'		if(!container.geo_place_name.value) {'."\n"
		.'			alert("'.i18n::s('You must give a name to this location.').'");'."\n"
		.'			Yacs.stopWorking();'."\n"
		.'			return false;'."\n"
		.'		}'."\n"
		."\n"
		.'		// geo_position is mandatory'."\n"
		.'		if(!container.geo_position.value) {'."\n"
		.'			alert("'.i18n::s('Please type some geographical coordinates.').'");'."\n"
		.'			Yacs.stopWorking();'."\n"
		.'			return false;'."\n"
		.'		}'."\n"
		."\n"
		.'		// successful check'."\n"
		.'		return true;'."\n"
		.'	}'."\n"
		."\n"
		.'// set the focus on first form field'."\n"
		.'$("geo_place_name").focus();'."\n"
		.JS_SUFFIX."\n";

	// general help on this form
	$help = '<p>'.i18n::s('Latitude and longitude are numbers separated by a comma and spaces, for example: 47.98481,-71.42124.').'</p>'
		.i18n::s('To find coordinates of any emplacement you can visit following sites:').'<ul>'
		.'<li>'.Skin::build_link(i18n::s('http://www.batchgeocode.com/lookup/'), i18n::s('Address Map Coordinate (Lat/Long) Finder'), 'external').'</li>'
		.'<li>'.Skin::build_link(i18n::s('http://www.travelgis.com/geocode/Default.aspx'), i18n::s('Free Geocoding Service for 22 Countries'), 'external').'</li>'
		.'</ul>'
		.'<p>'.sprintf(i18n::s('%s and %s are available to enhance text rendering.'), Skin::build_link('codes/', i18n::s('YACS codes'), 'help'), Skin::build_link('smileys/', i18n::s('smileys'), 'help')).'</p>';
	$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'boxes', 'help');

}

// render the skin
render_skin();

?>
