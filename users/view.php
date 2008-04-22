<?php
/**
 * view one user profile
 *
 * @todo share information with stds http://www.dataportability.org/graphsync/
 * @todo we have live profiles!
 * @todo your workspaces/your watchlist http://www.socialtext.com/products/overview
 * @todo list assigned files
 * @todo list assigned articles (along assigned sections) (Agnes)
 * @todo add 10 preferred blogs/links
 *
 * This script displays one user profile. Depending on who the surfer is, more or less information is provided.
 * - surfer is the user: view any detail, may edit the page
 * - surfer is associate: view any detail, may edit and delete the page
 * - else: view the user profile
 *
 * The script also lists sections where this user may act as a managing editor, if any.
 *
 * [deleted]If the user has a Yahoo! address or ICQ number, this script generates HTML codes required to display messenger status.[/deleted]
 *
 * Addresses are showed to the surfer only if the surfer has been duly authenticated as a member, or if
 * this has been explicitly required (parameter [code]users_with_email_display[/code] set in [script]control/configure.php[/script]).
 *
 * @link http://www.hypothetic.org/docs/msn/index.php MSN Messenger Protocol
 *
 * The list of most recent pages from this user is displayed in the main panel.
 * Only the author and associates can see articles that have not been published yet.
 *
 * Articles appear in the Watch list in following cases:
 * - the user is the initial poster of the page
 * - the user has edited the page
 * - the user has added a comment, or an image, or a file
 * - the surfer has explicitly asked for it
 *
 * The extra panel has following components:
 * - An extra box with shortcuts to contribute to the server, including bookmarklets, if this is the surfer profile
 * - A link to the related rss feed, as an extra box
 * - The list of most popular articles, if any, as an extra box
 * - The list of most popular files, if any, as an extra box
 * - The nearest locations, if any, into an extra box
 * - Means to reference this page, into a sidebar box
 * - Top popular referrals, if any
 * - Browser time shift information, if this is the surfer profile
 *
 * Several HTTP headers, or &lt;meta&gt; attributes of the displayed page, are set dynamically here
 * to help advanced web usage. This includes:
 * - a link to a RSS feed for this user profile (e.g., '&lt;link rel="alternate" href="http://127.0.0.1/yacs/users/feed.php/4038" title="RSS" type="application/rss+xml" /&gt;')
 * - a link to a RDF description of this page (e.g., '&lt;link rel="meta" href="http://127.0.0.1/yacs/users/describe.php/4310" title="rdf" type="application/rdf+xml" /&gt;')
 *
 * Restrictions apply on this page:
 * - associates are allowed to move forward
 * - this is the personal record of the authenticated surfer
 * - access is restricted ('active' field == 'R'), but the surfer is an authenticated member
 * - public access is allowed ('active' field == 'Y')
 * - permission denied is the default
 *
 * Accept following invocations:
 * - view.php (show my profile if I am logged)
 * - view.php/12 (view the first page of the user profile)
 * - view.php?id=12 (view the first page of the user profile)
 * - view.php/12/actions/2 (view the page 2 of the list of actions given to this user)
 * - view.php?id=12&actions=2 (view the page 2 of the list of actions given to this user)
 * - view.php/12/articles/2 (view the page 2 of the list of articles contributed by this user)
 * - view.php?id=12&articles=2 (view the page 2 of the list of articles contributed by this user)
 * - view.php/12/files/2 (view the page 2 of the list of files sent by this user)
 * - view.php?id=12&files=2 (view the page 2 of the list of files sent by this user)
 * - view.php/12/links/1 (view the page 1 of the list of links sent by this user)
 * - view.php?id=12&links=1 (view the page 1 of the list of links sent by this user)
 * - view.php/12/bookmarks/2 (view the page 2 of the list of pages bookmarked by this user)
 * - view.php?id=12&bookmarks=2 (view the page 2 of the list of pages bookmarked by this user)
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @tester Moi-meme
 * @tester Guillaume Perez
 * @tester AnsteyER
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../actions/actions.php';
include_once '../categories/categories.php';	// tags and categories
include_once '../files/files.php';
include_once '../links/links.php';
include_once '../locations/locations.php';

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

// view.php?id=12&actions=2
if(!isset($zoom_index) && isset($_REQUEST['actions']) && ($zoom_index = $_REQUEST['actions']))
	$zoom_type = 'actions';

// view.php?id=12&articles=2
if(!isset($zoom_index) && isset($_REQUEST['articles']) && ($zoom_index = $_REQUEST['articles']))
	$zoom_type = 'articles';

// view.php?id=12&files=2
if(!isset($zoom_index) && isset($_REQUEST['files']) && ($zoom_index = $_REQUEST['files']))
	$zoom_type = 'files';

// view.php?id=12&links=2
if(!isset($zoom_index) && isset($_REQUEST['links']) && ($zoom_index = $_REQUEST['links']))
	$zoom_type = 'links';

// view.php?id=12&bookmarks=2
if(!isset($zoom_index) && isset($_REQUEST['bookmarks']) && ($zoom_index = $_REQUEST['bookmarks']))
	$zoom_type = 'bookmarks';

// view.php/12/files/2
if(!isset($zoom_index) && isset($context['arguments'][1]) && isset($context['arguments'][2])) {
	$zoom_type = $context['arguments'][1];
	$zoom_index = $context['arguments'][2];
}

// get the item from the database
$item =& Users::get($id);

// get the related overlay, if any
$overlay = NULL;
include_once '../overlays/overlay.php';
if(isset($item['overlay']))
	$overlay = Overlay::load($item);

// actual capability of current surfer
if(isset($item['id']) && Surfer::get_id() && ($item['id'] == Surfer::get_id()) && ($item['capability'] != '?'))
	Surfer::empower();

// associates can do what they want
if(Surfer::is_associate())
	$permitted = TRUE;

// the record of the authenticated surfer
elseif(isset($item['id']) && Surfer::is_creator($item['id']))
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

// load localized strings
i18n::bind('users');

// load the skin
load_skin('users');

// the path to this page
$context['path_bar'] = array( 'users/' => i18n::s('People') );

// the title of the page
if(isset($item['full_name']) && $item['full_name']) {
	if(strpos($item['full_name'], $item['nick_name']) === FALSE)
		$context['page_title'] = $item['full_name'].' <span style="font-size: smaller;">- '.$item['nick_name'].'</span>';
	else
		$context['page_title'] = $item['full_name'];
} elseif(isset($item['nick_name']))
	$context['page_title'] = $item['nick_name'];

// not found -- help web crawlers
if(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Users::get_url($item['id'])));

	// permission denied to authenticated user
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// display the user profile
} else {

	// remember surfer visit
	Surfer::click('user:'.$item['id'], $item['active']);

	// initialize the rendering engine
	Codes::initialize(Users::get_url($item['id'], 'view', $item['nick_name']));

	//
	// the tab to contributions
	//
	$contributions = '';

	// details
	$details = array();

	// the number of posts
	if(isset($item['posts']) && ($item['posts'] > 1))
		$details[] = sprintf(i18n::s('%d posts'), $item['posts']);

	// the date of last login
	if(Surfer::is_associate() && isset($item['login_date']) && $item['login_date'])
		$details[] = sprintf(i18n::s('last login %s'), Skin::build_date($item['login_date']));

	// the date of registration
	if(isset($item['create_date']) && $item['create_date'])
		$details[] = sprintf(i18n::s('registered %s'), Skin::build_date($item['create_date']));

	// combine these three items into one
	if(count($details))
		$details = array( implode(', ', $details) );

	// the capability field is displayed only to logged users
	if(!Surfer::is_logged())
		;
	elseif($item['capability'] == 'A') {

		// add links to contribute to this site
		if(Surfer::is_creator($id))
			$details[] = i18n::s('As an associate of this community, you may contribute freely to any part of this server.');
		else
			$details[] = i18n::s('As an associate of this community, this user has unlimited rights (and duties) on this server.');

	} elseif($item['capability'] == 'M') {

		// add links to contribute to this site
		if(Surfer::is_creator($id))
			$details[] = i18n::s('As a member of this community, you may access freely most pages of this server.');
		else
			$details[] = i18n::s('Member of this community, with contribution rights to this server.');

	} elseif($item['capability'] == 'S') {
		if(Surfer::is_creator($id))
			$details[] = i18n::s('As a subscriber of this community, you may browse public pages and receive newsletters periodically.');
		else
			$details[] = i18n::s('Subscriber of this community, allowed to browse public pages and to receive e-mail newsletters.');

	} elseif($item['capability'] == '?') {
		if(Surfer::is_associate())
			$details[] = EXPIRED_FLAG.i18n::s('This surfer has been banned and cannot authenticate.');
	}

	// locked profile
	if(Surfer::is_member() && preg_match('/\blocked\b/i', $item['options']) ) {
		if(Surfer::is_associate())
			$details[] = LOCKED_FLAG.' '.i18n::s('Profil is locked, but you can modify it.');
		elseif(Surfer::is_creator($item['id']))
			$details[] = LOCKED_FLAG.' '.i18n::s('You are not allowed to modify your user profile.');
		else
			$details[] = LOCKED_FLAG.' '.i18n::s('Profil is locked, except to associates.');
	}

	// provide details
	if(count($details))
		$contributions .= '<p class="details">'.implode(BR."\n", $details).'</p>'.BR;

	// managed sections
	//

	// list assigned sections, if any
	if(!$items = Sections::list_assigned_by_title($item['id'], 0, 20, 'compact'))
		$items = array();

	// the maximum number of personal sections per user
	if(!isset($context['users_maximum_managed_sections']))
		$context['users_maximum_managed_sections'] = 0;

	// offer to extend personal spaces
	$allowed = max($context['users_maximum_managed_sections'] - count(Surfer::personal_sections()), 0);
	if(Surfer::is_member() && (Surfer::get_id() == $item['id']) && $allowed)
		$items = array_merge($items, array('sections/new.php' => i18n::s('Add a blog, a discussion board, or another personal web space')));

	// associates can assign editors and readers
	elseif(Surfer::is_associate()) {

		// members can become editors of some sections
		if(($item['capability'] == 'M') || ($item['capability'] == 'A'))
			$label = i18n::s('Select sections managed by this user');

		// subscribers can become readers of some sections
		else
			$label = i18n::s('Select sections accessed by this user');

		$items = array_merge($items, array('sections/select.php?anchor=user:'.$item['id'] => $label));
	}

	// one box for assigned sections
	if(count($items)) {
		$content = Skin::build_list($items, 'compact');
		$contributions .= Skin::build_box(i18n::s('Assigned sections'), $content, 'header1', 'assigned_sections');
	}

	// contributed articles
	//

	// the list of contributed articles if not at another follow-up page
	if(!isset($zoom_type) || ($zoom_type == 'articles')) {

		// cache the section
		if(!isset($zoom_index))
			$zoom_index = 1;
		$cache_id = 'users/view.php?id='.$item['id'].'#contributed_articles#'.$zoom_index;
		if(!$text =& Cache::get($cache_id)) {

			// build a complete box
			$box['bar'] = array();
			$box['text'] = '';

			// count the number of articles for this user
			$stats = Articles::stat_for_author($item['id']);
			if($stats['count'] > ARTICLES_PER_PAGE)
				$box['bar'] = array_merge($box['bar'], array('_count' => sprintf(i18n::ns('1 page', '%d pages', $stats['count']), $stats['count'])));

			// navigation commands for articles
//			$home = Users::get_url($item['id'], 'view', $item['title']);
//			$prefix = Users::get_url($item['id'], 'navigate', 'articles');
//			$box['bar'] = array_merge($box['bar'],
//				Skin::navigate($home, $prefix, $stats['count'], ARTICLES_PER_PAGE, $zoom_index));

			// the command to post a new article
			if((Surfer::get_id() == $item['id']) && Surfer::is_member())
				$box['bar'] = array_merge($box['bar'], array( 'articles/edit.php' => i18n::s('Add a page') ));

			// compute offset from list beginning
			$offset = ($zoom_index - 1) * ARTICLES_PER_PAGE;

			// list articles by date
			$items = Articles::list_by_date_for_author($item['id'], $offset, ARTICLES_PER_PAGE, 'simple');
			if(is_array($items))
				$box['text'] .= Skin::build_list($items, 'compact');

			// append a menu bar below the list
			if(is_array($box['bar']))
				$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');

			// a complete box
			if($box['text'])
				$text =& Skin::build_box(i18n::s('Recent pages'), $box['text'], 'header1', 'contributed_articles');

			// save in cache
			Cache::put($cache_id, $text, 'articles');
		}

		// embed a box for articles
		$contributions .= $text;
	}

	// the list of contributed files if not at another follow-up page
	if(!isset($zoom_type)) {

		// cache the section
		if(!isset($zoom_index))
			$zoom_index = 1;
		$cache_id = 'users/view.php?id='.$item['id'].'#contributed_files#'.$zoom_index;
		if(!$text =& Cache::get($cache_id)) {

			// build a complete box
			$box['text'] = '';

			// list files by date
			$items = Files::list_by_date_for_author($item['id'], 0, 20, 'simple');

			// actually render the html for the section
			if(is_array($items))
				$box['text'] .= Skin::build_list($items, 'compact');
			if($box['text'])
				$text =& Skin::build_box(i18n::s('Recent files'), $box['text'], 'header1', 'contributed_files');

			// save in cache
			Cache::put($cache_id, $text, 'files');
		}

		// embed a box for files
		$contributions .= $text;
	}

	// the list of contributed links if not at another follow-up page
	if(!isset($zoom_type)) {

		// cache the section
		if(!isset($zoom_index))
			$zoom_index = 1;
		$cache_id = 'users/view.php?id='.$item['id'].'#contributed_links#'.$zoom_index;
		if(!$text =& Cache::get($cache_id)) {

			// build a complete box
			$box['text'] = '';

			// list links by date
			$items = Links::list_by_date_for_author($item['id'], 0, 20, 'simple');

			// actually render the html for the section
			if(is_array($items))
				$box['text'] .= Skin::build_list($items, 'compact');
			if($box['text'])
				$text =& Skin::build_box(i18n::s('Recent links'), $box['text'], 'header1', 'contributed_links');

			// save in cache
			Cache::put($cache_id, $text, 'links');
		}

		// embed a box for links
		$contributions .= $text;
	}

	//
	// the tab to information
	//

	// we return some HTML
	$information = '';

	// tags
	if(isset($item['tags']) && $item['tags']) {
		$tags = explode(',', $item['tags']);
		$line = '';
		foreach($tags as $tag) {
			if($category = Categories::get_by_keyword(trim($tag)))
				$line .= Skin::build_link(Categories::get_url($category['id'], 'view', $category['title']), trim($tag), 'basic').' ';
			else
				$line .= trim($tag).' ';
		}
		$information .= '<p class="tags">'.sprintf(i18n::s('Tags: %s'), trim($line)).'</p>'."\n";
	}

	// get text related to the overlay, if any
	if(is_object($overlay))
		$information .= $overlay->get_text('view', $item);

	// the full text
	if(isset($item['description']) && $item['description'])
		$information .= '<div class="description">'.Codes::beautify($item['description'])."</div>\n";

	$bottom_menu = array();

	// anyone can modify his own profile; associates can do what they want
	if(isset($item['id']) && !isset($zoom_type) && Surfer::is_empowered())
		$bottom_menu = array_merge($bottom_menu, array( Users::get_url($item['id'], 'edit') => i18n::s('Edit') ));

	// anyone can modify his own profile; associates can do what they want
	if(isset($item['id']) && !isset($zoom_type) && Surfer::may_upload() && Surfer::is_empowered())
		$bottom_menu = array_merge($bottom_menu, array( 'images/edit.php?anchor=user:'.$item['id'] => i18n::s('Add an image') ));

	// to come after the menu
	$bottom_content = '';

	// list files
	//
	$items = Files::list_by_date_for_anchor('user:'.$item['id'], 0, FILES_PER_PAGE, 'no_author');
	if(is_array($items))
		$items = Skin::build_list($items, 'decorated');

	// append a box
	if($items) {

		// the command to post a new file
		if((Surfer::is_creator($item['id']) || Surfer::is_associate()) && Surfer::may_upload()) {
			$menu = array( 'files/edit.php?anchor=user:'.$item['id'] => i18n::s('Upload a file') );
			$items .= Skin::build_list($menu, 'menu_bar');
		}

		// a full box
		$bottom_content .= Skin::build_box(i18n::s('Files'), $items, 'header1', 'related_files');

	// or just offer to upload a file
	} elseif((Surfer::is_creator($item['id']) || Surfer::is_associate()) && Surfer::may_upload())
		$bottom_menu = array_merge($bottom_menu, array( 'files/edit.php?anchor=user:'.$item['id'] => i18n::s('Upload a file') ));

	// list links
	//
	if(preg_match('/\blinks_by_title\b/i', $item['options']))
		$items = Links::list_by_title_for_anchor('user:'.$item['id'], 0, LINKS_PER_PAGE, 'no_author');
	else
		$items = Links::list_by_date_for_anchor('user:'.$item['id'], 0, LINKS_PER_PAGE, 'no_author');
	if(is_array($items))
		$items = Skin::build_list($items, 'decorated');

	// append a box
	if($items) {

		// the command to add a new link
		if(Surfer::is_creator($item['id']) || Surfer::is_associate()) {
			$menu = array( 'links/edit.php?anchor=user:'.$item['id'] => i18n::s('Add a link') );
			$items .= '<p style="margin: 0 0 1em 0;">'.Skin::build_list($menu, 'menu').'</p>';
		}

		// a full box
		$bottom_content .= Skin::build_box(i18n::s('See also'), $items, 'header1', 'related_links');

	// or just offer to add a link
	} elseif(Surfer::is_creator($item['id']) || Surfer::is_associate())
		$bottom_menu = array_merge($bottom_menu, array( 'links/edit.php?anchor=user:'.$item['id'] => i18n::s('Add a link') ));

	// finalize the thing
	if(count($bottom_menu))
		$information .= Skin::build_list($bottom_menu, 'menu_bar');

	$information .= $bottom_content;

	// assemble tabs
	$all_tabs = array(
		array('contributions_tab', i18n::s('Contributions'), 'contributions_panel', $contributions),
		array('information_tab', i18n::s('Information'), 'information_panel', $information),
		array('contact_tab', i18n::s('Contact'), 'contact_panel', NULL, Users::get_url($item['id'], 'element', 'contact')),
		array('actions_tab', i18n::s('Actions'), 'actions_panel', NULL, Users::get_url($item['id'], 'element', 'actions')),
		array('watch_tab', i18n::s('Watch list'), 'watch_panel', NULL, Users::get_url($item['id'], 'element', 'watch'))
		);

	// show preferences only to related surfers and to associates
	if((Surfer::get_id() == $item['id']) || Surfer::is_associate())
		$all_tabs = array_merge($all_tabs, array(array('preferences_tab', i18n::s('Preferences'), 'preferences_panel', NULL, Users::get_url($item['id'], 'element', 'preferences'))));

	// let YACS do the hard job
	$context['text'] .= Skin::build_tabs($all_tabs);

	// bottom menu
	$menu = array();

	// only associates can delete user profiles; self-deletion may also be allowed
	if(isset($item['id']) && !isset($zoom_type) && $permitted
		&& (Surfer::is_associate()
			|| (Surfer::is_creator($item['id']) && (!isset($context['users_without_self_deletion']) || ($context['users_without_self_deletion'] != 'Y'))))) {

		$menu[] = Skin::build_link(Users::get_url($item['id'], 'delete'), i18n::s('Delete'), 'span');
	}

	// the print command is provided to logged users, or to any user in Wiki mode
	if(isset($item['id']) && !isset($zoom_type) && $permitted && (Surfer::is_logged() || (isset($context['users_with_auto_publish']) && ($context['users_with_auto_publish'] == 'Y')) )) {
		$menu[] = Skin::build_link(Users::get_url($item['id'], 'print'), i18n::s('Print'), 'span');
	}

	// append the menu
	if(count($menu))
		$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	//
	// populate the extra panel
	//

	// user profile aside
	$context['extra'] .= Skin::build_profile($item, 'extra');

	// on my page, offer tools to create articles
	if(isset($item['id']) && Surfer::is_member() && ($item['id'] == Surfer::get_id())) {

		// contribute now
		$label = i18n::s('Contribute now:')."\n";;

		// shortcuts

		$link_list = array(
				'articles/edit.php' => array(NULL, i18n::s('Add a page'), NULL, 'shortcut', NULL, i18n::s('Use a web form to submit new content'))
				);

		$label .= Skin::build_list($link_list, 'compact');

		// contribute later
		$label .= BR.i18n::s('Bookmark for later use:')."\n".'<ul>'."\n";

		// the blogging bookmarklet uses YACS codes
		$bookmarklet = "javascript:function findFrame(f){var i;try{isThere=f.document.selection.createRange().text;}catch(e){isThere='';}if(isThere==''){for(i=0;i&lt;f.frames.length;i++){findFrame(f.frames[i]);}}else{s=isThere}return s}"
			."var s='';"
			."d=document;"
			."s=d.selection?findFrame(window):window.getSelection();"
			."window.location='".$context['url_to_home'].$context['url_to_root']."articles/edit.php?"
				."title='+escape(d.title)+'"
				."&amp;text='+escape('%22'+s+'%22%5Bnl]-- %5Blink='+d.title+']'+d.location+'%5B/link]')+'"
				."&amp;source='+escape(d.location);";
		$label .= '<li><a href="'.$bookmarklet.'">'.sprintf(i18n::s('Blog at %s'), $context['site_name']).'</a></li>'."\n";

		// the bookmarking bookmarklet
		$bookmarklet = "javascript:function findFrame(f){var i;try{isThere=f.document.selection.createRange().text;}catch(e){isThere='';}if(isThere==''){for(i=0;i&lt;f.frames.length;i++){findFrame(f.frames[i]);}}else{s=isThere}return s}"
			."var s='';"
			."d=document;"
			."s=d.selection?findFrame(window):window.getSelection();"
			."window.location='".$context['url_to_home'].$context['url_to_root']."links/edit.php?"
				."link='+escape(d.location)+'"
				."&amp;title='+escape(d.title)+'"
				."&amp;text='+escape(s);";
		$label .= '<li><a href="'.$bookmarklet.'">'.sprintf(i18n::s('Bookmark at %s'), $context['site_name']).'</a></li>'."\n";

		// end of bookmarklets
		$label .= '</ul>'."\n";

		// an extra box
		$context['extra'] .= Skin::build_box(i18n::s('Tools'), $label, 'extra');
	}

	// get news from rss
	if(isset($item['id']) && isset($item['capability']) && (($item['capability'] == 'A') || ($item['capability'] == 'M')) && (!isset($context['skins_general_without_feed']) || ($context['skins_general_without_feed'] != 'Y')) ) {

		$content = Skin::build_link($context['url_to_home'].$context['url_to_root'].Users::get_url($item['id'], 'feed'), i18n::s('recent pages'), 'xml');

		// public aggregators
		if(!isset($context['without_internet_visibility']) || ($context['without_internet_visibility'] != 'Y'))
			$content .= BR.join(BR, Skin::build_subscribers($context['url_to_home'].$context['url_to_root'].Users::get_url($item['id'], 'feed'), $item['nick_name']));

		$context['extra'] .= Skin::build_box(i18n::s('Stay tuned'), $content, 'extra', 'feeds');
	}

	// the most popular articles for this user
	$cache_id = 'users/view.php?id='.$item['id'].'#popular_articles';
	if(!$text =& Cache::get($cache_id)) {
		$items = Articles::list_by_hits_for_author($item['id'], 0, COMPACT_LIST_SIZE, 'hits');
		if(is_array($items))
			$text .= Skin::build_box(i18n::s('Top pages'), Skin::build_list($items, 'compact'), 'extra', 'top_articles');

		// save in cache
		Cache::put($cache_id, $text, 'articles');
	}
	$context['extra'] .= $text;

	// the most popular files for this user
	$cache_id = 'users/view.php?id='.$item['id'].'#popular_files';
	if(!$text =& Cache::get($cache_id)) {
		$items = Files::list_by_hits_for_author($item['id'], 0, COMPACT_LIST_SIZE);
		if(is_array($items))
			$text .= Skin::build_box(i18n::s('Top files'), Skin::build_list($items, 'compact'), 'extra', 'top_files');

		// save in cache
		Cache::put($cache_id, $text, 'files');
	}
	$context['extra'] .= $text;

	// categories attached to this item, if not at another follow-up page
	if(!isset($zoom_type) || ($zoom_type == 'categories')) {

		// build a complete box
		$box['bar'] = array();
		$box['text'] = '';

		// list categories by title
		$items = Members::list_categories_by_title_for_member('user:'.$item['id'], 0, COMPACT_LIST_SIZE, 'sidebar');

		// the command to change categories assignments
		if(Categories::are_allowed(NULL, $item))
			$items = array_merge($items, array( Categories::get_url('user:'.$item['id'], 'select') => i18n::s('Assign categories') ));

		// actually render the html for the section
		if(is_array($box['bar']))
			$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');
		if(is_array($items))
			$box['text'] .= Skin::build_list($items, 'compact');
		if($box['text'])
			$context['extra'] .= Skin::build_box(i18n::s('See also'), $box['text'], 'navigation', 'categories');

	}

	// neighbours, if any
	if(!isset($zoom_type)) {

		// cache the section
		$cache_id = 'users/view.php?id='.$item['id'].'#neighbours#';
		if(!$text =& Cache::get($cache_id)) {

			// locate up to 5 neighbours
			$items = Locations::list_by_distance_for_anchor('user:'.$item['id'], 0, 5);
			if(is_array($items))
				$text .= Skin::build_box(i18n::s('Neighbours'), Skin::build_list($items, 'compact'), 'extra', 'locations');

			// avoid subsequent queries
			else
				$text = ' ';

			Cache::put($cache_id, $text, 'locations');

		}
		$context['extra'] .= $text;
	}

	// how to reference this page
	if(Surfer::is_member() && !isset($zoom_type) && (!isset($context['pages_without_reference']) || ($context['pages_without_reference'] != 'Y')) ) {

		// box content
		$label = sprintf(i18n::s('Here, use code %s'), '<code>[user='.$item['id'].']</code>')."\n"
			.BR.sprintf(i18n::s('Elsewhere, bookmark the %s'), Skin::build_link(Users::get_url($item['id'], 'view', $item['nick_name']), i18n::s('full link')))."\n";

		// in a sidebar box
		$text .= Skin::build_box(i18n::s('Reference this page'), $label, 'navigation', 'reference');
	}

	// referrals, if any
	if(!isset($zoom_type) && (Surfer::is_associate() || (isset($context['with_referrals']) && ($context['with_referrals'] == 'Y')))) {

		// cache the box
		$cache_id = 'users/view.php?id='.$item['id'].'#referrals#';
		if(!$text =& Cache::get($cache_id)) {

			// box content
			include_once '../agents/referrals.php';
			$text = Referrals::list_by_hits_for_url($context['url_to_root_parameter'].Users::get_url($item['id']));

			// in a sidebar box
			if($text)
				$text =& Skin::build_box(i18n::s('Referrals'), $text, 'navigation', 'referrals');

			// save in cache for one hour 60 * 60 = 3600
			Cache::put($cache_id, $text, 'referrals', 3600);

		}

		// in the extra panel
		$context['extra'] .= $text;

	}

	// display workstation time offset
	if(!isset($zoom_type) && Surfer::is_logged() && Surfer::is_creator($item['id'])) {

		// box content
		$text = '<script type="text/javascript">// <![CDATA['."\n"
			.'now = new Date();'."\n"
			.'offset = (-now.getTimezoneOffset() / 60);'."\n"
			.'document.write("<p>UTC " + ((offset > 0) ? "+" : "-") + offset + " '.i18n::s('hour(s)').'</p>");'."\n"
			.'// ]]></script>'."\n";

		// in the extra panel
		$context['extra'] .= Skin::build_box(i18n::s('Browser GMT offset'), $text, 'navigation', 'time_offset');

	}

	//
	// meta information
	//

	// a meta link to a feeding page
	$context['page_header'] .= "\n".'<link rel="alternate" href="'.$context['url_to_root'].Users::get_url($item['id'], 'feed').'" title="RSS" type="application/rss+xml"'.EOT;

	// a meta link to a description page (actually, rdf)
	$context['page_header'] .= "\n".'<link rel="meta" href="'.$context['url_to_root'].Users::get_url($item['id'], 'describe').'" title="Meta Information" type="application/rdf+xml"'.EOT;

	// set specific headers
	if(isset($item['introduction']) && $item['introduction'])
		$context['page_description'] = $item['introduction'];
	if(isset($item['create_name']) && $item['create_name'])
		$context['page_author'] = $item['create_name'];

	// load the google api if necessary--it breaks when loaded from Ajax.Updater
	if(preg_match('/\[location.+/', $item['description'])) {
		include_once $context['path_to_root'].'locations/locations.php';
		$context['site_trailer'] .= Locations::map_on_google_header();
	}

}

// render the skin
render_skin();

?>