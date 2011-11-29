<?php
/**
 * catch an unexpected error
 *
 * To use this script under Apache, create the following [code].htaccess[/code] file at your root directory:
 * [snippet]
 * #redirect to pretty error pages
 * ErrorDocument 401 /yacs/error.php/401
 * ErrorDocument 403 /yacs/error.php/403
 * ErrorDocument 404 /yacs/error.php/404
 * [/snippet]
 *
 * In order to avoid tracking of 404 codes due to web spiders browsing our scripts,
 * we don't log these errors if the referer URL matches [code]/scripts.view.php[/code].
 *
 * @author Bernard Paques
 * @tester Manuel Lopez Gallego
 * @tester NickR
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once 'shared/global.php';

// find the error code
if(!isset($error))
	$error = '';
if(isset($_REQUEST['error']))
	$error = $_REQUEST['error'];
elseif(isset($context['arguments'][0]))
	$error = $context['arguments'][0];
$error = strip_tags($error);

// protect from hackers
$error = htmlspecialchars($error);
if(isset($_SERVER['REQUEST_URI']))
	$_SERVER['REQUEST_URI'] = htmlspecialchars($_SERVER['REQUEST_URI']);
if(isset($_SERVER['HTTP_REFERER']))
	$_SERVER['HTTP_REFERER'] = htmlspecialchars($_SERVER['HTTP_REFERER']);
if(isset($_SERVER['HTTP_USER_AGENT']))
	$_SERVER['HTTP_USER_AGENT'] = htmlspecialchars($_SERVER['HTTP_USER_AGENT']);
if(isset($_SERVER['REMOTE_ADDR']))
	$_SERVER['REMOTE_ADDR'] = htmlspecialchars($_SERVER['REMOTE_ADDR']);

// make it short if the caller does not expect some text (for example, waiting for some image)
if($error && isset($_SERVER['HTTP_ACCEPT']) && !preg_match('/^text\//i', $_SERVER['HTTP_ACCEPT'])) {
	echo $error.' Impossible to fulfill your request';
	return;
}

// requests to favicon.ico may accept text responses
if(isset($_SERVER['REQUEST_URI']) && preg_match('/favicon\.ico/i', $_SERVER['REQUEST_URI'])) {
	echo $error.' No FAVICON.ICO is available';
	return;
}

// load localized strings
i18n::bind('root');

// load the skin
load_skin('error');

// process the error accroding to its code
switch ($error) {

// 401 - Unauthorized
case '401':

	// page title
	$context['page_title'] = i18n::s('You are not authorized to access this page (401)');

	// page content
	$context['text'] .= sprintf(i18n::s('We\'re sorry. You are not authorized to view %s'), Skin::build_block($_SERVER['REQUEST_URI'], 'code'));

	// remember the error
	if(class_exists('Logger')) {
		$event_label = sprintf(i18n::c('Unauthorized (401) at %s'), $_SERVER['REQUEST_URI']);
		$event_description = '';
		if(isset($_SERVER['HTTP_REFERER']))
			$event_description .= '$_SERVER[\'HTTP_REFERER\']='.$_SERVER['HTTP_REFERER']."\n";
		if(isset($_SERVER['HTTP_USER_AGENT']))
			$event_description .= '$_SERVER[\'HTTP_USER_AGENT\']='.$_SERVER['HTTP_USER_AGENT']."\n";
		if(isset($_SERVER['REMOTE_ADDR']))
			$event_description .= '$_SERVER[\'REMOTE_ADDR\']='.$_SERVER['REMOTE_ADDR']."\n";
		Logger::remember('error.php', $event_label, $event_description);
	}

	break;

// 403 - Forbidden
case '403':

	// page title
	$context['page_title'] = i18n::s('Access to this page is forbidden (403)');

	// page content
	$context['text'] .= sprintf(i18n::s('We\'re sorry. You are not authorized to view %s'), Skin::build_block($_SERVER['REQUEST_URI'], 'code'));

	// remember the error
	if(class_exists('Logger')) {
		$event_label = sprintf(i18n::c('Forbidden (403) at %s'), $_SERVER['REQUEST_URI']);
		$event_description = '';
		if(isset($_SERVER['HTTP_REFERER']))
			$event_description .= '$_SERVER[\'HTTP_REFERER\']='.$_SERVER['HTTP_REFERER']."\n";
		if(isset($_SERVER['HTTP_USER_AGENT']))
			$event_description .= '$_SERVER[\'HTTP_USER_AGENT\']='.$_SERVER['HTTP_USER_AGENT']."\n";
		if(isset($_SERVER['REMOTE_ADDR']))
			$event_description .= '$_SERVER[\'REMOTE_ADDR\']='.$_SERVER['REMOTE_ADDR']."\n";
		Logger::remember('error.php', $event_label, $event_description);
	}

	break;

// 404 - Not Found
case '404':
default:

	// the title of the page
	$context['page_title'] = i18n::s('Page not found (404)');

	// the content of the page
	$context['text'] .= sprintf(i18n::s('We\'re sorry. The page you requested doesn\'t exist on this server %s'), Skin::build_block($_SERVER['REQUEST_URI'], 'code'));

	// check typos
	$context['text'] .= Skin::build_box(i18n::s('Check the address'), i18n::s('Normally we are not using upper case letters, and no spacing sign.'));

	// update bookmarks
	$context['text'] .= Skin::build_box(i18n::s('Update your bookmark'),
		sprintf(i18n::s('It is likely that we have changed the content of this site without warning you. Thank you for browsing %s and to refresh your bookmark.'), Skin::build_link($context['url_to_root'], i18n::s('the site front page'), 'shortcut')));

	// search the site
	$context['text'] .= Skin::build_box(i18n::s('Search'),
		sprintf(i18n::s('Type one or several words in %s.'), Skin::build_link('search.php', i18n::s('the searching form'), 'shortcut')));

	// browse recent posts
	$context['text'] .= Skin::build_box(i18n::s('Browse index pages'),
		sprintf(i18n::s('Index of %1$s, %2$s, %3$s and %4$s will show you instantaneously the freshest pages and the most read pages on this site. This can be an efficient way for you to reach the information you are looking after.'), Skin::build_link('articles/', i18n::s('pages'), 'shortcut'), Skin::build_link('files/', i18n::s('files'), 'shortcut'), Skin::build_link('comments/', i18n::s('comments'), 'shortcut'), Skin::build_link('users/', i18n::s('people'), 'shortcut')));

	// only in debug mode
	if($context['with_debug'] != 'Y')
		;

	// remember the error
	elseif(class_exists('Logger') && isset($_SERVER['HTTP_REFERER'])) {
		$event_label = sprintf(i18n::c('Page not found (404) at %s'), $_SERVER['REQUEST_URI']);
		$event_description = '';
		if(isset($_SERVER['HTTP_REFERER']))
			$event_description .= '$_SERVER[\'HTTP_REFERER\']='.$_SERVER['HTTP_REFERER']."\n";
		if(isset($_SERVER['HTTP_USER_AGENT']))
			$event_description .= '$_SERVER[\'HTTP_USER_AGENT\']='.$_SERVER['HTTP_USER_AGENT']."\n";
		if(isset($_SERVER['REMOTE_ADDR']))
			$event_description .= '$_SERVER[\'REMOTE_ADDR\']='.$_SERVER['REMOTE_ADDR']."\n";
		Logger::remember('error.php', $event_label, $event_description);
	}

	break;

}

// render the skin
render_skin();

// do not return to caller, if any
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
	exit;

?>
