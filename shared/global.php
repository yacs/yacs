<?php
/**
 * included at the very beginning of each page
 *
 * YACS assumes that it has to answer web requests if [code]$_SERVER['REMOTE_ADDR'][/code] has been set.
 * Else it is running from the command line.
 *
 * Invoke functions load_skin() and render_skin() in your script only if it
 * prepares a regular web page.
 *
 * To further optimize performance, following constants can be set prior loading this script:
 * - NO_CONTROLLER_PRELOAD - no extensions, no service parameters, no Surfer library
 * - NO_VIEW_PRELOAD - no internationalization
 * - NO_MODEL_PRELOAD - no SQL library, and no standard data libraries (e.g., articles, sections, users)
 *
 * Do not modify this file by yourself, changes would be lost on next software upgrades.
 *
 * @author Bernard Paques
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @author Alain Lesage (Lasares)
 * @author Alexis Raimbault
 * @tester Olivier
 * @tester Arioch
 * @tester Fernand Le Chien
 * @tester Mordread Wallas
 * @tester Raeky
 * @tester Lilou
 * @tester Pierre Robert
 * @tester Anatoly
 * @tester NickR
 * @tester Thierry Pinelli (ThierryP)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// yacs is loading
define('YACS', TRUE);

// used to end lines in many technical specifications
if(!defined('CRLF'))
	define('CRLF', "\x0D\x0A");

// default value for name filtering in forms (e.g. 'edit_name' filled by anonymous surfers)
if(!defined('FORBIDDEN_IN_NAMES'))
	define('FORBIDDEN_IN_NAMES', '/[<>{}\(\)]+/');

// default value for path filtering in forms -- ../
if(!defined('FORBIDDEN_IN_PATHS'))
	define('FORBIDDEN_IN_PATHS', '/\.{2,}\//');

// default value for codes filtering for teasers
if(!defined('FORBIDDEN_IN_TEASERS'))
	define('FORBIDDEN_IN_TEASERS', '/\[(location=[^\]]+?|table=[^\]]+?|toc|toq)\]\s*/i');

// default value for url filtering in forms
if(!defined('FORBIDDEN_IN_URLS'))
	define('FORBIDDEN_IN_URLS', '/[^\w~_:@\/\.&#;\^\,+%\?=\-\[\]*]+/');

// options to utf8::to_ascii() for file names
if(!defined('FILENAME_SAFE_ALPHABET'))
	define('FILENAME_SAFE_ALPHABET', ' !"#$%&\'()*+,-.;<=>?@[]^_{|}~');

// options to utf8::to_ascii() for printable chars
if(!defined('PRINTABLE_SAFE_ALPHABET'))
	define('PRINTABLE_SAFE_ALPHABET', ' !"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~');

// options to utf8::to_ascii() for the encoding of individual component of URLs and web links
if(!defined('URL_SAFE_ALPHABET'))
	define('URL_SAFE_ALPHABET', '-_.~');  // all of these chars will be turned to '-'

// pattern for valid email recipients
if(!defined('VALID_RECIPIENT'))
	define('VALID_RECIPIENT', '/^[*+!\.&#$¦\'\\%\/0-9a-z^_`{}=?~:-]+@([0-9a-z-]+\.)+[0-9a-z]{2,4}$/i');

// the right way to integrate javascript code
if(!defined('JS_PREFIX'))
	define('JS_PREFIX', '<script type="text/javascript">//<![CDATA['."\n");
if(!defined('JS_SUFFIX'))
	define('JS_SUFFIX', '// ]]></script>'."\n");

// PHP5 could complain about not settings TZ correctly
if(function_exists("date_default_timezone_set") and function_exists("date_default_timezone_get"))
	@date_default_timezone_set(@date_default_timezone_get());

// store attributes for this request, including global parameters and request-specific variables
global $context;

// ensure we have built everything --stop all kinds of data injections
$context = array();

// the HTTP accepted verbs by default --can be modified in some scripts, if necessary
$context['accepted_methods'] = 'GET,HEAD,OPTIONS,POST,PUT';

// parameters of the scripts passed in the URL, if any
$context['arguments'] = array();

// pre-built extra and navigation boxes, and other components for the page factory -- see skins/configure.php
$context['components'] = array();
$context['components']['bookmarklets'] = '';
$context['components']['boxes'] = '';
$context['components']['categories'] = '';
$context['components']['channels'] = '';
$context['components']['contextual'] = '';
$context['components']['download'] = '';
$context['components']['neighbours'] = '';
$context['components']['news'] = '';
$context['components']['overlay'] = '';
$context['components']['profile'] = '';
$context['components']['referrals'] = '';
$context['components']['servers'] = '';
$context['components']['share'] = '';
$context['components']['twins'] = '';

// type of object produced by YACS
$context['content_type'] = 'text/html';

// these will be updated if GEOPIP has been installed
$context['country'] = 'N/A';
$context['country_code'] = '--';

// list of containers of the current page (e.g., array('section:123', 'section:456'))
$context['current_focus'] = array();

// handle of the current page (e.g., 'article:123')
$context['current_item'] = NULL;

// where developers can add debugging messages --one string per row
$context['debug'] = array();

// default mask to be used on mkdir -- see control/configure.php
$context['directory_mask'] = 0755;

// surfer does not benefit from extended rights yet -- see Surfer::empower() in shared/surfer.php
$context['empowered'] = '?';

// a stack of error messages --one string per row
$context['error'] = array();

// content of the extra panel
$context['extra'] = '';

// default mask to be used on chmod -- see control/configure.php
$context['file_mask'] = 0644;

// quite new --changed in load_skin() based on actual parameter site_revisit_after -- see skins/configure.php
$context['fresh'] = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-7,date("Y")));

// compute server gmt offset based on system configuration
$context['gmt_offset'] = intval((strtotime(date('M d Y H:i:s')) - strtotime(gmdate('M d Y H:i:s'))) / 3600);

// required client libraries
$context['javascript'] = array();

// surfer preferred language -- changed in i18n::initialize() in i18n/i18n.php
$context['language'] = 'en';

// content of the navigation panel
$context['navigation'] = '';

// date and time of execution --in GMT
$context['now'] = gmstrftime('%Y-%m-%d %H:%M:%S');

// default P3P compact policy enables IE support of our cookies, even through frameset -- http://support.microsoft.com/kb/323752
$context['p3p_compact_policy'] = 'CAO PSA OUR';

// page author (meta-information)
$context['page_author'] = '';

// page date (meta-information)
$context['page_date'] = '';

// page details (complementary information about the page)
$context['page_details'] = '';

// additional content for the page footer
$context['page_footer'] = '';

// additional meta-information to be put in page header
$context['page_header'] = '';

// the main image in the page
$context['page_image'] = '';

// language used to write the page
$context['page_language'] = NULL;

// the main bar of menus --an array of links
$context['page_menu'] = array();

// page publisher (meta-information)
$context['page_publisher'] = '';

// page tags
$context['page_tags'] = '';

// page main title
$context['page_title'] = '';

// side menus --an array of strings
$context['page_tools'] = array();

// page breadcrumbs
$context['path_bar'] = array();

// get our position from the environment --always end the string with a slash
if($home = getenv('YACS_HOME'))
	$context['path_to_root'] = str_replace('//', '/', $home.'/');

// get our position from run-time
else
	$context['path_to_root'] = dirname(dirname(__FILE__)).'/';

// fix windows backslashes
$context['path_to_root'] = str_replace('\\', '/', $context['path_to_root']);

// sanity checks - /foo/bar/.././ -> /foo/
$context['path_to_root'] = preg_replace(array('|/([^/]*)/\.\./|', '|/\./|'), '/', $context['path_to_root']);

// prefix for page main content
$context['prefix'] = '';

// suffix for page main content
$context['suffix'] = '';

// site icon --the little image displayed in bookmarks
$context['site_icon'] = '';

// typical number of days betwen refresh --see skins/configure.php
$context['site_revisit_after'] = 2;

// site slogan
$context['site_slogan'] = '';

// components to put in the extra panel --see skins/configure.php
$context['skins_extra_components'] = 'tools image profile news overlay boxes share channels twins neighbours categories bookmarklets servers download referrals visited';

// components to put in the main panel --see skins/configure.php
$context['skins_main_components'] = 'title bar error text tags details';

// components to put in the side panel --see skins/configure.php
$context['skins_navigation_components'] = 'user menu contextual navigation';

// minimize CPU used by rendering engine --see skins/configure.php
$context['skins_with_details'] = 'N';

// we will use this at several places
function get_micro_time() {
	list($usec, $sec) = explode(" ",microtime(), 2);
	return ((float)$usec + (float)$sec);
}

