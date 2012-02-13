<?php
/**
 * update reference skins
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Update reference skins';
$local['label_fr'] = 'Mise &agrave; jour compl&eacute;mentaire';
echo i18n::user('label')."<br />\n";

// the reference server to use
@include_once $context['path_to_root'].'scripts/parameters.include.php';
if(!isset($context['reference_server']) || !$context['reference_server'])
	$context['reference_server'] = 'www.yacs.fr';

// files to fetch, from root path
$files = array();
$files[] = 'articles/article.dtd';
$files[] = 'control/htaccess/options/.htaccess';
$files[] = 'included/browser/js_endpage/jquery.tipsy.js';
$files[] = 'included/browser/js_header/swfobject.js';
$files[] = 'included/browser/library_js_endpage.min.js';
$files[] = 'included/browser/library_js_header.min.js';
$files[] = 'included/fckeditor/editor/css/behaviors/disablehandles.htc';
$files[] = 'included/fckeditor/editor/css/behaviors/showtableborders.htc';
$files[] = 'readme.txt';
$files[] = 'services/xml-rpc/blogger.getUsersBlogs.response.2.xml';
$files[] = 'shared/yacs.js';
$files[] = 'skins/_reference/yacs.css';
$files[] = 'skins/_reference/ajax/ajax_spinner_black.gif';
$files[] = 'skins/_reference/tools/tipsy.gif';
$files[] = 'skins/boxesandarrows/boxesandarrows.css';
$files[] = 'skins/digital/digital.css';
$files[] = 'skins/joi/joi.css';
$files[] = 'skins/skeleton/skeleton.css';
$files[] = 'tools/transform.xsl';

// process every file
$count = 0;
foreach($files as $file) {

	// content of the updated file
	$content = '';

	// expected location in staging repository
	$local_reference = $context['path_to_root'].'scripts/staging/'.$file;

	// don't execute PHP scripts, just get them
	if(preg_match('/\.php$/i', $file))
		$remote_reference = 'http://'.$context['reference_server'].'/scripts/fetch.php?script='.urlencode($file);

	// fetch other files from remote reference store
	else
		$remote_reference = 'http://'.$context['reference_server'].'/scripts/reference/'.$file;

	// get the file locally
	if(file_exists($local_reference))
		$content = Safe::file_get_contents($local_reference);

	// or get the file from reference server
	elseif(($content = http::proceed($remote_reference)) === FALSE) {
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

$local['label_en'] = 'After the end of the upgrade, please rebuild the .htaccess file from the Control Panel.';
$local['label_fr'] = 'Lorsque la mise &agrave; jour sera termin&eacute;e, merci de reconstruire le fichier .htaccess &agrave; partir du Panneau de Configuration.';
echo '<p style="color: red;">'.i18n::user('label')."</p>\n";


?>