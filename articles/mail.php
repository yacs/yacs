<?php
/**
 * mail an article
 *
 * This script has a form to post a mail message related to an existing page.
 *
 * When a message is sent to invited people, these may, or not, be part of
 * the community.
 *
 * Long lines of the message are wrapped according to [link=Dan's suggestion]http://mailformat.dan.info/body/linelength.html[/link].
 *
 * @link http://mailformat.dan.info/body/linelength.html Dan's Mail Format Site: Body: Line Length
 *
 * Surfer signature is appended to the message, if any.
 * Else a default signature is used instead, with a link to the site front page.
 *
 * Senders can select to get a copy of messages.
 *
 * Messages are sent using utf-8, and are either base64-encoded, or send as-is.
 *
 * @link http://www.sitepoint.com/article/advanced-email-php/3 Advanced email in PHP
 *
 * Restrictions apply on this page:
 * - surfer has to have an e-mail address
 * - associates and editors are allowed to move forward
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
 * - mail.php/&lt;id&gt;
 * - mail.php?id=&lt;id&gt;
 *
 * If this article, or one of its anchor, specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
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
$id = '';
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Articles::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']))
	$anchor = Anchors::get($item['anchor']);

// get the related overlay, if any
$overlay = NULL;
include_once '../overlays/overlay.php';
if(isset($item['overlay']))
	$overlay = Overlay::load($item);

// associates can do what they want
if(Surfer::is_associate())
	Surfer::empower();

// anchor editors can do what they want
elseif(is_object($anchor) && $anchor->is_assigned())
	Surfer::empower();

// surfer created the page
elseif(Surfer::get_id() && isset($item['create_id']) && ($item['create_id'] == Surfer::get_id()))
	Surfer::empower();

// link to contribute --minimal format will be expanded with credentials afterwards
if(Surfer::is_empowered() && isset($_REQUEST['provide_credentials']) && ($_REQUEST['provide_credentials'] == 'Y'))
	$link = $context['url_to_home'].$context['url_to_root'].Articles::get_url($item['id']);
else
	$link = $context['url_to_home'].$context['url_to_root'].Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']);

// message prefix
if(isset($item['create_id']) && Surfer::get_id() && ($item['create_id'] == Surfer::get_id()))
	$message_prefix = i18n::s('I have created a web page and would like you to check it, at the following address.')
		."\n\n".$link."\n\n";
else
	$message_prefix = i18n::s('You are invited personally to check the following page.')
		."\n\n".$link."\n\n";

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
load_skin('articles', $anchor, isset($item['options']) ? $item['options'] : '');

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// path to this page
if(is_object($anchor))
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'articles/' => i18n::s('All pages') );
if(isset($item['id']))
	$context['path_bar'] = array_merge($context['path_bar'], array(Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']) => $item['title']));

// page title
if(isset($item['title']))
	$context['page_title'] = sprintf(i18n::s('Share: %s'), $item['title']);

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// not found
} elseif(!isset($item['id'])) {
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
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Articles::get_url($item['id'], 'mail')));

	// permission denied to authenticated user
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// no mail in demo mode
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST') && file_exists($context['path_to_root'].'parameters/demo.flag')) {

	// remind the surfer
	$context['text'] .= '<p>'.i18n::s('This instance of YACS runs in demonstration mode. For security reasons mail messages cannot be actually sent in this mode.').'</p>'."\n";

// no recipient has been found
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST') && (!isset($_REQUEST['to']) || !$_REQUEST['to'])) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('Please provide a recipient address.'));

// process submitted data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// sender address
	$from = Surfer::get_email_address();

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

		// add credentials in message
		if(Surfer::is_empowered() && isset($_REQUEST['provide_credentials']) && ($_REQUEST['provide_credentials'] == 'Y')) {

			// build credentials --see users/login.php
			$credentials = array();
			$credentials[0] = 'visit';
			$credentials[1] = 'article:'.$item['id'];
			$credentials[2] = $actual_recipient;
			$credentials[3] = sprintf('%u', crc32($actual_recipient.':'.$item['handle']));

			// the secret link
			$link = Users::get_url($credentials, 'credentials');

			// translate strings to allow for one-click authentication
			$actual_message = str_replace(Articles::get_url($item['id']), $link, $message);

		// regular message
		} else
			$actual_message = $message;

		// change content for message poster
		if(!strcmp($recipient, $from))
			$actual_message = i18n::s('This is a copy of the message you have sent, for your own record.')."\n".'-------'."\n\n".$actual_message;

		// post in debug mode, to get messages, if any
		if(Mailer::post($from, $actual_recipient, $subject, $actual_message, $headers, 'articles/mail.php'))
			$context['text'] .= '<p>'.sprintf(i18n::s('Your message is being transmitted to %s'), strip_tags($recipient)).'</p>';
	}

	// follow-up commands
	$follow_up = i18n::s('What do you want to do now?');
	$menu = array();
	$menu = array_merge($menu, array(Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']) => i18n::s('Back to main page')));
	$menu = array_merge($menu, array(Articles::get_url($item['id'], 'mail') => i18n::s('Invite people')));
	$follow_up .= Skin::build_list($menu, 'page_menu');
	$context['text'] .= Skin::build_block($follow_up, 'bottom');

// a form to send an invitation to several people
} else {

	// the form to send a message
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>';
	$fields = array();

	// recipients
	$label = i18n::s('Invite people');
	$input = '';
	if(Surfer::is_empowered())
		$input .= '<input type="radio" name="provide_credentials" value="N" checked="checked" /> '.i18n::s('to review the page')
			.' &nbsp; <input type="radio" name="provide_credentials" value="Y" /> '.i18n::s('to edit the page').BR;
	$input .= '<textarea name="to" id="names" rows="3" cols="50"></textarea><div id="names_choices" class="autocomplete"></div>';
	$hint = i18n::s('Enter nick names, or email addresses, separated by commas.');
	$fields[] = array($label, $input, $hint);

	// the subject
	$label = i18n::s('Message title');
	$title = '';
	if($name = Surfer::get_name())
		$title = sprintf(i18n::s('You are invited by %s'), $name);
	$input = '<input type="text" name="subject" size="50" maxlength="255" value="'.encode_field($title).'" />';
	$fields[] = array($label, $input);

	// message author
	$author = Surfer::get_name();
	if($author_id = Surfer::get_id())
		$author .= "\n".$context['url_to_home'].$context['url_to_root'].Users::get_url($author_id, 'view', Surfer::get_name());

	// the message
	$label = i18n::s('Message content');
	$content = i18n::s('Please let me thank you for your kind support.')."\n\n".$author;
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
	if(isset($item['id']))
		$menu[] = Skin::build_link(Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']), i18n::s('Cancel'), 'span');

	// insert the menu in the page
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// get a copy of the sent message
	$context['text'] .= '<p><input type="checkbox" name="self_copy" value="Y" checked="checked" /> '.i18n::s('Send me a copy of this message.').'</p>';

	// transmit the id as a hidden field
	$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'" />';

	// end of the form
	$context['text'] .= '</div></form>';

	// append the script used for data checking on the browser
	$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
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
		.'// enable autocompletion'."\n"
		.'Event.observe(window, "load", function() { new Ajax.Autocompleter("names", "names_choices", "'.$context['url_to_root'].'users/complete.php", { paramName: "q", minChars: 1, frequency: 0.4, tokens: "," }); });'."\n"
		.'// ]]></script>';

	// help message
	$help = '<p>'.i18n::s('Recipient addresses are used only once, to send your message, and are not stored afterwards.').'</p>';
	$context['extra'] .= Skin::build_box(i18n::s('Help'), $help, 'extra', 'help');

}

// render the skin
render_skin();

?>