// start processing the page
$context['start_time'] = get_micro_time();

// page main content
$context['text'] = '';

// default value for allowed tags -- see users/configure.php
$context['users_allowed_tags']	= '<a><abbr><acronym><b><big><br><code><dd><del><dfn><dl><dt><em><i><img><ins><li><ol><p><q><small><span><strong><sub><sup><tt><u><ul>';

// default editor -- see users/configure.php
$context['users_default_editor'] = 'tinymce';

// use titles in address -- see control/configure.php
$context['with_alternate_urls'] = 'N';

// debug the execution of this script -- see control/configure.php
$context['with_debug'] = 'N';

// how to build links -- see control/configure.php
$context['with_friendly_urls'] = 'N';

// profile execution of this script
$context['with_profile'] = 'N';

//
// load core libraries
//

// the http library
include_once $context['path_to_root'].'shared/http.php';

// the xml/html library
include_once $context['path_to_root'].'shared/xml.php';

// the safe library
include_once $context['path_to_root'].'shared/safe.php';

// the logging facility
include_once $context['path_to_root'].'shared/logger.php';

// the cache library
include_once $context['path_to_root'].'shared/cache.php';

// tools for js and css declaration
include_once 'js_css.php';

//
// set dynamic parameters
//

// load general parameters -- see control/configure.php
Safe::load('parameters/control.include.php');

// maximize level of error reporting on development servers
if($context['with_debug'] == 'Y') {
	Safe::ini_set('display_errors','1');
	Safe::ini_set('display_startup_errors','1');
	Safe::ini_set('allow_call_time_pass_reference','0');
 	if(defined('E_STRICT'))
 		$level = E_ALL | E_STRICT;
 	else
		$level = E_ALL;
} else
	$level = E_ALL ^ (E_NOTICE | E_USER_NOTICE | E_WARNING);
Safe::error_reporting($level);

// the name of this server
if(isset($_SERVER['HTTP_HOST']))
	$context['host_name'] = $_SERVER['HTTP_HOST']; // from HTTP request
elseif(!isset($_SERVER['REMOTE_ADDR']) && isset($context['main_host']))
	$context['host_name'] = $context['main_host'];	// pretend we are a virtual host during crontab job
elseif(isset($_SERVER['SERVER_NAME']))
	$context['host_name'] = $_SERVER['SERVER_NAME']; // from web daemon configuration file
else
	$context['host_name'] = 'localhost';

// stop hackers
$context['host_name'] = strip_tags($context['host_name']);

// strip port number, if any
if($here = strrpos($context['host_name'], ':'))
	$context['host_name'] = substr($context['host_name'], 0, $here);

// load parameters specific to this virtual host or sub-domain, if any
Safe::load('parameters/virtual_'.$context['host_name'].'.include.php');

// ensure we have a site name
if(!isset($context['site_name']))
	$context['site_name'] = $context['host_name'];

// the url to the front page -- to be used alone, or with an appended string starting with '/'
if(isset($_SERVER['SERVER_PORT']) && ($_SERVER['SERVER_PORT'] == 443))
	$context['url_to_home'] = 'https://'.$context['host_name'];
elseif(isset($_SERVER['SERVER_PORT']) && ($_SERVER['SERVER_PORT'] != 80))
	$context['url_to_home'] = 'http://'.$context['host_name'].':'.$_SERVER['SERVER_PORT'];
else
	$context['url_to_home'] = 'http://'.$context['host_name'];

// the url to reference ourself, including query string -- copy of the reference submitted by user agent (i.e., before rewritting)
$context['self_url'] = '';
if(isset($_SERVER['SCRIPT_URI']) && isset($_SERVER['QUERY_STRING']))
	$context['self_url'] = $_SERVER['SCRIPT_URI'].'?'.$_SERVER['QUERY_STRING'];
elseif(isset($_SERVER['SCRIPT_URI']))
	$context['self_url'] = $_SERVER['SCRIPT_URI'];
elseif(isset($_SERVER['REQUEST_URI'])) // this includes query string
	$context['self_url'] = $context['url_to_home'].$_SERVER['REQUEST_URI'];

//
// session cookie
//

// we will manage the cache by ourself
if(is_callable('session_cache_limiter'))
	@session_cache_limiter('none');

// manage session data, but not if run from the command line
if(isset($_SERVER['REMOTE_ADDR']) && !headers_sent() && (session_id() == '')) {

	// enable sub-domains automatically, using only last two name components: www.mydomain.com -> .mydomain.com
	$domain = $context['host_name'];
	if(($parent_domain = strstr($domain, '.')) && strpos($parent_domain, '.', 1))
		$domain = $parent_domain;

    // set the cookie to parent domain, to allow for sub-domains
	session_set_cookie_params(0, '/', $domain);

	// set or use the PHPSESSID cookie
	session_start();

	// if several hosts or domains have been defined for this server, ensure all use same session data
	if($hosts = Safe::file('parameters/hosts')) {

		// ask user agent to call various hosts via javascript
		$script = '<script type="text/javascript">'."\n";

		// one host at a time
		foreach($hosts as $index => $host) {
			if($host = trim($host)) {
			    
			if($host == $domain) continue;    			    

			// start an ajax transaction
			$script .= "\t".'$.ajax({'
				.'"url":"http://'.$host.$context['url_to_root'].'tools/session.php",'
				.'"type": "GET",'
				.'"data": {"id": "'.session_id().'","origin":"'.$domain.'"},'
				.'"xhrFields": { "withCredentials": true}' 
			    
				.'});'."\n";

			}
		}

		// close this javascript snippet
		$script .= '</script>'."\n";

		// defer its execution in user agent
		$context['page_footer'] .= $script;
	}

}


// redirect to given host name, if required to do so
if(isset($context['with_given_host']) && ($context['with_given_host'] == 'Y') && isset($context['main_host']) && ($context['main_host'] != $context['host_name']))
	Safe::redirect(str_replace($context['host_name'], $context['main_host'], $context['self_url']));

// ensure protocol is secured
if(isset($context['with_https']) && ($context['with_https'] == 'Y') && !isset($_SERVER['HTTPS']) && ($_SERVER['SERVER_PORT'] != 443))
	Safe::redirect(str_replace('http:', 'https:', $context['self_url']));

// web reference to yacs root-level scripts
if(!isset($context['url_to_root'])) {
	$context['url_to_root'] = '/';
	if(isset($context['self_url'])) {
		$items = @parse_url($context['self_url']);
		if(preg_match('/(.*?\/yacs.*?\/)/i', $items['path'], $matches))
			$context['url_to_root'] = $matches[1];
		elseif(preg_match('/(\/.*?\/)control/i', $items['path'], $matches))
			$context['url_to_root'] = $matches[1];
	}
}

// save parameter to be used in control/configure.php
$context['url_to_root_parameter'] = $context['url_to_root'];

// self_script is legacy -- mainly used in templates, as '<base href="'.$context['url_to_home'].$context['self_script'].'" />'
$context['self_script'] = str_replace($context['url_to_home'], '', $context['self_url']);

// script_url is used in forms, for self-referencing -- web reference after rewriting
$context['script_url'] = '';
if(isset($_SERVER['REDIRECT_URL']) && preg_match('/'.preg_quote($context['url_to_root'], '/').'.+?\.php/', $_SERVER['REDIRECT_URL'], $matches))
	$context['script_url'] = $matches[0];
elseif(isset($_SERVER['REDIRECT_URL']) && preg_match('/'.preg_quote($context['path_to_root'], '/').'.+?\.php/', $_SERVER['REDIRECT_URL'], $matches))
	$context['script_url'] = str_replace($context['path_to_root'], $context['url_to_root'], $matches[0]);
elseif(isset($_SERVER['REDIRECT_URI']) && preg_match('/'.preg_quote($context['url_to_root'], '/').'.+?\.php/', $_SERVER['REDIRECT_URI'], $matches))
	$context['script_URI'] = $matches[0];
elseif(isset($_SERVER['REDIRECT_URI']) && preg_match('/'.preg_quote($context['path_to_root'], '/').'.+?\.php/', $_SERVER['REDIRECT_URI'], $matches))
	$context['script_URI'] = str_replace($context['path_to_root'], $context['url_to_root'], $matches[0]);
elseif(isset($_SERVER['PHP_SELF']) && preg_match('/'.preg_quote($context['url_to_root'], '/').'.+?\.php/', $_SERVER['PHP_SELF'], $matches))
	$context['script_url'] = $matches[0];
elseif(isset($_SERVER['PHP_SELF']) && preg_match('/'.preg_quote($context['path_to_root'], '/').'.+?\.php/', $_SERVER['PHP_SELF'], $matches))
	$context['script_url'] = str_replace($context['path_to_root'], $context['url_to_root'], $matches[0]);
