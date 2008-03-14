<?php
/**
 * describe blogging API in RSD format
 *
 * Really Simple Discovery (RSD) is a way to help client software find the
 * services needed to read, edit, or "work with" weblogging software.
 *
 * @link http://cyber.law.harvard.edu/blogs/gems/tech/rsd.html RSD 1.0 specification
 *
 * This script is referenced from the front page, to help locate the blogging interface.
 *
 * If following features are enabled, this script will use them:
 * - compression - Using gzip, if accepted by user agent
 * - cache - Cache is supported through ETag and by setting Content-Length; Also, Cache-Control enables caching for some time, even through HTTPS
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// load a skin engine
load_skin('services');

// the preamble
$text = '<?xml version="1.0" encoding="'.$context['charset'].'"?>'."\n"
	.'<rsd version="1.0" xmlns="http://archipelago.phrasewise.com/rsd" >'."\n";

// the service
$text .= '<service>'."\n";

// name and link for the engine
$text .= '	<engineName>YACS</engineName>'."\n"
	.'<engineLink>http://www.yetanothercommunitysystem.com/</engineLink>'."\n";

// server home page
$text .= '	<homePageLink>'.$context['url_to_home'].$context['url_to_root'].'</homePageLink>'."\n";

// available blogging api
$text .= '	<apis>'."\n"
	.'		<api name="Movable Type" preferred="true" apiLink="'.$context['url_to_home'].$context['url_to_root'].'services/blog.php" blogID="" />'."\n"
	.'		<api name="MetaWeblog" preferred="false" apiLink="'.$context['url_to_home'].$context['url_to_root'].'services/blog.php" blogID="" />'."\n"
	.'		<api name="Blogger" preferred="false" apiLink="'.$context['url_to_home'].$context['url_to_root'].'services/blog.php" blogID="" />'."\n"
	.'	</apis>'."\n";

// the postamble
$text .= '</service>'."\n".'</rsd>'."\n";

//
// transfer to the user agent
//

// handle the output correctly
render_raw('text/xml; charset='.$context['charset']);

// suggest a name on download
if(!headers_sent()) {
	$file_name = utf8::to_ascii($context['site_name'].'.rsd.xml');
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

// the post-processing hook
finalize_page();

?>