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

// we will manage the cache by ourself
if(is_callable('session_cache_limiter'))
	@session_cache_limiter('none');

// retrieve session data, but not if run from the command line
if(isset($_SERVER['REMOTE_ADDR']) && !headers_sent() && (session_id() == ''))
	session_start();

// default value for name filtering in forms (e.g. 'edit_name' filled by anonymous surfers)
if(!defined('FORBIDDEN_IN_NAMES'))
	define('FORBIDDEN_IN_NAMES', '/[<>{}\(\)]+/');

// default value for path filtering in forms -- ../ and \
if(!defined('FORBIDDEN_IN_PATHS'))
	define('FORBIDDEN_IN_PATHS', '/\.{2,}\//');

// default value for codes filtering for teasers
if(!defined('FORBIDDEN_IN_TEASERS'))
	define('FORBIDDEN_IN_TEASERS', '/\[(location=[^\]]+?|table=[^\]]+?|toc|toq)\]\s*/i');

// default value for url filtering in forms
if(!defined('FORBIDDEN_IN_URLS'))
	define('FORBIDDEN_IN_URLS', '/[^\w~_:@\/\.&#;\,+%\?=\-\[\]]+/');

// store attributes for this request, including global parameters and request-specific variables
global $context;

// ensure we have built everything --stop all kinds of data injections
$context = array();

// the HTTP accepted verbs by default --can be modified in some scripts, if necessary
$context['accepted_methods'] = 'GET,HEAD,OPTIONS,POST,PUT';

// parameters of the scripts passed in the URL, if any
$context['arguments'] = array();

// pre-built extra and navigation boxes
$context['aside'] = array();

// type of object produced by YACS
$context['content_type'] = 'text/html';

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

// compute server gmt offset based on system configuration
$context['gmt_offset'] = intval((strtotime(date('M d Y H:i:s')) - strtotime(gmdate('M d Y H:i:s'))) / 3600);

// required client libraries
$context['javascript'] = array();

// surfer preferred language -- changed in i18n::initialize() in i18n/i18n.php
$context['language'] = 'en';

// content of the navigation panel
$context['navigation'] = '';

// default P3P compact policy enables IE support of our cookies, even through frameset -- http://support.microsoft.com/kb/323752
$context['p3p_compact_policy'] = 'CAO PSA OUR';

// page author (meta-information)
$context['page_author'] = '';

// page details (complementary information about the page)
$context['page_details'] = '';

// additional content for the page footer --cache restriction
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

// prefix for page main content
$context['prefix'] = '';

// suffix for page main content
$context['suffix'] = '';

// site icon --the little image displayed in bookmarks
$context['site_icon'] = '';

// site slogan
$context['site_slogan'] = '';

// components to put in the extra panel --see skins/configure.php
$context['skins_extra_components'] = 'profile tools news overlay boxes share channels twins neighbours contextual categories bookmarklets servers download referrals visited';

// components to put in the side panel --see skins/configure.php
$context['skins_navigation_components'] = 'menu user navigation';

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

// path to files and images, for supported virtual hosts --see files/edit.php and images/edit.php
$context['virtual_path'] = '';

// use titles in address -- see control/configure.php
$context['with_alternate_urls'] = 'N';

// debug the execution of this script -- see control/configure.php
$context['with_debug'] = 'N';

// how to build links -- see control/configure.php
$context['with_friendly_urls'] = 'N';

// profile execution of this script
$context['with_profile'] = 'N';

//
// System parameters
//

// get our position from the environment --always end the string with a slash
if(isset($_ENV['YACS_HOME']))
	$context['path_to_root'] = str_replace('//', '/', $_ENV['YACS_HOME'].'/');

// get our position from run-time
else
	$context['path_to_root'] = dirname(dirname(__FILE__)).'/';

// fix windows backslashes
$context['path_to_root'] = str_replace('\\', '/', $context['path_to_root']);

// sanity checks - /foo/bar/.././ -> /foo/
$context['path_to_root'] = preg_replace(array('|/([^/]*)/\.\./|', '|/\./|'), '/', $context['path_to_root']);

// the safe library
include_once $context['path_to_root'].'shared/safe.php';

