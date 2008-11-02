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
 * - element.php/12/actions
 * - element.php/12/watch
 * - element.php?id=12&action=actions
 * - element.php?id=12&action=watch
 *
 * @author Bernard Paques
 * @tester Moi-meme
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../files/files.php';
include_once '../links/links.php';
include_once '../locations/locations.php';

// ensure browser always look for fresh data
Safe::header("Cache-Control: no-cache, must-revalidate");
Safe::header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

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
$item =& Users::get($id);

// get the related overlay, if any
$overlay = NULL;
include_once '../overlays/overlay.php';
if(isset($item['overlay']))
	$overlay = Overlay::load($item);

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
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	die(i18n::s('You are not allowed to perform this operation.'));

// list actions
} elseif($action == 'actions') {

	// we return some HTML
	$output = '';

	// query the database
	include_once '../actions/actions.php';
	$items = Actions::list_by_date_for_anchor('user:'.$item['id'], 0, ACTIONS_PER_PAGE);
	if(is_array($items))
		$items = Skin::build_list($items, 'decorated');

	// display the list of pending actions
	if($items)
		$output .= $items;
	elseif(Surfer::get_id() != $item['id'])
		$output .= i18n::s('No action has been assigned to this person.');

	// offer to add a new action
	if(Surfer::is_member()) {
		$menu = array( 'actions/edit.php?anchor=user:'.$item['id'] => i18n::s('Add an action') );
		$output .= Skin::build_list($menu, 'menu_bar');
	}

	// actual transmission except on a HEAD request
	if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
		echo $output;

	// the post-processing hook, then exit
	finalize_page(TRUE);

// the watch list
} elseif($action == 'watch') {

	// we return some HTML
	$output = '';

	$menu = array();
	if(Surfer::is_associate() || (Surfer::get_id() == $item['id']))
		$menu = array(Users::get_url('user:'.$item['id'], 'select') => i18n::s('Manage'));
	$output .= Skin::build_list($menu, 'menu_bar');
	
	// list watched users by posts
	if($items =& Members::list_users_by_posts_for_member('user:'.$item['id'], 0, USERS_PER_PAGE, 'watch')) {
		if(is_array($items))
			$items = Skin::build_list($items, 'decorated');
		$output .= $items;
	}

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