<?php
/**
 * view one category
 *
 * The main panel has following elements:
 * - The category itself, with details, introduction, and main text.
 * - The list of sub-categories
 * - The list of related users
 * - The list of related sections
 * - The list of related articles
 * - The list of related files
 * - The list of comments
 * - The list of related links
 *
 * The extra panel has following elements:
 * - A bookmarklet to post bookmarks at this category
 * - A link to the related rss feed, as an extra box
 * - A list of anchored (and feeding) servers, into a sidebar
 * - A search form into a sidebar, if the category has some keyword
 * - Means to reference this page, into a sidebar box
 * - The top popular referrals, if any
 *
 * By default sub-categories are laid out in a decorated table.
 * This can be changed by putting one of following keyword in the field for options:
 * - layout_as_inline - list the entire hierarchy of categories and related articles
 * - layout_as_yahoo - include thumbnail images, and tease with top articles
 *
 * @link http://www.yahoo.com/ Yahoo!
 *
 * Several HTTP headers, or &lt;meta&gt; attributes of the displayed page, are set dynamically
 * to help advanced web usage. This includes:
 * - a link to a RDF description of this page (e.g., '&lt;link rel="alternate" href="http://127.0.0.1/yacs/sections/describe.php/4310" title="rdf" type="application/rdf+xml" /&gt;')
 * - a link to a RSS feed for this category (e.g., '&lt;link rel="alternate" href="http://127.0.0.1/yacs/categories/feed.php/4038" title="RSS" type="application/rss+xml" /&gt;')
 * - a rdf section implementing the [link=trackback]http://www.movabletype.org/docs/mttrackback.html[/link] interface
 * - a [link=pingback]http://www.hixie.ch/specs/pingback/pingback[/link] link (e.g., '&lt;link rel="pingback" href="http://here/yacs/services/pingback.php" /&gt;')
 *
 * Restrictions apply on this page:
 * - associates and editors are allowed to move forward
 * - permission is denied if the anchor is not viewable
 * - access is restricted ('active' field == 'R'), but the surfer is an authenticated member
 * - public access is allowed ('active' field == 'Y')
 * - permission denied is the default
 *
 * Accept following invocations:
 * - view.php/12 (view the first page of the category document)
 * - view.php?id=12 (view the first page of the category document)
 * - view.php/12/categories/1 (view the page 1 of the list of related categories)
 * - view.php?id=12&categories=1 (view the page 1 of the list of related categories)
 * - view.php/12/articles/1 (view the page 1 of the list of related articles)
 * - view.php?id=12&articles=1 (view the page 1 of the list of related articles)
 * - view.php/12/comments/1 (view the page 1 of the list of related comments)
 * - view.php?id=12&comments=1 (view the page 1 of the list of related comments)
 * - view.php/12/files/2 (view the page 2 of the list of related files)
 * - view.php?id=12&files=2 (view the page 2 of the list of related files)
 * - view.php/12/links/1 (view the page 1 of the list of related links)
 * - view.php?id=12&links=1 (view the page 1 of the list of related links)
 *
 * @link http://www.movabletype.org/docs/mttrackback.html TrackBack Technical Specification
 * @link http://www.hixie.ch/specs/pingback/pingback Pingback specification
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @tester Mark
 * @tester Moi-meme
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../comments/comments.php';
include_once '../files/files.php';
include_once '../links/links.php';
include_once '../servers/servers.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// encode ISO-8859-1 argument, if any
if(isset($_SERVER['HTTP_ACCEPT_CHARSET']) && preg_match('/^iso-8859-1/i', $_SERVER['HTTP_ACCEPT_CHARSET']))
	$id = utf8_encode($id);

// zoom, if any
$zoom_type = NULL;
$zoom_index = 1;

// view.php?id=12&categories=2
if(isset($_REQUEST['categories'])  && ($zoom_index = $_REQUEST['categories']))
	$zoom_type = 'categories';

// view.php?id=12&sections=2
elseif(isset($_REQUEST['sections'])  && ($zoom_index = $_REQUEST['sections']))
	$zoom_type = 'sections';

// view.php?id=12&articles=2
elseif(isset($_REQUEST['articles'])  && ($zoom_index = $_REQUEST['articles']))
	$zoom_type = 'articles';

// view.php?id=12&comments=2
elseif(isset($_REQUEST['comments'])  && ($zoom_index = $_REQUEST['comments']))
	$zoom_type = 'comments';

// view.php?id=12&files=2
elseif(isset($_REQUEST['files']) && ($zoom_index = $_REQUEST['files']))
	$zoom_type = 'files';

// view.php?id=12&links=2
elseif(isset($_REQUEST['links']) && ($zoom_index = $_REQUEST['links']))
	$zoom_type = 'links';

// view.php/12/files/2
elseif(isset($context['arguments'][1]) && isset($context['arguments'][2])) {
	$zoom_type = $context['arguments'][1];
	$zoom_index = $context['arguments'][2];
}

// get the item from the database
include_once 'categories.php';
$item =& Categories::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor = Anchors::get($item['anchor']);

// get the related overlay, if any
$overlay = NULL;
include_once '../overlays/overlay.php';
if(isset($item['overlay'])&&$item['overlay']!='')
	$overlay = Overlay::load($item);
elseif(isset($item['overlay_id']))
	$overlay = Overlay::bind($item['overlay_id']);

// associates and editors can do what they want
if(Surfer::is_associate() || (is_object($anchor) && $anchor->is_editable()))
	$permitted = TRUE;

// the anchor has to be viewable by this surfer
elseif(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;

// access is restricted to authenticated member
elseif(($item['active'] == 'R') && Surfer::is_member())
	$permitted = TRUE;

// public access is allowed
elseif($item['active'] == 'Y')
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load localized strings
i18n::bind('categories');

// use a specific skin only for this category -- warning: no validity check
if(isset($item['options']) && preg_match('/\bskin_(.+?)\b/i', $item['options'], $matches))
	$context['skin'] = 'skins/'.$matches[1];

// load the skin
if($item && preg_match('/\bvariant_(.+?)\b/i', $item['options'], $matches))
	load_skin($matches[1]);
else
	load_skin('categories');

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'categories/' => i18n::s('Categories') );

// the title of the page
if($item['title'])
	$context['page_title'] = $item['title'];
else
	$context['page_title'] = i18n::s('Display a category');

// associates can create a sub-category
if(isset($item['id']) && !$zoom_type && $permitted && Categories::are_allowed($anchor, $item))
	$context['page_menu'] = array_merge($context['page_menu'], array( 'categories/edit.php?anchor='.urlencode('category:'.$item['id']) => i18n::s('Create a sub-category') ));

// commands for associates and editors do not appear on follow-up pages
if((!$zoom_type) && (Surfer::is_associate() || (is_object($anchor) && $anchor->is_editable()))) {
	$context['page_menu'] = array_merge($context['page_menu'], array( Categories::get_url($id, 'edit') => i18n::s('Edit') ));
	if(Surfer::may_upload())
		$context['page_menu'] = array_merge($context['page_menu'], array( 'images/edit.php?anchor='.urlencode('category:'.$id) => i18n::s('Add an image') ));
	$context['page_menu'] = array_merge($context['page_menu'], array( Categories::get_url($id, 'delete') => i18n::s('Delete') ));
}

// command to add a link provided to members
if(isset($item['id']) && !$zoom_type && $permitted && Links::are_allowed($anchor, $item, TRUE))
	$context['page_menu'] = array_merge($context['page_menu'], array( 'links/edit.php?anchor='.urlencode('category:'.$item['id']) => i18n::s('Add a link') ));

// the print command is provided to logged users
if(isset($item['id']) && !$zoom_type && $permitted && Surfer::is_logged())
	$context['page_menu'] = array_merge($context['page_menu'], array( Categories::get_url($id, 'print') => i18n::s('Print') ));

// not found -- help web crawlers
if(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Categories::get_url($item['id'], 'view', $item['title'])));

	// permission denied to authenticated user
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// display the category
} else {

	// remember surfer visit
	Surfer::click('category:'.$item['id'], $item['active']);

	// initialize the rendering engine
	Codes::initialize(Categories::get_url($item['id'], 'view', $item['title']));

	// the category or the anchor icon, if any
	if(isset($item['icon_url']) && $item['icon_url'])
		$context['page_image'] = $item['icon_url'];
	elseif(is_object($anchor))
		$context['page_image'] = $anchor->get_icon_url();

	// display very few things if we are on a follow-up page
	if($zoom_type) {

		// insert anchor prefix
		if(is_object($anchor))
			$context['text'] .= $anchor->get_prefix();

		if($item['introduction'])
			$context['text'] .= Codes::beautify($item['introduction'])."<p> </p>\n";
		else
			$context['text'] .= Skin::cap(Codes::beautify($item['description']), 50)."<p> </p>\n";

	// else expose full details
	} else {

		// increment silently the hits counter
		if(!Surfer::is_associate() && (Surfer::get_id() != $item['create_id'])) {
			$item['hits'] += 1;
			Categories::increment_hits($item['id']);
		}

		// use the cache if possible
		$cache_id = 'categories/view.php?id='.$item['id'].'#content';
		if(!$text =& Cache::get($cache_id)) {

			// additional details for associates and editors
			$details = array();
			if(Surfer::is_associate() || (is_object($anchor) && $anchor->is_editable())) {

				// the nick name
				if($item['nick_name'])
					$details[] = '"'.$item['nick_name'].'"';

				// the creator of this category
				if($item['create_date'])
					$details[] = sprintf(i18n::s('posted by %s %s'), Users::get_link($item['create_name'], $item['create_address'], $item['create_id']), Skin::build_date($item['create_date']));

				// hide last edition if done by creator, and if less than 24 hours between creation and last edition
				if($item['create_date'] && ($item['create_id'] == $item['edit_id'])
						&& (strtotime($item['create_date'].' UTC')+24*60*60 >= strtotime($item['edit_date'].' UTC')))
					;

				// the last edition of this category
				else {

					if($item['edit_action'])
						$action = get_action_label($item['edit_action']);
					else
						$action = i18n::s('edited');

					$details[] = sprintf(i18n::s('%s by %s %s'), $action, Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

				}

				// the number of hits
				if($item['hits'] > 1)
					$details[] = sprintf(i18n::s('%d hits'), $item['hits']);

				// all details
				$text .= '<p class="details">';
				if(count($details))
					$text .= ucfirst(implode(', ', $details)).BR."\n";

				// one detail per line
				$details = array();

				// restricted to logged members
				if($item['active'] == 'R')
					$details[] = RESTRICTED_FLAG.' '.i18n::s('Access is restricted to authenticated members');

				// restricted to associates
				if($item['active'] == 'N')
					$details[] = PRIVATE_FLAG.' '.i18n::s('Access is restricted to associates');

				// rank for this category
				if(intval($item['rank']) != 10000)
					$details[] = sprintf(i18n::s('Rank: %s'), $item['rank']);

				// appears in navigation boxes
				if($item['display'] == 'site:all')
					$details[] = i18n::s('Is displayed on all pages, among other navigation boxes');
				elseif($item['display'] == 'home:gadget')
					$details[] = i18n::s('Is displayed in the middle of the front page, among other gadget boxes');
				elseif($item['display'] == 'home:extra')
					$details[] = i18n::s('Is displayed at the front page, among other extra boxes');

				// expired category
				$now = gmstrftime('%Y-%m-%d %H:%M:%S');
				if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $now))
					$details[] = EXPIRED_FLAG.' '.sprintf(i18n::s('Category has expired %s'), Skin::build_date($item['expiry_date']));

				// display details, if any
				if(count($details))
					$text .= ucfirst(implode(BR."\n", $details));

				// end of details
				$text .= "</p>\n";

			}

			// insert anchor prefix
			if(is_object($anchor))
				$text .= $anchor->get_prefix();

			// the introduction text, if any
			if(isset($item['introduction']) && $item['introduction'])
				$text .= Skin::build_block($item['introduction'], 'introduction');

			// get text related to the overlay, if any
			if(is_object($overlay))
				$text .= $overlay->get_text('view', $item);

			// the description, which is the actual page body
			if(isset($item['description']) && $item['description'])
				$text .= Codes::beautify($item['description'])."\n";

			// save in cache if no dynamic element
			if(!preg_match('/\[table=(.+?)\]/i', $item['description']))
				Cache::put($cache_id, $text, 'category:'.$item['id']);
		}
		$context['text'] .= $text;
	}

	//
	// sub-categories of this category
	//

	// the list of related categories if not at another follow-up page
	if( (!isset($zoom_type) || !$zoom_type || ($zoom_type == 'categories'))
		&& (!isset($item['categories_layout']) || ($item['categories_layout'] != 'none')) ) {

		// cache content
		$cache_id = 'categories/view.php?id='.$item['id'].'#categories#'.$zoom_index;
		if(!$text =& Cache::get($cache_id)) {

			// select a layout
			if(!isset($item['categories_layout']) || !$item['categories_layout']) {
				include_once 'layout_categories.php';
				$layout =& new Layout_categories();
			} elseif($item['categories_layout'] == 'decorated') {
				include_once 'layout_categories.php';
				$layout =& new Layout_categories();
			} elseif($item['categories_layout'] == 'map') {
				include_once 'layout_categories_as_yahoo.php';
				$layout =& new Layout_categories_as_yahoo();
			} elseif(is_readable($context['path_to_root'].'categories/layout_categories_as_'.$item['categories_layout'].'.php')) {
				$name = 'layout_categories_as_'.$item['categories_layout'];
				include_once $name.'.php';
				$layout =& new $name;
			} else {

				// useful warning for associates
				if(Surfer::is_associate())
					Skin::error(sprintf(i18n::s('Warning: No script exists for the customized layout %s'), $item['categories_layout']));

				include_once '../categories/layout_categories.php';
				$layout =& new Layout_categories();
			}

			// the maximum number of categories per page
			if(is_object($layout))
				$items_per_page = $layout->items_per_page();
			else
				$items_per_page = CATEGORIES_PER_PAGE;

			// build a complete box
			$box['bar'] = array();
			$box['text'] = '';

			// count the number of subcategories
			$stats = Categories::stat_for_anchor('category:'.$item['id']);
			if($stats['count'])
				$box['bar'] = array('_count' => sprintf(i18n::ns('1&nbsp;category', '%d&nbsp;categories', $stats['count']), $stats['count']));

			// list items by date (default) or by title (option 'categories_by_title')
			$offset = ($zoom_index - 1) * $items_per_page;
			if(preg_match('/\bcategories_by_title\b/i', $item['options']))
				$items = Categories::list_by_title_for_anchor('category:'.$item['id'], $offset, $items_per_page, $layout);
			else
				$items = Categories::list_by_date_for_anchor('category:'.$item['id'], $offset, $items_per_page, $layout);

			// navigation commands for categories
			$home = Categories::get_url($item['id'], 'view', $item['title']);
			$prefix = Categories::get_url($item['id'], 'navigate', 'categories');
			$box['bar'] = array_merge($box['bar'],
				Skin::navigate($home, $prefix, $stats['count'], $items_per_page, $zoom_index));

			// the command to post a new category
			if($stats['count'] && Categories::are_allowed($anchor, $item)) {
				$url = 'categories/edit.php?anchor='.urlencode('category:'.$item['id']);
				$box['bar'] = array_merge($box['bar'], array( $url => i18n::s('Create a sub-category') ));
			}

			// actually render the html for the section
			if(is_array($items))
				$box['text'] .= Skin::build_list($items, 'decorated');
			elseif(is_string($items))
				$box['text'] .= $items;
			if(is_array($box['bar']))
				$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');
			if($box['text'])
				$text =& Skin::build_box('', $box['text'], 'section', 'categories');

			// save in cache
			Cache::put($cache_id, $text, 'categories');
		}

		// part of the main content
		$context['text'] .= $text;
	}

	//
	// users associated to this category
	//

	// the list of related users if not at another follow-up page
	if((!$zoom_type) || ($zoom_type == 'users')) {

		// cache the section
		$cache_id = 'categories/view.php?id='.$item['id'].'#users#'.$zoom_index;
		if(!$text =& Cache::get($cache_id)) {

			// build a complete box
			$box['bar'] = array();
			$box['text'] = '';

			// count the number of users in this category
			$stats = Members::stat_users_for_anchor('category:'.$item['id']);
			if($stats['count'] > USERS_LIST_SIZE)
				$box['bar'] = array('_count' => sprintf(i18n::ns('1&nbsp;user', '%d&nbsp;users', $stats['count']), $stats['count']));

			// navigation commands for users
			$home = Categories::get_url($item['id'], 'view', $item['title']);
			$prefix = Categories::get_url($item['id'], 'navigate', 'users');
			$box['bar'] = array_merge($box['bar'],
				Skin::navigate($home, $prefix, $stats['count'], USERS_LIST_SIZE, $zoom_index));

			// list items by date (default) or by title (option 'users_by_title')
			$offset = ($zoom_index - 1) * USERS_LIST_SIZE;
			$items = Members::list_users_by_posts_for_anchor('category:'.$item['id'], $offset, USERS_LIST_SIZE, 'compact');

			// actually render the html for the section
			if(is_array($items))
				$box['text'] .= Skin::build_list($items, 'comma');
			elseif(is_string($items))
				$box['text'] .= $items;
			if(is_array($box['bar']))
				$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');
			if($box['text'])
				$text =& Skin::build_box(i18n::s('Related users'), $box['text'], 'section', 'users');

			// save in cache
			Cache::put($cache_id, $text, 'users');
		}

		// part of the main content
		$context['text'] .= $text;
	}

	//
	// sections associated to this category
	//

	// the list of related sections if not at another follow-up page
	if(((!$zoom_type) || ($zoom_type == 'sections'))
		&& (!isset($item['sections_layout']) || ($item['sections_layout'] != 'none'))) {

		// cache the section
		$cache_id = 'categories/view.php?id='.$item['id'].'#sections#'.$zoom_index;
		if(!$text =& Cache::get($cache_id)) {

			// select a layout
			if(!isset($item['sections_layout']) || !$item['sections_layout']) {
				include_once '../sections/layout_sections.php';
				$layout_sections =& new Layout_sections();
			} elseif($item['sections_layout'] == 'decorated') {
				include_once '../sections/layout_sections.php';
				$layout_sections =& new Layout_sections();
			} elseif($item['sections_layout'] == 'map') {
				include_once '../sections/layout_sections_as_yahoo.php';
				$layou_sectionst =& new Layout_sections_as_yahoo();
			} elseif(is_readable($context['path_to_root'].'sections/layout_sections_as_'.$item['sections_layout'].'.php')) {
				$name = 'layout_sections_as_'.$item['sections_layout'];
				include_once '../sections/'.$name.'.php';
				$layout_sections =& new $name;
			} else {

				// useful warning for associates
				if(Surfer::is_associate())
					Skin::error(sprintf(i18n::s('Warning: No script exists for the customized layout %s'), $item['sections_layout']));

				include_once '../sections/layout_sections.php';
				$layout_sections =& new Layout_sections();
			}

			// the maximum number of sections per page
			if(is_object($layout_sections))
				$items_per_page = $layout_sections->items_per_page();
			else
				$items_per_page = SECTIONS_PER_PAGE;

			// build a complete box
			$box['bar'] = array();
			$box['text'] = '';

			// count the number of sections in this category
			$stats = Members::stat_sections_for_anchor('category:'.$item['id']);
			if($stats['count'] > SECTIONS_PER_PAGE)
				$box['bar'] = array('_count' => sprintf(i18n::ns('1&nbsp;section', '%d&nbsp;sections', $stats['count']), $stats['count']));

			// navigation commands for sections
			$home = Categories::get_url($item['id'], 'view', $item['title']);
			$prefix = Categories::get_url($item['id'], 'navigate', 'sections');
			$box['bar'] = array_merge($box['bar'],
				Skin::navigate($home, $prefix, $stats['count'], SECTIONS_PER_PAGE, $zoom_index));

			// list items by date (default) or by title (option 'sections_by_title')
			$offset = ($zoom_index - 1) * SECTIONS_PER_PAGE;
			$items = Members::list_sections_by_title_for_anchor('category:'.$item['id'], $offset, SECTIONS_PER_PAGE, $layout_sections);

			// actually render the html for the section
			if(is_array($items))
				$box['text'] .= Skin::build_list($items, 'compact');
			elseif(is_string($items))
				$box['text'] .= $items;
			if(is_array($box['bar']))
				$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');
			if($box['text'])
				$text =& Skin::build_box(i18n::s('Related sections'), $box['text'], 'section', 'sections');

			// save in cache
			Cache::put($cache_id, $text, 'sections');
		}

		// part of the main content
		$context['text'] .= $text;
	}

	//
	// articles associated to this category
	//

	// the list of related articles if not at another follow-up page
	if((!$zoom_type) || ($zoom_type == 'articles')) {

		// cache the section
		$cache_id = 'categories/view.php?id='.$item['id'].'#articles#'.$zoom_index;
		if(!$text =& Cache::get($cache_id)) {

			// build a complete box
			$box['bar'] = array();
			$box['text'] = '';

			// select a layout
			if(!isset($item['articles_layout']) || !$item['articles_layout']) {
				include_once '../articles/layout_articles.php';
				$layout_articles =& new Layout_articles();
			} elseif($item['articles_layout'] == 'decorated') {
				include_once '../articles/layout_articles.php';
				$layout_articles =& new Layout_articles();
			} elseif($item['articles_layout'] == 'map') {
				include_once '../articles/layout_articles_as_yahoo.php';
				$layout_articles =& new Layout_articles_as_yahoo();
			} elseif($item['articles_layout'] == 'wiki') {
				include_once '../articles/layout_articles.php';
				$layout_sections_articles =& new Layout_articles();
			} elseif(is_readable($context['path_to_root'].'articles/layout_articles_as_'.$item['articles_layout'].'.php')) {
				$name = 'layout_articles_as_'.$item['articles_layout'];
				include_once $context['path_to_root'].'articles/'.$name.'.php';
				$layout_articles =& new $name;
			} else {

				// useful warning for associates
				if(Surfer::is_associate())
					Skin::error(sprintf(i18n::s('Warning: No script exists for the customized layout %s'), $item['articles_layout']));

				include_once '../articles/layout_articles.php';
				$layout_articles =& new Layout_articles();
			}

			// do not refer to this category
			$layout_articles->set_variant('category:'.$item['id']);

			// count the number of articles in this category
			$stats = Members::stat_articles_for_anchor('category:'.$item['id']);
			if($stats['count'])
				$box['bar'] = array('_count' => sprintf(i18n::ns('1&nbsp;page', '%d&nbsp;pages', $stats['count']), $stats['count']));

			// navigation commands for articles
			$home = Categories::get_url($item['id'], 'view', $item['title']);
			$prefix = Categories::get_url($item['id'], 'navigate', 'articles');
			$box['bar'] = array_merge($box['bar'],
				Skin::navigate($home, $prefix, $stats['count'], ARTICLES_PER_PAGE, $zoom_index));

			// list items by date (default) or by title (option 'articles_by_title')
			$offset = ($zoom_index - 1) * ARTICLES_PER_PAGE;
			if(isset($item['options']) && preg_match('/\barticles_by_title\b/i', $item['options']))
				$items = Members::list_articles_by_title_for_anchor('category:'.$item['id'], $offset, ARTICLES_PER_PAGE, $layout_articles);
			else
				$items = Members::list_articles_by_date_for_anchor('category:'.$item['id'], $offset, ARTICLES_PER_PAGE, $layout_articles);

			// actually render the html for the section
			if(is_array($items))
				$box['text'] .= Skin::build_list($items, 'decorated');
			elseif(is_string($items))
				$box['text'] .= $items;
			if(is_array($box['bar']))
				$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');
			if($box['text'])
				$text =& Skin::build_box(i18n::s('Related pages'), $box['text'], 'section', 'articles');

			// save in cache
			Cache::put($cache_id, $text, 'articles');
		}

		// part of the main content
		$context['text'] .= $text;
	}

	//
	// files attached to this category
	//

	// the list of related files if not at another follow-up page
	if((!$zoom_type) || ($zoom_type == 'files')) {

		// cache the section
		$cache_id = 'categories/view.php?id='.$item['id'].'#files#'.$zoom_index;
		if(!$text =& Cache::get($cache_id)) {

			// build a complete box
			$box['bar'] = array();
			$box['text'] = '';

			// count the number of files in this category
			if($count = Files::count_for_anchor('category:'.$item['id'])) {
				if($count > FILES_PER_PAGE)
					$box['bar'] = array('_count' => sprintf(i18n::ns('1&nbsp;file', '%d&nbsp;files', $count), $count));

				// list files by date (default) or by title (option 'files_by_title')
				$offset = ($zoom_index - 1) * FILES_PER_PAGE;
				if(isset($item['options']) && preg_match('/\bfiles_by_title\b/i', $item['options']))
					$items = Files::list_by_title_for_anchor('category:'.$item['id'], $offset, FILES_PER_PAGE);
				else
					$items = Files::list_by_date_for_anchor('category:'.$item['id'], $offset, FILES_PER_PAGE);
				if(is_array($items))
					$box['text'] .= Skin::build_list($items, 'decorated');

				// navigation commands for files
				$home = Categories::get_url($item['id'], 'view', $item['title']);
				$prefix = Categories::get_url($item['id'], 'navigate', 'files');
				$box['bar'] = array_merge($box['bar'],
					Skin::navigate($home, $prefix, $count, FILES_PER_PAGE, $zoom_index));
			}

			// the command to post a new file
			$url = 'files/edit.php?anchor='.urlencode('category:'.$item['id']);
			if(Files::are_allowed($anchor, $item, TRUE))
				$box['bar'] = array_merge($box['bar'], array( $url => i18n::s('Upload a file') ));

			// actually render the html for the section
			if(is_array($box['bar']))
				$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');
			if($box['text'])
				$text =& Skin::build_box('', $box['text'], 'section', 'files');

			// save in cache
			Cache::put($cache_id, $text, 'files');
		}

		// append to the page
		$context['text'] .= $text;
	}

	//
	// comments attached to this category
	//

	// the list of related comments if not at another follow-up page
	if((!$zoom_type) || ($zoom_type == 'comments')) {

		// cache the section
		$cache_id = 'categories/view.php?id='.$item['id'].'#comments#'.$zoom_index;
		if(!$text =& Cache::get($cache_id)) {

			// build a complete box
			$box['bar'] = array();
			$box['text'] = '';

			// count the number of comments in this category
			if($zoom_type == 'comments')
				$url = '_count';
			 else
				$url = 'categories/view.php?id='.$item['id'].'&amp;comments=1';
			if($count = Comments::count_for_anchor('category:'.$item['id'])) {
				if($count > COMMENTS_PER_PAGE)
					$box['bar'] = array('_count' => sprintf(i18n::ns('1&nbsp;comment', '%d&nbsp;comments', $count), $count));

				// list comments by date
				$offset = ($zoom_index - 1) * COMMENTS_PER_PAGE;
				if(!$zoom_type)
					$items = Comments::list_by_date_for_anchor('category:'.$item['id'], $offset, COMMENTS_PER_PAGE, 'compact');
				else
					$items = Comments::list_by_date_for_anchor('category:'.$item['id'], $offset, COMMENTS_PER_PAGE);
				if(is_array($items))
					$box['text'] .= Skin::build_list($items, 'rows');

				// navigation commands for comments
				$home = Categories::get_url($item['id'], 'view', $item['title']);
				$prefix = Categories::get_url($item['id'], 'navigate', 'comments');
				if($zoom_type == 'comments') {
					$box['bar'] = array_merge($box['bar'],
						Skin::navigate($home, $prefix, $count, COMMENTS_PER_PAGE, $zoom_index, TRUE));
				}
			}

			// the command to post a new comment
			if(Comments::are_allowed($anchor, $item, TRUE)) {
				$url = 'comments/edit.php?anchor='.urlencode('category:'.$item['id']);
				$box['bar'] = array_merge($box['bar'], array( $url => i18n::s('Add a comment') ));
			}

			// actually render the html
			if(is_array($box['bar']))
				$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');
			if($box['text']) {
				// either a section or an extra box
				if($zoom_type == 'comments')
					$text =& Skin::build_box('', $box['text'], 'section', 'comments');
				else
					$text =& Skin::build_box('', $box['text'], 'extra', 'comments');
			}

			// save in cache
			Cache::put($cache_id, $text, 'comments');
		}

		// either in the extra or in the main panel
		if($zoom_type == 'comments')
			$context['text'] .= $text;
		else
			$context['extra'] .= $text;
	}

	//
	// links attached to this category
	//

	// the list of related links if not at another follow-up page
	if(((!$zoom_type) || ($zoom_type == 'links'))) {

		// cache the section
		$cache_id = 'categories/view.php?id='.$item['id'].'#links#'.$zoom_index;
		if(!$text =& Cache::get($cache_id)) {

			// build a complete box
			$box['bar'] = array();
			$box['text'] = '';

			// count the number of links in this category
			if($count = Links::count_for_anchor('category:'.$item['id'])) {
				if($count > LINKS_PER_PAGE)
					$box['bar'] = array('_count' => sprintf(i18n::ns('1&nbsp;link', '%d&nbsp;links', $count), $count));

				// list items by date (default) or by title (option 'links_by_title')
				$offset = ($zoom_index - 1) * LINKS_PER_PAGE;
				if(isset($item['options']) && preg_match('/\blinks_by_title\b/i', $item['options']))
					$items = Links::list_by_title_for_anchor('category:'.$item['id'], $offset, LINKS_PER_PAGE);
				else
					$items = Links::list_by_date_for_anchor('category:'.$item['id'], $offset, LINKS_PER_PAGE);
				if(is_array($items))
					$box['text'] .= Skin::build_list($items, 'decorated');

				// navigation commands for links
				$home = Categories::get_url($item['id'], 'view', $item['title']);
				$prefix = Categories::get_url($item['id'], 'navigate', 'links');
				$box['bar'] = array_merge($box['bar'],
					Skin::navigate($home, $prefix, $count, LINKS_PER_PAGE, $zoom_index));
			}

			// the command to post a new link
			if(Links::are_allowed($anchor, $item, TRUE)) {
				$url = 'links/edit.php?anchor='.urlencode('category:'.$item['id']);
				$box['bar'] = array_merge($box['bar'], array( $url => i18n::s('Add a link') ));
			}

			// actually render the html
			if(is_array($box['bar']))
				$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');
			if($box['text'])
				$text =& Skin::build_box('', $box['text'], 'section', 'links');

			// save in cache
			Cache::put($cache_id, $text, 'links');
		}

		// embed in the page
		$context['text'] .= $text;
	}

	// insert anchor suffix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_suffix();

	//
	// extra boxes
	//

	// get news from rss
	if(isset($item['id']) && (!isset($context['pages_without_feed']) || ($context['pages_without_feed'] != 'Y')) ) {
		$label = sprintf(i18n::s('You can get %s'), Skin::build_link(Categories::get_url($item['id'], 'feed'), i18n::s('RSS news from this category'), 'xml'));
		$context['extra'] .= Skin::build_box(i18n::s('News Feeder'), $label, 'extra');
	}

	// the bookmarking bookmarklet
	if(isset($item['id']) && Surfer::is_member() && (!isset($context['pages_without_bookmarklets']) || ($context['pages_without_bookmarklets'] != 'Y')) ) {

		$bookmarklet = "javascript:function findFrame(f){var i;try{isThere=f.document.selection.createRange().text;}catch(e){isThere='';}if(isThere==''){for(i=0;i&lt;f.frames.length;i++){findFrame(f.frames[i]);}}else{s=isThere}return s}"
			."var s='';"
			."d=document;"
			."s=d.selection?findFrame(window):window.getSelection();"
			."window.location='".$context['url_to_home'].$context['url_to_root']."links/edit.php?"
				."link='+escape(d.location)+'"
				."&amp;anchor='+escape('category:".$item['id']."')+'"
				."&amp;title='+escape(d.title)+'"
				."&amp;text='+escape(s);";

		if($item['nick_name'] == 'bookmarks')
			$label = '<a href="'.$bookmarklet.'">'.sprintf(i18n::s('Bookmark at %s'), strip_tags($context['site_name'])).'</a>'."\n";
		else
			$label = '<a href="'.$bookmarklet.'">'.sprintf(i18n::s('Bookmark at %s'), strip_tags($item['title'])).'</a>'."\n";

		// an extra box
		$context['extra'] .= Skin::build_box(i18n::s('Bookmarklet to contribute'), $label, 'extra');
	}

	// list related servers, if any
	if($content = Servers::list_by_date_for_anchor('category:'.$item['id'])) {
		if(is_array($content))
			$context['extra'] .= Skin::build_box(i18n::s('Related servers'), Skin::build_list($content, 'compact'), 'navigation', 'servers');
	}

	// search on keyword, if any
	if($item['keywords']) {

		// internal search
		$label = sprintf(i18n::s('Maybe some new pages or additional material can be found by submitting the following keyword to our search engine. Give it a try. %s'), Codes::beautify('[search='.$item['keywords'].']'));
		$context['extra'] .= Skin::build_box(i18n::s('Internal search'), $label, 'navigation');

		// external search
		$text = '<p>'.sprintf(i18n::s('Search for %s at:'), $item['keywords']).' ';

		// encode for urls, but preserve unicode chars
		$search = urlencode(utf8::from_unicode($item['keywords']));

		// Google
		$link = 'http://www.google.com/search?q='.$search.'&amp;ie=utf-8';
		$text .= Skin::build_link($link, i18n::s('Google'), 'external').', ';

		// Yahoo!
		$link = 'http://search.yahoo.com/search?p='.$search.'&amp;ei=utf-8';
		$text .= Skin::build_link($link, i18n::s('Yahoo!'), 'external').', ';

		// Ask Jeeves
		$link = 'http://web.ask.com/web?q='.$search;
		$text .= Skin::build_link($link, i18n::s('Ask Jeeves'), 'external').', ';

		// All the web
		$link = 'http://alltheweb.com/search?q='.$search.'&amp;cs=utf8';
		$text .= Skin::build_link($link, i18n::s('All the web'), 'external').', ';

		// Feedster
		$link = 'http://www.feedster.com/search.php?q='.$search;
		$text .= Skin::build_link($link, i18n::s('Feedster'), 'external').', ';

		// Technorati
		$link = 'http://www.technorati.com/cosmos/search.html?rank=&url='.$search;
		$text .= Skin::build_link($link, i18n::s('Technorati'), 'external').'.';

		$text .= "</p>\n";
		$context['extra'] .= Skin::build_box(i18n::s('External search'), $text, 'navigation');

	}

	// how to reference this page
	if(Surfer::is_member() && !$zoom_type && (!isset($context['pages_without_reference']) || ($context['pages_without_reference'] != 'Y')) ) {

		// box content
		$label = sprintf(i18n::s('Here, use code %s'), '<code>[category='.$item['id'].']</code>')."\n"
			.BR.sprintf(i18n::s('Elsewhere, bookmark the %s'), Skin::build_link(Categories::get_url($item['id'], 'view', $item['title']), i18n::s('full link')))."\n";

		// in a sidebar box
		$text =& Skin::build_box(i18n::s('Reference this page'), $label, 'navigation', 'reference');

		// embed the box in the page
		$context['extra'] .= $text;
	}

	// referrals, if any
	if(!$zoom_type && (Surfer::is_associate() || (isset($context['with_referrals']) && ($context['with_referrals'] == 'Y')))) {

		// cache the box
		$cache_id = 'categories/view.php?id='.$item['id'].'#referrals#';
		if(!$text =& Cache::get($cache_id)) {

			// box content in a sidebar box
			include_once '../agents/referrals.php';
			if($text = Referrals::list_by_hits_for_url($context['url_to_root_parameter'].Categories::get_url($item['id'], 'view', $item['title'])))
				$text =& Skin::build_box(i18n::s('Referrals'), $text, 'navigation', 'referrals');

			// save in cache for one hour 60 * 60 = 3600
			Cache::put($cache_id, $text, 'referrals', 3600);

		}

		// in the extra panel
		$context['extra'] .= $text;
	}

	//
	// meta information
	//

	// a meta link to a feeding page
	$context['page_header'] .= "\n".'<link rel="alternate" href="'.$context['url_to_root'].Categories::get_url($item['id'], 'feed').'" title="RSS" type="application/rss+xml"'.EOT;

	// a meta link to a description page (actually, rdf)
	$context['page_header'] .= "\n".'<link rel="meta" href="'.$context['url_to_root'].Categories::get_url($item['id'], 'describe').'" title="Meta Information" type="application/rdf+xml"'.EOT;

	// implement the pingback interface
	$context['page_header'] .= "\n".'<link rel="pingback" href="'.$context['url_to_root'].'services/ping.php"'.EOT;

	// implement the trackback interface
	$permanent_link = $context['url_to_home'].$context['url_to_root'].Categories::get_url($item['id'], 'view', $item['title']);
	if(isset($context['with_friendly_urls']) && ($context['with_friendly_urls'] == 'Y'))
		$trackback_link = $context['url_to_home'].$context['url_to_root'].'links/trackback.php/category/'.$item['id'];
	else
		$trackback_link = $context['url_to_home'].$context['url_to_root'].'links/trackback.php?anchor=category:'.$item['id'];
	$context['page_header'] .= "\n".'<!--'
		."\n".'<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"'
		."\n".' 		xmlns:dc="http://purl.org/dc/elements/1.1/"'
		."\n".' 		xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/">'
		."\n".'<rdf:Description'
		."\n".' trackback:ping="'.$trackback_link.'"'
		."\n".' dc:identifier="'.$permanent_link.'"'
		."\n".' rdf:about="'.$permanent_link.'" />'
		."\n".'</rdf:RDF>'
		."\n".'-->';

	// set specific headers
	if(isset($item['introduction']) && $item['introduction'])
		$context['page_description'] = $item['introduction'];
	if(isset($item['create_name']) && $item['create_name'])
		$context['page_author'] = $item['create_name'];

}

// render the skin
render_skin();

?>