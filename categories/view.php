<?php
/**
 * view one category
 *
 * The main panel has following elements:
 * - The category itself, with details, introduction, and main text.
 * - The list of sub-categories
 * - The list of related sections
 * - The list of related articles
 * - The list of related files
 * - The list of comments
 * - The list of related links
 * - The list of related users
 *
 * The extra panel has following elements:
 * - A bookmarklet to post bookmarks at this category
 * - A link to the related rss feed, as an extra box
 * - A list of anchored (and feeding) servers, into a sidebar
 * - A search form into a sidebar, if the category has some keyword
 * - Means to reference this page, into a sidebar box
 * - The top popular referrals, if any
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
include_once 'categories.php';
include_once '../comments/comments.php';
include_once '../files/files.php';
include_once '../images/images.php';
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

// load the skin, maybe with a variant
load_skin('categories', $anchor, isset($item['options']) ? $item['options'] : '');

// path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'categories/' => i18n::s('Categories') );

// page title
if(is_object($overlay))
	$context['page_title'] = $overlay->get_text('title', $item);
elseif(isset($item['title']) && $item['title'])
	$context['page_title'] = $item['title'];

// modify this page
if((!$zoom_type) && (Surfer::is_associate() || (is_object($anchor) && $anchor->is_editable()))) {
	$context['page_menu'] = array_merge($context['page_menu'], array( Categories::get_url($id, 'edit') => i18n::s('Edit this page') ));
	$context['page_menu'] = array_merge($context['page_menu'], array( Categories::get_url($id, 'delete') => i18n::s('Delete') ));
}

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

	// increment silently the hits counter if not associate, nor creator -- editors are taken into account
	if(Surfer::is_associate())
		;
	elseif(Surfer::get_id() && isset($item['create_id']) && (Surfer::get_id() == $item['create_id']))
		;
	elseif(!$zoom_type) {
		$item['hits'] = isset($item['hits'])?($item['hits']+1):1;
		Categories::increment_hits($item['id']);
	}

	// initialize the rendering engine
	Codes::initialize(Categories::get_url($item['id'], 'view', $item['title']));

	//
	// page image -- $context['page_image']
	//

	// the category or the anchor icon, if any
	if(isset($item['icon_url']) && $item['icon_url'])
		$context['page_image'] = $item['icon_url'];
	elseif(is_object($anchor))
		$context['page_image'] = $anchor->get_icon_url();

	//
	// page meta information -- $context['page_header'], etc.
	//

	// a meta link to a feeding page
	$context['page_header'] .= "\n".'<link rel="alternate" href="'.$context['url_to_root'].Categories::get_url($item['id'], 'feed').'" title="RSS" type="application/rss+xml"'.EOT;

	// a meta link to a description page (actually, rdf)
	$context['page_header'] .= "\n".'<link rel="meta" href="'.$context['url_to_root'].Categories::get_url($item['id'], 'describe').'" title="Meta Information" type="application/rdf+xml"'.EOT;

	// implement the trackback interface
	$permanent_link = $context['url_to_home'].$context['url_to_root'].Categories::get_url($item['id'], 'view', $item['title']);
	if($context['with_friendly_urls'] == 'Y')
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

	// implement the pingback interface
	$context['page_header'] .= "\n".'<link rel="pingback" href="'.$context['url_to_root'].'services/ping.php"'.EOT;

	// set specific headers
	if(isset($item['introduction']) && $item['introduction'])
		$context['page_description'] = $item['introduction'];
	if(isset($item['create_name']) && $item['create_name'])
		$context['page_author'] = $item['create_name'];

	//
	// page details -- $context['page_details']
	//

	// do not mention details at follow-up pages
	if(!$zoom_type) {

		// one detail per line
		$context['page_details'] = '<p class="details">';
		$details = array();

		// restricted to logged members
		if($item['active'] == 'R')
			$details[] = RESTRICTED_FLAG.' '.i18n::s('Access is restricted to authenticated members');

		// restricted to associates
		if($item['active'] == 'N')
			$details[] = PRIVATE_FLAG.' '.i18n::s('Access is restricted to associates');

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
			$context['page_details'] .= ucfirst(implode(BR, $details)).BR;

		// other details
		$details = array();

		// additional details for associates and editors
		if(Surfer::is_associate() || (is_object($anchor) && $anchor->is_editable())) {

			// the nick name
			if($item['nick_name'])
				$details[] = '"'.$item['nick_name'].'"';

			// the creator of this category
			if($item['create_date'])
				$details[] = sprintf(i18n::s('posted by %s %s'), Users::get_link($item['create_name'], $item['create_address'], $item['create_id']), Skin::build_date($item['create_date']));

			// hide last edition if done by creator, and if less than 24 hours between creation and last edition
			if($item['create_date'] && ($item['create_id'] == $item['edit_id'])
					&& (SQL::strtotime($item['create_date'])+24*60*60 >= SQL::strtotime($item['edit_date'])))
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

			// rank for this section
			if(intval($item['rank']) != 10000)
				$details[] = '{'.$item['rank'].'}';

		}

		// inline details
		if(count($details))
			$context['page_details'] .= ucfirst(implode(', ', $details));

		$context['page_details'] .= '</p>';

	}

	//
	// main panel -- $context['text']
	//

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

		// use the cache if possible
		$cache_id = 'categories/view.php?id='.$item['id'].'#content';
		if(!$text =& Cache::get($cache_id)) {


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
				$text .= '<div class="description">'.Codes::beautify($item['description'])."</div>\n";

			// save in cache if no dynamic element
			if(!preg_match('/\[table=(.+?)\]/i', $item['description']))
				Cache::put($cache_id, $text, 'category:'.$item['id']);
		}
		$context['text'] .= $text;
	}

	//
	// panels
	//
	$panels = array();

	//
	// sections associated to this category
	//

	// the list of related sections if not at another follow-up page
	if(((!$zoom_type) || ($zoom_type == 'sections'))
		&& (!isset($item['sections_layout']) || ($item['sections_layout'] != 'none'))) {

		// cache panel content
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
				$layout_sections =& new Layout_sections_as_yahoo();
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
				$box['bar'] = array('_count' => sprintf(i18n::ns('1 section', '%d sections', $stats['count']), $stats['count']));

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
				$box['text'] .= Skin::build_list($items, 'decorated');
			elseif(is_string($items))
				$box['text'] .= $items;
			if(is_array($box['bar']))
				$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');
			if($box['text'])
				$text = $box['text'];

			// save in cache
			Cache::put($cache_id, $text, 'sections');
		}

		// in a separate panel
		if(trim($text))
			$panels[] = array('sections_tab', i18n::s('Sections'), 'sections_panel', $text);
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
				$box['bar'] = array('_count' => sprintf(i18n::ns('1 page', '%d pages', $stats['count']), $stats['count']));

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
				$text = $box['text'];

			// save in cache
			Cache::put($cache_id, $text, 'articles');
		}

		// in a separate panel
		if(trim($text))
			$panels[] = array('articles_tab', i18n::s('Pages'), 'articles_panel', $text);
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
					$box['bar'] = array('_count' => sprintf(i18n::ns('1 file', '%d files', $count), $count));

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
				$text = $box['text'];

			// save in cache
			Cache::put($cache_id, $text, 'files');
		}

		// in a separate panel
		if(trim($text))
			$panels[] = array('files_tab', i18n::s('Files'), 'files_panel', $text);
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
					$box['bar'] = array('_count' => sprintf(i18n::ns('1 comment', '%d comments', $count), $count));

				// list comments by date
				$offset = ($zoom_index - 1) * COMMENTS_PER_PAGE;
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
			if($box['text'])
				$text = $box['text'];

			// save in cache
			Cache::put($cache_id, $text, 'comments');
		}

		// in a separate panel
		if(trim($text))
			$panels[] = array('comments_tab', i18n::s('Comments'), 'comments_panel', $text);
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
					$box['bar'] = array('_count' => sprintf(i18n::ns('1 link', '%d links', $count), $count));

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
				$text = $box['text'];

			// save in cache
			Cache::put($cache_id, $text, 'links');
		}

		// in a separate panel
		if(trim($text))
			$panels[] = array('links_tab', i18n::s('Links'), 'links_panel', $text);
	}

	//
	// sub-categories of this category
	//
	$categories = '';

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
				$box['bar'] = array('_count' => sprintf(i18n::ns('1 category', '%d categories', $stats['count']), $stats['count']));

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
				$box['bar'] = array_merge($box['bar'], array( $url => i18n::s('Add a category') ));
			}

			// actually render the html for the section
			if(is_array($items))
				$box['text'] .= Skin::build_list($items, 'decorated');
			elseif(is_string($items))
				$box['text'] .= $items;
			if(is_array($box['bar']))
				$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');
			if($box['text'])
				$text = $box['text'];

			// save in cache
			Cache::put($cache_id, $text, 'categories');
		}

		// in a separate panel
		if(trim($text))
			$panels[] = array('categories_tab', i18n::s('Categories'), 'categories_panel', $text);
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
			$box = array('bar' => array(), 'text' => '');

			// count the number of users in this category
			$stats = Members::stat_users_for_anchor('category:'.$item['id']);

			// send a message to a category
			if(($stats['count'] > 1) && Surfer::is_associate())
				$box['bar'] = array_merge($box['bar'], array(Categories::get_url($item['id'], 'mail') => i18n::s('Send a message')));

			// spread the list over several pages
			if($stats['count'] > USERS_LIST_SIZE)
				$box['bar'] = array_merge($box['bar'], array('_count' => sprintf(i18n::ns('1 user', '%d users', $stats['count']), $stats['count'])));

			// navigation commands for users
			$home = Categories::get_url($item['id'], 'view', $item['title']);
			$prefix = Categories::get_url($item['id'], 'navigate', 'users');
			$box['bar'] = array_merge($box['bar'],
				Skin::navigate($home, $prefix, $stats['count'], USERS_LIST_SIZE, $zoom_index));

			// list items by date (default) or by title (option 'users_by_title')
			$offset = ($zoom_index - 1) * USERS_LIST_SIZE;
			$items = Members::list_users_by_posts_for_anchor('category:'.$item['id'], $offset, USERS_LIST_SIZE, 'watch');

			// actually render the html
			if(is_array($box['bar']))
				$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');
			if(is_array($items))
				$box['text'] .= Skin::build_list($items, 'decorated');
			elseif(is_string($items))
				$box['text'] .= $items;
			if(is_array($box['bar']) && (($stats['count'] - $offset) > 5))
				$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');
			if($box['text'])
				$text =$box['text'];

			// save in cache
			Cache::put($cache_id, $text, 'users');
		}

		// in a separate panel
		if(trim($text))
			$panels[] = array('users_tab', i18n::s('Persons'), 'users_panel', $text);
	}

	//
	// assemble all tabs
	//

	// let YACS do the hard job
	$context['text'] .= Skin::build_tabs($panels);

	//
	// trailer
	//

	// add trailer information from the overlay, if any
	if(is_object($overlay))
		$context['text'] .= $overlay->get_text('trailer', $item);

	// add trailer information from this item, if any
	if(isset($item['trailer']) && trim($item['trailer']))
		$context['text'] .= Codes::beautify($item['trailer']);

	// insert anchor suffix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_suffix();

	//
	// extra panel -- most content is cached, except commands specific to current surfer
	//

	// cache content
	$cache_id = 'categories/view.php?id='.$item['id'].'#extra#head';
	if(!$text =& Cache::get($cache_id)) {

		// add extra information from the overlay, if any
		if(is_object($overlay))
			$text .= $overlay->get_text('extra', $item);

		// add extra information from this item, if any
		if(isset($item['extra']) && $item['extra'])
			$text .= Codes::beautify($item['extra']);

		// save in cache
		Cache::put($cache_id, $text, 'category:'.$item['id']);
	}

	// update the extra panel
	$context['extra'] .= $text;

	// page tools
	//

	// only on first page, and for associates
	if(!$zoom_type && Surfer::is_associate()) {

		// modify this page
		$context['page_tools'][] = Skin::build_link(Categories::get_url($item['id'], 'edit'), i18n::s('Edit this page'), 'basic', i18n::s('Update the content of this page'));

		// post an image, if upload is allowed
		if(Images::are_allowed($anchor, $item)) {
			Skin::define_img('IMAGE_TOOL_IMG', 'icons/tools/image.gif');
			$context['page_tools'][] = Skin::build_link('images/edit.php?anchor='.urlencode('category:'.$item['id']), IMAGE_TOOL_IMG.i18n::s('Add an image'), 'basic', i18n::s('You can upload a camera shot, a drawing, or any image file, to illustrate this page.'));
		}

		// attach a file, if upload is allowed
		if(Files::are_allowed($anchor, $item, TRUE))
			$context['page_tools'][] = Skin::build_link('files/edit.php?anchor='.urlencode('category:'.$item['id']), FILE_TOOL_IMG.i18n::s('Upload a file'), 'basic', i18n::s('Do not hesitate to attach files related to this page.'));

		// comment this page if anchor does not prevent it
		if(Comments::are_allowed($anchor, $item, TRUE))
			$context['page_tools'][] = Skin::build_link(Comments::get_url('category:'.$item['id'], 'comment'), COMMENT_TOOL_IMG.i18n::s('Add a comment'), 'basic', i18n::s('Express yourself, and say what you think.'));

		// add a link
		if(Links::are_allowed($anchor, $item, TRUE))
			$context['page_tools'][] = Skin::build_link('links/edit.php?anchor='.urlencode('category:'.$item['id']), LINK_TOOL_IMG.i18n::s('Add a link'), 'basic', i18n::s('Contribute to the web and link to relevant pages.'));

		// add a category
		if(Categories::are_allowed($anchor, $item))
			$context['page_tools'][] = Skin::build_link('categories/edit.php?anchor='.urlencode('category:'.$item['id']), i18n::s('Add a category'), 'basic');

	}

	// spreading tools
	//

// 	// mail this page
// 	if(!$zoom_type && Surfer::is_empowered() && Surfer::get_email_address() && isset($context['with_email']) && ($context['with_email'] == 'Y')) {
// 		Skin::define_img('MAIL_TOOL_IMG', 'icons/tools/mail.gif');
// 		$context['page_tools'][] = Skin::build_link(Categories::get_url($item['id'], 'mail'), MAIL_TOOL_IMG.i18n::s('Invite people'), 'basic', '', i18n::s('Spread the word'));
// 	}

// 	// the command to track back -- complex command
// 	if(Surfer::is_logged() && Surfer::has_all()) {
// 		Skin::define_img('TRACKBACK_IMG', 'icons/links/trackback.gif');
// 		$context['page_tools'][] = Skin::build_link('links/trackback.php?anchor='.urlencode('category:'.$item['id']), TRACKBACK_IMG.i18n::s('Reference this page'), 'basic', i18n::s('Various means to link to this page'));
// 	}

	// print this page
	if(Surfer::is_logged()) {
		Skin::define_img('PRINT_TOOL_IMG', 'icons/tools/print.gif');
		$context['page_tools'][] = Skin::build_link(Categories::get_url($item['id'], 'print'), PRINT_TOOL_IMG.i18n::s('Print this page'), 'basic', i18n::s('Get a paper copy of this page.'));
	}

	// cache content
	$cache_id = 'categories/view.php?id='.$item['id'].'#extra#tail';
	if(!$text =& Cache::get($cache_id)) {

// 		// twin pages
// 		if(isset($item['nick_name']) && $item['nick_name']) {

// 			// build a complete box
// 			$box['text'] = '';

// 			// list pages with same name
// 			$items = Categories::list_for_name($item['nick_name'], $item['id'], 'compact');

// 			// actually render the html for the section
// 			if(is_array($items))
// 				$box['text'] .= Skin::build_list($items, 'compact');
// 			if($box['text'])
// 				$text .= Skin::build_box(i18n::s('Related'), $box['text'], 'extra', 'twins');

// 		}

		// get news from rss
		if(isset($item['id']) && (!isset($context['skins_general_without_feed']) || ($context['skins_general_without_feed'] != 'Y')) ) {

			$content = Skin::build_link($context['url_to_home'].$context['url_to_root'].Categories::get_url($item['id'], 'feed'), i18n::s('Recent pages'), 'xml');

			// public aggregators
			if(!isset($context['without_internet_visibility']) || ($context['without_internet_visibility'] != 'Y'))
				$content .= BR.join(BR, Skin::build_subscribers($context['url_to_home'].$context['url_to_root'].Categories::get_url($item['id'], 'feed'), $item['title']));

			$text .= Skin::build_box(i18n::s('Stay tuned'), $content, 'extra', 'feeds');
		}

		// list related servers, if any
		if($content = Servers::list_by_date_for_anchor('category:'.$item['id'])) {
			if(is_array($content))
				$content =& Skin::build_list($content, 'compact');
			$text .= Skin::build_box(i18n::s('Related servers'), $content, 'navigation', 'servers');
		}

		// search on keyword, if any
		if($item['keywords']) {

			// internal search
			$label = sprintf(i18n::s('Maybe some new pages or additional material can be found by submitting the following keyword to our search engine. Give it a try. %s'), Codes::beautify('[search='.$item['keywords'].']'));
			$content .= Skin::build_box(i18n::s('Internal search'), $label, 'navigation');

			// external search
			$content = '<p>'.sprintf(i18n::s('Search for %s at:'), $item['keywords']).' ';

			// encode for urls, but preserve unicode chars
			$search = urlencode(utf8::from_unicode($item['keywords']));

			// Google
			$link = 'http://www.google.com/search?q='.$search.'&amp;ie=utf-8';
			$content .= Skin::build_link($link, i18n::s('Google'), 'external').', ';

			// Yahoo!
			$link = 'http://search.yahoo.com/search?p='.$search.'&amp;ei=utf-8';
			$content .= Skin::build_link($link, i18n::s('Yahoo!'), 'external').', ';

			// Ask Jeeves
			$link = 'http://web.ask.com/web?q='.$search;
			$content .= Skin::build_link($link, i18n::s('Ask Jeeves'), 'external').', ';

			// All the web
			$link = 'http://alltheweb.com/search?q='.$search.'&amp;cs=utf8';
			$content .= Skin::build_link($link, i18n::s('All the web'), 'external').', ';

			// Feedster
			$link = 'http://www.feedster.com/search.php?q='.$search;
			$content .= Skin::build_link($link, i18n::s('Feedster'), 'external').', ';

			// Technorati
			$link = 'http://www.technorati.com/cosmos/search.html?rank=&url='.$search;
			$content .= Skin::build_link($link, i18n::s('Technorati'), 'external').'.';

			$content .= "</p>\n";
			$text .= Skin::build_box(i18n::s('External search'), $content, 'navigation');

		}

		// referrals, if any
		if(!$zoom_type && (Surfer::is_associate() || (isset($context['with_referrals']) && ($context['with_referrals'] == 'Y')))) {

			// in a sidebar box
			include_once '../agents/referrals.php';
			if($content = Referrals::list_by_hits_for_url($context['url_to_root_parameter'].Categories::get_url($item['id'])))
				$text .= Skin::build_box(i18n::s('Referrals'), $content, 'navigation', 'referrals');

		}

		// save in cache
		Cache::put($cache_id, $text, 'category:'.$item['id']);

	}

	// update the extra panel
	$context['extra'] .= $text;

}

// render the skin
render_skin();

?>