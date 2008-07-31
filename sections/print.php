<?php
/**
 * print one section
 *
 * Restrictions apply on this page:
 * - associates and editors are allowed to move forward
 * - permission is denied if the anchor is not viewable
 * - access is restricted ('active' field == 'R'), but the surfer is an authenticated member
 * - public access is allowed ('active' field == 'Y')
 * - permission denied is the default
 *
 * Accept following invocations:
 * - print.php/12
 * - print.php?id=12
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Sections::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor = Anchors::get($item['anchor']);

// editors have associate-like capabilities
if((isset($item['id']) && Sections::is_assigned($item['id'])) || (is_object($anchor) && $anchor->is_editable()))
	Surfer::empower();

// associates and editors are always authorized
if(Surfer::is_empowered())
	$permitted = TRUE;

// the anchor has to be viewable by this surfer
elseif(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;

// access is restricted to authenticated member
elseif(isset($item['active']) && ($item['active'] == 'R') && Surfer::is_member())
	$permitted = TRUE;

// public access is allowed
elseif(isset($item['active']) && $item['active'] == 'Y')
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load the skin, with a specific variant
load_skin('print');

// the title of the page
if(isset($item['title']))
	$context['page_title'] = $item['title'];

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// not found
} elseif(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Sections::get_url($item['id'], 'print')));

	// permission denied to authenticated user
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// display the section
} else {

	// initialize the rendering engine
	Codes::initialize(Sections::get_permalink($item));

	// the article or the anchor icon, if any
	if(isset($item['icon_url']) && $item['icon_url'])
		$context['page_image'] = $item['icon_url'];
	elseif(is_object($anchor))
		$context['page_image'] = $anchor->get_icon_url();

	// provide details
	$details = array();

	// restricted to logged members
	if($item['active'] == 'R')
		$details[] = RESTRICTED_FLAG.' '.i18n::s('Access is restricted to authenticated members');

	// restricted to associates
	elseif($item['active'] == 'N')
		$details[] = PRIVATE_FLAG.' '.i18n::s('Access is restricted to associates and editors');

	// rank for this section
	if(Surfer::is_associate() && (intval($item['rank']) != 10000))
		$details[] = sprintf(i18n::s('Rank: %s'), $item['rank']);

	// section editors
	if(Surfer::is_empowered() && Surfer::is_member()) {
		if($items = Members::list_editors_by_name_for_member('section:'.$item['id'], 0, 50, 'compact'))
			$details[] = sprintf(i18n::s('Editors: %s'), Skin::build_list($items, 'comma'));

		if($items = Members::list_readers_by_name_for_member('section:'.$item['id'], 0, 50, 'compact'))
			$details[] = sprintf(i18n::s('Readers: %s'), Skin::build_list($items, 'comma'));
	}

	// signal sections to be activated
	$now = gmstrftime('%Y-%m-%d %H:%M:%S');
	if(Surfer::is_empowered() && ($item['activation_date'] > $now))
		$details[] = DRAFT_FLAG.' '.sprintf(i18n::s('Section will be activated %s'), Skin::build_date($item['activation_date']));

	// expired section
	if(Surfer::is_empowered() && ($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $now))
		$details[] = EXPIRED_FLAG.' '.sprintf(i18n::s('Section has expired %s'), Skin::build_date($item['expiry_date']));

	// display details, if any
	if(count($details))
		$context['text'] .= '<p>'.ucfirst(implode(BR."\n", $details))."</p>\n";

	// insert anchor prefix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_prefix();

	// the introduction text, if any
	$context['text'] .= Skin::build_block($item['introduction'], 'introduction');

	// the description, which is the actual page body
	$context['text'] .= Skin::build_block($item['description'], 'description', '', $item['options']);

	//
	// sub-sections, if any
	//

	// select a layout
	if(!isset($item['sections_layout']))
		$layout = 'decorated';
	elseif($item['sections_layout'] == 'compact')
		$layout = 'compact';
	elseif($item['sections_layout'] == 'inline') {
		include_once '../sections/layout_sections_as_inline.php';
		$layout =& new Layout_sections_as_inline();
	} elseif($item['sections_layout'] == 'map') {
		include_once '../sections/layout_sections_as_yahoo.php';
		$layout =& new Layout_sections_as_yahoo();
	} elseif($item['sections_layout'] == 'yabb') {
		include_once '../sections/layout_sections_as_yabb.php';
		$layout =& new Layout_sections_as_yabb();
	} else
		$layout = 'decorated';

	// the maximum number of sections per page
	if(is_object($layout))
		$items_per_page = $layout->items_per_page();
	elseif(isset($item['sections_count']) && ($item['sections_count'] > 0))
		$items_per_page = $item['sections_count'];
	else
		$items_per_page = SECTIONS_PER_PAGE;

	// list items by title
	$items = Sections::list_by_title_for_anchor('section:'.$item['id'], 0, $items_per_page, $layout);

	// actually render the html for the section
	$box['text'] = '';
	if(is_array($box['bar']))
		$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');
	if(is_array($items) && ($layout == 'compact'))
		$box['text'] .= Skin::build_list($items, 'compact');
	elseif(is_array($items) && ($layout == 'decorated'))
		$box['text'] .= Skin::build_list($items, 'decorated');
	elseif(is_array($items))
		$box['text'] .= Skin::build_list($items, '2-columns');
	elseif(is_string($items))
		$box['text'] .= $items;
	if($box['text'])
		$context['text'] .= Skin::build_box('', $box['text']);

	//
	// the articles section
	//

	// select a layout
	if(!isset($item['articles_layout']))
		$layout = NULL;
	elseif($item['articles_layout'] == 'boxesandarrows') {
		include_once '../articles/layout_articles_as_boxesandarrows.php';
		$layout =& new Layout_articles_as_boxesandarrows();
	} elseif($item['articles_layout'] == 'daily') {
		include_once '../articles/layout_articles_as_daily.php';
		$layout =& new Layout_articles_as_daily();
	} elseif($item['articles_layout'] == 'jive') {
		include_once '../articles/layout_articles_as_jive.php';
		$layout =& new Layout_articles_as_jive();
	} elseif($item['articles_layout'] == 'manual') {
		include_once '../articles/layout_articles_as_manual.php';
		$layout =& new Layout_articles_as_manual();
	} elseif($item['articles_layout'] == 'table') {
		include_once '../articles/layout_articles_as_table.php';
		$layout =& new Layout_articles_as_table();
	} elseif($item['articles_layout'] == 'yabb') {
		include_once '../articles/layout_articles_as_yabb.php';
		$layout =& new Layout_articles_as_yabb();
	} elseif($item['articles_layout'] == 'compact')
		$layout = 'compact';
	else
		$layout = NULL;

	// the maximum number of articles per page
	if(is_object($layout))
		$items_per_page = $layout->items_per_page();
	else
		$items_per_page = ARTICLES_PER_PAGE;

	// list articles by date (default) or by title (option 'articles_by_title')
	if(preg_match('/\barticles_by_title\b/i', $item['options']))
		$items =& Articles::list_for_anchor_by('title', 'section:'.$item['id'], 0, $items_per_page, $layout);
	elseif(preg_match('/\barticles_by_publication\b/i', $item['options']))
		$items =& Articles::list_for_anchor_by('publication', 'section:'.$item['id'], 0, $items_per_page, $layout);
	elseif(preg_match('/\barticles_by_rating\b/i', $item['options']))
		$items =& Articles::list_for_anchor_by('rating', 'section:'.$item['id'], 0, $items_per_page, $layout);
	elseif(preg_match('/\barticles_by_reverse_rank\b/i', $item['options']))
		$items =& Articles::list_for_anchor_by('reverse_rank', 'section:'.$item['id'], 0, MAXIMUM_ITEMS_PER_SECTION+1, 'compact');
	else
		$items =& Articles::list_for_anchor('section:'.$item['id'], 0, $items_per_page, $layout);

	// actually render the html
	$box['text'] = '';
	if(is_array($items))
		$box['text'] .= Skin::build_list($items, 'decorated');
	elseif(is_string($items))
		$box['text'] .= $items;
	if($box['text'])
		$context['text'] .=  Skin::build_box('', $box['text']);

	// sub-sections targeting the main area
	if($anchors =& Sections::get_anchors_for_anchor('section:'.$item['id'], 'main')) {

		// build a complete box
		$box['bar'] = array();
		$box['text'] = '';

		// list articles by publication date
		$items = Articles::list_by_edition_date_for_anchor($anchors, 0, 50, 'full');

		// actually render the html for the section
		if(is_array($items))
			$box['text'] .= Skin::build_list($items, 'decorated');
		elseif(is_string($items))
			$box['text'] .= $items;
		if($box['text'])
			$context['text'] .= Skin::build_box(i18n::s('What is new?'), $box['text']);
	}

	//
	// attached files
	//

	// list files by date (default) or by title (option :files_by_title:)
	include_once '../files/files.php';
	if(preg_match('/\bfiles_by_title\b/i', $item['options']))
		$items = Files::list_by_title_for_anchor('section:'.$item['id'], 0, 70);
	else
		$items = Files::list_by_date_for_anchor('section:'.$item['id'], 0, 70);

	// actually render the html for the section
	if($items)
		$context['text'] .= Skin::build_box(i18n::s('Files'), Skin::build_list($items, 'decorated'));

	//
	// the comments section
	//

	// layout as defined in options
	if($item['articles_layout'] == 'boxesandarrows') {
		include_once '../comments/layout_comments_as_boxesandarrows.php';
		$layout =& new Layout_comments_as_boxesandarrows();

	} elseif($item['articles_layout'] == 'daily') {
		include_once '../comments/layout_comments_as_daily.php';
		$layout =& new Layout_comments_as_daily();

	} elseif($item['articles_layout'] == 'jive') {
		include_once '../comments/layout_comments_as_jive.php';
		$layout =& new Layout_comments_as_jive();

	} elseif($item['articles_layout'] == 'manual') {
		include_once '../comments/layout_comments_as_manual.php';
		$layout =& new Layout_comments_as_manual();

	} elseif($item['articles_layout'] == 'yabb') {
		include_once '../comments/layout_comments_as_yabb.php';
		$layout =& new Layout_comments_as_yabb();

	// layout as defined by general parameter
	} elseif($context['root_articles_layout'] == 'boxesandarrows') {
		include_once '../comments/layout_comments_as_boxesandarrows.php';
		$layout =& new Layout_comments_as_boxesandarrows();

	} elseif($context['root_articles_layout'] == 'daily') {
		include_once '../comments/layout_comments_as_daily.php';
		$layout =& new Layout_comments_as_daily();

	} else
		$layout = 'no_anchor';

	// the maximum number of comments per page
	if(is_object($layout))
		$items_per_page = $layout->items_per_page();
	else
		$items_per_page = COMMENTS_PER_PAGE;

	// build a complete box
	$box['bar'] = array();
	$box['text'] = '';

	// list comments by date
	include_once '../comments/comments.php';
	$items = Comments::list_by_date_for_anchor('section:'.$item['id'], 0, $items_per_page, $layout);

	// actually render the html
	if(is_array($items))
		$box['text'] .= Skin::build_list($items, 'rows');
	elseif(is_string($items))
		$box['text'] .= $items;

	// build a box
	if($box['text'])
		$context['text'] .= Skin::build_box('', $box['text']);

	//
	// related link
	//

	// list links by date (default) or by title (option :links_by_title:)
	include_once '../links/links.php';
	if(preg_match('/\blinks_by_title\b/i', $item['options']))
		$items = Links::list_by_title_for_anchor('section:'.$item['id'], 0, 70);
	else
		$items = Links::list_by_date_for_anchor('section:'.$item['id'], 0, 70);

	// actually render the html
	if($items)
		$context['text'] .= Skin::build_box(i18n::s('Links'), Skin::build_list($items, 'decorated'));

	// insert anchor suffix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_suffix();

}

// render the skin
render_skin();

?>