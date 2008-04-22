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
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
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
	function add_handle($handle) {

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
	function assigned_sections($id=NULL, $maximum=21) {
		global $context;

		// default to current surfer
		if(!$id)
			$id = Surfer::get_id();

		// sanity check
		if(!$id)
			return array();

		// query the database only once
		static $my_sections;
		if(isset($my_sections))
			return $my_sections;
		$my_sections = array();

		// only consider live sections
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		$where = "((sections.expiry_date is NULL)"
			."	OR (sections.expiry_date <= '".NULL_DATE."') OR (sections.expiry_date > '".$now."'))";

		// the list of sections
		$query = "SELECT sections.id FROM ".SQL::table_name('members')." AS members"
			.", ".SQL::table_name('sections')." AS sections"
			." WHERE (members.anchor LIKE 'user:".SQL::escape($id)."')"
			."	AND (members.member_type = 'section')"
			."	AND (members.member_id = sections.id)"
			."	AND (".$where.")"
			." ORDER BY sections.rank, sections.title, sections.edit_date DESC LIMIT 0, ".$maximum;

		// submit a silent query because at setup tables don't exist
		if(($result =& SQL::query($query, TRUE))) {

			// build the list
			while($row =& SQL::fetch($result))
				$my_sections[] = $row['id'];

		}

		// done
		return $my_sections;

	}

	/**
	 * build contact information for another user
	 *
	 * This function has to be called from the template, once the skin has been loaded.
	 *
	 * @param array attributes of the target user profile
	 * @return string the HTML snippet to be put in page
	 *
	 * @see skins/skin_skeleton.php
	 */
	function &build_user_contact($user) {
		global $context;

		// we return some text
		$text = '';

		// current presence
		//
		$box = array( 'list' => array(), 'text' => '');

		// i am looking at my own record
		if(Surfer::get_id() && (Surfer::get_id() == $user['id'])) {

			// some page or thread has been visited recently
			include_once $context['path_to_root'].'users/visits.php';
			if($items = Visits::list_for_user($user['id'])) {
				$box['list'] = array_merge($box['list'], $items);

			// user is present if active during last 10 minutes (10*60 = 600), but not at some thread
			} elseif(isset($user['click_date']) && ($user['click_date'] >= gmstrftime('%Y-%m-%d %H:%M:%S', time()-600))) {

				// show place of last click
				if(isset($user['click_anchor']) && ($anchor = Anchors::get($user['click_anchor'])))
					$box['list'] = array_merge($box['list'], array($anchor->get_url() => $anchor->get_title()));

			}

			// make a box
			if(count($box['list']))
				$text .= Skin::build_box(i18n::s('Your presence'), Skin::build_list($box['list'], 'compact'), 'folder', 'co_browsing');

		// navigating another profile
		} else {

			// some page or thread has been visited recently
			include_once $context['path_to_root'].'users/visits.php';
			if($items = Visits::list_for_user($user['id'])) {
				foreach($items as $url => $label)
					$box['list'] = array_merge($box['list'], array($url => sprintf(i18n::s('Join %s at %s'), $user['nick_name'], $label)));

			// user is present if active during last 10 minutes (10*60 = 600), but not at some thread
			} elseif(isset($user['click_date']) && ($user['click_date'] >= gmstrftime('%Y-%m-%d %H:%M:%S', time()-600))) {

				// show place of last click
				if(isset($user['click_anchor']) && ($anchor = Anchors::get($user['click_anchor'])))
					$box['list'] = array_merge($box['list'], array($anchor->get_url() => sprintf(i18n::s('Join %s at %s'), $user['nick_name'], $anchor->get_title())));

			}

			// make a box
			if(count($box['list']))
				$text .= Skin::build_box(i18n::s('Co-browsing'), Skin::build_list($box['list'], 'compact'), 'folder', 'co_browsing');

		}

		// on-going conversations
		//
		if($anchor = Sections::lookup('chats')) {

			$box = array( 'text' => '', 'title' => '' );

			// i am looking at my own record
			if(Surfer::get_id() && (Surfer::get_id() == $user['id'])) {
				$box['title'] = i18n::s('Your private conversations');

				// list private conversations, if any
				if($items = Articles::list_assigned_by_date_for_anchor($anchor, $user['id'], 0, 20, 'simple no_anchor', FALSE))
					$box['text'] = Skin::build_list($items, 'compact');

			// navigating another profile
			} else {
				$box['title'] = sprintf(i18n::s('Your private conversations with %s'), $user['nick_name']);

				// list existing private conversations, if any
				if($items = Articles::list_assigned_by_date_for_anchor($anchor, $user['id'], 0, 20, 'simple no_anchor', TRUE))
					$box['text'] = Skin::build_list($items, 'compact');

			}

			// make a box
			if($box['text'])
				$text .= Skin::build_box($box['title'], $box['text'], 'folder', 'private_conversations');

		}

		// real-time notifications
		//
		if(!Surfer::get_id() || (Surfer::get_id() != $user['id'])) {

			$box = array( 'list' => array(), 'text' => '', 'title' => '' );

			// contact a user that is currently present
			if($user['is_present']) {
				$box['title'] = sprintf(i18n::s('Options to contact %s'), $user['nick_name']);

				// a wide panel of options
				$box['text'] .= '<p>'.sprintf(i18n::s('%s is currently present at this site, and several contact options are available:'), $user['nick_name']).'</p>'."\n";

				// the form to list contact options
				$box['text'] .= '<form method="post" action="'.$context['url_to_root'].'users/contact.php">';

				// layout as a definition list
				$box['text'] .= '<dl>';

				// real-time notifications
				$box['text'] .= '<dt><input type="radio" name="rendez_vous" value="thread" checked="checked" onclick="javascript:document.getElementById(\'address\').disabled=true;"/>'.i18n::s('Start a private conversation').'</dt><dd><p /></dd>'."\n"
					.'<dt><input type="radio" name="rendez_vous" value="browse"  onclick="javascript:document.getElementById(\'address\').disabled=false;"/>'.i18n::s('Share the following web address').BR
						.'&nbsp;&nbsp;<input type="text" id="address" name="address" size="45" value="" maxlength="255" disabled="disabled"/></dt><dd><p /></dd>'."\n"
					.'<dt><input type="radio" name="rendez_vous" value="none"  onclick="javascript:document.getElementById(\'address\').disabled=true;"/>'.i18n::s('Send a private message').'</dt><dd> </dd>';

				// end of options
				$box['text'] .= '</dl>'."\n";

				// the message
				$box['text'] .= '<p>'.sprintf(i18n::s('Message to notify %s'), $user['nick_name']).BR
					.'<textarea name="message" id="message" value="" rows="2" cols="50" maxlength="240" /></textarea>'.BR
					.'<span class="tiny">'.i18n::s('Use only plain ASCII, no HTML.').'</span></p>';

				// the submit button
				$box['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Send'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

				// transmit the id as a hidden field
				$box['text'] .= '<input type="hidden" name="id" value="'.$user['id'].'" />';

				// end of the form
				$box['text'] .= '</form>';

				// append the script used for data checking on the browser
				$box['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
					.'document.getElementById("message").focus();'."\n"
					.'// ]]></script>';

			// contact an user that is currently absent
			} else {
				$box['title'] = sprintf(i18n::s('Start a private conversation with %s'), $user['nick_name']);

				// the form to create a private thread
				$box['text'] .= '<form method="post" action="'.$context['url_to_root'].'users/contact.php">';

				// thread title
				$box['text'] .= '<p>'.i18n::s('Conversation topic').BR
					.'<input type="text" name="title" id="thread_title" value="" size="45" maxlength="240" /></p>';

				// thread first contribution
				$box['text'] .= '<p>'.i18n::s('Your first contribution').BR
					.'<textarea name="message" id="message" value="" rows="2" cols="50" maxlength="240" /></textarea>'.BR
					.'<span class="tiny">'.i18n::s('Use only plain ASCII, no HTML.').'</span></p>'
					.'<input type="hidden" name="rendez_vous" value="thread" />';

				// the submit button
				$box['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Send'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

				// transmit the id as a hidden field
				$box['text'] .= '<input type="hidden" name="id" value="'.$user['id'].'" />';

				// end of the form
				$box['text'] .= '</form>';

				// append the script used for data checking on the browser
				$box['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
					.'document.getElementById("thread_title").focus();'."\n"
					.'// ]]></script>';

			}

			// make a box
			if($box['text'])
				$text .= Skin::build_box($box['title'], $box['text'], 'folder', 'contact_options');

		}

		// return by reference
		return $text;
	}

	/**
	 * build the navigation menu for this surfer
	 *
	 * This function has to be called from the template, once the skin has been loaded.
	 *
	 * @param string the type of each link
	 * @return a list of $url => array($prefix, $label, $suffix, $type) to be used with Skin::build_list()
	 *
	 * @see skins/skin_skeleton.php
	 */
	function &build_user_menu($type = 'submenu') {
		global $context;

		// an array of links
		$menu = array();

		// surfer is a valid user
		if(Surfer::is_logged()) {

			if(Surfer::get_id() && is_callable(array('Users', 'get_url'))) {
				$link = Users::get_url(Surfer::get_id(), 'view', Surfer::get_name());
				$menu[$link] = array(NULL, i18n::s('My profile'), NULL, $type, NULL, i18n::s('View all data this site knows about you'));
			}

			if(Surfer::is_associate()
				|| (Surfer::is_member() && (!isset($context['users_without_submission']) || ($context['users_without_submission'] != 'Y'))) ) {

				$menu['articles/edit.php'] = array(NULL, i18n::s('Add a page'), NULL, $type, NULL, i18n::s('Use a web form to submit new content'));

				$menu['links/edit.php'] = array(NULL, i18n::s('Bookmark a link'), NULL, $type, NULL, i18n::s('Share interesting pages'));

			}

			if(Surfer::is_associate())
				$menu['articles/review.php'] = array(NULL, i18n::s('Review queue'), NULL, $type, NULL, i18n::s('Check requests, publish submitted articles, review old pages'));

			$menu['users/logout.php'] = array(NULL, i18n::s('Logout'), NULL, $type, NULL, i18n::s('You will be considered as an anonymous surfer'));

			if(Surfer::is_associate())
				$menu['control/'] = array(NULL, i18n::s('Control Panel'), NULL, $type, NULL, i18n::s('System commands, configuration panels, content overview'));

			$menu['help.php'] = array(NULL, i18n::s('Help'), NULL, $type, NULL, i18n::s('If you don\'t know how to proceed, start here'));

		// no user menu during installation
		} elseif(!file_exists($context['path_to_root'].'parameters/switch.on') && !file_exists($context['path_to_root'].'parameters/switch.off')) {

		// surfer has not been authenticated, and self-registration is allowed
		} elseif(!isset($context['users_without_registration']) || ($context['users_without_registration'] != 'Y')) {

			$menu['users/edit.php'] = array(NULL, i18n::s('Register'), NULL, $type, NULL, i18n::s('Share your profile in this community'));

			$menu['users/login.php'] = array(NULL, i18n::s('Login'), NULL, $type, NULL, i18n::s('Prove who you are'));

			$menu['help.php'] = array(NULL, i18n::s('Help'), NULL, $type, NULL, i18n::s('If you don\'t know how to proceed, start here'));
		}

		// return by reference
		if(!count($menu))
			$menu = NULL;
		return $menu;
	}

	/**
	 * set default attributes for this surfer
	 *
	 * This function is called before writing records in the database,
	 * to ensure attributes 'edit_name', 'edit_id', 'edit_address' and 'edit_date' have been properly set.
	 *
	 * @param array attributes to check
	 * @return updated attributes
	 *
	 * @see actions/actions.php
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
	function check_default_editor($fields) {

		// if a name has been set, do not impersonate the surfer at other fields
		if(!isset($fields['edit_name']) || !$fields['edit_name']) {

			// default value for editor name
			$fields['edit_name'] = Surfer::get_name();

			// default value for editor id
			if(!isset($fields['edit_id']) || !$fields['edit_id'])
				$fields['edit_id'] = Surfer::get_id();

			// default value for editor address
			if(!isset($fields['edit_address']) || !$fields['edit_address'])
				$fields['edit_address'] = Surfer::get_email_address();

		// SQL statements assume following fields have been set
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

		// done
		return $fields;
	}

	/**
	 * stamp last click
	 *
	 * This function is used to track presence information.
	 * Errors are not reported, if any
	 *
	 * @param string the target anchor, if any
	 * @param string level of visibility for this anchor (e.g., 'Y', 'R' or 'N')
	 * @return boolean TRUE on success, FALSE otherwise
	 */
	function click($anchor=NULL, $active='Y') {
		global $context;

		// this is allowed only once
		static $fuse;
		if(isset($fuse))
			return TRUE;
		$fuse = TRUE;

		// ensure regular operation of the server
		if(!file_exists($context['path_to_root'].'parameters/switch.on'))
			return FALSE;

		// nothing remembered for anonymous surfers
		if(!Surfer::get_id())
			return FALSE;

		// we need more than a HEAD
		if(!isset($_SERVER['REQUEST_METHOD']) || ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
			return FALSE;

		// Firefox pre-fetch is not a real visit
		if(isset($_SERVER['HTTP_X_MOZ']) && ($_SERVER['HTTP_X_MOZ'] == 'prefetch'))
			return FALSE;

		// ensure the back-end is there
		if(!is_callable(array('SQL', 'query')))
			return FALSE;

		// update the record of the surfer
		$query = "UPDATE ".SQL::table_name('users')
			." SET click_anchor='".SQL::escape($anchor)."', click_date='".gmstrftime('%Y-%m-%d %H:%M:%S')."'"
			." WHERE id = ".SQL::escape(Surfer::get_id());
		SQL::query($query, FALSE, $context['users_connection']);

		// also update recent visits
		include_once $context['path_to_root'].'users/visits.php';
		Visits::track($anchor, $active);

		// job done
		return TRUE;

	}

	/**
	 * extend capability for this request
	 *
	 * This function is used to flag editors, and any surfer which benefits from
	 * extended rights for the duration of the current script.
	 *
	 * @param string new capability of this surfer, '?', 'M' or 'A'
	 */
	function empower($capability='A') {
		global $context;

		if(($capability == '?') || ($capability == 'S') || ($capability == 'M') || ($capability == 'A'))
			$context['empowered'] = $capability;
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
	function from_GMT($stamp) {
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
	 * @return char either 'A', 'M', 'S' or '?'
	 */
	function get_capability() {
		global $context;

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
	 * - 'fckeditor' - WYSIWYG editor
	 * - 'tinymce' - WYSIWYG editor
	 *
	 * @link http://www.fckeditor.net/ FCKEditor
	 * @link http://tinymce.moxiecode.com/ TinyMCE
	 *
	 * @param string the name of the editing field
	 * @param string content to be put in the editor
	 * @return string to be inserted in the XHTML flow
	 */
	function get_editor($name='description', $value='') {
		global $context;

		// returned string
		$text = '';

		// enforce default configuration
		if(!isset($_SESSION['surfer_editor']) && isset($context['users_default_editor']))
			$_SESSION['surfer_editor'] = $context['users_default_editor'];

		// fckeditor
		if(isset($_SESSION['surfer_editor']) && ($_SESSION['surfer_editor'] == 'fckeditor') && is_readable($context['path_to_root'].'included/fckeditor/fckeditor.php')) {

			include_once($context['path_to_root'].'included/fckeditor/fckeditor.php');
			$handle =& new FCKeditor($name);
			$handle->BasePath = $context['url_to_root'].'included/fckeditor/';
			$handle->Value = $value;
			$handle->Height = '400' ;
			$handle->ToolbarSet = 'YACS'; // see fckconfig.js
			$text .= $handle->CreateHtml() ;

			// signal an advanced editor
			$text .= '<input type="hidden" name="editor" value="fckeditor" />';

		// tinymce
		} elseif(isset($_SESSION['surfer_editor']) && ($_SESSION['surfer_editor'] == 'tinymce') && is_readable($context['path_to_root'].'included/tiny_mce/tiny_mce_src.js')) {

			// load the TinyMCE script -- see shared/global.php
			$context['javascript']['tinymce'] = TRUE;

			// the textarea that will be handled by TinyMCE
			$text .= '<div><textarea name="'.$name.'" id="edit_area" class="tinymce" rows="25" cols="50" accesskey="c">'.encode_field($value).'</textarea></div>';

			// signal an advanced editor
			$text .= '<input type="hidden" name="editor" value="tinymce" />';

		// default to plain editor
		} else {
			if(file_exists($context['path_to_root'].'codes/edit.js')) {
				$text .= '<script type="text/javascript" src="'.$context['url_to_root'].'codes/edit.js"></script>';
				if(file_exists($context['path_to_root'].'smileys/edit.js'))
					$text .= '<script type="text/javascript" src="'.$context['url_to_root'].'smileys/edit.js"></script>';
			}
			$text .= '<textarea name="'.$name.'" id="edit_area" rows="25" cols="50" accesskey="c">'.encode_field($value).'</textarea>';

		}

		// hint
		if(Surfer::is_associate())
			$hint = i18n::s('You are allowed to post any XHTML.');
		else
			$hint = sprintf(i18n::s('Also, following XHTML tags are allowed: %s'), trim(str_replace('><', ', ', $context['users_allowed_tags']), '<>'));
		$text .= '<span class="tiny">'.$hint.'</span>';

		// job done
		return $text;
	}

	/**
	 * get the e-mail address of the current surfer, if known
	 *
	 * @return string a valid address, or NULL
	 */
	function get_email_address() {
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
	function get_gmt_offset() {
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
	function get_id() {
		global $context;

		// enforce session scope
		if(isset($context['url_to_root']) && (!isset($_SESSION['server_id']) || ($_SESSION['server_id'] == $context['url_to_root']))) {

			// use session data
			if(isset($_SESSION['surfer_id']))
				return $_SESSION['surfer_id'];

		}
		return 0;
	}

	/**
	 * get the name of the current surfer, if known
	 *
	 * This function will remove mention of originating server for shadow records.
	 *
	 * @return string user short name, or NULL
	 */
	function get_name() {
		global $context;

		// use session data
		if(isset($_SESSION['surfer_name']))
			return preg_replace('/(@.+)$/', '', $_SESSION['surfer_name']);

		// don't give a name if we have been called by a crawler
		if(Surfer::is_crawler())
			return NULL;

		// surfer is unknown, give him a name
		$_SESSION['surfer_name'] = i18n::s('Surfer').rand(1000, 100000);
		return $_SESSION['surfer_name'];
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
	function get_robot_stopper() {
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

		// build a random string --  l, o, O and 0 are confusing
		$pool = 'abcdefghijkmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ123456789';
		$salt = $pool[mt_rand(0, strlen($pool)-1)]
			.$pool[mt_rand(0, strlen($pool)-1)]
			.$pool[mt_rand(0, strlen($pool)-1)]
			.$pool[mt_rand(0, strlen($pool)-1)]
			.$pool[mt_rand(0, strlen($pool)-1)];

		// add salt and pepper to the form
		$label = i18n::s('Robot stopper').' *';
		$input = i18n::s('Type exactly the following 5 chars:').' '.$salt.' <input type="text" name="pepper" size="7"'.EOT
			.'<input type="hidden" name="salt" value="'.md5($salt).'"'.EOT;
		return array($label, $input);
	}

	/**
	 * user interface complexity
	 *
	 * @return TRUE or FALSE
	 */
	function has_all() {

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
	function has_flash() {
		global $context;

		// use cookie sent by browser -- see shared/yacs.js
		if(isset($_COOKIE['FlashIsAvailable']) && ($_COOKIE['FlashIsAvailable'] == 'yes'))
			return TRUE;

		// assume no Flash
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
	function is_associate($capability='?') {
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
	function is_crawler() {

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
			'crawler',				// generic crawler
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
			'yacs', 				// another yacs server
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
	 * is the current surfer the creator of one item?
	 *
	 * To control that a surfer is the actual creator of one article, following code may be used:
	 * [php]
	 * // load the article from the database, including the editors list
	 * $item =& Articles::get($id);
	 *
	 * // check that the current surfer is a valid editor
	 * if(Surfer::is_creator($item['create_id']) {
	 *	 ...
	 * }
	 * [/php]
	 *
	 * @param string the id of the original poster
	 * @return true or false
	 */
	function is_creator($id) {

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
	 * is this a super user?
	 *
	 * @param string checked capability for this surfer, '?', 'M' or 'A'
	 * @return true or false
	 */
	function is_empowered($capability='A') {
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
	function is_logged() {
		global $context;

		// enforce session scope
		if(isset($context['url_to_root']) && (!isset($_SESSION['server_id']) || ($_SESSION['server_id'] == $context['url_to_root']))) {

			// no role has been defined for this surfer
			if(!isset($_SESSION['surfer_capability']))
				return FALSE;

			// surfer has been authenticated as a valid associate
			if($_SESSION['surfer_capability'] == 'A')
				return TRUE;

			// surfer has been authenticated as a valid associate
			if($_SESSION['surfer_capability'] == 'M')
				return TRUE;

			// surfer has been authenticated as a valid associate
			if($_SESSION['surfer_capability'] == 'S')
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
	function is_member($capability='?') {
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
	 * check salt and pepper
	 *
	 * This function helps to stop robots, by checking outcome of user challenge.
	 *
	 * It has to be used in cuonjonction with [code]Surfer::get_robot_stopper()[/code].
	 *
	 * @return FALSE if salt and pepper are equals, TRUE otherwise
	 */
	function may_be_a_robot() {
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
		if(!isset($_REQUEST['salt']))
			return TRUE;

		// salt and pepper are ok
		if(isset($_REQUEST['pepper']) && !strcmp(md5($_REQUEST['pepper']), $_REQUEST['salt'])) {

			// remember this, to not challenge the surfer again
			$_SESSION['surfer_is_not_a_robot'] = TRUE;

			// not a robot, for sure
			return FALSE;
		}

		// user agent is dumb, no doubt on that
		return TRUE;

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
	function may_handle($handle) {

		// no handle in session
		if(!isset($_SESSION['surfer_handles']) || !is_array($_SESSION['surfer_handles']))
			return FALSE;

		// true if handle is here
		return in_array($handle, $_SESSION['surfer_handles']);

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
	function may_upload($capability = '?') {
		global $context;

		// only authenticated members can upload files
		if(!Surfer::is_member() && ($capability == '?'))
			return FALSE;

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
	 * list personal sections for one surfer
	 *
	 * If a member is acting as a managing editor for some sections,
	 * this function returns ids of these sections, if he has created them.
	 *
	 * @param int id of the surfer to consider
	 * @param int maximum number of sections to return
	 * @return array ids of managed sections
	 */
	function personal_sections($id=NULL, $maximum=21) {
		global $context;

		// default to current surfer
		if(!$id)
			$id = Surfer::get_id();

		// sanity check
		if(!$id)
			return array();

		// query the database only once
		static $my_sections;
		if(isset($my_sections))
			return $my_sections;
		$my_sections = array();

		// sections have been created by this surfer
		$where = "(sections.create_id = '".SQL::escape($id)."')";

		// only consider live sections
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		$where .= " AND ((sections.expiry_date is NULL)"
			."	OR (sections.expiry_date <= '".NULL_DATE."') OR (sections.expiry_date > '".$now."'))";

		// the list of sections
		$query = "SELECT sections.id FROM ".SQL::table_name('members')." AS members"
			.", ".SQL::table_name('sections')." AS sections"
			." WHERE (members.anchor LIKE 'user:".SQL::escape($id)."')"
			."	AND (members.member_type = 'section')"
			."	AND (members.member_id = sections.id)"
			."	AND ".$where
			." ORDER BY sections.rank, sections.title, sections.edit_date DESC LIMIT 0, ".$maximum;

		// submit a silent query because at setup tables don't exist
		if(($result =& SQL::query($query, TRUE))) {

			// build the list
			while($row =& SQL::fetch($result))
				$my_sections[] = $row['id'];

		}

		// done
		return $my_sections;

	}

	/**
	 * kill the current session
	 *
	 * This function deletes almost everything related to the current session,
	 * except the cookie that contains the session id.
	 *
	 * @link http://fr.php.net/manual/en/function.session-destroy.php PHP: session_destroy
	 */
	function reset() {
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
			if(isset($_ENV['YACS_HOME']))
				Safe::setcookie('screening', '', time()-3600, $_ENV['YACS_HOME'].'/');
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
	 * - $fields['capability'] - 'A'ssociate or 'M'ember
	 *
	 * We also remember the IP address of the authenticating workstation,
	 * and the root path of the instance that has validated the surfer.
	 *
	 * @param array session attributes
	 * @param boolean TRUE to remind date of last login in user record
	 */
	function set($fields, $update_flag = FALSE) {
		global $context;

		// save session attributes
		$_SESSION['surfer_id'] = isset($fields['id'])?$fields['id']:'';

		$_SESSION['surfer_name'] = isset($fields['nick_name'])?$fields['nick_name']:'';

		$_SESSION['surfer_email_address'] = isset($fields['email'])?$fields['email']:'';

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
		if(!isset($_SESSION['surfer_editor']) && !$_SESSION['surfer_editor'])
			$_SESSION['surfer_editor'] = $context['users_default_editor'];

		// interface preference
		if(isset($fields['interface']) && ($fields['interface'] == 'C'))
			$_SESSION['surfer_interface'] = 'C';
		else
			$_SESSION['surfer_interface'] = 'I';

		// remember the address of the authenticating workstation
		if(isset($_SERVER['REMOTE_ADDR']))
			$_SESSION['workstation_id'] = $_SERVER['REMOTE_ADDR'];

		// screen sharing
		$_SESSION['with_sharing'] = isset($fields['with_sharing']) ? $fields['with_sharing'] : 'N';

		// an external network address has been defined
		$_SESSION['proxy_address'] = isset($fields['proxy_address']) ? $fields['proxy_address'] : '';

		// remember the authenticating instance
		if(isset($context['url_to_root']) && $context['url_to_root'])
			$_SESSION['server_id'] = $context['url_to_root'];

		// the surfer has been authenticated, do not challenge him anymore
		$_SESSION['surfer_is_not_a_robot'] = TRUE;

		// remember silently the date of the last login
		if($update_flag && isset($fields['id'])) {
			$query = "UPDATE ".SQL::table_name('users')." SET login_date='".gmstrftime('%Y-%m-%d %H:%M:%S')."', login_address='".$_SERVER['REMOTE_ADDR']."' WHERE id = ".$fields['id'];
			SQL::query($query, FALSE, $context['users_connection']);
		}

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
	function track($fields) {
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
	function strip_tags($input, $allowed_tags='') {

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
	function to_GMT($stamp) {

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

// retrieve session data, but not if run from the command line, and not from robot nor spider
if(isset($_SERVER['REMOTE_ADDR']) && !Surfer::is_crawler() && !headers_sent()) {

	// switch to another instance on same host
	if(isset($_SESSION['server_id']) && isset($_SESSION['url_to_root']) && strcmp($_SESSION['server_id'], $context['url_to_root']))
		Surfer::reset();

	// permanent identification has been selected
	elseif(isset($context['users_with_permanent_authentication']) && ($context['users_with_permanent_authentication'] == 'Y')) {

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

	// from fckeditor or tinymce
	elseif(isset($_REQUEST['editor']) && (($_REQUEST['editor'] == 'fckeditor') || ($_REQUEST['editor'] == 'tinymce')))
		;

	// strip most tags, except those that have been explicitly allowed
	else {
		if(!isset($context['users_allowed_tags']))
			$context['users_allowed_tags'] = '';
		$_REQUEST = Surfer::strip_tags($_REQUEST, $context['users_allowed_tags']);
	}
}

?>