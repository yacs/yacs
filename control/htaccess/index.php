<?php
/**
 * build .htaccess
 *
 * If the file [code]parameters/demo.flag[/code] exists, the script assumes that this instance
 * of YACS runs in demonstration mode.
 * In this mode the edit form is displayed, but parameters are not saved in the configuration file.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../../shared/global.php';

// load localized strings
i18n::bind('control');

// load the skin
load_skin('control');

// page title
$context['page_title'] = sprintf(i18n::s('%s: %s'), i18n::s('Configure'), i18n::s('Apache .htaccess'));

// this is reserved to associates
if(!Surfer::is_associate()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// no modifications in demo mode
} elseif(isset($_REQUEST['build']) && file_exists($context['path_to_root'].'parameters/demo.flag')) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation in demonstration mode.'));

// build a customized .htaccess
} elseif(isset($_REQUEST['build'])) {
	$content = '';

	// path to error script
	if(!$chunk = Safe::file_get_contents('control/htaccess/basic/.htaccess'))
		Logger::error(sprintf(i18n::s('Impossible to read %s.'), 'control/htaccess/basic/.htaccess'));
	else
		$content = str_replace('!!url_to_root!!', $context['url_to_root'], $chunk);

	// with Options
	if(isset($_SESSION['htaccess']['options'])) {
		if(!$chunk = Safe::file_get_contents('control/htaccess/options/.htaccess'))
			Logger::error(sprintf(i18n::s('Impossible to read %s.'), 'control/htaccess/options/.htaccess'));
		else
			$content .= $chunk;
	}

	// with Indexes
	if(isset($_SESSION['htaccess']['indexes'])) {
		if(!$chunk = Safe::file_get_contents('control/htaccess/indexes/.htaccess'))
			Logger::error(sprintf(i18n::s('Impossible to read %s.'), 'control/htaccess/indexes/.htaccess'));
		else
			$content .= $chunk;
	}

	// ensure smooth operations
	if($content && !count($context['error'])) {

		// backup the old version
		Safe::unlink($context['path_to_root'].'.htaccess.bak');
		Safe::rename($context['path_to_root'].'.htaccess', $context['path_to_root'].'.htaccess.bak');

		// update the parameters file
		if(!Safe::file_put_contents($context['path_to_root'].'.htaccess', $content)) {

			Logger::error(sprintf(i18n::s('ERROR: Impossible to write to the file %s. The configuration has not been saved.'), $context['path_to_root'].'.htaccess'));

			// allow for a manual update
			$context['text'] .= '<p style="text-decoration: blink;">'.sprintf(i18n::s('To actually change the configuration, please copy and paste following lines by yourself in file %s.'), $context['path_to_root'].'.htaccess')."</p>\n";

		// job done
		} else {

			$context['text'] .= '<p>'.sprintf(i18n::s('The following configuration has been saved into the file %s.'), '.htaccess')."</p>\n";

			// remember the change
			$label = sprintf(i18n::c('%s has been updated'), '.htaccess');
			Logger::remember('control/htaccess/index.php', $label);

		}

		// display updated parameters
		$context['text'] .= Skin::build_box(i18n::s('Configuration parameters'), str_replace("\n", BR, htmlspecialchars($content)), 'folded');

		// follow-up commands
		$follow_up = i18n::s('Where do you want to go now?');
		$menu = array();
		$menu = array_merge($menu, array( 'control/' => i18n::s('Control Panel') ));
		$menu = array_merge($menu, array( 'control/htaccess/' => i18n::s('Configure again') ));
		$follow_up .= Skin::build_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	}

// ask for tests
} else {

	// splash
	$context['text'] = '<p>'.i18n::s('Click on following links to assess actual capabilities of this server. In case of error, just come back to this page and move to next test.').'</p>';

	// remember capability in session context
	if(!isset($_SESSION['htaccess']))
		$_SESSION['htaccess'] = array();

	// test links
	$links = array();

	$tested = '';
	if(isset($_SESSION['htaccess']['basic']))
		$tested = ' '.i18n::s('OK');
	$links[] = Skin::build_link('control/htaccess/basic', i18n::s('Basic (compression)'), 'basic').$tested;

	$tested = '';
	if(isset($_SESSION['htaccess']['options']))
		$tested = ' '.i18n::s('OK');
	$links[] = Skin::build_link('control/htaccess/options', i18n::s('Options (URL rewriting)'), 'basic').$tested;

	$tested = '';
	if(isset($_SESSION['htaccess']['indexes']))
		$tested = ' '.i18n::s('OK');
	$links[] = Skin::build_link('control/htaccess/indexes', i18n::s('Indexes (expiration cache)'), 'basic').$tested;

	$context['text'] .= Skin::finalize_list($links, 'compact');

	// splash
	$context['text'] .= '<p>'.i18n::s('Once all links have been tested, click on the build button to actually update the .htaccess file.').'</p>';

	// commands
	$menu = array();
	$menu[] = Skin::build_submit_button(i18n::s('Build the .htaccess file'), NULL, NULL, 'confirmed');
	$menu[] = Skin::build_link('control/', i18n::s('Control Panel'), 'span');

	// render commands
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
		.Skin::finalize_list($menu, 'assistant_bar')
		.'<input type="hidden" name="build" value="yes" />'."\n"
		.'</p></form>'."\n";

}

// render the page according to the loaded skin
render_skin();

?>