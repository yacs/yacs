<?php
/**
 * change parameters for web services
 *
 * Use this script to modify following parameters:
 *
 * - [code]debug_blog[/code] - if set to '[code]Y[/code]',
 * save into [code]temporary/debug.txt[/code] data sent and received in [script]services/blog.php[/script].
 *
 * - [code]debug_call[/code] - if set to '[code]Y[/code]',
 * save into [code]temporary/debug.txt[/code] data sent and received in [script]services/call.php[/script].
 *
 * - [code]debug_comment[/code] - if set to '[code]Y[/code]',
 * save into [code]temporary/debug.txt[/code] data sent and received in [script]comments/post.php[/script].
 *
 * - [code]debug_ping[/code] - if set to '[code]Y[/code]',
 * save into [code]temporary/debug.txt[/code] data sent and received in [script]services/ping.php[/script].
 *
 * - [code]debug_rpc[/code] - if set to '[code]Y[/code]',
 * save into [code]temporary/debug.txt[/code] data sent and received in [script]services/json_rpc.php[/script] and in [script]services/xml_rpc.php[/script].
 *
 * - [code]debug_trackback[/code] - if set to '[code]Y[/code]',
 * save into [code]temporary/debug.txt[/code] data sent and received in [script]links/trackback.php[/script].
 * Also save data sent as trackback client in [script]links/links.php[/script].
 * Also save pingback data sent and receive in [script]services/ping.php[/script].
 *
 * - [code]google_api_key[/code] - according to your registration to
 * Google web services.
 *
 * - [code]google_urchin_account[/code] - according to your registration to
 * Google Analytics services.
 *
 * Configuration information is saved into [code]parameters/services.include.php[/code].
 * If YACS is prevented to write to the file, it displays parameters to allow for a manual update.
 *
 * The file [code]parameters/services.include.php.bak[/code] can be used to restore
 * the active configuration before the last change, if necessary.
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
i18n::bind('services');

// load the skin
load_skin('services');

// the path to this page
$context['path_bar'] = array( 'control/' => i18n::s('Control Panel') );

// the title of the page
$context['page_title'] = sprintf(i18n::s('%s: %s'), i18n::s('Configure'), i18n::s('Web services'));

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('services/configure.php'));

// only associates can proceed
elseif(!Surfer::is_associate()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// display the input form
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'POST')) {

	// the form
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>';

	// Google API key
	$label = i18n::s('Google API key');
	if(!isset($context['google_api_key']) || !$context['google_api_key'])
		$context['google_api_key'] = '';
	$input = '<input type="text" name="google_api_key" size="45" value="'.encode_field($context['google_api_key']).'" maxlength="255" />';
	$hint = sprintf(i18n::s('To integrate Google Maps to your server, %s and enter it here'), Skin::build_link(i18n::s('http://www.google.com/apis/maps/signup.html'), i18n::s('apply for a key')));
	$fields[] = array($label, $input, $hint);

	// Google Analytics Urchin account
	$label = i18n::s('Google Analytics');
	if(!isset($context['google_urchin_account']) || !$context['google_urchin_account'])
		$context['google_urchin_account'] = '';
	$input = '<input type="text" name="google_urchin_account" size="45" value="'.encode_field($context['google_urchin_account']).'" maxlength="255" />';
	$hint = sprintf(i18n::s('To monitor your server with Google Analytics, %s and enter your Urchin account here'), Skin::build_link(i18n::s('http://www.google-analytics.com/'), i18n::s('register')));
	$fields[] = array($label, $input, $hint);

	// debug_blog
	$label = i18n::s('Debug blog services');
	$checked = '';
	if(isset($context['debug_blog'])&& ($context['debug_blog'] == 'Y'))
		$checked = 'checked="checked"';
	$input = '<input type="checkbox" name="debug_blog" value="Y" '.$checked.'/> '.i18n::s('Copy network packets sent and received by services/blog.php in temporary/debug.txt');
	$hint = i18n::s('Use this option only for troubleshooting');
	$fields[] = array($label, $input, $hint);

	// debug_comment
	$label = i18n::s('Debug commenting services');
	$checked = '';
	if(isset($context['debug_comment'])&& ($context['debug_comment'] == 'Y'))
		$checked = 'checked="checked"';
	$input = '<input type="checkbox" name="debug_comment" value="Y" '.$checked.'/> '.i18n::s('Copy network packets sent and received by comments/post.php in temporary/debug.txt');
	$hint = i18n::s('Use this option only for troubleshooting');
	$fields[] = array($label, $input, $hint);

	// debug_call
	$label = i18n::s('Debug calls of web services');
	$checked = '';
	if(isset($context['debug_call'])&& ($context['debug_call'] == 'Y'))
		$checked = 'checked="checked"';
	$input = '<input type="checkbox" name="debug_call" value="Y" '.$checked.'/> '.i18n::s('Copy network packets sent and received by services/call.php in temporary/debug.txt');
	$hint = i18n::s('Use this option only for troubleshooting');
	$fields[] = array($label, $input, $hint);

	// debug_ping
	$label = i18n::s('Debug ping services');
	$checked = '';
	if(isset($context['debug_ping'])&& ($context['debug_ping'] == 'Y'))
		$checked = 'checked="checked"';
	$input = '<input type="checkbox" name="debug_ping" value="Y" '.$checked.'/> '.i18n::s('Copy network packets sent and received by services/ping.php in temporary/debug.txt');
	$hint = i18n::s('Use this option only for troubleshooting');
	$fields[] = array($label, $input, $hint);

	// debug_rpc
	$label = i18n::s('Debug remote procedure calls');
	$checked = '';
	if(isset($context['debug_rpc'])&& ($context['debug_rpc'] == 'Y'))
		$checked = 'checked="checked"';
	$input = '<input type="checkbox" name="debug_rpc" value="Y" '.$checked.'/> '.i18n::s('Copy network packets sent and received by services/json_rpc.php and by services/xml_rpc.php in temporary/debug.txt');
	$hint = i18n::s('Use this option only for troubleshooting');
	$fields[] = array($label, $input, $hint);

	// debug_trackback
	$label = i18n::s('Debug pingback and trackback services');
	$checked = '';
	if(isset($context['debug_trackback'])&& ($context['debug_trackback'] == 'Y'))
		$checked = 'checked="checked"';
	$input = '<input type="checkbox" name="debug_trackback" value="Y" '.$checked.'/> '.i18n::s('Copy network packets sent and received by links/trackback.php in temporary/debug.txt');
	$hint = i18n::s('Use this option only for troubleshooting');
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
		$menu[] = Skin::build_link('services/', i18n::s('Web services'), 'span');

	// insert the menu in the page
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// end of the form
	$context['text'] .= '</div></form>';

// no modifications in demo mode
} elseif(file_exists($context['path_to_root'].'parameters/demo.flag')) {

	// remind the surfer
	$context['text'] .= '<p>'.i18n::s('You are not allowed to perform this operation in demonstration mode.').'</p>'."\n";

// save updated parameters
} else {

	// backup the old version
	Safe::unlink($context['path_to_root'].'parameters/services.include.php.bak');
	Safe::rename($context['path_to_root'].'parameters/services.include.php', $context['path_to_root'].'parameters/services.include.php.bak');

	// build the new configuration file
	$content = '<?php'."\n"
		.'// This file has been created by the configuration script services/configure.php'."\n"
		.'// on '.gmdate("F j, Y, g:i a").' GMT, for '.Surfer::get_name().'. Please do not modify it manually.'."\n";
	if(isset($_REQUEST['debug_blog']))
		$content .= '$context[\'debug_blog\']=\''.addcslashes($_REQUEST['debug_blog'], "\\'")."';\n";
	if(isset($_REQUEST['debug_call']))
		$content .= '$context[\'debug_call\']=\''.addcslashes($_REQUEST['debug_call'], "\\'")."';\n";
	if(isset($_REQUEST['debug_comment']))
		$content .= '$context[\'debug_comment\']=\''.addcslashes($_REQUEST['debug_comment'], "\\'")."';\n";
	if(isset($_REQUEST['debug_ping']))
		$content .= '$context[\'debug_ping\']=\''.addcslashes($_REQUEST['debug_ping'], "\\'")."';\n";
	if(isset($_REQUEST['debug_rpc']))
		$content .= '$context[\'debug_rpc\']=\''.addcslashes($_REQUEST['debug_rpc'], "\\'")."';\n";
	if(isset($_REQUEST['debug_trackback']))
		$content .= '$context[\'debug_trackback\']=\''.addcslashes($_REQUEST['debug_trackback'], "\\'")."';\n";
	if(isset($_REQUEST['google_api_key']))
		$content .= '$context[\'google_api_key\']=\''.addcslashes($_REQUEST['google_api_key'], "\\'")."';\n";
	if(isset($_REQUEST['google_urchin_account']))
		$content .= '$context[\'google_urchin_account\']=\''.addcslashes($_REQUEST['google_urchin_account'], "\\'")."';\n";
	$content .= '?>'."\n";

	// update the parameters file
	if(!Safe::file_put_contents('parameters/services.include.php', $content)) {

		Logger::error(sprintf(i18n::s('ERROR: Impossible to write to the file %s. The configuration has not been saved.'), 'parameters/services.include.php'));

		// allow for a manual update
		$context['text'] .= '<p style="text-decoration: blink;">'.sprintf(i18n::s('To actually change the configuration, please copy and paste following lines by yourself in file %s.'), 'parameters/services.include.php')."</p>\n";

	// job done
	} else {

		$context['text'] .= '<p>'.sprintf(i18n::s('The following configuration has been saved into the file %s.'), 'parameters/services.include.php')."</p>\n";

		// purge the cache
		Cache::clear();

		// remember the change
		$label = sprintf(i18n::c('%s has been updated'), 'parameters/services.include.php');
		Logger::remember('services/configure.php', $label);

	}

	// display updated parameters
	$context['text'] .= Skin::build_box(i18n::s('Configuration parameters'), Safe::highlight_string($content), 'folder');

	// follow-up commands
	$follow_up = i18n::s('Where do you want to go now?');
	$menu = array();
	$menu = array_merge($menu, array( 'services/' => i18n::s('Web services') ));
	$menu = array_merge($menu, array( 'control/' => i18n::s('Control Panel') ));
	$menu = array_merge($menu, array( 'services/configure.php' => i18n::s('Configure again') ));
	$follow_up .= Skin::build_list($menu, 'page_menu');
	$context['text'] .= Skin::build_block($follow_up, 'bottom');

}

// render the skin
render_skin();

?>