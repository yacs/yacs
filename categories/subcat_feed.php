<?php
/**
 * list new categories in the RSS 2.0 format
 * Surveillance d'une catégorie alimentée par les membres
 *
 * List up to ten fresh pages for one category only.
 * At the moment, this script gives the list of the ten newest published articles,
 * with following information:
 * - title - the title of the article
 * - url - the absolute url to fetch the article
 * - time - the date and time of article last modification
 * - author - the last contributor to the article
 * - category - the label of the category from where the article is originated
 * - image - the absolute url to fetch a related image, if any
 *
 * Restrictions apply on this page:
 * - associates and editors are allowed to move forward
 * - permission is denied if the anchor is not viewable
 * - access is restricted ('active' field == 'R'), but the surfer is an authenticated member
 * - public access is allowed ('active' field == 'Y')
 * - permission denied is the default
 *
 * Accept following invocations:
 * - feed.php/12
 * - feed.php?id=12
 *
 * @link http://blogs.law.harvard.edu/tech/rss RSS 2.0 Specification
 *
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'categories.php';

// ensure we only provide public content through newsfeeds
$context['users_without_teasers'] = 'Y';

// check network credentials, if any
if($user = Users::authenticate())
	Surfer::empower($user['capability']);

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item = Categories::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor = Anchors::get($item['anchor']);

// associates and editors can do what they want
if(Surfer::is_associate() || (is_object($anchor) && $anchor->is_assigned()))
	$permitted = TRUE;

// the anchor has to be viewable by this surfer
elseif(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;

// access is restricted to authenticated member
elseif(($item['active'] == 'R') && Surfer::is_member())
	$permitted = TRUE;

// public access is allowed
elseif($item['active'] == 'Y')
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load the skin
load_skin('categories');

// path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'categories/' => i18n::s('Categories') );

// page title
$context['page_title'] = i18n::s('RSS feed');

// not found
if(!isset($item['id'])) {
	include '../error.php';

// permission denied
} elseif(!$permitted) {

	// give anonymous surfers a chance for HTTP authentication
	if(!Surfer::is_logged()) {
		Safe::header('WWW-Authenticate: Basic realm="'.utf8::to_iso8859($context['site_name']).'"');
		Safe::header('Status: 401 Unauthorized', TRUE, 401);
	}

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// display the category
} else {

	// get the list from the cache, if possible
	$cache_id = 'categories/feed.php?id='.$item['id'].'#channel';
	if(!$text = Cache::get($cache_id)) {

		// loads feeding parameters
		Safe::load('parameters/feeds.include.php');

		// set channel information
		$values = array();
		$values['channel'] = array();
		$values['channel']['title'] = $item['title'];
		$values['channel']['link'] = Categories::get_permalink($item);
		$values['channel']['description'] = $item['introduction'];

		// the image for this channel
		if(isset($context['powered_by_image']) && $context['powered_by_image'])
			$values['channel']['image'] = $context['url_to_home'].$context['url_to_root'].$context['powered_by_image'];

		// the list of newest pages
		// $values['items'] =& Members::list_articles_by_date_for_anchor('category:'.$item['id'], 0, 20, 'feed');
                // the list of newest subcategories
                $values['items'] = Categories::list_by_date_for_anchor('category:'.$item['id'], 0, 20, 'feed');

		// make a text
		include_once '../services/codec.php';
		include_once '../services/rss_codec.php';
		$result = rss_Codec::encode($values);
		$text = @$result[1];

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
		$file_name = utf8::to_ascii($context['site_name'].'.category.'.$item['id'].'.rss.xml');
		Safe::header('Content-Disposition: inline; filename="'.str_replace('"', '', $file_name).'"');
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
