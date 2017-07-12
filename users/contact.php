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
include_once '../comments/comments.php';	// create new threads

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
	//$id = explode(',', strip_tags($id));
        $id= Mailer::explode_recipients($id);

// avoid duplicates
if(is_array($id))
    $id = array_unique($id);

$with_form = false;
if(isset($_REQUEST['start']) && $_REQUEST['start'] == 'Y')
   $with_form = true;

// create a place for private pages if none exist yet
if(!$anchor = Sections::lookup('threads')) {
	$fields = array();
	$fields['nick_name'] = 'threads';
	$fields['title'] = i18n::c('Threads');
	$fields['introduction'] = i18n::c('For on-demand conversations');
	$fields['locked'] = 'Y'; // no direct contributions
        $fields['active'] = 'N'; // private 
        $fields['active_set'] = 'N';
	$fields['home_panel'] = 'none'; // content is not pushed at the front page
	$fields['index_map'] = 'N'; // listed only to associates
	$fields['articles_layout'] = 'yabb'; // these are threads
	$fields['content_options'] = 'with_export_tools auto_publish with_comments_as_wall';
	$fields['maximum_items'] = 20000; // limit the overall number of threads
	Sections::post($fields, FALSE);
}

// identify all recipients
$items = array();
$item = NULL;
if(is_array($id)) {

	// one recipient at a time
	foreach($id as $target) {
		$target = trim(str_replace(array("\r\n", "\r", "\n", "\t"), ' ', $target));

		// look for a user with this nick name
		if(!$user = Users::lookup($target)) {
                    if($target)
                        Logger::error(sprintf(i18n::s('Error while sending the message to %s'), $target));
                    // skip this recipient
                    continue;
                } else
                    $items[] = $user;
			
	}

	// only one recipient
	if(count($items) == 1)
		$item = $items[0];

}

// the path to this page
$context['path_bar'] = array( 'users/' => i18n::s('People') );
if(isset($item['id']))
	$context['path_bar'] = array_merge($context['path_bar'], array( Users::get_permalink($item) => $item['nick_name'] ));

// page title
if(isset($item['id']) && Surfer::is($item['id']))
	$context['page_title'] .= i18n::s('Your Threads');
elseif(isset($item['nick_name']))
	$context['page_title'] .= sprintf(i18n::s('Message to "%s"'), $item['nick_name']);

// get the related overlay, if any
$overlay = NULL;
if(isset($item['overlay']) && $item['overlay'])
	$overlay = Overlay::load($item, 'user:'.$item['id']);
elseif(isset($context['users_overlay']) && $context['users_overlay'])
	$overlay = Overlay::bind($context['users_overlay']);

// an error occured
if(count($context['error']))
	;

