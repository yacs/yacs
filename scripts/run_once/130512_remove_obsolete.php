<?php
/**
 * remove old files
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Remove obsoleted files';
$local['label_fr'] = 'Suppression des fichiers inutiles';
echo get_local('label')."<br />\n";
$count = 0;


// files to delete, from root path
$files = array();
$files[] = 'actions/accept.php';
$files[] = 'actions/actions.php';
$files[] = 'actions/check.php';
$files[] = 'actions/delete.php';
$files[] = 'actions/edit.php';
$files[] = 'actions/index.php';
$files[] = 'actions/layout_actions.php';
$files[] = 'actions/list.php';
$files[] = 'actions/view.php';
$files[] = 'articles/ie_bookmarklet.php';
$files[] = 'articles/layout_articles_as_freemind.php';
$files[] = 'articles/layout_articles_as_iui.php';
$files[] = 'articles/view_on_mobile.php';
$files[] = 'collections/browse.php';
$files[] = 'collections/collections.php';
$files[] = 'collections/configure.php';
$files[] = 'collections/fetch.php';
$files[] = 'collections/index.php';
$files[] = 'collections/play_audio.php';
$files[] = 'collections/play_slideshow.php';
$files[] = 'collections/stream.php';
$files[] = 'collections/upload.php';
$files[] = 'forms/delete.php';
$files[] = 'forms/edit.php';
$files[] = 'forms/forms.js';
$files[] = 'forms/forms.php';
$files[] = 'forms/index.php';
$files[] = 'forms/layout_forms.php';
$files[] = 'forms/view.php';
$files[] = 'included/fckeditor/editor/css/behaviors/disablehandles.htc';
$files[] = 'included/fckeditor/editor/css/behaviors/showtableborders.htc';
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
$files[] = 'included/fckeditor/editor/css/images/fck_anchor.gif';
$files[] = 'included/fckeditor/editor/css/images/fck_flashlogo.gif';
$files[] = 'included/fckeditor/editor/css/images/fck_hiddenfield.gif';
$files[] = 'included/fckeditor/editor/css/images/fck_pagebreak.gif';
$files[] = 'included/fckeditor/editor/css/images/fck_plugin.gif';
$files[] = 'included/fckeditor/editor/dialog/common/fck_dialog_common.css';
$files[] = 'included/fckeditor/editor/dialog/common/fck_dialog_common.js';
$files[] = 'included/fckeditor/editor/dialog/common/images/locked.gif';
$files[] = 'included/fckeditor/editor/dialog/common/images/reset.gif';
$files[] = 'included/fckeditor/editor/dialog/common/images/unlocked.gif';
$files[] = 'included/fckeditor/editor/dialog/fck_about.html';
$files[] = 'included/fckeditor/editor/dialog/fck_about/logo_fckeditor.gif';
$files[] = 'included/fckeditor/editor/dialog/fck_about/logo_fredck.gif';
$files[] = 'included/fckeditor/editor/dialog/fck_about/sponsors/spellchecker_net.gif';
$files[] = 'included/fckeditor/editor/dialog/fck_anchor.html';
$files[] = 'included/fckeditor/editor/dialog/fck_button.html';
$files[] = 'included/fckeditor/editor/dialog/fck_checkbox.html';
$files[] = 'included/fckeditor/editor/dialog/fck_colorselector.html';
$files[] = 'included/fckeditor/editor/dialog/fck_docprops.html';
$files[] = 'included/fckeditor/editor/dialog/fck_docprops/fck_document_preview.html';
$files[] = 'included/fckeditor/editor/dialog/fck_flash.html';
$files[] = 'included/fckeditor/editor/dialog/fck_flash/fck_flash.js';
$files[] = 'included/fckeditor/editor/dialog/fck_flash/fck_flash_preview.html';
$files[] = 'included/fckeditor/editor/dialog/fck_form.html';
$files[] = 'included/fckeditor/editor/dialog/fck_hiddenfield.html';
$files[] = 'included/fckeditor/editor/dialog/fck_image.html';
$files[] = 'included/fckeditor/editor/dialog/fck_image/fck_image.js';
$files[] = 'included/fckeditor/editor/dialog/fck_image/fck_image_preview.html';
$files[] = 'included/fckeditor/editor/dialog/fck_link.html';
$files[] = 'included/fckeditor/editor/dialog/fck_link/fck_link.js';
$files[] = 'included/fckeditor/editor/dialog/fck_listprop.html';
$files[] = 'included/fckeditor/editor/dialog/fck_paste.html';
$files[] = 'included/fckeditor/editor/dialog/fck_radiobutton.html';
$files[] = 'included/fckeditor/editor/dialog/fck_replace.html';
$files[] = 'included/fckeditor/editor/dialog/fck_select.html';
$files[] = 'included/fckeditor/editor/dialog/fck_select/fck_select.js';
$files[] = 'included/fckeditor/editor/dialog/fck_smiley.html';
$files[] = 'included/fckeditor/editor/dialog/fck_source.html';
$files[] = 'included/fckeditor/editor/dialog/fck_specialchar.html';
$files[] = 'included/fckeditor/editor/dialog/fck_spellerpages.html';
$files[] = 'included/fckeditor/editor/dialog/fck_spellerpages/spellerpages/blank.html';
$files[] = 'included/fckeditor/editor/dialog/fck_spellerpages/spellerpages/controlWindow.js';
$files[] = 'included/fckeditor/editor/dialog/fck_spellerpages/spellerpages/controls.html';
$files[] = 'included/fckeditor/editor/dialog/fck_spellerpages/spellerpages/server-scripts/spellchecker.php';
$files[] = 'included/fckeditor/editor/dialog/fck_spellerpages/spellerpages/spellChecker.js';
$files[] = 'included/fckeditor/editor/dialog/fck_spellerpages/spellerpages/spellchecker.html';
$files[] = 'included/fckeditor/editor/dialog/fck_spellerpages/spellerpages/spellerStyle.css';
$files[] = 'included/fckeditor/editor/dialog/fck_spellerpages/spellerpages/wordWindow.js';
$files[] = 'included/fckeditor/editor/dialog/fck_table.html';
$files[] = 'included/fckeditor/editor/dialog/fck_tablecell.html';
$files[] = 'included/fckeditor/editor/dialog/fck_template.html';
$files[] = 'included/fckeditor/editor/dialog/fck_template/images/template1.gif';
$files[] = 'included/fckeditor/editor/dialog/fck_template/images/template2.gif';
$files[] = 'included/fckeditor/editor/dialog/fck_template/images/template3.gif';
$files[] = 'included/fckeditor/editor/dialog/fck_textarea.html';
$files[] = 'included/fckeditor/editor/dialog/fck_textfield.html';
$files[] = 'included/fckeditor/editor/fckdebug.html';
$files[] = 'included/fckeditor/editor/fckdialog.html';
$files[] = 'included/fckeditor/editor/fckeditor.html';
$files[] = 'included/fckeditor/editor/fckeditor.original.html';
$files[] = 'included/fckeditor/editor/images/anchor.gif';
$files[] = 'included/fckeditor/editor/images/arrow_ltr.gif';
$files[] = 'included/fckeditor/editor/images/arrow_rtl.gif';
$files[] = 'included/fckeditor/editor/images/smiley/msn/angel_smile.gif';
$files[] = 'included/fckeditor/editor/images/smiley/msn/angry_smile.gif';
$files[] = 'included/fckeditor/editor/images/smiley/msn/broken_heart.gif';
$files[] = 'included/fckeditor/editor/images/smiley/msn/cake.gif';
$files[] = 'included/fckeditor/editor/images/smiley/msn/confused_smile.gif';
$files[] = 'included/fckeditor/editor/images/smiley/msn/cry_smile.gif';
$files[] = 'included/fckeditor/editor/images/smiley/msn/devil_smile.gif';
$files[] = 'included/fckeditor/editor/images/smiley/msn/embaressed_smile.gif';
$files[] = 'included/fckeditor/editor/images/smiley/msn/envelope.gif';
$files[] = 'included/fckeditor/editor/images/smiley/msn/heart.gif';
$files[] = 'included/fckeditor/editor/images/smiley/msn/kiss.gif';
$files[] = 'included/fckeditor/editor/images/smiley/msn/lightbulb.gif';
$files[] = 'included/fckeditor/editor/images/smiley/msn/omg_smile.gif';
$files[] = 'included/fckeditor/editor/images/smiley/msn/regular_smile.gif';
$files[] = 'included/fckeditor/editor/images/smiley/msn/sad_smile.gif';
$files[] = 'included/fckeditor/editor/images/smiley/msn/shades_smile.gif';
$files[] = 'included/fckeditor/editor/images/smiley/msn/teeth_smile.gif';
$files[] = 'included/fckeditor/editor/images/smiley/msn/thumbs_down.gif';
$files[] = 'included/fckeditor/editor/images/smiley/msn/thumbs_up.gif';
$files[] = 'included/fckeditor/editor/images/smiley/msn/tounge_smile.gif';
$files[] = 'included/fckeditor/editor/images/smiley/msn/whatchutalkingabout_smile.gif';
$files[] = 'included/fckeditor/editor/images/smiley/msn/wink_smile.gif';
$files[] = 'included/fckeditor/editor/images/spacer.gif';
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
$files[] = 'included/fckeditor/editor/skins/default/images/toolbar.arrowright.gif';
$files[] = 'included/fckeditor/editor/skins/default/images/toolbar.buttonarrow.gif';
$files[] = 'included/fckeditor/editor/skins/default/images/toolbar.collapse.gif';
$files[] = 'included/fckeditor/editor/skins/default/images/toolbar.end.gif';
$files[] = 'included/fckeditor/editor/skins/default/images/toolbar.expand.gif';
$files[] = 'included/fckeditor/editor/skins/default/images/toolbar.separator.gif';
$files[] = 'included/fckeditor/editor/skins/default/images/toolbar.start.gif';
$files[] = 'included/fckeditor/editor/skins/silver/fck_dialog.css';
$files[] = 'included/fckeditor/editor/skins/silver/fck_dialog_ie6.js';
$files[] = 'included/fckeditor/editor/skins/silver/fck_editor.css';
$files[] = 'included/fckeditor/editor/skins/silver/fck_strip.gif';
$files[] = 'included/fckeditor/editor/skins/silver/images/dialog.sides.gif';
$files[] = 'included/fckeditor/editor/skins/silver/images/dialog.sides.png';
$files[] = 'included/fckeditor/editor/skins/silver/images/dialog.sides.rtl.png';
$files[] = 'included/fckeditor/editor/skins/silver/images/sprites.gif';
$files[] = 'included/fckeditor/editor/skins/silver/images/sprites.png';
$files[] = 'included/fckeditor/editor/skins/silver/images/toolbar.arrowright.gif';
$files[] = 'included/fckeditor/editor/skins/silver/images/toolbar.buttonarrow.gif';
$files[] = 'included/fckeditor/editor/skins/silver/images/toolbar.buttonbg.gif';
$files[] = 'included/fckeditor/editor/skins/silver/images/toolbar.collapse.gif';
$files[] = 'included/fckeditor/editor/skins/silver/images/toolbar.end.gif';
$files[] = 'included/fckeditor/editor/skins/silver/images/toolbar.expand.gif';
$files[] = 'included/fckeditor/editor/skins/silver/images/toolbar.separator.gif';
$files[] = 'included/fckeditor/editor/skins/silver/images/toolbar.start.gif';
$files[] = 'included/fckeditor/fckconfig.js';
$files[] = 'included/fckeditor/fckeditor.js';
$files[] = 'included/fckeditor/fckeditor.php';
$files[] = 'included/fckeditor/fckeditor_php4.php';
$files[] = 'included/fckeditor/fckeditor_php5.php';
$files[] = 'included/fckeditor/fckpackager.xml';
$files[] = 'included/fckeditor/fckstyles.xml';
$files[] = 'included/fckeditor/fcktemplates.xml';
$files[] = 'included/fckeditor/license.txt';
$files[] = 'index_on_mobile.php';
$files[] = 'overlays/form.php';
$files[] = 'sections/freemind.php';
$files[] = 'sections/layout_sections_as_freemind.php';
$files[] = 'sections/view_as_freemind.php';
$files[] = 'skins/_mobile/LICENSE.TXT';
$files[] = 'skins/_mobile/NOTICE.TXT';
$files[] = 'skins/_mobile/iui/backButton.png';
$files[] = 'skins/_mobile/iui/blueButton.png';
$files[] = 'skins/_mobile/iui/cancel.png';
$files[] = 'skins/_mobile/iui/grayButton.png';
$files[] = 'skins/_mobile/iui/iui-logo-touch-icon.png';
$files[] = 'skins/_mobile/iui/iuix.css';
$files[] = 'skins/_mobile/iui/iuix.js';
$files[] = 'skins/_mobile/iui/listArrow.png';
$files[] = 'skins/_mobile/iui/listArrowSel.png';
$files[] = 'skins/_mobile/iui/listGroup.png';
$files[] = 'skins/_mobile/iui/loading.gif';
$files[] = 'skins/_mobile/iui/pinstripes.png';
$files[] = 'skins/_mobile/iui/redButton.png';
$files[] = 'skins/_mobile/iui/selection.png';
$files[] = 'skins/_mobile/iui/thumb.png';
$files[] = 'skins/_mobile/iui/toggle.png';
$files[] = 'skins/_mobile/iui/toggleOn.png';
$files[] = 'skins/_mobile/iui/toolButton.png';
$files[] = 'skins/_mobile/iui/toolbar.png';
$files[] = 'skins/_mobile/iui/whiteButton.png';
$files[] = 'skins/_mobile/skin.php';
$files[] = 'skins/_mobile/template.php';
$files[] = 'skins/skeleton/images/background_page.gif';
$files[] = 'skins/skeleton/images/bullet.gif';
$files[] = 'skins/skeleton/images/button_drop.png';
$files[] = 'skins/skeleton/images/buttons_cssw3c.gif';
$files[] = 'skins/skeleton/images/buttons_xhtmlw3c.gif';
$files[] = 'skins/skeleton/images/contentfill.gif';
$files[] = 'skins/skeleton/images/gadget_tab.jpg';
$files[] = 'skins/skeleton/images/header_background.jpg';
$files[] = 'skins/skeleton/images/navigation_tab.gif';
$files[] = 'skins/skeleton/images/nicetitle_background.png';
$files[] = 'skins/skeleton/images/poll_left.gif';
$files[] = 'skins/skeleton/images/poll_main.gif';
$files[] = 'skins/skeleton/images/poll_right.gif';
$files[] = 'skins/skeleton/images/shadow.gif';
$files[] = 'skins/skeleton/images/shadow_alpha.png';
$files[] = 'skins/skeleton/images/side_header.gif';
$files[] = 'skins/skeleton/images/yacs_background.png';
$files[] = 'skins/skeleton/images/yacs_bottom.png';
$files[] = 'skins/skeleton/images/yacs_top.png';
$files[] = 'skins/skeleton/layouts/map.gif';
$files[] = 'skins/skeleton/manifest.php';
$files[] = 'skins/skeleton/preview.jpg';
$files[] = 'skins/skeleton/skeleton.css';
$files[] = 'skins/skeleton/skin.php';
$files[] = 'skins/skeleton/squares.ico';
$files[] = 'skins/skeleton/template.php';
$files[] = 'skins/skeleton/template_print.php';
$files[] = 'skins/skeleton/tools/more.gif';
$files[] = 'skins/skeleton/tools/new.gif';
$files[] = 'skins/skeleton/tools/updated.gif';

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
echo $count.' '.get_local('label')."<br />\n";
?>