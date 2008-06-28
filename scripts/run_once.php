<?php
/**
 * run one time only scripts
 *
 * This scripts helps to finalize complex software updates, such as:
 * - update of the database schema (e.g., apply complex UPDATE queries to populate new fields of the SQL database)
 * - change files that are not PHP scripts (e.g., change cascading style sheets of reference skins)
 * - download of binary files (e.g., get a copy of the Flash viewer for Freemind maps)
 *
 * [title]How to write a script to be ran once?[/title]
 *
 * Such script is really simple to write, since the only requirement for software developer is to
 * display information to the surfer through 'echo' commands. This is because the regular page factory
 * (updated via the $context variable) is not available during the execution of multiple scripts.
 *
 * Except for the bare display interface, a script that is ran once benefits from the full power of YACS (database access, localization, ...).
 *
 * Exemple of a script to perform some database update:
 * [php]
 * <?php
 * // feed-back
 * echo 'Change anchors in files...<br />'."\n";
 *
 * // split membership components
 * $query = "UPDATE ".SQL::table_name('files')
 *			." SET anchor_type = SUBSTRING_INDEX(anchor, ':', 1)"
 *			.", anchor_id = SUBSTRING_INDEX(anchor, ':', -1)";
 * if($count = SQL::query($query, TRUE))
 *	echo $count.' records have been updated.<br />'."\n";
 *
 * ?>
 * [/php]
 *
 * [title]How to run scripts only once?[/title]
 *
 * All you have to do it to upload these scripts into the directory [code]scripts/run_once[/code], and to launch
 * the [code]scripts/run_once.php[/code] script.
 *
 * The script will locate every script into [code]scripts/run_once[/code] and include each of them.
 * Also, to avoid further execution, each executed script is renamed by appending the extension '.done'.
 *
 * [title]How to run scripts during software updates?[/title]
 *
 * If some reference scripts are put into the [code]scripts/run_once[/code] directory, they will be transferred
 * and installed at the target server as other scripts.
 *
 * For example, if you have to change the database through some software upgrade, and if this update has to
 * be performed only once, you will:
 * - prepare the software upgrade as usual, assuming that scripts may use an updated database
 * - prepare an additional script to change the database itself, for example: [code]my_database_update.php[/code]
 * - put this additional script into [code]scripts/run_once[/script]
 *
 * During the software upgrade, [script]scripts/update.php[/script] will transfer every new script,
 * including [code]my_database_update.php[/code], to the staging repository.
 * Also, [script]control/scan.php[/script] and [script]control/setup.php[/script] are launched as usual
 * to ensure that the database schema is correct. Then [script]scripts/run_once.php[/script] will launch
 * [code]my_database_update.php[/code], to actually finalize the database change.
 *
 * Note that if there is no script to run in [code]scripts/run_once.php[/code], and if we are in the middle
 * of an update (because [code]parameters/switch.off[/code] exists), then the surfer is silently redirected to [script]control/index.php[/script].
 *
 * While the run-once approach is powerful and should suffice for most needs, you may have to complement it
 * in very specific cases. People interested into weird software will take a look at [script]scripts/update_trailer.php[/script].
 *
 * [title]What happens on first installation?[/title]
 *
 * The YACS archive that contains reference scripts is used jointly on first installation and on upgrades.
 * However, scripts to be ran once are useful only for upgrades.
 * Therefore, on first installation (i.e., when the switch file is absent), the extension '.done' is appended to
 * every script in the directory scripts/run_once without actual execution of them.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// include global declarations
include_once '../shared/global.php';

// load localized strings
i18n::bind('scripts');

// load the skin
load_skin('scripts');

// the path to this page
$context['path_bar'] = array( 'control/' => i18n::s('Control Panel') );

// the title of the page
$context['page_title'] = i18n::s('Run one-time scripts');

// if the user table exists, check that the user is an admin
$query = "SELECT count(*) FROM ".SQL::table_name('users');
if((SQL::query($query) !== FALSE) && !Surfer::is_associate()) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// open the directory
} elseif(!$dir = Safe::opendir($context['path_to_root'].'scripts/run_once'))
	Skin::error(sprintf(i18n::s('Impossible to read %s.'), $context['path_to_run_once_scripts']));

// browse the directory
else {
	while(($item = Safe::readdir($dir)) !== FALSE) {

		// skip some files
		if($item == '.' || $item == '..')
			continue;

		// do not execute twins, to ensure scripts are ran only once
		if(file_exists($context['path_to_root'].'scripts/run_once/'.$item.'.done'))
			continue;

		// remember any php script found here
		if(preg_match('/\.php$/i', $item))
			$scripts[] = $item;

	}

	// close the directory
	Safe::closedir($dir);

	// no script has been found; if the server has been switched off, go silently to the control panel
	if(!@count($scripts) && file_exists('../parameters/switch.off'))
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'control/');

}

/**
 * dynamically generate the page
 *
 * @see skins/index.php
 */
function send_body() {
	global $context, $local; // $local is required to localize included scripts

	// include every script that has to be run once
	global $scripts, $scripts_count;
	if(@count($scripts)) {

		// the alphabetical order may be used to control script execution order
		sort($scripts);
		reset($scripts);

		// process each script one by one
		foreach($scripts as $item) {

			// do not execute on first installation
			if(file_exists('../parameters/switch.on') || file_exists('../parameters/switch.off')) {

				// ensure we have a valid database resource
				if(!$context['connection'])
					break;

				// remember this as an event
				Logger::remember('scripts/run_once.php', sprintf(i18n::c('script %s has been executed'), $item));

				// where scripts actually are
				$actual_item = str_replace('//', '/', $context['path_to_root'].'scripts/run_once/'.$item);

				// include the script to execute it
				$scripts_count++;
				echo Skin::build_block($item, 'subtitle');
				include $actual_item;
				echo "\n";

			}

			// ensure enough overall execution time
			Safe::set_time_limit(30);

			// stamp the file to remember execution time
			Safe::touch($actual_item);

			// rename the script to avoid further execution
			Safe::unlink($actual_item.'.done');
			Safe::rename($actual_item, $actual_item.'.done');

		}

		// refresh javascript libraries
		Cache::purge('js');

	}

	// report on actual execution
	if($scripts_count)
		echo '<p>&nbsp;</p><p>'.sprintf(i18n::ns('%d script has been executed', '%d scripts have been executed', $scripts_count), $scripts_count)."</p>\n";
	else
		echo '<p>'.i18n::s('No script has been executed')."</p>\n";

	// display the total execution time
	$time = round(get_micro_time() - $context['start_time'], 2);
	if($time > 30)
		echo '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

	// if the server has been switched off, go back to the control panel
	if(file_exists('../parameters/switch.off')) {
		echo '<form method="get" action="'.$context['url_to_root'].'control/">'."\n"
			.'<p class="assistant_bar">'.Skin::build_submit_button(i18n::s('Back to the control panel')).'</p>'."\n"
			.'</form>'."\n";

	// else back to the control panel as well, but without a button
	} else {
		$menu = array('control/' => i18n::s('Back to the control panel'));
		echo Skin::build_list($menu, 'menu_bar');
	}

	// purge the cache, since it is likely that we have modified some data
	Cache::clear();

}

// render the skin
render_skin();

?>