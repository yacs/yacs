<?php
/**
 * one year of dates
 *
 * Accept following invocations:
 * - year.php
 * - year.php/2012
 * - year.php?year=2012
 *
 * @author Bernard Paques
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
	$year = (int)gmdate('Y');

// load the skin
load_skin('dates');

// the title of the page
$context['page_title'] = $year;

// no more than three years difference with now
if(abs(mktime(0, 0, 0, 1, 1, $year) - time()) > (31536000 * 3)) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

} else {

	// page main content
	$cache_id = 'dates/year.php#text#'.$year;
	if(!$text = Cache::get($cache_id)) {

		// robots cannot navigate
		if(!Surfer::is_crawler()) {

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

		}

		// one calendar per month
		for($index = 1; $index <= 12; $index++) {

			// items for this month
			$items = Dates::list_for_month($year, $index, 'links');

			// draw all months - force empty months
			$text .= Dates::build_months($items, TRUE, TRUE, TRUE, FALSE, $year, $index);

		}

		// cache, whatever change, for 5 minutes
		Cache::put($cache_id, $text, 'stable', 300);
	}
	$context['text'] .= $text;

}

// page extra content
$cache_id = 'dates/year.php#extra';
if(!$text = Cache::get($cache_id)) {

	// side bar with the list of most recent pages
	if($items = Articles::list_by('publication', 0, COMPACT_LIST_SIZE, 'compact'))
		$text = Skin::build_box(i18n::s('Recent pages'), Skin::build_list($items, 'compact'), 'boxes');

	Cache::put($cache_id, $text, 'articles');
}
$context['components']['boxes'] = $text;

// referrals, if any
$context['components']['referrals'] = Skin::build_referrals('dates/year.php/'.$year);

// render the skin
render_skin();

?>
