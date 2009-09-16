<?php
/**
 * locate several items on Google Maps
 *
 * This script integrates with Google Map to achieve a nice localized rendering.
 *
 * Accepted calls:
 * - map_on_google.php/&lt;section&gt;/&lt;id&gt;
 * - map_on_google.php?anchor=&lt;section:id&gt;
 * - map_on_google.php/all
 * - map_on_google.php?id=all
 * - map_on_google.php/users
 * - map_on_google.php?id=users
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'locations.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['anchor']))
	$id = $_REQUEST['anchor'];
elseif(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][1]))
	$id = $context['arguments'][0].':'.$context['arguments'][1];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the related anchor, if any
$anchor =& Anchors::get($id);

// the anchor has to be viewable by this surfer
if(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;
else
	$permitted = TRUE;

// load the skin
load_skin('locations');

// the path to this page
if(is_object($anchor))
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'locations/' => i18n::s('Locations') );

// the title of the page
if(is_object($anchor))
	$context['page_title'] = sprintf(i18n::s('Locations related to %s'), $anchor->get_title());
elseif($id == 'users')
	$context['page_title'] = i18n::s('Locations related to community members');

// not found
if(!is_object($anchor) && ($id != 'all') && ($id != 'users'))
	Logger::error(i18n::s('Reference a valid anchor, or all users.'));

// no capability to create an image
elseif(!isset($context['google_api_key']) || !$context['google_api_key'])
	Logger::error(i18n::s('Use the configuration panel for web services to enter your Google API key.'));

// display the map
else {

	// insert anchor prefix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_prefix();

	// get markers
	$items = array();
	switch($id) {
	case 'all':
	default:
		$items = Locations::list_by_date(0, 100, 'raw');
		break;

	case 'users':
		$items = Locations::list_users_by_date(0, 100, 'raw');
		break;

	}

	// integrate with google maps
	$context['text'] .= Locations::map_on_google($items, 2);

	// insert anchor suffix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_suffix();

}

// render the skin
render_skin();

?>