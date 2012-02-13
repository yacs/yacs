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
$files[] = 'included/browser/js_endpage/swfobject.js';
$files[] = 'overlays/external_meeting.php';
$files[] = 'skins/digital/nicetitle.css';
$files[] = 'skins/digital/nicetitle.js';
$files[] = 'skins/skeleton/nicetitle.css';
$files[] = 'skins/skeleton/nicetitle.js';
$files[] = 'users/contact.php';

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