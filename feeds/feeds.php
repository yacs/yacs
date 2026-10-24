<?php
/**
 * get news
 *
 * This data abstraction for feeds provides following functions:
 * - [code]get_local_news()[/code] - retrieve local news
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
	 * former news aggregation from remote servers, now removed
	 *
	 * Kept only because [code]parameters/hooks.include.php[/code], as generated
	 * by previous versions of [script]control/scan.php[/script], calls it on each tick.
	 *
	 * @param boolean not used anymore
	 * @return string an empty string
	 */
	public static function tick_hook($forced=FALSE) {
		return '';
	}

}

// load localized strings
if(is_callable(array('i18n', 'bind')))
	i18n::bind('feeds');

?>
