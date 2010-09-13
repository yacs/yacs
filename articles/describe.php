<?php
/**
 * describe an article in RDF
 *
 * @todo derive this to support web slices http://en.wikipedia.org/wiki/Web_Slice
 * @todo web slice example at http://blogs.msdn.com/ie/archive/2009/03/03/create-a-dynamic-web-slice-in-5-minutes.aspx
 *
 * This script may be used by crawlers to index pages.
 * Therefore, only partial information is provided here.
 *
 * You will find below an example of script result:
 * [snippet]
 * <?xml version="1.0" encoding="UTF-8"?>
 *	<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dc="http://purl.org/dc/elements/1.1/">
 *		<rdf:Description rdf:about="http://127.0.0.1/yacs/articles/view.php/4310">
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
 * If this article, or one of its anchor, specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 *
 * @link http://www.movabletype.org/docs/mttrackback.html TrackBack Technical Specification
 */

// common definitions and initial processing
include_once '../shared/global.php';

// check network credentials, if any
if($user = Users::authenticate())
	Surfer::empower($user['capability']);

// look for the article id
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
if(isset($item['anchor']) && $item['anchor'])
	$anchor =& Anchors::get($item['anchor']);

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
} elseif(!Articles::allow_access($item, $anchor)) {

	// give anonymous surfers a chance for HTTP authentication
	if(!Surfer::is_logged()) {
		Safe::header('WWW-Authenticate: Basic realm="'.utf8::to_iso8859($context['site_name']).'"');
		Safe::header('Status: 401 Unauthorized', TRUE, 401);
	}

	// permission denied to authenticated user
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// describe the article
} else {

	// initialize the rendering engine
	Codes::initialize(Articles::get_permalink($item));

	// compute the url for this article
	$permanent_link = $context['url_to_home'].$context['url_to_root'].Articles::get_permalink($item);

	// the trackback link
	$trackback_link = $context['url_to_home'].$context['url_to_root'].'links/trackback.php?anchor=article:'.$item['id'];

	// get a description -- render codes
	if(isset($item['introduction']) && $item['introduction'])
		$description = Codes::beautify($item['introduction'], $item['options']);
	else
		$description = Skin::cap(Codes::beautify($item['description'], $item['options']), 50);

	// prepare the response
	$text = '<?xml version="1.0" encoding="'.$context['charset'].'"?>'."\n"
		.'<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"'."\n"
		.'		   xmlns:dc="http://purl.org/dc/elements/1.1/"'."\n"
		.'		   xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/">'."\n"
		.'	<rdf:Description'."\n"
		.'		trackback:ping="'.$trackback_link.'"'."\n"
		.'		dc:identifier="'.$permanent_link.'"'."\n"
		.'		rdf:about="'.$permanent_link.'">'."\n"
		.'		<dc:title>'.encode_field($item['title']).'</dc:title>'."\n"
		.'		<dc:description>'.encode_field(Skin::strip($description)).'</dc:description>'."\n"
		.'		<dc:creator>'.$item['create_name'].'</dc:creator>'."\n"
		.'		<dc:date>'.gmdate('Y-m-d').'</dc:date>'."\n"
		.'		<dc:format>text/html</dc:format>'."\n"
		.'		<dc:language>'.$context['preferred_language'].'</dc:language>'."\n";
	if(is_object($anchor))
		$text .= '	<dc:subject>'.encode_field($anchor->get_title()).'</dc:subject>'."\n";
	$text .= '	</rdf:Description>'."\n"
		.'</rdf:RDF>';

	//
	// transfer to the user agent
	//

	// handle the output correctly
	render_raw('text/xml; charset='.$context['charset']);

	// suggest a name on download
	if(!headers_sent()) {
		$file_name = Skin::strip($context['page_title'], 20).'.opml.xml';
		$file_name =& utf8::to_ascii($file_name);
		Safe::header('Content-Disposition: inline; filename="'.$file_name.'"');
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