<?php
/**
 * remove old images, if any
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
$files[] = 'skins/boxesandarrows/icons/pagers/aim.gif';
$files[] = 'skins/boxesandarrows/icons/pagers/icq.gif';
$files[] = 'skins/boxesandarrows/icons/pagers/irc.gif';
$files[] = 'skins/boxesandarrows/icons/pagers/jabber.gif';
$files[] = 'skins/boxesandarrows/icons/pagers/msn.gif';
$files[] = 'skins/boxesandarrows/icons/pagers/skype.gif';
$files[] = 'skins/boxesandarrows/icons/pagers/yahoo.gif';
$files[] = 'skins/digital/icons/pagers/aim.gif';
$files[] = 'skins/digital/icons/pagers/icq.gif';
$files[] = 'skins/digital/icons/pagers/irc.gif';
$files[] = 'skins/digital/icons/pagers/jabber.gif';
$files[] = 'skins/digital/icons/pagers/msn.gif';
$files[] = 'skins/digital/icons/pagers/skype.gif';
$files[] = 'skins/digital/icons/pagers/yahoo.gif';
$files[] = 'skins/joi/icons/pagers/aim.gif';
$files[] = 'skins/joi/icons/pagers/icq.gif';
$files[] = 'skins/joi/icons/pagers/irc.gif';
$files[] = 'skins/joi/icons/pagers/jabber.gif';
$files[] = 'skins/joi/icons/pagers/msn.gif';
$files[] = 'skins/joi/icons/pagers/skype.gif';
$files[] = 'skins/joi/icons/pagers/yahoo.gif';
$files[] = 'skins/skeleton/icons/pagers/aim.gif';
$files[] = 'skins/skeleton/icons/pagers/icq.gif';
$files[] = 'skins/skeleton/icons/pagers/irc.gif';
$files[] = 'skins/skeleton/icons/pagers/jabber.gif';
$files[] = 'skins/skeleton/icons/pagers/msn.gif';
$files[] = 'skins/skeleton/icons/pagers/skype.gif';
$files[] = 'skins/skeleton/icons/pagers/yahoo.gif';

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