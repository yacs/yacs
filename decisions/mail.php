<?php
/**
 * ask for a decision by e-mail
 *
 * This script sends a mail message to one or several recipients to ask for a decision.
 *
 * When a message is sent to invited people, these may, or not, be part of
 * the community.
 *
 * Restrictions apply on this page:
 * - associates and editors are allowed to move forward
 * - creator is allowed to view the page
 * - permission is denied if the anchor is not viewable
 * - article is restricted ('active' field == 'R'), but the surfer is an authenticated member
 * - public access is allowed ('active' field == 'Y'), and the surfer has been authenticated
 * - permission denied is the default
 *
 * If the file [code]demo.flag[/code] exists, the script assumes that this instance
 * of YACS runs in demonstration mode, and no message is actually posted.
 *
 * Accepted calls:
 * - mail.php/&lt;id&gt;
 * - mail.php?id=&lt;id&gt;
 * - mail.php/&lt;id&gt;/feed-back
 * - mail.php?id=&lt;id&gt;&action=feed-back
 * - mail.php/&lt;id&gt;/invite
 * - mail.php?id=&lt;id&gt;&action=invite
 *
 * If the anchor page specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
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
	$anchor = Anchors::get($target_anchor);

// maybe the anchor has been locked
if(is_object($anchor) && $anchor->has_option('locked'))
	$permitted = FALSE;

// associates and editors can do what they want
elseif(Surfer::is_empowered() || (is_object($anchor) && $anchor->is_editable()))
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
if(is_object($anchor))
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'articles/' => i18n::s('All pages') );

// page title
$context['page_title'] = i18n::s('Ask for a decision');

// not found
if(!is_object($anchor)) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No item has the provided id.'));

// e-mail has not been enabled
} elseif(!isset($context['with_email']) || ($context['with_email'] != 'Y')) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('E-mail has not been enabled on this system.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Decisions::get_url($anchor->get_reference(), 'mail')));

	// permission denied to authenticated user
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// no mail in demo mode
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST') && file_exists($context['path_to_root'].'parameters/demo.flag')) {

	// remind the surfer
	$context['text'] .= '<p>'.i18n::s('This instance of YACS runs in demonstration mode. For security reasons mail messages cannot be actually sent in this mode.').'</p>'."\n";

// no recipient has been found
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST') && (!isset($_REQUEST['to']) || !$_REQUEST['to'])) {
	Skin::error(i18n::s('Please provide a recipient address.'));

// process submitted data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// sender address
	$from = '';
	if(isset($_REQUEST['from']))
		$from = strip_tags($_REQUEST['from']);

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

	// additional headers
	$headers = array();

	// make an array of recipients
	if(!is_array($to))
		$to = explode(',', $to);

	// process every recipient
	include_once $context['path_to_root'].'shared/mailer.php';
	$posts = 0;
	foreach($to as $recipient) {
		$recipient = trim($recipient);

		// if this is not a valid address
		if(!preg_match('/\w+@\w+\.\w+/', $recipient)) {

			// look for a user with this nick name
			if(($user =& Users::get($recipient)) && $user['email'])
				$recipient = $user['email'];

			// skip this recipient
			else {
				Skin::error(sprintf(i18n::s('Error while sending the message to %s'), $recipient));
				continue;
			}
		}

		// clean the provided string
		$recipient = trim(str_replace(array("\r\n", "\r", "\n", "\t"), ' ', $recipient));

		// extract the actual e-mail address -- Foo Bar <foo@bar.com> => foo@bar.com
		$tokens = explode(' ', $recipient);
		$actual_recipient = trim(str_replace(array('<', '>'), '', $tokens[count($tokens)-1]));

		// change content for message poster
		if(!strcmp($recipient, $from))
			$message = i18n::s('This is a copy of the message you have sent, for your own record.')."\n".'-------'."\n\n".$message;

		// post in debug mode, to get messages, if any
		if(Mailer::post($from, $actual_recipient, $subject, $message, $headers, 'articles/mail.php')) {
			$context['text'] .= '<p>'.sprintf(i18n::s('Your message is being transmitted to %s'), strip_tags($recipient)).'</p>';

		// document the error
		} else {

			// the address
			$context['text'] .= '<p>'.sprintf(i18n::s('Mail address: %s'), $actual_recipient).'</p>'."\n";

			// the sender
			$context['text'] .= '<p>'.sprintf(i18n::s('Sender address: %s'), $from).'</p>'."\n";

			// the subject
			$context['text'] .= '<p>'.sprintf(i18n::s('Subject: %s'), $subject).'</p>'."\n";

			// the message
			$context['text'] .= '<p>'.sprintf(i18n::s('Message: %s'), BR.nl2br($message)).'</p>'."\n";

		}
	}

// a form to send an invitation to several people
} else {

	// the form to send a message
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>';

	// the recipient
	$label = i18n::s('People to invite');
	$input = '<textarea name="to" id="names" rows="3" cols="50"></textarea><div id="names_choices" class="autocomplete"></div>';
	$hint = i18n::s('Enter nick names, or email addresses, separated by commas.');
	$fields[] = array($label, $input, $hint);

	// the sender
	$label = i18n::s('Your e-mail address');
	$input = '<input type="text" name="from" size="45" maxlength="255" value="'.encode_field(Surfer::get_email_address()).'" />';

	// get a copy of the sent message
	if(Surfer::is_logged())
		$input .= BR.'<input type="checkbox" name="self_copy" value="Y" checked="checked" /> '.i18n::s('Send me a copy of this message.');

	$fields[] = array($label, $input, '');

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
	$content = sprintf(i18n::s("You are personally invited to express your decision on following page.\n\n%s\n\nPlease let me thank you for your kind support.\n\n%s"), $context['url_to_home'].$context['url_to_root'].Decisions::get_url($anchor->get_reference(), 'decision'), $author);

	$input = '<textarea name="message" rows="15" cols="50">'.encode_field($content).'</textarea>';
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
	$context['text'] .= Skin::finalize_list($menu, 'menu_bar');

	// transmit the id as a hidden field
	$context['text'] .= '<input type="hidden" name="anchor" value="'.$anchor->get_reference().'" />';

	// end of the form
	$context['text'] .= '</div></form>';

	// append the script used for data checking on the browser
	$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
		.'// check that main fields are not empty'."\n"
		.'func'.'tion validateDocumentPost(container) {'."\n"
		."\n"
		.'	// to is mandatory'."\n"
		.'	if(!container.to.value) {'."\n"
		.'		alert("'.i18n::s('You must type a recipient for your message.').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		return false;'."\n"
		.'	}'."\n"
		."\n"
		.'	// from is mandatory'."\n"
		.'	if(!container.from.value) {'."\n"
		.'		alert("'.i18n::s('You must type an address for replies.').'");'."\n"
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
		.'		alert("'.i18n::s('The message content can not be empty.').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		return false;'."\n"
		.'	}'."\n"
		."\n"
		.'	// successful check'."\n"
		.'	return true;'."\n"
		.'}'."\n"
		."\n"
		.'// set the focus on first form field'."\n"
		.'Event.observe(window, "load", function() { $("names").focus() });'."\n"
		."\n"
		."\n"
		.'// enable tags autocompletion'."\n"
		.'Event.observe(window, "load", function() { new Ajax.Autocompleter("names", "names_choices", "'.$context['url_to_root'].'users/complete.php", { paramName: "q", minChars: 1, frequency: 0.4, tokens: "," }); });'."\n"
		.'// ]]></script>';

}

// render the skin
render_skin();

?>