// private pages are disallowed
elseif(isset($context['users_without_private_pages']) && ($context['users_without_private_pages'] == 'Y')) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// permission denied
} elseif(!Surfer::is_member()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// the place for private pages
} elseif(!$anchor = Sections::lookup('threads')) {
	header('Status: 500 Internal Error', TRUE, 500);
	Logger::error(i18n::s('Impossible to add a page.'));

// process submitted data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {
    
        // react to overlaid POST by a overlaid response
        global $render_overlaid;
        if($render_overlaid) 
            $context['text'] .= '<div class="hidden require-overlaid"></div>'."\n";
    
        // target user statut doesn't allow MP, except from associates
        if(!Surfer::is_associate() && 
              ($overlay->attributes['user_status'] == 'donotdisturb' 
                || $overlay->attributes['user_status'] == 'anonymous'  
                || $item['without_alerts'] == 'Y') 
               ) {
            
            $context['text'] .= sprintf(i18n::s('Sorry, %s wish not to receive private message'),$item['nick_name']);
            render_skin();
            finalize_page(TRUE);
        }

	// the new thread
	$article = array();
	$article['anchor'] = $anchor;
	$article['title'] = isset($_REQUEST['title']) ? $_REQUEST['title'] : utf8::transcode(Skin::build_date(gmstrftime('%Y-%m-%d %H:%M:%S GMT'), 'full'));
	$article['active_set'] = 'N';	// this is private
	$article['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S'); // no review is required
	$article['options'] = 'view_as_zic_pm';

	// include some overlay
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
            
                //get full article
                $article = Articles::get($article['id']);

		// attach some file
		if(isset($_FILES['upload']) && $file = Files::upload($_FILES['upload'], 'files/article/'.$article['id'], 'article:'.$article['id'])) {
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
		$context['page_title'] = i18n::s('Message sent&nbsp;!');

		// feed-back to surfer
		// $context['text'] .= '<p>'.i18n::s('A new thread has been created, and it will be listed in profiles of the persons that you have involved. You can invite additional people later on if you wish.').'</p>';

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
                    
                    
                        // contact target user by e-mail
                        $mail = array();
                        $mail['subject'] = sprintf(i18n::c('Private message: %s'), strip_tags($article['title']));

                        $mail['message'] = Articles::build_notification('message', $article, $overlay);

                        // enable threading
                        $mail['headers'] = Mailer::set_thread('article:'.$article['id'], $anchor);

			// each recipient, one at a time
			foreach($items as $item) {

				// you cannot write to yourself
				if(isset($item['id']) && (Surfer::get_id() == $item['id']))
					continue;

				// target recipient does not accept messages
				if(isset($item['without_messages']) && ($item['without_messages'] == 'Y'))
					continue;

				// target is known here
				if(isset($item['id'])) {

					// suggest to change user preferences if applicable
					$mail['message'] .= '<p>&nbsp;</p>'
						.'<p>'.i18n::c('To prevent other members from contacting you, please visit your profile at the following address, and change preferences.').'</p>'
						.'<p>'.$context['url_to_master'].$context['url_to_root'].Users::get_permalink($item).'</p>';

					// alert the target user
					if(!Users::alert($item, $mail))
						Logger::error(sprintf(i18n::s('Impossible to send a message to %s.'), $item['nick_name']));

				// we only have a recipient address
				} elseif($item['email'] && !Mailer::notify(Surfer::from(), $item['email'], $mail['subject'], $mail['message'], $mail['headers']))
					Logger::error(sprintf(i18n::s('Impossible to send a message to %s.'), $item['email']));

			}

		}

	}

	// follow-up commands
        if(!$render_overlaid) {
            $menu = array();
            if(isset($article['id']))
                    $menu = array(Articles::get_permalink($article) => i18n::s('View the new thread'));
            if((count($items) == 1) && ($item = $items[0]) && isset($item['id']))
                    $menu = array_merge($menu, array(Users::get_permalink($item) => sprintf(i18n::s('Back to %s'), $item['nick_name'])));
            elseif(Surfer::get_id())
                    $menu = array_merge($menu, array(Surfer::get_permalink() => i18n::s('Back to my profile')));
            if(count($menu))
                    $context['text'] .= Skin::build_block(i18n::s('Where do you want to go now?').Skin::build_list($menu, 'menu_bar'), 'bottom');
        }

} elseif ($with_form) {
    
        // target user statut doen't allow MP, except from associates
        if(!Surfer::is_associate() && 
              ($overlay->attributes['user_status'] == 'donotdisturb' 
                || $overlay->attributes['user_status'] == 'anonymous'  
                || $item['without_alerts'] == 'Y') 
               ) {

           $context['text'] .= '<p>'.sprintf(i18n::s('Sorry, "%s" wish not to receive private message'),$item['nick_name']).'</p>'."\n";
           render_skin();
           finalize_page(TRUE);
        }
    
        $context['text'] .= Users::get_thread_creation_form($item['id']);

// layout the available contact options
} elseif($threads = Sections::get('threads')) {

	// do not link to this user profile
	//include_once $context['path_to_root'].'articles/layout_articles_as_timeline.php';
	//$layout = new Layout_articles_as_timeline();
	//$layout->set_variant($item['id']);
        $layout = layouts::new_('last', 'article');
        
	// i am looking at my own record
	if(Surfer::get_id() == $item['id']) {
            
                Skin::define_img('ARTICLES_ADD_IMG', 'articles/add.gif');
                $url = 'users/contact.php?start=Y&overlaid=Y';
                $label = ARTICLES_ADD_IMG.i18n::s('Start a new thread');
                // new theard is overlaid
                $label = array(null,$label,null,'edit-overlaid');
                $menu_top = array($url => $label);
                $context['text'] .= Skin::build_list($menu_top, 'menu_bar');
            
                $context['text'] .= Members::list_surfer_threads(0, 50, $layout);
		//if($items = Members::list_surfer_threads(0, 50, $layout))
		//	$context['text'] .= Skin::build_list($items, 'compact');

	// navigating another profile
	} else {

		if($items = Members::list_shared_thread_for_user($item['id'], 0, 50, $layout))
			$context['text'] .= '<p>'.sprintf(i18n::s('Your threads with "%s"'), $item['nick_name']).'</p>'.$items.'<p> </p>'."\n";
                
	}

}

// render the skin
render_skin();

// post processing
finalize_page(TRUE);

?>
