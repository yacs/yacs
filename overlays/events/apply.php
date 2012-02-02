<?php
/**
 * ask to participate to an event
 *
 * Accepted calls:
 * - apply.php/article/&lt;id&gt;
 * - apply.php?id=&lt;article:id&gt;
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../../shared/global.php';
include_once '../../shared/enrolments.php';
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
} elseif(!$anchor->is_viewable()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// no overlay or no seat available
} elseif(!is_object($overlay) || (!$offer = $overlay->get_value('seats', 1000))) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// an error occured
} elseif(count($context['error']))
	;

// get a seat
elseif(!enrolments::get_seat($anchor->get_reference(), $offer))
	Logger::error(i18n::s('There is no seat left available for this event. We are sorry to not consider your application.'));

// surfer is not known --ask for credentials
elseif(!Surfer::get_id()) {
	$link = $context['url_to_home'].$context['url_to_root'].'overlays/events/apply.php?id='.urlencode($anchor->get_reference());
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode($link));

// proceed with the action
} else {

	// add the page to the watch list
	if(Surfer::get_id())
		Members::assign($anchor->get_reference(), 'user:'.Surfer::get_id());

	// look for surfer id, if any
	if(Surfer::get_id())
		$where = "user_id = ".SQL::escape(Surfer::get_id());

	// look for this e-mail address
	elseif(isset($_REQUEST['surfer_address']) && $_REQUEST['surfer_address'])
		$where = "user_email LIKE '".SQL::escape($_REQUEST['surfer_address'])."'";
	else
		$where = "user_email LIKE '".SQL::escape(Surfer::get_email_address())."'";

	// there is already some enrolment record --redirect to the meeting page
	$query = "SELECT id FROM ".SQL::table_name('enrolments')." WHERE (anchor LIKE '".$anchor->get_reference()."') AND ".$where;
	if(SQL::query_count($query))
		Safe::redirect($context['url_to_home'].$context['url_to_root'].$anchor->get_url());

	// fields to save
	$query = array();

	// reference to the meeting page
	$query[] = "anchor = '".$anchor->get_reference()."'";

	// don't approve page owners, and accept simple confirmations as well
	if($anchor->is_owned() || ($overlay->get_value('enrolment') == 'none')) {
		$query[] = "approved = 'Y'";

		$context['text'] .= '<p>'.i18n::s('You have been enrolled to this meeting.').'</p>';

	} else
		$context['text'] .= '<p>'.i18n::s('Your invitation request has been recorded.').'</p>';

	// save surfer id, if known
	if(Surfer::get_id())
		$query[] = "user_id = ".SQL::escape(Surfer::get_id());

	// save some e-mail address
	if(isset($_REQUEST['surfer_address']) && $_REQUEST['surfer_address'])
		$query[] = "user_email = '".SQL::escape($_REQUEST['surfer_address'])."'";
	else
		$query[] = "user_email = '".SQL::escape(Surfer::get_email_address())."'";

	// insert a new record
	$query = "INSERT INTO ".SQL::table_name('enrolments')." SET ".implode(', ', $query);
	SQL::query($query);

	// notify page owner of this application, except if it is me
	if(!$anchor->is_owned() && ($owner_id = $anchor->get_value('owner_id')) && ($user = Users::get($owner_id)) && $user['email']) {

		// mail subject
		$subject = sprintf(i18n::c('%s: %s'), i18n::c('Meeting'), strip_tags($anchor->get_title()));

		// user has confirmed participation
		if($overlay->get_value('enrolment') == 'none')
			$headline = sprintf(i18n::c('%s has confirmed participation to %s'),
				'<a href="'.$context['url_to_home'].$context['url_to_root'].Surfer::get_permalink().'">'.Surfer::get_name().'</a>',
				'<a href="'.$context['url_to_home'].$context['url_to_root'].$anchor->get_url().'">'.$anchor->get_title().'</a>');

		// user is asking for an invitation
		else
			$headline = sprintf(i18n::c('%s would like to be enrolled to %s'),
				'<a href="'.$context['url_to_home'].$context['url_to_root'].Surfer::get_permalink().'">'.Surfer::get_name().'</a>',
				'<a href="'.$context['url_to_home'].$context['url_to_root'].$anchor->get_url().'">'.$anchor->get_title().'</a>');

		// help the chairman
		$message = Skin::build_mail_content($headline,
			i18n::s('Click on the link below to manage the full list of participants'));

		// several links
		$menu = array();

		// call for action
		$link = $context['url_to_home'].$context['url_to_root'].'overlays/events/enroll.php?id='.urlencode($anchor->get_reference());
		$title = sprintf(i18n::c('Manage enrolment of %s'), strip_tags($anchor->get_title()));
		$menu[] = Skin::build_mail_button($link, $title, TRUE);

		// surfer profile
		$link = $context['url_to_home'].$context['url_to_root'].Surfer::get_permalink();
		$menu[] = Skin::build_mail_button($link, Surfer::get_name(), FALSE);

		// add the menu
		$message .= Skin::build_mail_menu($menu);

		// threads messages
		$headers = Mailer::set_thread($anchor->get_reference());

		// send the message
		Mailer::notify(Surfer::from(), $user['email'], $subject, $message, $headers);

	}

	// socialize self-applications
	if($overlay->get_value('enrolment') == 'none') {
		include_once $context['path_to_root'].'comments/comments.php';
		$fields = array();
		$fields['anchor'] = $anchor->get_reference();
		$fields['description'] = sprintf(i18n::s('%s has confirmed his participation'), Surfer::get_name());
		$fields['type'] = 'notification';
		Comments::post($fields);
	}

	// follow-up commands
	$menu = array();
	$menu[] = Skin::build_link($anchor->get_url(), i18n::s('Done'), 'button');
	$context['text'] .= Skin::build_block(Skin::finalize_list($menu, 'menu_bar'), 'bottom');

}

// page title
if(is_object($anchor))
	$context['page_title'] = sprintf(i18n::c('%s: %s'), i18n::c('Meeting'), strip_tags($anchor->get_title()));

// render the skin
render_skin();

?>
