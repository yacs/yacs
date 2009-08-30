<?php
/**
 * configure parameters for files
 *
 * Use this script to modify following parameters:
 *
 * [*] [code]files_extensions[/code] - an optional list of extensions to be accepted,
 * on top of the rich set of official extensions.
 *
 * [*] [code]files_on_ftp[/code] - if set to '[code]Y[/code]',
 * place files in a local area remotely accessible from FTP.
 *
 * [*] [code]files_path[/code] - the local path to files in the FTP area
 *
 * [*] [code]files_url[/code] - the URL prefix used to access the FTP area
 *
 * Configuration information is saved into [code]parameters/files.include.php[/code].
 * If YACS is prevented to write to the file, it displays parameters to allow for a manual update.
 *
 * The file [code]parameters/files.include.php.bak[/code] can be used to restore
 * the active configuration before the last change.
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
include_once 'files.php';

// load the skin
load_skin('files');

// the path to this page
$context['path_bar'] = array( 'control/' => i18n::s('Control Panel') );

// the title of the page
$context['page_title'] = sprintf(i18n::s('%s: %s'), i18n::s('Configure'), i18n::s('Files'));

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('files/configure.php'));

// only associates can proceed
elseif(!Surfer::is_associate()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// display the input form
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'POST')) {

	// load current parameters, if any
	Safe::load('parameters/files.include.php');

	// the form
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>';

	//
	// supported extensions
	//
	$extensions = '';

	// supported extensions
	$label = i18n::s('Supported extensions');
	include_once 'files.php';
	$input = implode(', ', array_keys(Files::get_mime_types()));
	$hint = i18n::s('Recommended tools are listed for each extension on download.');
	$fields[] = array($label, $input, $hint);

	// additional extensions
	$label = i18n::s('Additional extensions');
	if(!isset($context['files_extensions']))
		$context['files_extensions'] = '';
	$input = '<input type="text" name="files_extensions" size="65" value="'.encode_field($context['files_extensions']).'" maxlength="128" />';
	$hint = i18n::s('List extensions you would like to support, separated by spaces or commas.');
	$fields[] = array($label, $input, $hint);

	// build the form
	$extensions .= Skin::build_form($fields);
	$fields = array();

	//
	// file store
	//
	$store = '<p>'.i18n::s('By default files are stored into the web space of your server. To optimize the transfer of large files, you should setup an anonymous ftp service on your server, and then use this configuration panel to enable its usage.').'</p>';

	// use ftp
	$label = i18n::s('Use FTP');
	$input = '<input type="radio" name="files_on_ftp" id="files_on_ftp" value="N"';
	if(!isset($context['files_on_ftp']) || ($context['files_on_ftp'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('No, all uploaded files are placed in the web space.').BR;
	$input .= '<input type="radio" name="files_on_ftp" value="Y"';
	if(isset($context['files_on_ftp']) && ($context['files_on_ftp'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Yes, and following parameters are used for the mapping to the FTP service.')."\n";
	$fields[] = array($label, $input);

	// the path prefix
	if(!isset($context['files_path']))
		$context['files_path'] = '';
	$label = i18n::s('Local path');
	$input = '<input type="text" name="files_path" size="45" value="'.encode_field($context['files_path']).'" maxlength="255" />';
	$hint = i18n::s('The place where shared files will be written.');
	$fields[] = array($label, $input, $hint);

	// the ftp prefix
	if(!isset($context['files_url']))
		$context['files_url'] = '';
	$label = i18n::s('FTP prefix');
	$input = '<input type="text" name="files_url" size="45" value="'.encode_field($context['files_url']).'" maxlength="255" />';
	$hint = i18n::s('The ftp:// address that is inserted in links used to download files remotely');
	$fields[] = array($label, $input, $hint);

	// put the set of fields in the page
	$store .= Skin::build_form($fields);
	$fields = array();

	//
	// assemble all tabs
	//
	$all_tabs = array(
		array('extensions', i18n::s('Extensions'), 'extensions_panel', $extensions),
		array('store', i18n::s('Storage'), 'store_panel', $store)
		);

	// let YACS do the hard job
	$context['text'] .= Skin::build_tabs($all_tabs);

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
		$menu[] = Skin::build_link('files/', i18n::s('Files'), 'span');

	// insert the menu in the page
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// end of the form
	$context['text'] .= '</div></form>';

	// set the focus
	$context['text'] .= JS_PREFIX
		.'// set the focus on first form field'."\n"
		.'$("files_extensions").focus();'."\n"
		.JS_SUFFIX."\n";

	// general help on this form
	$help = '<p>'.i18n::s('Shared files are not put in the database, but in the file system of the web server.').'</p>'
		.'<p>'.i18n::s('If you cannot upload files because of permissions settings, use the configuration panel for users to disable all uploads.').'</p>';
	$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'navigation', 'help');

// no modifications in demo mode
} elseif(file_exists($context['path_to_root'].'parameters/demo.flag')) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation in demonstration mode.'));

// save updated parameters
} else {

	// backup the old version
	Safe::unlink($context['path_to_root'].'parameters/files.include.php.bak');
	Safe::rename($context['path_to_root'].'parameters/files.include.php', $context['path_to_root'].'parameters/files.include.php.bak');

	// build the new configuration file
	$content = '<?php'."\n"
		.'// This file has been created by the configuration script files/configure.php'."\n"
		.'// on '.gmdate("F j, Y, g:i a").' GMT, for '.Surfer::get_name().'. Please do not modify it manually.'."\n"
		.'global $context;'."\n";
	if(isset($_REQUEST['files_extensions']))
		$content .= '$context[\'files_extensions\']=\''.addcslashes($_REQUEST['files_extensions'], "\\'")."';\n";
	if(isset($_REQUEST['files_on_ftp']))
		$content .= '$context[\'files_on_ftp\']=\''.addcslashes($_REQUEST['files_on_ftp'], "\\'")."';\n";
	if(isset($_REQUEST['files_path']))
		$content .= '$context[\'files_path\']=\''.addcslashes($_REQUEST['files_path'], "\\'")."';\n";
	if(isset($_REQUEST['files_url']))
		$content .= '$context[\'files_url\']=\''.addcslashes($_REQUEST['files_url'], "\\'")."';\n";
	$content .= '?>'."\n";

	// update the parameters file
	if(!Safe::file_put_contents('parameters/files.include.php', $content)) {

		Logger::error(sprintf(i18n::s('ERROR: Impossible to write to the file %s. The configuration has not been saved.'), 'parameters/files.include.php'));

		// allow for a manual update
		$context['text'] .= '<p style="text-decoration: blink;">'.sprintf(i18n::s('To actually change the configuration, please copy and paste following lines by yourself in file %s.'), 'parameters/files.include.php')."</p>\n";

	// job done
	} else {

		$context['text'] .= '<p>'.sprintf(i18n::s('The following configuration has been saved into the file %s.'), 'parameters/files.include.php')."</p>\n";

		// purge the cache
		Cache::clear();

		// remember the change
		$label = sprintf(i18n::c('%s has been updated'), 'parameters/files.include.php');
		Logger::remember('files/configure.php', $label);

	}

	// display updated parameters
	$context['text'] .= Skin::build_box(i18n::s('Configuration parameters'), Safe::highlight_string($content), 'folded');

	// follow-up commands
	$follow_up = i18n::s('Where do you want to go now?');
	$menu = array();
	$menu = array_merge($menu, array( 'files/' => i18n::s('Files') ));
	$menu = array_merge($menu, array( 'control/' => i18n::s('Control Panel') ));
	$menu = array_merge($menu, array( 'files/configure.php' => i18n::s('Configure again') ));
	$follow_up .= Skin::build_list($menu, 'menu_bar');
	$context['text'] .= Skin::build_block($follow_up, 'bottom');

}

// render the skin
render_skin();

?>