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

// do not always show the edition form
$with_form = FALSE;

// load the skin
load_skin('users');

// the path to this page
$context['path_bar'] = array( 'users/' => i18n::s('People') );

// the title of the page
if(isset($item['full_name']) && $item['full_name'])
	$context['page_title'] = sprintf(i18n::s('Password: %s'), $item['full_name']);
elseif(isset($item['nick_name']))
	$context['page_title'] = sprintf(i18n::s('Password: %s'), $item['nick_name']);
else
	$context['page_title'] = i18n::s('Change password');

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// we are using an external authenticator
} elseif(isset($context['users_authenticator']) && $context['users_authenticator']) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// no user id has been provided
} elseif(!isset($item['id']) || !$item['id']) {
	$context['page_title'] = i18n::s('Lost password');

	// mail has been enabled at this site
	if(isset($context['with_email']) && ($context['with_email'] == 'Y')) {

		$context['text'] .= Skin::build_block(i18n::s('Automatic recovery'), 'title');

		// splash message
		$context['text'] .= '<p>'.i18n::s('If you have registered to this site, type your nick name below and we will send you a web link to authenticate.').'</p>';

		// the form to edit a user
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>';
		$fields = array();

		// the nick name
		$label = i18n::s('Nick name');
		$input = '<input type="text" name="id" size="40" '.EOT;
		$fields[] = array($label, $input);

		// stop replay attacks and robots
		if($field = Surfer::get_robot_stopper())
			$fields[] = $field;

		// build the form
		$context['text'] .= Skin::build_form($fields);

		// the submit button
		$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

		// end of the form
		$context['text'] .= '</div></form>';

		$context['text'] .= Skin::build_block(i18n::s('Manual recovery'), 'title');

	}

	// the help message
	$context['text'] .= '<p>'.sprintf(i18n::s('You may use the %s to ask for a password reset.'), Skin::build_link('query.php', i18n::s('query form'))).'</p>'
		.'<p>'.i18n::s('Please provide your e-mail address and we will email your member name and password, and instructions for accessing your account.').'</p>'
		.'<p>'.i18n::s('For the security of our members, you must make this request with the e-mail address you used when you registered. If your original e-mail address has expired or is no longer valid, please re-register. Unused accounts may be suppressed without notice.').'</p>';

// anonymous users are invited to log in or to register
} elseif(!Surfer::is_logged()) {
	$context['page_title'] = i18n::s('Lost password');

	// stop robots
	if(Surfer::may_be_a_robot()) {
		Skin::error(i18n::s('Please prove you are not a robot.'));

	// we have a target address, which is the correct one, and mail has been activated
	} elseif(isset($item['email']) && trim($item['email']) && !strcmp($id, $item['nick_name']) && isset($context['with_email']) && ($context['with_email'] == 'Y')) {

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
		$message .= "\n".sprintf(i18n::s('On-line help is available at %s'), $context['url_to_home'].$context['url_to_root'].'help.php')."\n"
			."\n".sprintf(i18n::s('Thank you for your interest into %s.'), strip_tags($context['site_name']))."\n";

		// post the confirmation message
		include_once $context['path_to_root'].'shared/mailer.php';
		Mailer::notify($item['email'], $subject, $message);

		// feed-back message
		$context['text'] .= '<p>'.i18n::s('A reminder message has been sent to you. Check your mailbox and use provided information to authenticate to this site.').'</p>';

		// go to the login form
		$menu = array('users/login.php' => i18n::s('Login'));
		$context['text'] .= Skin::build_list($menu, 'menu_bar');

	// an on-line help message
	} else {

		$context['text'] .= '<p>'.sprintf(i18n::s('You may use the %s to ask for a password reset.'), Skin::build_link('query.php', i18n::s('query form'))).'</p>'
			.'<p>'.i18n::s('Please provide your e-mail address and we will email your member name and password, and instructions for accessing your account.').'</p>'
			.'<p>'.i18n::s('For the security of our members, you must make this request with the e-mail address you used when you registered. If your original e-mail address has expired or is no longer valid, please re-register. Unused accounts may be suppressed without notice.').'</p>';

	}

// restrictions: anyone can modify its own profile; associates can modify everything
} elseif(($id != Surfer::get_id()) && !Surfer::is_associate())
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// redirect to the origin server
elseif($origin) {
	Skin::error(sprintf(i18n::s('We are only keeping a shadow record for this user profile. Please change the password for this account at %s'), Skin::build_link('http://'.$origin, $origin, 'external')));

// some data have been posted
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// passwords have to be confirmed
	if(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] != $_REQUEST['password'])) {
		Skin::error(i18n::s('Please confirm your new password.'));
		$item = $_REQUEST;
		$with_form = TRUE;

	// stop robots and replay attacks
	} elseif(Surfer::may_be_a_robot()) {
		Skin::error(i18n::s('Please prove you are not a robot.'));
		$item = $_REQUEST;
		$with_form = TRUE;

	// display the form on error
	} elseif(!Users::put($_REQUEST)) {
		$item = $_REQUEST;
		$with_form = TRUE;

	// display the updated page
	} else
		Safe::redirect($context['url_to_home'].$context['url_to_root'].Users::get_url($item['id'], 'view', isset($item['nick_name'])?$item['nick_name']:''));

// display the form on GET
} else
	$with_form = TRUE;

// display the form
if($with_form) {

	// the form to edit a user
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>';
	$fields = array();

	// the full name
	if(isset($item['full_name']) && $item['full_name']) {
		$label = i18n::s('Full name');
		$field = $item['full_name'];
		$fields[] = array($label, $field);
	}

	// the email address
	if(isset($item['email']) && $item['email']) {
		$label = i18n::s('E-mail address');
		$field = $item['email'];
		$fields[] = array($label, $field);
	}

	// the password
	$label = i18n::s('New password');
	$input = '<input type="password" name="password" id="password" size="20" value="'.encode_field(isset($_REQUEST['password']) ? $_REQUEST['password'] : '').'" />';
	$fields[] = array($label, $input);

	// the password has to be repeated for confirmation
	$label = i18n::s('Password confirmation');
	$input = '<input type="password" name="confirm" size="20" value="'.encode_field(isset($_REQUEST['confirm']) ? $_REQUEST['confirm'] : '').'" />';
	$fields[] = array($label, $input);

	// stop replay attacks and robots
	if($field = Surfer::get_robot_stopper())
		$fields[] = $field;

	// build the form
	$context['text'] .= Skin::build_form($fields);

	// bottom commands
	$context['text'] .= Skin::finalize_list(array(
		Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's'),
		Skin::build_link(Users::get_url($item['id'], 'view', isset($item['nick_name'])?$item['nick_name']:''), i18n::s('Cancel'), 'span')
		), 'menu_bar');

	// hidden field that have to be saved as well
	if(isset($item['id']) && $item['id'])
		$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'" />';

	// end of the form
	$context['text'] .= '</div></form>';

	// append the script used for data checking on the browser
	$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
		.'// check that main fields are not empty'."\n"
		.'func'.'tion validateDocumentPost(container) {'."\n"
		."\n"
		.'	// successful check'."\n"
		.'	return true;'."\n"
		.'}'."\n"
		."\n"
		.'// set the focus on first form field'."\n"
		.'$("password").focus();'."\n"
		."\n"
		.'// ]]></script>'."\n";
}

// render the skin
render_skin();

?>