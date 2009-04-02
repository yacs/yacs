<?php
/**
 * launch periodic jobs
 *
 * This script includes all scripts bound to the id '[code]tick[/code]'.
 *
 * This is achieved through the loading of hook definitions, if any.
 * See [script]control/scan.php[/script] for more information on hooks.
 *
 * @see control/scan.php
 *
 * It can be launched either from the command line, making it suitable to be invoked from a crontab,
 * or remotely through the web, for example through wget and the like.
 *
 * This architecture allows for different kinds of background processing:
 *
 * [*] Integrated to front-end processing - This is the default mode of operation.
 * Ticks are triggered randomly when regular pages have been generated.
 * This easy setup is very nice, except that most servers are buffering data nowadays,
 * meaning surfers may have to wait for the end of background processing to get their page.
 * Simple, but not that good for surfer satisfaction.
 *
 * [*] Linked to cron - Launching this script from the crontab is
 * everything that is required for YACS background processing.
 * A period of time of 10 minutes is suitable in most situations.
 * This script takes care of time, and will wait for a minimum of 5 minutes between ticks anyway.
 *
 * [*] Linked to wget - If you don't have access to the crontab,
 * you may install another computer to launch this script remotely, through the web.
 * For example, add an entry to the crontab of your own computer to launch the wget command
 * with the URL of this script as parameter (e.g., ##http://a_nice_server/yacs/cron.php##).
 *
 * To enable running from anywhere the script change the current directory to where it is located.
 * Alternatively, you can set the environment variable '[code]YACS_HOME[/code]'
 * to point to the installation path of YACS.
 *
 * For example:
 * [snippet]
 * set YACS_HOME = "c:/public/yacs"
 * [/snippet]
 *
 * The script ensures a minimum delay of 5 minutes between successive ticks.
 *
 * This script is not localized, and always provides result in English. 
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// launched from the command line
if(!isset($_SERVER['REMOTE_ADDR'])) {

	// use environment
	if(($home = getenv('YACS_HOME')) && is_callable('chdir'))
		chdir($home);

	// else jump to where this file is executed from --SCRIPT_FILENAME may be buggy
	elseif(is_callable('chdir'))
		chdir(dirname(__FILE__));

	// carriage return for ends of line
	define('BR', "\n");

}

// sanity check
if(!is_readable('shared/global.php'))
	exit('The file shared/global.php has not been found. Please reinstall or configure the YACS_HOME environment variable.');

// common definitions and initial processing
include_once 'shared/global.php';
include_once $context['path_to_root'].'shared/values.php';	// cron.tick

// define new lines
if(!defined('BR')) {

	// invoked through the web
	if(isset($_SERVER['REMOTE_ADDR']))
		define('BR', "<br />\n");

	// we are running from the command line
	else
		define('BR', "\n");
}

// load localized strings
i18n::bind('root');

// only on regular operation
if(!file_exists($context['path_to_root'].'parameters/switch.on'))
	return;

// if it was a HEAD request, stop here --see scripts/validate.php
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
	return;

// load for hooks --see control/scan.php
if(!is_callable(array('Hooks', 'include_scripts')))
	exit(sprintf('Impossible to read %s.', 'parameters/hooks.include.php'));

// here we go
echo 'Starting cron...'.BR;

//
// one tick every 5 minutes
//
echo 'Checking ticks...'.BR;

// get date of last tick
$record = Values::get_record('cron.tick', NULL_DATE);

// wait at least 5 minutes = 300 seconds between ticks
if(isset($record['edit_date']))
	$target = SQL::strtotime($record['edit_date']) + 300;
else
	$target = time();

// request to be delayed
if($target > time())
	echo 'Wait until '.gmdate('r', $target).' GMT'.BR;
	
// remember tick date and avoid racing conditions
else {
	Values::set('cron.tick', 'running...');

	// do the job and provide feed-back to user
	$context['cron_text'] = Hooks::include_scripts('tick');
	echo $context['cron_text'];
	
	// remember tick date and resulting text
	Values::set('cron.tick', $context['cron_text']);
	
	// log outcome of script execution in debug mode
	if($context['with_debug'] == 'Y')
		Logger::remember('cron.php', 'tick processing', $context['cron_text'], 'debug');

}

//
// hourly jobs
//
echo 'Checking hourly jobs...'.BR;

// get date of last run
$record = Values::get_record('cron.hourly', NULL_DATE);

// wait at least 1 hour = 3600 seconds between runs
if(isset($record['edit_date']))
	$target = SQL::strtotime($record['edit_date']) + 3600;
else
	$target = time();

// request to be delayed
if($target > time())
	echo 'Wait until '.gmdate('r', $target).' GMT'.BR;

// remember tick date and avoid racing conditions
else {
	Values::set('cron.hourly', 'running...');

	// do the job and provide feed-back to user
	$context['text'] = Hooks::include_scripts('hourly');
	echo $context['text'];
	
	// remember tick date and resulting text
	Values::set('cron.hourly', $context['text']);
	
	// log outcome of script execution in debug mode
	if($context['with_debug'] == 'Y')
		Logger::remember('cron.php', 'hourly processing', $context['text'], 'debug');

}

// dump profile data
Logger::profile_dump();

// all done
$time = round(get_micro_time() - $context['start_time'], 2);
exit(sprintf('Script terminated in %.2f seconds.', $time).BR);

?>