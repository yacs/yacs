<?php
/**
 * look for PHP inconsistencies
 *
 * This script behaves as follows:
 * - the script aborts if the user is not an associate
 * - else if this is a GET, a confirmation button is displayed
 * - else the syntax of reference scripts is checked, and they are also included on request
 *
 * The syntax check parses each script without running it, so it has no side effect.
 * Inclusion also reports warnings raised at run time, but it runs scripts: some of them
 * redirect and stop the process, others may change the database. An error thrown
 * by one included script is reported, and the process goes on.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'scripts.php';

// load localized strings
i18n::bind('scripts');

// load the skin
load_skin('scripts');

// the path to this page
$context['path_bar'] = array( 'scripts/' => i18n::s('Server software') );

// the title of the page
$context['page_title'] = i18n::s('Validate PHP syntax');

// anonymous users are invited to log in or to register
if(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('scripts/validate.php'));

/**
 * dynamically generate the page
 *
 * @see skins/index.php
 */
function send_body() {
	global $context;

	// only associates can proceed
	if(!Surfer::is_associate()) {
		Safe::header('Status: 401 Unauthorized', TRUE, 401);
		echo '<p>'.i18n::s('You are not allowed to perform this operation.')."</p>\n";

		// forward to the index page
		$menu = array('scripts/' => i18n::s('Server software'));
		echo Skin::build_list($menu, 'menu_bar');

	// ask for confirmation
	} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'GET')) {

		// the splash message
		echo '<p>'.i18n::s('This tool will include most of the running reference PHP scripts. Any syntax error should be spotted easily.').'</p>';

		// the submit button
		echo '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'
			.'<input type="checkbox" name="include" id="include" value="Y" /> <label for="include">'.i18n::s('Also include scripts, to report warnings raised at run time. Some scripts stop the process or change the database.').'</label>'.BR
			.Skin::build_submit_button(i18n::s('Yes, I want to validate scripts'), NULL, NULL, 'confirmed')
			.'</p></form>';

		// set the focus on the button
		Page::insert_script('$("#confirmed").focus();');

		// this may take some time
		echo '<p>'.i18n::s('When you will click on the button the server will be immediately requested to proceed. However, because of the so many things to do on the back-end, you may have to wait for minutes before getting a response displayed. Thank you for your patience.').'</p>';

	// just do it
	} else {

		// scripts are included only on request, since this runs them
		$with_inclusion = isset($_REQUEST['include']) && ($_REQUEST['include'] == 'Y');

		// the splash message
		if($with_inclusion)
			echo '<p>'.i18n::s('All reference scripts are included, to show evidence of possible syntax errors.')."</p>\n";
		else
			echo '<p>'.i18n::s('The syntax of all reference scripts is checked, without running them.')."</p>\n";

		// list running scripts
		echo '<p>'.i18n::s('Listing files...').BR."\n";

		// locate script files starting at root
		$scripts = Scripts::list_scripts_at(NULL);
		if(is_array($scripts) && count($scripts)) {
			echo BR.sprintf(i18n::s('%d scripts have been found.'), count($scripts))."\n";
			natsort($scripts);
		}
		echo "</p>\n";

		// parse each reference script without running it
		echo '<p>'.i18n::s('Checking reference scripts...').BR."\n";
		$references = array();
		$syntax_errors = 0;
		foreach($scripts as $file) {

			// skip run once scripts
			if(strpos($file, 'run_once/'))
				continue;

			// don't include ourself
			if($file == 'scripts/validate.php')
				continue;

			// process only reference scripts
			if(!Scripts::hash($file))
				continue;
			$references[] = $file;

			try {
				token_get_all(Safe::file_get_contents($file), TOKEN_PARSE);
			} catch(ParseError $e) {
				$syntax_errors += 1;
				echo sprintf(i18n::s('%s: %s at line %d'), $file, $e->getMessage(), $e->getLine()).BR."\n";
			}
		}

		// report of checked files, before any inclusion
		echo sprintf(i18n::s('%d scripts have been checked.'), count($references)).' ';
		if($syntax_errors)
			echo sprintf(i18n::ns('%d syntax error has been found.', '%d syntax errors have been found.', $syntax_errors), $syntax_errors);
		else
			echo i18n::s('No syntax error has been found.');
		echo '</p>'."\n";

		// including scripts
		if($with_inclusion)
			echo '<p>'.i18n::s('Including reference scripts...').BR."\n";
		else
			$references = array();

		// strip as much output as possible
		$_SERVER['REQUEST_METHOD'] = 'HEAD';

		// we will finalize this page later on
		global $finalizing_fuse;
		$finalizing_fuse = FALSE;

		// take care of dependancies
		include_once '../behaviors/behavior.php';
		include_once '../services/codec.php';
		include_once '../users/authenticator.php';

		// analyse each script
		$included_files = 0;
		$links_to_be_checked_manually = array();
		foreach($references as $file) {

			// ensure we have enough time to process this script
			Safe::set_time_limit(30);

			// check file content
			if(!$handle = Safe::fopen($file, 'rb')) {
				echo sprintf(i18n::s('%s has no readable content.'), $file).BR."\n";
				continue;
			}

			// look at the beginning of the file
			if(!$header = fread($handle, 16384)) {
				echo sprintf(i18n::s('%s has no readable content.'), $file).BR."\n";
				fclose($handle);
				continue;
			}
			fclose($handle);

			// skip scripts that generate content asynchronously
			if(stripos($header, 'send_body') || stripos($header, 'page::content')) {
				$links_to_be_checked_manually[$file] = '(asynchronous)';
				continue;
			}

			// skip scripts that would redefine our skin
			if(stripos($header, 'extends skin_skeleton')) {
				$links_to_be_checked_manually[$file] = '(skin)';
				continue;
			}

			// log script inclusion on development host
			if($context['with_debug'] == 'Y')
				logger::remember('scripts/validate.php: inclusion of '.$file, '', 'debug');

			// include the script and display any error
			$included_files += 1;

			$validate_stamp = time();
			echo sprintf(i18n::s('inclusion of %s'), $file)."\n";
			Safe::chdir($context['path_to_root'].dirname($file));

			// a fatal error in one script does not stop the process
			try {
				include_once $context['path_to_root'].$file;
			} catch(Throwable $e) {
				echo BR.sprintf(i18n::s('%s: %s at line %d'), str_replace($context['path_to_root'], '', $e->getFile()), get_class($e).': '.$e->getMessage(), $e->getLine());
			}
			$duration = time() - $validate_stamp;
			if($duration)
				echo ' ('.$duration.'s.)';
			echo BR;

		}

		// memory status
		$used_memory = '';
		if(is_callable('memory_get_usage'))
			$used_memory = ' ('.memory_get_usage().' bytes)';

		// report of included files
		if($included_files > 1)
			echo '<p>'.sprintf(i18n::s('%d files have been included.'), $included_files).$used_memory.'</p>';

		// list files to be checked manually
		if(count($links_to_be_checked_manually)) {
			echo '<p>'.i18n::s('Following scripts have to be included separately:').BR."\n";

			ksort($links_to_be_checked_manually);
			foreach($links_to_be_checked_manually as $file => $label)
				echo Skin::build_link($file, $file, 'basic').' '.$label.BR."\n";

			echo sprintf(i18n::s('%d files to be checked manually.'), count($links_to_be_checked_manually)).'</p>'."\n";
		}

		// display the execution time
		$time = round(get_micro_time() - $context['start_time'], 2);
		echo '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

		// forward to the referential building
		echo '<form method="get" action="'.$context['url_to_root'].'scripts/build.php"><p>'."\n"
			.Skin::build_submit_button(i18n::s('If no error has popped up, build the reference set >>'))."\n"
			.'</p></form>'."\n";

		// this may take some time
		echo '<p>'.i18n::s('When you will click on the button the server will be immediately requested to proceed. However, because of the so many things to do on the back-end, you may have to wait for minutes before getting a response displayed. Thank you for your patience.').'</p>';

		// clear text some scripts could have added
		$context['debug'] = '';
		$context['extra'] = '';
		$context['navigation'] = '';
		$context['suffix'] = '';
		$context['text'] = '';
		$context['page_details'] = '';
		$context['page_footer'] = '';
		$context['page_menu'] = array();
		$context['page_tags'] = '';
		$context['page_tools'] = array();

		// now we will finalize this page
		global $finalizing_fuse;
		unset($finalizing_fuse);

	}
}

// render the skin
render_skin();

?>
