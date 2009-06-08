<?php
/**
 * on-line bookmarklet for Internet Explorer under Windows
 *
 * This script prepares two static files aiming to install and to support a contextual bookmarklet
 * (i.e., accessible from a right-click) in Internet Explorer.
 *
 * To install the bookmarklet you will have to call this script remotely from within Internet Explorer.
 * Accept any alert from Windows related to the modification of the registry.
 * Then close and restart Internet Explorer and enjoy the new contextual menu.
 *
 * To use the bookmarklet from within Internet Explorer select some text, then press the right button of the mouse.
 * In the pop-up menu click on the command to blog to your site.
 * The edit form will appear on your screen.
 *
 * @see articles/edit.php
 *
 * The contextual bookmark has following components:
 * - some javascript code to submit selected data to the server
 * - configuration data for the contextual menu
 *
 * Both components are prepared as static files by this script in the [code]articles[/code] directory.
 * If you change server parameters such as the server title, delete both components and relaunch this script.
 *
 * The first file, [code]temporary/ie_bookmarklet.reg[/code], is a downloadable update to the Windows registry.
 * This update adds an item to right-click menus when some text has been selected.
 *
 * The second file, [code]temporary/ie_bookmarklet.html[/code], is a Javascript which implements the bookmarklet itself.
 * This script packages context data and submit them to the YACS on-line form.
 * It is invoked remotely from the contextual menu of Internet Explorer.
 *
 * If you want to suppress the contextual menu from your workstation,
 * launch [code]regedit[/code], go to the key [code]HKEY_CURRENT_USER\Software\Microsoft\Internet Explorer\MenuExt[/code],
 * and delete relevant entries.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 *
 * @link http://www.siteexperts.com/tips/hj/ts01/index.asp Customizing IE Context Menus
 */

// the main library
include_once '../shared/global.php';

//
// create the javascript that support bookmarklets
//

// the static javascript bookmarklet file
$html_name = 'temporary/ie_bookmarklet.html';

// create the static javascript bookmarklet if it does not exist, or if it is older than this script
if(!file_exists($context['path_to_root'].$html_name)
	|| (Safe::filemtime($context['path_to_root'].'articles/ie_bookmarklet.php') > Safe::filemtime($context['path_to_root'].$html_name))) {

	$content = '<html>'."\n"
		.JS_PREFIX."\n"
		.'// ensure we can rely on an adequate object model'."\n"
		.'if(typeof(external) == "undefined") {'."\n"
		."\n"
		.'	alert("This script is dedicated to Internet Explorer -- Sorry");'."\n"
		."\n"
		.'} else {'."\n"
		."\n"
		.'	// ensure there is a parent window'."\n"
		.'	if(typeof(external.menuArguments) == "undefined") {'."\n"
		."\n"
		.'		alert("Use this script from the context menu -- Sorry");'."\n"
		."\n"
		.'	} else {'."\n"
		."\n"
		.'		// page title'."\n"
		.'		var sTitle = external.menuArguments.document.title;'."\n"
		."\n"
		.'		// page address'."\n"
		.'		var sSource = external.menuArguments.location.href;'."\n"
		."\n"
		.'		// fetch the selection'."\n"
		.'		var sSelection = external.menuArguments.document.selection.createRange().text;'."\n"
		."\n"
		.'		// some text has been selected'."\n"
		.'		if(sSelection.length > 0) {'."\n"
		."\n"
		.'			// load the edit form from the server and populate it'."\n"
		.'			var sLocation = \''.$context['url_to_home'].$context['url_to_root']."articles/edit.php?"
						."title='+escape(sTitle)+'"
						."&text='+escape('[quote]'+sSelection+'[nl]-- [link='+sTitle+']'+sSource+'[/link][/quote]')+'"
						."&source='+escape(sSource);\n"
		."\n"
		.'		// use only the title and url'."\n"
		.'		} else {'."\n"
		."\n"
		.'			// load the edit form from the server and populate it'."\n"
		.'			var sLocation = \''.$context['url_to_home'].$context['url_to_root']."articles/edit.php?"
						."title='+escape(sTitle)+'"
						."&text='+escape('[link='+sTitle+']'+sSource+'[/link]')+'"
						."&source='+escape(sSource);\n"
		."\n"
		.'		}'."\n"
		."\n"
		.'		// we overlay the parent window to avoid pop ups'."\n"
		.'		external.menuArguments.location.href = sLocation;'."\n"
		."\n"
		.'	}'."\n"
		."\n"
		.'}'."\n"
		.JS_SUFFIX."\n"
		.'</html>'."\n";

	// save into the file system
	Safe::file_put_contents($html_name, $content);

}


//
// create registration entities to install the bookmarklet
//

// the static registration entries file
$reg_name = 'temporary/ie_bookmarklet.reg';

// create the registry update if it does not exist, or if it is older than this script or than the skin configuration file
if(!file_exists($context['path_to_root'].$reg_name)
	|| (file_exists($context['path_to_root'].'parameters/skins.include.php')
		&& (Safe::filemtime($context['path_to_root'].'parameters/skins.include.php') > Safe::filemtime($context['path_to_root'].$reg_name)))
	|| (Safe::filemtime($context['path_to_root'].'articles/ie_bookmarklet.php') > Safe::filemtime($context['path_to_root'].$reg_name)) ) {

	// get site name
	Safe::load('parameters/skins.include.php');

	// appears only on text selection (0x10)
	$content = 'REGEDIT4'."\n"
		."\n"
		.'[HKEY_CURRENT_USER\Software\Microsoft\Internet Explorer\MenuExt]'."\n"
		.'[HKEY_CURRENT_USER\Software\Microsoft\Internet Explorer\MenuExt\\'.sprintf(i18n::s('Post to %s'), utf8::to_ascii($context['site_name'], ' ')).']'."\n"
		.'@="'.$context['url_to_home'].$context['url_to_root'].$html_name.'"'."\n"
		.'"contexts"=hex:10'."\n";

	// save into the file system
	Safe::file_put_contents($reg_name, $content);

}

//
// start the installation
//

// if it was a HEAD request, stop here
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
	return;

// load the registry update
Safe::redirect($context['url_to_home'].$context['url_to_root'].$reg_name);

?>