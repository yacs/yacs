<?php
/**
 * the control panel for this web server
 *
 * The Control Panel runs a number of checks, and offers some corrective action
 * where applicable. Ultimately, when everything goes fine, it is the preferred
 * tool used by webmasters to manage their site.
 *
 * To ease the setup tasks this script is working like an assistant:
 *
 * - If the main configuration file [code]parameters/control.include.php[/code] is absent,
 * propose to switch either to [script]setup.php[/script] or to [script]control/configure.php[/script]
 *
 * - If there is no database, propose to switch to [script]control/configure.php[/script]
 *
 * - If the configuration file [code]parameters/hooks.include.php[/code] is absent,
 * propose to switch to [script]control/scan.php[/script]
 *
 * - If there is no tables in database, propose to switch to [script]control/setup.php[/script]
 *
 * - If the users table is empty, propose to switch to [script]control/populate.php[/script]
 *
 * - If the skin configuration file [code]parameters/skins.include.php[/code] is absent,
 * propose to switch to [script]skins/configure.php[/script]
 *
 * - If there is no switch file, jump to the [script]setup.php[/script] page
 *
 * - Else actually display the control panel
 *
 *
 * The control panel has several tabbed panels:
 * - Configuration panels
 * - Database overview
 * - Content management
 * - System management
 *
 * The overview tab provides key information about database and server content.
 * Useful to check the number of pages, images and files that have been posted.
 * The total size of images and files is provided as well.

 * The Content Management tab provides useful commands to augment or change
 * site content.
 *
 * The Configuration Panels tab is where associates can change parameters of the
 * various modules. This means that every YACS server can be configured
 * directly from the control panel. There is no separate back-end script as with
 * other popular content management systems.
 *
 * The System Management tab relates to general setup, database operation, etc.
 * Commands provided here are reserved to associates. Software versions are
 * provided as well, including YACS, PHP, MySQL and Apache.
 *
 * The control panel has a hook to integrate extended configuration panels:
 * - id: 'control/index.php#configure'
 * - type: 'link'
 *
 * The hook to list extra tools:
 * - id: 'control/index.php#tools'
 * - type: 'link'
 *
 * The hook to list extra modules:
 * - id: 'control/index.php#modules'
 * - type: 'link'
 *
 * @see control/scan.php
 *
 * @author Bernard Paques
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @author GnapZ
 * @tester Agnes
 * @tester FabriceV
 * @tester Lucrecius
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// include some libraries
include_once '../shared/global.php';

// load localized strings
i18n::bind('control');

// load the skin
load_skin('control');

// no parameters configured yet
if(!file_exists('../parameters/control.include.php')) {

	// title
	$context['page_title'] = i18n::s('Please configure your server');

	// splash screen
	$context['text'] .= '<p>'.i18n::s('No configuration file has been found. If you are installing a brand new server, follow the link below to create one.')."</p>\n";

	// link to the installation page
	$context['text'] .= '<p><a href="../setup.php">'.i18n::s('Jump to the installation page')."</a></p>\n";

	// splash screen
	$context['text'] .= '<p>'.i18n::s('Else follow the link below to load the configuration form.')."</p>\n";

	// link to the configuration page
	$context['text'] .= '<p><a href="configure.php">'.i18n::s('Jump to the configuration page')."</a></p>\n";

// no access to the database server yet
} elseif(!isset($context['database']) || !$context['database']
		|| !isset($context['connection']) || !$context['connection']) {

	// title
	$context['page_title'] = i18n::s('No access to the database server');

	// splash screen
	$context['text'] .= '<p>'.i18n::s('Impossible to access the database mentioned in your configuration file. Please create a database, or follow the link to change the configuration file.')."</p>\n";

	// link to the configuration page
	$context['text'] .= '<p><a href="configure.php">'.i18n::s('Jump to the configuration page')."</a></p>\n";

} else {

	// try to create a database if it does not exist
	$query = "CREATE DATABASE IF NOT EXISTS ".SQL::escape($context['database']);
	SQL::query($query, TRUE, $context['connection']);

	// still no database
	if(!SQL::has_database($context['database'])) {

		// title
		$context['page_title'] = i18n::s('Please create a database');

		// splash screen
		$context['text'] .= '<p>'.i18n::s('Impossible to access the database mentioned in your configuration file. Please create a database, or follow the link to change the configuration file.')."</p>\n";

		// link to the configuration page
		$context['text'] .= '<p><a href="configure.php">'.i18n::s('Go to the configuration page to change database parameters')."</a></p>\n";

	// no hooks found yet
	} elseif(!file_exists('../parameters/hooks.include.php')) {

		// title
		$context['page_title'] = i18n::s('Please configure software extensions');

		// splash screen
		$context['text'] .= '<p>'.i18n::s('No configuration file for extensions has been found. If you are installing a brand new server, follow the link to create one.')."</p>\n";

		// link to the scan page
		$context['text'] .= '<p><a href="scan.php">'.i18n::s('Look for software extensions')."</a></p>\n";

	// no tables
	} elseif(SQL::count_tables() < 5) {

		// title
		$context['page_title'] = i18n::s('Please create tables');

		// splash screen
		$context['text'] .= '<p>'.i18n::s('The database is currently empty. Please follow the link to create tables of the database.')."</p>\n";

		// link to the setup page
		$context['text'] .= '<p><a href="setup.php">'.i18n::s('Create tables in the database')."</a></p>\n";

	} else {

		// the user table is empty
		$query = "SELECT count(*) FROM ".SQL::table_name('users');
		$count = 0;
		if($result = SQL::query($query, TRUE, $context['users_connection'])) {
			$row = SQL::fetch_row($result);
			$count = $row[0];
		}
		if(!$result || ($count == 0)) {

			// title
			$context['page_title'] = i18n::s('Please populate tables');

			// splash screen
			$context['text'] .= '<p>'.i18n::s('The user table is currently empty. Please follow the link to populate the database.')."</p>\n";

			// link to the populate page
			$context['text'] .= '<p><a href="populate.php">'.i18n::s('Jump to the populate page')."</a></p>\n";

		// no parameters configured yet for the skin
		} elseif(!file_exists('../parameters/skins.include.php')) {

			// title
			$context['page_title'] = i18n::s('Please configure your server');

			// splash screen
			$context['text'] .= '<p>'.i18n::s('A configuration file is missing. If you are installing a brand new server, follow the link to create one.')."</p>\n";

			// link to the configuration page
			$context['text'] .= '<p><a href="../skins/configure.php">'.sprintf(i18n::s('%s: %s'), i18n::s('Configure'), i18n::s('Page factory'))."</a></p>\n";

		// end of verifications
		} else {

			// the title of the page
			$context['page_title'] = i18n::s('Control Panel');

			// server is closed
			if(file_exists($context['path_to_root'].'parameters/switch.off')) {

				// title
				Logger::error(i18n::s('The server is currently switched off. All users are redirected to the closed page.'));

				// link to the switch page
				if(Surfer::is_associate())
					$context['text'] = '<p style="text-decoration: blink;"><a href="switch.php?action=on">'.i18n::s('Switch the server on again').'</a></p>';

			// there is no switch file, redirect to the setup assistant
			} elseif(!file_exists($context['path_to_root'].'parameters/switch.on'))
				Safe::redirect($context['url_to_home'].$context['url_to_root'].'setup.php');

			// server is running on demonstration mode
			if(file_exists($context['path_to_root'].'parameters/demo.flag'))
				Logger::error(i18n::s('The server is running in demonstration mode, and restrictions apply, even to associates.'));

			// this is a tabbed page
			$all_tabs = array();

			//
			// the Configuration Panels tab is reserved to associates
			//
			if(Surfer::is_associate()) {

				$text = '<p>'.i18n::s('Click on following links to review or change parameters of this server.').'</p>';

				$commands = array();

				// configuration scripts that are part of the core -- some complex commands
				if(Surfer::has_all())
					$commands[] = sprintf(i18n::s('%s - configure database, security and other essential parameters'), Skin::build_link('control/configure.php', i18n::s('System parameters'), 'basic'));

				// apache configuration -- some complex commands
				if(Surfer::has_all())
					$commands[] = sprintf(i18n::s('%s - build a customized configuration file'), Skin::build_link('control/htaccess/', i18n::s('Apache .htaccess'), 'basic'));

				$commands[] = sprintf(i18n::s('%s - define permissions given to people'), Skin::build_link('users/configure.php', i18n::s('People'), 'basic'));

				$commands[] = sprintf(i18n::s('%s - select and configure building blocks for the front page'), Skin::build_link('configure.php', i18n::s('Front page'), 'basic'));

				$commands[] = sprintf(i18n::s('%s - change meta-information, etc.'), Skin::build_link('skins/configure.php', i18n::s('Page factory'), 'basic'));

				if(Surfer::has_all())
					$commands[] = sprintf(i18n::s('%s - select and test themes available at this server'), Skin::build_link('skins/', i18n::s('Themes'), 'basic'));

				if(Surfer::has_all())
					$commands[] = sprintf(i18n::s('%s - to change rendering of dynamic Flash objects'), Skin::build_link('feeds/flash/configure.php', i18n::s('Flash'), 'basic'));

				$commands[] = sprintf(i18n::s('%s - enhance information provided through RSS'), Skin::build_link('feeds/configure.php', i18n::s('Information channels'), 'basic'));

				if(Surfer::has_all())
					$commands[] = sprintf(i18n::s('%s - to extend accepted types and to make uploaded files available from FTP'), Skin::build_link('files/configure.php', i18n::s('Files'), 'basic'));

				if(isset($context['with_email']) && ($context['with_email'] == 'Y'))
					$commands[] = sprintf(i18n::s('%s - change the template used for newsletters'), Skin::build_link('letters/configure.php', i18n::s('Newsletters'), 'basic'));

				if(Surfer::has_all())
					$commands[] = sprintf(i18n::s('%s - add specific configuration details for some host names or sub-domains'), Skin::build_link('control/virtual.php', i18n::s('Virtual hosts'), 'basic'));

				if(Surfer::has_all())
					$commands[] = sprintf(i18n::s('%s - change parameters for back-end services'), Skin::build_link('services/configure.php', i18n::s('Web services'), 'basic'));

				if(Surfer::has_all())
					$commands[] = sprintf(i18n::s('%s - ban spamming hosts'), Skin::build_link('servers/configure.php', i18n::s('Servers'), 'basic'));

				if(Surfer::has_all())
					$commands[] = sprintf(i18n::s('%s - for incoming messages and files'), Skin::build_link('agents/configure.php', i18n::s('Background processing'), 'basic'));

				if(Surfer::has_all())
					$commands[] = sprintf(i18n::s('%s - change the reference server used for software updates'), Skin::build_link('scripts/configure.php', i18n::s('Server software'), 'basic'));

				// insert commands
				if(count($commands))
					$text .= '<ul><li>'.join('</li><li>', $commands).'</li></ul>';

				// the hook for the control panels
				if(is_callable(array('Hooks', 'link_scripts')) && ($links = Hooks::link_scripts('control/index.php#configure', 'bullets')))
					$text .= '<ul>'.$links.'</ul>';

				// build another tab
				$all_tabs = array_merge($all_tabs, array(array('configuration', i18n::s('Configuration'), 'configuration_panel', $text)));
			}

			//
			// overview tab
			//
			$text = '<p>'.i18n::s('The following table reports on current content of this server. Click on any link to get more details.').'</p>';

			// use a neat table for the layout
			$text .= Skin::table_prefix('wide');
			$text .= Skin::table_row(array(i18n::s('Table'), 'center='.i18n::s('Records'), 'center='.i18n::s('First record'), 'center='.i18n::s('Last record')), 'header');
			$lines = 2;

			// activities
			if($row = SQL::table_stat('activities')) {
				$cells = array();
				$cells[] = SQL::table_name('activities');
				$cells[] = 'center='.$row[0];
				$cells[] = 'center='.($row[1]?Skin::build_date($row[1]):'--');
				$cells[] = 'center='.($row[2]?Skin::build_date($row[2]):'--');
				$text .= Skin::table_row($cells, $lines++);
			} else
				$text .= Skin::table_row(array(SQL::table_name('activities'), i18n::s('unknown or empty table'), ' ', ' '), $lines++);

			// articles
			if($row = SQL::table_stat('articles')) {
				$cells = array();
				$cells[] = Skin::build_link('articles/', SQL::table_name('articles'), 'basic');
				$cells[] = 'center='.$row[0];
				$cells[] = 'center='.($row[1]?Skin::build_date($row[1]):'--');
				$cells[] = 'center='.($row[2]?Skin::build_date($row[2]):'--');
				$text .= Skin::table_row($cells, $lines++);
			} else
				$text .= Skin::table_row(array(SQL::table_name('articles'), i18n::s('unknown or empty table'), ' ', ' '), $lines++);

			// cache
			if($row = SQL::table_stat('cache')) {
				$cells = array();
				$cells[] = SQL::table_name('cache');
				$cells[] = 'center='.$row[0];
				$cells[] = 'center='.($row[1]?Skin::build_date($row[1]):'--');
				$cells[] = 'center='.($row[2]?Skin::build_date($row[2]):'--');
				$text .= Skin::table_row($cells, $lines++);
			} else
				$text .= Skin::table_row(array(SQL::table_name('cache'), i18n::s('unknown or empty table'), ' ', ' '), $lines++);

			// categories
			if($row = SQL::table_stat('categories')) {
				$cells = array();
				$cells[] = Skin::build_link('categories/', SQL::table_name('categories'), 'basic');
				$cells[] = 'center='.$row[0];
				$cells[] = 'center='.($row[1]?Skin::build_date($row[1]):'--');
				$cells[] = 'center='.($row[2]?Skin::build_date($row[2]):'--');
				$text .= Skin::table_row($cells, $lines++);
			} else
				$text .= Skin::table_row(array(SQL::table_name('categories'), i18n::s('unknown or empty table'), ' ', ' '), $lines++);

			// comments
			if($row = SQL::table_stat('comments')) {
				$cells = array();
				$cells[] = Skin::build_link('comments/', SQL::table_name('comments'), 'basic');
				$cells[] = 'center='.$row[0];
				$cells[] = 'center='.($row[1]?Skin::build_date($row[1]):'--');
				$cells[] = 'center='.($row[2]?Skin::build_date($row[2]):'--');
				$text .= Skin::table_row($cells, $lines++);
			} else
				$text .= Skin::table_row(array(SQL::table_name('comments'), i18n::s('unknown or empty table'), ' ', ' '), $lines++);

			// dates
			if($row = SQL::table_stat('dates')) {
				$cells = array();
				$cells[] = Skin::build_link('dates/', SQL::table_name('dates'), 'basic');
				$cells[] = 'center='.$row[0];
				$cells[] = 'center='.($row[1]?Skin::build_date($row[1]):'--');
				$cells[] = 'center='.($row[2]?Skin::build_date($row[2]):'--');
				$text .= Skin::table_row($cells, $lines++);
			} else
				$text .= Skin::table_row(array(SQL::table_name('dates'), i18n::s('unknown or empty table'), ' ', ' '), $lines++);

			// enrolments
			if($row = SQL::table_stat('enrolments')) {
				$cells = array();
				$cells[] = SQL::table_name('enrolments');
				$cells[] = 'center='.$row[0];
				$cells[] = 'center='.($row[1]?Skin::build_date($row[1]):'--');
				$cells[] = 'center='.($row[2]?Skin::build_date($row[2]):'--');
				$text .= Skin::table_row($cells, $lines++);
			} else
				$text .= Skin::table_row(array(SQL::table_name('enrolments'), i18n::s('unknown or empty table'), ' ', ' '), $lines++);

			// files
			include_once '../files/files.php';
			if($stats = Files::stat()) {
				$cells = array();
				$size = '';
				if($stats['total_size'])
					$size = ' ('.Skin::build_number($stats['total_size']).')';
				$cells[] = Skin::build_link('files/', SQL::table_name('files'), 'basic').$size;
				$cells[] = 'center='.$stats['count'];
				$cells[] = 'center='.($stats['oldest_date']?Skin::build_date($stats['oldest_date']):'--');
				$cells[] = 'center='.($stats['newest_date']?Skin::build_date($stats['newest_date']):'--');
				$text .= Skin::table_row($cells, $lines++);
			} else
				$text .= Skin::table_row(array(SQL::table_name('files'), i18n::s('unknown or empty table'), ' ', ' '), $lines++);

			// images
			include_once '../images/images.php';
			if($stats = Images::stat()) {
				$cells = array();
				$size = '';
				if($stats['total_size'])
					$size = ' ('.Skin::build_number($stats['total_size']).')';
				$cells[] = Skin::build_link('images/', SQL::table_name('images'), 'basic').$size;
				$cells[] = 'center='.$stats['count'];
				$cells[] = 'center='.($stats['oldest_date']?Skin::build_date($stats['oldest_date']):'--');
				$cells[] = 'center='.($stats['newest_date']?Skin::build_date($stats['newest_date']):'--');
				$text .= Skin::table_row($cells, $lines++);
			} else
				$text .= Skin::table_row(array(SQL::table_name('images'), i18n::s('unknown or empty table'), ' ', ' '), $lines++);

			// issues
			if($row = SQL::table_stat('issues')) {
				$cells = array();
				$cells[] = SQL::table_name('issues');
				$cells[] = 'center='.$row[0];
				$cells[] = 'center='.($row[1]?Skin::build_date($row[1]):'--');
				$cells[] = 'center='.($row[2]?Skin::build_date($row[2]):'--');
				$text .= Skin::table_row($cells, $lines++);
			} else
				$text .= Skin::table_row(array(SQL::table_name('issues'), i18n::s('unknown or empty table'), ' ', ' '), $lines++);

			// links
			if($row = SQL::table_stat('links')) {
				$cells = array();
				$cells[] = Skin::build_link('links/', SQL::table_name('links'), 'basic');
				$cells[] = 'center='.$row[0];
				$cells[] = 'center='.($row[1]?Skin::build_date($row[1]):'--');
				$cells[] = 'center='.($row[2]?Skin::build_date($row[2]):'--');
				$text .= Skin::table_row($cells, $lines++);
			} else
				$text .= Skin::table_row(array(SQL::table_name('links'), i18n::s('unknown or empty table'), ' ', ' '), $lines++);

			// locations
			if($row = SQL::table_stat('locations')) {
				$cells = array();
				$cells[] = Skin::build_link('locations/', SQL::table_name('locations'), 'basic');
				$cells[] = 'center='.$row[0];
				$cells[] = 'center='.($row[1]?Skin::build_date($row[1]):'--');
				$cells[] = 'center='.($row[2]?Skin::build_date($row[2]):'--');
				$text .= Skin::table_row($cells, $lines++);
			} else
				$text .= Skin::table_row(array(SQL::table_name('locations'), i18n::s('unknown or empty table'), ' ', ' '), $lines++);

			// members
			if($row = SQL::table_stat('members')) {
				$cells = array();
				$cells[] = SQL::table_name('members');
				$cells[] = 'center='.$row[0];
				$cells[] = 'center='.($row[1]?Skin::build_date($row[1]):'--');
				$cells[] = 'center='.($row[2]?Skin::build_date($row[2]):'--');
				$text .= Skin::table_row($cells, $lines++);
			} else
				$text .= Skin::table_row(array(SQL::table_name('members'), i18n::s('unknown or empty table'), ' ', ' '), $lines++);

			// messages
			if($row = SQL::table_stat('messages')) {
				$cells = array();
				$cells[] = SQL::table_name('messages');
				$cells[] = 'center='.$row[0];
				$cells[] = 'center='.($row[1]?Skin::build_date($row[1]):'--');
				$cells[] = 'center='.($row[2]?Skin::build_date($row[2]):'--');
				$text .= Skin::table_row($cells, $lines++);
			} else
				$text .= Skin::table_row(array(SQL::table_name('messages'), i18n::s('unknown or empty table'), ' ', ' '), $lines++);

			// notifications
			if($row = SQL::table_stat('notifications')) {
				$cells = array();
				$cells[] = SQL::table_name('notifications');
				$cells[] = 'center='.$row[0];
				$cells[] = 'center='.($row[1]?Skin::build_date($row[1]):'--');
				$cells[] = 'center='.($row[2]?Skin::build_date($row[2]):'--');
				$text .= Skin::table_row($cells, $lines++);
			} else
				$text .= Skin::table_row(array(SQL::table_name('notifications'), i18n::s('unknown or empty table'), ' ', ' '), $lines++);

			// the php documentation
			if($row = SQL::table_stat('phpdoc')) {
				$cells = array();
				$cells[] = Skin::build_link('scripts/', SQL::table_name('phpdoc'), 'basic');
				$cells[] = 'center='.$row[0];
				$cells[] = 'center='.($row[1]?Skin::build_date($row[1]):'--');
				$cells[] = 'center='.($row[2]?Skin::build_date($row[2]):'--');
				$text .= Skin::table_row($cells, $lines++);
			} else
				$text .= Skin::table_row(array(SQL::table_name('phpdoc'), i18n::s('unknown or empty table'), ' ', ' '), $lines++);

			// profiles
			include_once '../agents/profiles.php';
			if($stats = Profiles::stat()) {
				$cells = array();
				$cells[] = SQL::table_name('profiles');
				$cells[] = 'center='.$stats['count'];
				$cells[] = 'center=--';
				$cells[] = 'center=--';
				$text .= Skin::table_row($cells, $lines++);
			} else
				$text .= Skin::table_row(array(SQL::table_name('profiles'), i18n::s('unknown or empty table'), ' ', ' '), $lines++);

			// referrals
			include_once '../agents/referrals.php';
			if($stats = Referrals::stat()) {
				$cells = array();
				$cells[] = SQL::table_name('referrals');
				$cells[] = 'center='.$stats['count'];
				$cells[] = 'center=--';
				$cells[] = 'center=--';
				$text .= Skin::table_row($cells, $lines++);
			} else
				$text .= Skin::table_row(array(SQL::table_name('referrals'), i18n::s('unknown or empty table'), ' ', ' '), $lines++);

			// sections
			if($row = SQL::table_stat('sections')) {
				$cells = array();
				$cells[] = Skin::build_link('sections/', SQL::table_name('sections'), 'basic');
				$cells[] = 'center='.$row[0];
				$cells[] = 'center='.($row[1]?Skin::build_date($row[1]):'--');
				$cells[] = 'center='.($row[2]?Skin::build_date($row[2]):'--');
				$text .= Skin::table_row($cells, $lines++);
			} else
				$text .= Skin::table_row(array(SQL::table_name('sections'), i18n::s('unknown or empty table'), ' ', ' '), $lines++);

			// servers
			if($row = SQL::table_stat('servers')) {
				$cells = array();
				$cells[] = Skin::build_link('servers/', SQL::table_name('servers'), 'basic');
				$cells[] = 'center='.$row[0];
				$cells[] = 'center='.($row[1]?Skin::build_date($row[1]):'--');
				$cells[] = 'center='.($row[2]?Skin::build_date($row[2]):'--');
				$text .= Skin::table_row($cells, $lines++);
			} else
				$text .= Skin::table_row(array(SQL::table_name('servers'), i18n::s('unknown or empty table'), ' ', ' '), $lines++);

			// tables
			if($row = SQL::table_stat('tables')) {
				$cells = array();
				$cells[] = Skin::build_link('tables/', SQL::table_name('tables'), 'basic');
				$cells[] = 'center='.$row[0];
				$cells[] = 'center='.($row[1]?Skin::build_date($row[1]):'--');
				$cells[] = 'center='.($row[2]?Skin::build_date($row[2]):'--');
				$text .= Skin::table_row($cells, $lines++);
			} else
				$text .= Skin::table_row(array(SQL::table_name('tables'), i18n::s('unknown or empty table'), ' ', ' '), $lines++);

			// users
			if($row = SQL::table_stat('users')) {
				$cells = array();
				$cells[] = Skin::build_link('users/', SQL::table_name('users'), 'basic');
				$cells[] = 'center='.$row[0];
				$cells[] = 'center='.($row[1]?Skin::build_date($row[1]):'--');
				$cells[] = 'center='.($row[2]?Skin::build_date($row[2]):'--');
				$text .= Skin::table_row($cells, $lines++);
			} else
				$text .= Skin::table_row(array(SQL::table_name('users'), i18n::s('unknown or empty table'), ' ', ' '), $lines++);

			// values
			if($row = SQL::table_stat('values')) {
				$cells = array();
				$cells[] = SQL::table_name('values');
				$cells[] = 'center='.$row[0];
				$cells[] = 'center='.($row[1]?Skin::build_date($row[1]):'--');
				$cells[] = 'center='.($row[2]?Skin::build_date($row[2]):'--');
				$text .= Skin::table_row($cells, $lines++);
			} else
				$text .= Skin::table_row(array(SQL::table_name('values'), i18n::s('unknown or empty table'), ' ', ' '), $lines++);

			// versions
			if($row = SQL::table_stat('versions')) {
				$cells = array();
				$cells[] = SQL::table_name('versions');
				$cells[] = 'center='.$row[0];
				$cells[] = 'center='.($row[1]?Skin::build_date($row[1]):'--');
				$cells[] = 'center='.($row[2]?Skin::build_date($row[2]):'--');
				$text .= Skin::table_row($cells, $lines++);
			} else
				$text .= Skin::table_row(array(SQL::table_name('versions'), i18n::s('unknown or empty table'), ' ', ' '), $lines++);

			// visits
			if($row = SQL::table_stat('visits')) {
				$cells = array();
				$cells[] = SQL::table_name('visits');
				$cells[] = 'center='.$row[0];
				$cells[] = 'center='.($row[1]?Skin::build_date($row[1]):'--');
				$cells[] = 'center='.($row[2]?Skin::build_date($row[2]):'--');
				$text .= Skin::table_row($cells, $lines++);
			} else
				$text .= Skin::table_row(array(SQL::table_name('visits'), i18n::s('unknown or empty table'), ' ', ' '), $lines++);

			// end of the table
			$text .= Skin::table_suffix();

			// total size of the database
			$query = "SHOW TABLE STATUS";
			if(!$result = SQL::query($query)) {
				$context['text'] .= Logger::error_pop().BR."\n";
			} else {

				// consolidate numbers
				$total_tables = 0;
				$total_records = 0;
				$total_size = 0;
				$data_size = 0;
				$index_size = 0;
				$unused_size = 0;
				while($row = SQL::fetch($result)) {
					$total_tables += 1;
					$total_records += $row['Rows'];
					$total_size += $row['Data_length'] + $row['Index_length'] + $row['Data_free'];
					$data_size += $row['Data_length'];
					$index_size += $row['Index_length'];
					$unused_size += $row['Data_free'];
				}

				// turn big numbers to human-readable format
				function get_size($size){
					if($size < 1024)
						return $size.' ';

					$labels = array('', 'K', 'M', 'G', 'T');
					foreach($labels as $label) {
						if($size < 1024)
							break;
						$size = $size / 1024;
					}
					return round($size, 2).' '.$label;
				}

				// overall size of the database
				$text .= '<p>'.sprintf(i18n::s('%d tables and %d records in %sbytes'), $total_tables, $total_records, get_size($total_size))
					.BR.sprintf(i18n::s('(%sbytes data, %sbytes index, %sbytes unused)'), get_size($data_size), get_size($index_size), get_size($unused_size))."</p>\n";
			}

			// build another tab
			$all_tabs = array_merge($all_tabs, array(array('overview', i18n::s('Overview'), 'overview_panel', $text)));

			//
			// Content Management tab
			//
			$text = '';

			// surfer is not authenticated
			if(!Surfer::is_logged()) {

				$content = '<li>'.sprintf(i18n::s('Please %s to benefit from all contribution tools provided by this site.'), Skin::build_link('users/login.php', i18n::s('authenticate'), 'basic')).'</li>';

				// offer a self-registration, if allowed
				if(!isset($context['users_without_registration']) || ($context['users_without_registration'] != 'Y'))
					$content .= '<li>'.sprintf(i18n::s('%s if you have not yet a profile for yourself at %s.'), Skin::build_link('users/edit.php', i18n::s('Click here to register'), 'basic'), $context['site_name'])."</li>\n";

				// insert commands
				$text .= Skin::build_box(i18n::s('Express yourself'), '<ul>'.$content.'</ul>', 'header1', 'express_yourself');

			}

			// available commands
			$commands = array();

			// review queue
			if(Surfer::is_associate())
				$commands[] = sprintf(i18n::s('%s - articles waiting for publication, pending requests'), Skin::build_link('articles/review.php', i18n::s('Review queue'), 'basic'));

			// letters
			if(Surfer::is_associate() && isset($context['with_email']) && ($context['with_email'] == 'Y'))
				$commands[] = sprintf(i18n::s('%s - broadcast digests and announcements by e-mail'), Skin::build_link('letters/', i18n::s('Newsletters'), 'basic'));

			// create a page
			if(Surfer::is_associate()
				|| (Surfer::is_member() && (!isset($context['users_without_submission']) || ($context['users_without_submission'] != 'Y'))) ) {

				$commands[] = sprintf(i18n::s('%s - select a section and type some text, then add images, files and links'), Skin::build_link('articles/edit.php', i18n::s('Add a page'), 'basic'));

			}

			// content assistant
			if(Surfer::is_associate())
				$commands[] = sprintf(i18n::s('%s - create blogs, wikis, forums, and more'), Skin::build_link('help/populate.php', i18n::s('Content Assistant'), 'basic'));

			// site map
			if(Surfer::is_associate())
				$commands[] = sprintf(i18n::s('%s - you may enhance %s of some sections'),
					Skin::build_link('sections/', i18n::s('Site map')),
					'<a href="../behaviors/">'.i18n::s('behaviors').'</a>');

			// template pages
			if(Surfer::is_associate())
				$commands[] = sprintf(i18n::s('%s - some have %s'),
					Skin::build_link(Sections::get_url('templates'), i18n::s('Template pages')),
					'<a href="../overlays/">'.i18n::s('overlays').'</a>');

			// global pages
			if(Surfer::is_associate())
				$commands[] = sprintf(i18n::s('%s, including: %s, %s, %s and %s'),
					Skin::build_link(Sections::get_url('global'), i18n::s('Global pages')),
					Skin::build_link(Articles::get_url('cover'), i18n::s('the cover page')),
					Skin::build_link(Articles::get_url('menu'), i18n::s('Menu')),
					Skin::build_link(Articles::get_url('about'), i18n::s('About this site')),
					Skin::build_link(Articles::get_url('privacy'), i18n::s('Privacy statement')));

			// import some content
			if(Surfer::is_associate())
				$commands[] = sprintf(i18n::s('%s - exported from another server'), Skin::build_link('control/import.php', i18n::s('Import XML content'), 'basic'));

			// upload some CSV
			if(Surfer::is_associate())
				$commands[] = sprintf(i18n::s('%s - update the database'), Skin::build_link('tables/import.php', i18n::s('Upload a .CSV file'), 'basic'));

			// available feeds
			$commands[] = sprintf(i18n::s('%s - all feeds available (RSS, ATOM)'), Skin::build_link('feeds/', i18n::s('Information channels'), 'basic'));

			// web services
			$commands[] = sprintf(i18n::s('%s - connect remote computers through XML-RPC or REST'), Skin::build_link('services/', i18n::s('Web services'), 'basic'));

			// codes
			$commands[] = sprintf(i18n::s('%s - codes you can use to beautify your pages'), Skin::build_link('codes/', i18n::s('Codes'), 'basic'));

			// smileys
			$commands[] = sprintf(i18n::s('%s - smileys available for your posts'), Skin::build_link('smileys/', i18n::s('Smileys'), 'basic'));

			// avatars
			$commands[] = sprintf(i18n::s('%s - some pictures you may choose for your profile'), Skin::build_link('skins/_reference/avatars/', i18n::s('Avatars'), 'basic'));

			// usage information
			if(Surfer::is_associate())
				$commands[] = sprintf(i18n::s('%s - learn about your visitors'), Skin::build_link('agents/', i18n::s('Background processing'), 'basic'));

			// insert commands
			$text .= Skin::build_box(i18n::s('Content management'), '<ul><li>'.join('</li><li>', $commands).'</li></ul>', 'header1', 'content_management');

			// members can use additional tools
			if(Surfer::is_member()) {

				// introduce bookmarklets
				$box = '<p>'.i18n::s('To install following bookmarklets, right-click over them and add them to your bookmarks or favorites. Then recall them at any time while browsing the Internet, to add content to this site.').'</p>'."\n".'<ul>';

				// the blogging bookmarklet uses YACS codes
				$bookmarklet = "javascript:function findFrame(f){var i;try{isThere=f.document.selection.createRange().text;}catch(e){isThere='';}if(isThere==''){for(i=0;i&lt;f.frames.length;i++){findFrame(f.frames[i]);}}else{s=isThere}return s}"
					."var s='';"
					."d=document;"
					."s=d.selection?findFrame(window):window.getSelection();"
					."window.location='".$context['url_to_home'].$context['url_to_root']."articles/edit.php?"
						."title='+escape(d.title)+'"
						."&amp;text='+escape('%22'+s+'%22%5Bnl]-- %5Blink='+d.title+']'+d.location+'%5B/link]')+'"
						."&amp;source='+escape(d.location);";
				$box .= '<li><a href="'.$bookmarklet.'">'.sprintf(i18n::s('Blog at %s'), $context['site_name']).'</a></li>'."\n";

				// the bookmarking bookmarklet
				$bookmarklet = "javascript:function findFrame(f){var i;try{isThere=f.document.selection.createRange().text;}catch(e){isThere='';}if(isThere==''){for(i=0;i&lt;f.frames.length;i++){findFrame(f.frames[i]);}}else{s=isThere}return s}"
					."var s='';"
					."d=document;"
					."s=d.selection?findFrame(window):window.getSelection();"
					."window.location='".$context['url_to_home'].$context['url_to_root']."links/edit.php?"
						."link='+escape(d.location)+'"
						."&amp;title='+escape(d.title)+'"
						."&amp;text='+escape(s);";
				$box .= '<li><a href="'.$bookmarklet.'">'.sprintf(i18n::s('Bookmark at %s'), $context['site_name']).'</a></li>'."\n";

				// end of bookmarklets
				$box .= '</ul>'."\n";

				// the command to add a side panel
				$box .= '<p>'.sprintf(i18n::s('If your browser supports side panels and javascript, click on the following link to %s.'), '<a onclick="addSidePanel()">'.i18n::s('add a blogging panel').'</a>.').'</p>'."\n";

				// the actual javascript code to add a panel
				$context['page_footer'] .= JS_PREFIX
					.'// add a side panel to the current browser instance'."\n"
					.'function addSidePanel() {'."\n"
					.'	// a gecko-based browser: netscape, mozilla, firefox'."\n"
					.'	if((typeof window.sidebar == "object") && (typeof window.sidebar.addPanel == "function")) {'."\n"
					.'		window.sidebar.addPanel("'.strip_tags($context['site_name']).'", "'.$context['url_to_home'].$context['url_to_root'].'panel.php", "");'."\n"
					.'		alert("'.i18n::s('The panel has been added. You may have to ask your browser to make it visible (Ctrl-B for Firefox).').'");'."\n"
					.'	} else {'."\n"
					.'		// internet explorer'."\n"
					.'		if(document.all) {'."\n"
					.'			window.open("'.$context['url_to_home'].$context['url_to_root'].'panel.php?target=_main" ,"_search");'."\n"
					.'		// side panels are not supported'."\n"
					.'		} else {'."\n"
					.'			var rv = alert("'.i18n::s('Your browser does not support side panels. Have you considered to upgrade to Mozilla Firefox?').'");'."\n"
					.'			if(rv)'."\n"
					.'				document.location.href = "http://www.mozilla.org/products/firefox/";'."\n"
					.'		}'."\n"
					.'	}'."\n"
					.'}'."\n"
					.JS_SUFFIX;

				// make a nice box out of it
				$text .= Skin::build_box(i18n::s('Blogging tools'), $box);
			}

			// build another tab
			$all_tabs = array_merge($all_tabs, array(array('content', i18n::s('Content'), 'content_panel', $text)));

			//
			// System Management tab
			//
			$text = '';

			// display a system overview if not a crawler
			if(!Surfer::is_crawler()) {

				// use a neat table for the layout
				$box = Skin::table_prefix('wide');
				$lines = 1;

				// yacs version
				if(!isset($generation['version']))
					Safe::load('footprints.php');						// initial archive, or current version
				$cells = array();
				$cells[] = Skin::build_link('http://www.yacs.fr/', 'YACS');
				if(isset($generation['version']))
					$cells[] = $generation['version'].', '.$generation['date'].', '.$generation['server'];
				else
					$cells[] = '---';
				$box .= Skin::table_row($cells, $lines++);

				// php version
				$cells = array();
				$cells[] = Skin::build_link('http://www.php.net/', 'PHP');
				$cells[] = phpversion();
				$box .= Skin::table_row($cells, $lines++);

				// MySQL version
				if($version = SQL::version()) {
					$cells = array();
					$cells[] = Skin::build_link('http://www.mysql.com/', 'MySQL');
					$cells[] = $version;
					$box .= Skin::table_row($cells, $lines++);
				}

				// Apache version
				if(is_callable('apache_get_version')) {
					$cells = array();
					$cells[] = Skin::build_link('http://www.apache.org/', 'Apache');
					$cells[] = apache_get_version();
					$box .= Skin::table_row($cells, $lines++);
				}

				// time shift
				$cells = array();
				$cells[] = i18n::s('Server time zone');
				$cells[] = sprintf('UTC %s%s %s', ($context['gmt_offset'] > 0)?'+':'', $context['gmt_offset'], i18n::ns('hour', 'hours', abs($context['gmt_offset'])));
				$box .= Skin::table_row($cells, $lines++);

				// memory usage
				if(is_callable('memory_get_usage')) {
					$cells = array();
					$cells[] = i18n::s('Memory');
					$cells[] = memory_get_usage();
					$box .= Skin::table_row($cells, $lines++);
				}

				// end of the table
				$box .= Skin::table_suffix();

				// make a nice box out of it
				$text .= Skin::build_box(i18n::s('System overview'), $box);
			}

			// available commands for system management
			$commands = array();

			$commands[] = sprintf(i18n::s('%s - basic view of the system'), '<a href="test.php">'.i18n::s('Test').'</a>');

			// more information to associates
			if(Surfer::is_associate()) {
				$commands[] = sprintf(i18n::s('%s - phpinfo() and more'), '<a href="info.php">'.i18n::s('Run-time information').'</a>');
				$commands[] = sprintf(i18n::s('%s - operation summary'), '<a href="../agents/">'.i18n::s('Background processing').'</a>');
			}

			// commands for associates -- complex command
			if(Surfer::is_associate() && Surfer::has_all()) {

				$commands[] = sprintf(i18n::s('%s - the safety tool; also useful to submit bulk SQL statements'), '<a href="backup.php">'.i18n::s('Backup/Restore').'</a>');

				$commands[] = sprintf(i18n::s('%s - check the database structure; also optimize data tables and update index'), '<a href="setup.php">'.i18n::s('Database maintenance').'</a>');

				$commands[] = sprintf(i18n::s('%s - clear the cache or delete what can be safely deleted'), '<a href="purge.php">'.i18n::s('Purge').'</a>');

				$commands[] = sprintf(i18n::s('%s - shut the server down, or fire it up again'), '<a href="switch.php">'.i18n::s('Switch').'</a>');

				// signal scripts to run once, if any
				if(Safe::glob($context['path_to_root'].'scripts/run_once/*.php') !== FALSE) {
					$commands[] = sprintf(i18n::s('%s - some upgrades are waiting for execution'), '<a href="../scripts/run_once.php">'.i18n::s('Run once').'</a>');
				}

				$commands[] = sprintf(i18n::s('%s - server software'), '<a href="../scripts/">Yacs</a>');

				$commands[] = sprintf(i18n::s('%s - plugins and other pieces of software hooked into yacs'), '<a href="scan.php">'.i18n::s('Extensions').'</a>');

				$commands[] = sprintf(i18n::s('%s - change permissions of files'), '<a href="chmod.php">'.i18n::s('Permissions').'</a>');

				$commands[] = sprintf(i18n::s('%s - other commands'), '<a href="../tools/">'.i18n::s('Tools').'</a>');

			}

			// insert commands
			if(count($commands))
				$text .= Skin::build_box(i18n::s('System management'), '<ul><li>'.join('</li><li>', $commands).'</li></ul>', 'header1', 'system_management');

			// build another tab
			if($text)
				$all_tabs = array_merge($all_tabs, array(array('system', i18n::s('System'), 'system_panel', $text)));

			//
			// show all tabs
			//

			// let YACS do the hard job
			$context['text'] .= Skin::build_tabs($all_tabs);

			//
			// extra boxes
			//

			$links = array();
			$links[] = Skin::build_link('sections/', i18n::s('Site map'), 'shortcut');
			$links[] = Skin::build_link('categories/', i18n::s('Categories'), 'shortcut');
			$links[] = Skin::build_link('users/', i18n::s('People'), 'shortcut');

			// the hook for more tools
			if(is_callable(array('Hooks', 'link_scripts')) && ($more_links = Hooks::link_scripts('control/index.php#tools', 'array')))
				$links = array_merge($links, $more_links);

			$links[] = Skin::build_link('help/', i18n::s('Help'), 'shortcut');

			// list modules in an extra box
			$context['components']['boxes'] = Skin::build_box(i18n::s('See also'), Skin::finalize_list($links, 'compact'), 'boxes', 'tools_box');

			// list modules if a skin has been defined -- complex command
			if(class_exists('Skin') && Surfer::has_all()) {
				$links = array();

				if(Surfer::is_associate())
					$links[] = Skin::build_link('agents/', 'agents', 'shortcut');
				$links[] = Skin::build_link('articles/', 'articles', 'shortcut');
				if(Surfer::is_associate())
					$links[] = Skin::build_link('behaviors/', 'behaviors', 'shortcut');
				$links[] = Skin::build_link('categories/', 'categories', 'shortcut');
				$links[] = Skin::build_link('codes/', 'codes', 'shortcut');
				if(Surfer::is_associate())
					$links[] = Skin::build_link('comments/', 'comments', 'shortcut');
				$links[] = Skin::build_link('dates/', 'dates', 'shortcut');
				$links[] = Skin::build_link('feeds/', 'feeds', 'shortcut');
				$links[] = Skin::build_link('files/', 'files', 'shortcut');
				if(Surfer::is_associate())
					$links[] = Skin::build_link('images/', 'images', 'shortcut');
				$links[] = Skin::build_link('letters/', 'letters', 'shortcut');
				$links[] = Skin::build_link('links/', 'links', 'shortcut');
				if(Surfer::is_associate())
					$links[] = Skin::build_link('locations/', 'locations', 'shortcut');
				if(Surfer::is_associate())
					$links[] = Skin::build_link('overlays/', 'overlays', 'shortcut');
				$links[] = Skin::build_link('scripts/', 'scripts', 'shortcut');
				$links[] = Skin::build_link('sections/', 'sections', 'shortcut');
				$links[] = Skin::build_link('servers/', 'servers', 'shortcut');
				$links[] = Skin::build_link('services/', 'services', 'shortcut');
				$links[] = Skin::build_link('skins/', 'skins', 'shortcut');
				$links[] = Skin::build_link('smileys/', 'smileys', 'shortcut');
				if(Surfer::is_associate())
					$links[] = Skin::build_link('tables/', 'tables', 'shortcut');
				if(Surfer::is_associate())
					$links[] = Skin::build_link('tools/', 'tools', 'shortcut');
				$links[] = Skin::build_link('users/', 'users', 'shortcut');

				// the hook for more modules
				if(is_callable(array('Hooks', 'link_scripts')) && ($more_links = Hooks::link_scripts('control/index.php#modules', 'array')))
					$links = array_merge($links, $more_links);

				// list modules in an extra box
				$context['components']['boxes'] .= Skin::build_box(i18n::s('Modules'), Skin::finalize_list($links, 'compact'), 'boxes');

			}

		}
	}
}

// render the skin
render_skin();

?>
