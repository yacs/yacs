<?php
/**
 * purge the system
 *
 * This page is used to purge the system. Its usage is restricted to associates.
 *
 * At the moment following items can be purged:
 *
 * [*] 'bak' - Suppress old versions of scripts that have been replaced
 * during updates. Useful to recover disk space, at least until the next update.
 *
 * [*] 'cache' - All cached components are suppressed from the database. Make room
 * in the database by suppressing old and unused components. If activated, the
 * cache will be populated again based on actual requests.
 *
 * [*] 'debug' - Kill the file temporary/debug.txt
 *
 * [*] 'feeds' - Links received by newsfeed
 *
 * [*] 'log' - Kill the file temporary/log.txt
 *
 * [*] 'overhead' - Recover lost disk space of the database
 *
 * [*] 'reference' - All reference scripts are suppressed from the file system.
 * Related phpDoc pages are suppressed from the database. This is useful to save
 * space on slave servers. The reference repository and the phpDoc pages can be
 * built again from the index page of scripts.
 *
 * [*] 'referrals' - All referring links are deleted.
 * May be useful from time to time in case of spamming.
 *
 * [*] 'scripts' - All data concerning script execution and performance are deleted.
 *
 * [*] 'versions' - Old versioning information is deleted.
 *
 * Of course, scripts thmselves are left untouched.
 * May be useful from time to time to restart a sampling period of time.
 *
 * Note that if you want to purge some obsoleted file, you should create a
 * script to be run once, and to add it to your release.
 * See [script]scripts/run_once.php[/script] for more information.
 *
 * @see scripts/run_once.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Alexis Raimbault
 * @tester Paddy
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// include the global declarations
include_once '../shared/global.php';
include_once '../scripts/scripts.php';	// we handle many files

// the place to build the reference repository
if(!isset($context['path_to_reference']))
	$context['path_to_reference'] = preg_replace('|/[^/]+/\.\./|', '/', $context['path_to_root'].'../yacs.reference/');

// load localized strings
i18n::bind('control');

// load the skin
load_skin('control');

// do not index this page
$context->sif('robots','noindex');

// the path to this page
$context['path_bar'] = array( 'control/' => i18n::s('Control Panel') );

// the title of the page
$context['page_title'] = i18n::s('Purge');

/**
 * delete .bak files
 *
 * @param string the directory to start with
 * @see scripts/update.php
 */
function delete_backup($path) {
	global $context;

	$path_translated = $context['path_to_root'].$path;
	if($handle = Safe::opendir($path_translated)) {

		while(($node = Safe::readdir($handle)) !== FALSE) {

			if($node[0] == '.')
				continue;

			// make a real name
			$target = str_replace('//', '/', $path.'/'.$node);
			$target_translated = str_replace('//', '/', $path_translated.'/'.$node);

			// delete a sub directory
			if(is_dir($target_translated) && !strpos($path_translated, '/files/') && !strpos($path_translated, '/images/'))
				delete_backup($target);

			// delete a backed up file
			elseif(preg_match('/\.bak$/i', $target_translated)) {
				$context['text'] .= sprintf(i18n::s('Deleting %s'), $target_translated).BR."\n";
				Safe::unlink($target_translated);
				global $deleted_nodes;
				$deleted_nodes++;
			}

			// ensure we have enough time
			Safe::set_time_limit(30);
		}
		Safe::closedir($handle);
	}

}

/**
 * delete reference files
 *
 * @param string the directory to start with
 * @see scripts/build.php
 */
function delete_reference($path) {
	global $context;

	$path_translated = str_replace('//', '/', $context['path_to_reference'].$path);
	if($handle = Safe::opendir($path_translated)) {

		while(($node = Safe::readdir($handle)) !== FALSE) {

			if($node[0] == '.')
				continue;

			// make a real name
			$target = str_replace('//', '/', $path.'/'.$node);
			$target_translated = str_replace('//', '/', $path_translated.'/'.$node);

			// delete sub directory content
			if(is_dir($target_translated))
				delete_reference($target);

			// delete only files that are part of the reference set
			elseif(Scripts::hash($context['path_to_reference'].$target)) {
				$context['text'] .= sprintf(i18n::s('Deleting %s'), substr($target, 1)).BR."\n";
				Safe::unlink($target_translated);
				global $deleted_nodes;
				$deleted_nodes++;
			}

			// ensure we have enough time
			Safe::set_time_limit(30);
		}
		Safe::closedir($handle);
	}

}

