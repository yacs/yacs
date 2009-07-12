<?php
/**
 * check scripts footprints
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'scripts.php';

// what to do
$action = '';
if(isset($_REQUEST['action']))
	$action = $_REQUEST['action'];
if(!$action && isset($context['arguments'][0]))
	$action = $context['arguments'][0];
$action = strip_tags($action);

// load footprints, if any
Safe::load('footprints.php');

// load localized strings
i18n::bind('scripts');

// load the skin
load_skin('scripts');

// the path to this page
$context['path_bar'] = array( 'scripts/' => i18n::s('Server software') );

// the title of the page
$context['page_title'] = i18n::s('Check software integrity');

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('scripts/update.php'));

// only associates can proceed
elseif(!Surfer::is_associate()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// invalid staging index
} elseif(!isset($generation['date']) || !$generation['date'] || !$generation['server'] || !is_array($footprints)) {
		$context['text'] .= '<p>'.sprintf(i18n::s('ERROR: File %s is missing or corrupted.'), 'footprints.php')."</p>\n";

		// forward to the index page
		$menu = array('scripts/' => i18n::s('Server software'));
		$context['text'] .= Skin::build_list($menu, 'menu_bar');

// actual update
} elseif($action == 'confirmed') {

	function check_file($node) {
		global $context;
		global $footprints;
	
		$key = substr($node, strlen($context['path_to_root']));

		// no extension to check
		if(strpos($key, '.') === FALSE)
			;
			
		// skip the staging directory
		elseif(!strncmp($node, 'scripts/staging', 16))
			;

		// the main signature file
		elseif(!strcmp($key, 'footprints.php'))
			;
			
		// an index file created by yacs
		elseif(!strncmp(substr($key, -9), 'index.php', 9) && ($content = Safe::file_get_contents($node)) && !strcmp($content, Safe::mkdir_index_content()))
			;
			
		// a localized set of string
		elseif(!strncmp($key, 'temporary/cache_i18n_locale_', 28))
			;

		// another PHP file
		elseif(!strncmp(substr($key, -4), '.php', 4)) {

			// one of the parameter files created by yacs
			if(preg_match('/parameters\/(agents|collections|control|feeds|files|hooks|letters|root|scripts|services|skins|users)\.include\.php$/i', $key))
				;
				
			elseif(isset($footprints[$key])) {
				$expected = $footprints[$key];
				$actual = Scripts::hash($node);
				
				if(($expected[0] != $actual[0]) || (($expected[1] != $actual[1]) && ($expected[2] != $actual[3])))
					$context['text'] .= sprintf(i18n::s('ERROR: File %s is missing or corrupted.'), $key).BR."\n";
	
			} else
				$context['text'] .= sprintf(i18n::s('File %s is not part of Yacs.'), $key).BR."\n";

		// not a safe file
		} elseif(!preg_match('/\.(bak|bat|css|done|dtd|fdb|flv|gif|ico|jpeg|jpg|js|jsmin|htc|htm|html|mo|off|on|pdf|png|po|pot|reg|sh|sql|swf|tgz|txt|xml|zip)$/i', $key))
			$context['text'] .= sprintf(i18n::s('File %s is not part of Yacs.'), $key).BR."\n";

	}			


		// ensure enough execution time
//		Safe::set_time_limit(30);

	// list of updated scripts
	$context['text'] .= '<p>'.i18n::s('Checking scripts...').BR."\n";
	Scripts::walk_files_at($context['path_to_root'], 'check_file');
	
} else {

	// splash message
	$context['text'] .= '<p>'.i18n::s('Click on the button below to check running scripts on your server.')."</p>\n";

	// propose to update the server
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'"><p>'
			.Skin::build_submit_button(i18n::s('Yes, I want to check scripts on this server'))
			.'<input type="hidden" name="action" value="confirmed" />'
			.'</p></form>'."\n";

}

// render the skin
render_skin();

?>