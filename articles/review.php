<?php
/**
 * the review page for articles
 *
 * This page lists all articles that need some review of some sort.
 * This includes:
 *
 * - queries submitted by any surfers
 *
 * - articles that have been posted, but that are not yet published (i.e., the 'publish_date' field is empty).
 *
 * - articles that have been published in the future (i.e., the 'publish_date' is in the future)
 *
 * - articles that have expired (i.e., the 'expiry_date' is in the past)
 *
 * - articles that have the less hits
 *
 * - articles with the oldest edition dates
 *
 * Everybody can view this page.
 * However, anonymous surfers and regular members will only access draft pages here.
 * Therefore this index page is a straightforward mean for a regular member to ensure that his/her post has been
 * registered by the system before it has been officially published.
 *
 * Other parts of this index enable associates to review content of their system.
 * You can check this single page quite regularly to track new queries and dead pages.
 * Also you can review oldest pages to ensure their accuracy, or try to figure why nobody is interested in published material.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Mark
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// load the skin
load_skin('articles');

// the path to this page
$context['path_bar'] = array( 'articles/' => i18n::s('All pages') );

// the title of the page
$context['page_title'] = i18n::s('Articles to be reviewed');

// the menu bar for this page
$context['page_menu'] = array( 'articles/' => i18n::s('All pages') );

// list queries
if(Surfer::is_associate()) {
	$anchor = Sections::lookup('queries');
	if($anchor && ($rows = Articles::list_by_date_for_anchor($anchor, 0, 5))) {
		if(is_array($rows))
			$rows = Skin::build_list($rows, 'decorated');
		$context['text'] .= Skin::build_box(i18n::s('Submitted queries'), $rows, 'header1', 'queries');
	}
}

// list draft articles
if($rows = Articles::list_by('draft')) {
	// set a title to the section only if we have other sections
	if(Surfer::is_associate())
		$context['text'] .= Skin::build_block(i18n::s('Submitted articles'), 'title', 'submitted');
	if(is_array($rows))
		$context['text'] .= Skin::build_list($rows, 'decorated');
	else
		$context['text'] .= $rows;
}

// list future articles
if(Surfer::is_associate() && ($rows = Articles::list_by('future', 0, 5))) {
	if(is_array($rows))
		$rows = Skin::build_list($rows, 'decorated');
	$context['text'] .= Skin::build_box(i18n::s('Future articles'), $rows, 'header1', 'future');
}

// list dead articles
if(Surfer::is_associate() && ($rows = Articles::list_by('expiry', 0, 10, 'hits'))) {
	if(is_array($rows))
		$rows = Skin::build_list($rows, 'decorated');
	$context['text'] .= Skin::build_box(i18n::s('Dead articles'), $rows, 'header1', 'expired');
}

// list the oldest published articles, that have to be validated again
if(Surfer::is_associate() && ($rows = Articles::list_by('review', 0, 10, 'review'))) {
	if(is_array($rows))
		$rows = Skin::build_list($rows, 'decorated');
	$context['text'] .= Skin::build_box(i18n::s('Oldest articles'), $rows, 'header1', 'oldest');
}

// list articles with very few hits
if(Surfer::is_associate() && ($rows = Articles::list_by('unread', 0, 10, 'hits'))) {
	if(is_array($rows))
		$rows = Skin::build_list($rows, 'decorated');
	$context['text'] .= Skin::build_box(i18n::s('Less read articles'), $rows, 'header1', 'unread');
}

// render the skin
render_skin();

?>