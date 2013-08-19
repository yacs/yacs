<?php
/**
 * remove old files
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Remove obsoleted files';
$local['label_fr'] = 'Suppression des fichiers inutiles';
echo get_local('label')."<br />\n";
$count = 0;


// files to delete, from root path
$files = array();
$files[] = 'decisions/check.php';
$files[] = 'decisions/decisions.php';
$files[] = 'decisions/delete.php';
$files[] = 'decisions/edit.php';
$files[] = 'decisions/feed.php';
$files[] = 'decisions/index.php';
$files[] = 'decisions/layout_decisions.php';
$files[] = 'decisions/layout_decisions_as_feed.php';
$files[] = 'decisions/list.php';
$files[] = 'decisions/mail.php';
$files[] = 'decisions/view.php';
$files[] = 'i18n/locale/en/decisions.mo';
$files[] = 'i18n/locale/en/decisions.mo.php';
$files[] = 'i18n/locale/en/decisions.po';
$files[] = 'i18n/locale/fr/decisions.mo';
$files[] = 'i18n/locale/fr/decisions.mo.php';
$files[] = 'i18n/locale/fr/decisions.po';
$files[] = 'i18n/templates/decisions.pot';
$files[] = 'overlays/vote.php';
$files[] = 'skins/_reference/decisions/no.gif';
$files[] = 'skins/_reference/decisions/yes.gif';

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