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
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @tester Eoin
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// which page should be displayed
$page = 1;
if(isset($_REQUEST['page']))
	$page = $_REQUEST['page'];
elseif(isset($context['arguments'][0]))
	$page = $context['arguments'][0];
$page = strip_tags($page);

// sanity check
if($page < 1)
	$page = 1;

// load localized strings
i18n::bind('sections');

// load the skin
load_skin('sections');

// page size
$items_per_page = 50;

// the title of the page
$context['page_title'] = i18n::s('Site map');

// count public root sections in the database
$count = Sections::count_for_anchor(NULL);
if($count > $items_per_page) {

	// display the total count of sections
	$context['page_menu'] = array_merge($context['page_menu'], array('_count' => sprintf(i18n::s('%d&nbsp;sections'), $count)));

	// navigation commands for sections, if necessary
	$home = 'sections/index.php';
	if(isset($context['with_friendly_urls']) && ($context['with_friendly_urls'] == 'Y'))
		$prefix = $home.'/';
	elseif(isset($context['with_friendly_urls']) && ($context['with_friendly_urls'] == 'R'))
		$prefix = $home.'/';
	else
		$prefix = $home.'?page=';
	$context['page_menu'] = array_merge($context['page_menu'], Skin::navigate($home, $prefix, $count, $items_per_page, $page));
}

// associates may create a new section
if(Surfer::is_associate())
	$context['page_menu'] = array_merge($context['page_menu'], array( 'sections/edit.php' => i18n::s('Create a section') ));

// associates may trigger the content assistant
if(Surfer::is_associate())
	$context['page_menu'] = array_merge($context['page_menu'], array( 'control/populate.php' => i18n::s('Content Assistant') ));

// associates may check the database
if(Surfer::is_associate())
	$context['page_menu'] = array_merge($context['page_menu'], array( 'sections/check.php' => i18n::s('Maintenance') ));

//
// meta information
//

// a meta link to our blogging interface
$context['page_header'] .= "\n".'<link rel="EditURI" href="'.$context['url_to_home'].$context['url_to_root'].'services/describe.php" title="RSD" type="application/rsd+xml"'.EOT;

// the prefix hook for the site map page
if(is_callable(array('Hooks', 'include_scripts')))
	$context['text'] .= Hooks::include_scripts('sections/index.php#prefix');

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
	if(!$items = Sections::list_by_title_for_anchor(NULL, $offset, $items_per_page, $layout))
		$items = '<p>'.i18n::s('No regular section has been created yet!').'</p>';

	// we have an array to format
	if(is_array($items))
		$items =& Skin::build_list($items, '2-columns');

	// make a box
	if($items)
		$text .= Skin::build_box('', $items, 'section', 'sections');

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

			// splash
			$content = '<p>'.i18n::s('Only associates can access following sections.').'</p>';

			// we have an array to format
			if(is_array($items))
				$content .= Skin::build_list($items, '2-columns');
			else
				$content .= (string)$items;

			// displayed as another page section
			$text .= Skin::build_box(i18n::s('Special sections'), $content, 'section', 'special_sections');

		}
	}

	// cache this to speed subsequent queries
	Cache::put($cache_id, $text, 'sections');
}
$context['text'] .= $text;

// the suffix hook for the site map page
if(is_callable(array('Hooks', 'include_scripts')))
	$context['text'] .= Hooks::include_scripts('sections/index.php#suffix');

// display extra information
$cache_id = 'sections/index.php#extra';
if(!$text =& Cache::get($cache_id)) {

	// add an extra box with helpful links
	$links = array('categories/' => i18n::s('All categories'),
		'search.php' => i18n::s('Search on keyword'),
		'help.php' => i18n::s('Help index'),
		'query.php' => i18n::s('Contact the webmaster'));
	$text .= Skin::build_box(i18n::s('See also'), Skin::build_list($links, 'compact'), 'extra')."\n";

	// list monthly publications in an extra box
	include_once '../categories/categories.php';
	$anchor =& Categories::get(i18n::c('monthly'));
	if(isset($anchor['id']) && ($items = Categories::list_by_date_for_anchor('category:'.$anchor['id'], 0, COMPACT_LIST_SIZE, 'compact'))) {
		$text .= Skin::build_box($anchor['title'], Skin::build_list($items, 'categories'), 'extra')."\n";
	}

	// side boxes for related categories, if any
	include_once '../categories/categories.php';
	if($categories = Categories::list_by_date_for_display('section:index', 0, 7, 'raw')) {
		foreach($categories as $id => $attributes) {

			// link to the category page from the box title
			$label =& Skin::build_box_title(Skin::strip($attributes['title']), Categories::get_url($attributes['id'], 'view', $attributes['title']), i18n::s('View the category'));

			// box content
			if($items = Members::list_articles_by_date_for_anchor('category:'.$id, 0, COMPACT_LIST_SIZE, 'compact'))
				$text .= Skin::build_box($label, Skin::build_list($items, 'compact'), 'navigation')."\n";
		}
	}

	// download content
	if(Surfer::is_member() && (!isset($context['pages_without_freemind']) || ($context['pages_without_freemind'] != 'Y')) ) {

		// box content
		$content = Skin::build_link(Sections::get_url('all', 'freemind', utf8::to_ascii($context['site_name']).'.mm'), i18n::s('Freemind map'), 'basic');

		// in a sidebar box
		$text .= Skin::build_box(i18n::s('Download'), Codes::beautify($content), 'navigation');

	}

	// save, whatever change, for 5 minutes
	Cache::put($cache_id, $text, 'stable', 300);
}
$context['extra'] .= $text;

// referrals, if any
$context['extra'] .= Skin::build_referrals('sections/index.php');

// render the skin
render_skin();

?>