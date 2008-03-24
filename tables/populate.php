<?php
/**
 * populate tables
 *
 * @todo add a table to track uploads per month
 *
 * files
 * SELECT EXTRACT(YEAR_MONTH FROM edit_date) AS Year, SUM(file_size) AS Size FROM `yacs_files` GROUP BY EXTRACT(YEAR_MONTH FROM edit_date) ORDER BY EXTRACT(YEAR_MONTH FROM edit_date) LIMIT 0, 200000;
 *
 * images
 * SELECT EXTRACT(YEAR_MONTH FROM edit_date) AS Year, SUM(image_size) AS Size FROM `yacs_images` GROUP BY EXTRACT(YEAR_MONTH FROM edit_date) ORDER BY EXTRACT(YEAR_MONTH FROM edit_date) LIMIT 0, 200000;
 *
 * Creates following sample tables:
 * - 'my_articles' - A simple table listing newest articles
 *
 * @see control/populate.php
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// if it was a HEAD request, stop here
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
	return;

// this one has to be included
if(count(get_included_files()) < 3) {
	echo 'Script must be included';
	return;
}
include_once $context['path_to_root'].'tables/tables.php';

// load localized strings
i18n::bind('tables');

// clear the cache for tables
Cache::clear('tables');

$text = '';

// 'my_articles' article
if(Tables::get('my_articles'))
	$text .= i18n::s('A sample "my_articles" table already exists.').BR."\n";
elseif($anchor = Articles::lookup('my_article')) {
	$fields = array();
	$fields['anchor'] = $anchor;
	$fields['nick_name'] = 'my_articles';
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
		$text .= sprintf(i18n::s('A table %s has been created.'), $fields['title']).BR."\n";
	else
		$text .= Skin::error_pop().BR."\n";
}

// print error message, if any
echo $text."\n";

?>