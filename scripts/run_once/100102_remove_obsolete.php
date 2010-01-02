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
$count = 0;

// international files have been moved away
if($items=Safe::glob($context['path_to_root'].'temporary/cache_*.mo.php')) {

	foreach($items as $name) {
		if(Safe::unlink($name)) {
			$local['error_en'] = substr($name, strlen($context['path_to_root'])).' has been removed';
			$local['error_fr'] = substr($name, strlen($context['path_to_root'])).' a &eacute;t&eacute; supprim&eacute;';
			echo get_local('error')."<br />\n";
			$count += 1;
		}

	}

}

// files to delete, from root path
$files = array();
$files[] = 'skins/boxesandarrows/template_mobile.php';
$files[] = 'skins/digital/template_mobile.php';
$files[] = 'skins/joi/template_mobile.php';
$files[] = 'skins/skeleton/template_mobile.php';

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