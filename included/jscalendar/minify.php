<?php
/**
 * concatenate js files
 *
 * This script reads all javascript from the directory included/jscalendar
 * and returns a single string to the browser, to save on HTTP requests.
 *
 * If the library jsmin is available it is used to reduce the size of the
 * concatenated string (61k raw scripts, 39k after reduction.)
 *
 * @see included/jsmin.php
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

// no need for access to the database
define('NO_MODEL_PRELOAD', TRUE);

// common definitions and initial processing
include_once '../../shared/global.php';

// get content from the cache, if possible
$hash = Cache::hash('included/jscalendar/minify-'.$context['language'].'.js');
if(!$text =& Safe::file_get_contents($hash)) {

	// the returned string
	$text = '';

	// leading scripts
	if(isset($context['with_debug']) && ($context['with_debug'] == 'Y'))
		Logger::remember('included/jscalendar/minify.php', 'calendar.js', '', 'debug');
	$text .= Safe::file_get_contents($context['path_to_root'].'included/jscalendar/calendar.js')."\n";

	if(file_exists($context['path_to_root'].'included/jscalendar/lang/calendar-'.strtolower($context['language']).'.js')) {
		if(isset($context['with_debug']) && ($context['with_debug'] == 'Y'))
			Logger::remember('included/jscalendar/minify.php', 'lang/calendar-'.strtolower($context['language']).'.js', '', 'debug');
		$text .= Safe::file_get_contents($context['path_to_root'].'included/jscalendar/lang/calendar-'.strtolower($context['language']).'.js')."\n";
	} else {
		if(isset($context['with_debug']) && ($context['with_debug'] == 'Y'))
			Logger::remember('included/jscalendar/minify.php', 'lang/calendar-en.js', '', 'debug');
		$text .= Safe::file_get_contents($context['path_to_root'].'included/jscalendar/lang/calendar-en.js')."\n";
	}

	if(isset($context['with_debug']) && ($context['with_debug'] == 'Y'))
		Logger::remember('included/jscalendar/minify.php', 'lang/calendar-setup.js', '', 'debug');
	$text .= Safe::file_get_contents($context['path_to_root'].'included/jscalendar/calendar-setup.js')."\n";

	// minify the thing
// 	if(file_exists($context['path_to_root'].'included/jsmin.php')) {
// 		include_once $context['path_to_root'].'included/jsmin.php';
// 		$text = JSMin::minify($text);
// 	}

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