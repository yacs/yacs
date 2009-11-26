<?php
/**
 * display an image
 *
 * This script provides a full-screen view of the target image.
 *
 * Allowed call:
 * - display.php?id=boxes/t-wood5.jpg
 * - display.php/boxes/t-wood5.jpg
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../../shared/global.php';
include_once '../../images/image.php'; // background positioning

// stop hackers
if(isset($_REQUEST['id']))
	$_REQUEST['id'] = str_replace('../', '', $_REQUEST['id']);

// load the skin
load_skin('skins');

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('skins/flexible/upload.php'));

// only associates can proceed
elseif(!Surfer::is_associate()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// we need a target image
} elseif(!isset($_REQUEST['id']) || !is_readable('./'.$_REQUEST['id'])) {
	Safe::header('Status: 400 Bad Request', TRUE, 400);
	Logger::error(i18n::s('Request is invalid.'));

// build a page for this image
} else {

	$text = '<html><body style="width: 100%; height: 100%; background: pink '.Image::as_background($context['url_to_root'].'skins/flexible/'.$_REQUEST['id']).';">&nbsp;</body></html>';

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