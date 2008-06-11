<?php
/**
 * the index page for links
 *
 * For a comprehensive description of links, you should check the database abstraction script
 * at [script]links/links.php[/script].
 *
 * This page list recent links as blogmarks.
 *
 * Of course links are filtered in the displayed list depending on restriction level and surfer ability.
 * Moreover, non-associates will only see links attached to public published non-expired articles.
 *
 * The main menu has navigation links to browse links by page, for sites that have numerous links.
 *
 * Links are displayed using the blogmarks layout.
 *
 * Accept following invocations:
 * - index.php (view the 20 top links)
 * - index.php/2 (view links 41 to 60)
 * - index.php?page=2 (view links 41 to 60)
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'links.php';

// which page should be displayed
$page = 1;
if(isset($_REQUEST['page']))
	$page = $_REQUEST['page'];
elseif(isset($context['arguments'][0]))
	$page = $context['arguments'][0];
$page = strip_tags($page);

// load the skin
load_skin('links');

// page size
$items_per_page = 50;

// the title of the page
$context['page_title'] = i18n::s('Links');

// count links in the database
$stats = Links::stat();
if($stats['count'])
	$context['page_menu'] = array_merge($context['page_menu'], array('_count' => sprintf(i18n::ns('1 link', '%d links', $stats['count']), $stats['count'])));

// navigation commands for links, if necessary
if($stats['count'] > $items_per_page) {
	$home = 'links/index.php';
	if($context['with_friendly_urls'] == 'Y')
		$prefix = $home.'/';
	elseif($context['with_friendly_urls'] == 'R')
		$prefix = $home.'/';
	else
		$prefix = $home.'?page=';
	$context['page_menu'] = array_merge($context['page_menu'], Skin::navigate($home, $prefix, $stats['count'], $items_per_page, $page));
}

// page main content
$cache_id = 'links/index.php#text#'.$page;
if(!$text =& Cache::get($cache_id)) {

	// load the layout to use
	switch($context['root_articles_layout']) {
		case 'daily':
			include 'layout_links_as_daily.php';
			$layout =& new Layout_links_as_daily();
			break;
		default:
			$layout = 'full';
			break;
	}

	// query the database and layout that stuff
	$offset = ($page - 1) * $items_per_page;
	if(!$text = Links::list_by_date($offset, $items_per_page, $layout))
		$text = '<p>'.i18n::s('No link has been recorded yet.').'</p>';

	// we have an array to format
	if(is_array($text))
		$text =& Skin::build_list($text, 'decorated');

	// beautify
	$text = Codes::beautify($text);

	// cache this to speed subsequent queries
	Cache::put($cache_id, $text, 'links');
}
$context['text'] .= $text;

// page tools
if(Surfer::is_associate()) {
	if(Sections::lookup('clicks'))
		$context['page_tools'][] = Skin::build_link(Sections::get_url('clicks'), i18n::s('Detected clicks'), 'basic');
	$context['page_tools'][] = Skin::build_link('links/check.php', i18n::s('Maintenance'), 'basic');
}

// page extra content
$cache_id = 'links/index.php#extra';
if(!$text =& Cache::get($cache_id)) {

	// side bar with the list of most popular links
	if($items = Links::list_by_hits(0, COMPACT_LIST_SIZE, 'compact'))
		$text .= Skin::build_box(i18n::s('Popular'), Skin::build_list($items, 'compact'), 'extra');

	// side boxes for related categories, if any
	include_once '../categories/categories.php';
	if($categories = Categories::list_by_date_for_display('link:index', 0, 7, 'raw')) {
		foreach($categories as $id => $attributes) {

			// link to the category page from the box title
			$label =& Skin::build_box_title(Skin::strip($attributes['title']), Categories::get_url($attributes['id'], 'view', $attributes['title']), i18n::s('View the category'));

			// box content
			if($items = Members::list_articles_by_date_for_anchor('category:'.$id, 0, COMPACT_LIST_SIZE, 'compact'))
				$text .= Skin::build_box($label, Skin::build_list($items, 'compact'), 'navigation')."\n";
		}
	}

	// cache, whatever change, for 5 minutes
	Cache::put($cache_id, $text, 'stable', 300);
}
$context['extra'] .= $text;

// referrals, if any
$context['extra'] .= Skin::build_referrals('links/index.php');

// render the skin
render_skin();

?>