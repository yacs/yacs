<?php
/**
 * download one article in XML
 *
 * Accept following invocations:
 * - export.php/12
 * - export.php?id=12
 *
 * If this article, or one of its anchor, specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
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
	$anchor =& Anchors::get($item['anchor']);

// get the related overlay, if any
$overlay = NULL;
include_once '../overlays/overlay.php';
if(isset($item['overlay']))
	$overlay = Overlay::load($item);

// load the skin, maybe with a variant
load_skin('articles', $anchor, isset($item['options']) ? $item['options'] : '');

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the title of the page
if(isset($item['title']))
	$context['page_title'] = $item['title'];

// not found
if(!isset($item['id'])) {
	include '../error.php';

// permission denied
} elseif(!Articles::allow_display($anchor, $item)) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Articles::get_url($item['id'], 'export')));

	// permission denied to authenticated user
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// display the article
} else {

	// article header
	$text = '<?xml version="1.0" encoding="'.$context['charset'].'"?>'."\n"
		.'<!DOCTYPE article SYSTEM "'.$context['url_to_home'].$context['url_to_root'].'articles/article.dtd">'."\n"
		.'<?xml-stylesheet type="text/css" href="'.$context['url_to_home'].$context['url_to_root'].'articles/article.css" ?>'."\n"
		.'<article>'."\n";

	// the title
	$text .= ' <title>'.encode_field($context['page_title']).'</title>'."\n";

	// the nick name
	if(isset($item['nick_name']) && $item['nick_name'])
		$text .= ' <nick_name>'.encode_field($item['nick_name']).'</nick_name>'."\n";

	// the introduction text
	if(isset($item['introduction']) && $item['introduction'])
		$text .=  ' <introduction>'.encode_field($item['introduction']).'</introduction>'."\n";

	// the source
	if(isset($item['source']) && $item['source'])
		$text .=  ' <source>'.encode_field($item['source']).'</source>'."\n";

	// the overlay, if any
	if(is_object($overlay))
		$text .= $overlay->export();

	// the description, which is the actual page body
	if(isset($item['description']) && $item['description'])
		$text .=  " <description>\n".encode_field($item['description'])."\n</description>\n";

	// article footer
	$text .= '</article>'."\n";

	//
	// transfer to the user agent
	//

	// handle the output correctly
	render_raw('text/xml; charset='.$context['charset']);

	// suggest a name on download
	if(!headers_sent()) {
		$file_name = utf8::to_ascii(Skin::strip($context['page_title'], 20).'.xml');
		Safe::header('Content-Disposition: attachment; filename="'.$file_name.'"');
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

// render the skin
render_skin();

?>