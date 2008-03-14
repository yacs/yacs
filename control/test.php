<?php
/**
 * display system information
 *
 * This script is aiming to troubleshoot particular situations.
 *
 * First of all, several options are offered to call it, including a bar GET, a more complex GET
 * with parameters, and also a POST.
 *
 * After that, several blocks of information are displayed:
 *
 * [*] Data sent by the user agent -- the content of [code]$_REQUEST[/code]
 *
 * [*] Arguments passed in the URL -- YACS decodes the query string and puts everything into [code]$context['arguments'][/code]
 *
 * [*] Cookies -- the content of [code]$_COOKIE[/code]
 *
 * [*] Session data -- the content of [code]$_SESSION[/code]
 *
 * [*] Session storage test -- a counter incremented at each page visit
 *
 * [*] Some YACS global variables -- including [code]$context['host_name'][/code],
 * [code]$context['url_to_home'][/code], [code]$context['url_to_root'][/code],
 * [code]$context['script_url'][/code], [code]$context['path_to_root'][/code],
 * and [code]$context['charset'][/code].
 *
 * [*] YACS version -- the content of [code]footprints.php[/code]
 *
 * [*] Run-time information (to associates only) -- the result of [code]getcwd()[/code],
 * of [code]php_sapi_name()[/code]
 *
 * [*] Server attributes -- the content of [code]$_SERVER[/code]; some attributes are masked to non-associates,
 * for example: [code]$_SERVER['COMSPEC'][/code], [code]$_SERVER['DOCUMENT_ROOT'][/code],
 * [code]$_SERVER['PATH'][/code], [code]$_SERVER['SCRIPT_FILENAME'][/code],
 * [code]$_SERVER['SystemRoot'][/code], [code]$_SERVER['WINDIR'][/code].
 *
 * [*] Environment attributes (to associates only) -- the content of [code]$_ENV[/code]
 *
 * [*] Time offset, as expressed by the browser, if any -- based on Javascript, and by the server -- based on PHP
 *
 * @link http://www.olate.com/articles/254 Use PHP and JavaScript to Display Local Time
 *
 * [*] The output handlers, if any
 *
 * If the file [code]demo.flag[/code] exists, the script assumes that this instance
 * of YACS runs in demonstration mode, and does not provide the content
 * of [code]$_SERVER[/code] nor of [code]$_ENV[/code].
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @tester Geoffroy Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// include global declarations
include_once '../shared/global.php';

// if it was a HEAD request, stop here
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
	return;

// load localized strings
i18n::bind('control');

// no skin for this page
define('BR', '<br>');

// the path to this page
echo '<p><a href="'.$context['url_to_root'].'control/">'.i18n::s('Control Panel')."</a></p>\n";

// the title of the page
echo '<h1>'.i18n::s('The test page')."</h1>\n";

// stop crawlers here
if(Surfer::is_crawler())
	return;

// native calls for this script
echo '<p>'.i18n::s('A more complex GET test').' <a href="'.$context['url_to_root'].'control/test.php/123/456/789?a=B">test.php/123/456/789?a=B</a></p>'."\n";

echo '<form action="'.$context['url_to_root'].'control/test.php/123/456/789?a=B" method="post"><div>'
	.'<button type="submit"><span>'.i18n::s('A complex POST test').'</span></button>'
	.'<input type="hidden" name="hello" value="world">'
	.'</div></form>'."\n";

// rewritten call for this script
echo '<form action="'.$context['script_url'].'" method="post"><div>'
	.'<button type="submit"><span>'.i18n::s('A self-referencing POST test').'</span></button>'
	.'<input type="hidden" name="hello" value="world">'
	.'</div></form>'."\n";

// reflect data sent by the user agent
if(@count($_REQUEST)) {
	echo '<p>'.i18n::s('Submitted request:').BR."\n";
	foreach($_REQUEST as $name => $value)
		echo '$_REQUEST[\''.strip_tags($name).'\']='.strip_tags($value).BR."\n";
	echo "</p>\n";
}

// args passed in the URL
if(@count($context['arguments'])) {
	echo '<p>'.i18n::s('Script args:').BR."\n";
	for($index = 0; $index < count($context['arguments']); $index++)
		echo '$context[\'arguments\']['.$index.']='.strip_tags($context['arguments'][$index]).BR."\n";
	echo "</p>\n";
}

// cookies
if(@count($_COOKIE)) {
	echo '<p>'.i18n::s('Cookies sent by the browser:').BR."\n";
	foreach($_COOKIE as $name => $value)
		echo '$_COOKIE[\''.strip_tags($name).'\']='.strip_tags($value).BR."\n";
	echo "</p>\n";
}

// session data -- this is safe, we are only reflecting data for this surfer
if(@count($_SESSION)) {
	echo '<p>'.i18n::s('Session data:').BR."\n";
	foreach($_SESSION as $name => $value)
		echo '$_SESSION[\''.$name.'\']='.$value.BR."\n";
	echo "</p>\n";
}

// session test
if(isset($_SESSION['test_hits']))
	$_SESSION['test_hits'] += 1;
else
	$_SESSION['test_hits'] = 1;
echo '<p>'.sprintf(i18n::s('Session variables are stored correctly if the counter increments on page reload: %s'), $_SESSION['test_hits']).'</p>'."\n";

// yacs version
if(!isset($generation['version']))
	Safe::load('scripts/reference/footprints.php'); 	// on-going development
if(!isset($generation['version']))
	Safe::load('scripts/staging/footprints.php');		// last update
if(!isset($generation['version']))
	Safe::load('footprints.php');						// initial archive
if(isset($generation['version'])) {
	echo '<p>'.sprintf(i18n::s('YACS version %s'), $generation['version'].', '.$generation['date'].', '.$generation['server'])."</p>\n";
} else {
	echo '<p>'.sprintf(i18n::s('YACS version %s'), '< 6.3')."</p>\n";
}

// YACS variables
echo '<p>'.i18n::s('Global YACS variables:').BR."\n"
	.'$context[\'language\']='.$context['language'].BR."\n"
	.'$context[\'host_name\']='.$context['host_name'].BR."\n"
	.'$context[\'url_to_home\']='.$context['url_to_home'].BR."\n"
	.'$context[\'url_to_root\']='.$context['url_to_root'].BR."\n"
	.'$context[\'script_url\']='.$context['script_url'].BR."\n"
	.'$context[\'self_url\']='.$context['self_url'].BR."\n"
	.'$context[\'self_script\']='.$context['self_script'].BR."\n";
if(Surfer::is_associate()) {
	echo '$context[\'path_to_root\']='.$context['path_to_root'].BR."\n";
	echo '$context[\'directory_mask\']='.sprintf('0%o', $context['directory_mask']).BR."\n";
	echo '$context[\'file_mask\']='.sprintf('0%o', $context['file_mask']).BR."\n";
}
echo '$context[\'charset\']='.$context['charset'].BR."\n";

// run-time information
if(Surfer::is_associate()) {
	echo '<p>';

	// current directory
	if(is_callable('getcwd'))
		echo 'getcwd()='.getcwd().BR."\n";

	// PHP SAPI name
	if(is_callable('php_sapi_name'))
		echo 'php_sapi_name()='.php_sapi_name().BR."\n";

	echo "</p>\n";
}

// server attributes -- not in demonstration mode
if(@count($_SERVER) && !file_exists($context['path_to_root'].'parameters/demo.flag')) {
	echo '<p>'.i18n::s('Server attributes:').BR."\n";
	foreach($_SERVER as $name => $value) {
		if(!Surfer::is_associate() && !preg_match('/^(HTTP_|PATH_INFO|QUERY_STRING|REMOTE_|REQUEST_|SERVER_|STATUS)/', $name))
			continue;
		echo '$_SERVER[\''.$name.'\']='.$value.BR."\n";
	}
	echo "</p>\n";
}

// environment dump for associates -- not in demonstration mode
if(@count($_ENV) && Surfer::is_associate() && !file_exists($context['path_to_root'].'parameters/demo.flag')) {
	echo '<p>'.i18n::s('System environment:').BR."\n";
	foreach($_ENV as $name => $value)
		echo '$_ENV[\''.$name.'\']='.$value.BR."\n";
	echo "</p>\n";
}

// display workstation time offset
echo '<script type="text/javascript">// <![CDATA['."\n"
	.'now = new Date();'."\n"
	.'offset = (-now.getTimezoneOffset() / 60);'."\n"
	.'document.write("<p>'.i18n::s('Browser GMT offset:').' UTC " + ((offset > 0) ? "+" : "") + offset + " '.i18n::s('hour(s)').'</p>");'."\n"
	.'// ]]></script>'."\n";

// display server time offset
$offset = intval((strtotime(date('M d Y H:i:s')) - strtotime(gmdate('M d Y H:i:s'))) / 3600);
echo '<p>'.i18n::s('Server GMT offset:').' UTC '.(($offset > 0) ? "+" : "").$offset.' '.i18n::s('hour(s)').' ('.date('Y-M-d H:i:s').")</p>\n";

// list output handlers
if(!is_callable('ob_list_handlers')) {
	echo '<p>'.i18n::s('ob_list_handlers() cannot be invoked')."</p>\n";
} else {
	$handlers = ob_list_handlers();
	if(count($handlers)) {
		echo '<p>'.i18n::s('Output handlers:').BR."\n";
		foreach($handlers as $name => $value)
			echo '[\''.$name.'\']='.$value.BR."\n";
		echo "</p>\n";
	}
}

// user/group of this script
if(is_callable('getmyuid') && (($uid = getmyuid()) !== FALSE) && is_callable('getmygid') && (($gid = getmygid()) !== FALSE)) {

	// describe user
	$ulabel = $uid;
	if(is_callable('posix_getpwuid') && (($uinfo = posix_getpwuid($uid)) !== FALSE)) {
		if(isset($uinfo['name']))
			$ulabel = $uinfo['name'].'['.$uid.']';
	}

	// describe group and members
	$glabel = $gid;
	if(is_callable('posix_getgrgid') && (($ginfo = posix_getgrgid($gid)) !== FALSE)) {

		// group name
		if(isset($ginfo['name']))
			$glabel = $ginfo['name'].'['.$gid.']';

		// group members
		if(isset($ginfo['members']) && is_array($ginfo['members'])) {
			$gmembers = array();
			foreach($ginfo['members'] as $index => $label)
				$gmembers[] = $label;
			if(count($gmembers))
				$glabel .= ' ('.implode(', ', $gmembers).')';
		}
	}

	// display gathered information
	echo '<p>'.i18n::s('user/group of this script:').' '.$ulabel.'/'.$glabel."</p>\n";

} else
	echo '<p>'.i18n::s('Impossible to retrieve user/group of this script.')."</p>\n";

// user/group of this process
if(is_callable('posix_geteuid') && (($uid = posix_geteuid()) !== FALSE) && is_callable('posix_getgroups') && (($gids = posix_getgroups()) !== FALSE)) {

	// describe user
	$ulabel = $uid;
	if(is_callable('posix_getpwuid') && (($uinfo = posix_getpwuid($uid)) !== FALSE)) {
		if(isset($uinfo['name']))
			$ulabel = $uinfo['name'].'['.$uid.']';
	}

	// describe groups
	$glabel = '';
	foreach($gids as $gid) {

		// group name
		if(is_callable('posix_getgrgid') && (($ginfo = posix_getgrgid($gid)) !== FALSE) && isset($ginfo['name']))
			$glabel .= $ginfo['name'].'['.$gid.']';

		else
			$glabel .= $gid;

		// next one
		$glabel .= ' ';

	}

	// display gathered information
	echo '<p>'.i18n::s('user/group of this process:').' '.$ulabel.'/'.$glabel."</p>\n";

} else {
	echo '<p>'.i18n::s('Impossible to retrieve user/group of this process.')."</p>\n";
}

?>