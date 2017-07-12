<?php
/**
 * extends articles functionality with canvas
 *
 * canvas are a mean to change display of articles
 *
 *
 * @author Christophe Battarel
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 *
 * @see canvas/standard.php
 */
include_once '../shared/global.php';

// load localized strings
i18n::bind('canvas');

// load the skin
load_skin('canvas');

// the title of the page
$context['page_title'] = i18n::s('Canvas');

// splash message
if(Surfer::is_associate())
	$context['text'] .= '<p>'.i18n::s('Canvas listed below can be used to customise articles display attached to some sections.').'</p>';

// list canvas available on this system
$context['text'] .= '<ul>';
if ($dir = Safe::opendir($context['path_to_root'].'canvas')) {

	// every php script is an canvas, except index.php, canvas.php, and hooks
	while(($file = Safe::readdir($dir)) !== FALSE) {
		if(($file[0] == '.') || is_dir($context['path_to_root'].'canvas/'.$file))
			continue;
		if($file == 'index.php')
			continue;
		if($file == 'canvas.php')
			continue;
		if(preg_match('/hook\.php$/i', $file))
			continue;
		if(!preg_match('/(.*)\.php$/i', $file, $matches))
			continue;
		$canvas[] = $matches[1];
	}
	Safe::closedir($dir);
	if(@count($canvas)) {
		natsort($canvas);
		foreach($canvas as $canvas)
			$context['text'] .= '<li>'.$canvas."</li>\n";
	}
}
$context['text'] .= '</ul>';

// how to use canvas
if(Surfer::is_associate()) {
	$context['text'] .= '<p>'.sprintf(i18n::s('For example, if you want to apply the canvas <code>foo</code>, go to the %s, and select a target section, or add one.'), Skin::build_link('sections/', i18n::s('site map'), 'shortcut')).'</p>'
		.'<p>'.i18n::s('In the form used to edit the section, select <code>foo</code> in the canvas field, then save changes.').'</p>';
}

// referrals, if any
$context['components']['referrals'] = Skin::build_referrals('canvas/index.php');

// render the skin
render_skin();

?>