<?php
/**
 * print one article
 *
 * Restrictions apply on this page:
 * - associates and editors are allowed to move forward
 * - creator is allowed to view the page
 * - permission is denied if the anchor is not viewable
 * - article is restricted ('active' field == 'R'), but the surfer is an authenticated member
 * - public access is allowed ('active' field == 'Y')
 * - permission denied is the default
 *
 * Accept following invocations:
 * - print.php/12
 * - print.php?id=12
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../overlays/overlay.php';

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
    $anchor = Anchors::get($item['anchor']);

// get the related overlay, if any
$overlay = NULL;
include_once '../overlays/overlay.php';
if(isset($item['overlay']))
	$overlay = Overlay::load($item);

// maybe this anonymous surfer is allowed to handle this item
if(isset($item['handle']) && Surfer::may_handle($item['handle']))
	Surfer::empower();

// editors can do what they want on items anchored here
elseif(Articles::is_assigned($id) || (is_object($anchor) && $anchor->is_editable()))
	Surfer::empower();

// anonymous edition is allowed here
elseif(isset($item['options']) && $item['options'] && preg_match('/\banonymous_edit\b/i', $item['options']))
	Surfer::empower();

// members edition is allowed here
elseif(Surfer::is_member() && isset($item['options']) && $item['options'] && preg_match('/\bmembers_edit\b/i', $item['options']))
	Surfer::empower();

// associates and editors can do what they want
if(Surfer::is_empowered() || Articles::is_assigned($id) || (is_object($anchor) && $anchor->is_editable()))
	$permitted = TRUE;

// poster can always view the page
elseif(Surfer::is_creator($item['create_id']))
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

// load localized strings
i18n::bind('articles');

// load the skin with a specific variant
load_skin('print');

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the title of the page
if(isset($item['title']) && $item['title'])
    $context['page_title'] = $item['title'];
else
	$context['page_title'] = i18n::s('No title has been provided.');

// not found
if(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Articles::get_url($item['id'], 'print')));

	// permission denied to authenticated user
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// display the article
} else {

	// initialize the rendering engine
	Codes::initialize(Articles::get_url($item['id'], 'view', $item['title']));

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
		$description = Codes::beautify($item['description'], $item['options']);

		// use adequate label
		if(is_object($overlay) && ($label = $overlay->get_label('description')))
			$context['text'] .= Skin::build_block($label, 'title').'<p>'.$description."</p>\n";
		else
			$context['text'] .= $description."\n";
	}

	//
	// attached files
	//

	// list files by date (default) or by title (option files_by_title)
	include_once '../files/files.php';
	$items = array();
	if(isset($item['options']) && preg_match('/\bfiles_by_title\b/i', $item['options']))
		$items = Files::list_by_title_for_anchor('article:'.$item['id'], 0, 50, 'compact');
	else
		$items = Files::list_by_date_for_anchor('article:'.$item['id'], 0, 50, 'compact');

	// actually list items
	if(count($items))
		$context['text'] .= Skin::build_box(i18n::s('Related files'), Skin::build_list($items, 'decorated'), 'section');

	//
	// attached comments
	//

	// list immutable comments by date
	include_once '../comments/comments.php';
	$items = Comments::list_by_date_for_anchor('article:'.$item['id'], 0, 500, 'excerpt');

	// actually list items
	if(count($items))
		$context['text'] .= Skin::build_box(i18n::s('Comments'), Skin::build_list($items, 'rows'), 'section');

	//
	// related links
	//

	// list links by date (default) or by title (option links_by_title)
	include_once '../links/links.php';
	$items = array();
	if(isset($item['options']) && preg_match('/\blinks_by_title\b/i', $item['options']))
		$items = Links::list_by_title_for_anchor('article:'.$item['id'], 0, 50, 'compact');
	else
		$items = Links::list_by_date_for_anchor('article:'.$item['id'], 0, 50, 'compact');

	// actually list items
	if(count($items))
		$context['text'] .= Skin::build_box(i18n::s('Related links'), Skin::build_list($items, 'decorated'), 'section');

	// insert anchor suffix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_suffix();

}

// render the skin
render_skin();

?>