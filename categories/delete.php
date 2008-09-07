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
$item =& Categories::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor =& Anchors::get($item['anchor']);

// associates and authenticated editors can do what they want
if(Surfer::is_associate() || (Surfer::is_member() && is_object($anchor) && $anchor->is_editable()))
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
	$context['page_title'] = sprintf(i18n::s('Delete: %s'), $item['title']);

// not found
if(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// deletion is confirmed
} elseif(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'yes')) {

	// touch the related anchor before actual deletion, since the image has to be accessible at that time
	if(is_object($anchor))
		$anchor->touch('category:delete', $item['id'], TRUE);

	// attempt to delete
	if(Categories::delete($item['id'])) {

		// this can appear anywhere
		Cache::clear();

		// back to the anchor page or to the index page
		if(is_object($anchor))
			Safe::redirect($context['url_to_home'].$context['url_to_root'].$anchor->get_url());
		else
			Safe::redirect($context['url_to_home'].$context['url_to_root'].'categories/');
	}

// deletion has to be confirmed
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST'))
	Skin::error(i18n::s('The deletion has not been confirmed.'));

// the form
else {

	// the submit button
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
		.Skin::build_submit_button(i18n::s('Yes, I want to delete this category'), NULL, NULL, 'confirmed')."\n"
		.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n"
		.'<input type="hidden" name="confirm" value="yes" />'."\n"
		.'</p></form>'."\n";

	// set the focus
	$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
		.'// set the focus on first form field'."\n"
		.'$("confirmed").focus();'."\n"
		.'// ]]></script>'."\n";

	// the title of the category
	if($item['title'])
		$context['text'] .= Skin::build_block($item['title'], 'title');

	// information on last editor
	if($item['edit_name'])
		$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

	// hits
	if($item['hits'] > 1)
		$details[] = Skin::build_number($item['hits'], i18n::s('hits'));

	// all details
	if(@count($details))
		$context['text'] .= '<p class="details">'.ucfirst(implode(', ', $details))."</p>\n";

	// the introduction text, if any
	$context['text'] .= Skin::build_block($item['introduction'], 'introduction');

	// main text
	$context['text'] .= Skin::build_block($item['description'], 'description');

	// count items related to this category
	$context['text'] .= Anchors::stat_related_to('category:'.$item['id'], i18n::s('Following items are attached to this record and will be deleted as well.'));

}

// render the skin
render_skin();

?>