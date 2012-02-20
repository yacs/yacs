<?php
/**
 * the index page for categories
 *
 * @todo categorize files (NickR)
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
 * @author Bernard Paques
 * @author GnapZ
 * @tester Eoin
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'categories.php';

// which page should be displayed
if(isset($_REQUEST['page']))
	$page = $_REQUEST['page'];
elseif(isset($context['arguments'][0]))
	$page = $context['arguments'][0];
else
	$page = 1;
$page = max(1,intval($page));

// load the skin
load_skin('site_taxonomy');

// the title of the page
$context['page_title'] = i18n::s('Categories');

// count categories in the database
$stats = Categories::stat_for_anchor(NULL);

// stop hackers
if(($page > 1) && (($page - 1) * CATEGORIES_PER_PAGE > $stats['count'])) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

} else {

	// page main content
	$cache_id = 'categories/index.php#text#'.$page;
	if(!$text =& Cache::get($cache_id)) {

		// do it the Yahoo! style
		include_once '../categories/layout_categories_as_yahoo.php';
		$layout = new Layout_categories_as_yahoo();

		// the list of active categories
		$offset = ($page - 1) * CATEGORIES_PER_PAGE;
		if(!$text = Categories::list_by_title_for_anchor(NULL, $offset, CATEGORIES_PER_PAGE, $layout))
			$text = '<p>'.i18n::s('No category has been created yet.').'</p>';

		// we have an array to format
		if(is_array($text))
			$text =& Skin::build_list($text, '2-columns');

		// navigation commands for categories, if necessary
		if($stats['count'] > CATEGORIES_PER_PAGE) {

			$menu = array('_count' => Skin::build_number($stats['count'], i18n::s('categories')));

			$home = 'categories/';
			if($context['with_friendly_urls'] == 'Y')
				$prefix = $home.'index.php/';
			elseif($context['with_friendly_urls'] == 'R')
				$prefix = $home;
			else
				$prefix = $home.'?page=';
			$menu = array_merge($menu, Skin::navigate($home, $prefix, $stats['count'], CATEGORIES_PER_PAGE, $page));

			// add a menu at the bottom
			$text .= Skin::build_list($menu, 'menu_bar');

		}

		// make a box
		if($text)
			$text =& Skin::build_box('', $text, 'header1', 'categories');

		// associates may list specific categories as well
		if(($page == 1) && Surfer::is_associate()) {

			// query the database and layout that stuff
			if($items = Categories::list_inactive_by_title(0,25)) {

				// we have an array to format
				if(is_array($items))
					$items = Skin::build_list($items, '2-columns');

				// displayed as another page section
				$text .= Skin::build_box(i18n::s('Special categories'), $items, 'header1', 'inactive_categories');
			}
		}

		// cache this to speed subsequent queries
		Cache::put($cache_id, $text, 'categories');
	}
	$context['text'] .= $text;

}

// page tools
if(Surfer::is_associate()) {
	$context['page_tools'][] = Skin::build_link('categories/edit.php', i18n::s('Add a category'));
	$context['page_tools'][] = Skin::build_link('help/populate.php', i18n::s('Content Assistant'));
	$context['page_tools'][] = Skin::build_link('categories/check.php', i18n::s('Maintenance'));
}

// display extra information
$cache_id = 'categories/index.php#extra';
if(!$text =& Cache::get($cache_id)) {

	// see also
	$links = array('categories/cloud.php' => i18n::s('Cloud of tags'),
		'sections/' => i18n::s('Site map'),
		'search.php' => i18n::s('Search'),
		'help/' => i18n::s('Help index'),
		'query.php' => i18n::s('Contact'));
	$text .= Skin::build_box(i18n::s('See also'), Skin::build_list($links, 'compact'), 'boxes');

	// side bar with the list of most popular articles, if this server is well populated
	if($stats['count'] > CATEGORIES_PER_PAGE) {
		if($items = Categories::list_by_hits(0, COMPACT_LIST_SIZE, 'compact')) {
			$title = i18n::s('Popular');
			$text .= Skin::build_box($title, Skin::build_list($items, 'compact'), 'boxes');
		}
	}

	// cache, whatever change, for 5 minutes
	Cache::put($cache_id, $text, 'stable', 300);
}
$context['components']['boxes'] = $text;

// referrals, if any
$context['components']['referrals'] = Skin::build_referrals('categories/index.php');

// render the skin
render_skin();

?>
