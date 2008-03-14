<?php
/**
 * move images from collections/icons to skins/images/files_inline
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Move icons used in collections';
$local['label_fr'] = 'D&eacute;placement des images utilis&eacute;es dans les collections';
echo i18n::l($local, 'label')."<br />\n";

// files to fetch, from root path
unset($files);
$files[] = 'collections/icons/access.gif';
$files[] = 'collections/icons/collection.png';
$files[] = 'collections/icons/default.gif';
$files[] = 'collections/icons/excel.gif';
$files[] = 'collections/icons/exe.gif';
$files[] = 'collections/icons/flash.gif';
$files[] = 'collections/icons/folder.png';
$files[] = 'collections/icons/folder_up.gif';
$files[] = 'collections/icons/help.gif';
$files[] = 'collections/icons/html.gif';
$files[] = 'collections/icons/image.gif';
$files[] = 'collections/icons/midi.gif';
$files[] = 'collections/icons/ooo_calc_icon.png';
$files[] = 'collections/icons/ooo_database_icon.png';
$files[] = 'collections/icons/ooo_draw_icon.png';
$files[] = 'collections/icons/ooo_global_icon.png';
$files[] = 'collections/icons/ooo_html_icon.png';
$files[] = 'collections/icons/ooo_impress_icon.png';
$files[] = 'collections/icons/ooo_writer_icon.png';
$files[] = 'collections/icons/pdf.png';
$files[] = 'collections/icons/php.gif';
$files[] = 'collections/icons/postscript.png';
$files[] = 'collections/icons/ppt.gif';
$files[] = 'collections/icons/project.gif';
$files[] = 'collections/icons/publisher.gif';
$files[] = 'collections/icons/qb_download.gif';
$files[] = 'collections/icons/qb_movie.gif';
$files[] = 'collections/icons/qb_sound.gif';
$files[] = 'collections/icons/sound.gif';
$files[] = 'collections/icons/system.gif';
$files[] = 'collections/icons/tex.png';
$files[] = 'collections/icons/txt.gif';
$files[] = 'collections/icons/video.gif';
$files[] = 'collections/icons/visio.gif';
$files[] = 'collections/icons/word.gif';
$files[] = 'collections/icons/write.gif';
$files[] = 'collections/icons/zip.gif';

// the new location for these files
$target = 'skins/images/files_inline';

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