<?php
/**
 * print one category
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
include_once 'categories.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Categories::get($id);

// get the related anchor
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor =& Anchors::get($item['anchor']);

// associates and editors can do what they want
if(Surfer::is_associate() || (is_object($anchor) && $anchor->is_assigned()))
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

// load the skin
load_skin('print');

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'categories/' => i18n::s('Categories') );

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
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Categories::get_url($item['id'], 'print')));

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// display the category
} else {

	// the introduction text
	$context['text'] .= Skin::build_block($item['introduction'], 'introduction');

	// the beautified description, which is the actual page body
	$context['text'] .= Skin::build_block($item['description'], 'description');

//	// date of last update
//	$context['text'] .= i18n::s('Last update').' '.Skin::build_date($item['edit_date']);

	//
	// the section of sub-categories
	//

	// title
	$section = Skin::build_block(i18n::s('Related categories'), 'title');

	// list items by date (default) or by title (option :categories_by_title:)
	if(preg_match('/\bcategories_by_title\b/i', $item['options']))
		$items = Categories::list_by_title_for_anchor('category:'.$item['id'], 0, 50);
	else
		$items = Categories::list_by_date_for_anchor('category:'.$item['id'], 0, 50);

	// actually render the html for the section
	if($items)
		$context['text'] .= $section.Skin::build_list($items, 'decorated');

	//
	// the section of linked articles
	//

	// title
	$section = Skin::build_block(i18n::s('Pages'), 'title');

	// list items by date (default) or by title (option articles_by_title)
	if(preg_match('/\barticles_by_title\b/i', $item['options']))
		$items =& Members::list_articles_by_title_for_anchor('category:'.$item['id'], 0, 50);
	else
		$items =& Members::list_articles_by_date_for_anchor('category:'.$item['id'], 0, 50);

	// actually render the html for the section
	if($items)
		$context['text'] .= $section.Skin::build_list($items, 'decorated');

	//
	// the files section
	//

	// title
	$section = Skin::build_block(i18n::s('Files'), 'title');

	// list files by date (default) or by title (option :files_by_title:)
	if(preg_match('/\bfiles_by_title\b/i', $item['options']))
		$items = Files::list_by_title_for_anchor('category:'.$item['id'], 0, 50);
	else
		$items = Files::list_by_date_for_anchor('category:'.$item['id'], 0, 50);

	// actually render the html for the section
	if($items)
		$context['text'] .= $section.Skin::build_list($items, 'decorated');

	//
	// the links section
	//

	// title
	$section = Skin::build_block(i18n::s('See also'), 'title');

	// list links by date (default) or by title (option :links_by_title:)
	include_once '../links/links.php';
	if(preg_match('/\blinks_by_title\b/i', $item['options']))
		$items = Links::list_by_title_for_anchor('category:'.$item['id'], 0, 50);
	else
		$items = Links::list_by_date_for_anchor('category:'.$item['id'], 0, 50);

	// actually render the html
	if($items)
		$context['text'] .= $section.Skin::build_list($items, 'decorated');

}

// render the skin
render_skin();

?>
