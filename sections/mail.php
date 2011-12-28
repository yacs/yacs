<?php
/**
 * send a message to section watchers
 *
 * If the file [code]parameters/demo.flag[/code] exists, the script assumes that this instance
 * of YACS runs in demonstration mode, and no message is actually posted.
 *
 * Accepted calls:
 * - mail.php/&lt;id&gt;
 * - mail.php?id=&lt;id&gt;
 *
 * @author Bernard Paques
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
$item =& Sections::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']))
	$anchor =& Anchors::get($item['anchor']);

// check surfer capability
if(Sections::allow_message($item, $anchor))
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load the skin, maybe with a variant
load_skin('sections', $anchor, isset($item['options']) ? $item['options'] : '');

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// path to this page
if(is_object($anchor))
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'sections/' => i18n::s('Site map') );

if(isset($item['id']) && $item['title'])
	$context['path_bar'] = array_merge($context['path_bar'], array(Sections::get_permalink($item) => $item['title'] ));

// page title
$context['page_title'] = i18n::s('Notify participants');

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

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Sections::get_url($item['id'], 'mail')));

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// no mail in demo mode
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST') && file_exists($context['path_to_root'].'parameters/demo.flag')) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation in demonstration mode.'));

// process submitted data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// recipient(s) address(es)
	$to = array();
	foreach($_REQUEST['selected_users'] as $address)
		$to[] = $address;

	// message subject
	$subject = '';
	if(isset($_REQUEST['subject']))
		$subject = strip_tags($_REQUEST['subject']);

	// headline
	$headline = sprintf(i18n::c('%s has notified you from %s'),
		'<a href="'.$context['url_to_home'].$context['url_to_root'].Surfer::get_permalink().'">'.Surfer::get_name().'</a>',
		'<a href="'.$context['url_to_home'].$context['url_to_root'].Sections::get_permalink($item).'">'.$item['title'].'</a>');

	// enable yacs codes in messages
	$content = Codes::beautify($_REQUEST['message']);

	// avoid duplicates
	$to = array_unique($to);

	// copy to sender
	if(isset($_REQUEST['self_copy']) && ($_REQUEST['self_copy'] == 'Y'))
		$to[] = Surfer::from();

	// process every recipient
	$actual_names = array();
	foreach($to as $recipient) {

		// clean the provided string
		$recipient = trim(str_replace(array("\r\n", "\r", "\n", "\t"), ' ', $recipient));

		// look for a user with this nick name
		if(!$user =& Users::lookup($recipient))
			continue;

		// this person has no valid email address
		if(!$user['email'] || !preg_match(VALID_RECIPIENT, $user['email']))
			continue;

		// use this email address
		if($user['full_name'])
			$recipient = Mailer::encode_recipient($user['email'], $user['full_name']);
		else
			$recipient = Mailer::encode_recipient($user['email'], $user['nick_name']);

		// basic message
		$message = $content;

		// change content for message poster
		if(strpos(Surfer::from(), $recipient) !== FALSE) {
			$message = '<hr /><p>'.i18n::s('This is a copy of the message you have sent, for your own record.').'</p><p>'.join(', ', $actual_names).'</p><hr />'.$message;
		}

		// assemble main content of this message
		$message = Skin::build_mail_content($headline, $message);

		// a set of links
		$menu = array();

		// call for action
		$link = $context['url_to_home'].$context['url_to_root'].Sections::get_permalink($item);
		if(!is_object($overlay) || (!$label = $overlay->get_label('permalink_command', 'sections', FALSE)))
			$label = i18n::c('View the section');
		$menu[] = Skin::build_mail_button($link, $label, TRUE);

		// link to the container
		if(is_object($anchor)) {
			$link = $context['url_to_home'].$context['url_to_root'].$anchor->get_url();
			$menu[] = Skin::build_mail_button($link, $anchor->get_title(), FALSE);
		}

		// finalize links
		$message .= Skin::build_mail_menu($menu);

		// threads messages
		$headers = Mailer::set_thread('', 'section:'.$item['id']);

		// post message for this recipient
		if(Mailer::notify(Surfer::from(), $recipient, $subject, $message, $headers))
			$actual_names[] = htmlspecialchars($recipient);

	}
	Mailer::close();

	// display the list of actual recipients
	if($actual_names)
		$context['text'] .= '<div>'.sprintf(i18n::s('Your message is being transmitted to %s'), Skin::finalize_list($actual_names, 'compact')).'</div>';
	else
		$context['text'] .= '<p>'.i18n::s('No message has been sent').'</p>';

	// back to the section page
	$menu = array();
	$menu[] = Skin::build_link(Sections::get_permalink($item), i18n::s('Done'), 'button');
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

// recipients are watchers of this section (including parent sections)
} elseif(!$recipients = Sections::list_watchers_by_posts($item, 0, 1000, 'mail')) {
	Logger::error(i18n::s('No recipient has been found.'));

// display the form
} else {

	// the form to send a message
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>';

	// build a nice list of recipients
	$label = i18n::s('Message recipients');
	$input = Skin::build_box(i18n::s('Select recipients'), $recipients, 'folded');
	$fields[] = array($label, $input);

	// the subject
	$label = i18n::s('Message title');
	$title = sprintf(i18n::s('Notification: %s'), $item['title']);
	$input = '<input type="text" name="subject" id="subject" size="70" value="'.encode_field($title).'" />';
	$fields[] = array($label, $input);

	// default message content
	$content = '<p>'.i18n::s('Can you review the following page and contribute to it where applicable?').'</p>'
		.'<p><a href="'.$context['url_to_home'].$context['url_to_root'].Sections::get_permalink($item).'">'.$item['title'].'</a></p>'
		.'<p>'.i18n::s('Please let me thank you for your involvement.').'</p>'
		.'<p>'.Surfer::get_name().'</p>';

	// the message
	$label = i18n::s('Message content');
	$input = Surfer::get_editor('message', $content);
	$fields[] = array($label, $input);

	// build the form
	$context['text'] .= Skin::build_form($fields);

	//
	// bottom commands
	//
	$menu = array();

	// the submit button
	$menu[] = Skin::build_submit_button(i18n::s('Send'), i18n::s('Press [s] to submit data'), 's');

	// cancel button
	if(isset($item['id']))
		$menu[] = Skin::build_link(Sections::get_permalink($item), i18n::s('Cancel'), 'span');

	// insert the menu in the page
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// transmit the id as a hidden field
	$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'" />';

	// end of the form
	$context['text'] .= '</div></form>';

	// append the script used for data checking on the browser
	$context['text'] .= JS_PREFIX
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
		.'$("#subject").focus();'."\n"
		."\n"
		.JS_SUFFIX;

}

// render the skin
render_skin();

?>
