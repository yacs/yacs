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
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
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
$item =& Users::get($id);

// load localized strings
i18n::bind('users');

// load the skin
load_skin('users');

// the path to this page
$context['path_bar'] = array( 'users/' => i18n::s('All users') );

// the title of the page
$context['page_title'] = i18n::s('Validate your e-mail address');

// not found
if(!$item['id'])
	Skin::error(i18n::s('No item has the provided id.'));

// bad handle
elseif($id != $item['handle'])
	Skin::error(i18n::s('Invalid handle has been provided.'));

// actual validation
elseif(Users::validate($item['id'])) {

	// congratulations
	$context['text'] .= sprintf(i18n::s('<p>%s,</p><p>Your e-mail address has been validated, and you are now an active member of this community.</p>'), ucfirst($item['nick_name']));

	// follow-up commands
	$context['text'] .= '<p>'.i18n::s('What do you want to do now?').'</p>';
	$menu = array();
	$menu = array_merge($menu, array('users/login.php' => i18n::s('Login')));
	$menu = array_merge($menu, array(Users::get_url($item['id'], 'view', isset($item['nick_name'])?$item['nick_name']:'') => i18n::s('Go to my user profile')));
	$menu = array_merge($menu, array('index.php' => i18n::s('Go to the front page')));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// failed operation
} else
	$context['text'] .= '<p>'.i18n::s('Your e-mail address has not been validated.')."</p>\n";

// render the skin
render_skin();

?>