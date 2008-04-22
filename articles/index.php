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
 * [code]&lt;link rel="alternate" href="http://.../yacs/feeds/rss_2.0.php" title="RSS" type="application/rss+xml" /&gt;[/code]
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
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../categories/categories.php'; // categories displayed here
include_once '../feeds/feeds.php'; // some links to newsfeeds

// which page should be displayed
$page = 1;
if(isset($_REQUEST['page']))
	$page = $_REQUEST['page'];
elseif(isset($context['arguments'][0]))
	$page = $context['arguments'][0];
$page = strip_tags($page);

// load localized strings
i18n::bind('articles');

// load the skin
load_skin('articles');

// page size
$items_per_page = 50;

// the title of the page
$context['page_title'] = i18n::s('All pages');

// count articles in the database
$stats = Articles::stat();
if($stats['count'])
	$context['page_menu'] = array_merge($context['page_menu'], array('_count' => sprintf(i18n::ns('1 page', '%d pages', $stats['count']), $stats['count'])));

// navigation commands for articles, if necessary
if($stats['count'] > $items_per_page) {
	$home = 'articles/index.php';
	if($context['with_friendly_urls'] == 'Y')
		$prefix = $home.'/';
	elseif($context['with_friendly_urls'] == 'R')
		$prefix = $home.'/';
	else
		$prefix = $home.'?page=';
	$context['page_menu'] = array_merge($context['page_menu'], Skin::navigate($home, $prefix, $stats['count'], $items_per_page, $page));
}

// the command to post a new page
if(Surfer::is_associate()
	|| (Surfer::is_member() && (!isset($context['users_without_submission']) || ($context['users_without_submission'] != 'Y'))) ) {
	$context['page_menu'] = array_merge($context['page_menu'], array( 'articles/edit.php' => i18n::s('Add a page') ));
}

// associates can review submitted articles
if(Surfer::is_associate())
	$context['page_menu'] = array_merge($context['page_menu'], array( 'articles/review.php' => i18n::s('Review queue') ));

// associates may import some XML
if(Surfer::is_associate())
	$context['page_menu'] = array_merge($context['page_menu'], array( 'articles/import.php' => i18n::s('Import articles') ));

// associates may check the database
if(Surfer::is_associate())
	$context['page_menu'] = array_merge($context['page_menu'], array( 'articles/check.php' => i18n::s('Maintenance') ));

// page main content
$cache_id = 'articles/index.php#text#'.$page;
if(!$text =& Cache::get($cache_id)) {

	// load the layout to use
	switch($context['root_articles_layout']) {
		case 'boxesandarrows':
			include_once 'layout_articles_as_boxesandarrows.php';
			$layout =& new Layout_articles_as_boxesandarrows();
			break;
		default:
			$layout = 'full';
			break;
	}

	// query the database and layout that stuff
	$offset = ($page - 1) * $items_per_page;
	if(!$text = Articles::list_by_date($offset, $items_per_page, $layout))
		$text = '<p>'.i18n::s('Be the first one to add a page!').'</p>';

	// we have an array to format
	if(is_array($text))
		$text =& Skin::build_list($text, 'decorated');

	// cache this to speed subsequent queries
	Cache::put($cache_id, $text, 'articles');
}
$context['text'] .= $text;

// page extra information
$cache_id = 'articles/index.php#extra';
if(!$text =& Cache::get($cache_id)) {

	// side bar with the list of most popular articles, if this server is well populated
	if($stats['count'] > $items_per_page) {
		if($items = Articles::list_by_hits(0, COMPACT_LIST_SIZE, 'compact'))
			$text .= Skin::build_box(i18n::s('Popular'), Skin::build_list($items, 'compact'), 'extra');
	}

	// side bar with a rss feed, if this server is well populated
	if($stats['count'] > $items_per_page) {
		$text .= Skin::build_box(i18n::s('Stay tuned'), Skin::build_link(Feeds::get_url('rss'), i18n::s('recent pages'), 'xml')
			.BR.Skin::build_link(Feeds::get_url('articles'), i18n::s('full content'), 'xml'));
	}

	// side boxes for related categories, if any
	if($categories = Categories::list_by_date_for_display('article:index', 0, 7, 'raw')) {
		foreach($categories as $id => $attributes) {

			// link to the category page from the box title
			$label =& Skin::build_box_title(Skin::strip($attributes['title']), Categories::get_url($attributes['id'], 'view', $attributes['title']), i18n::s('View the category'));

			// box content
			if($items = Members::list_articles_by_date_for_anchor('category:'.$id, 0, COMPACT_LIST_SIZE, 'compact'))
				$text .= Skin::build_box($label, Skin::build_list($items, 'compact'), 'navigation')."\n";
		}
	}

	// cache it, whatever change, for 1 minute
	Cache::put($cache_id, $text, 'stable', 60);
}
$context['extra'] .= $text;

// referrals, if any
$context['extra'] .= Skin::build_referrals('articles/index.php');

// a meta link to a feeding page
$context['page_header'] .= "\n".'<link rel="alternate" href="'.$context['url_to_root'].Feeds::get_url('rss').'" title="RSS" type="application/rss+xml"'.EOT;

// render the skin
render_skin();

?>