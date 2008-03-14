<?php
/**
 * update i18n and l10n files
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Update language files';
$local['label_fr'] = 'Mise &agrave; jour des fichiers de langue';
echo get_local('label')."<br />\n";

// the reference server to use
@include_once $context['path_to_root'].'scripts/parameters.include.php';
if(!isset($context['reference_server']) || !$context['reference_server'])
	$context['reference_server'] = 'www.yetanothercommunitysystem.com';

// files to fetch, from root path
unset($files);
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
		$local['error_en'] = 'Unable to get '.$file;
		$local['error_fr'] = 'Impossible d\'obtenir '.$file;
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
			$local['label_fr'] = 'Impossible d\'&eacute;crire le fichier '.$file.'.';
			echo get_local('label')."<br />\n";
		} else {
			$local['label_en'] = 'has been updated';
			$local['label_fr'] = 'a &eacute;t&eacute; mis &agrave; jour';
			echo $file.' '.get_local('label')."<br />\n";
		}

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