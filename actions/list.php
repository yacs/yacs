<?php
/**
 * list actions for one anchor
 *
 * @todo reference this page from user profile
 *
 * This script has a first section with on-going actions, then two additional sections
 * to list completed and rejected actions.
 *
 * Links are provided to page in completed actions only. We have assumed, for simplicity,
 * that the number of on-going actions what kept minimum, and that the rejected actions were exceptions.
 * Therefore, we should not have long lists of these kinds of actions.
 *
 * Access is granted only if the surfer is allowed to view the anchor page.
 *
 * Accepted calls:
 * - list.php/12 -- list of last actions for user #12
 * - list.php?id=12
 * - list.php/user/12 -- list of last actions for given anchor (more generally: list.php/&lt;type&gt;/&lt;id&gt;)
 * - list.php?anchor=user:12 (or, more generally: list.php?anchor=&lt;type&gt;:&lt;id&gt;)
 *
 * If the anchor for this item specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'actions.php';

// look for the target anchor on item creation
$target_anchor = NULL;
if(isset($_REQUEST['anchor']))
	$target_anchor = $_REQUEST['anchor'];
elseif(isset($_REQUEST['id']))
	$target_anchor = 'user:'.$_REQUEST['id'];
elseif(isset($context['arguments'][1]))
	$target_anchor = $context['arguments'][0].':'.$context['arguments'][1];
elseif(isset($context['arguments'][0]))
	$target_anchor = 'user:'.$context['arguments'][0];
$target_anchor = strip_tags($target_anchor);

// get the related anchor, if any
$anchor = NULL;
if($target_anchor)
	$anchor =& Anchors::get($target_anchor);

// which page should be displayed
$page = 1;
if(isset($_REQUEST['page']))
	$page = $_REQUEST['page'];
elseif(isset($context['arguments'][0]))
	$page = $context['arguments'][0];
$page = strip_tags($page);

// the anchor has to be viewable by this surfer
if(!is_object($anchor) || $anchor->is_viewable())
	$permitted = TRUE;
else
	$permitted = FALSE;

// load the skin, maybe with a variant
load_skin('actions', $anchor);

// items per page
$items_per_page = 50;

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'actions/' => i18n::s('Actions') );

// the title of the page
if(is_object($anchor) && $anchor->is_viewable())
	$context['page_title'] = $anchor->get_title();
else
	$context['page_title'] = i18n::s('Actions');

// the command to create a new action is available to associates, editors and target member
if(Surfer::is_associate()
	|| (is_object($anchor) && $anchor->is_editable())
	|| (Surfer::is_member() && ($target_anchor == 'user:'.Surfer::get_id()))) {

	if($context['with_friendly_urls'] == 'Y')
		$link = 'actions/edit.php/'.str_replace(':', '/', $target_anchor);
	else
		$link = 'actions/edit.php?anchor='.$target_anchor;
	$context['page_menu'] = array_merge($context['page_menu'], array( $link => i18n::s('Add an action') ));
}

// an anchor is mandatory
if(!is_object($anchor)) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No anchor has been found.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('actions/list.php?anchor='.$target_anchor));

	// permission denied to authenticated user
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// display the index
} else {

	//
	// section with on-going actions, but only at the first page
	//
	if($page == 1) {

		// cache the section
		$cache_id = 'actions/list.php?anchor='.$target_anchor.'#on-going';
		if(!$text =& Cache::get($cache_id)) {

			// query the database and layout that stuff
			$box['bar'] = array();
			$box['text'] = '';

			$offset = ($page - 1) * $items_per_page;
			if($box['text'] = Actions::list_by_date_for_anchor($target_anchor, $offset, $items_per_page, 'full')) {

				// we have an array to format
				if(is_array($box['text']))
					$box['text'] =& Skin::build_list($box['text'], 'decorated');

				// make a section
				$text =& Skin::build_box(i18n::s('On-going actions'), $box['text']);

			}

			// cache this to speed subsequent queries
			Cache::put($cache_id, $text, 'actions');
		}
		$context['text'] .= $text;

	}

	//
	// section with completed actions
	//

	// cache the section
	$cache_id = 'actions/list.php?anchor='.$target_anchor.'#completed#'.$page;
	if(!$text =& Cache::get($cache_id)) {

		// section title
		$box['bar'] = array();
		$box['text'] = '';

		$box['title'] = i18n::s('Completed actions');

		// completed actions
		$stats = Actions::stat_for_anchor($target_anchor);
		if($stats['count'] > $items_per_page) {

			// count actions in the database
			$box['bar'] = array_merge($box['bar'], array('_count' => sprintf(i18n::ns('%d action', '%d actions', $stats['count']), $stats['count'])));

			// navigation commands for actions, if necessary
			$home = 'actions/list.php';
			if($context['with_friendly_urls'] == 'Y')
				$prefix = $home.'/'.str_replace(':', '/', $target_anchor);
			elseif($context['with_friendly_urls'] == 'R')
				$prefix = $home.'/'.str_replace(':', '/', $target_anchor);
			else
				$prefix = $home.'?anchor='.$target_anchor.'&page=';
			$box['bar'] = array_merge($box['bar'], Skin::navigate($home, $prefix, $stats['count'], $items_per_page, $page));
		}

		// query the database and layout that stuff
		$offset = ($page - 1) * $items_per_page;
		if($text = Actions::list_completed_for_anchor($target_anchor, $offset, $items_per_page, 'full')) {

			// we have an array to format
			if(is_array($text))
				$text =& Skin::build_list($text, 'decorated');

			// layout everything in a box
			if(count($box['bar']))
				$text =& Skin::build_list($box['bar'], 'menu_bar')."\n".$text;
			$text =& Skin::build_box($box['title'], $text);

		}

		// cache this to speed subsequent queries
		Cache::put($cache_id, $text, 'actions');
	}
	$context['text'] .= $text;

	//
	// section with rejected actions, but only at the first page
	//
	if($page == 1) {

		// cache the section
		$cache_id = 'actions/list.php?anchor='.$target_anchor.'#rejected';
		if(!$text =& Cache::get($cache_id)) {

			// query the database and layout that stuff
			$box['bar'] = array();
			$box['text'] = '';

			$offset = ($page - 1) * $items_per_page;
			if($box['text'] = Actions::list_rejected_for_anchor($target_anchor, $offset, $items_per_page, 'full')) {

				// we have an array to format
				if(is_array($box['text']))
					$box['text'] =& Skin::build_list($box['text'], 'decorated');

				// make a section
				$text =& Skin::build_box(i18n::s('Rejected actions'), $box['text']);

			}

			// cache this to speed subsequent queries
			Cache::put($cache_id, $text, 'actions');
		}
		$context['text'] .= $text;

	}

}

// render the skin
render_skin();

?>