<?php
/**
 * the index page for actions
 *
 * In YACS context, actions are a straightforward mean of implementing some weak form of workflow.
 *
 * For a more comprehensive description of actions, you should check the database abstraction script
 * at [script]actions/actions.php[/script].
 *
 * This page list actions available in the system.
 *
 * Note that because action records have no active field, as other items of the database, they
 * cannot be protected individually.
 * Because of that only associates can access this page.
 * Other surfers will have to go through related pages to access actions.
 * Therefore, actions will be protected by any security scheme applying to related pages.
 *
 * Let take for example an action inserted in a page restricted to logged members.
 * Only authenticated users will be able to read the page, and the embedded action as well.
 * Through this index associates will have an additional access link to all actions.
 *
 * The main menu has navigation links to browse actions by page, for sites that have numerous actions.
 *
 * Actions are displayed using the default decorated layout.
 *
 * A list of most recent articles is displayed as a sidebar.
 *
 * Accept following invocations:
 * - index.php (view the 20 top actions)
 * - index.php/2 (view actions 41 to 60)
 * - index.php?page=2 (view actions 41 to 60)
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'actions.php';

// which page should be displayed
if(isset($_REQUEST['page']))
	$page = $_REQUEST['page'];
elseif(isset($context['arguments'][0]))
	$page = $context['arguments'][0];
else
	$page = 1;
$page = max(1,intval($page));

// load the skin
load_skin('actions');

// page size
$items_per_page = 50;

// the title of the page
$context['page_title'] = i18n::s('Actions');

// this page is really only for associates
if(!Surfer::is_associate()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// display the index
} else {

	// count actions in the database
	$stats = Actions::stat();
	if($stats['count'])
		$context['page_menu'] += array('_count' => sprintf(i18n::ns('%d action', '%d actions', $stats['count']), $stats['count']));

	// navigation commands for actions, if necessary
	if($stats['count'] > $items_per_page) {
		$home = 'actions/';
		if($context['with_friendly_urls'] == 'Y')
			$prefix = $home.'index.php/';
		elseif($context['with_friendly_urls'] == 'R')
			$prefix = $home;
		else
			$prefix = $home.'?page=';
		$context['page_menu'] += Skin::navigate($home, $prefix, $stats['count'], $items_per_page, $page);
	}

	// query the database and layout that stuff
	$offset = ($page - 1) * $items_per_page;
	if($text = Actions::list_by_date($offset, $items_per_page, 'full')) {

		// we have an array to format
		if(is_array($text))
			$text =& Skin::build_list($text, 'decorated');

	}
	$context['text'] .= $text;

}

// page tools
if(Surfer::is_associate())
	$context['page_tools'][] = Skin::build_link('actions/check.php', i18n::s('Maintenance'), 'basic');

// side bar with the list of most recent pages
$cache_id = 'actions/index.php#articles_by_date';
if(!$text = Cache::get($cache_id)) {
	if($items =& Articles::list_by('publication', 0, COMPACT_LIST_SIZE, 'compact'))
		$text =& Skin::build_box(i18n::s('Recent pages'), Skin::build_list($items, 'compact'), 'boxes');

	Cache::put($cache_id, $text, 'articles');
}
$context['components']['boxes'] = $text;

// referrals, if any
$context['components']['referrals'] = Skin::build_referrals('actions/index.php');

// render the skin
render_skin();

?>
