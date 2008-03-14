<?php
/**
 * concatenate js files
 *
 * This script reads all javascript from the directory included/browser
 * and returns a single string to the browser, to save on HTTP requests.
 *
 * To reduce the size of Javascript files you can use the jsmin command from
 * the Control Panel
 *
 * @see control/jsmin.php
 *
 * The result of concatenation and compression is cached in the
 * directory for temporary files.
 *
 * If following features are enabled, this script will use them:
 * - compression - Using gzip, if accepted by user agent
 * - cache - Cache is supported through ETag and by setting Content-Length;
 * Also, Cache-Control enables caching for some time, even through HTTPS
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// no decision, no extension
define('NO_CONTROLLER_PRELOAD', TRUE);

// no need for transforming data
define('NO_VIEW_PRELOAD', TRUE);

// no need for access to the database
define('NO_MODEL_PRELOAD', TRUE);

// common definitions and initial processing
include_once '../../shared/global.php';

// get content from the cache, if possible
$hash = Cache::hash('included/browser/minify.js');
if(!$text =& Safe::file_get_contents($hash)) {

	// the returned string
	$text = '';

	// prototype
	if(isset($context['with_debug']) && ($context['with_debug'] == 'Y'))
		Logger::remember('included/browser/minify.php', 'prototype.js', '', 'debug');
	$text .= Safe::file_get_contents($context['path_to_root'].'included/browser/prototype.js')."\n";

	// effects
	if(isset($context['with_debug']) && ($context['with_debug'] == 'Y'))
		Logger::remember('included/browser/minify.php', 'effects.js', '', 'debug');
	$text .= Safe::file_get_contents($context['path_to_root'].'included/browser/effects.js')."\n";

	// script to not load afterwards
	$to_avoid = array(

		// already loaded
		'effects.js', 'prototype.js',

		// not used at the moment
		'builder.js', 'scriptaculous.js', 'slider.js', 'sound.js', 'unittest.js'

		);

	// read all js files
	foreach(Safe::glob('*.js') as $name) {
		if(in_array($name, $to_avoid))
			continue;
		if(isset($context['with_debug']) && ($context['with_debug'] == 'Y'))
			Logger::remember('included/browser/minify.php', $name, '', 'debug');
		$text .= Safe::file_get_contents($context['path_to_root'].'included/browser/'.$name)."\n";
	}

	// save in cache for the next request
	Safe::file_put_contents($hash, $text);
}

//
// transfer to the user agent
//

// handle the output correctly
render_raw('application/javascript; charset='.$context['charset']);

// suggest a name on download
if(!headers_sent()) {
	$file_name = utf8::to_ascii('minify.js');
	Safe::header('Content-Disposition: inline; filename="'.$file_name.'"');
}

// cache only on regular operation
if(file_exists($context['path_to_root'].'parameters/switch.on')) {

	// enable 3-day caching (3*24*60*60 = 259200), even through https
	if(!headers_sent()) {
		Safe::header('Expires: '.gmdate("D, d M Y H:i:s", time() + 259200).' GMT');
		Safe::header("Cache-Control: max-age=259200, public");
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

// force reload on each call
} else {
	Safe::header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	Safe::header("Cache-Control: no-store, no-cache, must-revalidate");
	Safe::header("Cache-Control: post-check=0, pre-check=0", false);
	Safe::header("Pragma: no-cache");
}

// actual transmission except on a HEAD request
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
	echo $text;

// the post-processing hook
finalize_page();

?>