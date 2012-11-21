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

// top ten active pages over last months

SELECT 
	i.month, 
	i.rank, 
	CONCAT( '<a href="/article-', i.id, '">', i.title, '</a>'
		' (<a href="/user-', i.owner_id, '">', u.full_name, '</a>)',
		' in <a href="/section-', i.section_id, '">', s.title, '</a>') AS summary,
	i.contributions,
	i.notifications, 
	i.activities
FROM
	(SELECT
		t.month, 
		t.rank, 
		p.title, 
		t.id, 
		t.contributions, 
		t.notifications, 
		t.activities, 
		p.owner_id, 
		SUBSTRING(p.anchor, 9) AS section_id 
	FROM
		(SELECT 
			c.month, 
			c.id, 
			c.contributions, 
			c.notifications,
			c.activities,
			@num := if(@breakdown = c.month, @num + 1, 1) AS rank,
			@breakdown := c.month AS carry_over
		FROM
			(SELECT 
				c1.month, 
				SUBSTRING(c1.anchor, 9) AS id, 
				c1.contributions, 
				if(c2.activities > 0, c2.activities, 0) AS notifications,
				if(c2.activities > 0, c1.contributions + c2.activities, c1.contributions) AS activities
			FROM
				(SELECT * FROM yacs_comments_by_anchor_per_month x
				WHERE x.month IN (SUBSTRING(NOW(), 1, 7), 
					SUBSTRING(NOW() - INTERVAL 1 MONTH, 1, 7), 
					SUBSTRING(NOW() - INTERVAL 2 MONTH, 1, 7), 
					SUBSTRING(NOW() - INTERVAL 3 MONTH, 1, 7))
					AND anchor LIKE 'article:%'
				ORDER BY x.month DESC, contributions DESC) c1
				LEFT JOIN (SELECT * FROM yacs_activities_by_anchor_per_month x
				WHERE x.month IN (SUBSTRING(NOW(), 1, 7), 
					SUBSTRING(NOW() - INTERVAL 1 MONTH, 1, 7), 
					SUBSTRING(NOW() - INTERVAL 2 MONTH, 1, 7), 
					SUBSTRING(NOW() - INTERVAL 3 MONTH, 1, 7))
					AND anchor LIKE 'article:%'
				ORDER BY x.month DESC, activities DESC) c2
				ON (c2.month = c1.month) AND (c2.anchor = c1.anchor)
			GROUP BY c1.month, c1.anchor
			ORDER BY c1.month DESC, activities DESC) c,
			(SELECT @breakdown := '') b,
			(SELECT @num := 1) n
		GROUP BY c.month, id
		HAVING rank <= 10
		ORDER BY c.month DESC, rank ASC) t
	LEFT JOIN yacs_articles p ON (p.id = t.id)) i
LEFT JOIN yacs_users u ON (u.id = i.owner_id)
LEFT JOIN yacs_sections s ON (s.id = i.section_id)

// overall level of activity over time

SELECT 
	c1.month, 
	c1.contributions, 
	if(c2.activities > 0, c2.activities, 0) AS notifications,
	if(c2.activities > 0, c1.contributions + c2.activities, c1.contributions) AS activities
FROM
	(SELECT x.month, SUM(x.contributions) AS contributions FROM yacs_comments_by_anchor_per_month x
	WHERE x.month IN (SUBSTRING(NOW(), 1, 7), 
		SUBSTRING(NOW() - INTERVAL 1 MONTH, 1, 7), 
		SUBSTRING(NOW() - INTERVAL 2 MONTH, 1, 7), 
		SUBSTRING(NOW() - INTERVAL 3 MONTH, 1, 7), 
		SUBSTRING(NOW() - INTERVAL 4 MONTH, 1, 7), 
		SUBSTRING(NOW() - INTERVAL 5 MONTH, 1, 7), 
		SUBSTRING(NOW() - INTERVAL 6 MONTH, 1, 7), 
		SUBSTRING(NOW() - INTERVAL 7 MONTH, 1, 7), 
		SUBSTRING(NOW() - INTERVAL 8 MONTH, 1, 7), 
		SUBSTRING(NOW() - INTERVAL 9 MONTH, 1, 7), 
		SUBSTRING(NOW() - INTERVAL 10 MONTH, 1, 7), 
		SUBSTRING(NOW() - INTERVAL 11 MONTH, 1, 7), 
		SUBSTRING(NOW() - INTERVAL 12 MONTH, 1, 7), 
		SUBSTRING(NOW() - INTERVAL 13 MONTH, 1, 7))
	GROUP BY x.month
	ORDER BY x.month DESC) c1
	LEFT JOIN (SELECT x.month, SUM(x.activities) AS activities FROM yacs_activities_by_anchor_per_month x
	WHERE x.month IN (SUBSTRING(NOW(), 1, 7), 
		SUBSTRING(NOW() - INTERVAL 1 MONTH, 1, 7), 
		SUBSTRING(NOW() - INTERVAL 2 MONTH, 1, 7), 
		SUBSTRING(NOW() - INTERVAL 3 MONTH, 1, 7), 
		SUBSTRING(NOW() - INTERVAL 4 MONTH, 1, 7), 
		SUBSTRING(NOW() - INTERVAL 5 MONTH, 1, 7), 
		SUBSTRING(NOW() - INTERVAL 6 MONTH, 1, 7), 
		SUBSTRING(NOW() - INTERVAL 7 MONTH, 1, 7), 
		SUBSTRING(NOW() - INTERVAL 8 MONTH, 1, 7), 
		SUBSTRING(NOW() - INTERVAL 9 MONTH, 1, 7), 
		SUBSTRING(NOW() - INTERVAL 10 MONTH, 1, 7), 
		SUBSTRING(NOW() - INTERVAL 11 MONTH, 1, 7), 
		SUBSTRING(NOW() - INTERVAL 12 MONTH, 1, 7), 
		SUBSTRING(NOW() - INTERVAL 13 MONTH, 1, 7))
	GROUP BY x.month
	ORDER BY x.month DESC) c2
	ON (c2.month = c1.month)
GROUP BY c1.month
ORDER BY c1.month DESC

*/

// print error message, if any
echo $text."\n";

?>