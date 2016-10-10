<?php

/**
 * Update files netgrabber alpha v3 to v4
 *
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Upgrade from Netgrabber alpha #3';
$local['label_fr'] = 'Mise à jour de Netgrabber alpha #3';
echo get_local('label')."<br />\n";

// the reference server to use
@include_once $context['path_to_root'].'scripts/parameters.include.php';
if(!isset($context['reference_server']) || !$context['reference_server'])
	$context['reference_server'] = 'www.yacs.fr';

$count = 0;
// files to delete, from root path
$delete = array();
$delete[] = 'articles/layout_articles_as_daily.php';
$delete[] = 'articles/layout_articles_as_hardboiled.php';
$delete[] = 'articles/layout_articles_as_jive.php';
$delete[] = 'articles/layout_articles_as_last.php';
$delete[] = 'articles/layout_articles_as_newspaper.php';
$delete[] = 'articles/layout_articles_as_titles.php';
$delete[] = 'articles/layout_articles_as_yabb.php';
$delete[] = 'articles/layout_articles_as_yahoo.php';

$delete[] = 'categories/layout_categories_as_titles.php';
$delete[] = 'categories/layout_categories_as_yahoo.php';

$delete[] = 'included/jssor/js/jssor.js';
$delete[] = 'included/jssor/js/jssor.player.ytiframe.js';
$delete[] = 'included/jssor/js/jssor.player.ytiframe.min.js';
$delete[] = 'included/jssor/js/jssor.slider.js';
$delete[] = 'included/jssor/js/jssor.slider.mini.js';
$delete[] = 'included/jssor/js/jssor.css';

$delete[] = 'sections/layout_sections_as_jive.php';
$delete[] = 'sections/layout_sections_as_titles.php';
$delete[] = 'sections/layout_sections_as_yabb.php';
$delete[] = 'sections/layout_sections_as_yahoo.php';

$delete[] = 'shared/codes.php';

$delete[] = 'skins/_reference/ajax/overlay_background.png';

$delete[] = 'skins/_reference/yacs.css';

$delete[] = 'skins/starterfive/css/knacss-garni.css';
$delete[] = 'skins/starterfive/css/main-bottom.css';
$delete[] = 'skins/starterfive/css/main-top.css';
$delete[] = 'skins/starterfive/css/normalize.css';

$delete[] = 'skins/layout_home_articles_as_daily.jpg';
$delete[] = 'skins/layout_home_articles_as_daily.php';
$delete[] = 'skins/layout_home_articles_as_hardboiled.jpg';
$delete[] = 'skins/layout_home_articles_as_hardboiled.php';
$delete[] = 'skins/layout_home_articles_as_newspaper.jpg';
$delete[] = 'skins/layout_home_articles_as_newspaper.php';





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

/////////////////////////////////////////////

// files to fetch, from root path
$copy = array();

$copy[] = 'i18n/locale/en/agents.mo';
$copy[] = 'i18n/locale/en/articles.mo';
$copy[] = 'i18n/locale/en/canvas.mo';
$copy[] = 'i18n/locale/en/categories.mo';
$copy[] = 'i18n/locale/en/codes.mo';
$copy[] = 'i18n/locale/en/comments.mo';
$copy[] = 'i18n/locale/en/control.mo';
$copy[] = 'i18n/locale/en/dates.mo';
$copy[] = 'i18n/locale/en/feeds.mo';
$copy[] = 'i18n/locale/en/files.mo';
$copy[] = 'i18n/locale/en/help.mo';
$copy[] = 'i18n/locale/en/images.mo';
$copy[] = 'i18n/locale/en/layouts.mo';
$copy[] = 'i18n/locale/en/letters.mo';
$copy[] = 'i18n/locale/en/links.mo';
$copy[] = 'i18n/locale/en/locations.mo';
$copy[] = 'i18n/locale/en/overlays.mo';
$copy[] = 'i18n/locale/en/root.mo';
$copy[] = 'i18n/locale/en/scripts.mo';
$copy[] = 'i18n/locale/en/sections.mo';
$copy[] = 'i18n/locale/en/servers.mo';
$copy[] = 'i18n/locale/en/services.mo';
$copy[] = 'i18n/locale/en/shared.mo';
$copy[] = 'i18n/locale/en/skins.mo';
$copy[] = 'i18n/locale/en/tables.mo';
$copy[] = 'i18n/locale/en/tools.mo';
$copy[] = 'i18n/locale/en/users.mo';

$copy[] = 'i18n/locale/fr/agents.mo';
$copy[] = 'i18n/locale/fr/articles.mo';
$copy[] = 'i18n/locale/fr/canvas.mo';
$copy[] = 'i18n/locale/fr/categories.mo';
$copy[] = 'i18n/locale/fr/codes.mo';
$copy[] = 'i18n/locale/fr/comments.mo';
$copy[] = 'i18n/locale/fr/control.mo';
$copy[] = 'i18n/locale/fr/dates.mo';
$copy[] = 'i18n/locale/fr/feeds.mo';
$copy[] = 'i18n/locale/fr/files.mo';
$copy[] = 'i18n/locale/fr/help.mo';
$copy[] = 'i18n/locale/fr/images.mo';
$copy[] = 'i18n/locale/fr/layouts.mo';
$copy[] = 'i18n/locale/fr/letters.mo';
$copy[] = 'i18n/locale/fr/links.mo';
$copy[] = 'i18n/locale/fr/locations.mo';
$copy[] = 'i18n/locale/fr/overlays.mo';
$copy[] = 'i18n/locale/fr/root.mo';
$copy[] = 'i18n/locale/fr/scripts.mo';
$copy[] = 'i18n/locale/fr/sections.mo';
$copy[] = 'i18n/locale/fr/servers.mo';
$copy[] = 'i18n/locale/fr/services.mo';
$copy[] = 'i18n/locale/fr/shared.mo';
$copy[] = 'i18n/locale/fr/skins.mo';
$copy[] = 'i18n/locale/fr/tables.mo';
$copy[] = 'i18n/locale/fr/tools.mo';
$copy[] = 'i18n/locale/fr/users.mo';

$copy[] = 'included/jscolor/jscolor.js';

$copy[] = 'included/jssor/css/jssor.css';
$copy[] = 'included/jssor/js/jssor.slider.min.js';

$copy[] = 'included/less/docs/docs.md';
$copy[] = 'included/less/LICENCE';
$copy[] = 'included/less/README.md';
$copy[] = 'included/less/lessc.inc.php';

$copy[] = 'layouts/layout_as_columns/layout_as_columns.css';
$copy[] = 'layouts/layout_as_daily/layout_as_daily.css';
$copy[] = 'layouts/layout_as_hardboiled/layout_as_hardboiled.css';
$copy[] = 'layouts/layout_as_jive/layout_as_jive.css';
$copy[] = 'layouts/layout_as_last/layout_as_last.css';
$copy[] = 'layouts/layout_as_newpaper/layout_as_newpaper.css';
$copy[] = 'layouts/layout_as_smartlist/layout_as_smartlist.css';
$copy[] = 'layouts/layout_as_titles/layout_as_titles.css';
$copy[] = 'layouts/layout_as_yabb/layout_as_yabb.css';

$copy[] = 'shared/yacs.js';

$copy[] = 'skins/_reference/layouts/accordion_minus.png';
$copy[] = 'skins/_reference/layouts/accordion_plus.png';

$copy[] = 'skins/_reference/yacss.less';

$copy[] = 'skins/starterfive/starterfive.css';

$copy[] = '.gitignore';


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


////////////////////////:

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

// copy jssor library

// basic reporting
$local['label_en'] = 'Copy Font Awesome files';
$local['label_fr'] = 'Copie des fichers Font Awesome';
echo i18n::user('label')."<br />\n";


$count = 0;
rcopy($context['path_to_root'].'scripts/staging/included/font_awesome/fonts',$context['path_to_root'].'included/font_awesome/fonts', $count);
rcopy($context['path_to_root'].'scripts/staging/included/font_awesome/less',$context['path_to_root'].'included/font_awesome/less', $count);

// basic reporting
$local['label_en'] = 'files have been copied';
$local['label_fr'] = 'fichiers ont &eacute;t&eacute; copi&eacute;s';
echo $count.' '.i18n::user('label')."<br />\n";

// basic reporting
$local['label_en'] = 'Copy KNACSS lib';
$local['label_fr'] = 'Copie de la biblio KNACSS';
echo i18n::user('label')."<br />\n";


$count = 0;
rcopy($context['path_to_root'].'scripts/staging/included/knacss',$context['path_to_root'].'included/font_awesome/knacss', $count);

// basic reporting
$local['label_en'] = 'files have been copied';
$local['label_fr'] = 'fichiers ont &eacute;t&eacute; copi&eacute;s';
echo $count.' '.i18n::user('label')."<br />\n";


