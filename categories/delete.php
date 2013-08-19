<?php
/**
 * delete a category.
 *
 * This script calls for confirmation, then actually deletes a category.
 *
 * Restrictions apply on this page:
 * - associates and authenticated editors are allowed to move forward
 * - permission denied is the default
 *
 * Accept following invocations:
 * - delete.php/12
 * - delete.php?id=12
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'categories.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item = Categories::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor = Anchors::get($item['anchor']);

// get the related overlay, if any
$overlay = NULL;
if(isset($item['overlay']))
	$overlay = Overlay::load($item, 'category:'.$item['id']);

// associates and authenticated editors can do what they want
if(Surfer::is_associate() || (Surfer::is_member() && is_object($anchor) && $anchor->is_assigned()))
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load the skin
load_skin('categories');

// the path to this page
if(is_object($anchor)&& $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'categories/' => i18n::s('Categories') );
if(isset($item['id']))
	$context['path_bar'] = array_merge($context['path_bar'], array(Categories::get_permalink($item) => $item['title'] ));

// the title of the page
if(isset($item['title']))
	$context['page_title'] = sprintf(i18n::s('%s: %s'), i18n::s('Delete'), $item['title']);

// not found
if(!isset($item['id'])) {
	include '../error.php';

// permission denied
} elseif(!$permitted) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// deletion is confirmed
} elseif(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'yes')) {

	// touch the related anchor before actual deletion, since the image has to be accessible at that time
	if(is_object($anchor))
		$anchor->touch('category:delete', $item['id']);

	// attempt to delete
	if(Categories::delete($item['id'])) {

		// log item deletion
		$label = sprintf(i18n::c('Deletion: %s'), strip_tags($item['title']));
		$description = Categories::get_permalink($item);
		Logger::remember('categories/delete.php: '.$label, $description);

		// this can appear anywhere
		Cache::clear();

		// back to the anchor page or to the index page
		if(is_object($anchor))
			Safe::redirect($anchor->get_url());
		else
			Safe::redirect($context['url_to_home'].$context['url_to_root'].'categories/');
	}

// deletion has to be confirmed
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST'))
	Logger::error(i18n::s('The action has not been confirmed.'));

// the form
else {

	// commands
	$menu = array();
	$menu[] = Skin::build_submit_button(i18n::s('Yes, I want to delete this category'), NULL, NULL, 'confirmed');
	if(isset($item['id']))
		$menu[] = Skin::build_link(Categories::get_permalink($item), i18n::s('Cancel'), 'span');

	// the submit button
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
		.Skin::finalize_list($menu, 'menu_bar')
		.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n"
		.'<input type="hidden" name="confirm" value="yes" />'."\n"
		.'</p></form>'."\n";

	// set the focus
	Page::insert_script('$("#confirmed").focus();');

	// the title of the category
	if($item['title'])
		$context['text'] .= Skin::build_block($item['title'], 'title');

	// the introduction text, if any
	$context['text'] .= '<div style="margin: 1em 0;">'.Codes::beautify($item['introduction']).'</div>'."\n";

	// get text related to the overlay, if any
	if(is_object($overlay))
		$context['text'] .= $overlay->get_text('view', $item);

	// details
	$details = array();

	// information on last editor
	if($item['edit_name'])
		$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

	// hits
	if($item['hits'] > 1)
		$details[] = Skin::build_number($item['hits'], i18n::s('hits'));

	// all details
	if(@count($details))
		$context['text'] .= '<p class="details">'.ucfirst(implode(', ', $details))."</p>\n";

	// count items related to this category
	$context['text'] .= Anchors::stat_related_to('category:'.$item['id'], i18n::s('Following items are attached to this record and will be deleted as well.'));

}

// render the skin
render_skin();

?>
