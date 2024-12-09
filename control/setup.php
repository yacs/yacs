<?php
/**
 * adjust the database schema and optimize data
 *
 * This script will consider each table that has to be present in the database.
 * If the table does not exist, it will be created.
 * Else columns are checked one by one, and altered if necessary.
 * All indexes are rebuilt.
 *
 * The structure of each table in described into related database abstractions.
 * Look for the [code]setup()[/code] function in following scripts:
 * - [script]agents/profiles.php[/script]
 * - [script]agents/referrals.php[/script]
 * - [script]articles/articles.php[/script]
 * - [script]categories/categories.php[/script]
 * - [script]comments/comments.php[/script]
 * - [script]dates/dates.php[/script]
 * - [script]files/files.php[/script]
 * - [script]images/images.php[/script]
 * - [script]links/links.php[/script]
 * - [script]locations/locations.php[/script]
 * - [script]scripts/phpdoc.php[/script]
 * - [script]sections/sections.php[/script]
 * - [script]servers/servers.php[/script]
 * - [script]shared/cache.php[/script]
 * - [script]shared/enrolments.php[/script]
 * - [script]shared/mailer.php[/script]
 * - [script]shared/members.php[/script]
 * - [script]shared/values.php[/script]
 * - [script]tables/tables.php[/script]
 * - [script]users/users.php[/script]
 * - [script]users/notifications.php[/script]
 * - [script]users/visits.php[/script]
 * - [script]versions/versions.php[/script]
 *
 * Also, additional database abstractions can be taken into account through the following hook:
 * - id: 'control/setup.php'
 * - type: 'include'
 *
 * @see control/scan.php
 *
 * The integrated search engine is based on full-text indexing capabilities of MySQL.
 * Was previously only available for the [code]MyISAM[/code] table type.
 *
 * @link http://dev.mysql.com/doc/mysql/en/Fulltext_Search.html MySQL Manual | 12.6 Full-Text Search Functions
 *
 * This script will also analyze and optimize tables, and can be safely launched periodically
 * to enhance response times, to reduce the size of data files and to rebuild indexes.
 *
 * The MySQL ANALYZE statement analyzes and stores the key distribution for a table.
 * MySQL uses the stored key distribution to decide the order in which tables should
 * be joined when you perform a join on something other than a constant.
 *
 * @link http://dev.mysql.com/doc/mysql/en/ANALYZE_TABLE.html ANALYZE TABLE Syntax
 *
 * The MySQL OPTIMIZE command works as follows:
 * - If the table has deleted or split rows, repair the table.
 * - If the index pages are not sorted, sort them.
 * - If the statistics are not up to date (and the repair couldn't be done by sorting the index), update them.
 *
 * @link http://dev.mysql.com/doc/mysql/en/OPTIMIZE_TABLE.html OPTIMIZE TABLE Syntax
 *
 * Access to this script is restricted to associates, except if no user profile exists in the database.
 * In this case, which is likely to happen on first installation, the current surfer is flagged as being an associate.
 *
 * Also, this script executes normally even in demonstration mode.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester James Wharris
 * @tester Lilou
 * @tester Anatoly
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
*/

// include the global declarations
include_once '../shared/global.php';

// what to do
$action = '';
if(!file_exists('../parameters/switch.on') && !file_exists('../parameters/switch.off'))
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

// do not index this page
$context->sif('robots','noindex');

// the path to this page
$context['path_bar'] = array( 'control/' => i18n::s('Control Panel') );

// the title of the page
$context['page_title'] = i18n::s('Database maintenance');

/**
 * dynamically generate the page
 *
 * @see skins/index.php
 */
