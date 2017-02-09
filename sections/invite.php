<?php
/**
 * invite participants to a section
 *
 * This script has a form to post a mail message related to an existing page.
 *
 * When a message is sent to invited people, these may, or not, be part of
 * the community. For each unknown mail address added by the sender, a new user profile
 * is created automatically and a notification is sent by e-mail to this recipient for further
 * reference.
 *
 * Senders can get a copy of messages if they want.
 *
 * This script is able to interact with the page overlay, by calling various functions:
 * - get_invite_attachments() - to complement the message (e.g., to join .ics file)
 * - get_invite_default_message() - to adapt the message to page content
 * - invite() - remember the id of some invitee (e.g., for event enrolment)
 *
 * This script localize string of the user interface as usual. However, content of the
 * default invitation is localized according to server/community main settings.
 *
 * Long lines of the message are wrapped according to [link=Dan's suggestion]http://mailformat.dan.info/body/linelength.html[/link].
 *
 * @link http://mailformat.dan.info/body/linelength.html Dan's Mail Format Site: Body: Line Length
 *
 * Messages are sent using utf-8, and are either base64-encoded, or send as-is.
 *
 * @link http://www.sitepoint.com/article/advanced-email-php/3 Advanced email in PHP
 *
 * If the file [code]parameters/demo.flag[/code] exists, the script assumes that this instance
 * of YACS runs in demonstration mode, and no message is actually posted.
 *
 * Accepted calls:
 * - invite.php/&lt;id&gt;
 * - invite.php?id=&lt;id&gt;
 * - invite.php?id=&lt;id&gt;&amp;invited=&lt;invited_id&gt;
 *
 * If this section, or one of its anchor, specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
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
$item = Sections::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']))
	$anchor = Anchors::get($item['anchor']);

// get the related overlay, if any
$overlay = NULL;
if(isset($item['overlay']))
	$overlay = Overlay::load($item, 'section:'.$item['id']);

// owners can do what they want
if(Sections::is_owned($item, $anchor, TRUE))
	Surfer::empower();

// associates and editors can do what they want
if(Sections::is_owned($item, $anchor, TRUE))
	$permitted = TRUE;

// help to share public items
elseif(isset($item['active']) && ($item['active'] == 'Y') && Surfer::is_member())
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
$context['path_bar'] = Surfer::get_path_bar($anchor, FALSE);
if(isset($item['id']))
	$context['path_bar'] = array_merge($context['path_bar'], array(Sections::get_permalink($item) => $item['title']));

// page title
$context['page_title'] = i18n::s('Invite participants');

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
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Sections::get_url($item['id'], 'invite')));

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// no mail in demo mode
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST') && file_exists($context['path_to_root'].'parameters/demo.flag')) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation in demonstration mode.'));

// stop robots
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST') && Surfer::may_be_a_robot()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('Please prove you are not a robot.'));

// process submitted data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// ensure the section has a private handle
	if(!isset($item['handle']) || !$item['handle']) {
		$item['handle'] = md5(mt_rand());

		// save in the database
		$fields = array();
		$fields['id'] = $item['id'];
		$fields['handle'] = $item['handle'];
		$fields['silent'] = 'Y';
		Sections::put_attributes($fields);
	}

	// track anonymous surfers
	Surfer::track($_REQUEST);

	// message subject
	$subject = '';
	if(isset($_REQUEST['subject']))
		$subject = strip_tags($_REQUEST['subject']);

	// recipient(s) address(es)
	$to = '';
	if(isset($_REQUEST['to']))
		$to = $_REQUEST['to'];

	// make an array of recipients
	if(!is_array($to))
		$to = Mailer::explode_recipients($to);

	// add selected recipients
	if(isset($_REQUEST['selected_users']) && @count($_REQUEST['selected_users'])) {

		foreach($_REQUEST['selected_users'] as $dummy => $id)
			$to[] = $id;

	}

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
		if(!$user = Users::lookup($recipient)) {

			// skip this recipient
			if($recipient)
				Logger::error(sprintf(i18n::s('Error while sending the message to %s'), $recipient));
			continue;

		}

		// make this user an editor of the target item
		if(($item['active'] == 'N')
			|| (Sections::is_owned($item, $anchor, TRUE) && isset($_REQUEST['provide_credentials']) && ($_REQUEST['provide_credentials'] == 'Y')))
			Members::assign('user:'.$user['id'], 'section:'.$item['id']);

		// always add the item to the watch list
		Members::assign('section:'.$item['id'], 'user:'.$user['id']);

		// propagate the invitation to the overlay, if applicable
		if(is_callable(array($overlay, 'invite')))
			$overlay->invite($user['id']);

		// this person has no valid email address
		if(!$user['email'] || !preg_match(VALID_RECIPIENT, $user['email']))
			continue;

		// use this email address
		if($user['full_name'])
			$recipient = Mailer::encode_recipient($user['email'], $user['full_name']);
		else
			$recipient = Mailer::encode_recipient($user['email'], $user['nick_name']);

		// headline
		$headline = sprintf(i18n::c('%s has invited you to %s'),
			Surfer::get_link(),
			'<a href="'.Sections::get_permalink($item).'">'.$item['title'].'</a>');

		// build the full message
		if(isset($_REQUEST['message']))
			$message = '<div>'.$_REQUEST['message'].'</div>';

		else
			$message = '<p>'.i18n::c('I would like to invite you to the following page.').'</p>'
				.'<p><a href="'.Sections::get_permalink($item).'">'.$item['title'].'</a></p>';

		// change content for message poster
		if(strpos(Surfer::from(), $user['email']) !== FALSE) {
			$message = '<hr /><p>'.i18n::c('This is a copy of the message you have sent, for your own record.').'</p><p>'.join(', ', $actual_names).'</p><hr />'.$message;
		}

		// allow the overlay to filter message content
		if(is_callable(array($overlay, 'filter_invite_message')))
			$message = $overlay->filter_invite_message($message);

		// assemble main content of this message
		$message = Skin::build_mail_content($headline, $message);

		// a set of links
		$menu = array();

		// call for action
		$link = Sections::get_permalink($item);
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

		// provide a link that also authenticates surfers on click-through --see users/login.php
		$message = str_replace(array(Sections::get_permalink($item),
			str_replace('&', '&amp;', Sections::get_permalink($item))),
			$context['url_to_root'].Users::get_login_url('visit', 'section:'.$item['id'], $user['id'], $item['handle']), $message);

		// threads messages
		$headers = Mailer::set_thread('section:'.$item['id']);

		// get attachments from the overlay, if any
		$attachments = NULL;
		if(is_callable(array($overlay, 'get_invite_attachments')))
			$attachments = $overlay->get_invite_attachments('PUBLISH');

		// post it
		if(Mailer::notify(Surfer::from(), $recipient, $subject, $message, $headers, $attachments))
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

// a form to send an invitation to several people
} else {

	// the form to send a message
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>';
	$fields = array();

	// additional fields for anonymous surfers
	if(!Surfer::is_logged()) {

		// splash
		$login_url = $context['url_to_root'].'users/login.php?url='.urlencode(Sections::get_url($item['id'], 'invite'));
		$context['text'] .= '<p>'.sprintf(i18n::s('If you have previously registered to this site, please %s. Then the server will automatically put your name and address in following fields.'), Skin::build_link($login_url, i18n::s('authenticate')))."</p>\n";

		// the name, if any
		$label = i18n::s('Your name');
		$input = '<input type="text" name="edit_name" size="45" maxlength="128" accesskey="n" value="'.encode_field(Surfer::get_name(' ')).'" />';
		$hint = i18n::s('Let us a chance to know who you are');
		$fields[] = array($label, $input, $hint);

		// the address, if any
		$label = i18n::s('Your e-mail address');
		$input = '<input type="text" name="edit_address" size="45" maxlength="128" accesskey="a" value="'.encode_field(Surfer::get_email_address()).'" />';
		$hint = i18n::s('Put your e-mail address to receive feed-back');
		$fields[] = array($label, $input, $hint);

		// stop robots
		if($field = Surfer::get_robot_stopper())
			$fields[] = $field;

	}

	// recipients
	$label = i18n::s('Invite participants');
	$input = '';
	if(Sections::is_owned($item, $anchor, TRUE)) {

		// roles are defined as per invitation settings
		if(is_callable(array($overlay, 'get_invite_roles')))
			$input .= $overlay->get_invite_roles();

		// standard roles for non private pages
		elseif($item['active'] != 'N')
			$input .= '<p><input type="radio" name="provide_credentials" value="N" checked="checked" /> '.i18n::s('to review public content (watchers)')
				.BR.'<input type="radio" name="provide_credentials" value="Y" /> '.i18n::s('to manage public and private content (editors)').'</p>'
				.'<hr/>';
	}

	// get a customized layout
	$layout = Layouts::new_('mail', 'user');

	// avoid links to this page
	if(is_object($layout) && is_callable(array($layout, 'set_variant')))
		$layout->set_variant('unchecked');

	// pre-invite someone
	$invited = '';
	if(isset($_REQUEST['invited']) && ($user = Users::get($_REQUEST['invited'])))
		$invited = $user['nick_name'];

	// list selectable groups of people
	else {
		$handle = $item['anchor'];
		while($handle && ($parent = Anchors::get($handle))) {
			$handle = $parent->get_parent();

			// invitation to a private page should be limited to editors
			if($item['active'] == 'N') {

				if($editors = Members::list_editors_for_member($parent->get_reference(), 0, 1000, $layout))
					$input .= Skin::build_box(sprintf(i18n::s('Invite editors of %s'), $parent->get_title()), Skin::build_list($editors, 'compact'), 'folded');

			// else invitation should be extended to watchers
			} else {

				if($watchers = Members::list_watchers_by_name_for_anchor($parent->get_reference(), 0, 1000, $layout))
					$input .= Skin::build_box(sprintf(i18n::s('Invite watchers of %s'), $parent->get_title()), Skin::build_list($watchers, 'compact'), 'folded');

			}
		}
	}

	// add some names manually
	$input .= Skin::build_box(i18n::s('Invite some persons'), '<textarea name="to" id="names" rows="3" cols="50">'.$invited.'</textarea><div><span class="tiny">'.i18n::s('Enter nick names, or email addresses, separated by commas.').'</span></div>', 'unfolded');

	// combine all these elements
	$fields[] = array($label, $input);

	// the subject
	$label = i18n::s('Message title');
	if(is_object($overlay))
		$title = $overlay->get_live_title($item);
	else
		$title = $item['title'];
	$title = sprintf(i18n::c('Invitation: %s'), $title);
	$input = '<input type="text" name="subject" size="50" maxlength="255" value="'.encode_field($title).'" />';
	$fields[] = array($label, $input);

	// default message content
	$content = '';
	if(is_callable(array($overlay, 'get_invite_default_message')))
		$content = $overlay->get_invite_default_message();
	if(!$content)
		$content = '<p>'.i18n::c('I would like to invite you to the following page.').'</p>'
			.'<p><a href="'.Sections::get_permalink($item).'">'.$item['title'].'</a></p>'
			.'<p>'.i18n::c('Please let me thank you for your involvement.').'</p>'
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
	$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');

	// cancel button
	if(isset($item['id']))
		$menu[] = Skin::build_link(Sections::get_permalink($item), i18n::s('Cancel'), 'span');

	// insert the menu in the page
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// get a copy of the sent message
	$context['text'] .= '<p><input type="checkbox" name="self_copy" value="Y" checked="checked" /> '.i18n::s('Send me a copy of this message.').'</p>';

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
		.'		alert("'.i18n::s('Message content can not be empty.').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		return false;'."\n"
		.'	}'."\n"
			// successful check
		.'	return true;'."\n"
		.'}'."\n"
		.'$(function() {'."\n"
		.'	$("#names").focus();'."\n" // set the focus on first form field
		.'	Yacs.autocomplete_names("names");'."\n" // enable names autocompletion
		.'});  '."\n"
		);

	// help message
	$help = '<p>'.i18n::s('New e-mail addresses are converted to new user profiles. Because of this, you should not use e-mail addresses that have multiple recipients.').'</p>';
	$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'boxes', 'help');

}

// render the skin
render_skin();

?>
