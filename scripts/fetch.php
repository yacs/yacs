<?php
/**
 * download one or several scripts
 *
 * This script is useful to copy one or several scripts from the reference store
 * across the network.
 *
 * When a single script is required, it is returned directly with MIME type 'application/x-httpd-php'.
 *
 * When several scripts are asked for, their content is returned at once, with one line to separate them.
 * The first line is also the separator. MIME type is 'text/download'.
 *
 * Accept following invocations:
 * - fetch.php/skins/skeleton/skin.php
 * - fetch.php?script=/skins/skeleton/skin.php
 * - fetch.php?script=/skins/skeleton/skin.php,/skins/skeleton/template.php
 *
 * If following features are enabled, this script will use them:
 * - compression - using gzip
 * - cache - supported through ETag and Last-Modified, not mentioning the setting of Content-Length;
 * Also, Cache-Control enables caching for some time, even through HTTPS
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// the target script
$script = NULL;
if(isset($_REQUEST['script']))
	$script = $_REQUEST['script'];
elseif(isset($context['arguments'][0])) {
	$script = $context['arguments'][0];
	if(isset($context['arguments'][1]))
		$script .= '/'.$context['arguments'][1];
	if(isset($context['arguments'][2]))
		$script .= '/'.$context['arguments'][2];
	if(isset($context['arguments'][3]))
		$script .= '/'.$context['arguments'][3];
	if(isset($context['arguments'][4]))
		$script .= '/'.$context['arguments'][4];
	if(isset($context['arguments'][5]))
		$script .= '/'.$context['arguments'][5];
}

// fight hackers
$script = trim(preg_replace(FORBIDDEN_STRINGS_IN_PATHS, '', strip_tags($script)));

// distinguish between single and multiple requests
if($script) {
	$script = explode(',', $script);
	if(!count($script))
		$script = NULL;
}

// load localized strings
i18n::bind('scripts');

// load the skin
load_skin('scripts');

// the path to this page
$context['path_bar'] = array( 'scripts/' => i18n::s('Server software') );

// the title of the page
if(is_array($script))
	$context['page_title'] = $script[0];
elseif($script)
	$context['page_title'] = $script;
else
	$context['page_title'] = i18n::s('Please indicate a script name.');

// no argument has been passed
if(!$script)
	$context['text'] .= '<p>'.i18n::s('Please indicate a script name.')."</p>\n";

// actually answer the request
else {

	// the separator
	$separator = '----------------------- aqwzsxedcrfvtgbyhnujikolpm ---------------------------'."\n";

	// the output
	$text = '';

	// one script at a time
	foreach($script as $name) {

		// read the file from the reference store
		if(!$content = Safe::file_get_contents($context['path_to_root'].'scripts/reference/'.$name)) {
			Safe::header('Status: 404 Not Found', TRUE, 404);
			exit('File "'.'scripts/reference/'.$name.'" not found');
		}

		// happen this to the output buffer
		if($text)
			$text .= $separator;
		$text .= $content;

	}

	// only one script has been asked
	if(count($script) == 1) {

		// compress the page if possible, but no transcoding -- the bare handler
		$context['charset'] = 'ASCII';
		render_raw('text/x-httpd-php');

		// send the response to the caller
		if(!headers_sent())
			Safe::header('Content-Description: Reference file from YACS environment');

		// suggest a download
		if(!headers_sent()) {
			$file_name = utf8::to_ascii(basename($script[0]));
			Safe::header('Content-Disposition: attachment; filename="'.$file_name.'"');
		}

	// several scripts at one
	} else {

		// multi-part separator on the first line
		$text = $separator.$text;

		// compress the page if possible, but no transcoding -- the bare handler
		$context['charset'] = 'ASCII';
		render_raw('text/html');

		// send the response to the caller
		if(!headers_sent())
			Safe::header('Content-Description: Reference files from YACS environment');

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

		// generate some weak validator
		if(count($script) == 1) {
			$last_modified = gmdate('D, d M Y H:i:s', Safe::filemtime($context['path_to_root'].'scripts/reference/'.$script[0])).' GMT';
			Safe::header('Last-Modified: '.$last_modified);

			// validate the content if stamp is ok, and if ETag is not handled
			if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && ($if_modified_since = preg_replace('/;.*$/', '', $_SERVER['HTTP_IF_MODIFIED_SINCE']))) {
				if(($if_modified_since == $last_modified) && !$_SERVER['HTTP_IF_NONE_MATCH']) {
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

// render the skin
render_skin();

?>