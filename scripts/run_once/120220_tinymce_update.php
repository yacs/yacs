<?php
/**
 * update tinymce editor
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Update TinyMCE editor';
$local['label_fr'] = 'Mise &agrave; jour de TinyMCE';
echo i18n::user('label')."<br />\n";

// the reference server to use
@include_once $context['path_to_root'].'scripts/parameters.include.php';
if(!isset($context['reference_server']) || !$context['reference_server'])
	$context['reference_server'] = 'www.yacs.fr';

// files to fetch, from root path
$files = array();
$files[] = 'included/tiny_mce/langs/en.js';
$files[] = 'included/tiny_mce/langs/fr.js';
$files[] = 'included/tiny_mce/plugins/advhr/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/advhr/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/advhr/langs/en_dlg.js';
$files[] = 'included/tiny_mce/plugins/advhr/langs/fr_dlg.js';
$files[] = 'included/tiny_mce/plugins/advhr/rule.htm';
$files[] = 'included/tiny_mce/plugins/advimage/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/advimage/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/advimage/image.htm';
$files[] = 'included/tiny_mce/plugins/advimage/js/image.js';
$files[] = 'included/tiny_mce/plugins/advimage/langs/en_dlg.js';
$files[] = 'included/tiny_mce/plugins/advimage/langs/fr_dlg.js';
$files[] = 'included/tiny_mce/plugins/advlink/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/advlink/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/advlink/js/advlink.js';
$files[] = 'included/tiny_mce/plugins/advlink/langs/en_dlg.js';
$files[] = 'included/tiny_mce/plugins/advlink/langs/fr_dlg.js';
$files[] = 'included/tiny_mce/plugins/advlink/link.htm';
$files[] = 'included/tiny_mce/plugins/advlist/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/advlist/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/autolink/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/autolink/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/autoresize/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/autoresize/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/autosave/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/autosave/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/autosave/langs/en.js';
$files[] = 'included/tiny_mce/plugins/bbcode/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/bbcode/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/contextmenu/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/contextmenu/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/directionality/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/directionality/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/emotions/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/emotions/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/emotions/emotions.htm';
$files[] = 'included/tiny_mce/plugins/emotions/img/smiley-foot-in-mouth.gif';
$files[] = 'included/tiny_mce/plugins/emotions/img/smiley-laughing.gif';
$files[] = 'included/tiny_mce/plugins/emotions/img/smiley-sealed.gif';
$files[] = 'included/tiny_mce/plugins/emotions/img/smiley-smile.gif';
$files[] = 'included/tiny_mce/plugins/emotions/img/smiley-surprised.gif';
$files[] = 'included/tiny_mce/plugins/emotions/img/smiley-wink.gif';
$files[] = 'included/tiny_mce/plugins/emotions/js/emotions.js';
$files[] = 'included/tiny_mce/plugins/emotions/langs/en_dlg.js';
$files[] = 'included/tiny_mce/plugins/emotions/langs/fr_dlg.js';
$files[] = 'included/tiny_mce/plugins/example/dialog.htm';
$files[] = 'included/tiny_mce/plugins/example/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/example/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/example/img/example.gif';
$files[] = 'included/tiny_mce/plugins/example/js/dialog.js';
$files[] = 'included/tiny_mce/plugins/example/langs/en.js';
$files[] = 'included/tiny_mce/plugins/example/langs/en_dlg.js';
$files[] = 'included/tiny_mce/plugins/example_dependency/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/example_dependency/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/fullpage/css/fullpage.css';
$files[] = 'included/tiny_mce/plugins/fullpage/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/fullpage/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/fullpage/fullpage.htm';
$files[] = 'included/tiny_mce/plugins/fullpage/js/fullpage.js';
$files[] = 'included/tiny_mce/plugins/fullpage/langs/en_dlg.js';
$files[] = 'included/tiny_mce/plugins/fullpage/langs/fr_dlg.js';
$files[] = 'included/tiny_mce/plugins/fullscreen/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/fullscreen/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/fullscreen/fullscreen.htm';
$files[] = 'included/tiny_mce/plugins/iespell/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/iespell/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/inlinepopups/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/inlinepopups/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/inlinepopups/skins/clearlooks2/img/alert.gif';
$files[] = 'included/tiny_mce/plugins/inlinepopups/skins/clearlooks2/img/button.gif';
$files[] = 'included/tiny_mce/plugins/inlinepopups/skins/clearlooks2/img/confirm.gif';
$files[] = 'included/tiny_mce/plugins/inlinepopups/skins/clearlooks2/img/corners.gif';
$files[] = 'included/tiny_mce/plugins/inlinepopups/skins/clearlooks2/img/vertical.gif';
$files[] = 'included/tiny_mce/plugins/inlinepopups/skins/clearlooks2/window.css';
$files[] = 'included/tiny_mce/plugins/insertdatetime/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/insertdatetime/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/layer/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/layer/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/legacyoutput/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/legacyoutput/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/lists/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/lists/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/media/css/media.css';
$files[] = 'included/tiny_mce/plugins/media/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/media/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/media/js/embed.js';
$files[] = 'included/tiny_mce/plugins/media/js/media.js';
$files[] = 'included/tiny_mce/plugins/media/langs/en_dlg.js';
$files[] = 'included/tiny_mce/plugins/media/langs/fr_dlg.js';
$files[] = 'included/tiny_mce/plugins/media/media.htm';
$files[] = 'included/tiny_mce/plugins/media/moxieplayer.swf';
$files[] = 'included/tiny_mce/plugins/nonbreaking/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/nonbreaking/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/noneditable/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/noneditable/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/pagebreak/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/pagebreak/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/paste/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/paste/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/paste/js/pastetext.js';
$files[] = 'included/tiny_mce/plugins/paste/js/pasteword.js';
$files[] = 'included/tiny_mce/plugins/paste/langs/en_dlg.js';
$files[] = 'included/tiny_mce/plugins/paste/langs/fr_dlg.js';
$files[] = 'included/tiny_mce/plugins/paste/pastetext.htm';
$files[] = 'included/tiny_mce/plugins/paste/pasteword.htm';
$files[] = 'included/tiny_mce/plugins/preview/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/preview/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/preview/example.html';
$files[] = 'included/tiny_mce/plugins/preview/jscripts/embed.js';
$files[] = 'included/tiny_mce/plugins/preview/preview.html';
$files[] = 'included/tiny_mce/plugins/print/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/print/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/save/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/save/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/searchreplace/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/searchreplace/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/searchreplace/js/searchreplace.js';
$files[] = 'included/tiny_mce/plugins/searchreplace/langs/en_dlg.js';
$files[] = 'included/tiny_mce/plugins/searchreplace/langs/fr_dlg.js';
$files[] = 'included/tiny_mce/plugins/searchreplace/searchreplace.htm';
$files[] = 'included/tiny_mce/plugins/spellchecker/css/content.css';
$files[] = 'included/tiny_mce/plugins/spellchecker/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/spellchecker/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/spellchecker/img/wline.gif';
$files[] = 'included/tiny_mce/plugins/style/css/props.css';
$files[] = 'included/tiny_mce/plugins/style/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/style/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/style/js/props.js';
$files[] = 'included/tiny_mce/plugins/style/langs/en_dlg.js';
$files[] = 'included/tiny_mce/plugins/style/langs/fr_dlg.js';
$files[] = 'included/tiny_mce/plugins/style/props.htm';
$files[] = 'included/tiny_mce/plugins/tabfocus/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/tabfocus/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/table/cell.htm';
$files[] = 'included/tiny_mce/plugins/table/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/table/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/table/js/cell.js';
$files[] = 'included/tiny_mce/plugins/table/js/merge_cells.js';
$files[] = 'included/tiny_mce/plugins/table/js/row.js';
$files[] = 'included/tiny_mce/plugins/table/js/table.js';
$files[] = 'included/tiny_mce/plugins/table/langs/en_dlg.js';
$files[] = 'included/tiny_mce/plugins/table/langs/fr_dlg.js';
$files[] = 'included/tiny_mce/plugins/table/merge_cells.htm';
$files[] = 'included/tiny_mce/plugins/table/row.htm';
$files[] = 'included/tiny_mce/plugins/table/table.htm';
$files[] = 'included/tiny_mce/plugins/template/blank.htm';
$files[] = 'included/tiny_mce/plugins/template/css/template.css';
$files[] = 'included/tiny_mce/plugins/template/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/template/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/template/js/template.js';
$files[] = 'included/tiny_mce/plugins/template/langs/en_dlg.js';
$files[] = 'included/tiny_mce/plugins/template/langs/fr_dlg.js';
$files[] = 'included/tiny_mce/plugins/template/template.htm';
$files[] = 'included/tiny_mce/plugins/visualchars/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/visualchars/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/wordcount/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/wordcount/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/xhtmlxtras/abbr.htm';
$files[] = 'included/tiny_mce/plugins/xhtmlxtras/acronym.htm';
$files[] = 'included/tiny_mce/plugins/xhtmlxtras/attributes.htm';
$files[] = 'included/tiny_mce/plugins/xhtmlxtras/cite.htm';
$files[] = 'included/tiny_mce/plugins/xhtmlxtras/css/attributes.css';
$files[] = 'included/tiny_mce/plugins/xhtmlxtras/css/popup.css';
$files[] = 'included/tiny_mce/plugins/xhtmlxtras/del.htm';
$files[] = 'included/tiny_mce/plugins/xhtmlxtras/editor_plugin.js';
$files[] = 'included/tiny_mce/plugins/xhtmlxtras/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/xhtmlxtras/ins.htm';
$files[] = 'included/tiny_mce/plugins/xhtmlxtras/js/abbr.js';
$files[] = 'included/tiny_mce/plugins/xhtmlxtras/js/acronym.js';
$files[] = 'included/tiny_mce/plugins/xhtmlxtras/js/attributes.js';
$files[] = 'included/tiny_mce/plugins/xhtmlxtras/js/cite.js';
$files[] = 'included/tiny_mce/plugins/xhtmlxtras/js/del.js';
$files[] = 'included/tiny_mce/plugins/xhtmlxtras/js/element_common.js';
$files[] = 'included/tiny_mce/plugins/xhtmlxtras/js/ins.js';
$files[] = 'included/tiny_mce/plugins/xhtmlxtras/langs/en_dlg.js';
$files[] = 'included/tiny_mce/plugins/xhtmlxtras/langs/fr_dlg.js';
$files[] = 'included/tiny_mce/themes/advanced/about.htm';
$files[] = 'included/tiny_mce/themes/advanced/anchor.htm';
$files[] = 'included/tiny_mce/themes/advanced/charmap.htm';
$files[] = 'included/tiny_mce/themes/advanced/color_picker.htm';
$files[] = 'included/tiny_mce/themes/advanced/editor_template.js';
$files[] = 'included/tiny_mce/themes/advanced/editor_template_src.js';
$files[] = 'included/tiny_mce/themes/advanced/image.htm';
$files[] = 'included/tiny_mce/themes/advanced/img/colorpicker.jpg';
$files[] = 'included/tiny_mce/themes/advanced/img/flash.gif';
$files[] = 'included/tiny_mce/themes/advanced/img/icons.gif';
$files[] = 'included/tiny_mce/themes/advanced/img/iframe.gif';
$files[] = 'included/tiny_mce/themes/advanced/img/pagebreak.gif';
$files[] = 'included/tiny_mce/themes/advanced/img/quicktime.gif';
$files[] = 'included/tiny_mce/themes/advanced/img/realmedia.gif';
$files[] = 'included/tiny_mce/themes/advanced/img/shockwave.gif';
$files[] = 'included/tiny_mce/themes/advanced/img/trans.gif';
$files[] = 'included/tiny_mce/themes/advanced/img/video.gif';
$files[] = 'included/tiny_mce/themes/advanced/img/windowsmedia.gif';
$files[] = 'included/tiny_mce/themes/advanced/js/about.js';
$files[] = 'included/tiny_mce/themes/advanced/js/anchor.js';
$files[] = 'included/tiny_mce/themes/advanced/js/charmap.js';
$files[] = 'included/tiny_mce/themes/advanced/js/color_picker.js';
$files[] = 'included/tiny_mce/themes/advanced/js/image.js';
$files[] = 'included/tiny_mce/themes/advanced/js/link.js';
$files[] = 'included/tiny_mce/themes/advanced/js/source_editor.js';
$files[] = 'included/tiny_mce/themes/advanced/langs/en.js';
$files[] = 'included/tiny_mce/themes/advanced/langs/en_dlg.js';
$files[] = 'included/tiny_mce/themes/advanced/langs/fr.js';
$files[] = 'included/tiny_mce/themes/advanced/langs/fr_dlg.js';
$files[] = 'included/tiny_mce/themes/advanced/link.htm';
$files[] = 'included/tiny_mce/themes/advanced/shortcuts.htm';
$files[] = 'included/tiny_mce/themes/advanced/skins/default/content.css';
$files[] = 'included/tiny_mce/themes/advanced/skins/default/dialog.css';
$files[] = 'included/tiny_mce/themes/advanced/skins/default/img/buttons.png';
$files[] = 'included/tiny_mce/themes/advanced/skins/default/img/items.gif';
$files[] = 'included/tiny_mce/themes/advanced/skins/default/img/tabs.gif';
$files[] = 'included/tiny_mce/themes/advanced/skins/default/ui.css';
$files[] = 'included/tiny_mce/themes/advanced/skins/highcontrast/content.css';
$files[] = 'included/tiny_mce/themes/advanced/skins/highcontrast/dialog.css';
$files[] = 'included/tiny_mce/themes/advanced/skins/highcontrast/ui.css';
$files[] = 'included/tiny_mce/themes/advanced/skins/o2k7/content.css';
$files[] = 'included/tiny_mce/themes/advanced/skins/o2k7/dialog.css';
$files[] = 'included/tiny_mce/themes/advanced/skins/o2k7/img/button_bg.png';
$files[] = 'included/tiny_mce/themes/advanced/skins/o2k7/img/button_bg_black.png';
$files[] = 'included/tiny_mce/themes/advanced/skins/o2k7/img/button_bg_silver.png';
$files[] = 'included/tiny_mce/themes/advanced/skins/o2k7/ui.css';
$files[] = 'included/tiny_mce/themes/advanced/skins/o2k7/ui_black.css';
$files[] = 'included/tiny_mce/themes/advanced/skins/o2k7/ui_silver.css';
$files[] = 'included/tiny_mce/themes/advanced/source_editor.htm';
$files[] = 'included/tiny_mce/themes/simple/editor_template.js';
$files[] = 'included/tiny_mce/themes/simple/editor_template_src.js';
$files[] = 'included/tiny_mce/themes/simple/img/icons.gif';
$files[] = 'included/tiny_mce/themes/simple/langs/en.js';
$files[] = 'included/tiny_mce/themes/simple/langs/fr.js';
$files[] = 'included/tiny_mce/themes/simple/skins/default/ui.css';
$files[] = 'included/tiny_mce/themes/simple/skins/o2k7/ui.css';
$files[] = 'included/tiny_mce/tiny_mce.js';
$files[] = 'included/tiny_mce/tiny_mce_popup.js';
$files[] = 'included/tiny_mce/tiny_mce_src.js';
$files[] = 'included/tiny_mce/utils/editable_selects.js';
$files[] = 'included/tiny_mce/utils/form_utils.js';
$files[] = 'included/tiny_mce/utils/mctabs.js';
$files[] = 'included/tiny_mce/utils/validate.js';

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
?>