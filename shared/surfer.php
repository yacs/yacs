<?php
/**
 * what we know about the current surfer
 *
 * This module uses cookies to control user login, logout, and access
 * to protected resources.
 *
 * When users enter this server and sign on, their related profile is saved
 * into session data. This module is able to change and retrieve these data.
 *
 * The security model is quite simple. The community defined by this system is made of:
 * - associates, that are allowed to do anything on the server
 * - members, that may contribute to the content of the server and may access articles with restricted access.
 * - subscribers, that may read and contribute to the public pages of the server
 * - all other web surfers that may not be identified by the system
 *
 * YACS saves the address of the surfer host, as reported by the network
 * sub-system, in session data.
 *
 * During the login process the root path (e.g., [code]/yacs/[/code]) is saved in session data.
 * This session attribute is checked afterwards to evaluate actual surfer capability.
 * Therefore, even if several YACS instances are installed at the same host, cross-authentication is blocked.
 * For example, if you have two separate installations at [code]/yacs/[/code] and at [code]/yacs_demo/[/code],
 * users authenticating as associates at the demo site would not be considered as being associates at the main instance.
 *
 * On load, and if the surfer is not an associate, this script will strip any
 * HTML tag from the [code]$_REQUEST[/code] array.
 * Note that special HTML characters encoded as Unicode numerical entities are
 * correctly unmasked, to prevent related code injections. (Thanks to Mordread on this).
 *
 * @author Bernard Paques
 * @author Vincent No&euml;l
 * @author GnapZ
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @tester Mordread Wallas
 * @tester Anatoly
 * @tester LeToto
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Surfer {

	/**
	 * extend surfer capability
	 *
	 * This function is used to record additional anchors in session space.
	 *
	 * @param string additional anchor editable during this session
	 */
	public static function add_handle($handle) {

		// sanity check
		if(!$handle)
			return;

		// an array of manageable objects
		if(!isset($_SESSION['surfer_handles']))
			$_SESSION['surfer_handles'] = array();

		// append the new handle if not already present
		if(!in_array($handle, $_SESSION['surfer_handles']))
			$_SESSION['surfer_handles'][] = $handle;

	}

	/**
	 * list articles assigned to this surfer
	 *
	 * If a member is acting as a managing editor for some articles,
	 * this function returns ids of these articles.
	 *
	 * For subscribers, this function will return the list of accessible articles.
	 *
	 * @param int id of the surfer to consider
	 * @param int maximum number of articles to return
	 * @return array ids of managed articles
	 */
	public static function assigned_articles($id=NULL, $maximum=200) {
		global $context;

		// default to current surfer
		if(!$id)
			$id = Surfer::get_id();

		// sanity check
		if(!$id)
			return array();

		// query the database only once
		static $cache;
		if(!isset($cache))
			$cache = array();
		if(isset($cache[ $id ]))
			return $cache[ $id ];
		$cache[ $id ] = array();

		// the list of articles
		$query = "SELECT articles.id FROM ".SQL::table_name('members')." AS members"
			.", ".SQL::table_name('articles')." AS articles"
			." WHERE (members.anchor LIKE 'user:".SQL::escape($id)."')"
			."	AND (members.member_type = 'article')"
			."	AND (members.member_id = articles.id)"
			." ORDER BY members.edit_date DESC LIMIT 0, ".$maximum;

		// submit a silent query because at setup tables don't exist
		if(($result = SQL::query($query, TRUE))) {

			// build the list
			while($row = SQL::fetch($result))
				$cache[ $id ][] = $row['id'];

		}

		// done
		return $cache[ $id ];

	}

	/**
	 * list sections assigned to this surfer
	 *
	 * If a member is acting as a managing editor for some sections,
	 * this function returns ids of these sections.
	 *
	 * For subscribers, this function will return the list of accessible sections.
	 *
	 * @param int id of the surfer to consider
	 * @param int maximum number of sections to return
	 * @return array ids of managed sections
	 */
	public static function assigned_sections($id=NULL, $maximum=200) {
		global $context;

		// don't look at sub-levels
		$with_subs = FALSE;

		// default to current surfer
		if(!$id) {
			$id = Surfer::get_id();
			$with_subs = TRUE;
		}

		// sanity check
		if(!$id)
			return array();

		// query the database only once
		static $cache;
		if(!isset($cache))
			$cache = array();
		if(isset($cache[ $id ]))
			return $cache[ $id ];
		$cache[ $id ] = array();

		// backend may not available --error.php
		if(is_callable(array('SQL', 'query'))) {

			// only consider live sections
			$where = "((sections.expiry_date is NULL)"
				."	OR (sections.expiry_date <= '".NULL_DATE."') OR (sections.expiry_date > '".$context['now']."'))";

			// the list of sections
			$query = "SELECT sections.id FROM ".SQL::table_name('members')." AS members"
				.", ".SQL::table_name('sections')." AS sections"
				." WHERE (members.anchor LIKE 'user:".SQL::escape($id)."')"
				."	AND (members.member_type = 'section')"
				."	AND (members.member_id = sections.id)"
				."	AND (".$where.")"
				." ORDER BY members.edit_date DESC LIMIT 0, ".$maximum;

			// submit a silent query because at setup tables don't exist
			if(($result = SQL::query($query, TRUE))) {

				// build the list
				while($row = SQL::fetch($result))
					$cache[ $id ][] = $row['id'];

			}

			// re-enforce ownership inheritance
			if($with_subs) {

				// level 2
				$query = "SELECT sections.id FROM ".SQL::table_name('sections')." AS sections"
					." WHERE sections.anchor IN ('section:".join("', 'section:", $cache[ $id ])."')"
					."	AND (".$where.")"
					." ORDER BY sections.edit_date DESC LIMIT 0, ".$maximum;

				// submit a silent query because at setup tables don't exist
				$level = array();
				if(($result = SQL::query($query, TRUE))) {

					// build the list
					while($row = SQL::fetch($result)) {
						$cache[ $id ][] = $row['id'];
						$level[] = $row['id'];
					}

				}

				// level 3
				$query = "SELECT sections.id FROM ".SQL::table_name('sections')." AS sections"
					." WHERE sections.anchor IN ('section:".join("', 'section:", $level)."')"
					."	AND (".$where.")"
					." ORDER BY sections.edit_date DESC LIMIT 0, ".$maximum;

				// submit a silent query because at setup tables don't exist
				if($level && ($result = SQL::query($query, TRUE))) {

					// build the list
					$level = array();
					while($row = SQL::fetch($result)) {
						$cache[ $id ][] = $row['id'];
						$level[] = $row['id'];
					}

				}

				// level 4
				$query = "SELECT sections.id FROM ".SQL::table_name('sections')." AS sections"
					." WHERE sections.anchor IN ('section:".join("', 'section:", $level)."')"
					."	AND (".$where.")"
					." ORDER BY sections.edit_date DESC LIMIT 0, ".$maximum;

				// submit a silent query because at setup tables don't exist
				if($level && ($result = SQL::query($query, TRUE))) {

					// build the list
					$level = array();
					while($row = SQL::fetch($result)) {
						$cache[ $id ][] = $row['id'];
						$level[] = $row['id'];
					}

				}

				// level 5
				$query = "SELECT sections.id FROM ".SQL::table_name('sections')." AS sections"
					." WHERE sections.anchor IN ('section:".join("', 'section:", $level)."')"
					."	AND (".$where.")"
					." ORDER BY sections.edit_date DESC LIMIT 0, ".$maximum;

				// submit a silent query because at setup tables don't exist
				if($level && ($result = SQL::query($query, TRUE))) {

					// build the list
					$level = array();
					while($row = SQL::fetch($result)) {
						$cache[ $id ][] = $row['id'];
						$level[] = $row['id'];
					}

				}

				// level 6
				$query = "SELECT sections.id FROM ".SQL::table_name('sections')." AS sections"
					." WHERE sections.anchor IN ('section:".join("', 'section:", $level)."')"
					."	AND (".$where.")"
					." ORDER BY sections.edit_date DESC LIMIT 0, ".$maximum;

				// submit a silent query because at setup tables don't exist
				if($level && ($result = SQL::query($query, TRUE))) {

					// build the list
					$level = array();
					while($row = SQL::fetch($result)) {
						$cache[ $id ][] = $row['id'];
						$level[] = $row['id'];
					}

				}

			}

		}

		// done
		return $cache[ $id ];

	}

	/**
	 * build the navigation menu for this surfer
	 *
	 * This function has to be called from the template, once the skin has been loaded.
	 *
	 * @param string the type of each link
	 * @return string to be displayed as user menu
	 *
	 * @see skins/skin_skeleton.php
	 */
	public static function &build_user_menu($type = 'submenu') {
		global $context;

		// surfer is a valid user
		if(Surfer::is_logged()) {

			// all available commands
			$menu = array();

			if($link = Surfer::get_permalink())
				$menu[$link] = array('', i18n::s('My profile'), '', $type, '', i18n::s('View all data this site knows about you'));

			if(Surfer::is_associate())
				$menu['articles/review.php'] = array('', i18n::s('Review queue'), '', $type, '', i18n::s('Check requests, publish submitted articles, review old pages'));

			if(Surfer::is_associate())
				$menu['control/'] = array('', i18n::s('Control Panel'), '', $type, '', i18n::s('System commands, configuration panels, content overview'));

			$menu['users/logout.php'] = array('', i18n::s('Logout'), '', $type, '', i18n::s('You will be considered as an anonymous surfer'));

			$content = Skin::build_list($menu, 'compact');

		// no user menu during installation
		} elseif(!file_exists($context['path_to_root'].'parameters/switch.on') && !file_exists($context['path_to_root'].'parameters/switch.off'))
			;

		// surfer has not been authenticated, and login box is allowed
		elseif(!isset($context['users_without_login_box']) || ($context['users_without_login_box'] != 'Y')) {

			$content = '<form method="post" action="'.$context['url_to_root'].'users/login.php" id="login_form"><p>'."\n";

			// use cookie, if any -- don't populate the name to enable caching
			$name = '';

			// the id or email field
			$content .= i18n::s('User').BR.'<input type="text" name="login_name" size="10" maxlength="255" value="'.encode_field($name).'" />'.BR;

			// the password
			$content .= i18n::s('Password').BR.'<input type="password" name="login_password" size="10" maxlength="255" />'.BR;
			
			// Remember me ?
			$content .= '<span class="details"><input type="checkbox" name="remember" value="Y" '.EOT.'&nbsp;'.i18n::s('Stay connected').'</span>'.BR;

			// the button
			$content .= Skin::build_submit_button(i18n::s('Login'));

			// end of the form
			$content .= '</p></form>';

			// additional commands
			$menu = array();

			// self-registration is allowed
			if(!isset($context['users_without_registration']) || ($context['users_without_registration'] != 'Y'))
				$menu['users/edit.php'] = array('', i18n::s('Register'), '', $type, '', i18n::s('Share your profile in this community'));

			$menu['users/password.php'] = array('', i18n::s('Lost password'), '', $type, '', i18n::s('Prove who you are'));

			$content .= Skin::build_list($menu, 'compact');

		}

		// return by reference
		return $content;
	}

	/**
	 * set default attributes for this surfer
	 *
	 * This function is called before writing records in the database,
	 * to ensure attributes 'edit_name', 'edit_id', 'edit_address' and 'edit_date' have been properly set.
	 *
	 * @param array attributes to check
	 *
	 * @see articles/articles.php
	 * @see categories/categories.php
	 * @see comments/comments.php
	 * @see files/files.php
	 * @see links/links.php
	 * @see locations/locations.php
	 * @see sections/sections.php
	 * @see servers/servers.php
	 * @see tables/tables.php
	 * @see users/users.php
	 */
	public static function check_default_editor(&$fields) {

		// surfer is the editor
		if(!isset($fields['edit_name']) || !trim($fields['edit_name'])) {

			// default value for editor name
			$fields['edit_name'] = Surfer::get_name();

			// default value for editor id
			if(!isset($fields['edit_id']) || !$fields['edit_id'])
				$fields['edit_id'] = Surfer::get_id();

			// default value for editor address
			if(!isset($fields['edit_address']) || !$fields['edit_address'])
				$fields['edit_address'] = Surfer::get_email_address();

		// if a name has been set, do not impersonate the surfer at other fields
		} else {

			// default value for editor id
			if(!isset($fields['edit_id']))
				$fields['edit_id'] = 0;

			// default value for editor address
			if(!isset($fields['edit_address']))
				$fields['edit_address'] = '';

		}

		// default value for edition date (GMT)
		if(!isset($fields['edit_date']) || ($fields['edit_date'] <= NULL_DATE))
			$fields['edit_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');

	}

	/**
	 * extend capability for this request
	 *
	 * This function is used to flag editors, and any surfer which benefits from
	 * extended rights for the duration of the current script.
	 *
	 * @param string new capability of this surfer, '?', 'S', 'M' or 'A'
	 */
	public static function empower($capability='A') {
		global $context;

		if(($capability == '?') || ($capability == 'S') || ($capability == 'M') || ($capability == 'A'))
			$context['empowered'] = $capability;

	}

	/**
	 * format surfer recipient address
	 *
	 * @return string "Foo Bar" <foo@acme.com>, or NULL
	 */
	public static function from() {

		// use surfer full name if possible
		$text = '"'.str_replace('"', '', Surfer::get_name()).'" ';

		// add the email address
		if($address = Surfer::get_email_address())
			$text .= '<'.$address.'>';

		// nothing found
		$text = trim($text);
		if(!$text)
			return NULL;

		// job done
		return $text;
	}

	/**
	 * adjust a date to surfer time zone
	 *
	 * Use this function to convert dates from UTC time zone before they are
	 * sent in forms processed by surfer.
	 *
	 * To beautify a date sent to the surfer use Skin::build_date() instead.
	 *
	 * You should apply this function only to data coming from the database.
	 *
	 * @param string a stamp written on the 'YYYY-MM-DD HH:MM:SS' model
	 * @return string a rewrite of the stamp in the surfer time zone
	 */
	public static function from_GMT($stamp) {
		global $context;

		// sanity check
		if(!isset($stamp) || ($stamp <= NULL_DATE))
			return $stamp;

		// time in UTC time zone
		$stamp = mktime(intval(substr($stamp, 11, 2)), intval(substr($stamp, 14, 2)), intval(substr($stamp, 17, 2)), intval(substr($stamp, 5, 2)), intval(substr($stamp, 8, 2)), intval(substr($stamp, 0, 4)));

		// shift to surfer time zone
		return strftime('%Y-%m-%d %H:%M:%S', $stamp + (Surfer::get_gmt_offset() * 3600));
	}

	/**
	 * get the web address of personal avatar, if any
	 *
	 * @return string web link to the avatar, or NULL
	 */
	public static function get_avatar_url() {
		global $context;

		// read surfer record in database
		if(($id = Surfer::get_id()) && is_callable(array('Users', 'get')) && ($user = Users::get($id)) && ($url = trim($user['avatar_url'])))
			return $url;

		// tough luck
		return NULL;
	}

	/**
	 * get the capability of the current surfer
	 *
	 * If the surfer has authenticated, his general capability is stored in
	 * session context.
	 *
	 * Surfer can be empowered on some transactions, and this is saved in
	 * transaction context.
	 *
	 * This function returns highest setting of session and transaction
	 * capability.
	 *
	 * @return char either 'A', 'M', 'S', 'C' or '?'
	 */
	public static function get_capability() {
		global $context;

		// flag crawlers to the cache engine
		if(Surfer::is_crawler())
			return 'C';

		// enforce session scope
		if(isset($_SESSION['surfer_capability']) && isset($context['url_to_root']) && (!isset($_SESSION['server_id']) || ($_SESSION['server_id'] == $context['url_to_root']))) {

			// session data may be overriden by transaction data
			if(($context['empowered'] == 'A') || ($_SESSION['surfer_capability'] == 'A'))
				return 'A';
			if(($context['empowered'] == 'M') || ($_SESSION['surfer_capability'] == 'M'))
				return 'M';
			if(($context['empowered'] == 'S') || ($_SESSION['surfer_capability'] == 'S'))
				return 'S';

		}

		// use transaction data
		return $context['empowered'];
	}

	/**
	 * get the preferred editor
	 *
	 * Provides XHTML snippet to invoke one of the available editors,
	 * based on user preference.
	 *
	 * Curently YACS supports following choices:
	 * - 'yacs' - the default, plain, code-based textarea
	 * - 'tinymce' - WYSIWYG editor
	 *
	 * @link http://tinymce.moxiecode.com/ TinyMCE
	 *
	 * @param string the name of the editing field
	 * @param string content to be put in the editor
	 * @param boolean TRUE to grow the control on focus
	 * @return string to be inserted in the XHTML flow
	 */
	public static function get_editor($name='description', $value='', $spring=FALSE) {
		global $context;

		// returned string
		$text = '';

		// enforce default configuration
		if(!isset($_SESSION['surfer_editor']) && isset($context['users_default_editor']))
			$_SESSION['surfer_editor'] = $context['users_default_editor'];

		// tinymce
		if(isset($_SESSION['surfer_editor']) && ($_SESSION['surfer_editor'] == 'tinymce') && is_readable($context['path_to_root'].'included/tiny_mce/tiny_mce.js') && !$spring) {

			// load the TinyMCE script -- see shared/global.php
			$context['javascript']['tinymce'] = TRUE;

			// a growing control
			if($spring)
				$text .= '<textarea name="description" id="description" rows="1" cols="50" style="width: 60%;" onfocus="Yacs.growPanel(this); if(navigator.appName==\'Microsoft Internet Explorer\'){initialize_editor()};tinyMCE.execCommand(\'mceAddControl\', true, \'description\');tinyMCE.get(\'description\').focus();"></textarea>';

			// the textarea that will be handled by TinyMCE
			else
				$text .= '<div><textarea name="'.$name.'" class="tinymce" rows="25" cols="50" accesskey="c">'.encode_field($value).'</textarea></div>';

			// signal an advanced editor
			$text .= '<input type="hidden" name="editor" value="tinymce" />';

		// a textarea that grow automatically
		} elseif($spring) {
			$text .= '<script type="text/javascript">var fuse'.$name.'=1;</script>'
				.'<textarea name="'.$name.'" id="'.$name.'"'
				.	' rows="1" cols="50" class="tip" >'
				.	'</textarea>'."\n"
				.JS_PREFIX
				.'$(function(){'
				.	'$("textarea#'.$name.'").autogrow();'
				.	'setTimeout(function() {'
				.		'$("textarea#'.$name.'")'
				.			'.tipsy({fallback: "'.i18n::s('Contribute to this page!').'", gravity: "s", fade: true})'
				.			'.tipsy("show");'
				.	'}, 5000);'
				.'});'."\n"
				.JS_SUFFIX;

		// default to plain editor -- BR after the Textarea is mandatory
		} else {

			// the main textarea
			if($name == 'description') {
				if(file_exists($context['path_to_root'].'codes/edit.js')) {
					$text .= '<script type="text/javascript" src="'.$context['url_to_root'].'codes/edit.js"></script>';
					if(file_exists($context['path_to_root'].'smileys/edit.js'))
						$text .= '<script type="text/javascript" src="'.$context['url_to_root'].'smileys/edit.js"></script>';
				}
				$text .= '<textarea name="description" id="edit_area" rows="25" cols="50" accesskey="c">'.encode_field($value).'</textarea>'.BR;

			// a secondary textarea
			} else
				$text .= '<textarea name="'.$name.'" rows="25" cols="50">'.encode_field($value).'</textarea>'.BR;

			// hint
			if(Surfer::is_associate())
				$hint = i18n::s('You are allowed to post any XHTML.');
			else
				$hint = sprintf(i18n::s('Following XHTML tags are allowed: %s'), trim(str_replace('><', ', ', $context['users_allowed_tags']), '<>'));
			$text .= '<span class="tiny">'.$hint.'</span>';

		}

		// job done
		return $text;
	}

	/**
	 * get the e-mail address of the current surfer, if known
	 *
	 * @return string a valid address, or NULL
	 */
	public static function get_email_address() {
		global $context;

		// use session data
		if(isset($_SESSION['surfer_email_address']))
			return $_SESSION['surfer_email_address'];

		return NULL;
	}

	/**
	 * get offset to GMT
	 *
	 * @return the number of hours (-12 ... +12)
	 *
	 * @see skins/skin_skeleton.php
	 */
	public static function get_gmt_offset() {
		global $context;

		// use cookie sent by browser -- see shared/yacs.js
		if(isset($_COOKIE['TimeZone']))
			return $_COOKIE['TimeZone'];

		// assume GMT
		return 0;
	}

	/**
	 * get the id of the current surfer, if known
	 *
	 * @return a positive integer or 0
	 */
	public static function get_id() {
		global $context;

		// enforce session scope
		if(isset($context['url_to_root']) && (!isset($_SESSION['server_id']) || ($_SESSION['server_id'] == $context['url_to_root']))) {

			// use session data
			if(isset($_SESSION['surfer_id']) && $_SESSION['surfer_id'])
				return $_SESSION['surfer_id'];

		}
		return 0;
	}

	/**
	 * build a pretty link to the profile page of this surfer
	 *
	 * This function is a proxy for Users::get_link(), limited to current surfer.
	 *
	 * @return string some text describing this surfer, with a link to get more information
	 *
	 * @see users/users.php
	 */
	public static function get_link() {
		global $context;

		return Users::get_link(Surfer::get_name(), Surfer::get_email_address(), Surfer::get_id());
	}

	/**
	 * get the name of the current surfer, if known
	 *
	 * If the surfer has been authenticated, then its name is provided.
	 *
	 * In all other cases the address of the workstation is appended to the name.
	 * The label can be either retrieved from a cookie, or it's 'anonymous'.
	 *
	 * @param string default name
	 * @return string the name to qualify this surfer
	 */
	public static function get_name($default = '') {
		global $context;

		// use session data
		if(isset($_SESSION['surfer_name']) && trim($_SESSION['surfer_name']))
			return $_SESSION['surfer_name'];

		// use cookie
		if(isset($_COOKIE['surfer_name']) && trim($_COOKIE['surfer_name']))
			$name = $_COOKIE['surfer_name'];

		// we have some default string to use
		elseif($default)
			$name =  $default;

		// really anonymous!
		else
			$name = i18n::s('anonymous');

		// append network address
		if(isset($_SERVER['REMOTE_ADDR']) && trim($_SERVER['REMOTE_ADDR']))
			$name .= '@'.$_SERVER['REMOTE_ADDR'];

		// return this name
		return $name;
	}

	/**
	 * update navigation context
	 *
	 * @return string web link to the target user profile, or NULL
	 */
	public static function get_path_bar($anchor=NULL, $all_articles=TRUE) {

		// section is visible to this user
		if(is_object($anchor) && $anchor->is_viewable())
			return $anchor->get_path_bar();

		// go back to surfer's profile
		if(Surfer::get_id() && is_callable(array('Users', 'get_url')))
			return array( Users::get_url(Surfer::get_id(), 'view', Surfer::get_name()) => i18n::s('My pages') );

		// list all public pages
		if($all_articles)
			return array( 'articles/' => i18n::s('All pages') );

		// no context, sorry
		return NULL;
	}

	/**
	 * get profile address for this surfer, if known
	 *
	 * @return string web link to the target user profile, or NULL
	 */
	public static function get_permalink() {
		if(Surfer::get_id() && is_callable(array('Users', 'get_url')))
			return Users::get_url(Surfer::get_id(), 'view', Surfer::get_name());
		return NULL;
	}

	/**
	 * get the phone number of the current surfer, if known
	 *
	 * @return string a valid number, or NULL
	 */
	public static function get_phone_number() {
		global $context;

		// use session data
		if(isset($_SESSION['surfer_phone_number']))
			return $_SESSION['surfer_phone_number'];

		return NULL;
	}

	/**
	 * ask surfer to replicate random data
	 *
	 * This function complements a web form to stop anonymous robots.
	 *
	 * If the global parameter 'users_without_robot_check' is set to 'Y',
	 * or if the surfer has been authenticated,
	 * this function returns NULL.
	 *
	 * In every other cases, the function returns an array to be inserted
	 * in current form
	 *
	 * Example of usage:
	 * [php]
	 * if($field = Surfer::get_robot_stopper())
	 *	   $fields[] = $field;
	 * [/php]
	 *
	 * Data submitted through the form can be checked using function [code]Surfer::may_be_a_robot()[/code].
	 *
	 * @return array to be inserted in the form, or NULL
	 */
	public static function get_robot_stopper() {
		global $context;

		// we are sure the surfer is not a robot
		if(isset($_SESSION['surfer_is_not_a_robot']) && $_SESSION['surfer_is_not_a_robot'])
			return NULL;

		// we have been asked to not challenge anonymous surfers
		if(isset($context['users_without_robot_check']) && ($context['users_without_robot_check'] == 'Y'))
			return NULL;

		// this only applies to anonymous surfers
		if(Surfer::is_logged())
			return NULL;

		// build a random string --  1, l, o, O and 0 are confusing
		$pool = 'abcdefghijkmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ23456789';
		$_SESSION['salt'] = $pool[mt_rand(0, strlen($pool)-1)]
			.$pool[mt_rand(0, strlen($pool)-1)]
			.$pool[mt_rand(0, strlen($pool)-1)]
			.$pool[mt_rand(0, strlen($pool)-1)]
			.$pool[mt_rand(0, strlen($pool)-1)];

		// add salt and pepper to the form
		$label = i18n::s('Robot stopper').' *';
		$input = i18n::s('Type exactly the following 5 chars:').' '.$_SESSION['salt'].' <input type="text" name="pepper" size="7" />';
		return array($label, $input);
	}

	/**
	 * user interface complexity
	 *
	 * @return TRUE or FALSE
	 */
	public static function has_all() {

		if(isset($_SESSION['surfer_interface']) && ($_SESSION['surfer_interface'] == 'C'))
			return TRUE;
		return FALSE;

	}

	/**
	 * detect Flash availibility in user agent
	 *
	 * This function combines detection on client and on server-side
	 *
	 * @return TRUE or FALSE
	 *
	 * @see skins/skin_skeleton.php
	 */
	public static function has_flash() {
		global $context;

		// use cookie sent by browser -- see shared/yacs.js
		if(isset($_COOKIE['FlashIsAvailable']) && ($_COOKIE['FlashIsAvailable'] == 'yes'))
			return TRUE;

		// assume no Flash
		return FALSE;
	}

	/**
	 * check the id of page surfer
	 *
	 * To control that a surfer is the actual creator of one article, following code may be used:
	 * [php]
	 * // load the article from the database, including the editors list
	 * $item = Articles::get($id);
	 *
	 * // check that the current surfer is a valid editor
	 * if(Surfer::is($item['create_id']) {
	 *	 ...
	 * }
	 * [/php]
	 *
	 * @param string the id of the original poster
	 * @return true or false
	 */
	public static function is($id) {

		// sanity check
		if(!$id)
			return FALSE;
		if(!Surfer::get_id())
			return FALSE;

		// look for strict equivalence
		if(Surfer::get_id() == $id)
			return TRUE;

		return FALSE;
	}

	/**
	 * is the current user an associate?
	 *
	 * The additional parameter allows for user empowerment on a per-transaction basis.
	 *
	 * @param string specific capability, '?', 'M' or 'A'
	 * @return true or false
	 */
	public static function is_associate($capability='?') {
		global $context;

		// surfer has been empowered for this transaction
		if($capability == 'A')
			return TRUE;

		// sanity check
		if(!Surfer::is_logged())
			return FALSE;

		// enforce session scope
		if(isset($context['url_to_root']) && (!isset($_SESSION['server_id']) || ($_SESSION['server_id'] == $context['url_to_root']))) {

			// use session data
			if(isset($_SESSION['surfer_capability']) && ($_SESSION['surfer_capability'] == 'A'))
				return TRUE;

		}
		return FALSE;
	}

	/**
	 * is the surfer a software robot?
	 *
	 * @return TRUE or FALSE
	 */
	public static function is_crawler() {

		// quite often software robots do not declare themselves
		if(!isset($_SERVER['HTTP_USER_AGENT']))
			return TRUE;

		// well-known robots
		$robots = array(
			'almaden',				// ibm almaden web crawler
			'answerbus',			// http://www.answerbus.com/, web questions
			'ask jeeves',			// ask jeeves
			'baiduspider',			// baiduspider asian search spider
			'blo\.gs',
			'blog', 				// generic ping
			'boitho.com-dc',		// norwegian search engine
			'bot',					// generic bot
			'crawler',				// generic crawler, including gsa-crawler
			'fast-webcrawler',		// all the web
			'frontier',
			'gigabot',				// gigabot
			'googlebot',			// google
			'ia_archiver',			// ia_archiver
			'inktomi',				// inktomi bot
			'mediapartners-google', // google adsense
			'msnbot',				// msn search
			'naverbot',
			'objectssearch',		// open source search engine
			'openbot',				// openbot, from taiwan
			'scooter',				// altavista
			'psbot',				// psbot image crawler
			'slurp',				// inktomi bot
			'sohu-search',			// chinese media company, search component
			'spider',
			'surveybot',
			'teoma',				// ask jeeves
			'webreaper',			// WebReaper
			'yahoo-verticalcrawler',// old yahoo bot
			'yahoo! slurp', 		// new yahoo bot
			'yahoo-mm', 			// another yahoo bot
			'zyborg'				// looksmart
			);

		// check the user-agent string
		if(preg_match('/('.str_replace('/', '\/', join('|', $robots)).')\b/i', $_SERVER['HTTP_USER_AGENT']))
			return TRUE;

		// maybe a human being, or a dog
		return FALSE;
	}

	/**
	 * useful if we have several versions of content to provide
	 *
	 * @link http://notnotmobile.appspot.com/ Mobile Device Detection
	 *
	 * @return boolean TRUE if a large browser is used, FALSE otherwise
	 */
	public static function is_desktop() {
		global $context;

		// use header provided by PHP
		if(isset($_SERVER['HTTP_USER_AGENT']))
			$user_agent = $_SERVER['HTTP_USER_AGENT'];

		// else rely on Apache integration
		elseif(!is_callable('apache_request_headers'))
			return TRUE;
		elseif(!$headers = apache_request_headers())
			return TRUE;
		elseif(!isset($headers['User-Agent']))
			return TRUE;
		else
			$user_agent = $headers['User-Agent'];

		// not a desktop, for sure
		if(preg_match('/(iphone|ipod|blackberry|android|palm|windows\s+ce)/i', $user_agent))
			return FALSE;

		// we don't know
		return TRUE;
	}

	/**
	 * is this a super user?
	 *
	 * @param string checked capability for this surfer, '?', 'M' or 'A'
	 * @return true or false
	 */
	public static function is_empowered($capability='A') {
		global $context;

		// surfer has been empowered for this transaction
		if($capability == $context['empowered'])
			return TRUE;

		// obvious
		if($context['empowered'] == 'A')
			return TRUE;
		if(Surfer::is_associate())
			return TRUE;
		if(($capability == 'M') && Surfer::is_member())
			return TRUE;

		return FALSE;
	}

	/**
	 * has the current surfer been authenticated?
	 *
	 * @return TRUE or FALSE
	 */
	public static function is_logged() {
		global $context;

		// enforce session scope
		if(isset($context['url_to_root']) && (!isset($_SESSION['server_id']) || ($_SESSION['server_id'] == $context['url_to_root']))) {

			// no role has been defined for this surfer
			if(!isset($_SESSION['surfer_capability']))
				return FALSE;

			// surfer is either an associate, a member, or a subscriber
			if(in_array($_SESSION['surfer_capability'], array('A', 'M', 'S')))
				return TRUE;

		}

		return FALSE;
	}

	/**
	 * is the current user a member?
	 *
	 * The additional parameter allows for user empowerment on a per-transaction basis.
	 *
	 * @param string specific capability, '?', 'M' or 'A'
	 * @return true or false
	 */
	public static function is_member($capability='?') {
		global $context;

		// surfer has been empowered for this transaction
		if(($capability == 'A') || ($capability == 'M'))
			return TRUE;

		// sanity check
		if(!Surfer::is_logged())
			return FALSE;

		// enforce session scope
		if(isset($context['url_to_root']) && (!isset($_SESSION['server_id']) || ($_SESSION['server_id'] == $context['url_to_root']))) {

			// use session data
			if(isset($_SESSION['surfer_capability']) && (($_SESSION['surfer_capability'] == 'M') || ($_SESSION['surfer_capability'] == 'A')))
				return TRUE;

		}
		return FALSE;
	}

	/**
	 * should we tease anonymous surfers?
	 *
	 * @return boolean TRUE if links to protected pages should be provided, FALSE otherwise
	 */
	public static function is_teased() {
		global $context;

		// never tease crawlers
		if(Surfer::is_crawler())
			return FALSE;

		// use global parameter
		if(isset($context['users_without_teasers']) && ($context['users_without_teasers'] == 'Y'))
			return FALSE;

		// suggest registrations
		return TRUE;
	}

	/**
	 * is this a trusted host?
	 *
	 * Network addresses of trusted hosts are defined in the configuration panel for users.
	 * Private content should be exposed to trusted hosts.
	 *
	 * @see users/configure.php
	 *
	 * @return boolean TRUE if request is coming from a trusted host, FALSE otherwise
	 */
	public static function is_trusted() {
		global $context;

		// no parameter has been set
		if(!isset($context['users_trusted_hosts']))
			return FALSE;

		// match the network address
		if(isset($_SERVER['REMOTE_ADDR']) && (strpos($context['users_trusted_hosts'], $_SERVER['REMOTE_ADDR']) !== FALSE))
			return TRUE;

		// match the network address
		if(isset($_SERVER['REMOTE_HOST']) && (strpos($context['users_trusted_hosts'], $_SERVER['REMOTE_HOST']) !== FALSE))
			return TRUE;

		// not trusted
		return FALSE;
	}

	/**
	 * update surfer presence
	 *
	 * This function is used to track presence information.
	 * Errors are not reported, if any
	 *
	 * @param string web address of visited page
	 * @param string related title
	 * @param string the target anchor, if any
	 * @param string level of visibility for this anchor (e.g., 'Y', 'R' or 'N')
	 */
	public static function is_visiting($link, $label, $anchor=NULL, $active='Y') {
		global $context;

		// don't track crawlers
		if(Surfer::is_crawler())
			return;

		// update the history stack
		if(!isset($context['pages_without_history']) || ($context['pages_without_history'] != 'Y')) {

			// put at top of stack
			if(!isset($_SESSION['visited']))
				$_SESSION['visited'] = array();
			$_SESSION['visited'] = array_merge(array($link => $label), $_SESSION['visited']);

			// limit to 20 most recent pages
			if(count($_SESSION['visited']) > 20)
				array_pop($_SESSION['visited']);

		}

		// no anchor to remember
		if(!$anchor)
			return;

		// ensure regular operation of the server
		if(!file_exists($context['path_to_root'].'parameters/switch.on'))
			return;

		// nothing remembered for anonymous surfers
		if(!Surfer::get_id())
			return;

		// we need a GET
		if(!isset($_SERVER['REQUEST_METHOD']) || ($_SERVER['REQUEST_METHOD'] != 'GET'))
			return;

		// Firefox pre-fetch is not a real visit
		if(isset($_SERVER['HTTP_X_MOZ']) && ($_SERVER['HTTP_X_MOZ'] == 'prefetch'))
			return;

		// ensure the back-end is there
		if(!is_callable(array('SQL', 'query')))
			return;

		// update the record of the surfer
		$query = "UPDATE ".SQL::table_name('users')
			." SET click_anchor='".SQL::escape($anchor)."', click_date='".gmstrftime('%Y-%m-%d %H:%M:%S')."'"
			." WHERE id = ".SQL::escape(Surfer::get_id());
		SQL::query($query, FALSE, $context['users_connection']);

		// also update recent visits
		include_once $context['path_to_root'].'users/visits.php';
		Visits::track($anchor, $active);

		// job done
		return;

	}

	/**
	 * check salt and pepper
	 *
	 * This function helps to stop robots, by checking outcome of user challenge.
	 *
	 * It has to be used in conjonction with [code]Surfer::get_robot_stopper()[/code].
	 *
	 * @return FALSE if salt and pepper are equals, TRUE otherwise
	 */
	public static function may_be_a_robot() {
		global $context;

		// this has already been checked
		if(isset($_SESSION['surfer_is_not_a_robot']) && $_SESSION['surfer_is_not_a_robot'])
			return FALSE;

		// we have been asked to not challenge anonymous surfers
		if(isset($context['users_without_robot_check']) && ($context['users_without_robot_check'] == 'Y'))
			return FALSE;

		// surfer has been authenticated
		if(Surfer::is_logged())
			return FALSE;

		// salt could have been hacked
		if(!isset($_SESSION['salt']))
			return TRUE;

		// salt and pepper are ok
		if(isset($_REQUEST['pepper']) && !strcmp($_REQUEST['pepper'], $_SESSION['salt'])) {

			// remember this, to not challenge the surfer again
			$_SESSION['surfer_is_not_a_robot'] = TRUE;

			// not a robot, for sure
			return FALSE;
		}

		// user agent is dumb, no doubt on that
		return TRUE;

	}

	/**
	 * can this surfer contact another user?
	 *
	 * @return TRUE if alowed, FALSE otherwise
	 */
	public static function may_contact($id=NULL) {
		global $context;

		// associate can always do it
		if(Surfer::is_associate())
			return TRUE;

		// communication between members is always fostered (intranet)
		if(isset($context['users_with_email_display']) && ($context['users_with_email_display'] == 'Y'))
			return TRUE;

		// communication between members is not allowed (internet)
		if(isset($context['users_with_email_display']) && ($context['users_with_email_display'] == 'N'))
			return FALSE;

		// authenticated surfers can communicate through mail
		if(Surfer::is_logged())
			return TRUE;

		return FALSE;
	}

	/**
	 * back door to some resource
	 *
	 * This function checks the provided handle against authorized handles
	 * listed in session data.
	 *
	 * @param string the secret handle
	 * @return TRUE or FALSE
	 */
	public static function may_handle($handle) {

		// no handle in session
		if(!isset($_SESSION['surfer_handles']) || !is_array($_SESSION['surfer_handles']))
			return FALSE;

		// true if handle is here
		return in_array($handle, $_SESSION['surfer_handles']);

	}

	/**
	 * can this surfer mail other users?
	 *
	 * @return TRUE if alowed, FALSE otherwise
	 */
	public static function may_mail() {
		global $context;

		// email has to be activated
		if(!isset($context['with_email']))
			return FALSE;

		if($context['with_email'] != 'Y')
			return FALSE;

		// only members can send e-mail
		if(!Surfer::is_member())
			return FALSE;

		return Surfer::may_contact();
	}

	/**
	 * check upload capability
	 *
	 * This function checks surfer overall capability against actual
	 * server configuration (PHP parameter 'file_uploads') and also
	 * against the parameter [code]users_without_uploads[/code]
	 * set in the configuration panel for users.
	 *
	 * @see users/configure.php
	 *
	 * @param string actual capability, for possible impersonation (see services/blog.php)
	 * @return TRUE if the surfer is allowed to upload files, FALSE otherwise
	 */
	public static function may_upload($capability=NULL) {
		global $context;

		// sanity check
		if(!$capability)
			$capability = Surfer::get_capability();

		// server limitation
		if(!ini_get('file_uploads'))
			return FALSE;

		// administrative permission has not been set
		if(!isset($context['users_without_uploads']))
			return TRUE;

		// even associates may not upload files
		if($context['users_without_uploads'] == 'Y')
			return FALSE;

		// only associates may upload files
		if(($context['users_without_uploads'] == 'R') && !Surfer::is_associate() && ($capability != 'A'))
			return FALSE;

		// surfers are encouraged to share files and images
		return TRUE;
	}

	/**
	 * kill the current session
	 *
	 * This function deletes almost everything related to the current session,
	 * except the cookie that contains the session id.
	 *
	 * @link http://fr.php.net/manual/en/function.session-destroy.php PHP: session_destroy
	 */
	public static function reset() {
		global $context;

		// if surfer has been authenticated
		if(Surfer::get_id()) {

			// erase presence information in his record
			$query = "UPDATE ".SQL::table_name('users')
				." SET click_date='".NULL_DATE."', click_anchor=''"." WHERE id = ".Surfer::get_id();
			SQL::query($query, FALSE, $context['users_connection']);

			// also forget last visits
			include_once $context['path_to_root'].'users/visits.php';
			Visits::purge_for_user(Surfer::get_id());

		}

		// unset all of the session variables.
		$_SESSION = array();

		// also delete permanent session cookie, if any
		if(isset($_COOKIE['screening'])) {
			Safe::setcookie('screening', '', time()-3600, $context['url_to_root']);

			// also clear cookies used in leading index.php
			if($home = getenv('YACS_HOME'))
				Safe::setcookie('screening', '', time()-3600, $home.'/');
			if($context['url_to_root'] != '/')
				Safe::setcookie('screening', '', time()-3600, '/');
	   }

		// finally, destroy the session and release related resources --no warning if session data cannot be deleted
		if(session_id() && is_callable('session_destroy'))
			@session_destroy();

	}

	/**
	 * surfer has been authenticated
	 *
	 * This function copies user attributes in session storage area.
	 *
	 * Following named attributes from the provided array are copied in session storage area:
	 * - $fields['id'] - id of the logged surfer
	 * - $fields['nick_name'] - nick name of the logged surfer
	 * - $fields['email'] - email address
	 * - $fields['editor'] - preferred on-line editor
	 * - $fields['capability'] - 'A'ssociate or 'M'ember or 'S'ubscriber or '?'
	 * - $fields['phone_number'] - phone number (international format)
	 *
	 * We also remember the IP address of the authenticating workstation,
	 * and the root path of the instance that has validated the surfer.
	 *
	 * @param array session attributes
	 * @param boolean TRUE to remind date of last login in user record
	 */
	public static function set($fields, $update_flag = FALSE) {
		global $context;

		// save session attributes
		$_SESSION['surfer_id'] = isset($fields['id'])?$fields['id']:'';

		$_SESSION['surfer_language'] = isset($fields['language'])?$fields['language']:'none';

		if(isset($fields['full_name']) && $fields['full_name'])
			$_SESSION['surfer_name'] = $fields['full_name'];
		elseif(isset($fields['nick_name']) && $fields['nick_name'])
			$_SESSION['surfer_name'] = $fields['nick_name'];
		else
			$_SESSION['surfer_name'] = '';

		$_SESSION['surfer_email_address'] = isset($fields['email'])?$fields['email']:'';

		$_SESSION['surfer_phone_number'] = isset($fields['phone_number'])?$fields['phone_number']:'';

		// provide a default capability only to recorded users
		if(!$_SESSION['surfer_id'])
			$default_capability = '';

		// closed community, accept only subscribers
		elseif(isset($context['users_with_approved_members']) && ($context['users_with_approved_members'] == 'Y'))
			$default_capability = 'S';

		// email addresses have to be validated
		elseif(isset($context['users_with_email_validation']) && ($context['users_with_email_validation'] == 'Y'))
			$default_capability = 'S';

		// open community, accept subscribers and members
		else
			$default_capability = 'M';

		$_SESSION['surfer_capability'] = isset($fields['capability'])?$fields['capability']:$default_capability;

		// editor preference
		if(isset($fields['editor']))
			$_SESSION['surfer_editor'] = $fields['editor'];
		if(!isset($_SESSION['surfer_editor']) || !$_SESSION['surfer_editor'])
			$_SESSION['surfer_editor'] = $context['users_default_editor'];

		// interface preference
		if(isset($fields['interface']) && ($fields['interface'] == 'C'))
			$_SESSION['surfer_interface'] = 'C';
		else
			$_SESSION['surfer_interface'] = 'I';

		// remember the address of the authenticating workstation
		if(isset($_SERVER['REMOTE_ADDR']))
			$_SESSION['workstation_id'] = $_SERVER['REMOTE_ADDR'];

		// remember the authenticating instance
		if(isset($context['url_to_root']) && $context['url_to_root'])
			$_SESSION['server_id'] = $context['url_to_root'];

		// the surfer has been authenticated, do not challenge him anymore
		$_SESSION['surfer_is_not_a_robot'] = TRUE;

		// update user record
		if(isset($fields['id'])) {

			// clear tentatives of authentication
			$query = array();
			$query[] = 'authenticate_failures=0';

			// remember the date of login
			if($update_flag) {
				$query[] = "login_date='".gmstrftime('%Y-%m-%d %H:%M:%S')."'";
				$query[] = "login_address='".$_SERVER['REMOTE_ADDR']."'";
			}

			// do the update
			$query = "UPDATE ".SQL::table_name('users')." SET ".implode(', ', $query)." WHERE id = ".$fields['id'];
			SQL::query($query, FALSE, $context['users_connection']);
		}

		// set a semi-permanent cookie for user identification
		if(isset($fields['handle']) && $fields['handle'] && isset($context['users_with_permanent_authentication']) && ($context['users_with_permanent_authentication'] == 'Y')) {

			// time of authentication
			$now = (string)time();

			// token is made of: user id, time of login, gmt offset, salt --salt combines date of login with secret handle
			$token = $fields['id'].'|'.$now.'|'.Surfer::get_gmt_offset().'|'.md5($now.'|'.$fields['handle']);

			// attempt to set this cookie while answering the current request
			Surfer::set_cookie('screening', $token);

			// path to this instance			// we will do it again on next transaction, to take care of redirections, if any
			$_SESSION['surfer_token'] = $token;

		}

	}

	/**
	 * set a permanent cookie
	 *
	 * @param string cookie name
	 * @param string cookie value
	 */
	public static function set_cookie($name, $value) {
		global $context;

		// assign the cookie to this instance of yacs
		Safe::setcookie($name, $value, time()+60*60*24*500, $context['url_to_root']);

		// also set cookies used in leading index.php
		if($home = getenv('YACS_HOME'))
			Safe::setcookie($name, $value, time()+60*60*24*500, $home.'/');
		if($context['url_to_root'] == '/yacs/')
			Safe::setcookie($name, $value, time()+60*60*24*500, '/');

	}

	/**
	 * remember surfer data
	 *
	 * This function is used to track anonymous surfers based on what they
	 * put in web forms.
	 *
	 * Following named attributes from the provided array are copied in session storage area:
	 * - $fields['edit_name'] - nick name of the logged surfer
	 * - $fields['edit_address'] - email address
	 *
	 * @param array session attributes
	 */
	public static function track($fields) {
		global $context;

		// preserve permanent settings
		if(Surfer::is_logged())
			return;

		// remember one time name
		if(isset($fields['edit_name']))
			$_SESSION['surfer_name'] = $fields['edit_name'];

		// remember one time email address
		if(isset($fields['edit_address']))
			$_SESSION['surfer_email_address'] = $fields['edit_address'];

	}

	/**
	 * strip all HTML tags
	 *
	 * @link http://www.fileformat.info/info/unicode/version/1.1/index.htm Unicode 1.1
	 *
	 * @param a string or an array
	 * @param string of allowed tags, if any
	 * @return a clean string or array
	 */
	public static function strip_tags($input, $allowed_tags='') {

		// do it recursively
		if(is_array($input)) {
			foreach($input as $name => $value)
				$input[$name] = Surfer::strip_tags($value, $allowed_tags);
			return $input;
		}

		// unmask HTML special chars
		$input = preg_replace('/&(#0*38|amp);/i', '&', $input);
		$input = preg_replace('/%u0*26/i', '&', $input);

		$input = preg_replace('/&(#0*60|lt);/i', '<', $input);
		$input = preg_replace('/%u0*3c/i', '<', $input);

		$input = preg_replace('/&(#0*62|gt);/i', '>', $input);
		$input = preg_replace('/%u0*3e/i', '>', $input);

		$input = preg_replace('/&#0*59;/i', ';', $input);

		// strip tags
		return strip_tags($input, $allowed_tags);
	}

	/**
	 * adjust a date to UTC time zone
	 *
	 * Use this function to convert dates received from surfer time zone
	 * before saving them in the database.
	 *
	 * You should apply this function only to data received from web forms.
	 *
	 * @param string a stamp written on the 'YYYY-MM-DD HH:MM:SS' model
	 * @return string a rewrite of the stamp in the UTC time zone
	 */
	public static function to_GMT($stamp) {

		// sanity check
		if(!isset($stamp) || ($stamp <= NULL_DATE))
			return $stamp;

		// time in surfer time zone
		$stamp = mktime(intval(substr($stamp, 11, 2)), intval(substr($stamp, 14, 2)), intval(substr($stamp, 17, 2)), intval(substr($stamp, 5, 2)), intval(substr($stamp, 8, 2)), intval(substr($stamp, 0, 4)));

		// shift to UTC time zone
		return strftime('%Y-%m-%d %H:%M:%S', $stamp - (Surfer::get_gmt_offset() * 3600));
	}

}

