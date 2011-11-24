<?php
/**
 * change parameters for meetings
 *
 * Use this script to modify following parameters:
 *
 * - [code]bbb_server[/code] - host name of the server that hosts BigBlueButton meetings
 *
 * - [code]bbb_salt[/code] - secret shared with the server, used to compute checksums
 *
 * Configuration information is saved into [code]parameters/overlays.bbb_meetings.include.php[/code].
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

// load the skin
load_skin('overlays');

// the path to this page
$local['control_en'] = 'Control Panel';
$local['control_fr'] = 'Panneau de contr&ocirc;le';
$context['path_bar'] = array( 'control/index.php' => i18n::user('control') );

// page title
$context['page_title'] = i18n::s('Configure: BigBlueButton meetings');

// only associates can proceed
if(!Surfer::is_associate()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	$context['error'] .= '<p>'.i18n::s('You are not allowed to perform this operation.').'</p>';

// display the input form
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'POST')) {

	// load current parameters, if any
	Safe::load('parameters/overlays.bbb_meetings.include.php');

	// the form
	$context['text'] .= '<form method="post" action="'.$context['self_script'].'" id="main_form">';
	$fields = array();

	// server name
	$label = i18n::s('BBB server');
	$input = '<input type="text" name="bbb_server" id="bbb_server" size="32" value="'.encode_field(isset($context['bbb_server']) ? $context['bbb_server'] : '').'" maxlength="255" />';
	$fields[] = array($label, $input);

	// server salt
	$label = i18n::s('Security salt');
	$input = '<input type="text" name="bbb_salt" id="bbb_salt" size="32" value="'.encode_field(isset($context['bbb_salt']) ? $context['bbb_salt'] : '').'" maxlength="255" />';
	$fields[] = array($label, $input);

	// build the form
	$context['text'] .= Skin::build_form($fields);
	$fields = array();

	// bottom commands
	$menu = array();

	// the submit button
	$local['submit_en'] = 'Submit';
	$local['submit_fr'] = 'Enregistrer';
	$local['title_en'] = 'Press [s] to save data';
	$local['title_fr'] = 'Appuyer sur [s] pour enregistrer les donn&eacute;es';
	$menu[] = Skin::build_submit_button(i18n::user('submit'), i18n::user('title'), 's');

	// control panel
	$menu[] = Skin::build_link('control/', i18n::user('Control Panel'), 'span');

	// insert the menu in the page
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// end of the form
	$context['text'] .= '</form>';

// no modifications in demo mode
} elseif(file_exists($context['path_to_root'].'parameters/demo.flag')) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// save updated parameters
} else {

	// build the new configuration file
	$content = '<?php'."\n"
		.'// This file has been created by the configuration script overlays/bbb_meetings/configure.php'."\n"
		.'// on '.gmdate("F j, Y, g:i a").' GMT, for '.Surfer::get_name().'. Please do not modify it manually.'."\n";

	if(isset($_REQUEST['bbb_server']))
		$content .= '$context[\'bbb_server\']=\''.addcslashes($_REQUEST['bbb_server'], "\\'")."';\n";

	if(isset($_REQUEST['bbb_salt']))
		$content .= '$context[\'bbb_salt\']=\''.addcslashes($_REQUEST['bbb_salt'], "\\'")."';\n";

	$content .= '?>'."\n";

	// update the parameters file
	if(!Safe::file_put_contents('parameters/overlays.bbb_meetings.include.php', $content)) {

		Logger::error(sprintf(i18n::s('ERROR: Impossible to write to the file %s. The configuration has not been saved.'), 'parameters/overlays.bbb_meetings.include.php'));

	// report to end-user
	} else {

		$context['text'] .= '<p>'.sprintf(i18n::s('The following configuration has been saved into the file %s.'), 'parameters/overlays.bbb_meetings.include.php')."</p>\n";

		// purge the cache
		Cache::clear();

		// remember the change
		$label = sprintf(i18n::c('%s has been updated'), 'parameters/overlays.bbb_meetings.include.php');
		Logger::remember('overlays/bbb_meetings/configure.php', $label);

		// display updated parameters
		$context['text'] .= Skin::build_box(i18n::s('Configuration parameters'), Safe::highlight_string($content), 'folded');

		// follow-up commands
		$follow_up = i18n::s('Where do you want to go now?');
		$menu = array();
		$menu = array_merge($menu, array( 'control/' => i18n::s('Control Panel') ));
		$menu = array_merge($menu, array( 'overlays/bbb_meetings/configure.php' => i18n::s('Configure again') ));
		$follow_up .= Skin::build_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	}
}

// render the skin
render_skin();

?>