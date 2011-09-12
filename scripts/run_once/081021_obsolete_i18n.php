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
$files[] = 'i18n/locale/en/actions.mo';
$files[] = 'i18n/locale/en/agents.mo';
$files[] = 'i18n/locale/en/articles.mo';
$files[] = 'i18n/locale/en/behaviors.mo';
$files[] = 'i18n/locale/en/categories.mo';
$files[] = 'i18n/locale/en/codes.mo';
$files[] = 'i18n/locale/en/collections.mo';
$files[] = 'i18n/locale/en/comments.mo';
$files[] = 'i18n/locale/en/control.mo';
$files[] = 'i18n/locale/en/dates.mo';
$files[] = 'i18n/locale/en/decisions.mo';
$files[] = 'i18n/locale/en/feeds.mo';
$files[] = 'i18n/locale/en/files.mo';
$files[] = 'i18n/locale/en/forms.mo';
$files[] = 'i18n/locale/en/i18n.mo';
$files[] = 'i18n/locale/en/images.mo';
$files[] = 'i18n/locale/en/letters.mo';
$files[] = 'i18n/locale/en/links.mo';
$files[] = 'i18n/locale/en/locations.mo';
$files[] = 'i18n/locale/en/overlays.mo';
$files[] = 'i18n/locale/en/root.mo';
$files[] = 'i18n/locale/en/scripts.mo';
$files[] = 'i18n/locale/en/sections.mo';
$files[] = 'i18n/locale/en/servers.mo';
$files[] = 'i18n/locale/en/services.mo';
$files[] = 'i18n/locale/en/shared.mo';
$files[] = 'i18n/locale/en/skins.mo';
$files[] = 'i18n/locale/en/smileys.mo';
$files[] = 'i18n/locale/en/tables.mo';
$files[] = 'i18n/locale/en/tools.mo';
$files[] = 'i18n/locale/en/users.mo';
$files[] = 'i18n/locale/en/versions.mo';
$files[] = 'i18n/locale/fr/actions.mo';
$files[] = 'i18n/locale/fr/agents.mo';
$files[] = 'i18n/locale/fr/articles.mo';
$files[] = 'i18n/locale/fr/behaviors.mo';
$files[] = 'i18n/locale/fr/categories.mo';
$files[] = 'i18n/locale/fr/codes.mo';
$files[] = 'i18n/locale/fr/collections.mo';
$files[] = 'i18n/locale/fr/comments.mo';
$files[] = 'i18n/locale/fr/control.mo';
$files[] = 'i18n/locale/fr/dates.mo';
$files[] = 'i18n/locale/fr/decisions.mo';
$files[] = 'i18n/locale/fr/feeds.mo';
$files[] = 'i18n/locale/fr/files.mo';
$files[] = 'i18n/locale/fr/forms.mo';
$files[] = 'i18n/locale/fr/i18n.mo';
$files[] = 'i18n/locale/fr/images.mo';
$files[] = 'i18n/locale/fr/letters.mo';
$files[] = 'i18n/locale/fr/links.mo';
$files[] = 'i18n/locale/fr/locations.mo';
$files[] = 'i18n/locale/fr/overlays.mo';
$files[] = 'i18n/locale/fr/root.mo';
$files[] = 'i18n/locale/fr/scripts.mo';
$files[] = 'i18n/locale/fr/sections.mo';
$files[] = 'i18n/locale/fr/servers.mo';
$files[] = 'i18n/locale/fr/services.mo';
$files[] = 'i18n/locale/fr/shared.mo';
$files[] = 'i18n/locale/fr/skins.mo';
$files[] = 'i18n/locale/fr/smileys.mo';
$files[] = 'i18n/locale/fr/tables.mo';
$files[] = 'i18n/locale/fr/tools.mo';
$files[] = 'i18n/locale/fr/users.mo';
$files[] = 'i18n/locale/fr/versions.mo';

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