<?php
/**
 * referral processing
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

class Referrals {

	/**
	 * process one single HTTP request
	 *
	 * This function removes any PHPSESSID data in the query string, if any
	 *
	 * @return void
	 *
	 * @see agents/referrals_hook.php
	 */
	function check_request() {
		global $context;

		// don't bother with HEAD requests
		if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
			return;

		// the target url
		if(!isset($_SERVER['REQUEST_URI']) || (!$url = $_SERVER['REQUEST_URI']))
			return;

		// only remember viewed pages and index pages
		if(!preg_match('/\/(index|view).php/', $url))
			return;

		// continue only if we have a referer
		if(!isset($_SERVER['HTTP_REFERER']) || (!$referer = $_SERVER['HTTP_REFERER']))
			return;

		// do not memorize cache referrals
		if(preg_match('/cache:/i', $referer))
			return;

		// block pernicious attacks
		$referer = strip_tags($referer);

		// only remember external referrals
		if(preg_match('/\b'.preg_quote(str_replace('www.', '', $context['host_name']), '/').'\b/i', $referer))
			return;

		// stop crawlers
		if(Surfer::is_crawler())
			return;

		// avoid banned sources
		include_once $context['path_to_root'].'servers/servers.php';
		if(preg_match(Servers::get_banned_pattern(), $referer))
			return;

		// normalize the referral, extract keywords, and domain
		list($referer, $domain, $keywords) = Referrals::normalize($referer);

		// if a record exists for this url
		$query = "SELECT id FROM ".SQL::table_name('referrals')." AS referrals"
			." WHERE referrals.url LIKE '".SQL::escape($url)."' AND referrals.referer LIKE '".SQL::escape($referer)."'";
		if(!$item =& SQL::query_first($query))
			return;

		// update figures
		if(isset($item['id'])) {
			$query = "UPDATE ".SQL::table_name('referrals')." SET"
				." hits=hits+1,"
				." stamp='".gmstrftime('%Y-%m-%d %H:%M:%S')."'"
				." WHERE id = ".$item['id'];

		// create a new record
		} else {

			// ensure the referer is accessible
			include_once $context['path_to_root'].'links/link.php';
			if(($content = Link::fetch($referer, '', '', 'agents/referrals.php')) === FALSE)
				return;

			// we have to find a reference to ourself in this page
			if(strpos($content, $context['url_to_home']) === FALSE)
				return;

			$query = "INSERT INTO ".SQL::table_name('referrals')." SET"
				." url='".SQL::escape($url)."',"
				." referer='".SQL::escape($referer)."',"
				." domain='".SQL::escape($domain)."',"
				." keywords='".SQL::escape($keywords)."',"
				." hits=1,"
				." stamp='".gmstrftime('%Y-%m-%d %H:%M:%S')."'";
		}

		// actual database update
		if(SQL::query($query) === FALSE)
			return;

		// prune with a probability of 1/100
		if(rand(1, 100) != 50)
			return;

		// purge oldest records -- 100 days = 8640000 seconds
		$query = "DELETE FROM ".SQL::table_name('referrals')
			." WHERE stamp < '".gmstrftime('%Y-%m-%d %H:%M:%S', time()-8640000)."'";
		SQL::query($query);
	}

	/**
	 * delete one referer
	 *
	 * @param string the referer to delete
	 *
	 * @see links/check.php
	 */
	function delete($referer) {
		global $context;

		$query = "DELETE FROM ".SQL::table_name('referrals')." WHERE referer LIKE '".SQL::escape($referer)."'";
		SQL::query($query);
	}

	/**
	 * list most recent referrals
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 *
	 * @see links/check.php
	 */
	function list_by_dates($offset=0, $count=10) {
		global $context;

		// the list of referrals
		$rows = array();
		$query = "SELECT * FROM ".SQL::table_name('referrals')
			." ORDER BY stamp DESC LIMIT ".$offset.', '.$count;

		return SQL::query($query, $context['connection']);
	}

	/**
	 * list most popular domains
	 *
	 * This function removes as many referrals coming from search engines as possible.
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 *
	 * @see agents/index.php
	 */
	function list_by_domain($offset=0, $count=10) {
		global $context;

		// the list of domains
		$query = "SELECT domain, MIN(referer) as referer, SUM(hits) as hits FROM ".SQL::table_name('referrals')
			." WHERE keywords = ''"
			." GROUP BY domain"
			." ORDER BY hits DESC LIMIT ".$offset.', '.$count;

		return SQL::query($query, $context['connection']);
	}

	/**
	 * list referrals for a given URL
	 *
	 * @param string the referenced url
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 *
	 * @see index.php
	 * @see actions/index.php
	 * @see actions/view.php
	 * @see agents/index.php
	 * @see articles/index.php
	 * @see articles/view.php
	 * @see categories/index.php
	 * @see categories/view.php
	 * @see codes/index.php
	 * @see collections/index.php
	 * @see comments/index.php
	 * @see comments/view.php
	 * @see feeds/index.php
	 * @see files/index.php
	 * @see files/view.php
	 * @see images/index.php
	 * @see images/view.php
	 * @see letters/index.php
	 * @see links/index.php
	 * @see locations/index.php
	 * @see locations/view.php
	 * @see overlays/index.php
	 * @see scripts/index.php
	 * @see scripts/view.php
	 * @see sections/index.php
	 * @see sections/view.php
	 * @see servers/index.php
	 * @see servers/view.php
	 * @see services/index.php
	 * @see skins/index.php
	 * @see smileys/index.php
	 * @see tables/index.php
	 * @see tables/view.php
	 * @see users/index.php
	 * @see users/view.php
	 */
	function list_by_hits_for_url($url, $offset=0, $count=10) {
		global $context;

		// the front page is a special case
		if(($url == '/') || ($url == '/index.php') || ($url == $context['url_to_root_parameter'].'index.php'))
			$where = "(url LIKE '/') OR (url LIKE '/index.php') OR (url LIKE '".$context['url_to_root_parameter']."index.php')";
		else
			$where = "url LIKE '".SQL::escape($url)."'";

		// the list of referrals
		$query = "SELECT * FROM ".SQL::table_name('referrals')
			." WHERE ".$where
			." ORDER BY hits DESC LIMIT ".$offset.', '.$count;
		if(!$result = SQL::query($query, $context['connection']))
			return NULL;

		// empty list
		if(!SQL::count($result))
			return NULL;

		// render a compact list, and including the number of referrals
		$items = array();
		while($row =& SQL::fetch($result)) {

			// hack to make this compliant to XHTML
			$url = str_replace('&', '&amp;', $row['referer']);
			if(isset($row['keywords']) && $row['keywords'])
				$items[$url] = array('', $row['keywords'], ' ('.$row['hits'].')', 'basic', '');
			else
				$items[$url] = array('', $row['domain'], ' ('.$row['hits'].')', 'basic', '');
		}
		if(count($items))
			return Skin::build_list($items, 'compact');

		return NULL;
	}

	/**
	 * list most popular referrals
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 */
	function list_by_hits($offset=0, $count=10) {
		global $context;

		// the list of referrals
		$query = "SELECT referer, sum(hits) as hits FROM ".SQL::table_name('referrals')
			." GROUP BY referer"
			." ORDER BY hits DESC LIMIT ".$offset.', '.$count;
		if($result = SQL::query($query, $context['connection'])) {
			while($row =& SQL::fetch($result)) {
				$url = $row['referer'];
				$items[$url] = $row['hits'];
			}
		}

		return $items;
	}

	/**
	 * list most popular keywords
	 *
	 * @param int the offset from the start of the list; usually, 0 or 1
	 * @param int the number of items to display
	 *
	 * @see agents/index.php
	 */
	function list_by_keywords($offset=0, $count=10) {
		global $context;

		// the list of domains
		$query = "SELECT keywords, MIN(referer) as referer, SUM(hits) as hits FROM ".SQL::table_name('referrals')
			." GROUP BY keywords"
			." ORDER BY hits DESC LIMIT ".$offset.', '.$count;

		return SQL::query($query, $context['connection']);
	}

	/**
	 * normalize an external reference
	 *
	 * This function strips noise attributes from search engines
	 *
	 * @param string the raw reference
	 * @return an array( normalized string, search keywords )
	 */
	function normalize($link) {
		global $context;

		// get the query string, if any
		$tokens = explode('?', $link, 2);
		$link = $tokens[0];
		$query_string = '';
		if(isset($tokens[1]))
			$query_string = $tokens[1];

		// split the query string in variables, if any
		$attributes = array();
		if($query_string) {
			$tokens = explode('&', $query_string);
			foreach($tokens as $token) {
				list($name, $value) = explode('=', $token);
				$name = urldecode($name);
				$value = urldecode($value);

				// strip any PHPSESSID data
				if(preg_match('/^PHPSESSID/i', $name))
					continue;

				// strip any JSESSIONID data
				if(preg_match('/^jsessionid/i', $name))
					continue;

				// remember this variable
				$attributes[ $name ] = $value;
			}
		}

		// looking for keywords
		$keywords = '';

		// link options, if any
		$suffix = '';

		// coming from all the web
		if(preg_match('/\balltheweb\b.+/', $link) && isset($attributes['q'])) {
			$attributes = array( 'q' => $attributes['q'] );
			$keywords = $attributes['q'];

		// coming from altavista
		} elseif(preg_match('/\baltavista\b.+/', $link) && isset($attributes['q'])) {
			$attributes = array( 'q' => $attributes['q'] );
			$keywords = $attributes['q'];

		// coming from aol
		} elseif(preg_match('/\baol\b.+/', $link) && isset($attributes['q'])) {
			$attributes = array( 'q' => $attributes['q'] );
			$keywords = $attributes['q'];

		// coming from ask
		} elseif(preg_match('/\bask\b.+/', $link) && isset($attributes['q'])) {
			$attributes = array( 'q' => $attributes['q'] );
			$keywords = $attributes['q'];

		// coming from feedster
		} elseif(preg_match('/\bfeedster\b.+/', $link) && isset($attributes['q'])) {
			$attributes = array( 'q' => $attributes['q'] );
			$keywords = $attributes['q'];

		// coming from google
		} elseif(preg_match('/\bgoogle\b.+/', $link) && isset($attributes['q'])) {

			// signal to Google the charset to be used
			if(isset($attributes['ie']))
				$suffix = '&ie='.urlencode($attributes['ie']);

			$attributes = array( 'q' => $attributes['q'] );
			$keywords = $attributes['q'];

		// coming from msn
		} elseif(preg_match('/\bmsn\b.+/', $link) && isset($attributes['q'])) {
			$attributes = array( 'q' => $attributes['q'] );
			$keywords = $attributes['q'];

		// coming from yahoo
		} elseif(preg_match('/\byahoo\b.+/', $link) && isset($attributes['p'])) {
			$attributes = array( 'p' => $attributes['p'] );
			$keywords = $attributes['p'];
		}

		// rebuild a full link
		$query_string = '';
		foreach($attributes as $name => $value) {
			if($query_string)
				$query_string .= '&';
			$query_string .= urlencode($name).'='.urlencode($value);
		}
		if($query_string)
			$link .= '?'.$query_string.$suffix;

		// extract the referer domain
		$domain = preg_replace("/^\w+:\/\//i", "", $link);
		$domain = preg_replace("/^www\./i", "", $domain);
		$domain = preg_replace("/\/.*/i", "", $domain);

		// transcode keywords from utf-8 to unicode, and make it a safe string to display
		if($keywords)
			$keywords = utf8::to_unicode(htmlspecialchars($keywords));

		// return normalized elements
		return array($link, trim($domain), trim($keywords));
	}

	/**
	 * create tables for referrals
	 *
	 * @see agents/referrals_hook.php
	 */
	function setup() {
		global $context;

		$fields = array();
		$fields['id']			= "MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT";
		$fields['url']			= "TEXT NOT NULL";
		$fields['referer']		= "TEXT NOT NULL";
		$fields['domain']		= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['keywords'] 	= "VARCHAR(255) DEFAULT ''";
		$fields['hits'] 		= "INT UNSIGNED DEFAULT 0 NOT NULL";
		$fields['stamp']		= "DATETIME";

		$indexes = array();
		$indexes['PRIMARY KEY'] 	= "(id)";
		$indexes['INDEX url']		= "(url(255))";
		$indexes['INDEX referer']	= "(referer(255))";
		$indexes['INDEX domain']	= "(domain)";
		$indexes['INDEX keywords']	= "(keywords)";
		$indexes['INDEX hits']		= "(hits)";
		$indexes['INDEX stamp'] 	= "(stamp)";

		return SQL::setup_table('referrals', $fields, $indexes);
	}

	/**
	 * get some statistics
	 *
	 * @return the number of rows in table
	 *
	 * @see control/index.php
	 */
	function &stat() {
		global $context;

		// select among available items
		$query = "SELECT COUNT(*) as count FROM ".SQL::table_name('referrals');

		$output =& SQL::query_first($query);
		return $output;
	}

}

// load localized strings
if(is_callable(array('i18n', 'bind')))
	i18n::bind('agents');

?>