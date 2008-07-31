<?php
/**
 * describe a category in RDF
 *
 * This script may be used by crawlers to index pages.
 * Therefore, only partial information is provided here.
 *
 * You will find below an example of script result:
 * [snippet]
 * <?xml version="1.0" encoding="UTF-8"?>
 *	<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dc="http://purl.org/dc/elements/1.1/">
 *		<rdf:Description rdf:about="http://127.0.0.1/yacs/categories/view.php/4310">
 *			<dc:title>Please read this marvellous page</dc:title>
 *			<dc:description>It is really interesting</dc:description>
 *			<dc:date>2004-01-15</dc:date>
 *			<dc:format>text/html</dc:format>
 *			<dc:language>en</dc:language>
 *		</rdf:Description>
 *	</rdf:RDF>
 * [/snippet]
 *
 * If following features are enabled, this script will use them:
 * - compression - Using gzip, if accepted by user agent
 * - cache - Cache is supported through ETag and by setting Content-Length; Also, Cache-Control enables caching for some time, even through HTTPS
 *
 * Restrictions apply on this page:
 * - associates and editors are allowed to move forward
 * - permission is denied if the anchor is not viewable
 * - access is restricted ('active' field == 'R'), but the surfer is an authenticated member
 * - public access is allowed ('active' field == 'Y')
 * - permission denied is the default
 *
 * Accept following invocations:
 * - describe.php/12
 * - describe.php?id=12
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

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor = Anchors::get($item['anchor']);

// associates and editors can do what they want
if(Surfer::is_associate() || (is_object($anchor) && $anchor->is_editable()))
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
load_skin('categories');

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'categories/' => i18n::s('path') );

// the title of the page
if(isset($item['title']))
	$context['page_title'] = $item['title'];

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// not found
} elseif(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// describe the category
} else {

	// compute the url for this category
	$url = $context['url_to_home'].$context['url_to_root'].Categories::get_permalink($item);

	// get a description
	if($item['introduction'])
		$description = Codes::beautify($item['introduction']);
	else
		$description = Skin::cap(Codes::beautify($item['description']), 50);

	// prepare the response
	$text = '<?xml version="1.0" encoding="'.$context['charset'].'"?>'."\n"
		.'<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dc="http://purl.org/dc/elements/1.1/">'."\n"
		.'	<rdf:Description rdf:about="'.$url.'">'."\n"
		.'		<dc:title>'.encode_field($item['title']).'</dc:title>'."\n"
		.'		<dc:description>'.encode_field(Skin::strip($description)).'</dc:description>'."\n"
		.'		<dc:date>'.gmdate('Y-m-d').'</dc:date>'."\n"
		.'		<dc:format>text/html</dc:format>'."\n"
		.'		<dc:language>'.$context['preferred_language'].'</dc:language>'."\n"
		.'	</rdf:Description>'."\n"
		.'</rdf:RDF>';

	//
	// transfer to the user agent
	//

	// handle the output correctly
	render_raw('text/xml; charset='.$context['charset']);

	// suggest a name on download
	if(!headers_sent()) {
		$file_name = utf8::to_ascii(Skin::strip($context['page_title'], 20).'.opml.xml');
		Safe::header('Content-Disposition: inline; filename="'.$file_name.'"');
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

// render the skin on error
render_skin();

?>