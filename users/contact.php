<?php
/**
 * contact some user
 *
 * If the target user is currently active, this script will send him a real-time
 * notification. Else it will only populate a private conversation.
 *
 * Existing private conversations between the surfer and the target user are
 * listed, if any, to support follow-up on existing threads.
 *
 * Also, a new thread can always be created in the section dedicated to
 * private conversations.
 *
 * Restrictions apply on this page:
 * - associates are allowed to move forward
 * - access is restricted ('active' field == 'R'), but the surfer is an authenticated member
 * - public access is allowed ('active' field == 'Y') and the surfer has been logged
 * - permission denied is the default
 *
 * Accepted calls:
 * - contact.php/&lt;id&gt;
 * - contact.php?id=&lt;id&gt;
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../comments/comments.php';	// to create new threads

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Users::get($id);

// associates can do what they want
if(Surfer::is_associate())
	$permitted = TRUE;

// only regular members can contact other members
elseif(!Surfer::is_member())
	$permitted = FALSE;

// access is restricted to authenticated member
elseif(isset($item['active']) && (($item['active'] == 'R') || ($item['active'] == 'Y')))
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
	$context['page_title'] .= sprintf(i18n::s('Contact %s'), $item['nick_name']);
else
	$context['page_title'] .= i18n::s('Contact');

// command to go back
if(isset($item['id']))
	$context['page_menu'] = array( Users::get_url($item['id'], 'view', isset($item['nick_name'])?$item['nick_name']:'') => sprintf(i18n::s('Back to the page of %s'), $item['nick_name']) );

// not found
if(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No item has the provided id.'));

// user does not accept private messages
} elseif(isset($item['without_messages']) && ($item['without_messages'] == 'Y')) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('This member does not accept e-mail messages.'));

// you are not allowed to contact yourself
} elseif(Surfer::get_id() && (Surfer::get_id() == $item['id'])) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Users::get_url($item['id'], 'contact')));

	// permission denied to authenticated user
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// process submitted data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// mail message
	$mail = array();

	// notification
	$notification = array();
	$notification['nick_name'] = Surfer::get_name();
	$notification['recipient'] = $item['id'];

	switch($_REQUEST['rendez_vous']) {

	case 'thread':

		// the place for private interactive discussions
		if(!$anchor = Sections::lookup('chats')) {
			$section = array();
			$section['nick_name'] = 'chats';
			$section['title'] =& i18n::c('Private conversations');
			$section['introduction'] =& i18n::c('Long-lived one-to-one interactions');
			$section['locked'] = 'N'; // no direct contributions
			$section['active_set'] = 'N'; // for associates only
			$section['home_panel'] = 'none'; // content is not pushed at the front page
			$section['index_map'] = 'N'; // this is a special section
			$section['sections_layout'] = 'none'; // prevent creation of sub-sections
			$section['articles_layout'] = 'yabb'; // these are threads
			$section['content_options'] = 'view_as_thread'; // these are threads
			$section['maximum_items'] = 1000; // limit the overall number of threads
			if($section['id'] = Sections::post($section)) {
				Sections::clear($section);
				$anchor = 'section:'.$section['id'];
			}
		}

		// the new thread
		$article = array();
		$article['anchor'] = $anchor;
		$article['title'] = isset($_REQUEST['title']) ? $_REQUEST['title'] : utf8::transcode(sprintf(i18n::s('%s and %s %s'), Surfer::get_name(), $item['nick_name'], Skin::build_date(gmstrftime('%Y-%m-%d %H:%M:%S GMT'), 'full')));
		$article['active_set'] = 'N';	// this is private
		$article['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S'); // no review is required

		// ensure we have a valid anchor
		if(!$anchor)
			Skin::error(i18n::s('Impossible to add a page.'));

		// post the new thread
		elseif(!$article['id'] = Articles::post($article))
			Skin::error(i18n::s('Impossible to add a page.'));

		// ensure both surfers will be allowed to access this page
		else {

			// make editors of the new page
			Members::assign('user:'.Surfer::get_id(), 'article:'.$article['id']);
			Members::assign('user:'.$item['id'], 'article:'.$article['id']);

			// add this page to watch lists
			Members::assign('article:'.$article['id'], 'user:'.Surfer::get_id());
			Members::assign('article:'.$article['id'], 'user:'.$item['id']);

			// make a new comment out of received message, if any
			if(isset($_REQUEST['message']) && trim($_REQUEST['message'])) {
				$comment = array();
				$comment['anchor'] = 'article:'.$article['id'];
				$comment['description'] = strip_tags($_REQUEST['message']);
				Comments::post($comment);
			}

			// purge section cache
			if($section = Anchors::get($article['anchor']))
				$section->touch('article:create', $article['id'], TRUE);

			// clear the cache
			Articles::clear($article);

			// contact target user by e-mail
			$mail['subject'] = sprintf(i18n::c('Chat: %s'), strip_tags($article['title']));
			$mail['message'] = sprintf(i18n::c('%s would like to have a private chat with you'), Surfer::get_name())
				."\n\n".ucfirst(strip_tags($article['title']))
				."\n".$context['url_to_home'].$context['url_to_root'].Articles::get_url($article['id'], 'view', $article['title'])
				."\n\n"
				.i18n::c('If you wish to prevent other surfers to contact you please visit your user profile at the following address, and change preferences.')
				."\n\n".$context['url_to_home'].$context['url_to_root'].Users::get_url($item['id'], 'view', $item['nick_name'])
				."\n\n";

			// also prepare an interactive alert
			$notification['type'] = 'hello';
			$notification['address'] = $context['url_to_home'].$context['url_to_root'].Articles::get_url($article['id']);
			$notification['reference'] = 'article:'.$article['id'];

		}

		break;

	case 'browse':
		if(!isset($_REQUEST['address']))
			Skin::error(i18n::s('Notification is invalid.'));
		else {

			// contact target user by e-mail
			$mail['subject'] = sprintf(i18n::c('Browse with %s'), Surfer::get_name());
			$mail['message'] = sprintf(i18n::c('%s would like you to browse the following link'), Surfer::get_name())
				."\n\n".preg_replace(FORBIDDEN_CHARS_IN_URLS, '_', $_REQUEST['address'])
				."\n\n"
				.i18n::c('If you wish to prevent other surfers to contact you please visit your user profile at the following address, and change preferences.')
				."\n\n".$context['url_to_home'].$context['url_to_root'].Users::get_url($item['id'], 'view', $item['nick_name'])
				."\n\n";

			// also prepare an interactive alert
			$notification['address'] = preg_replace(FORBIDDEN_CHARS_IN_URLS, '_', $_REQUEST['address']);
			$notification['type'] = 'browse';

			// also attach received message, if any
			if(isset($_REQUEST['message']) && trim($_REQUEST['message']))
				$notification['message'] = strip_tags($_REQUEST['message']);

		}
		break;

	case 'none';
		$notification['type'] = 'hello';

		// also attach received message, if any
		if(isset($_REQUEST['message']) && trim($_REQUEST['message']))
			$notification['message'] = strip_tags($_REQUEST['message']);

		break;

	}

	// incorrect request
	if(!isset($notification['type'])) {
		Safe::header('Status: 400 Bad Request', TRUE, 400);
		Skin::error('Invalid notification');

	// alert the target user
	} elseif(!Users::alert($item, $mail, $notification)) {
		header('Status: 500 Internal Error', TRUE, 500);
		Skin::error(sprintf(i18n::s('Impossible to send your message to %s.'), $item['nick_name']));

	// follow-up for the surfer
	} else {
		$context['text'] = '<p>'.sprintf(i18n::s('Your message is being transmitted to %s.'), $item['nick_name']).'</p>';
	}

	// offer a link to the target page
	if(isset($notification['address'])) {

		// adapt the message to context
		if(isset($notification['type']) && ($notification['type'] == 'hello'))
			$menu = array($notification['address'] => sprintf(i18n::s('Jump to the private page that has been created for your web chat with %s'), $item['nick_name']));
		else
			$menu = array($notification['address'] => sprintf(i18n::s('Jump to the web address sent to %s'), $item['nick_name']));

		$context['text'] .= Skin::build_list($menu, 'menu_bar');
	}

// layout the available contact options
} else
	$context['text'] .= Skin::build_user_contact($item);


// render the skin
render_skin();

?>