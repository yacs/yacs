<?php
/**
 * list available tools
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
include_once '../shared/global.php';

// load localized strings
i18n::bind('tools');

// load the skin
load_skin('tools');

// do not index this page
$context->sif('robots','noindex');

// the title of the page
$context['page_title'] = i18n::s('Tools');

// list tools available on this system
$context['text'] .= '<ul>';
if ($dir = Safe::opendir($context['path_to_root'].'tools')) {

	// every php script is an overlay, except index.php, overlay.php, and hooks
	while(($file = Safe::readdir($dir)) !== FALSE) {
		if($file[0] == '.')
			continue;
		if($file == 'index.php')
			continue;
		if(preg_match('/hook\.php$/i', $file))
			continue;
		if(strpos($file, '.') && !preg_match('/(.*)\.php$/i', $file, $matches))
			continue;
		$tools[] = Skin::build_link('tools/'.$file, $file, 'basic');
	}
	Safe::closedir($dir);
	if(@count($tools)) {
		natsort($tools);
		foreach($tools as $tool)
			$context['text'] .= '<li>'.$tool."</li>\n";
	}
}
$context['text'] .= '</ul>';

// referrals, if any
$context['components']['referrals'] = Skin::build_referrals('tools/index.php');

// render the skin
render_skin();

?>