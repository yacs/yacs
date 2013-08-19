<?php
/**
 * remove old files
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
$files[] = 'articles/import.php';
$files[] = 'behaviors/agreements/index.php';
$files[] = 'comments/layout_comments_as_boxesandarrows.php';
$files[] = 'comments/layout_comments_as_daily.php';
$files[] = 'comments/layout_comments_as_jive.php';
$files[] = 'comments/layout_comments_as_manual.php';
$files[] = 'comments/layout_comments_as_wall.php';
$files[] = 'comments/layout_comments_as_wiki.php';
$files[] = 'comments/layout_comments_as_yabb.php';
$files[] = 'control/htaccess/basic/.htaccess.bak';
$files[] = 'control/htaccess/indexes/.htaccess.bak';
$files[] = 'control/htaccess/options/.htaccess.bak';
$files[] = 'feeds/flash/index.php';
$files[] = 'files/files_hook.php';
$files[] = 'i18n/index.php';
$files[] = 'i18n/locale/en/index.php';
$files[] = 'i18n/locale/fr/index.php';
$files[] = 'i18n/locale/index.php';
$files[] = 'included/browser/css/index.php';
$files[] = 'included/browser/css/redmond/images/index.php';
$files[] = 'included/browser/css/redmond/index.php';
$files[] = 'included/browser/js_endpage/index.php';
$files[] = 'included/browser/js_header/index.php';
$files[] = 'included/font/index.php';
$files[] = 'included/index.php';
$files[] = 'overlays/bbb_meetings/index.php';
$files[] = 'overlays/etherpad_meetings/index.php';
$files[] = 'overlays/events/index.php';
$files[] = 'overlays/forms/index.php';
$files[] = 'overlays/issues/index.php';
$files[] = 'overlays/meetings/index.php';
$files[] = 'overlays/mutables/index.php';
$files[] = 'overlays/polls/index.php';
$files[] = 'skins/_mobile/index.php';
$files[] = 'skins/_reference/index.php';
$files[] = 'skins/boxesandarrows/index.php';
$files[] = 'skins/digital/index.php';
$files[] = 'skins/flexible/index.php';
$files[] = 'skins/joi/index.php';
$files[] = 'skins/shared/index.php';
$files[] = 'skins/skeleton/index.php';
$files[] = 'versions/index.php';

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