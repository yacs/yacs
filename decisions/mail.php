<?php
/**
 * ask for a decision by e-mail
 *
 * This script sends a mail message to one or several recipients to ask for a decision.
 *
 * When a message is sent to invited people, these may, or not, be part of
 * the community.
 *
 * If the file [code]parameters/demo.flag[/code] exists, the script assumes that this instance
 * of YACS runs in demonstration mode, and no message is actually posted.
 *
 * Accepted calls:
 * - mail.php/&lt;id&gt;
 * - mail.php?id=&lt;id&gt;
 * - mail.php/&lt;id&gt;
 * - mail.php?id=&lt;id&gt;
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'decisions.php';

// parameters transmitted through friendly urls
if(isset($context['arguments'][0]) && isset($context['arguments'][1]))
	$target_anchor = $context['arguments'][0].':'.$context['arguments'][1];

// or usual parameters
elseif(isset($_REQUEST['anchor']))
	$target_anchor = $_REQUEST['anchor'];

// fight hackers
$target_anchor = strip_tags($target_anchor);

// get the anchor
$anchor = NULL;
if($target_anchor)
	$anchor =& Anchors::get($target_anchor);

// what kind of action?
$action = '';
if(isset($_REQUEST['action']))
	$action = $_REQUEST['action'];
elseif(isset($context['arguments'][1]))
	$action = $context['arguments'][1];
$action = strip_tags($action);

// anchor editors can do what they want
if(is_object($anchor) && $anchor->is_assigned())
	Surfer::empower();

// the page on balance
if(is_object($anchor)) {

	// link to decision form
	$link = $context['url_to_home'].$context['url_to_root'].Decisions::get_url($anchor->get_reference(), 'decision');

	// message prefix
	$message_prefix = i18n::s('You are personally invited to express your decision on following page.')
		."\n\n".$link."\n\n";

}

// we are using surfer own address
if(!Surfer::get_email_address())
	$permitted = FALSE;

// associates and editors can do what they want
elseif(Surfer::is_empowered())
	$permitted = TRUE;

// function is available only to authenticated members --not subscribers
elseif(!Surfer::is_member())
	$permitted = FALSE;

// the anchor has to be viewable by this surfer
elseif(is_object($anchor) && $anchor->is_viewable())
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load the skin, maybe with a variant
load_skin('decisions', $anchor);

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// path to this page
$context['path_bar'] = Surfer::get_path_bar($anchor);

// page title
$context['page_title'] = i18n::s('Ask for a decision');

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// not found
} elseif(!is_object($anchor)) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No anchor has been found.'));

// e-mail has not been enabled
} elseif(!isset($context['with_email']) || ($context['with_email'] != 'Y')) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('E-mail has not been enabled on this system.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Decisions::get_url($anchor->get_reference(), 'mail')));

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// no mail in demo mode
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST') && file_exists($context['path_to_root'].'parameters/demo.flag')) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation in demonstration mode.'));

// no recipient has been found
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST') && (!isset($_REQUEST['to']) || !$_REQUEST['to'])) {
	Logger::error(i18n::s('Please provide a recipient address.'));

// process submitted data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// sender address
	$from = Surfer::from();

	// recipient(s) address(es)
	$to = '';
	if(isset($_REQUEST['to']))
		$to = strip_tags($_REQUEST['to']);
	if(isset($_REQUEST['self_copy']) && ($_REQUEST['self_copy'] == 'Y')) {
		if($to)
			$to .= ', ';
		$to .= $from;
	}

	// message subject
	$subject = '';
	if(isset($_REQUEST['subject']))
		$subject = strip_tags($_REQUEST['subject']);

	// message body
	$message = $message_prefix;
	if(isset($_REQUEST['message']))
		$message .= strip_tags($_REQUEST['message']);

	// add a tail to the sent message
	if($message) {

		// use surfer signature, if any
		$signature = '';
		if(($user =& Users::get(Surfer::get_id())) && $user['signature'])
			$signature = $user['signature'];

		// else use default signature
		else
			$signature = sprintf(i18n::s('Visit %s to get more interesting pages.'), $context['url_to_home'].$context['url_to_root']);

		// transform YACS code, if any
		if(is_callable('Codes', 'render'))
			$signature = Codes::render($signature);

		// plain text only
		$signature = trim(strip_tags($signature));

		// append the signature
		if($signature)
			$message .= "\n\n-----\n".$signature;

	}

	// make an array of recipients
	if(!is_array($to))
		$to = Mailer::explode_recipients($to);

	// process every recipient
	$posts = 0;
	$actual_names = array();
	foreach($to as $recipient) {
		$recipient = trim($recipient);

		// we have a valid e-mail address
		if(preg_match('/\w+@\w+\.\w+/', $recipient)) {
			if(strcmp($recipient, $from))
				$actual_names[] = $recipient;

		// look for a user with this nick name
		} elseif(($user =& Users::get($recipient)) && $user['email']) {
			$recipient = $user['email'];
			if(!strcmp($user['email'], $from))
				;
			elseif($user['full_name'])
				$actual_names[] = $user['full_name'];
			else
				$actual_names[] = $user['nick_name'];

		// skip this recipient
		} else {
			Logger::error(sprintf(i18n::s('Error while sending the message to %s'), $recipient));
			continue;
		}

		// clean the provided string
		$recipient = trim(str_replace(array("\r\n", "\r", "\n", "\t"), ' ', $recipient));

		// extract the actual e-mail address -- Foo Bar <foo@bar.com> => foo@bar.com
		$tokens = explode(' ', $recipient);
		$actual_recipient = trim(str_replace(array('<', '>'), '', $tokens[count($tokens)-1]));
		$actual_subject = $subject;

		// change content for message poster
		if(!strcmp($recipient, $from)) {
			$actual_subject = sprintf(i18n::s('Copy: %s'), $subject);
			$message = i18n::s('This is a copy of the message you have sent, for your own record.')."\n".'-------'."\n".join(', ', $actual_names)."\n".'-------'."\n\n".$message;
		}
		// post in debug mode, to get messages, if any
		if(Mailer::post($from, $actual_recipient, $actual_subject, $message))
			$context['text'] .= '<p>'.sprintf(i18n::s('Your message is being transmitted to %s'), strip_tags($recipient)).'</p>';

	}
	Mailer::close();
	// follow-up commands
	$follow_up = i18n::s('What do you want to do now?');
	$menu = array();
	$menu = array_merge($menu, array($anchor->get_url() => i18n::s('Back to main page')));
	$menu = array_merge($menu, array($context['script_url'] => i18n::s('Ask for a decision')));
	$follow_up .= Skin::build_list($menu, 'menu_bar');
	$context['text'] .= Skin::build_block($follow_up, 'bottom');

// a form to send an invitation to several people
} else {

	// the form to send a message
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>';

	// recipients
	$label = i18n::s('Ask for a decision');
	$input = '<textarea name="to" id="names" rows="3" cols="50"></textarea><div id="names_choices" class="autocomplete"></div>';
	$hint = i18n::s('Enter nick names, or email addresses, separated by commas.');
	$fields[] = array($label, $input, $hint);

	// the subject
	$label = i18n::s('Message title');
	$title = sprintf(i18n::s('Decide: %s'), $anchor->get_title());
	$input = '<input type="text" name="subject" size="45" maxlength="255" value="'.encode_field($title).'" />';
	$fields[] = array($label, $input);

	// message author
	$author = Surfer::get_name();
	if($author_id = Surfer::get_id())
		$author .= "\n".$context['url_to_home'].$context['url_to_root'].Users::get_url($author_id, 'view', Surfer::get_name());

	// the message
	$label = i18n::s('Message content');
	$content = i18n::s('Please let me thank you for your involvement.')."\n\n".$author;
	$input = str_replace("\n", BR, $message_prefix)
		.'<textarea name="message" rows="15" cols="50">'.encode_field($content).'</textarea>';
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
	$menu[] = Skin::build_link($anchor->get_url(), i18n::s('Cancel'), 'span');

	// insert the menu in the page
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// get a copy of the sent message
	$context['text'] .= '<p><input type="checkbox" name="self_copy" value="Y" checked="checked" /> '.i18n::s('Send me a copy of this message.').'</p>';

	// transmit the id as a hidden field
	$context['text'] .= '<input type="hidden" name="anchor" value="'.$anchor->get_reference().'" />';

	// end of the form
	$context['text'] .= '</div></form>';

	// append the script used for data checking on the browser
	$context['text'] .= JS_PREFIX
		.'// check that main fields are not empty'."\n"
		.'func'.'tion validateDocumentPost(container) {'."\n"
		."\n"
		.'	// to is mandatory'."\n"
		.'	if(!container.to.value) {'."\n"
		.'		alert("'.i18n::s('Please provide a recipient address.').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		return false;'."\n"
		.'	}'."\n"
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
		.'		alert("'.i18n::s('Message content can not be empty.').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		return false;'."\n"
		.'	}'."\n"
		."\n"
		.'	// successful check'."\n"
		.'	return true;'."\n"
		.'}'."\n"
		."\n"
		.'// set the focus on first form field'."\n"
		.'$(document).ready( function() { $("#names").focus() });'."\n"
		."\n"
		."\n"
  	.'// enable names autocompletion'."\n"
    .'$(document).ready( function() {'."\n"
    .'  $("#names").autocomplete({                     '."\n"
    .'		source: "'.$context['url_to_root'].'users/complete.php",  '."\n"
    .'		minLength: 1                                                  '."\n"
    .'  });                                                              '."\n"
    .'});  '."\n"
    .JS_SUFFIX;

}

// render the skin
render_skin();

?>
