<?php
/**
 * move images from comments/images to skins/images/comments
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Move icons used in comments';
$local['label_fr'] = 'D&eacute;placement des images utilis&eacute;es dans les commentaires';
echo i18n::l($local, 'label')."<br />\n";

// files to fetch, from root path
unset($files);
$files[] = 'comments/images/attention.gif';
$files[] = 'comments/images/done.gif';
$files[] = 'comments/images/idea.gif';
$files[] = 'comments/images/information.gif';
$files[] = 'comments/images/question.gif';
$files[] = 'comments/images/thumbs_down.gif';
$files[] = 'comments/images/thumbs_up.gif';
$files[] = 'comments/images/warning.gif';

// the new location for these files
$target = 'skins/images/comments';

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