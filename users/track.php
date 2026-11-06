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
 * - track.php?anchor=section:2&member=34 (associates only, change the watch list of user #34)
 *
 * An associate may add a page to the watch list of another member, or remove it,
 * for example on request of a member who does not find their settings. Because
 * this changes the profile of someone else, the link displays a confirmation
 * form, and the change is made only when this form is posted, with its token.
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

// the watch list of another member
$on_behalf = isset($_REQUEST['member']) && ((int)$_REQUEST['member'] != Surfer::get_id());
$member = NULL;
if($on_behalf && Surfer::is_associate())
	$member = Users::get((int)$_REQUEST['member']);

// get the item from the database
$item = NULL;
$anchor = Anchors::get($track);
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
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// only associates can change the watch list of another member, and only for pages
} elseif($on_behalf && (!Surfer::is_associate() || !strncmp($track, 'user:', 5))) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// unknown member
} elseif($on_behalf && !isset($member['id'])) {
	include '../error.php';

// you cannot watch yourself
} elseif($track == 'user:'.Surfer::get_id()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// ask an associate to confirm the change of the watch list of another member
} elseif($on_behalf && ($_SERVER['REQUEST_METHOD'] != 'POST')) {

	// the member and the page
	$name = $member['full_name'] ? $member['full_name'] : $member['nick_name'];
	if(is_object($anchor) && $anchor->is_viewable())
		$context['text'] .= '<p>'.Skin::build_link($anchor->get_url(), $anchor->get_title())."</p>\n";

	// the question
	if(Members::check($track, 'user:'.$member['id']))
		$question = sprintf(i18n::s('Remove this page from the watch list of %s?'), Skin::build_link(Users::get_permalink($member), $name));
	else
		$question = sprintf(i18n::s('Add this page to the watch list of %s?'), Skin::build_link(Users::get_permalink($member), $name));

	// the change is made only by posting this form
	$menu = array();
	$menu[] = Skin::build_submit_button(i18n::s('Yes'));
	$menu[] = Skin::build_link(Users::get_permalink($member), i18n::s('Cancel'), 'span');
	$context['text'] .= '<form method="post" action="'.$context['url_to_root'].'users/track.php">'
		.'<p>'.$question.'</p>'
		.'<input type="hidden" name="anchor" value="'.encode_field($track).'" />'
		.'<input type="hidden" name="member" value="'.$member['id'].'" />'
		.Skin::finalize_list($menu, 'assistant_bar')
		.'</form>';

// toggle membership status
} elseif($error = Members::toggle($track, 'user:'.($on_behalf ? $member['id'] : Surfer::get_id()))) {
	Logger::error($error);

// feed-back to an associate who has changed the watch list of another member
} elseif($on_behalf) {

	$name = $member['full_name'] ? $member['full_name'] : $member['nick_name'];
	$context['page_title'] = sprintf(i18n::s('The watch list of %s has been updated'), $name);

	// reference the anchor page
	$menu = array();
	if(is_object($anchor) && $anchor->is_viewable()) {
		$context['text'] .= '<p>'.Skin::build_link($anchor->get_url(), $anchor->get_title())."</p>\n";
		$menu[] = Skin::build_link($anchor->get_url(), i18n::s('Done'), 'button');
	}

	// the new status
	if(Members::check($track, 'user:'.$member['id']))
		$context['text'] .= '<p>'.sprintf(i18n::s('The page has been added to the watch list of %s, who will receive electronic messages on each future update.'), $name)."</p>\n";
	else
		$context['text'] .= '<p>'.sprintf(i18n::s('The page has been removed from the watch list of %s, who won\'t receive any message about it anymore.'), $name)."</p>\n";

	// follow-up commands
	$menu[] = Skin::build_link(Users::get_url($track, 'track').(strpos(Users::get_url($track, 'track'), '?') ? '&amp;' : '?').'member='.$member['id'], i18n::s('I have changed my mind'), 'span');
	$menu[] = Skin::build_link(Users::get_permalink($member), $name, 'span');
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

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

				// headline
				$headline = sprintf(i18n::c('%s is following you'), Surfer::get_link());

				// information
				$message = '<p>'.sprintf(i18n::c('%s will receive notifications when you will update your followers at %s'), Surfer::get_name(), $context['site_name']).'</p>';

				// assemble main content of this message
				$message = Skin::build_mail_content($headline, $message);

				// a set of links
				$menu = array();

				// call for action
				$link = Surfer::get_permalink();
				$menu[] = Skin::build_mail_button($link, ucfirst(strip_tags(Surfer::get_name())), TRUE);

				// finalize links
				$message .= Skin::build_mail_menu($menu);

				// enable threading
				$headers = Mailer::set_thread('user:'.$item['id']);

				// sent by the server
				Mailer::notify(NULL, $user['email'], $subject, $message, $headers);
			}

			// feed-back to poster
			$context['text'] .= '<p>'.sprintf(i18n::s('You have been connected to %s.'), Skin::build_link($anchor->get_url(), $anchor->get_title()))."</p>\n";

			$menu[] = Skin::build_link(Users::get_url($track, 'track'), i18n::s('I have changed my mind'), 'span');

		// we are tracking a page
		} else {

			// reference the anchor page
			if(is_object($anchor) && $anchor->is_viewable())
				$context['text'] .= '<p>'.Skin::build_link($anchor->get_url(), $anchor->get_title())."</p>\n";

			$context['text'] .= '<p>'.i18n::s('The page has been added to your watch list. You will receive electronic messages to warn you on each future update.')."</p>\n";

			$menu[] = Skin::build_link(Users::get_url($track, 'track'), i18n::s('I have changed my mind'), 'span');

		}

	// the page has been suppressed from the watch list
	} else {

		// we are tracking a user
		if(!strncmp($track, 'user:', 5)) {

			$context['text'] .= '<p>'.sprintf(i18n::s('You are not connected to %s anymore.'), Skin::build_link($anchor->get_url(), $anchor->get_title()))."</p>\n";

			$menu[] = Skin::build_link(Users::get_url($track, 'track'), i18n::s('I have changed my mind'), 'span');

		// we are tracking a page
		} else {

			// reference the anchor page
			if(is_object($anchor) && $anchor->is_viewable())
				$context['text'] .= '<p>'.Skin::build_link($anchor->get_url(), $anchor->get_title())."</p>\n";

			$context['text'] .= '<p>'.i18n::s('The page has been removed from your watch list. You won\'t receive any message about it anymore.')."</p>\n";

			$menu[] = Skin::build_link(Users::get_url($track, 'track'), i18n::s('I have changed my mind'), 'span');

		}

	}

	// subscription can be automatic
	if(!strncmp($track, 'article:', 8))
		$context['text'] .= '<p>'.i18n::s('Please note that when you contribute to a page it is added automatically to your watch list.')."</p>\n";

	// check the watch list
	if(Surfer::get_id()) {
		if(!strncmp($track, 'user:', 5))
			$menu[] = Skin::build_link(Users::get_url(Surfer::get_id()).'#_followers', i18n::s('My followers'), 'span');
		else
			$menu[] = Skin::build_link(Users::get_url(Surfer::get_id()), i18n::s('My Profile'), 'span');
	}

	// follow-up commands
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

}

// render the skin
render_skin();

?>
