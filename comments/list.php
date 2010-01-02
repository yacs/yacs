<?php
/**
 * list comments available for a given anchor
 *
 * This page features various elements, depending of the layout selected for the anchor.
 *
 * Following principles have been selected for threads that start at anchor page:
 * - The anchor page ([script]articles/view.php[/script]) features a first set of comments (1..COMMENTS_PER_PAGE).
 * Therefore, this script lists following pages of comments (page 2, 3, etc.).
 * - The link to comments is actually the link to the anchor page (e.g., 'articles/view.php/123#comments').
 * - Page 1 (e.g., 'comments/view.php/article/123') lists comments COMMENTS_PER_PAGE+1..2*COMMENTS_PER_PAGE.
 * - Each comment features links to Reply, Quote, Edit and Delete, taking proper access restrictions into account.
 * - The link to add a comment is labelled 'Add your own comment'.
 *
 * This applies to following layouts:
 * - compact
 * - daily
 * - decorated
 * - jive
 * - manual
 * - table
 * - wiki
 * - yabb
 *
 * Following principles have been selected for other layouts:
 * - This script is used to list every comments, and the anchor page ([script]articles/view.php[/script])
 * only has a 'Discuss this page (### comments)' links.
 * - The link to comments is actually the link to this page (e.g., 'comments/list.php/article/123').
 * - Page 1 (e.g., 'comments/view.php/article/123') lists comments 1..COMMENTS_PER_PAGE, etc.
 * - Each comment features links to Reply, Quote, Edit and Delete, taking proper access restrictions into account.
 * - The link to add a comment is labelled 'Add your own comment'.
 *
 * This applies to following layouts:
 * - alistapart
 *
 * Access is granted only if the surfer is allowed to view the anchor page.
 *
 * Accepted calls:
 * - list.php?id=article:&lt;id&gt;
 * - list.php/&lt;article&gt;/&lt;id&gt;
 *
 * If the anchor for this item specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Agnes
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'comments.php';

// look for the target anchor
$id = NULL;
if(isset($context['arguments'][0]) && isset($context['arguments'][1]))
	$id = $context['arguments'][0].':'.$context['arguments'][1];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
elseif(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
if(strpos($id, ':') === FALSE)
	$id = 'article:'.$id;
$id = strip_tags($id);

// get the anchor
$anchor = NULL;
if($id)
	$anchor =& Anchors::get($id);

// which page should be displayed
if(isset($_REQUEST['page']))
	$page = $_REQUEST['page'];
elseif(isset($context['arguments'][2]))
	$page = $context['arguments'][2];
else
	$page = 1;
$page = max(1,intval($page));

// the anchor has to be viewable by this surfer
if(!is_object($anchor) || $anchor->is_viewable())
	$permitted = TRUE;
else
	$permitted = FALSE;

// load the skin, maybe with a variant
load_skin('comments', $anchor);

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'comments/' => i18n::s('Comments') );

// the title of the page
if(is_object($anchor) && $anchor->is_viewable()) {
	$title = $anchor->get_title();;
	$context['page_title'] = $anchor->get_label('comments', 'list_title', $title);
} else
	$context['page_title'] = i18n::s('Comments');

// an anchor is mandatory
if(!is_object($anchor)) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No anchor has been found.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('comments/list.php?id='.$anchor->get_reference()));

	// permission denied to authenticated user
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// stop hackers
} elseif($page > 10) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// display the index
} else {

	// insert anchor prefix and suffix, plus any available icon
	$context['prefix'] .= $anchor->get_prefix();

	// some introductory text to the anchor
	$context['text'] .= $anchor->get_teaser('teaser');

	// cache the section
	$cache_id = 'comments/list.php?id='.$anchor->get_reference().'#'.$page;
	if(!$text =& Cache::get($cache_id)) {

		// get a layout from anchor
		$layout =& Comments::get_layout($anchor);

		// provide author information to layout
		if(is_object($layout) && is_object($anchor) && isset($anchor->item['create_id']))
			$layout->set_variant('user:'.$anchor->item['create_id']);

		// the maximum number of comments per page
		if(is_object($layout))
			$items_per_page = $layout->items_per_page();
		else
			$items_per_page = COMMENTS_PER_PAGE;

		// the first comment to list
		$offset = ($page - 1) * $items_per_page;
		if(is_object($layout) && method_exists($layout, 'set_offset'))
			$layout->set_offset($offset);

		// reverse order
		$reverted = FALSE;
		if(is_object($anchor) && $anchor->has_option('comments_as_wall'))
			$reverted = TRUE;

		// build a complete box
		$box['bar'] = array();
		$box['text'] = '';

		// a navigation bar for these comments
		include_once '../comments/comments.php';
		if($count = Comments::count_for_anchor($anchor->get_reference())) {
			if($count > 1) {
				$box['bar'] += array('_count' => $count.' '.$anchor->get_label('comments', 'count_many'));
			} elseif($count == 1) {
				$box['bar'] += array('_count' => '1 '.$anchor->get_label('comments', 'count_one'));
			}

			// navigation commands for comments
			$prefix = Comments::get_url($anchor->get_reference(), 'navigate');
			$box['bar'] = array_merge($box['bar'],
				Skin::navigate($anchor->get_url('comments'), $prefix, $count, $items_per_page, $page, FALSE));

			// list comments by date
			$items = Comments::list_by_date_for_anchor($anchor->get_reference(), $offset, $items_per_page, $layout, $reverted);

			// actually render the html
			if(is_array($items))
				$box['text'] .= Skin::build_list($items, 'rows');
			elseif(is_string($items))
				$box['text'] .= $items;

		}

		// the command to post a new comment, if this is allowed
		if(Comments::are_allowed($anchor)) {
			Skin::define_img('COMMENTS_ADD_IMG', 'comments/add.gif');
			$box['bar'] = array_merge($box['bar'],
				array( Comments::get_url($anchor->get_reference(), 'comment') => COMMENTS_ADD_IMG.$anchor->get_label('comments', 'new_command') ));
		}

		// show commands
		if(@count($box['bar'])) {

			// append the menu bar at the end
			if((strlen($box['text']) > 10) && $count)
				$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');

			// shortcut to last comment in page
			if(is_object($layout) && ($count > 3)) {

				$box['bar'] += array('#last_comment' => i18n::s('Page bottom'));

				$box['text'] .= '<span id="last_comment" />';
			}

			// insert the menu bar at the beginning
			$box['text'] = Skin::build_list($box['bar'], 'menu_bar').$box['text'];

		}

		// build a box
		if($box['text'])
			$text =& Skin::build_box('', $box['text'], 'header1', 'comments');

		// cache this to speed subsequent queries
		Cache::put($cache_id, $text, 'comments');
	}
	$context['text'] .= $text;

	// command to go back
	if(is_object($anchor) && $anchor->is_viewable()) {
		$menu = array();
		if($anchor->has_layout('alistapart'))
			$menu[] = Skin::build_link( $anchor->get_url('parent'), i18n::s('Done'), 'button' );
		else
			$menu[] = Skin::build_link( $anchor->get_url(), i18n::s('Done'), 'button' );
		$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');
	}

	// insert anchor suffix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_suffix();

}

// render the skin
render_skin();

?>