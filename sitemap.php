<?php
/**
 * help Google to index this site
 *
 * To speed the indexation of your site, visit Google, and submit
 * result of this script.
 *
 * @link https://www.google.com/webmasters/sitemaps/docs/en/submit_mobile.html
 *
 * This script lists pages to feed Google crawler:
 * - the front page
 * - the site map
 * - top root sections
 * - the categories tree
 * - top root categories
 * - the member index
 * - the OMPL feed
 *
 * This script is a straightforward implementation of the XML Sitemap protocol.
 *
 * @link https://www.google.com/webmasters/sitemaps/docs/en/protocol.html Sitemap Protocol Contents
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
include_once 'shared/global.php';

// required by Google
$context['charset'] = 'utf-8';

// load localized strings
i18n::bind('root');

// load a skin engine
load_skin('sitemap');

// get the list from the cache, if possible
$cache_id = 'sitemap.php#content';
if(!$text =& Cache::get($cache_id)) {

	// the preamble
	$text = '<?xml version="1.0" encoding="'.$context['charset'].'"?>'."\n"
		.'<urlset xmlns="http://www.google.com/schemas/sitemap/0.84">'."\n";

	// the front page
	$text .= '	<url>'."\n"
		.'		<loc>'.$context['url_to_home'].$context['url_to_root'].'</loc>'."\n"
		.'		<changefreq>weekly</changefreq>'."\n"
		.'		<priority>1.0</priority>'."\n"
		.'	</url>'."\n\n";

	// the site map
	$text .= '	<url>'."\n"
		.'		<loc>'.$context['url_to_home'].$context['url_to_root'].'sections/</loc>'."\n"
		.'		<changefreq>weekly</changefreq>'."\n"
		.'		<priority>1.0</priority>'."\n"
		.'	</url>'."\n\n";

	// main sections
	if($items = Sections::list_by_title_for_anchor(NULL, 0, 25, 'raw'))
		foreach($items as $id => $item)
			$text .= '	<url>'."\n"
				.'		<loc>'.$context['url_to_home'].$context['url_to_root'].Sections::get_url($id, 'view', $item['title']).'</loc>'."\n"
				.'		<changefreq>weekly</changefreq>'."\n"
				.'	</url>'."\n\n";

	// the categories tree
	$text .= '	<url>'."\n"
		.'		<loc>'.$context['url_to_home'].$context['url_to_root'].'categories/</loc>'."\n"
		.'		<changefreq>weekly</changefreq>'."\n"
		.'		<priority>0.7</priority>'."\n"
		.'	</url>'."\n\n";

	// main categories
	include_once 'categories/categories.php';
	if($items = Categories::list_by_date(0, 25, 'raw'))
		foreach($items as $id => $item)
			$text .= '	<url>'."\n"
				.'		<loc>'.$context['url_to_home'].$context['url_to_root'].Categories::get_url($item['id'], 'view', $item['title']).'</loc>'."\n"
				.'		<changefreq>weekly</changefreq>'."\n"
				.'	</url>'."\n\n";

	// members
	$text .= '	<url>'."\n"
		.'		<loc>'.$context['url_to_home'].$context['url_to_root'].'users/</loc>'."\n"
		.'		<changefreq>weekly</changefreq>'."\n"
		.'		<priority>0.7</priority>'."\n"
		.'	</url>'."\n\n";

	// the OPML feed
	$text .= '	<url>'."\n"
		.'		<loc>'.$context['url_to_home'].$context['url_to_root'].'feeds/describe.php</loc>'."\n"
		.'		<changefreq>weekly</changefreq>'."\n"
		.'	</url>'."\n\n";

	// the postamble
	$text .= '</urlset>'."\n";

	// save in cache for the next request
	Cache::put($cache_id, $text, 'articles');
}

//
// transfer to the user agent
//

// handle the output correctly
render_raw('text/xml; charset='.$context['charset']);

// suggest a name on download
$file_name = utf8::to_ascii($context['site_name'].'.sitemap.xml');
if(!headers_sent())
	Safe::header('Content-Disposition: inline; filename="'.$file_name.'"');

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
//finalize_page();

?>