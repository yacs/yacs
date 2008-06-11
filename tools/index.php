<?php
/**
 * list available tools
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
include_once '../shared/global.php';

// load localized strings
i18n::bind('tools');

// load the skin
load_skin('tools');

// the title of the page
$context['page_title'] = i18n::s('Tools');

// list tools available on this system
$context['text'] .= '<ul>';
if ($dir = Safe::opendir($context['path_to_root'].'tools')) {

	// every php script is an overlay, except index.php, overlay.php, and hooks
	while(($file = Safe::readdir($dir)) !== FALSE) {
		if($file == '.' || $file == '..' || is_dir($context['path_to_root'].'tools/'.$file))
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
		sort($tools);
		foreach($tools as $tool)
			$context['text'] .= '<li>'.$tool."</li>\n";
	}
}
$context['text'] .= '</ul>';

// referrals, if any
if(Surfer::is_associate() || (isset($context['with_referrals']) && ($context['with_referrals'] == 'Y'))) {

	$cache_id = 'tools/index.php#referrals#';
	if(!$text =& Cache::get($cache_id)) {

		// box content in a sidebar box
		include_once '../agents/referrals.php';
		if($text = Referrals::list_by_hits_for_url($context['url_to_root_parameter'].'tools/index.php'))
			$text =& Skin::build_box(i18n::s('Referrals'), $text, 'navigation', 'referrals');

		// save in cache for one hour 60 * 60 = 3600
		Cache::put($cache_id, $text, 'referrals', 3600);

	}

	// in the extra panel
	$context['extra'] .= $text;
}

// render the skin
render_skin();

?>