// the logging facility
include_once $context['path_to_root'].'shared/logger.php';

// the cache library
include_once $context['path_to_root'].'shared/cache.php';

// load general parameters -- see control/configure.php
Safe::load('parameters/control.include.php');

// maximize level of error reporting on development servers
if($context['with_debug'] == 'Y') {
	Safe::ini_set('display_errors','1');
	Safe::ini_set('display_startup_errors','1');
	$level = E_ALL;
} else
	$level = E_ALL ^ (E_NOTICE | E_USER_NOTICE | E_WARNING);
Safe::error_reporting($level);

// profile this page -- add '?profile' at the end of the URL to profile, but only at selected sites
if(($context['with_debug'] == 'Y') && isset($_REQUEST['profile']))
	$context['with_profile'] = 'Y';

// performance stuff
global $logger_profile_data;
$logger_profile_data = array();

// profiling mode -- okay, we are missing things happening before this line...
if($context['with_profile'] == 'Y')
	logger::profile('global', 'start');

// the name of this server
if(isset($_SERVER['HTTP_HOST']))
	$context['host_name'] = strip_tags($_SERVER['HTTP_HOST']); // from HTTP request
elseif(!isset($_SERVER['REMOTE_ADDR']) && isset($context['main_host']))
	$context['host_name'] = $context['main_host'];	// pretend we are a virtual host during crontab job
elseif(isset($_SERVER['SERVER_NAME']))
	$context['host_name'] = strip_tags($_SERVER['SERVER_NAME']); // from web daemon configuration file
else
	$context['host_name'] = 'localhost';

// strip port number, if any
if($here = strrpos($context['host_name'], ':'))
	$context['host_name'] = substr($context['host_name'], 0, $here);

// normalize virtual name and strip leading 'www.'
$virtual = 'virtual_'.preg_replace('/^www\./', '', $context['host_name']);

// load parameters specific to this virtual host, if any, and create specific path for files and images
if(Safe::load('parameters/'.$virtual.'.include.php'))
	$context['virtual_path'] = $virtual.'/';

// start with a default skin
if(!isset($context['skin']) && is_dir($context['path_to_root'].'skins/digital'))
	$context['skin'] = 'skins/digital';

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
	Logger::remember($context['script_url'], 'run', '', 'debug');

//
// decode script parameters passed in URL
//

// we cannot rewrite $_SERVER
$path_info = '';
if(isset($_SERVER['PATH_INFO']) && strlen($_SERVER['PATH_INFO']))
	$path_info = $_SERVER['PATH_INFO'];

// a tricky way to set path info correctly at some sites
elseif(isset($HTTP_SERVER_VARS['ORIG_PATH_INFO']) && $HTTP_SERVER_VARS['ORIG_PATH_INFO'])
	$path_info = $HTTP_SERVER_VARS['ORIG_PATH_INFO'];

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
// Transcoding stuff -- after setting of skin variant
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
function &encode_field($text) {
	global $context;

	// encode special chars
	$output = htmlspecialchars($text);

	// preserve unicode entities
	$output = preg_replace(array('/&amp;#/i', '/&amp;u/i'), array('&#', '&u'), $output);

	// transcode HTML entities to unicode
	$output =& utf8::transcode($output);

	// escape double quotes
	if($context['charset'] == 'utf-8')
		$output = str_replace(array('"', '&quot;', '&#34;'), '&quot;', $output);
	else
		$output = str_replace(array('"', '&quot;', '&#34;'), "'", $output);

	// prevent codes rendering within encoded fields
	$output = str_replace(array('[', ']'), array('&#91;', '&#93;'), $output);

	// done
	return $output;

}

/**
 * encode links properly
 *
 * @param string a web reference to check
 * @return a clean string
 */
