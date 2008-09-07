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
include_once '../shared/mailer.php';		// to send messages

// load the skin
load_skin('users');

// several recipients
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
elseif(Surfer::get_id())
	$id = Surfer::get_id();
if($id)
	$id = explode(',', strip_tags($id));

// identify all recipients
$items = array();
$item = NULL;
if(is_array($id)) {

	// one recipient at a time
	foreach($id as $target) {
		$target = trim($target);

		// look for a user with this nick name
		if($user =& Users::get($target))
			$items[] = $user;

		// maybe a valid address
		elseif(preg_match('/\w+@\w+\.\w+/', $target))
			$items[] = array( 'email' => $target );

		else
			Skin::error(sprintf(i18n::s('%s is unknown.'), $target));

	}

	// only one recipient
	if(count($items) == 1)
		$item = $items[0];

}

// the path to this page
$context['path_bar'] = array( 'users/' => i18n::s('People') );
if(isset($item['id']))
	$context['path_bar'] = array_merge($context['path_bar'], array( Users::get_url($item['id'], 'view', $item['nick_name']) => $item['full_name']?$item['full_name']:$item['nick_name'] ));

// page title
if(isset($item['id']) && Surfer::is($item['id']))
	$context['page_title'] .= i18n::s('Private pages');
elseif(isset($item['nick_name']))
	$context['page_title'] .= sprintf(i18n::s('Contact %s'), $item['full_name']?$item['full_name']:$item['nick_name']);
else
	$context['page_title'] .= i18n::s('Contact');

// an error occured
if(count($context['error']))
	;

// not found
elseif(!count($items)) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No item has the provided id.'));

// private pages are disallowed
} elseif(isset($context['users_without_private_pages']) && ($context['users_without_private_pages'] == 'Y')) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// permission denied
} elseif(!Surfer::is_member()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// the place for private pages
} elseif(!$anchor = Sections::lookup('private')) {
	header('Status: 500 Internal Error', TRUE, 500);
	Skin::error(i18n::s('Impossible to add a page.'));

// process submitted data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// the new thread
	$article = array();
	$article['anchor'] = $anchor;
	$article['title'] = isset($_REQUEST['title']) ? $_REQUEST['title'] : utf8::transcode(Skin::build_date(gmstrftime('%Y-%m-%d %H:%M:%S GMT'), 'full'));
	$article['active_set'] = 'N';	// this is private
	$article['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S'); // no review is required

	// post the new thread
	if(!$article['id'] = Articles::post($article))
		Skin::error(i18n::s('Impossible to add a page.'));

	// ensure all surfers will be allowed to access this page
	else {

		// make a new comment out of received message, if any
		if(isset($_REQUEST['message']) && trim($_REQUEST['message'])) {
			$comment = array();
			$comment['anchor'] = 'article:'.$article['id'];
			$comment['description'] = strip_tags($_REQUEST['message']);
			Comments::post($comment);
		}

		// feed-back to surfer
		$context['text'] .= '<p>'.i18n::s('A new private page has been created. You can invite additional people later on if you wish.').'</p>';

		// increment the post counter of the surfer
		Users::increment_posts(Surfer::get_id());

		// make editors of the new page
		Members::assign('user:'.Surfer::get_id(), 'article:'.$article['id']);
		foreach($items as $item) {
			if(isset($item['id']))
				Members::assign('user:'.$item['id'], 'article:'.$article['id']);
		}

		// add this page to watch lists
		Members::assign('article:'.$article['id'], 'user:'.Surfer::get_id());
		foreach($items as $item) {
			if(isset($item['id']))
				Members::assign('article:'.$article['id'], 'user:'.$item['id']);
		}

		// email has to be activated
		if(isset($context['with_email']) && ($context['with_email'] == 'Y')) {

				// each recipient, one at a time
				foreach($items as $item) {

				// you cannot write to yourself
				if(isset($item['id']) && (Surfer::get_id() == $item['id']))
					continue;

				// target recipient does not accept messages
				if(isset($item['without_messages']) && ($item['without_messages'] == 'Y'))
					continue;

				// contact target user by e-mail
				$mail = array();
				$mail['subject'] = sprintf(i18n::c('New page: %s'), strip_tags($article['title']));
				$mail['message'] = sprintf(i18n::c('%s would like to share a private page with you'), Surfer::get_name())
					."\n\n".ucfirst(strip_tags($article['title']))
					."\n".$context['url_to_home'].$context['url_to_root'];

				// provide credentials by e-mail
				if(isset($item['email'])) {

					// build credentials --see users/login.php
					$credentials = array();
					$credentials[0] = 'visit';
					$credentials[1] = 'article:'.$article['id'];
					$credentials[2] = $item['email'];
					$credentials[3] = sprintf('%u', crc32($item['email'].':'.$article['handle']));

					// the secret link
					$mail['message'] .= Users::get_url($credentials, 'credentials');

				// target will have to authenticate on his own
				} else
					$mail['message'] .= Articles::get_permalink($article);

				// target is known here
				if(isset($item['id'])) {

					// suggest to change user preferences if applicable
					$mail['message'] .= "\n\n"
						.i18n::c('To prevent other surfers from contacting you, please visit your user profile at the following address, and change preferences.')
						."\n\n".$context['url_to_home'].$context['url_to_root'].Users::get_url($item['id'], 'view', $item['nick_name'])
						."\n\n";

					// also prepare an interactive alert
					$notification = array();
					$notification['nick_name'] = Surfer::get_name();
					$notification['recipient'] = $item['id'];
					$notification['type'] = 'hello';
					$notification['address'] = $context['url_to_home'].$context['url_to_root'].Articles::get_permalink($article);
					$notification['reference'] = 'article:'.$article['id'];

					// alert the target user
					if(!Users::alert($item, $mail, $notification))
						Skin::error(sprintf(i18n::s('Impossible to send a message to %s.'), $item['nick_name']));

				// we only have a recipient address
				} elseif($item['email'] && !Mailer::notify($item['email'], $mail['subject'], $mail['message']))
					Skin::error(sprintf(i18n::s('Impossible to send a message to %s.'), $item['email']));

			}

		} else
			Skin::error(i18n::s('No notification has been sent. Please share the address of the new page by yourself.'));

	}

	// follow-up commands
	$menu = array();
	if(isset($article['id']))
		$menu = array(Articles::get_permalink($article) => i18n::s('View the page'));
	if((count($items) == 1) && ($item = $items[0]) && isset($item['id']))
		$menu = array_merge($menu, array(Users::get_url($item['id'], 'view', $item['nick_name']) => sprintf(i18n::s('Back to %s'), $item['nick_name'])));
	elseif(Surfer::get_id())
		$menu = array_merge($menu, array(Users::get_url(Surfer::get_id(), 'view', Surfer::get_name()) => sprintf(i18n::s('Back to %s'), Surfer::get_name())));
	if(count($menu))
		$context['text'] .= Skin::build_block(i18n::s('Where do you want to go now?').Skin::build_list($menu, 'page_menu'), 'bottom');

// layout the available contact options
} else
	$context['text'] .= Skin::build_user_contact($item);


// render the skin
render_skin();

?>