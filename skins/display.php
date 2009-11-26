<?php
/**
 * display an image
 *
 * This script provides a full-screen view of the target image.
 *
 * Allowed call:
 * - display.php?id=skins%2Fflexible%2Fboxes%2Ft-wood5.jpg
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../images/image.php'; // background positioning

// stop hackers
if(isset($_REQUEST['id']))
	$_REQUEST['id'] = str_replace('../', '', $_REQUEST['id']);

// load the skin
load_skin('skins');

// only associates can proceed
if(!Surfer::is_associate()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// we need a target image
} elseif(!isset($_REQUEST['id']) || !is_file($context['path_to_root'].$_REQUEST['id'])) {
	Safe::header('Status: 400 Bad Request', TRUE, 400);
	Logger::error(i18n::s('Request is invalid.'));

// build a page for this image
} else {

	$text = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">'."\n"
		.'<html  xmlns="http://www.w3.org/1999/xhtml">'."\n"
		.'<head>'."\n"
		.'	<style type="text/css" media="screen">'."\n"
		.'	div#demo {'."\n"
		.'		min-height: 500px;'."\n"
		.'		width: 100%;'."\n"
		.'		background: pink '.Image::as_background($context['url_to_root'].$_REQUEST['id']).';'."\n"
		.'		text-align: center;'."\n"
		.'		margin: 0;'."\n"
		.'		padding: 0;'."\n"
		.'		border: 1px solid #666;'."\n"
		.'	}'."\n"
		.'	</style>'."\n"
		.'	<title>'.basename($_REQUEST['id']).'</title>'."\n"
		.'</head>'."\n"
		.'<body><div style="text-align: center;">'.basename($_REQUEST['id']).'</div>'
		.'<div id="demo"> </div></body></html>';

	//
	// transfer to the user agent
	//

	// handle the output correctly
	render_raw('text/xml; charset='.$context['charset']);

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