elseif(isset($_SERVER['SCRIPT_URL']) && preg_match('/'.preg_quote($context['url_to_root'], '/').'.+?\.php/', $_SERVER['SCRIPT_URL'], $matches))
	$context['script_url'] = $matches[0];
elseif(isset($_SERVER['SCRIPT_URL']) && preg_match('/'.preg_quote($context['path_to_root'], '/').'.+?\.php/', $_SERVER['SCRIPT_URL'], $matches))
	$context['script_url'] = str_replace($context['path_to_root'], $context['url_to_root'], $matches[0]);
elseif(isset($_SERVER['SCRIPT_NAME']) && preg_match('/'.preg_quote($context['url_to_root'], '/').'.+?\.php/', $_SERVER['SCRIPT_NAME'], $matches))
	$context['script_url'] = $matches[0];
elseif(isset($_SERVER['SCRIPT_NAME']) && preg_match('/'.preg_quote($context['path_to_root'], '/').'.+?\.php/', $_SERVER['SCRIPT_NAME'], $matches))
	$context['script_url'] = str_replace($context['path_to_root'], $context['url_to_root'], $matches[0]);
elseif(isset($_SERVER['SCRIPT_FILENAME']) && preg_match('/'.preg_quote($context['path_to_root'], '/').'.+?\.php/', $_SERVER['SCRIPT_FILENAME'], $matches))
	$context['script_url'] = str_replace($context['path_to_root'], $context['url_to_root'], $matches[0]);
elseif(isset($_SERVER['REQUEST_URI']) && preg_match('/\.php$/', $_SERVER['REQUEST_URI']))
	$context['script_url'] = $_SERVER['REQUEST_URI'];

// which script are we executing?
if(($context['with_profile'] == 'Y') && $context['script_url'] && !preg_match('/(error|services\/check|users\/heartbit|users\/visit)\.php/', $context['script_url']))
	Logger::remember($context['script_url'].': run', '', 'debug');

//
// decode script parameters passed in URL
//

// we cannot rewrite $_SERVER
$path_info = '';
if(isset($_SERVER['PATH_INFO']) && strlen($_SERVER['PATH_INFO']))
	$path_info = $_SERVER['PATH_INFO'];

// a tricky way to set path info correctly at some sites
elseif(isset($_SERVER['ORIG_PATH_INFO']) && $_SERVER['ORIG_PATH_INFO']) {

	// sometimes this is corrupted by CGI interface (e.g., 1and1) and ORIG_PATH_INFO takes the value of ORIG_SCRIPT_NAME
	if(isset($_SERVER['ORIG_SCRIPT_NAME']) && !strcmp($_SERVER['ORIG_PATH_INFO'], $_SERVER['ORIG_SCRIPT_NAME']))
		;
	elseif(isset($_SERVER['SCRIPT_NAME']) && !strcmp($_SERVER['ORIG_PATH_INFO'], $_SERVER['SCRIPT_NAME']))
		;

	else
		$path_info = $_SERVER['ORIG_PATH_INFO'];
}

// analyze script args (e.g. 'articles/view.php/123/3', where '123' is the article id, and '3' is the page number)
if(strlen($path_info)) {

	// split all args, if any, and decode each of them
	$arguments = explode('/', substr($path_info, 1));
	if(is_array($arguments)) {
		foreach($arguments as $argument)
			$context['arguments'][] = rawurldecode($argument);
	}
}

//
// Software extensions
//

// global definitions of extension
global $hooks;
$hooks = array();

// load software extensions, if any
if(!defined('NO_CONTROLLER_PRELOAD'))
	Safe::load('parameters/hooks.include.php');

//
// Transcoding stuff
//

/**
 * remove quoting recursively
 *
 * This function extends [code]stripslashes()[/code] to arrays.
 * Should probably be part of the next version of native PHP library?
 *
 * @param array of encoded fields
 * @return the transformed array
 */
function stripslashes_recursively($fields) {
	if(!is_array($fields))
		return $fields;
	foreach($fields as $name => $value) {
		if(is_array($value))
			$fields[$name] = stripslashes_recursively($value);
		else
			$fields[$name] = stripslashes($value);
	}
	return $fields;
}

// The very first thing is to manage parameters transmitted to scripts.
// If get_magic_quotes_gpc is on, we strip all slashes
if(@count($_REQUEST) && get_magic_quotes_gpc())
	$_REQUEST = stripslashes_recursively($_REQUEST);
if(@count($_COOKIE) && get_magic_quotes_gpc())
	$_COOKIE = stripslashes_recursively($_COOKIE);

// always disable magic quote runtime
if(is_callable('set_magic_quotes_runtime'))
	@set_magic_quotes_runtime(0);

// support utf-8
include_once $context['path_to_root'].'shared/utf8.php';

// user agent specifies which character set to use
if(isset($_SERVER['HTTP_ACCEPT_CHARSET'])) {

	// utf-8 is ok
	if(preg_match('/\butf-8\b/i', $_SERVER['HTTP_ACCEPT_CHARSET']))
		$context['charset'] = 'utf-8';

	// back to another charset
	else
		$context['charset'] = 'iso-8859-15';

// use also utf-8 where there is no specification
} else
	$context['charset'] = 'utf-8';

// transcode bookmarklet input -- see sections/view.php
if(isset($_REQUEST['text']) && $_REQUEST['text']) {

	// transcode what we have in iso-8859-1 to utf-8
	if(is_callable('mb_convert_variables'))
		$charset = mb_convert_variables('UTF-8', 'ISO-8859-1', $_REQUEST);

}

/**
 * encode a form field
 *
 * This function encode special HTML chars, such as '&', '&lt;', etc.
 * It also preserves Unicode entities, meaning that if you have typed an euro sign in an article,
 * you will still have an euro sign in the edit form instead of a mysterious &#8364;.
 *
 * Use it to encode every textarea or input field in forms.
 * Use it also to generate XML data, such as RSS feed.
 *
 * Note that unicode entities will be transformed to actual UTF-8 in YACS handler.
 *
 * @param string some text to be encoded
 * @return the string to be displayed
 */
function encode_field($text) {
	global $context;

	// not a string
	if(!is_string($text))
		return $text;

	// encode special chars
	$text = htmlspecialchars($text);

	// preserve unicode entities
	$text = preg_replace(array('/&amp;#/i', '/&amp;u/i'), array('&#', '&u'), $text);

	// transcode HTML entities to unicode
	$text =& utf8::transcode($text);

	// escape double quotes
	if($context['charset'] == 'utf-8')
		$text = str_replace(array('"', '&quot;', '&#34;'), '&quot;', $text);
	else
		$text = str_replace(array('"', '&quot;', '&#34;'), "'", $text);

	// prevent codes rendering within encoded fields
	$text = str_replace(array('[', ']'), array('&#91;', '&#93;'), $text);

	// done
	return $text;

}

/**
 * encode links properly
 *
 * @param string a web reference to check
 * @return a clean string
 */
function encode_link($link) {

	// suppress invalid chars, if any
	$output = trim(preg_replace(FORBIDDEN_IN_URLS, '_', str_replace(' ', '%20', $link)), ' _');

	// transform & to &amp;
	$output = str_replace('&', '&amp;', $output);

	// done
	return $output;
}

//
// Localization and internationalization
//

// no need to transform data
if(!defined('NO_VIEW_PRELOAD')) {

	// the internationalization library
	include_once $context['path_to_root'].'i18n/i18n.php';

	// initialize the library
	i18n::initialize();

	// load strings localized externally for shared scripts
	i18n::bind('shared');

}

//
// Access data
//

// if no parameters file, jump to the control panel, if not in it already
if(!is_readable($context['path_to_root'].'parameters/control.include.php') && !preg_match('/(\/control\/|\/included\/|setup\.php)/i', $context['script_url']))
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'control/');

// no need for data access
if(!defined('NO_MODEL_PRELOAD')) {

	// the SQL virtualization library
	include_once $context['path_to_root'].'shared/sql.php';

	// initialize connections to the database --will redirect on error
	SQL::initialize();

}

//
// All containers that will be referred in shared/anchors.php afterwards
//
if(!defined('NO_MODEL_PRELOAD')) {
	include_once $context['path_to_root'].'articles/articles.php';
	include_once $context['path_to_root'].'categories/categories.php';
	include_once $context['path_to_root'].'files/files.php';
	include_once $context['path_to_root'].'sections/sections.php';
	include_once $context['path_to_root'].'users/users.php';
	include_once $context['path_to_root'].'users/activities.php';

	// load users parameters -- see users/configure.php
	Safe::load('parameters/users.include.php');
}

