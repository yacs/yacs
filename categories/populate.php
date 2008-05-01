<?php
/**
 * populate categories
 *
 * Creates following categories for web management purposes:
 * - i18n::c('featured') - the articles to display at the front page
 * - i18n::c('monthly') - for articles published each month
 * - i18n::c('weekly') - for articles published each week
 *
 * Also, if the parameter $context['populate'] is set to 'samples', additional articles will be created:
 * - 'my_category' - a sample top-level category
 * - 'my_sub_category' - a sample category
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

// 'my_category' category -- sample data
if(!isset($context['populate']) || ($context['populate'] != 'samples'))
	;
elseif(Categories::get('my_category'))
	$text .= i18n::s('A category already exists for regular articles.').BR."\n";
else {
	$fields = array();
	$fields['nick_name'] = 'my_category';
	$fields['title'] = i18n::c('My category');
	$fields['introduction'] = i18n::c('Sample plain category');
	$fields['description'] = i18n::c('This category has been created automatically by the populate script for experimentation purpose. Feel free to change this text, to add some images, to play with codes, etc. Have you checked the help link on the side of this page? Once you will feel more comfortable with the handling of categories, just delete this one and create other categories of your own.');
	if(Categories::post($fields))
		$text .= sprintf(i18n::s('A category %s has been created.'), $fields['title']).BR."\n";
}

// 'my_sub_category' category - sample data
if(!isset($context['populate']) || ($context['populate'] != 'samples'))
	;
elseif(Categories::get('my_sub_category'))
	$text .= i18n::s('A sub category already exists for regular articles.').BR."\n";
elseif($anchor = Categories::lookup('my_category')) {
	$fields = array();
	$fields['nick_name'] = 'my_sub_category';
	$fields['anchor'] = $anchor;
	$fields['title'] = i18n::c('My sub-category');
	$fields['introduction'] = i18n::c('Sample sub category');
	$fields['description'] = i18n::c('This category has been created automatically by the populate script for experimentation purpose. Feel free to change this text, to add some images, to play with codes, etc. Have you checked the help link on the side of this page? Once you will feel more comfortable with the handling of categories, just delete this one and create other categories of your own.');
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