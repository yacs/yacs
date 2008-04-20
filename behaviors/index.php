<?php
/**
 * add intelligence to yacs
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 *
 */
include_once '../shared/global.php';

// load localized strings
i18n::bind('behaviors');

// load the skin
load_skin('behaviors');

// set page title
$context['page_title'] = i18n::s('Behaviors');

// splash message
if(Surfer::is_associate())
	$context['text'] .= '<p>'.i18n::s('Behaviors listed below can be used to customise articles attached to some sections.').'</p>';

// list behaviors available on this system
$context['text'] .= '<ul>';
if ($dir = Safe::opendir($context['path_to_root'].'behaviors')) {

	// every php script is a behavior, except index.php, behavior.php and behaviors.php
	while(($file = Safe::readdir($dir)) !== FALSE) {
		if($file == '.' || $file == '..' || is_dir($context['path_to_root'].'behaviors/'.$file))
			continue;
		if($file == 'index.php')
			continue;
		if($file == 'behavior.php')
			continue;
		if($file == 'behaviors.php')
			continue;
		if(!preg_match('/(.*)\.php$/i', $file, $matches))
			continue;
		$behaviors[] = $matches[1];
	}
	Safe::closedir($dir);
	if(@count($behaviors)) {
		sort($behaviors);
		foreach($behaviors as $behavior)
			$context['text'] .= '<li>'.$behavior."</li>\n";
	}
}
$context['text'] .= '</ul>';

// how to use behaviors
if(Surfer::is_associate())
	$context['text'] .= '<p>'.sprintf(i18n::s('For example, if you want to apply the behavior <code>foo</code>, go to the %s , and select a target section, or add a new one.'), Skin::build_link('sections/', i18n::s('site map'), 'shortcut')).'</p>'
		.'<p>'.i18n::s('In the form used to edit the section, type the keyword <code>foo</code> in the behavior field, then save changes.').'</p>';

// referrals, if any
if(Surfer::is_associate() || (isset($context['with_referrals']) && ($context['with_referrals'] == 'Y'))) {

	$cache_id = 'behaviors/index.php#referrals#';
	if(!$text =& Cache::get($cache_id)) {

		// box content
		include_once '../agents/referrals.php';
		$text = Referrals::list_by_hits_for_url($context['url_to_root_parameter'].'behaviors/index.php');

		// in a sidebar box
		if($text)
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