function &encode_link($link) {

	// suppress invalid chars, if any
	$output = trim(preg_replace(FORBIDDEN_IN_URLS, '_', $link), ' _');

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
// User information
//

// the database of site members
if(!defined('NO_MODEL_PRELOAD'))
	include_once $context['path_to_root'].'users/users.php';

// load users parameters -- see users/configure.php
if(!defined('NO_MODEL_PRELOAD'))
	Safe::load('parameters/users.include.php');

// our knowledge about current surfer -- after the definition of path_to_root parameter, and after the loading of user parameters
include_once $context['path_to_root'].'shared/surfer.php';

//
// Switch management
//

// redirect if the server has been switched off and if not in the control panel, nor in the scripts or users modules
if(file_exists($context['path_to_root'].'parameters/switch.off') && !Surfer::is_associate() && !preg_match('/\/(control|included|scripts|users)\//i', $context['script_url']) && !preg_match('/\/configure\.php$/i', $context['script_url']))
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'control/closed.php');

//
// Content basic information -- articles and sections
//
if(!defined('NO_MODEL_PRELOAD'))
	include_once $context['path_to_root'].'articles/articles.php';
if(!defined('NO_MODEL_PRELOAD'))
	include_once $context['path_to_root'].'sections/sections.php';

//
// Skin and rendering -- see skins/index.php for more information
//

// load the layout interface, if we have access to some data
if(!defined('NO_MODEL_PRELOAD'))
	include_once $context['path_to_root'].'skins/layout.php';

// skin variant is asked for explicitly
if(isset($_REQUEST['variant']) && $_REQUEST['variant'])
	$context['skin_variant'] = basename(preg_replace(FORBIDDEN_IN_PATHS, '_', strip_tags($_REQUEST['variant'])));

// force the skin variant if polled by AvantGo
if(!isset($context['skin_variant']) && is_callable('getenv') && base64_decode(getenv("HTTP_X_AVANTGO_DEVICEOS"))) {
	$context['skin_variant'] = 'mobile';
	Logger::debug('polled by AvantGo');
}

// the layout for the home page is used at several places
if(!isset($context['root_articles_layout']) || !$context['root_articles_layout'])
	$context['root_articles_layout'] = 'daily';

/**
 * integrate yacs start of page
 *
 * This function may be used to integrate the YACS rendering engine into another software.
 * Look at [script]tools/embed.php[/script] for an example of how to use this function.
 */
function embed_yacs_prefix() {
	global $context;

	// limit the output to the prefix only
	$context['embedded'] = 'prefix';

	// load the skin
	load_skin();

	// render page header
	render_skin();

}

/**
 * integrate yacs end of page
 *
 * This function may be used to integrate the YACS rendering engine into another software.
 * Look at [script]tools/embed.php[/script] for an example of how to use this function.
 */
function embed_yacs_suffix() {
	global $context;

	// no content on HEAD request
	if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
		return;

	// limit the output to the suffix only
	$context['embedded'] = 'suffix';

	// reload a template for footer rendering
	if(isset($context['skin_variant']) && is_readable($context['path_to_root'].$context['skin'].'/template_'.$context['skin_variant'].'.php'))
		include $context['path_to_root'].$context['skin'].'/template_'.$context['skin_variant'].'.php';

	// else reuse the original template for footer rendering
	elseif(is_readable($context['path_to_root'].$context['skin'].'/template.php'))
		include $context['path_to_root'].$context['skin'].'/template.php';

}

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

	// profiling mode
	if($context['with_profile'] == 'Y')
		logger::profile('load_skin', 'start');

	// use a specific skin, if any
	if($options && preg_match('/\bskin_(.+?)\b/i', $options, $matches))
		$context['skin'] = 'skins/'.$matches[1];
	elseif(is_object($anchor) && ($skin = $anchor->has_option('skin', FALSE)) && is_string($skin))
		$context['skin'] = 'skins/'.$skin;

	// load skins parameters, if any
	Safe::load('parameters/skins.include.php');
	Safe::load('parameters/root.include.php'); // to support Page::tabs()

	// ensure tools are accessible
	if(strpos($context['skins_extra_components'], 'tools') === FALSE)
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

	// skin variant is already set -- maybe already set as 'mobile'
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

	if($context['with_profile'] == 'Y')
		logger::profile('skin::load', 'start');

	// initialize skin constants
	if(!defined('BR'))
		Skin::load();

	if($context['with_profile'] == 'Y')
		logger::profile('skin::load', 'stop');

	// profiling mode
	if($context['with_profile'] == 'Y')
		logger::profile('load_skin', 'stop');

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
 * This script does not set or modify other attributes in responses.
 * These attributes may be set directly in scripts according to following suggestions:
 *
 * [*] Internet Explorer may have strange behaviour with the [code]Expire[/code] attribute.
 * It does not take into account very short-term expiration date and does not validate after the deadline.
 * On the other hand, setting an expiration date is useful to fix the 'download a .zip file directly from the browser' bug.
 * We recommend to set this attribute in all scripts related to file transfers and download, and to not set it
 * at all in every other script.
 *
 * [*] The [code]Cache-Control[/code] attribute allows for cache-control.
 * It has been primarily designed for HTTP/1.1 agents, and few proxies seem to handle it correctly at the moment.
 * However to explicitly declare that the output of some script may be cached for three hours by intermediate proxies,
 * you can use [code]Safe::header("Cache-Control: public, max-age=10800");[/code].
 * On the other hand, if only the user-agent (i.e., the browser) is allowed to cache something,
 * you can use [code]Safe::header("Cache-Control: private, max-age=10800");[/code].
 *
 * [*] What to do with [code]Pragma:[/code]? Well, almost nothing; this is used only by some legacy browsers.
 * If you want an old browser to cache some object, use [code]Safe::header("Pragma:");[/code].
 *
 * Post-processing hooks are triggered after all HTML is returned to the browser,
 * including the poor-man's cron so the user who kicks off the cron jobs should not notice any delay.
 *
 * @param int the time() to be used as the Last-Modified date of the page, if any
 */
