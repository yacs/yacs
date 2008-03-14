<?php
/**
 * move images from files/icons to skins/images/files
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Move icons used for files';
$local['label_fr'] = 'D&eacute;placement des images utilis&eacute;es pour les fichiers';
echo i18n::l($local, 'label')."<br />\n";

// files to fetch, from root path
unset($files);
$files[] = 'files/icons/access_icon.gif';
$files[] = 'files/icons/default_icon.gif';
$files[] = 'files/icons/excel_icon.gif';
$files[] = 'files/icons/exe_icon.gif';
$files[] = 'files/icons/film_icon.gif';
$files[] = 'files/icons/flash_icon.gif';
$files[] = 'files/icons/freemind_icon.gif';
$files[] = 'files/icons/gpx_icon.gif';
$files[] = 'files/icons/html_icon.gif';
$files[] = 'files/icons/image_icon.gif';
$files[] = 'files/icons/midi_icon.gif';
$files[] = 'files/icons/mov_icon.gif';
$files[] = 'files/icons/ooo_calc_icon.png';
$files[] = 'files/icons/ooo_chart_icon.png';
$files[] = 'files/icons/ooo_database_icon.png';
$files[] = 'files/icons/ooo_draw_icon.png';
$files[] = 'files/icons/ooo_global_icon.png';
$files[] = 'files/icons/ooo_html_icon.png';
$files[] = 'files/icons/ooo_impress_icon.png';
$files[] = 'files/icons/ooo_math_icon.png';
$files[] = 'files/icons/ooo_writer_icon.png';
$files[] = 'files/icons/open_workbench_icon.gif';
$files[] = 'files/icons/palm_icon.gif';
$files[] = 'files/icons/pdf_icon.gif';
$files[] = 'files/icons/postscript_icon.gif';
$files[] = 'files/icons/powerpoint_icon.gif';
$files[] = 'files/icons/project_icon.gif';
$files[] = 'files/icons/publisher_icon.gif';
$files[] = 'files/icons/sound_icon.gif';
$files[] = 'files/icons/tex_icon.gif';
$files[] = 'files/icons/text_icon.gif';
$files[] = 'files/icons/visio_icon.gif';
$files[] = 'files/icons/word_icon.gif';
$files[] = 'files/icons/write_icon.gif';
$files[] = 'files/icons/zip_icon.gif';

// the new location for these files
$target = 'skins/images/files';

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