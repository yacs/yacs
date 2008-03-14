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
 * [*] List of on-going actions, if any. See [script]actions/index.php[/script] for more information on actions.
 *
 * [*] Shortcuts are displayed in the extra section to streamline navigation and contribution efforts.
 *
 *
 * [title]one-click authentication[/title]
 *
 * This authentication mechanism is aiming to help members to come back to the
 * site, through registration confirmation by e-mail.
 *
 * The link used for one-click authentication references the target user profile,
 * and a salted secret.
 *
 * This is done through a base64-encoded snippet made of serialization of
 * following array:
 * - the string 'login'
 * - user id
 * - salt (i.e., some random string)
 * - salted secret (see below)
 *
 * The salted secret is the CRC32 computation of a string made out of
 * following components:
 * - salt
 * - the ':' character
 * - the secret handle attached to the target user profile
 *
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
 * - salted secret (see below)
 *
 * The salted secret ensures a minimum level of authentication, and makes it
 * difficult to forge a link to another page than the target one.
 *
 * The salted secret is the CRC32 computation of a string made out of
 * following components:
 * - nick name of the original poster (e.g., 'john')
 * - the ':' character
 * - the secret handle attached to the target page
 *
 *
 * [title]visitor authentication[/title]
 *
 * This script is aiming to support visitors contacted by e-mail.
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
 * - e-mail address (e.g., 'tom@foo.bar', or 'Tom &lt;tom@foo.bar&gt;')
 * - salted secret (see below)
 *
 * The salted secret ensures a minimum level of authentication, and protects
 * against e-mail impersonation, and ensures driving to one single target.
 *
 * The salted secret is the CRC32 computation of a string made out of
 * following components:
 * - e-mail address
 * - the ':' character
 * - the secret handle attached to the target page
 *
 * [title]script invocation[/title]
 *
 * This script may be called from anywhere either to login or to logout:
 * - users/login.php -- show the login form or process POSTed arguments
 * - users/login.php?url=... -- the same, with a URL to be used on success
 * - users/login.php/... -- authenticate surfer using providing credentials
 * - users/login.php/?credentials=... -- use provided credentials
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @tester GnapZ
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

// data has been serialized, then base64 encoded
if($credentials && ($credentials = base64_decode($credentials))) {

	// json is more efficient, but we may have to fall-back to php serialization
	if(!$credentials = Safe::json_decode($credentials))
		$credentials = unserialize($credentials);

}

// load localized strings
i18n::bind('users');

// load the skin
load_skin('users');

