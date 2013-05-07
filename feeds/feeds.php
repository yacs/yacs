<?php
/**
 * get news
 *
 * This data abstraction for feeds provides two main functions, plus several utility functions:
 * - [code]get_local_news()[/code] - retrieve local news
 * - [code]get_remote_news()[/code] - retrieve news collected from remote sites
 * - [code]get_remote_news_from()[/code] - actual news fetching from one feeding site
 * - [code]tick_hook()[/code] - trigger feeding in the background
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Dobliu
 * @tester NickR
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Feeds {

	/**
	 * decode a date
	 *
	 * @link http://www.w3.org/TR/NOTE-datetime Date and Time Formats, a profile of ISO 8601
	 *
	 * @param string some date
	 * @return int a valid time stamp, or -1
	 */
	public static function decode_date($date) {
		global $context;

		// match wc3dtf
		if(preg_match("/(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})?(?:([-+])(\d{2}):?(\d{2})|(Z))?/", $date, $matches)) {

			// split date components
			list($year, $month, $day, $hours, $minutes, $seconds) = array($matches[1], $matches[2], $matches[3], $matches[4], $matches[5], $matches[6]);

			// calc epoch for current date assuming GMT
			$stamp = gmmktime((int)$hours, (int)$minutes, (int)$seconds, (int)$month, (int)$day, (int)$year);

			// zulu time, aka GMT
			if($matches[9] == 'Z')
				$offset = 0;

			else {
				list($tz_mod, $tz_hour, $tz_min) = array($matches[7], $matches[8], $matches[9]);

				// zero out the variables
				if(!$tz_hour)
					$tz_hour = 0;
				if(!$tz_min)
					$tz_min = 0;

				$offset = (($tz_hour*60)+$tz_min)*60;

				// is timezone ahead of GMT?  then subtract offset
				if($tz_mod == '-')
					$offset = $offset * -1;

			}
			return ($stamp + $offset);

		// everything else
		} else
			return strtotime($date);

	}

	/**
	 * get current news from this server
	 *
	 * Actually, this function lists most recent published articles.
	 *
	 * @param int the number of items to list
	 * @param 'feed' to get a regular feed, or 'contents' to get everything
	 * @return an array of array($time, $title, $author, $section, $image, $description)
	 */
	public static function get_local_news($count=20, $variant='feed') {
		global $context;

		// list the newest published articles
		return Articles::list_by('publication', 0, $count, $variant);

	}

	/**
	 * get news from remote servers
	 *
	 * This function extracts from the database most recent links fetched from feeders.
	 *
	 * By default, up to 20 items are displayed.
	 *
	 * @param the maximum number of news to fetch
	 * @param the expected variant to use
	 * @return an array to use with [code]Skin::build_list()[/code], or NULL
	 *
	 * @see feeds/index.php
	 */
	public static function get_remote_news($count=20, $variant='compact') {
		global $context;

		// number of items to display
		if($count < 3)
			$count = 10;
		if($count > 50)
			$count = 50;

		// get them from the database
		include_once $context['path_to_root'].'links/links.php';
		return Links::list_news(0, $count, $variant);
	}

	/**
	 * get news from a remote server
	 *
	 * This function is aiming to run silently, therefore errors are logged in a file.
	 * To troubleshoot feeders you can configure the debugging facility in the
	 * configuration panel for feeds (parameter [code]debug_feeds[/code], at [script]feeds/configure.php[/script]).
	 *
	 * @param string the URL to use to fetch news
	 * @return either an array of items, or NULL on error
	 *
	 * @see feeds/feeds.php
	 * @see servers/test.php
	 */
	public static function get_remote_news_from($feed_url) {
		global $context;

		// ensure we are using adequate feeding parameters
		Safe::load('parameters/feeds.include.php');

		// parse the target URL
		$items = @parse_url($feed_url);

		// stop here if no host
		if(!isset($items['host']) || !$items['host']) {
			Logger::remember('feeds/feeds.php: No valid host at '.$feed_url);
			return NULL;
		}

		// use simplepie
		include_once $context['path_to_root'].'included/simplepie.inc';
		$feed = new SimplePie($feed_url, $context['path_to_root'].'temporary');
		$feed->init();

		// make an array
		$items = array();
		foreach($feed->get_items() as $item) {

			$category = '';
			if(($categories = $item->get_categories()) && is_array($categories))
				foreach($categories as $one)
					$category .= $one->get_label().', ';
			if($category)
				$category = rtrim($category, ', ');

			$items[] = array(
				'author' => $item->get_author(),
				'category' => $category,
				'description' => $item->get_content(),
				'link' => $item->get_permalink(),
				'pubDate' => $item->get_date('r'),
				'title' => $item->get_title()
				);
		}

		// and returns it
		return $items;
	}

	/**
	 * build a reference to a feed
	 *
	 * Depending on parameter '[code]with_friendly_urls[/code]' and on action,
	 * following results can be observed:
	 *
	 * - atom - feeds/atom.php or feeds/atom
	 * - articles - articles/feed.php or feeds/articles
	 * - comments - comments/feed.php or feeds/comments
	 * - files - files/feed.php or feeds/files
	 * - opml - feeds/describe.php or feeds/opml
	 * - rss - feeds/rss.php or feeds/rss
	 * - foo_bar - feeds/foo_bar.php or feeds/foo_bar
	 *
	 * @param string the expected feed ('atom', 'articles', 'comments', 'files', ...)
	 * @return string a normalized reference
	 *
	 * @see control/configure.php
	 */
	public static function get_url($id='rss') {
		global $context;

		// use rewriting engine to achieve pretty references
		if($context['with_friendly_urls'] == 'R')
			return 'feeds/'.$id;

		// the default is to trigger actual PHP scripts
		switch($id) {
		case 'articles':
			return 'articles/feed.php';
		case 'atom':
			return 'feeds/atom.php';
		case 'comments':
			return 'comments/feed.php';
		case 'files':
			return 'files/feed.php';
		case 'opml':
			return 'feeds/describe.php';
		case 'rdf':
			return 'feeds/rdf.php';
		case 'rss':
			return 'feeds/rss.php';
		default:
			return 'feeds/'.$id.'.php';
		}

	}

	/**
	 * get news from remote servers
	 *
	 * This function queries remote sources and populate the table of links based on fetched news.
	 *
	 * On tick, the including hook calls [code]Feeds::tick_hook()[/code].
	 * See [script]control/scan.php[/script] for a more complete description of hooks.
	 *
	 * The function browses the database to locate servers acting as feeders, and read the URLs to use.
	 *
	 * A round-robin algorithm is implemented, meaning that servers are polled in sequence throughout successive ticks.
	 * At most 1 feed is parsed on each tick, to limit impact when the "poor-man" cron mechanism is used,
	 * which is the default setting.
	 *
	 * XML feeds are fetched and parsed according to their type.
	 * At the moment YACS is able to process RSS and slashdot feeds.
	 * Link records are created or updated in the database saving as much of possible of provided data.
	 * Item data is reflected in Link, Title, and Description fields.
	 * Channel	data is used to populate the Source field.
	 * Stamping information is based on feeding date, and channel title.
	 * Also, the edit action 'link:feed' marks links that are collected from feeders.
	 * The anchor field is set to the category assigned in the server profile.
	 *
	 * At the end of the feeding process, the database is purged from oldest links according to the limit
	 * defined in parameters/feeds.include.php, set through feeds/configure.php.
	 * See Links::purge_old_news().
	 *
	 * @param boolean if set to true, fetch news on each call; else use normal period of time
	 * @return a string to be displayed in resulting page, if any
	 *
	 * @see control/scan.php
	 * @see feeds/configure.php
	 */
	public static function tick_hook($forced=FALSE) {
		global $context;

		// load librairies only once
		include_once $context['path_to_root'].'links/links.php';
		include_once $context['path_to_root'].'servers/servers.php';
		include_once $context['path_to_root'].'shared/values.php';	// feeds.tick

		// get feeding parameters
		Safe::load('parameters/feeds.include.php');

		// delay between feeds - minimum is 5 minutes
		if(!isset($context['minutes_between_feeds']) || ($context['minutes_between_feeds'] < 5))
			$context['minutes_between_feeds'] = 5;

		// do not wait for the end of a feeding cycle
		if($forced)
			$threshold = gmstrftime('%Y-%m-%d %H:%M:%S');

		// do not process servers that have been polled recently
		else
			$threshold = gmstrftime('%Y-%m-%d %H:%M:%S', time() - ($context['minutes_between_feeds'] * 60));

		// get a batch of feeders
		if(!$feeders = Servers::list_for_feed(0, 1, 'feed'))
			return 'feeds/feeds.php: no feed has been defined'.BR;

		// remember start time
		$start_time = get_micro_time();

		// list banned tokens
		$banned_pattern = Servers::get_banned_pattern();

		// browse each feed
		$count = 0;
		foreach($feeders as $server_id => $attributes) {

			// get specific feed parameters
			list($feed_url, $feed_title, $anchor, $stamp) = $attributes;

			// skip servers processed recently
			if($stamp > $threshold)
				continue;

			// flag this record to enable round-robin even on error
			Servers::stamp($server_id);

			// fetch news from the provided link
			if((!$news = Feeds::get_remote_news_from($feed_url)) || !is_array($news))
				continue;

			// no anchor has been defined for this feed
			if(!$anchor) {

				// create a default section if necessary
				if(!($anchor = Sections::lookup('external_news'))) {
					$fields = array();
					$fields['nick_name'] = 'external_news';
					$fields['create_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', time());
					$fields['edit_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', time());
					$fields['index_map'] = 'N';
					$fields['locked'] = 'Y'; // no direct contributions
					$fields['rank'] = 40000; // at the end of the list
					$fields['title'] = i18n::c('External News');
					$fields['description'] = i18n::c('Received from feeding servers');
					if(!$fields['id'] = Sections::post($fields)) {
						Logger::remember('feeds/feeds.php: Impossible to add a section.');
						return;
					}
					$anchor = 'section:'.$fields['id'];
				}
			}

			// process retrieved links
			$links = 0;
			foreach($news as $item) {


				// link has to be valid
				if(!isset($item['link']) || !($item['title'].$item['description'])) {
					if(isset($context['debug_feeds']) && ($context['debug_feeds'] == 'Y'))
						Logger::remember('feeds/feeds.php: feed item is invalid', $item, 'debug');
					continue;
				}

				// skip banned servers
				if($banned_pattern && preg_match($banned_pattern, $item['link'])) {
					if(isset($context['debug_feeds']) && ($context['debug_feeds'] == 'Y'))
						Logger::remember('feeds/feeds.php: feed host has been banned', $item['link'], 'debug');
					continue;
				}

				// one link processed
				$links++;

				// link description
				$fields = array();
				$fields['anchor'] = $anchor;
				$fields['link_url'] = $item['link'];
				$fields['title'] = $item['title'];
				$fields['description'] = $item['description'];
				if($item['category'])
					$fields['description'] .= ' ('.$item['category'].')';
				$fields['edit_name'] = $feed_title;
				$fields['edit_address'] = $feed_url;
				$fields['edit_action'] = 'link:feed';
				if($item['pubDate'])
					$fields['edit_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', strtotime($item['pubDate']));

				// update links that already exist in the database
				if(Links::have($item['link'], $anchor, $fields))
					continue;

				// save link in the database
				if(!Links::post($fields))
					Logger::remember('feeds/feeds.php: Impossible to save feed link: '.Logger::error_pop());
			}

			// one feed has been processed
			$count += 1;

			// remember tick date
			Values::set('feeds.tick.'.$feed_url, $links);
		}

		// cap the number of links used for news
		if(!isset($context['maximum_news']) || !$context['maximum_news'])
			$context['maximum_news'] = 1000;
		if($context['maximum_news'] > 10) {
			include_once $context['path_to_root'].'links/links.php';
			Links::purge_old_news($context['maximum_news']);
		}

		// compute execution time
		$time = round(get_micro_time() - $start_time, 2);

		// report on work achieved
		if($count > 1)
			return 'feeds/feeds.php: '.$count.' feeds have been processed ('.$time.' seconds)'.BR;
		elseif($count == 1)
			return 'feeds/feeds.php: 1 feed has been processed ('.$time.' seconds)'.BR;
		else
			return 'feeds/feeds.php: nothing to do ('.$time.' seconds)'.BR;
	}

}

// load localized strings
if(is_callable(array('i18n', 'bind')))
	i18n::bind('feeds');

?>
