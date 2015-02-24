<?php
/**
 * Update files frow lasares to netgrabber
 * Use this only on lasares stable archive
 *
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Upgrade from Lasares to Netgrabber';
$local['label_fr'] = 'Mise à jour de Lasares vers Netgrabber';
echo get_local('label')."<br />\n";
$count = 0;


// files to delete, from root path
$delete = array();
$delete[] = 'images/upload.php';
$delete[] = 'included/browser/library_js_header.min.js';
$delete[] = 'skins/layout.php';
$delete[] = 'included/browser/js_endpage/jquery-ui-1.8.14.custom.min.js';
$delete[] = 'included/browser/js_endpage/jquery.json.min.js';
$delete[] = 'included/browser/js_endpage/swfobject.js';
$delete[] = 'included/browser/js_header/jquery-1.6.2.min.js';
$delete[] = 'included/browser/js_header/reflection.js';

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
function rmdirr($dir) {
        
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

if(rmdirr('included/browser/css/redmond/images')) {
    $local['label_en'] = 'suppressing of old jqueryui pictures';
    $local['label_fr'] = 'suppression des anciennes images jquery-ui';
    echo get_local('label')."<br />\n";
}

if(rmdirr('included/tinymce')) {
    $local['label_en'] = 'suppress of old tinymce';
    $local['label_fr'] = 'suppression de l\'ancienne version de tinymce';
    echo get_local('label')."<br />\n";
}


// files to fetch, from root path
$copy = array();

$copy[] = 'control/htaccess/basic/.htaccess';
$copy[] = 'control/htaccess/options/.htaccess';
$copy[] = 'included/browser/css/redmond/jquery-ui-1.10.3.custom.min.css';
$copy[] = 'included/browser/js_header/readme.md';
$copy[] = 'included/browser/js_endpage/aa-jquery-1.10.1.min.js';
$copy[] = 'included/browser/js_endpage/autogrow.min.js';
$copy[] = 'included/browser/js_endpage/file.txt';
$copy[] = 'included/browser/js_endpage/imagesloaded.pkgd.min.js';
$copy[] = 'included/browser/js_endpage/jquery-migrate-1.2.1.js';
$copy[] = 'included/browser/js_endpage/jquery-ui-1.10.3.custom.min.js';
$copy[] = 'included/browser/js_endpage/jquery.json.min.js';
$copy[] = 'included/browser/js_endpage/jquery.livetwitter.js';
$copy[] = 'included/browser/js_endpage/jquery.sortelements.js';
$copy[] = 'included/browser/js_endpage/jquery.tipsy.js';
$copy[] = 'included/browser/js_endpage/masonry.pkgd.min.js';
$copy[] = 'included/browser/js_endpage/readme.md';
$copy[] = 'included/browser/js_endpage/reflection.js';
$copy[] = 'included/browser/js_endpage/swfobject.js';
$copy[] = 'included/browser/library_js_endpage.min.js';
$copy[] = 'layouts/layout_as_accordion/layout_as_accordion.css';
$copy[] = 'layouts/layout_as_accordion/layout_as_accordion.js';
$copy[] = 'layouts/layout_as_mosaic/jquery.infinitescroll.min.js';
$copy[] = 'layouts/layout_as_mosaic/ayout_as_mosaic.css';
$copy[] = 'layouts/layout_as_mosaic/ayout_as_mosaic.js';
$copy[] = 'layouts/layout_as_smartlist/layout_as_smartlist.css';
$copy[] = 'layouts/layout_as_tree_manager/layout_as_tree_manager.css';
$copy[] = 'layouts/layout_as_tree_manager/layout_as_tree_manager.js';
$copy[] = 'shared/yacs.js';
$copy[] = 'skins/_reference/yacs.css';
$copy[] = 'tools/build_i18n.bat';
$copy[] = 'tools/build_i18n.sh';
$copy[] = 'tools/check_i18n.bat';
$copy[] = 'tools/srcfiles.txt';
$copy[] = 'tools/update_i18n.bat';
$copy[] = '.gitignore';
$copy[] = 'readme.txt';
$copy[] = 'robots.txt';
$copy[] = 'included/securimage/AHGBold.ttf';
$copy[] = 'included/securimage/backgrounds/bg3.jpg';
$copy[] = 'included/securimage/backgrounds/bg4.jpg';
$copy[] = 'included/securimage/backgrounds/bg5.jpg';
$copy[] = 'included/securimage/backgrounds/bg6.png';
$copy[] = 'included/securimage/captcha.html';
$copy[] = 'included/securimage/database/index.html';
$copy[] = 'included/securimage/database/securimage.sq3';
$copy[] = 'included/securimage/example_form.ajax.php';
$copy[] = 'included/securimage/example_form.php';
$copy[] = 'included/securimage/images/audio_icon.png';
$copy[] = 'included/securimage/images/refresh.png';
$copy[] = 'included/securimage/LICENSE.txt';
$copy[] = 'included/securimage/README.FONT.txt';
$copy[] = 'included/securimage/README.txt';
$copy[] = 'included/securimage/securimage.php';
$copy[] = 'included/securimage/securimage_play.php';
$copy[] = 'included/securimage/securimage_play.swf';
$copy[] = 'included/securimage/securimage_show.php';
$copy[] = 'included/securimage/WavFile.php';
$copy[] = 'included/securimage/words/words.txt';
$copy[] = 'included/timepicker/i18n/jquery-ui-timepicker-fr.js';
$copy[] = 'included/timepicker/i18n/jquery.ui.datepicker-fr.js';
$copy[] = 'included/timepicker/jquery-ui-timepicker-addon.min.css';
$copy[] = 'included/timepicker/jquery-ui-timepicker-addon.min.js';
$copy[] = 'included/tiny_mce/jquery.tinymce.min.js';
$copy[] = 'included/tiny_mce/langs/fr.js';
$copy[] = 'included/tiny_mce/langs/fr_FR.js';
$copy[] = 'included/tiny_mce/langs/readme.md';
$copy[] = 'included/tiny_mce/license.txt';
$copy[] = 'included/tiny_mce/plugins/advlist/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/anchor/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/autolink/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/autoresize/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/autosave/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/bbcode/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/charmap/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/code/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/compat3x/editable_selects.js';
$copy[] = 'included/tiny_mce/plugins/compat3x/form_utils.js';
$copy[] = 'included/tiny_mce/plugins/compat3x/mctabs.js';
$copy[] = 'included/tiny_mce/plugins/compat3x/tiny_mce_popup.js';
$copy[] = 'included/tiny_mce/plugins/compat3x/validate.js';
$copy[] = 'included/tiny_mce/plugins/contextmenu/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/directionality/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/emoticons/img/smiley-cool.gif';
$copy[] = 'included/tiny_mce/plugins/emoticons/img/smiley-cry.gif';
$copy[] = 'included/tiny_mce/plugins/emoticons/img/smiley-embarassed.gif';
$copy[] = 'included/tiny_mce/plugins/emoticons/img/smiley-foot-in-mouth.gif';
$copy[] = 'included/tiny_mce/plugins/emoticons/img/smiley-frown.gif';
$copy[] = 'included/tiny_mce/plugins/emoticons/img/smiley-innocent.gif';
$copy[] = 'included/tiny_mce/plugins/emoticons/img/smiley-kiss.gif';
$copy[] = 'included/tiny_mce/plugins/emoticons/img/smiley-laughing.gif';
$copy[] = 'included/tiny_mce/plugins/emoticons/img/smiley-money-mouth.gif';
$copy[] = 'included/tiny_mce/plugins/emoticons/img/smiley-sealed.gif';
$copy[] = 'included/tiny_mce/plugins/emoticons/img/smiley-smile.gif';
$copy[] = 'included/tiny_mce/plugins/emoticons/img/smiley-surprised.gif';
$copy[] = 'included/tiny_mce/plugins/emoticons/img/smiley-tongue-out.gif';
$copy[] = 'included/tiny_mce/plugins/emoticons/img/smiley-undecided.gif';
$copy[] = 'included/tiny_mce/plugins/emoticons/img/smiley-wink.gif';
$copy[] = 'included/tiny_mce/plugins/emoticons/img/smiley-yell.gif';
$copy[] = 'included/tiny_mce/plugins/emoticons/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/example/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/example_dependency/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/fullpage/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/fullscreen/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/hr/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/image/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/insertdatetime/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/layer/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/legacyoutput/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/link/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/lists/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/media/moxieplayer.swf';
$copy[] = 'included/tiny_mce/plugins/media/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/nonbreaking/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/noneditable/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/pagebreak/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/paste/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/preview/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/print/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/save/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/searchreplace/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/spellchecker/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/tabfocus/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/table/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/template/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/textcolor/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/visualblocks/css/visualblocks.css';
$copy[] = 'included/tiny_mce/plugins/visualblocks/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/visualchars/plugin.min.js';
$copy[] = 'included/tiny_mce/plugins/wordcount/plugin.min.js';
$copy[] = 'included/tiny_mce/skins/lightgray/content.inline.min.css';
$copy[] = 'included/tiny_mce/skins/lightgray/content.min.css';
$copy[] = 'included/tiny_mce/skins/lightgray/fonts/icomoon-small.eot';
$copy[] = 'included/tiny_mce/skins/lightgray/fonts/icomoon-small.svg';
$copy[] = 'included/tiny_mce/skins/lightgray/fonts/icomoon-small.ttf';
$copy[] = 'included/tiny_mce/skins/lightgray/fonts/icomoon-small.woff';
$copy[] = 'included/tiny_mce/skins/lightgray/fonts/icomoon.eot';
$copy[] = 'included/tiny_mce/skins/lightgray/fonts/icomoon.svg';
$copy[] = 'included/tiny_mce/skins/lightgray/fonts/icomoon.ttf';
$copy[] = 'included/tiny_mce/skins/lightgray/fonts/icomoon.woff';
$copy[] = 'included/tiny_mce/skins/lightgray/fonts/readme.md';
$copy[] = 'included/tiny_mce/skins/lightgray/img/anchor.gif';
$copy[] = 'included/tiny_mce/skins/lightgray/img/loader.gif';
$copy[] = 'included/tiny_mce/skins/lightgray/img/object.gif';
$copy[] = 'included/tiny_mce/skins/lightgray/img/trans.gif';
$copy[] = 'included/tiny_mce/skins/lightgray/img/wline.gif';
$copy[] = 'included/tiny_mce/skins/lightgray/skin.ie7.min.css';
$copy[] = 'included/tiny_mce/skins/lightgray/skin.min.css';
$copy[] = 'included/tiny_mce/themes/modern/theme.min.js';
$copy[] = 'included/tiny_mce/tinymce.min.js';
$copy[] = 'included/browser/css/redmond/images/animated-overlay.gif';
$copy[] = 'included/browser/css/redmond/images/ui-bg_flat_0_aaaaaa_40x100.png';
$copy[] = 'included/browser/css/redmond/images/ui-bg_flat_55_fbec88_40x100.png';
$copy[] = 'included/browser/css/redmond/images/ui-bg_glass_75_d0e5f5_1x400.png';
$copy[] = 'included/browser/css/redmond/images/ui-bg_glass_85_dfeffc_1x400.png';
$copy[] = 'included/browser/css/redmond/images/ui-bg_glass_95_fef1ec_1x400.png';
$copy[] = 'included/browser/css/redmond/images/ui-bg_gloss-wave_55_5c9ccc_500x100.png';
$copy[] = 'included/browser/css/redmond/images/ui-bg_inset-hard_100_f5f8f9_1x100.png';
$copy[] = 'included/browser/css/redmond/images/ui-bg_inset-hard_100_fcfdfd_1x100.png';
$copy[] = 'included/browser/css/redmond/images/ui-icons_217bc0_256x240.png';
$copy[] = 'included/browser/css/redmond/images/ui-icons_2e83ff_256x240.png';
$copy[] = 'included/browser/css/redmond/images/ui-icons_469bdd_256x240.png';
$copy[] = 'included/browser/css/redmond/images/ui-icons_6da8d5_256x240.png';
$copy[] = 'included/browser/css/redmond/images/ui-icons_cd0a0a_256x240.png';
$copy[] = 'included/browser/css/redmond/images/ui-icons_d8e7f3_256x240.png';
$copy[] = 'included/browser/css/redmond/images/ui-icons_f9bd01_256x240.png';

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

