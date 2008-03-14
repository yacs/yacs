<?php
/**
 * one year of dates
 *
 * Accept following invocations:
 * - year.php
 * - year.php/2012
 * - year.php?year=2012
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'dates.php';

// target year
$year = 0;
if(isset($context['arguments'][0]))
	$year = $context['arguments'][0];
elseif(isset($_REQUEST['year']))
	$year = $_REQUEST['year'];
$year = strip_tags($year);
if($year < 1970)
	$year = (int)gmstrftime('%Y');

// load localized strings
i18n::bind('dates');

// load the skin
load_skin('dates');

// the title of the page
$context['page_title'] = $year;

// page main content
$cache_id = 'dates/year.php#text#'.$year;
if(!$text =& Cache::get($cache_id)) {

	// draw every month
	for($month = 1; $month <= 12; $month++)
		$text .= Dates::build_month_calendar($year, $month, 'year');

	// previous year
	$previous = $year-1;

	// next year
	$next = $year+1;

	// neighbours
	$neighbours = array(Dates::get_url($previous, 'year'), $previous,
		Dates::get_url($next, 'year'), $next,
		NULL, NULL);

	// links to display previous and next years
	$text .= Skin::neighbours($neighbours, 'slideshow');

	// cache, whatever change, for 5 minutes
	Cache::put($cache_id, $text, 'stable', 300);
}
$context['text'] .= $text;

// page extra content
$cache_id = 'dates/year.php#extra';
if(!$text =& Cache::get($cache_id)) {

	// side bar with the list of most recent pages
	if($items = Articles::list_by_date(0, COMPACT_LIST_SIZE, 'compact')) {
		$text =& Skin::build_box(i18n::s('Recent pages'), Skin::build_list($items, 'compact'), 'extra');
	}

	Cache::put($cache_id, $text, 'articles');
}
$context['extra'] .= $text;

// referrals, if any
$context['extra'] .= Skin::build_referrals('dates/year.php/'.$year);

// render the skin
render_skin();

?>