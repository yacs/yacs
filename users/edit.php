<?php
/**
 * create a new user or edit an existing one
 *
 * @todo on subscriptor application, post a query page when there is no messaging facility (gnapz)
 * @todo select preferred language for alert messages
 * @todo if a profile is modified, send a message to the target user to let him know about it
 * @todo derive this to users/subscribe.php
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
 * If the file [code]demo.flag[/code] exists, the script assumes that this instance
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
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author Vincent No&euml;l
 * @author GnapZ
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @tester Cloubech
 * @tester Dakoon
 * @tester Fw_crocodile
 * @tester GnapZ
 * @tester Manuel López Gallego
 * @tester J&eacute;r&ocirc;me Douill&eacute;
 * @tester Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

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
include_once '../overlays/overlay.php';
if(isset($item['overlay']) && $item['overlay'])
	$overlay = Overlay::load($item);
elseif(isset($context['users_overlay']) && $context['users_overlay'])
	$overlay = Overlay::bind($context['users_overlay']);

// associates can do what they want
if(Surfer::is_associate())
	$permitted = TRUE;

// the page of the authenticated surfer
elseif(isset($item['id']) && Surfer::is_creator($item['id']))
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

// always validate input syntax
if(isset($_REQUEST['introduction']))
	validate($_REQUEST['introduction']);
if(isset($_REQUEST['description']))
	validate($_REQUEST['description']);

// permission denied
if(!$permitted) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);

	// registration is not allowed to anonymous surfers
	if(!isset($item['id']) && !Surfer::is_logged())
		Skin::error(sprintf(i18n::s('Self-registration is not allowed. Use the %s to submit your application.'), Skin::build_link('query.php', i18n::s('query form'), 'shortcut')));

	// permission denied to authenticated user
	else
		Skin::error(i18n::s('You are not allowed to perform this operation.'));

// an error occured
} elseif(count($context['error'])) {
	$item = $_REQUEST;
	$with_form = TRUE;

// save posted data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// build the full name for new users
	if(isset($_REQUEST['first_name']) || isset($_REQUEST['last_name']))
		$_REQUEST['full_name'] = trim($_REQUEST['last_name'].' '.$_REQUEST['first_name']);

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

		// allow back-referencing from overlay
		$_REQUEST['self_reference'] = 'user:'.$item['id'];
		$_REQUEST['self_url'] = $context['url_to_root'].Users::get_url($item['id']);

		// actual update
		if(Users::put($_REQUEST)
			&& (!is_object($overlay) || $overlay->remember('update', $_REQUEST))) {

			// 'users/edit.php#put' hook
			if(is_callable(array('Hooks', 'include_scripts')))
				Hooks::include_scripts('users/edit.php#put', $item['id']);

			// display the updated page
			Safe::redirect($context['url_to_home'].$context['url_to_root'].Users::get_url($item['id'], 'view', isset($item['nick_name'])?$item['nick_name']:''));

		}

		// on error display the form again
		$with_form = TRUE;
		$id = $_REQUEST['id'];
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
			Skin::error(i18n::s('Please confirm your password.'));
			$item = $_REQUEST;
			$with_form = TRUE;

		// stop robots
		} elseif(Surfer::may_be_a_robot()) {
			Skin::error(i18n::s('Please prove you are not a robot.'));
			$item = $_REQUEST;
			$with_form = TRUE;

		// actual post
		} elseif(!$id = Users::post($_REQUEST)) {

			// on error display the form again
			$with_form = TRUE;
			$item = $_REQUEST;

		// successful post
		} else {

			// save id in the request as well;
			$_REQUEST['id'] = $id;

			// allow back-referencing from overlay
			$_REQUEST['self_reference'] = 'user:'.$id;
			$_REQUEST['self_url'] = Users::get_url($id, 'view', isset($_REQUEST['nick_name'])?$_REQUEST['nick_name']:'');

			// post an overlay, with the new user id
			if(is_object($overlay) && !$overlay->remember('insert', $_REQUEST)) {
				$item = $_REQUEST;
				$with_form = TRUE;

			// thanks
			} else {

				// 'users/edit.php#post' hook
				if(is_callable(array('Hooks', 'include_scripts')))
					Hooks::include_scripts('users/edit.php#post', $id);

				// associates are redirected to the new user page
				if(Surfer::is_associate())
					Safe::redirect($context['url_to_home'].$context['url_to_root'].Users::get_url($id, 'view', isset($_REQUEST['nick_name'])?$_REQUEST['nick_name']:''));

				// welcome message
				else {

					// get the new record
					$item =& Users::get($id);

					// start a session with this new record
					if(!Surfer::is_logged())
						Surfer::set($item);

					// the welcome page
					$context['page_title'] = i18n::s('Welcome !');

					// the welcome message
					$context['text'] .= '<p>'.ucfirst($item['nick_name']).',</p>'
						.'<p>'.i18n::s('You are now a registered user of this community. Each time you will visit this site, please provide your nick name and password to authenticate.').'</p>'
						.'<p>'.i18n::s('What do you want to do now?').'</p>';

					// just proceed
					if(isset($_REQUEST['forward']) && $_REQUEST['forward']) {
						$context['text'] .= '<ul><li><b>'.Skin::build_link($_REQUEST['forward'], i18n::s('Proceed with what I was doing before registering')).'</a></b></li></ul>';
					}

					// select an avatar
					$context['text'] .= '<ul><li>'.sprintf(i18n::s('%s from the library'), '<a href="'.$context['url_to_root'].'users/select_avatar.php?id='.$id.'">'.i18n::s('Select an avatar').'</a>').'</li></ul>';

					// post a new page
					if(($item['capability'] == 'M') || ($item['capability'] == 'A')) {
						$context['text'] .= '<ul><li>'.sprintf(i18n::s('%s, maybe with some images and/or files'), '<a href="'.$context['url_to_root'].'articles/edit.php">'.i18n::s('Add a page').'</a>').'</li></ul>';
					}

					// edit profile
					$context['text'] .= '<ul><li>'.sprintf(i18n::s('%s, to let others have a better understanding of who I am'), '<a href="'.$context['url_to_root'].'users/edit.php?id='.$id.'">'.i18n::s('Edit my user profile').'</a>').'</li></ul>';

					// more help
					$context['text'] .= '<ul><li>'.sprintf(i18n::s('%s, and get basic directions'), '<a href="'.$context['url_to_root'].'help.php">'.i18n::s('Go the main help page').'</a>').'</li></ul>';

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
					$description = $context['url_to_home'].$context['url_to_root'].Users::get_url($item['id'], 'view', $item['nick_name']);
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

	// this form has several panels
	$panels = array();
	$panels['information'] = '';
	$panels['contact'] = '';
	$panels['preferences'] = '';
	$fields = array();

	// the information panel
	//

	// associates can change the capability flag: Associate, Member, Subscriber or ?-unknown
	if(Surfer::is_associate()) {
		$label = i18n::s('Capability').' *';
		$input = '<input type="radio" name="capability" value="A"';
		if(isset($item['capability']) && ($item['capability'] == 'A'))
			$input .= ' checked="checked"';
		$input .= ' '.EOT.' '.i18n::s('Associate').' ';
		$input .= '<input type="radio" name="capability" value="M"';
		if(!isset($item['capability']) || ($item['capability'] == 'M'))
			$input .= ' checked="checked"';
		$input .= ' '.EOT.' '.i18n::s('Member').' ';
		$input .= '<input type="radio" name="capability" value="S"';
		if(isset($item['capability']) && ($item['capability'] == 'S'))
			$input .= ' checked="checked"';
		$input .= ' '.EOT.' '.i18n::s('Subscriber').' ';
		$input .= '<input type="radio" name="capability" value="?"';
		if(isset($item['capability']) && ($item['capability'] == '?'))
			$input .= ' checked="checked"';
		$input .= ' '.EOT.' '.i18n::s('Banned')."\n";
		$fields[] = array($label, $input);
	}

	// full name
	if(isset($item['full_name']) && $item['full_name']) {
		$label = i18n::s('Full name');
		$input = '<input type="text" name="full_name" size="50" value="'.encode_field($item['full_name']).'" '.EOT;
		$hint = i18n::s('Last name followed by a comma and by other names you may have');
		$fields[] = array($label, $input, $hint);
	} else {
		$label = i18n::s('First name(s)');
		$input = '<input type="text" name="first_name" size="50" '.EOT;
		$fields[] = array($label, $input);

		$label = i18n::s('Last name(s)');
		$input = '<input type="text" name="last_name" size="50" '.EOT;
		$fields[] = array($label, $input);
	}

	// nick name
	$label = i18n::s('Nick name').' *';
	$input = '<input type="text" name="nick_name" id="nick_name" size="40" value="'.encode_field(isset($item['nick_name'])?$item['nick_name']:'').'" '.EOT;
	$hint = i18n::s('Please carefully select a meaningful and unused nick name.');
	$fields[] = array($label, $input, $hint);

	// the password, but only for registering user
	if(!isset($item['id'])) {
		$label = i18n::s('Password').' *';
		$input = '<input type="password" name="password" size="20" value="'.encode_field(isset($item['password'])?$item['password']:'').'" '.EOT;
		$hint = i18n::s('We suggest at least 4 numbers, two letters, and a punctuation sign - in any order');
		$fields[] = array($label, $input, $hint);

		// the password has to be confirmed
		$label = i18n::s('Password confirmation').' *';
		$input = '<input type="password" name="confirm" size="20" value="'.encode_field(isset($item['confirm'])?$item['confirm']:'').'" '.EOT;
		$fields[] = array($label, $input);

		// stop robots
		if($field = Surfer::get_robot_stopper())
			$fields[] = $field;

	}

	// the email address
	$label = i18n::s('E-mail address');
	if(isset($context['users_with_email_validation']) && ($context['users_with_email_validation'] == 'Y'))
		$label .= ' *';
	$input = '<input type="text" name="email" size="40" value="'.encode_field(isset($item['email'])?$item['email']:'').'" '.EOT;
	$hint = '';
	if(!isset($item['id']) && isset($context['user_with_email_validation']) && ($context['user_with_email_validation'] == 'Y'))
		$hint = i18n::s('You will receive a message on this address to activate your membership.');
	$hint .= ' '.i18n::s('We won\'t disclose personal information about you or your company to anyone outside this site.');
	$fields[] = array($label, $input, $hint);

	// tags
	$label = i18n::s('Tags');
	$input = '<input type="text" name="tags" id="tags" value="'.encode_field(isset($item['tags'])?$item['tags']:'').'" size="45" maxlength="255" accesskey="t"/><div id="tags_choices" class="autocomplete"></div>';
	$hint = i18n::s('A comma-separated list of keywords');
	$fields[] = array($label, $input, $hint);

	// include overlay fields, if any
	if(is_object($overlay)) {

		// append editing fields for this overlay
		$fields = array_merge($fields, $overlay->get_fields($item));

	}

	// the description
	$label = i18n::s('Description');

	// use the editor if possible
	$input = Surfer::get_editor('description', isset($item['description'])?$item['description']:'', TRUE);
	$fields[] = array($label, $input);

	// form fields in this panel
	$panels['information'] .= Skin::build_form($fields);
	$fields = array();

	// if we are editing an existing item
	if(isset($item['id'])) {

		// related images
		$box = '';

		// the menu to post a new image or to select an avatar
		$menu = array();

		if(Surfer::may_upload())
			$menu = array_merge($menu, array('images/edit.php?anchor=user:'.$item['id'] => i18n::s('Add an image')));

		$box .= Skin::build_list($menu, 'menu_bar');

		// the list of images
		include_once '../images/images.php';
		if($items = Images::list_by_date_for_anchor('user:'.$item['id'], 0, 50)) {

			// help to insert in textarea
			if(!isset($_SESSION['surfer_editor']) || (($_SESSION['surfer_editor'] != 'fckeditor') && ($_SESSION['surfer_editor'] != 'tinymce')))
				$box .= '<p>'.i18n::s('Click on links to insert images in the main field.')."</p>\n";

			$box .= Skin::build_list($items, 'decorated');
		}

		// in a folded box
		$panels['information'] .= Skin::build_box(i18n::s('Images'), $box, 'folder');

		// related locations
		$box = '';

		// the menu to post a new location
		$menu = array( 'locations/edit.php?anchor=user:'.$item['id'] => i18n::s('Add a location') );
		$box .= Skin::build_list($menu, 'menu_bar');

		// the list of locations
		include_once '../locations/locations.php';
		$items = Locations::list_by_date_for_anchor('user:'.$item['id']);
		$box .= Skin::build_list($items, 'decorated');

		// in a folded box
		$panels['information'] .= Skin::build_box(i18n::s('Locations'), $box, 'folder');
	}

	// the contact panel
	//

	// organisation
	$label = i18n::s('Organization');
	$input = '<input type="text" name="vcard_organization" size="40" value="'.encode_field(isset($item['vcard_organization'])?$item['vcard_organization']:'').'" '.EOT;
	$fields[] = array($label, $input);

	// title
	$label = i18n::s('Title');
	$input = '<input type="text" name="vcard_title" size="40" value="'.encode_field(isset($item['vcard_title'])?$item['vcard_title']:'').'" '.EOT;
	$hint = i18n::s('Your occupation, your motto, or some interesting words');
	$fields[] = array($label, $input, $hint);

	// label
	$label = i18n::s('Physical address');
	$input = '<textarea name="vcard_label" rows="5" cols="50">'.encode_field(isset($item['vcard_label'])?$item['vcard_label']:'').'</textarea>';
	$fields[] = array($label, $input);

	// phone number
	$label = i18n::s('Phone number');
	$input = '<input type="text" name="phone_number" size="20" value="'.encode_field(isset($item['phone_number'])?$item['phone_number']:'').'" '.EOT;
	$fields[] = array($label, $input);

	// alternate number
	$label = i18n::s('Alternate number');
	$input = '<input type="text" name="alternate_number" size="20" value="'.encode_field(isset($item['alternate_number'])?$item['alternate_number']:'').'" '.EOT;
	$fields[] = array($label, $input);

	// web address, if any
	$label = i18n::s('Web address');
	$input = '<input type="text" name="web_address" size="40" value="'.encode_field(isset($item['web_address'])?$item['web_address']:'').'" '.EOT;
	$hint = i18n::s('If your home page is not here.');
	$fields[] = array($label, $input, $hint);

	// birth date
	$label = i18n::s('Birth date');
	$input = '<input type="text" name="birth_date" size="20" value="'.encode_field(isset($item['birth_date'])?$item['birth_date']:'').'" '.EOT;
	$hint = i18n::s('YYYY-MM-DD');
	$fields[] = array($label, $input, $hint);

	// agent
	$label = i18n::s('Alternate contact');
	$input = '<input type="text" name="vcard_agent" size="40" value="'.encode_field(isset($item['vcard_agent'])?$item['vcard_agent']:'').'" '.EOT;
	$hint = i18n::s('Another person who can act on your behalf');
	$fields[] = array($label, $input, $hint);

	// extend the form
	$panels['contact'] .= Skin::build_form($fields);
	$fields = array();

	// the AIM address
	$label = i18n::s('AIM Screenname');
	$input = '<input type="text" name="aim_address" size="40" value="'.encode_field(isset($item['aim_address'])?$item['aim_address']:'').'" '.EOT;
	$hint = sprintf(i18n::s('Fill this one only if you are a regular %s user.'), Skin::build_link('http://www.aim.com/', i18n::s('AOL Instant Messenger'), 'external'));
	$fields[] = array($label, $input, $hint);

	// the ICQ number
	$label = i18n::s('ICQ Number');
	$input = '<input type="text" name="icq_address" size="40" value="'.encode_field(isset($item['icq_address'])?$item['icq_address']:'').'" '.EOT;
	$hint = sprintf(i18n::s('Fill this one only if you use %s'), Skin::build_link('http://www.icq.com/', i18n::s('ICQ'), 'external'));
	$fields[] = array($label, $input, $hint);

	// the IRC address
	$label = i18n::s('IRC address');
	$input = '<input type="text" name="irc_address" size="40" value="'.encode_field(isset($item['irc_address'])?$item['irc_address']:'').'" '.EOT;
	$hint = sprintf(i18n::s('Fill this one only if you use %s'), Skin::build_link('http://www.irchelp.org/', i18n::s('IRC'), 'external'));
	$fields[] = array($label, $input, $hint);

	// the Jabber address
	$label = i18n::s('Jabber address');
	$input = '<input type="text" name="jabber_address" size="40" value="'.encode_field(isset($item['jabber_address'])?$item['jabber_address']:'').'" '.EOT;
	$hint = sprintf(i18n::s('If you are using some %s solution.'), Skin::build_link('http://www.jabber.org/', i18n::s('Jabber'), 'external'));
	$fields[] = array($label, $input, $hint);

	// the MSN address
	$label = i18n::s('MSN address');
	$input = '<input type="text" name="msn_address" size="40" value="'.encode_field(isset($item['msn_address'])?$item['msn_address']:'').'" '.EOT;
	$hint = sprintf(i18n::s('Fill this one only if you have a %s account'), Skin::build_link('http://www.msn.com', i18n::s('MSN'), 'external'));
	$fields[] = array($label, $input, $hint);

	// the skype address
	$label = i18n::s('Skype id');
	$input = '<input type="text" name="skype_address" size="40" value="'.encode_field(isset($item['skype_address'])?$item['skype_address']:'').'" '.EOT;
	$hint = sprintf(i18n::s('Fill this one only if you use %s'), Skin::build_link('http://www.skype.com', i18n::s('Skype'), 'external'));
	$fields[] = array($label, $input, $hint);

	// the Yahoo address
	$label = i18n::s('Yahoo address');
	$input = '<input type="text" name="yahoo_address" size="40" value="'.encode_field(isset($item['yahoo_address'])?$item['yahoo_address']:'').'" '.EOT;
	$hint = sprintf(i18n::s('Fill this one only if you are a regular %s user'), Skin::build_link('http://messenger.yahoo.com/', 'Yahoo Messenger', 'external'));
	$fields[] = array($label, $input, $hint);

	// add a folded box
	$panels['contact'] .= Skin::build_box(i18n::s('Instant messaging'), Skin::build_form($fields), 'folder');
	$fields = array();

	// pgp key
	$label = i18n::s('PGP key or certificate');
	$input = '<textarea name="pgp_key" rows="5" cols="50">'.encode_field(isset($item['pgp_key'])?$item['pgp_key']:'').'</textarea>';
	$hint = i18n::s('Paste here the public key you would like to share with others.');
	$fields[] = array($label, $input, $hint);

	// add a folded box
	$panels['contact'] .= Skin::build_box(i18n::s('Public key'), Skin::build_form($fields), 'folder');
	$fields = array();

	// the preferences panel
	//

	// associates may change the active flag: Yes/public, Restricted/logged, No/associates
	if(Surfer::is_associate()) {
		$label = i18n::s('Visibility');
		$input = '<input type="radio" name="active" value="Y"';
		if(!isset($item['active']) || ($item['active'] == 'Y'))
			$input .= ' checked="checked"';
		$input .= ' '.EOT.' '.i18n::s('Anyone may read this profile.')
			.BR.'<input type="radio" name="active" value="R"';
		if(isset($item['active']) && ($item['active'] == 'R'))
			$input .= ' checked="checked"';
		$input .= ' '.EOT.' '.i18n::s('Access is restricted to authenticated members.')
			.BR.'<input type="radio" name="active" value="N"';
		if(isset($item['active']) && ($item['active'] == 'N'))
			$input .= ' checked="checked"';
		$input .= ' '.EOT.' '.i18n::s('Access is restricted to associates.')."\n";
		$fields[] = array($label, $input);
	}

	// the avatar url
	if(isset($item['id'])) {
		$label = i18n::s('Avatar URL');
		$input = '<input type="text" name="avatar_url" size="50" value="'.encode_field(isset($item['avatar_url'])?$item['avatar_url']:'').'" maxlength="255" '.EOT;
		$hint = sprintf(i18n::s('%s, paste your gravatar address, or use the list of images attached to this profile, if any.'), Skin::build_link(Users::get_url($item['id'], 'select_avatar'), i18n::s('Select an avatar from the library'), 'basic'));
		$fields[] = array($label, $input, $hint);
	}

	// from where
	$label = i18n::s('From');
	$input = '<input type="text" name="from_where" size="50" value="'.encode_field(isset($item['from_where'])?$item['from_where']:'').'" maxlength="255" '.EOT;
	$hint = i18n::s('Some hint on your location (eg, \'Paris\', \'home\', \'the toys-for-sick-persons department\')');
	$fields[] = array($label, $input, $hint);

	// the introduction
	$label = i18n::s('Introduction');
	$input = '<textarea name="introduction" rows="5" cols="50">'.encode_field(isset($item['introduction'])?$item['introduction']:'').'</textarea>';
	$hint = i18n::s('Displayed aside your pages');
	$fields[] = array($label, $input, $hint);

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
	$input .= ' '.EOT;
	if(isset($item['id']))
		$input .= ' '.i18n::s('Confirm every password change.')."\n";
	else
		$input .= ' '.i18n::s('Confirm registration and password.')."\n";

	// receive alerts
	$input .= BR.'<input type="checkbox" name="without_alerts" value="N"';
	if(!isset($item['without_alerts']) || ($item['without_alerts'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= ' '.EOT.' '.i18n::s('Alert me when my articles are commented.')."\n";

	// receive private messages
	$input .= BR.'<input type="checkbox" name="without_messages" value="N"';
	if(!isset($item['without_messages']) || ($item['without_messages'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= ' '.EOT.' '.i18n::s('Allow other members to send me messages.')."\n";

	// explicit newsletter subscription
	$input .= BR.'<input type="checkbox" name="with_newsletters" value="Y"';
	if(!isset($item['id']) || !isset($item['with_newsletters']) || ($item['with_newsletters'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= ' '.EOT.' '.i18n::s('Send me periodical newsletters related to this server.')."\n";

	$hint = i18n::s('Your explicit approval is a pre-requisite for us to use your e-mail address.');
	$fields[] = array($label, $input, $hint);

	// editor
	$label = i18n::s('Editor');
	$input = '<select name="preferred_editor">';	// hack because of FCKEditor already uses 'editor'
	if(isset($item['editor']))
		;
	elseif(!isset($context['users_default_editor']))
		$item['editor'] = 'yacs';
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

//	// options
//	if(Surfer::is_associate()) {
//		$label = i18n::s('Options');
//		$input = '<input type="text" name="options" size="55" value="'.encode_field(isset($item['options']) ? $item['options'] : '').'" maxlength="255" accesskey="o"'.EOT;
//		$hint = i18n::s('You may combine several keywords:').' locked';
//		$fields[] = array($label, $input, $hint);
//	}

	// share screen
	$label = i18n::s('Share screen');
	$input = '<input type="radio" name="with_sharing" value="N"';
	if(!isset($item['with_sharing']) || ($item['with_sharing'] == 'N'))
		$input .= ' checked="checked"';
	$input .= ' '.EOT.' '.i18n::s('Screen is not shared with other people.')
		.BR.'<input type="radio" name="with_sharing" value="V"';
	if(isset($item['with_sharing']) && ($item['with_sharing'] == 'V'))
		$input .= ' checked="checked"';
	$input .= ' '.EOT.' '.i18n::s('Allow remote access using VNC.')
		.BR.'<input type="radio" name="with_sharing" value="M"';
	if(isset($item['with_sharing']) && ($item['with_sharing'] == 'M'))
		$input .= ' checked="checked"';
	$input .= ' '.EOT.' '.i18n::s('Allow remote access with NetMeeting.')."\n";
	$fields[] = array($label, $input);

	// proxy
	if(isset($item['login_address'])) {
		$label = i18n::s('Network address');
		$input = '<input type="text" name="proxy_address" size="55" value="'.encode_field(isset($item['proxy_address']) ? $item['proxy_address'] : '').'" maxlength="255"'.EOT;
		$hint = sprintf(i18n::s('The network address to be used to reach your workstation, if not %s'), $item['login_address']);
		$fields[] = array($label, $input, $hint);
	}

	// form fields in this panel
	$panels['preferences'] .= Skin::build_form($fields);
	$fields = array();

	// show all tabs
	//
	$all_tabs = array(
		array('information_tab', i18n::s('Information'), 'information_panel', $panels['information']),
		array('contact_tab', i18n::s('Contact'), 'contact_panel', $panels['contact']),
		array('preferences_tab', i18n::s('Preferences'), 'preferences_panel', $panels['preferences'])
		);

	// let YACS do the hard job
	$context['text'] .= Skin::build_tabs($all_tabs);

	//
	// bottom commands
	//
	$menu = array();

	// the submit button
	$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');

	// cancel button
	if(isset($item['id']))
		$menu[] = Skin::build_link(Users::get_url($item['id'], 'view', $item['nick_name'], $item['full_name']), i18n::s('Cancel'), 'span');

	// insert the menu in the page
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// link to privacy statement
	if(!isset($item['id']) && !Surfer::is_associate())
		$context['text'] .= '<p>'.sprintf(i18n::s('By clicking submit, you agree to the terms and conditions outlined in the %s.'), Skin::build_link('privacy.php', i18n::s('privacy policy'), 'basic')).'</p>';

	// associates may decide to not stamp changes -- complex command
	if(isset($item['id']) && Surfer::is_associate() && Surfer::has_all())
		$context['text'] .= '<p><input type="checkbox" name="silent" value="Y"'.EOT.' '.i18n::s('Do not change modification date.').'</p>';

	// transmit the id as a hidden field
	if(isset($item['id']) && $item['id'])
		$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'"'.EOT;

	// transmit the link to use after registration
	if(!isset($item['id']) && isset($_REQUEST['forward']) && $_REQUEST['forward'])
		$context['text'] .= '<input type="hidden" name="forward" value="'.encode_field($_REQUEST['forward']).'"'.EOT;

	// end of the form
	$context['text'] .= '</div></form>';

	// append the script used for data checking on the browser
	$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
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
		.'// set the focus on first form field'."\n"
		.'document.getElementById("nick_name").focus();'."\n"
		."\n"
		.'// enable tags autocompletion'."\n"
		.'Event.observe(window, "load", function() { new Ajax.Autocompleter("tags", "tags_choices", "'.$context['url_to_root'].'categories/complete.php", { paramName: "q", minChars: 1, frequency: 0.4, tokens: "," }); });'."\n"
		.'// ]]></script>';

	// the help panel
	if(isset($item['id']) || Surfer::is_associate()) {
		$help = '<p>'.i18n::s('The nick name has to be unique throughout the database of users.').'</p>';

		// html and codes
		$help .= '<p>'.sprintf(i18n::s('%s and %s are available to enhance text rendering.'), Skin::build_link('codes/', i18n::s('YACS codes'), 'help'), Skin::build_link('smileys/', i18n::s('smileys'), 'help')).'</p>';

	} else {
		$help = i18n::s('Help us to create a better online experience for you, and become a registered user of this site.')
			.'<p>'.i18n::s('With your registration, you will benefit from advanced services such as the ability:').'</p><ul class="compact">'
			.'<li>'.i18n::s('to view pages restricted to the core community').'</li>'
			.'<li>'.i18n::s('to submit new articles into available sections').'</li>'
			.'<li>'.i18n::s('to attach a file to an existing page').'</li>'
			.'<li>'.i18n::s('to post a camera shot or an image to a published page').'</li>'
			.'<li>'.i18n::s('to comment any viewed page').'</li>'
			.'<li>'.i18n::s('and more...').'</li></ul>'
			.'<p>'.i18n::s('And, best of all -- it\'s FREE.').'</p>'
			.'<p>'.i18n::s('We are aiming to protect your privacy.')
			.' '.i18n::s('We don\'t share or distribute the email addresses stored in our database.').'</p>';
	}

 	// locate mandatory fields
 	$help .= '<p>'.i18n::s('Mandatory fields are marked with a *').'</p>';

 	// change to another editor
	$help .= '<form><p><select name="preferred_editor" onchange="Yacs.setCookie(\'surfer_editor\', this.value); window.location = window.location;">';
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

	$context['extra'] .= Skin::build_box(i18n::s('Help'), $help, 'navigation', 'help');

}

// render the skin
render_skin();

?>