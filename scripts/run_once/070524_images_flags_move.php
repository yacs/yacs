<?php
/**
 * move images from codes/flags to skins/images/flags
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Move flags';
$local['label_fr'] = 'D&eacute;placement des drapeaux';
echo i18n::l($local, 'label')."<br />\n";

// files to fetch, from root path
unset($files);
$files[] = 'codes/flags/be.gif';
$files[] = 'codes/flags/ca.gif';
$files[] = 'codes/flags/ch.gif';
$files[] = 'codes/flags/de.gif';
$files[] = 'codes/flags/es.gif';
$files[] = 'codes/flags/fr.gif';
$files[] = 'codes/flags/gb.gif';
$files[] = 'codes/flags/gr.gif';
$files[] = 'codes/flags/it.gif';
$files[] = 'codes/flags/pt.gif';
$files[] = 'codes/flags/us.gif';

// the new location for these files
$target = 'skins/images/flags';

// create missing directories
Safe::make_path($target);

// process every file
$count = 0;
foreach($files as $file) {

	// get the file locally
	if(file_exists($context['path_to_root'].$file)) {

		// update the target file
		if(!Safe::rename($file, $target.'/'.basename($file))) {
			$local['label_en'] = 'Impossible to move the file '.$file.'.';
			$local['label_fr'] = 'Impossible de d&eacute;placer le fichier '.$file.'.';
			echo i18n::l($local, 'label')."<br />\n";
		} else {
			$local['label_en'] = 'has been moved to';
			$local['label_fr'] = 'a &eacute;t&eacute; d&eacute;plac&eacute vers';
			echo $file.' '.i18n::l($local, 'label').' '.$target."<br />\n";
		}

	}

	// attemp to silently remove the origin directory
	Safe::rmdir(dirname($file));

	// next one
	$count += 1;
	Safe::set_time_limit(30);
}

// basic reporting
$local['label_en'] = 'files have been processed';
$local['label_fr'] = 'fichiers ont été traités';
echo $count.' '.i18n::l($local, 'label')."<br />\n";
?>