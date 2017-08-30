<?php

/**
 * Update files netgrabber alpha #6 to RC1
 *
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Upgrade from Netgrabber alpha #6 to RC1';
$local['label_fr'] = 'Mise à jour de Netgrabber alpha #6 vers RC1';
echo get_local('label')."<br />\n";

// the reference server to use
@include_once $context['path_to_root'].'scripts/parameters.include.php';
if(!isset($context['reference_server']) || !$context['reference_server'])
	$context['reference_server'] = 'www.yacs.fr';



$count = 0;
// files to delete, from root path
$delete = array();
$delete[] = 'included/browser/js_endpage/aa-jquery-3.1.1.min.js';
$delete[] = 'included/jscolor/jscolor.js';
$delete[] = 'included/jscolor/arrow.gif';
$delete[] = 'included/jscolor/cross.gif';
$delete[] = 'included/jscolor/hs.png';
$delete[] = 'included/jscolor/hv.png';

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
if(!is_callable('rmdirr')) {
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

if(rmdirr('included/browser/css/redmond')) {
    $local['label_en'] = 'suppressing of old jqueryui pictures';
    $local['label_fr'] = 'suppression des anciennes images jquery-ui';
    echo get_local('label')."<br />\n";
}

// files to fetch, from root path
$copy = array();

$copy[] = 'included/browser/js_endpage/aa-jquery-3.2.1.min.js';
$copy[] = 'included/browser/js_endpage/imagesloaded.pkgd.min.js';
$copy[] = 'included/browser/js_endpage/jquery.fitvids.js';
$copy[] = 'included/browser/js_endpage/masonry.pkgd.min.js';
$copy[] = 'included/browser/js_header/prefixfree.min.js';
$copy[] = 'included/jscolor/jscolor.min.js';
$copy[] = 'included/timepicker/i18n/jquery-ui-timepicker-fr.js';
$copy[] = 'included/timepicker/jquery-ui-timepicker-addon.min.css';
$copy[] = 'included/timepicker/jquery-ui-timepicker-addon.min.js';
$copy[] = 'shared/yacs.js';
$copy[] = 'skins/_reference/yacss.scss';
$copy[] = 'skins/_reference/variable.scss';
$copy[] = 'skins/mu/tune.scss';
$copy[] = 'included/browser/library_js_endpage.min.js';
$copy[] = 'included/browser/library_js_header.min.js';
$copy[] = 'tools/build_i18n.sh';
      
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

/**
 * Recursively copy files from one directory to another
 * 
 * @param String $src - Source of files being moved
 * @param String $dest - Destination of files being moved
 */
if(!is_callable('rcopy')) {
        function rcopy($src, $dest, &$count){

            // If source is not a directory stop processing
            if(!is_dir($src)) return false;

            // If the destination directory does not exist create it
            if(!is_dir($dest)) { 
                if(!mkdir($dest)) {
                    // If the destination directory could not be created stop processing
                    return false;
                }    
            }

            // Open the source directory to read in files
            $i = new DirectoryIterator($src);
            foreach($i as $f) {
                if($f->isFile()) {
                    copy($f->getRealPath(), "$dest/" . $f->getFilename());
                    $local['label_en'] = 'has been updated';
                    $local['label_fr'] = 'a &eacute;t&eacute; mis &agrave; jour';
		    echo $f->getPathname().' '.i18n::user('label')."<br />\n";
                    $count++;
                    
                } else if(!$f->isDot() && $f->isDir()) {
                    rcopy($f->getRealPath(), "$dest/$f");
                }
            }
            return true;
        }
}

//----------------
// basic reporting
$local['label_en'] = 'Copy jqueryui theme';
$local['label_fr'] = 'Copie du thème jqueryui';
echo i18n::user('label')."<br />\n";

$count = 0;
rcopy($context['path_to_root'].'scripts/staging/included/browser/css/redmond',$context['path_to_root'].'included/browser/css/redmond', $count);

// basic reporting
$local['label_en'] = 'files have been copied';
$local['label_fr'] = 'fichiers ont &eacute;t&eacute; copi&eacute;s';
echo $count.' '.i18n::user('label')."<br />\n";