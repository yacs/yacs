<?php
/**
 * proxy requests from browser
 *
 * This script addresses issue related to cross-domain scripting
 *
 * @todo insert standard HTTP headers to signal proxying to callee
 * @todo transcode cookie domains
 * @todo transcode host http header
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 *
 */
include_once '../shared/global.php';

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	echo i18n::s('You are not allowed to perform this operation.');

// we reject requests not originating from this server
} elseif(!isset($_SERVER['HTTP_REFERER']) || strncmp($_SERVER['HTTP_REFERER'], $context['url_to_home'], strlen($context['url_to_home']))) {
	Safe::header('Status: 400 Bad Request', TRUE, 400);
	echo i18n::s('You are not allowed to perform this operation.');

// process the provided url
} elseif(isset($_REQUEST['url']) && $_REQUEST['url']) {

	// read raw content
	$raw_data = file_get_contents("php://input");

	// save the raw request if debug mode
	if(isset($context['with_debug']) && ($context['with_debug'] == 'Y'))
		Logger::remember('services/proxy.php: proxy request', $raw_data, 'debug');

	// forward the request, and process the response
	$response = http::proceed($_REQUEST['url']);

	// save response headers if debug mode
	if(isset($context['with_debug']) && ($context['with_debug'] == 'Y'))
		Logger::remember('services/proxy.php: proxy response headers', http::get_headers(), 'debug');

	// transmit response headers
	$headers = explode("\n", http::get_headers());
	for($index = 1; $index < count($headers); $index++) {

		// assume we will provide our own encoding
		if(!strncmp($headers[$index], 'Content-Encoding', strlen('Content-Encoding')))
			continue;

		// only one header per type
		$replace = TRUE;

		// maybe several cookies can be set
		if(!strncmp($headers[$index], 'Set-Cookie', strlen('Set-Cookie')))
			$replace = FALSE;

		// remember this one
		Safe::header($headers[$index], $replace);
	}

	// size should always refer to the actual content
	Safe::header('Content-Length: '.strlen($response));

	// save response content if debug mode
	if(isset($context['with_debug']) && ($context['with_debug'] == 'Y'))
		Logger::remember('services/proxy.php: proxy response content', $response, 'debug');

	// actual transmission except on a HEAD request
	if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
		echo $response;

}

?>