//
// Anchor stuff -- see shared/anchor.php for more information
//

if(!defined('NO_MODEL_PRELOAD')) {

	// load the anchor interface
	include_once $context['path_to_root'].'shared/anchor.php';

	// global functions related to anchors
	include_once $context['path_to_root'].'shared/anchors.php';

}

// the overlay interface
if(!defined('NO_MODEL_PRELOAD'))
	include_once $context['path_to_root'].'overlays/overlay.php';

// the library for membership
if(!defined('NO_MODEL_PRELOAD'))
	include_once $context['path_to_root'].'shared/members.php';

// the library for messages
include_once $context['path_to_root'].'shared/mailer.php';

// load parameters for web services -- including debugging
if(!defined('NO_CONTROLLER_PRELOAD'))
	Safe::load('parameters/services.include.php');

// our knowledge about current surfer -- after the definition of path_to_root parameter, and after the loading of user parameters
include_once $context['path_to_root'].'shared/surfer.php';

//
// Switch management
//

// redirect if the server has been switched off and if not in the control panel, nor in the scripts or users modules
if(file_exists($context['path_to_root'].'parameters/switch.off') && !Surfer::is_associate() && !preg_match('/\/(control|included|scripts|users)\//i', $context['script_url']) && !preg_match('/\/configure\.php$/i', $context['script_url']))
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'control/closed.php');

//
// Skin and rendering -- see skins/index.php for more information
//

// start with a default skin
if(!isset($context['skin']) && is_dir($context['path_to_root'].'skins/digital'))
	$context['skin'] = 'skins/digital';

// load the layout interface, if we have access to some data
if(!defined('NO_MODEL_PRELOAD'))
	include_once $context['path_to_root'].'skins/layout.php';

// skin variant is asked for explicitly
if(isset($_REQUEST['variant']) && $_REQUEST['variant'])
	$context['skin_variant'] = basename(preg_replace(FORBIDDEN_IN_PATHS, '_', strip_tags($_REQUEST['variant'])));

// the layout for the home page is used at several places
if(!isset($context['root_articles_layout']) || !$context['root_articles_layout'])
	$context['root_articles_layout'] = 'daily';

/**
 * load a skin
 *
 * This function will declare the Skin class related to the skin declared in [code]$context['skin'][/code].
 * It does it by loading a file named [code]'skin.php'[/code].
 * For example, if you have stated: [code]$context['skin'] = 'skins/myskin'[/code],
 * then [code]load_skin()[/code] will include [code]'skins/myskin/skin.php'[/code].
 *
 * The skin can be overriden by changing options of a section, or of an article.
 * In both cases, use the keyword 'skin_foo' to have YACS look for the skin [code]foo[/code] and related files.
 *
 * The variant parameter determines the template used for page rendering.
 * If 'foo' is provided, YACS will look for template [code]template_foo.php[/code] in the target skin.
 *
 * The variant can be overriden by changing options of a section, or of an article.
 * In both cases, use the keyword 'variant_foo' to have YACS look for template [code]template_foo.php[/code] in the target skin.
 *
 * @param string a skin variant, if any
 * @param object anchor of the target item, if any
 * @param string additional options for the target item, if any
 */
function load_skin($variant='', $anchor=NULL, $options='') {
	global $context;
	
	// allow for only one call
	global $loading_fuse;
	if(isset($loading_fuse))
		return;
	$loading_fuse = TRUE;

	// use a specific skin, if any
	if($options && preg_match('/\bskin_(.+?)\b/i', $options, $matches))
		$context['skin'] = 'skins/'.$matches[1];
	elseif(is_object($anchor) && ($skin = $anchor->has_option('skin', FALSE)) && is_string($skin))
		$context['skin'] = 'skins/'.$skin;

	// load skins parameters, if any
	Safe::load('parameters/skins.include.php');
	Safe::load('parameters/root.include.php'); // to support Page::tabs()

	// quite new
	$context['fresh'] = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));

	// ensure tools are accessible
	if((strpos($context['skins_extra_components'], 'tools') === FALSE) && (strpos($context['skins_navigation_components'], 'tools') === FALSE))
		$context['skins_extra_components'] = 'tools '.$context['skins_extra_components'];

	// load skin basic library
	include_once $context['path_to_root'].'skins/skin_skeleton.php';

	// load actual skin
	if(is_readable($context['path_to_root'].$context['skin'].'/skin.php')) {
		include_once $context['path_to_root'].$context['skin'].'/skin.php';

	// no skin found, create a fake one
	} else {

		// actually, everything is in the skin skeleton
		class Skin extends Skin_Skeleton {
		}

	}

	// the codes library
	include_once $context['path_to_root'].'shared/codes.php';

	// the library of smileys
	include_once $context['path_to_root'].'smileys/smileys.php';
	
	// load the page library
	include_once $context['path_to_root'].'skins/page.php';

	// variant is already set
	if(isset($context['skin_variant']))
		;

	// use item variant
	elseif($options && preg_match('/\bvariant_(.+?)\b/i', $options, $matches))
		$context['skin_variant'] = $matches[1];

	// use anchor variant
	elseif(is_object($anchor) && ($anchor_variant = $anchor->has_option('variant', FALSE)) && is_string($anchor_variant))
		$context['skin_variant'] = $anchor_variant;

	// use provided variant
	else
		$context['skin_variant'] = $variant;

	// initialize skin constants
	if(!defined('BR'))
		Skin::load();

}

/**
 * actually render a page
 *
 * This function loads the template declared in [code]$context['skin'][/code].
 *
 * It does it by loading a file named [code]'template.php'[/code].
 * For example, if you have stated: [code]$context['skin'] = 'skins/myskin/'[/code],
 * then [code]render_skin()[/code] will include [code]'skins/myskin/template.php'[/code].
 *
 * Moreover, if a template is available for the current module, it will be loaded instead.
 * For example, if the module is [code]'sections'[/code], and if there a file named [code]'template_sections.php'[/code],
 * this one will be loaded instead of the standard [code]'template.php'[/code].
 *
 * The assumption here is that the executing script is either at the root level (e.g., the main
 * [code]index.php[/code] of the server) or at some level below (e.g., [code]articles/index.php[/code]).
 * Moreover, we are also assuming that the skin has been designed for the root level only.
 * Therefore, it has to be adapted for other scripts.
 * For example, [code]<img src="skins/myskin/mylogo.png" />[/code] has to be
 * changed to [code]<img src="../skins/myskin/mylogo.png" />[/code]. Of course, we would like to avoid
 * doing such a change on-the-fly to stay efficient.
 *
 * To cope with all these requirements, this function builds and uses a cached version of the
 * modified skin, according to the following algorithm:
 * - select the template file (e.g., [code]'skins/myskin/template.php'[/code])
 * - if the file exists, use it (we are at the root level)
 * - else if the cache file exists, use it (we are at one level down or below)
 * - else build and use the cache file (e.g., [code]'skins/myskin/template_cache.php'[/code])
 *
 * This function will also highlight some words in the page if we are coming from a search engine.
 * [*] Words to be highlighted can be found in [code]$_SERVER['HTTP_REFERER'][/code] if we are coming from Google
 * (field 'q'), from Yahoo (field 'p') or from the YACS search page (field 'search').
 * Alternatively, this script will also consider [code]$_REQUEST['highlight'][/code].
 * [*] This function look for highlighted words in following page components:
 * [code]$context['text'][/code] and [code]$context['title'][/code].
 * The style class [code]'highlight'[/code] is used.
 * [*] This function is activated only at [code]view.php[/code] scripts.
 *
 * @link http://www.webreference.com/programming/php/cookbook/chap11/1/6.html preg_match to highlight code
 *
 * This function will attempt to handle web caching through following mechanisms:
 *
 * [*] If [code]$context['text'][/code] has some content, and if there is no [code]send_body()[/code] function,
 * we assume that the page may be versioned.
 * In this case a [code]ETag headers[/code] is computed as being the hash of page content.
 * Page content is defined as resulting from the concatenation of:	[code]$context['page_title'][/code],
 * [code]$context['text'][/code] and [code]$context['extra'][/code].
 * Also, modification dates of [code]parameters/control.include.php[/code] and of [code]parameters/skins.include.php[/code], to reflect
 * any change of one of the main configuration files.
 * Lastly, the surfer capability is appended as well, to cope with login/logout correctly.
 * If the content changes, the value of the [code]ETag[/code] will change accordingly, and the new page will be provided.
 * Else the transaction will break on code [code]304 Not Modified[/code], savings some bytes and some time.
 * This mechanism is very efficient for the front page and for some index pages,
 * for which the content may vary but dates are difficult to evaluate.
 *
 * [*] If a time stamp is provided, the script sets the [code]Last-Modified[/code] attribute in responses and manages
 * the [code]If-Modified-Since[/code] attribute in requests.
 * If two dates are the same (and if [code]ETag[/code] attribute is not provided), the transaction will break on
 * code [code]304 Not Modified[/code], savings some bytes and some time.
 * Else the new page will be transmitted to the requestor.
 * This mechanism is very efficient for article pages, for which an edition date can be precisely assessed.
 *
 * Post-processing hooks are triggered after all HTML is returned to the browser,
 * including the poor-man's cron so the user who kicks off the cron jobs should not notice any delay.
 *
 */