function render_skin($stamp=0) {
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

	// don't do this on second rendering phase
	if(!isset($context['embedded']) || ($context['embedded'] != 'suffix')) {
	
		// menu - common menu across pages
		if(file_exists($context['path_to_root'].'parameters/switch.on')) {
		
			// cache the site menu for performance
			$cache_id = 'shared/global.php#render_skin#menu';
			if((!$text =& Cache::get($cache_id)) && is_callable(array('Articles', 'get')) && is_callable(array('Codes', 'beautify'))) {
				if($item =& Articles::get('menu'))
					$text =& Skin::build_box(Codes::beautify_title($item['title']), Codes::beautify($item['description']), 'navigation', 'main_menu');
				Cache::put($cache_id, $text, 'articles');
			}
			$context['aside']['user'] = $text;
		}
				
		// navigation - navigation boxes
		if(file_exists($context['path_to_root'].'parameters/switch.on')) {
		
			// cache dynamic boxes for performance, and if the database can be accessed
			$cache_id = 'shared/global.php#render_skin#navigation';
			if((!$text =& Cache::get($cache_id)) && !defined('NO_MODEL_PRELOAD')) {
	
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
				include_once $context['path_to_root'].'categories/categories.php';
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
			$context['aside']['navigation'] = $text;
		}
			
		
		// tools - finalize page tools
		if(count($context['page_tools']) > 0)
			$context['aside']['tools'] = Skin::build_box(i18n::s('Tools'), Skin::finalize_list($context['page_tools'], 'tools'), 'extra', 'page_tools');

		// user - user menu
		if(is_callable(array('Users', 'get_url')) && ($menu = Skin::build_user_menu('basic')) && is_callable(array('i18n', 's'))) {
			if(Surfer::is_logged()) {
				$box_title = Surfer::get_name();
			} else {
				$box_title = i18n::s('User login');
			}
			$context['aside']['user'] = Skin::build_box($box_title, $menu, 'navigation', 'user_menu');
		}

		// visited - list pages visited previously at this site, if any
		if(isset($_SESSION['visited']) && count($_SESSION['visited']) && is_callable(array('i18n', 's'))) {
	
			// box title
			$title = i18n::s('Visited');
	
			// box content as a compact list
			$text =& Skin::build_list($_SESSION['visited'], 'compact');
	
			// the list of recent pages
			$context['aside']['visited'] = Skin::build_box($title, $text, 'navigation', 'visited_pages');
		}

		// finalize page context
		if(is_callable(array('Skin', 'finalize_context')))
			Skin::finalize_context();

		// list extra components --before navigation components
		$parameters = explode(' ', $context['skins_extra_components']);
			
		// populate the extra panel
		foreach($parameters as $parameter) {
			if(isset($context['aside'][ $parameter ]))
				$context['extra'] .= $context['aside'][ $parameter ];
		}
			
		// list navigation components
		$parameters = explode(' ', $context['skins_navigation_components']);
			
		// populate the navigation panel
		foreach($parameters as $parameter) {
		
			// part of context
			if(isset($context['aside'][ $parameter ]))
				$context['navigation'] .= $context['aside'][ $parameter ];
				
			// include the full extra panel
			elseif($parameter == 'extra')
				$context['navigation'] .= $context['extra'];
				
			// a named page, but only during regular operation
			elseif(file_exists($context['path_to_root'].'parameters/switch.on')) {
			
				// cache the item for performance
				$cache_id = 'shared/global.php#render_skin#'.$parameter;
				if((!$text =& Cache::get($cache_id)) && is_callable(array('Articles', 'get')) && is_callable(array('Codes', 'beautify'))) {
					if($item =& Articles::get($parameter))
						$text =& Skin::build_box(Codes::beautify_title($item['title']), Codes::beautify($item['description']), 'navigation', 'box_'.$parameter);
					Cache::put($cache_id, $text, 'articles');
				}
				$context['navigation'] .= $text;
				
			}
	
		}
			
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

	// profiling mode
	if($context['with_profile'] == 'Y')
		logger::profile('render_skin', 'start');

	// yes, we are proud of this piece of software
	Safe::header('X-Powered-By: YACS (http://www.yetanothercommunitysystem.com/)');

	// provide P3P compact policy, if any
	if(isset($context['p3p_compact_policy']))
		Safe::header('P3P: CP="'.$context['p3p_compact_policy'].'"');

	// inform proxies that we may serve several versions per reference
	Safe::header('Vary: Accept-Encoding, Cookie, ETag, If-None-Match, Set-Cookie');

	// handle web cache
	if(($stamp >= 0) && !(isset($context['without_http_cache']) && ($context['without_http_cache'] == 'Y')) && !headers_sent()) {

		// ask for revalidation in any case - 'no-cache' is mandatory for IE6 !!!
		Safe::header('Expires: Thu, 19 Nov 1981 08:52:00 GMT');
		Safe::header('Cache-Control: private, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
		Safe::header('Pragma:');

		// validate the content if hash is ok - content depends also on configuration files and on surfer capability
		if($context['text'] && !is_callable('send_body')) {

			// concatenate significant content
			$content = '';
			if(isset($context['debug']) && count($context['debug']))
				$content .= implode(':', $context['debug']).':';
			if(isset($context['error']) && count($context['error']))
				$content .= implode(':', $context['error']).':';
			if(isset($context['extra']))
				$content .= $context['extra'].':';
			if(isset($context['navigation']))
				$content .= $context['navigation'].':';
			if(isset($context['page_details']))
				$content .= $context['page_details'].':';
			if(isset($context['page_image']))
				$content .= $context['page_image'].':';
			if(isset($context['page_menu']))
				$content .= Skin::build_list($context['page_menu'], 'compact').':';
			if(isset($context['page_title']))
				$content .= $context['page_title'].':';
			if(isset($context['prefix']))
				$content .= $context['prefix'].':';
			if(isset($context['suffix']))
				$content .= $context['suffix'].':';
			if(isset($context['text']))
				$content .= $context['text'].':';
			if(isset($context['etag']))
				$content .= $context['etag'].':';
			$content .= Safe::filemtime($context['path_to_root'].'parameters/control.include.php').':'
				.Safe::filemtime($context['path_to_root'].'parameters/skins.include.php');

			// not the same content for editors
			if(is_callable(array('Surfer', 'get_capability')))
				$content .= ':'.Surfer::get_capability();

			// not the same content for associates
			if(is_callable(array('Surfer', 'is_associate')) && Surfer::is_associate())
				$content .= ':A';

			// hash content to create the etag string
			$etag = '"'.md5($content).'"';

			// always sent, even in case of 304, according to RFC2616
			Safe::header('ETag: '.$etag);
			if(isset($_SERVER['HTTP_IF_NONE_MATCH']) && is_array($if_none_match = explode(',', str_replace('\"', '"', $_SERVER['HTTP_IF_NONE_MATCH'])))) {
				foreach($if_none_match as $target) {
					if(trim($target) == $etag) {
						Safe::header('Status: 304 Not Modified', TRUE, 304);
						return;
					}
				}
			}
		}

		// validate the content if stamp is ok
		if($stamp > 1000000) {
			$last_modified = gmdate('D, d M Y H:i:s', $stamp).' GMT';
			Safe::header('Last-Modified: '.$last_modified);
			if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && ($if_modified_since = preg_replace('/;.*$/', '', $_SERVER['HTTP_IF_MODIFIED_SINCE']))) {
				if(($if_modified_since == $last_modified) && !isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
					Safe::header('Status: 304 Not Modified', TRUE, 304);
					return;
				}
			}
		}
	}

	// if it was a HEAD request, stop here
	if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD')) {
		// dump profile information, if any
		logger::profile_dump();

		return;
	}

	// normalize customized components of the head
	if(isset($context['site_head']))
		$context['page_header'] .= $context['site_head']."\n";

	// the title
	$page_title = ucfirst(strip_tags($context['page_title']));
	$context['page_header'] .= '<title>'.$page_title;
	if($context['site_name'] && !preg_match('/'.str_replace('/', ' ', strip_tags($context['site_name'])).'/', strip_tags($context['page_title']))) {
		if($page_title)
			$context['page_header'] .= ' - ';
		$context['page_header'] .= strip_tags($context['site_name']);
	}
	$context['page_header'] .= "</title>\n";

	// set icons for this site
	if($context['site_icon']) {
		$context['page_header'] .= '<link rel="icon" href="'.$context['url_to_root'].$context['site_icon'].'" type="image/x-icon" />'."\n"
			.'<link rel="shortcut icon" href="'.$context['url_to_root'].$context['site_icon'].'" type="image/x-icon" />'."\n";
	}

	// a meta-link to our help page
	$context['page_header'] .= '<link rel="help" href="'.$context['url_to_root'].'help/" type="text/html" />'."\n";

	// the description of this page
	if(isset($context['page_description']) && $context['page_description'])
		$context['page_header'] .= '<meta name="description" content="'.encode_field(strip_tags($context['page_description'])).'" />'."\n";
	elseif(isset($context['site_description']) && $context['site_description'])
		$context['page_header'] .= '<meta name="description" content="'.encode_field(strip_tags($context['site_description'])).'" />'."\n";

	// copyright
	if(isset($context['site_copyright']) && $context['site_copyright'])
		$context['page_header'] .= '<meta name="copyright" content="'.encode_field($context['site_copyright']).'" />'."\n";

	// author
	if(isset($context['page_author']) && $context['page_author'])
		$context['page_header'] .= '<meta name="author" content="'.encode_field($context['page_author']).'" />'."\n";

	// publisher
	if(isset($context['page_publisher']) && $context['page_publisher'])
		$context['page_header'] .= '<meta name="publisher" content="'.encode_field($context['page_publisher']).'" />'."\n";

	// the keywords to be used for this page
	if(isset($context['site_keywords']) && $context['site_keywords'])
		$context['page_header'] .= '<meta name="keywords" content="'.encode_field($context['site_keywords']).'" />'."\n";

	// revisit-after
	if(!isset($context['site_revisit_after']))
		;
	elseif($context['site_revisit_after'] == 1)
		$context['page_header'] .= '<meta name="revisit-after" content="1 day" />'."\n";
	elseif($context['site_revisit_after'])
		$context['page_header'] .= '<meta name="revisit-after" content="'.encode_field($context['site_revisit_after']).' days" />'."\n";

	// no Microsoft irruption in our pages
	$context['page_header'] .= '<meta name="MSSmartTagsPreventParsing" content="TRUE" />'."\n";

	// suppress awful hovering toolbar on images in IE
	$context['page_header'] .= '<meta http-equiv="imagetoolbar" content="no" />'."\n";

	// lead robots
	$context['page_header'] .= '<meta name="robots" content="index,follow" />'."\n";

	// help Javascript scripts to locate files --in header, because of potential use by in-the-middle javascript snippet
	$context['page_header'] .= '<script type="text/javascript">// <![CDATA['."\n"
		.'	var url_to_root = "'.$context['url_to_home'].$context['url_to_root'].'";'."\n"
		.'	var url_to_skin = "'.$context['url_to_home'].$context['url_to_root'].$context['skin'].'/"'."\n"
		.'// ]]></script>'."\n";

	// activate tinyMCE, if available -- before prototype and scriptaculous
	if(isset($context['javascript']['tinymce']) && file_exists($context['path_to_root'].'included/tiny_mce/tiny_mce.js')) {

		$context['page_header'] .= '<script type="text/javascript" src="'.$context['url_to_root'].'included/tiny_mce/tiny_mce.js"></script>'."\n"
			.'<script type="text/javascript">// <![CDATA['."\n"
			.'	tinyMCE.init({'."\n"
			.'		mode : "textareas",'."\n"
			.'		theme : "advanced",'."\n"
			.'		editor_selector : "tinymce",'."\n"
			.'		languages : "fr",'."\n"
			.'		disk_cache : false,'."\n"
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
			.'// ]]></script>'."\n";

	}

	// load a bunch of included scripts in one step, including prototype --we are doing that in the header, because of Event.observe(window, "load", ... in $context['text']
	$context['page_header'] .= '<script type="text/javascript" src="'.$context['url_to_root'].'included/browser/minify.php"></script>'."\n";

// 	// load the google library
// 	if(isset($context['google_api_key']) && $context['google_api_key'])
// 		$context['page_header'] .= '<script type="text/javascript" src="http://www.google.com/jsapi?key='.$context['google_api_key'].'"></script>'."\n";

	// activate AJAX client library
	if(file_exists($context['path_to_root'].'shared/yacs.js'))
		$context['page_header'] .= '<script type="text/javascript" src="'.$context['url_to_root'].'shared/yacs.js"></script>'."\n";

	// insert one tabulation before each header line
	$context['page_header'] = "\t".str_replace("\n", "\n\t", $context['page_header'])."\n";

	// site trailer, if any
	if(isset($context['site_trailer']) && $context['site_trailer'])
		$context['page_footer'] .= $context['site_trailer']."\n";

	// activate jsCalendar, if available
	if(isset($context['javascript']['calendar']) && (file_exists($context['path_to_root'].'included/jscalendar/calendar.js.jsmin') || file_exists($context['path_to_root'].'included/jscalendar/calendar.js'))) {

		// load the skin
		$context['page_header'] .= "\t".'<link rel="stylesheet" type="text/css" media="all" href="'.$context['url_to_root'].'included/jscalendar/skins/aqua/theme.css" title="jsCalendar - Aqua" />'."\n";
		$context['page_header'] .= "\t".'<link rel="alternate stylesheet" type="text/css" media="all" href="'.$context['url_to_root'].'included/jscalendar/calendar-system.css" title="jsCalendar - system" />'."\n";

		// load the scripts
		$context['page_footer'] .= '<script type="text/javascript" src="'.$context['url_to_root'].'included/jscalendar/minify.php"></script>'."\n";

	}

	// activate urchin at google analytics, if configured
	if(isset($context['google_urchin_account']) && $context['google_urchin_account']) {

		$context['page_footer'] .= '<script type="text/javascript" src="http://www.google-analytics.com/urchin.js"></script>'."\n"
			.'<script type="text/javascript">//<![CDATA['."\n"
			.'_uacct = "'.$context['google_urchin_account'].'";'."\n"
			.'if(typeof urchinTracker != "undefined") { urchinTracker(); };'."\n"
			.'// ]]></script>'."\n";

	}

	// handle the output correctly
	Safe::ob_start('yacs_handler');

	// return the standard MIME type, but ensure the user agent will accept it -- Internet Explorer 6 don't
//		if(isset($_SERVER['HTTP_ACCEPT']) && stristr($_SERVER['HTTP_ACCEPT'], 'application/xhtml+xml'))
//			$context['content_type'] = 'application/xhtml+xml';
//	else
		$context['content_type'] = 'text/html';
	Safe::header('Content-Type: '.$context['content_type'].'; charset='.$context['charset']);

// load the page library
	include_once $context['path_to_root'].'skins/page.php';

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

	// profiling mode
	if($context['with_profile'] == 'Y')
		logger::profile('render_skin', 'stop');

	// dump profile information, if any
	Logger::profile_dump();

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
		Logger::remember('cron.php', 'tick', '', 'debug');

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

	// dump profile information, if any
	Logger::profile_dump();

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


//
// Anchor stuff -- see shared/anchor.php for more information
//

// load the anchor interface
if(!defined('NO_MODEL_PRELOAD'))
	include_once $context['path_to_root'].'shared/anchor.php';

// global functions related to anchors
if(!defined('NO_MODEL_PRELOAD'))
	include_once $context['path_to_root'].'shared/anchors.php';

/**
 * get the label for an action
 *
 * Following actions codes have been defined:
 * - 'article:create'
 * - 'article:update'
 * - 'article:publish'
 * - 'article:review'
 * - 'section:create'
 * - 'section:update'
 * - 'comment:create'
 * - 'comment:update'
 * - 'file:create'
 * - 'file:update'
 * - 'link:create'
 * - 'link:update'
 * - 'link:stamp'
 * - 'image:create'
 * - 'image:update'
 * - 'image:set_as_icon'
 * - 'location:create'
 * - 'location:update'
 * - 'table:create'
 * - 'table:update'
 * - 'user:create'
 * - 'user:update'
 *
 * @param string the action code example: 'article:publish', 'image:create'
 * @return a string
 * @see shared/anchor.php#touch
 */
function get_action_label($action) {

	if(preg_match('/.*:import/i', $action))
		return i18n::s('imported');

	switch($action) {
	case 'article:create':
		return i18n::s('created');

	case 'article:update':
		return i18n::s('edited');

	case 'article:publish':
		return i18n::s('published');

	case 'article:review':
		return i18n::s('reviewed');

	case 'section:create':
		return i18n::s('section created');

	case 'section:update':
		return i18n::s('section updated');

	case 'comment:create':
		return i18n::s('commented');

	case 'comment:update':
		return i18n::s('edited');

	case 'file:create':
		return i18n::s('file uploaded');

	case 'file:update':
		return i18n::s('file updated');

	case 'image:create':
		return i18n::s('image uploaded');

	case 'image:update':
		return i18n::s('image updated');

	case 'image:set_as_icon':
		return i18n::s('icon set');

	case 'link:create':
	case 'link:feed':
		return i18n::s('link posted');

	case 'link:update':
		return i18n::s('link updated');

	case 'link:stamp':
		return i18n::s('page updated');

	case 'location:create':
		return i18n::s('location created');

	case 'location:update':
		return i18n::s('location updated');

	case 'table:create':
		return i18n::s('table posted');

	case 'table:update':
		return i18n::s('table updated');

	case 'thread:update':
		return i18n::s('new message');

	case 'user:create':
		return i18n::s('new user');

	case 'user:update':
		return i18n::s('profile updated');

	default:
		return i18n::s('edited');
	}
}

// the library for membership
if(!defined('NO_MODEL_PRELOAD'))
	include_once $context['path_to_root'].'shared/members.php';

// load parameters for web services -- including debugging
if(!defined('NO_CONTROLLER_PRELOAD'))
	Safe::load('parameters/services.include.php');

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
		$name = str_replace(array(' ', '..', ',', ';', ':', '!', '?', '<', '>', '/', '_'), '-', strtolower(utf8::to_ascii(trim($name))));

	// do not fool rewriting, just in case alternate name would be put in title
	if(isset($name))
		$name = preg_replace('/[a-z_]+-[0-9]+$/', '', $name);

	// remove dashes
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

// profiling mode
if($context['with_profile'] == 'Y')
	logger::profile('global', 'stop');

?>