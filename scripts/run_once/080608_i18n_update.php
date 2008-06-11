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
echo i18n::user('label')."<br />\n";

// the reference server to use
@include_once $context['path_to_root'].'scripts/parameters.include.php';
if(!isset($context['reference_server']) || !$context['reference_server'])
	$context['reference_server'] = 'www.yetanothercommunitysystem.com';

// files to fetch, from root path
unset($files);
$files[] = 'temporary/cache_i18n_locale_en_actions.mo.php';
$files[] = 'temporary/cache_i18n_locale_en_agents.mo.php';
$files[] = 'temporary/cache_i18n_locale_en_articles.mo.php';
$files[] = 'temporary/cache_i18n_locale_en_behaviors.mo.php';
$files[] = 'temporary/cache_i18n_locale_en_categories.mo.php';
$files[] = 'temporary/cache_i18n_locale_en_codes.mo.php';
$files[] = 'temporary/cache_i18n_locale_en_collections.mo.php';
$files[] = 'temporary/cache_i18n_locale_en_comments.mo.php';
$files[] = 'temporary/cache_i18n_locale_en_control.mo.php';
$files[] = 'temporary/cache_i18n_locale_en_dates.mo.php';
$files[] = 'temporary/cache_i18n_locale_en_decisions.mo.php';
$files[] = 'temporary/cache_i18n_locale_en_feeds.mo.php';
$files[] = 'temporary/cache_i18n_locale_en_files.mo.php';
$files[] = 'temporary/cache_i18n_locale_en_forms.mo.php';
$files[] = 'temporary/cache_i18n_locale_en_i18n.mo.php';
$files[] = 'temporary/cache_i18n_locale_en_images.mo.php';
$files[] = 'temporary/cache_i18n_locale_en_letters.mo.php';
$files[] = 'temporary/cache_i18n_locale_en_links.mo.php';
$files[] = 'temporary/cache_i18n_locale_en_locations.mo.php';
$files[] = 'temporary/cache_i18n_locale_en_overlays.mo.php';
$files[] = 'temporary/cache_i18n_locale_en_root.mo.php';
$files[] = 'temporary/cache_i18n_locale_en_scripts.mo.php';
$files[] = 'temporary/cache_i18n_locale_en_sections.mo.php';
$files[] = 'temporary/cache_i18n_locale_en_servers.mo.php';
$files[] = 'temporary/cache_i18n_locale_en_services.mo.php';
$files[] = 'temporary/cache_i18n_locale_en_shared.mo.php';
$files[] = 'temporary/cache_i18n_locale_en_skins.mo.php';
$files[] = 'temporary/cache_i18n_locale_en_smileys.mo.php';
$files[] = 'temporary/cache_i18n_locale_en_tables.mo.php';
$files[] = 'temporary/cache_i18n_locale_en_tools.mo.php';
$files[] = 'temporary/cache_i18n_locale_en_users.mo.php';
$files[] = 'temporary/cache_i18n_locale_en_versions.mo.php';

$files[] = 'temporary/cache_i18n_locale_fr_actions.mo.php';
$files[] = 'temporary/cache_i18n_locale_fr_agents.mo.php';
$files[] = 'temporary/cache_i18n_locale_fr_articles.mo.php';
$files[] = 'temporary/cache_i18n_locale_fr_behaviors.mo.php';
$files[] = 'temporary/cache_i18n_locale_fr_categories.mo.php';
$files[] = 'temporary/cache_i18n_locale_fr_codes.mo.php';
$files[] = 'temporary/cache_i18n_locale_fr_collections.mo.php';
$files[] = 'temporary/cache_i18n_locale_fr_comments.mo.php';
$files[] = 'temporary/cache_i18n_locale_fr_control.mo.php';
$files[] = 'temporary/cache_i18n_locale_fr_dates.mo.php';
$files[] = 'temporary/cache_i18n_locale_fr_decisions.mo.php';
$files[] = 'temporary/cache_i18n_locale_fr_feeds.mo.php';
$files[] = 'temporary/cache_i18n_locale_fr_files.mo.php';
$files[] = 'temporary/cache_i18n_locale_fr_forms.mo.php';
$files[] = 'temporary/cache_i18n_locale_fr_i18n.mo.php';
$files[] = 'temporary/cache_i18n_locale_fr_images.mo.php';
$files[] = 'temporary/cache_i18n_locale_fr_letters.mo.php';
$files[] = 'temporary/cache_i18n_locale_fr_links.mo.php';
$files[] = 'temporary/cache_i18n_locale_fr_locations.mo.php';
$files[] = 'temporary/cache_i18n_locale_fr_overlays.mo.php';
$files[] = 'temporary/cache_i18n_locale_fr_root.mo.php';
$files[] = 'temporary/cache_i18n_locale_fr_scripts.mo.php';
$files[] = 'temporary/cache_i18n_locale_fr_sections.mo.php';
$files[] = 'temporary/cache_i18n_locale_fr_servers.mo.php';
$files[] = 'temporary/cache_i18n_locale_fr_services.mo.php';
$files[] = 'temporary/cache_i18n_locale_fr_shared.mo.php';
$files[] = 'temporary/cache_i18n_locale_fr_skins.mo.php';
$files[] = 'temporary/cache_i18n_locale_fr_smileys.mo.php';
$files[] = 'temporary/cache_i18n_locale_fr_tables.mo.php';
$files[] = 'temporary/cache_i18n_locale_fr_tools.mo.php';
$files[] = 'temporary/cache_i18n_locale_fr_users.mo.php';
$files[] = 'temporary/cache_i18n_locale_fr_versions.mo.php';

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
		echo i18n::user('error')."<br />\n";
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
			echo i18n::user('label')."<br />\n";
		} else {
			$local['label_en'] = 'has been updated';
			$local['label_fr'] = 'a &eacute;t&eacute; mis &agrave; jour';
			echo $file.' '.i18n::user('label')."<br />\n";
		}

	}

	// next one
	$count += 1;
	Safe::set_time_limit(30);
}

// basic reporting
$local['label_en'] = 'files have been processed';
$local['label_fr'] = 'fichiers ont &eacute;t&eacute; trait&eacute;s';
echo $count.' '.i18n::user('label')."<br />\n";
?>