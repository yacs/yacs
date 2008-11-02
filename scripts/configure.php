<?php
/**
 * change parameters for scripts
 *
 * Use this script to modify following parameters:
 *
 * - [code]reference_server[/code] - the name or the IP address
 * of the yacs server hosting the repository of reference scripts for updates.
 *
 * - [code]home_at_root[/code] - if set to '[code]Y[/code]',
 * during script updates if the main index page ([code]/yacs/index.php[/code]) is updated
 * it will also be duplicated as the server front page ([code]index.php[/code]).
 *
 * Configuration information is saved into [code]parameters/scripts.include.php[/code].
 * If YACS is prevented to write to the file, it displays parameters to allow for a manual update.
 *
 * The file [code]parameters/scripts.include.php.bak[/code] can be used to restore
 * the active configuration before the last change.
 *
 * If the file [code]demo.flag[/code] exists, the script assumes that this instance
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

// load localized strings
i18n::bind('scripts');

// load the skin
load_skin('scripts');

// the path to this page
$context['path_bar'] = array( 'control/' => i18n::s('Control Panel') );

// the title of the page
$context['page_title'] = sprintf(i18n::s('%s: %s'), i18n::s('Configure'), i18n::s('Server software'));

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('scripts/configure.php'));

// only associates can proceed
elseif(!Surfer::is_associate()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// get the input parameters
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'POST')) {

	// load current parameters, if any
	Safe::load('parameters/scripts.include.php');

	// the form
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>';

	// the reference server
	if(!isset($context['reference_server']))
		$context['reference_server'] = i18n::s('www.yetanothercommunitysystem.com');
	$label = i18n::s('If you are using some reference server to download updated pieces of software, please type its address below (name or IP address)');
	$input = '<input type="text" name="reference_server" id="reference_server" size="45" value="'.encode_field($context['reference_server']).'" maxlength="255" />';
	$context['text'] .= '<p>'.$label.BR.$input."</p>\n";

	// we are not at the front page
	if(strcmp($context['url_to_root'], '/')) {

		// index.php has to be duplicated
		$label = i18n::s('Update the front page of this server:');
		$input = '<input type="radio" name="home_at_root" value="N"';
		if(!isset($context['home_at_root']) || ($context['home_at_root'] != 'Y'))
			$input .= ' checked="checked"';
		$input .= '/> '.i18n::s('No, the front page of this server is not managed by YACS.');
		$input .= BR.'<input type="radio" name="home_at_root" value="Y"';
		if(isset($context['home_at_root']) && ($context['home_at_root'] == 'Y'))
			$input .= ' checked="checked"';
		$input .= '/> '.i18n::s('Yes. If the script index.php is updated into the YACS directory, it will be duplicated at the upper directory as well');
		$context['text'] .= '<p>'.$label.BR.$input."</p>\n";

	}

	//
	// bottom commands
	//
	$menu = array();

	// the submit button
	$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');

	// control panel
	if(file_exists('../parameters/control.include.php'))
		$menu[] = Skin::build_link('control/', i18n::s('Control Panel'), 'span');

	// all skins
	if(file_exists('../parameters/control.include.php'))
		$menu[] = Skin::build_link('scripts/', i18n::s('Server software'), 'span');

	// insert the menu in the page
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// end of the form
	$context['text'] .= '</div></form>';

	// set the focus
	$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
		.'// set the focus on first form field'."\n"
		.'$("reference_server").focus();'."\n"
		.'// ]]></script>'."\n";

	// general help on this form
	$help = '<p>'.i18n::s('Indicate only the DNS name or IP address of the reference server; nothing more, nothing less.').'</p>';

// no modifications in demo mode
} elseif(file_exists($context['path_to_root'].'parameters/demo.flag')) {

	// remind the surfer
	$context['text'] .= '<p>'.i18n::s('You are not allowed to perform this operation in demonstration mode.').'</p>';

// save updated parameters
} else {

	// backup the old version
	Safe::unlink($context['path_to_root'].'parameters/scripts.include.php.bak');
	Safe::rename($context['path_to_root'].'parameters/scripts.include.php', $context['path_to_root'].'parameters/scripts.include.php.bak');

	// build the new configuration file
	$content = '<?php'."\n"
		.'// This file has been created by the configuration script scripts/configure.php'."\n"
		.'// on '.gmdate("F j, Y, g:i a").' GMT, for '.Surfer::get_name().'. Please do not modify it manually.'."\n"
		.'$context[\'home_at_root\']=\''.addcslashes($_REQUEST['home_at_root'], "\\'")."';\n"
		.'$context[\'reference_server\']=\''.addcslashes($_REQUEST['reference_server'], "\\'")."';\n"
		.'?>'."\n";

	// update the parameters file
	if(!Safe::file_put_contents('parameters/scripts.include.php', $content)) {

		Logger::error(sprintf(i18n::s('ERROR: Impossible to write to the file %s. The configuration has not been saved.'), 'parameters/scripts.include.php'));

		// allow for a manual update
		$context['text'] .= '<p style="text-decoration: blink;">'.sprintf(i18n::s('To actually change the configuration, please copy and paste following lines by yourself in file %s.'), 'parameters/scripts.include.php')."</p>\n";

	// job done
	} else {

		$context['text'] .= '<p>'.sprintf(i18n::s('The following configuration has been saved into the file %s.'), 'parameters/scripts.include.php')."</p>\n";

		// purge the cache
		Cache::clear();

		// remember the change
		$label = sprintf(i18n::c('%s has been updated'), 'parameters/scripts.include.php');
		Logger::remember('scripts/configure.php', $label);

	}

	// display updated parameters
	$context['text'] .= Skin::build_box(i18n::s('Configuration parameters'), Safe::highlight_string($content), 'folder');

	// follow-up commands
	$follow_up = i18n::s('Where do you want to go now?');
	$menu = array();
	$menu = array_merge($menu, array( 'scripts/stage.php' => i18n::s('Stage updated scripts') ));
	$menu = array_merge($menu, array( 'scripts/' => i18n::s('Server software') ));
	$menu = array_merge($menu, array( 'control/' => i18n::s('Control Panel') ));
	$menu = array_merge($menu, array( 'scripts/configure.php' => i18n::s('Configure again') ));
	$follow_up .= Skin::build_list($menu, 'page_menu');
	$context['text'] .= Skin::build_block($follow_up, 'bottom');

}

// render the skin
render_skin();

?>