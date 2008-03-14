<?php
/**
 * install tinymce 2.1.0
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Install TinyMCE 2.1.0';
$local['label_fr'] = 'Installation de TinyMCE 2.1.0';
echo get_local('label')."<br />\n";

// the reference server to use
@include_once $context['path_to_root'].'scripts/parameters.include.php';
if(!$context['reference_server'])
	$context['reference_server'] = 'www.yetanothercommunitysystem.com';

// files to fetch, from root path
unset($files);
$files[] = 'included/tiny_mce/blank.htm';
$files[] = 'included/tiny_mce/license.txt';
$files[] = 'included/tiny_mce/tiny_mce_popup.js';
$files[] = 'included/tiny_mce/tiny_mce_src.js';
$files[] = 'included/tiny_mce/langs/en.js';
$files[] = 'included/tiny_mce/plugins/advhr/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/advhr/rule.htm';
$files[] = 'included/tiny_mce/plugins/advhr/css/advhr.css';
$files[] = 'included/tiny_mce/plugins/advhr/images/advhr.gif';
$files[] = 'included/tiny_mce/plugins/advhr/jscripts/rule.js';
$files[] = 'included/tiny_mce/plugins/advhr/langs/en.js';
$files[] = 'included/tiny_mce/plugins/advimage/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/advimage/image.htm';
$files[] = 'included/tiny_mce/plugins/advimage/css/advimage.css';
$files[] = 'included/tiny_mce/plugins/advimage/images/sample.gif';
$files[] = 'included/tiny_mce/plugins/advimage/jscripts/functions.js';
$files[] = 'included/tiny_mce/plugins/advimage/langs/en.js';
$files[] = 'included/tiny_mce/plugins/advlink/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/advlink/link.htm';
$files[] = 'included/tiny_mce/plugins/advlink/css/advlink.css';
$files[] = 'included/tiny_mce/plugins/advlink/jscripts/functions.js';
$files[] = 'included/tiny_mce/plugins/advlink/langs/en.js';
$files[] = 'included/tiny_mce/plugins/fullscreen/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/fullscreen/fullscreen.htm';
$files[] = 'included/tiny_mce/plugins/fullscreen/css/page.css';
$files[] = 'included/tiny_mce/plugins/fullscreen/images/fullscreen.gif';
$files[] = 'included/tiny_mce/plugins/fullscreen/langs/en.js';
$files[] = 'included/tiny_mce/plugins/paste/blank.htm';
$files[] = 'included/tiny_mce/plugins/paste/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/paste/pastetext.htm';
$files[] = 'included/tiny_mce/plugins/paste/pasteword.htm';
$files[] = 'included/tiny_mce/plugins/paste/css/blank.css';
$files[] = 'included/tiny_mce/plugins/paste/css/pasteword.css';
$files[] = 'included/tiny_mce/plugins/paste/images/pastetext.gif';
$files[] = 'included/tiny_mce/plugins/paste/images/pasteword.gif';
$files[] = 'included/tiny_mce/plugins/paste/images/selectall.gif';
$files[] = 'included/tiny_mce/plugins/paste/jscripts/pastetext.js';
$files[] = 'included/tiny_mce/plugins/paste/jscripts/pasteword.js';
$files[] = 'included/tiny_mce/plugins/paste/langs/en.js';
$files[] = 'included/tiny_mce/plugins/searchreplace/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/searchreplace/searchreplace.htm';
$files[] = 'included/tiny_mce/plugins/searchreplace/css/searchreplace.css';
$files[] = 'included/tiny_mce/plugins/searchreplace/images/replace.gif';
$files[] = 'included/tiny_mce/plugins/searchreplace/images/replace_all_button_bg.gif';
$files[] = 'included/tiny_mce/plugins/searchreplace/images/replace_button_bg.gif';
$files[] = 'included/tiny_mce/plugins/searchreplace/images/search.gif';
$files[] = 'included/tiny_mce/plugins/searchreplace/jscripts/searchreplace.js';
$files[] = 'included/tiny_mce/plugins/searchreplace/langs/en.js';
$files[] = 'included/tiny_mce/plugins/table/cell.htm';
$files[] = 'included/tiny_mce/plugins/table/editor_plugin_src.js';
$files[] = 'included/tiny_mce/plugins/table/merge_cells.htm';
$files[] = 'included/tiny_mce/plugins/table/row.htm';
$files[] = 'included/tiny_mce/plugins/table/table.htm';
$files[] = 'included/tiny_mce/plugins/table/css/cell.css';
$files[] = 'included/tiny_mce/plugins/table/css/row.css';
$files[] = 'included/tiny_mce/plugins/table/css/table.css';
$files[] = 'included/tiny_mce/plugins/table/images/buttons.gif';
$files[] = 'included/tiny_mce/plugins/table/images/table.gif';
$files[] = 'included/tiny_mce/plugins/table/images/table_cell_props.gif';
$files[] = 'included/tiny_mce/plugins/table/images/table_delete.gif';
$files[] = 'included/tiny_mce/plugins/table/images/table_delete_col.gif';
$files[] = 'included/tiny_mce/plugins/table/images/table_delete_row.gif';
$files[] = 'included/tiny_mce/plugins/table/images/table_insert_col_after.gif';
$files[] = 'included/tiny_mce/plugins/table/images/table_insert_col_before.gif';
$files[] = 'included/tiny_mce/plugins/table/images/table_insert_row_after.gif';
$files[] = 'included/tiny_mce/plugins/table/images/table_insert_row_before.gif';
$files[] = 'included/tiny_mce/plugins/table/images/table_merge_cells.gif';
$files[] = 'included/tiny_mce/plugins/table/images/table_row_props.gif';
$files[] = 'included/tiny_mce/plugins/table/images/table_split_cells.gif';
$files[] = 'included/tiny_mce/plugins/table/jscripts/cell.js';
$files[] = 'included/tiny_mce/plugins/table/jscripts/merge_cells.js';
$files[] = 'included/tiny_mce/plugins/table/jscripts/row.js';
$files[] = 'included/tiny_mce/plugins/table/jscripts/table.js';
$files[] = 'included/tiny_mce/plugins/table/langs/en.js';
$files[] = 'included/tiny_mce/themes/advanced/about.htm';
$files[] = 'included/tiny_mce/themes/advanced/anchor.htm';
$files[] = 'included/tiny_mce/themes/advanced/charmap.htm';
$files[] = 'included/tiny_mce/themes/advanced/color_picker.htm';
$files[] = 'included/tiny_mce/themes/advanced/editor_template_src.js';
$files[] = 'included/tiny_mce/themes/advanced/image.htm';
$files[] = 'included/tiny_mce/themes/advanced/link.htm';
$files[] = 'included/tiny_mce/themes/advanced/source_editor.htm';
$files[] = 'included/tiny_mce/themes/advanced/css/colorpicker.css';
$files[] = 'included/tiny_mce/themes/advanced/css/editor_content.css';
$files[] = 'included/tiny_mce/themes/advanced/css/editor_popup.css';
$files[] = 'included/tiny_mce/themes/advanced/css/editor_ui.css';
$files[] = 'included/tiny_mce/themes/advanced/images/anchor.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/anchor_symbol.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/backcolor.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/bold.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/bold_de_se.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/bold_es.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/bold_fr.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/bold_ru.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/bold_tw.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/browse.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/bullist.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/button_menu.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/buttons.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/cancel_button_bg.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/charmap.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/cleanup.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/close.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/code.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/color.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/colors.jpg';
$files[] = 'included/tiny_mce/themes/advanced/images/copy.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/custom_1.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/cut.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/forecolor.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/help.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/hr.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/image.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/indent.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/insert_button_bg.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/italic.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/italic_de_se.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/italic_es.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/italic_ru.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/italic_tw.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/justifycenter.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/justifyfull.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/justifyleft.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/justifyright.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/link.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/menu_check.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/newdocument.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/numlist.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/opacity.png';
$files[] = 'included/tiny_mce/themes/advanced/images/outdent.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/paste.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/redo.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/removeformat.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/separator.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/spacer.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/statusbar_resize.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/strikethrough.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/sub.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/sup.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/underline.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/underline_es.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/underline_fr.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/underline_ru.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/underline_tw.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/undo.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/unlink.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/visualaid.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/xp/tab_bg.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/xp/tab_end.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/xp/tab_sel_bg.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/xp/tab_sel_end.gif';
$files[] = 'included/tiny_mce/themes/advanced/images/xp/tabs_bg.gif';
$files[] = 'included/tiny_mce/themes/advanced/jscripts/about.js';
$files[] = 'included/tiny_mce/themes/advanced/jscripts/anchor.js';
$files[] = 'included/tiny_mce/themes/advanced/jscripts/charmap.js';
$files[] = 'included/tiny_mce/themes/advanced/jscripts/color_picker.js';
$files[] = 'included/tiny_mce/themes/advanced/jscripts/image.js';
$files[] = 'included/tiny_mce/themes/advanced/jscripts/link.js';
$files[] = 'included/tiny_mce/themes/advanced/jscripts/source_editor.js';
$files[] = 'included/tiny_mce/themes/advanced/langs/en.js';
$files[] = 'included/tiny_mce/themes/simple/editor_template_src.js';
$files[] = 'included/tiny_mce/themes/simple/css/editor_content.css';
$files[] = 'included/tiny_mce/themes/simple/css/editor_popup.css';
$files[] = 'included/tiny_mce/themes/simple/css/editor_ui.css';
$files[] = 'included/tiny_mce/themes/simple/images/bold.gif';
$files[] = 'included/tiny_mce/themes/simple/images/bold_de_se.gif';
$files[] = 'included/tiny_mce/themes/simple/images/bold_fr.gif';
$files[] = 'included/tiny_mce/themes/simple/images/bold_ru.gif';
$files[] = 'included/tiny_mce/themes/simple/images/bold_tw.gif';
$files[] = 'included/tiny_mce/themes/simple/images/bullist.gif';
$files[] = 'included/tiny_mce/themes/simple/images/buttons.gif';
$files[] = 'included/tiny_mce/themes/simple/images/cleanup.gif';
$files[] = 'included/tiny_mce/themes/simple/images/italic.gif';
$files[] = 'included/tiny_mce/themes/simple/images/italic_de_se.gif';
$files[] = 'included/tiny_mce/themes/simple/images/italic_ru.gif';
$files[] = 'included/tiny_mce/themes/simple/images/italic_tw.gif';
$files[] = 'included/tiny_mce/themes/simple/images/numlist.gif';
$files[] = 'included/tiny_mce/themes/simple/images/redo.gif';
$files[] = 'included/tiny_mce/themes/simple/images/separator.gif';
$files[] = 'included/tiny_mce/themes/simple/images/spacer.gif';
$files[] = 'included/tiny_mce/themes/simple/images/strikethrough.gif';
$files[] = 'included/tiny_mce/themes/simple/images/underline.gif';
$files[] = 'included/tiny_mce/themes/simple/images/underline_fr.gif';
$files[] = 'included/tiny_mce/themes/simple/images/underline_ru.gif';
$files[] = 'included/tiny_mce/themes/simple/images/underline_tw.gif';
$files[] = 'included/tiny_mce/themes/simple/images/undo.gif';
$files[] = 'included/tiny_mce/utils/editable_selects.js';
$files[] = 'included/tiny_mce/utils/form_utils.js';
$files[] = 'included/tiny_mce/utils/mclayer.js';
$files[] = 'included/tiny_mce/utils/mctabs.js';
$files[] = 'included/tiny_mce/utils/validate.js';

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
		$local['error_en'] = 'Unable to get '.$url;
		$local['error_fr'] = 'Impossible d\'obtenir '.$url;
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
			$local['label_fr'] = 'Impossible d\'écrire le fichier '.$file.'.';
			echo get_local('label')."<br />\n";
		} else {
			$local['label_en'] = 'has been updated';
			$local['label_fr'] = 'a été mis à jour';
			echo $file.' '.get_local('label')."<br />\n";
		}

	}

	// next one
	$count += 1;
	Safe::set_time_limit(30);
}

// basic reporting
$local['label_en'] = 'files have been processed';
$local['label_fr'] = 'fichiers ont été traités';
echo $count.' '.get_local('label')."<br />\n";
?>