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
$files[] = 'articles/layout_articles_as_admin.php';
$files[] = 'control/import.php';
$files[] = 'control/import_csv.php';
$files[] = 'overlays/contact.php';
$files[] = 'overlays/select.php';

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