<?php
/**
 * mail a message to a user
 *
 * Long lines of the message are wrapped according to [link=Dan's suggestion]http://mailformat.dan.info/body/linelength.html[/link].
 *
 * @link http://mailformat.dan.info/body/linelength.html Dan's Mail Format Site: Body: Line Length
 *
 * Surfer signature is appended to the message, if any.
 * Else a default signature is used instead, with a link to the front page of the web server.
 *
 * Senders can select to get a copy of messages.
 *
 * Messages are sent using utf-8, and are base64-encoded.
 *
 * @link http://www.sitepoint.com/article/advanced-email-php/3 Advanced email in PHP
 *
 * Restrictions apply on this page:
 * - associates are allowed to move forward
 * - access is restricted ('active' field == 'R'), but the surfer is an authenticated member
 * - public access is allowed ('active' field == 'Y') and the surfer has been logged
 * - permission denied is the default
 *
 * This script prevents mail when the target surfer has disallowed private messages.
 *
 * If the file [code]parameters/demo.flag[/code] exists, the script assumes that this instance
 * of YACS runs in demonstration mode, and the message is not actually posted.
 *
 * Accepted calls:
 * - mail.php/&lt;id&gt;
 * - mail.php?id=&lt;id&gt;
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Jan Boen
 * @tester Guillaume Perez
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
$id = strip_tags($id);

// get the item from the database
$item =& Users::get($id);

// associates can do what they want
if(Surfer::is_associate())
	$permitted = TRUE;

// check global parameter
elseif(isset($context['users_with_email_display']) && ($context['users_with_email_display'] == 'N'))
	$permitted = FALSE;

// only regular members can post mail messages
elseif(!Surfer::is_member())
	$permitted = FALSE;

// access is restricted to authenticated member
elseif(isset($item['active']) && (($item['active'] == 'R') || ($item['active'] == 'Y')))
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
	$context['page_title'] .= sprintf(i18n::s('Mail to %s'), $item['nick_name']);
else
	$context['page_title'] .= i18n::s('Send a message');

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// not found
} elseif(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No item has the provided id.'));

// e-mail has not been enabled
} elseif(!isset($context['with_email']) || ($context['with_email'] != 'Y')) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('E-mail has not been enabled on this system.'));

// user does not accept private messages
} elseif(isset($item['without_messages']) && ($item['without_messages'] == 'Y')) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('This member does not accept e-mail messages.'));

// you are not allowed to mail yourself
} elseif(Surfer::get_id() && (Surfer::get_id() == $item['id'])) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Users::get_url($item['id'], 'mail')));

	// permission denied to authenticated user
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// no mail in demo mode
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST') && file_exists($context['path_to_root'].'parameters/demo.flag')) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation in demonstration mode.'));

// no recipient has been found
} elseif(!isset($item['email']) || !$item['email'])
	Logger::error(i18n::s('This user profile has no email address.'));

// process submitted data
elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// sender address
	$from = Surfer::get_email_address();

	// recipient(s) address(es)
	$to = strip_tags($item['email']);

	// get a copy
	if(isset($_REQUEST['self_copy']) && ($_REQUEST['self_copy'] == 'Y'))
		$to .= ', '.$from;

	// message subject
	$subject = '';
	if(isset($_REQUEST['subject']))
		$subject = strip_tags($_REQUEST['subject']);

	// message body
	$message = '';
	if(isset($_REQUEST['message']))
		$message = strip_tags($_REQUEST['message']);

	// add a tail to the sent message
	if($message) {

		// use surfer signature, if any
		$signature = '';
		if(($user =& Users::get(Surfer::get_id())) && $user['signature'])
			$signature = $user['signature'];

		// else use default signature
		else
			$signature = sprintf(i18n::c('Visit %s to get more interesting pages.'), $context['url_to_home'].$context['url_to_root']);

		// transform YACS code, if any
		if(is_callable('Codes', 'render'))
			$signature = Codes::render($signature);

		// plain text only
		$signature = trim(strip_tags($signature));

		// append the signature
		if($signature)
			$message .= "\n\n-----\n".$signature;

	}

	// send the message
	include_once $context['path_to_root'].'shared/mailer.php';
	if(Mailer::post($from, $to, $subject, $message)) {

		// feed-back to the sender
		$context['text'] .= '<p>'.sprintf(i18n::s('Your message is being transmitted to %s'), strip_tags($item['email'])).'</p>';

		// signal that a copy has been forwarded as well
		if(isset($_REQUEST['self_copy']) && ($_REQUEST['self_copy'] == 'Y'))
			$context['text'] .= '<p>'.sprintf(i18n::s('At your request, a copy was also sent to %s'), $from).'</p>';

	}
	Mailer::close();

// display the form
} else {

	// name
	if(isset($item['full_name']))
		$name = $item['full_name'];
	else
		$name = $item['nick_name'];

	if(isset($item['email']) && $item['email'])
		$name .= ' &lt;'.$item['email'].'&gt;';

	// header
	$context['text'] .= '<p>'.i18n::s('You are sending a message to:').' '.$name.'</p>'."\n";

	// the form to send a message
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>';

	// the subject
	$label = i18n::s('Message title');
	$input = '<input type="text" name="subject" id="subject" size="70" />';
	$hint = i18n::s('Please provide a meaningful title.');
	$fields[] = array($label, $input, $hint);

	// the message
	$label = i18n::s('Message content');
	$input = '<textarea name="message" rows="15" cols="50"></textarea>';
	$hint = i18n::s('Use only plain ASCII, no HTML.');
	$fields[] = array($label, $input, $hint);

	// build the form
	$context['text'] .= Skin::build_form($fields);

	//
	// bottom commands
	//
	$menu = array();

	// the submit button
	$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');

	// cancel button
	if(isset($item['id']))
		$menu[] = Skin::build_link(Users::get_url($item['id'], 'view', $item['nick_name']), i18n::s('Cancel'), 'span');

	// insert the menu in the page
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// get a copy of the sent message
	if(Surfer::is_logged())
		$context['text'] .= BR.'<input type="checkbox" name="self_copy" value="Y" checked="checked" /> '.i18n::s('Send me a copy of this message.');

	// transmit the id as a hidden field
	$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'" />';

	// end of the form
	$context['text'] .= '</div></form>';

	// append the script used for data checking on the browser
	$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
		.'// check that main fields are not empty'."\n"
		.'func'.'tion validateDocumentPost(container) {'."\n"
		."\n"
		.'	// title is mandatory'."\n"
		.'	if(!container.subject.value) {'."\n"
		.'		alert("'.i18n::s('Please provide a meaningful title.').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		return false;'."\n"
		.'	}'."\n"
		."\n"
		.'	// body is mandatory'."\n"
		.'	if(!container.message.value) {'."\n"
		.'		alert("'.i18n::s('Message content can not be empty').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		return false;'."\n"
		.'	}'."\n"
		."\n"
		.'	// successful check'."\n"
		.'	return true;'."\n"
		.'}'."\n"
		."\n"
		.'// set the focus on first form field'."\n"
		.'$("subject").focus();'."\n"
		."\n"
		.'// ]]></script>';

}

// render the skin
render_skin();

?>