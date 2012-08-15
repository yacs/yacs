<?php
/**
 * build the reference store for this server
 *
 * This script behaves as follows:
 * - the script aborts if the user is not an associate
 * - else if this is not a POST, a confirmation form is displayed
 * - else the reference store is rebuilt, except in demo mode
 *
 * The confirmation form allows for following parameters:
 *
 * [*] Version information, that will be displayed within the Control Panel.
 * For the core set of reference files the rule is to use the last digit of the current
 * year as major version number, and the month index as minor version number.
 * Letters 'a' and 'b' are appended for alpha and beta releases, respectively.
 *
 * [*] Checkbox to disallow remote updates after the build.
 * By default the checkbox is checked, meaning that any remote servers can update
 * itself against the new reference store.
 * If you uncheck this checkbox, the footprints file is not generated.
 * This option is useful during integration phases, when the content of the
 * reference store has to be checked before official release.
 *
 * If the file [code]parameters/demo.flag[/code] exists, the script assumes that this instance
 * of YACS runs in demonstration mode and forbids the build.
 *
 * The building process is made of several successive steps:
 *
 * 1. The footprints file is deleted to disable remote updates.
 *
 * 2. Reference scripts are copied to the reference store
 * All running .php scripts are scanned for the keyword '@reference'.
 * Matching scripts are copied to the reference store.
 * Files that where present in the reference repository before the build are preserved.
 * This is the mechanism used to put any kind of files such as images, cascading style sheets, or even PHP scripts,
 * in the reference store.
 *
 * 3. Documentation pages are deleted.
 * This means that the table [code]yacs_phpdoc[/code] is emptied.
 * Files in the reference store are not impacted at this step.
 *
 * 4. Documentation pages are built based on phpDoc comments.
 * All php scripts of the reference store are scanned.
 * For each script having some phpDoc comment a new entry is created into the [code]yacs_phpdoc[/code] table.
 *
 * 5. The footprints file is (re)created.
 * This file contains a hash of all reference scripts of the reference store.
 * It is downloaded by remote servers that are updated, to identify new or updated scripts.
 * Note: When the script is asked to build a reference store for tests, the footprints file
 * has a special name to avoid erroneous downloads rom remote servers.
 *
 * 6. An archive file is created.
 * Finally all files of of the reference are put together into a single archive file simply named '[code]yacs.zip[/code]'
 * and located at the yacs directory.
 * At the end of the process the archive contains all files necessary to install a fresh server, including images, etc.
 *
 *
 * If you really want to restart a fresh built from start, please go to the control panel and select
 * the purge command. This will actually remove reference scripts of the reference store. All other files,
 * including non-reference scripts, will be preserved.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'scripts.php';

// the place to build the reference repository
if(!isset($context['path_to_reference']))
	$context['path_to_reference'] = preg_replace('|/[^/]+/\.\./|', '/', $context['path_to_root'].'../yacs.reference/');

// load localized strings
i18n::bind('scripts');

// load the skin
load_skin('scripts');

// the path to this page
$context['path_bar'] = array( 'scripts/' => i18n::s('Server software') );

// the title of the page
$context['page_title'] = i18n::s('Build the software');

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('scripts/build.php'));

// only associates can proceed
elseif(!Surfer::is_associate()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

	// forward to the index page
	$menu = array('scripts/' => i18n::s('Server software'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// ask for confirmation
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'POST')) {

	// the splash message
	$context['text'] .= '<p>'.i18n::s('This script will scan current php scripts and build the related reference store that other servers may use to update their software.').'</p>';

	// a compact form
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>';

	// version string
	$context['text'] .= i18n::s('Version string (optional)').' <input type="text" name="version" size="8" maxlength="128" />';

	// the submit button
	$context['text'] .= BR.Skin::build_submit_button(i18n::s('Yes, I want to (re)build the reference store'), NULL, NULL, 'confirmed');

	// footprints files
	$context['text'] .= BR.'<input type="checkbox" name="enable_footprints" value="Y" checked="checked" /> '.i18n::s('Enable remote servers to update from this reference store.');

	// end of the form
	$context['text'] .= '</p></form>';

	// set the focus on the button
	$context['text'] .= JS_PREFIX
		.'$("#confirmed").focus();'."\n"
		.JS_SUFFIX."\n";

	// this may take several minutes
	$context['text'] .=  '<p>'.i18n::s('When you will click on the button the server will be immediately requested to proceed. However, because of the so many things to do on the back-end, you may have to wait for minutes before getting a response displayed. Thank you for your patience.').'</p>';

	// display the existing reference script, if any
	if($content = Safe::file_get_contents($context['path_to_reference'].'footprints.php'))
		$context['text'] .= Skin::build_box(sprintf(i18n::s('Current content of %s'), $context['path_to_reference'].'footprints.php'), Safe::highlight_string($content), 'folded');

// no build in demo mode
} elseif(file_exists($context['path_to_root'].'parameters/demo.flag')) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation in demonstration mode.'));

// just do it
} else {

	// the splash message
	$context['text'] .= '<p>'.i18n::s('Please go to the end of this page to check results of the building process.')."</p>\n";

	// suppress the footprints file to disable remote updates
	$context['text'] .= '<p>'.i18n::s('Suppressing the footprints file to disable remote updates...')."</p>\n";
	Safe::unlink($context['path_to_reference'].'footprints.php');

	// list running scripts
	$context['text'] .= '<p>'.i18n::s('Listing files...').BR."\n";

	// locate script files starting at root
	$scripts = Scripts::list_scripts_at(NULL);
	if(is_array($scripts))
		$context['text'] .= BR.sprintf(i18n::s('%d scripts have been found.'), count($scripts))."\n";
	$context['text'] .= "</p>\n";

	// copying scripts to the reference store
	$context['text'] .= '<p>'.i18n::s('Copying files...').BR."\n";

	// analyse each script
	$footprints = array();
	foreach($scripts as $file) {

		// silently skip configuration files
		if(strpos($file, '.include.php'))
			continue;

		// process only reference scripts
		if(!$footprint = Scripts::hash($file)) {
			$context['text'] .= sprintf(i18n::s('%s is not a reference script'), $file).BR."\n";
			continue;
		}

		// store the footprint for later use --number of lines, content hash
		$footprints[$file] = array($footprint[0], $footprint[1]);

		// ensure a clean reference store
		Safe::unlink($context['path_to_reference'].$file);

		// create adequate path
		if(!Safe::make_path($context['path_to_reference'].dirname($file)))
			$context['text'] .= sprintf(i18n::s('Impossible to create path %s.'), $context['path_to_reference'].dirname($file)).BR."\n";

		// copy file content into the reference store
		elseif(!Safe::copy($context['path_to_root'].$file, $context['path_to_reference'].$file))
			$context['text'] .= sprintf(i18n::s('Impossible to copy file %s.'), $file).BR."\n";

		// post-processing
		else {

			// try to preserve the modification date
			Safe::touch($context['path_to_reference'].$file, Safe::filemtime($context['path_to_root'].$file));

			// this will be filtered by umask anyway
			Safe::chmod($context['path_to_reference'].$file, $context['file_mask']);

		}

		// avoid timeouts
		if(!(count($footprints)%50)) {
			Safe::set_time_limit(30);
			SQL::ping();
		}

	}

	if(count($footprints))
		$context['text'] .= sprintf(i18n::s('%d reference scripts have been copied.'), count($footprints))."\n";
	$context['text'] .= "</p>\n";

	// purge documentation pages
	$context['text'] .= '<p>'.i18n::s('Purging the documentation pages...')."</p>\n";

	// get a parser
	include_once 'phpdoc.php';
	$documentation = new PhpDoc;

	// purge the existing documentation, if any
	$documentation->purge();

	// list reference files
	$context['text'] .= '<p>'.i18n::s('Listing files...').BR."\n";

	// locate reference files --include special nodes
	$references = Scripts::list_files_at($context['path_to_reference'], TRUE, $context['path_to_reference'], '.htaccess');
	if(is_array($references))
		$context['text'] .= BR.sprintf(i18n::s('%d files have been found.'), count($references))."\n";
	$context['text'] .= "</p>\n";

	// build documentation pages
	$context['text'] .= '<p>'.i18n::s('Building documentation pages...').BR."\n";

	// analyse each script
	$index = 0;
	foreach($references as $reference) {

		// use file content
		list($module, $name) = $reference;
		if($module)
			$file = $module.'/'.$name;
		else
			$file = $name;

		// look only into  php script
		if(!preg_match('/\.php$/i', $name))
			continue;

		// look at phpDoc blocks
		if($text = $documentation->parse($file, $context['path_to_reference']))
			$context['text'] .= $text.BR."\n";
		elseif(isset($documentation->index[$file]) && $documentation->index[$file])
			$context['text'] .= $documentation->index[$file].BR."\n";
		else
			$context['text'] .= sprintf(i18n::s('*** %s has no documentation block'), $file).BR."\n";

		// update footprints
		if(!isset($footprints[$file]) && ($footprint = Scripts::hash($context['path_to_reference'].$file)))
			$footprints[$file] = $footprint;

		// ensure we have enough time to process next script
		Safe::set_time_limit(30);

		// avoid timeouts
		if(!(($index++)%50)) {
			Safe::set_time_limit(30);
			SQL::ping();
		}

	}

	// report to surfer
	if(is_array($footprints))
		$context['text'] .= sprintf(i18n::s('%d scripts have been parsed.'), count($footprints))."\n";
	$context['text'] .= "</p>\n";

	// generate the php documentation
	$context['text'] .= $documentation->generate();

	// start the footprints file
	$content = '<?php'."\n"
		.'// This file has been created by the building script scripts/build.php'."\n"
		.'// on '.gmdate("F j, Y, g:i a").' GMT, for '.Surfer::get_name().'. Please do not modify it manually.'."\n";

	// process gathered footprints
	if(is_array($footprints)) {
		reset($footprints);
		ksort($footprints);

		// ensure this variable is globally visible on file inclusion
		$content .= 'global $footprints;'."\n"
			.'if(!isset($footprints)) $footprints = array();'."\n";

		// global statistics
		$global_scripts = 0;
		$global_lines = 0;

		foreach($footprints as $file => $footprint) {
			$content .= "\$footprints['$file']=array($footprint[0], '$footprint[1]');\n";

			// global statistics
			$global_scripts += 1;
			$global_lines += $footprint[0];
		}

		// provide global meta-information
		$content .= 'global $generation;'."\n"
			.'if(!isset($generation)) $generation = array();'."\n"
			."\$generation['date']='".gmdate("ymd-H:i:s")." GMT';\n"
			."\$generation['server']='".$context['host_name']."';\n";

		// store version string as well
		if(isset($_REQUEST['version']) && trim($_REQUEST['version']))
			$content .= "\$generation['version']='".trim($_REQUEST['version'])."';\n";

		// remember statistics
		$content .= "\$generation['scripts']='".$global_scripts."'; // number of reference scripts\n"
			."\$generation['lines']='".$global_lines."'; // lines of code\n";

	}

	// end the footprints file
	$content .= '?>'."\n";

	// stop here
	if(!isset($_REQUEST['enable_footprints']) || ($_REQUEST['enable_footprints'] != 'Y')) {
		$context['text'] .= '<p>'.sprintf(i18n::s('The file %s has not been generated and the reference store can only be used for test purpose.'), $context['path_to_reference'].'footprints.php')."</p>\n";

	// file cannot be saved
	} elseif(!Safe::file_put_contents($context['path_to_reference'].'footprints.php', $content)) {
		$context['text'] .= '<p>'.sprintf(i18n::s('Impossible to write to %s.'), $context['path_to_reference'].'footprints.php')."</p>\n";

	// follow-up
	} else {
		$context['text'] .= '<p>'.sprintf(i18n::s('Meta data have been saved in %s'), $context['path_to_reference'].'footprints.php')."</p>\n";

		// also put the file in the archive
		$references[] = array('', 'footprints.php');

	}

	// also update our own version
	Safe::file_put_contents($context['path_to_root'].'footprints.php', $content);

	// splash message
	$context['text'] .= '<p>'.i18n::s('On-going archive preparation...')."\n";

	$file_path = $context['path_to_reference'];
	$file_name = 'temporary/'.gmdate("Ymd").'_yacs_'.trim($_REQUEST['version']);

	// start the zip file
	include_once '../shared/zipfile.php';
	$zipfile = new zipfile();

	// place all files into a single directory --fixed time to allow cacheability
	$zipfile->store('yacs/', 0);

	// process every reference file
	$all_files = array();
	$index = 0;
	foreach($references as $reference) {

		// let's go
		list($path, $file) = $reference;
		if(strlen(trim($path)) > 0)
			$file = $path.'/'.$file;

		// read file content
		if(($content = Safe::file_get_contents($file_path.$file)) !== FALSE) {

			// compress textual content
			if($content && preg_match('/\.(css|htc|htm|html|include|js|mo|php|po|pot|sql|txt|xml)$/i', $file))
				$zipfile->deflate('yacs/'.$file, Safe::filemtime($file_path.$file), $content);

			// store binary data
			else
				$zipfile->store('yacs/'.$file, Safe::filemtime($file_path.$file), $content);

			// to be included in tar file as well
			$all_files[] = $file_path.$file;

		} else
			$context['text'] .= BR.'cannot read '.$file_path.$file;

		// avoid timeouts
		if(!(($index++)%50)) {
			Safe::set_time_limit(30);
			SQL::ping();
		}

	}

	// save the zipfile
	if($handle = Safe::fopen($context['path_to_root'].$file_name.'.zip', 'wb')) {
		fwrite($handle, $zipfile->get());
		fclose($handle);
		$context['text'] .= BR.Skin::build_link($file_name.'.zip', $file_name.'.zip', 'basic');
	}

	// start the tar file
	include_once '../included/tar.php';
	$tarfile = new Archive_Tar($context['path_to_root'].$file_name.'.tgz');

	// extend the tar file as well
	if($tarfile->createModify($all_files, '', $file_path))
		$context['text'] .= BR.Skin::build_link($file_name.'.tgz', $file_name.'.tgz', 'basic');

	$context['text'] .= "</p>\n";

	// display the execution time
	$time = round(get_micro_time() - $context['start_time'], 2);
	$context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

	// forward to the index page
	$menu = array('scripts/' => i18n::s('Server software'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

	// remember the built
	Logger::remember('scripts/build.php', i18n::c('The reference store has been rebuilt'));
}

// render the skin
render_skin();

?>
