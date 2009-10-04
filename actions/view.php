<?php
/**
 * display one action in situation
 *
 * If several actions have been posted to a single anchor, a navigation bar will be built to jump
 * directly to previous and next neighbours.
 * This is displayed as a sidebar box in the extra panel.
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
 * @tester GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'actions.php';
include_once '../links/links.php';	// target url

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Actions::get($id);

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
	$context['page_title'] = $item['title'];

// not found -- help web crawlers
if(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Actions::get_url($item['id'])));

	// permission denied to authenticated user
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// display the action
} else {

	// initialize the rendering engine
	Codes::initialize(Actions::get_url($item['id']));

	// insert anchor prefix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_prefix();

	// display the full text
	$context['text'] .= Skin::build_block($item['description'], 'description');

	// related link
	if($item['target_url']) {

		// transform local references, if any
		$attributes = Links::transform_reference($item['target_url']);

		if(isset($attributes[1]))
			$title = $attributes[1];
		else
			$title = $item['target_url'];

		if(isset($attributes[0]))
			$link = Skin::build_link($context['url_to_root'].$attributes[0], $title, 'basic');
		else
			$link = $item['target_url'];

		$context['text'] .= sprintf(i18n::s('%s: %s'), i18n::s('Target address'), $link);
	}

	// action status
	switch($item['status']) {

	// on-going -- add buttons to complete or to reject
	case 'O':
		$context['text'] .= '<p>'.i18n::s('Action is on-going');

		// let action owner and associates change action status
		if(($item['anchor'] == 'user:'.Surfer::get_id()) || Surfer::is_associate())
			$context['text'] .= ' '.Skin::build_link(Actions::get_url($item['id'], 'accept', 'completed'), i18n::s('Completed'), 'button')
				.' '.Skin::build_link(Actions::get_url($item['id'], 'accept', 'rejected'), i18n::s('Rejected'), 'button');

		$context['text'] .= '</p>'."\n";
		break;

	// completed
	case 'C':
		$context['text'] .= '<p>'.i18n::s('Action has been completed');

		// let action owner and associates change action status
		if(($item['anchor'] == 'user:'.Surfer::get_id()) || Surfer::is_associate())
			$context['text'] .= ' '.Skin::build_link(Actions::get_url($item['id'], 'accept', 'on-going'), i18n::s('Reset'), 'button');

		$context['text'] .= '</p>'."\n";
		break;

	// rejected
	case 'R':
		$context['text'] .= '<p>'.i18n::s('Action has been rejected');

		// let action owner and associates change action status
		if(($item['anchor'] == 'user:'.Surfer::get_id()) || Surfer::is_associate())
			$context['text'] .= ' '.Skin::build_link(Actions::get_url($item['id'], 'accept', 'on-going'), i18n::s('Reset'), 'button');

		$context['text'] .= '</p>'."\n";
		break;

	}

	$details = array();

	// information to members
	if(Surfer::is_member()) {

		// action poster
		$details[] = sprintf(i18n::s('posted by %s %s'), Users::get_link($item['create_name'], $item['create_address'], $item['create_id']), Skin::build_date($item['create_date']));

		// last editor
		if($item['edit_name'] != $item['create_name'])
			$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

	}

	// all details
	if(count($details))
		$context['text'] .= '<p class="details">'.ucfirst(implode(', ', $details))."</p>\n";

	// insert anchor suffix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_suffix();

	// back to the anchor page
	if(is_object($anchor) && $anchor->is_viewable()) {
		$menu = array(Skin::build_link($anchor->get_url(), i18n::s('Back to main page'), 'button'));
		$context['text'] .= Skin::build_block(Skin::finalize_list($menu, 'menu_bar'), 'bottom');
	}

	//
	// extra panel
	//

	// page tools
	//

	// the edit command is available to associates, editors, target member, and poster
	if($item['id'] && (Surfer::is_associate()
		|| (is_object($anchor) && $anchor->is_assigned())
		|| (Surfer::is_member() && ($item['anchor'] == 'user:'.Surfer::get_id()))
		|| Surfer::is($item['create_id']))) {

		$context['page_tools'][] = Skin::build_link(Actions::get_url($item['id'], 'edit'), i18n::s('Edit'));
	}

	// the delete command is available to associates, editors, target member, and poster
	if($item['id'] && (Surfer::is_associate()
		|| (is_object($anchor) && $anchor->is_assigned())
		|| (Surfer::is_member() && ($item['anchor'] == 'user:'.Surfer::get_id()))
		|| Surfer::is($item['create_id']))) {

		$context['page_tools'][] = Skin::build_link(Actions::get_url($item['id'], 'delete'), i18n::s('Delete'));
	}

	// referrals, if any, in a sidebar
	//
	$context['components']['referrals'] =& Skin::build_referrals(Actions::get_url($item['id']));
}

// render the skin
render_skin();

?>