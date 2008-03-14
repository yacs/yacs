<?php
/**
 * centralize configuration and temporary files
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Centralize configuration files';
$local['label_fr'] = 'Centralisation des fichiers de configuration';
echo get_local('label')."<br />\n";

// files to move, from root path
unset($files);
$files[] = array('agents/parameters.include.php',		'parameters/agents.include.php');
$files[] = array('collections/parameters.include.php',	'parameters/collections.include.php');
$files[] = array('feeds/parameters.include.php',		'parameters/feeds.include.php');
$files[] = array('feeds/flash/parameters.include.php',	'parameters/feeds.flash.include.php');
$files[] = array('files/parameters.include.php',		'parameters/files.include.php');
$files[] = array('letters/parameters.include.php',		'parameters/letters.include.php');
$files[] = array('scripts/parameters.include.php',		'parameters/scripts.include.php');
$files[] = array('servers/parameters.include.php',		'parameters/servers.include.php');
$files[] = array('services/parameters.include.php',		'parameters/services.include.php');
$files[] = array('shared/hooks.include.php',			'parameters/hooks.include.php');
$files[] = array('shared/hooks.xml',					'parameters/hooks.xml');
$files[] = array('shared/parameters.include.php',		'parameters/control.include.php');
$files[] = array('skins/parameters.include.php',		'parameters/skins.include.php');
$files[] = array('users/parameters.include.php',		'parameters/user.include.php');
$files[] = array('switch.include.php',					'parameters/switch.include.php');
$files[] = array('switch.off',							'parameters/switch.off');
$files[] = array('switch.on',							'parameters/switch.on');
$files[] = array('agents/debug.txt',					'temporary/debug.txt');
$files[] = array('agents/log.txt',						'temporary/log.txt');

// process every file
$count = 0;
foreach($files as $items) {

	// extract source and target
	list($from, $to) = $items;

	// source file has to exist
	if(!file_exists($context['path_to_root'].$from))
		continue;

	// create missing directories where applicable
	Safe::make_path(dirname($to));

	// copy cannot overwrite files
	Safe::unlink($context['path_to_root'].$to.'.bak');
	Safe::rename($context['path_to_root'].$to, $context['path_to_root'].$to.'.bak');

	// copy file
	if(Safe::copy($context['path_to_root'].$from, $context['path_to_root'].$to)) {
		echo sprintf('%s has been copied to %s', $from, $to).BR;

		// silently remove old file
		Safe::unlink($context['path_to_root'].$from);

	// houston, we've got a problem
	} else
		echo sprintf('Unable to copy %s to %s. Please do it manually', $from, $to).BR;

	// next one
	$count += 1;
	Safe::set_time_limit(30);
}

// basic reporting
$local['label_en'] = 'files have been processed';
$local['label_fr'] = 'fichiers ont été traités';
echo $count.' '.get_local('label')."<br />\n";
?>