function render_skin($with_last_modified=TRUE) {
	global $context, $local; // put here ALL global variables to be included in template, including $local

	// allow for only one call -- see scripts/validate.php
	global $rendering_fuse;
	if(isset($rendering_fuse))
		return;
	$rendering_fuse = TRUE;

	// ensure we have a fake skin, at least
	if(!is_callable(array('Skin', 'build_list'))) {

		class Skin {
			function &build_block($text) {
				return $text;
			}

			function &build_box($title, $content) {
				$text = '<h3>'.$title.'</h3><div>'.$content.'</div>';
				return $text;
			}

			function &build_link($url, $label) {
				$text = '<a href="'.$url.'">'.$label.'</a>';
				return $text;
			}

			function &build_list() {
				$text = '{list}';
				return $text;
			}

			function &build_user_menu() {
				$text = '{user_menu}';
				return $text;
			}

			function &finalize_list($list, $kind) {
				$text = '{list}';
				return $text;
			}

		}

		define('BR', '<br />');
		define('EOT', '/>');

		$context['skin_variant'] = 'fake';
	}

	// navigation - navigation boxes
	if(file_exists($context['path_to_root'].'parameters/switch.on')) {

		// cache dynamic boxes for performance, and if the database can be accessed
		$cache_id = 'shared/global.php#render_skin#navigation';
		if((!$text = Cache::get($cache_id)) && !defined('NO_MODEL_PRELOAD')) {

			// navigation boxes in cache
			global $global_navigation_box_index;
			if(!isset($global_navigation_box_index))
				$global_navigation_box_index = 20;
			else
				$global_navigation_box_index += 20;

			// the maximum number of boxes is a global parameter
			if(!isset($context['site_navigation_maximum']) || !$context['site_navigation_maximum'])
				$context['site_navigation_maximum'] = 7;

			// navigation boxes from the dedicated section
			$anchor = Sections::lookup('navigation_boxes');

			if($anchor && ($rows =& Articles::list_for_anchor_by('publication', $anchor, 0, $context['site_navigation_maximum'], 'boxes'))) {

				// one box per article
				foreach($rows as $title => $attributes)
					$text .= "\n".Skin::build_box($title, $attributes['content'], 'navigation', $attributes['id'])."\n";

				// cap the total number of navigation boxes
				$context['site_navigation_maximum'] -= count($rows);
			}

			// navigation boxes made from categories
			if($categories = Categories::list_by_date_for_display('site:all', 0, $context['site_navigation_maximum'], 'raw')) {

				// one box per category
				foreach($categories as $id => $attributes) {

					// box title
					$label = Skin::strip($attributes['title']);

					// link to the category page from box title
					if(is_callable(array('i18n', 's')))
						$label =& Skin::build_box_title($label, Categories::get_permalink($attributes), i18n::s('View the category'));

					// list sub categories
					$items = Categories::list_by_date_for_anchor('category:'.$id, 0, COMPACT_LIST_SIZE, 'compact');

					// list linked articles
					include_once $context['path_to_root'].'links/links.php';
					if($articles =& Members::list_articles_by_date_for_anchor('category:'.$id, 0, COMPACT_LIST_SIZE, 'compact')) {
						if($items)
							$items = array_merge($items, $articles);
						else
							$items = $articles;

					// else list links
					} elseif($links = Links::list_by_date_for_anchor('category:'.$id, 0, COMPACT_LIST_SIZE, 'compact')) {
						if($items)
							$items = array_merge($items, $links);
						else
							$items = $links;
					}

					// display what has to be displayed
					if($items)
						$text .= Skin::build_box($label, Skin::build_list($items, 'articles'), 'navigation')."\n";

				}
			}

			// save on requests
			Cache::put($cache_id, $text, 'various');

		}
		$context['navigation'] .= $text;

		// finalize page context
		if(is_callable(array('Skin', 'finalize_context')))
			Skin::finalize_context();

		// ensure adequate HTTP answer
		if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] && !preg_match('/\b('.str_replace(',', '|', $context['accepted_methods']).')\b/', $_SERVER['REQUEST_METHOD'])) {
			Safe::header('405 Method not allowed');
			Safe::header('Allow: '.$context['accepted_methods']);
			exit('Impossible to process this method.');
		}

		// answer OPTIONS requests
		if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'OPTIONS')) {
			Safe::header('Status: 200 OK', TRUE, 200);
			Safe::header('Allow: '.$context['accepted_methods']);
			Safe::header('Content-Type: text/plain');
			exit('Following methods are accepted: '.$context['accepted_methods']);
		}

	}

	// close pending connections
	if(is_callable(array('Mailer', 'close')))
		Mailer::close();

	// provide P3P compact policy, if any
	if(isset($context['p3p_compact_policy']))
		Safe::header('P3P: CP="'.$context['p3p_compact_policy'].'"');

	// inform proxies that we may serve several versions per reference
	Safe::header('Vary: Accept-Encoding, Cookie, ETag, If-None-Match, Set-Cookie');

	// handle web cache
	if(!isset($context['without_http_cache']) || ($context['without_http_cache'] != 'Y')) {

		// ask for revalidation
		http::expire(0);

		// validate the content if hash is ok - content depends also on configuration files and on surfer capability
		$etag = NULL;

		// don't cache too dynamic content
		if(is_callable('send_body'))
			;

		// don't cache debugging messages, nor errors
		elseif(isset($context['debug']) && count($context['debug']))
			;
		elseif(isset($context['error']) && count($context['error']))
			;

		// we rely on progressive hash
		elseif(is_callable('hash_init')) {

			// using MD5
			$h = hash_init('md5');

			if(isset($context['extra']))
				hash_update($h, $context['extra']);
			if(isset($context['navigation']))
				hash_update($h, $context['navigation']);
			if(isset($context['page_details']))
				hash_update($h, $context['page_details']);
			if(isset($context['page_image']))
				hash_update($h, $context['page_image']);
			if(isset($context['page_menu']))
				hash_update($h, Skin::build_list($context['page_menu'], 'page_menu'));
			if(isset($context['page_title']))
				hash_update($h, $context['page_title']);
			if(isset($context['page_tags']))
				hash_update($h, $context['page_tags']);
			if(isset($context['prefix']))
				hash_update($h, $context['prefix']);
			if(isset($context['suffix']))
				hash_update($h, $context['suffix']);
			if(isset($context['text']))
				hash_update($h, $context['text']);
			hash_update($h, $context['page_date']);
			hash_update($h, Safe::filemtime($context['path_to_root'].'parameters/control.include.php').':'
				.Safe::filemtime($context['path_to_root'].'parameters/skins.include.php'));

			// not the same content for editors
			if(is_callable(array('Surfer', 'get_id')))
				hash_update($h, Surfer::get_id());

			// not the same content for associates
			if(is_callable(array('Surfer', 'get_name')))
				hash_update($h, Surfer::get_name());

			// afford content negociation
			if(isset($_SERVER['HTTP_ACCEPT_ENCODING']))
				hash_update($h, $_SERVER['HTTP_ACCEPT_ENCODING']);

			// hash content to create the etag string
			$etag = '"'.hash_final($h).'"';

		}

		// provide stamp in the expected format
		$stamp = NULL;
		if(isset($context['cache_has_been_poisoned']) || !$with_last_modified)
			;
		elseif($context['page_date'] && is_callable(array('SQL', 'strtotime')))
			$stamp = gmdate('D, d M Y H:i:s', SQL::strtotime($context['page_date'])).' GMT';
		if(http::validate($stamp, $etag))
			return;

	}

	// if it was a HEAD request, stop here
	if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
		return;

	// more meta information
	$metas = array();

	// we support Dublin Core too
	$metas[] = '<link rel="schema.DC" href="http://purl.org/dc/elements/1.1/" />';

	// page title
	$page_title = ucfirst(strip_tags($context['page_title']));
	$context['page_header'] .= '<title>'.$page_title;
	if($context['site_name'] && !preg_match('/'.str_replace('/', ' ', strip_tags($context['site_name'])).'/', strip_tags($context['page_title']))) {
		if($page_title)
			$context['page_header'] .= ' - ';
		$context['page_header'] .= strip_tags($context['site_name']);
	}
	$context['page_header'] .= "</title>\n";
	if($page_title)
		$metas[] = '<meta name="DC.title" content="'.encode_field($page_title).'" />';

	// set icons for this site
	if($context['site_icon']) {
		$metas[] = '<link rel="icon" href="'.$context['url_to_root'].$context['site_icon'].'" type="image/x-icon" />';
		$metas[] = '<link rel="shortcut icon" href="'.$context['url_to_root'].$context['site_icon'].'" type="image/x-icon" />';
	}

	// a meta-link to our help page
	$metas[] = '<link rel="help" href="'.$context['url_to_root'].'help/" type="text/html" />';

	// page meta description
	if(isset($context['page_meta']) && $context['page_meta']) {
		$metas[] = '<meta name="description" content="'.encode_field(strip_tags($context['page_meta'])).'" />';
		$metas[] = '<meta name="DC.description" content="'.encode_field(strip_tags($context['page_meta'])).'" />';
	} elseif(isset($context['site_description']) && $context['site_description']) {
		$metas[] = '<meta name="description" content="'.encode_field(strip_tags($context['site_description'])).'" />';
		$metas[] = '<meta name="DC.description" content="'.encode_field(strip_tags($context['site_description'])).'" />';
	}

	// page copyright
	if(isset($context['site_copyright']) && $context['site_copyright'])
		$metas[] = '<meta name="copyright" content="'.encode_field($context['site_copyright']).'" />';

	// page author
	if(isset($context['page_author']) && $context['page_author']) {
		$metas[] = '<meta name="author" content="'.encode_field($context['page_author']).'" />';
		$metas[] = '<meta name="DC.author" content="'.encode_field($context['page_author']).'" />';
	}

	// page publisher
	if(isset($context['page_publisher']) && $context['page_publisher']) {
		$metas[] = '<meta name="publisher" content="'.encode_field($context['page_publisher']).'" />';
		$metas[] = '<meta name="DC.publisher" content="'.encode_field($context['page_publisher']).'" />';
	}

	// page keywords
	if(isset($context['site_keywords']) && $context['site_keywords']) {
		$metas[] = '<meta name="keywords" content="'.encode_field($context['site_keywords']).'" />';
		$metas[] = '<meta name="DC.subject" content="'.encode_field($context['site_keywords']).'" />';
	}

	// page date
	if($context['page_date'])
		$metas[] = '<meta name="DC.date" content="'.encode_field(substr($context['page_date'], 0, 10)).'" />';

	// page language
	if($context['page_language'])
		$metas[] = '<meta name="DC.language" content="'.encode_field($context['page_language']).'" />';

	// revisit-after
	if(!isset($context['site_revisit_after']))
		;
	elseif($context['site_revisit_after'] == 1)
		$metas[] = '<meta name="revisit-after" content="1 day" />';
	elseif($context['site_revisit_after'])
		$metas[] = '<meta name="revisit-after" content="'.encode_field($context['site_revisit_after']).' days" />';

	// no Microsoft irruption in our pages
	$metas[] = '<meta name="MSSmartTagsPreventParsing" content="TRUE" />';

	// suppress awful hovering toolbar on images in IE
	$metas[] = '<meta http-equiv="imagetoolbar" content="no" />';

	// lead robots
	$metas[] = '<meta name="robots" content="index,follow" />';

	// help Javascript scripts to locate files --in header, because of potential use by in-the-middle javascript snippet
	$metas[] = JS_PREFIX
		.'	var url_to_root = "'.$context['url_to_home'].$context['url_to_root'].'";'."\n"
		.'	var url_to_skin = "'.$context['url_to_home'].$context['url_to_root'].$context['skin'].'/"'."\n"
		.JS_SUFFIX;

	// activate tinyMCE, if available
	if(isset($context['javascript']['tinymce'])) {

		Page::defer_script('included/tiny_mce/tiny_mce.js');
		Page::insert_script('	tinyMCE.init({'."\n"
			.'		mode : "textareas",'."\n"
			.'		theme : "advanced",'."\n"
			.'		editor_selector : "tinymce",'."\n"
			.'		language : "'.$context['language'].'",'."\n"
			.'		disk_cache : false,'."\n"
			.'		relative_urls : false,'."\n"
			.'		remove_script_host : false,'."\n"
			.'		document_base_url : "'.$context['url_to_home'].$context['url_to_root'].'",'."\n"
			.'		plugins : "safari,table,advhr,advimage,advlink,emotions,insertdatetime,searchreplace,paste,directionality,fullscreen,visualchars",'."\n"
			.'		theme_advanced_buttons1 : "cut,copy,paste,pastetext,pasteword,|,formatselect,fontselect,fontsizeselect",'."\n"
			.'		theme_advanced_buttons2 : "bold,italic,underline,strikethrough,|,forecolor,backcolor,|,justifyleft,justifycenter,justifyright,justifyfull,|,bullist,numlist,|,outdent,indent,blockquote,|,removeformat",'."\n"
			.'		theme_advanced_buttons3 : "charmap,emotions,advhr,|,sub,sup,|,insertdate,inserttime,|,link,unlink,anchor,image,|,ltr,rtl,|,undo,redo,|,fullscreen",'."\n"
			.'		theme_advanced_buttons4 : "tablecontrols,|,search,replace,|,cleanup,code,help",'."\n"
			.'		theme_advanced_toolbar_location : "top",'."\n"
			.'		theme_advanced_toolbar_align : "left",'."\n"
			.'		theme_advanced_statusbar_location : "bottom",'."\n"
			.'		theme_advanced_resizing : true,'."\n"
			.'		extended_valid_elements : "a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name],hr[class|width|size|noshade],font[face|size|color|style],span[class|align|style]",'."\n"
			.'		template_external_list_url : "example_template_list.js",'."\n"
			.'		use_native_selects : true,'."\n"
			.'		debug : false	});'."\n"
			);

	}

	// javascript libraries files to declare in header of page
	$context['page_header'] .= Js_Css::get_js_libraries('js_header');
	
		// insert headers (and maybe, include more javascript files)
	if(isset($context['site_head']))
		$metas[] = $context['site_head'];			
	
	// provide a page reference to Javascript --e.g., for reporting activity from this page
	$context['page_footer'] .= JS_PREFIX;

	// a reference to the data we are at (e.g., 'article:123')
	if(isset($context['current_item']) && $context['current_item'])
		$context['page_footer'] .= '	Yacs.current_item = "'.$context['current_item'].'";'."\n";
	else
		$context['page_footer'] .= '	Yacs.current_item = "";'."\n";

	// some indication at what we are doing (e.g., 'edit')
	if(isset($context['current_action']) && $context['current_action'])
		$context['page_footer'] .= '	Yacs.current_action = "'.$context['current_action'].'";'."\n";
	else
		$context['page_footer'] .= '	Yacs.current_action = "";'."\n";

	$context['page_footer'] .= JS_SUFFIX;

	// jquery-ui stylesheet	
	Page::load_style('included/browser/css/redmond/jquery-ui-1.10.3.custom.min.css');

	// activate jscolor, if available
	if(isset($context['javascript']['jscolor']))
		Page::load_script('included/jscolor/jscolor.js');

	// activate SIMILE timeline, if required
	if(isset($context['javascript']['timeline']))		
		Page::load_script('http://simile.mit.edu/timeline/api/timeline-api.js');
		

	// activate SIMILE timeplot, if required
	if(isset($context['javascript']['timeplot']))		
		Page::load_script('http://api.simile-widgets.org/timeplot/1.1/timeplot-api.js');

	// activate SIMILE exhibit, if required
	if(isset($context['javascript']['exhibit']))
		Page::load_script('http://static.simile.mit.edu/exhibit/api-2.0/exhibit-api.js');

	// activate OpenTok, if required
	if(isset($context['javascript']['opentok']))		
		Page::load_script('http://static.opentok.com/v0.91/js/TB.min.js');
	
	// activate jsCalendar, if required
	if(isset($context['javascript']['calendar'])) {

		// load the skin
		Page::load_style('included/jscalendar/skins/aqua/theme.css');
		Page::load_style('included/jscalendar/calendar-system.css');		

		// use the compressed version if it's available
		Page::defer_script('included/jscalendar/calendar.min.js');		

		if(file_exists($context['path_to_root'].'included/jscalendar/lang/calendar-'.strtolower($context['language']).'.js'))
		    Page::defer_script('included/jscalendar/lang/calendar-'.strtolower($context['language']).'.js');			
		else 
		    Page::defer_script ('included/jscalendar/lang/calendar-en.js');		    		

		Page::defer_script('included/jscalendar/calendar-setup.min.js');		

	}
	
	// load occasional libraries declared through scripts
	if(isset($context['javascript']['header']))
	    $context['page_header'] .= $context['javascript']['header'];
	
	
	// load occasional libraries declared through scripts
	if(isset($context['javascript']['footer']))
		$context['page_footer'] .= $context['javascript']['footer'];

	// javascript libraries files to declare in footer of page, plus YACS ajax library
	$context['page_footer'] = Js_Css::get_js_libraries('js_endpage','shared/yacs.js').$context['page_footer'];	

	// site trailer, if any
	if(isset($context['site_trailer']) && $context['site_trailer'])
		$context['page_footer'] .= $context['site_trailer']."\n";		

	// insert one tabulation before each header line
	$context['page_header'] = "\t".str_replace("\n", "\n\t", join("\n", $metas)."\n".$context['page_header'])."\n";

	// handle the output correctly
	Safe::ob_start('yacs_handler');

	// return the standard MIME type, but ensure the user agent will accept it -- Internet Explorer 6 don't
