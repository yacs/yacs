<?php
/**
 * provide news in the RSS 2.0 format
 *
 * This script lists newest published articles, providing following information:
 * - title - title of the article
 * - link - absolute url to fetch the article
 * - description - introduction of the particle, or the first part of the main text
 * - content:encoded - article description (up to 500 words)
 * - time - date and time of article last modification
 * [removed because of spammers] - author - last contributor to the article
 * - category - label of the section from where the article is originated
 * - image - absolute url to fetch a related image, if any
 * - comments - absolute url to expose related comments for a human being
 *
 * @link http://blogs.law.harvard.edu/tech/rss RSS 2.0 Specification
 * @link http://www.disobey.com/detergent/2002/extendingrss2/ Extending RSS 2.0 With Namespaces
 * @link http://feeds.archive.org/validator/ FEED Validator for Atom and RSS
 *
 * Also, several meta-links are provided to help client software to do its job correclty:
 * - trackback:about - absolute url to fetch the article (=== link)
 * - trackback:ping - absolute url to post links to the article
 * - wfw:comment - absolute url to post a new comment
 * - wfw:commentRss - absolute url to expose related comments as RSS feed
 *
 * Basically, these meta-links mean that YACS fully support trackback and Comment API specifications.
 *
 * @link http://purl.org/rss/1.0/modules/content/ xmlns:content
 * @link http://madskills.com/public/xml/rss/module/trackback/ xmlns:trackback
 * @link http://wellformedweb.org/CommentAPI/ xmlns:wfw
 * @link http://wellformedweb.org/story/9 The Comment API
 *
 * If the geographical position of the server has been set through the configuration panel for skins,
 * it is included into the feed as well.
 *
 * @link http://postneo.com/icbm/ ICBM RSS module
 *
 * Additionally, the provided XML links to a cascaded style sheet, enabling further rendering enhancements.
 *
 * You will find below an excerpt of a real RSS 2.0 feed from Yahoo! News:
 * [snippet]
 * <?xml version="1.0" encoding="UTF-8"?>
 * <rss version="2.0">
 * <channel>
 * <title>Yahoo! News - Technology</title>
 * <link>http://news.yahoo.com/news?tmpl=index&amp;cid=738</link>
 * <description>Yahoo! News - Technology</description>
 * <language>en-us</language>
 * <lastBuildDate>Sun, 19 Oct 2003 15:11:25 GMT</lastBuildDate>
 * <ttl>5</ttl>
 * <image>
 * <title>Yahoo! News</title>
 * <width>142</width>
 * <height>18</height>
 * <link>http://news.yahoo.com/</link>
 * <url>http://us.i1.yimg.com/us.yimg.com/i/us/nws/th/main_142.gif</url>
 * </image>
 * <item>
 *	<title>Programs: 'Ghostmaster' Offers a Boo-Tiful Fright (Reuters)</title>
 *	<link>http://us.rd.yahoo.com/dailynews/rss/738/*http://story.news.yahoo.com/news?tmpl=story2&amp;u=/nm/20031018/tc_nm/column_programs_dc</link>
 *	<guid isPermaLink="false">nm/20031018/column_programs_dc</guid>
 *	<pubDate>Sat, 18 Oct 2003 11:57:39 GMT</pubDate>
 *	<description>Reuters - Most spooky computer and
 *	console games require players to fight off ghosts, goblins, and
 *	poltergeists. But "Ghost Master" turns -- and sometimes upturns
 *	-- the tables.</description>
 * </item>
 * ...
 * <item>
 *	<title>Apple/Analysts on the iTunes Music Store, iPod (MacCentral)</title>
 *	<link>http://us.rd.yahoo.com/dailynews/rss/738/*http://story.news.yahoo.com/news?tmpl=story2&amp;u=/mc/20031017/tc_mc/appleanalystsontheitunesmusicstoreipod</link>
 *	<guid isPermaLink="false">mc/20031017/appleanalystsontheitunesmusicstoreipod</guid>
 *	<pubDate>Fri, 17 Oct 2003 17:16:32 GMT</pubDate>
 *	<description>MacCentral - Hell may have frozen over, as Apple's Web site says, but the battle for supremacy in the digital music distribution market is just heating up. With the release of iTunes Music Store for Windows, Apple is confident that they have the solution to beat the competition. With the help of the iPod digital music player, analysts agree that Apple has an edge.</description>
 * </item>
 * </channel>
 * </rss>
 * [/snippet]
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

// load a skin engine
load_skin('feeds');

// get the list from the cache, if possible
$cache_id = Cache::hash('feeds/rss').'.xml';

// save for 5 minutes
if(!file_exists($context['path_to_root'].$cache_id) || (filemtime($context['path_to_root'].$cache_id)+300 < time()) || (!$text = Safe::file_get_contents($context['path_to_root'].$cache_id))) {
	$text = '';

	// load feeding parameters
	Safe::load('parameters/feeds.include.php');

	// we are handling structured data
	$values = array();
	$values['channel'] = array();

	// set default values
	if(!isset($context['channel_title']) || !$context['channel_title'])
		$context['channel_title'] = $context['site_name'].' - '.i18n::c('Latest Articles');
	if(!isset($context['channel_description']) || !$context['channel_description'])
		$context['channel_description'] = $context['site_description'];
	if(!isset($context['webmaster_address']) || !$context['webmaster_address'])
		$context['webmaster_address'] = $context['site_email'];

	// set channel information
	$values['channel']['title'] = $context['channel_title'];
	$values['channel']['link'] = $context['url_to_home'].'/';
	$values['channel']['description'] = $context['channel_description'];

	// the image for this channel
	if(isset($context['powered_by_image']) && $context['powered_by_image'])
		$values['channel']['image'] = $context['powered_by_image'];

	// get local news
	include_once 'feeds.php';
	$values['items'] = Feeds::get_local_news();

	// make a text
	include_once '../services/codec.php';
	include_once '../services/rss_codec.php';
	$result = rss_Codec::encode($values);
	$text = @$result[1];

	// put in cache
	Safe::file_put_contents($cache_id, $text);

}

//
// transfer to the user agent
//

// handle the output correctly
render_raw('text/xml; charset='.$context['charset']);

// suggest a name on download
if(!headers_sent()) {
	$file_name = utf8::to_ascii($context['site_name'].'.rss.xml');
	Safe::header('Content-Disposition: inline; filename="'.$file_name.'"');
}

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

// the post-processing hook
finalize_page();

?>
