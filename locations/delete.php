<?php
/**
 * delete a location
 *
 * This script calls for confirmation, then actually deletes the link.
 * It updates the database, then redirects to the anchor page.
 *
 * Restrictions apply on this page:
 * - associates and authenticated editors are allowed to move forward
 * - permission is denied if the anchor is not viewable by this surfer
 * - permission is granted if the anchor is the profile of this member
 * - authenticated users may suppress their own posts
 * - else permission is denied
 *
 * Accept following invocations:
 * - delete.php/12
 * - delete.php?id=12
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
$item = Locations::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor = Anchors::get($item['anchor']);

// associates and authenticated editors can do what they want
if(Surfer::is_associate() || (Surfer::is_member() && is_object($anchor) && $anchor->is_assigned()))
	$permitted = TRUE;

// the anchor has to be viewable by this surfer
elseif(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;

// the item is anchored to the profile of this member
elseif(Surfer::is_member() && !strcmp($item['anchor'], 'user:'.Surfer::get_id()))
	$permitted = TRUE;

// authenticated surfers may suppress their own posts --no create_id yet...
elseif(isset($item['edit_id']) && Surfer::is($item['edit_id']))
	$permitted = TRUE;

// the default is to deny access
else
	$permitted = FALSE;

// load the skin, mabe with a variant
load_skin('locations', $anchor);

// the path to this page
if(is_object($anchor))
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'locations/' => i18n::s('Locations') );

// the title of the page
$context['page_title'] = i18n::s('Delete a location');

// not found
if(!isset($item['id'])) {
	include '../error.php';

// permission denied
} elseif(!$permitted) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// deletion is confirmed
} elseif(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'yes')) {

	// touch the related anchor before actual deletion, since the location has to be accessible at that time
	if(is_object($anchor))
		$anchor->touch('location:delete', $item['id']);

	// if no error, back to the anchor or to the index page
	if(Locations::delete($item['id'])) {
		Locations::clear($item);
		if(is_object($anchor))
			Safe::redirect($context['url_to_home'].$context['url_to_root'].$anchor->get_url());
		else
			Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/');
	}

// deletion has to be confirmed
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('The action has not been confirmed.'));

// ask for confirmation
} else {

	// commands
	$menu = array();
	$menu[] = Skin::build_submit_button(i18n::s('Yes, I want to delete this location'), NULL, NULL, 'confirmed');
	if(is_object($anchor))
		$menu[] = Skin::build_link($anchor->get_url(), i18n::s('Cancel'), 'span');

	// the submit button
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
		.Skin::finalize_list($menu, 'menu_bar')
		.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n"
		.'<input type="hidden" name="confirm" value="yes" />'."\n"
		.'</p></form>'."\n";

	// set the focus
	$context['text'] .= JS_PREFIX
		.'// set the focus on first form field'."\n"
		.'$("#confirmed").focus();'."\n"
		.JS_SUFFIX."\n";

	// the geo_place_name of the location
	$context['text'] .= Skin::build_block($item['geo_place_name'], 'title');

	// display the full text
	$context['text'] .= '<div style="margin: 1em 0;">'.Codes::beautify($item['description']).'</div>'."\n";

	// more details
	$details = array();

	// information on uploader
	if(Surfer::is_member() && $item['edit_name'])
		$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

	// the complete details
	if($details)
		$context['text'] .= '<p class="details">'.ucfirst(implode(', ', $details))."</p>\n";

}

// render the skin
render_skin();

?>
