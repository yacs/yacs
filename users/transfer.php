<?php
/**
 * transfer ownership from one user to another one
 *
 * This script can be used by a site associate when a person leaves the organization,
 * and when another person is taking over.
 *
 * Of course, only associates can proceed.
 *
 * Accepted calls:
 * - own.php/&lt;id&gt;
 * - own.php?id=&lt;id&gt;
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Users::get($id);

// load the skin
load_skin('users');

// the path to this page
$context['path_bar'] = array( 'users/' => i18n::s('People') );

// page title
if(isset($item['nick_name']))
	$context['page_title'] .= sprintf(i18n::s('Transfer ownership of %s'), $item['nick_name']);

// for associates only
if(!Surfer::is_associate()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// not found
} elseif(!isset($item['id'])) {
	include '../error.php';

// rerport on some error
} elseif(count($context['error']))
	;

// do the job
elseif(isset($_REQUEST['assigned_name']) && ($user = Users::get($_REQUEST['assigned_name']))) {

		$context['text'] .= '<p>'.sprintf(i18n::s('Changing ownership to %s'), $user['nick_name']).'...';

		// change all sections at once
		$query = "UPDATE ".SQL::table_name('sections')." SET owner_id = ".$user['id']." WHERE owner_id = ".$item['id'];
		if($count = SQL::query($query))
			$context['text'] .= BR.sprintf(i18n::s('%d sections have been updated'), $count);

		// change all pages at once
		$query = "UPDATE ".SQL::table_name('articles')." SET owner_id = ".$user['id']." WHERE owner_id = ".$item['id'];
		if($count = SQL::query($query))
			$context['text'] .= BR.sprintf(i18n::s('%d articles have been updated'), $count);

		// change editor records
		$query = "UPDATE ".SQL::table_name('members')." SET anchor = 'user:".$user['id']."' WHERE anchor LIKE 'user:".$item['id']."'";
		if($count = SQL::query($query))
			$context['text'] .= BR.sprintf(i18n::s('%d editor assignments have been updated'), $count);

		// change watch lists
		$query = "UPDATE ".SQL::table_name('members')." SET member = 'user:".$user['id']."'"
			.", member_type = 'user', member_id = ".$user['id']." WHERE member LIKE 'user:".$item['id']."'";
		if($count = SQL::query($query))
			$context['text'] .= BR.sprintf(i18n::s('%d watching assignments have been updated'), $count);

	// back to the anchor page
	$links = array();
	$links[] = Skin::build_link(Users::get_permalink($item), i18n::s('Done'), 'button');
	$context['text'] .= Skin::finalize_list($links, 'assistant_bar');

// ask for the new owner
} else {

	// delegate to another person
	$context['text'] .= '<p style="margin-top: 2em;">'.i18n::s('To transfer ownership to another person, type some letters of the name you are looking for.').'</p>';

	// the form to link additional users
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'
		.'<input type="text" name="assigned_name" id="name" size="45" maxlength="255" />'
		.'<input type="hidden" name="id" value="'.encode_field($item['id']).'">'
		.'<input type="hidden" name="action" value="set">'
		.'</p></form>'."\n";

	// enable autocompletion
	$context['text'] .= JS_PREFIX
		."\n"
		.'// set the focus on first form field'."\n"
		.'$(document).ready( function() { $("#name").focus() });'."\n"
		."\n"
		.'// enable name autocompletion'."\n"
		.'$(document).ready( function() {'."\n"
		.' Yacs.autocomplete_names("#name",true);'."\n"
		.'});  '."\n"
		.JS_SUFFIX;

	// back to the anchor page
	$links = array();
	$links[] = Skin::build_link(Users::get_permalink($item), i18n::s('Cancel'), 'span');
	$context['text'] .= Skin::finalize_list($links, 'assistant_bar');

}

// render the skin
render_skin();

?>