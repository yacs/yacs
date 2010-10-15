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
	$context['path_bar'] = array_merge($context['path_bar'], array( Surfer::get_permalink() => Surfer::get_name() ));

// the title of the page
$context['page_title'] = i18n::s('Watch list');

// not found
if(!$item['id']) {
	include '../error.php';

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
	if(!strncmp($track, 'user:', 5))
		$context['page_title'] = i18n::s('The list of persons that you follow has been updated');
	else
		$context['page_title'] = i18n::s('Your watch list has been updated');

	// follow-up commands
	$menu = array();
	if(is_object($anchor) && $anchor->is_viewable())
		$menu[] = Skin::build_link($anchor->get_url(), i18n::s('Done'), 'button');

	// the page now appears in the watch list
	if(Members::check($track, 'user:'.Surfer::get_id())) {

		// we are tracking a user
		if(!strncmp($track, 'user:', 5)) {

			// notify a person that is followed
			if(($user = Users::get(str_replace('user:', '', $track))) && isset($user['email']) && $user['email'] && ($user['without_alerts'] != 'Y')) {

				// contact target user by e-mail
				$subject = sprintf(i18n::c('%s is following you'), strip_tags(Surfer::get_name()));
				$message = sprintf(i18n::c('%s will receive notifications when you will create new public content at %s'), Surfer::get_name(), $context['site_name'])
					.'<p>'.ucfirst(strip_tags(Surfer::get_name()))
					.BR.$context['url_to_home'].$context['url_to_root'].Surfer::get_permalink().'</p>';

				// sent by the server
				Mailer::post(NULL, $user['email'], $subject, $message);
			}

			// feed-back to poster
			$context['text'] .= '<p>'.sprintf(i18n::s('You have been connected to %s.'), Skin::build_link($anchor->get_url(), $anchor->get_title()))."</p>\n";

			$menu[] = Skin::build_link(Users::get_url($track, 'track'), i18n::s('I have changed my mind'), 'basic');

		// we are tracking a page
		} else {

			// reference the anchor page
			if(is_object($anchor) && $anchor->is_viewable())
				$context['text'] .= '<p>'.Skin::build_link($anchor->get_url(), $anchor->get_title())."</p>\n";

			$context['text'] .= '<p>'.i18n::s('The page has been added to your watch list. You will receive electronic messages to warn you on each future update.')."</p>\n";

			$menu[] = Skin::build_link(Users::get_url($track, 'track'), i18n::s('I have changed my mind'), 'basic');

		}

	// the page has been suppressed from the watch list
	} else {

		// we are tracking a user
		if(!strncmp($track, 'user:', 5)) {

			$context['text'] .= '<p>'.sprintf(i18n::s('You are not connected to %s anymore.'), Skin::build_link($anchor->get_url(), $anchor->get_title()))."</p>\n";

			$menu[] = Skin::build_link(Users::get_url($track, 'track'), i18n::s('I have changed my mind'), 'basic');

		// we are tracking a page
		} else {

			// reference the anchor page
			if(is_object($anchor) && $anchor->is_viewable())
				$context['text'] .= '<p>'.Skin::build_link($anchor->get_url(), $anchor->get_title())."</p>\n";

			$context['text'] .= '<p>'.i18n::s('The page has been removed from your watch list. You won\'t receive any message about it anymore.')."</p>\n";

			$menu = array_merge($menu, array(Users::get_url($track, 'track') => i18n::s('I have changed my mind')));

		}

	}

	// subscription can be automatic
	if(!strncmp($track, 'article:', 8))
		$context['text'] .= '<p>'.i18n::s('Please note that when you contribute to a page it is added automatically to your watch list.')."</p>\n";

	// check the watch list
	if(Surfer::get_id()) {
		if(!strncmp($track, 'user:', 5))
			$menu[] = Skin::build_link(Users::get_url(Surfer::get_id()).'#_followers', i18n::s('My followers'), 'basic');
		else
			$menu[] = Skin::build_link(Users::get_url(Surfer::get_id()), i18n::s('My Profile'), 'basic');
	}

	// follow-up commands
	$context['text'] .= Skin::build_list($menu, 'assistant_bar');

}

// render the skin
render_skin();

?>
