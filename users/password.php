<?php
/**
 * change the password for an existing user
 *
 * This page allows users that have been registered locally to change their password.
 * Associates can change any password as well.
 *
 * Users that have only a shadow user profile are invited to go to the origin server.
 *
 * To avoid replay attacks YACS generates a random string and asks end user to type it.
 *
 * This page also helps to recover from lost password. Non-authenticated users
 * can provide their nick name, and a message is sent to the related e-mail
 * address, with a link to authenticate back to the site.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Guillaume Perez
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

// get existing user data, if any
$item =& Users::get($id);

// is this is a shadow record?
$origin = '';
if(isset($item['password'])) {
	$parts = parse_url($item['password']);
	if(isset($parts['host']))
		$origin = $parts['host'];
}

// optional processing steps
$with_form = FALSE;

// load the skin
load_skin('users');

// the path to this page
$context['path_bar'] = array( 'users/' => i18n::s('People') );

// the title of the page
if(!Surfer::is_logged())
	$context['page_title'] = i18n::s('Lost password');
elseif(isset($item['full_name']) && $item['full_name'])
	$context['page_title'] = sprintf(i18n::s('%s: %s'), i18n::s('Password'), $item['full_name']);
elseif(isset($item['nick_name']))
	$context['page_title'] = sprintf(i18n::s('%s: %s'), i18n::s('Password'), $item['nick_name']);
else
	$context['page_title'] = i18n::s('Change password');

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// we are using an external authenticator
} elseif(isset($context['users_authenticator']) && $context['users_authenticator']) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// no user id has been provided
} elseif(!isset($item['id']) || !$item['id']) {
	$context['page_title'] = i18n::s('Lost password');

	// redirect to the query form if mail has been enabled at this site
	if(!isset($context['with_email']) || ($context['with_email'] != 'Y'))
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'query.php');

	// ask for nick name
	$with_form = TRUE;

// anonymous surfers that are recovering from lost password
} elseif(!Surfer::is_logged()) {

	// redirect to the query form if mail has been enabled at this site
	if(!isset($context['with_email']) || ($context['with_email'] != 'Y'))
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'query.php');

	// redirect also if this user has no email address
	if(!isset($item['email']) || !trim($item['email']))
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'query.php');

	// direct arrival
	if(Surfer::may_be_a_robot())
		$with_form = TRUE;

	// send authentication message
	else {

		// message title
		$subject = sprintf(i18n::s('Your account at %s'), strip_tags($context['site_name']));

		// top of the message
		$message = sprintf(i18n::s('This message relates to your account at %s.'), strip_tags($context['site_name']))."\n"
			."\n".$context['url_to_home'].$context['url_to_root']."\n";

		// mention nick name
		$message .= "\n".sprintf(i18n::s('Your nick name is %s'), $item['nick_name'])."\n";

		// build credentials --see users/login.php
		$credentials = array();
		$credentials[0] = 'login';
		$credentials[1] = $id;
		$credentials[2] = rand(1000, 9999);
		$credentials[3] = sprintf('%u', crc32($credentials[2].':'.$item['handle']));

		// direct link to login page
		$message .= "\n".i18n::s('Record this message and use the following link to authenticate to the site at any time:')."\n"
			."\n".$context['url_to_home'].$context['url_to_root'].Users::get_url($credentials, 'credentials')."\n";

		// caution note
		$message .= "\n".i18n::s('Caution: This hyperlink contains your login credentials encrypted. Please be aware anyone who uses this link will have full access to your account.')."\n";

		// bottom of the message
		$message .= "\n".sprintf(i18n::s('On-line help is available at %s'), $context['url_to_home'].$context['url_to_root'].'help/')."\n"
			."\n".sprintf(i18n::s('Thank you for your interest into %s.'), strip_tags($context['site_name']))."\n";

		// enable threading
		$headers = Mailer::set_thread(NULL, 'user:'.$item['id']);

		// post the confirmation message
		Mailer::notify(NULL, $item['email'], $subject, $message, $headers);

		// feed-back message
		$context['text'] .= '<p>'.i18n::s('A reminder message has been sent to you. Check your mailbox and use provided information to authenticate to this site.').'</p>';

		// back to the anchor page
		$links = array();
		$links[] = Skin::build_link('users/login.php', i18n::s('Login'));
		$context['text'] .= Skin::finalize_list($links, 'assistant_bar');

	}


// redirect to the origin server
} elseif($origin) {
	Logger::error(sprintf(i18n::s('We are only keeping a shadow record for this profile. Please handle this account at %s'), Skin::build_link('http://'.$origin, $origin, 'external')));

// password is changing
} elseif(isset($_REQUEST['confirm'])) {

	// restrictions: anyone can modify its own profile; associates can modify everything
	if(($item['id'] != Surfer::get_id()) && !Surfer::is_associate()) {
		Safe::header('Status: 401 Forbidden', TRUE, 401);
		Logger::error(i18n::s('You are not allowed to perform this operation.'));

	// passwords have to be confirmed
	} elseif(!isset($_REQUEST['password']) || !$_REQUEST['password'] || strcmp($_REQUEST['confirm'], $_REQUEST['password'])) {
		Logger::error(i18n::s('Please confirm your new password.'));
		$with_form = TRUE;

	// stop robots and replay attacks
	} elseif(Surfer::may_be_a_robot()) {
		Logger::error(i18n::s('Please prove you are not a robot.'));
		$with_form = TRUE;

	// display the form on error
	} elseif(!Users::put($_REQUEST)) {
		$with_form = TRUE;

	// save one click to associates
	} elseif(Surfer::is_associate())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].Users::get_permalink($item));

	// follow-up
	else {

		// splash message
		$context['text'] .= '<p>'.i18n::s('Password has been changed.').'</p>';

		// back to the anchor page
		$links = array();
		$links[] = Skin::build_link(Users::get_permalink($item), i18n::s('Done'), 'button');
		$context['text'] .= Skin::finalize_list($links, 'assistant_bar');

	}

// display the form on GET
} else
	$with_form = TRUE;

// display the form
if($with_form) {

	// splash message
	if(!isset($item['id']))
		$context['text'] .= '<p>'.i18n::s('If you have registered to this site, type your nick name below and we will send you a web link to authenticate.').'</p>';
	elseif(!Surfer::is_logged())
		$context['text'] .= '<p>'.i18n::s('Please confirm that you would like to receive a message to authenticate to the following account.').'</p>';

	// the form
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>';
	$fields = array();

	// get nick name
	if(!isset($item['id'])) {
		$label = i18n::s('Nick name');
		$input = '<input type="text" name="id" size="40" />';
		$fields[] = array($label, $input);
	}

	// display full name
	if(isset($item['full_name']) && $item['full_name']) {
		$label = i18n::s('Full name');
		$field = $item['full_name'];
		$fields[] = array($label, $field);
	}

	// display email address
	if(isset($item['email']) && $item['email']) {
		$label = i18n::s('E-mail address');
		$field = $item['email'];
		$fields[] = array($label, $field);
	}

	// surfer is changing password
	if(Surfer::is_logged()) {

		// the password
		$label = i18n::s('New password');
		$input = '<input type="password" name="password" id="password" size="20" value="'.encode_field(isset($_REQUEST['password']) ? $_REQUEST['password'] : '').'" />';
		$fields[] = array($label, $input);

		// the password has to be repeated for confirmation
		$label = i18n::s('Password confirmation');
		$input = '<input type="password" name="confirm" size="20" value="'.encode_field(isset($_REQUEST['confirm']) ? $_REQUEST['confirm'] : '').'" />';
		$fields[] = array($label, $input);

		// append the script used for data checking on the browser
		$context['text'] .= JS_PREFIX
			.'$("password").focus();'."\n"
			.JS_SUFFIX."\n";

	}

	// stop replay attacks and robots
	if($field = Surfer::get_robot_stopper())
		$fields[] = $field;

	// build the form
	$context['text'] .= Skin::build_form($fields);

	// bottom commands
	$context['text'] .= Skin::finalize_list(array(
		Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's'),
		Skin::build_link(Users::get_permalink($item), i18n::s('Cancel'), 'span')
		), 'assistant_bar');

	// hidden field that have to be saved as well
	if(isset($item['id']) && $item['id'])
		$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'" />';

	// end of the form
	$context['text'] .= '</div></form>';
}

// render the skin
render_skin();

?>