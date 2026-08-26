<?php
/**
 * help search engines to index this site
 *
 * To speed the indexation of your site, submit the address of this script
 * to search engines, or reference it from [code]robots.txt[/code]:
 * [code]Sitemap: http://www.example.com/sitemap.php[/code]
 *
 * This script lists pages to feed crawlers:
 * - the front page
 * - the site map
 * - public sections that are part of index maps
 * - the categories tree
 * - public categories
 * - the member index
 * - the OPML feed
 * - published pages of public sections
 *
 * Only public content is advertised here. Because the outcome is cached and
 * served to every visitor, the queries below explicitly select active items,
 * and do not use the regular listing functions of the library, which extend
 * their scope to restricted and hidden items on behalf of the current surfer.
 *
 * Where the number of URLs exceeds SITEMAP_URLS_PER_PAGE, this script splits
 * the list over several pages, and turns the entry point into a sitemap index.
 *
 * This script is a straightforward implementation of the XML Sitemap protocol.
 *
 * @link https://www.sitemaps.org/protocol.html Sitemap XML format
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once 'shared/global.php';

// required by the protocol
$context['charset'] = 'utf-8';

// load localized strings
i18n::bind('root');

// load a skin engine
load_skin('sitemap');

// maximum number of URLs put in one single page -- the protocol allows up to 50000
if(!defined('SITEMAP_URLS_PER_PAGE'))
	define('SITEMAP_URLS_PER_PAGE', 10000);

// how long a generated page is kept, in seconds
if(!defined('SITEMAP_CACHE_DURATION'))
	define('SITEMAP_CACHE_DURATION', 300);

/**
 * turn a database date to the format expected by the protocol
 *
 * @param string a GMT date, as saved in the database
 * @return string a W3C date, or NULL
 */
function sitemap_date($date) {

	// no date to advertise
	if(!$date || ($date <= NULL_DATE))
		return NULL;

	// dates are kept as GMT in the database
	if(($stamp = strtotime($date.' UTC')) === FALSE)
		return NULL;

	return gmdate('Y-m-d\TH:i:s\Z', $stamp);
}

/**
 * build one entry of the list
 *
 * @param string the absolute link, already encoded
 * @param string the date of last modification, if any
 * @param string the change frequency, if any
 * @param string the priority of this page, if any
 * @return string a XML fragment
 */
function sitemap_url($location, $modification=NULL, $frequency=NULL, $priority=NULL) {

	$text = '	<url>'."\n"
		.'		<loc>'.$location.'</loc>'."\n";

	if($modification)
		$text .= '		<lastmod>'.$modification.'</lastmod>'."\n";

	if($frequency)
		$text .= '		<changefreq>'.$frequency.'</changefreq>'."\n";

	if($priority)
		$text .= '		<priority>'.$priority.'</priority>'."\n";

	$text .= '	</url>'."\n\n";

	return $text;
}

/**
 * list public sections that are part of index maps
 *
 * Since the index_map attribute is not cascaded to sub-sections, sections that
 * descend from a section kept out of index maps are pruned as well.
 *
 * @return array of items, indexed by section id
 */
function sitemap_sections() {
	global $context;

	// only active sections that are listed in index maps
	$query = "SELECT sections.id, sections.nick_name, sections.title, sections.anchor, sections.edit_date"
		." FROM ".SQL::table_name('sections')." AS sections"
		." WHERE (sections.active='Y')"
		."	AND (sections.index_map='Y')"
		."	AND ((sections.activation_date is NULL) OR (sections.activation_date <= '".$context['now']."'))"
		."	AND ((sections.expiry_date is NULL) OR (sections.expiry_date <= '".NULL_DATE."')"
		."		OR (sections.expiry_date > '".$context['now']."'))"
		." ORDER BY sections.id";

	$items = array();
	if($result = SQL::query($query))
		while($row = SQL::fetch($result))
			$items[ $row['id'] ] = $row;

	// prune orphans, until the set is stable
	$pruning = TRUE;
	while($pruning) {
		$pruning = FALSE;

		foreach($items as $id => $item) {

			// a top-level section, or a section anchored to something else
			if(!isset($item['anchor']) || strncmp($item['anchor'], 'section:', 8))
				continue;

			// the parent section is not advertised
			if(!isset($items[ intval(substr($item['anchor'], 8)) ])) {
				unset($items[$id]);
				$pruning = TRUE;
			}
		}
	}

	return $items;
}

/**
 * list public categories
 *
 * @return array of items, indexed by category id
 */
function sitemap_categories() {
	global $context;

	$query = "SELECT categories.id, categories.nick_name, categories.title, categories.anchor, categories.edit_date"
		." FROM ".SQL::table_name('categories')." AS categories"
		." WHERE (categories.active='Y')"
		."	AND ((categories.expiry_date is NULL) OR (categories.expiry_date <= '".NULL_DATE."')"
		."		OR (categories.expiry_date > '".$context['now']."'))"
		." ORDER BY categories.id";

	$items = array();
	if($result = SQL::query($query))
		while($row = SQL::fetch($result))
			$items[ $row['id'] ] = $row;

	return $items;
}