function send_body() {
	global $context, $action;

	// check that the user is an admin, but only if there is at least one user record
	$query = "SELECT count(*) FROM ".SQL::table_name('users');
	if(!Surfer::is_associate() && (SQL::query($query) !== FALSE)) {
		Safe::header('Status: 401 Unauthorized', TRUE, 401);
		echo '<p>'.i18n::s('You are not allowed to perform this operation.')."</p>\n";
		return;
	}

	// log the current surfer as an associate if not yet the case
	if(!Surfer::is_associate()) {
		$fields = array();
		$fields['id'] = 1;
		$fields['nick_name'] = 'admin';
		$fields['email'] = '';
		$fields['capability'] = 'A';
		Surfer::set($fields);
		echo '<p>'.i18n::s('You have associate privilege').'</p>';
	}

	// check every table of the database
	if($action == 'build') {

		// maybe we will have to switch the server off
		$temporary_off = FALSE;

		// ensure nobody else will access the database during the operation
		if(file_exists('../parameters/switch.on')) {
			if(Safe::rename($context['path_to_root'].'parameters/switch.on', $context['path_to_root'].'parameters/switch.off')) {
				echo BR.i18n::s('The server has been switched off.');

				$temporary_off = TRUE;
			}

			// let concurrent on-going transactions finish properly
			Safe::sleep(3);

		// first installation
		} elseif(!file_exists('../parameters/switch.off'))
			echo '<p>'.i18n::s('Review provided information and go to the bottom of the page to move forward.')."</a></p>\n";

		// ensure utf8 character set for this database
		$query = "ALTER DATABASE `".$context['database']."`  DEFAULT CHARACTER SET utf8";
		SQL::query($query);

		// create tables for users
		echo Users::setup();

		// create tables for activities
		echo Activities::setup();

		// create tables for notifications
		include_once '../users/notifications.php';
		echo Notifications::setup();

		// create tables for messages
		echo Mailer::setup();

		// create tables for visits
		include_once '../users/visits.php';
		echo Visits::setup();

		// create tables for sections
		echo Sections::setup();

		// create tables for articles
		echo Articles::setup();

		// create tables for images
		include_once '../images/images.php';
		echo Images::setup();

		// create tables for tables
		include_once '../tables/tables.php';
		echo Tables::setup();

		// create tables for files
		echo Files::setup();

		// create tables for links
		include_once '../links/links.php';
		echo Links::setup();

		// create tables for locations
		include_once '../locations/locations.php';
		echo Locations::setup();

		// create tables for comments
		include_once '../comments/comments.php';
		echo Comments::setup();

		// create tables for categories
		echo Categories::setup();

		// create tables for members
		include_once '../shared/members.php';
		echo Members::setup();

		// create tables for dates
		include_once '../dates/dates.php';
		echo Dates::setup();

		// create tables for servers
		include_once '../servers/servers.php';
		echo Servers::setup();

		// create tables for versions
		include_once '../versions/versions.php';
		echo Versions::setup();

		// create tables for enrolments
		include_once '../shared/enrolments.php';
		echo Enrolments::setup();

		// create tables for values
		include_once '../shared/values.php';
		echo Values::setup();

		// create tables for the cache
		echo Cache::setup();

		// create tables for the php documentation
		include_once '../scripts/phpdoc.php';
		echo PhpDoc::setup();

		// the setup hook
		if(is_callable(array('Hooks', 'include_scripts')))
			echo Hooks::include_scripts('control/setup.php');

		// reopen the server for others
		if($temporary_off && Safe::rename($context['path_to_root'].'parameters/switch.off', $context['path_to_root'].'parameters/switch.on'))
			echo '<p>'.i18n::s('The server has been switched on.').'</p>';

		// in the middle of an update
		if(file_exists('../parameters/switch.off')) {
			echo Skin::build_block('<form method="get" action="../scripts/run_once.php">'."\n"
				.'<p class="assistant_bar">'.Skin::build_submit_button(i18n::s('Run one-time scripts and go to the Control Panel')).'</p>'."\n"
				.'</form>', 'bottom');

			// this may take several minutes
			echo '<p>'.i18n::s('When you will click on the button the server will be immediately requested to proceed. However, because of the so many things to do on the back-end, you may have to wait for minutes before getting a response displayed. Thank you for your patience.').'</p>';

		// populate the database on first installation
		} elseif(!file_exists('../parameters/switch.on')) {
			echo Skin::build_block('<form method="get" action="populate.php">'."\n"
				.'<p class="assistant_bar">'.Skin::build_submit_button(i18n::s('Initialize the database')).'</p>'."\n"
				.'</form>', 'bottom');

		// or back to the control panel
		} else {
			$menu = array('control/' => i18n::s('Control Panel'));
			echo Skin::build_list($menu, 'menu_bar');
		}

		// clear the cache
		Cache::clear();

		// remember the change
		$label = i18n::c('The database has been optimised');
		Logger::remember('control/setup.php: '.$label);

	// ask for confirmation
	} else {

		// the splash message
		echo '<p>'.i18n::s('This script will check the structure of the database and optimize data storage:').'</p>'."\n"
			.'<ul>'."\n"
			.'<li>'.i18n::s('Missing tables will be created, if necessary.').'</li>'."\n"
			.'<li>'.i18n::s('Some columns may be created or converted if their type has evolved.').'</li>'."\n"
			.'<li>'.i18n::s('All indexes will be (re)built.').'</li>'."\n"
			.'<li>'.i18n::s('Data files will be optimized as well.').'</li>'."\n"
			.'</ul>'."\n";

		// the submit button
		echo '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'
			.Skin::build_submit_button(i18n::s('Ensure the database structure is accurate'), NULL, NULL, 'confirmed')
			.'<input type="hidden" name="action" value="build" />'
			.'</p></form>';

		// the script used for form handling at the browser
		Page::insert_script('$("#confirmed").focus();');

		// this may take several minutes
		echo '<p>'.i18n::s('When you will click on the button the server will be immediately requested to proceed. However, because of the so many things to do on the back-end, you may have to wait for minutes before getting a response displayed. Thank you for your patience.').'</p>';

	}

}

// render the skin
render_skin();