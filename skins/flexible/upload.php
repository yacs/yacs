<?php
/**
 * upload an image
 *
 * This script put the received file in the selected directory.
 *
 * Allowed call:
 * - upload?directory=boxes
 * - upload?directory=footers
 * - upload?directory=headers
 * - upload?directory=logos
 * - upload?directory=pages
 * - upload?directory=panels
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../../shared/global.php';
include_once '../../files/files.php';

// load the skin
load_skin('skins');

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('skins/flexible/upload.php'));

// only associates can proceed
elseif(!Surfer::is_associate()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// we need a target directory
} elseif(!isset($_REQUEST['directory']) || !is_dir('./'.basename($_REQUEST['directory']))) {
	Safe::header('Status: 400 Bad Request', TRUE, 400);
	Logger::error(i18n::s('Request is invalid.'));

// process uploaded file
} elseif(isset($_FILES['upload']['name']) && $_FILES['upload']['name'] && ($_FILES['upload']['name'] != 'none')) {

	// where to put this file
	$file_path = 'skins/flexible/'.basename($_REQUEST['directory']);

	// attach some file
	if($file_name = Files::upload($_FILES['upload'], $file_path)) {
		$context['page_title'] = i18n::s('Thank you for your contribution');

		$context['text'] .= '<p><img src="'.$context['url_to_home'].$context['url_to_root'].$file_path.'/'.$file_name.'" /></p>';

		if(isset($_REQUEST['referer']) && $_REQUEST['referer'])
			$link = $_REQUEST['referer'];
		elseif(isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'])
			$link = $_SERVER['HTTP_REFERER'];
		else
			$link = 'skins/flexible/configure.php';
		$follow_up = Skin::build_link($link, i18n::s('Done'), 'button');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');
	}

// nothing has been posted
} else {
	$context['page_title'] = i18n::s('Add a file');

	// preserve referer address
	if(isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'])
		$link = $_SERVER['HTTP_REFERER'];
	else
		$link = 'skins/flexible/configure.php';

	// the form to select the file to upload
	$context['text'] = '<form method="post" enctype="multipart/form-data" action="'.$context['script_url'].'">'
		.'<p style="margin: 0; padding: 0;">'
		.'<input type="file" name="upload" size="30" title="'.encode_field(i18n::s('Press to select a local file')).'" />'
		.Skin::build_submit_button(i18n::s('Submit'))
		.'<input type="hidden" name="directory" value="'.$_REQUEST['directory'].'" />'
		.'<input type="hidden" name="referer" value="'.$link.'" />'
		.'</p>'
		.'</form>';


	$headers = array(i18n::s('Suffix'), i18n::s('Alignment'), i18n::s('Style'), i18n::s('Example'));
	$rows = array();
	$rows[] = array('<tt>-x</tt>', i18n::s('repeat horizontally'), '<tt>repeat-x top left</tt>', '<tt>light_gray_bevel-x.jpg</tt>');
	$rows[] = array('<tt>-m</tt>', i18n::s('center at the top'), '<tt>no-repeat top center</tt>', '<tt>ephemeral_header05-m.jpg</tt>');
	$rows[] = array('<tt>-l</tt>', i18n::s('anchor to top left'), '<tt>no-repeat top left</tt>', '<tt>bar-grey-l.png</tt>');
	$rows[] = array('<tt>-r</tt>', i18n::s('anchor to top right'), '<tt>no-repeat top right</tt>', '<tt>flowers-r.jpg</tt>');
	$rows[] = array('<tt>-b</tt>', i18n::s('align to bottom, then repeat horizontally'), '<tt>repeat-x bottom left</tt>', '<tt>chrom_menu-b.png</tt>');
	$rows[] = array('<tt>-bm</tt>', i18n::s('center at the bottom'), '<tt>no-repeat bottom center</tt>', '<tt>bamboo2-bm.gif</tt>');
	$rows[] = array('<tt>-bl</tt>', i18n::s('anchor to bottom left'), '<tt>no-repeat bottom left</tt>', '<tt>bar-bl.gif</tt>');
	$rows[] = array('<tt>-br</tt>', i18n::s('anchor to bottom right'), '<tt>no-repeat bottom right</tt>', '<tt>shadow_alpha-br.png</tt>');
	$rows[] = array('<tt>-y</tt>', i18n::s('align to left, then repeat vertically'), '<tt>repeat-y top left</tt>', '<tt>sidebar-bg-y.png</tt>');
	$rows[] = array('<tt>-ym</tt>', i18n::s('center, then repeat vertically'), '<tt>repeat-y top center</tt>', '<tt>hgradient-ym.jpg</tt>');
	$rows[] = array('<tt>-yr</tt>', i18n::s('align to right, then repeat vertically'), '<tt>repeat-y top right</tt>', '<tt>sidebar-bg-yr.png</tt>');
	$rows[] = array(i18n::s('other cases'), i18n::s('repeat horizontally and vertically'), '<tt>repeat</tt>', '<tt>p-chrom_degh.png</tt>');


	// some suggestions
	$context['text'] .= '<p>'.i18n::s('Image will be aligned depending of its suffix, as depicted in the following table.').'</p>'
		.Skin::table($headers, $rows);
}

// render the skin
render_skin();

?>