//		if(isset($_SERVER['HTTP_ACCEPT']) && stristr($_SERVER['HTTP_ACCEPT'], 'application/xhtml+xml'))
//			$context['content_type'] = 'application/xhtml+xml';
//	else
		$context['content_type'] = 'text/html';
	Safe::header('Content-Type: '.$context['content_type'].'; charset='.$context['charset']);

	// load a template for this module -- php version
	if(isset($context['skin_variant']) && is_readable($context['path_to_root'].$context['skin'].'/template_'.$context['skin_variant'].'.php'))
		include $context['path_to_root'].$context['skin'].'/template_'.$context['skin_variant'].'.php';

	// else use the original template -- php version
	elseif(is_readable($context['path_to_root'].$context['skin'].'/template.php'))
		include $context['path_to_root'].$context['skin'].'/template.php';

	// no valid template has been loaded, build one from scratch
	else {
		global $context;
		echo "<html>\n<head>\n";

		// the title
		echo '<title>'.ucfirst(strip_tags($context['page_title']));
		if($context['site_name'] && isset($context['skin_variant']) && ($context['skin_variant'] != 'home'))
			echo ' - '.$context['site_name'];
		echo '</title>';

		// display the dynamic header, if any
		if(is_callable('send_meta'))
			send_meta();

		echo "</head>\n<body>\n";

		// display the title
		if($context['page_title'])
			echo '<h1>'.$context['page_title']."</h1>\n";

		// display the page menu
		if(is_array($context['page_menu']) && count($context['page_menu']))
			echo Skin::build_list($context['page_menu'], 'page_menu');

		// display error messages, if any
		if(count($context['error']))
			echo '<p>'.implode("<p>\n</p>", $context['error'])."</p>\n";

		// render and display the content, if any
		echo $context['text'];
		$context['text'] = '';

		// display the dynamic content, if any
		if(is_callable('send_body'))
			send_body();

		// maybe some additional text has been created in send_body()
		echo $context['text'];

		// debug output, if any
		if(is_array($context['debug']) && count($context['debug']))
			echo "\n".'<ul id="debug">'."\n".'<li>'.implode('</li>'."\n".'<li>', $context['debug']).'<li>'."\n".'</ul>'."\n";

		// render and display extra content, if any
		if($context['extra'])
			echo '<hr />'."\n".$context['extra'];

		// allow anonymous user to login
		if(is_callable(array('Surfer', 'is_logged')) && !Surfer::is_logged())
			echo '<hr />'."\n".Skin::build_link('users/login.php', 'Login', 'basic');

		echo "\n</body>\n</html>";

	}

	// tick only during regular operation
	if(!file_exists($context['path_to_root'].'parameters/switch.on'))
		return;

	// no tick on error
	if(isset($context['skin_variant']) && ($context['skin_variant'] == 'error'))
		return;

	// allow for only one call -- see scripts/validate.php
	global $finalizing_fuse;
	if(isset($finalizing_fuse))
		return;
	$finalizing_fuse = TRUE;

	// the post-processing hooks
	if(!is_callable(array('Hooks', 'include_scripts')))
		return;

	// don't stop even if the browser connection dies
	Safe::ignore_user_abort(TRUE);

	// statistics, etc...
	Hooks::include_scripts('finalize');

	// the poor man's cron, except if actual cron has been activated
	if(isset($context['with_cron']) && ($context['with_cron'] == 'Y'))
		return;

	// tick only on selected regular scripts
	if(!preg_match('/\b(view|index)\.php\b/', $context['script_url']))
		return;

	// boost the front page -- no tick here
	if(isset($context['skin_variant']) && ($context['skin_variant'] == 'home'))
		return;

	// get date of last tick
	include_once $context['path_to_root'].'shared/values.php';	// cron.tick
	$stamp = Values::get_stamp('cron.tick');

	// wait at least 5 minutes = 300 seconds between ticks
	if($stamp > NULL_DATE)
		$target = SQL::strtotime($stamp) + 300;
	else
		$target = time();
	if($target > time())
		return;

	// remember cron tick
	if($context['with_debug'] == 'Y')
		Logger::remember('cron.php: tick', '', 'debug');

	// trigger background processing -- capture the output and don't send it back to the browser
	$context['cron_text'] = Hooks::include_scripts('tick');

	// remember tick date
	Values::set('cron.tick', $context['cron_text']);

}

