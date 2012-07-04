<?php
/**
 * populate tables
 *
 * @todo add tables to build the activity dashboard
 *
 * @see control/populate.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// if it was a HEAD request, stop here
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
	return;

// stop hackers
defined('YACS') or exit('Script must be included');

include_once $context['path_to_root'].'tables/tables.php';

// clear the cache for tables
Cache::clear('tables');

$text = '';

/**

// the section that host dashboard pages

// 'table_sample' article
if(Tables::get('table_sample'))
	$text .= sprintf(i18n::s('A table "%s" already exists.'), i18n::c('My Articles')).BR."\n";
elseif($anchor = Articles::lookup('table_sample')) {
	$fields = array();
	$fields['anchor'] = $anchor;
	$fields['nick_name'] = 'table_sample';
	$fields['title'] = i18n::c('My Articles');
	$fields['description'] = i18n::c('This is a sample table to let you learn and practice.');
	$fields['query'] = "SELECT \n"
		."articles.title as titre, \n"
		."articles.id as 'id', \n"
		."articles.introduction as introduction, \n"
		."articles.edit_name as 'last editor', \n"
		."articles.edit_date as 'Date' \n"
		."FROM ".SQL::table_name('articles')." AS articles \n"
		."WHERE (articles.active='Y') \n"
		."ORDER BY articles.rank, articles.edit_date DESC, articles.title LIMIT 0,10";
	if(Tables::post($fields))
		$text .= sprintf(i18n::s('A table "%s" has been created.'), $fields['title']).BR."\n";
	else
		$text .= Logger::error_pop().BR."\n";
}

// 'monthly_notifications' article
if(Tables::get('monthly_notifications'))
	$text .= sprintf(i18n::s('A table "%s" already exists.'), i18n::c('Monthly notifications')).BR."\n";
elseif($anchor = Articles::lookup('monthly_notifications')) {
	$fields = array();
	$fields['anchor'] = $anchor;
	$fields['nick_name'] = 'monthly_notifications';
	$fields['title'] = i18n::c('My Articles');
	$fields['description'] = i18n::c('This is a sample table to let you learn and practice.');
	$fields['query'] = "SELECT \n"
		."articles.title as titre, \n"
		."articles.id as 'id', \n"
		."articles.introduction as introduction, \n"
		."articles.edit_name as 'last editor', \n"
		."articles.edit_date as 'Date' \n"
		."FROM ".SQL::table_name('articles')." AS articles \n"
		."WHERE (articles.active='Y') \n"
		."ORDER BY articles.rank, articles.edit_date DESC, articles.title LIMIT 0,10";
	if(Tables::post($fields))
		$text .= sprintf(i18n::s('A table "%s" has been created.'), $fields['title']).BR."\n";
	else
		$text .= Logger::error_pop().BR."\n";
}

(SELECT t1.Month, t1.Person, t1.Notifications
FROM yacs_activities_notifications_per_month t1
LEFT OUTER JOIN yacs_activities_notifications_per_month t2
  ON (t1.Month = t2.Month AND t1.Notifications < t2.Notifications)
GROUP BY t1.Month, t1.Person
HAVING COUNT(*) < 3
ORDER BY t1.Month DESC, t1.Notifications DESC)

UNION

(SELECT Month, 'Total' AS 'Person', SUM(Notifications) AS 'Notifications'
FROM yacs_activities_notifications_per_month
GROUP BY Month
ORDER BY Month DESC)

ORDER By Month DESC, Notifications DESC

*/

// print error message, if any
echo $text."\n";

?>