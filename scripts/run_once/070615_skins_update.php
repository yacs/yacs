<?php
/**
 * update reference skins
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Update reference skins';
$local['label_fr'] = 'Mise &agrave; jour des styles de r&eacute;f&eacute;rence';
echo get_local('label')."<br />\n";

// the reference server to use
@include_once $context['path_to_root'].'scripts/parameters.include.php';
if(!isset($context['reference_server']) || !$context['reference_server'])
	$context['reference_server'] = 'www.yetanothercommunitysystem.com';

// files to fetch, from root path
unset($files);
$files[] = 'skins/boxesandarrows/boxesandarrows.css';
$files[] = 'skins/boxesandarrows/icons/xml.gif';
$files[] = 'skins/digital/digital.css';
$files[] = 'skins/digital/icons/xml.gif';
$files[] = 'skins/images/feeds/addthis.gif';
$files[] = 'skins/images/feeds/addtonetvibes.gif';
$files[] = 'skins/images/files/google_icon.gif';
$files[] = 'skins/images/files/mmap_icon.gif';
$files[] = 'skins/images/files/pdf_icon.gif';
$files[] = 'skins/images/files/security_icon.png';
$files[] = 'skins/images/files/sound_icon.png';
$files[] = 'skins/images/files_inline/mmap.gif';
$files[] = 'skins/images/files_inline/pdf.gif';
$files[] = 'skins/joi/joi.css';
$files[] = 'skins/joi/icons/xml.gif';
$files[] = 'skins/skeleton/skeleton.css';
$files[] = 'skins/skeleton/icons/xml.gif';
$files[] = 'smileys/edit.js';

// process every file
$count = 0;
foreach($files as $file) {

	// content of the updated file
	$content = '';

	// expected location in staging repository
	$local_reference = $context['path_to_root'].'scripts/staging/'.$file;

	// expected link from reference server
	include_once $context['path_to_root'].'links/link.php';

	// don't execute PHP scripts, just get them
	if(preg_match('/\.php$/i', $file))
		$remote_reference = 'http://'.$context['reference_server'].'/scripts/fetch.php?script='.urlencode($file);

	// fetch other files from remote reference store
	else
		$remote_reference = 'http://'.$context['reference_server'].'/scripts/reference/'.$file;

	// get the file locally
	if(file_exists($local_reference))
		$content = file_get_contents($local_reference);

	// or get the file from reference server
	elseif(($content = Link::fetch($remote_reference)) === FALSE) {
		$local['error_en'] = 'Unable to get '.$file;
		$local['error_fr'] = 'Impossible d\'obtenir '.$file;
		echo get_local('error')."<br />\n";
	}

	// we have something in hand
	if($content) {

		// create missing directories where applicable
		Safe::make_path(dirname($file));

		// update the target file
		if(!Safe::file_put_contents($file, $content)) {
			$local['label_en'] = 'Impossible to write to the file '.$file.'.';
			$local['label_fr'] = 'Impossible d\'écrire le fichier '.$file.'.';
			echo get_local('label')."<br />\n";
		} else {
			$local['label_en'] = 'has been updated';
			$local['label_fr'] = 'a été mis à jour';
			echo $file.' '.get_local('label')."<br />\n";
		}

	}

	// next one
	$count += 1;
	Safe::set_time_limit(30);
}

// basic reporting
$local['label_en'] = 'files have been processed';
$local['label_fr'] = 'fichiers ont été traités';
echo $count.' '.get_local('label')."<br />\n";
?>