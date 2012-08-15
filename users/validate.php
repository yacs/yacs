<?php
/**
 * validate an e-mail address
 *
 * After a new user has registered, he may receive a mail message for the
 * validation of the provided e-mail address.
 * The link provided in the message will trigger he execution of this script.
 *
 * @see users/users.php
 *
 * This page may be triggered by anyone.
 *
 * Accept following invocations:
 * - validate.php/&lt;handle&gt;
 * - validate.php?id=&lt;handle&gt;
 *
 * @see users/edit.php
 *
 * @author Bernard Paques
 * @author GnapZ
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
$item = Users::get($id);

// load the skin
load_skin('users');

// the path to this page
$context['path_bar'] = array( 'users/' => i18n::s('People') );

// the title of the page
$context['page_title'] = i18n::s('Validate your e-mail address');

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// not found
} elseif(!isset($item['id'])) {
	include '../error.php';

// bad handle
} elseif($id != $item['handle'])
	include '../error.php';

// actual validation
elseif(Users::validate($item['id'])) {

	// congratulations
	$context['text'] .= sprintf(i18n::s('<p>%s,</p><p>Your e-mail address has been validated, and you are now an active member of this community.</p>'), ucfirst($item['nick_name']));

	// set permanent name shown from top level
	Safe::setcookie('surfer_name', $user['nick_name'], time()+60*60*24*500, '/');

	// save surfer profile in session context
	Surfer::set($item);

	// follow-up commands
	$follow_up = i18n::s('Where do you want to go now?');
	$menu = array();
	$menu = array_merge($menu, array(Users::get_permalink($item) => i18n::s('My profile')));
	$menu = array_merge($menu, array($context['url_to_root'] => i18n::s('Front page')));
	$follow_up .= Skin::build_list($menu, 'menu_bar');
	$context['text'] .= Skin::build_block($follow_up, 'bottom');

// failed operation
} else
	$context['text'] .= '<p>'.i18n::s('Your e-mail address has not been validated.')."</p>\n";

// render the skin
render_skin();

?>
