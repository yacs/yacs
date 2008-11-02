<?php
/**
 * display pages of one section as a slideshow
 *
 * Restrictions apply on this page:
 * - associates and editors are allowed to move forward
 * - permission is denied if the anchor is not viewable
 * - access is restricted ('active' field == 'R'), but the surfer is an authenticated member
 * - public access is allowed ('active' field == 'Y')
 * - permission denied is the default
 *
 * Accept following invocations:
 * - slideshow.php/12
 * - slideshow.php?id=12
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../links/links.php';

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
	$anchor =& Anchors::get($item['anchor']);

// editors have associate-like capabilities
if((isset($item['id']) && Sections::is_assigned($item['id'])) || (is_object($anchor) && $anchor->is_editable()))
	Surfer::empower();

// associates and editors can do what they want
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

// load the skin, and use the special template for S5
load_skin('s5');

// the title of the page
if(isset($item['title']))
	$context['page_title'] = $item['title'];

// not found
if(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Sections::get_url($item['id'], 'slideshow')));

	// permission denied to authenticated user
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// display the section
} else {

	// initialize the rendering engine
	Codes::initialize(Sections::get_permalink($item));

	// increment silently the hits counter if not associate, nor creator
	if(Surfer::is_associate())
		;
	elseif(Surfer::get_id() && isset($item['create_id']) && (Surfer::get_id() == $item['create_id']))
		;
	else {
		$item['hits'] = isset($item['hits'])?($item['hits']+1):1;
		Sections::increment_hits($item['id']);
	}

	//
	// first slide reflects section content
	//

	// allow associates and editors to change the page
	$anchor = '';
	if(Surfer::is_empowered())
		$anchor = Skin::build_link(Sections::get_permalink($item), MORE_IMG, 'basic');

	$context['text'] .= '<div class="slide first">'."\n"
		.'<h1>'.Codes::beautify_title($item['title']).$anchor.'</h1>'."\n"
		.'<h3>'.Codes::beautify($item['introduction']).'</h3>'."\n"
		.'<h4>'.Codes::beautify($item['description']).'</h4>'."\n"
		.'</div>'."\n";

	//
	// one slide per article attached to this section
	//

	// select a layout
	include_once '../articles/layout_articles_as_slides.php';
	$layout =& new Layout_articles_as_slides();

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
		$items =& Articles::list_for_anchor_by('reverse_rank', 'section:'.$item['id'], 0, $items_per_page, $layout);
	else
		$items =& Articles::list_for_anchor('section:'.$item['id'], 0, $items_per_page, $layout);

	// actually render the html for the box
	if(is_array($items))
		$context['text'] .= Skin::build_list($items, 'decorated');
	elseif(is_string($items))
		$context['text'] .= $items;

	//
	// one last slide to revert to anchor page
	//

	// back link
	if(isset($item['anchor']) && ($anchor =& Anchors::get($item['anchor'])))
		$link = $anchor->get_url();
	else
		$link =& Sections::get_permalink($item);

	// one closing slide
	$context['text'] .= '<div class="slide last">'."\n"
		.'<h1>'.i18n::s('End of the presentation').'</h1>'."\n"
		.'<h3>'.Skin::build_link($link, i18n::s('Back to web browsing'), 'shortcut').'</h3>'."\n"
		.'</div>'."\n\n";

}

// render the skin
render_skin();

?>