//
// build the list of URLs to advertise
//

// which page has been requested, if any
$page = 0;
if(isset($_REQUEST['page']))
	$page = max(0, intval($_REQUEST['page']));

// the entry point drives the freshness of the whole set
$cache_id = Cache::hash('sitemap').'.xml';
$cache_path = $context['path_to_root'].$cache_id;

// regenerate every page of the set at once
if(!file_exists($cache_path) || (filemtime($cache_path)+SITEMAP_CACHE_DURATION < time())) {

	// the address of this site
	$prefix = $context['url_to_home'].$context['url_to_root'];

	$urls = array();

	// the front page
	$urls[] = sitemap_url($prefix, NULL, 'weekly', '1.0');

	// the site map
	$urls[] = sitemap_url($prefix.'sections/', NULL, 'weekly', '1.0');

	// public sections
	$sections = sitemap_sections();
	foreach($sections as $id => $item)
		$urls[] = sitemap_url(encode_link(Sections::get_permalink($item)), sitemap_date($item['edit_date']));

	// the categories tree
	$urls[] = sitemap_url($prefix.'categories/', NULL, 'weekly', '0.7');

	// public categories
	foreach(sitemap_categories() as $id => $item)
		$urls[] = sitemap_url(encode_link(Categories::get_permalink($item)), sitemap_date($item['edit_date']));

	// members
	$urls[] = sitemap_url($prefix.'users/', NULL, 'weekly', '0.7');

	// the OPML feed
	$urls[] = sitemap_url($prefix.'feeds/describe.php', NULL, 'weekly');

	// published pages, in sections that are advertised above
	if($sections) {
		$query = "SELECT articles.id, articles.nick_name, articles.title, articles.anchor, articles.edit_date"
			." FROM ".SQL::table_name('articles')." AS articles"
			." WHERE (articles.active='Y')"
			."	AND (articles.anchor IN ('section:".join("', 'section:", array_keys($sections))."'))"
			."	AND NOT ((articles.publish_date is NULL) OR (articles.publish_date <= '".NULL_DATE."'))"
			."	AND (articles.publish_date < '".$context['now']."')"
			."	AND ((articles.expiry_date is NULL) OR (articles.expiry_date <= '".NULL_DATE."')"
			."		OR (articles.expiry_date > '".$context['now']."'))"
			." ORDER BY articles.id";

		if($result = SQL::query($query))
			while($row = SQL::fetch($result))
				$urls[] = sitemap_url(encode_link(Articles::get_permalink($row)), sitemap_date($row['edit_date']));
	}

	//
	// put the whole set in cache
	//

	$preamble = '<?xml version="1.0" encoding="'.$context['charset'].'"?>'."\n";
	$stamp = gmdate('Y-m-d\TH:i:s\Z');

	// everything fits in one single page
	if(count($urls) <= SITEMAP_URLS_PER_PAGE) {
		$pages = 0;

		Safe::file_put_contents($cache_id, $preamble
			.'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
			.join('', $urls)
			.'</urlset>'."\n");

	// split the list, and turn the entry point into a sitemap index
	} else {
		$chunks = array_chunk($urls, SITEMAP_URLS_PER_PAGE);
		$pages = count($chunks);

		$index = $preamble.'<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

		foreach($chunks as $offset => $chunk) {
			$index .= '	<sitemap>'."\n"
				.'		<loc>'.$prefix.'sitemap.php?page='.($offset+1).'</loc>'."\n"
				.'		<lastmod>'.$stamp.'</lastmod>'."\n"
				.'	</sitemap>'."\n\n";

			Safe::file_put_contents(Cache::hash('sitemap-'.($offset+1)).'.xml', $preamble
				.'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
				.join('', $chunk)
				.'</urlset>'."\n");
		}

		$index .= '</sitemapindex>'."\n";

		Safe::file_put_contents($cache_id, $index);
	}

	// drop pages left over from a previous, longer, set
	$leftovers = Safe::glob($context['path_to_root'].Cache::hash('sitemap-').'*.xml');
	if(is_array($leftovers))
		foreach($leftovers as $leftover)
			if(intval(preg_replace('/^\D+/', '', basename($leftover))) > $pages)
				Safe::unlink($leftover);

}

//
// serve the requested page
//

// one page of the set
if($page)
	$cache_id = Cache::hash('sitemap-'.$page).'.xml';

// this page does not exist
if(!$text = Safe::file_get_contents($context['path_to_root'].$cache_id)) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	$text = '<?xml version="1.0" encoding="'.$context['charset'].'"?>'."\n"
		.'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
		.'</urlset>'."\n";
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

// let the user agent cache this page as long as we do
http::expire(SITEMAP_CACHE_DURATION);

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
