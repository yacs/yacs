<?php
/**
 * switch the server off or on
 *
 * This page is used to switch the server on or off. Its usage is restricted to associates.
 *
 * The switch is made of a single file, named either '<code>parameters/switch.on</code>' or
 * '<code>parameters/switch.off</code>'.
 *
 * While switching off, you can optionnally specify:
 *
 * [*] A target server to redirect to. Useful to migrate from a server to another one.
 *
 * [*] Some contact information. Just in case you would like people to be able to react anyway.
 *
 * These parameters are saved into the file [code]parameters/switch.include.php[/code],
 * and used to adapt messages provided to surfers in [script]control/closed.php[/script].
 *
 * @see control/closed.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// include the global declarations
include_once '../shared/global.php';

// load localized strings
i18n::bind('control');

// load the skin
load_skin('control');

// the path to this page
$context['path_bar'] = array( 'control/' => i18n::s('Control Panel') );

// the title of the page
$context['page_title'] = i18n::s('Main Switch');

// only associates can used the switch
if(!Surfer::is_associate()) {

	// prevent access to this script
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

	// back to the control panel
	$menu = array('control/' => i18n::s('Control Panel'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// switch on
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'on')) {

	// delete switch parameters, if any
	Safe::unlink('../parameters/switch.include.php');

	// rename the switch file
	if(Safe::rename($context['path_to_root'].'parameters/switch.off', $context['path_to_root'].'parameters/switch.on')) {
		$context['text'] .= '<p>'.i18n::s('The server has been successfully switched on. Pages are provided normally to surfers.')."</p>\n";

		// clear the cache, to avoid side effects of complex updates
		$context['text'] .= Cache::clear();

		// remember the change
		$label = i18n::c('The server has been switched on.');
		Logger::remember('control/switch.php', $label);

	// if the server is currently switched on
	} elseif(file_exists($context['path_to_root'].'parameters/switch.on')) {
		$context['text'] .= '<p>'.i18n::s('The server is currently switched on. Pages are provided normally to surfers.')."</p>\n";

	// failure
	} else {
		Skin::error(i18n::s('The server has NOT been switched on successfully. Please rename the file parameters/switch.off to parameters/switch.on.'));
	}

	// back to the control panel
	$menu = array('control/' => i18n::s('Control Panel'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// switch off
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'off')) {

	// build the new configuration file
	$content = '<?php'."\n"
		.'// This file has been created by the configuration script control/switch.php'."\n"
		.'// on '.gmdate("F j, Y, g:i a").' GMT, for '.Surfer::get_name().'. Please do not modify it manually.'."\n"
		.'global $context;'."\n";
	if(isset($_REQUEST['switch_target']))
		$content .= '$context[\'switch_target\']=\''.addcslashes($_REQUEST['switch_target'], "\\'")."';\n";
	if(isset($_REQUEST['switch_contact']))
		$content .= '$context[\'switch_contact\']=\''.addcslashes($_REQUEST['switch_contact'], "\\'")."';\n";
	$content .= '?>'."\n";

	// save switch parameters, if any
	if(!Safe::file_put_contents('parameters/switch.include.php', $content)) {

		// not enough rights to write the file
		Skin::error(sprintf(i18n::s('Impossible to write to %s.'), 'parameters/switch.include.php.'));

		// allow for a manual update
		$context['text'] .= '<p style="text-decoration: blink;">'.sprintf(i18n::s('To actually change the configuration, please copy and paste following lines by yourself in file %s.'), 'parameters/switch.include.php')."</p>\n";

		// display updated parameters
		$context['text'] .= Skin::build_box(i18n::s('Configuration'), Safe::highlight_string($content), 'folder');

	}

	// rename the switch file
	if(Safe::rename($context['path_to_root'].'parameters/switch.on', $context['path_to_root'].'parameters/switch.off')) {

		Skin::error(i18n::s('The server has been switched OFF. Switch it back on as soon as possible. Keep your browser session open to do it, you would not be able to login again if you close it. In case of trouble you can manually rename the file <code>parameters/switch.off</code> to <code>parameters/switch.on</code>.'));

		// remember the change
		$label = i18n::c('The server has been switched off.');
		Logger::remember('control/switch.php', $label);

	// if the server is currently switched off
	} elseif(file_exists($context['path_to_root'].'parameters/switch.off'))
		Skin::error(i18n::s('The server is currently switched off. All users are redirected to the closed page.'));

	// failure
	else
		Skin::error(i18n::s('Impossible to rename the file parameters/switch.on to parameters/switch.off. Do it yourself manually if you like.'));

	// follow-up commands
	$menu = array();

	// do it again
	if(file_exists($context['path_to_root'].'parameters/switch.off'))
		$menu = array_merge($menu, array( 'control/switch.php?action=on' => i18n::s('Switch on') ));

	// control panel
	$menu = array_merge($menu, array( 'control/' => i18n::s('Control Panel') ));

	// display follow-up commands
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// confirmation is required
} else {

	// if the server is currently switched off
	if(file_exists($context['path_to_root'].'parameters/switch.off')) {

		// server status
		Skin::error(i18n::s('The server is currently switched off. All users are redirected to the closed page.'));

		// the confirmation question
		$context['text'] .= '<b>'.i18n::s('You are about to open the server again. Are you sure?')."</b>\n";

		// the menu for this page
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'"><p>'
			.Skin::build_submit_button(i18n::s('Yes, I do want to switch this server on'))
			.'<input type="hidden" name="action" value="on"'.EOT
			.'</p></form>'."\n";

	// else the server is currently switched on
	} else {

		// server status
		$context['text'] .= '<p>'.i18n::s('The server is currently switched on. Pages are provided normally to surfers.')."</p>\n";

		// the confirmation question
		$context['text'] .= '<p><b>'.i18n::s('You are about to close the server. Are you sure?')."</b></p>\n";

		// the menu for this page
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'"><div>'
			.Skin::build_submit_button(i18n::s('Yes, I do want to switch this server off'))
			.'<input type="hidden" name="action" value="off"'.EOT;

		// redirect
		$label = i18n::s('Option: redirect requests to the following address');
		$input = '<input type="text" name="switch_target" size="45" maxlength="255" />';
		$context['text'] .= '<p>'.$label.BR.$input."</p>\n";

		// contact information
		$label = i18n::s('Option: contact information, such as an email address');
		$input = '<input type="text" name="switch_contact" size="45" maxlength="255" />';
		$context['text'] .= '<p>'.$label.BR.$input."</p>\n";

		// end of the form
		$context['text'] .= '</div></form>'."\n";

	}

}

// render the skin
render_skin();

?>