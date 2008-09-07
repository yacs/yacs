<?php
/**
 * provide news in the RSS 1.0 format
 *
 * This script lists the ten newest published articles, providing following information:
 * - title - the title of the article
 * - url - the absolute url to fetch the article
 * - time - the date and time of article last modification
 * - author - the last contributor to the article
 * - section - the label of the section from where the article is originated
 * - image - the absolute url to fetch a related image, if any
 *
 * You will find below an excerpt of a real RSS 1.0 feed:
 * [snippet]
 * <?xml version="1.0" encoding="UTF-8"?>
 * <rdf:RDF
 *	xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
 *	xmlns="http://purl.org/rss/1.0/"
 * >
 * <channel rdf:about="http://www.php.net/">
 *	<title>PHP: Hypertext Preprocessor</title>
 *	<link>http://www.php.net/</link>
 *	<description>The PHP scripting language web site</description>
 *	<items>
 *		<rdf:Seq>
 *			<rdf:li rdf:resource="http://qa.php.net/" />
 *			...
 *			<rdf:li rdf:resource="http://www.php.net/downloads.php" />
 *		</rdf:Seq>
 *	</items>
 * </channel>
 * <!-- RSS-Items -->
 *
 * <item rdf:about="http://qa.php.net/">
 *	<title>PHP 4.3.0RC4 Released</title>
 *	<link>http://qa.php.net/</link>
 *	<description>	Despite our best efforts, it was necessary to make one more release candidate, hence PHP 4.3.0RC4. This one mainly corrects the CGI vs CLI SAPI naming and fixes a couple of bugs. It is supposed to have a short testing cycle, after which the final version will be put out, hopefully before the end of the year.  </description>
 * </item>
 *
 * ...
 *
 * <item rdf:about="http://www.php.net/downloads.php">
 *	<title>PHP 4.2.3 Released</title>
 *	<link>http://www.php.net/downloads.php</link>
 *	<description>	PHP 4.2.3 has been released with a large number of bug fixes.  It is a maintenance release, and is a recommended update for all users of PHP, and Windows users in particular. A complete list of changes can be found in the ChangeLog.  </description>
 * </item>
 * <!-- / RSS-Items PHP/RSS -->
 * </rdf:RDF>
 * [/snippet]
 *
 * If following features are enabled, this script will use them:
 * - compression - Through gzip, we have observed a shift from 3859 bytes to 964 bytes, meaning one Ethernet frame rather than three
 * - cache - Cache is supported through ETag and by setting Content-Length; Also, Cache-Control enables caching for some time, even through HTTPS
 *
 * @link http://web.resource.org/rss/1.0/spec RDF Site Summary (RSS) 1.0 Specification
 * @link http://www.tbray.org/ongoing/When/200x/2003/08/02/RSSNumbers RSS Flow, Measured
 * @link http://rss.lockergnome.com/archives/help/006601.phtml Optimising your feed
 *
 * If the geographical position of the server has been set through the configuration panel for skins,
 * it is included into the feed as well.
 *
 * @link http://www.w3.org/2003/01/geo/ RDFIG Geo vocab workspace
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
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	die(i18n::s('You are not allowed to perform this operation.'));
}

// get the list from the cache, if possible
$cache_id = 'feeds/rss_1.0.php#news';
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
		.'<rdf:RDF'."\n"
		.'	xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"'."\n"
		.'	xmlns:geo="http://www.w3.org/2003/01/geo/wgs84_pos#"'."\n"
		.'	xmlns="http://purl.org/rss/1.0/"'."\n"
		.">\n"
		.'<channel rdf:about="'.$context['url_to_home'].'/">'."\n"
		.'	<title>'.encode_field(strip_tags($context['channel_title'])).'</title>'."\n"
		.'	<link>'.$context['url_to_home'].'/</link>'."\n"
		.'	<description>'.encode_field($context['channel_description']).'</description>'."\n";

	// the geographical position, if any
	if(isset($context['site_position']) && $context['site_position']) {
		list($latitude, $longitude) = preg_split('/[ ,\t]+/', $context['site_position']);
		$text .= '	<geo:Point>'."\n"
			.'		<geo:lat>'.encode_field($latitude).'</geo:lat>'."\n"
			.'		<geo:long>'.encode_field($longitude).'</geo:long>'."\n"
			.'	</geo:Point>'."\n";
	}

	// the list of items
	$text .= '	<items>'."\n"
		.'		<rdf:Seq>'."\n";

	// get local news
	include_once 'feeds.php';
	$rows = Feeds::get_local_news();

	// process rows, if any
	if(is_array($rows)) {

		// limit to ten items
		@array_splice($rows, 10);

		// build the list
		$items = '';

		// for each item
		foreach($rows as $url => $attributes) {
			list($time, $title, $author, $section, $image, $description) = $attributes;

			// the index of resources
			$text .= '		<rdf:li rdf:resource="'.$url.'" />'."\n";

			// output of one story
			$items .= "\n".'<item rdf:about="'.$url.'">'."\n"
				.'	<title>'.encode_field(strip_tags($title))."</title>\n"
				."	<link>$url</link>\n"
				.'	<description>'.encode_field(strip_tags($description)).' ('.gmdate('D, d M Y H:i:s', intval($time))." GMT)</description>\n";
			$items .= "</item>\n";

		}
	}

	// the postamble
	$text .= "		</rdf:Seq>\n"
		."	</items>\n"
		."</channel>\n";

	// the items themselves
	$text .= $items;

	// the suffix
	$text .= "\n</rdf:RDF>";

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
	$file_name = utf8::to_ascii($context['site_name'].'.rdf.xml');
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