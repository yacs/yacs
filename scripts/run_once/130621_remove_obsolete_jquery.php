<?php
/**
 * remove old files from previous jquery-ui version
 *
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Remove obsoleted files from former jquery version';
$local['label_fr'] = 'Suppression des fichiers inutiles d\'une version précédente de jQuery';
echo get_local('label')."<br />\n";
$count = 0;


// files to delete, from root path
$files = array();
$files[] = 'included/browser/css/redmond/images/ui-anim_basic_16x16.gif';
$files[] = 'included/browser/css/redmond/images/ui-bg_diagonals-thick_18_b81900_40x40.png';
$files[] = 'included/browser/css/redmond/images/ui-bg_diagonals-thick_20_666666_40x40.png';
$files[] = 'included/browser/css/redmond/images/ui-bg_flat_10_000000_40x100.png';
$files[] = 'included/browser/css/redmond/images/ui-bg_glass_65_ffffff_1x400.png';
$files[] = 'included/browser/css/redmond/images/ui-bg_glass_100_f6f6f6_1x400.png';
$files[] = 'included/browser/css/redmond/images/ui-bg_glass_100_fdf5ce_1x400.png';
$files[] = 'included/browser/css/redmond/images/ui-bg_gloss-wave_35_f6a828_500x100.png';
$files[] = 'included/browser/css/redmond/images/ui-bg_highlight-soft_75_ffe45c_1x100.png';
$files[] = 'included/browser/css/redmond/images/ui-icons_222222_256x240.png';
$files[] = 'included/browser/css/redmond/images/ui-icons_228ef1_256x240.png';
$files[] = 'included/browser/css/redmond/images/ui-icons_ffd27a_256x240.png';
$files[] = 'included/browser/css/redmond/images/ui-icons_ffffff_256x240.png';
$files[] = 'included/browser/css/redmond/jquery-ui-1.8.14.custom.css';
$files[] = 'included/browser/js_endpage/jquery-ui-1.8.14.custom.min.js';
$files[] = 'included/browser/js_header/jquery-1.6.2.min.js';

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