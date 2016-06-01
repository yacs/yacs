<?php
/**
 * manage enrolment
 *
 * Accepted calls:
 * - enroll.php/article/&lt;id&gt;
 * - enroll.php?id=&lt;article:id&gt;
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../../shared/global.php';

// look for the id --actually, a reference
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]) && isset($context['arguments'][1]))
	$id = $context['arguments'][0].':'.$context['arguments'][1];
$id = strip_tags($id);

// get the anchor
$anchor = Anchors::get($id);

// get the related overlay, if any
$overlay = NULL;
if(is_object($anchor)) {
	$fields = array();
	$fields['id'] = $anchor->get_value('id');
	$fields['overlay'] = $anchor->get_value('overlay');
	$overlay = Overlay::load($fields, $anchor->get_reference());
}

// load the skin, maybe with a variant
load_skin('articles', $anchor);

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// not found
} elseif(!is_object($anchor)) {
	include '../../error.php';

// permission denied
} elseif(!$anchor->is_owned()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// no overlay
} elseif(!is_object($overlay))
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// an error occured
elseif(count($context['error']))
	;

// display the enrolment list
else {

	// enrolled persons
	$enrolled_names = array();

	// drop a participant
	if(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'drop') && isset($_REQUEST['target']) && $_REQUEST['target']) {

		// list enrolment for this meeting
		$query = "SELECT * FROM ".SQL::table_name('enrolments')." WHERE id = ".SQL::escape($_REQUEST['target']);
		if(($result = SQL::query_first($query)) && ($user = Users::get($result['user_id']))) {

			// confirm cancellation by e-mail
			if($user['email'] && preg_match(VALID_RECIPIENT, $user['email'])) {

				// use this email address
				if($user['full_name'])
					$recipient = Mailer::encode_recipient($user['email'], $user['full_name']);
				else
					$recipient = Mailer::encode_recipient($user['email'], $user['nick_name']);

				// mail subject
				$subject = sprintf(i18n::c('%s: %s'), i18n::c('Cancellation'), strip_tags($anchor->get_title()));

				// headline
				$headline = sprintf(i18n::c('%s has cancelled your participation to %s'),
					Surfer::get_link(),
					'<a href="'.$context['url_to_home'].$context['url_to_root'].$anchor->get_url().'">'.$anchor->get_title().'</a>');

				// message confirmation
				$message = $overlay->get_invite_default_message('CANCEL');

				// assemble main content of this message
				$message = Skin::build_mail_content($headline, $message);

				// a set of links
				$menu = array();

				// call for action
				$link = $context['url_to_home'].$context['url_to_root'].$anchor->get_url();
				$menu[] = Skin::build_mail_button($link, $anchor->get_title(), TRUE);

				// finalize links
				$message .= Skin::build_mail_menu($menu);

				// threads messages
				$headers = Mailer::set_thread($anchor->get_reference());

				// get attachments from the overlay, if any
				$attachments = $overlay->get_invite_attachments('CANCEL');

				// send the message
				Mailer::notify(Surfer::from(), $recipient, $subject, $message, $headers, $attachments);

			}
		}

		// drop enrolment record
		$query = "DELETE FROM ".SQL::table_name('enrolments')." WHERE id = ".SQL::escape($_REQUEST['target']);
		SQL::query($query);

	}

	// validate an application
	if(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'validate') && isset($_REQUEST['target']) && $_REQUEST['target']) {

		// update enrolment record
		$query = "UPDATE ".SQL::table_name('enrolments')." SET approved = 'Y' WHERE id = ".SQL::escape($_REQUEST['target']);
		SQL::query($query);

		// list enrolment for this meeting
		$query = "SELECT * FROM ".SQL::table_name('enrolments')." WHERE id = ".SQL::escape($_REQUEST['target']);
		if(($result = SQL::query_first($query)) && ($user = Users::get($result['user_id']))) {

			// add the page to the watch list
			Members::assign($anchor->get_reference(), 'user:'.$user['id']);

			// ensure that the enrolled person can access private pages
			if($anchor->is_hidden())
				Members::assign('user:'.$user['id'], $anchor->get_reference());

			// confirm enrolment by e-mail
			if($user['email'] && preg_match(VALID_RECIPIENT, $user['email'])) {

				// use this email address
				if($user['full_name'])
					$recipient = Mailer::encode_recipient($user['email'], $user['full_name']);
				else
					$recipient = Mailer::encode_recipient($user['email'], $user['nick_name']);

				// mail subject
				$subject = sprintf(i18n::c('%s: %s'), i18n::c('Meeting'), strip_tags($anchor->get_title()));

				// headline
				$headline = sprintf(i18n::c('%s has confirmed your participation to %s'),
					Surfer::get_link(),
					'<a href="'.$context['url_to_home'].$context['url_to_root'].$anchor->get_url().'">'.$anchor->get_title().'</a>');

				// message confirmation
				$message = $overlay->get_invite_default_message('PUBLISH');

				// assemble main content of this message
				$message = Skin::build_mail_content($headline, $message);

				// a set of links
				$menu = array();

				// call for action
				$link = $context['url_to_home'].$context['url_to_root'].$anchor->get_url();
				$menu[] = Skin::build_mail_button($link, $anchor->get_title(), TRUE);

				// finalize links
				$message .= Skin::build_mail_menu($menu);

				// threads messages
				$headers = Mailer::set_thread($anchor->get_reference());

				// get attachments from the overlay, if any
				$attachments = $overlay->get_invite_attachments('PUBLISH');

				// send the message
				Mailer::notify(Surfer::from(), $recipient, $subject, $message, $headers, $attachments);

				// report on this notification
				$enrolled_names[] = htmlspecialchars($recipient);

			}

		}

	}

	// display the list of actual recipients
	if($enrolled_names)
		$context['text'] .= '<div style="margin-bottom: 2em">'.Skin::build_block(i18n::s('Following persons have been notified of their enrolment by e-mail').Skin::finalize_list($enrolled_names, 'compact'), 'note').'</div>';

	// splash
	switch($overlay->get_value('enrolment')) {
	case 'manual':
		$context['text'] .= '<p>'.i18n::s('Registration is managed by page owner. You have to invite participants manually, and they will be notified by e-mail about this event.').'</p>';
		break;
	case 'none':
	default:
		$context['text'] .= '<p>'.i18n::s('Enrolment has been configured to accept any visitor. You may invite some participants to draw their attention on this event.').'</p>';
		break;
	case 'validate':
		$context['text'] .= '<p>'.i18n::s('Visitors are entitled to ask for participation, and enrolment is confirmed by page owner. Check the list below to validate applications, and to confirm registrations by e-mail.').'</p>';
		break;
	}

	// list enrolment for this meeting
	$query = "SELECT * FROM ".SQL::table_name('enrolments')." WHERE anchor LIKE '".SQL::escape($anchor->get_reference())."'";
	if($result = SQL::query($query)) {

		// splash message
		$context['text'] .= '<div style="margin-top: 2em;">'.i18n::s('Current enrolment');

		// browse the list
		$items = array();
		while($item = SQL::fetch($result)) {

			// one item at a time
			$line = '';

			// a user registered on this server
			if($item['user_id'] && ($user = Users::get($item['user_id']))) {

				// make an url
				$url = Users::get_permalink($user);

				// gather information on this user
				if(isset($user['full_name']) && $user['full_name'])
					$label = $user['full_name'].' ('.$user['nick_name'].')';
				else
					$label = $user['nick_name'];

				$line .= Skin::build_link($url, $label, 'user');

			// we only have some e-mail address
			} else
				$line .= $item['user_email'];

			// application has not been validated yet
			if($item['approved'] != 'Y') {
				$link = $context['script_url'].'?id='.$anchor->get_reference().'&amp;target='.$item['id'].'&amp;action=validate';
				$line .= ' - '.Skin::build_link($link, i18n::s('validate'), 'basic');
			}

			// allow to kill some registration
			$link = $context['script_url'].'?id='.$anchor->get_reference().'&amp;target='.$item['id'].'&amp;action=drop';
			$line .= ' - <span '.tag::_class('details').'>'.Skin::build_link($link, i18n::s('drop'), 'basic').'</span>';

			// next item
			$items[] = $line;

		}

		// shape a compact list
		if(count($items))
			$context['text']  .= Skin::finalize_list($items, 'compact');

		// close the enrolment list
		$context['text'] .= '</div>';
	}

	// follow-up commands
	$menu = array();
	$menu[] = Skin::build_link($anchor->get_url(), i18n::s('Done'), 'button');
	$menu[] = Skin::build_link($anchor->get_url('invite'), i18n::s('Invite participants'), 'span');
	$context['text'] .= Skin::build_block(Skin::finalize_list($menu, 'menu_bar'), 'bottom');

}

// page title
if(is_object($anchor))
	$context['page_title'] = $anchor->get_title();

// render the skin
render_skin();

?>
