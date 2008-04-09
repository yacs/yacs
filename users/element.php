<?php
/**
 * provide part of user information to AJAX front-end
 *
 * Restrictions apply on this page:
 * - associates are allowed to move forward
 * - this is the personal record of the authenticated surfer
 * - access is restricted ('active' field == 'R'), but the surfer is an authenticated member
 * - public access is allowed ('active' field == 'Y')
 * - permission denied is the default
 *
 * Accept following invocations:
 * - element.php/12/actions
 * - element.php/12/contact
 * - element.php/12/preferences
 * - element.php/12/watch
 * - element.php?id=12&action=actions
 * - element.php?id=12&action=contact
 * - element.php?id=12&action=preferences
 * - element.php?id=12&action=watch
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../files/files.php';
include_once '../links/links.php';
include_once '../locations/locations.php';

// ensure browser always look for fresh data
Safe::header("Cache-Control: no-cache, must-revalidate");
Safe::header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

// stop here on scripts/validate.php
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
	return;

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
elseif(Surfer::is_logged())
	$id = Surfer::get_id();
$id = strip_tags($id);

// encode ISO-8859-1 argument, if any
if(isset($_SERVER['HTTP_ACCEPT_CHARSET']) && preg_match('/^iso-8859-1/i', $_SERVER['HTTP_ACCEPT_CHARSET']))
	$id = utf8_encode($id);

// get the item from the database
$item =& Users::get($id);

// get the related overlay, if any
$overlay = NULL;
include_once '../overlays/overlay.php';
if(isset($item['overlay']))
	$overlay = Overlay::load($item);

// look for the action
$action = NULL;
if(isset($_REQUEST['action']))
	$action = $_REQUEST['action'];
elseif(isset($context['arguments'][1]))
	$action = $context['arguments'][1];
$action = strip_tags($action);

// actual capability of current surfer
if(isset($item['id']) && Surfer::get_id() && ($item['id'] == Surfer::get_id()) && ($item['capability'] != '?'))
	Surfer::empower();

// associates can do what they want
if(Surfer::is_associate())
	$permitted = TRUE;

// the record of the authenticated surfer
elseif(isset($item['id']) && Surfer::is_creator($item['id']))
	$permitted = TRUE;

// access is restricted to authenticated member
elseif(isset($item['active']) && ($item['active'] == 'R') && Surfer::is_member())
	$permitted = TRUE;

// public access is allowed
elseif(isset($item['active']) && ($item['active'] == 'Y'))
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load localized strings
i18n::bind('users');

// load the skin
load_skin('users');

// the path to this page
$context['path_bar'] = array( 'users/' => i18n::s('Users') );

// the title of the page
if(isset($item['nick_name']))
	$context['page_title'] = $item['nick_name'];
elseif(isset($item['full_name']))
	$context['page_title'] = $item['full_name'];
else
	$context['page_title'] = i18n::s('Unknown user');

// not found -- help web crawlers
if(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	die(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	die(i18n::s('You are not allowed to perform this operation.'));

// list actions
} elseif($action == 'actions') {

	// we return some HTML
	$output = '';

	// query the database
	include_once '../actions/actions.php';
	$items = Actions::list_by_date_for_anchor('user:'.$item['id'], 0, ACTIONS_PER_PAGE);
	if(is_array($items))
		$items = Skin::build_list($items, 'decorated');

	// display the list of pending actions
	if($items)
		$output .= $items;
	else
		$output .= i18n::s('No action has been assigned to this user.');

	// offer to add a new action
	$menu = array( 'actions/edit.php?anchor=user:'.$item['id'] => i18n::s('Add an action') );
	$output .= Skin::build_list($menu, 'menu_bar');

	// actual transmission except on a HEAD request
	if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
		echo $output;

	// the post-processing hook, then exit
	finalize_page(TRUE);

// list contact
} elseif($action == 'contact') {

	// we return some HTML
	$text = '';

	// include contact information
	$text .= Skin::build_user_contact($item);

	// permanent address
	$box = array( 'bar' => array(), 'text' => '');

	// web address, if any
	if(isset($item['web_address']) && $item['web_address'])
		$box['text'] .= '<p>'.sprintf(i18n::s('Web address: %s'), Skin::build_link($item['web_address'], $item['web_address'], 'external'))."</p>\n";

	// do not let robots steal addresses
	if(Surfer::is_empowered()
		|| (isset($context['users_with_email_display']) && ($context['users_with_email_display'] == 'Y'))
		|| (Surfer::is_logged() && isset($context['users_with_email_display']) && ($context['users_with_email_display'] == 'R'))) {

		// email address - not showed to anonymous surfers for spam protection
		if(isset($item['email']) && $item['email']) {
			if(Surfer::is_creator($item['id']))
				$label = i18n::s('Your e-mail address: %s %s');
			else
				$label = i18n::s('E-mail address: %s %s');

			if(isset($context['with_email']) && ($context['with_email'] == 'Y'))
				$url = $context['url_to_root'].Users::get_url($id, 'mail');
			else
				$url = 'mailto:'.$item['email'];

			if(isset($item['with_newsletters']) && ($item['with_newsletters'] == 'Y'))
				$suffix = '';
			else
				$suffix = i18n::s('(do not wish to receive newsletters)');

			$box['text'] .= '<p>'.sprintf(i18n::s($label), Skin::build_link($url, $item['email'], 'email'), $suffix)."</p>\n";
		}

		// put contact addresses in a table
		$rows = array();

		// a clickable aim address
		if(isset($item['aim_address']) && $item['aim_address'])
			$rows[] = array(Skin::build_presence($item['aim_address'], 'aim'), sprintf(i18n::s('AIM Screenname: %s'), $item['aim_address']));

		// a clickable icq number
		if(isset($item['icq_address']) && $item['icq_address'])
			$rows[] = array(Skin::build_presence($item['icq_address'], 'icq'), sprintf(i18n::s('ICQ Number: %s'), $item['icq_address']));

		// a clickable irc address
		if(isset($item['irc_address']) && $item['irc_address'])
			$rows[] = array(Skin::build_presence($item['irc_address'], 'irc'), sprintf(i18n::s('IRC address: %s'), $item['irc_address']));

		// a clickable jabber address
		if(isset($item['jabber_address']) && $item['jabber_address'])
			$rows[] = array(Skin::build_presence($item['jabber_address'], 'jabber'), sprintf(i18n::s('Jabber Messenger: %s'), $item['jabber_address']));

		// a clickable msn address
		if(isset($item['msn_address']) && $item['msn_address'])
			$rows[] = array(Skin::build_presence($item['msn_address'], 'msn'), sprintf(i18n::s('MSN Instant Messenger: %s'), $item['msn_address']));

		// a clickable skype address
		if(isset($item['skype_address']) && $item['skype_address'])
			$rows[] = array(Skin::build_presence($item['skype_address'], 'skype'), sprintf(i18n::s('Skype: %s'), $item['skype_address']));

		// a clickable yahoo address -- the on-line status indicator requires to be connected to the Internet
		if(isset($item['yahoo_address']) && $item['yahoo_address'])
				$rows[] = array(Skin::build_presence($item['yahoo_address'], 'yahoo'), sprintf(i18n::s('Yahoo Messenger: %s'), $item['yahoo_address'])
				.' <img src="http://opi.yahoo.com/online?u='.$item['yahoo_address'].'&amp;m=g&amp;t=1" alt="Yahoo Online Status Indicator"'.EOT);

		if(count($rows))
			$box['text'] .= Skin::table(NULL, $rows, 'form');

		// how to change this
		if(Surfer::is_empowered()) {
			$menu = array( Users::get_url($item['id'], 'edit') => i18n::s('Edit') );
			$box['text'] .= Skin::build_list($menu, 'menu_bar');
		}

	// motivate people to register
	} elseif(!Surfer::is_logged())
		$box['text'] .= '<p>'.i18n::s('Please authenticate to view e-mail and instant messaging contact information, for this user.')."</p>\n";

	// a full box
	if($box['text'])
		$text .= Skin::build_box(i18n::s('Permanent addresses'), $box['text'], 'header1', 'external_contact');

	// actual transmission except on a HEAD request
	if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
		echo $text;

	// the post-processing hook, then exit
	finalize_page(TRUE);

// list preferences
} elseif($action == 'preferences') {

	// we return some HTML
	$output = '';

	// preferences, but only to authenticated surfers
	if(Surfer::is_logged()) {

		// preferred editor
		if(isset($item['editor']) && ($item['editor'] == 'fckeditor'))
			$label = Skin::build_link('http://www.fckeditor.net/', i18n::s('FCKeditor'), 'external');
		elseif(isset($item['editor']) && ($item['editor'] == 'tinymce'))
			$label = Skin::build_link('http://tinymce.moxiecode.com/', i18n::s('TinyMCE'), 'external');
		else
			$label = i18n::s('Textarea &amp; YACS codes');
		$output .= '<p>'.sprintf(i18n::s('Editor: %s'), $label).'</p>'."\n";

		// from where
		if(isset($item['from_where']) && $item['from_where'])
			$output .= '<p>'.sprintf(i18n::s('From: %s'), Codes::beautify($item['from_where'])).'</p>'."\n";

		// signature
		if(isset($item['signature']) && $item['signature'])
			$output .= '<p>'.sprintf(i18n::s('Signature: %s'), BR.Codes::beautify($item['signature'])).'</p>'."\n";

		// pgp key
		if(isset($item['pgp_key']) && $item['pgp_key'])
			$output .= '<p>'.sprintf(i18n::s('PGP key or certificate: %s'), BR.'<span style="font-size: 50%">'.$item['pgp_key'].'</span>').'</p>'."\n";

	}

	// how to change this
	if(Surfer::is_empowered()) {
		$menu = array( Users::get_url($item['id'], 'edit') => i18n::s('Edit') );
		$output .= Skin::build_list($menu, 'menu_bar');
	}

	// actual transmission except on a HEAD request
	if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
		echo $output;

	// the post-processing hook, then exit
	finalize_page(TRUE);

// the watch list
} elseif($action == 'watch') {

	// we return some HTML
	$output = '';

	// help surfer on their own profiles
	if(Surfer::get_id() == $item['id'])
		$output .= '<p>'.i18n::s('Click on the Watch link while browsing user profiles, sections, or articles.').'</p>';

	// list watched users by posts
	if($items = Members::list_users_by_posts_for_member('user:'.$item['id'], 0, BOOKMARKS_PER_PAGE, 'watch')) {
		if(is_array($items))
			$output .= Skin::build_box(i18n::s('Watched users'), Skin::build_list($items, 'decorated'), 'header1', 'watched_users');
		else
			$output .= Skin::build_box(i18n::s('Watched users'), $items, 'header1', 'watched_users');
	}

	// list watched sections by date
	if($items = Members::list_sections_by_date_for_member('user:'.$item['id'], 0, BOOKMARKS_PER_PAGE, 'compact')) {
		if(is_array($items))
			$output .= Skin::build_box(i18n::s('Watched sections'), Skin::build_list($items, 'compact'), 'header1', 'watched_sections');
		else
			$output .= Skin::build_box(i18n::s('Watched sections'), $items, 'header1', 'watched_sections');
	}

	// list watched articles by date
	if($items = Members::list_articles_by_date_for_member('user:'.$item['id'], 0, BOOKMARKS_PER_PAGE, 'simple')) {
		if(is_array($items))
			$output .= Skin::build_box(i18n::s('Watched pages'), Skin::build_list($items, 'compact'), 'header1', 'watched_articles');
		else
			$output .= Skin::build_box(i18n::s('Watched pages'), $items, 'header1', 'watched_articles');
	}

	// actual transmission except on a HEAD request
	if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
		echo $output;

	// the post-processing hook, then exit
	finalize_page(TRUE);

// invalid action selector
} else {
	Safe::header('Status: 400 Bad Request', TRUE, 400);
	die('Request is invalid.');
}

// render the skin
render_skin();

?>