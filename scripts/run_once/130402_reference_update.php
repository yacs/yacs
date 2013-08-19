<?php
/**
 * update reference files
 *
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Update reference files (starterfive skin)';
$local['label_fr'] = 'Mise &agrave; jour compl&eacute;mentaire (theme starterfive)';
echo i18n::user('label')."<br />\n";

// the reference server to use
@include_once $context['path_to_root'].'scripts/parameters.include.php';
if(!isset($context['reference_server']) || !$context['reference_server'])
	$context['reference_server'] = 'www.yacs.fr';

// files to fetch, from root path
$files = array();
$files[] = 'shared/yacs.js';
$files[] = 'skins/_reference/yacs.css';
$files[] = 'tools/srcfiles.txt';
$files[] = 'skins/starterfive/preview.png';
$files[] = 'skins/starterfive/starterfive.css';
$files[] = 'skins/starterfive/favicon.ico';
$files[] = 'skins/starterfive/css/knacss-garni.css';
$files[] = 'skins/starterfive/css/main-bottom.css';
$files[] = 'skins/starterfive/css/kmain-top.css';
$files[] = 'skins/starterfive/css/normalize.css';
$files[] = 'skins/starterfive/fonts/kenyancoffee.ttf';
$files[] = 'skins/starterfive/fonts/kenyancoffee.ttf';
$files[] = 'skins/starterfive/fonts/kenyancoffee.ttf';
$files[] = 'skins/starterfive/fonts/readThisFirst.html';
$files[] = 'skins/starterfive/fonts/typodermic_license_agreement.pdf';
$files[] = 'skins/starterfive/js/modernizr-2.6.2.min.js';
$files[] = 'skins/starterfive/js/PIE.js';
$files[] = 'skins/starterfive/js/pie_enhance_ie.js';
$files[] = 'skins/starterfive/layouts/decorated.png';
$files[] = 'sections/layout_sections_as_smartlist.php';


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