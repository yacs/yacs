<?php
/**
 * delete a form
 *
 * This script calls for confirmation, then actually deletes the form.
 * The script updates the database, then redirects to the referer URL, or to the index page.
 *
 * This page is to be used only by associates.
 *
 * Accepted calls:
 * - delete.php/12
 * - delete.php?id=12
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'forms.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item = Forms::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor = Anchors::get($item['anchor']);

// load the skin
load_skin('forms');

// the path to this page
$context['path_bar'] = array( 'forms/' => i18n::s('Forms') );

// the title of the page
if(isset($item['title']))
	$context['page_title'] = sprintf(i18n::s('%s: %s'), i18n::s('Delete'), $item['title']);

// not found
if(!isset($item['id'])) {
	include '../error.php';

// deletion is restricted to associates
} elseif(!Surfer::is_associate()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// deletion is confirmed
} elseif(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'yes')) {

	// delete and go back to the index page
	if(Forms::delete($item['id'])) {
		Forms::clear($item);
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'forms/');
	}

// deletion has to be confirmed
} elseif(isset($_form['REQUEST_METHOD']) && ($_form['REQUEST_METHOD'] == 'POST')) {
	Logger::error(i18n::s('The action has not been confirmed.'));

}

// deletion is restricted to associates
if($item['id'] && Surfer::is_associate()) {

	// the submit button
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
		.Skin::finalize_list(array(
			Skin::build_submit_button(i18n::s('Yes, I want to suppress this form'), NULL, NULL, 'confirmed'),
			Skin::build_link(Forms::get_url($item['id'], 'view', $item['title']), i18n::s('Cancel'), 'span')
			), 'menu_bar')
		.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n"
		.'<input type="hidden" name="confirm" value="yes" />'."\n"
		.'</p></form>'."\n";

	// set the focus
	$context['text'] .= JS_PREFIX
		.'// set the focus on first form field'."\n"
		.'$("#confirmed").focus();'."\n"
		.JS_SUFFIX."\n";

	// the title of the form
	$context['text'] .= Skin::build_block($item['title'], 'title');

	// the nick name
	if($item['host_name'] && Surfer::is_associate())
		$details[] = '"'.$item['host_name'].'"';

	// information on last update
	if($item['edit_name'])
		$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

	// restricted to logged members
	if($item['active'] == 'R')
		$details[] = RESTRICTED_FLAG.i18n::s('Community - Access is granted to any identified surfer').BR."\n";

	// restricted to associates
	elseif($item['active'] == 'N')
		$details[] = PRIVATE_FLAG.i18n::s('Private - Access is restricted to selected persons').BR."\n";

	// all details
	$context['text'] .= '<p class="details">'.ucfirst(implode(', ', $details))."</p>\n";

	// the related anchor
	if(is_object($anchor))
		$text .= '<p>'.sprintf(i18n::s('Related to %s'), Skin::build_link($anchor->get_url(), $anchor->get_title(), 'section'))."</p>\n";

	// show description
	$context['text'] .= Skin::build_block($item['description'], 'description');

}

// render the skin
render_skin();

?>
