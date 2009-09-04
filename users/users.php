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
	 * This script sends an e-mail message.
	 *
	 * @todo use the notification to create an action?
	 *
	 * This function will ensure that only one alert is send to a user,
	 * by maintaining an internal list of ids that have been processed.
	 *
	 * @param mixed an array of attributes, or only an id of the user profile
	 * @param array components of a mail message to be submitted to Mailer::notify() (i.e., $mail['subject'], $mail['message'])
	 * @param array components of a notification to be submitted to Notifications::post()
	 * @return TRUE on success, FALSE otherwise
	 */
	function alert($user, $mail, $notification=NULL) {
		global $context;

		// retrieve user attributes
		if(!isset($user['id']) && !$user =& Users::get($user))
			return FALSE;

		// ensure poster wants alerts
		if(isset($user['without_alerts']) && ($user['without_alerts'] == 'Y'))
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

		// a valid address is required for e-mail...
		if(!isset($user['email']) || !$user['email'])
			return FALSE;

		// obviously we need a valid message as well
		if(!isset($mail['subject']) || !$mail['subject'] || !isset($mail['message']))
			return FALSE;

		// post a message to this particular user
		include_once $context['path_to_root'].'shared/mailer.php';
		return Mailer::notify($user['email'], $mail['subject'], $mail['message']);

	}

	/**
	 * alert all watchers of one anchor
	 *
	 * This script send either real-time notifications or asynchronous
	 * e-mail messages, depending of the presence of each watcher.
	 *
	 * Users who are currently visiting the target anchor are skipped silently.
	 *
	 * @param mixed, either reference of the updated anchor, or array for containers path
	 * @param array components of a mail message to be submitted to Mailer::notify() (i.e., $mail['subject'], $mail['message'])
	 * @param array components of a notification to be submitted to Notifications::post()
	 * @param array users assigned to the reference, if any
	 * @return TRUE on success, FALSE otherwise
	 */
	function alert_watchers($reference, $mail, $notification=NULL, $restricted=NULL) {
		global $context;

		// list watchers, including watchers of containers of this page
		if($items =& Members::list_watchers_by_posts_for_anchor($reference, 0, 500, 'raw', $restricted)) {

			// who is visiting this page
			if(is_array($reference))
				$reference = $reference[0];

			// check every watcher
			include_once $context['path_to_root'].'users/visits.php';
			foreach($items as $id => $attributes) {

				// current surfer is already watching the thing
				if(Surfer::get_id() && (Surfer::get_id() == $id))
					continue;

				// the user is already visiting this page
				if(Visits::check_user_at_anchor($id, $reference))
					continue;

				// ensure this surfer wants to be alerted
				elseif($attributes['without_alerts'] != 'Y')
					Users::alert($attributes, $mail, $notification);
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
	function authenticate() {
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
	 * change all user profiles at once
	 *
	 * @param mixed attributes to change
	 * @return boolean TRUE on success, FALSE otherwise
	 */
	function change_all($attributes) {
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
	 * check the presence of some user
	 *
	 * @param mixed an array of attributes, or only an id of the user profile
	 * @return TRUE if the user is present, FALSE otherwise
	 */
	function check_presence_of($user) {
		global $context;

		// retrieve user attributes
		if(!isset($user['id']) && (!$user =& Users::get($user)))
			return FALSE;

		// only consider recent clicks; 300 seconds = 5 minutes
		$threshold = gmstrftime('%Y-%m-%d %H:%M:%S', time() - 300);
		if(isset($user['click_date']) && ($user['click_date'] >= $threshold))
			return TRUE;

		// rely on recent visits
		include_once $context['path_to_root'].'users/visits.php';
		return Visits::prove_presence_of($user['id']);
	}

	/**
	 * clear cache entries for one item
	 *
	 * @param array item attributes
	 */
	function clear(&$item) {

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
	function delete($id) {
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
	function &get($id, $mutable=FALSE) {
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
		$output =& SQL::query_first($query, FALSE, $context['users_connection']);

		// ensure we have a full name
		if(!isset($output['full_name']) || !$output['full_name'])
			$output['full_name'] = $output['nick_name'];

		include_once $context['path_to_root'].'users/visits.php';

		// user is present if active during last 10 minutes (10*60 = 600)
		if(isset($output['click_date']) && ($output['click_date'] >= gmstrftime('%Y-%m-%d %H:%M:%S', time()-600)))
			$output['is_present'] = TRUE;

		// some page or thread is currently observed
		elseif(isset($output['id']) && ($items = Visits::list_for_user($output['id'])))
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
			return '';

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

		// authenticate a user
		if($action == 'credentials') {

			// encode credentials --see users/login.php
			if(is_array($id)) {

				// we prefer JSON over PHP serialization
				if(!$serialized = Safe::json_encode($id))
					$serialized = serialize($id);

				// finalize the snippet
				$id = base64_encode($serialized);
			}

			// be cool with search engines
			if($context['with_friendly_urls'] == 'Y')
				return 'users/login.php/'.rawurlencode($id);
			elseif($context['with_friendly_urls'] == 'R')
				return 'users/login.php/'.rawurlencode($id);
			else
				return 'users/login.php?credentials='.urlencode($id);
		}

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

		// check the target action
		if(!preg_match('/^(contact|delete|describe|edit|element|feed|fetch_vcard|mail|navigate|password|print|select_avatar|share|validate|view|visit)$/', $action))
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
	 * If the variant is 'mail', then users who have not subscribed explicitly to newsletters
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

		// protect the privacy of e-mail boxes and never send messages to banned users
		if($variant == 'mail')
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
	 * If the variant is 'mail', then users who have not subscribed explicitly to newsletters
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
		if($variant == 'mail')
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
	 * Accept following variants:
	 * - 'complete'
	 * - 'raw'
	 * - 'compact'
	 * - 'email' to build list of recipients
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
			$output =& $variant->layout($result);
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
				$layout =& new $name;

				// provide parameters to the layout
				if(isset($attributes[1]))
					$layout->set_variant($attributes[1]);
		
			}
		}

		// use default layout
		if(!$layout) {
			include_once $context['path_to_root'].'users/layout_users.php';
			$layout =& new Layout_users();
			$layout->set_variant($variant);
		}

		// do the job
		$output =& $layout->layout($result);
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

		// up to three authentication attempts during last hour
		$blocked = FALSE;
		$authentication_horizon = gmstrftime('%Y-%m-%d %H:%M:%S', time()-3600);

		// search a user profile locally
		$query = "SELECT * FROM ".SQL::table_name('users')." AS users"
			." WHERE users.email LIKE '".SQL::escape($name)."' OR users.nick_name LIKE '".SQL::escape($name)."' OR users.full_name LIKE '".SQL::escape($name)."'";
		if(isset($context['users_connection']) && ($item =& SQL::query_first($query, FALSE, $context['users_connection']))) {

			// the user has been explicitly banned
			if($item['capability'] == '?')
				$authenticated = FALSE;

			// more than three failed authentications during previous hour
			elseif(($item['authenticate_date'] > $authentication_horizon) && ($item['authenticate_failures'] >= 3)) {
				$authenticated = FALSE;
				$blocked = TRUE;

			// successful local check
			} elseif(md5($password) == $item['password'])
				$authenticated = TRUE;

		}

		// we have to authenticate externally, if this has been explicitly allowed
		if(!$authenticated && !$blocked && isset($context['users_authenticator']) && $context['users_authenticator']) {

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
			if(isset($item['authenticate_date']) && ($item['authenticate_date'] >= $authentication_horizon)) {

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

		// control user capability
		if(!Surfer::is_associate()) {

			// closed community, accept only subscribers
			if(isset($context['users_with_approved_members']) && ($context['users_with_approved_members'] == 'Y'))
				$fields['capability'] = 'S';

			// email addresses have to be validated
			elseif(isset($context['users_with_email_validation']) && ($context['users_with_email_validation'] == 'Y'))
				$fields['capability'] = 'S';

			// open community, accept subscribers and members
			elseif(!isset($fields['capability']) || (($fields['capability'] != 'S') && ($fields['capability'] != 'M') && ($fields['capability'] != '?')))
				$fields['capability'] = 'M';

		}

		// protect from hackers
		if(isset($fields['avatar_url']))
			$fields['avatar_url'] =& encode_link($fields['avatar_url']);

		// remember who is changing this record
		Surfer::check_default_editor($fields);

		// reinforce date formats
		if(!isset($fields['create_date']) || ($fields['create_date'] <= NULL_DATE))
			$fields['create_date'] = $fields['edit_date'];
		if(!isset($fields['post_date']) || ($fields['post_date'] <= NULL_DATE))
			$fields['post_date'] = $fields['edit_date'];

		// set default values
		if(!isset($fields['active']) || !trim($fields['active']))
			$fields['active'] = 'Y';
		if(isset($fields['selected_editor']))
			$fields['editor'] = $fields['selected_editor'];	// hack because of FCKEditor already uses 'editor'
		elseif(isset($context['users_default_editor']))
			$fields['editor'] = $context['users_default_editor'];
		else
			$fields['editor'] = 'yacs';
		if(!isset($fields['interface']) || ($fields['interface'] != 'C'))
			$fields['interface'] = 'I';
		if(!isset($fields['with_newsletters']) || ($fields['with_newsletters'] != 'N'))
			$fields['with_newsletters'] = 'Y';
		if(!isset($fields['without_alerts']) || ($fields['without_alerts'] != 'Y'))
			$fields['without_alerts'] = 'N';
		if(!isset($fields['without_confirmations']) || ($fields['without_confirmations'] != 'Y'))
			$fields['without_confirmations'] = 'N';
		if(!isset($fields['without_messages']) || ($fields['without_messages'] != 'Y'))
			$fields['without_messages'] = 'N';

		// clean provided tags
		if(isset($fields['tags']))
			$fields['tags'] = trim($fields['tags'], " \t.:,!?");

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
		
		if(!isset($fields['birth_date']) || !$fields['birth_date'])
			$fields['birth_date'] = NULL_DATE;

		// create a handle for this user
		if(!isset($fields['handle']) || !trim($fields['handle']))
			$fields['handle'] = md5(rand());

		// insert a new record
		$query = "INSERT INTO ".SQL::table_name('users')." SET ";
		if(isset($fields['id']))
			$query .= "id='".SQL::escape($fields['id'])."',";
		$query .= "email='".SQL::escape(isset($fields['email']) ? $fields['email'] : '')."', "
			."active='".SQL::escape($fields['active'])."', "
			."aim_address='".SQL::escape(isset($fields['aim_address']) ? $fields['aim_address'] : '')."', "
			."alternate_number='".SQL::escape(isset($fields['alternate_number']) ? $fields['alternate_number'] : '')."', "
			."avatar_url='".SQL::escape(isset($fields['avatar_url']) ? $fields['avatar_url'] : '')."', "
			."birth_date='".SQL::escape($fields['birth_date'])."', "
			."capability='".SQL::escape($fields['capability'])."', "
			."create_name='".SQL::escape(isset($fields['create_name']) ? $fields['create_name'] : $fields['edit_name'])."', "
			."create_id=".SQL::escape(isset($fields['create_id']) ? $fields['create_id'] : $fields['edit_id']).", "
			."create_address='".SQL::escape(isset($fields['create_address']) ? $fields['create_address'] : $fields['edit_address'])."', "
			."create_date='".SQL::escape($fields['create_date'])."', "
			."description='".SQL::escape(isset($fields['description']) ? $fields['description'] : '')."', "
			."edit_name='".SQL::escape($fields['edit_name'])."', "
			."edit_id=".SQL::escape($fields['edit_id']).", "
			."edit_address='".SQL::escape($fields['edit_address'])."', "
			."edit_action='".SQL::escape(isset($fields['edit_action']) ? $fields['edit_action'] : 'new')."', "
			."edit_date='".SQL::escape($fields['edit_date'])."', "
			."editor='".SQL::escape($fields['editor'])."', "
			."from_where='".SQL::escape(isset($fields['from_where']) ? $fields['from_where'] : '')."', "
			."full_name='".SQL::escape(isset($fields['full_name']) ? $fields['full_name'] : '')."', "
			."handle='".SQL::escape($fields['handle'])."', "
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
			."password='".SQL::escape(isset($fields['password']) ? $fields['password'] : '')."', "
			."pgp_key='".SQL::escape(isset($fields['pgp_key']) ? $fields['pgp_key'] : '')."', "
			."phone_number='".SQL::escape(isset($fields['phone_number']) ? $fields['phone_number'] : '')."', "
			."post_date='".SQL::escape($fields['post_date'])."', "
			."posts=".SQL::escape(isset($fields['posts']) ? $fields['posts'] : '0').", "
			."proxy_address='".SQL::escape(isset($fields['proxy_address']) ? $fields['proxy_address'] : '')."', "
			."signature='".SQL::escape(isset($fields['signature']) ? $fields['signature'] : '')."', "
			."skype_address='".SQL::escape(isset($fields['skype_address']) ? $fields['skype_address'] : '')."', "
			."tags='".SQL::escape(isset($fields['tags']) ? $fields['tags'] : '')."', "
			."vcard_agent='".SQL::escape(isset($fields['vcard_agent']) ? $fields['vcard_agent'] : '')."', "
			."vcard_label='".SQL::escape(isset($fields['vcard_label']) ? $fields['vcard_label'] : '')."', "
			."vcard_organization='".SQL::escape(isset($fields['vcard_organization']) ? $fields['vcard_organization'] : '')."', "
			."vcard_title='".SQL::escape(isset($fields['vcard_title']) ? $fields['vcard_title'] : '')."', "
			."web_address='".SQL::escape(isset($fields['web_address']) ? $fields['web_address'] : '')."', "
			."with_newsletters='".($fields['with_newsletters'])."', "
			."with_sharing='".(isset($fields['with_sharing']) ? $fields['with_sharing'] : 'N')."', "
			."without_alerts='".($fields['without_alerts'])."', "
			."without_confirmations='".($fields['without_confirmations'])."', "
			."without_messages='".($fields['without_messages'])."', "
			."yahoo_address='".SQL::escape(isset($fields['yahoo_address']) ? $fields['yahoo_address'] : '')."'";

		// actual insert
		if(SQL::query($query, FALSE, $context['users_connection']) === FALSE)
			return FALSE;

		// remember the id of the new item
		if(!$fields['id'] = SQL::get_last_id($context['users_connection'])) {
			logger::remember('users/users.php', 'unable to retrieve id of new record');
			return FALSE;
		}

		// list the user in categories
		include_once $context['path_to_root'].'categories/categories.php';
		Categories::remember('user:'.$fields['id'], NULL_DATE, isset($fields['tags']) ? $fields['tags'] : '');

		// clear the cache for users
		Users::clear($fields);

		// send a confirmation message
		if(isset($fields['email']) && trim($fields['email']) && isset($context['with_email']) && ($context['with_email'] == 'Y')) {

			// message title
			$subject = sprintf(i18n::s('Your account at %s'), strip_tags($context['site_name']));

			// top of the message
			$message = i18n::s('Welcome!')."\n"
				."\n".sprintf(i18n::s('This message relates to your account at %s.'), strip_tags($context['site_name']))."\n"
				."\n".$context['url_to_home'].$context['url_to_root']."\n";

			// mention nick name
			$message .= "\n".sprintf(i18n::s('Your nick name is %s'), $fields['nick_name'])."\n";

			// build credentials --see users/login.php
			$credentials = array();
			$credentials[0] = 'login';
			$credentials[1] = $fields['id'];
			$credentials[2] = rand(1000, 9999);
			$credentials[3] = sprintf('%u', crc32($credentials[2].':'.$fields['handle']));

			// direct link to login page
			$message .= "\n".i18n::s('Record this message and use the following link to authenticate to the site at any time:')."\n"
				."\n".$context['url_to_home'].$context['url_to_root'].Users::get_url($credentials, 'credentials')."\n";

			// caution note
			$message .= "\n".i18n::s('Caution: This hyperlink contains your login credentials encrypted. Please be aware anyone who uses this link will have full access to your account.')."\n";

			// confirmation link
			if(isset($context['users_with_email_validation']) && ($context['users_with_email_validation'] == 'Y')) {
				$message = "\n".i18n::s('Click on the link below to activate your new account.')."\n";

				// use the secret handle
				$message .= "\n".$context['url_to_home'].$context['url_to_root'].Users::get_url($fields['handle'], 'validate')."\n";
			}

			// bottom of the message
			$message .= "\n".sprintf(i18n::s('On-line help is available at %s'), $context['url_to_home'].$context['url_to_root'].'help/')."\n"
				."\n".sprintf(i18n::s('Thank you for your interest into %s.'), strip_tags($context['site_name']))."\n";

			// post the confirmation message
			include_once $context['path_to_root'].'shared/mailer.php';
			Mailer::notify($fields['email'], $subject, $message);

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

			// hash password if coming from a human facing a form
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

		}

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
				."proxy_address='".SQL::escape(isset($fields['proxy_address']) ? $fields['proxy_address'] : '')."', "
				."signature='".SQL::escape(isset($fields['signature']) ? $fields['signature'] : '')."', "
				."skype_address='".SQL::escape(isset($fields['skype_address']) ? $fields['skype_address'] : '')."', "
				."tags='".SQL::escape(isset($fields['tags']) ? $fields['tags'] : '')."', "
				."vcard_agent='".SQL::escape(isset($fields['vcard_agent']) ? $fields['vcard_agent'] : '')."', "
				."vcard_label='".SQL::escape(isset($fields['vcard_label']) ? $fields['vcard_label'] : '')."', "
				."vcard_organization='".SQL::escape(isset($fields['vcard_organization']) ? $fields['vcard_organization'] : '')."', "
				."vcard_title='".SQL::escape(isset($fields['vcard_title']) ? $fields['vcard_title'] : '')."', "
				."web_address='".SQL::escape(isset($fields['web_address']) ? $fields['web_address'] : '')."', "
				."with_newsletters='".($fields['with_newsletters'])."', "
				."with_sharing='".(isset($fields['with_sharing']) ? $fields['with_sharing'] : 'N')."', "
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
		if(isset($fields['tags']) && $fields['tags']) {
			include_once $context['path_to_root'].'categories/categories.php';
			Categories::remember('user:'.$item['id'], NULL_DATE, $fields['tags']);
		}

		// clear all the cache on profile update, because of avatars, etc.
		$fields['id'] = $item['id'];
		Users::clear($fields);

		// send a confirmation message on password change
		if(isset($fields['email']) && $fields['email'] && isset($context['with_email']) && ($context['with_email'] == 'Y')
			&& isset($fields['confirm']) && $fields['confirm'] && $item['email'] && ($item['without_confirmations'] != 'Y')) {

			// message title
			$subject = sprintf(i18n::s('Your account at %s'), strip_tags($context['site_name']));

			// message body
			$message = sprintf(i18n::s('This message has been automatically sent to you to confirm a change of your profile at %s.'), strip_tags($context['site_name']))."\n"
				."\n"
				.$context['url_to_home'].$context['url_to_root']."\n"
				."\n"
				.sprintf(i18n::s('Your nick name is %s'), $item['nick_name'])."\n"
				.sprintf(i18n::s('Authenticate with password %s'), $fields['confirm'])."\n" 	// $fields['password'] has been hashed
				."\n"
				.sprintf(i18n::s('On-line help is available at %s'), $context['url_to_home'].$context['url_to_root'].'help/')."\n"
				.sprintf(i18n::s('Thank you for your interest into %s.'), strip_tags($context['site_name']))."\n";

			// post the confirmation message
			include_once $context['path_to_root'].'shared/mailer.php';
			Mailer::notify($item['email'], $subject, $message);

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
			." WHERE (".$where.") AND (".$match.")"
			." ORDER BY users.nick_name, users.edit_date DESC"
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
		$fields['proxy_address']= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['signature']	= "TEXT NOT NULL";
		$fields['skype_address'] = "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['tags'] 		= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['vcard_agent']	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['vcard_label']	= "TEXT NOT NULL";
		$fields['vcard_organization']	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['vcard_title']	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['web_address']	= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['with_newsletters'] = "ENUM('Y','N') DEFAULT 'N' NOT NULL";
		$fields['with_sharing'] = "ENUM('N','V', 'M') DEFAULT 'N' NOT NULL";
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

		$output =& SQL::query_first($query, FALSE, $context['users_connection']);
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

		$output =& SQL::query_first($query, FALSE, $context['users_connection']);
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
		$result =& SQL::query($query, FALSE, $context['users_connection']);

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