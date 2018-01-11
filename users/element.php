<?php
/**
 * provide part of user information to AJAX front-end
 *
 * Restrictions apply on this page:
 * - associates are allowed to move forward
 * - this is the personal record of the authenticated surfer
 * - access is restricted ('active' field == 'R'), but the surfer is an authenticated member
 * - public access is allowed ('active' field == 'Y')
 * - permission denied is the default
 *
 * Accept following invocations:
 * - element.php/12/watch
 * - element.php?id=12&action=watch
 *
 * @author Bernard Paques
 * @tester Moi-meme
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../links/links.php';
include_once '../locations/locations.php';

// ensure browser always look for fresh data
http::expire(0);

// stop here on scripts/validate.php
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
	return;

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
elseif(Surfer::is_logged())
	$id = Surfer::get_id();
$id = strip_tags($id);

// encode ISO-8859-1 argument, if any
if(isset($_SERVER['HTTP_ACCEPT_CHARSET']) && preg_match('/^iso-8859-1/i', $_SERVER['HTTP_ACCEPT_CHARSET']))
	$id = utf8_encode($id);

// get the item from the database
$item = Users::get($id);

// get the related overlay, if any
$overlay = NULL;
if(isset($item['overlay']))
	$overlay = Overlay::load($item, 'user:'.$item['id']);

// look for the action
$action = NULL;
if(isset($_REQUEST['action']))
	$action = $_REQUEST['action'];
elseif(isset($context['arguments'][1]))
	$action = $context['arguments'][1];
$action = strip_tags($action);

// actual capability of current surfer
if(isset($item['id']) && Surfer::get_id() && ($item['id'] == Surfer::get_id()) && ($item['capability'] != '?'))
	Surfer::empower();

// associates can do what they want
if(Surfer::is_associate())
	$permitted = TRUE;

// the record of the authenticated surfer
elseif(isset($item['id']) && Surfer::is($item['id']))
	$permitted = TRUE;

// access is restricted to authenticated member
elseif(isset($item['active']) && ($item['active'] == 'R') && Surfer::is_member())
	$permitted = TRUE;

// public access is allowed
elseif(isset($item['active']) && ($item['active'] == 'Y'))
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load the skin
load_skin('users');

// the path to this page
$context['path_bar'] = array( 'users/' => i18n::s('People') );

// the title of the page
if(isset($item['nick_name']))
	$context['page_title'] = $item['nick_name'];
elseif(isset($item['full_name']))
	$context['page_title'] = $item['full_name'];

// not found -- help web crawlers
if(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	die(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	die(i18n::s('You are not allowed to perform this operation.'));

// the watch list
} elseif($action == 'watch') {
	render_raw();

	// we return some HTML
	$output = '';

	// horizontal menu
	$menu = array();

	// manage command
	if(Surfer::is_associate() || (Surfer::get_id() == $item['id'])) {
		$menu[] = Skin::build_link(Users::get_url('user:'.$item['id'], 'select'), fa::_("fa-eye").' '.i18n::s('Manage followers'), 'span');
	}

	// build the menu
	$output .= Skin::finalize_list($menu, 'menu_bar');

	// list watched users by posts
	$watched = '';
	if($items = Members::list_connections_for_user('user:'.$item['id'], 0, 200, 'watch')) {
		if(is_array($items))
			$items = Skin::build_list($items, 'decorated');
		$watched .= $items;
	} elseif(Surfer::get_id() == $item['id'])
		$watched .= '<p>'.i18n::s('Click on the link above to follow someone.').'</p>';
	else
		$watched .= '<p>'.sprintf(i18n::s('%s is not yet following other persons.'), $item['full_name']).'</p>';

	// the list of followers
	$followers = '';
	if($items = Members::list_watchers_by_name_for_anchor('user:'.$item['id'], 0, 1000, 'compact')) {
		if(is_array($items))
			$items = Skin::build_list($items, 'compact');
		if(Surfer::get_id() == $item['id'])
			$followers .= '<p>'.i18n::s('Persons who follow you:').'</p>'.$items;
		else
			$followers .= '<p>'.sprintf(i18n::s('Persons who follow %s:'), $item['full_name']).'</p>'.$items;

	}

	// connect to people
	if(Surfer::get_id() && (Surfer::get_id() != $item['id'])) {

		// suggest a new connection
		if(!Members::check('user:'.$item['id'], 'user:'.Surfer::get_id())) {
			$link = Users::get_url('user:'.$item['id'], 'track');
			$followers .= '<p style="margin: 1em 0;">'.Skin::build_link($link, fa::_("fa-eye").' '.sprintf(i18n::s('Follow %s'), $item['full_name']), 'basic', i18n::s('Be notified of additions from this person')).'</p>';
		}

	}

	// put followers in a sidebar
	if($followers)
		$output .= Skin::layout_horizontally($watched, Skin::build_block($followers, 'sidecolumn'));
	else
		$output .= $watched;

	// actual transmission except on a HEAD request
	if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
		echo $output;

	// the post-processing hook, then exit
	finalize_page(TRUE);

// invalid action selector
} else {
	Safe::header('Status: 400 Bad Request', TRUE, 400);
	die('Request is invalid.');
}

// render the skin
render_skin();

?>
