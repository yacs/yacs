<?php
/**
 * authenticate a surfer
 *
 * @todo uses https if required
 *
 * [title]on-line authentication[/title]
 *
 * If the provided user name is not known locally, several options are
 * available for remote authentication, including:
 * - LDAP authentication
 * - XML-RPC autentication
 *
 * See [code]Users::login()[/code] in [script]users/users.php[/script].
 *
 * For this to take place use the configuration panel for users and declare
 * the authenticator implementation to use, plus any parameters.
 *
 * Following information is displayed on successful login:
 *
 * [*] User's avatar, if one exists
 *
 * [*] Time offset, as observed by some javascript code on the workstation
 *
 * [*] Recorded IP address
 *
 * [*] Date of previous login
 *
 * @link http://www.olate.com/articles/254 Use PHP and JavaScript to Display Local Time
 *
 * [*] Shortcuts are displayed in the extra section to streamline navigation and contribution efforts.
 *
 *
 * [title]one-click authentication[/title]
 *
 * This authentication mechanism helps members to come back to the
 * site, through registration confirmation by e-mail.
 *
 * The link used for one-click authentication references the target user profile,
 * and a salted secret.
 *
 * This is done through a base64-encoded snippet made of serialization of
 * following array:
 * - the string 'login'
 * - user id
 * - random string
 * - salted secret (see below)
 *
 * The salted secret is computed as follows:
 * - consider the three first args
 * - consider the secret handle of the user profile
 * - concatenate the four args, separated by ':'
 * - compute CRC32 in decimal
 *
 * [title]one-click modification[/title]
 *
 * This authentication mechanism is aiming to smooth the experience of a page
 * author receiving a notification by e-mail. This notification usually features
 * a link to jointly authenticate the author and to jump to the updated page.
 *
 * The link used for one-click authentication has to reference the target page,
 * and a salted secret.
 *
 * This is done through a base64-encoded snippet made of serialization of
 * following array:
 * - the string 'edit'
 * - target anchor (e.g., 'article:123')
 * - unused string
 * - salted secret (see below)
 *
 * The salted secret is computed as follows:
 * - consider the three first args
 * - consider the secret handle of the target page
 * - concatenate the four args, separated by ':'
 * - compute CRC32 in decimal
 *
 * [title]visitor authentication[/title]
 *
 * This script is aiming to authenticate visitors driven to a given page.
 *
 * At key resources, typically, a web page, web authors have the opportunity to
 * send e-mails to people invited to contribute to this page.
 *
 * To make the thing quite easy, these people are known only by their e-mail
 * address, and the challenge is to recognize them, to drive them to the proper
 * resource, and to deliver a streamlined end-user experience.
 *
 * The link used for visitor authentication has to reference the target page,
 * and to mention end-user credentials.
 *
 * This is done through a base64-encoded snippet made of serialization of
 * following array:
 * - the string 'visit'
 * - target anchor (e.g., 'article:123')
 * - user identifier or name (e.g., '47', or 'tom@foo.bar', or 'Tom &lt;tom@foo.bar&gt;')
 * - salted secret (see below)
 *
 * The salted secret is computed as follows:
 * - consider the three first args
 * - consider the secret handle of the target page
 * - concatenate the four args, separated by ':'
 * - compute CRC32 in decimal
 *
 * [title]script invocation[/title]
 *
 * This script may be called from anywhere either to login or to logout:
 * - users/login.php -- show the login form or process POSTed arguments
 * - users/login.php?url=... -- the same, with a URL to be used on success
 * - users/login.php/... -- authenticate surfer using providing credentials
 * - users/login.php?credentials=... -- use provided credentials
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Pierre Robert
 * @tester Antoine Bour
 * @tester AnsteyER
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// look for credentials
$credentials = NULL;
if(isset($_REQUEST['credentials']))
	$credentials = $_REQUEST['credentials'];
elseif(isset($context['arguments'][0]))
	$credentials = $context['arguments'][0];
$credentials = strip_tags($credentials);

// fix credentials if followed by text
if($credentials && ($position = strpos($credentials, '-')))
	$credentials = substr($credentials, 0, $position);

// data has been serialized, then base64 encoded
if($credentials && ($credentials = base64_decode($credentials))) {

	// json is more efficient, but we may have to fall-back to php serialization
	if(!$credentials = Safe::json_decode($credentials))
		$credentials = Safe::unserialize($credentials);

}

// load localized strings
if(is_callable(array('i18n', 'bind')))
    i18n::bind('users');

// load the skin
load_skin('users');

// do not index this page
$context->sif('robots','noindex');

// page title
if(!Surfer::is_logged())
    $context['page_title'] = i18n::s('Who are you?');
else
    $context['page_title'] = i18n::s('You are').' '.Surfer::get_name();

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// use provided credentials
} elseif($credentials) {

	// page author is coming back
	if(isset($credentials[0]) && ($credentials[0] == 'edit')) {

		// get an anchor
		if(!isset($credentials[1]) || (!$anchor = Anchors::get($credentials[1])))
			Logger::error(i18n::s('No anchor has been found.'));

		// retrieve poster attributes
		elseif((!$poster = $anchor->get_poster()) || !isset($poster['nick_name']))
			Logger::error(i18n::s('Request is invalid.'));

		// we need some salted secret
		elseif(!isset($credentials[2]))
			Logger::error(i18n::s('Request is invalid.'));

		// check salted hash
		elseif(!Users::check_credentials($credentials, $anchor->get_handle()))
			Logger::error(i18n::s('Request is invalid.'));

		// authenticate and redirect
		else {

			// note date of login
			$update_flag = FALSE;
			if(!Surfer::get_id() || (Surfer::get_id() != $poster['id']))
				$update_flag = TRUE;

			// save surfer profile in session context
			Surfer::set($poster, $update_flag);

			// redirect to target page
			Safe::redirect(full_link($anchor->get_url()));

		}

	// surfer is authenticating through e-mail
	} elseif(isset($credentials[0]) && ($credentials[0] == 'login')) {

		// get user profile
		if(!isset($credentials[1]) || (!$user = Users::get($credentials[1])))
			Logger::error('No item has the provided id.');

		// random string
		elseif(!isset($credentials[2]) || !$credentials[2])
			Logger::error(i18n::s('Request is invalid.'));

		// check salted hash
		elseif(!Users::check_credentials($credentials, $user['handle']))
			Logger::error(i18n::s('Request is invalid.'));

		// authenticate and offer to change the password
		else {

			// note date of login
			$update_flag = FALSE;
			if(!Surfer::get_id() || (Surfer::get_id() != $user['id']))
				$update_flag = TRUE;

			// save surfer profile in session context
			Surfer::set($user, $update_flag);

			// the user icon, if any
			if(isset($user['avatar_url']) && $user['avatar_url'])
				$context['page_image'] = $user['avatar_url'];

			// splash message
			$context['page_title'] = i18n::s('Welcome!');
			$context['text'] .= '<p>'.i18n::s('You have been successfully authenticated.').'</p>';

			// follow-up commands
			$follow_up = i18n::s('What do you want to do now?');
			$menu = array();
			$menu = array_merge($menu, array('users/password.php' => i18n::s('Change password')));
			$menu = array_merge($menu, array(Surfer::get_permalink() => i18n::s('My profile')));
			$follow_up .= Skin::build_list($menu, 'menu_bar');
			$context['text'] .= Skin::build_block($follow_up, 'bottom');

		}

	// surfer is an invited visitor
	} elseif(isset($credentials[0]) && ($credentials[0] == 'visit')) {

		// get an anchor
		if(!isset($credentials[1]) || (!$anchor = Anchors::get($credentials[1])))
			Logger::error(i18n::s('No anchor has been found.'));

		// visitor id or email address
		elseif(!isset($credentials[2]) || !$credentials[2])
			Logger::error(i18n::s('Request is invalid.'));

		// check salted hash
		elseif(!Users::check_credentials($credentials, $anchor->get_handle()))
			Logger::error(i18n::s('Request is invalid.'));

		// authenticate and redirect
		else {

			// extract the actual e-mail address -- Foo Bar <foo@bar.com> => foo@bar.com
			$tokens = explode(' ', $credentials[2]);
			$address = trim(str_replace(array('<', '>'), '', $tokens[count($tokens)-1]));

			// if surfer has not been authenticated yet
			if(!Surfer::get_id()) {

				// look for a surfer with this address
				if(!$user = Users::get($address)) {
					$user = array();
					$user['nick_name'] = $address;
					$user['email'] = $address;
				}

				// save surfer profile in session context
				Surfer::set($user, TRUE);

			}

			// add this anchor to allowed handles during this session
			Surfer::add_handle($anchor->get_handle());

			// redirect to target page
			Safe::redirect($anchor->get_url());

		}

	} else
		Logger::error(i18n::s('Request is invalid.'));

// some data have been posted
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// protect from hackers
	$name = preg_replace(FORBIDDEN_IN_NAMES, '_', strip_tags($_REQUEST['login_name']));

	// the surfer has been authenticated
	if($user = Users::login($name, $_REQUEST['login_password'])) {
	    
		// surfer request long validity authentication
		if(isset($_REQUEST['remember']) && $_REQUEST['remember'] =='Y')
			$context['users_with_permanent_authentication'] = 'Y';

		// set permanent name shown from top level
		Safe::setcookie('surfer_name', $user['nick_name'], time()+60*60*24*500, '/');

		// save surfer profile in session context
		Surfer::set($user);

		// redirect to previous page
		if(isset($context['users_without_login_welcome']) && ($context['users_without_login_welcome'] == 'Y')) {

			// go to the forwarded reference or to the front page
			if(isset($_REQUEST['login_forward']))
				Safe::redirect($_REQUEST['login_forward']);
			else
				Safe::redirect($context['url_to_home'].$context['url_to_root']);

		}

		// page title
		$context['page_title'] = i18n::s('Welcome!');

		//
		// panels
		//
		$panels = array();

		//
		// main panel
		//
		$information = '';

		// lay fields in a table
		$information .= Skin::table_prefix('form');
		$lines = 1;

		// a link to the user profile
		$cells = array();
		$cells[] = i18n::s('Your profile');
		$cells[] = 'left='.Surfer::get_link();
		$information .= Skin::table_row($cells, $lines++);

		// the email field
		if(Surfer::get_email_address()) {
			$cells = array();
			$cells[] = i18n::s('Your address');
			$cells[] = 'left='.Surfer::get_email_address();
			$information .= Skin::table_row($cells, $lines++);
		}

		// the capability field - associate, member, or subscriber
		$cells = array();
		$cells[] = i18n::s('Your status');
		if(Surfer::is_associate())
			$cells[] = 'left='.i18n::s('As an associate of this community, you may contribute freely to any part of this server.');
		elseif(Surfer::is_member())
			$cells[] = 'left='.i18n::s('As a member of this community, you may access freely most pages of this server.');
		else
			$cells[] = 'left='.i18n::s('As a subscriber of this community, you may freely access most pages of this server.');
		if(isset($cells[1]))
			$information .= Skin::table_row($cells, $lines++);

		// end of the table
		$information .= Skin::table_suffix();

		// display in a separate panel
		$panels[] = array('information', i18n::s('You'), 'information_panel', $information);

		//
		// assemble all tabs
		//
		$context['text'] .= Skin::build_tabs($panels);

		// follow-up commands
		$follow_up = i18n::s('Where do you want to go now?');
		$menu = array();
		if(isset($_REQUEST['login_forward']))
			$menu[] = Skin::build_link($_REQUEST['login_forward'], i18n::s('Move forward'), 'button');
		elseif(isset($_SERVER['HTTP_REFERER']) && !preg_match('/users\/login\.php/', $_SERVER['HTTP_REFERER']))
			$menu[] = Skin::build_link($_SERVER['HTTP_REFERER'], i18n::s('Move forward'), 'button');
		else
			$menu[] = Skin::build_link($context['url_to_root'], i18n::s('Front page'), 'button');
		$menu[] = Skin::build_link(Surfer::get_permalink(), i18n::s('My profile'), 'button');
		if(Surfer::is_associate())
			$menu[] = Skin::build_link('control/', i18n::s('Control Panel'), 'button');
		$follow_up .= Skin::finalize_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

		//
		// extra panel
		//

		// user profile aside
		$context['components']['profile'] = Skin::build_profile($user, 'extra');

		// navigation links, in an extra box
		$links = array();
		$links = array_merge($links, array( $context['url_to_root'] => i18n::s('Front page') ));
		$links = array_merge($links, array( 'sections/' => i18n::s('Site map') ));
		$links = array_merge($links, array( 'users/' => i18n::s('People') ));
		$links = array_merge($links, array( 'categories/' => i18n::s('Categories') ));
		$links = array_merge($links, array( 'search.php' => i18n::s('Search') ));

		$context['components']['boxes'] .= Skin::build_box(i18n::s('Navigate'), Skin::build_list($links, 'compact'), 'boxes');

	// failed authentication
	} else {

		// set permanent name shown from top level
		Safe::setcookie('surfer_name', preg_replace('/(@.+)$/', '', $name), time()+60*60*24*500, '/');

		// reset the current session
		Surfer::reset();

		// share status
		Logger::error(i18n::s('Failed authentication'), FALSE);

		// help surfer to recover
		if($items = Users::search($name, 1.0, 7, 'password')) {
			// display candidate profiles
			if(is_array($items))
				$items = Skin::build_list($items, 'decorated');
			$context['text'] .= Skin::build_box(i18n::s('Have you lost your password?'), $items);

		}

		// ask for support
		$context['text'] .= Skin::build_box(i18n::s('Do you need more help?'), '<p>'.sprintf(i18n::s('Use the %s to ask for help'), Skin::build_link('query.php', i18n::s('query form'), 'shortcut')).'</p>');

	}

// provide the empty form by default
} elseif(!Surfer::is_logged()) {

	// the page title
	if(isset($_REQUEST['url']))
		$context['page_title'] = i18n::s('The page you requested is available only to registered members.');

	// the place to authenticate
	$main_column = '';

	// the form
	$main_column .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>';

	// the title
	$main_column .= Skin::build_block(i18n::s('Please authenticate'), 'title');

	// use cookie, if any
	$name = '';
	if(isset($_COOKIE['surfer_name']))
		$name = $_COOKIE['surfer_name'];

	// the id or email field
	$label = i18n::s('Your nick name, or e-mail address');
	$input = '<input type="text" name="login_name" id="login_name" size="45" maxlength="255" value="'.encode_field($name).'" />'."\n";
	$main_column .= '<p>'.$label.BR.$input.'</p>';

	// the password
	$label = i18n::s('Password');
	$input = '<input type="password" name="login_password" size="45" maxlength="255" />'."\n";
	$main_column .= '<p>'.$label.BR.$input.'</p>';
	
	// remember me ?
	if($context['users_with_permanent_authentication'] == 'U') {
	    $label = i18n::s('Stay connected');
	    $input = '<input type="checkbox" name="remember" value="Y" />'."\n";
	    $main_column .= '<p>'.$input.'&nbsp;'.$label.'</p>';
	}

	// bottom commands
	$menu = array();

	// the submit button
	$menu[] = Skin::build_submit_button(i18n::s('Login'), NULL, NULL, 'login_button');

	// lost password?
	$menu[] = Skin::build_link('users/password.php', i18n::s('Lost password'), 'span');

	// insert the menu in the page
	$main_column .= Skin::finalize_list($menu, 'menu_bar');

	// save the forwarding url as well
	if(isset($_REQUEST['url']) && !strncmp($_REQUEST['url'], 'http', 4))
		$main_column .= '<p><input type="hidden" name="login_forward" value="'.encode_field($_REQUEST['url']).'" /></p>';
	elseif(isset($_REQUEST['url']))
		$main_column .= '<p><input type="hidden" name="login_forward" value="'.encode_field($context['url_to_root'].$_REQUEST['url']).'" /></p>';
	elseif(isset($_SERVER['HTTP_REFERER']))
		$main_column .= '<p><input type="hidden" name="login_forward" value="'.encode_field($_SERVER['HTTP_REFERER']).'" /></p>';

	// end of the form
	$main_column .= '</div></form>';

	// offer a self-registration, if allowed
	$side_column = '';
	if(!isset($context['users_without_registration']) || ($context['users_without_registration'] != 'Y')) {

		if(isset($_REQUEST['url'])) {
			$link = 'users/edit.php?forward='.htmlentities(urlencode($_REQUEST['url']));
		} elseif(isset($_SERVER['HTTP_REFERER'])) {
			$link = 'users/edit.php?forward='.htmlentities(urlencode($_SERVER['HTTP_REFERER']));
		} else
			$link = 'users/edit.php';

		$side_column .= Skin::build_block(Skin::build_block(i18n::s('Create your profile'), 'title').sprintf(i18n::s('%s if you have not yet a profile for yourself at %s.'), Skin::build_link($link, i18n::s('Click here to register'), 'shortcut'), $context['site_name']), 'sidecolumn');
	}

	// layout the columns
	if($side_column)
		$context['text'] .= Skin::layout_horizontally($main_column, $side_column);
	elseif($main_column)
		$context['text'] .= $main_column;

	// the script used for data checking on the browser
	Page::insert_script(
		// check that main fields are not empty
		'func'.'tion validateDocumentPost(container) {'."\n"
			// email is mandatory
		.'	if(!container.login_name.value) {'."\n"
		.'		alert("'.i18n::s('You must provide a nick name or an email address.').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		return false;'."\n"
		.'	}'."\n"
			// successful check
		.'	return true;'."\n"
		.'}'."\n"
		// set the focus on first form field
		.'$("#login_name").focus();'."\n"
		."\n"
		);

	// a place holder for cookies activation
	$context['text'] .= '<p id="ask_for_cookies" style="display: none; color: red; text-decoration: blink;"></p>';

	// the script used to check that cookies are activated
	Page::insert_script(
		'document.cookie = \'CookiesEnabled=1\';'."\n"
		.'if((document.cookie == "") && document.getElementById) {'."\n"
		."\t".'$("#ask_for_cookies").html("'.i18n::s('Your browser must accept cookies in order to successfully register and log in.').'");'."\n"
		."\t".'$("#ask_for_cookies").style.display = \'block\';'."\n"
		."\t".'$("#login_button").disabled = true;'."\n"
		.'}'."\n"
		);

	// the help panel
	$help = '<p>'.i18n::s('Your browser must accept cookies in order to successfully register and log in.').'</p>'
		.'<p>'.sprintf(i18n::s('If you already are a registered member, but do not remember your username and/or password, %s.'), Skin::build_link('users/password.php', i18n::s('click here'))).'</p>';
	$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'boxes', 'help');

} else {
    
    if(isset($_REQUEST['url'])) {
	if(!strncmp($_REQUEST['url'], 'http', 4))
	    $url = encode_field($_REQUEST['url']);
	else
	    $url = $context['url_to_root'].$_REQUEST['url'];
	
	Safe::redirect($url);
    }
    
    $context['text'] = i18n::s('... and you are already logged in !');
}

// render the skin
render_skin();

?>
