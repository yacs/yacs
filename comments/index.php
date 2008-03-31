<?php
/**
 * the index page for comments
 *
 * @todo explicit thread dates, like in http://www.sitepoint.com/forums/
 *
 * For a comprehensive description of comments, you should check the database abstraction script
 * at [script]comments/comments.php[/script].
 *
 * This page list threads, based on recent comments.
 * Therefore, this index page is a very convenient place to look for most recent replies of your community.
 *
 * A list of most recent articles is displayed as a sidebar.
 *
 * Accept following invocations:
 * - index.php (view the 20 top comments)
 * - index.php/2 (view comments 41 to 60)
 * - index.php?page=2 (view comments 41 to 60)
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'comments.php';

// which page should be displayed
$page = 1;
if(isset($_REQUEST['page']))
	$page = $_REQUEST['page'];
elseif(isset($context['arguments'][0]))
	$page = $context['arguments'][0];
$page = strip_tags($page);

// load localized strings
i18n::bind('comments');

// load the skin
load_skin('comments');

// the title of the page
$context['page_title'] = i18n::s('All threads');

// count comments in the database
$stats = Comments::stat_threads();
if($stats['count'])
	$context['page_menu'] = array_merge($context['page_menu'], array('_count' => sprintf(i18n::ns('1&nbsp;thread', '%d&nbsp;threads', $stats['count']), $stats['count'])));

// navigation commands for comments, if necessary
if($stats['count'] > THREADS_PER_PAGE) {
	$home = 'comments/index.php';
	if($context['with_friendly_urls'] == 'Y')
		$prefix = $home.'/';
	elseif($context['with_friendly_urls'] == 'R')
		$prefix = $home.'/';
	else
		$prefix = $home.'?page=';
	$context['page_menu'] = array_merge($context['page_menu'], Skin::navigate($home, $prefix, $stats['count'], THREADS_PER_PAGE, $page));
}

// associates may check the database
if(Surfer::is_associate())
	$context['page_menu'] = array_merge($context['page_menu'], array( 'comments/check.php' => i18n::s('Maintenance') ));

// page main content
$cache_id = 'comments/index.php#text#'.$page;
if(!$text =& Cache::get($cache_id)) {

	// the first comment to list
	$offset = ($page - 1) * THREADS_PER_PAGE;

	// load the layout to use
	include_once $context['path_to_root'].'articles/layout_articles_as_yabb.php';
	$layout =& new Layout_articles_as_yabb();

	// query the database and layout that stuff
	if(!$text = Comments::list_threads_by_date($offset, THREADS_PER_PAGE, $layout))
		$context['text'] .= '<p>'.i18n::s('No comment has been transmitted yet.').'</p>';

	// we have an array to format
	if(is_array($text))
		$text =& Skin::build_list($text, 'rows');

	// cache, whatever changes, for 1 minute
	Cache::put($cache_id, $text, 'stable', 60);
}
$context['text'] .= $text;

// page extra information
$cache_id = 'comments/index.php#extra';
if(!$text =& Cache::get($cache_id)) {

	// side bar with the list of most recent pages
	if($items = Articles::list_by_date(0, COMPACT_LIST_SIZE, 'compact'))
		$text =& Skin::build_box(i18n::s('Recent pages'), Skin::build_list($items, 'compact'), 'extra');

	Cache::put($cache_id, $text, 'articles');
}
$context['extra'] .= $text;

// referrals, if any
$context['extra'] .= Skin::build_referrals('comments/index.php');

// render the skin
render_skin();

?>