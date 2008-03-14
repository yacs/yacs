<?php
/**
 * process ping requests
 *
 * This script accepts following XML-RPC calls:
 * - monitor.ping
 * - pingback.ping
 * - weblogUpdates.ping
 *
 * Please note that technical specifications describe different ways to return information,
 * and this may lead to various error codes depending on situation.
 * Basically, we have implemented XML-RPC return codes where applicable, plus specific recommendations
 * where applicable.
 * For example, if no data is provided to this script, the sender will be returned a structure
 * with attributes '[code]faultCode[/code]' and '[code]faultString[/code]'.
 * But if inadequate parameters are given to a '[code]pingback.ping[/code]' call, a simple error code will be returned.
 *
 * [title]monitor.ping[/title]
 *
 * Syntax: [code]monitor.ping()[/code] returns [code](int, string)[/code]
 *
 * This entry point is used to centralize the monitoring of yacs servers.
 *
 * The returned structure is made of an integer and a string.
 * The value 0 means that the pinged server has checked its internal state and that this state is OK.
 * Else an error code and a printable label are returned.
 *
 * You can prevent a server to monitor you by disabling monitoring in the related server profile.
 *
 *
 * [title]pingback.ping[/title]
 *
 * Syntax: [code]pingback.ping(source_uri, target_uri)[/code] returns string or int
 *
 * This entry point is used to link a remote page (the source_uri) to one page at the server receiving the call
 * (the target_uri).
 * We are claiming to fully support the pingback server interface here, as described in the [link=pingback]http://www.hixie.ch/specs/pingback/pingback[/link] specification.
 *
 * According to the specification we are returning either the string 'Thank you for the ping', or an error code.
 * Following error codes have been defined:
 * - 0 generic fault code
 * - 16 The source URI does not exists
 * - 17 The source URI does not contain a link to the target URI, and so cannot be used as a source
 * - 32 The specific target URI does not exist
 * - 33 The specified target URI cannot be used as a target. It either does not exist, or it is not a pingback-enabled resource.
 * - 48 The pingback has already been registered
 * - 49 Access denied
 * - 50 The server could not communicate with an upstream server, or received an error from an upstream server.
 *
 * Note that YACS also has a client implementation of the pingback specification into [script]links/links.php[/script].
 *
 * @see articles/view.php
 * @see articles/publish.php
 * @see categories/view.php
 * @see links/links.php
 * @see sections/view.php
 *
 * How does pingback work?
 *
 * Ok, for those new to pingback here is an overview of the protocol:
 * [list=1]
 * [*] Alice posts to her blog, including the URL http://www.bob.com/post5.
 * [*] Alice's blogging system (AliceBlog) gets all external URLs
 * referenced in the post (in this case, just the one to Bob).
 * [*] AliceBlog requests http://www.bob.com/post5 and parses it for a
 * 		[code]&lt;link&gt;[/code] tag matching [code]&lt;link rel="pingback" href="http://foo/xmlrpcserver" /&gt;[/code].
 * [*] If it doesn't find one, or the response of the request doesn't seem
 * to support it, then abort the ping trial for this link.
 * [*] Perform an XML-RPC ping to the URL found in the [code]&lt;link&gt;[/code].
 * AliceBlog doesn't care if it succeeds or not, really; that's the end of its bit.
 * [*] BobBlog receives the XML-RPC ping, naming the URL of
 * Alice's post (AliceURL), and the URL it linked to (BobURL).
 * [*] BobBlog should check that BobURL is part of Bob's blog.
 * [*] BobBlog requests the URL of Alice's post, and confirms that it
 * mentions BobURL.
 * [*] BobBlog parses the title of AliceURL.
 * [*] BobBlog stashes the details of the ping somewhere in its database,
 * against the entry which is BobURL..
 * [/list]
 *
 * You can prevent a server to flood you by disabling pings in the related server profile.
 *
 * [title]weblogUpdates.ping[/title]
 *
 * Syntax: [code]weblogUpdates.ping(blog_name, blog_url)[/code] returns [code](flerror = boolean, message = string)[/code]
 *
 * It takes two parameters, both strings. The first is the name of the weblog, the second is its URL.
 *
 * It returns a struct that indicates success or failure. It has two elements, flerror and message.
 * If flerror is false, it worked. If it's true, message contains an English-language description of the reason
 * for the failure.
 *
 * If the call succeeds, an entry for this server will be created
 * or updated in the database (see [script]servers/index.php[/script]) for the provided URL.
 * Note that an existing profile is updated only if ping is still allowed for it.
 *
 * This entry point is used to centralize change information.
 * By letting the receiving server know about updates, efficient aggregation mechanisms can be put in place.
 *
 * To tell a YACS server that a weblog has changed, call [code]weblogUpdates.ping[/code] at path [code]/yacs/services/ping.php[/code].
 *
 * @see services/ping_test.php
 * @see servers/index.php
 *
 * You can prevent a server to flood you by disabling pings in the related server profile.
 *
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 *
 * @see services/configure.php
 *
 * @link http://www.xmlrpc.com/weblogsCom Weblogs.Com XML-RPC interface
 * @link http://www.hixie.ch/specs/pingback/pingback Pingback specification
 */
