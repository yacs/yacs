<?php
/**
 * list decisions available for a given anchor
 *
 * This pages features various elements, depending of the layout selected for the anchor.
 *
 * Following principles have been selected for the overall layout:
 * - This script is used to list every decisions, and the anchor page ([script]articles/view.php[/script])
 * only has a 'Annotate this page' link.
 * - The link to decisions is actually the link to this page (e.g., 'decisions/list.php/article/123').
 * - Page 1 (e.g., 'decisions/view.php/article/123') lists decisions 1..DECISIONS_PER_PAGE, etc.
 * - Each decision features links to Edit and Delete, taking proper access restrictions into account.
 * - The link to add a decision is labelled 'Add your own decision'.
 *
 *
 * The permission assessment is based upon following rules applied in the provided orders:
 * - the anchor may disallow the action
 * - associates and editors are allowed to move forward
 * - permission is granted if the anchor is viewable
 * - permission denied is the default
 *
 * Accepted calls:
 * - list.php?anchor=article:&lt;id&gt;
 * - list.php/&lt;article&gt;/&lt;id&gt;
 *
 * If the anchor for this item specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'decisions.php';

// parameters transmitted through friendly urls
if(isset($context['arguments'][0]) && isset($context['arguments'][1]))
	$target_anchor = $context['arguments'][0].':'.$context['arguments'][1];

// or usual parameters
elseif(isset($_REQUEST['anchor']))
	$target_anchor = $_REQUEST['anchor'];

// fight hackers
$target_anchor = strip_tags($target_anchor);

// get the anchor
$anchor = NULL;
if($target_anchor)
	$anchor = Anchors::get($target_anchor);

// load localized strings
i18n::bind('decisions');

// load the skin, maybe with a variant
load_skin('decisions', $anchor);

// which page should be displayed
if(isset($_REQUEST['page']))
	$page = $_REQUEST['page'];
if(!isset($page) && isset($context['arguments'][2]))
	$page = $context['arguments'][2];
if(!isset($page))
	$page = 1;
$page = strip_tags($page);

// ensure editors have the same rights than associates
if(is_object($anchor) && $anchor->is_editable())
	Surfer::empower();

// the anchor may control the script
if(is_object($anchor) && is_callable(array($anchor, 'allows')) && !$anchor->allows('decision', 'list'))
	$permitted = FALSE;

// associates and editors can do what they want
elseif(Surfer::is_associate() || (is_object($anchor) && $anchor->is_editable()))
	$permitted = TRUE;

// the anchor has to be viewable by this surfer
elseif(is_object($anchor) && $anchor->is_viewable())
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'decisions/' => i18n::s('Decisions') );

// the title of the page
if(is_object($anchor) && $anchor->is_viewable())
	$context['page_title'] = sprintf(i18n::s('Decisions: %s'), $anchor->get_title());
else
	$context['page_title'] = i18n::s('List decisions');

// command to go back
if(is_object($anchor) && $anchor->is_viewable())
	$context['page_menu'] = array( $anchor->get_url() => i18n::s('Back to main content') );

// an anchor is mandatory
if(!is_object($anchor)) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No anchor has been found.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('decisions/list.php?anchor='.$anchor->get_reference()));

	// permission denied to authenticated user
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// provide buttons only if page is not locked
//} elseif(!isset($item['id']) && (is_object($anchor) && $anchor->has_option('locked')) && !Surfer::is_associate()) {

// display the index
} else {

	// insert anchor prefix and suffix
	$context['prefix'] .= $anchor->get_prefix();

	// overall results
	$cache_id = 'decisions/list.php?anchor='.$target_anchor.'#results';
	if(!$text =& Cache::get($cache_id)) {

		// get results from the database
		list($total, $yes, $no) = Decisions::get_results_for_anchor($anchor->get_reference());

		// show results as percentage
		if($total)
			$text = sprintf(i18n::s('%s approvals (%d%%), %s rejections (%d%%)'), $yes, (int)($yes*100/$total), $no, (int)($no*100/$total));


		// cache this to speed subsequent queries
		Cache::put($cache_id, $text, 'decisions');
	}
	$context['text'] .= '<p>'.$text.'</p>';


	// some introductory text to the anchor
	$context['text'] .= $anchor->get_teaser('teaser');

	// cache the section
	$cache_id = 'decisions/list.php?anchor='.$target_anchor.'#'.$page;
	if(!$text =& Cache::get($cache_id)) {

		// thread starts here
		$home_link = Decisions::get_url($anchor->get_reference(), 'list');

		$layout = 'no_anchor';

		// the maximum number of decisions per page
		if(is_object($layout))
			define('DECISIONS_PER_PAGE', $layout->items_per_page());
		else
			define('DECISIONS_PER_PAGE', 40);

		// the first decision to list
		$offset = ($page - 1) * DECISIONS_PER_PAGE;
		if(is_object($layout) && method_exists($layout, 'set_offset'))
			$layout->set_offset($offset);

		// build a complete box
		$box['bar'] = array();
		$box['text'] = '';

		// a navigation bar for these decisions
		include_once '../decisions/decisions.php';
		$stats = Decisions::stat_for_anchor($anchor->get_reference());
		if($stats['count'] > DECISIONS_PER_PAGE)
			$box['bar'] = array_merge($box['bar'], array('_count' => sprintf(i18n::ns('1&nbsp;decision', '%d&nbsp;decisions', $stats['count']), $stats['count'])));

		// list decisions by date
		$items = Decisions::list_by_date_for_anchor($anchor->get_reference(), $offset, DECISIONS_PER_PAGE, $layout);

		// actually render the html
		if(is_array($items))
			$box['text'] .= Skin::build_list($items, 'rows');
		elseif(is_string($items))
			$box['text'] .= $items;

		// navigation commands for decisions
		$prefix = Decisions::get_url($anchor->get_reference(), 'navigate');
		$box['bar'] = array_merge($box['bar'],
			Skin::navigate($home_link, $prefix, $stats['count'], DECISIONS_PER_PAGE, $page, FALSE));

		// show commands
		if(@count($box['bar']) && ($context['skin_variant'] != 'mobile')) {
			$menu_bar =& Skin::build_list($box['bar'], 'menu_bar');

			// append the menu bar at the end
			if($box['text'] && (strlen($box['text']) > 128))
				$box['text'] .= $menu_bar;

			// insert the menu bar at the beginning
			$box['text'] = $menu_bar.$box['text'];

		}

		// build a box
		if($box['text'])
			$text =& Skin::build_box('', $box['text'], 'section', 'decisions');

		// cache this to speed subsequent queries
		Cache::put($cache_id, $text, 'decisions');
	}
	$context['text'] .= $text;

	// insert anchor suffix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_suffix();

}

// render the skin
render_skin();

?>