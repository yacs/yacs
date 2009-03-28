<?php
/**
 * change parameters for servers
 *
 * Use this script mainly to extend the list of banned hosts or domains.
 *
 * Following parameters may be edited by associates:
 *
 * - [code]banned_hosts[/code] - a space- or comma-separated list of hosts
 *
 * Configuration information is saved into [code]parameters/servers.include.php[/code].
 * If YACS is prevented to write to the file, it displays parameters to allow for a manual update.
 *
 * The file [code]parameters/servers.include.php.bak[/code] can be used to restore
 * the active configuration before the last change, if necessary.
 *
 * If the file [code]parameters/demo.flag[/code] exists, the script assumes that this instance
 * of YACS runs in demonstration mode.
 * In this mode the edit form is displayed, but parameters are not saved in the configuration file.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'servers.php';

// load the skin
load_skin('servers');

// the path to this page
$context['path_bar'] = array( 'control/' => i18n::s('Control Panel') );

// the title of the page
$context['page_title'] = sprintf(i18n::s('%s: %s'), i18n::s('Configure'), i18n::s('Servers'));

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('servers/configure.php'));

// only associates can proceed
elseif(!Surfer::is_associate()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// display the input form
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'POST')) {

	// load current parameters, if any
	Safe::load('parameters/servers.include.php');

	// the form
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>';

	// banned feeders
	if(!isset($context['banned_hosts']))
		$context['banned_hosts'] = '.kanoodle.com \bporn \bsex';
	$label = i18n::s('Servers to be banned');
	$input = '<textarea name="banned_hosts" id="banned_hosts" cols="40" rows="2">'.encode_field($context['banned_hosts']).'</textarea>';
	$hint = i18n::s('Links and referrals from these servers will be dropped');
	$fields[] = array($label, $input, $hint);

	// build the form
	$context['text'] .= Skin::build_form($fields);

	//
	// bottom commands
	//
	$menu = array();

	// the submit button
	$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');

	// control panel
	if(file_exists('../parameters/control.include.php'))
		$menu[] = Skin::build_link('control/', i18n::s('Control Panel'), 'span');

	// index page
	if(file_exists('../parameters/control.include.php'))
		$menu[] = Skin::build_link('servers/', i18n::s('Servers'), 'span');

	// insert the menu in the page
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// end of the form
	$context['text'] .= '</div></form>';

	// set the focus
	$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
		.'// set the focus on first form field'."\n"
		.'$("banned_hosts").focus();'."\n"
		.'// ]]></script>'."\n";

// no modifications in demo mode
} elseif(file_exists($context['path_to_root'].'parameters/demo.flag')) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation in demonstration mode.'));

// save updated parameters
} else {

	// backup the old version
	Safe::unlink($context['path_to_root'].'parameters/servers.include.php.bak');
	Safe::rename($context['path_to_root'].'parameters/servers.include.php', $context['path_to_root'].'parameters/servers.include.php.bak');

	// build the new configuration file
	$content = '<?php'."\n"
		.'// This file has been created by the configuration script servers/configure.php'."\n"
		.'// on '.gmdate("F j, Y, g:i a").' GMT, for '.Surfer::get_name().'. Please do not modify it manually.'."\n";
	if(isset($_REQUEST['banned_hosts']))
		$content .= '$context[\'banned_hosts\']=\''.addcslashes($_REQUEST['banned_hosts'], "\\'")."';\n";
	$content .= '?>'."\n";

	// update the parameters file
	if(!Safe::file_put_contents('parameters/servers.include.php', $content)) {

		Logger::error(sprintf(i18n::s('ERROR: Impossible to write to the file %s. The configuration has not been saved.'), 'parameters/servers.include.php'));

		// allow for a manual update
		$context['text'] .= '<p style="text-decoration: blink;">'.sprintf(i18n::s('To actually change the configuration, please copy and paste following lines by yourself in file %s.'), 'parameters/servers.include.php')."</p>\n";

	// job done
	} else {

		$context['text'] .= '<p>'.sprintf(i18n::s('The following configuration has been saved into the file %s.'), 'parameters/servers.include.php')."</p>\n";

		// purge the cache
		Cache::clear('servers');

		// remember the change
		$label = sprintf(i18n::c('%s has been updated'), 'parameters/servers.include.php');
		Logger::remember('servers/configure.php', $label);

	}

	// display updated parameters
	$context['text'] .= Skin::build_box(i18n::s('Configuration parameters'), Safe::highlight_string($content), 'folder');

	// follow-up commands
	$follow_up = i18n::s('Where do you want to go now?');
	$menu = array();
	$menu = array_merge($menu, array( 'servers/' => i18n::s('Servers') ));
	$menu = array_merge($menu, array( 'control/' => i18n::s('Control Panel') ));
	$menu = array_merge($menu, array( 'servers/configure.php' => i18n::s('Configure again') ));
	$follow_up .= Skin::build_list($menu, 'menu_bar');
	$context['text'] .= Skin::build_block($follow_up, 'bottom');

}

// render the skin
render_skin();

?>