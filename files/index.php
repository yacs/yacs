<?php
/**
 * the index page for files
 *
 * @todo add a bulk.php to upload files as a table (Justin)
 *
 * For a comprehensive description of files, you should check the database abstraction script
 * at [script]files/files.php[/script].
 *
 * This page list recent files to any surfer.
 * Following restrictions apply:
 * - anonymous users can see only active files (the 'active' field == 'Y')
 * - members can see active and restricted files ('active field == 'Y' or 'R')
 * - associates can see all files
 *
 * Of course files are filtered in the displayed list depending on restriction level and surfer ability.
 * Moreover, non-associates will only see files attached to public published non-expired articles.
 *
 * The main menu has navigation links to browse files by page, for sites that have numerous files.
 *
 * Files are displayed using the decorated layout.
 *
 * The extra panel has following components:
 * - A link to the rss feed for public files, as an extra box
 * - The list of most popular files, if any, as an extra box
 * - Top popular referrals, if any
 *
 * Accept following invocations:
 * - index.php (view the 20 top files)
 * - index.php/2 (view files 41 to 60)
 * - index.php?page=2 (view files 41 to 60)
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../feeds/feeds.php'; // podcast feed
include_once 'files.php';

// which page should be displayed
$page = 1;
if(isset($_REQUEST['page']))
	$page = $_REQUEST['page'];
elseif(isset($context['arguments'][0]))
	$page = $context['arguments'][0];
$page = strip_tags($page);

// load the skin
load_skin('files');

// page size
$items_per_page = 50;

// the title of the page
$context['page_title'] = i18n::s('Files');

// count files in the database
$stats = Files::stat();
if($stats['count'])
	$context['page_menu'] = array_merge($context['page_menu'], array('_count' => sprintf(i18n::ns('1 file', '%d files', $stats['count']), $stats['count'])));

// navigation commands for files, if necessary
if($stats['count'] > $items_per_page) {
	$home = 'files/index.php';
	if($context['with_friendly_urls'] == 'Y')
		$prefix = $home.'/';
	elseif($context['with_friendly_urls'] == 'R')
		$prefix = $home.'/';
	else
		$prefix = $home.'?page=';
	$context['page_menu'] = array_merge($context['page_menu'], Skin::navigate($home, $prefix, $stats['count'], $items_per_page, $page));
}

// command to review files
if(Surfer::is_member())
	$context['page_menu'] = array_merge($context['page_menu'], array( 'files/review.php' => i18n::s('Review files') ));

// command to check the database
if(Surfer::is_associate())
	$context['page_menu'] = array_merge($context['page_menu'], array( 'files/check.php' => i18n::s('Maintenance') ));

// configuration panel
if(Surfer::is_associate())
	$context['page_menu'] = array_merge($context['page_menu'], array( 'files/configure.php' => i18n::s('Configure') ));

// page main content
$cache_id = 'files/index.php#text#'.$page;
if(!$text =& Cache::get($cache_id)) {

	// the list of files
	$offset = ($page - 1) * $items_per_page;
	if(!$text = Files::list_by_date($offset, $items_per_page, 'full'))
		$text = '<p>'.i18n::s('No file has been uploaded yet.').'</p>';

	// we have an array to format
	if(is_array($text))
		$text = Skin::build_list($text, 'decorated');

	// cache this to speed subsequent queries
	Cache::put($cache_id, $text, 'files');
}
$context['text'] .= $text;

// page extra content
$cache_id = 'files/index.php#extra';
if(!$text =& Cache::get($cache_id)) {

	// get news from rss
	$title = i18n::s('Podcast');
	$label = sprintf(i18n::s('You can list new public files through RSS by going %s'), Skin::build_link(Feeds::get_url('files'), 'here', 'xml'));
	$text .= Skin::build_box($title, $label, 'extra');

	// side bar with the list of most popular files
	if($items = Files::list_by_hits(0, COMPACT_LIST_SIZE, 'compact')) {
		$text .= Skin::build_box(i18n::s('Popular'), Skin::build_list($items, 'compact'), 'extra');
	}

	// side boxes for related categories, if any
	include_once '../categories/categories.php';
	if($categories = Categories::list_by_date_for_display('file:index', 0, 7, 'raw')) {
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
$context['extra'] .= Skin::build_referrals('files/index.php');

// render the skin
render_skin();

?>