// we don't want to use url rewriting, but only cookies (exclusive with 'use.cookies' and 'use_trans_sid')
if(isset($_SERVER['REMOTE_ADDR'])) {
	Safe::ini_set('session.use_cookies', '1');
	Safe::ini_set('session.use_only_cookies', '1');
	//Safe::ini_set('session.use_trans_sid', '0'); -- don't uncomment !!!
	Safe::ini_set('url_rewriter.tags', '');
}

// set the permanent cookie on the transaction that follows the login, in case a redirection would have happened
if(isset($_SESSION['surfer_token'])) {

	// set it
	Surfer::set_cookie('screening', $_SESSION['surfer_token']);

	// don't do that again
	unset($_SESSION['surfer_token']);
}

// retrieve session data, but not if run from the command line, and not from robot nor spider
if(isset($_SERVER['REMOTE_ADDR']) && !Surfer::is_crawler() && !headers_sent()) {

	// permanent identification has been selected
	if(isset($context['users_with_permanent_authentication']) && ($context['users_with_permanent_authentication'] == 'Y')) {

		// use cookie to identify user -- user id, time of login, gmt offset, salt
		if(!Surfer::is_logged() && isset($_COOKIE['screening']) && ($nouns = explode('|', $_COOKIE['screening'], 4)) && (count($nouns) == 4)) {

			// get user by id
			if(!$user = Users::get($nouns[0]))
				;

			// ensure we have the target user profile
			elseif(!isset($user['id']) || ($user['id'] != $nouns[0]))
				;

			// salted stamp has to match
			elseif(!isset($user['handle']) || strcmp($nouns[3], md5($nouns[1].'|'.$user['handle'])))
				;

			// save surfer profile in session context, and remind date of last login
			else
				Surfer::set($user, TRUE);

		}

	// allow no more than one hour of inactivity
	} elseif(isset($_SESSION['watchdog']) && (time() > ($_SESSION['watchdog'] + 3600)))
		Surfer::reset();

	// refresh the watchdog
	$_SESSION['watchdog'] = time();

	// bare server-side flash detection
	if(isset($_SERVER['HTTP_ACCEPT']) && preg_match('/application\/x-shockwave-flash/i', $_SERVER['HTTP_ACCEPT']))
		$_SESSION['browser_has_flash'] = TRUE;

}

// filter HTML tags of submitted material
if(@count($_REQUEST) && (!isset($context['allow_html_input']) || ($context['allow_html_input'] != 'Y'))) {

	// from an associate
	if(Surfer::is_associate())
		;

	// from tinymce
	elseif(isset($_REQUEST['editor']) && ($_REQUEST['editor'] == 'tinymce'))
		;

	// strip most tags, except those that have been explicitly allowed
	else {
		if(!isset($context['users_allowed_tags']))
			$context['users_allowed_tags'] = '';
		$_REQUEST = Surfer::strip_tags($_REQUEST, $context['users_allowed_tags']);
	}
}

// remember editor setting
if(isset($_COOKIE['surfer_editor']))
	$_SESSION['surfer_editor'] = $_COOKIE['surfer_editor'];

?>
