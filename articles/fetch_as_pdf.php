<?php
/**
 * download one article as a PDF file
 *
 * @todo allow for a configurable footer string
 *
 * The downloaded object is always cacheable, to avoid IE to remove it too early from temporary directory.
 *
 * @link http://www.webmasterworld.com/forum88/5891.htm Internet Explorer download problem
 *
 * Accept following invocations:
 * - fetch_as_pdf.php/12
 * - fetch_as_pdf.php?id=12
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

// page title
if(is_object($overlay))
	$context['page_title'] = $overlay->get_text('title', $item);
elseif(isset($item['title']))
	$context['page_title'] = $item['title'];
$context['page_title'] = utf8::to_unicode($context['page_title']);

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
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Articles::get_url($item['id'], 'fetch_as_pdf')));

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// display the article
} else {

	// use anchor title as document subject
	if(is_object($anchor))
		$context['subject'] = utf8::to_unicode($anchor->get_title());

	// set specific headers
	if(isset($item['create_name']) && $item['create_name'])
		$context['page_author'] = $item['create_name'];
	if(isset($item['edit_date']) && $item['edit_date'])
		$context['page_date'] = $item['edit_date'];
	if(isset($item['introduction']) && $item['introduction'])
		$context['page_meta'] = strip_tags(Codes::beautify_introduction($item['introduction']));
	if(isset($item['publish_name']) && $item['publish_name'])
		$context['page_publisher'] = $item['publish_name'];

	// insert anchor prefix
	if(is_object($anchor))
		$context['text'] .= utf8::to_unicode($anchor->get_prefix());

	// the introduction text
	if(is_object($overlay))
		$context['text'] .= Skin::build_block(utf8::to_unicode($overlay->get_text('introduction', $item)), 'introduction');
	elseif(isset($item['introduction']) && trim($item['introduction']))
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
	// the files section
	//

	// list files by date (default) or by title (option files_by_title)
	$items = array();
	if(Articles::has_option('files_by', $anchor, $item) == 'title')
		$items = Files::list_by_title_for_anchor('article:'.$item['id'], 0, 300, 'compact');
	else
		$items = Files::list_by_date_for_anchor('article:'.$item['id'], 0, 300, 'compact');

	// actually render the html for the section
	if(count($items))
		$context['text'] .= Skin::build_box(i18n::s('Files'), Skin::build_list($items, 'compact'));

	//
	// the comments section
	//

	// list immutable comments by date
	$items = Comments::list_by_date_for_anchor('article:'.$item['id'], 0, 500, 'excerpt');

	// actually render the html
	if($items)
		$context['text'] .= Skin::build_box(i18n::s('Comments'), utf8::to_unicode($items));

	//
	// the links section
	//

	// list links by date (default) or by title (option links_by_title)
	$items = array();
	if(Articles::has_option('links_by_title', $anchor, $item))
		$items = Links::list_by_title_for_anchor('article:'.$item['id'], 0, 50, 'compact');
	else
		$items = Links::list_by_date_for_anchor('article:'.$item['id'], 0, 50, 'compact');

	// actually render the html
	if(count($items))
		$context['text'] .= Skin::build_box(i18n::s('See also'), utf8::to_unicode(Skin::build_list($items, 'compact')));

	//
	// page suffix
	//

	// add trailer information from the overlay, if any
	if(is_object($overlay))
		$context['text'] .= utf8::to_unicode($overlay->get_text('trailer', $item));

	// add trailer information from this item, if any
	if(isset($item['trailer']) && trim($item['trailer']))
		$context['text'] .= utf8::to_unicode(Codes::beautify($item['trailer']));

	// gather details
	$details = Articles::build_dates($anchor, $item);

	// all details
	if(count($details))
		$context['text'] .= '<p>'.ucfirst(implode(', ', $details))."</p>\n";

	// display the source, if any
	if(isset($item['source']) && $item['source']) {
		if($attributes = Links::transform_reference($item['source'])) {
			list($link, $title, $description) = $attributes;
			$item['source'] = $title;
		}
		$context['text'] .= '<p>'.sprintf(i18n::s('Source: %s'), utf8::to_unicode($item['source']))."</p>\n";
	}

	// link to the original page
	$context['text'] .= '<p>'.sprintf(i18n::s('The original page is located at %s'), Skin::build_link(Articles::get_permalink($item), Articles::get_permalink($item)))."</p>\n";

	// insert anchor suffix
	if(is_object($anchor))
		$context['text'] .= utf8::to_unicode($anchor->get_suffix());

	//
	// generate some HTML
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

	//
	// make some PDF
	//

	// PDF is not UTF-8
	$text = utf8::to_iso8859($text);

	// actual generation
	if(!defined('FPDF_FONTPATH'))
		define('FPDF_FONTPATH', $context['path_to_root'].'included/fpdf/font/');
	include_once '../included/fpdf/fpdf.php';
	include_once '../shared/pdf.php';
	$pdf = new PDF();
	$text = $pdf->encode($text);

	//
	// transfer to the user agent
	//

	// blind transfer
	if(!headers_sent()) {
		Safe::header('Content-Type: application/pdf');
		Safe::header('Content-Transfer-Encoding: binary');
		Safe::header('Content-Length: '.strlen($text));
	}

	// suggest a download
	if(!headers_sent()) {
		$file_name = utf8::to_ascii(Skin::strip($context['page_title']).'.pdf');
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
