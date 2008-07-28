<?php
/**
 * the index page for dates
 *
 * @todo add upcoming.php, like in http://www.atwonline.com/events/upcoming.html
 *
 * For a comprehensive description of dates, you should check the database abstraction script
 * at [script]dates/dates.php[/script].
 *
 * This page list dates available in the system.
 *
 * Note that because date records have no active field, as other items of the database, they
 * cannot be protected individually.
 * Because of that only associates can access this page.
 * Other surfers will have to go through related pages to access dates.
 * Therefore, dates will be protected by any security scheme applying to related pages.
 *
 * Let take for example a date inserted in a page restricted to logged members.
 * Only authenticated users will be able to read the page, and the embedded date as well.
 * Through this index associates will have an additional access link to all dates.
 *
 * The main menu has navigation links to browse dates by page, for sites that have numerous dates.
 *
 * dates are displayed using the default decorated layout.
 *
 * A list of most recent articles is displayed as a sidebar.
 *
 * Accept following invocations:
 * - index.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'dates.php';

// load the skin
load_skin('dates');

// the title of the page
$context['page_title'] = i18n::s('Dates');

// count dates in the database
$stats = Dates::stat();
if($stats['count'])
	$context['page_menu'] = array_merge($context['page_menu'], array('_count' => sprintf(i18n::ns('%d date', '%d dates', $stats['count']), $stats['count'])));

// page main content
$cache_id = 'dates/index.php#text';
if(!$text =& Cache::get($cache_id)) {

	// get and draw upcoming events with links to global calendars
	if($items =& Dates::list_future(0, 200, 'links'))
		$text = Dates::build_months($items, TRUE);
	else
		$text = i18n::s('No event has been planned so far');

	// cache, whatever changes, for 1 minute
	Cache::put($cache_id, $text, 'stable', 60);
}
$context['text'] .= $text;

// page tools
if(Surfer::is_associate())
	$context['page_tools'][] = Skin::build_link('dates/check.php', i18n::s('Maintenance'), 'basic');

// page extra information
$cache_id = 'dates/index.php#extra';
if(!$text =& Cache::get($cache_id)) {

	// side bar with the list of most recent pages
	if($items = Articles::list_by_date(0, COMPACT_LIST_SIZE, 'compact'))
		$text =& Skin::build_box(i18n::s('Recent pages'), Skin::build_list($items, 'compact'), 'extra');

	Cache::put($cache_id, $text, 'articles');
}
$context['extra'] .= $text;

// referrals, if any
$context['extra'] .= Skin::build_referrals('dates/index.php');

// render the skin
render_skin();

?>