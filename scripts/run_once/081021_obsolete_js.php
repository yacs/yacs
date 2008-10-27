<?php
/**
 * remove old files, if any
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Remove obsoleted files';
$local['label_fr'] = 'Suppression des fichiers inutiles';
echo get_local('label')."<br />\n";

// files to delete, from root path
$files = array();
$files[] = 'included/browser/builder.js';
$files[] = 'included/browser/controls.js';
$files[] = 'included/browser/document_write.js';
$files[] = 'included/browser/dragdrop.js';
$files[] = 'included/browser/effects.js';
$files[] = 'included/browser/prototype.js';
$files[] = 'included/browser/scriptaculous.js';
$files[] = 'included/browser/slider.js';
$files[] = 'included/browser/sound.js';
$files[] = 'included/browser/swfobject.js';
$files[] = 'included/browser/unittest.js';
$files[] = 'included/jscalendar/calendar.js';
$files[] = 'included/jscalendar/calendar-setup.js';
$files[] = 'included/tiny_mce/tiny_mce_gzip.js';
$files[] = 'included/tiny_mce/tiny_mce_gzip.php';

// process every file
$count = 0;
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