<?php
/**
 * provide news in the RSS 0.92 format
 *
 * This script lists the ten newest published articles, providing following information:
 * - title - the title of the article
 * - url - the absolute url to fetch the article
 * - time - the date and time of article last modification
 * - author - the last contributor to the article
 * - section - the label of the section from where the article is originated
 * - image - the absolute url to fetch a related image, if any
 *
 * You will find below an excerpt of a real RSS 0.92 feed:
 * [snippet]
 * <?xml version="1.0"?>
 * <rss version="0.92">
 *	<channel>
 *		<title>blog</title>
 *		<link>http://circle.ch/blog/index.php</link>
 *		<description>babblings!</description>
 *		<lastBuildDate>Thu, 23 Jan 2003 17:53:39 GMT</lastBuildDate>
 *		<docs>http://backend.userland.com/rss092</docs>
 *		<managingEditor>urs@circle.ch</managingEditor>
 *		<webMaster>urs@circle.ch</webMaster>
 *		<language>en</language>
 *
 *		<item>
 *				<title>nanoweb and OpenOffice.org releases</title>
 *				<description>I just found some new releases; one on the webserver called nanoweb (2.0.1), which is hacked completely in PHP [1] and a new release of the Office suite OpenOffice.org (1.0.2) [2].</description>
 *				<link>http://circle.ch/blog/p560.html</link>
 *		</item>
 *		<item>
 *				<title>HTTP-server written in Postscript</title>
 *				<description>PS-HTTPD [1] is a HTTP-server written in Postscript. It is very simple (even if the code is somewhat complex) but can handle the basic tasks of a web-server. There are also Debian packages available [2].
 *
 * [1] http://pugo.org:8080
 * [2] http://www.godisch.de/debian/pshttpd/ </description>
 *				<link>http://circle.ch/blog/p558.html</link>
 *		</item>
 * ...
 *		<item>
 *				<title>Common UNIX Printing System for Mac OS X </title>				<description>[via whump.com] Use CUPS to print to any PostScript printer now also on Mac OS X [1].[1] http://www.macosxhints.com/article.php?story=20020827081141461 </description>
 *				<link>http://circle.ch/blog/p539.html</link>
 *		</item>
 *
 *	</channel>
 * </rss>
 * [/snippet]
 *
 * If following features are enabled, this script will use them:
 * - compression - Through gzip, we have observed a shift from 2900 bytes to 909 bytes, meaning one Ethernet frame rather than two
 * - cache - Cache is supported through ETag and by setting Content-Length; Also, Cache-Control enables caching for some time, even through HTTPS
 *
 * @link http://www.tbray.org/ongoing/When/200x/2003/08/02/RSSNumbers RSS Flow, Measured
 * @link http://rss.lockergnome.com/archives/help/006601.phtml Optimising your feed
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
$cache_id = 'feeds/rss_0.92.php#news';
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
	$text = '<?xml version="1.0" encoding="'.$context['charset'].'"?>'."\n"
		.'<rss version="0.92">'."\n"
		.'	<channel>'."\n"
		.'		<title>'.encode_field(strip_tags($context['channel_title'])).'</title>'."\n"
		.'		<link>'.$context['url_to_home'].'/</link>'."\n"
		.'		<description>'.encode_field($context['channel_description']).'</description>'."\n"
		.'		<lastBuildDate>'.gmdate('D, d M Y H:i:s').' GMT</lastBuildDate>'."\n"
		.'		<docs>http://backend.userland.com/rss092</docs>'."\n"
		.'		<managingEditor>'.encode_field($context['webmaster_address']).'</managingEditor>'."\n"
		.'		<webMaster>'.encode_field($context['webmaster_address']).'</webMaster>'."\n"
		.'		<language>'.encode_field($context['preferred_language']).'</language>'."\n";

	// get local news
	include_once 'feeds.php';
	$rows = Feeds::get_local_news();

	// process rows, if any
	if(is_array($rows)) {

		// limit to ten items
		@array_splice($rows, 10);

		// for each item
		foreach($rows as $url => $attributes) {
			list($time, $title, $author, $section, $image, $description) = $attributes;

			// output one story
			$text .= "\n".' <item>'."\n"
				.'		<title>'.encode_field(strip_tags($title))."</title>\n"
				."		<link>$url</link>\n"
				.'		<description>'.encode_field(strip_tags($description))."</description>\n"
				.'		<pubDate>'.gmdate('D, d M Y H:i:s', intval($time))." GMT</pubDate>\n"
				."	</item>\n";

		}
	}

	// the postamble
	$text .= "\n	</channel>\n"
		.'</rss>';

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
	$file_name = utf8::to_ascii($context['site_name'].'.rss.0.92.xml');
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