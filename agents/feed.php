<?php
/**
 * list logged events as a RSS feed
 *
 * This script may be used by site administrators to stay tuned to their web site.
 *
 * Basically, this script extracts most recent events from the system log, and formats it according to RSS specification.
 * For each event, following attributes are provided:
 * - title - event label
 * - description - any additional text attached to the event
 * - pubDate - date and time of event
 * - category - script name (e.g., 'articles.index.php')
 * - link and guid are provided for readers to get unique identifiers for events
 *
 * @link http://blogs.law.harvard.edu/tech/rss RSS 2.0 Specification
 *
 * The surfer has to be an authenticated associate.
 * He will have either used the regular login page, or provide name and password on a per-request basis.
 *
 * Per-request authentication is based on HTTP basic authentication mechanism, as explained in
 * [link=RFC2617]http://www.faqs.org/rfcs/rfc2617.html[/link].
 *
 * @link http://www.faqs.org/rfcs/rfc2617.html HTTP Authentication: Basic and Digest Access Authentication
 *
 * If an unknown user asks for the RSS feed, he will be prompted by his user agent to enter his name and password.
 * This mechanism has been checked with [link=FeedReader 2.7]http://www.feedreader.com/[/link],
 * [link=Internet Explorer 6.0]http://www.microsoft.com/windows/ie/default.mspx[/link],
 * and [link=Mozilla 1.7.3]http://www.mozilla.org/releases/mozilla1.7.3[/link].
 *
 * The RFC explicitly allows for building URLs including the name and the password, as in the following example:
 * [snippet]
 * http://my_name:my_password@my_site/yacs/agents/feed.php
 * [/snippet]
 *
 * Such links can be used in user agents (i.e., a lot of poorly written news readers) that do not handle HTTP authentication properly.
 * However, please note that Microsoft has recently [link=removed support for such URLs]http://internetnews.com/dev-news/article.php/3305741[/link].
 * This may means that some news readers based on Internet Explorer won't be able to get YACS events log.
 * If this occurs, please consider to switch to standard-conformant software such as [link=Feedreader]http://www.feedreader.com/[/link].
 *
 * @link http://support.microsoft.com/default.aspx?scid=kb;[LN];834489	Microsoft Knowledge Base Article - 834489
 *
 * If following features are enabled, this script will use them:
 * - compression - Through gzip, we have observed a shift from 2900 bytes to 909 bytes, meaning one Ethernet frame rather than two
 * - cache - Cache is supported through ETag and by setting Content-Length; Also, Cache-Control enables caching for some time, even through HTTPS
 *
 * @link http://www.tbray.org/ongoing/When/200x/2003/08/02/RSSNumbers RSS Flow, Measured
 * @link http://rss.lockergnome.com/archives/help/006601.phtml Optimising your feed
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// check network credentials, if any
if($user = Users::authenticate())
	Surfer::empower($user['capability']);

// session authentication
if(Surfer::is_empowered())
	$permitted = TRUE;

// default is to block access to logged information
else
	$permitted = FALSE;

// load localized strings
i18n::bind('agents');

// just to benefit from current skin style
load_skin('agents');

// path to this page
$context['path_bar'] = array( 'feeds/' => i18n::s('Information channels') );

// page title
$context['page_title'] = i18n::s('RSS feed');

// permission denied
if(!$permitted) {

	// give anonymous surfers a chance for HTTP authentication
	if(!Surfer::is_logged()) {
		Safe::header('WWW-Authenticate: Basic realm="'.utf8::to_iso8859($context['site_name']).'"');
		Safe::header('Status: 401 Unauthorized', TRUE, 401);
	}

	// permission denied, or user hit the Cancel button of the authentication box
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// provide requested data
} else {

	// get the list from the cache, if possible
	$cache_id = 'agents/feed.php#news';
	if(!$text =& Cache::get($cache_id)) {

		// loads feeding parameters
		Safe::load('parameters/feeds.include.php');

		// set preamble strings
		$title = sprintf(i18n::s('Event log at %s'), strip_tags($context['site_name']));

		// provide shortcuts for this site
		$splash = sprintf(i18n::s('<p>This is the list of most recent events at %s</p><p>You can also use following shortcuts to get more information for this server:</p><ul><li><a href="%s">Go to the front page (%s)</a></li><li><a href="%s">Go to the control panel (%s)</a></li></ul>'),
				$context['host_name'],
				$context['url_to_home'].$context['url_to_root'],
				$context['url_to_root'],
				$context['url_to_home'].$context['url_to_root'].'control/',
				$context['url_to_root'].'control/');

		// the preamble
		$text = '<?xml version="1.0" encoding="'.$context['charset'].'"?>'."\n"
			.'<rss version="2.0">'."\n"
			.'<channel>'."\n"
			.'	<title>'.encode_field($title).'</title>'."\n"
			.'	<link>'.$context['url_to_home'].$context['url_to_root'].'agents/</link>'."\n"
			.'	<description><![CDATA[ '.$splash.' ]]></description>'."\n";
		if(isset($context['powered_by_image']) && $context['powered_by_image'] && ($size = Safe::GetImageSize($context['path_to_root'].$context['powered_by_image'])) ) {
			$text .= '	<image>'."\n"
				.'		<url>'.$context['url_to_home'].$context['url_to_root'].$context['powered_by_image'].'</url>'."\n"
				.'		<width>'.$size[0].'</width>'."\n"
				.'		<height>'.$size[1].'</height>'."\n"
				.'		<title>'.encode_field($title).'</title>'."\n"
				.'		<link>'.$context['url_to_home'].$context['url_to_root'].'agents/</link>'."\n"
				.'	</image>'."\n";
		}
		if($context['preferred_language'])
			$text .= '	<language>'.encode_field($context['preferred_language']).'</language>'."\n";
		$text .= '	<lastBuildDate>'.gmdate('D, d M Y H:i:s').' GMT</lastBuildDate>'."\n"
			.'	<generator>Yet Another Community System</generator>'."\n"
			.'	<docs>http://blogs.law.harvard.edu/tech/rss</docs>'."\n"
			.'	<ttl>5</ttl>'."\n";

		// list last events
		$events = Logger::get_tail(50, 'all');
		if(is_array($events)) {

			// the actual list of events
			foreach($events as $event) {
				list($stamp, $surfer, $script, $label, $description) = $event;

			// formatting patterns
			$search = array(
				"|\r\n|",
				"|<br\s*/>\n+|i",		/* don't insert additional \n after <br /> */
				"|\n\n+[ \t]*-\s+|i",	/* hard-coded lists with - */
				"|\n[ \t]*-\s+|i",
				"|\n\n+[ \t]*\.\s+|i",	/* hard-coded lists with . */
				"|\n[ \t]*\.\s+|i",
				"|\n\n+[ \t]*\*\s+|i",	/* hard-coded lists with * */
				"|\n[ \t]*\*\s+|i",
				"|\n\n+[ \t]*¤\s+|i",	/* hard-coded lists with ¤ */
				"|\n[ \t]*¤\s+|i",
				"|\n\n+[ \t]*\•\s+|i",	/* hard-coded lists with • */
				"|\n[ \t]*\•\s+|i",
				"/\n[ \t]*(From|To|cc|bcc|Subject|Date):(\s*)/i",	/* common message headers */
				"|\n[ \t]*>(\s*)|i",		/* quoted by >*/
				"|\n[ \t]*\|(\s*)|i",		/* quoted by | */
				"#([\n\t ])([a-z]+?)://([^, <>{}\n\r]+)#is", /* make URL clickable */
				"#^([a-z]+?)://([^, <>{}\n\r]+)#is",
				"#([\n\t ])www\.([a-z0-9\-]+)\.([a-z0-9\-.\~]+)((?:/[^,< \n\r]*)?)#is",
				"#([\n\t ])([a-z0-9\-_.]+?)@([^,< \.\n\r]+\.[^,< \n\r]+)#is",
				"|\n\n|i"				/* force an html space between paragraphs */
				);

			$replace = array(
				"\n",
				BR,
				BR.BR."- ",
				BR."- ",
				BR.BR."- ",
				BR."- ",
				BR.BR."- ",
				BR."- ",
				BR.BR."- ",
				BR."- ",
				BR.BR."• ",
				BR."• ",
				BR."$1:$2",
				BR.">$1",
				BR."|$1",
				"$1<a href=\"$2://$3\">$2://$3</a>",
				"<a href=\"$1://$2\">$1://$2</a>",
				"$1<a href=\"http://www.$2.$3$4\">http://www.$2.$3$4</a>",
				"$1<a href=\"mailto:$2@$3\">$2@$3</a>",
				BR.BR
				);

				// build an extensive description field
				$description = nl2br(preg_replace($search, $replace, $description))
					.'<p>'.$script.((strlen($surfer) > 1)?' for '.$surfer:'').' on '.$stamp."</p>";

				// build a unique id
				$id = md5($label.$description.$stamp.$script.$surfer);

				// output one story
				$text .= "\n".' <item>'."\n"
					.'		<title>'.encode_field(strip_tags($label))."</title>\n"
					.'		<description><![CDATA[ '.$description." ]]></description>\n"
					.'		<pubDate>'.gmdate('D, d M Y H:i:s', SQL::strtotime($stamp))." GMT</pubDate>\n"
					.'		<link>'.$context['url_to_home'].$context['url_to_root'].'agents/?subject=events&amp;id='.$id."</link>\n"
					.'		<guid isPermaLink="false">'.$id."</guid>\n"
					.'		<category>'.encode_field($script)."</category>\n"
					."	</item>\n";

			}
		}

		// the postamble
		$text .= "\n	</channel>\n"
			.'</rss>';

		// save in cache during 5 minutes
		Cache::put($cache_id, $text, 'events', 300);
	}

	//
	// transfer to the user agent
	//

	// handle the output correctly
	render_raw('text/xml; charset='.$context['charset']);

	// suggest a name on download
	if(!headers_sent()) {
		$file_name = $context['site_name'].'.events.rss.xml';
		$file_name =& utf8::to_ascii($file_name);
		Safe::header('Content-Disposition: inline; filename="'.$file_name.'"');
	}

	// enable 30-minute caching (30*60 = 1800), even through https, to help IE6 on download
	if(!headers_sent()) {
		Safe::header('Expires: '.gmdate("D, d M Y H:i:s", time() + 1800).' GMT');
		Safe::header("Cache-Control: max-age=1800, public");
		Safe::header("Pragma: ");
	}

	// strong validation
	if((!isset($context['without_http_cache']) || ($context['without_http_cache'] != 'Y')) && !headers_sent()) {

		// generate some strong validator
		$etag = '"'.md5($text).'"';
		Safe::header('ETag: '.$etag);

		// validate the content if hash is ok
		if(isset($_SERVER['HTTP_IF_NONE_MATCH']) && is_array($if_none_match = explode(',', str_replace('\"', '"', $_SERVER['HTTP_IF_NONE_MATCH'])))) {
			foreach($if_none_match as $target) {
				if(trim($target) == $etag) {
					Safe::header('Status: 304 Not Modified', TRUE, 304);
					return;
				}
			}
		}

	}

	// actual transmission except on a HEAD request
	if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
		echo $text;

	// the post-processing hook, then exit
	finalize_page(TRUE);

}

// display error messages, if any
render_skin();

?>