/**
 * render raw content
 *
 * This function only installs the output handler.
 *
 * @param string content type of the output
 *
 */
function render_raw($type=NULL) {
	global $context;

	// allow for only one call -- see scripts/validate.php
	global $rendering_fuse;
	if(isset($rendering_fuse))
		return;
	$rendering_fuse = TRUE;

	// sanity check
	if(!$type) {
		// return the standard MIME type, but ensure the user agent will accept it -- Internet Explorer 6 don't
		if(isset($_SERVER['HTTP_ACCEPT']) && stristr($_SERVER['HTTP_ACCEPT'], 'application/xhtml+xml'))
			$type = 'application/xhtml+xml; charset='.$context['charset'];
		else
			$type = 'text/html; charset='.$context['charset'];
	}

	// remember type
	if($position = strpos($type, ';'))
		$context['content_type'] = substr($type, 0, $position);
	else
		$context['content_type'] = $type;

	// handle the output correctly
	Safe::ob_start('yacs_handler');
	Safe::header('Content-Type: '.$type);

}

/**
 * finalize page rendering
 *
 * This function is equivalent to what is done during regular page rendering
 * at the end of render_skin(), except that:
 * - script execution may be stopped and function never return,
 * - and no background activities will take place (cron)
 *
 * For example you should use this function instead of render_skin() for any
 * real-time script (e.g., comments/thread.php) or for scripts that return
 * specific MIME types (e.g., sections/feed.php)
 *
 * @param boolean TRUE to not return, FALSE to allow for subsequent execution steps
 */
function finalize_page($no_return=FALSE) {
	global $context;

	// always return on HEAD -- see scripts/validate.php
	if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
		$no_return = FALSE;

	// no tick on error
	if(isset($context['skin_variant']) && ($context['skin_variant'] == 'error'))
		if($no_return) exit; else return;

	// allow for only one call -- see scripts/validate.php
	global $finalizing_fuse;
	if(isset($finalizing_fuse))
		if($no_return) exit; else return;
	$finalizing_fuse = TRUE;

	// the post-processing hooks
	if(!is_callable(array('Hooks', 'include_scripts')))
		if($no_return) exit; else return;

	// don't stop even if the browser connection dies
	Safe::ignore_user_abort(TRUE);

	// statistics, etc...
	Hooks::include_scripts('finalize');

	// asta la vista, baby
	if($no_return) exit; else return;
}

/**
 * general purpose handler
 *
 * This handler is able:
 * - to transcode data to another character set
 * - to compress data, if asked for it
 * - to set the Content-Length HTTP header
 *
 * We have it because the standard 'ob_gzhandler' does not set the Content-Length header properly.
 * Also, this function transcodes char to valid Unicode if necessary.
 *
 * @link http://dev.e-taller.net/gzhandler/miscGzHandler.phps
 *
 * @parameter string the buffered page
 * @return string the string to actually transmit
 */
