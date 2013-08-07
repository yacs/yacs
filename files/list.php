<?php
/**
 * list files available for a given anchor
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
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'files.php';

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
	$anchor = Anchors::get($id);

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
load_skin('files', $anchor);

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'files/' => i18n::s('Files') );

// the title of the page
if(is_object($anchor) && ($title = $anchor->get_title()))
	$context['page_title'] = sprintf(i18n::s('Files: %s'), $title);
else
	$context['page_title'] = i18n::s('Files');

// an anchor is mandatory
if(!is_object($anchor)) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No anchor has been found.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('files/list.php?id='.$anchor->get_reference()));

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// stop hackers
} elseif($page > 10) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// display the index
} else {

	// insert anchor prefix and suffix, plus any available icon
	$context['prefix'] .= $anchor->get_prefix();

	$layout = Layouts::new_('decorated', 'file');

	// provide anthor information to layout
	if(is_object($layout))
		$layout->set_focus($anchor->get_reference());

	// the maximum number of files per page
	if(is_object($layout))
		$items_per_page = $layout->items_per_page();
	else
		$items_per_page = FILES_PER_PAGE;

	// the first file to list
	$offset = ($page - 1) * $items_per_page;
	if(is_object($layout) && method_exists($layout, 'set_offset'))
		$layout->set_offset($offset);

	// a navigation bar for these files
	if($count = Files::count_for_anchor($anchor->get_reference())) {
		$context['page_menu'] += array('_count' => sprintf(i18n::ns('%d file', '%d files', $count), $count));

		// navigation commands for files
		$prefix = Files::get_url($anchor->get_reference(), 'navigate');
		$context['page_menu'] += Skin::navigate($anchor->get_url('files'), $prefix, $count, $items_per_page, $page, FALSE);

		// list files by date or by title
		if($anchor->has_option('files_by') == 'title')
			$items = Files::list_by_title_for_anchor($anchor->get_reference(), $offset, $items_per_page, $anchor->get_reference());
		else
			$items = Files::list_by_date_for_anchor($anchor->get_reference(), $offset, $items_per_page, $anchor->get_reference());

		// actually render the html
		if(is_array($items))
			$context['text'] .= Skin::build_list($items, 'decorated');
		elseif(is_string($items))
			$context['text'] .= $items;

	}

	// insert anchor suffix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_suffix();

	// page menu
	//

	// get parent of the anchor too
	$parent = NULL;
	if(is_object($anchor) && ($parent = $anchor->get_parent()))
		$parent =&  Anchors::get($parent);

	// the command to post a new file, if this is allowed
	if(is_object($anchor) && Files::allow_creation($anchor->get_values(), $parent, $anchor->get_type())) {
		Skin::define_img('FILES_UPLOAD_IMG', 'files/upload.gif');
		$context['page_menu'][] = Skin::build_link(Files::get_url($anchor->get_reference(), 'file'), FILES_UPLOAD_IMG.i18n::s('Add a file'));
	}

	// command to go back
	if(is_object($anchor) && $anchor->is_viewable())
		$context['page_menu'][] = Skin::build_link($anchor->get_url(), i18n::s('Back to main page'), 'basic');

	// side tools
	//

	// the command to post a new file, if this is allowed
	if(is_object($anchor) && Files::allow_creation($anchor->get_values(), $parent, $anchor->get_type()))
		$context['page_tools'][] = Skin::build_link(Files::get_url($anchor->get_reference(), 'file'), i18n::s('Add a file'));

	// back to main page
	$context['page_tools'][] = Skin::build_link($anchor->get_url(), i18n::s('Back to main page'));

}

// render the skin
render_skin();

?>
