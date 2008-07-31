<?php
/**
 * the index page for locations
 *
 * For a comprehensive description of locations, you should check the database abstraction script
 * at [script]locations/locations.php[/script].
 *
 * This page list locations available in the system.
 *
 * Note that because location records have no active field, as other items of the database, they
 * cannot be protected individually.
 * Because of that only associates can access this page.
 * Other surfers will have to go through related pages to access locations.
 * Therefore, locations will be protected by any security scheme applying to related pages.
 *
 * Let take for example a location inserted in a page restricted to logged members.
 * Only authenticated users will be able to read the page, and the embedded location as well.
 * Through this index associates will have an additional access link to all locations.
 *
 * The main menu has navigation links to browse locations by page, for sites that have numerous locations.
 *
 * locations are displayed using the default decorated layout.
 *
 * A list of most recent articles is displayed as a sidebar.
 *
 * Accept following invocations:
 * - index.php (view the 20 top locations)
 * - index.php/2 (view locations 41 to 60)
 * - index.php?page=2 (view locations 41 to 60)
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'locations.php';

// which page should be displayed
$page = 1;
if(isset($_REQUEST['page']))
	$page = $_REQUEST['page'];
elseif(isset($context['arguments'][0]))
	$page = $context['arguments'][0];
$page = strip_tags($page);

// load the skin
load_skin('locations');

// the maximum number of locations per page
if(!defined('LOCATIONS_PER_PAGE'))
	define('LOCATIONS_PER_PAGE', 20);

// the title of the page
$context['page_title'] = i18n::s('Locations');

// this page is really only for associates
if(!Surfer::is_associate())
	$context['text'] .= '<p>'.i18n::s('Because of our security policy you are not allowed to list locations.').'</p>'
		.'<p>'.sprintf(i18n::s('Please browse %s to visualize any location that could be embedded.'), Skin::build_link('articles/', i18n::s('pages'), 'basic')).'</p>'."\n";

// display the index
else {

	// count locations in the database
	$stats = Locations::stat();
	if($stats['count'])
		$context['page_menu'] = array_merge($context['page_menu'], array('_count' => sprintf(i18n::ns('%d location', '%d locations', $stats['count']), $stats['count'])));

	// navigation commands for locations, if necessary
	if($stats['count'] > LOCATIONS_PER_PAGE) {
		$home = 'locations/';
		if($context['with_friendly_urls'] == 'Y')
			$prefix = $home.'index.php/';
		elseif($context['with_friendly_urls'] == 'R')
			$prefix = $home;
		else
			$prefix = $home.'?page=';
		$context['page_menu'] = array_merge($context['page_menu'], Skin::navigate($home, $prefix, $stats['count'], LOCATIONS_PER_PAGE, $page));
	}

	// page main content
	$cache_id = 'locations/index.php#text#'.$page;
	if(!$text =& Cache::get($cache_id)) {

		// query the database and layout that stuff
		$offset = ($page - 1) * LOCATIONS_PER_PAGE;
		if(!$items = Locations::list_by_date($offset, LOCATIONS_PER_PAGE, 'full'))
			$text .= '<p>'.i18n::s('No location has been created yet.').'</p>';

		// we have an array to format
		if(is_array($items))
			$text =& Skin::build_list($items, 'rows');

		// cache this to speed subsequent queries
		Cache::put($cache_id, $text, 'locations');
	}
	$context['text'] .= $text;

}

// page tools
if(Surfer::is_associate())
	$context['page_tools'][] = Skin::build_link('locations/check.php', i18n::s('Maintenance'), 'basic');

// page extra content
$cache_id = 'locations/index.php#extra';
if(!$text =& Cache::get($cache_id)) {

	// side bar with the list of most recent pages
	if($items = Articles::list_by_date(0, COMPACT_LIST_SIZE, 'compact'))
		$text =& Skin::build_box(i18n::s('Recent pages'), Skin::build_list($items, 'compact'), 'extra');

	Cache::put($cache_id, $text, 'articles');
}
$context['extra'] .= $text;

// referrals, if any
$context['extra'] .= Skin::build_referrals('locations/index.php');

// render the skin
render_skin();

?>