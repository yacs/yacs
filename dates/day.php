<?php
/**
 * one day of dates
 *
 * Accept following invocations:
 * - day.php
 * - day.php/2012/3/6
 * - day.php?day=2012-03-02
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'dates.php';

// target day
$target = 0;
if(isset($context['arguments'][2]))
	$target = $context['arguments'][0].'/'.$context['arguments'][1].'/'.$context['arguments'][2];
elseif(isset($_REQUEST['day']))
	$target = $_REQUEST['day'];
$target = strip_tags($target);
if($target < '1970')
	$target = gmstrftime('%Y/%m/%d');

// do not accept more than 10 chars
if(strlen($target) > 10)
	$target = substr($target, 0, 10);

// expand the compact form (e.g., '19990302' -> '1999/03/02')
if(strpos($target, '/') === FALSE)
	$target = substr($target, 0, 4).'/'.substr($target, 5, 2).'/'.substr($target, 7, 2);

// normalize separator
$target = str_replace('-', '/', $target);

// load the skin
load_skin('dates');

// the title of the page
$context['page_title'] = ucfirst(Skin::build_date($target, 'day'));

// cache between modifications
$cache_id = 'dates/day.php#text#'.$target;
if(!$text =& Cache::get($cache_id)) {

	// draw one day
	list($year, $month, $day) = explode('/', $target, 3);
	if($items = Dates::list_for_day($year, $month, $day, 'decorated'))
		$text .= Skin::build_list($items, 'decorated');
	else
		$text .= '<p>'.i18n::s('Nothing has been recorded for this date.').'</p>';

	// previous day
	$previous = gmstrftime('%Y/%m/%d', gmmktime(0, 0, 0, $month, $day-1, $year));

	// next day
	$next = gmstrftime('%Y/%m/%d', gmmktime(0, 0, 0, $month, $day+1, $year));

	// neighbours
	$neighbours = array(Dates::get_url($previous, 'day'), Skin::build_date($previous, 'standalone'),
		Dates::get_url($next, 'day'), Skin::build_date($next, 'standalone'),
		Dates::get_url($year.'/'.$month, 'month'), Dates::get_month_label($year.'/'.$month));

	// links to display previous and next days
	$text .= Skin::neighbours($neighbours, 'slideshow');

	// cache, whatever change, for 1 minute
	Cache::put($cache_id, $text, 'stable', 60);
}

// in the main panel
$context['text'] .= $text;

// side bar with the list of most recent pages
$cache_id = 'dates/day.php/'.$target.'#extra';
if(!$text =& Cache::get($cache_id)) {
	if($items = Articles::list_by_date(0, COMPACT_LIST_SIZE, 'compact')) {
		$text =& Skin::build_box(i18n::s('Recent pages'), Skin::build_list($items, 'compact'), 'extra');
	}
	Cache::put($cache_id, $text, 'articles');
}
$context['extra'] .= $text;

// referrals, if any
$context['extra'] .= Skin::build_referrals('dates/day.php/'.$target);

// render the skin
render_skin();

?>