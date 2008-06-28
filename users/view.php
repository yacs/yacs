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
 * @author Bernard Paques
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

// no follow-up page yet
$zoom_type = '';
$zoom_index = 1;

// view.php?id=12&actions=2
if(!isset($zoom_index) && isset($_REQUEST['actions']) && ($zoom_index = $_REQUEST['actions']))
	$zoom_type = 'actions';

// view.php?id=12&articles=2
elseif(!isset($zoom_index) && isset($_REQUEST['articles']) && ($zoom_index = $_REQUEST['articles']))
	$zoom_type = 'articles';

// view.php?id=12&files=2
elseif(!isset($zoom_index) && isset($_REQUEST['files']) && ($zoom_index = $_REQUEST['files']))
	$zoom_type = 'files';

// view.php?id=12&links=2
elseif(!isset($zoom_index) && isset($_REQUEST['links']) && ($zoom_index = $_REQUEST['links']))
	$zoom_type = 'links';

// view.php?id=12&bookmarks=2
elseif(!isset($zoom_index) && isset($_REQUEST['bookmarks']) && ($zoom_index = $_REQUEST['bookmarks']))
	$zoom_type = 'bookmarks';

// view.php/12/files/2
elseif(isset($context['arguments'][1]) && isset($context['arguments'][2])) {
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
if(isset($item['full_name']) && $item['full_name']) {
	if(strpos($item['full_name'], $item['nick_name']) === FALSE)
		$context['page_title'] = $item['full_name'].' <span style="font-size: smaller;">- '.$item['nick_name'].'</span>';
	else
		$context['page_title'] = $item['full_name'];
} elseif(isset($item['nick_name']))
	$context['page_title'] = $item['nick_name'];

// anyone can modify his own profile; associates can do what they want
if(isset($item['id']) && !$zoom_type && Surfer::is_empowered()) {
	Skin::define_img('EDIT_USER_IMG', 'icons/users/edit.gif');
	$context['page_menu'] = array_merge($context['page_menu'], array( Users::get_url($item['id'], 'edit') => EDIT_USER_IMG.i18n::s('Edit this page') ));
}

// only associates can delete user profiles; self-deletion may also be allowed
if(isset($item['id']) && !$zoom_type && $permitted
	&& (Surfer::is_associate()
		|| (Surfer::is($item['id']) && (!isset($context['users_without_self_deletion']) || ($context['users_without_self_deletion'] != 'Y'))))) {

	$context['page_menu'] = array_merge($context['page_menu'], array( Users::get_url($item['id'], 'delete') => i18n::s('Delete') ));
}

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
	// meta-information -- $context['page_header'], etc.
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

	//
	// page details -- $context['page_details']
	//

	// tags, if any
	if(isset($item['tags']) && $item['tags'])
		$context['page_tags'] = $item['tags'];

	// one detail per line
	$context['page_details'] .= '<p class="details">';
	$details = array();

	// the capability field is displayed only to logged users
	if(!Surfer::is_logged())
		;
	elseif($item['capability'] == 'A')
		$details[] = i18n::s('Associate');

	elseif($item['capability'] == 'M')
		$details[] = i18n::s('Member');

	elseif($item['capability'] == 'S')
		$details[] = i18n::s('Subscriber');

	elseif($item['capability'] == '?')
			$details[] = EXPIRED_FLAG.i18n::s('Banned');

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
		$context['page_details'] .= ucfirst(implode(', ', $details));

	// reference this item
	if(Surfer::is_member())
		$context['page_details'] .= BR.sprintf(i18n::s('Code to reference this user: %s'), '[user='.$item['nick_name'].']');

	$context['page_details'] .= '</p>';

	//
	// tabbed panels
	//
	$panels = array();

	//
	// the tab to contributions
	//
	$contributions = '';

	// managed sections
	//

	// the list of assigned sections
	if(!$zoom_type) {

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
				$label = i18n::s('Select sections managed by this person');

			// subscribers can become readers of some sections
			else
				$label = i18n::s('Select sections accessed by this person');

			$items = array_merge($items, array('sections/select.php?anchor=user:'.$item['id'] => $label));
		}

		// one box for assigned sections
		if(count($items)) {
			$content = Skin::build_list($items, 'compact');
			$contributions .= Skin::build_box(i18n::s('Assigned sections'), $content, 'header1', 'assigned_sections');
		}
	}

	// contributed articles
	//

	// the list of contributed articles if not at another follow-up page
	if(!$zoom_type || ($zoom_type == 'articles')) {

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
			$home = Users::get_url($item['id'], 'view', $item['nick_name']);
			$prefix = Users::get_url($item['id'], 'navigate', 'articles');
			$box['bar'] = array_merge($box['bar'],
				Skin::navigate($home, $prefix, $stats['count'], ARTICLES_PER_PAGE, $zoom_index));

			// the command to post a new article
			if((Surfer::get_id() == $item['id']) && Surfer::is_member())
				$box['bar'] = array_merge($box['bar'], array( 'articles/edit.php' => i18n::s('Add a page') ));

			// append a menu bar before the list on pages 2, 3, ...
			if(count($box['bar']) && ($zoom_index > 1))
				$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');

			// compute offset from list beginning
			$offset = ($zoom_index - 1) * ARTICLES_PER_PAGE;

			// list articles by date
			$items = Articles::list_by_date_for_author($item['id'], $offset, ARTICLES_PER_PAGE, 'simple');
			if(is_array($items))
				$box['text'] .= Skin::build_list($items, 'compact');

			// append a menu bar below the list
			if(count($box['bar']))
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
	if(!$zoom_type) {

		// cache the section
		if(!isset($zoom_index))
			$zoom_index = 1;
		$cache_id = 'users/view.php?id='.$item['id'].'#contributed_files#'.$zoom_index;
		if(!$text =& Cache::get($cache_id)) {

			// build a complete box
			$box['text'] = '';

			// avoid links to this page
			include_once '../files/layout_files_as_simple.php';
			$layout =& new Layout_files_as_simple();
			if(is_object($layout))
				$layout->set_variant('user:'.$item['id']);

			// list files by date
			$items = Files::list_by_date_for_author($item['id'], 0, 20, $layout);

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
	if(!$zoom_type) {

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

	// in a separate panel
	if(trim($contributions))
		$panels[] = array('contributions_tab', i18n::s('Contributions'), 'contributions_panel', $contributions);

	//
	// the tab to information
	//
	$information = '';

	// if not at another follow-up page
	if(!$zoom_type) {

		// local menu
		$menu = array();

		// the command to post a new file
		if((Surfer::is($item['id']) || Surfer::is_associate()) && Surfer::may_upload())
			$menu[] = Skin::build_link('files/edit.php?anchor=user:'.$item['id'], i18n::s('Upload a file'), 'basic');

		// the command to add a new link
		if(Surfer::is($item['id']) || Surfer::is_associate())
			$menu[] = Skin::build_link('links/edit.php?anchor=user:'.$item['id'], i18n::s('Add a link'), 'basic');

		if(count($menu))
			$information .= Skin::finalize_list($menu, 'menu_bar');

		// get text related to the overlay, if any
		if(is_object($overlay))
			$information .= $overlay->get_text('view', $item);

		// the full text
		if(isset($item['description']) && $item['description'])
			$information .= '<div class="description">'.Codes::beautify($item['description'])."</div>\n";

		// list files
		//
		$items = Files::list_by_date_for_anchor('user:'.$item['id'], 0, FILES_PER_PAGE, 'no_author');
		if(is_array($items))
			$items = Skin::build_list($items, 'decorated');
		if($items)
			$information .= Skin::build_box(i18n::s('Files'), $items, 'header1', 'related_files');

		// list links
		//
		if(preg_match('/\blinks_by_title\b/i', $item['options']))
			$items = Links::list_by_title_for_anchor('user:'.$item['id'], 0, LINKS_PER_PAGE, 'no_author');
		else
			$items = Links::list_by_date_for_anchor('user:'.$item['id'], 0, LINKS_PER_PAGE, 'no_author');
		if(is_array($items))
			$items = Skin::build_list($items, 'decorated');
		if($items)
			$information .= Skin::build_box(i18n::s('Links'), $items, 'header1', 'related_links');
	}

	// in a separate tab
	if(trim($information))
		$panels[] = array('information_tab', i18n::s('Information'), 'information_panel', $information);

	//
	// assemble tabs
	//
	if(!$zoom_type)
		$panels[] = array('contact_tab', i18n::s('Contact'), 'contact_panel', NULL, Users::get_url($item['id'], 'element', 'contact'));
	if(!$zoom_type)
		$panels[] = array('actions_tab', i18n::s('Actions'), 'actions_panel', NULL, Users::get_url($item['id'], 'element', 'actions'));
	if(!$zoom_type)
		$panels[] = array('watch_tab', i18n::s('Dashboard'), 'watch_panel', NULL, Users::get_url($item['id'], 'element', 'watch'));

	// show preferences only to related surfers and to associates
	if(!$zoom_type && ((Surfer::get_id() == $item['id']) || Surfer::is_associate()))
		$panels = array_merge($panels, array(array('preferences_tab', i18n::s('Preferences'), 'preferences_panel', NULL, Users::get_url($item['id'], 'element', 'preferences'))));

	// let YACS do the hard job
	$context['text'] .= Skin::build_tabs($panels);

	//
	// populate the extra panel
	//

	// user profile aside
	$context['extra'] .= Skin::build_profile($item, 'extra');

	// add extra information from the overlay, if any
	if(is_object($overlay))
		$context['extra'] .= $overlay->get_text('extra', $item);

	// add extra information from this item, if any
	if(isset($item['extra']) && $item['extra'])
		$context['extra'] .= Codes::beautify($item['extra']);

	// page tools
	//

	// tools to maintain my page
	if(Surfer::is_empowered()) {

		// modify this page
		Skin::define_img('EDIT_USER_IMG', 'icons/users/edit.gif');
		$context['page_tools'][] = Skin::build_link(Users::get_url($item['id'], 'edit'), EDIT_USER_IMG.i18n::s('Edit this page'), 'basic');

		// change avatar
		$context['page_tools'][] = Skin::build_link(Users::get_url($item['id'], 'select_avatar'), i18n::s('Change avatar'), 'basic');

		// change password
		if(!isset($context['users_authenticator']) || !$context['users_authenticator'])
			$context['page_tools'][] = Skin::build_link(Users::get_url($item['id'], 'password'), i18n::s('Change password'), 'basic');

	}

	// 'Share' box
	//
	$lines = array();

	// logged users may download the vcard
	if(Surfer::is_logged())
		$lines[] = Skin::build_link(Users::get_url($item['id'], 'fetch_vcard', $item['nick_name']), i18n::s('Get business card'), 'basic');

	// print this page
	if(Surfer::is_logged())
		$lines[] = Skin::build_link(Users::get_url($id, 'print'), i18n::s('Print this page'), 'basic');

	// in a side box
	if(count($lines))
		$context['extra'] .= Skin::build_box(i18n::s('Share'), Skin::finalize_list($lines, 'tools'), 'extra', 'share');

	// 'More information' box
	//
	$lines = array();

	// watch command is provided to logged surfers
	if(Surfer::get_id() && (Surfer::get_id() != $item['id']) && !$zoom_type) {

		$link = Users::get_url('user:'.$item['id'], 'track');

		// is the item on user watch list?
		$in_watch_list = FALSE;
		if(isset($item['id']) && Surfer::get_id())
			$in_watch_list = Members::check('user:'.$item['id'], 'user:'.Surfer::get_id());

		if($in_watch_list)
			$label = i18n::s('Forget');
		else
			$label = i18n::s('Watch');

		Skin::define_img('WATCH_TOOL_IMG', 'icons/tools/watch.gif');
		$lines[] = Skin::build_link($link, WATCH_TOOL_IMG.$label, 'basic', i18n::s('Manage your watch list'));
	}

	// get news from rss
	if(isset($item['capability']) && (($item['capability'] == 'A') || ($item['capability'] == 'M')) && (!isset($context['skins_general_without_feed']) || ($context['skins_general_without_feed'] != 'Y')) ) {

		$lines[] = Skin::build_link($context['url_to_home'].$context['url_to_root'].Users::get_url($item['id'], 'feed'), i18n::s('Recent pages'), 'xml');

		// public aggregators
		if(!isset($context['without_internet_visibility']) || ($context['without_internet_visibility'] != 'Y'))
			$lines[] = join(BR, Skin::build_subscribers($context['url_to_home'].$context['url_to_root'].Users::get_url($item['id'], 'feed'), $item['nick_name']));

	}

	// in a side box
	if(count($lines))
		$context['extra'] .= Skin::build_box(i18n::s('More information'), join(BR, $lines), 'extra', 'feeds');

	// most popular articles for this user
	$cache_id = 'users/view.php?id='.$item['id'].'#popular_articles';
	if(!$text =& Cache::get($cache_id)) {
		$items = Articles::list_by_hits_for_author($item['id'], 0, COMPACT_LIST_SIZE, 'hits');
		if(is_array($items) && count($items))
			$text .= Skin::build_box(i18n::s('Top pages'), Skin::build_list($items, 'compact'), 'extra', 'top_articles');

		// save in cache
		Cache::put($cache_id, $text, 'articles');
	}
	$context['extra'] .= $text;

	//most popular files from this user
	$cache_id = 'users/view.php?id='.$item['id'].'#popular_files';
	if(!$text =& Cache::get($cache_id)) {
		$items = Files::list_by_hits_for_author($item['id'], 0, COMPACT_LIST_SIZE);
		if(is_array($items) && count($items))
			$text .= Skin::build_box(i18n::s('Top files'), Skin::build_list($items, 'compact'), 'extra', 'top_files');

		// save in cache
		Cache::put($cache_id, $text, 'files');
	}
	$context['extra'] .= $text;

	// categories attached to this item, if not at another follow-up page
	if(!$zoom_type || ($zoom_type == 'categories')) {

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
	if(!$zoom_type) {

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

	// referrals, if any
	if(!$zoom_type && (Surfer::is_associate() || (isset($context['with_referrals']) && ($context['with_referrals'] == 'Y')))) {

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

	//
	// put this page in visited items
	//
	if(!isset($context['pages_without_history']) || ($context['pages_without_history'] != 'Y')) {

		// put at top of stack
		if(!isset($_SESSION['visited']))
			$_SESSION['visited'] = array();
		$_SESSION['visited'] = array_merge(array(Users::get_url($item['id'], 'view', $item['nick_name']) => Codes::beautify($item['full_name']?$item['full_name']:$item['nick_name'])), $_SESSION['visited']);

		// limit to 7 most recent pages
		if(count($_SESSION['visited']) > 7)
			array_pop($_SESSION['visited']);

	}

}

// render the skin
render_skin();

?>