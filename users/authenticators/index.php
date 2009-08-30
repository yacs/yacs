<?php
/**
 * list available authenticators
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
include_once '../../shared/global.php';

// load the skin
load_skin('users');

// the title of the page
$context['page_title'] = i18n::s('Authenticators');

// splash message
if(Surfer::is_associate())
	$context['text'] .= '<p>'.i18n::s('Authenticators listed below can be used to link this server to an existing list of users.').'</p>';

// list authenticators available on this system
$context['text'] .= '<ul>';
if ($dir = Safe::opendir($context['path_to_root'].'users/authenticators')) {

	// every php script is an authenticator, except index.php and hooks
	while(($file = Safe::readdir($dir)) !== FALSE) {
		if(($file[0] == '.') || is_dir($context['path_to_root'].'authenticators/'.$file))
			continue;
		if($file == 'index.php')
			continue;
		if(preg_match('/hook\.php$/i', $file))
			continue;
		if(!preg_match('/(.*)\.php$/i', $file, $matches))
			continue;
		$authenticators[] = $matches[1];
	}
	Safe::closedir($dir);
	if(@count($authenticators)) {
		sort($authenticators);
		foreach($authenticators as $authenticator)
			$context['text'] .= '<li>'.$authenticator."</li>\n";
	}
}
$context['text'] .= '</ul>';

// how to use authenticators
if(Surfer::is_associate()) {
	$context['text'] .= '<p>'.sprintf(i18n::s('For example, if you want to apply the authenticator <code>foo</code>, put that keyword in the %s.'), Skin::build_link('users/configure.php', i18n::s('configuration panel for users'), 'shortcut')).'</p>';
}

// referrals, if any
$context['components']['referrals'] =& Skin::build_referrals('users/authenticators/index.php');

// render the skin
render_skin();

?>