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
include_once '../../overlays/overlay.php';

// look for the id --actually, a reference
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]) && isset($context['arguments'][1]))
	$id = $context['arguments'][0].':'.$context['arguments'][1];
$id = strip_tags($id);

// get the anchor
$anchor =& Anchors::get($id);

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

		$query = "DELETE FROM ".SQL::table_name('enrolments')." WHERE id = ".SQL::escape($_REQUEST['target']);
		SQL::query($query);

	}

	// enroll a full list
	if(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'enroll') && isset($_REQUEST['assign_list']) && $_REQUEST['assign_list']) {

		// enrolment to a private page should be limited to editors
		if($anchor->is_hidden())
			$users =& Members::list_users_by_posts_for_member($_REQUEST['assign_list'], 0, 50*USERS_LIST_SIZE, 'raw');

		// else enrolment should be extended to watchers
		else
			$users =& Members::list_users_by_posts_for_anchor($_REQUEST['assign_list'], 0, 50*USERS_LIST_SIZE, 'raw');

		// list members
		if(count($users)) {

			// enroll each member separately
			foreach($users as $id => $user) {

				// add the page to the watch list
				Members::assign($anchor->get_reference(), 'user:'.$user['id']);

				// ensure that the enrolled person can access private pages
				if($anchor->is_hidden())
					Members::assign('user:'.$user['id'], $anchor->get_reference());

				// if there is no enrolment record yet
				$query = "SELECT id FROM ".SQL::table_name('enrolments')." WHERE (anchor LIKE '".$anchor->get_reference()."') AND (user_id = ".SQL::escape($user['id']).")";
				if(!SQL::query_count($query)) {

					// fields to save
					$query = array();

					// reference to the meeting page
					$query[] = "anchor = '".$anchor->get_reference()."'";

					// direct enrolment
					$query[] = "approved = 'Y'";

					// save user id
					$query[] = "user_id = ".SQL::escape($user['id']);

					// save user e-mail address
					$query[] = "user_email = '".SQL::escape($user['email'])."'";

					// insert a new record
					$query = "INSERT INTO ".SQL::table_name('enrolments')." SET ".implode(', ', $query);
					SQL::query($query);

					// confirm enrolment by e-mail
					if($user['email'] && preg_match(VALID_RECIPIENT, $user['email'])) {

						// use this email address
						if($user['full_name'])
							$recipient = Mailer::encode_recipient($user['email'], $user['full_name']);
						else
							$recipient = Mailer::encode_recipient($user['email'], $user['nick_name']);

						// mail message
						$mail = array();

						// mail subject
						$mail['subject'] = sprintf(i18n::c('Invitation: %s'), strip_tags($anchor->get_title()));

						// message confirmation
						$action = sprintf(i18n::c('You have been enrolled by %s'), Surfer::get_name());
						$title = strip_tags($anchor->get_title());
						$link = $context['url_to_home'].$context['url_to_root'].$anchor->get_url();
						$mail['message'] =& Mailer::build_notification($action, $title, $link);

						// threads messages
						$mail['headers'] = Mailer::set_thread($anchor->get_reference());

						// send the message
						Mailer::notify(Surfer::from(), $recipient, $mail['subject'], $mail['message'], $mail['headers']);

						// report on this notification
						$enrolled_names[] = htmlspecialchars($recipient);

					}
				}
			}
		}
	}

	// enroll a new person
	if(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'enroll') && isset($_REQUEST['assigned_name']) && $_REQUEST['assigned_name']) {

		// from an existing user profile
		if($user = Users::get($_REQUEST['assigned_name'])) {

			// add the page to the watch list
			Members::assign($anchor->get_reference(), 'user:'.$user['id']);

			// ensure that the enrolled person can access private pages
			if($anchor->is_hidden())
				Members::assign('user:'.$user['id'], $anchor->get_reference());

			// if there is no enrolment record yet
			$query = "SELECT id FROM ".SQL::table_name('enrolments')." WHERE (anchor LIKE '".$anchor->get_reference()."') AND (user_id = ".SQL::escape($user['id']).")";
			if(!SQL::query_count($query)) {

				// fields to save
				$query = array();

				// reference to the meeting page
				$query[] = "anchor = '".$anchor->get_reference()."'";

				// direct enrolment
				$query[] = "approved = 'Y'";

				// save user id
				$query[] = "user_id = ".SQL::escape($user['id']);

				// save user e-mail address
				$query[] = "user_email = '".SQL::escape($user['email'])."'";

				// insert a new record
				$query = "INSERT INTO ".SQL::table_name('enrolments')." SET ".implode(', ', $query);
				SQL::query($query);

				// confirm enrolment by e-mail
				if($user['email'] && preg_match(VALID_RECIPIENT, $user['email'])) {

					// use this email address
					if($user['full_name'])
						$recipient = Mailer::encode_recipient($user['email'], $user['full_name']);
					else
						$recipient = Mailer::encode_recipient($user['email'], $user['nick_name']);

					// mail message
					$mail = array();

					// mail subject
					$mail['subject'] = sprintf(i18n::c('Invitation: %s'), strip_tags($anchor->get_title()));

					// message confirmation
					$action = sprintf(i18n::c('You have been enrolled by %s'), Surfer::get_name());
					$title = strip_tags($anchor->get_title());
					$link = $context['url_to_home'].$context['url_to_root'].$anchor->get_url();
					$mail['message'] =& Mailer::build_notification($action, $title, $link);

					// threads messages
					$mail['headers'] = Mailer::set_thread($anchor->get_reference());

					// send the message
					Mailer::notify(Surfer::from(), $recipient, $mail['subject'], $mail['message'], $mail['headers']);

					// report on this notification
					$enrolled_names[] = htmlspecialchars($recipient);

				}

			}

		// use provided mail address
		} else {

			// if there is no enrolment record yet
			$query = "SELECT id FROM ".SQL::table_name('enrolments')." WHERE (anchor LIKE '".$anchor->get_reference()."') AND (user_email LIKE '".SQL::escape($_REQUEST['assigned_name'])."')";
			if(!SQL::query_count($query)) {

				// fields to save
				$query = array();

				// reference to the meeting page
				$query[] = "anchor = '".$anchor->get_reference()."'";

				// direct enrolment
				$query[] = "approved = 'Y'";

				// save user id
				$query[] = "user_id = 0";

				// save user e-mail address
				$query[] = "user_email = '".SQL::escape($_REQUEST['assigned_name'])."'";

				// insert a new record
				$query = "INSERT INTO ".SQL::table_name('enrolments')." SET ".implode(', ', $query);
				SQL::query($query);

			}

		}

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

				// mail message
				$mail = array();

				// mail subject
				$mail['subject'] = sprintf(i18n::c('Invitation: %s'), strip_tags($anchor->get_title()));

				// message confirmation
				$action = sprintf(i18n::c('You have been enrolled by %s'), Surfer::get_name());
				$title = strip_tags($anchor->get_title());
				$link = $context['url_to_home'].$context['url_to_root'].$anchor->get_url();
				$mail['message'] =& Mailer::build_notification($action, $title, $link);

				// threads messages
				$mail['headers'] = Mailer::set_thread($anchor->get_reference());

				// send the message
				Mailer::notify(Surfer::from(), $recipient, $mail['subject'], $mail['message'], $mail['headers']);

				// report on this notification
				$enrolled_names[] = htmlspecialchars($recipient);

			}

		}

	}

	// display the list of actual recipients
	if($enrolled_names)
		$context['text'] .= '<div style="margin-bottom: 2em">'.i18n::s('Following persons have been notified of their enrolment by e-mail').Skin::finalize_list($enrolled_names, 'compact').'</div>';

	// splash
	switch($overlay->get_value('enrolment')) {
	case 'manual':
		$context['text'] .= '<p>'.i18n::s('Registration is managed by page owner. You have to enroll all participants manually, and they will be notified by e-mail about this event.').'</p>';
		break;
	case 'none':
	default:
		$context['text'] .= '<p>'.i18n::s('Enrolment has been configured to accept any visitor. You may enroll manually some participants to draw their attention on this event.').'</p>';
		break;
	case 'validate':
		$context['text'] .= '<p>'.i18n::s('Visitors are entitled to ask for participation, and enrolment is confirmed by page owner. Check the list below to validate applications, and to confirm registrations by e-mail.').'</p>';
		break;
	}

	// list also selectable groups of people
	$items = array();
	$handle = $anchor->get_parent();
	while($handle && ($parent = Anchors::get($handle))) {
		$handle = $parent->get_parent();

		// the link to trigger the enrolment of this list
		$link = $context['script_url'].'?id='.$anchor->get_reference().'&amp;assign_list='.$parent->get_reference().'&amp;action=enroll';

		// enrolment to a private page should be limited to editors
		if($anchor->is_hidden()) {

			if($count = Members::count_users_for_member($parent->get_reference()))
				$items[] = Skin::build_link($link, sprintf(i18n::s('Persons assigned to %s'), $parent->get_title()).' ('.$count.')', 'basic');

		// else enrolment should be extended to watchers
		} else {

			if($count = Members::count_users_for_anchor($parent->get_reference()))
				$items[] = Skin::build_link($link, sprintf(i18n::s('Persons watching %s'), $parent->get_title()).' ('.$count.')', 'basic');

		}
	}

	if($items) {
		$context['text'] .= '<div style="margin-bottom: 1em;">'.i18n::s('To add several persons at once, use following lists').Skin::finalize_list($items, 'compact').'</div>';
	}

	// way of working
	$context['text'] .= '<p>'.i18n::s('To add a person, type some letters to look for a name, then select one profile at a time.').'</p>';

	// the form to link additional users
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'
		.'<input type="text" name="assigned_name" id="name" size="45" maxlength="255" /><div id="name_choices" class="autocomplete"></div> <span id="ajax_spinner" style="display: none"><img src="'.$context['url_to_root'].'skins/_reference/ajax/ajax_completer.gif" alt="Working..." /></span>'
		.'<input type="hidden" name="id" value="'.encode_field($anchor->get_reference()).'">'
		.'<input type="hidden" name="action" value="enroll">'
		.'</p></form>'."\n";

	// enable autocompletion
	$context['text'] .= JS_PREFIX
		."\n"
		.'// set the focus on first form field'."\n"
		.'Event.observe(window, "load", function() { $("name").focus() });'."\n"
		."\n"
		.'// enable tags autocompletion'."\n"
		.'Event.observe(window, "load", function() { new Ajax.Autocompleter("name", "name_choices", "'.$context['url_to_root'].'users/complete.php", { paramName: "q", minChars: 1, frequency: 0.4, tokens: ",", afterUpdateElement: function(text, li) { $("ajax_spinner").style.display = "inline"; $("main_form").submit() }, indicator: "ajax_spinner" }); });'."\n"
		.JS_SUFFIX;

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
				$line .= ' - <span class="details">'.Skin::build_link($link, i18n::s('validate'), 'basic').'</span>';
			}

			// allow to kill some registration
			$link = $context['script_url'].'?id='.$anchor->get_reference().'&amp;target='.$item['id'].'&amp;action=drop';
			$line .= ' - <span class="details">'.Skin::build_link($link, i18n::s('drop'), 'basic').'</span>';

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
	$context['text'] .= Skin::build_block(Skin::finalize_list($menu, 'menu_bar'), 'bottom');

}

// page title
if(is_object($anchor))
	$context['page_title'] = $anchor->get_title();

// render the skin
render_skin();

?>
