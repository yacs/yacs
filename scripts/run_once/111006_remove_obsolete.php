<?php
/**
 * remove old files from 10.4.6
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Remove obsoleted files';
$local['label_fr'] = 'Suppression des fichiers inutiles';
echo get_local('label')."<br />\n";
$count = 0;


// files to delete, from root path
$files = array();
$files[] = 'control/htaccess/basic/.htaccess.bak';
$files[] = 'control/htaccess/indexes/.htaccess.bak';
$files[] = 'control/htaccess/options/.htaccess.bak';
$files[] = 'included/browser/document_write.js.jsmin';
$files[] = 'included/browser/library_js_endpage.jsmin.js';
$files[] = 'included/browser/library_js_header.jsmin.js';
$files[] = 'included/browser/slider.js.jsmin';
$files[] = 'overlays/issues/percent-0.png';
$files[] = 'overlays/issues/percent-10.png';
$files[] = 'overlays/issues/percent-100.png';
$files[] = 'overlays/issues/percent-20.png';
$files[] = 'overlays/issues/percent-30.png';
$files[] = 'overlays/issues/percent-40.png';
$files[] = 'overlays/issues/percent-50.png';
$files[] = 'overlays/issues/percent-60.png';
$files[] = 'overlays/issues/percent-70.png';
$files[] = 'overlays/issues/percent-80.png';
$files[] = 'overlays/issues/percent-90.png';
$files[] = 'skins/_reference/files/security_icon.gif';
$files[] = 'skins/_reference/files/sound_icon.gif';
$files[] = 'skins/_reference/files_inline/tex.gif';
$files[] = 'skins/_reference/layouts/gadget_tab.gif';
$files[] = 'skins/_reference/links/menu_bar.png';
$files[] = 'skins/_reference/links/next.png';
$files[] = 'skins/_reference/links/previous.png';
$files[] = 'skins/_reference/user-agents/aix.gif';
$files[] = 'skins/_reference/user-agents/altavista.gif';
$files[] = 'skins/_reference/user-agents/be.gif';
$files[] = 'skins/_reference/user-agents/bsd.gif';
$files[] = 'skins/_reference/user-agents/explorer.gif';
$files[] = 'skins/_reference/user-agents/irix.gif';
$files[] = 'skins/_reference/user-agents/konqueror.gif';
$files[] = 'skins/_reference/user-agents/linux.gif';
$files[] = 'skins/_reference/user-agents/lynx.gif';
$files[] = 'skins/_reference/user-agents/mac.gif';
$files[] = 'skins/_reference/user-agents/mozilla.gif';
$files[] = 'skins/_reference/user-agents/netscape.gif';
$files[] = 'skins/_reference/user-agents/opera.gif';
$files[] = 'skins/_reference/user-agents/os2.gif';
$files[] = 'skins/_reference/user-agents/question.gif';
$files[] = 'skins/_reference/user-agents/sun.gif';
$files[] = 'skins/_reference/user-agents/webtv.gif';
$files[] = 'skins/_reference/user-agents/windows.gif';
$files[] = 'skins/flexible/boxes/p-corner-topleft-x.png';
$files[] = 'skins/flexible/boxes/p-gh-bloc-news-l.jpg';
$files[] = 'skins/flexible/headers/p-whiteblack-x.gif';
$files[] = 'skins/shared/yacs.js';
$files[] = 'tools/mebeam.php';

// process every file
foreach($files as $file) {

	// file does not exist
	if(!file_exists($context['path_to_root'].$file))
		continue;

	// remove it
	if(Safe::unlink($context['path_to_root'].$file)) {
		$local['error_en'] = $file.' has been removed';
		$local['error_fr'] = $file.' a &eacute;t&eacute; supprim&eacute;';
		echo get_local('error')."<br />\n";
	}

	// next one
	$count += 1;
	Safe::set_time_limit(30);
}

// basic reporting
$local['label_en'] = 'files have been processed';
$local['label_fr'] = 'fichiers ont &eacute;t&eacute; trait&eacute;s';
echo $count.' '.get_local('label')."<br />\n";
?>