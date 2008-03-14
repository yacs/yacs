<?php
/**
 * mail an article
 *
 * This script has a form to post a mail message based on an existing article.
 *
 * If the action parameter is empty, a menu is offered either:
 * - to invite a set of people to contribute
 * - or to send some feed-back to the initial author
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
 * If this article, or one of its anchor, specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
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

// what kind of action?
$action = '';
if(isset($_REQUEST['action']))
	$action = $_REQUEST['action'];
elseif(isset($context['arguments'][1]))
	$action = $context['arguments'][1];
$action = strip_tags($action);

// original poster should only invite other
if(!$action && isset($item['create_id']) && Surfer::get_id() && ($item['create_id'] == Surfer::get_id()))
	$action = 'invite';

// even non-registered posters
elseif(!$action && Surfer::is_logged() &&  isset($item['create_address']) && Surfer::get_email_address() && !strcmp($item['create_address'], Surfer::get_email_address()))
	$action = 'invite';

// maybe this anonymous surfer is allowed to handle this item
if(isset($item['handle']) && Surfer::may_handle($item['handle']))
	Surfer::empower();

// associates and editors can do what they want
if(Surfer::is_empowered() || Articles::is_assigned($id) || (is_object($anchor) && $anchor->is_editable()))
	$permitted = TRUE;

// poster can always view the page
// elseif(isset($item['create_id']) && Surfer::get_id() && ($item['create_id'] == Surfer::get_id()))
//	$permitted = TRUE;

// maybe this article cannot be modified anymore
elseif(isset($item['locked']) && ($item['locked'] == 'Y'))
	$permitted = FALSE;

// surfer created the page and the page has not been published
elseif(Surfer::get_id() && isset($item['create_id']) && ($item['create_id'] == Surfer::get_id())
	&& (!isset($item['publish_date']) || ($item['publish_date'] <= NULL_DATE)) )
	$permitted = TRUE;

// surfer has created the published page and revisions are allowed
elseif(Surfer::get_id() && isset($item['create_id']) && ($item['create_id'] == Surfer::get_id())
	&& isset($item['publish_date']) && ($item['publish_date'] > NULL_DATE) && (!isset($context['users_without_revision']) || ($context['users_without_revision'] != 'Y')))
	$permitted = TRUE;

// the anchor has to be viewable by this surfer
elseif(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;

// access is restricted to authenticated member
elseif(isset($item['active']) && ($item['active'] == 'R') && Surfer::is_member())
	$permitted = TRUE;

// public access is allowed to authenticated members
elseif(isset($item['active']) && ($item['active'] == 'Y') && Surfer::is_member())
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load localized strings
i18n::bind('articles');

// load the skin, maybe with a variant
load_skin('articles', $anchor, isset($item['options']) ? $item['options'] : '');

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor))
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'articles/' => i18n::s('Articles') );
if(isset($item['id']))
	$context['path_bar'] = array_merge($context['path_bar'], array(Articles::get_url($item['id'], 'view', $item['title']) => $item['title']));

// the title of the page
if(isset($labels['title']) && $labels['title'])
	$context['page_title'] = $labels['title'];
else
	$context['page_title'] = i18n::s('Mail a page');

// command to go back
if(isset($item['id']))
	$context['page_menu'] = array( Articles::get_url($item['id'], 'view', $item['title']) => i18n::s('Back to the page') );

// not found
if(!isset($item['id'])) {
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
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Articles::get_url($item['id'], 'mail', 'invite')));

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

		// change content for message poster
		if(!strcmp($recipient, $from))
			$actual_message = i18n::s('This is a copy of the message you have sent, for your own record.')."\n".'-------'."\n\n".$actual_message;

		// post in debug mode, to get messages, if any
		if(Mailer::post($from, $actual_recipient, $subject, $actual_message, $headers, 'articles/mail.php')) {
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
} elseif($action == 'invite') {

	// page title
	$context['page_title'] = i18n::s('Invite people to review and to contribute');

	// referenced page
	$context['text'] .= '<p>'.Skin::build_link(Articles::get_url($item['id']), $item['title'])."</p>\n";

	// splash message
	$context['text'] .= '<p>'.i18n::s('Recipient addresses put in this page are used only once, to send your message, and are not stored afterwards.').'</p>';

	// the form to send a message
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>';

	// the recipient
	$label = i18n::s('People to invite');
	$input = '<textarea name="to" id="names" rows="3" cols="50"></textarea><div id="names_choices" class="autocomplete"></div>';
	$hint = i18n::s('Enter nick names, or email addresses, separated by commas, of all persons allowed to contribute to this page.');
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
	if($name = Surfer::get_name())
		$title = sprintf(i18n::s('You are invited by %s'), $name);
	else
		$title = i18n::s('You are invited to collaborate');
	$input = '<input type="text" name="subject" size="45" maxlength="255" value="'.encode_field($title).'" />';
	$fields[] = array($label, $input);

	// message author
	$author = Surfer::get_name();
	if($author_id = Surfer::get_id())
		$author .= "\n".$context['url_to_home'].$context['url_to_root'].Users::get_url($author_id, 'view', Surfer::get_name());

	// the message
	$label = i18n::s('Message content');
	if(isset($item['create_id']) && Surfer::get_id() && ($item['create_id'] == Surfer::get_id()))
		$content = sprintf(i18n::s("I have created a web page and would like you to review it and to contribute. \n\n%s\n\nPlease let me thank you for your kind support.\n\n%s"), $context['url_to_home'].$context['url_to_root'].Articles::get_url($item['id']), $author);
	else
		$content = sprintf(i18n::s("You are invited personnally to check the the following page, and to contribute accordingly.\n\n%s\n\nPlease let me thank you for your kind support.\n\n%s"), $context['url_to_home'].$context['url_to_root'].Articles::get_url($item['id']), $author);

	$input = '<textarea name="message" rows="15" cols="50">'.encode_field($content).'</textarea>';
	$hint = i18n::s('Use only plain ASCII, no HTML.');
	$fields[] = array($label, $input, $hint);

	// build the form
	$context['text'] .= Skin::build_form($fields);

	// the submit button
	$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

	// transmit the id as a hidden field
	$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'" />';
	$context['text'] .= '<input type="hidden" name="action" value="invite" />';

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

// a form to send some feed-back
} elseif($action == 'feed-back') {

	// page title
	$context['page_title'] = i18n::s('Provide feed-back to page author');

	// referenced page
	$context['text'] .= '<p>'.Skin::build_link(Articles::get_url($item['id']), $item['title'])."</p>\n";

	// the form to send a message
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>';

	// the recipient
	$label = i18n::s('Recipient e-mail address');
	if(isset($item['create_address']) && $item['create_address'])
		$recipient = 'value="'.encode_field($item['create_address']).'"';
	elseif(isset($context['site_email']) && $context['site_email'])
		$recipient = 'value="'.encode_field($context['site_email']).'"';
	else
		$recipient = '';
	$input = '<input type="text" name="to" id="to" size="45" maxlength="255" '.$recipient.'/>';
	$hint = i18n::s('The recipient to receive feed-back.');
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
	$input = '<input type="text" name="subject" size="45" maxlength="255" value="'.encode_field(isset($item['title']) ? $item['title'] : '').'" />';
	$fields[] = array($label, $input);

	// the message
	$label = i18n::s('Message content');
	$content = '';

	// some introductory text for this article
	include_once $context['path_to_root'].'articles/article.php';
	$article =& new Article();
	$article->load_by_content($item);

	// link to the original page
	$content = sprintf(i18n::s("You have written:\n\n%s\n\n%s"), $article->get_teaser('teaser'), $context['url_to_home'].$context['url_to_root'].Articles::get_url($item['id']));

	// no HTML in mail messages
	$content = strip_tags(preg_replace(array('/(<br>|<br\/>|<br \/>)/i', '/<\/td>/i', '/<ol>/i', '/<ul>/i', '/<li>/i'), array("\n", "</td>\t", "<ol>\n", "<ul>\n", "<li>- "), $content));

	$input = '<textarea name="message" rows="15" cols="50">'.encode_field($content).'</textarea>';
	$hint = i18n::s('Use only plain ASCII, no HTML.');
	$fields[] = array($label, $input, $hint);

	// build the form
	$context['text'] .= Skin::build_form($fields);

	// the submit button
	$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

	// transmit the id as a hidden field
	$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'" />';
	$context['text'] .= '<input type="hidden" name="action" value="feed-back" />';

	// end of the form
	$context['text'] .= '</div></form>';

	// append the script used for data checking on the browser
	$context['text'] .= '<script type="text/javascript">'."\n"
		.'<!--'."\n"
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
		.'document.getElementById(\'to\').focus();'."\n"
		."\n"
		.'// ]]></script>';

// which action?
} else {

	// the splash message
	$context['text'] .= '<p>'.i18n::s('Please select below the kind of message you would like to send.')."</p>\n";

	// the form
	$context['text'] .= '<form method="get" action="'.$context['script_url'].'" id="main_form"><div>';

	// invite other people to contribute
	$context['text'] .= '<p><input type="radio" name="action" id="action" value="invite" /> '.i18n::s('Invite people to review and to contribute').'</p>';

	// provide some feed-back
	$context['text'] .= '<p><input type="radio" name="action" id="action" value="feed-back" /> '.i18n::s('Provide feed-back to page author').'</p>';

	// the submit button
	$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Start')).'</p>'."\n";

	// transmit the id as a hidden field
	$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'" />';

	// end of the form
	$context['text'] .= '</div></form>';

	// set the focus on the button
	$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
		.'document.getElementById("action").focus();'."\n"
		.'// ]]></script>'."\n";

}

// render the skin
render_skin();

?>