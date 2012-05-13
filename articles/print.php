<?php
/**
 * print one article
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
$item =& Articles::get($id);

// get the related anchor
$anchor = NULL;
if(isset($item['anchor']))
	$anchor =& Anchors::get($item['anchor']);

// get the related overlay, if any
$overlay = NULL;
if(isset($item['overlay']))
	$overlay = Overlay::load($item, 'article:'.$item['id']);

// load the skin with a specific variant
load_skin('print');

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

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
} elseif(!Articles::allow_access($item, $anchor)) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Articles::get_url($item['id'], 'print')));

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// display the article
} else {

	// initialize the rendering engine
	Codes::initialize(Articles::get_permalink($item));

	// the article or the anchor icon, if any
	if(isset($item['icon_url']) && $item['icon_url'])
		$context['page_image'] = $item['icon_url'];
	elseif(is_object($anchor))
		$context['page_image'] = $anchor->get_icon_url();

	// display the source, if any
	if(isset($item['source']) && $item['source']) {
		include_once '../links/links.php';
		if($attributes = Links::transform_reference($item['source'])) {
			list($link, $title, $description) = $attributes;
			$item['source'] = $title;
		}
		$context['text'] .= '<p>'.sprintf(i18n::s('Source: %s'), $item['source'])."</p>\n";
	}

	// insert anchor prefix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_prefix();

	// the introduction text
	if(isset($item['introduction']) && $item['introduction'])
		$context['text'] .= Skin::build_block($item['introduction'], 'introduction');

	// get text related to the overlay, if any
	if(is_object($overlay))
		$context['text'] .= $overlay->get_text('view', $item);

	// the beautified description, which is the actual page body
	if(isset($item['description']) && $item['description']) {

		// use adequate label
		if(is_object($overlay) && ($label = $overlay->get_label('description')))
			$context['text'] .= Skin::build_block($label, 'title');

		$context['text'] .= Skin::build_block($item['description'], 'description', '', $item['options']);

	}

	//
	// attached files
	//

	// list files by date (default) or by title (option files_by_title)
	$items = array();
	if(Articles::has_option('files_by', $anchor, $item) == 'title')
		$items = Files::list_by_title_for_anchor('article:'.$item['id'], 0, 300, 'compact');
	else
		$items = Files::list_by_date_for_anchor('article:'.$item['id'], 0, 300, 'compact');

	// actually list items
	if(count($items))
		$context['text'] .= Skin::build_box(i18n::s('Files'), Skin::build_list($items, 'decorated'));

	//
	// attached comments
	//

	// list immutable comments by date
	include_once '../comments/comments.php';
	$items = Comments::list_by_date_for_anchor('article:'.$item['id'], 0, 500, 'excerpt');

	// actually list items
	if(is_array($items))
		$context['text'] .= Skin::build_box(i18n::s('Comments'), Skin::build_list($items, 'rows'));

	//
	// related links
	//

	// list links by date (default) or by title (option links_by_title)
	include_once '../links/links.php';
	$items = array();
	if(Articles::has_option('links_by_title', $anchor, $item))
		$items = Links::list_by_title_for_anchor('article:'.$item['id'], 0, 50, 'compact');
	else
		$items = Links::list_by_date_for_anchor('article:'.$item['id'], 0, 50, 'compact');

	// actually list items
	if(count($items))
		$context['text'] .= Skin::build_box(i18n::s('Links'), Skin::build_list($items, 'decorated'));

	// insert anchor suffix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_suffix();

}

// render the skin
render_skin();

?>
