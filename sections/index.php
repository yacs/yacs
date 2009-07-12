<?php
/**
 * the index page for sections
 *
 * @todo allow for a freemind version
 *
 * At any YACS system, each and every page is anchored to one (yes, only one) section.
 * You can view sections as the top-level containers of information published at your site.
 *
 * Compared to categories, sections feature:
 *
 * [*] access rules - you can restrict access only to some sections to members and/or to associates
 *
 * [*] overlay extension - to create a cookbook, create a section and add the 'recipe' overlay
 *
 * [*] diverging rendering - apply any skin to any section
 *
 * For a more comprehensive description of sections, you should check the database abstraction script
 * at [script]sections/sections.php[/script].
 *
 * This page list regular sections to any surfer.
 * Here, regular means that these sections are not hidden and that they have not hit some deadline.
 *
 * For associates, this index has a second part to list hidden or dead sections.
 * Hidden sections are used to store special pages such as menus, covers, navigation boxes or extra boxes.
 *
 * Following restrictions apply:
 * - anonymous users can see only active sections (the 'active' field == 'Y')
 * - members can see active and restricted sections ('active field == 'Y' or 'R')
 * - associates can see all sections
 *
 * The main menu for this page has a command to create a new section, if necessary.
 * It also has navigation links to browse sections by page, for sites that have numerous sections.
 *
 * Sections are displayed using the default 2-column layout.
 * This script also supports the 'boxesandarrows' layout, if this has been configured for the front page at [link]configure.php[/link].
 *
 * @see configure.php
 *
 * Because this script is also the site map, it has an extra box with helpful links, and another extra box to list monthly publications.
 * These shortcuts will help surfers to locate some page rapidly.
 *
 * The prefix hook is used to invoke any software extension bound as follows:
 * - id: 'sections/index.php#prefix'
 * - type: 'include'
 * - parameters: none
 * Use this hook to include any text right before the main content.
 *
 * The suffix hook is used to invoke any software extension bound as follows:
 * - id: 'sections/index.php#suffix'
 * - type: 'include'
 * - parameters: none
 * Use this hook to include any text right after the main content.
 *
 * Accept following invocations:
 * - index.php (view the 20 top sections)
 * - index.php/2 (view sections 41 to 60)
 * - index.php?page=2 (view sections 41 to 60)
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Eoin
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// which page should be displayed
if(isset($_REQUEST['page']))
	$page = $_REQUEST['page'];
elseif(isset($context['arguments'][0]))
	$page = $context['arguments'][0];
else
	$page = 1;
$page = max(1,intval($page));

// sanity check
if($page < 1)
	$page = 1;

// load the skin
load_skin('site_map');

// page size
$items_per_page = 50;

// the title of the page
$context['page_title'] = i18n::s('Site map');

// count public root sections in the database
$count = Sections::count_for_anchor(NULL);

// a meta link to our blogging interface
$context['page_header'] .= "\n".'<link rel="EditURI" href="'.$context['url_to_home'].$context['url_to_root'].'services/describe.php" title="RSD" type="application/rsd+xml" />';

// the prefix hook for the site map page
if(is_callable(array('Hooks', 'include_scripts')))
	$context['text'] .= Hooks::include_scripts('sections/index.php#prefix');

// stop hackers
if($page > 10) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

} else {

	// page main content
	$cache_id = 'sections/index.php#text#'.$page;
	if(!$text =& Cache::get($cache_id)) {
	
		// load the layout to use
		switch($context['root_articles_layout']) {
			case 'boxesandarrows':
				include_once 'layout_sections_as_boxesandarrows.php';
				$layout =& new Layout_sections_as_boxesandarrows();
				break;
			default:
				include_once 'layout_sections_as_yahoo.php';
				$layout =& new Layout_sections_as_yahoo();
				$layout->set_variant(20); // show more elements at the site map
				break;
		}
	
		// the list of active sections
		$offset = ($page - 1) * $items_per_page;
		if(!$items =& Sections::list_by_title_for_anchor(NULL, $offset, $items_per_page, $layout))
			$items = '<p>'.i18n::s('No regular section has been created yet.').'</p>';
	
		// we have an array to format
		if(is_array($items))
			$items =& Skin::build_list($items, '2-columns');
	
		// navigation commands for sections, if necessary
		if($count > 5) {
		
			$menu = array('_count' => Skin::build_number($count, i18n::s('sections')));
		
			$home = 'sections/';
			if($context['with_friendly_urls'] == 'Y')
				$prefix = $home.'index.php/';
			elseif($context['with_friendly_urls'] == 'R')
				$prefix = $home;
			else
				$prefix = $home.'?page=';
			$menu = array_merge($menu, Skin::navigate($home, $prefix, $count, $items_per_page, $page));
			
			// add a menu at the bottom
			$text .= Skin::build_list($menu, 'menu_bar');
	
		}

		// make a box
		if($items)
			$text .= Skin::build_box('', $items, 'header1', 'sections');
	
		// associates may list specific sections as well
		if(($page == 1) && Surfer::is_associate()) {
	
			// load the layout to use
			switch($context['root_articles_layout']) {
				case 'boxesandarrows':
					include_once 'layout_sections_as_boxesandarrows.php';
					$layout =& new Layout_sections_as_boxesandarrows();
					break;
				default:
					include_once 'layout_sections_as_yahoo.php';
					$layout =& new Layout_sections_as_yahoo();
					$layout->set_variant(20); // show more elements at the site map
					break;
			}
	
			// query the database and layout that stuff
			if($items = Sections::list_inactive_by_title_for_anchor(NULL, 0, 50, $layout)) {
	
				// we have an array to format
				if(is_array($items))
					$items = Skin::build_list($items, '2-columns');
	
				// displayed as another page section
				$text .= Skin::build_box(i18n::s('Other sections'), $items, 'header1', 'other_sections');
	
			}
		}
	
		// cache this to speed subsequent queries
		Cache::put($cache_id, $text, 'sections');
	}
	$context['text'] .= $text;

}

// the suffix hook for the site map page
if(is_callable(array('Hooks', 'include_scripts')))
	$context['text'] .= Hooks::include_scripts('sections/index.php#suffix');

// page tools
if(Surfer::is_associate()) {
	$context['page_tools'][] = Skin::build_link('sections/edit.php', i18n::s('Add a section'));
	$context['page_tools'][] = Skin::build_link('help/populate.php', i18n::s('Content Assistant'));
	$context['page_tools'][] = Skin::build_link('sections/check.php', i18n::s('Maintenance'));
}

// display extra information
$cache_id = 'sections/index.php#extra';
if(!$text =& Cache::get($cache_id)) {

	// see also
	$lines = array();
	$lines[] = Skin::build_link('categories/', i18n::s('Categories'));
	$lines[] = Skin::build_link('search.php', i18n::s('Search'));
	$lines[] = Skin::build_link('help/', i18n::s('Help index'));
	$lines[] = Skin::build_link('query.php', i18n::s('Contact'));
	$text .= Skin::build_box(i18n::s('See also'), Skin::finalize_list($lines, 'compact'), 'extra');

	// list monthly publications in an extra box
	include_once '../categories/categories.php';
	$anchor =& Categories::get(i18n::c('monthly'));
	if(isset($anchor['id']) && ($items = Categories::list_by_date_for_anchor('category:'.$anchor['id'], 0, COMPACT_LIST_SIZE, 'compact'))) {
		$text .= Skin::build_box($anchor['title'], Skin::build_list($items, 'compact'), 'extra')."\n";
	}

	// side boxes for related categories, if any
	include_once '../categories/categories.php';
	if($categories = Categories::list_by_date_for_display('section:index', 0, 7, 'raw')) {
		foreach($categories as $id => $attributes) {

			// link to the category page from the box title
			$label =& Skin::build_box_title(Skin::strip($attributes['title']), Categories::get_permalink($attributes), i18n::s('View the category'));

			// box content
			if($items =& Members::list_articles_by_date_for_anchor('category:'.$id, 0, COMPACT_LIST_SIZE, 'compact'))
				$text .= Skin::build_box($label, Skin::build_list($items, 'compact'), 'navigation')."\n";
		}
	}

	// download content
	if(Surfer::is_associate() && (!isset($context['pages_without_freemind']) || ($context['pages_without_freemind'] != 'Y')) ) {

		// box content
		$content = Skin::build_link(Sections::get_url('all', 'freemind', utf8::to_ascii($context['site_name']).'.mm'), i18n::s('Freemind map'), 'basic');

		// in a sidebar box
		$text .= Skin::build_box(i18n::s('Download'), $content, 'navigation');

	}

	// save, whatever change, for 5 minutes
	Cache::put($cache_id, $text, 'stable', 300);
}
$context['aside']['boxes'] = $text;

// referrals, if any
$context['aside']['referrals'] = Skin::build_referrals('sections/index.php');

// render the skin
render_skin();

?>