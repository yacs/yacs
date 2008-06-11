<?php
/**
 * populate categories
 *
 * Creates following categories for web management purposes:
 * - i18n::c('featured') - the articles to display at the front page
 * - i18n::c('monthly') - for articles published each month
 * - i18n::c('weekly') - for articles published each week
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

// stop hackers
defined('YACS') or exit('Script must be included');

// clear the cache for categories
Cache::clear('categories');

// this page is dedicated to categories
include_once $context['path_to_root'].'categories/categories.php';

$text = '';

// 'featured' category
if(Categories::get(i18n::c('featured')))
	$text .= i18n::s('A category already exists for featured articles.').BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = i18n::c('featured');
	$fields['title'] = i18n::c('Featured');
	$fields['introduction'] = i18n::c('Pages to display at the front page');
	$fields['description'] = i18n::c('This category is displayed at the front page as a compact list of featured pages. Attach to this category either important and permament pages to be displayed upfront. If you are publishing some articles on a regular basis, you can also describe explicitly the new content for the week or for the month.');
	$fields['active_set'] = 'N';
	$fields['active'] = 'N';
	$fields['options'] = 'no_links';
	if(Categories::post($fields))
		$text .= sprintf(i18n::s('A category %s has been created.'), $fields['title']).BR."\n";
}

// 'monthly' category
if(Categories::get(i18n::c('monthly')))
	$text .= i18n::s('A category already exists for publications by months.').BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = i18n::c('monthly');
	$fields['title'] = i18n::c('Publications by month');
	$fields['introduction'] = '';
	$fields['description'] = i18n::c('Articles published on this server are automatically referenced below.');
	$fields['rank'] = 22000;
	$fields['options'] = 'no_links';
	if(Categories::post($fields))
		$text .= sprintf(i18n::s('A category %s has been created.'), $fields['title']).BR."\n";
}

// 'weekly' category
if(Categories::get(i18n::c('weekly')))
	$text .= i18n::s('A category already exists for publications week by week.').BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = i18n::c('weekly');
	$fields['title'] = i18n::c('Publications by week');
	$fields['introduction'] = '';
	$fields['description'] = i18n::c('Articles published on this server are automatically referenced below.');
	$fields['rank'] = 21000;
	$fields['options'] = 'no_links';
	if(Categories::post($fields))
		$text .= sprintf(i18n::s('A category %s has been created.'), $fields['title']).BR."\n";
}

// print error message, if any
echo $text."\n";

?>