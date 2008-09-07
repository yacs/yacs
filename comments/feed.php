<?php
/**
 * list comments in the RSS 2.0 format
 *
 * List up to ten fresh comments for one anchor only.
 * At the moment, this script gives the list of the newest posted comments,
 * with following information:
 * - title - the title of the article
 * - url - the absolute url to fetch the comment
 * - time - the date and time of comment last modification
 * - author - the last contributor to the comment
 *
 * If following features are enabled, this script will use them:
 * - compression - Through gzip, we have observed a shift from 3098 bytes to 751 bytes, meaning one Ethernet frame rather than three
 * - cache - Cache is supported through ETag and by setting Content-Length; Also, Cache-Control enables caching for some time, even through HTTPS
 *
 * Access is granted only if the surfer is allowed to view the anchor page.
 *
 * Accept following invocations:
 * - feed.php -- list of last comments
 * - feed.php/12 -- list of last comments for article #12
 * - feed.php?id=12
 * - feed.php/article/12 -- list of last comments for given anchor
 * - feed.php?anchor=article:12
 *
 * @link http://blogs.law.harvard.edu/tech/rss RSS 2.0 Specification
 *
 * If the anchor for this item specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'comments.php';

// look for the anchor as a string
$anchor = '';
if(isset($_REQUEST['anchor']))
	$anchor = $_REQUEST['anchor'];
elseif(isset($context['arguments'][0]) && isset($context['arguments'][1]))
	$anchor = $context['arguments'][0].':'.$context['arguments'][1];
$anchor =& Anchors::get(strip_tags($anchor));

// no anchor, look for an article id
if(!$anchor) {
	$id = NULL;
	if(isset($_REQUEST['id']))
		$id = $_REQUEST['id'];
	elseif(isset($context['arguments'][0]))
		$id = $context['arguments'][0];
	$id = strip_tags($id);
	$anchor =& Anchors::get('article:'.$id);
}

// the anchor has to be viewable by this surfer
if(!is_object($anchor) || $anchor->is_viewable())
	$permitted = TRUE;
else
	$permitted = FALSE;

// load the skin, maybe with a variant
load_skin('comments', $anchor);

// path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'comments/' => i18n::s('Comments') );

// page title
$context['page_title'] = i18n::s('RSS feed');

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// permission denied
} elseif(!$permitted) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// display feed content
} else {

	// get the list from the cache, if possible
	if(is_object($anchor))
		$cache_id = 'comments/feed.php?anchor='.$anchor->get_reference().'#channel';
	else
		$cache_id = 'comments/feed.php#channel';
	if(!$text =& Cache::get($cache_id)) {

		// loads feeding parameters
		Safe::load('parameters/feeds.include.php');

		// set channel information
		$values = array();
		$values['channel'] = array();
		if(is_object($anchor)) {
			$values['channel']['title'] = sprintf(i18n::s('Comments for: %s'), $anchor->get_title());
			$values['channel']['link'] = $context['url_to_home'].$context['url_to_root'].$anchor->get_url();
			$values['channel']['description'] = $anchor->get_teaser('quote');
		} else {
			$values['channel']['title'] = sprintf(i18n::s('Recent comments at %s'), $context['site_name']);
			$values['channel']['link'] = $context['url_to_home'].$context['url_to_root'];
			$values['channel']['description'] = i18n::s('Each article also has its own newsfeed.');
		}

		// the image for this channel
		if(isset($context['powered_by_image']) && $context['powered_by_image'])
			$values['channel']['image'] = $context['url_to_home'].$context['url_to_root'].$context['powered_by_image'];

		// list comments from the database -- 2000 is the limit set in Comments::purge_for_anchor()
		if(is_object($anchor))
			$values['items'] = Comments::list_by_date_for_anchor($anchor->get_reference(), 0, 2000, 'feeds');
		else
			$values['items'] = Comments::list_by_date(0, 2000, 'feeds');

		// make a text
		include_once '../services/codec.php';
		include_once '../services/rss_codec.php';
		$result = rss_Codec::encode($values);
		$text = @$result[1];

		// save in cache for the next request
		Cache::put($cache_id, $text, 'comments');
	}

	//
	// transfer to the user agent
	//

	// handle the output correctly
	render_raw('text/xml; charset='.$context['charset']);

	// suggest a name on download
	if(!headers_sent()) {
		if(is_object($anchor))
			$file_name = $context['site_name'].'.comments.'.str_replace(':', '.', $anchor->get_reference()).'.xml';
		else
			$file_name = $context['site_name'].'.comments.xml';
		$file_name =& utf8::to_ascii($file_name);
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

	// the post-processing hook, then exit
	finalize_page(TRUE);

}

// render the skin
render_skin();

?>