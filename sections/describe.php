<?php
/**
 * describe a section in RDF
 *
 * This script may be used by crawlers to index pages.
 * Therefore, only partial information is provided here.
 *
 * You will find below an example of script result:
 * [snippet]
 * <?xml version="1.0" encoding="UTF-8"?>
 *	<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dc="http://purl.org/dc/elements/1.1/">
 *		<rdf:Description rdf:about="http://127.0.0.1/yacs/sections/view.php/4310">
 *			<dc:title>Please read this marvellous page</dc:title>
 *			<dc:description>It is really interesting</dc:description>
 *			<dc:date>2004-01-15</dc:date>
 *			<dc:format>text/html</dc:format>
 *			<dc:language>en</dc:language>
 *		</rdf:Description>
 *	</rdf:RDF>
 * [/snippet]
 *
 * Accept following invocations:
 * - describe.php/12
 * - describe.php?id=12
 *
 * If this section, or one of its anchor, specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// check network credentials, if any
if($user = Users::authenticate())
	Surfer::empower($user['capability']);

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
if((isset($item['id']) && Sections::is_assigned($item['id'])) || (is_object($anchor) && $anchor->is_assigned()))
	Surfer::empower();

// load the skin, maybe with a variant
load_skin('sections', $anchor, isset($item['options']) ? $item['options'] : '');

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	die(i18n::s('You are not allowed to perform this operation.'));
}

// page title
if(isset($item['title']))
	$context['page_title'] = $item['title'];

// not found
if(!$item['id']) {
	include '../error.php';

// access denied
} elseif(!Sections::allow_access($item, $anchor)) {

	// give anonymous surfers a chance for HTTP authentication
	if(!Surfer::is_logged()) {
		Safe::header('WWW-Authenticate: Basic realm="'.utf8::to_iso8859($context['site_name']).'"');
		Safe::header('Status: 401 Unauthorized', TRUE, 401);
	}

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// describe the section
} else {

	// compute the url for this section
	$url = $context['url_to_home'].$context['url_to_root'].Sections::get_permalink($item);

	// get a description
	if($item['introduction'])
		$description = Codes::beautify($item['introduction']);
	else
		$description = Skin::strip(Codes::beautify($item['description']), 50);

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
		$file_name = utf8::to_ascii(Skin::strip($context['page_title']).'.opml.xml');
		Safe::header('Content-Disposition: inline; filename="'.str_replace('"', '', $file_name).'"');
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

// render the skin on error
render_skin();

?>
