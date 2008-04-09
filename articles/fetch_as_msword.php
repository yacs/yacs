<?php
/**
 * download one article into Microsoft MS-Word
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
 * The downloaded object is always cacheable, to avoid IE to remove it too early from temporary directory.
 *
 * @link http://www.webmasterworld.com/forum88/5891.htm Internet Explorer download problem
 *
 * Accept following invocations:
 * - fetch_as_msword.php/12
 * - fetch_as_msword.php?id=12
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
	$context['page_title'] = utf8::to_unicode($item['title']);
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
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Articles::get_url($item['id'], 'fetch_as_msword')));

	// permission denied to authenticated user
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// display the article
} else {

	// always use the iso-8859-15 charset, else word will get garbage chars...
	$context['charset'] = 'iso-8859-15';

	// the creator of this article, if any, and if different from the publisher and if more than 24 hours before last edition
	if(Surfer::is_member() && isset($item['create_date']) && ($item['create_id'] != $item['publish_id'])
		&& (SQL::strtotime($item['create_date'])+24*60*60 < SQL::strtotime($item['edit_date']))) {

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
	if($item['create_date'] && ($item['edit_date'] == $item['create_date']))
		;
	elseif($item['publish_date'] && ($item['edit_date'] == $item['publish_date']))
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

	// all details
	if($details)
		$context['text'] .= ucfirst(implode(', ', $details)).BR."\n";

	// display the source, if any
	if(isset($item['source']) && $item['source']) {
		include_once '../links/links.php';
		if($attributes = Links::transform_reference($item['source'])) {
			list($link, $title, $description) = $attributes;
			$item['source'] = $title;
		}
		$context['text'] .= '<p>'.sprintf(i18n::s('Source: %s'), utf8::to_unicode($item['source']))."</p>\n";
	}

	// insert anchor prefix
	if(is_object($anchor))
		$context['text'] .= utf8::to_unicode($anchor->get_prefix());

	// the introduction text
	if(isset($item['introduction']) && $item['introduction'])
		$context['text'] .= Skin::build_block(utf8::to_unicode($item['introduction']), 'introduction');

	// get text related to the overlay, if any
	if(is_object($overlay))
		$context['text'] .= utf8::to_unicode($overlay->get_text('view', $item));

	// the description, which is the actual page body
	if(isset($item['description']) && $item['description']) {
		$description = utf8::to_unicode(Codes::beautify($item['description'], $item['options']));

		// use adequate label
		if(is_object($overlay) && ($label = $overlay->get_label('description')))
			$context['text'] .= Skin::build_block($label, 'title').'<div>'.$description."</div>\n";
		else
			$context['text'] .= $description."\n";
	}

	//
	// attached files
	//

	// list files by date (default) or by title (option :files_by_title:)
	include_once '../files/files.php';
	$items = array();
	if(isset($item['options']) && preg_match('/\bfiles_by_title\b/i', $item['options']))
		$items = Files::list_by_title_for_anchor('article:'.$item['id'], 0, 50, 'compact');
	else
		$items = Files::list_by_date_for_anchor('article:'.$item['id'], 0, 50, 'compact');

	// actually list items
	if(count($items))
		$context['text'] .= Skin::build_box(i18n::s('Related files'), utf8::to_unicode(Skin::build_list($items, 'compact')));

	//
	// attached comments
	//

	// list immutable comments by date
	include_once '../comments/comments.php';
	$items = Comments::list_by_date_for_anchor('article:'.$item['id'], 0, 500, 'excerpt');

	// actually list items
	if($items)
		$context['text'] .= Skin::build_box(i18n::s('Comments'), utf8::to_unicode($items));

	//
	// related links
	//

	// list links by date (default) or by title (option :links_by_title:)
	include_once '../links/links.php';
	$items = array();
	if(preg_match('/\blinks_by_title\b/i', $item['options']))
		$items = Links::list_by_title_for_anchor('article:'.$item['id'], 0, 50, 'compact');
	else
		$items = Links::list_by_date_for_anchor('article:'.$item['id'], 0, 50, 'compact');

	// actually list items
	if(count($items))
		$context['text'] .= Skin::build_box(i18n::s('Related links'), utf8::to_unicode(Skin::build_list($items, 'compact')));

	//
	// page suffix
	//

	// link to the original page
	$context['text'] .= '<p>'.sprintf(i18n::s('The original page is located at %s'), Skin::build_link($context['url_to_home'].$context['url_to_root'].Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']), $context['url_to_home'].$context['url_to_root'].Articles::get_url($item['id'], 'view', $item['title'])))."</p>\n";

	// insert anchor suffix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_suffix();

	//
	// special rendering if everything is ok
	//

	$text = '<html><body>'."\n";

	// display the title
	if(isset($context['page_title']) && $context['page_title'])
		$text .= Skin::build_block($context['page_title'], 'page_title');

	// display error messages, if any
	$text .= Skin::build_error_block();

	// render and display the content, if any
	$text .= $context['text']."\n";

	$text .= '</body></html>'."\n";

	// MS-WORD won't import pictures
	$text = preg_replace('/<img (.*?)\/>/i', '', $text);

	// strip relative links
	$text = preg_replace('/<a (.*?)>(.*?)<\/a>/is', '\\2', $text);

	//
	// transfer to the user agent
	//

	// handle the output correctly
	render_raw('application/msword; charset='.$context['charset']);

	// suggest a download
	if(!headers_sent()) {
		$file_name = utf8::to_ascii(Skin::strip($context['page_title'], 20).'.doc');
		Safe::header('Content-Disposition: attachment; filename="'.$file_name.'"');
	}

	// enable 30-minute caching (30*60 = 1800), even through https, to help IE6 on download
	if(!headers_sent()) {
		Safe::header('Expires: '.gmdate("D, d M Y H:i:s", time() + 1800).' GMT');
		Safe::header("Cache-Control: max-age=1800, public");
		Safe::header("Pragma: ");
	}

	// strong validation
	if((!isset($context['without_http_cache']) || ($context['without_http_cache'] != 'Y')) && !headers_sent()) {

		// generate some strong validator
		$etag = '"'.md5($text).'"';
		Safe::header('ETag: '.$etag);

		// validate the content if hash is ok
		if(isset($_SERVER['HTTP_IF_NONE_MATCH']) && is_array($if_none_match = explode(',', str_replace('\"', '"', $_SERVER['HTTP_IF_NONE_MATCH'])))) {
			foreach($if_none_match as $target) {
				if(trim($target) == $etag) {
					Safe::header('Status: 304 Not Modified', TRUE, 304);
					return;
				}
			}
		}
	}

	// actual transmission except on a HEAD request
	if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
		echo $text;

	// the post-processing hook, then exit
	finalize_page(TRUE);

}

// render the normal skin in case of error
render_skin();

?>