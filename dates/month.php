<?php
/**
 * one month of dates
 *
 * @todo http://www.investinitaly.com/events_archivio.jsp?calFrom=2006-3-01&calTo=2006-3-01&resultFrom=2006-3-01&resultTo=2006-3-31
 *
 * Accept following invocations:
 * - month.php
 * - month.php/2012/3
 * - month.php?month=2012-03
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'dates.php';

// target month
$target = 0;
if(isset($context['arguments'][1]))
	$target = $context['arguments'][0].'/'.$context['arguments'][1];
elseif(isset($_REQUEST['month']))
	$target = $_REQUEST['month'];
$target = strip_tags($target);
if($target < '1970')
	$target = gmstrftime('%Y/%m');

// do not accept more than 7 chars
if(strlen($target) > 7)
	$target = substr($target, 0, 7);

// expand the compact form (e.g., '199903' -> '1999/03')
if(strlen($target) == 6)
	$target = substr($target, 0, 4).'/'.substr($target, 5, 2);

// normalize separator
$target = str_replace('-', '/', $target);

// load the skin
load_skin('dates');

// the title of the page
$context['page_title'] = ucfirst(Dates::get_month_label($target));

// we do need 7 chars
if(strlen($target) != 7) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// parse the provided parameter
} else {

	// which month?
	list($year, $month) = explode('/', $target, 2);

	// no more than two years difference with now
	if(abs(mktime(0, 0, 0, $month, 1, $year) - time()) > (31536000 * 2)) {
		Safe::header('Status: 401 Forbidden', TRUE, 401);
		Logger::error(i18n::s('You are not allowed to perform this operation.'));

	} else {
	
		// page main content
		$cache_id = 'dates/month.php#text#'.$target;
		if(!$text =& Cache::get($cache_id)) {
	
			// robots cannot navigate
			if(!Surfer::is_crawler()) {
			
				// previous month
				$previous = gmstrftime('%Y/%m', gmmktime(0, 0, 0, $month-1, 1, $year));
			
				// next month
				$next = gmstrftime('%Y/%m', gmmktime(0, 0, 0, $month+1, 1, $year));
			
				// neighbours
				$neighbours = array(Dates::get_url($previous, 'month'), Dates::get_month_label($previous),
					Dates::get_url($next, 'month'), Dates::get_month_label($next),
					Dates::get_url($year, 'year'), $year);
			
				// links to display previous and next months
				$text .= Skin::neighbours($neighbours, 'slideshow');

			}
			
			// get items for this month
			$items =& Dates::list_for_month($year, $month, 'links');
		
			// draw one month - force an empty month
			$text .= Dates::build_months($items, FALSE, FALSE, TRUE, FALSE, $year, $month);
		
			// cache, whatever change, for 5 minutes
			Cache::put($cache_id, $text, 'stable', 300);
		
		}
		$context['text'] .= $text;
	}
}

// page extra content
$cache_id = 'dates/month.php#extra';
if(!$text =& Cache::get($cache_id)) {

	// side bar with the list of most recent pages
	if($items =& Articles::list_by('publication', 0, COMPACT_LIST_SIZE, 'compact'))
		$text =& Skin::build_box(i18n::s('Recent pages'), Skin::build_list($items, 'compact'), 'extra');

	Cache::put($cache_id, $text, 'articles');
}
$context['aside']['boxes'] = $text;

// referrals, if any
$context['aside']['referrals'] = Skin::build_referrals('dates/month.php/'.$target);

// render the skin
render_skin();

?>