// use provided credentials
if($credentials) {

	// the page title
	$context['page_title'] = i18n::s('One-click authentication');

	// page author is coming back
	if(isset($credentials[0]) && ($credentials[0] == 'edit')) {

		// get an anchor
		if(!isset($credentials[1]) || (!$anchor = Anchors::get($credentials[1])))
			Skin::error(i18n::s('No anchor has been found.'));

		// retrieve poster attributes
		elseif((!$poster = $anchor->get_poster()) || !isset($poster['nick_name']))
			Skin::error(i18n::s('Request is invalid.'));

		// we need some salted secret
		elseif(!isset($credentials[2]) || strcmp($credentials[2], sprintf('%u', crc32($poster['nick_name'].':'.$anchor->get_handle()))))
			Skin::error(i18n::s('Request is invalid.'));

		// authenticate and redirect
		else {

			// save surfer profile in session context
			Surfer::set($poster);

			// add this anchor to allowed handles
			Surfer::add_handle($anchor->get_handle());

			// redirect to target page
			Safe::redirect($context['url_to_home'].$context['url_to_root'].$anchor->get_url());

		}

	// surfer is authenticating through e-mail
	} elseif(isset($credentials[0]) && ($credentials[0] == 'login')) {

		// get user profile
		if(!isset($credentials[1]) || (!$user = Users::get($credentials[1])))
			Skin::error('No item has the provided id.');

		// random string
		elseif(!isset($credentials[2]) || !$credentials[2])
			Skin::error(i18n::s('Request is invalid.'));

		// check salted secret
		elseif(!isset($credentials[3]) || strcmp($credentials[3], sprintf('%u', crc32($credentials[2].':'.$user['handle']))))
			Skin::error(i18n::s('Request is invalid.'));

		// authenticate and offer to change the password
		else {

			// save surfer profile in session context
			Surfer::set($user);

			// the user icon, if any
			if(isset($user['avatar_url']) && $user['avatar_url'])
				$context['page_image'] = $user['avatar_url'];

			// splash message
			$context['text'] .= '<p>'.i18n::s('Welcome!').'</p>'
				.'<p>'.i18n::s('You have been successfully authenticated.').'</p>';

			// what to do next
			$context['text'] .= '<p>'.i18n::s('What do you want to do now?').'</p>';

			// the menu of possible actions
			$menu = array();

			// change password
			$menu = array_merge($menu, array('users/password.php' => i18n::s('Change password')));

			// command to go to the user page
			$menu = array_merge($menu, array(Users::get_url(Surfer::get_id(), 'view', Surfer::get_name()) => i18n::s('Go to my user profile')));

			// display the login menu
			$context['text'] .= Skin::build_list($menu, 'menu_bar');

		}

	// surfer is an invited visitor --'invite' is legacy, and should be removed in 2008
	} elseif(isset($credentials[0]) && (($credentials[0] == 'visit') || ($credentials[0] == 'invite'))) {

		// get an anchor
		if(!isset($credentials[1]) || (!$anchor = Anchors::get($credentials[1])))
			Skin::error(i18n::s('No anchor has been found.'));

		// visitor email address
		elseif(!isset($credentials[2]) || !$credentials[2])
			Skin::error(i18n::s('Request is invalid.'));

		// we need some salted secret
		elseif(!isset($credentials[3]) || strcmp($credentials[3], sprintf('%u', crc32($credentials[2].':'.$anchor->get_handle()))))
			Skin::error(i18n::s('Request is invalid.'));

		// authenticate and redirect
		else {

			// extract the actual e-mail address -- Foo Bar <foo@bar.com> => foo@bar.com
			$tokens = explode(' ', $credentials[2]);
			$address = trim(str_replace(array('<', '>'), '', $tokens[count($tokens)-1]));

			// ensure authenticated surfer is also an editor of the visited page
			if($id = Surfer::get_id())
				Members::assign('user:'.$id, $anchor->get_reference());

			// start a new session
			else {

				// look for a surfer with this address
				if(!$user = Users::get($address)) {
					$user = array();
					$user['nick_name'] = $address;
					$user['email'] = $address;
				}

				// save surfer profile in session context
				Surfer::set($user);

				// ensure this user profile is also an editor of the visited page
				if(isset($user['id']))
					Members::assign('user:'.$user['id'], $anchor->get_reference());

			}

			// add this anchor to allowed handles during this session
			Surfer::add_handle($anchor->get_handle());

			// redirect to target page
			Safe::redirect($context['url_to_home'].$context['url_to_root'].$anchor->get_url());

		}

	} else
		Skin::error(i18n::s('Request is invalid.'));

// some data have been posted
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// protect from hackers
	$name = preg_replace('/[\'"\{\}\[\]\(\)]/', ' ', strip_tags($_REQUEST['login_name']));

	// the surfer has been authenticated
	if($user = Users::login($name, $_REQUEST['login_password'])) {

		// save surfer profile in session context
		Surfer::set($user);

		// set a semi-permanent cookie for user identification
		if(isset($user['handle']) && $user['handle'] && isset($context['users_with_permanent_authentication']) && ($context['users_with_permanent_authentication'] == 'Y')) {

			// time of authentication
			$now = (string)time();

			// token is made of: user id, time of login, gmt offset, salt --salt combines date of login with secret handle
			$token = $user['id'].'|'.$now.'|'.$gmt_offset.'|'.md5($now.'|'.$user['handle']);

			// path to this instance
			Safe::setcookie('screening', $token, time()+60*60*24*500, $context['url_to_root']);

			// also set cookies used in leading index.php
			if(isset($_ENV['YACS_HOME']))
				Safe::setcookie('screening', $token, time()+60*60*24*500, $_ENV['YACS_HOME'].'/');
			if($context['url_to_root'] == '/yacs/')
				Safe::setcookie('screening', $token, time()+60*60*24*500, '/');

		}

		// redirect to previous page
		if(isset($context['users_without_login_welcome']) && ($context['users_without_login_welcome'] == 'Y')) {

			// go to the forwarded reference or to the front page
			if(isset($_REQUEST['login_forward']))
				Safe::redirect($_REQUEST['login_forward']);
			else
				Safe::redirect($context['url_to_home'].$context['url_to_root'].'index.php');

		}

		// the page title
		$context['page_title'] = i18n::s('Welcome!');

		// the user icon, if any
		if(isset($user['avatar_url']) && $user['avatar_url'])
			$context['page_image'] = $user['avatar_url'];

		// lay fields in a table
		$context['text'] .= Skin::table_prefix('form');
		$lines = 1;

		// a link to the user profile
		$cells = array();
		$cells[] = i18n::s('Your personal record');
		$url = Users::get_url(Surfer::get_id(), 'view', Surfer::get_name());
		$cells[] = 'left='.Skin::build_link($url, Surfer::get_name(), 'user');
		$context['text'] .= Skin::table_row($cells, $lines++);

		// the name
		if(isset($user['full_name']) && $user['full_name']) {
			$cells = array();
			$cells[] = i18n::s('Your name');
			$cells[] = 'left='.$user['full_name'];
			$context['text'] .= Skin::table_row($cells, $lines++);
		}

		// the email field
		if(Surfer::get_email_address()) {
			$cells = array();
			$cells[] = i18n::s('Your e-mail address');
			$cells[] = 'left='.Surfer::get_email_address();
			$context['text'] .= Skin::table_row($cells, $lines++);
		}

		// gmt offset
		if($gmt_offset = Surfer::get_gmt_offset()) {
			$cells = array();
			$cells[] = i18n::s('Time Zone');
			if($gmt_offset == -1)
				$cells[] = 'left='.i18n::s('GMT-1 hour');
			elseif($gmt_offset == 1)
				$cells[] = 'left='.i18n::s('GMT+1 hour');
			elseif($gmt_offset > 1)
				$cells[] = 'left='.sprintf(i18n::s('GMT+%d hours'), $gmt_offset);
			else
				$cells[] = 'left='.sprintf(i18n::s('GMT%d hours'), $gmt_offset);
			$context['text'] .= Skin::table_row($cells, $lines++);
		}

		// IP address
		if($_SERVER['REMOTE_ADDR']) {
			$cells = array();
			$cells[] = i18n::s('Your network address');
			$cells[] = 'left='.$_SERVER['REMOTE_ADDR'];
			$context['text'] .= Skin::table_row($cells, $lines++);
		}

		// last login
		if(isset($user['login_date']) && $user['login_date']) {
			$cells = array();
			$cells[] = i18n::s('Last login');
			$cells[] = 'left='.Skin::build_date($user['login_date']);
			$context['text'] .= Skin::table_row($cells, $lines++);
		}

		// end of the table
		$context['text'] .= Skin::table_suffix();

		// the capability field - associate, member, or subscriber
		if(Surfer::is_associate())
			$context['text'] .= '<p>'.i18n::s('As an associate of this community, you may contribute freely to any part of this server.').'</p>';
		elseif(Surfer::is_member())
			$context['text'] .= '<p>'.i18n::s('As a member of this community, you may access freely most pages of this server.').'</p>';
		else
			$context['text'] .= '<p>'.i18n::s('As a subscriber of this community, you may freely access most pages of this server.').'</p>';

		// what to do next
		$context['text'] .= '<p>'.i18n::s('Where do you want to go now?').'</p>';

		// the menu of possible actions
		$menu = array();

		// explicit forward to the calling page
		if(isset($_REQUEST['login_forward']))
			$menu = array_merge($menu, array($_REQUEST['login_forward'] => i18n::s('Move forward')));

		// go back to caller
		elseif(isset($_SERVER['HTTP_REFERER']) && !preg_match('/users\/login\.php/', $_SERVER['HTTP_REFERER']))
			$menu = array_merge($menu, array($_SERVER['HTTP_REFERER'] => i18n::s('Back to previous page')));

		// go to the front page
		else
			$menu = array_merge($menu, array($context['url_to_root'] => i18n::s('Server front page')));

		// shortcut to on-going threads
		if(Surfer::is_associate())
			$menu = array_merge($menu, array('comments/' => i18n::s('On-going threads')));

		// shortcut to the review queue
		if(Surfer::is_associate())
			$menu = array_merge($menu, array('articles/review.php' => i18n::s('Articles to review')));

		// shortcut to the control panel for associates
		if(Surfer::is_associate())
			$menu = array_merge($menu, array('control/' => i18n::s('Control Panel')));


		// command to edit a new article, except if it is the forward
		if(Surfer::is_member() && isset($_REQUEST['login_forward']) && !preg_match('/^articles\/edit.php/', $_REQUEST['login_forward']))
			$menu = array_merge($menu, array('articles/edit.php' => i18n::s('Write a page')));

		// command to go to the user page
		$menu = array_merge($menu, array(Users::get_url(Surfer::get_id(), 'view', Surfer::get_name()) => i18n::s('Go to my user profile')));

		// display the login menu
		$context['text'] .= Skin::build_list($menu, 'menu_bar');

		// on-going actions, if any
		$box['bar'] = array();
		$box['text'] = '';
		include_once '../actions/actions.php';
		$items = Actions::list_by_date_for_anchor('user:'.Surfer::get_id(), 0, ACTIONS_PER_PAGE);
		if(is_array($items) && @count($items))
			$box['text'] .= Skin::build_list($items, 'decorated');
		if($box['text'])
			$context['text'] .= Skin::build_box(i18n::s('On-going actions'), $box['text'], 'section', 'actions');

		// contribution links, in an extra box
		if(Surfer::is_member()) {
			$links = array();
			$links = array_merge($links, array( 'articles/edit.php' => i18n::s('Write a page') ));
			$links = array_merge($links, array( 'images/edit.php' => i18n::s('Post a photo') ));
			$links = array_merge($links, array( 'files/edit.php' => i18n::s('Share a file') ));

			if(Surfer::is_associate()) {
				$links = array_merge($links, array( 'sections/edit.php' => i18n::s('New section') ));
				$links = array_merge($links, array( 'categories/edit.php' => i18n::s('New category') ));
				$links = array_merge($links, array( 'users/edit.php' => i18n::s('New user') ));
			}

			$context['extra'] .= Skin::build_box(i18n::s('Contribute'), Skin::build_list($links, 'compact'), 'navigation');
		}

		// navigation links, in an extra box
		$links = array();

		$links = array_merge($links, array( $context['url_to_root'] => i18n::s('Main page') ));
		$links = array_merge($links, array( 'users/view.php' => i18n::s('My profile') ));
		$links = array_merge($links, array( 'sections/' => i18n::s('Site map') ));
		$links = array_merge($links, array( 'categories/' => i18n::s('Categories tree') ));
		$links = array_merge($links, array( 'articles/' => i18n::s('Articles') ));
		$links = array_merge($links, array( 'files/' => i18n::s('Files') ));
		$links = array_merge($links, array( 'links/' => i18n::s('Links') ));
		$links = array_merge($links, array( 'search.php' => i18n::s('Search') ));

		$context['extra'] .= Skin::build_box(i18n::s('Navigate'), Skin::build_list($links, 'compact'), 'navigation');

	// impossible to authenticate the surfer
	} else {

		// reset the current session
		Surfer::reset();

		// the page title
		$context['page_title'] = i18n::s('Sorry!');

		// some explanation
		$context['text'] = i18n::s('We are very sorry, but we have not been able to authenticate you as a valid member of this community.');

		// limit brute attacks
		Safe::sleep(5);
	}

// provide the empty form by default
} else {

	// the page title
	$context['page_title'] = i18n::s('Please register or log in');

	// the menu for this page
	if(isset($_SERVER['HTTP_REFERER']))
		$context['page_menu'] = array( $_SERVER['HTTP_REFERER'] => i18n::s('Back') );

	// the introduction, for protected pages only
	if(isset($_REQUEST['url']))
		$context['text'] .= Skin::build_block(i18n::s('The page you requested is available only to registered members.'), 'introduction');

	// offer a self-registration, if allowed
	if(!isset($context['users_without_registration']) || ($context['users_without_registration'] != 'Y')) {

		if(isset($_REQUEST['url'])) {
			$link = 'users/edit.php?forward='.htmlentities(urlencode($_REQUEST['url']));
		} elseif(isset($_SERVER['HTTP_REFERER'])) {
			$link = 'users/edit.php?forward='.htmlentities(urlencode($_SERVER['HTTP_REFERER']));
		} else
			$link = 'users/edit.php';

		$context['text'] .= '<p>'.sprintf(i18n::s('Registration is FREE and offers great benefits. %s if you are not yet a member of %s.'), Skin::build_link($link, i18n::s('Click here to register'), 'shortcut'), $context['site_name'])."</p>\n";
	}

	// the form
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>';

	// the title
	$context['text'] .= Skin::build_block(i18n::s('Already registered? Please authenticate:'), 'subtitle');

	// lay fields in a table
	$context['text'] .= Skin::table_prefix('form');
	$lines = 1;

	// the id or email field
	$cells = array();
	$cells[] = i18n::s('Your nick name, or e-mail address');
	$cells[] = '<input type="text" name="login_name" id="login_name" size="45" maxlength="255"'.EOT."\n";
	$context['text'] .= Skin::table_row($cells, $lines++);

	// the password
	$cells = array();
	$cells[] = i18n::s('Password');
	$cells[] = '<input type="password" name="login_password" size="45" maxlength="255"'.EOT."\n";
	$context['text'] .= Skin::table_row($cells, $lines++);

	// end of the table
	$context['text'] .= Skin::table_suffix();

	// bottom commands
	$menu = array();

	// the submit button
	$menu[] = Skin::build_submit_button(i18n::s('Login'), NULL, NULL, 'login_button');

	// lost password?
	$menu[] = Skin::build_link('users/password.php', i18n::s('Lost password'), 'span');

	// insert the menu in the page
	$context['text'] .= Skin::finalize_list($menu, 'menu_bar');

	// save the forwarding url as well
	if(isset($_REQUEST['url']))
		$context['text'] .= '<p><input type="hidden" name="login_forward" value="'.encode_field($context['url_to_root'].$_REQUEST['url']).'" /></p>';
	elseif(isset($_SERVER['HTTP_REFERER']))
		$context['text'] .= '<p><input type="hidden" name="login_forward" value="'.encode_field($_SERVER['HTTP_REFERER']).'" /></p>';

	// end of the form
	$context['text'] .= '</div></form>';

	// the script used for data checking on the browser
	$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
		.'// check that main fields are not empty'."\n"
		.'func'.'tion validateDocumentPost(container) {'."\n"
		."\n"
		.'	// email is mandatory'."\n"
		.'	if(!container.login_name.value) {'."\n"
		.'		alert("'.i18n::s('You must provide a nick name or an email address.').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		return false;'."\n"
		.'}'."\n"
		."\n"
		.'	// successful check'."\n"
		.'	return true;'."\n"
		.'}'."\n"
		."\n"
		.'// set the focus on first form field'."\n"
		.'document.getElementById("login_name").focus();'."\n"
		.'// ]]></script>'."\n";

	// a place holder for cookies activation
	$context['text'] .= '<p id="ask_for_cookies" style="display: none; color: red; text-decoration: blink;"></p>';

	// the script used to check that cookies are activated
	$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
		.'document.cookie = \'CookiesEnabled=1\';'."\n"
		.'if((document.cookie == \'\') && document.getElementById) {'."\n"
		."\t".'document.getElementById(\'ask_for_cookies\').innerHTML = \''.i18n::s('You must enable cookies to authenticate to this server. Change settings of your browser accordingly, then revisit this login page afterwards.').'\';'."\n"
		."\t".'document.getElementById(\'ask_for_cookies\').style.display = \'block\';'."\n"
		."\t".'document.getElementById(\'login_button\').disabled = true;'."\n"
		.'}'."\n"
		.'// ]]></script>'."\n";

	// the help panel
	$help = '<p>'.i18n::s('Your browser must accept cookies in order to successfully register and log in.').'</p>'
		.'<p>'.sprintf(i18n::s('If you already are a registered member, but do not remember your username and/or password, %s.'), Skin::build_link('users/password.php', i18n::s('click here'))).'</p>';
	$context['extra'] .= Skin::build_box(i18n::s('Help'), $help, 'navigation', 'help');

}

// render the skin
render_skin();

?>