<?php
/**
 * browse content as a Freemind map
 *
 * This script allows for the interactive browsing of site content,
 * through a full-size Flash or Java applet.
 *
 * Accept following invocations:
 * - view_as_freemind.php - browse the entire content tree
 * - view_freemind.php/12/any_name - browse content of section 12
 * - view_as_freemind.php?id=12 - browse content of section 12
 *
 * @author Bernard Paques
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);
if($id == 'all')
	$id = NULL;

// get the item from the database
$item = Sections::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor = Anchors::get($item['anchor']);

// editors have associate-like capabilities
if(Surfer::is_empowered('M') && (isset($item['id']) && isset($user['id']) && (Sections::is_assigned($item['id'], $user['id']))) || (is_object($anchor) && $anchor->is_assigned()))
	Surfer::empower('A');

// load a skin, maybe with a variant
load_skin('sections', $anchor, isset($item['options']) ? $item['options'] : '');

// the path to this page
$context['path_bar'] = array( 'sections/' => i18n::s('Site map') );

// the title of the page
if(isset($item['title']) && $item['title'])
	$context['page_title'] = $item['title'];
else
	$context['page_title'] = i18n::s('Freemind');

// not found
if($id && !isset($item['id'])) {
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

// display data
} else {

	// web reference to site full content
	if(isset($item['id']))
		$target_href = $context['url_to_home'].$context['url_to_root'].Sections::get_url($item['id'], 'freemind', utf8::to_ascii($context['site_name'].' - '.strip_tags(Codes::beautify_title(trim($item['title']))).'.mm'));
	else
		$target_href = $context['url_to_home'].$context['url_to_root'].Sections::get_url('all', 'freemind', utf8::to_ascii($context['site_name'].'.mm'));

	// allow to load Flash objects
	$script = 'included/browser/js_header/swfobject.js';

	// page preamble
	$text = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">'."\n"
		.'<html xmlns="http://www.w3.org/1999/xhtml">'."\n"
		.'<head>'."\n"
			.'<title>'.$item['title'].'</title>'."\n"
			.'<script type="text/javascript" src="'.$context['url_to_root'].$script.'"></script>'."\n"
		.'</head>'."\n"
		.'<body>'."\n";

	// render object full size
	$text .= Codes::render_freemind($target_href.', 100%, 600px');

	// page postamble
	$text .= '</body>'."\n"
			.'</html>'."\n";

	//
	// transfer to the user agent
	//

	// if we have a valid wrapper
	if($text) {

		// return the standard MIME type, but ensure the user agent will accept it -- Internet Explorer 6 don't
//		if(isset($_SERVER['HTTP_ACCEPT']) && stristr($_SERVER['HTTP_ACCEPT'], 'application/xhtml+xml'))
//			Safe::header('Content-Type: application/xhtml+xml; charset='.$context['charset']);
//		else
			Safe::header('Content-Type: text/html; charset='.$context['charset']);

		Safe::header('Content-Length: '.strlen($text));

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

}

// render the skin
render_skin();

?>
