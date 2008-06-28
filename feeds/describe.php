<?php
/**
 * describe available feeds in OPML
 *
 * This script outline following information sources:
 * - the main rss feed of the site
 * - the rss feed for files (and, therefore, for podcasting)
 * - one feed per section (up to 7 sections)
 * - one feed per category (up to 7 categories)
 * - one feed per user (up to seven users)
 *
 * This script may be used by crawlers to index pages.
 * Therefore, only partial information is provided here.
 *
 * If following features are enabled, this script will use them:
 * - compression - Using gzip, if accepted by user agent
 * - cache - Cache is supported through ETag and by setting Content-Length; Also, Cache-Control enables caching for some time, even through HTTPS
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'feeds.php';

// load a skin engine
load_skin('feeds');

// get the list from the cache, if possible
$cache_id = 'feeds/describe.php#content';
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

	// use site name as a suffix for channel titles
	$suffix = ' - '.$context['channel_title'];

	// the preamble
	$text = '<?xml version="1.0" encoding="'.$context['charset'].'"?>'."\n"
		.'<opml xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'."\n";

	// the head
	$text .= '<head>'."\n";

	// the title
	$text .= '	<title>'.encode_field(strip_tags($context['channel_title']))."</title>\n";

	// the body
	$text .= '</head>'."\n".'<body>'."\n";

	// the main rss feed of this site
	$text .= '	<outline type="rss" title="'.encode_field($context['channel_title']).'" xmlurl="'.$context['url_to_home'].$context['url_to_root'].Feeds::get_url('rss').'"'." />\n";

	// full articles
	$text .= '	<outline type="rss" title="'.encode_field(i18n::c('Articles with full content').$suffix).'" xmlurl="'.$context['url_to_home'].$context['url_to_root'].Feeds::get_url('articles').'"'." />\n";

	// newest comments
	$text .= '	<outline type="rss" title="'.encode_field(i18n::c('Comments and reactions').$suffix).'" xmlurl="'.$context['url_to_home'].$context['url_to_root'].Feeds::get_url('comments').'"'." />\n";

	// the file rss feed for podcasting, etc.
	$text .= '	<outline type="rss" title="'.encode_field(i18n::c('Files and podcasts').$suffix).'" xmlurl="'.$context['url_to_home'].$context['url_to_root'].Feeds::get_url('files').'"'." />\n";

	// one feed per section
	if($items = Sections::list_by_title_for_anchor(NULL, 0, COMPACT_LIST_SIZE, 'raw'))
		foreach($items as $id => $attributes)
			$text .= '	<outline type="rss" title="'.encode_field(strip_tags($attributes['title']).$suffix).'" xmlurl="'.$context['url_to_home'].$context['url_to_root'].Sections::get_url($id, 'feed').'"'." />\n";

	// one feed per category
	include_once '../categories/categories.php';
	if($items = Categories::list_by_date(0, COMPACT_LIST_SIZE, 'raw'))
		foreach($items as $id => $attributes)
			$text .= '	<outline type="rss" title="'.encode_field(strip_tags($attributes['title']).$suffix).'" xmlurl="'.$context['url_to_home'].$context['url_to_root'].Categories::get_url($id, 'feed').'"'." />\n";

	// one feed per user
	if($items = Users::list_by_posts(0, COMPACT_LIST_SIZE, 'raw'))
		foreach($items as $id => $attributes)
			$text .= '	<outline type="rss" title="'.encode_field(strip_tags($attributes['nick_name']).$suffix).'" xmlurl="'.$context['url_to_home'].$context['url_to_root'].Users::get_url($id, 'feed').'"'." />\n";


	// the postamble
	$text .= '</body>'."\n".'</opml>'."\n";

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
	$file_name = utf8::to_ascii($context['site_name'].'.opml.xml');
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