function yacs_handler($content) {
	global $context;

	// transform standalone & to &amp; -- do not transform javascript operator &&
	if(($context['content_type'] == 'text/html') || ($context['content_type'] == 'application/xhtml+xml')) {

		// do not transform CDATA
		$areas = preg_split('/(<!\[CDATA\[.*?]]>)/is', trim($content), -1, PREG_SPLIT_DELIM_CAPTURE);
		$index = 0;
		$content = '';
		foreach($areas as $area) {
			switch($index%2) {
			case 0: // area to be formatted
				$content .= preg_replace('/(?<!&)&(?![#0-9a-zA-Z]+;)(?!&)/', '&amp;', $area);
				break;

			case 1: // area to be preserved
				$content .= $area;
				break;
			}
			$index++;
		}
	}

	// normally, Unicode entities are transformed to UTF-8
	if($context['charset'] == 'utf-8')
		$content = utf8::from_unicode($content);

	// otherwise, Unicode entities are transformed according to expected charset
	elseif(strpos($context['charset'], 'iso-8859') === 0)
		$content = utf8::to_iso8859($content);

	// empty string cannot be compressed
	if(empty($content))
		$compress = FALSE;

	// headers have already been sent
	elseif(headers_sent())
		$compress = FALSE;

	// this run-time cannot compress
	elseif(!is_callable('gzcompress'))
		$compress = FALSE;

	// user agent does not support compression
	elseif(!isset($_SERVER['HTTP_ACCEPT_ENCODING']))
		$compress = FALSE;

	// user agent does not support gzip compression
	elseif(!preg_match('/(x-gzip|gzip)\b/i', $_SERVER['HTTP_ACCEPT_ENCODING'], $matches))
		$compress = FALSE;

	// compression has not been allowed
	elseif(!isset($context['with_compression']) || ($context['with_compression'] != 'Y'))
		$compress = FALSE;

	// YES, you can compress
	else
		$compress = TRUE;

	// send plain data
	if(!$compress) {
		if(!headers_sent())
			Safe::header('Content-Length: '.strlen($content));
		return $content;
	}

	// compress data
	$data = gzcompress($content, 5);
	$data = pack('cccccccc',0x1f,0x8b,0x08,0x00,0x00,0x00,0x00,0x00)
		.substr($data, 0, -4)
		.pack('V',crc32($content))
		.pack('V',strlen($content));
	Safe::header('Content-Encoding: '.$matches[1]);
	Safe::header('Content-Length: '.strlen($data));
	return $data;
}

/**
 * change base use for a large number in order to reduce the number of digits
 *
 * @param int number to convert
 * @return string reduced representation
 */
function reduce_number($number) {

	// safety test
	if(!is_callable('bcscale'))
		return $number;

	// 62 digits
	$digits = '1234567890aAbBcCdDeEfFgGhHiIjJkKlLmMnNoOpPqQrRsStTuUvVwWxXyYzZ';

	// compute each digit
	bcscale(0);
	$result = '';
	while($number > 61) {
		$rest = bcmod($number, 62);
		$result = $digits[$rest].$result;

		$number = bcdiv($number, 62);
	}
	$result = $digits[intval($number)].$result;

	// job done
	return $result;
}

/**
 * restore a reduced number
 *
 * @return string reduced representation
 * @param int associated number
 */
function restore_number($number) {

	// safety test
	if(!is_callable('bcscale'))
		return $number;

	// 62 digits
	$digits = '1234567890aAbBcCdDeEfFgGhHiIjJkKlLmMnNoOpPqQrRsStTuUvVwWxXyYzZ';

	// compute each digit
	$result = '0';
	$count = strlen($number);
	for($index = 0; $index < $count; $index++) {
		$position = strpos($digits, $number[$index]);
		$result = bcadd($result, bcmul($position, bcpow(62, $count-$index-1)));
	}

	// job done
	return $result;

}

/**
 * normalize links
 *
 * By default, a relative URL will be provided (e.g. '[code]articles/view.php?id=512[/code]'),
 * which may be not processed correctly by search engines.
 * If the parameter '[code]with_friendly_urls[/code]' has been set to '[code]Y[/code]' in the configuration panel,
 * this function will return an URL parsable by search engines (e.g. '[code]articles/view.php/512[/code]').
 * If the parameter '[code]with_friendly_urls[/code]' has been set to '[code]R[/code]' in the configuration panel,
 * then rewriting is assumed and pretty links are generated (e.g. '[code]article-512[/code]').
 *
 * This function has to be closely synchronized with rules defined in the main
 * ##.htaccess## file provided in YACS archive.
 *
 * @link http://www.mattcutts.com/blog/dashes-vs-underscores Why we use dashes
 * @link http://www.w3.org/Provider/Style/URI Cool URIs don't change
 *
 * @param mixed the module (e.g., 'articles') or an array (e.g. array('categories', 'category'))
 * @param string the expected action ('view', 'print', 'edit', 'delete', ...)
 * @param mixed the id of the item to handle, or an anchor reference
 * @param string additional data, such as page name, if any
 * @return string a normalized reference
 *
 * @see control/configure.php
 */
function normalize_url($prefix, $action, $id, $name=NULL) {
	global $context;

	// sanity check
	if(!$id)
		return NULL;

	// separate args
	if(is_array($prefix)) {
		$module = $prefix[0];
		if(isset($prefix[1]))
			$alternate = $prefix[1];
		else
			$alternate = $module;
	} else
		$module = $alternate = $prefix;

	// the id may be a composed anchor
	$nouns = explode(':', $id, 2);

	// ensure a safe name
	if(isset($name))
		$name = strtolower(utf8::to_ascii(trim($name), URL_SAFE_ALPHABET));

	// do not fool rewriting, just in case alternate name would be put in title
	if(isset($name))
		$name = preg_replace('/([a-z_]+)-([0-9]+)$/', '', $name);

	// remove dashes at both ends
	if(isset($name))
		$name = trim($name, '-');

	// be cool with search engines
	if($context['with_friendly_urls'] == 'Y') {

		// reference module and action in reference
		if($action == 'navigate')
			$link = $module.'/view.php/';
		else
			$link = $module.'/'.$action.'.php/';

		// 'section:123' -> 'section/123'
		if(count($nouns) == 2)
			$link .= rawurlencode($nouns[0]).'/'.rawurlencode($nouns[1]);

		// only append target id
		else
			$link .= rawurlencode($id);

		// a prefix for navigation links
		if($action == 'navigate')
			$link .= '?'.urlencode($name).'=';

		// append name, if any, for comprehensive URL rewriting
		elseif(isset($name) && $name)
			$link .= '/'.rawurlencode($name);

		// done
		return $link;

	// use rewriting engine to achieve pretty references, except if composed anchor -- look .htaccess
	} elseif(($context['with_friendly_urls'] == 'R') && (count($nouns) == 1)) {

		// 'view' and 'navigate' are special cases, else insert action in reference
		if(($action == 'view') || ($action == 'navigate'))
			$link = $alternate.'-';
		else
			$link = $alternate.'-'.$action.'/';

		// append target id
		$link .= rawurlencode($id);

		// a prefix for navigation links
		if($action == 'navigate')
			$link .= '/'.rawurlencode($name).'-';

		// append normalized name, if any, for comprehensive URL rewriting
		elseif(isset($name) && $name)
			$link .= '-'.rawurlencode($name);

		// done
		return $link;

	// generate a link safe at all systems
	} else {

		// a prefix for navigation links
		if($action == 'navigate')
			$link = $module.'/view.php?id='.urlencode($id).'&amp;'.rawurlencode($name).'=';

		// regular case
		else {
			$link = $module.'/'.$action.'.php?id='.urlencode($id);

			// append name, if any, for comprehensive URL rewriting -- use '&' and not '&amp;' else users/element.php is killed
			if(isset($name) && $name)
				$link .= '&action='.urlencode($name);

		}

		// done
		return $link;
	}
}

/**
 * create a shortcut link
 *
 * @param string page name
 * @return string related URL
 */
function normalize_shortcut($id, $with_prefix=FALSE) {
	global $context;

	// sanity check
	if(!$id)
		return NULL;

	// be cool with search engines
	if($context['with_friendly_urls'] == 'Y')
		$link = 'go.php/'.rawurlencode($id);

	// use rewriting engine to achieve pretty references, except if composed anchor -- look .htaccess
	elseif(($context['with_friendly_urls'] == 'R'))
		$link = 'go/'.rawurlencode($id);

	// generate a link safe at all systems
	else
		$link = 'go.php?id='.urlencode($id);

	// link prefix
	if($with_prefix)
		$link = $context['url_to_home'].$context['url_to_root'].$link;

	// job done
	return $link;
}

/**
 * proxy some URL through the yacs server
 *
 * @param string target URL, maybe from another server
 * @return string proxied URL
 */
function proxy($url) {
	global $context;

	return $context['url_to_home'].$context['url_to_root'].'services/proxy.php?url='.urlencode($url);
}

?>