include_once '../shared/global.php';
include_once '../links/link.php';
include_once '../links/links.php';

// load a skin engine
load_skin('services');

// ensure we have some raw content
global $HTTP_RAW_POST_DATA;
if(!isset($HTTP_RAW_POST_DATA))
   $HTTP_RAW_POST_DATA = file_get_contents("php://input");

// save the raw request if debug mode
if(isset($context['debug_ping']) && ($context['debug_ping'] == 'Y'))
	Logger::remember('services/ping.php', 'ping request', $HTTP_RAW_POST_DATA, 'debug');

// transcode to our internal charset
if($context['charset'] == 'utf-8')
	$HTTP_RAW_POST_DATA = utf8::to_unicode($HTTP_RAW_POST_DATA);

// load the adequate codec
include_once 'codec.php';
include_once 'xml_rpc_codec.php';
$codec =& new xml_rpc_Codec();

// parse xml parameters
$result = $codec->import_request($HTTP_RAW_POST_DATA);
$status = @$result[0];
$parameters = @$result[1];

// nothing to parse
if(!$HTTP_RAW_POST_DATA) {
	$response = array('faultCode' => 5, 'faultString' => 'Empty request, please retry');

// parse has failed
} elseif(!$status) {
	$response = array('faultCode' => 5, 'faultString' => 'Impossible to parse parameters');

// dispatch the request
} else {

	// remember parameters if debug mode
	if(isset($context['debug_ping']) && ($context['debug_ping'] == 'Y'))
		Logger::remember('services/ping.php', 'ping '.$parameters['methodName'], $parameters['params'], 'debug');
	elseif(isset($context['debug_trackback']) && ($context['debug_trackback'] == 'Y') && ($parameters['methodName'] == 'pingback.ping'))
		Logger::remember('services/ping.php', 'ping '.$parameters['methodName'], $parameters['params'], 'debug');

	// depending on method name
	switch($parameters['methodName']) {

	// we are pinged from a monitoring yacs server
	case 'monitor.ping':

		// caller has been banned
		include_once $context['path_to_root'].'servers/servers.php';
		if($_SERVER['REMOTE_HOST'] && ($server =& Servers::get($_SERVER['REMOTE_HOST']) && ($server['process_monitor'] != 'Y')))
			$response = array('faultCode' => 49, 'faultString' => 'Access denied');

		// check we have a configuration file
		elseif(!file_exists($context['path_to_root'].'parameters/control.include.php'))
			$response = array('faultCode' => 16, 'faultString' => 'No parameter file parameters/control.include.php');

		// ok
		else
			$response = array('faultCode' => 0, 'faultString' => 'OK');

		break;

	// ping an external reference to some page on this site
	case 'pingback.ping':
		list($source, $target) = $parameters['params'];

		// we are linking to an article
		$anchor = NULL;
		if(preg_match('/\/articles\/view.php\/(\w+)/', $target, $matches))
			$anchor = 'article:'.$matches[1];
		elseif(preg_match('/\/articles\/view.php\?id=(\w+)/', $target, $matches))
			$anchor = 'article:'.$matches[1];

		// we are linking to a section
		if(preg_match('/\/sections\/view.php\/(\w+)/', $target, $matches))
			$anchor = 'section:'.$matches[1];
		elseif(preg_match('/\/sections\/view.php\?id=(\w+)/', $target, $matches))
			$anchor = 'section:'.$matches[1];

		// we are linking to a category
		if(preg_match('/\/categories\/view.php\/(\w+)/', $target, $matches))
			$anchor = 'category:'.$matches[1];
		elseif(preg_match('/\/categories\/view.php\?id=(\w+)/', $target, $matches))
			$anchor = 'category:'.$matches[1];

		// caller has been banned
		include_once $context['path_to_root'].'servers/servers.php';
		if(isset($_SERVER['REMOTE_HOST']) && ($server =& Servers::get($_SERVER['REMOTE_HOST']) && ($server['process_ping'] != 'Y')))
			$response = 49;

		// check we are linking on this site
		elseif(!preg_match('/^'.preg_quote($context['url_to_home'], '/').'/i', $target))
			$response = 33;
		elseif(!$anchor)
			$response = 33;

		// check that the source has not already been registered
		elseif(Links::have($source, $anchor))
			$response = 48;

		// check that the source actually has a link to us
		elseif(($content = Link::fetch($source, '', '', 'services/ping.php')) === FALSE)
			$response = 16;

		// we have to found a reference to the target here
		else {

			// ensure enough execution time
			Safe::set_time_limit(30);

			// we have to found a reference to the target here
			if(($position = strpos($content, $target)) === FALSE)
				$response = 17;

			// register the source link back from the target page
			else {

				// try to grab a title
				if(preg_match("/<h1>(.*)<\/h1>/i", $content, $matches))
					$fields['title'] = $matches[1];
				elseif(preg_match("/<title>(.*)<\/title>/i", $content, $matches))
					$fields['title'] = $matches[1];

				// try to extract some text around the link
				$extract = strip_tags(substr($content, max(0, $position-70), 210), '<a><b><i>');
				if(preg_match('/[^<]*>(.*)$/', $extract, $matches))
					$extract = $matches[1];
				if($extract)
					$fields['description'] = '...'.$extract.'...';

				// save in the database
				$fields['anchor'] = $anchor;
				$fields['link_url'] = $source;
				if(!Links::post($fields))
					$response = 0;
				else
					$response = 'Thanks for the ping';
			}
		}

		break;

	// ping an external reference to some page on this site
	case 'weblogUpdates.ping':
		list($label, $url) = $parameters['params'];

		// caller has been banned
		include_once $context['path_to_root'].'servers/servers.php';
		if($_SERVER['REMOTE_HOST'] && ($server =& Servers::get($_SERVER['REMOTE_HOST']) && ($server['process_ping'] != 'Y')))
			$response = array('flerror' => 49, 'message' => 'Access denied');

		// do not accept local address
		elseif(preg_match('/\b(127\.0\.0\.1|localhost)\b/', $url))
			$response = array('flerror' => 1, 'message' => 'We don\'t accept local references '.$url);

		// check we can read the given address, or the same with an additional '/'
		elseif((($content = Link::fetch($url, '', '', 'services/ping.php')) === FALSE) && (($content = Link::fetch($url.'/', '', '', 'services/ping.php')) === FALSE))
			$response = array('flerror' => 1, 'message' => 'Cannot read source address '.$url);

		// create or update a server entry
		else {
			include_once $context['path_to_root'].'servers/servers.php';
			$response = Servers::ping(strip_tags($label), $url);
			if($response) {
				Logger::remember('services/ping.php', 'failing ping', $response, 'debug');
				$response = array('flerror' => 1, 'message' => $response);
			} else
				$response = array('flerror' => 0, 'message' => 'Thanks for the ping');
		}

		break;

	default:
		$response = array('faultCode' => 1, 'faultString' => 'Do not know how to process '.$parameters['methodName']);
		Logger::remember('services/ping.php', 'ping unsupported methodName', $parameters, 'debug');
	}
}

// no response yet
if(!isset($response))
	$response = array('faultCode' => 1, 'faultString' => 'no response');

// build a XML snippet
$result = $codec->export_response($response);
$status = @$result[0];
$response = @$result[1];

// handle the output correctly
render_raw('text/xml; charset='.$context['charset']);

// actual transmission except on a HEAD request
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
	echo $response;

// save the response if debug mode
if(isset($context['debug_ping']) && ($context['debug_ping'] == 'Y'))
	Logger::remember('services/ping.php', 'ping response', $response, 'debug');
elseif(isset($context['debug_trackback']) && ($context['debug_trackback'] == 'Y') && ($parameters['methodName'] == 'pingback.ping'))
	Logger::remember('services/ping.php', 'ping response', $response, 'debug');

// the post-processing hook
finalize_page();

?>