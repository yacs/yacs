<?php
/**
 * send a message to article participants
 *
 * If the file [code]parameters/demo.flag[/code] exists, the script assumes that this instance
 * of YACS runs in demonstration mode, and no message is actually posted.
 *
 * Accepted calls:
 * - mail.php/&lt;id&gt;
 * - mail.php?id=&lt;id&gt;
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
	$anchor =& Anchors::get($item['anchor']);

// get the related overlay, if any
$overlay = NULL;
include_once '../overlays/overlay.php';
if(isset($item['overlay']))
	$overlay = Overlay::load($item, 'article:'.$item['id']);

// owners can proceed
if(Articles::allow_message($item, $anchor))
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
$context['path_bar'] = Surfer::get_path_bar($anchor);
if(isset($item['id']) && $item['title'])
	$context['path_bar'] = array_merge($context['path_bar'], array(Articles::get_permalink($item) => $item['title']));

// page title
$context['page_title'] = i18n::s('Notify participants');

// recipients of a private page are all editors upwards
$recipients = array();
if(isset($item['active']) && ($item['active'] == 'N')) {
	$anchors = array_merge(array('article:'.$item['id']), $anchor->get_focus());
	$recipients = Members::list_editors_for_member($anchors, 0, 300, 'mail');

// recipients for a public page are watchers of it and of parent section
} elseif(is_object($anchor)) {
	$anchors = array('article:'.$item['id'], $anchor->get_reference());
	$recipients = Members::list_watchers_by_posts_for_anchor($anchors, 0, 300, 'mail');
}

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
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Articles::get_url($item['id'], 'mail')));

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// no mail in demo mode
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST') && file_exists($context['path_to_root'].'parameters/demo.flag')) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation in demonstration mode.'));

// process submitted data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// we are using surfer own address
	$from = Surfer::from();

	// recipient(s) address(es)
	$to = array();
	foreach($_REQUEST['selected_users'] as $address)
		$to[] = $address;

	// message subject
	$subject = '';
	if(isset($_REQUEST['subject']))
		$subject = strip_tags($_REQUEST['subject']);

	// enable yacs codes in messages
	$text = Codes::beautify($_REQUEST['message']);

	// preserve tagging as much as possible
	$message = Mailer::build_message($subject, $text);

	// send the message
	if(Mailer::post($from, $to, $subject, $message)) {

		// feed-back to the sender
		$context['text'] .= '<p>'.i18n::s('A message has been sent to:')."</p>\n".'<ul>'."\n";
		foreach($to as $address)
			$context['text'] .= '<li>'.encode_field($address).'</li>'."\n";
		$context['text'] .= '</ul>'."\n";

		// back to the section page
		$menu = array();
		$menu[] = Skin::build_link(Articles::get_permalink($item), i18n::s('Done'), 'button');
		$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	}
	Mailer::close();

// send message to all watchers
} elseif(!count($recipients)) {
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
	$input = '<input type="text" name="subject" id="subject" size="70" value="'.encode_field($item['title']).'" />';
	$fields[] = array($label, $input);

	// the message
	$label = i18n::s('Message content');
	$input = Surfer::get_editor('message', '<p>&nbsp;</p><p><a href="'.$context['url_to_home'].$context['url_to_root'].Articles::get_permalink($item).'">'.$item['title'].'</a></p>');
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
		$menu[] = Skin::build_link(Articles::get_permalink($item), i18n::s('Cancel'), 'span');

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
		.'$("subject").focus();'."\n"
		."\n"
		.JS_SUFFIX;

}

// render the skin
render_skin();

?>
