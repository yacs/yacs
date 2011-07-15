<?php
/**
 * move to jquery based javascript libraries
 *
 * @author Alexis raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Migrate to JQuery javascript libraries';
$local['label_fr'] = 'Migration vers la bibliothèque javascript JQuery';
echo i18n::user('label')."<br />\n";

// the reference server to use
@include_once $context['path_to_root'].'scripts/parameters.include.php';
if(!isset($context['reference_server']) || !$context['reference_server'])
	$context['reference_server'] = 'www.yacs.fr';

// files to fetch, from root path
$files = array();
$files[] = 'included/browser/css/redmond/images/ui-anim_basic_16x16.gif';
$files[] = 'included/browser/css/redmond/images/ui-bg_diagonals-thick_18_b81900_40x40.png';
$files[] = 'included/browser/css/redmond/images/ui-bg_diagonals-thick_20_666666_40x40.png';
$files[] = 'included/browser/css/redmond/images/ui-bg_flat_0_aaaaaa_40x100.png';
$files[] = 'included/browser/css/redmond/images/ui-bg_flat_10_000000_40x100.png';
$files[] = 'included/browser/css/redmond/images/ui-bg_flat_55_fbec88_40x100.png';
$files[] = 'included/browser/css/redmond/images/ui-bg_glass_100_f6f6f6_1x400.png';
$files[] = 'included/browser/css/redmond/images/ui-bg_glass_100_fdf5ce_1x400.png';
$files[] = 'included/browser/css/redmond/images/ui-bg_glass_65_ffffff_1x400.png';
$files[] = 'included/browser/css/redmond/images/ui-bg_glass_75_d0e5f5_1x400.png';
$files[] = 'included/browser/css/redmond/images/ui-bg_glass_85_dfeffc_1x400.png';
$files[] = 'included/browser/css/redmond/images/ui-bg_glass_95_fef1ec_1x400.png';
$files[] = 'included/browser/css/redmond/images/ui-bg_gloss-wave_35_f6a828_500x100.png';
$files[] = 'included/browser/css/redmond/images/ui-bg_gloss-wave_55_5c9ccc_500x100.png';
$files[] = 'included/browser/css/redmond/images/ui-bg_highlight-soft_100_eeeeee_1x100.png';
$files[] = 'included/browser/css/redmond/images/ui-bg_highlight-soft_75_ffe45c_1x100.png';
$files[] = 'included/browser/css/redmond/images/ui-bg_inset-hard_100_f5f8f9_1x100.png';
$files[] = 'included/browser/css/redmond/images/ui-bg_inset-hard_100_fcfdfd_1x100.png';
$files[] = 'included/browser/css/redmond/images/ui-icons_217bc0_256x240.png';
$files[] = 'included/browser/css/redmond/images/ui-icons_222222_256x240.png';
$files[] = 'included/browser/css/redmond/images/ui-icons_228ef1_256x240.png';
$files[] = 'included/browser/css/redmond/images/ui-icons_2e83ff_256x240.png';
$files[] = 'included/browser/css/redmond/images/ui-icons_469bdd_256x240.png';
$files[] = 'included/browser/css/redmond/images/ui-icons_6da8d5_256x240.png';
$files[] = 'included/browser/css/redmond/images/ui-icons_cd0a0a_256x240.png';
$files[] = 'included/browser/css/redmond/images/ui-icons_d8e7f3_256x240.png';
$files[] = 'included/browser/css/redmond/images/ui-icons_ef8c08_256x240.png';
$files[] = 'included/browser/css/redmond/images/ui-icons_f9bd01_256x240.png';
$files[] = 'included/browser/css/redmond/images/ui-icons_ffd27a_256x240.png';
$files[] = 'included/browser/css/redmond/images/ui-icons_ffffff_256x240.png';
$files[] = 'included/browser/css/redmond/jquery-ui-1.8.14.custom.css';
$files[] = 'included/browser/js_endpage/jquery-ui-1.8.14.custom.min.js';
$files[] = 'included/browser/js_endpage/jquery.json.min.js';
$files[] = 'included/browser/js_endpage/swfobject.js';
$files[] = 'included/browser/js_header/jquery-1.6.2.min.js';
$files[] = 'included/browser/js_header/reflection.js';
$files[] = 'included/browser/library_endpage.min.js';
$files[] = 'included/browser/library_header.min.js';
$files[] = 'included/fckeditor/editor/js/fckeditorcode_gecko.js';
$files[] = 'included/fckeditor/editor/js/fckeditorcode_ie.js';
$files[] = 'shared/yacs.js';
$files[] = 'skins/_reference/yacs.css';

// process every file
$count = 0;
foreach($files as $file) {

	// content of the updated file
	$content = '';

	// expected location in staging repository
	$local_reference = $context['path_to_root'].'scripts/staging/'.$file;

	// don't execute PHP scripts, just get them
	if(preg_match('/\.php$/i', $file))
		$remote_reference = 'http://'.$context['reference_server'].'/scripts/fetch.php?script='.urlencode($file);

	// fetch other files from remote reference store
	else
		$remote_reference = 'http://'.$context['reference_server'].'/scripts/reference/'.$file;

	// get the file locally
	if(file_exists($local_reference))
		$content = Safe::file_get_contents($local_reference);

	// or get the file from reference server
	elseif(($content = http::proceed($remote_reference)) === FALSE) {
		$local['error_en'] = 'Unable to get '.$file;
		$local['error_fr'] = 'Impossible d\'obtenir '.$file;
		echo i18n::user('error')."<br />\n";
	}

	// we have something in hand
	if($content) {

		// create missing directories where applicable
		Safe::make_path(dirname($file));

		// create backups, if possible
		if(file_exists($context['path_to_root'].$file)) {
			Safe::unlink($context['path_to_root'].$file.'.bak');
			Safe::rename($context['path_to_root'].$file, $context['path_to_root'].$file.'.bak');
		}

		// update the target file
		if(!Safe::file_put_contents($file, $content)) {
			$local['label_en'] = 'Impossible to write to the file '.$file.'.';
			$local['label_fr'] = 'Impossible d\'&eacute;crire le fichier '.$file.'.';
			echo i18n::user('label')."<br />\n";
		} else {
			$local['label_en'] = 'has been updated';
			$local['label_fr'] = 'a &eacute;t&eacute; mis &agrave; jour';
			echo $file.' '.i18n::user('label')."<br />\n";
		}

	}

	// next one
	$count += 1;
	Safe::set_time_limit(30);
}

// basic reporting
$local['label_en'] = 'files have been processed';
$local['label_fr'] = 'fichiers ont &eacute;t&eacute; trait&eacute;s';
echo $count.' '.i18n::user('label')."<br />\n";
?>