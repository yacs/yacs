<?php
/**
 * print one section
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
include_once '../comments/comments.php';
include_once '../links/links.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item = Sections::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor = Anchors::get($item['anchor']);

// editors have associate-like capabilities
if((isset($item['id']) && Sections::is_assigned($item['id'])) || (is_object($anchor) && $anchor->is_assigned()))
	Surfer::empower();

// load the skin, with a specific variant
load_skin('print');

// the title of the page
if(isset($item['title']))
	$context['page_title'] = $item['title'];

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// not found
} elseif(!isset($item['id'])) {
	include '../error.php';

// permission denied
} elseif(!Sections::allow_access($item, $anchor)) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Sections::get_url($item['id'], 'print')));

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

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
		$details[] = RESTRICTED_FLAG.i18n::s('Community - Access is granted to any identified surfer');

	// restricted to associates
	elseif($item['active'] == 'N')
		$details[] = PRIVATE_FLAG.i18n::s('Private - Access is restricted to selected persons');

	// rank for this section
	if((intval($item['rank']) != 10000) && Surfer::is_associate())
		$details[] = sprintf(i18n::s('Rank: %s'), $item['rank']);

	// signal sections to be activated
	if(Surfer::is_empowered() && ($item['activation_date'] > $context['now']))
		$details[] = DRAFT_FLAG.' '.sprintf(i18n::s('Section will be activated %s'), Skin::build_date($item['activation_date']));

	// expired section
	if(Surfer::is_empowered() && ($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
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
	else
		$layout = Layouts::new_($item['sections_layout'], 'section');	

	// the maximum number of sections per page
	if(is_object($layout))
		$items_per_page = $layout->items_per_page();
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
	else
		$layout = Layouts::new_ ($item['articles_layout'], 'article');

	// the maximum number of articles per page
	if(is_object($layout))
		$items_per_page = $layout->items_per_page();
	else
		$items_per_page = ARTICLES_PER_PAGE;

	// list articles by date (default) or by title (option 'articles_by_title')
	if(preg_match('/\barticles_by_([a-z_]+)\b/i', $item['options'], $matches))
		$order = $matches[1];
	else
		$order = 'edition';
	$items = Articles::list_for_anchor_by($order, 'section:'.$item['id'], 0, MAXIMUM_ITEMS_PER_SECTION+1, 'compact');

	// actually render the html
	$box['text'] = '';
	if(is_array($items))
		$box['text'] .= Skin::build_list($items, 'decorated');
	elseif(is_string($items))
		$box['text'] .= $items;
	if($box['text'])
		$context['text'] .=  Skin::build_box('', $box['text']);

	// newest articles posted in this branch of the content tree
	if($anchors = Sections::get_branch_at_anchor('section:'.$item['id'])) {

		// build a complete box
		$box['bar'] = array();
		$box['text'] = '';

		// list articles by publication date
		$items = Articles::list_for_anchor_by('edition', $anchors, 0, 50, 'full');

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
	if(preg_match('/\bfiles_by_title\b/i', $item['options']))
		$items = Files::list_by_title_for_anchor('section:'.$item['id'], 0, 300, 'section:'.$item['id']);
	else
		$items = Files::list_by_date_for_anchor('section:'.$item['id'], 0, 300, 'section:'.$item['id']);

	// actually render the html for the section
	if($items)
		$context['text'] .= Skin::build_box(i18n::s('Files'), Skin::build_list($items, 'decorated'));

	//
	// the comments section
	//

	// layout for printed comments
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
