<?php
/**
 * mail a message to a user
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
$item = Users::get($id);

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
	$context['page_title'] .= sprintf(i18n::s('Mail to %s'), $item['full_name']);

// do not provide the form to capture the message
$with_form = FALSE;

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// not found
} elseif(!isset($item['id'])) {
	include '../error.php';

// e-mail has not been enabled
} elseif(!isset($context['with_email']) || ($context['with_email'] != 'Y')) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('E-mail has not been enabled on this system.'));

// user does not accept private messages
} elseif(isset($item['without_messages']) && ($item['without_messages'] == 'Y')) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('This member does not accept e-mail messages.'));

// you are not allowed to mail yourself
} elseif(Surfer::get_id() && (Surfer::get_id() == $item['id'])) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Users::get_url($item['id'], 'mail')));

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// no mail in demo mode
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST') && file_exists($context['path_to_root'].'parameters/demo.flag')) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation in demonstration mode.'));

// no recipient has been found
} elseif(!isset($item['email']) || !$item['email'])
	Logger::error(i18n::s('No email address has been provided for this person.'));

// process submitted data
elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// sender address
	$from = Surfer::from();

	// recipient(s) address(es)
	$to = strip_tags($item['email']);

	// get a copy
	if(isset($_REQUEST['self_copy']) && ($_REQUEST['self_copy'] == 'Y'))
		$to .= ', '.$from;

	// message subject
	$subject = '';
	if(isset($_REQUEST['subject']))
		$subject = strip_tags($_REQUEST['subject']);

	// enable yacs codes in messages
	$message = Codes::beautify($_REQUEST['message']);

	// nothing to do
	if(!$subject || !$message) {
		Logger::error('Please provide a subject and some text for your message.');
		$with_form = TRUE;

	// do the post
	} else {

		// headline
		$headline = sprintf(i18n::c('%s has sent a message to you'), Surfer::get_link());

		// assemble main content of this message
		$message = Skin::build_mail_content($headline, $message);

		// a set of links
		$menu = array();

		// call for action
		$link = $context['url_to_home'].$context['url_to_root'].Users::get_url(Surfer::get_id(), 'mail');
		$menu[] = Skin::build_mail_button($link, i18n::c('Reply'), TRUE);

		// link to surfer profile
		$link = Surfer::get_permalink();
		$menu[] = Skin::build_mail_button($link, Surfer::get_name(), FALSE);

		// finalize links
		$message .= Skin::build_mail_menu($menu);

		// threads messages
		$headers = Mailer::set_thread('user:'.$item['id']);

		// send the message
		if(Mailer::notify($from, $to, $subject, $message, $headers)) {

			// feed-back to the sender
			$context['text'] .= '<p>'.sprintf(i18n::s('Your message is being transmitted to %s'), strip_tags($item['email'])).'</p>';

			// signal that a copy has been forwarded as well
			if(isset($_REQUEST['self_copy']) && ($_REQUEST['self_copy'] == 'Y'))
				$context['text'] .= '<p>'.sprintf(i18n::s('At your request, a copy was also sent to %s'), $from).'</p>';

		}
		Mailer::close();

		// back to user profile
		$menu = array();
		$menu[] = Skin::build_link(Users::get_permalink($item), i18n::s('Done'), 'button');
		$context['text'] .= Skin::build_block(Skin::finalize_list($menu, 'menu_bar'), 'bottom');

	}

// the default case
} else
	$with_form = TRUE;

// display the form
if($with_form) {

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
	$fields[] = array($label, $input);

	// the message
	$label = i18n::s('Message content');
	$input = Surfer::get_editor('message', '');
	$fields[] = array($label, $input);

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
		$menu[] = Skin::build_link(Users::get_permalink($item), i18n::s('Cancel'), 'span');

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
	Page::insert_script(
		// check that main fields are not empty
		'func'.'tion validateDocumentPost(container) {'."\n"
			// title is mandatory
		.'	if(!container.subject.value) {'."\n"
		.'		alert("'.i18n::s('Please provide a meaningful title.').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		return false;'."\n"
		.'	}'."\n"
			// body is mandatory
		.'	if(!container.message.value) {'."\n"
		.'		alert("'.i18n::s('Message content can not be empty').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		return false;'."\n"
		.'	}'."\n"
			// successful check
		.'	return true;'."\n"
		.'}'."\n"
		// set the focus on first form field
		.'$("#subject").focus();'."\n"
		."\n"
		);

}

// render the skin
render_skin();

?>
