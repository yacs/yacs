<?php
/**
 * fckeditor update
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Update FCKEditor';
$local['label_fr'] = 'Mise &agrave; jour de FCKEditor';
echo i18n::user('label')."<br />\n";

// the reference server to use
@include_once $context['path_to_root'].'scripts/parameters.include.php';
if(!isset($context['reference_server']) || !$context['reference_server'])
	$context['reference_server'] = 'www.yetanothercommunitysystem.com';

// files to fetch, from root path
$files = array();
$files[] = 'included/fckeditor/fckconfig.js';
$files[] = 'included/fckeditor/fckeditor.js';
$files[] = 'included/fckeditor/fckeditor.php';
$files[] = 'included/fckeditor/fckeditor_php4.php';
$files[] = 'included/fckeditor/fckeditor_php5.php';
$files[] = 'included/fckeditor/fckpackager.xml';
$files[] = 'included/fckeditor/fckstyles.xml';
$files[] = 'included/fckeditor/fcktemplates.xml';
$files[] = 'included/fckeditor/license.txt';
$files[] = 'included/fckeditor/editor/fckdebug.html';
$files[] = 'included/fckeditor/editor/fckdialog.html';
$files[] = 'included/fckeditor/editor/fckeditor.html';
$files[] = 'included/fckeditor/editor/fckeditor.original.html';
$files[] = 'included/fckeditor/editor/css/fck_editorarea.css';
$files[] = 'included/fckeditor/editor/css/fck_internal.css';
$files[] = 'included/fckeditor/editor/css/fck_showtableborders_gecko.css';
$files[] = 'included/fckeditor/editor/css/images/block_address.png';
$files[] = 'included/fckeditor/editor/css/images/block_blockquote.png';
$files[] = 'included/fckeditor/editor/css/images/block_div.png';
$files[] = 'included/fckeditor/editor/css/images/block_h1.png';
$files[] = 'included/fckeditor/editor/css/images/block_h2.png';
$files[] = 'included/fckeditor/editor/css/images/block_h3.png';
$files[] = 'included/fckeditor/editor/css/images/block_h4.png';
$files[] = 'included/fckeditor/editor/css/images/block_h5.png';
$files[] = 'included/fckeditor/editor/css/images/block_h6.png';
$files[] = 'included/fckeditor/editor/css/images/block_p.png';
$files[] = 'included/fckeditor/editor/css/images/block_pre.png';
$files[] = 'included/fckeditor/editor/css/images/fck_plugin.gif';
$files[] = 'included/fckeditor/editor/dialog/fck_about.html';
$files[] = 'included/fckeditor/editor/dialog/fck_anchor.html';
$files[] = 'included/fckeditor/editor/dialog/fck_button.html';
$files[] = 'included/fckeditor/editor/dialog/fck_checkbox.html';
$files[] = 'included/fckeditor/editor/dialog/fck_colorselector.html';
$files[] = 'included/fckeditor/editor/dialog/fck_docprops.html';
$files[] = 'included/fckeditor/editor/dialog/fck_flash.html';
$files[] = 'included/fckeditor/editor/dialog/fck_form.html';
$files[] = 'included/fckeditor/editor/dialog/fck_hiddenfield.html';
$files[] = 'included/fckeditor/editor/dialog/fck_image.html';
$files[] = 'included/fckeditor/editor/dialog/fck_link.html';
$files[] = 'included/fckeditor/editor/dialog/fck_listprop.html';
$files[] = 'included/fckeditor/editor/dialog/fck_paste.html';
$files[] = 'included/fckeditor/editor/dialog/fck_radiobutton.html';
$files[] = 'included/fckeditor/editor/dialog/fck_replace.html';
$files[] = 'included/fckeditor/editor/dialog/fck_select.html';
$files[] = 'included/fckeditor/editor/dialog/fck_smiley.html';
$files[] = 'included/fckeditor/editor/dialog/fck_source.html';
$files[] = 'included/fckeditor/editor/dialog/fck_specialchar.html';
$files[] = 'included/fckeditor/editor/dialog/fck_spellerpages.html';
$files[] = 'included/fckeditor/editor/dialog/fck_table.html';
$files[] = 'included/fckeditor/editor/dialog/fck_tablecell.html';
$files[] = 'included/fckeditor/editor/dialog/fck_template.html';
$files[] = 'included/fckeditor/editor/dialog/fck_textarea.html';
$files[] = 'included/fckeditor/editor/dialog/fck_textfield.html';
$files[] = 'included/fckeditor/editor/dialog/common/fck_dialog_common.css';
$files[] = 'included/fckeditor/editor/dialog/common/fck_dialog_common.js';
$files[] = 'included/fckeditor/editor/dialog/fck_about/sponsors/spellchecker_net.gif';
$files[] = 'included/fckeditor/editor/dialog/fck_docprops/fck_document_preview.html';
$files[] = 'included/fckeditor/editor/dialog/fck_flash/fck_flash.js';
$files[] = 'included/fckeditor/editor/dialog/fck_flash/fck_flash_preview.html';
$files[] = 'included/fckeditor/editor/dialog/fck_image/fck_image.js';
$files[] = 'included/fckeditor/editor/dialog/fck_image/fck_image_preview.html';
$files[] = 'included/fckeditor/editor/dialog/fck_link/fck_link.js';
$files[] = 'included/fckeditor/editor/dialog/fck_select/fck_select.js';
$files[] = 'included/fckeditor/editor/dialog/fck_spellerpages/spellerpages/blank.html';
$files[] = 'included/fckeditor/editor/dialog/fck_spellerpages/spellerpages/controls.html';
$files[] = 'included/fckeditor/editor/dialog/fck_spellerpages/spellerpages/spellchecker.html';
$files[] = 'included/fckeditor/editor/dialog/fck_spellerpages/spellerpages/spellChecker.js';
$files[] = 'included/fckeditor/editor/dialog/fck_spellerpages/spellerpages/spellerStyle.css';
$files[] = 'included/fckeditor/editor/dialog/fck_spellerpages/spellerpages/server-scripts/spellchecker.php';
$files[] = 'included/fckeditor/editor/js/fckadobeair.js';
$files[] = 'included/fckeditor/editor/js/fckeditorcode_gecko.js';
$files[] = 'included/fckeditor/editor/js/fckeditorcode_ie.js';
$files[] = 'included/fckeditor/editor/lang/en.js';
$files[] = 'included/fckeditor/editor/lang/fr.js';
$files[] = 'included/fckeditor/editor/plugins/autogrow/fckplugin.js';
$files[] = 'included/fckeditor/editor/plugins/dragresizetable/fckplugin.js';
$files[] = 'included/fckeditor/editor/plugins/simplecommands/fckplugin.js';
$files[] = 'included/fckeditor/editor/plugins/tablecommands/fckplugin.js';
$files[] = 'included/fckeditor/editor/skins/_fckviewstrips.html';
$files[] = 'included/fckeditor/editor/skins/default/fck_dialog.css';
$files[] = 'included/fckeditor/editor/skins/default/fck_dialog_ie6.js';
$files[] = 'included/fckeditor/editor/skins/default/fck_editor.css';
$files[] = 'included/fckeditor/editor/skins/default/fck_strip.gif';
$files[] = 'included/fckeditor/editor/skins/default/images/dialog.sides.gif';
$files[] = 'included/fckeditor/editor/skins/default/images/dialog.sides.png';
$files[] = 'included/fckeditor/editor/skins/default/images/dialog.sides.rtl.png';
$files[] = 'included/fckeditor/editor/skins/default/images/sprites.gif';
$files[] = 'included/fckeditor/editor/skins/default/images/sprites.png';
$files[] = 'included/fckeditor/editor/skins/silver/fck_dialog.css';
$files[] = 'included/fckeditor/editor/skins/silver/fck_dialog_ie6.js';
$files[] = 'included/fckeditor/editor/skins/silver/fck_editor.css';
$files[] = 'included/fckeditor/editor/skins/silver/fck_strip.gif';
$files[] = 'included/fckeditor/editor/skins/silver/images/dialog.sides.gif';
$files[] = 'included/fckeditor/editor/skins/silver/images/dialog.sides.png';
$files[] = 'included/fckeditor/editor/skins/silver/images/dialog.sides.rtl.png';
$files[] = 'included/fckeditor/editor/skins/silver/images/sprites.gif';
$files[] = 'included/fckeditor/editor/skins/silver/images/sprites.png';

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
		$content = Safe::file_get_contents($local_reference);

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

// files to delete, from root path
$files = array();
$files[] = 'included/fckeditor/htaccess.txt';
$files[] = 'included/fckeditor/editor/dialog/fck_find.html';
$files[] = 'included/fckeditor/editor/dialog/common/fcknumericfield.htc';
$files[] = 'included/fckeditor/editor/dialog/common/moz-bindings.xml';

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
echo $count.' '.i18n::user('label')."<br />\n";
?>