/**
 * delete staging files
 *
 * @param string the directory to start with
 * @see scripts/update.php
 */
function delete_staging($path) {
	global $context;

	$path_translated = str_replace('//', '/', $context['path_to_root'].'/scripts/staging'.$path);
	if($handle = Safe::opendir($path_translated)) {

		while(($node = Safe::readdir($handle)) !== FALSE) {

			if(($node == '.') || ($node == '..'))
				continue;

			// make a real name
			$target = str_replace('//', '/', $path.'/'.$node);
			$target_translated = str_replace('//', '/', $path_translated.'/'.$node);

			// delete sub directory content
			if(is_dir($target_translated)) {
				delete_staging($target);
				Safe::rmdir($target_translated);

			// delete all files
			} else {
				$context['text'] .= sprintf(i18n::s('Deleting %s'), '/scripts/staging'.$target).BR."\n";
				Safe::unlink($target_translated);
				global $deleted_nodes;
				$deleted_nodes++;
			}

			// ensure we have enough time
			Safe::set_time_limit(30);
		}
		Safe::closedir($handle);
	}

}

// the user has to be an associate
if(!Surfer::is_associate()) {

	// prevent access to this script
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

	// forward to the control panel
	$menu = array('control/' => i18n::s('Control Panel'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// delete agents data
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'agents')) {

	$context['text'] .= '<p>'.i18n::s('Deleting agents data...')."</p>\n";

	// suppress records
	$query = "DELETE FROM ".SQL::table_name('counters');
	if(SQL::query($query) === FALSE)
		$context['text'] .= Logger::error_pop().BR."\n";

	// display the execution time
	$time_end = get_micro_time();
	$time = round($time_end - $context['start_time'], 2);
	$context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

	// forward to the control panel
	$menu = array('control/' => i18n::s('Control Panel'), 'control/purge.php' => i18n::s('Purge again'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// purge old scripts
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'bak')) {

	$context['text'] .= '<p>'.i18n::s('Deleting previous versions of scripts...')."</p>\n";
	delete_backup('/');

	// ending message
	global $deleted_nodes;
	if($deleted_nodes > 1)
		$context['text'] .= sprintf(i18n::s('%d items have been deleted'), $deleted_nodes).BR."\n";

	// display the execution time
	$time_end = get_micro_time();
	$time = round($time_end - $context['start_time'], 2);
	$context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

	// forward to the control panel
	$menu = array('control/' => i18n::s('Control Panel'), 'control/purge.php' => i18n::s('Purge again'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// purge the cache
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'cache')) {

	$context['text'] .= '<p>'.i18n::s('Deleting all cached items...')."</p>\n";
	Cache::clear();

	// refresh javascript libraries
	Cache::purge();

	// display the execution time
	$time_end = get_micro_time();
	$time = round($time_end - $context['start_time'], 2);
	$context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

	// forward to the control panel
	$menu = array('control/' => i18n::s('Control Panel'), 'control/purge.php' => i18n::s('Purge again'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// delete debug data
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'debug')) {

	$context['text'] .= '<p>'.i18n::s('Deleting debug data...')."</p>\n";

	// suppress temporary/debug.txt
	Safe::unlink($context['path_to_root'].'temporary/debug.txt');

	// display the execution time
	$time_end = get_micro_time();
	$time = round($time_end - $context['start_time'], 2);
	$context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

	// forward to the control panel
	$menu = array('control/' => i18n::s('Control Panel'), 'control/purge.php' => i18n::s('Purge again'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// delete links received via newsfeeds
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'feeds')) {

	$context['text'] .= '<p>'.i18n::s('Deleting links received from newsfeeds...')."</p>\n";

	// suppress records
	include_once '../links/links.php';
	if($count = Links::purge_old_news(0))
		$context['text'] .= sprintf(i18n::s('%d items have been deleted'), $count);

	// display the execution time
	$time_end = get_micro_time();
	$time = round($time_end - $context['start_time'], 2);
	$context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

	// forward to the control panel
	$menu = array('control/' => i18n::s('Control Panel'), 'control/purge.php' => i18n::s('Purge again'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// delete log data
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'log')) {

	$context['text'] .= '<p>'.i18n::s('Deleting log data...')."</p>\n";

	// suppress temporary/debug.txt
	Safe::unlink($context['path_to_root'].'temporary/log.txt');

	// display the execution time
	$time_end = get_micro_time();
	$time = round($time_end - $context['start_time'], 2);
	$context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

	// forward to the control panel
	$menu = array('control/' => i18n::s('Control Panel'), 'control/purge.php' => i18n::s('Purge again'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// delete log data
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'overhead')) {

	$context['text'] .= '<p>'.i18n::s('Recovering overhead space from the database...')."</p>\n";

	// purge the database
	SQL::purge();

	// display the execution time
	$time_end = get_micro_time();
	$time = round($time_end - $context['start_time'], 2);
	$context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

	// forward to the control panel
	$menu = array('control/' => i18n::s('Control Panel'), 'control/purge.php' => i18n::s('Purge again'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// delete the repository of reference scripts and related php documentation
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'reference')) {

	// purge the old documentation
	$context['text'] .= '<p>'.i18n::s('Deleting the documentation pages...')."</p>\n";

	// suppressing documentation pages in the database
	$query = "DELETE FROM ".SQL::table_name('phpdoc');
	if(SQL::query($query) === FALSE)
		$context['text'] .= Logger::error_pop().BR."\n";

	// delete reference files
	$context['text'] .= '<p>'.i18n::s('Deleting the reference repository...')."</p>\n";
	if(file_exists($context['path_to_reference'].'footprints.php')) {
		$context['text'] .= sprintf(i18n::s('Deleting %s'), $context['path_to_reference'].'footprints.php').BR."\n";
		if(Safe::unlink($context['path_to_reference'].'footprints.php'))
			$deleted_nodes = 1;
	}
	delete_reference('/');

	// ending message
	global $deleted_nodes;
	if($deleted_nodes > 1)
		$context['text'] .= sprintf(i18n::s('%d items have been deleted'), $deleted_nodes).BR."\n";

	// display the execution time
	$time_end = get_micro_time();
	$time = round($time_end - $context['start_time'], 2);
	$context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

	// forward to the control panel
	$menu = array('control/' => i18n::s('Control Panel'), 'control/purge.php' => i18n::s('Purge again'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// delete staging scripts
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'staging')) {

	// delete staging files
	$context['text'] .= '<p>'.i18n::s('Deleting staging files...')."</p>\n";
	delete_staging('/');
	Safe::rmdir($context['path_to_root'].'scripts/staging');

	// ending message
	global $deleted_nodes;
	if($deleted_nodes > 1)
		$context['text'] .= sprintf(i18n::s('%d items have been deleted'), $deleted_nodes).BR."\n";

	// display the execution time
	$time_end = get_micro_time();
	$time = round($time_end - $context['start_time'], 2);
	$context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

	// forward to the control panel
	$menu = array('control/' => i18n::s('Control Panel'), 'control/purge.php' => i18n::s('Purge again'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// delete referrals data
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'referrals')) {

	$context['text'] .= '<p>'.i18n::s('Deleting referall data...')."</p>\n";

	// suppress records
	$query = "DELETE FROM ".SQL::table_name('referrals');
	if(SQL::query($query) === FALSE)
		$context['text'] .= Logger::error_pop().BR."\n";

	// display the execution time
	$time_end = get_micro_time();
	$time = round($time_end - $context['start_time'], 2);
	$context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

	// forward to the control panel
	$menu = array('control/' => i18n::s('Control Panel'), 'control/purge.php' => i18n::s('Purge again'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// delete scripts data
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'scripts')) {

	$context['text'] .= '<p>'.i18n::s('Deleting scripts data...')."</p>\n";

	// suppress records
	$query = "DELETE FROM ".SQL::table_name('profiles');
	if(SQL::query($query) === FALSE)
		$context['text'] .= Logger::error_pop().BR."\n";

	// display the execution time
	$time_end = get_micro_time();
	$time = round($time_end - $context['start_time'], 2);
	$context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

	// forward to the control panel
	$menu = array('control/' => i18n::s('Control Panel'), 'control/purge.php' => i18n::s('Purge again'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// delete legacy versioning information
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'versions')) {

	$context['text'] .= '<p>'.i18n::s('Deleting old versions...')."</p>\n";

	// suppress old records
	$query = "DELETE FROM ".SQL::table_name('versions')." WHERE (DATE_SUB(CURDATE(),INTERVAL 183 DAY) > edit_date)";
	if(SQL::query($query) === FALSE)
		$context['text'] .= Logger::error_pop().BR."\n";

	// display the execution time
	$time_end = get_micro_time();
	$time = round($time_end - $context['start_time'], 2);
	$context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

	// forward to the control panel
	$menu = array('control/' => i18n::s('Control Panel'), 'control/purge.php' => i18n::s('Purge again'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');
        
// delete formatting code patterns, will be rebuild automaticaly
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'codeyacs')) {


        $context['text'] .= '<p>'.i18n::s('Deleting formatting codes cache...')."</p>\n";
        Safe::unlink($context['path_to_root'].'codes/patterns.auto.php');

        // display the execution time
        $time_end = get_micro_time();
        $time = round($time_end - $context['start_time'], 2);
        $context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';


        // forward to the control panel
        $menu = array('control/' => i18n::s('Control Panel'), 'control/purge.php' => i18n::s('Purge again'));
        $context['text'] .= Skin::build_list($menu, 'menu_bar');

// which check?
} else {

	// the splash message
	$context['text'] .= '<p>'.i18n::s('Please select the action to perform.')."</p>\n";

	// the form
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form">';

	// purge the cache
	$context['text'] .= '<p><input type="radio" name="action" value="cache" checked="checked" /> '.i18n::s('Purge the server cache.').'</p>';

	// purge .bak scripts
	$context['text'] .= '<p><input type="radio" name="action" value="bak" /> '.i18n::s('Delete all files with the suffix .bak.').'</p>';

	// purge staging files
	if(file_exists($context['path_to_root'].'scripts/staging/footprints.php'))
		$context['text'] .= '<p><input type="radio" name="action" value="staging" /> '.i18n::s('Delete files staged during software update.').'</p>';

	// purge debug data
	if(file_exists($context['path_to_root'].'temporary/debug.txt'))
		$context['text'] .= '<p><input type="radio" name="action" value="debug" /> '.i18n::s('Delete file temporary/debug.txt to purge debug data.').'</p>';

	// purge links received via newsfeeds
	$context['text'] .= '<p><input type="radio" name="action" value="feeds" /> '.i18n::s('Purge links received via newsfeeds. These will be recreated progressively during future feeding.').'</p>';

	// purge referrals
	$context['text'] .= '<p><input type="radio" name="action" value="referrals" /> '.sprintf(i18n::s('Delete all referrals from the database. These will be recreated progressively during future browsing. You may prefer to %s.'), Skin::build_link('links/check.php', i18n::s('check referrals'), 'shortcut')).'</p>';

	// purge script profiles
	$context['text'] .= '<p><input type="radio" name="action" value="scripts" /> '.i18n::s('Purge script performance data from the database. These will be recreated progressively during future browsing.').'</p>';

	// purge log data
	if(file_exists($context['path_to_root'].'temporary/log.txt'))
		$context['text'] .= '<p><input type="radio" name="action" value="log" /> '.i18n::s('Delete file temporary/log.txt to purge events data.').'</p>';

	// recover overhead from the database
	$context['text'] .= '<p><input type="radio" name="action" value="overhead" /> '.i18n::s('Recover overhead disk space from the database.').'</p>';

	// purge obsolete versions
	$context['text'] .= '<p><input type="radio" name="action" value="versions" /> '.i18n::s('Delete versioning information saved more than six months ago. Preserve recent versions.').'</p>';

	// purge reference scripts
	if(file_exists($context['path_to_reference'].'footprints.php'))
		$context['text'] .= '<p><input type="radio" name="action" value="reference" /> '.i18n::s('Delete the repository of reference scripts and related documentation. This can be rebuild from the scripts index page.').'</p>';

        // purge formatting code patterns cache
        if(file_exists($context['path_to_root'].'codes/patterns.auto.php'))
                $context['text'] .= '<p><input type="radio" name="action" value="codeyacs" /> '.i18n::s('Rebuild formatting codes cache patterns').'</p>';
        
	// the submit button
	$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Start'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

	// end of the form
	$context['text'] .= '</form>';

}

// render the skin
render_skin();

?>
