<?php
/**
 * the main setup script
 *
 * This page is used for the very first installation of the server
 *
 * This script looks for following items:
 * - [code]parameters/control.include.php[/code]
 * - [code]parameters/hooks.include.php[/code]
 * - [code]parameters/skins.include.php[/code]
 *
 * If all of these items are absent, the script assumes this is a first installation,
 * welcomes the surfer, and offers to jump to [script]control/configure.php[/script].
 *
 * Also, the script performs several test to ensure:
 * - that the version of PHP is greater or equal than 4.3
 * - that the MySQL PHP extension is available
 * - that the XML parser of PHP is available
 * - that it can write to local files (configuration and log files)
 *
 * If one of these tests fails, the script advises the user on the right thing to do.
 * For example, it will offer to chmod the YACS installation directory is necessary.
 *
 * On first installation this script will behave like a setup assistant,
 * and the user will have to pass through successive screens:
 * - the configuration panel ([script]control/configure.php[/script]), to record database and other essential parameters
 * - the extension scanner ([script]control/scan.php[/script]), to load all hooks
 * - the database setup ([script]control/setup.php[/script]), to create tables in the database
 * - the populate screen ([script]control/populate.php[/script]), to create a basic set of records in the database
 * - the configuration panel for skins ([script]scripts/configure.php[/script]), to set rendering parameters
 *
 * @see control/configure.php
 * @see control/scan.php
 * @see control/setup.php
 * @see control/populate.php
 * @see scripts/configure.php
 *
 * If at least one file is missing, the script assumes there is an on-going installation,
 * and proposes to jump to the control panel at [script]control/index.php[/script].
 * In this case the control panel will redirect to the adequate script depending of the missing element.
 *
 * If all files are present, but there is no [code]parameters/switch.on[/code] nor [code]parameters/switch.off[/code] file, the script
 * assumes the installation ends correctly, creates [code]parameters/switch.on[/code], and offers to jump to the
 * control panel at [script]control/index.php[/script].
 * Also, if there is no index page at the upper directory level, the script will silently attempt
 * to duplicate index.php there.
 *
 * Else the script assumes there is no need for an installation,
 * and offers to jump to the control panel at [script]control/index.php[/script].
 *
 * @see control/index.php
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// include explicitly some libraries
if(!is_readable('shared/global.php'))
	exit('No shared library. Please copy the entire set of files in the provided archive.');
include_once 'shared/global.php';

// load localized strings
i18n::bind('root');

// load the skin
load_skin('setup');

// check key files
$missing = 0;
if(!file_exists('parameters/control.include.php'))
	$missing++;
if(!file_exists('parameters/hooks.include.php'))
	$missing++;
if(!file_exists('parameters/skins.include.php'))
	$missing++;

// first installation
if($missing == 3) {

	// title
	$context['page_title'] = i18n::s('First installation');

	// to report on checks
	$checks = array();

	// ensure we have a minimum version of PHP
	if(version_compare(phpversion(),'4.3','<')) {

		// provide instructions
		Skin::error(sprintf(i18n::s('ERROR: YACS requires at least PHP version 4.3. The server runs version %s.'), phpversion()));

		$context['text'] .= '<p><a href="setup.php">'.i18n::s('Check PHP version again')."</a></p>\n";

		// check
		$check = i18n::s('ERROR');

	} else
		$check = i18n::s('OK');

	// check
	$checks[] = array(i18n::s('PHP'), phpversion(), $check);

	// ensure we have MySQL
	if(!SQL::check()) {

		// provide instructions
		Skin::error(i18n::s('ERROR: YACS requires the MySQL PHP extension.'));

		$context['text'] .= '<p><a href="setup.php">'.i18n::s('Check the MySQL PHP extension again')."</a></p>\n";

		// check
		$value = i18n::s('Absent');
		$check = i18n::s('ERROR');

	} else {
		$value = i18n::s('Present');
		$check = i18n::s('OK');
	}

	// check
	$checks[] = array(i18n::s('MySQL'), $value, $check);

	// ensure we can handle XML
	if(!is_callable('xml_parser_create')) {

		// provide instructions
		Skin::error(i18n::s('ERROR: YACS requires the XML PHP extension.'));

		$context['text'] .= '<p><a href="setup.php">'.i18n::s('Check the XML PHP extension again')."</a></p>\n";

		// check
		$value = i18n::s('Absent');
		$check = i18n::s('ERROR');

	} else {
		$value = i18n::s('Present');
		$check = i18n::s('OK');
	}

	// check
	$checks[] = array(i18n::s('XML'), $value, $check);

	// ensure we can handle ZIP files
	if(!is_callable('zip_open')) {
		$context['text'] .= '<p>'.i18n::s('WARNING: You will not be able to upload zip files.')."</p>\n";
		$context['text'] .= '<p><a href="setup.php">'.i18n::s('Check the ZIP PHP extension again')."</a></p>\n";

		// check
		$value = i18n::s('Absent');
		$check = i18n::s('WARNING');

	} else {
		$value = i18n::s('Present');
		$check = i18n::s('OK');
	}

	// check
	$checks[] = array(i18n::s('zip'), $value, $check);

	// show evidence of safe mode
	if(Safe::ini_get('safe_mode')) {
		$context['text'] .= '<p>'.i18n::s('WARNING: This server runs in safe mode, and YACS may be prevented to perform a number of key operations.')."</p>\n";

		// check
		$value = i18n::s('Yes');
		$check = i18n::s('WARNING');

	} else {
		$value = i18n::s('No');
		$check = i18n::s('OK');
	}

	// check
	$checks[] = array(i18n::s('Safe mode'), $value, $check);

	// test our ability to write to files
	$can_write = TRUE;

	// actual attempt to write to representative files
	$files = array();
	$files[] = './parameters/agents.include.php';
	$files[] = './parameters/collections.include.php';
	$files[] = './parameters/control.include.php';
	$files[] = './parameters/feeds.include.php';
	$files[] = './parameters/files.include.php';
	$files[] = './parameters/hooks.include.php';
	$files[] = './parameters/hooks.xml';
	$files[] = './parameters/letters.include.php';
	$files[] = './parameters/scripts.include.php';
	$files[] = './parameters/servers.include.php';
	$files[] = './parameters/services.include.php';
	$files[] = './parameters/skins.include.php';
	$files[] = './parameters/switch.on';
	$files[] = './parameters/users.include.php';
	$files[] = './temporary/debug.txt';
	$files[] = './temporary/ie_bookmarklet.html';
	$files[] = './temporary/ie_bookmarklet.reg';
	$files[] = './temporary/log.txt';
	foreach($files as $file) {

		// test one file at a time
		if(!Safe::is_writable($file)) {
			$context['text'] .= sprintf(i18n::s('Impossible to write to %s.'), $file).BR;
			$can_write = FALSE;
		}
	}

	// please chmod or chown files
	if(!$can_write) {

		// provide instructions
		$context['text'] .= '<p>'.i18n::s('WARNING: YACS cannot write to files. If you are running some Unix, please ensure that permissions have been properly set. This issue can also be due to server running in safe mode.')."</p>\n";

		$context['text'] .= '<p>'.sprintf(i18n::s('Check the provided %s file to find more help on file permissions.'), '<a href="'.i18n::s('readme.txt').'">'.i18n::s('readme.txt').'</a>')."</p>\n";

		$context['text'] .= '<p><a href="setup.php">'.i18n::s('Check again our ability to write to files')."</a></p>\n";


		// check
		$value = i18n::s('Configuration files cannot be changed');
		$check = i18n::s('WARNING');

	} else {
		$value = i18n::s('May change configuration files');
		$check = i18n::s('OK');
	}

	// check
	$checks[] = array(i18n::s('Write permissions'), $value, $check);

	// report on prerequisite checks
	$context['text'] .= '<h2>'.i18n::s('Pre-installation checks').'</h2>';

	// check results
	$context['text'] .= '<table border="1">';
	$row_count = 1;
	foreach($checks as $cells)
		$context['text'] .= Skin::table_row($cells, $row_count++);
	$context['text'] .= '</table>';

	// link to the readme file
	$context['text'] .= '<p>'.sprintf(i18n::s('Check the provided %s file to ensure prerequisites are fulfilled.'), '<a href="'.i18n::s('readme.txt').'">'.i18n::s('readme.txt').'</a>')."</p>\n";

	// link to the configuration page
	if(!count($context['error'])) {

		// title
		$context['page_title'] = i18n::s('Welcome in the YACS setup assistant');

		// report on checks
		$context['text'] .= '<h2>'.i18n::s('Ready to start the installation').'</h2>';

		// splash screen
		$context['text'] .= i18n::s("<p>At the moment no configuration file has been found. You will now have to pass through several steps in order to achieve the setup of your server:</p>\n<ul>\n<li>Configure parameters related to the database.</li>\n<li>Load extension hooks.</li>\n<li>Create tables in the database.</li>\n<li>Add one user profile and populate the database.</li>\n<li>Configure the skin of your server.</li>\n</ul>\nIn normal conditions this will take only some minutes. If you have any problems, please consult <a href=\"http://www.yetanothercommunitysystem.com/\">www.yetanothercommunitysystem.com</a> for additional support.<p>Thank you for having selected the YACS solution.</p>")."\n";

		// add a button to start the installation process
		$context['text'] .= '<form method="get" action="control/configure.php" id="main_form">'."\n"
			.'<p>'.Skin::build_submit_button(i18n::s('Start the installation process'), NULL, NULL, 'confirmed').'</p>'."\n"
			.'</form>'."\n";

		// a place holder for cookies activation
		$context['text'] .= '<p id="ask_for_cookies" style="display: none; color: red; text-decoration: blink;"></p>';

		// the script used to check that cookies are activated
		$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
			.'document.cookie = \'CookiesEnabled=1\';'."\n"
			.'if((document.cookie == \'\') && document.getElementById) {'."\n"
			.'	document.getElementById(\'ask_for_cookies\').innerhtml = \''.i18n::s('You must enable cookies to manage this server. Change settings of your browser accordingly, then revisit this page afterwards.').'\';'."\n"
			.'	document.getElementById(\'ask_for_cookies\').style.display = \'block\';'."\n"
			.'	document.getElementById(\'confirmed\').disabled = true;'."\n"
			.'}'."\n"
			.'// ]]></script>'."\n";

		// purge the scripts/run_once directory on first installation
		include_once $context['path_to_root'].'scripts/scripts.php';
		Scripts::purge_run_once();

	}

// on-going installation
} elseif($missing) {

	// title
	$context['page_title'] = i18n::s('Incomplete installation');

	// splash screen
	$context['text'] .= '<p>'.i18n::s('Some configuration files are missing. Please follow the link to complete the installation process.')."</p>\n";

	// to the control panel
	$context['text'] .= '<p><a href="control/">'.i18n::s('Control panel')."</a></p>\n";

// end of the installation
} elseif(!file_exists('parameters/switch.on') && !file_exists('parameters/switch.off')) {

	// the title of the page
	$context['page_title'] = i18n::s('End of installation');

	// create the switch
	$content = '---------------------------------------------'."\n"
		.'YACS will process requests if this file is named switch.on,'."\n"
		.'and will redirect everything to control/closed.php if its name is changed to switch.off.'."\n"
		."\n"
		.'Associates can use the script control/switch.php to stop and restart remotely.'."\n"
		.'---------------------------------------------'."\n";
	if(!Safe::file_put_contents('parameters/switch.on', $content)) {

		// not enough rights to write the file
		Skin::error(i18n::s('ERROR: YACS cannot create the file parameters/switch.on to activate the server.'));

		// allow for a manual update
		$context['text'] .= '<p style="text-decoration: blink;">'.sprintf(i18n::s('To actually switch on the server, please copy and paste following lines by yourself in file %s.'), 'parameters/switch.on')."</p>\n";

		// content of the switch file
		$context['text'] .= '<pre>'.$content."</pre>\n";

	}

	// if there is no index at the upper level
	if(!file_exists($context['path_to_root'].'../index.php') && ($content = Safe::file_get_contents($context['path_to_root'].'index.php'))) {

		// silently attempt to duplicate our index
		Safe::file_put_contents('../index.php', $content);

		// remember this for the next incremental update
		$content = '<?php'."\n"
			.'// This file has been created by the setup script setup.php'."\n"
			.'// on '.gmdate("F j, Y, g:i a").' GMT, for '.Surfer::get_name().'. Please do not modify it manually.'."\n"
			.'$context[\'home_at_root\']=\'Y\';'."\n"
			.'$context[\'reference_server\']=\''.addcslashes(i18n::s('www.yetanothercommunitysystem.com'), "\\'")."';\n"
			.'?>'."\n";
		Safe::file_put_contents('parameters/scripts.include.php', $content);

	}

	// the splash message
	$context['text'] .= sprintf(i18n::s("<p>You have passed through the several installation steps.</p>\nWhat do you want to do now?<ul>\n<li>Select %s for your site.</li>\n<li>Populate your site with the %s.</li>\n<li>Manage everything from the %s.</li>\n<li>Check the %s of this site.</li>\n<li>Review your %s.</li>\n<li>Create a %s.</li>\n<li>Look at the %s.</li>\n<li>Visit %s to learn more.</li>\n</ul>\n<p>Thank you for having selected to use YACS for your web site.</p>\n"),
		Skin::build_link('skins/', i18n::s('another skin')),
		Skin::build_link('control/populate.php', i18n::s('Content Assistant')),
		Skin::build_link('control/', i18n::s('Control Panel')),
		Skin::build_link($context['url_to_root'], i18n::s('main page')),
		Skin::build_link('users/view.php', i18n::s('user profile')),
		Skin::build_link('articles/edit.php', i18n::s('new page')),
		Skin::build_link('help.php', i18n::s('help index page')),
		Skin::build_link(i18n::s('http://www.yetanothercommunitysystem.com/'), i18n::s('www.yetanothercommunitysystem.com'), 'external'))."\n";

// no need for installation
} else {

	// the title of the page
	$context['page_title'] = i18n::s('No need for setup');

	// the splash message
	$context['text'] .= i18n::s('<p>Since basic configuration files exist on your server, it is likely that the installation has been achieved successfully. Click on the link below to modify the running parameters of your server.</p>')."\n";

	// to the control panel
	$context['text'] .= '<p><a href="control/">'.i18n::s('Go to the control panel')."</a></p>\n";

}

// render the skin
render_skin();
?>