<?php
/**
 * download one article into a Palm device
 *
 * Restrictions apply on this page:
 * - associates and editors are allowed to move forward
 * - creator is allowed to view the page
 * - permission is denied if the anchor is not viewable
 * - article is restricted ('active' field == 'R'), but the surfer is an authenticated member
 * - public access is allowed ('active' field == 'Y')
 * - permission denied is the default
 *
 * If following features are enabled, this script will use them:
 * - compression - using gzip
 * - cache - supported through ETag and by setting Content-Length; Also, Cache-Control enables caching for some time, even through HTTPS
 *
 * Accept following invocations:
 * - fetch_as_palm.php/12
 * - fetch_as_palm.php?id=12
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

// associates and editors can do what they want
if(Surfer::is_empowered() || Articles::is_assigned($id) || (is_object($anchor) && $anchor->is_editable()))
	$permitted = TRUE;

// poster can always view the page
elseif(Surfer::get_id() && isset($item['create_id']) && ($item['create_id'] == Surfer::get_id()))
	$permitted = TRUE;

// the anchor has to be viewable by this surfer
elseif(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;

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
i18n::bind('articles');

// load the skin, with a specific rendering option
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
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Articles::get_url($item['id'], 'fetch_for_palm')));

	// permission denied to authenticated user
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// display the article
} else {

	// insert anchor prefix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_prefix();

	// the introduction text
	if(isset($item['introduction']) & $item['introduction'])
		$context['text'] .= Skin::build_block($item['introduction'], 'introduction');

	// get text related to the overlay, if any
	if(is_object($overlay))
		$context['text'] .= $overlay->get_text('view', $item);

	// the description, which is the actual page body
	if(isset($item['description']) && $item['description']) {
		$description = Codes::beautify($item['description'], $item['options']);

		// use adequate label
		if(is_object($overlay) && ($label = $overlay->get_label('description')))
			$context['text'] .= Skin::build_block($label, 'title').'<p>'.$description."</p>\n";
		else
			$context['text'] .= $description."\n";
	}

	//
	// the comments section
	//

	// list immutable comments by date
	include_once '../comments/comments.php';
	$items = Comments::list_by_date_for_anchor('article:'.$item['id'], 0, 500, 'excerpt');

	// actually render the html
	if($items)
		$context['text'] .= Skin::build_box(i18n::s('Comments'), Skin::build_list($items, 'rows'));

	//
	// details come after everything else
	//

	// gather details
	$details = array();

	// the creator of this article, if any, and if different from the publisher and if more than 24 hours before last edition
	if(Surfer::is_member() && $item['create_date'] && ($item['create_id'] != $item['publish_id'])
		&& (SQL::strtotime($item['create_date'])+24*60*60 < SQl::strtotime($item['edit_date']))) {

		$details[] = sprintf(i18n::s('posted by %s %s'), ucfirst($item['create_name']), Skin::build_date($item['create_date']));
	}

	// the publisher of this article, if any
	if(isset($item['publish_name']) && $item['publish_name']) {

		// show publisher to members
		if(Surfer::is_member()) {
			$details[] = sprintf(i18n::s('published by %s %s'), ucfirst($item['publish_name']), Skin::build_date($item['publish_date']));

		// show only publication date
		} elseif($item['publish_date'])
			$details[] = sprintf(i18n::s('published %s'), Skin::build_date($item['publish_date']));

	}

	// the last edition of this article, if different from creation/publication date
	if(isset($item['create_date']) && $item['create_date'] && ($item['edit_date'] == $item['create_date']))
		;
	elseif(isset($item['publish_date']) && $item['publish_date'] && ($item['edit_date'] == $item['publish_date']))
		;
	elseif(Surfer::is_member()) {

		// label for the last action
		if($item['edit_action'])
			$action = get_action_label($item['edit_action']);
		else
			$action = i18n::s('edited');

		// show last modifier, if any
		if($item['edit_name'])
			$details[] = sprintf(i18n::s('%s by %s %s'), $action, ucfirst($item['publish_name']), Skin::build_date($item['edit_date']));
		else
			$details[] = sprintf(i18n::s('%s %s'), $action, Skin::build_date($item['edit_date']));

	}

	// all details -- suffix
	if(count($details))
		$context['text'] .= ucfirst(implode(', ', $details)).BR."\n";

	// display the source, if any -- suffix
	if(isset($item['source']) && $item['source']) {
		include_once '../links/links.php';
		if($attributes = Links::transform_reference($item['source'])) {
			list($link, $title, $description) = $attributes;
			$item['source'] = $title;
		}
		$context['text'] .= '<p>'.sprintf(i18n::s('Source: %s'), $item['source'])."</p>\n";
	}

	// insert anchor suffix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_suffix();

	// link to the original page
	$context['text'] .= '<p>'.sprintf(i18n::s('The original page is located at %s'), Skin::build_link($context['url_to_home'].$context['url_to_root'].Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']), ''))."</p>\n";

	//
	// special rendering if everything is ok
	//

	$text = '<html><body>'."\n";

	// display the title
	if($context['page_title'])
		$text .= Skin::build_block($context['page_title'], 'page_title');

	// display error messages, if any
	$text .= Skin::build_error_block();

	// render and display the content, if any
	$text .= $context['text']."\n";

	$text .= '</body></html>'."\n";

	// don't import pictures
	$text = preg_replace('/<img (.*?)>/i', '', $text);

	// strip relative links
	$text = preg_replace('/<a (.*?)>(.*?)<\/a>/is', '\\2', $text);

	// transform HTM to the DOC format
	include_once '../included/php-pdb_html.php';
	$text = FilterHTML($text);

	// create a valid .pdb file
	include_once '../included/php-pdb.php';
	include_once '../included/php-pdb_doc.php';
	$handle =& new PalmDoc(Skin::strip($context['page_title'], 20), FALSE);
	$handle->AddDocText($text);

	//
	// transfer to the user agent
	//

	// blind transfer
	if(!headers_sent()) {
	//	Safe::header('Content-Type: application/x-pilot; charset=utf-8');
		Safe::header('Content-Type: application/x-pilot');
		Safe::header('Content-Transfer-Encoding: binary'); // already encoded
	}

	// suggest a download
	if(!headers_sent()) {
		$file_name = utf8::to_ascii(Skin::strip($context['page_title'], 20).'.pdb');
		Safe::header('Content-Disposition: attachment; filename="'.$file_name.'"');
	}

	// enable 30-minute caching (30*60 = 1800), even through https, to help IE6 on download
	if(!headers_sent()) {
		Safe::header('Expires: '.gmdate("D, d M Y H:i:s", time() + 1800).' GMT');
		Safe::header("Cache-Control: max-age=1800, public");
		Safe::header("Pragma: ");
	}

	// actual transmission except on a HEAD request
	if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
		$handle->WriteToStdout();

	// the post-processing hook, then exit
	finalize_page(TRUE);

}

// render the normal skin in case of error
render_skin();

?>