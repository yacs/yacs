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
include_once '../files/files.php';			// to upload files
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

// create a place for private pages if none exist yet
if(!$anchor = Sections::lookup('threads')) {
	$fields = array();
	$fields['nick_name'] = 'threads';
	$fields['title'] =& i18n::c('Threads');
	$fields['introduction'] =& i18n::c('For on-demand conversations');
	$fields['locked'] = 'N'; // no direct contributions
	$fields['home_panel'] = 'none'; // content is not pushed at the front page
	$fields['index_map'] = 'N'; // listed only to associates
	$fields['articles_layout'] = 'yabb'; // these are threads
	$fields['content_options'] = 'with_export_tools auto_publish with_comments_as_wall';
	$fields['maximum_items'] = 20000; // limit the overall number of threads
	Sections::post($fields);
}

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
			Logger::error(sprintf(i18n::s('%s is unknown.'), $target));

	}

	// only one recipient
	if(count($items) == 1)
		$item = $items[0];

}

// the path to this page
$context['path_bar'] = array( 'users/' => i18n::s('People') );
if(isset($item['id']))
	$context['path_bar'] = array_merge($context['path_bar'], array( Users::get_permalink($item) => $item['full_name']?$item['full_name']:$item['nick_name'] ));

// page title
if(isset($item['id']) && Surfer::is($item['id']))
	$context['page_title'] .= i18n::s('Threads');
elseif(isset($item['nick_name']))
	$context['page_title'] .= sprintf(i18n::s('Contact %s'), $item['full_name']?$item['full_name']:$item['nick_name']);

// an error occured
if(count($context['error']))
	;

// private pages are disallowed
elseif(isset($context['users_without_private_pages']) && ($context['users_without_private_pages'] == 'Y')) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// permission denied
} elseif(!Surfer::is_member()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// the place for private pages
} elseif(!$anchor = Sections::lookup('threads')) {
	header('Status: 500 Internal Error', TRUE, 500);
	Logger::error(i18n::s('Impossible to add a page.'));

// process submitted data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// the new thread
	$article = array();
	$article['anchor'] = $anchor;
	$article['title'] = isset($_REQUEST['title']) ? $_REQUEST['title'] : utf8::transcode(Skin::build_date(gmstrftime('%Y-%m-%d %H:%M:%S GMT'), 'full'));
	$article['active_set'] = 'N';	// this is private
	$article['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S'); // no review is required
	$article['options'] = 'view_as_tabs comments_as_wall';

	// include some overlay
	include_once '../overlays/overlay.php';
	$overlay = Overlay::bind('thread');
	$article['overlay'] = $overlay->save();
	$article['overlay_id'] = $overlay->get_id();
	
	// ensure everything is positioned as expected
	Surfer::empower();
	
	// post the new thread
	if(!$article['id'] = Articles::post($article))
		Logger::error(i18n::s('Impossible to add a page.'));

	// ensure all surfers will be allowed to access this page
	else {

		// attach some file
		if($file = Files::upload($_FILES['upload'], 'files/article/'.$article['id'], 'article:'.$article['id'])) {
			$_REQUEST['message'] .= $file;
		}
		
		// make a new comment out of received message, if any
		if(isset($_REQUEST['message']) && trim($_REQUEST['message'])) {
			$comment = array();
			$comment['anchor'] = 'article:'.$article['id'];
			$comment['description'] = strip_tags($_REQUEST['message']);
			Comments::post($comment);
		}

		// page title
		$context['page_title'] = i18n::s('Thank you for your contribution');

		// feed-back to surfer
		$context['text'] .= '<p>'.i18n::s('A new thread has been created, and it will be listed in profiles of the persons that you have involved. You can invite additional people later on if you wish.').'</p>';

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

				// enable threading
				$mail['headers'] = Mailer::set_thread('article:'.$article['id'], $anchor);
			
				// target is known here
				if(isset($item['id'])) {

					// suggest to change user preferences if applicable
					$mail['message'] .= "\n\n"
						.i18n::c('To prevent other surfers from contacting you, please visit your profile at the following address, and change preferences.')
						."\n\n".$context['url_to_home'].$context['url_to_root'].Users::get_permalink($item)
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
						Logger::error(sprintf(i18n::s('Impossible to send a message to %s.'), $item['nick_name']));

				// we only have a recipient address
				} elseif($item['email'] && !Mailer::notify(Surfer::from(), $item['email'], $mail['subject'], $mail['message'], $mail['headers']))
					Logger::error(sprintf(i18n::s('Impossible to send a message to %s.'), $item['email']));

			}

		}

	}

	// follow-up commands
	$menu = array();
	if(isset($article['id']))
		$menu = array(Articles::get_permalink($article) => i18n::s('View the new thread'));
	if((count($items) == 1) && ($item = $items[0]) && isset($item['id']))
		$menu = array_merge($menu, array(Users::get_permalink($item) => sprintf(i18n::s('Back to %s'), $item['nick_name'])));
	elseif(Surfer::get_id())
		$menu = array_merge($menu, array(Surfer::get_permalink() => i18n::s('Back to my profile')));
	if(count($menu))
		$context['text'] .= Skin::build_block(i18n::s('Where do you want to go now?').Skin::build_list($menu, 'menu_bar'), 'bottom');

// layout the available contact options
} elseif($threads = Sections::get('threads')) {

	// do not link to this user profile
	include_once $context['path_to_root'].'articles/layout_articles_as_timeline.php';
	$layout = new Layout_articles_as_thread();
	$layout->set_variant($item['id']);

	// i am looking at my own record
	if(Surfer::get_id() == $item['id']) {

		if($items =& Articles::list_assigned_by_date_for_anchor('section:'.$threads['id'], $item['id'], 0, 50, $layout, FALSE))
			$context['text'] .= Skin::build_list($items, 'compact');

	// navigating another profile
	} else {

		if($items =& Articles::list_assigned_by_date_for_anchor('section:'.$threads['id'], $item['id'], 0, 50, $layout, TRUE))
			$context['text'] .= '<p>'.sprintf(i18n::s('Your threads with %s'), $item['full_name']).'</p>'.Skin::build_list($items, 'compact').'<p> </p>';

	}
	
}

// render the skin
render_skin();

?>