<?php
/**
 * scan subdirectories for hooks
 *
 * This page is used to configure hooks. Its usage is restricted to associates.
 *
 * Simply speaking, a hook is a straightforward way of integrating some scripts to any existing system.
 *
 * [title]What are the hooks supported by YACS?[/title]
 *
 * At the moment YACS supports following hooks:
 *
 * - '[code]control/populate.php[/code]' is called from the control panel to populate a new server
 * - '[code]control/setup.php[/code]' is called on each update of the database schema
 * - '[code]finalize[/code]' is called after the processing of any request
 * - '[code]publish[/code]' is called on article publication
 * - '[code]tick[/code]' to trigger periodic jobs
 *
 * [subtitle]'control/populate.php'[/subtitle]
 *
 * This hook is triggered in the script [script]control/populate.php[/script] during the first installation,
 * and also on demand when necessary.
 *
 * This hook has been created to fully support external modules of YACS that are based on specific tables.
 *
 * [subtitle]'control/setup.php'[/subtitle]
 *
 * This hook is triggered in the script [script]control/setup.php[/script] during the first installation,
 * and also on each subsequent upgrade of the YACS software.
 *
 * This hook has been created to fully support external modules of YACS that are based on specific tables.
 *
 * We have also used it internally for some back-end modules that are provided with YACS, but that could be replaced
 * by other more powerful pieces of software.
 * Take a look at [script]agents/browsers.php[/script], [script]agents/profiles.php[/script], or
 * [script]agents/referrals.php[/script] for such examples.
 *
 * [subtitle]'finalize'[/subtitle]
 *
 * This hook is called after page rendering, just before exiting from called script.
 *
 * It is triggered at the end of the [code]render_page()[/code] function ([script]global.php[/script])
 * for pages built upon the templating system of YACS.
 * This hook is triggered directly in other scripts ([script]services/blog.php[/script], ...), in order to
 * integrate non-interactive scripts in finalizing steps as well.
 *
 * This hook has been created mainly for statistics purpose. Look at [script]agents/browsers.php[/script],
 * [script]agents/profiles.php[/script] and [script]agents/referrals.php[/script] for self-explanatory examples provided with YACS.
 * Or create hooks of your own to improve computed figures...
 *
 * [subtitle]'publish'[/subtitle]
 *
 * This hook is called on article publication, either through the on-line publication script ([script]articles/publish.php[/script]),
 * or on post from w.bloggar (see script [script]services/blog.php[/script]).
 *
 * By default, this hook pings several sites to notify them that your site has changed.
 * The sites that will be advertised are listed in the table for servers.
 *
 * To know servers that are pinged by default go to script [script]servers/populate.php[/script].
 *
 * [subtitle]'tick'[/subtitle]
 *
 * This hook is triggered regularly in the script [script]cron.php[/script] (the 'standard' cron),
 * or on each script rendering (look at [script]shared/global.php[/script], the 'poor man' cron).
 *
 * This means that scripts triggered on tick should make no assumption regarding delays between invocations.
 *
 * With no additional configuration this hook calls [code]Feeds::tick()[/code] to read XML news from feeders.
 *
 * [title]How to use hooks?[/title]
 * As a wise programmer, you want to open your software to others. Cool! Hooks are perfectly suited for that.
 *
 * Firstly, select a unique id for you hook. We recommend to derive the id from the script name.
 * For example, the hook to integrate configuration panels into the script [script]control/index.php[/script]
 * has the id [code]control/index.php#configure[/code]. Simple enough, no?
 *
 * Secondly, select a type for the hook.
 * - '[code]link[/code]' means that a link to the hooking script will be inserted into the resulting page
 * - '[code]include[/code]' means that the hooking script has to be included
 * - '[code]call[/code]' to link some remote procedure call to one web service
 * - '[code]serve[/code]' to bind one web service to [script]services/xml_rpc.php[/script] or to another RPC script
 *
 * Thirdly, load the hook. Use the file resulting from hook scanning.
 * For example, here is an excerpt from [script]control/index.php[/script] on the hook used to link to
 * additional configuration panels:
 * [php]
 * // the hook for the control panel
 * if(is_callable(array('Hooks', 'link_scripts')))
 *	$context['text'] .= Hooks::link_scripts('control/index.php#configure', 'bullets');
 * [/php]
 *
 * Another example is an excerpt from [script]control/setup.php[/script] on the hook used to create/alter
 * extra tables of the database:
 * [php]
 * // the setup hook
 * if(is_callable(array('Hooks', 'include_scripts')))
 *	$context['text'] .= Hooks::include_scripts('control/setup.php');
 * [/php]
 *
 * [title]How to describe a hook?[/title]
 * A hook is a php script file that describes some extension to the system.
 *
 * One usage of hook is to include additional code to an existing script.
 * For example, here is a hook to create additional tables during the setup of the database.
 * Note that it is possible to call one function in the included file. This is an option however.
 * [php]
 * $hooks[] = array(
 *	'id'		=> 'control/setup.php',
 *	'type'		=> 'include',
 *	'script'	=> 'overlays/assignment.php',
 *	'function'	=> 'Assignment::setup',
 *	'label_en'	=> 'Assignments',
 *	'label_fr'	=> 'Assignations',
 *	'description_en' => 'Create tables for assignments.',
 *	'description_fr' => 'Cr&eacute;ation des tables pour les assignations.',
 *	'source' => 'http://www.yetanothercommunitysystem.com/' );
 * [/php]
 *
 * [title]How to install a hook?[/title]
 * Typically an extended YACS environment will have several files hook.php spreaded over the file system.
 * Of course, it would be not so efficient to browse the entire file system each time some hook is required.
 * Therefore, hooks have to be installed through the control panel, and this is exactly the job of the
 * script [code]control/scan.php[/code].
 * When triggered, [code]control/scan.php[/code] will look for files named '[code]hook.php[/code]'
 * or '[code]&lt;some_label_here&gt;_hook.php[/code]' in selected directories of the YACS installation directory.
 * It will then compile gathered information into the single file [code]parameters/hooks.include.php[/code].
 *
 * [title]Configuration information[/title]
 *
 * Configuration information is saved into [code]parameters/hooks.include.php[/code].
 *
 * The file [code]parameters/hooks.include.php.bak[/code] can be used to restore
 * the active configuration before the last change.
 *
 * This script does save hooking information even in demonstration mode, because of
 * software updates. There is no known security issue with this way of proceeding anyway.
 *
 * Hooking information is also saved in the file [code]parameters/hooks.xml[/code] for further processing.
 * For example, this file can be read by [script]services/index.php[/script] to list web services installed
 * on this system.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// include explicitly some libraries
include_once '../shared/global.php';

// what to do
$action = '';
if(!file_exists('../parameters/hooks.include.php'))
	$action = 'build';
if(!$action && isset($_REQUEST['action']))
	$action = $_REQUEST['action'];
if(!$action && isset($context['arguments'][0]))
	$action = $context['arguments'][0];
$action = strip_tags($action);

// load localized strings
i18n::bind('control');

// load the skin
load_skin('control');

// the path to this page
$context['path_bar'] = array( 'control/index.php' => i18n::s('Control Panel') );

// the title of the page
$context['page_title'] = i18n::s('Scan scripts for software extensions');

// include 'hook.php' for one directory, scan sub-directories
function include_hook($path) {
	global $context, $hooks;

	// animate user screen and take care of time
	global $scanned_directories;
	$scanned_directories++;

	// ensure enough execution time
	Safe::set_time_limit(30);

	// open the directory
	if(!$dir = Safe::opendir($path)) {
		$context['text'] .= sprintf(i18n::s('Impossible to read %s.'), $path).BR."\n";
		return;
	}

	// browse the directory
	while(($item = Safe::readdir($dir)) !== FALSE) {

		// skip some files
		if($item[0] == '.')
			continue;

		// load any 'hook.php', or any file which names ends with 'hook.php'
		$actual_item = str_replace('//', '/', $path.'/'.$item);
		if(preg_match('/hook\.php$/i', $item)) {
			include_once $actual_item;
			$context['text'] .= sprintf(i18n::s('Hook %s has been included'), $actual_item).BR."\n";

		// scan any sub dir except at server root
		} elseif(($path != $context['path_to_root']) && is_dir($actual_item))
			include_hook($actual_item);
	}

	// close the directory
	Safe::closedir($dir);
}

global $hooks, $action;

// scan only selected sub-directories
$scanned = array('', 'agents', 'articles', 'categories', 'control', 'included', 'overlays', 'parameters', 'sections', 'services', 'shared', 'tools', 'users');

// ensure that the user is an associate, except on first install
if(!Surfer::is_associate() && (file_exists('../parameters/switch.on') || file_exists('../parameters/switch.off'))) {

	// prevent access to this script
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

	// forward to the control panel
	$menu = array('control/' => i18n::s('Control Panel'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// review hooks, but never on first install
} elseif(($action == 'check') && (file_exists('../parameters/switch.on') || file_exists('../parameters/switch.off'))) {

	// include all scripts named 'hook.php' recursively
	foreach($scanned as $name)
		include_hook($context['path_to_root'].$name);
	global $scanned_directories;
	if($scanned_directories > 1)
		$context['text'] .= sprintf(i18n::s('%d directories have been scanned.'), $scanned_directories).BR."\n";

	// no hook has been found
	if(!count($hooks)) {
		$context['text'] .= i18n::s('No item has been found.');

	// introduce each hook
	} else {

		$links = $includes = $calls = $services = array();

		// consider each hook
		foreach($hooks as $hook) {

			// bad script!
			if(!$hook['id'] || !$hook['type'] || ($hook['type'] != 'call' && !$hook['script'])) {
				$context['text'] .= '<p><strong>'.i18n::s('Bad hook:').'</strong>'.BR."\n";
				foreach($hook as $name => $value)
					$context['text'] .= $name.': '.$value.BR;
				$context['text'] .= "</p>\n";
				continue;
			}

			// script does not exist
			if(!file_exists($context['path_to_root'].$hook['script'])) {
				$context['text'] .= '<p><strong>'.i18n::s('Script does not exist:').'</strong>'.BR."\n";
				foreach($hook as $name => $value)
					$context['text'] .= $name.': '.$value.BR;
				$context['text'] .= "</p>\n";
				continue;
			}

			// set default values
			if(!isset($hook['label_en']))
				$hook['label_en'] = i18n::c('*** undefined label');
			if(!isset($hook['description_en']))
				$hook['description_en'] = '';
			if(!isset($hook['source']))
				$hook['source'] = '';

			// item id
			$id = preg_replace('/([^\w]+)/', '_', $hook['id'].' '.$hook['script']);

			// depending on hook type
			switch($hook['type']) {

			case 'link':

				// form item
				$input = '<input type="checkbox" name="'.$id.'" value="Y" checked="checked" />';

				// description
				if($description = i18n::l($hook, 'description'))
					$description .= BR;
					
				// user information
				$text = '<dt>'.$input.' <b>'.i18n::l($hook, 'label').'</b></dt><dd>'.$description."\n";

				if(isset($hook['id']) && $hook['id'])
					$text .= '- '.sprintf(i18n::s('identifier: %s'), $hook['id']).BR."\n";

				if(isset($hook['script']) && $hook['script'])
					$text .= '- '.sprintf(i18n::s('script: %s'), $hook['script']).BR."\n";

				if(isset($hook['source']) && $hook['source'])
					$text .= '- '.sprintf(i18n::s('source: %s'), $hook['source']).BR."\n";

				$text .= "</dd>\n\n";

				// remember for later use
				$links[ $id ] = $text;

				break;

			case 'include':

				// form item
				$input = '<input type="checkbox" name="'.$id.'" value="Y" checked="checked" />';

				// description
				if($description = i18n::l($hook, 'description'))
					$description .= BR;
					
				// user information
				$text = '<dt>'.$input.' <b>'.i18n::l($hook, 'label').'</b></dt><dd>'.$description."\n";

				if(isset($hook['id']) && $hook['id'])
					$text .= '- '.sprintf(i18n::s('identifier: %s'), $hook['id']).BR."\n";

				if(isset($hook['script']) && $hook['script'])
					$text .= '- '.sprintf(i18n::s('script: %s'), $hook['script']).BR."\n";

				if(isset($hook['function']) && $hook['function'])
					$text .= '- '.sprintf(i18n::s('function: %s'), $hook['function']).BR."\n";

				if(isset($hook['source']) && $hook['source'])
					$text .= '- '.sprintf(i18n::s('source: %s'), $hook['source']).BR."\n";

				$text .= "</dd>\n\n";

				// remember for later use
				$includes[ $id ] = $text;

				break;

			case 'call':

				// form item
				$input = '<input type="checkbox" name="'.$id.'" value="Y" checked="checked" />';

				// description
				if($description = i18n::l($hook, 'description'))
					$description .= BR;
					
				// user information
				$text = '<dt>'.$input.' <b>'.i18n::l($hook, 'label').'</b></dt><dd>'.$description."\n";

				if(isset($hook['service']) && $hook['service'])
					$text .= '- '.sprintf(i18n::s('service: %s'), $hook['service']).BR."\n";

				if(isset($hook['link']) && $hook['link'])
					$text .= '- '.sprintf(i18n::s('link: %s'), $hook['link']).BR."\n";

				if(isset($hook['source']) && $hook['source'])
					$text .= '- '.sprintf(i18n::s('source: %s'), $hook['source']).BR."\n";

				$text .= "</dd>\n\n";

				// remember for later use
				$calls[ $id ] = $text;

				break;

			case 'serve':

				// form item
				$input = '<input type="checkbox" name="'.$id.'" value="Y" checked="checked" />';

				// description
				if($description = i18n::l($hook, 'description'))
					$description .= BR;
					
				// user information
				$text = '<dt>'.$input.' <b>'.i18n::l($hook, 'label').'</b></dt><dd>'.$description."\n";

				if(isset($hook['id']) && $hook['id'])
					$text .= '- '.sprintf(i18n::s('service: %s'), $hook['id']).BR."\n";

				if(isset($hook['script']) && $hook['script'])
					$text .= '- '.sprintf(i18n::s('script: %s'), $hook['script']).BR."\n";

				if($hook['function'] && $hook['function'])
					$text .= '- '.sprintf(i18n::s('function: %s'), $hook['function']).BR."\n";

				if(isset($hook['source']) && $hook['source'])
					$text .= '- '.sprintf(i18n::s('source: %s'), $hook['source']).BR."\n";

				$text .= "</dd>\n\n";

				// remember for later use
				$services[ $id ] = $text;

				break;

			default:
				// user information
				$context['text'] .= '<b>'.sprintf(i18n::s('Bad hook type %s to %s for %s'), $hook['type'], $hook['script'], $hook['id']).'</b>'.BR."\n";

			}

		}

		$context['text'] .= '<p>'.i18n::s('Review hooks in the following list and uncheck unwanted extensions.')."</p>\n";
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'"><div>'."\n";

		// list linking hooks
		if(count($links)) {
			asort($links);
			$context['text'] .= Skin::build_block(i18n::s('Linking hooks'), 'title').'<dl>'.implode('', array_values($links)).'</dl>';
		}

		// list including hooks
		if(count($includes)) {
			asort($includes);
			$context['text'] .= Skin::build_block(i18n::s('Including hooks'), 'title').'<dl>'.implode('', array_values($includes)).'</dl>';
		}

		// list calling hooks
		if(count($calls)) {
			asort($calls);
			$context['text'] .= Skin::build_block(i18n::s('Client hooks'), 'title').'<dl>'.implode('', array_values($calls)).'</dl>';
		}

		// list serving hooks
		if(count($services)) {
			asort($services);
			$context['text'] .= Skin::build_block(i18n::s('Service hooks'), 'title').'<dl>'.implode('', array_values($services)).'</dl>';
		}

		// the submit button
		$context['text'] .= '<p>'
			.Skin::build_submit_button(i18n::s('Yes, I want to (re)build the set of hooks'))
			.'<input type="hidden" name="reviewed" value="yes" />'
			.'<input type="hidden" name="action" value="build" /></p>';

		$context['text'] .= '</div></form>';

	}

	// display the execution time
	$time = round(get_micro_time() - $context['start_time'], 2);
	$context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

	// back to the control panel
	$menu = array('control/' => i18n::s('Control Panel'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// rebuild hooks or first installation
} elseif($action == 'build' || $action == 'check') {

	// feed-back to the user
	$context['text'] .= '<p>'.i18n::s('Following hooks have been detected and integrated into the file parameters/hooks.include.php')."</p>\n";

	// first installation
	if(!file_exists('../parameters/switch.on') && !file_exists('../parameters/switch.off'))
		$context['text'] .= '<p>'.i18n::s('Review provided information and go to the bottom of the page to move forward.')."</p>\n";

	// include all scripts named 'hook.php' recursively
	foreach($scanned as $name)
		include_hook($context['path_to_root'].$name);
	global $scanned_directories;
	if($scanned_directories > 1)
		$context['text'] .= sprintf(i18n::s('%d directories have been scanned.'), $scanned_directories).BR."\n";

	// no hook has been found
	if(!count($hooks))
		$context['text'] .= i18n::s('No item has been found.');

	// compile all hooks
	else {

		// backup the old version
		Safe::unlink('../parameters/hooks.include.php.bak');
		Safe::rename('../parameters/hooks.include.php', '../parameters/hooks.include.php.bak');

		// what we have to produce
		$called_items = array();
		$included_items = array();
		$included_items['tick'] = '';
		$linked_items = array();
		$served_items = array();

		// we will remember a xml file as well
		$xml = '';

		// consider each hook
		foreach($hooks as $hook) {

			// bad script!
			if(!$hook['id'] || !$hook['type'] || ($hook['type'] != 'call' && !$hook['script'])) {
				$context['text'] .= '<p><strong>'.i18n::s('Bad hook:').'</strong>'.BR."\n";
				foreach($hook as $name => $value)
					$context['text'] .= $name.': '.$value.BR;
				$context['text'] .= "</p>\n";
				continue;
			}

			// script does not exist
			if(!file_exists($context['path_to_root'].$hook['script'])) {
				$context['text'] .= '<p><strong>'.i18n::s('Script does not exist:').'</strong>'.BR."\n";
				foreach($hook as $name => $value)
					$context['text'] .= $name.': '.$value.BR;
				$context['text'] .= "</p>\n";
				continue;
			}

			// set default values
			if(!isset($hook['label_en']))
				$hook['label_en'] = '*** undefined label';
			if(!isset($hook['description_en']))
				$hook['description_en'] = '';
			if(!isset($hook['source']))
				$hook['source'] = '';

			// item id
			$id = preg_replace('/([^\w]+)/', '_', $hook['id'].' '.$hook['script']);

			// ensure this item has been selected
			if(isset($_REQUEST['reviewed']) && ($_REQUEST['reviewed'] == 'yes')) {
				unset($local['name_en']);
				if(isset($hook['label_en']))
					$local['name_en'] = $hook['label_en'];
				unset($local['name_fr']);
				if(isset($hook['label_fr']))
					$local['name_fr'] = $hook['label_fr'];
				if(!isset($_REQUEST[$id]) || ($_REQUEST[$id] != 'Y')) {
					$context['text'] .= sprintf(i18n::s('Disabling extension %s'), $id).BR."\n";
					continue;
				}
			}

			// depending on hook type
			switch($hook['type']) {

			case 'link':

				// user information
				$context['text'] .= sprintf(i18n::s('Linking hook %s for %s'), $hook['script'], $hook['id']).BR."\n";

				// stay politically correct
				if(!isset($linked_items[$hook['id']]))
					$linked_items[$hook['id']] = '';

				// compilation
				$linked_items[$hook['id']] .= "\n";
				if(isset($hook['label_en']))
					$linked_items[$hook['id']] .= "\t\t\t".'$local[\'label_en\'] = \''.addslashes($hook['label_en']).'\';'."\n";
				if(isset($hook['label_fr']))
					$linked_items[$hook['id']] .= "\t\t\t".'$local[\'label_fr\'] = \''.addslashes($hook['label_fr']).'\';'."\n";
				$linked_items[$hook['id']] .= "\t\t\t".'$links[\''.$hook['script'].'\'] = i18n::user(\'label\');'."\n";
				if(isset($hook['description_en']))
					$linked_items[$hook['id']] .= "\t\t\t".'$local[\'label_en\'] = \''.addslashes($hook['description_en']).'\';'."\n";
				if(isset($hook['description_fr']))
					$linked_items[$hook['id']] .= "\t\t\t".'$local[\'label_fr\'] = \''.addslashes($hook['description_fr']).'\';'."\n";
				$linked_items[$hook['id']] .= "\t\t\t".'$descriptions[\''.$hook['script'].'\'] = i18n::user(\'label\');'."\n";
				if(isset($hook['source']))
					$linked_items[$hook['id']] .= "\t\t\t".'$sources[\''.$hook['script'].'\'] = \''.addslashes($hook['source']).'\';'."\n";

				break;

			case 'include':

				// user information
				$context['text'] .= sprintf(i18n::s('Including hook %s for %s'), $hook['script'], $hook['id']).BR."\n";

				// stay politically correct
				if(!isset($included_items[$hook['id']]))
					$included_items[$hook['id']] = '';

				// compilation
				$included_items[$hook['id']] .= "\n"
					."\t\t\t".'if(file_exists($context[\'path_to_root\'].\''.$hook['script'].'\')) {'."\n"
					."\t\t\t\t".'include_once $context[\'path_to_root\'].\''.$hook['script'].'\';'."\n";

				// with a function call
				if(isset($hook['function']))
					$included_items[$hook['id']] .= "\t\t\t\t".'$text .= '.$hook['function'].'($parameters);'."\n";

				// end of this block
				$included_items[$hook['id']] .= "\t\t\t}\n";

				break;

			case 'call':

				// user information
				$context['text'] .= sprintf(i18n::s('Calling hook for %s'), $hook['id']).BR."\n";

				// local invocation
				if(!isset($hook['link']))
					$hook['link'] = 'localhost';

				// stay politically correct
				if(!isset($hook['id']))
					$hook['id'] = 'hello';

				// initialize all calls for this id
				if(!isset($called_items[$hook['id']]))
					$called_items[$hook['id']] = '';

				// compilation
				$called_items[$hook['id']] .= "\n"
					."\t\t\t".'$result = array_merge($result, Call::invoke(\''.$hook['link'].'\', \''.$hook['id'].'\', $parameters, $variant));'."\n";

				break;

			case 'serve':

				// user information
				$context['text'] .= sprintf(i18n::s('Serving hook %s for %s'), $hook['script'], $hook['id']).BR."\n";

				// stay politically correct
				if(!isset($served_items[$hook['id']]))
					$served_items[$hook['id']] = '';

				// compilation
				$served_items[$hook['id']] .= "\n"
					."\t\t\t".'include_once $context[\'path_to_root\'].\''.$hook['script'].'\';'."\n";

				// with a function call
				if($hook['function'])
					$served_items[$hook['id']] .= "\t\t\t".'$result = '.$hook['function'].'($parameters);'."\n";

				break;

			default:
				// user information
				$context['text'] .= '<b>'.sprintf(i18n::s('Bad hook type %s to %s for %s'), $hook['type'], $hook['script'], $hook['id']).'</b>'.BR."\n";
				continue;

			}

			// append to hooks.xml
			$xml .= "<hook>\n";
			foreach($hook as $label => $value) {
				$xml .= "\t<".$label.'>'.$value.'<'.$label.">\n";
			}
			$xml .= "</hook>\n\n";
		}

		// the header section
		$content = '<?php'."\n"
			.'// This file has been created by the script control/scan.php'."\n"
			.'// on '.gmdate("F j, Y, g:i a").' GMT, for '.Surfer::get_name().'. Please do not modify it manually.'."\n"
			."\n"
			.'class Hooks {'."\n\n";

		// start the linking function
		$content .= "\t".'function link_scripts($id, $variant=\'list\') {'."\n"
			."\t\t".'global $local, $context;'."\n\n"
			."\t\t".'$links = array();'."\n\n"
			."\t\t".'switch($id) {'."\n\n";

		// one linking item per id
		if(count($linked_items))
			foreach($linked_items as $id => $item)
				$content .= "\t\t".'case \''.$id.'\':'."\n".$item
					."\t\t\tbreak;\n\n";

		// return the array itself
		$content .= "\t\t}\n\n\t\t".'if($variant == \'array\')'."\n"
			."\t\t\t".'return $links;'."\n\n";

		// no linking hook has been found
		$content .= "\t\t".'if(!count($links))'."\n"
			."\t\t\t".'return NULL;'."\n\n";

		// format the result
		$content .= "\t\t".'$text = \'\';'."\n\n"
			."\t\t".'if($variant == \'list\')'."\n"
			."\t\t\t".'$text .= \'<ul>\';'."\n\n"
			."\t\t".'foreach($links as $script => $label) {'."\n"
			."\t\t\t".'$text .= \'<li>\'.Skin::build_link($script, $label, \'shortcut\');'."\n"
			."\t\t\t".'if($descriptions[$script])'."\n"
			."\t\t\t\t".'$text .= \' - \'.$descriptions[$script];'."\n"
			."\t\t\t".'if($sources[$script])'."\n"
			."\t\t\t\t".'$text .= \' (\'.$sources[$script].\')\';'."\n"
			."\t\t\t".'$text .= \'</li>\';'."\n"
			."\t\t".'}'."\n\n"
			."\t\t".'if($variant == \'list\')'."\n"
			."\t\t\t".'$text .= \'</ul>\';'."\n\n"
			."\t\t".'return $text;'."\n\n";

		// end the linking function
		$content .= "\t".'}'."\n\n";

		// start the including function
		$content .= "\t".'function include_scripts($id, $parameters=NULL) {'."\n"
			."\t\t".'global $local, $context;'."\n\n"
			."\t\t".'$text = \'\';'."\n\n"
			."\t\t".'switch($id) {'."\n\n";

		// default cron (or 'tick') hook calls for news update
		$included_items['tick'] .= "\n"
			."\t\t\t".'include_once $context[\'path_to_root\'].\'feeds/feeds.php\';'."\n"
			."\t\t\t".'$text .= Feeds::tick_hook($parameters);'."\n";
		$context['text'] .= sprintf(i18n::s('Including hook %s for %s'), 'news', 'tick').BR."\n";

		// one including item per id
		if(count($included_items))
			foreach($included_items as $id => $item)
				$content .= "\t\t".'case \''.$id.'\':'."\n".$item
					."\n\t\t\tbreak;\n\n";

		// end the including function
		$content .= "\t\t}\n\n\t\t".'return $text;'."\n\n\t}\n\n";

		// start the calling function
		$content .= "\t".'function call_scripts($id, $parameters, $variant=\'XML-RPC\') {'."\n"
			."\t\t".'global $local, $context;'."\n"
			."\t\t".'include_once $context[\'path_to_root\'].\'services/call.php\';'."\n\n"
			."\t\t".'$result = array();'."\n\n"
			."\t\t".'switch($id) {'."\n\n";

		// one including item per id
		if(count($called_items))
			foreach($called_items as $id => $item)
				$content .= "\t\t".'case \''.$id.'\':'."\n".$item
					."\t\t\tbreak;\n\n";

		// end the calling function
		$content .= "\t\t}\n\n\t\t".'return $result;'."\n";
		$content .= "\t".'}'."\n\n";

		// start the serving function
		$content .= "\t".'function serve_scripts($id, $parameters) {'."\n"
			."\t\t".'global $local, $context;'."\n\n"
			."\t\t".'$result = NULL;'."\n\n"
			."\t\t".'switch($id) {'."\n\n";

		// one including item per id
		if(count($served_items))
			foreach($served_items as $id => $item)
				$content .= "\t\t".'case \''.$id.'\':'."\n".$item
					."\t\t\tbreak;\n\n";

		// end the serving function
		$content .= "\t\t}\n\n\t\t".'return $result;'."\n";
		$content .= "\t".'}'."\n\n";

		// the tail section
		$content .= '}'."\n"
			.'?>'."\n";

		// compile all hooks into a single file
		if(!Safe::file_put_contents('parameters/hooks.include.php', $content))
			$context['text'] .= sprintf(i18n::s('Impossible to write to %s.'), 'parameters/hooks.include.php').BR."\n";
		else {
			$context['text'] .= i18n::s('Hooks have been compiled in parameters/hooks.include.php').BR."\n";

			// remember the change
			$label = sprintf(i18n::c('%s has been updated'), 'parameters/hooks.include.php');
			Logger::remember('control/scan.php', $label);

		}

		// list hooks using xml
		if(isset($xml)) {
			$xml = '<?xml version="1.0" ?>'."\n"
				.'<hooks>'."\n"
				.$xml
				.'</hooks>'."\n";

			if(!Safe::file_put_contents('parameters/hooks.xml', $xml))
				$context['text'] .= sprintf(i18n::s('Impossible to write to %s.'), 'parameters/hooks.xml').BR."\n";
			else
				$context['text'] .= i18n::s('Hooks have been listed in parameters/hooks.xml').BR."\n";
		}

	}

	// display the execution time
	$time = round(get_micro_time() - $context['start_time'], 2);
	$context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

	// if the server has been switched off, update the database schema
	if(file_exists('../parameters/switch.off')) {
		$context['text'] .= Skin::build_block('<form method="post" action="setup.php"><p class="assistant_bar">'."\n"
			.Skin::build_submit_button(i18n::s('Update the database schema'))."\n"
			.'<input type="hidden" name="action" value="build" />'."\n"
			.'</p></form>', 'bottom');

		// this may take several minutes
		$context['text'] .= '<p>'.i18n::s('When you will click on the button the server will be immediately requested to proceed. However, because of the so many things to do on the back-end, you may have to wait for minutes before getting a response displayed. Thank you for your patience.').'</p>';

	// create the database on first installation
	} elseif(!file_exists('../parameters/switch.on')) {
		$context['text'] .= Skin::build_block('<form method="post" action="setup.php"><p class="assistant_bar">'."\n"
			.Skin::build_submit_button(i18n::s('Create tables in the database'))."\n"
			.'<input type="hidden" name="action" value="build" />'."\n"
			.'</p></form>', 'bottom');

		// this may take several minutes
		$context['text'] .= '<p>'.i18n::s('When you will click on the button the server will be immediately requested to proceed. However, because of the so many things to do on the back-end, you may have to wait for minutes before getting a response displayed. Thank you for your patience.').'</p>';

	// or back to the control panel
	} else {
		$menu = array('control/' => i18n::s('Control Panel'));
		$context['text'] .= Skin::build_list($menu, 'menu_bar');
	}

// display current hooks
} else {

	// the splash message
	$context['text'] .= i18n::s('This script will scan your php scripts to install software hooks.');

	// the submit button
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'
		.Skin::build_submit_button(i18n::s('Scan scripts for software extensions'), NULL, NULL, 'confirmed')
		.'<input type="hidden" name="action" value="check" />'
		.'</p></form>';

	// the script used for form handling at the browser
	$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
		.'// set the focus on first form field'."\n"
		.'$("confirmed").focus();'."\n"
		.'// ]]></script>'."\n";

	// this may take several minutes
	$context['text'] .= '<p>'.i18n::s('When you will click on the button the server will be immediately requested to proceed. However, because of the so many things to do on the back-end, you may have to wait for minutes before getting a response displayed. Thank you for your patience.').'</p>';

	// display the existing hooks configuration file, if any
	$content = Safe::file_get_contents('../parameters/hooks.include.php');
	if(strlen($content)) {
		$context['text'] .= Skin::build_box(sprintf(i18n::s('Current content of %s'), 'parameters/hooks.include.php'), Safe::highlight_string($content), 'folder');

	}

}

// render the skin
render_skin();

?>