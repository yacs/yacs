<?php
/**
 * the index of published articles
 *
 * Most pages of your YACS server are articles.
 * As a result, articles support a rich set of features:
 *
 * [*] The specific abstraction used for this kind of object is described in [script]articles/articles.php[/script].
 *
 * [*] Also, articles can be considered as containers of some sort for images, files, etc.
 * Check [script]articles/article.php[/script], which implements [script]shared/anchor.php[/script].
 *
 * [*] Lastly, if you are looking to extend articles to something else, you should check the overlay interface,
 * implemented in [script]overlays/overlay.php[/script], and then a live example such as
 * [script]overlays/recipe.php[/script] or [script]overlays/poll.php[/script].
 *
 * This index page lists most recent regular articles to any surfer.
 * Here, regular means that these pages have been published, and that they have not hit some deadline.
 *
 * The main menu displays the total number of articles.
 * It also has navigation links to browse past pages.
 * Commands are available to either create a new page, to review submitted articles.
 * Associates can also import some XML data, check the database, or populate saple pages.
 *
 * A list of most popular articles is displayed as a sidebar, if the total number of articles in the database is significant.
 *
 * Optionnally, categories to be displayed at this index page are listed as sidebar boxes.
 *
 * This script secretely features a link to the main RSS feeder for this site, namely:
 *
 * [code]&lt;link rel="alternate" href="http://.../feeds/rss.php" title="RSS" type="application/rss+xml" /&gt;[/code]
 *
 * Restrictions apply on this page:
 * - anonymous users can see only active pages (the 'active' field == 'Y')
 * - members can see active and restricted pages ('active field == 'Y' or 'R')
 * - associates can see all published articles
 *
 * Accept following invocations:
 * - index.php (view the 20 most recent articles)
 * - index.php/10 (view articles 200 to 220, in the past)
 * - index.php?page=4 (view articles 80 to 100, back in the past)
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../feeds/feeds.php'; // some links to newsfeeds

// which page should be displayed
if(isset($_REQUEST['page']))
	$page = $_REQUEST['page'];
elseif(isset($context['arguments'][0]))
	$page = $context['arguments'][0];
else
	$page = 1;
$page = max(1,intval($page));

// load the skin
load_skin('articles');

// page size
$items_per_page = 50;

// the title of the page
$context['page_title'] = i18n::s('All pages');

// count articles in the database
$stats = Articles::stat();
if($stats['count'])
	$context['page_menu'] += array('_count' => sprintf(i18n::ns('%d page', '%d pages', $stats['count']), $stats['count']));

// stop hackers
if(($page > 1) && (($page - 1) * $items_per_page > $stats['count'])) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

} else {

	// navigation commands for articles, if necessary
	if($stats['count'] > $items_per_page) {
		$home = 'articles/';
		if($context['with_friendly_urls'] == 'Y')
			$prefix = $home.'index.php/';
		elseif($context['with_friendly_urls'] == 'R')
			$prefix = $home;
		else
			$prefix = $home.'?page=';
		$context['page_menu'] += Skin::navigate($home, $prefix, $stats['count'], $items_per_page, $page);
	}

	// page main content
	$cache_id = 'articles/index.php#text#'.$page;
	if(!$text =& Cache::get($cache_id)) {

		// query the database and layout that stuff
		$offset = ($page - 1) * $items_per_page;
		if($text =& Articles::list_by('publication', $offset, $items_per_page)) {

			// we have an array to format
			if(is_array($text))
				$text =& Skin::build_list($text, 'decorated');

		}

		// cache this to speed subsequent queries
		Cache::put($cache_id, $text, 'articles');
	}
	$context['text'] .= $text;

}

//
// extra content
//

// add a page
if(Surfer::is_associate() || (Surfer::is_member() && (!isset($context['users_without_submission']) || ($context['users_without_submission'] != 'Y'))) )
	$context['page_tools'][] = Skin::build_link('articles/edit.php', i18n::s('Add a page'), 'basic');

// other commands
if(Surfer::is_associate()) {
	$context['page_tools'][] = Skin::build_link('articles/review.php', i18n::s('Review queue'), 'basic');
	$context['page_tools'][] = Skin::build_link('help/populate.php', i18n::s('Content Assistant'));
	$context['page_tools'][] = Skin::build_link('articles/import.php', i18n::s('Import articles'), 'basic');
	$context['page_tools'][] = Skin::build_link('articles/check.php', i18n::s('Maintenance'), 'basic');
}

// side bar with a rss feed, if this server is well populated
if($stats['count'] > $items_per_page) {
	$context['components']['channels'] = Skin::build_box(i18n::s('Information channels'), Skin::build_link(Feeds::get_url('rss'), i18n::s('Recent pages'), 'xml')
		.BR.Skin::build_link(Feeds::get_url('articles'), i18n::s('Full content'), 'xml'), 'channels');
}

// page extra information
$cache_id = 'articles/index.php#extra';
if(!$text =& Cache::get($cache_id)) {

	// side bar with the list of most popular articles, if this server is well populated
	if($stats['count'] > $items_per_page) {
		if($items =& Articles::list_by('hits', 0, COMPACT_LIST_SIZE, 'compact'))
			$text .= Skin::build_box(i18n::s('Popular'), Skin::build_list($items, 'compact'), 'boxes');
	}

	// side boxes for related categories, if any
	if($categories = Categories::list_by_date_for_display('article:index', 0, 7, 'raw')) {
		foreach($categories as $id => $attributes) {

			// link to the category page from the box title
			$label =& Skin::build_box_title(Skin::strip($attributes['title']), Categories::get_permalink($attributes), i18n::s('View the category'));

			// box content
			if($items =& Members::list_articles_by_date_for_anchor('category:'.$id, 0, COMPACT_LIST_SIZE, 'compact'))
				$text .= Skin::build_box($label, Skin::build_list($items, 'compact'), 'boxes')."\n";
		}
	}

	// cache it, whatever change, for 1 minute
	Cache::put($cache_id, $text, 'stable', 60);
}
$context['components']['boxes'] = $text;

// referrals, if any
$context['components']['referrals'] = Skin::build_referrals('articles/index.php');

// a meta link to a feeding page
$context['page_header'] .= "\n".'<link rel="alternate" href="'.$context['url_to_root'].Feeds::get_url('rss').'" title="RSS" type="application/rss+xml" />';

// render the skin
render_skin();

?>