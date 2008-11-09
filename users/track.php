<?php
/**
 * track one item
 *
 * This page is triggered by logged users, to managed items that they manage.
 *
 * Accept following invocations:
 * - track.php/section/2 (track section #2)
 * - track.php?anchor=section:2 (track section #2)
 * - track.php/article/12 (track article #12)
 * - track.php?anchor=article:12 (track article #12)
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// tracked item
$track = NULL;
if(isset($_REQUEST['article']))
	$track = 'article:'.$_REQUEST['article'];
elseif(isset($_REQUEST['section']))
	$track = 'section:'.$_REQUEST['section'];
elseif(isset($_REQUEST['user']))
	$track = 'user:'.$_REQUEST['user'];
elseif(isset($_REQUEST['anchor']))
	$track = $_REQUEST['anchor'];
elseif(isset($context['arguments'][1]))
	$track = $context['arguments'][0].':'.$context['arguments'][1];
$track = strip_tags($track);

// get the item from the database
$item = NULL;
$anchor =& Anchors::get($track);
if(is_object($anchor))
	$item = $anchor->item;

// load the skin
load_skin('users');

// the path to this page
$context['path_bar'] = array( 'users/' => i18n::s('People') );
if(Surfer::is_logged())
	$context['path_bar'] = array_merge($context['path_bar'], array( Users::get_url(Surfer::get_id(), 'view', Surfer::get_name()) => Surfer::get_name() ));

// the title of the page
$context['page_title'] = i18n::s('Watch list');

// not found
if(!$item['id']) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No item has the provided id.').' ('.$track.')');

// operation is restricted to logged users
} elseif(!Surfer::get_id()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// you cannot watch yourself
} elseif($track == 'user:'.Surfer::get_id()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// toggle membership status
} elseif($error = Members::toggle($track, 'user:'.Surfer::get_id())) {
	Logger::error($error);

// post-processing tasks
} else {

	// successful operation reflected into page title
	$context['page_title'] = i18n::s('Your watch list has been successfully updated');

	// reference the anchor page
	if(is_object($anchor) && $anchor->is_viewable())
		$context['text'] .= '<p>'.Skin::build_link($anchor->get_url(), $anchor->get_title())."</p>\n";

	// follow-up commands
	$menu = array();
	if(is_object($anchor) && $anchor->is_viewable())
		$menu = array($anchor->get_url() => i18n::s('Back to main page'));

	// the page now appears in the watch list
	if(Members::check($track, 'user:'.Surfer::get_id())) {

		// we are tracking a user
		if(substr($track, 0, 5) == 'user:') {
			$context['text'] .= '<p>'.i18n::s('The user has been added to your watch list. This list is reflected at your own user profile.')."</p>\n";

			$menu = array_merge($menu, array(Users::get_url($track, 'track') => i18n::s('I have changed my mind, forget this user')));

		// we are tracking a page
		} else {
			$context['text'] .= '<p>'.i18n::s('The page has been added to your watch list. You will receive electronic messages to warn you on each future update.')."</p>\n";

			$menu = array_merge($menu, array(Users::get_url($track, 'track') => i18n::s('I have changed my mind, forget this page')));

		}

	// the page has been suppressed from the watch list
	} else {

		// we are tracking a user
		if(substr($track, 0, 5) == 'user:') {
			$context['text'] .= '<p>'.i18n::s('The user has been removed from your watch list.')."</p>\n";

			$menu = array_merge($menu, array(Users::get_url($track, 'track') => i18n::s('I have changed my mind, watch this user')));

		// we are tracking a page
		} else {
			$context['text'] .= '<p>'.i18n::s('The page has been removed from your watch list. You won\'t receive any message about it anymore.')."</p>\n";

			$menu = array_merge($menu, array(Users::get_url($track, 'track') => i18n::s('I have changed my mind, watch this page')));

		}

	}

	// subscription can be automatic
	if(substr($track, 0, 5) != 'user:')
		$context['text'] .= '<p>'.i18n::s('Please note that pages automatically appear in your watch list if you modify them either directly or by posting new pieces of information (e.g., comment, image, or file).')."</p>\n";

	if(Surfer::get_id())
		$menu = array_merge($menu, array(Users::get_url(Surfer::get_id()).'#watch_list' => i18n::s('Watch list')));

	// follow-up commands
	$follow_up = i18n::s('What do you want to do now?');
	$follow_up .= Skin::build_list($menu, 'menu_bar');
	$context['text'] .= Skin::build_block($follow_up, 'bottom');

}

// failed operation
if(count($context['error']))
	$context['text'] .= '<p>'.i18n::s('Operation has failed.').'</p>';

// render the skin
render_skin();

?>