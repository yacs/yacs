<?php
/**
 * the database abstraction layer for servers
 *
 * @author Bernard Paques
 * @author Florent
 * @author GnapZ
 * @tester Fw_crocodile
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Servers {

	/**
	 * retrieve endpoints of last calls
	 *
	 * This is useful to list all servers notified after a publication.
	 *
	 * @param string title of the folded box generated
	 * @return mixed text to be integrated into the page, or array with one item per recipient, or ''
	 */
	public static function build_endpoints($title=NULL) {
		global $context;

		// nothing to show
		if(!Surfer::get_id() || !isset($context['servers_endpoints']) || !$context['servers_endpoints'])
			return '';

		// return the bare list
		if(!$title)
			return $context['servers_endpoints'];

		// build a nice list
		$list = array();
		foreach($context['servers_endpoints'] as $recipient)
			$list[] = htmlspecialchars($recipient);
		return Skin::build_box($title, Skin::finalize_list($list, 'compact'), 'folded');

	}

	/**
	 * clear cache entries for one item
	 *
	 * @param array item attributes
	 */
	public static function clear(&$item) {

		// where this item can be displayed
		$topics = array('servers');

		// clear this page
		if(isset($item['id']))
			$topics[] = 'server:'.$item['id'];

		// clear the cache
		Cache::clear($topics);

	}

	/**
	 * delete one server in the database
	 *
	 * @param int the id of the server to delete
	 * @return boolean TRUE on success, FALSE otherwise
	 *
	 * @see servers/delete.php
	 */
	public static function delete($id) {
		global $context;

		// id cannot be empty
		if(!$id || !is_numeric($id))
			return FALSE;

		// delete related items
		Anchors::delete_related_to('server:'.$id);

		// delete the record in the database
		$query = "DELETE FROM ".SQL::table_name('servers')." WHERE id = ".$id;
		if(SQL::query($query) === FALSE)
			return FALSE;

		// job done
		return TRUE;
	}

	/**
	 * get one server by id
	 *
	 * This public static function can be used to search for one server entry, either by id
	 * or submitting its nick name.
	 *
	 * @param int the id of the server, or its nick name
	 * @return the resulting $item array, with at least keys: 'id', 'title', etc.
	 */
	public static function get($id) {
		global $context;

		// sanity check
		if(!$id) {
			$output = NULL;
			return $output;
		}

		// search by id
		if(is_numeric($id))
			$query = "SELECT * FROM ".SQL::table_name('servers')." AS servers"
				." WHERE (servers.id = ".SQL::escape((integer)$id).")";

		// or look for given name of handle
		else
			$query = "SELECT * FROM ".SQL::table_name('servers')." AS servers"
				." WHERE (servers.host_name LIKE '".SQL::escape($id)."')"
				." ORDER BY edit_date DESC LIMIT 1";

		// do the job
		$output = SQL::query_first($query);
		return $output;
	}

	/**
	 * list banned servers
	 *
	 * This function is used to limit external links saved into the database.
	 * It makes a pattern suitable for [code]preg_match()[/code] out of this list.
	 *
	 * Banned hosts and domains are manually put in the configuration file for servers,
	 * as a list of words separated by spaces or commas.
	 *
	 * Following domains are always banned:
	 * - mail.yahoo.com
	 * - gmail.google.com
	 * - www.laposte.net
	 *
	 * Here is an example of usage:
	 * [php]
	 * // the url to check
	 * $url = ...
	 *
	 * // list banned servers
	 * include_once '../servers/servers.php';
	 * $banned_pattern = Servers::get_banned_pattern();
	 *
	 * // skip banned hosts
	 * if(preg_match($banned_pattern, $url))
	 *	   continue;
	 * [/php]
	 *
	 * This function will always return a valid pattern string, even if no configuration file has been saved.
	 *
	 * @return a string to be used in [code]preg_match()[/code]
	 *
	 * @see agents/referrals.php
	 * @see feeds/feeds.php
	 * @see servers/test.php
	 */
	public static function get_banned_pattern() {
		global $context;

		// use the configuration file
		Safe::load('parameters/servers.include.php');

		// get parameter
		if(isset($context['banned_hosts']) && $context['banned_hosts'])
			$banned = $context['banned_hosts'];
		else
			$banned = '.kanoodle.com, \bporn, \bsex';

		// quote tokens to build a pattern
		$banned_pattern = array();
		$banned_tokens = preg_split('/[\s,]+/', $banned, -1, PREG_SPLIT_NO_EMPTY);
		foreach($banned_tokens as $banned_token)
			$banned_pattern[] = str_replace(array('.', '/'), array('\.', '\/'), $banned_token);

		return '/('.implode('|', $banned_pattern).')/';
	}

	/**
	 * get one server by url
	 *
	 * This function can be used to search for one server entry.
	 *
	 * @param string one of the URLs related to this server
	 * @return the resulting $item array, with at least keys: 'id', 'title', etc.
	 */
	public static function get_by_url($url) {
		global $context;

		// sanity check
		if(!$url)
			return NULL;
		$url = SQL::escape($url);

		// select among available items
		$query = 'SELECT * FROM '.SQL::table_name('servers')
			." WHERE (main_url LIKE '$url') OR (feed_url LIKE '$url')";

		$output = SQL::query_first($query);
		return $output;
	}

	/**
	 * build a reference to a server
	 *
	 * Depending on parameter '[code]with_friendly_urls[/code]' and on action,
	 * following results can be observed:
	 *
	 * - view - servers/view.php?id=123 or servers/view.php/123 or server-123
	 *
	 * - other - servers/edit.php?id=123 or servers/edit.php/123 or server-edit/123
	 *
	 * @param int the id of the server to handle
	 * @param string the expected action ('view', 'print', 'edit', 'delete', ...)
	 * @return string a normalized reference
	 *
	 * @see control/configure.php
	 */
	public static function get_url($id, $action='view') {
		global $context;

		// check the target action
		if(!preg_match('/^(delete|edit|test|view)$/', $action))
			return 'servers/'.$action.'.php?id='.urlencode($id);

		// normalize the link
		return normalize_url(array('servers', 'server'), $action, $id);
	}

	/**
	 * list newest servers
	 *
	 * Used to list servers that have been ping us, or that have been manually added, recently.
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $icon)
	 *
	 * @see index.php
	 * @see servers/index.php
	 */
	public static function list_by_date($offset=0, $count=10, $variant='full') {
		global $context;

		// select among active and restricted items
		$where = "servers.active='Y'";
		if(Surfer::is_member())
			$where .= " OR servers.active='R'";
		if(Surfer::is_associate())
			$where .= " OR servers.active='N'";

		// limit the scope of the request
		$query = 'SELECT servers.* FROM '.SQL::table_name('servers').' AS servers'
			.' WHERE ('.$where.')'
			.' ORDER BY servers.edit_date DESC, servers.title LIMIT '.$offset.','.$count;

		// the list of servers
		$output = Servers::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list servers that will feed us
	 *
	 * This script is used internally to locate servers to be polled at feeding times.
	 * Profiles that have not been polled for a long time are returned first.
	 *
	 * All entries are seeked, and the active field is not taken into account.
	 *
	 * @see feeders/feeders.php
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $type, $icon)
	 */
	public static function list_for_feed($offset=0, $count=10, $variant='feed') {
		global $context;

		// limit the scope of the request
		$query = 'SELECT * FROM '.SQL::table_name('servers')
			." WHERE (submit_feed = 'Y')"
			.' ORDER BY stamp_date, edit_date DESC, title LIMIT '.$offset.','.$count;

		// the list of servers
		$output = Servers::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list servers to be pinged
	 *
	 * This script is used internally to locate servers to be pinged on content change.
	 *
	 * All entries are seeked, and the active field is not taken into account.
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $type, $icon)
	 *
	 * @see articles/publish.php
	 */
	public static function list_for_ping($offset=0, $count=10, $variant='ping') {
		global $context;

		// limit the scope of the request
		$query = 'SELECT * FROM '.SQL::table_name('servers')
			." WHERE (submit_ping = 'Y')"
			.' ORDER BY edit_date DESC, title LIMIT '.$offset.','.$count;

		// the list of servers
		$output = Servers::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list servers to be searched
	 *
	 * This script is used internally to locate servers to which search requests may be submitted.
	 *
	 * All entries are seeked, and the active field is not taken into account.
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 * @param string the list variant, if any
	 * @return NULL on error, else an ordered array with $url => ($prefix, $label, $suffix, $type, $icon)
	 *
	 * @see search.php
	 */
	public static function list_for_search($offset=0, $count=10, $variant='search') {
		global $context;

		// limit the scope of the request
		$query = 'SELECT * FROM '.SQL::table_name('servers')
			." WHERE (submit_search = 'Y')"
			.' ORDER BY edit_date DESC, title LIMIT '.$offset.','.$count;

		// the list of servers
		$output = Servers::list_selected(SQL::query($query), $variant);
		return $output;
	}

	/**
	 * list selected servers
	 *
	 * If variant is provided as a string, the functions looks for a script featuring this name.
	 * E.g., for variant 'compact', the file 'servers/layout_servers_as_compact.php' is loaded.
	 * If no file matches then the default 'servers/layout_servers.php' script is loaded.
	 *
	 * @param resource result of database query
	 * @param string 'full', etc or object, i.e., an instance of Layout_Interface
	 * @return NULL on error, else an ordered array with $url => array ($prefix, $label, $suffix, $type, $icon)
	 */
	public static function list_selected($result, $variant='compact') {
		global $context;

		// no result
		if(!$result) {
			$output = NULL;
			return $output;
		}

		// special layouts
		if(is_object($variant)) {
			$output = $variant->layout($result);
			return $output;
		}

		// one of regular layouts
		switch($variant) {

		case 'compact':
			include_once $context['path_to_root'].'servers/layout_servers_as_compact.php';
			$layout = new Layout_servers_as_compact();
			$output = $layout->layout($result);
			return $output;

		case 'dates':
			include_once $context['path_to_root'].'servers/layout_servers_as_dates.php';
			$layout = new Layout_servers_as_dates();
			$output = $layout->layout($result);
			return $output;

		case 'feed':
			$items = array();
			while($item = SQL::fetch($result)) {

				// stamp of last feed
				$stamp = NULL_DATE;
				if($item['stamp_date'] > NULL_DATE)
					$stamp = $item['stamp_date'];

				// prepare for feed
				if($item['feed_url'])
					$items[$item['id']] = array($item['feed_url'], strip_tags($item['title']), $item['anchor'], $stamp);
			}
			return $items;

		case 'ping':
			$items = array();
			while($item = SQL::fetch($result)) {
				$url = Servers::get_url($item['id']);

				// prepare for ping
				if($item['ping_url'])
					$items[$url] = array($item['ping_url'], strip_tags($item['title']), $item['anchor']);
			}
			return $items;

		case 'search':
			$items = array();
			while($item = SQL::fetch($result)) {
				$url = Servers::get_url($item['id']);

				// prepare for search
				if($item['search_url'])
					$items[$url] = array($item['search_url'], strip_tags($item['title']), $item['anchor']);
			}
			return $items;

		default:
			include_once $context['path_to_root'].'servers/layout_servers.php';
			$layout = new Layout_servers();
			$output = $layout->layout($result);
			return $output;

		}

	}

	/**
	 * notify servers about a new page
	 *
	 * @param string page URL
	 * @param string server name
	 */
	public static function notify($link, $title=NULL) {
		global $context;

		if(!$title)
			$title = $context['site_name'];

		// the list of recipients contacted during overall script execution
		if(!isset($context['servers_endpoints']))
			$context['servers_endpoints'] = array();

		// list servers to be advertised
		if($servers = Servers::list_for_ping(0, COMPACT_LIST_SIZE, 'ping')) {

			// ping each server
			include_once $context['path_to_root'].'services/call.php';
			foreach($servers as $server_url => $attributes) {
				list($server_ping, $server_label) = $attributes;

				$milestone = get_micro_time();
				$result = Call::invoke($server_ping, 'weblogUpdates.ping', array(strip_tags($title), $context['url_to_home'].$context['url_to_root'].$link), 'XML-RPC');

				if($result[0])
					$server_label .= ' ('.round(get_micro_time() - $milestone, 2).' sec.)';

				$context['servers_endpoints'][] = $server_label;
			}

		}

	}

	/**
	 * create or update a server entry
	 *
	 * This function is called when a remote server pings us, to mean its content has changed.
	 *
	 * If the provided URL does not exist, a new server profile is created.
	 * Else the profile is updated only if ping is still allowed for this server profile.
	 *
	 * @see services/ping.php
	 *
	 * @param string the title of the updated server
	 * @param string the link to it
	 * @return string either a null string, or some text describing an error to be inserted into the html response
	 */
	public static function ping($title, $url) {
		global $context;

		// the entry already exists
		if($item = Servers::get_by_url($url)) {

			// ensure this operation is allowed
			if(isset($item['process_ping']) && ($item['process_ping'] != 'Y'))
				return 'You are not allowed to perform this operation.';

			// clear the cache for this server
			Cache::clear('server:'.$item['id']);

			// update the existing record
			$query = "UPDATE ".SQL::table_name('servers')." SET "
				."title='".SQL::escape($title)."', "
				."edit_name='ping', "
				."edit_id=0, "
				."edit_address='', "
				."edit_date='".gmstrftime('%Y-%m-%d %H:%M:%S')."'"
				." WHERE id = ".SQL::escape($item['id']);

			if(SQL::query($query) === FALSE)
				return 'ERROR';

		// the entry does not exist yet
		} else {

			// create a new record
			$query = "INSERT INTO ".SQL::table_name('servers')." SET "
				."title='".SQL::escape($title)."', "
				."main_url='".SQL::escape($url)."', "
				."edit_name='".SQL::escape($title)."', "
				."edit_id=0, "
				."edit_address='', "
				."edit_date='".gmstrftime('%Y-%m-%d %H:%M:%S')."'";

			if(SQL::query($query) === FALSE)
				return 'ERROR';

		}

		// clear the cache for server profiles
		Cache::clear('servers');

		// end of job
		return NULL;
	}

	/**
	 * post a new server or an updated server
	 *
	 * @see servers/edit.php
	 * @see servers/populate.php
	 *
	 * @param array an array of fields
	 * @return string either a null string, or some text describing an error to be inserted into the html response
	**/
	public static function post(&$fields) {
		global $context;

		// no title
		if(!$fields['title'])
			return i18n::s('No title has been provided.');

		// clear the cache for servers
		Cache::clear('servers');
		if(isset($fields['id']))
			Cache::clear('server:'.$fields['id']);

		// protect from hackers
		if(isset($fields['main_url']))
			$fields['main_url'] = encode_link($fields['main_url']);
		if(isset($fields['feed_url']))
			$fields['feed_url'] = encode_link($fields['feed_url']);
		if(isset($fields['ping_url']))
			$fields['ping_url'] = encode_link($fields['ping_url']);
		if(isset($fields['search_url']))
			$fields['search_url'] = encode_link($fields['search_url']);
		if(isset($fields['monitor_url']))
			$fields['monitor_url'] = encode_link($fields['monitor_url']);

		// make a host name
		if(!isset($fields['host_name']))
			$fields['host_name'] = '';
		if(!$fields['host_name']) {
			if(($parts = parse_url($fields['main_url'])) && isset($parts['host']))
				$fields['host_name'] = $parts['host'];
		}
		if(!$fields['host_name']) {
			if(($parts = parse_url($fields['feed_url'])) && isset($parts['host']))
				$fields['host_name'] = $parts['host'];
		}
		if(!$fields['host_name']) {
			if(($parts = parse_url($fields['ping_url'])) && isset($parts['host']))
				$fields['host_name'] = $parts['host'];
		}
		if(!$fields['host_name']) {
			if(($parts = parse_url($fields['monitor_url'])) && isset($parts['host']))
				$fields['host_name'] = $parts['host'];
		}
		if(!$fields['host_name']) {
			if(($parts = parse_url($fields['search_url'])) && isset($parts['host']))
				$fields['host_name'] = $parts['host'];
		}

		// set default values
		if(!isset($fields['active']) || !$fields['active'])
			$fields['active'] = 'Y';
		if(!isset($fields['process_ping']) || ($fields['process_ping'] != 'Y'))
			$fields['process_ping'] = 'N';
		if(!isset($fields['process_monitor']) || ($fields['process_monitor'] != 'Y'))
			$fields['process_monitor'] = 'N';
		if(!isset($fields['process_search']) || ($fields['process_search'] != 'Y'))
			$fields['process_search'] = 'N';

		// set default values for this editor
		Surfer::check_default_editor($fields);

		// update the existing record
		if(isset($fields['id'])) {

			// id cannot be empty
			if(!isset($fields['id']) || !is_numeric($fields['id']))
				return i18n::s('No item has the provided id.');

			// update the existing record
			$query = "UPDATE ".SQL::table_name('servers')." SET "
				."title='".SQL::escape($fields['title'])."', "
				."description='".SQL::escape($fields['description'])."', "
				."main_url='".SQL::escape($fields['main_url'])."', "
				."anchor='".SQL::escape(isset($fields['anchor']) ? $fields['anchor'] : '')."', "

				."submit_feed='".SQL::escape(($fields['submit_feed'] == 'Y') ? 'Y' : 'N')."', "
				."feed_url='".SQL::escape($fields['feed_url'])."', "

				."submit_ping='".SQL::escape(($fields['submit_ping'] == 'Y') ? 'Y' : 'N')."', "
				."ping_url='".SQL::escape($fields['ping_url'])."', "
				."process_ping='".SQL::escape(($fields['process_ping'] == 'Y') ? 'Y' : 'N')."', "

				."submit_monitor='".SQL::escape(($fields['submit_monitor'] == 'Y') ? 'Y' : 'N')."', "
				."monitor_url='".SQL::escape($fields['monitor_url'])."', "
				."process_monitor='".SQL::escape(($fields['process_monitor'] == 'Y') ? 'Y' : 'N')."', "

				."submit_search='".SQL::escape(($fields['submit_search'] == 'Y') ? 'Y' : 'N')."', "
				."search_url='".SQL::escape($fields['search_url'])."', "
				."process_search='".SQL::escape(($fields['process_search'] == 'Y') ? 'Y' : 'N')."',"

				."host_name='".SQL::escape($fields['host_name'])."',"
				."active='".SQL::escape($fields['active'])."'";

			// maybe a silent update
			if(!isset($fields['silent']) || ($fields['silent'] != 'Y')) {
				$query .= ", "
				."edit_name='".SQL::escape($fields['edit_name'])."', "
				."edit_id=".SQL::escape($fields['edit_id']).", "
				."edit_address='".SQL::escape($fields['edit_address'])."', "
				."edit_date='".SQL::escape($fields['edit_date'])."'";
			}

			$query .= " WHERE id = ".SQL::escape($fields['id']);

			if(SQL::query($query) === FALSE)
				return $query.BR.SQL::error();

		// insert a new record
		} else {

			// always remember the date
			$query = "INSERT INTO ".SQL::table_name('servers')." SET ";
			if(isset($fields['id']) && $fields['id'])
				$query .= "id='".SQL::escape($fields['id'])."',";
			$query .= "title='".SQL::escape($fields['title'])."', "
				."host_name='".SQL::escape($fields['host_name'])."', "
				."description='".SQL::escape(isset($fields['description']) ? $fields['description'] : '')."', "
				."main_url='".SQL::escape($fields['main_url'])."', "
				."anchor='".SQL::escape(isset($fields['anchor']) ? $fields['anchor'] : 'category:1')."', "

				."submit_feed='".SQL::escape(($fields['submit_feed'] == 'Y') ? 'Y' : 'N')."', "
				."feed_url='".SQL::escape($fields['feed_url'])."', "

				."submit_ping='".SQL::escape(($fields['submit_ping'] == 'Y') ? 'Y' : 'N')."', "
				."ping_url='".SQL::escape($fields['ping_url'])."', "
				."process_ping='".SQL::escape(($fields['process_ping'] == 'Y') ? 'Y' : 'N')."', "

				."submit_monitor='".SQL::escape(($fields['submit_monitor'] == 'Y') ? 'Y' : 'N')."', "
				."monitor_url='".SQL::escape($fields['monitor_url'])."', "
				."process_monitor='".SQL::escape(($fields['process_monitor'] == 'Y') ? 'Y' : 'N')."', "

				."submit_search='".SQL::escape(($fields['submit_search'] == 'Y') ? 'Y' : 'N')."', "
				."search_url='".SQL::escape($fields['search_url'])."', "
				."process_search='".SQL::escape(($fields['process_search'] == 'Y') ? 'Y' : 'N')."', "

				."active='".SQL::escape($fields['active'])."', "
				."edit_name='".SQL::escape($fields['edit_name'])."', "
				."edit_id=".SQL::escape($fields['edit_id']).", "
				."edit_address='".SQL::escape($fields['edit_address'])."', "
				."edit_date='".SQL::escape($fields['edit_date'])."'";

			if(SQL::query($query) === FALSE)
				return $query.BR.SQL::error();

		}

		// end of job
		return NULL;
	}

	/**
	 * create or alter tables for servers
	 *
	 * @see control/setup.php
	 */
	public static function setup() {
		global $context;

		$fields = array();
		$fields['id']			= "MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT";
		$fields['title']		= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['host_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['description']	= "TEXT NOT NULL";
		$fields['main_url'] 	= "VARCHAR(128) DEFAULT '' NOT NULL";

		$fields['submit_feed']	= "ENUM('Y','N') DEFAULT 'N' NOT NULL";
		$fields['feed_url'] 	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['anchor']		= "VARCHAR(64) DEFAULT 'category:1' NOT NULL";

		$fields['submit_ping']	= "ENUM('Y','N') DEFAULT 'N' NOT NULL";
		$fields['ping_url'] 	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['process_ping'] = "ENUM('Y','N') DEFAULT 'Y' NOT NULL";

		$fields['submit_monitor'] = "ENUM('Y','N') DEFAULT 'N' NOT NULL";
		$fields['monitor_url']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['process_monitor'] = "ENUM('Y','N') DEFAULT 'Y' NOT NULL";

		$fields['submit_search'] = "ENUM('Y','N') DEFAULT 'N' NOT NULL";
		$fields['search_url']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['process_search'] = "ENUM('Y','N') DEFAULT 'Y' NOT NULL";

		$fields['stamp_date']	= "DATETIME";

		$fields['active']		= "ENUM('Y','R','N') DEFAULT 'Y' NOT NULL";
		$fields['edit_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_id']		= "MEDIUMINT DEFAULT 0 NOT NULL";
		$fields['edit_address'] = "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_date']	= "DATETIME";

		$indexes = array();
		$indexes['PRIMARY KEY'] 	= "(id)";
		$indexes['INDEX active']		= "(active)";
		$indexes['INDEX edit_date'] = "(edit_date)";
		$indexes['INDEX edit_id']	= "(edit_id)";
		$indexes['INDEX host_name'] = "(host_name)";
		$indexes['INDEX main_url']	= "(main_url)";
		$indexes['INDEX stamp_date']	= "(stamp_date)";
		$indexes['INDEX submit_monitor']	= "(submit_monitor)";
		$indexes['INDEX submit_ping']	= "(submit_ping)";
		$indexes['INDEX submit_search'] = "(submit_search)";
		$indexes['INDEX title'] 	= "(title)";
		$indexes['FULLTEXT INDEX']	= "full_text(title, description)";

		return SQL::setup_table('servers', $fields, $indexes);
	}

	/**
	 * stamp one server profile
	 *
	 * This is used to remember the date of last feed.
	 *
	 * $param int the id of the server to update
	 */
	public static function stamp($id) {
		global $context;

		// sanity check
		if(!isset($id) || !$id)
			return;

		// update the record of authenticated user
		$query = "UPDATE ".SQL::table_name('servers')
			." SET stamp_date='".gmstrftime('%Y-%m-%d %H:%M:%S')."'"
			." WHERE id = ".SQL::escape($id);

		// do not report on error
		SQL::query($query, TRUE);

	}

	/**
	 * get some statistics
	 *
	 * @return the resulting ($count, $min_date, $max_date) array
	 */
	public static function stat() {
		global $context;

		// select among active and restricted items
		$where = "servers.active='Y'";
		if(Surfer::is_member())
			$where .= " OR servers.active='R'";
		if(Surfer::is_associate())
			$where .= " OR servers.active='N'";

		// select among available items
		$query = "SELECT COUNT(*) as count, MIN(edit_date) as oldest_date, MAX(edit_date) as newest_date"
			.' FROM '.SQL::table_name('servers').' AS servers'
			.' WHERE ('.$where.')';

		$output = SQL::query_first($query);
		return $output;
	}

}

// load localized strings
if(is_callable(array('i18n', 'bind')))
	i18n::bind('servers');

?>