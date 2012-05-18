<?php
/**
 * create a new user or edit an existing one
 *
 * @todo on subscriptor application, post a query page when there is no messaging facility (gnapz)
 *
 * This page can be used by anonymous surfers that would like to register, by logged
 * users that are updating their profile, or by associates that declare new users
 * or new associates.
 *
 * On registration YACS attempts to stop robots by generating a random string and by asking user to type it.
 *
 * A button-based editor is used for the description field.
 * It's aiming to introduce most common [link=codes]codes/index.php[/link] supported by YACS.
 *
 * This script attempts to validate the new or updated user description against a standard PHP XML parser.
 * The objective is to spot malformed or unordered HTML and XHTML tags. No more, no less.
 *
 * By default the provision of an e-mail address is optional, but this can be changed
 * for communities relying on e-mail messages, such as education teams, etc.
 * On new application, and when the parameter [code]users_with_email_validation[/code] is set to 'Y',
 * YACS ensures that an e-mail address is provided and saves a Subscriber profile.
 * Also, a confirmation message is sent to the e-mail address, that contains a validation feed-back web link.
 * When a subscriber clicks on this link, and if the parameter [code]users_with_approved_members[/code]
 * is not set to 'Y', his profile is turned to a true Member.
 *
 * An overlay can be used to capture additional user data. This is defined in the configuration panel for users, and
 * the same overlay class applies for all community users.
 *
 * @see users/configure.php
 *
 * Restrictions apply on this page:
 * - associates are allowed to move forward
 * - surfer modifies his/her own profile
 * - this is a new post and registration is allowed
 * - permission denied is the default
 *
 * Self-registration can be disabled by associates from the configuration panel for users,
 * by setting the global parameter '[code]users_without_registration[/code]' to 'Y'.
 *
 * @see control/configure.php
 *
 * If the file [code]parameters/demo.flag[/code] exists, the script assumes that this instance
 * of YACS runs in demonstration mode, and associates profiles cannot be degraded.
 *
 * The creation hook is used to invoke any software extension bound as follows:
 * - id: 'users/edit.php#post'
 * - type: 'include'
 * - parameters: id of the new user profile
 * Use this hook for example to create additional content in home page, if any.
 *
 * The update hook is used to invoke any software extension bound as follows:
 * - id: 'users/edit.php#put'
 * - type: 'include'
 * - parameters: id of the modified user profile
 * Use this hook for example to track changes in user profiles.
 *
 * Accepted calls:
 * - edit.php					edit a new user profile
 * - edit.php/&lt;id&gt;				modify an existing user profile
 * - edit.php?id=&lt;id&gt; 		modify an existing user profile
 *
 * @author Bernard Paques
 * @author Vincent No&euml;l
 * @author GnapZ
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @tester Christian Loubechine
 * @tester Dakoon
 * @tester Fw_crocodile
 * @tester GnapZ
 * @tester Manuel Lopez Gallego
 * @tester J&eacute;r&ocirc;me Douill&eacute;
 * @tester Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @tester Mathieu Favez
 * @tester Jean-Marc Schwartz
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../shared/xml.php';	// input validation
include_once '../images/images.php';
include_once '../locations/locations.php';
include_once '../tables/tables.php';
include_once '../versions/versions.php'; // roll-back

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database, if any
$item =& Users::get($id);

// get the related overlay, if any
$overlay = NULL;
if(isset($item['overlay']) && $item['overlay'])
	$overlay = Overlay::load($item, 'user:'.$item['id']);
elseif(isset($context['users_overlay']) && $context['users_overlay'])
	$overlay = Overlay::bind($context['users_overlay']);

// associates can do what they want
if(Surfer::is_associate())
	$permitted = TRUE;

// the page of the authenticated surfer
elseif(isset($item['id']) && Surfer::is($item['id']))
	$permitted = TRUE;

// registration of new users is allowed
elseif(!isset($item['id']) && !Surfer::is_logged() && (!isset($context['users_without_registration']) || ($context['users_without_registration'] != 'Y')))
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// show the edition form only if required to do so
$with_form = FALSE;

// load the skin
load_skin('users');

// the path to this page
$context['path_bar'] = array( 'users/' => i18n::s('People') );

// the title of the page
if($item['nick_name'])
	$context['page_title'] = sprintf(i18n::s('Edit: %s'), $item['nick_name']);
elseif(Surfer::is_associate())
	$context['page_title'] = i18n::s('Add a user');
else
	$context['page_title'] = i18n::s('Register on this server');

// validate input syntax only if required
if(isset($_REQUEST['option_validate']) && ($_REQUEST['option_validate'] == 'Y')) {
	if(isset($_REQUEST['introduction']))
		xml::validate($_REQUEST['introduction']);
	if(isset($_REQUEST['description']))
		xml::validate($_REQUEST['description']);
}

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// permission denied
} elseif(!$permitted) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);

	// registration is not allowed to anonymous surfers
	if(!isset($item['id']) && !Surfer::is_logged())
		Logger::error(sprintf(i18n::s('Self-registration is not allowed. Use the %s to submit your application.'), Skin::build_link('query.php', i18n::s('query form'), 'shortcut')));

	// permission denied to authenticated user
	else
		Logger::error(i18n::s('You are not allowed to perform this operation.'));

// an error occured
} elseif(count($context['error'])) {
	$item = $_REQUEST;
	$with_form = TRUE;

// save posted data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// stop hackers
	$_REQUEST['nick_name'] = preg_replace(FORBIDDEN_IN_NAMES, '_', strip_tags($_REQUEST['nick_name']));

	// build the full name for new users
	if(isset($_REQUEST['first_name']) && isset($_REQUEST['last_name']))
		$_REQUEST['full_name'] = trim(ucfirst($_REQUEST['first_name']).' '.ucfirst($_REQUEST['last_name']));

	// when the page has been overlaid
	if(is_object($overlay)) {

		// update the overlay from form content
		$overlay->parse_fields($_REQUEST);

		// save content of the overlay in the article
		$_REQUEST['overlay'] = $overlay->save();
		$_REQUEST['overlay_id'] = $overlay->get_id();
	}

	// update an existing record
	if($item['id']) {

		// associates will always stay associates in demo mode
		if(isset($item['capability']) && ($item['capability'] == 'A') && file_exists($context['path_to_root'].'parameters/demo.flag'))
			$_REQUEST['capability'] = 'A';

		// actual update
		if(Users::put($_REQUEST)
			&& (!is_object($overlay) || $overlay->remember('update', $_REQUEST, 'user:'.$_REQUEST['id']))) {

			// 'users/edit.php#put' hook
			if(is_callable(array('Hooks', 'include_scripts')))
				Hooks::include_scripts('users/edit.php#put', $item['id']);

			// clear cache
			Users::clear($_REQUEST);

			// display the updated page
			Safe::redirect($context['url_to_home'].$context['url_to_root'].Users::get_permalink($item));

		}

		// on error display the form again
		$with_form = TRUE;
		$item = $_REQUEST;

	// insert a new record in the database
	} else {

		// anyone can post a new profile, but restrictions apply
		if(!Surfer::is_associate()) {

			// the profile cannot be hidden
			$_REQUEST['active'] = 'Y';

			// the profile can not be an Associate, but Members and Subscribers are accepted
			if(isset($_REQUEST['capability']) && ($_REQUEST['capability'] == 'A'))
				$_REQUEST['capability'] = 'M';
		}

		// passwords have to be confirmed
		if($item['confirm'] && ($item['confirm'] != $item['password'])) {
			Logger::error(i18n::s('Please confirm your password.'));
			$item = $_REQUEST;
			$with_form = TRUE;

		// stop robots
		} elseif(Surfer::may_be_a_robot()) {
			Logger::error(i18n::s('Please prove you are not a robot.'));
			$item = $_REQUEST;
			$with_form = TRUE;

		// actual post
		} elseif(!$_REQUEST['id'] = Users::post($_REQUEST)) {

			// on error display the form again
			$with_form = TRUE;
			$item = $_REQUEST;
			$item['password'] = $item['confirm']; // password has been md5 in Users::post()
			unset($item['id']);

		// successful post
		} else {

			// post an overlay, with the new user id
			if(is_object($overlay) && !$overlay->remember('insert', $_REQUEST, 'user:'.$_REQUEST['id'])) {
				$item = $_REQUEST;
				$with_form = TRUE;

			// thanks
			} else {

				// 'users/edit.php#post' hook
				if(is_callable(array('Hooks', 'include_scripts')))
					Hooks::include_scripts('users/edit.php#post', $_REQUEST['id']);

				// associates are redirected to the new user page
				if(Surfer::is_associate())
					Safe::redirect($context['url_to_home'].$context['url_to_root'].Users::get_permalink($_REQUEST));

				// welcome message
				else {

					// get the new record
					$item =& Users::get($_REQUEST['id'], TRUE);

					// the welcome page
					$context['page_title'] = i18n::s('Welcome!');

					// the welcome message
					$context['text'] .= '<p>'.ucfirst($item['nick_name']).',</p>'
						.'<p>'.i18n::s('You are now a registered user of this community. Each time you will visit this site, please provide your nick name and password to authenticate.').'</p>';

					// follow-up commands
					$follow_up = i18n::s('What do you want to do now?');

					// just proceed
					if(isset($_REQUEST['forward']) && $_REQUEST['forward'])
						$follow_up .= '<ul><li><b>'.Skin::build_link($_REQUEST['forward'], i18n::s('Proceed with what I was doing before registering')).'</a></b></li></ul>';

					// select an avatar
					$follow_up .= '<ul><li><a href="'.$context['url_to_root'].'users/select_avatar.php?id='.$_REQUEST['id'].'">'.i18n::s('Select a picture from the library').'</a></li></ul>';

					// post a new page
					if(($item['capability'] == 'M') || ($item['capability'] == 'A'))
						$follow_up .= '<ul><li>'.sprintf(i18n::s('%s, maybe with some images and/or files'), '<a href="'.$context['url_to_root'].'articles/edit.php">'.i18n::s('Add a page').'</a>').'</li></ul>';

					// edit profile
					$follow_up .= '<ul><li>'.sprintf(i18n::s('%s, to let others have a better understanding of who I am'), '<a href="'.$context['url_to_root'].Users::get_permalink($_REQUEST).'">'.i18n::s('Edit my profile').'</a>').'</li></ul>';

					// more help
					$follow_up .= '<ul><li>'.sprintf(i18n::s('%s, and get basic directions'), '<a href="'.$context['url_to_root'].'help/">'.i18n::s('Go the main help page').'</a>').'</li></ul>';

					// display on page bottom
					$context['text'] .= Skin::build_block($follow_up, 'bottom');

					// send silently a message to the event logger, if any
					switch($item['capability']) {
					case 'A':
						$label = sprintf(i18n::c('New associate: %s'), $item['nick_name']);
						break;
					case 'M':
						$label = sprintf(i18n::c('New member: %s'), $item['nick_name']);
						break;
					default:
						$label = sprintf(i18n::c('New subscriber: %s'), $item['nick_name']);
						break;
					}
					$link = $context['url_to_home'].$context['url_to_root'].Users::get_permalink($item);
                                        $description = '<a href="'.$link.'">'.$link.'</a>';
					Logger::notify('users/edit.php', $label, $description);
				}
			}
		}
	}

// on GET always display the form
} else
	$with_form = TRUE;

// display the form if required to do so
if($with_form) {

	// the form to edit a user
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>';
	$fields = array();

	// this form has several panels
	$panels = array();

	// the contact panel
	//
	$text = '';

	// associates can change the capability flag: Associate, Member, Subscriber or ?-unknown
	if(Surfer::is_associate()) {
		$label = i18n::s('Capability').' *';
		$input = '<input type="radio" name="capability" value="A"';
		if(isset($item['capability']) && ($item['capability'] == 'A'))
			$input .= ' checked="checked"';
		$input .= ' /> '.i18n::s('Associate').' ';
		$input .= '<input type="radio" name="capability" value="M"';
		if(!isset($item['capability']) || ($item['capability'] == 'M'))
			$input .= ' checked="checked"';
		$input .= ' /> '.i18n::s('Member').' ';
		$input .= '<input type="radio" name="capability" value="S"';
		if(isset($item['capability']) && ($item['capability'] == 'S'))
			$input .= ' checked="checked"';
		$input .= ' /> '.i18n::s('Subscriber').' ';
		$input .= '<input type="radio" name="capability" value="?"';
		if(isset($item['capability']) && ($item['capability'] == '?'))
			$input .= ' checked="checked"';
		$input .= ' /> '.i18n::s('Blocked')."\n";
		$fields[] = array($label, $input);
	}

	// full name
	if(isset($item['full_name']) && $item['full_name']) {
		$label = i18n::s('Full name');
		$input = '<input type="text" name="full_name" id="full_name" size="50" value="'.encode_field($item['full_name']).'" />';
		$hint = i18n::s('First names followed by last names');
		$fields[] = array($label, $input, $hint);
	} else {
		$label = i18n::s('First name(s)');
		$input = '<input type="text" name="first_name" id="first_name" size="50" />';
		$fields[] = array($label, $input);

		$label = i18n::s('Last name(s)');
		$input = '<input type="text" name="last_name" size="50" />';
		$fields[] = array($label, $input);
	}

	// nick name
	$label = i18n::s('Nick name').' *';
	$input = '<input type="text" name="nick_name" id="nick_name" size="40" value="'.encode_field(isset($item['nick_name'])?$item['nick_name']:'').'" />';
	$hint = i18n::s('Please carefully select a meaningful and unused nick name.');
	$fields[] = array($label, $input, $hint);

	// the email address on registration
	if(!isset($item['id'])) {
		$label = i18n::s('E-mail address');
		if(isset($context['users_with_email_validation']) && ($context['users_with_email_validation'] == 'Y'))
			$label .= ' *';
		$input = '<input type="text" name="email" size="40" value="'.encode_field(isset($item['email'])?$item['email']:'').'" />';
		$hint = '';
		if(!isset($item['id']) && isset($context['users_with_email_validation']) && ($context['users_with_email_validation'] == 'Y'))
			$hint = i18n::s('You will receive a message on this address to activate your membership.');
		$hint .= ' '.i18n::s('We won\'t disclose personal information about you or your company to anyone outside this site.');
		$fields[] = array($label, $input, $hint);
	}

	// the password, but only for registering user
	if(!isset($item['id'])) {
		$label = i18n::s('Password').' *';
		$input = '<input type="password" name="password" size="20" value="'.encode_field(isset($item['password'])?$item['password']:'').'" />';
		$hint = i18n::s('We suggest at least 4 numbers, two letters, and a punctuation sign - in any order');
		$fields[] = array($label, $input, $hint);

		// the password has to be confirmed
		$label = i18n::s('Password confirmation').' *';
		$input = '<input type="password" name="confirm" size="20" value="'.encode_field(isset($item['confirm'])?$item['confirm']:'').'" />';
		$fields[] = array($label, $input);

		// stop robots
		if($field = Surfer::get_robot_stopper())
			$fields[] = $field;

	}

	// form fields in this panel
	$text .= Skin::build_form($fields);
	$fields = array();

	// business card
	//

	// title
	$label = i18n::s('Title');
	$input = '<input type="text" name="vcard_title" size="40" value="'.encode_field(isset($item['vcard_title'])?$item['vcard_title']:'').'" />';
	$hint = i18n::s('Your occupation, your motto, or some interesting words');
	$fields[] = array($label, $input, $hint);

	// organisation
	$label = i18n::s('Organization');
	$input = '<input type="text" name="vcard_organization" size="40" value="'.encode_field(isset($item['vcard_organization'])?$item['vcard_organization']:'').'" />';
	$fields[] = array($label, $input);

	// label
	$label = i18n::s('Physical address');
	$input = '<textarea name="vcard_label" rows="5" cols="50">'.encode_field(isset($item['vcard_label'])?$item['vcard_label']:'').'</textarea>';
	$fields[] = array($label, $input);

	// phone number
	$label = i18n::s('Phone number');
	$input = '<input type="text" name="phone_number" size="20" value="'.encode_field(isset($item['phone_number'])?$item['phone_number']:'').'" />';
	$hint = i18n::s('Enter phone number in international format, starting with country code');
	$fields[] = array($label, $input, $hint);

	// alternate number
	$label = i18n::s('Alternate number');
	$input = '<input type="text" name="alternate_number" size="20" value="'.encode_field(isset($item['alternate_number'])?$item['alternate_number']:'').'" />';
	$fields[] = array($label, $input);

	// the email address
	if(isset($item['id'])) {
		$label = i18n::s('E-mail address');
		if(isset($context['users_with_email_validation']) && ($context['users_with_email_validation'] == 'Y'))
			$label .= ' *';
		$input = '<input type="text" name="email" size="40" value="'.encode_field(isset($item['email'])?$item['email']:'').'" />';
		$hint = '';
		if(!isset($item['id']) && isset($context['users_with_email_validation']) && ($context['users_with_email_validation'] == 'Y'))
			$hint = i18n::s('You will receive a message on this address to activate your membership.');
		$hint .= ' '.i18n::s('We won\'t disclose personal information about you or your company to anyone outside this site.');
		$fields[] = array($label, $input, $hint);
	}

	// web address, if any
	$label = i18n::s('Web address');
	$input = '<input type="text" name="web_address" size="40" value="'.encode_field(isset($item['web_address'])?$item['web_address']:'').'" />';
	$hint = i18n::s('If your home page is not here.');
	$fields[] = array($label, $input, $hint);

	// agent
	$label = i18n::s('Alternate contact');
	$input = '<input type="text" name="vcard_agent" id="vcard_agent" value ="'.encode_field(isset($item['vcard_agent'])?$item['vcard_agent']:'').'" size="25" maxlength="32" />';
	$hint = i18n::s('Another person who can act on your behalf');
	$fields[] = array($label, $input, $hint);

	// extend the form
	$text .= Skin::build_box(i18n::s('Business card'), Skin::build_form($fields), 'unfolded');
	$fields = array();

	// append the script used for data checking on the browser
	$text .= JS_PREFIX
		.'// enable autocompletion for user names'."\n"
		.'$(document).ready( function() { Yacs.autocomplete_names("vcard_agent",true); });  '."\n"
		.JS_SUFFIX;

	// instant messaging
	//

	// the twitter address
	$label = Skin::build_link('http://www.twitter.com/', i18n::s('Twitter'), 'external');
	$input = '<input type="text" name="twitter_address" size="40" value="'.encode_field(isset($item['twitter_address'])?$item['twitter_address']:'').'" />';
	$fields[] = array($label, $input);

	// the Jabber address
	$label = sprintf(i18n::s('%s, or %s'), Skin::build_link('http://mail.google.com/', i18n::s('GMail'), 'external'), Skin::build_link('http://www.jabber.org/', i18n::s('Jabber'), 'external'));
	$input = '<input type="text" name="jabber_address" size="40" value="'.encode_field(isset($item['jabber_address'])?$item['jabber_address']:'').'" />';
	$fields[] = array($label, $input);

	// the skype address
	$label = Skin::build_link('http://www.skype.com/', i18n::s('Skype'), 'external');
	$input = '<input type="text" name="skype_address" size="40" value="'.encode_field(isset($item['skype_address'])?$item['skype_address']:'').'" />';
	$fields[] = array($label, $input);

	// the Yahoo address
	$label = Skin::build_link('http://messenger.yahoo.com/', i18n::s('Yahoo! Messenger'), 'external');
	$input = '<input type="text" name="yahoo_address" size="40" value="'.encode_field(isset($item['yahoo_address'])?$item['yahoo_address']:'').'" />';
	$fields[] = array($label, $input);

	// the MSN address
	$label = Skin::build_link('http://messenger.live.com/', i18n::s('Windows Live Messenger'), 'external');
	$input = '<input type="text" name="msn_address" size="40" value="'.encode_field(isset($item['msn_address'])?$item['msn_address']:'').'" />';
	$fields[] = array($label, $input);

	// the AIM address
	$label = Skin::build_link('http://www.aim.com/', i18n::s('AIM'), 'external');
	$input = '<input type="text" name="aim_address" size="40" value="'.encode_field(isset($item['aim_address'])?$item['aim_address']:'').'" />';
	$fields[] = array($label, $input);

	// the IRC address
	$label = Skin::build_link('http://www.irchelp.org/', i18n::s('IRC'), 'external');
	$input = '<input type="text" name="irc_address" size="40" value="'.encode_field(isset($item['irc_address'])?$item['irc_address']:'').'" />';
	$fields[] = array($label, $input);

	// the ICQ number
	$label = Skin::build_link('http://www.icq.com/', i18n::s('ICQ'), 'external');
	$input = '<input type="text" name="icq_address" size="40" value="'.encode_field(isset($item['icq_address'])?$item['icq_address']:'').'" />';
	$fields[] = array($label, $input);

	// add a folded box
	$text .= Skin::build_box(i18n::s('Instant communication'), Skin::build_form($fields), 'folded');
	$fields = array();

	// display in a separate panel
	if($text)
		$panels[] = array('contact', i18n::s('Contact'), 'contact_panel', $text);

	// the information panel
	//
	$text = '';

	// the avatar url
	if(isset($item['id'])) {
		$label = i18n::s('Picture URL');

		// show the current avatar
		if(isset($item['avatar_url']) && $item['avatar_url'])
			$input = '<img src="'.$item['avatar_url'].'" alt="" />'.BR;

		$value = '';
		if(isset($item['avatar_url']) && $item['avatar_url'])
			$value = $item['avatar_url'];
		$input .= '<input type="text" name="avatar_url" size="55" value="'.encode_field($value).'" maxlength="255" />';

		$input .= ' <span class="details">'.Skin::build_link(Users::get_url($item['id'], 'select_avatar'), i18n::s('Change picture'), 'button').'</span>';

		$fields[] = array($label, $input);
	}

	// from where
	$label = i18n::s('From');
	$input = '<input type="text" name="from_where" size="50" value="'.encode_field(isset($item['from_where'])?$item['from_where']:'').'" maxlength="255" />';
	$hint = i18n::s('Some hint on your location (eg, \'Paris\', \'home\', \'the toys-for-sick-persons department\')');
	$fields[] = array($label, $input, $hint);

	// the introduction
	$label = i18n::s('Introduction');
	$input = '<textarea name="introduction" rows="5" cols="50">'.encode_field(isset($item['introduction'])?$item['introduction']:'').'</textarea>';
	$hint = i18n::s('Displayed aside your pages');
	$fields[] = array($label, $input, $hint);

	// include overlay fields, if any
	if(is_object($overlay))
		$fields = array_merge($fields, $overlay->get_fields($item));

	// the description
	$label = i18n::s('Description');

	// use the editor if possible
	$input = Surfer::get_editor('description', isset($item['description'])?$item['description']:'');
	$fields[] = array($label, $input);

	// birth date
	$label = i18n::s('Birth date');
	if(isset($item['birth_date']) && ($item['birth_date'] > NULL_DATE))
		$value = substr($item['birth_date'], 0, 10);
	else
		$value = '';
	$input = '<input type="text" name="birth_date" size="20" value="'.encode_field($value).'" />';
	$hint = i18n::s('YYYY-MM-DD');
	$fields[] = array($label, $input, $hint);

	// form fields in this panel
	$text .= Skin::build_form($fields);
	$fields = array();

	// user preferences
	//

	// preferred language
	$label = i18n::s('Language');
	$input = i18n::get_languages_select(isset($item['language'])?$item['language']:'none');
	$fields[] = array($label, $input);

	// associates may change the active flag: Yes/public, Restricted/logged, No/associates
	if(Surfer::is_associate()) {
		$label = i18n::s('Access');
		$input = '<input type="radio" name="active" value="Y"';
		if(!isset($item['active']) || ($item['active'] == 'Y'))
			$input .= ' checked="checked"';
		$input .= ' /> '.i18n::s('Public - Everybody, including anonymous surfers')
			.BR.'<input type="radio" name="active" value="R"';
		if(isset($item['active']) && ($item['active'] == 'R'))
			$input .= ' checked="checked"';
		$input .= ' /> '.i18n::s('Community - Access is granted to any identified surfer')
			.BR.'<input type="radio" name="active" value="N"';
		if(isset($item['active']) && ($item['active'] == 'N'))
			$input .= ' checked="checked"';
		$input .= ' /> '.i18n::s('Private - Access is restricted to selected persons')."\n";
		$fields[] = array($label, $input);
	}

	// signature
	$label = i18n::s('Signature');
	$input = '<textarea name="signature" rows="2" cols="50">'.encode_field(isset($item['signature'])?$item['signature']:'').'</textarea>';
	$hint = i18n::s('To be appended to your comments and mail messages. Separated with dashes from main text.');
	$fields[] = array($label, $input, $hint);

	// e-mail usage
	$label = i18n::s('E-mail usage');

	// confirm password
	$input = '<input type="checkbox" name="without_confirmations" value="N"';
	if(!isset($item['without_confirmations']) || ($item['without_confirmations'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= ' />';
	if(isset($item['id']))
		$input .= ' '.i18n::s('Confirm every password change.')."\n";
	else
		$input .= ' '.i18n::s('Confirm registration and password.')."\n";

	// receive alerts
	$input .= BR.'<input type="checkbox" name="without_alerts" value="N"';
	if(!isset($item['without_alerts']) || ($item['without_alerts'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= ' /> '.i18n::s('Alert me when my pages are commented.')."\n";

	// receive private messages
	$input .= BR.'<input type="checkbox" name="without_messages" value="N"';
	if(!isset($item['without_messages']) || ($item['without_messages'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= ' /> '.i18n::s('Allow other members to contact me.')."\n";

	// explicit newsletter subscription
	$input .= BR.'<input type="checkbox" name="with_newsletters" value="Y"';
	if(!isset($item['id']) || !isset($item['with_newsletters']) || ($item['with_newsletters'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= ' /> '.i18n::s('Send me periodical newsletters.')."\n";

	$hint = i18n::s('Your explicit approval is a pre-requisite for us to use your e-mail address.');
	$fields[] = array($label, $input, $hint);

	// editor
	$label = i18n::s('Preferred editor');
	$input = '<select name="selected_editor">';	// hack because of FCKEditor already uses 'editor'
	if(isset($item['editor']))
		;
	elseif(!isset($context['users_default_editor']))
		$item['editor'] = 'tinymce';
	else
		$item['editor'] = $context['users_default_editor'];
	$input .= '<option value="tinymce"';
	if($item['editor'] == 'tinymce')
		$input .= ' selected="selected"';
	$input .= '>'.i18n::s('TinyMCE')."</option>\n";
	$input .= '<option value="fckeditor"';
	if($item['editor'] == 'fckeditor')
		$input .= ' selected="selected"';
	$input .= '>'.i18n::s('FCKEditor')."</option>\n";
	$input .= '<option value="yacs"';
	if($item['editor'] == 'yacs')
		$input .= ' selected="selected"';
	$input .= '>'.i18n::s('Textarea')."</option>\n";
	$input .= '</select>';
	$hint = i18n::s('Select your preferred tool to edit text.');
	$fields[] = array($label, $input, $hint);

	// interface
	$label = i18n::s('Interface');
	$input = '<select name="interface">';
	$input .= '<option value="I"';
	if(!isset($item['interface']) || ($item['interface'] == 'I'))
		$input .= ' selected="selected"';
	$input .= '>'.i18n::s('Improved interface')."</option>\n";
	$input .= '<option value="C"';
	if(isset($item['interface']) && ($item['interface'] == 'C'))
		$input .= ' selected="selected"';
	$input .= '>'.i18n::s('Complex interface')."</option>\n";
	$input .= '</select>';
	$fields[] = array($label, $input);

	// form fields in this panel
	$text .= Skin::build_box(i18n::s('Preferences'), Skin::build_form($fields), 'folded');
	$fields = array();

	// display in a separate panel
	if($text)
		$panels[] = array('information', i18n::s('Information'), 'information_panel', $text);

	// append tabs from the overlay, if any
	//
	if(is_object($overlay) && ($more_tabs = $overlay->get_tabs('edit', $item)))
 		$panels = array_merge($panels, $more_tabs);

	//
	// resources tab
	//
	$text = '';

	// end of regular fields
	$text .= Skin::build_form($fields);
	$fields = array();

	// splash message for new items
	if(!isset($item['id']))
		$text .= Skin::build_box(i18n::s('Images'), '<p>'.i18n::s('Submit the new page, and you will be able to add images afterwards.').'</p>', 'folded');

	// the list of images
	elseif($items = Images::list_by_date_for_anchor('user:'.$item['id']))
		$text .= Skin::build_box(i18n::s('Images'), Skin::build_list($items, 'decorated'), 'unfolded', 'edit_images');

	// if we are editing an existing profile
	if(isset($item['id'])) {

		// files are reserved to authenticated members
		if($items = Files::list_embeddable_for_anchor('user:'.$item['id'], 0, 50))
			$text .= Skin::build_box(i18n::s('Files'), Skin::build_list($items, 'decorated'), 'unfolded');

		// locations are reserved to authenticated members
		if(Locations::allow_creation(NULL, $item)) {
			$menu = array( 'locations/edit.php?anchor='.urlencode('user:'.$item['id']) => i18n::s('Add a location') );
			$items = Locations::list_by_date_for_anchor('user:'.$item['id'], 0, 50, 'user:'.$item['id']);
			$text .= Skin::build_box(i18n::s('Locations'), Skin::build_list($menu, 'menu_bar').Skin::build_list($items, 'decorated'), 'folded');
		}

		// tables are reserved to associates
		if(Tables::allow_creation(NULL, $item)) {
			$menu = array( 'tables/edit.php?anchor='.urlencode('user:'.$item['id']) => i18n::s('Add a table') );
			$items = Tables::list_by_date_for_anchor('user:'.$item['id'], 0, 50, 'user:'.$item['id']);
			$text .= Skin::build_box(i18n::s('Tables'), Skin::build_list($menu, 'menu_bar').Skin::build_list($items, 'decorated'), 'folded');
		}

		// pgp key
// 		$label = i18n::s('PGP key or certificate');
// 		$input = '<textarea name="pgp_key" rows="5" cols="50">'.encode_field(isset($item['pgp_key'])?$item['pgp_key']:'').'</textarea>';

		// add a folded box
// 		$text .= Skin::build_box(i18n::s('Public key'), $label.BR.$input, 'folded');
// 		$fields = array();

	}

	// display in a separate panel
	if($text)
		$panels[] = array('resources', i18n::s('Resources'), 'resources_panel', $text);

	// let YACS do the hard job
	$context['text'] .= Skin::build_tabs($panels);

	//
	// bottom commands
	//
	$menu = array();

	// the submit button
	$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');

	// cancel button
	if(isset($item['id']))
		$menu[] = Skin::build_link(Users::get_permalink($item), i18n::s('Cancel'), 'span');

	// several options to check
	$suffix = array();

	// associates may decide to not stamp changes -- complex command
	if(isset($item['id']) && Surfer::is_associate() && Surfer::has_all())
		$suffix[] = '<input type="checkbox" name="silent" value="Y" />'.' '.i18n::s('Do not change modification date.');

	// validate page content
	$suffix[] = '<input type="checkbox" name="option_validate" value="Y" checked="checked" /> '.i18n::s('Ensure this post is valid XHTML.');

	// an assistant-like rendering at page bottom
	$context['text'] .= Skin::build_assistant_bottom('', $menu, $suffix, isset($item['tags'])?$item['tags']:'');

	// link to privacy statement
	if(!isset($item['id']) && !Surfer::is_associate())
		$context['text'] .= '<p>'.sprintf(i18n::s('By clicking submit, you agree to the terms and conditions outlined in the %s.'), Skin::build_link(Articles::get_url('privacy'), i18n::s('privacy statement'), 'basic')).'</p>';

	// transmit the id as a hidden field
	if(isset($item['id']) && $item['id'])
		$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'" />';

	// transmit the link to use after registration
	if(!isset($item['id']) && isset($_REQUEST['forward']) && $_REQUEST['forward'])
		$context['text'] .= '<input type="hidden" name="forward" value="'.encode_field($_REQUEST['forward']).'" />';

	// end of the form
	$context['text'] .= '</div></form>';

	// append the script used for data checking on the browser
	$context['text'] .= JS_PREFIX
		.'// check that main fields are not empty'."\n"
		.'func'.'tion validateDocumentPost(container) {'."\n"
		."\n"
		.'	// name is mandatory'."\n"
		.'	if(!container.nick_name.value) {'."\n"
		.'		alert("'.i18n::s('You must provide a nick name.').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		return false;'."\n"
		.'	}'."\n";
	if(!isset($item['id']))
		$context['text'] .= "\n"
			.'	// password is mandatory'."\n"
			.'	if(!container.password.value) {'."\n"
			.'		alert("'.i18n::s('You must provide a password.').'");'."\n"
			.'		Yacs.stopWorking();'."\n"
			.'		return false;'."\n"
			.'	}'."\n";
	if(isset($context['users_with_email_validation']) && ($context['users_with_email_validation'] == 'Y'))
		$context['text'] .= "\n"
			.'	// email is mandatory'."\n"
			.'	if(!container.email.value) {'."\n"
			.'		alert("'.i18n::s('You must provide a valid e-mail address.').'");'."\n"
			.'		Yacs.stopWorking();'."\n"
			.'		return false;'."\n"
			.'	}'."\n";
	$context['text'] .= "\n"
		.'	// successful check'."\n"
		.'	return true;'."\n"
		.'}'."\n"
		."\n"
		.'// disable editor selection on change in form'."\n"
                .'$("#main_form textarea, #main_form input, #main_form select").change(function() {'."\n"
                .'      $("#preferred_editor").attr("disabled",true);'."\n"
                .'});'."\n"
		."\n";
	if(isset($item['full_name']) && $item['full_name'])
		$context['text'] .= '// set the focus on first form field'."\n"
	 		.'$("#full_name").focus();'."\n"
	 		."\n";
	else
		$context['text'] .= '// set the focus on first form field'."\n"
	 		.'$("#first_name").focus();'."\n"
	 		."\n";
	$context['text'] .= '// enable tags autocompletion'."\n"
		.'$(document).ready( function() {'."\n"
		.'  Yacs.autocomplete_m("tags", "'.$context['url_to_root'].'categories/complete.php");'."\n"
		.'});  '."\n"
		.JS_SUFFIX;

	// the help panel
	$help = '<p>'.i18n::s('The nick name has to be unique throughout the database of users.').'</p>';

	// html and codes
	$help .= '<p>'.sprintf(i18n::s('%s and %s are available to enhance text rendering.'), Skin::build_link('codes/', i18n::s('YACS codes'), 'open'), Skin::build_link('smileys/', i18n::s('smileys'), 'open')).'</p>';

 	// locate mandatory fields
 	$help .= '<p>'.i18n::s('Mandatory fields are marked with a *').'</p>';

 	// change to another editor
	$help .= '<form action=""><p><select name="preferred_editor" id="preferred_editor" onchange="Yacs.setCookie(\'surfer_editor\', this.value); window.location = window.location;">';
	$selected = '';
	if(isset($_SESSION['surfer_editor']) && ($_SESSION['surfer_editor'] == 'fckeditor'))
		$selected = ' selected="selected"';
	$help .= '<option value="tinymce"'.$selected.'>'.i18n::s('TinyMCE')."</option>\n";
	$selected = '';
	if(isset($_SESSION['surfer_editor']) && ($_SESSION['surfer_editor'] == 'fckeditor'))
		$selected = ' selected="selected"';
	$help .= '<option value="fckeditor"'.$selected.'>'.i18n::s('FCKEditor')."</option>\n";
	$selected = '';
	if(!isset($_SESSION['surfer_editor']) || ($_SESSION['surfer_editor'] == 'yacs'))
		$selected = ' selected="selected"';
	$help .= '<option value="yacs"'.$selected.'>'.i18n::s('Textarea')."</option>\n";
	$help .= '</select></p></form>';

	$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'boxes', 'help');

}

// render the skin
render_skin();

?>
