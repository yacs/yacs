<?php
/**
 * the index page for categories
 *
 * @todo categorize images (NickR)
 * @todo categorize files (NickR)
 * @todo categorize users
 *
 * Any article can be associated to a variable number of categories.
 *
 * For a more comprehensive description of categories, you should check the database abstraction script
 * at [script]categories/categories.php[/script].
 *
 * This page list regular categories to any surfer.
 * Here, regular means that these categories are not hidden and that they have not hit some deadline.
 *
 * For associates, this index has a second part to list hidden or dead categories.
 * Hidden categories are used to locate special items such as featured pages.
 *
 * Following restrictions apply:
 * - anonymous users can see only active categories (the 'active' field == 'Y')
 * - members can see active and restricted categories ('active field == 'Y' or 'R')
 * - associates can see all categories
 *
 * The main menu for this page has a command to create a new category, if necessary.
 * It also has navigation links to browse categories by page, for sites that have numerous categories.
 *
 * Categories are displayed using the default 2-column layout.
 *
 * Because this script is the top of the categories tree, it has a special extra box with helpful links.
 * If the database has more categories than what can be displayed on a single page,
 * another extra box will be displayed to list popular categories.
 *
 * Accept following invocations:
 * - index.php (view the 20 top categories)
 * - index.php/2 (view categories 41 to 60)
 * - index.php?page=2 (view categories 41 to 60)
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @tester Eoin
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'categories.php';

// which page should be displayed
$page = 1;
if(isset($_REQUEST['page']))
	$page = $_REQUEST['page'];
elseif(isset($context['arguments'][0]))
	$page = $context['arguments'][0];
$page = strip_tags($page);

// load localized strings
i18n::bind('categories');

// load the skin
load_skin('site_taxonomy');

// the title of the page
$context['page_title'] = i18n::s('The categories tree');

// count categories in the database
$stats = Categories::stat_for_anchor(NULL);
if($stats['count'] > CATEGORIES_PER_PAGE) {

	// display the total count of categories
	$context['page_menu'] = array_merge($context['page_menu'], array('_count' => sprintf(i18n::s('%d&nbsp;categories'), $stats['count'])));

	// navigation commands for categories, if necessary
	$home = 'categories/index.php';
	if($context['with_friendly_urls'] == 'Y')
		$prefix = $home.'/';
	elseif($context['with_friendly_urls'] == 'R')
		$prefix = $home.'/';
	else
		$prefix = $home.'?page=';
	$context['page_menu'] = array_merge($context['page_menu'], Skin::navigate($home, $prefix, $stats['count'], CATEGORIES_PER_PAGE, $page));
}

// link to site cloud
if($stats['count'])
	$context['page_menu'] = array_merge($context['page_menu'], array( 'categories/cloud.php' => i18n::s('Cloud of tags') ));

// commands for associates
if(Surfer::is_associate())
	$context['page_menu'] = array_merge($context['page_menu'], array( 'categories/edit.php' => i18n::s('Add a category'),
		'categories/check.php' => i18n::s('Maintenance') ));

// page main content
$cache_id = 'categories/index.php#text#'.$page;
if(!$text =& Cache::get($cache_id)) {

	// do it the Yahoo! style
	include_once '../categories/layout_categories_as_yahoo.php';
	$layout =& new Layout_categories_as_yahoo();

	// the list of active categories
	$offset = ($page - 1) * CATEGORIES_PER_PAGE;
	if(!$text = Categories::list_by_title_for_anchor(NULL, $offset, CATEGORIES_PER_PAGE, $layout))
		$text = '<p>'.i18n::s('No category has been created yet.').'</p>';

	// we have an array to format
	if(is_array($text))
		$text =& Skin::build_list($text, '2-columns');

	// make a box
	if($text)
		$text =& Skin::build_box('', $text, 'section', 'categories');

	// associates may list specific categories as well
	if(($page == 1) && Surfer::is_associate()) {

		// query the database and layout that stuff
		if($items = Categories::list_inactive_by_title(0,25)) {

			// we have an array to format
			if(is_array($items))
				$items = Skin::build_list($items, '2-columns');

			// displayed as another page section
			$text .= Skin::build_box(i18n::s('Special categories'), $items, 'section', 'inactive_categories');
		}
	}

	// cache this to speed subsequent queries
	Cache::put($cache_id, $text, 'categories');
}
$context['text'] .= $text;

// display extra information
$cache_id = 'categories/index.php#extra';
if(!$text =& Cache::get($cache_id)) {

	// add an extra box with helpful links
	$links = array('sections/' => i18n::s('Site map'),
		'search.php' => i18n::s('Search on keyword'),
		'help.php' => i18n::s('Help index'),
		'query.php' => i18n::s('Contact the webmaster'));
	$text .= Skin::build_box(i18n::s('See also'), Skin::build_list($links, 'compact'), 'extra')."\n";

	// side bar with the list of most popular articles, if this server is well populated
	if($stats['count'] > CATEGORIES_PER_PAGE) {
		if($items = Categories::list_by_hits(0, COMPACT_LIST_SIZE, 'compact')) {
			$title = i18n::s('Popular');
			$text .= Skin::build_box($title, Skin::build_list($items, 'compact'), 'extra');
		}
	}

	// cache, whatever change, for 5 minutes
	Cache::put($cache_id, $text, 'stable', 300);
}
$context['extra'] .= $text;

// referrals, if any
$context['extra'] .= Skin::build_referrals('categories/index.php');

// render the skin
render_skin();

?>