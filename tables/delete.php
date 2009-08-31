<?php
/**
 * delete a table
 *
 * This script calls for confirmation, then actually deletes the table.
 * The script updates the database, then redirects to the referer URL, or to the index page.
 *
 * Only associates can delete tables.
 *
 * Accept following invocations:
 * - delete.php/12
 * - delete.php?id=12
 *
 * If the anchor for this item specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'tables.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Tables::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor =& Anchors::get($item['anchor']);

// only associates can proceed
if(Surfer::is_associate())
	$permitted = TRUE;

// the default is to deny access
else
	$permitted = FALSE;

// load the skin, maybe with a variant
load_skin('tables', $anchor);

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'tables/' => i18n::s('Tables') );

// the title of the page
$context['page_title'] = i18n::s('Delete a table');

// not found
if(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// deletion is confirmed
} elseif(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'yes')) {

	// touch the related anchor before actual deletion, since the table has to be accessible at that time
	if(is_object($anchor))
		$anchor->touch('table:delete', $item['id']);

	// delete and go back to the anchor or to the index page
	if(Tables::delete($item['id'])) {
		Tables::clear($item);
		if(is_object($anchor))
			Safe::redirect($context['url_to_home'].$context['url_to_root'].$anchor->get_url());
		else
			Safe::redirect($context['url_to_home'].$context['url_to_root'].'articles/');
	}

// deletion has to be confirmed
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST'))
	Logger::error(i18n::s('The action has not been confirmed.'));

// ask for confirmation
else {

	// commands
	$menu = array();
	$menu[] = Skin::build_submit_button(i18n::s('Yes, I want to delete this table'), NULL, NULL, 'confirmed');
	if(is_object($anchor))
		$menu[] = Skin::build_link($anchor->get_url(), i18n::s('Cancel'), 'span');

	// the submit button
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
		.Skin::finalize_list($menu, 'menu_bar')
		.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n"
		.'<input type="hidden" name="confirm" value="yes" />'."\n"
		.'</p></form>'."\n";

	// set the focus
	$context['text'] .= JS_PREFIX
		.'// set the focus on first form field'."\n"
		.'$("confirmed").focus();'."\n"
		.JS_SUFFIX."\n";

	// the title of the table
	if(isset($item['title']))
		$context['text'] .= Skin::build_block($item['title'], 'title');

	// display the full text
	$context['text'] .= Skin::build_block($item['description'], 'description');

	// execute the query string to build the table
	if(isset($item['query']) && $item['query'])
		$context['text'] .= Tables::build($item['id'], 'sortable');

	// display the query string, if any
	if(isset($item['query']) && $item['query'])
		$context['text'] .= BR.'<pre>'.$item['query'].'</pre>'.BR."\n";

	// details
	$details = array();

	// information on uploader
	if(Surfer::is_member() && $item['edit_name'])
		$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

	// all details
	if($details)
		$context['text'] .= '<p class="details">'.ucfirst(implode(', ', $details)).'</p>'."\n";

}

// render the skin
render_skin();

?>