<?php
/**
 * list articles in the RSS 2.0 format for some user
 *
 * List up to ten fresh pages for one user only.
 * At the moment, this script gives the list of the ten newest published articles,
 * with following information:
 * - title - the title of the article
 * - url - the absolute url to fetch the article
 * - time - the date and time of article last modification
 * - author - the last contributor to the article
 * - section - the label of the section from where the article is originated
 * - image - the absolute url to fetch a related image, if any
 *
 * Restrictions apply on this page:
 * - associates are allowed to move forward
 * - access is restricted ('active' field == 'R'), but the surfer is an authenticated member
 * - public access is allowed ('active' field == 'Y')
 * - permission denied is the default
 *
 * Accept following invocations:
 * - feed.php (show my own feed if I am logged)
 * - feed.php/12
 * - feed.php?id=12
 *
 * @link http://blogs.law.harvard.edu/tech/rss RSS 2.0 Specification
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Pat
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// check network credentials, if any -- used by winamp and other media players
if($user = Users::authenticate())
	Surfer::empower($user['capability']);

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
elseif(Surfer::is_logged())
	$id = Surfer::get_id();
$id = strip_tags($id);

// get the item from the database
$item = Users::get($id);

// associates can do what they want
if(Surfer::is_associate())
	$permitted = TRUE;

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
load_skin('users');

// path to this page
$context['path_bar'] = array( 'users/' => i18n::s('People') );

// page title
$context['page_title'] = i18n::s('RSS feed');

// not found
if(!isset($item['id'])) {
	include '../error.php';

// permission denied
} elseif(!$permitted) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// send the list of recent contributions by this user
} else {

	// get the list from the cache, if possible
	$cache_id = 'users/feed.php?id='.$item['id'].'#channel';
	if(!$text = Cache::get($cache_id)) {

		// loads feeding parameters
		Safe::load('parameters/feeds.include.php');

		// structured data
		$values = array();
		$values['channel'] = array();

		// set channel information
		$values['channel']['title'] = $item['full_name'] ? $item['full_name'] : $item['nick_name'];
		$values['channel']['link'] = $context['url_to_home'].$context['url_to_root'].Users::get_permalink($item);
		$values['channel']['description'] = $item['introduction'];

		// the image for this channel
		if(isset($context['powered_by_image']) && $context['powered_by_image'])
			$values['channel']['image'] = $context['url_to_home'].$context['url_to_root'].$context['powered_by_image'];

		// the list of newest pages
		$values['items'] = (array)Articles::list_for_author_by('publication', $item['id'], 0, 50, 'feed');

		// make a text
		include_once '../services/codec.php';
		include_once '../services/rss_codec.php';
		$result = rss_Codec::encode($values);
		$status = @$result[0];
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
		$file_name = utf8::to_ascii($context['site_name'].'.section.'.$item['id'].'.rss.xml');
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
