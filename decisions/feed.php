<?php
/**
 * list decisions in the RSS 2.0 format
 *
 * List up to ten fresh decisions for one anchor only.
 * At the moment, this script gives the list of the newest posted decisions,
 * with following information:
 * - title - the title of the article
 * - url - the absolute url to fetch the decision
 * - time - the date and time of decision last modification
 * - author - the last contributor to the decision
 *
 * Restrictions apply on this page:
 * - associates and editors are allowed to move forward
 * - permission is denied if the anchor is not viewable
 * - access is restricted ('active' field == 'R'), but the surfer is an authenticated member
 * - public access is allowed ('active' field == 'Y')
 * - permission denied is the default
 *
 * Accept following invocations:
 * - feed.php -- list of last decisions
 * - feed.php/12 -- list of last decisions for article #12
 * - feed.php?id=12
 * - feed.php/article/12 -- list of last decisions for given anchor
 * - feed.php?anchor=article:12
 *
 * @link http://blogs.law.harvard.edu/tech/rss RSS 2.0 Specification
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'decisions.php';

// check network credentials, if any -- used by winamp and other media players
if($user = Users::authenticate())
	Surfer::empower($user['capability']);

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

// associates and editors can do what they want
if(Surfer::is_associate() || (is_object($anchor) && $anchor->is_assigned()))
	$permitted = TRUE;

// the anchor has to be viewable by this surfer
elseif(is_object($anchor) && $anchor->is_viewable())
	$permitted = TRUE;

// no anchor -- show public decisions
elseif(!is_object($anchor))
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load the skin
load_skin('decisions');

// path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'decisions/' => i18n::s('Decisions') );

// page title
$context['page_title'] = i18n::s('RSS feed');

// permission denied
if(!$permitted) {

	// give anonymous surfers a chance for HTTP authentication
	if(!Surfer::is_logged()) {
		Safe::header('WWW-Authenticate: Basic realm="'.utf8::to_iso8859($context['site_name']).'"');
		Safe::header('Status: 401 Unauthorized', TRUE, 401);
	}

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// display feed content
} else {

	// get the list from the cache, if possible
	if(is_object($anchor))
		$cache_id = 'decisions/feed.php?anchor='.$anchor->get_reference().'#channel';
	else
		$cache_id = 'decisions/feed.php#channel';
	if(!$text =& Cache::get($cache_id)) {

		// loads feeding parameters
		Safe::load('parameters/feeds.include.php');

		// set channel information
		$values = array();
		$values['channel'] = array();
		if(is_object($anchor)) {
			$values['channel']['title'] = sprintf(i18n::s('Decisions: %s'), $anchor->get_title());
			$values['channel']['link'] = $context['url_to_home'].$context['url_to_root'].$anchor->get_url();
			$values['channel']['description'] = $anchor->get_teaser('quote');
		} else {
			$values['channel']['title'] = sprintf(i18n::s('Recent decisions at %s'), $context['site_name']);
			$values['channel']['link'] = $context['url_to_home'].$context['url_to_root'];
			$values['channel']['description'] = i18n::s('Each article also has its own newsfeed.');
		}

		// the image for this channel
		if(isset($context['powered_by_image']) && $context['powered_by_image'])
			$values['channel']['image'] = $context['url_to_home'].$context['url_to_root'].$context['powered_by_image'];

		// list decisions from the database
		if(is_object($anchor))
			$values['items'] = Decisions::list_by_date_for_anchor($anchor->get_reference(), 0, 200, 'feed');
		else
			$values['items'] = Decisions::list_by_date(0, 200, 'feed');

		// make a text
		include_once '../services/codec.php';
		include_once '../services/rss_codec.php';
		$result = rss_Codec::encode($values);
		$text = @$result[1];

		// evaluate a valid dependance
		if(is_object($anchor))
			$dependance = $anchor->get_reference();
		else
			$dependance = 'articles';

		// save in cache for the next request
		Cache::put($cache_id, $text, $dependance);
	}

	//
	// transfer to the user agent
	//

	// handle the output correctly
	render_raw('text/xml; charset='.$context['charset']);

	// suggest a name on download
	if(!headers_sent()) {
		if(is_object($anchor))
			$file_name = utf8::to_ascii($context['site_name'].'.decisions.'.str_replace(':', '.', $anchor->get_reference()).'.xml');
		else
			$file_name = utf8::to_ascii($context['site_name'].'.decisions.xml');
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

	// the post-processing hook, then exit
	finalize_page(TRUE);

}

// render the skin
render_skin();

?>
