<?php
/**
 * Updated files netgrabber alpha v1 to v2
 *
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Upgrade from Netgrabber alpha #1';
$local['label_fr'] = 'Mise à jour de Netgrabber alpha #2';
echo get_local('label')."<br />\n";


// the reference server to use
@include_once $context['path_to_root'].'scripts/parameters.include.php';
if(!isset($context['reference_server']) || !$context['reference_server'])
	$context['reference_server'] = 'www.yacs.fr';


$count = 0;
// files to delete, from root path
$delete = array();
$delete[] = 'i18n/locale/en/action.mo';
$delete[] = 'i18n/locale/fr/action.mo';
$delete[] = 'i18n/locale/en/collection.mo';
$delete[] = 'i18n/locale/fr/collection.mo';
$delete[] = 'i18n/locale/en/forms.mo';
$delete[] = 'i18n/locale/fr/forms.mo';
$delete[] = 'included/jscalendar/calendar-setup.js.jsmin';
$delete[] = 'included/jscalendar/calendar.js.jsmin';
$delete[] = 'overlays/form.php';
$delete[] = 'shared/codes.php';
$delete[] = 'skins/starterfive/js/modernizr-2.6.2.min.js';
$delete[] = 'tools/ajax_upload.php';

// process every file
foreach($delete as $file) {

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
$local['label_en'] = 'files have been deleted';
$local['label_fr'] = 'fichiers ont &eacute;t&eacute; supprim&eacute;s';
echo $count.' '.get_local('label')."<br />\n";

/**
 * Delete a file, or a folder and its contents (recursive algorithm)
 *
 */
if(!is_callable(rmdirr)) {
    function rmdirr($dir) {
        global $context;

        $dir = $context['path_to_root'].$dir;
        // Sanity check
        if (!file_exists($dir)) {
            return false;
        }

        $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it,
                     RecursiveIteratorIterator::CHILD_FIRST);
        foreach($files as $file) {
            if ($file->isDir()){
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }
}

/*if(rmdirr('included/browser/css/redmond/images')) {
    $local['label_en'] = 'suppressing of old jqueryui pictures';
    $local['label_fr'] = 'suppression des anciennes images jquery-ui';
    echo get_local('label')."<br />\n";
}

if(rmdirr('included/tinymce')) {
    $local['label_en'] = 'suppress of old tinymce';
    $local['label_fr'] = 'suppression de l\'ancienne version de tinymce';
    echo get_local('label')."<br />\n";
}*/


// files to fetch, from root path
$copy = array();

$copy[] = 'codes/unused/readme.md';
$copy[] = 'included/jscalendar/calendar-setup.min.js';
$copy[] = 'included/jscalendar/calendar.min.js';
$copy[] = 'shared/yacs.js';
$copy[] = 'skins/_reference/yacs.css';
$copy[] = 'tools/build_i18n.bat';
$copy[] = '.gitignore';
$copy[] = 'skins/starterfive/css/knacss-garni.css';
$copy[] = 'skins/starterfive/css/main-bottom.css';
$copy[] = 'skins/starterfive/css/normalize.css';
$copy[] = 'skins/starterfive/css/main-top.css';
$copy[] = 'skins/starterfive/js/modernizr-2.8.3.min.js';

// process every file
$count = 0;
foreach($copy as $file) {

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
$local['label_en'] = 'files have been copied';
$local['label_fr'] = 'fichiers ont &eacute;t&eacute; copi&eacute;s';
echo $count.' '.i18n::user('label')."<br />\n";

