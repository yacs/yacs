<?php
/**
 * delete a user profile
 *
 * This script calls for confirmation, then it actually deletes the user.
 *
 * If the file [code]demo.flag[/code] exists, the script assumes that this instance
 * of YACS runs in demonstration mode, and user profiles are not actually deleted.
 *
 * Restrictions apply on this page:
 * - associates are allowed to move forward
 * - surfer is allowed to delete its own profile, except if this is forbidden
 * - permission denied is the default
 *
 * To prevent self-deletion, you have to change the parameter 'users_without_self_deletion'
 * from the configuration panel for users.
 *
 * Accept following invocations:
 * - delete.php/12
 * - delete.php?id=12
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Pierre Robert
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
elseif(Surfer::is_logged())
	$id = Surfer::get_id();
$id = strip_tags($id);

// get the item from the database
$item =& Users::get($id);

// associates can do what they want
if(Surfer::is_associate())
	$permitted = TRUE;

// self-deletion is forbidden
elseif(isset($context['users_without_self_deletion']) && ($context['users_without_self_deletion'] == 'Y'))
	$permitted = FALSE;

// the surfer is allowed to delete its own profile
elseif(isset($item['create_id']) && Surfer::is($item['create_id']))
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load the skin
load_skin('users');

// the path to this page
$context['path_bar'] = array( 'users/' => i18n::s('People') );

// the title of the page
if(isset($item['nick_name']))
	$context['page_title'] = sprintf(i18n::s('%s: %s'), i18n::s('Delete'), $item['nick_name']);

// not found
if(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// no deletion in demo mode
} elseif(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'yes') && file_exists($context['path_to_root'].'parameters/demo.flag')) {

	// remind the surfer
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	$context['text'] .= '<p>'.i18n::s('You are not allowed to perform this operation in demonstration mode.')."</p>\n";

// deletion is confirmed
} elseif(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'yes')) {

	// close the session on self-deletion
	if(Surfer::get_id() == $item['id'])
		Surfer::reset();

	// attempt to delete
	if(Users::delete($item['id'])) {

		// this can appear anywhere
		Cache::clear();

		// back to the index page
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/');
	}

// deletion has to be confirmed
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST'))
	Skin::error(i18n::s('The deletion has not been confirmed.'));

// ask for confirmation
else {

	// the submit button
	if(Surfer::is($item['id']))
		$label = i18n::s('Yes, I want to suppress my own profile from this server and log out.');
	else
		$label = i18n::s('Yes, I want to suppress this user');
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
		.Skin::build_submit_button($label, NULL, NULL, 'confirmed')."\n"
		.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n"
		.'<input type="hidden" name="confirm" value="yes" />'."\n"
		.'</p></form>'."\n";

	// set the focus
	$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
		.'// set the focus on first form field'."\n"
		.'$("confirmed").focus();'."\n"
		.'// ]]></script>'."\n";

	// user nick name
	if($item['nick_name'])
		$context['text'] .= Skin::build_block($item['nick_name'], 'title');

	// user full name
	if($item['full_name'])
		$context['text'] .= '<p>'.$item['full_name']."</p>\n";

	// introduction text, if any
	$context['text'] .= Skin::build_block($item['introduction'], 'introduction');

	// beautified description, which is the actual page body
	$context['text'] .= Skin::build_block($item['description'], 'description');

	// count items related to this user
	$context['text'] .= Anchors::stat_related_to('user:'.$item['id'], i18n::s('Following items are attached to this record and will be deleted as well.'));

}

// render the skin
render_skin();

?>