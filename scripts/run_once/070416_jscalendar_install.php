<?php
/**
 * install jscalendar 1.0
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Install jsCalendar 1.0';
$local['label_fr'] = 'Installation de jsCalendar 1.0';
echo get_local('label')."<br />\n";

// the reference server to use
@include_once $context['path_to_root'].'scripts/parameters.include.php';
if(!isset($context['reference_server']) || !$context['reference_server'])
	$context['reference_server'] = 'www.yetanothercommunitysystem.com';

// files to fetch, from root path
unset($files);
$files[] = 'included/jscalendar/calendar.js';
$files[] = 'included/jscalendar/calendar-setup.js';
$files[] = 'included/jscalendar/calendar-system.css';
$files[] = 'included/jscalendar/img.gif';
$files[] = 'included/jscalendar/menuarrow.gif';
$files[] = 'included/jscalendar/menuarrow2.gif';
$files[] = 'included/jscalendar/README';
$files[] = 'included/jscalendar/lang/calendar-en.js';
$files[] = 'included/jscalendar/lang/calendar-fr.js';
$files[] = 'included/jscalendar/lang/calendar-it.js';
$files[] = 'included/jscalendar/skins/aqua/active-bg.gif';
$files[] = 'included/jscalendar/skins/aqua/dark-bg.gif';
$files[] = 'included/jscalendar/skins/aqua/hover-bg.gif';
$files[] = 'included/jscalendar/skins/aqua/menuarrow.gif';
$files[] = 'included/jscalendar/skins/aqua/normal-bg.gif';
$files[] = 'included/jscalendar/skins/aqua/rowhover-bg.gif';
$files[] = 'included/jscalendar/skins/aqua/status-bg.gif';
$files[] = 'included/jscalendar/skins/aqua/theme.css';
$files[] = 'included/jscalendar/skins/aqua/title-bg.gif';
$files[] = 'included/jscalendar/skins/aqua/today-bg.gif';

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
		$local['error_en'] = 'Unable to get '.$url;
		$local['error_fr'] = 'Impossible d\'obtenir '.$url;
		echo get_local('error')."<br />\n";
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