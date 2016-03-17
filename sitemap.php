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
 * @author Bernard Paques
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
$cache_id = Cache::hash('sitemap').'.xml';

// save for 5 minutes
if(!file_exists($context['path_to_root'].$cache_id) || (filemtime($context['path_to_root'].$cache_id)+300 < time()) || (!$text = Safe::file_get_contents($context['path_to_root'].$cache_id))) {
	$text = '';

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
				.'		<loc>'.encode_link(Sections::get_permalink($item)).'</loc>'."\n"
				.'		<changefreq>weekly</changefreq>'."\n"
				.'	</url>'."\n\n";

	// the categories tree
	$text .= '	<url>'."\n"
		.'		<loc>'.$context['url_to_home'].$context['url_to_root'].'categories/</loc>'."\n"
		.'		<changefreq>weekly</changefreq>'."\n"
		.'		<priority>0.7</priority>'."\n"
		.'	</url>'."\n\n";

	// main categories
	if($items = Categories::list_by_date(0, 25, 'raw'))
		foreach($items as $id => $item)
			$text .= '	<url>'."\n"
				.'		<loc>'.encode_link(Categories::get_permalink($item)).'</loc>'."\n"
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

    // put in cache
    Safe::file_put_contents($cache_id, $text);

}

//
// transfer to the user agent
//

// handle the output correctly
render_raw('text/xml; charset='.$context['charset']);

// suggest a name on download
$file_name = utf8::to_ascii($context['site_name'].'.sitemap.xml');
if(!headers_sent())
    Safe::header('Content-Disposition: inline; filename="'.str_replace('"', '', $file_name).'"');

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
//finalize_page();

?>