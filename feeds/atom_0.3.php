<?php
/**
 * provide news in the atom 0.3 format
 *
 * This script lists the ten newest published articles, providing following information:
 * - title - the title of the article
 * - url - the absolute url to fetch the article
 * - time - the date and time of article last modification
 * - author - the last contributor to the article
 * - section - the label of the section from where the article is originated
 * - image - the absolute url to fetch a related image, if any
 *
 * @link http://www.griffinbrown.co.uk/qa/rss.asp Validated at Dr Feed Good - Online Feed Validation (RSS 1.0/RSS 2.0/Atom 0.3)
 *
 * Additionally, the provided XML links to a cascaded style sheet, enabling further rendering enhancements.
 *
 * If following features are enabled, this script will use them:
 * - compression - Through gzip, we have observed a shift from 2900 bytes to 909 bytes, meaning one Ethernet frame rather than two
 * - cache - Cache is supported through ETag and by setting Content-Length; Also, Cache-Control enables caching for some time, even through HTTPS
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// load the rendering skin
load_skin('feeds');

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	die(i18n::s('You are not allowed to perform this operation.'));
}

// get the list from the cache, if possible
$cache_id = 'feeds/atom_0.3.php#news';
if(!$text =& Cache::get($cache_id)) {

	// loads feeding parameters
	Safe::load('parameters/feeds.include.php');

	// set default values
	if(!isset($context['channel_title']) || $context['channel_title'])
		$context['channel_title'] = $context['site_name'];
	if(!isset($context['channel_description']) || !$context['channel_description'])
		$context['channel_description'] = $context['site_description'];
	if(!isset($context['webmaster_address']) || !$context['webmaster_address'])
		$context['webmaster_address'] = $context['site_email'];

	// the preamble
	$text = '<?xml version="1.0" encoding="'.$context['charset'].'" ?>'."\n"
		.'<?xml-stylesheet type="text/css" href="'.$context['url_to_home'].$context['url_to_root'].'feeds/atom.css" ?>'."\n"
		.'<feed version="0.3"'
		.' xml:lang="'.$context['preferred_language'].'"'
		.' xmlns:dc="http://purl.org/dc/elements/1.1/"'
		.' xmlns="http://purl.org/atom/ns#">'."\n"
		.'	<title>'.encode_field(strip_tags($context['channel_title'])).'</title>'."\n"
		.'	<link rel="alternate" type="text/html" href="'.$context['url_to_home'].'/" />'."\n"
		.'	<author><name>'.encode_field($context['site_owner']).'</name></author>'."\n"
		.'	<tagline type="text/plain" mode="escaped">'.encode_field($context['channel_description']).'</tagline>'."\n"
		.'	<modified>'.gmdate('Y-m-d\TG:i:s\Z').'</modified>'."\n"
		.'	<generator url="http://www.yetanothercommunitysystem.com/">YACS</generator>'."\n";

	// get local news
	include_once 'feeds.php';
	$rows = Feeds::get_local_news();

	// process rows, if any
	if(is_array($rows)) {

		// limit to ten items
		@array_splice($rows, 10);

		// for each item
		foreach($rows as $url => $attributes) {
			list($time, $title, $author, $section, $image, $introduction, $description, $trackback) = $attributes;

			// author is a comma-separated list of e-mail_address (name)
			if(preg_match('/\((.+?)\)/', $author, $matches))
				$author = $matches[1];

			// output one story
			$text .= "\n".' <entry>'."\n";

			if($title)
				$text .= '		<title>'.encode_field(strip_tags($title))."</title>\n";

			if($url)
				$text .= '		<link rel="alternate" type="text/html" href="'.$url.'" />'."\n"
					.'		<id>'.$url.'</id>'."\n";

			if($author)
				$text .= '		<author><name>'.encode_field($author).'</name></author>'."\n";

			if($introduction)
				$text .= '		<summary type="text/html" mode="escaped"><![CDATA[ '.$introduction." ]]></summary>\n";

			if($description)
				$text .= '		<content type="text/html" mode="escaped"><![CDATA[ '.$description." ]]></content>\n";

			if(intval($time))
				$text .= '		<modified>'.gmdate('Y-m-d\TG:i:s\Z', intval($time))."</modified>\n"
					.'		<issued>'.gmdate('Y-m-d\TG:i:s\Z', intval($time))."</issued>\n";

			if($section)
				$text .= '		<dc:source>'.encode_field($section).'</dc:source>'."\n";

			if($author)
				$text .= '		<dc:creator>'.encode_field($author).'</dc:creator>'."\n";

			$text .= '		<dc:publisher>'.encode_field($context['site_owner']).'</dc:publisher>'."\n"
				.'		<dc:format>text/html</dc:format>'."\n"
				.'		<dc:language>'.$context['preferred_language'].'</dc:language>'."\n"
				.'		<dc:rights>Copyright '.encode_field($context['site_copyright']).'</dc:rights>'."\n"
				."	</entry>\n";

		}
	}

	// the postamble
	$text .= "\n".'</feed>';

	// save in cache for the next request
	Cache::put($cache_id, $text, 'articles');
}

//
// transfer to the user agent
//

// handle the output correctly
render_raw('text/xml; charset='.$context['charset']);

// suggest a name on download
if(!headers_sent()) {
	$file_name = utf8::to_ascii($context['site_name'].'.atom.xml');
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