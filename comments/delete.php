<?php
/**
 * delete a comment
 *
 * This script calls for confirmation, then actually deletes the comment.
 *
 * This page is to be used by associates and editors only, while they are editing comments.
 * The script updates the database, then redirects to the referer URL, or to the index page.
 *
 * Restrictions apply on this page:
 * - associates and authenticated editors are allowed to move forward
 * - permission is denied if the anchor is not viewable by this surfer
 * - permission is granted if the anchor is the profile of this member
 * - authenticated users may suppress their own posts
 * - else permission is denied
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
include_once 'comments.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Comments::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor =& Anchors::get($item['anchor']);

// load the skin, maybe with a variant
load_skin('comments', $anchor);

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'comments/' => i18n::s('Threads') );

// the title of the page
if(is_object($overlay))
	$context['page_title'] = $overlay->get_label('delete_title', 'comments');
if(!$context['page_title'])
	$context['page_title'] = i18n::s('Delete a comment');

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// not found
} elseif(!isset($item['id'])) {
	include '../error.php';

// permission denied
} elseif(!Comments::allow_modification($anchor, $item)) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// deletion is confirmed
} elseif(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'yes')) {

	// touch the related anchor before actual deletion, since the item has to be accessible at that time
	if(is_object($anchor))
		$anchor->touch('comment:delete', $item['id']);

	// if no error, back to the anchor or to the index page
	if(Comments::delete($item['id'])) {
		Comments::clear($item);
		if(is_object($anchor))
			Safe::redirect($context['url_to_home'].$context['url_to_root'].$anchor->get_url('comments'));
		else
			Safe::redirect($context['url_to_home'].$context['url_to_root'].'comments/');
	}

// deletion has to be confirmed
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST'))
	Logger::error(i18n::s('The action has not been confirmed.'));

// ask for confirmation
else {

	// commands
	$menu = array();
	$delete_label = '';
	if(is_object($overlay))
		$delete_label = $overlay->get_label('delete_confirmation', 'comments');
	if(!$delete_label)
		$delete_label = i18n::s('Yes, I want to delete this comment');
	$menu[] = Skin::build_submit_button($delete_label, NULL, NULL, 'confirmed');
	if(isset($item['id']))
		$menu[] = Skin::build_link(Comments::get_url($item['id']), i18n::s('Cancel'), 'span');

	// the submit button
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
		.Skin::finalize_list($menu, 'menu_bar')
		.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n"
		.'<input type="hidden" name="confirm" value="yes" />'."\n"
		.'</p></form>'."\n";

	// set the focus
	$context['text'] .= JS_PREFIX
		.'// set the focus on first form field'."\n"
		.'$("#confirmed").focus();'."\n"
		.JS_SUFFIX;

	// display the full comment
	$context['text'] .= '<div style="margin: 1em 0;">'.Codes::beautify($item['description']).'</div>'."\n";

	// details
	$details = array();

	// the poster of this comment
	$details[] = sprintf(i18n::s('by %s %s'), Users::get_link($item['create_name'], $item['create_address'], $item['create_id']), Skin::build_date($item['create_date']));

	// the last edition of this comment
	if($item['create_name'] != $item['edit_name'])
		$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

	// the complete details
	if($details)
		$context['text'] .= '<p class="details">'.ucfirst(implode(', ', $details))."</p>\n";

}

// render the skin
render_skin();

?>
