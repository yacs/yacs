<?php
/**
 * the database abstraction layer for users
 *
 * YACS has been designed to address needs of small to mid-size communities.
 * The idea behind this concept is that addressing security concerns is becoming complex
 * on a large scale. In order to avoid this complexity, and related management overhead,
 * we have selected to base YACS on a very small set of user profiles:
 *
 * [*] [b]associates[/b] - These users are the webmasters of the community.
 * In YACS, all associates are considered as being equivalent.
 * Therefore, associates have to trust each other to operate the community smoothly.
 * One consequence of this equivalence is that the number of associates should be kept as small
 * as possible, otherwise your system may be in trouble.
 *
 * [*] [b]member[/b] - These users are interested into your community.
 * They have extended reading access rights.
 * With YACS, they are able to submit new articles, to post images,
 * to upload files, and to share on interesting links.
 *
 * [*] [b]subscriber[/b] - They are coming to your server on a more or less regular basis.
 * Some of them are only interested into receiving information
 * through e-mail messages.
 * At YACS, authenticated subscribers are allowed to submit new articles, to post images,
 * to upload files, and to share on interesting links.
 *
 * [*] [b]anonymous[/b] - All other people on earth (more precisely, most of them)
 * are consider as honest readers of public information shared by a YACS community.
 *
 * These profiles, while simple, may support different community patterns.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Jan Boen
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Users {

	/**
	 * alert one user
	 *
	 * This function sends messages only to users ready to receive alerts and notifications.
	 *
	 * It ensures that only one alert is send to a user,
	 * by maintaining an internal list of ids that have been processed.
	 *
	 * @param mixed an array of attributes, or only an id of the user profile
	 * @param array components of a mail message to be submitted to Mailer::notify()
	 * @param string reference of the watched container
	 * @return TRUE on success, FALSE otherwise
	 */
	private static function alert($user, $mail, $reference) {
		global $context;

		// retrieve user attributes
		if(!isset($user['id']) && (!$user =& Users::get($user)))
			return FALSE;

		// a valid address is required for e-mail...
		if(!isset($user['email']) || !$user['email'] || !preg_match(VALID_RECIPIENT, $user['email']))
			return FALSE;

		// ensure poster wants alerts
		if(isset($user['without_alerts']) && ($user['without_alerts'] == 'Y'))
			return FALSE;

		// sanity check
		if(!isset($mail['subject']) || !$mail['subject'] || !isset($mail['message']))
			return FALSE;

		// the list of users notified during overall script execution
		static $already_processed;
		if(!isset($already_processed))
			$already_processed = array();

		// this user has already been notified
		if(in_array($user['id'], $already_processed))
			return FALSE;

		// remember this recipient
		$already_processed[] = $user['id'];

		// use this email address
		if($user['full_name'])
			$recipient = Mailer::encode_recipient($user['email'], $user['full_name']);
		else
			$recipient = Mailer::encode_recipient($user['email'], $user['nick_name']);

		// post a message to this particular user
		return Mailer::notify(Surfer::from(), $recipient, $mail['subject'], $mail['message'], isset($mail['headers'])?$mail['headers']:'');

	}

	/**
	 * alert watchers of one anchor
	 *
	 * @param mixed, either reference of the updated anchor, or array of containers path
	 * @param array components of a mail message to be submitted to Mailer::notify() (i.e., $mail['subject'], $mail['message'])
	 * @param array users assigned to the reference, if any
	 * @return TRUE on success, FALSE otherwise
	 */
	function alert_watchers($references, $mail, $restricted=NULL) {
		global $context;

		// ensure we have an array of references
		if(!is_array($references))
			$references = array( $references );

		// for each reference
		foreach($references as $reference) {

			// list watchers, including watchers of containers of this page
			if($items = Members::list_watchers_by_posts_for_anchor($reference, 0, 10000, 'raw', $restricted)) {

				// check every watcher
				foreach($items as $id => $watcher) {

					// skip banned users
					if($watcher['capability'] == '?')
						continue;

					// skip current surfer
					if(Surfer::get_id() && (Surfer::get_id() == $id))
						continue;

					// ensure this surfer wants to be alerted
					if($watcher['without_alerts'] != 'Y')
						Users::alert($watcher, $mail, $reference);
				}
			}
		}

		// job done
		return TRUE;

	}

	/**
	 * authenticate using network credentials
	 *
	 * For authentication on protected page this function use basic HTTP authentication, as described in
	 * [link=RFC2617]http://www.faqs.org/rfcs/rfc2617.html[/link].
	 *
	 * @link http://www.faqs.org/rfcs/rfc2617.html HTTP Authentication: Basic and Digest Access Authentication
	 *
	 * On some Apache servers PHP is running as CGI, meaning that Apache variables [code]$_SERVER['PHP_AUTH_USER'][/code]
	 * and [code]$_SERVER['PHP_AUTH_PW'][/code] are empty, and the credentials are not transmitted to the function.
	 *
	 * In this case you can try to modify the [code].htaccess[/code] file and add following text:
	 * [snippet]
	 * &lt;IfModule mod_rewrite.c&gt;
	 *	RewriteEngine on
	 *	RewriteRule .* - [E=REMOTE_USER:%{HTTP:Authorization},L]
	 * &lt;/IfModule&gt;
	 * [/snippet]
	 *
	 * This directive states, if [code]mod_rewrite[/code] is available, that credentials get from the HTTP header Authorization
	 * are put into the [code]$_SERVER['REMOTE_USER'][/code] variable.
	 *
	 * From there you can decode base64 credentials, and split the string to retrieve user name and password, as
	 * explained from RFC2617 on HTTP Authentication.
	 *
	 * This function does exactly that. If [code]$_SERVER['PHP_AUTH_USER'][/code] and [code]$_SERVER['PHP_AUTH_PW'][/code]
	 * are empty, it attempts to rebuild them by using [code]$_SERVER['REMOTE_USER'][/code].
	 * Of course, this works only if the [code]mod_rewrite[/code] module is available,
	 * and if you have changed the file [code].htaccess[/code] as explained above.
	 *
	 * @return array one user record matching credentials, or NULL
	 */
	public static function authenticate() {
		global $context;

		// surfer is already logged
		if(Surfer::is_logged())
			return NULL;

		// maybe we have caught authentication data in $_SERVER['REMOTE_USER'] -- see trick explained above
		if((!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']))
			&& isset($_SERVER['REMOTE_USER']) && preg_match('/Basic\s+(.*)$/i', $_SERVER['REMOTE_USER'], $matches)) {
			list($name, $password) = explode(':', base64_decode($matches[1]), 2);
			$_SERVER['PHP_AUTH_USER'] = strip_tags($name);
			$_SERVER['PHP_AUTH_PW'] = strip_tags($password);
		}

		// no credentials
		if(!isset($_SERVER['PHP_AUTH_USER']) || !$_SERVER['PHP_AUTH_USER'] || !isset($_SERVER['PHP_AUTH_PW']))
			return NULL;

		// use credentials
		if($user = Users::login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']))
			return $user;

		// log failing basic authentication
		if(is_callable(array('Logger', 'remember')))
			Logger::remember('users/users.php', 'Failed basic authentication', 'User: '.$_SERVER['PHP_AUTH_USER']."\n".'Password: '.$_SERVER['PHP_AUTH_PW']);

		// tough luck
		return NULL;
	}

	/**
	 * all presence icons for a surfer
	 *
	 * @param array user record
	 * @return string HTML tags
	 */
	public static function build_presence($item) {
		$contacts = array();

		// twitter
		if(isset($item['twitter_address']) && ($id = trim($item['twitter_address'])))
			$contacts[] = Skin::build_presence($id, 'twitter');

		// jabber
		if(isset($item['jabber_address']) && ($id = trim($item['jabber_address'])))
			$contacts[] = Skin::build_presence($id, 'jabber');

		// skype
		if(isset($item['skype_address']) && ($id = trim($item['skype_address'])))
			$contacts[] = Skin::build_presence($id, 'skype');

		// yahoo
		if(isset($item['yahoo_address']) && ($id = trim($item['yahoo_address'])))
			$contacts[] = Skin::build_presence($id, 'yahoo');

		// msn
		if(isset($item['msn_address']) && ($id = trim($item['msn_address'])))
			$contacts[] = Skin::build_presence($id, 'msn');

		// aim
		if(isset($item['aim_address']) && ($id = trim($item['aim_address'])))
			$contacts[] = Skin::build_presence($id, 'aim');

		// irc
		if(isset($item['irc_address']) && ($id = trim($item['irc_address'])))
			$contacts[] = Skin::build_presence($id, 'irc');

		// icq
		if(isset($item['icq_address']) && ($id = trim($item['icq_address'])))
			$contacts[] = Skin::build_presence($id, 'icq');

		return join(' ', $contacts);
	}

	/**
	 * change all user profiles at once
	 *
	 * @param mixed attributes to change
	 * @return boolean TRUE on success, FALSE otherwise
	 */
	public static function change_all($attributes) {
		global $context;

		// prepare change statement
		$query = array();
		foreach($attributes as $name => $value)
			$query[] = $name."='".SQL::escape($value)."'";

		// sanity check
		if(!count($query))
			return TRUE;

		// update all records
		$query = "UPDATE ".SQL::table_name('users')
			." SET ".join(',', $query);
		if(SQL::query($query, FALSE, $context['users_connection']) === FALSE)
			return FALSE;
		return TRUE;

	}

	/**
	 * check encoded credentials
	 *
	 * @param array credentials received from surfer
	 * @param string secret salted string
	 * @return boolen TRUE if credentials are ok, FALSE otherwise
	 */
	public static function check_credentials($credentials, $salt) {
		global $context;

		// not enough args
		if(!isset($credentials[3]))
			return FALSE;

		// the full string
		$computed = sprintf('%u', crc32($credentials[0].':'.$credentials[1].':'.$credentials[2].':'.$salt));

		// args have not been modified
		if(!strcmp($credentials[3], $computed))
			return TRUE;

		// sorry
		return FALSE;

	}

	/**
	 * clear cache entries for one item
	 *
	 * @param array item attributes
	 */
	public static function clear(&$item) {

		// where this item can be displayed
		$topics = array('users', 'categories');

		// clear this page
		if(isset($item['id']))
			$topics[] = 'user:'.$item['id'];

		// clear the cache
		Cache::clear($topics);

	}

	/**
	 * delete one user
	 *
	 * @param int the id of the user to delete
	 * @return boolean TRUE on success, FALSE otherwise
	 *
	 * @see users/delete.php
	 */
	public static function delete($id) {
		global $context;

		// load the record
		$item =& Users::get($id);
		if(!isset($item['id']) || !$item['id']) {
			Logger::error(i18n::s('No item has the provided id.'));
			return FALSE;
		}

		// delete related items
		Anchors::delete_related_to('user:'.$item['id']);

		// delete the record in the database
		$query = "DELETE FROM ".SQL::table_name('users')." WHERE id = ".SQL::escape($item['id']);
		if(SQL::query($query, FALSE, $context['users_connection']) === FALSE)
			return FALSE;

		// job done
		return TRUE;
	}

	/**
	 * get one user by id
	 *
	 * Actually this function locates one user profile by looking at: id, nick name, email address, and profile handle.
	 *
	 * @param mixed the id or nick name or e-mail address or secret handle of the user
	 * @param boolean TRUE to always fetch a fresh instance, FALSE to enable cache
	 * @return the resulting $item array, with at least keys: 'id', 'nick_name', 'full_name', 'description', etc.
	 *
	 * @see agents/messages/php
	 * @see articles/view.php
	 * @see comments/layout_comments_as_yabb.php
	 * @see links/edit.php
	 * @see services/blog.php
	 * @see shared/codes.php
	 * @see users/delete.php
	 * @see users/edit.php
	 * @see users/feed.php
	 * @see users/fetch_vcard.php
	 * @see users/mail.php
	 * @see users/password.php
	 * @see users/populate.php
	 * @see users/print.php
	 * @see users/user.php
	 * @see users/view.php
	 */
	public static function &get($id, $mutable=FALSE) {
		global $context;

		// sanity check
		if(!$id) {
			$output = NULL;
			return $output;
		}

		// ensure proper utf8 encoding
		$id = (string)$id;
		$id = utf8::encode($id);

		// strip extra text from enhanced ids '3-alfred' -> '3'
		if(preg_match('/^([0-9]+)-.+/', $id, $matches))
			$id = $matches[1];

		// cache previous answers
		static $cache;
		if(!is_array($cache))
			$cache = array();

		// cache hit, but only for immutable objects
		if(!$mutable && isset($cache[$id]))
			return $cache[$id];

		$query = array();

		if(strpos($id, '@'))
			$query[] = "(users.email LIKE '".SQL::escape($id)."')";
		elseif(preg_match('/[0-9a-fA-F]{32}/', $id))
			$query[] = "(users.handle LIKE '".SQL::escape($id)."')";
		elseif($int_value = intval($id))
			$query[] = "(users.id = ".SQL::escape($int_value).")";
		else
			$query[] = "(users.nick_name LIKE '".SQL::escape($id)."')";

		// select among available items -- exact match
		$query = "SELECT * FROM ".SQL::table_name('users')." AS users"
			." WHERE ".join(' OR ', $query)
			." LIMIT 1";
		$output = SQL::query_first($query, FALSE, $context['users_connection']);

		// ensure we have a full name
		if((!isset($output['full_name']) || !$output['full_name']) && isset($output['nick_name']))
			$output['full_name'] = $output['nick_name'];

		// user is present if active during last 10 minutes (10*60 = 600)
		if(isset($output['click_date']) && ($output['click_date'] >= gmstrftime('%Y-%m-%d %H:%M:%S', time()-600)))
			$output['is_present'] = TRUE;

		// user is not present
		elseif(isset($output['id']))
			$output['is_present'] = FALSE;

		// save in cache
		if(isset($output['id']))
			$cache[ $output['id'] ] = $output;

		// return by reference
		return $output;
	}

	/**
	 * get the unique handle associated to a user profile
	 *
	 * @param int or string the id or nick name of the user
	 * @return the associated handle, or NULL if no record matches the input parameter
	 */
	public static function &get_handle($id) {
		global $context;

		// sanity check
		if(!$id) {
			$output = NULL;
			return $output;
		}

		// ensure proper unicode encoding
		$id = (string)$id;
		$id = utf8::encode($id);

		// cache previous answers
		static $cache;
		if(!is_array($cache))
			$cache = array();

		// cache hit
		if(isset($cache[$id]))
			return $cache[$id];

		// search by id or nick name
		$query = "SELECT handle FROM ".SQL::table_name('users')." AS users"
			." WHERE (users.id = ".SQL::escape((integer)$id).") OR (users.nick_name LIKE '".SQL::escape($id)."')"
			." ORDER BY edit_date DESC LIMIT 1";

		// do the job
		$output = SQL::query_scalar($query, FALSE, $context['users_connection']);

		// save in cache
		$cache[$id] = $output;

		// return by reference
		return $output;
	}

	/**
	 * build a pretty link to a user page
	 *
	 * YACS has been designed to track people who submit or change information, and this small function
	 * is aiming to shape tracking data.
	 *
	 * Most of the time it will build a nice link to the user profile of the involved community member.
	 * At other time it will link to the external server that has provided published information.
	 *
	 * Pseudo code:
	 * [snippet]
	 * If there is a user profile for this id
	 *	 return a link to the related page
	 * else if the user is not logged and if email addresses have to be protected
	 *	 return the name string without any link (protected from spam)
	 * else if a web address has been provided
	 *	 return a http: link to it
	 * else if an email address has been provided
	 *	 return a mailto: link to it
	 * else
	 *	 return the name string without any link
	 * [/snippet]
	 *
	 * @param string the user name
	 * @param string the email address, or a web address
	 * @param string the user id
	 * @param boolean TRUE to open the link in a new window, FALSE otherwise
	 * @param string an optional hovering label
	 * @return a pretty link to insert in the HTML page
	 *
	 * @see feeds/feeds.php
	 */
	function get_link($name, $email, $id, $new_window=FALSE, $hover=NULL) {
		global $context;

		if(!$name)
			$name = i18n::s('(unknown)');

		$name = ucfirst($name);

		if(($id > 0) && ($url = Users::get_url($id, 'view', $name)))
			return Skin::build_link($url, $name, 'user', $hover, $new_window);
		elseif(!Surfer::may_mail())
			return $name;
		elseif(preg_match('/@/', $email))
			return Skin::build_link($email, $name, 'email', $hover);
		elseif(preg_match('/[:\/]/', $email))
			return Skin::build_link($email, $name, NULL, $hover);
		else
			return $name;
	}

	/**
	 * package credentials to be passed in a link
	 *
	 * @param string action (e.g., 'visit', see users/login.php)
	 * @param string reference to target (e.g., 'section:123')
	 * @param string name or e-mail address
	 * @param string salt to be used for the hash
	 * @return string the link to be authenticated
	 */
	function get_login_url($command, $reference, $name, $salt) {
		global $context;

		// build a signed
		$credentials = array();
		$credentials[0] = $command;
		$credentials[1] = $reference;
		$credentials[2] = $name;
		$credentials[3] = sprintf('%u', crc32($command.':'.$reference.':'.$name.':'.$salt));

		// we prefer JSON over PHP serialization
		if(!$serialized = Safe::json_encode($credentials))
			$serialized = serialize($credentials);

		// finalize the snippet
		$id = base64_encode($serialized);

		// be cool with search engines
		if($context['with_friendly_urls'] == 'Y')
			return 'users/login.php/'.rawurlencode($id);
		elseif($context['with_friendly_urls'] == 'R')
			return 'users/login.php/'.rawurlencode($id);
		else
			return 'users/login.php?credentials='.urlencode($id);

	}

	/**
	 * get permanent address
	 *
	 * @param array page attributes
	 * @return string the permalink
	 */
	function get_permalink($item) {
		$output = Users::get_url($item['id'], 'view', isset($item['full_name'])?$item['full_name']:( isset($item['nick_name'])?$item['nick_name']:'' ));
		return $output;
	}

	/**
	 * get signature of some user
	 *
	 * @param int user id
	 * @param string his signature, or ''
	 */
	function get_signature($id) {
		global $context;

		if(!$id)
			return '';

		// optimize repeated queries
		static $cache;
		if(!isset($cache))
			$cache = array();

		// we already found this one
		if(isset($cache[$id]))
			return $cache[$id];
		$cache[$id] = '';

		// lookup for this user
		if(($user =& Users::get($id)) && trim($user['signature']))
			$cache[$id] = "\n\n-----\n".$user['signature'];

		// return the cached value
		return $cache[$id];
	}

	/**
	 * build a reference to a user
	 *
	 * Depending on parameter '[code]with_friendly_urls[/code]' and on action,
	 * following results can be observed:
	 *
	 * - view - users/view.php?id=123 or users/view.php/123 or user-123
	 *
	 * - other - users/edit.php?id=123 or users/edit.php/123 or user-edit/123
	 *
	 * @param int the id of the user to handle
	 * @param string the expected action ('view', 'print', 'edit', 'delete', ...)
	 * @param string user name
	 * @return string a normalized reference
	 *
	 * @see control/configure.php
	 */
	function get_url($id, $action='view', $name=NULL) {
		global $context;

		// track something -- the id has to be an anchor (e.g., 'article:15')
		if($action == 'track') {
			if($context['with_friendly_urls'] == 'Y')
				return 'users/track.php/'.str_replace(':', '/', $id);
			elseif($context['with_friendly_urls'] == 'R')
				return 'users/track.php/'.str_replace(':', '/', $id);
			else
				return 'users/track.php?anchor='.urlencode($id);
		}

		// assign users to an anchor
		if($action == 'select')
			return 'users/select.php?member='.urlencode($id);

		// list watchers
		if($action == 'watch')
			return 'users/select.php?anchor='.urlencode($id);

		// check the target action
		if(!preg_match('/^(contact|delete|describe|edit|element|feed|fetch_vcard|leave|mail|navigate|password|print|select_avatar|share|transfer|validate|view|visit)$/', $action))
			return 'users/'.$action.'.php?id='.urlencode($id).'&action='.urlencode($name);

// 		// view user profile --use only the nick name, since it is unique
// 		if(($action == 'view') && $name) {
// 			$id = $name;
// 			$name = '';
// 		}

		// normalize the link
		return normalize_url(array('users', 'user'), $action, $id, $name);
	}

	/**
	 * increment the posts counter - errors are not reported, if any
	 *
	 * This function increment the number of posts, and record the date of the last post
	 *
	 * @param the id of the user to update
	 *
	 * @see actions/edit.php
	 * @see articles/edit.php
	 * @see categories/edit.php
	 * @see comments/edit.php
	 * @see files/edit.php
	 * @see images/edit.php
	 * @see links/edit.php
	 * @see locations/edit.php
	 * @see sections/edit.php
	 * @see servers/edit.php
	 * @see services/blog.php
	 * @see tables/edit.php
	 */
	function increment_posts($id) {
		global $context;

		// sanity check
		if(!$id)
			return;

		// do the job
		$query = "UPDATE ".SQL::table_name('users')
			." SET post_date='".gmstrftime('%Y-%m-%d %H:%M:%S')."', posts=posts+1"
			." WHERE id = ".$id;
		SQL::query($query, FALSE, $context['users_connection']);

		// clear the cache for users
		Cache::clear(array('user:'.$id, 'users'));
	}

	/**
	 * list associates
	 *
	 * Note that compared to [code]list_by_posts()[/code], this function lists all associates,
	 * even those who haven't subscribed to newsletters.
	 *
	 * Example:
	 * [php]
	 * $items = Users::list_associates_by_posts(0, 10);
	 * $context['text'] .= Skin::build_list($items, 'compact');
	 * [/php]
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see letters/new.php
	 */
	function &list_associates_by_posts($offset=0, $count=10, $variant='compact') {
		global $context;

		// limit the scope of the request
		$where = "users.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR users.active='R'";
		if(Surfer::is_associate())
			$where .= " OR users.active='N'";

		// the list of users
		$query = "SELECT * FROM ".SQL::table_name('users')." AS users"
			." WHERE (users.capability='A') AND (".$where.")"
			." ORDER BY users.posts DESC, users.nick_name"
			." LIMIT ".$offset.','.$count;

		$output =& Users::list_selected(SQL::query($query, FALSE, $context['users_connection']), $variant);
		return $output;
	}

	/**
	 * list newest members
	 *
	 * The list is ordered by creation date rather than by edition date to better reflect
	 * the 'oldiness' of members.
	 *
	 * Example:
	 * [php]
	 * $items = Users::list_by_date(0, 10);
	 * $context['text'] .= Skin::build_list($items, 'compact');
	 * [/php]
	 *
	 * You can also display the newest user separately, using [code]Users::get_newest()[/code]
	 * In this case, skip the very first user in the list by using
	 * [code]Users::list_by_date(1, 10)[/code]
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see users/index.php
	 * @see users/review.php
	 */
	function &list_by_date($offset=0, $count=10, $variant='compact') {
		global $context;

		// limit the scope of the request
		$where = "users.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR users.active='R'";
		if(Surfer::is_associate())
			$where .= " OR users.active='N'";

		// the list of users
		$query = "SELECT * FROM ".SQL::table_name('users')." AS users"
			." WHERE ".$where
			." ORDER BY users.create_date DESC, users.nick_name LIMIT ".$offset.','.$count;

		$output =& Users::list_selected(SQL::query($query, FALSE, $context['users_connection']), $variant);
		return $output;
	}

	/**
	 * list inactive members
	 *
	 * The list is ordered by login date.
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see users/review.php
	 */
	function &list_by_login_date($offset=0, $count=10, $variant='dates') {
		global $context;

		// limit the scope of the request
		$where = "users.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR users.active='R'";
		if(Surfer::is_associate())
			$where .= " OR users.active='N'";

		// the list of users
		$query = "SELECT * FROM ".SQL::table_name('users')." AS users"
			." WHERE (".$where.") AND (users.login_date > '2000-01-01')"
			." ORDER BY users.login_date, users.nick_name LIMIT ".$offset.','.$count;

		$output =& Users::list_selected(SQL::query($query, FALSE, $context['users_connection']), $variant);
		return $output;
	}

	/**
	 * list users by name
	 *
	 * To build a simple box of the users in your main index page, just use
	 * [code]Users::list_by_name(0, 10)[/code]
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_by_name($offset=0, $count=10, $variant='full') {
		global $context;

		// limit the scope of the request
		$where = "users.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR users.active='R'";
		if(Surfer::is_associate())
			$where .= " OR users.active='N'";

		// the list of users
		$query = "SELECT * FROM ".SQL::table_name('users')." AS users"
			." WHERE ".$where
			." ORDER BY users.nick_name, users.edit_date DESC LIMIT ".$offset.','.$count;

		$output =& Users::list_selected(SQL::query($query, FALSE, $context['users_connection']), $variant);
		return $output;
	}

	/**
	 * list inactive members
	 *
	 * The list is ordered by post date.
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see users/review.php
	 */
	function &list_by_post_date($offset=0, $count=10, $variant='dates') {
		global $context;

		// limit the scope of the request
		$where = "users.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR users.active='R'";
		if(Surfer::is_associate())
			$where .= " OR users.active='N'";
		$where = '('.$where.')';

		// the list of users
		$query = "SELECT * FROM ".SQL::table_name('users')." AS users"
			." WHERE ".$where." AND (users.post_date > '2000-01-01')"
			." ORDER BY users.post_date, users.nick_name LIMIT ".$offset.','.$count;

		$output =& Users::list_selected(SQL::query($query, FALSE, $context['users_connection']), $variant);
		return $output;
	}

	/**
	 * list most contributing users
	 *
	 * Profiles are sorted by decreasing number of posts and decreasing edition dates
	 *
	 * Example:
	 * [php]
	 * $items = Users::list_by_posts(0, 10);
	 * $context['text'] .= Skin::build_list($items, 'compact');
	 * [/php]
	 *
	 * Only users matching following criteria are returned:
	 * - user is visible (active='Y')
	 * - user is restricted (active='R'), but surfer is a logged member
	 * - user is restricted (active='N'), but surfer is an associate
	 *
	 * If the variant is 'address', then users who have not subscribed explicitly to newsletters
	 * won't be listed.
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see letters/new.php
	 * @see users/index.php
	 */
	function &list_by_posts($offset=0, $count=10, $variant='compact') {
		global $context;

		// limit the scope of the request
		$where = "users.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR users.active='R'";
		if(Surfer::is_associate())
			$where .= " OR users.active='N'";
		$where = '('.$where.')';

		// protect the privacy of e-mail boxes and never send messages to locked users
		if($variant == 'address')
			$where .= " AND (users.with_newsletters='Y') AND (users.capability != '?')";

		// the list of users
		$query = "SELECT * FROM ".SQL::table_name('users')." AS users"
			." WHERE ".$where
			." ORDER BY users.posts DESC, users.edit_date DESC LIMIT ".$offset.','.$count;

		$output =& Users::list_selected(SQL::query($query, FALSE, $context['users_connection']), $variant);
		return $output;
	}

	/**
	 * list members
	 *
	 * Example:
	 * [php]
	 * $items = Users::list_members_by_posts(0, 10);
	 * $context['text'] .= Skin::build_list($items, 'compact');
	 * [/php]
	 *
	 * If the variant is 'address', then users who have not subscribed explicitly to newsletters
	 * won't be listed.
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see letters/new.php
	 */
	function &list_members_by_posts($offset=0, $count=10, $variant='compact') {
		global $context;

		// limit the scope of the request
		$where = "users.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR users.active='R'";
		if(Surfer::is_associate())
			$where .= " OR users.active='N'";

		// protect the privacy of e-mail boxes
		if($variant == 'address')
			$where = '('.$where.') AND (users.with_newsletters=\'Y\')';

		// the list of users
		$query = "SELECT * FROM ".SQL::table_name('users')." AS users"
			." WHERE ((users.capability='A') OR (users.capability='M')) AND (".$where.")"
			." ORDER BY users.posts DESC, users.nick_name"
			." LIMIT ".$offset.','.$count;

		$output =& Users::list_selected(SQL::query($query, FALSE, $context['users_connection']), $variant);
		return $output;
	}

	/**
	 * list present members
	 *
	 * The list is ordered by date of last click, and is limited to users
	 * that have clicked during the last 15 minutes.
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see users/index.php
	 */
	function &list_present($offset=0, $count=10, $variant='compact') {
		global $context;

		// limit the scope of the request
		$where = "users.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR users.active='R'";
		if(Surfer::is_associate())
			$where .= " OR users.active='N'";

		// present means 'a click not too long in the past'
		$threshold = gmstrftime('%Y-%m-%d %H:%M:%S', time()-15*60);
		$where = "(".$where.") AND (click_date > '".$threshold."')";

		// the list of users
		$query = "SELECT * FROM ".SQL::table_name('users')." AS users"
			." WHERE ".$where
			." ORDER BY users.click_date DESC, users.nick_name LIMIT ".$offset.','.$count;

		$output =& Users::list_selected(SQL::query($query, FALSE, $context['users_connection']), $variant);
		return $output;
	}

	/**
	 * list selected users
	 *
	 * If variant is provided as a string, the functions looks for a script featuring this name.
	 * E.g., for variant 'compact', the file 'users/layout_users_as_compact.php' is loaded.
	 * If no file matches then the default 'users/layout_users.php' script is loaded.
	 *
	 * @param resource result of database query
	 * @param string 'full', etc or object, i.e., an instance of Layout_Interface
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 */
	function &list_selected(&$result, $variant='compact') {
		global $context;

		// no result
		if(!$result) {
			$output = NULL;
			return $output;
		}

		// special layout
		if(is_object($variant)) {
			$output = $variant->layout($result);
			return $output;
		}

		// no layout yet
		$layout = NULL;

		// separate options from layout name
		$attributes = explode(' ', $variant, 2);

		// instanciate the provided name
		if($attributes[0]) {
			$name = 'layout_users_as_'.$attributes[0];
			if(is_readable($context['path_to_root'].'users/'.$name.'.php')) {
				include_once $context['path_to_root'].'users/'.$name.'.php';
				$layout = new $name;

				// provide parameters to the layout
				if(isset($attributes[1]))
					$layout->set_variant($attributes[1]);

			}
		}

		// use default layout
		if(!$layout) {
			include_once $context['path_to_root'].'users/layout_users.php';
			$layout = new Layout_users();
			$layout->set_variant($variant);
		}

		// do the job
		$output = $layout->layout($result);
		return $output;

	}

	/**
	 * login
	 *
	 * The script checks provided name and password against the local database.
	 *
	 * If no record matches, and if the provided name explicitly mentions some origin server
	 * (e.g., 'john@foo.bar'), then this server is asked to authenticate the user.
	 * This is done by transmitting the user name and the password to the origin server,
	 * through a XML-RPC call ([code]drupal.login[/code] at [script]services/xml_rpc.php[/script]).
	 * On success the origin server will provide the original id for the user profile.
	 * Else a null id will be returned.
	 *
	 * On successful remote authentication the surfer will be considered as logged, either
	 * as an associate, a member (default case), or as a subscriber (for closed communities).
	 *
	 * On successful remote authentication a 'shadow' user profile will be created locally,
	 * using another id, and a copy of the authentication url saved in the password field.
	 * Also the user description explicitly references the original user profile.
	 * This local record may be referenced in pages published locally.
	 *
	 * This means that on subsequent visits the 'shadow' profile will be retrieved, and the origin
	 * server will be sollicitated again for credentials validation.
	 * As a consequence the validity of login data is always checked by the server that actually
	 * stores the original user profile.
	 * If the user profile is modified or is deleted this change will be taken into account on next login.
	 *
	 * @link http://drupal.org/node/312 Using distributed authentication (drupal.org)
	 *
	 * This script also allows for a last resort password.
	 * When a webmaster has lost his password, and if there is no other associate to help,
	 * he can modify manually the file [code]parameters/control.include.php[/code] to add
	 * a parameter [code]$context['last_resort_password'][/code], followed by a long passphrase
	 * of at least seven characters. For example:
	 * [php]
	 * $context['last_resort_password'] = 'a quite long passphrase, to be used 1 time';
	 * [/php]
	 *
	 * Then he can authenticate normally, using this password, and any name.
	 *
	 * On successful login the returned array contains following named atributes:
	 * - id - record in the table of users (can be a shadow record)
	 * - nick_name - name to be displayed  in user menu, and server messages
	 * - email - user e-mail address, if any
	 * - capability - either 'A'ssociate, 'M'ember, 'S'ubscriber or '?'
	 *
	 * @param string the nickname or the email address of the user
	 * @param string the submitted password
	 * @return the record of the authenticated surfer, or NULL
	 *
	 * @see users/login.php
	 * @see services/blog.php
	 */
	function login($name, $password) {
		global $context;

		// using the last resort password
		if(isset($context['last_resort_password']) && (strlen(trim($context['last_resort_password'])) >= 1) && ($password == $context['last_resort_password'])) {

			// this is an event to remember
			Logger::remember('users/users.php', i18n::c('lrp has logged in'), i18n::c('Login using the last resort password'));

			// a fake associate
			$user = array();
			$user['id'] = 1;
			$user['nick_name'] = 'lrp';
			$user['email'] = '';
			$user['capability'] = 'A';
			return $user;

		}

		// user has not been authenticated yet
		$authenticated = FALSE;
		$item = NULL;

		// search a user profile locally
		$query = "SELECT * FROM ".SQL::table_name('users')." AS users"
			." WHERE users.email LIKE '".SQL::escape($name)."' OR users.nick_name LIKE '".SQL::escape($name)."' OR users.full_name LIKE '".SQL::escape($name)."'";
		if(isset($context['users_connection']) && ($item = SQL::query_first($query, FALSE, $context['users_connection']))) {

			// the user has been explicitly locked
			if($item['capability'] == '?')
				return NULL;

			// more than three failed authentications during previous hour
			elseif(($item['authenticate_failures'] >= 3) && ($item['authenticate_date'] > gmstrftime('%Y-%m-%d %H:%M:%S', time()-3600))) {
				Logger::error(i18n::s('Wait for one hour to recover from too many failed authentications.'));
				return NULL;

			// successful local check
			} elseif(md5($password) == $item['password'])
				$authenticated = TRUE;

		}

		// we have to authenticate externally, if this has been explicitly allowed
		if(!$authenticated && isset($context['users_authenticator']) && $context['users_authenticator']) {

			// load and configure an authenticator instance
			include_once $context['path_to_root'].'users/authenticator.php';
			if(!$authenticator = Authenticator::bind($context['users_authenticator']))
				return NULL;

			// submit full name to authenticator
			if(isset($item['full_name']) && trim($item['full_name']) && $authenticator->login($item['full_name'], $password))
				$authenticated = TRUE;

			// submit credentials to authenticator
			elseif($authenticator->login($name, $password))
				$authenticated = TRUE;

		}

		// we have to create a shadow record
		if($authenticated && !isset($item['id'])) {

			// shadow record
			$fields = array();
			$fields['nick_name'] = $name;
			$fields['description'] = i18n::s('Authenticated externally.');
			$fields['password'] = 'shadow';
			$fields['with_newsletters'] = 'Y';
			$fields['without_alerts'] = 'N';
			$fields['without_confirmations'] = 'N';
			$fields['authenticate_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
			$fields['authenticate_failures'] = 0;

			// stop on error
			if(!$fields['id'] = Users::post($fields))
				return NULL;

			// retrieve the shadow record
			$item =& Users::get($fields['id']);
		}

		// bad credentials
		if(!$authenticated && isset($item['id'])) {

			// increment failing authentications during last hour
			if(isset($item['authenticate_date']) && ($item['authenticate_date'] >= gmstrftime('%Y-%m-%d %H:%M:%S', time()-3600))) {

				$query = "UPDATE ".SQL::table_name('users')
					." SET authenticate_failures=authenticate_failures+1"
					." WHERE id = ".$item['id'];

				if($item['authenticate_failures'] >= 2)
					Logger::error(i18n::s('Wait for one hour to recover from too many failed authentications.'));
				elseif($item['authenticate_failures'] == 1)
					Logger::error(i18n::s('You have 1 grace authentication attempt.'));

			// first failure in a row
			} else {
				$query = "UPDATE ".SQL::table_name('users')
					." SET authenticate_date = '".gmstrftime('%Y-%m-%d %H:%M:%S')."'"
					.", authenticate_failures=1"
					." WHERE id = ".$item['id'];

				Logger::error(i18n::s('You have 2 grace authentication attempts.'));
			}

			// update target record
			SQL::query($query, FALSE, $context['users_connection']);

			// no user record is returned
			return NULL;
		}

		// not authenticated, or no record
		if(!$authenticated || !isset($item['id']))
			return NULL;

		// generate a random handle if necessary
		$handle = '';
		if(!isset($item['handle']) || !$item['handle']) {
			$item['handle'] = md5(rand());
			$handle = ", handle='".$item['handle']."' ";
		}

		// remember silently the date of the last login, and reset authentication counter
		$query = "UPDATE ".SQL::table_name('users')
			." SET login_date='".gmstrftime('%Y-%m-%d %H:%M:%S')."'"
			.", login_address='".$_SERVER['REMOTE_ADDR']."'"
			.", authenticate_date='".gmstrftime('%Y-%m-%d %H:%M:%S')."'"
			.", authenticate_failures=0"
			.$handle
			." WHERE id = ".$item['id'];
		SQL::query($query, FALSE, $context['users_connection']);

		// valid user - date of previous login is transmitted as well
		return $item;
	}

	/**
	 * get the id of one user knowing its name or mail address
	 *
	 * @param string the name looked for, or a mail address, or a complex RFC 822 recipient address
	 * @return array either the found profile, or NULL
	 */
	function lookup($name) {
		global $context;

		// the profile already exists
		if($item =& Users::get($name))
			return $item;

		// guess a shadow profile
		$user = array();

		// analyze a RFC 822 recipient address: foo@acme.com or "John Foo" <foo@acme.com>
		$index_maximum = strlen($name);
		$quoted = FALSE;
		$head = $dot = $middle = $tail = 0;
		for($index = 0; $index < $index_maximum; $index++) {

			// start quoted string
			if(!$quoted && ($name[$index] == '"'))
				$quoted = TRUE;

			// end of quoted string
			elseif($quoted && ($name[$index] == '"'))
				$quoted = FALSE;

			// start of mail address
			elseif(!$quoted && ($name[$index] == '<') && !$head)
				$head = $index;

			// dot between names
			elseif(!$quoted && ($name[$index] == '.') && !$dot && !$middle)
				$dot = $index;

			// middle of mail address
			elseif(!$quoted && ($name[$index] == '@') && !$middle)
				$middle = $index;

			// end of mail address
			elseif(!$quoted && ($name[$index] == '>') && !$tail)
				$tail = $index;

		}

		// we don't create a profile if there is no e-mail address
		if(!$middle)
			return NULL;

		// complex case: "John Foo" <foo@acme.com>
		if($head && ($tail > $head+1)) {
			$user['email'] = substr($name, $head+1, $tail-$head-1);
			$user['full_name'] = trim(substr($name, 0, $head), ' "');
			if($dot > $head)
				$user['nick_name'] = substr($name, $head+1, $dot-$head-1);
			else
				$user['nick_name'] = substr($name, $head+1, $middle-$head-1);

		// just a recipient address: foo@acme.com
		} else {
			$user['email'] = $name;
			$user['full_name'] = substr($name, 0, $middle);
			if($dot)
				$user['nick_name'] = substr($name, 0, $dot);
			else
				$user['nick_name'] = substr($name, 0, $middle);
		}

		// the e-mail address already exists
		if($item =& Users::get($user['email']))
			return $item;

		// add a random number of 4 digits to make nick name as unique as possible
		$pool = '123456789';
		$user['nick_name'] .= $pool[mt_rand(0, strlen($pool)-1)]
			.$pool[mt_rand(0, strlen($pool)-1)]
			.$pool[mt_rand(0, strlen($pool)-1)]
			.$pool[mt_rand(0, strlen($pool)-1)];

		// create a short password with only numbers, like a PIN code
		$pool = '123456789';
		$user['password'] = $pool[mt_rand(0, strlen($pool)-1)]
			.$pool[mt_rand(0, strlen($pool)-1)]
			.$pool[mt_rand(0, strlen($pool)-1)]
			.$pool[mt_rand(0, strlen($pool)-1)];

		// create this fake profile
		if(!Users::post($user))
			return NULL;

		// do the check again
		if($item =& Users::get($user['nick_name']))
			return $item;

		// tough luck
		return NULL;
	}

	/**
	 * post a new user profile
	 *
	 * @param array an array of fields
	 * @return the id of the new user profile, or FALSE on error
	 *
	 * @see control/populate.php
	 * @see users/edit.php
	 * @see users/populate.php
	 * @see query.php
	**/
	function post(&$fields) {
		global $context;

		// nick_name is required
		if(!isset($fields['nick_name']) || !trim($fields['nick_name'])) {
			Logger::error(i18n::s('Please indicate a nick name.'));
			return FALSE;
		}

		// some weird users put spaces around
		$fields['nick_name'] = trim($fields['nick_name']);

		// names used on shadow records are quite long (eg, tom@foo.bar.com)
		if(preg_match('/^(.+)@(.+)$/', $fields['nick_name'], $matches)) {

			// if short name is free
			if(!Users::get($matches[1]))

				// use it instead (eg, tom)
				$fields['nick_name'] = $matches[1];
		}

		// nickname may be already used
		if(Users::get($fields['nick_name'])) {
			Logger::error(i18n::s('Another member already has this nick name. Please select a different one.'));
			return FALSE;
		}

		// ensure we have a full name
		if(!isset($fields['full_name']) || !trim($fields['full_name']))
			$fields['full_name'] = $fields['nick_name'];

		// password is required
		if(!isset($fields['password']) || !trim($fields['password'])) {
			Logger::error(i18n::s('Please indicate a password.'));
			return FALSE;
		}

		// hash password if coming from a human facing a form
		if(isset($fields['confirm']) && ($fields['confirm'] == $fields['password']))
			$fields['password'] = md5($fields['password']);

		// open community, accept subscribers and members
		if(!isset($fields['capability']) || !in_array($fields['capability'], array('A', 'M', 'S', '?')))
			$fields['capability'] = 'M';

		// control user capability
		if(!Surfer::is_associate()) {

			// closed community, accept only subscribers
			if(isset($context['users_with_approved_members']) && ($context['users_with_approved_members'] == 'Y'))
				$fields['capability'] = 'S';

			// email addresses have to be validated
			elseif(isset($context['users_with_email_validation']) && ($context['users_with_email_validation'] == 'Y'))
				$fields['capability'] = 'S';

		}

		// remember who is changing this record
		Surfer::check_default_editor($fields);

		// save new settings in session and in cookie
		if(isset($fields['id']) && Surfer::is($fields['id'])) {

			// change preferred editor
			$_SESSION['surfer_editor'] = $fields['editor'];
			Safe::setcookie('surfer_editor', $fields['editor'], NULL, '/');

			// change preferred language
			if(isset($fields['language']) && ($_SESSION['surfer_language'] != $fields['language'])) {
				$_SESSION['surfer_language'] = $fields['language'];
				$_SESSION['l10n_modules'] = array();
			}
		}


		// fields to update
		$query = array();

		// on import
		if(isset($fields['id']))
			$query[] = "id=".SQL::escape($fields['id']);

		if(!isset($fields['active']) || !trim($fields['active']))
			$fields['active'] = 'Y';
		$query[] = "active='".SQL::escape($fields['active'])."'";

		$query[] = "aim_address='".SQL::escape(isset($fields['aim_address']) ? $fields['aim_address'] : '')."'";
		$query[] = "alternate_number='".SQL::escape(isset($fields['alternate_number']) ? $fields['alternate_number'] : '')."'";

		// protect from hackers
		if(isset($fields['avatar_url']))
			$fields['avatar_url'] =& encode_link($fields['avatar_url']);
		$query[] = "avatar_url='".SQL::escape(isset($fields['avatar_url']) ? $fields['avatar_url'] : '')."'";

		if(!isset($fields['birth_date']) || !$fields['birth_date'])
			$fields['birth_date'] = NULL_DATE;
		$query[] = "birth_date='".SQL::escape($fields['birth_date'])."'";

		$query[] = "capability='".SQL::escape($fields['capability'])."'";

		$query[] = "create_name='".SQL::escape(isset($fields['create_name']) ? $fields['create_name'] : $fields['edit_name'])."'";

		if(isset($fields['create_id']) || $fields['edit_id'])
			$query[] = "create_id=".SQL::escape(isset($fields['create_id']) ? $fields['create_id'] : $fields['edit_id']);

		$query[] = "create_address='".SQL::escape(isset($fields['create_address']) ? $fields['create_address'] : $fields['edit_address'])."'";

		if(!isset($fields['create_date']) || ($fields['create_date'] <= NULL_DATE))
			$fields['create_date'] = $fields['edit_date'];
		$query[] = "create_date='".SQL::escape($fields['create_date'])."'";
		$query[] = "description='".SQL::escape(isset($fields['description']) ? $fields['description'] : '')."'";
		$query[] = "edit_name='".SQL::escape($fields['edit_name'])."'";
		$query[] = "edit_id=".SQL::escape($fields['edit_id']);
		$query[] = "edit_address='".SQL::escape($fields['edit_address'])."'";
		$query[] = "edit_action='".SQL::escape(isset($fields['edit_action']) ? $fields['edit_action'] : 'new')."'";
		$query[] = "edit_date='".SQL::escape($fields['edit_date'])."'";

		if(isset($fields['selected_editor']))
			$fields['editor'] = $fields['selected_editor'];	// hack because of FCKEditor already uses 'editor'
		elseif(isset($context['users_default_editor']))
			$fields['editor'] = $context['users_default_editor'];
		else
			$fields['editor'] = 'yacs';
		$query[] = "editor='".SQL::escape($fields['editor'])."'";

		$query[] = "email='".SQL::escape(isset($fields['email']) ? $fields['email'] : '')."'";

		$query[] = "from_where='".SQL::escape(isset($fields['from_where']) ? $fields['from_where'] : '')."'";
		$query[] = "full_name='".SQL::escape(isset($fields['full_name']) ? $fields['full_name'] : '')."'";

		// always create a handle for this user
		$fields['handle'] = md5(rand());
		$query[] = "handle='".SQL::escape($fields['handle'])."'";

		$query[] = "icq_address='".SQL::escape(isset($fields['icq_address']) ? $fields['icq_address'] : '')."'";

		if(!isset($fields['interface']) || ($fields['interface'] != 'C'))
			$fields['interface'] = 'I';
		$query[] = "interface='".SQL::escape($fields['interface'])."'";

		$query[] = "introduction='".SQL::escape(isset($fields['introduction']) ? $fields['introduction'] : '')."'";
		$query[] = "irc_address='".SQL::escape(isset($fields['irc_address']) ? $fields['irc_address'] : '')."'";
		$query[] = "jabber_address='".SQL::escape(isset($fields['jabber_address']) ? $fields['jabber_address'] : '')."'";
		$query[] = "language='".SQL::escape(isset($fields['language']) ? $fields['language'] : '')."'";
		$query[] = "msn_address='".SQL::escape(isset($fields['msn_address']) ? $fields['msn_address'] : '')."'";
		$query[] = "nick_name='".SQL::escape($fields['nick_name'])."'";
		$query[] = "options='".SQL::escape(isset($fields['options']) ? $fields['options'] : '')."'";
		$query[] = "overlay='".SQL::escape(isset($fields['overlay']) ? $fields['overlay'] : '')."'";
		$query[] = "overlay_id='".SQL::escape(isset($fields['overlay_id']) ? $fields['overlay_id'] : '')."'";
		$query[] = "password='".SQL::escape(isset($fields['password']) ? $fields['password'] : '')."'";
		$query[] = "pgp_key='".SQL::escape(isset($fields['pgp_key']) ? $fields['pgp_key'] : '')."'";
		$query[] = "phone_number='".SQL::escape(isset($fields['phone_number']) ? $fields['phone_number'] : '')."'";

		if(!isset($fields['post_date']) || ($fields['post_date'] <= NULL_DATE))
			$fields['post_date'] = $fields['edit_date'];
		$query[] = "post_date='".SQL::escape($fields['post_date'])."'";

		$query[] = "posts=".SQL::escape(isset($fields['posts']) ? $fields['posts'] : '0');
		$query[] = "signature='".SQL::escape(isset($fields['signature']) ? $fields['signature'] : '')."'";
		$query[] = "skype_address='".SQL::escape(isset($fields['skype_address']) ? $fields['skype_address'] : '')."'";

		// clean provided tags
		if(isset($fields['tags']))
			$fields['tags'] = trim($fields['tags'], " \t.:,!?");
		$query[] = "tags='".SQL::escape(isset($fields['tags']) ? $fields['tags'] : '')."'";

		$query[] = "twitter_address='".SQL::escape(isset($fields['twitter_address']) ? $fields['twitter_address'] : '')."'";
		$query[] = "vcard_agent='".SQL::escape(isset($fields['vcard_agent']) ? $fields['vcard_agent'] : '')."'";
		$query[] = "vcard_label='".SQL::escape(isset($fields['vcard_label']) ? $fields['vcard_label'] : '')."'";
		$query[] = "vcard_organization='".SQL::escape(isset($fields['vcard_organization']) ? $fields['vcard_organization'] : '')."'";
		$query[] = "vcard_title='".SQL::escape(isset($fields['vcard_title']) ? $fields['vcard_title'] : '')."'";
		$query[] = "web_address='".SQL::escape(isset($fields['web_address']) ? $fields['web_address'] : '')."'";

		if(!isset($fields['with_newsletters']) || ($fields['with_newsletters'] != 'N'))
			$fields['with_newsletters'] = 'Y';
		$query[] = "with_newsletters='".$fields['with_newsletters']."'";

		if(!isset($fields['without_alerts']) || ($fields['without_alerts'] != 'Y'))
			$fields['without_alerts'] = 'N';
		$query[] = "without_alerts='".$fields['without_alerts']."'";

		if(!isset($fields['without_confirmations']) || ($fields['without_confirmations'] != 'Y'))
			$fields['without_confirmations'] = 'N';
		$query[] = "without_confirmations='".$fields['without_confirmations']."'";

		if(!isset($fields['without_messages']) || ($fields['without_messages'] != 'Y'))
			$fields['without_messages'] = 'N';
		$query[] = "without_messages='".$fields['without_messages']."'";

		$query[] = "yahoo_address='".SQL::escape(isset($fields['yahoo_address']) ? $fields['yahoo_address'] : '')."'";

		// insert statement
		$query = "INSERT INTO ".SQL::table_name('users')." SET ".implode(', ', $query);

		// actual insert
		if(SQL::query($query, FALSE, $context['users_connection']) === FALSE)
			return FALSE;

		// remember the id of the new item
		if(!$fields['id'] = SQL::get_last_id($context['users_connection'])) {
			logger::remember('users/users.php', 'unable to retrieve id of new record');
			return FALSE;
		}

		// list the user in categories
		Categories::remember('user:'.$fields['id'], NULL_DATE, isset($fields['tags']) ? $fields['tags'] : '');

		// clear the cache for users
		Users::clear($fields);

		// send a confirmation message
		if(isset($fields['email']) && trim($fields['email']) && isset($context['with_email']) && ($context['with_email'] == 'Y')) {

			// message title
			$subject = sprintf(i18n::s('Your account at %s'), strip_tags($context['site_name']));

			// top of the message
			$message = '<p>'.i18n::s('Welcome!').'</p>'
				.'<p>'.sprintf(i18n::s('This message relates to your account at %s.'),
					'<a href="'.$context['url_to_home'].$context['url_to_root'].'">'.strip_tags($context['site_name']).'</a>').'</p>';

			// mention nick name
			$message .= '<p>'.sprintf(i18n::s('Your nick name is %s'), $fields['nick_name']).'</p>';

			// direct link to login page --see users/login.php
			$link = $context['url_to_home'].$context['url_to_root'].Users::get_login_url('login', $fields['id'], rand(1000, 9999), $fields['handle']);

			$message .= '<p>'.i18n::s('Record this message and use the following link to authenticate to the site at any time:').'</p>'
				.'<p><a href="'.$link.'">'.$link.'</a></p>';

			// caution note
			$message .= '<p>'.i18n::s('Caution: This hyperlink contains your login credentials encrypted. Please be aware anyone who uses this link will have full access to your account.').'</p>';

			// confirmation link
			if(isset($context['users_with_email_validation']) && ($context['users_with_email_validation'] == 'Y')) {
				$message .= '<p>'.i18n::s('Click on the link below to activate your new account.').'</p>';

				// use the secret handle
				$link = $context['url_to_home'].$context['url_to_root'].Users::get_url($fields['handle'], 'validate');
				$message .= '<p><a href="'.$link.'">'.$link.'</a></p>';
			}

			// bottom of the message
			$message .= '<p>'.sprintf(i18n::s('On-line help is available at %s'),
				'<a href="'.$context['url_to_home'].$context['url_to_root'].'help/'.'">'.$context['url_to_home'].$context['url_to_root'].'help/'.'</a>').'</p>'
				.'<p>'.sprintf(i18n::s('Thank you for your interest into %s.'),
					'<a href="'.$context['url_to_home'].$context['url_to_root'].'">'.strip_tags($context['site_name']).'</a>').'</p>';

			// enable threading
			$headers = Mailer::set_thread('user:'.$fields['id']);

			// post the confirmation message
			Mailer::notify(NULL, $fields['email'], $subject, $message, $headers);

		}

		// automatic login
		if(!Surfer::get_id() && is_callable(array('Surfer', 'set')))
			Surfer::set($fields, TRUE);

		// return the id of the new item
		return $fields['id'];
	}

	/**
	 * put an updated user profile in the database
	 *
	 * If present, only the password is changed. Or other fields except the password are modified.
	 *
	 * To change a password, set fields 'id', 'password' and 'confirm'
	 *
	 * @param array an array of fields
	 * @return TRUE on success, FALSE otherwise
	 *
	 * @see users/edit.php
	 * @see users/password.php
	 * @see users/select_avatar.php
	**/
	function put(&$fields) {
		global $context;

		// load the record
		$item =& Users::get($fields['id']);
		if(!isset($item['id']) || !$item['id']) {
			Logger::error(i18n::s('No item has the provided id.'));
			return FALSE;
		}

		// remember who is changing this record
		Surfer::check_default_editor($fields);

		// if a password change
		if(isset($fields['password'])) {

			// ensure that the password has been provided twice
			if(!isset($fields['confirm']) || ($fields['confirm'] != $fields['password'])) {
				Logger::error(i18n::s('New password has to be confirmed.'));
				return FALSE;
			}

			// hash password, we are coming from an interactive form
			$fields['password'] = md5($fields['password']);

		// else if a regular profile update
		} else {

			// nick_name is required
			if(!isset($fields['nick_name']) || !trim($fields['nick_name'])) {
				Logger::error(i18n::s('Please indicate a nick name.'));
				return FALSE;
			}

			// some weird users put spaces around
			$fields['nick_name'] = trim($fields['nick_name']);

			// nick_name may be already used
			if(($used =& Users::get($fields['nick_name'])) && ($used['id'] != $fields['id'])) {
				Logger::error(i18n::s('Another member already has this nick name. Please select a different one.'));
				return FALSE;
			}

			// ensure we have a full name
			if(!isset($fields['full_name']) || !trim($fields['full_name']))
				$fields['full_name'] = $fields['nick_name'];

			// protect from hackers
			if(isset($fields['avatar_url']))
				$fields['avatar_url'] =& encode_link($fields['avatar_url']);

			// set default values
			if(!isset($fields['active']) || !$fields['active'])
				$fields['active'] = 'Y';
			if(isset($fields['selected_editor']))
				$fields['editor'] = $fields['selected_editor'];	// hack because of FCKEditor already uses 'editor'
			elseif(isset($context['users_default_editor']))
				$fields['editor'] = $context['users_default_editor'];
			else
				$fields['editor'] = 'yacs';
			if(!isset($fields['interface']) || ($fields['interface'] != 'C'))
				$fields['interface'] = 'I';
			if(!isset($fields['with_newsletters']) || ($fields['with_newsletters'] != 'Y'))
				$fields['with_newsletters'] = 'N';
			if(!isset($fields['without_alerts']) || ($fields['without_alerts'] != 'N'))
				$fields['without_alerts'] = 'Y';
			if(!isset($fields['without_confirmations']) || ($fields['without_confirmations'] != 'N'))
				$fields['without_confirmations'] = 'Y';
			if(!isset($fields['without_messages']) || ($fields['without_messages'] != 'N'))
				$fields['without_messages'] = 'Y';

			if(!isset($fields['birth_date']) || !$fields['birth_date'])
				$fields['birth_date'] = NULL_DATE;

			// clean provided tags
			if(isset($fields['tags']))
				$fields['tags'] = trim($fields['tags'], " \t.:,!?");

			// save new settings in session and in cookie
			if(Surfer::is($fields['id'])) {

				// change preferred editor
				$_SESSION['surfer_editor'] = $fields['editor'];
				Safe::setcookie('surfer_editor', $fields['editor'], NULL, '/');

				// change preferred language
				if(isset($fields['language']) && ($_SESSION['surfer_language'] != $fields['language'])) {
					$_SESSION['surfer_language'] = $fields['language'];
					$_SESSION['l10n_modules'] = array();
				}
			}


		}

		// update an existing record
		$query = "UPDATE ".SQL::table_name('users')." SET ";

		// change only the password
		if(isset($fields['password']))
			$query .= "password='".SQL::escape($fields['password'])."'";

		// change all fields, except the password
		else {
			$query .= "email='".SQL::escape(isset($fields['email']) ? $fields['email'] : '')."', "
				."aim_address='".SQL::escape(isset($fields['aim_address']) ? $fields['aim_address'] : '')."', "
				."alternate_number='".SQL::escape(isset($fields['alternate_number']) ? $fields['alternate_number'] : '')."', "
				."avatar_url='".SQL::escape(isset($fields['avatar_url']) ? $fields['avatar_url'] : '')."', "
				."birth_date='".SQL::escape($fields['birth_date'])."', "
				."description='".SQL::escape(isset($fields['description']) ? $fields['description'] : '')."', "
				."editor='".SQL::escape($fields['editor'])."', "
				."from_where='".SQL::escape(isset($fields['from_where']) ? $fields['from_where'] : '')."', "
				."full_name='".SQL::escape(isset($fields['full_name']) ? $fields['full_name'] : '')."', "
				."icq_address='".SQL::escape(isset($fields['icq_address']) ? $fields['icq_address'] : '')."', "
				."interface='".SQL::escape($fields['interface'])."', "
				."introduction='".SQL::escape(isset($fields['introduction']) ? $fields['introduction'] : '')."', "
				."irc_address='".SQL::escape(isset($fields['irc_address']) ? $fields['irc_address'] : '')."', "
				."jabber_address='".SQL::escape(isset($fields['jabber_address']) ? $fields['jabber_address'] : '')."', "
				."language='".SQL::escape(isset($fields['language']) ? $fields['language'] : '')."', "
				."msn_address='".SQL::escape(isset($fields['msn_address']) ? $fields['msn_address'] : '')."', "
				."nick_name='".SQL::escape($fields['nick_name'])."', "
				."options='".SQL::escape(isset($fields['options']) ? $fields['options'] : '')."', "
				."overlay='".SQL::escape(isset($fields['overlay']) ? $fields['overlay'] : '')."',"
				."overlay_id='".SQL::escape(isset($fields['overlay_id']) ? $fields['overlay_id'] : '')."',"
				."pgp_key='".SQL::escape(isset($fields['pgp_key']) ? $fields['pgp_key'] : '')."', "
				."phone_number='".SQL::escape(isset($fields['phone_number']) ? $fields['phone_number'] : '')."', "
				."signature='".SQL::escape(isset($fields['signature']) ? $fields['signature'] : '')."', "
				."skype_address='".SQL::escape(isset($fields['skype_address']) ? $fields['skype_address'] : '')."', "
				."tags='".SQL::escape(isset($fields['tags']) ? $fields['tags'] : '')."', "
				."twitter_address='".SQL::escape(isset($fields['twitter_address']) ? $fields['twitter_address'] : '')."', "
				."vcard_agent='".SQL::escape(isset($fields['vcard_agent']) ? $fields['vcard_agent'] : '')."', "
				."vcard_label='".SQL::escape(isset($fields['vcard_label']) ? $fields['vcard_label'] : '')."', "
				."vcard_organization='".SQL::escape(isset($fields['vcard_organization']) ? $fields['vcard_organization'] : '')."', "
				."vcard_title='".SQL::escape(isset($fields['vcard_title']) ? $fields['vcard_title'] : '')."', "
				."web_address='".SQL::escape(isset($fields['web_address']) ? $fields['web_address'] : '')."', "
				."with_newsletters='".($fields['with_newsletters'])."', "
				."without_alerts='".($fields['without_alerts'])."', "
				."without_confirmations='".($fields['without_confirmations'])."', "
				."without_messages='".($fields['without_messages'])."', "
				."yahoo_address='".SQL::escape(isset($fields['yahoo_address']) ? $fields['yahoo_address'] : '')."'";

			// fields set only by associates -- see users/edit.php
			if(Surfer::is_associate()) {
				$query .= ", "
					."capability='".SQL::escape($fields['capability'])."', "
					."active='".SQL::escape($fields['active'])."'";

			}
		}

		// maybe a silent update
		if(!isset($fields['silent']) || ($fields['silent'] != 'Y')) {
			$query .= ", "
				."edit_name='".SQL::escape($fields['edit_name'])."', "
				."edit_id=".SQL::escape($fields['edit_id']).", "
				."edit_address='".SQL::escape($fields['edit_address'])."', "
				."edit_action='user:update', "
				."edit_date='".SQL::escape($fields['edit_date'])."'";
		}

		// actual update query
		$query .= " WHERE id = ".SQL::escape($item['id']);
		SQL::query($query, FALSE, $context['users_connection']);

		// list the user in categories
		if(isset($fields['tags']) && $fields['tags'])
			Categories::remember('user:'.$item['id'], NULL_DATE, $fields['tags']);

		// clear all the cache on profile update, because of avatars, etc.
		$fields['id'] = $item['id'];
		Users::clear($fields);

		// send a confirmation message on password change
		if(isset($context['with_email']) && ($context['with_email'] == 'Y')
			&& isset($fields['confirm']) && $item['email'] && ($item['without_confirmations'] != 'Y')) {

			// message title
			$subject = sprintf(i18n::s('Your account at %s'), strip_tags($context['site_name']));

			// message body
			$message = '<p>'.sprintf(i18n::s('This message has been automatically sent to you to confirm a change of your profile at %s.'),
					'<a href="'.$context['url_to_home'].$context['url_to_root'].'">'.strip_tags($context['site_name']).'</a>').'</p>'
				.'<p>'.sprintf(i18n::s('Your nick name is %s'), $item['nick_name'])
				.BR.sprintf(i18n::s('Authenticate with password %s'), $fields['confirm']).'</p>' 	// $fields['password'] has been hashed
				.'<p>'.sprintf(i18n::s('On-line help is available at %s'),
						'<a href="'.$context['url_to_home'].$context['url_to_root'].'help/'.'">'.$context['url_to_home'].$context['url_to_root'].'help/'.'</a>').'</p>'
				.'<p>'.sprintf(i18n::s('Thank you for your interest into %s.'),
					'<a href="'.$context['url_to_home'].$context['url_to_root'].'">'.strip_tags($context['site_name']).'</a>').'</p>';

			// enable threading
			$headers = Mailer::set_thread('', 'user:'.$item['id']);

			// post the confirmation message
			Mailer::notify(NULL, $item['email'], $subject, $message, $headers);

		}

		// update user session
		if(isset($fields['nick_name']) && Surfer::get_id() && ($fields['id'] == Surfer::get_id()) && is_callable(array('Surfer', 'set')))
			Surfer::set($fields);

		// end of job
		return TRUE;
	}

	/**
	 * search for some keywords in all users
	 *
	 * @param the search string
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see search.php
	 */
	function &search($pattern, $offset=0, $count=50, $variant='decorated') {
		global $context;

		// sanity check
		if(!$pattern = trim($pattern)) {
			$output = NULL;
			return $output;
		}

		// limit the scope of the request
		$where = "users.active='Y'";
		if(Surfer::is_logged())
			$where .= " OR users.active='R'";
		if(Surfer::is_associate())
			$where .= " OR users.active='N'";
		$where = '('.$where.')';

		// do not show suspended users, except to associates
		if(!Surfer::is_associate())
			$where .= " AND (users.capability != '?')";

		// match
		$match = '';
		$words = preg_split('/\s/', $pattern);
		while($word = each($words)) {
			if($match)
				$match .= ' AND ';
//			$match .= "MATCH(nick_name, full_name, introduction, description) AGAINST('".SQL::escape($word['value'])."')";
			$match .= "((nick_name LIKE '%".SQL::escape($word['value'])."%')"
				." OR (full_name LIKE '%".SQL::escape($word['value'])."%')"
				." OR (email LIKE '%".SQL::escape($word['value'])."%'))";
		}

		// the list of users
		$query = "SELECT * FROM ".SQL::table_name('users')." AS users"
			." WHERE ".$where." AND (".$match.")"
			." ORDER BY users.login_date DESC"
			." LIMIT ".$offset.','.$count;

		$output =& Users::list_selected(SQL::query($query, FALSE, $context['users_connection']), $variant);
		return $output;
	}

	/**
	 * create tables for users
	 *
	 * @see control/setup.php
	 */
	function setup() {
		global $context;

		$fields = array();
		$fields['id']			= "MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT";
		$fields['active']		= "ENUM('Y','R','N') DEFAULT 'Y' NOT NULL";
		$fields['aim_address']	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['alternate_number'] = "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['authenticate_date']	= "DATETIME";
		$fields['authenticate_failures']	= "SMALLINT UNSIGNED DEFAULT 0 NOT NULL";
		$fields['avatar_url']	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['birth_date'] = "DATETIME";
		$fields['capability']	= "ENUM('A','M','S','?') DEFAULT '?' NOT NULL";
		$fields['click_anchor'] = "VARCHAR(64) DEFAULT '' NOT NULL";
		$fields['click_date']	= "DATETIME";
		$fields['create_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['create_id']	= "MEDIUMINT UNSIGNED DEFAULT 1 NOT NULL";
		$fields['create_address']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['create_date']	= "DATETIME";
		$fields['description']	= "TEXT NOT NULL";
		$fields['edit_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_id']		= "MEDIUMINT UNSIGNED DEFAULT 1 NOT NULL";
		$fields['edit_address'] = "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_action']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_date']	= "DATETIME";
		$fields['editor']		= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['email']		= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['full_name']	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['from_where']	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['handle']		= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['icq_address']	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['interface']	= "ENUM('I','C') DEFAULT 'I' NOT NULL";
		$fields['introduction'] = "TEXT NOT NULL";
		$fields['irc_address']	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['jabber_address'] = "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['language'] = "VARCHAR(6) DEFAULT '' NOT NULL";
		$fields['login_address'] = "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['login_date']	= "DATETIME";
		$fields['msn_address']	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['nick_name']	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['options']		= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['overlay']		= "TEXT NOT NULL";
		$fields['overlay_id']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['password'] 	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['pgp_key']		= "TEXT NOT NULL";
		$fields['phone_number'] = "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['post_date']	= "DATETIME";
		$fields['posts']		= "INT UNSIGNED DEFAULT 0 NOT NULL";
		$fields['signature']	= "TEXT NOT NULL";
		$fields['skype_address'] = "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['tags'] 		= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['twitter_address'] = "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['vcard_agent']	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['vcard_label']	= "TEXT NOT NULL";
		$fields['vcard_organization']	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['vcard_title']	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['web_address']	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['with_newsletters'] = "ENUM('Y','N') DEFAULT 'N' NOT NULL";
		$fields['without_alerts'] = "ENUM('Y','N') DEFAULT 'N' NOT NULL";
		$fields['without_confirmations'] = "ENUM('Y','N') DEFAULT 'N' NOT NULL";
		$fields['without_messages'] = "ENUM('Y','N') DEFAULT 'N' NOT NULL";
		$fields['yahoo_address'] = "VARCHAR(255) DEFAULT '' NOT NULL";

		$indexes = array();
		$indexes['PRIMARY KEY'] 		= "(id)";
		$indexes['INDEX birth_date']	= "(birth_date)";
		$indexes['INDEX create_date']	= "(create_date)";
		$indexes['INDEX create_id'] 	= "(create_id)";
		$indexes['INDEX edit_date'] 	= "(edit_date)";
		$indexes['INDEX edit_id']		= "(edit_id)";
		$indexes['INDEX email'] 		= "(email)";
		$indexes['INDEX full_name'] 	= "(full_name(255))";
		$indexes['INDEX handle']		= "(handle)";
		$indexes['INDEX login_date']	= "(login_date)";
		$indexes['INDEX nick_name'] 	= "(nick_name)";
		$indexes['INDEX overlay_id']	= "(overlay_id)";
		$indexes['INDEX post_date'] 	= "(post_date)";
		$indexes['INDEX posts'] 		= "(posts)";
		$indexes['INDEX with_newsletters'] = "(with_newsletters)";
		$indexes['FULLTEXT INDEX']		= "full_text(nick_name, full_name, introduction, description)";

		return SQL::setup_table('users', $fields, $indexes);
	}

	/**
	 * get some statistics
	 *
	 * Only users matching following criteria are returned:
	 * - user is visible (active='Y')
	 * - user is restricted (active='R'), but surfer is a logged member
	 * - user is restricted (active='N'), but surfer is an associate
	 *
	 * @return the resulting ($count, $min_date, $max_date) array
	 *
	 * @see users/index.php
	 */
	function &stat() {
		global $context;

		// limit the scope of the request
		$where = "users.active='Y'";
		if(Surfer::is_member())
			$where .= " OR users.active='R'";
		if(Surfer::is_associate())
			$where .= " OR users.active='N'";

		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(users.edit_date) as oldest_date, MAX(users.edit_date) as newest_date"
			." FROM ".SQL::table_name('users')." AS users"
			." WHERE ".$where;

		$output = SQL::query_first($query, FALSE, $context['users_connection']);
		return $output;
	}

	/**
	 * count present users
	 *
	 * Only users matching following criteria are returned:
	 * - user is visible (active='Y')
	 * - user is restricted (active='R'), but surfer is a logged member
	 * - user is restricted (active='N'), but surfer is an associate
	 * - user has clicked during the last 15 minutes
	 *
	 * @return the resulting ($count, $min_date, $max_date) array
	 *
	 * @see users/index.php
	 */
	function &stat_present() {
		global $context;

		// limit the scope of the request
		$where = "users.active='Y'";
		if(Surfer::is_member())
			$where .= " OR users.active='R'";
		if(Surfer::is_associate())
			$where .= " OR users.active='N'";

		// present means 'a click not too long in the past'
		$threshold = gmstrftime('%Y-%m-%d %H:%M:%S', time()-15*60);
		$where = "(".$where.") AND (click_date > '".$threshold."')";

		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(users.edit_date) as oldest_date, MAX(users.edit_date) as newest_date"
			." FROM ".SQL::table_name('users')." AS users"
			." WHERE ".$where;

		$output = SQL::query_first($query, FALSE, $context['users_connection']);
		return $output;
	}

	/**
	 * validate an e-mail address
	 *
	 * This function promotes a subscriber to a member
	 *
	 * @param the id of the user to update
	 *
	 * @see users/validate.php
	 */
	function validate($id) {
		global $context;

		// sanity check
		if(!$id)
			return 0;

		// do the job
		$query = "UPDATE ".SQL::table_name('users')
			." SET capability='M'"
			." WHERE (id = ".$id.") AND (capability != 'A')";
		$result = SQL::query($query, FALSE, $context['users_connection']);

		// clear the cache for users
		Cache::clear(array('users', 'user:'.$id, 'categories'));

		// maybe it was already set
		return 1;
	}

}

// load localized strings
if(is_callable(array('i18n', 'bind')))
	i18n::bind('users');

?>
