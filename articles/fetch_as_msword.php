<?php
/**
 * download one article into Microsoft MS-Word
 *
 * The downloaded object is always cacheable, to avoid IE to remove it too early from temporary directory.
 *
 * @link http://www.webmasterworld.com/forum88/5891.htm Internet Explorer download problem
 *
 * Accept following invocations:
 * - fetch_as_msword.php/12
 * - fetch_as_msword.php?id=12
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
$item = Articles::get($id);

// get the related anchor
$anchor = NULL;
if(isset($item['anchor']))
	$anchor = Anchors::get($item['anchor']);

// get the related overlay, if any
$overlay = NULL;
if(isset($item['overlay']))
	$overlay = Overlay::load($item, 'article:'.$item['id']);

// load the skin, with a specific rendering option
load_skin('print');

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the title of the page
if(isset($item['title']))
	$context['page_title'] = utf8::to_unicode($item['title']);

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
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Articles::get_url($item['id'], 'fetch_as_msword')));

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// display the article
} else {

	// always use the iso-8859-15 charset, else word will get garbage chars...
	$context['charset'] = 'iso-8859-15';

	// details
	$details =& Articles::build_dates($anchor, $item);

	// all details
	if($details)
		$context['text'] .= ucfirst(implode(', ', $details)).BR."\n";

	// display the source, if any
	if(isset($item['source']) && $item['source']) {
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
	$items = array();
	if(Articles::has_option('files_by', $anchor, $item) == 'title')
		$items = Files::list_by_title_for_anchor('article:'.$item['id'], 0, 300, 'compact');
	else
		$items = Files::list_by_date_for_anchor('article:'.$item['id'], 0, 300, 'compact');

	// actually list items
	if(count($items))
		$context['text'] .= Skin::build_box(i18n::s('Files'), utf8::to_unicode(Skin::build_list($items, 'compact')));

	//
	// attached comments
	//

	// list immutable comments by date
	$items = Comments::list_by_date_for_anchor('article:'.$item['id'], 0, 500, 'excerpt');

	// actually list items
	if($items)
		$context['text'] .= Skin::build_box(i18n::s('Comments'), utf8::to_unicode($items));

	//
	// related links
	//

	// list links by date (default) or by title (option :links_by_title:)
	$items = array();
	if(Articles::has_option('links_by_title', $anchor, $item))
		$items = Links::list_by_title_for_anchor('article:'.$item['id'], 0, 50, 'compact');
	else
		$items = Links::list_by_date_for_anchor('article:'.$item['id'], 0, 50, 'compact');

	// actually list items
	if(count($items))
		$context['text'] .= Skin::build_box(i18n::s('See also'), utf8::to_unicode(Skin::build_list($items, 'compact')));

	//
	// page suffix
	//

	// link to the original page
	$context['text'] .= '<p>'.sprintf(i18n::s('The original page is located at %s'), Skin::build_link($context['url_to_home'].$context['url_to_root'].Articles::get_permalink($item), $context['url_to_home'].$context['url_to_root'].Articles::get_permalink($item)))."</p>\n";

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
		$file_name = utf8::to_ascii(Skin::strip($context['page_title']).'.doc');
		Safe::header('Content-Disposition: attachment; filename="'.str_replace('"', '', $file_name).'"');
	}

	// enable 30-minute caching (30*60 = 1800), even through https, to help IE6 on download
	http::expire(1800);

	// strong validator
	$etag = '"'.md5($text).'"';

	// manage web cache
	if(http::validate(NULL, $etag))
		return;

	// actual transmission except on a HEAD request
	if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
		echo $text;

	// the post-processing hook, then exit
	finalize_page(TRUE);

}

// render the normal skin in case of error
render_skin();

?>
