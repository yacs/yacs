<?php
/**
 * delete an action
 *
 * This script calls for confirmation, then actually deletes the action.
 * It updates the database, then redirects to the referer URL, or to the index page.
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
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'actions.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item = Actions::get($id);

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

// authenticated users may suppress their own posts
elseif(isset($item['create_id']) && Surfer::is($item['create_id']))
	$permitted = TRUE;

// the default is to deny access
else
	$permitted = FALSE;

// load the skin, maybe with a variant
load_skin('actions', $anchor);

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'actions/' => i18n::s('Actions') );

// the title of the page
if(isset($item['title']))
	$context['page_title'] = sprintf(i18n::s('%s: %s'), i18n::s('Delete'), $item['title']);

// not found
if(!isset($item['id'])) {
	include '../error.php';

// permission denied
} elseif(!$permitted) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// deletion is confirmed
} elseif(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'yes')) {

	// touch the related anchor before actual deletion, since the action has to be accessible at that time
	if(is_object($anchor))
		$anchor->touch('action:delete', $item['id']);

	// back to the anchor page except on error
	if(Actions::delete($item['id'])) {
		Actions::clear($item);
		Safe::redirect($context['url_to_home'].$context['url_to_root'].$anchor->get_url());
	}

// deletion has to be confirmed
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {
	Logger::error(i18n::s('The action has not been confirmed.'));

// ask for confirmation
} else {

	// the anchor icon, if any
	if(is_object($anchor))
		$context['page_image'] = $anchor->get_icon_url();

	// commands
	$menu = array();
	$menu[] = Skin::build_submit_button(i18n::s('Yes, I want to delete this action'), NULL, NULL, 'confirmed');
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
		.JS_SUFFIX;

	// the title of the action
	$context['text'] .= Skin::build_block($item['title'], 'title');

	// display the full text
	$context['text'] .= '<div style="margin: 1em 0;">'.Codes::beautify($item['description']).'</div>'."\n";

	// action status
	switch($item['status']) {

	// on-going -- add buttons to complete or to reject
	case 'O':
		$context['text'] .= '<p>'.i18n::s('Action is on-going').'</p>'."\n";
		break;

	// completed
	case 'C':
		$context['text'] .= '<p>'.i18n::s('Action has been completed').'</p>'."\n";
		break;

	// rejected
	case 'R':
		$context['text'] .= '<p>'.i18n::s('Action has been rejected').'</p>'."\n";
		break;

	}

	// information to members
	if(Surfer::is_member()) {

		// action poster
		$details[] = sprintf(i18n::s('posted by %s %s'), Users::get_link($item['create_name'], $item['create_address'], $item['create_id']), Skin::build_date($item['create_date']));

		// last editor
		if($item['edit_name'] != $item['create_name']) {
			$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));
		}
	}

	// all details
	$context['text'] .= '<p class="details">'.ucfirst(implode(', ', $details))."</p>\n";

	// insert anchor suffix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_suffix();

}

// render the skin
render_skin();

?>
