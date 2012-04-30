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
$files[] = 'images/upload.php';
$files[] = 'included/tiny_mce/plugins/paste/blank.htm';
$files[] = 'included/tiny_mce/plugins/paste/css/blank.css';
$files[] = 'included/tiny_mce/plugins/paste/css/pasteword.css';
$files[] = 'included/tiny_mce/plugins/safari/blank.htm';
$files[] = 'included/tiny_mce/plugins/safari/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/safari/editor_plugin_src.js';
$files[] = 'overlays/generic_meeting.php';
$files[] = 'overlays/meetings/hook.php';
$files[] = 'sections/import.php';

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