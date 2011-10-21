<?php
/**
 * build content dashboard
 *
 * This script derives various tables from actual content of the database, that will be useful
 * to produce dashboards and to motivate management decisions:
 *
 * - yacs_stat_sections - total number of items in second-level sections (sub-sections, articles, files, comments, links)
 * - yacs_stat_sections_2011 - number of items in second-level sections (sub-sections, articles, files, comments, links) from current year
 * - yacs_stat_users - total contributions from most active users (sub-sections, articles, files, comments, links)
 * - yacs_stat_users_2011 - contributions from most active users (sub-sections, articles, files, comments, links) in current year
 * - yacs_top_editors - list of people who are managing the highest number of articles
 * - yacs_top_uploaders - list of people who have uploaded many files
 *
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
if(!is_readable('../shared/global.php'))
	exit('The file shared/global.php has not been found. Please reinstall or configure the YACS_HOME environment variable.');

// common definitions and initial processing
include_once '../shared/global.php';
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

// load the skin
load_skin('tools');

// launched from the command line
if(!isset($_SERVER['REMOTE_ADDR'])) {

	// a fake associate
	$user = array();
	$user['id'] = 1;
	$user['nick_name'] = 'cron';
	$user['email'] = '';
	$user['capability'] = 'A';
	Surfer::set($user);
}

// screen access to this script
if(!Surfer::is_associate()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	echo 'You are not allowed to perform this operation.';

// do nothing on HEAD request --see scripts/validate.php
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD')) {
	;

// if interactive, ask for confirmation
} elseif(isset($_SERVER['REMOTE_ADDR']) && !isset($_REQUEST['action'])) {

	// the splash message
	echo 'This script will compute statistics from actual database content.';

	// the submit button
	echo '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'
		.Skin::build_submit_button('Compute statistics', NULL, NULL, 'confirmed')
		.'<input type="hidden" name="action" value="compute" />'
		.'</p></form>';

	// this may take several minutes
	echo '<p>'.'When you will click on the button the server will be immediately requested to proceed. However, because of the so many things to do on the back-end, you may have to wait for minutes before getting a response displayed. Thank you for your patience.'."</p>\n";

// do the job
} else {

	// job is starting there
	echo 'Building the dashboard...'.BR;

	// stating current year
	$current_year = intval(date('Y'));
	$all_years = array( $current_year, $current_year - 1, $current_year - 2, $current_year - 3 );

	// stat second-level sections
	//
	echo sprintf('Building table %s...', SQL::table_name('stat_sections')).BR;

	// drop the table first
	$query = "DROP TABLE IF EXISTS `".SQL::table_name('stat_sections')."`";
	SQL::query($query);

	// create table with the appropriate structure
	$query = "CREATE TABLE `".SQL::table_name('stat_sections')."` (\n"
		."parent_id TEXT DEFAULT '',\n"
		."edit_date TEXT DEFAULT '',\n"
		."section_id TEXT DEFAULT '',\n"
		."section_label TEXT DEFAULT '',\n"
		."active TEXT DEFAULT '',\n"
		."articles INT DEFAULT 0,\n"
		."articles_private INT DEFAULT 0,\n"
		."comments INT DEFAULT 0,\n"
		."comments_private INT DEFAULT 0,\n"
		."files INT DEFAULT 0,\n"
		."files_private INT DEFAULT 0,\n"
		."links INT DEFAULT 0,\n"
		."links_private INT DEFAULT 0,\n"
		."sections INT DEFAULT 0,\n"
		."sections_private INT DEFAULT 0,\n"
		."total INT DEFAULT 0, \n"
		."total_private INT DEFAULT 0)";
	SQL::query($query);

	// stat second-level sections since beginning of the year
	//
	echo sprintf('Building table %s...', SQL::table_name('stat_sections_'.$current_year)).BR;

	// drop the table first
	$query = "DROP TABLE IF EXISTS `".SQL::table_name('stat_sections_'.$current_year)."`";
	SQL::query($query);

	// create table with the appropriate structure
	$query = "CREATE TABLE `".SQL::table_name('stat_sections_'.$current_year)."` (\n"
		."parent_id TEXT DEFAULT '',\n"
		."edit_date TEXT DEFAULT '',\n"
		."section_id TEXT DEFAULT '',\n"
		."section_label TEXT DEFAULT '',\n"
		."active TEXT DEFAULT '',\n"
		."articles INT DEFAULT 0,\n"
		."articles_private INT DEFAULT 0,\n"
		."comments INT DEFAULT 0,\n"
		."comments_private INT DEFAULT 0,\n"
		."files INT DEFAULT 0,\n"
		."files_private INT DEFAULT 0,\n"
		."links INT DEFAULT 0,\n"
		."links_private INT DEFAULT 0,\n"
		."sections INT DEFAULT 0,\n"
		."sections_private INT DEFAULT 0,\n"
		."total INT DEFAULT 0, \n"
		."total_private INT DEFAULT 0)";
	SQL::query($query);

	// one record for each second-level section
	$query = "SELECT SUBSTRING_INDEX(anchor, ':', -1) as parent_id, edit_date, id AS section_id, title AS section_label, active FROM ".SQL::table_name('sections')." WHERE (anchor IN \n"
		."(SELECT CONCAT('section:', id) FROM ".SQL::table_name('sections')." WHERE (anchor < 's')))";

	if($sections =& SQL::query($query)) {

		$records = 0;
		while($item =& SQL::fetch($sections)) {

			echo '.';

			$total = 0;
			$total_y = 0;
			$total_private = 0;
			$total_private_y = 0;

			$fields = array();
			$fileds_y = array();
			foreach($item as $name => $value) {
				$fields[] = "`".$name."`='".SQL::escape($value)."'";
				$fields_y[] = "`".$name."`='".SQL::escape($value)."'";
			}

			// list children sections, as deep as necessary
			$anchors = array( 'section:'.$item['section_id'] );
			$anchors_private = array();
			$row = $anchors;
			while(count($row)) {

				$query = "SELECT id, active FROM ".SQL::table_name('sections')." WHERE anchor IN ('".implode("', '", $row)."')";
				$row = array();
				if($result =& SQL::query($query)) {

					while($sitem =& SQL::fetch($result)) {
						$row[] = 'section:'.$sitem['id'];
						$anchors[] = 'section:'.$sitem['id'];
						if($sitem['active'] == 'N')
							$anchors_private[] = 'section:'.$sitem['id'];
					}

				}

			}

			// count sub-sections
			$fields[] = "`sections`=".(count($anchors)-1);
			$fields_y[] = "`sections`=".(count($anchors)-1);
			$total += count($anchors)-1;
			$total_y += count($anchors)-1;
			$fields[] = "`sections_private`=".count($anchors_private);
			$fields_y[] = "`sections_private`=".count($anchors_private);
			$total_private += count($anchors_private);
			$total_private_y += count($anchors_private);

			// some elements could have been attached to this private section
			if($item['active'] == 'N')
				$anchors_private[] = 'section:'.$item['section_id'];

			// count private articles
			$query = "SELECT id FROM ".SQL::table_name('articles')." WHERE (anchor IN ('".implode("', '", $anchors)."')) AND (active = 'N')";
			if($count = SQL::query_count($query)) {
				$fields[] = "`articles_private`=".$count;
				$total_private += $count;
			}

			// count private articles edited this year
			$query = "SELECT id FROM ".SQL::table_name('articles')." WHERE (anchor IN ('".implode("', '", $anchors)."')) AND (active = 'N') AND (edit_date LIKE '".$current_year."%')";
			if($count = SQL::query_count($query)) {
				$fields_y[] = "`articles_private`=".$count;
				$total_private_y += $count;
			}

			// list articles anchored to these sections
			$query = "SELECT id, active FROM ".SQL::table_name('articles')." WHERE (anchor IN ('".implode("', '", $anchors)."'))";
			logger::debug($query, 'looking for articles');
			if($result =& SQL::query($query)) {

				// count articles there
				$fields[] = "`articles`=".SQL::count($result);
				$total += SQL::count($result);

				while($aitem =& SQL::fetch($result)) {
					$anchors[] = 'article:'.$aitem['id'];
					if($aitem['active'] == 'N')
						$anchors_private[] = 'article:'.$aitem['id'];
				}

			}

			// count articles edited this year
			$query = "SELECT id FROM ".SQL::table_name('articles')." WHERE (anchor IN ('".implode("', '", $anchors)."')) AND (edit_date LIKE '".$current_year."%')";
			if($count = SQL::query_count($query)) {
				$fields_y[] = "`articles`=".SQL::count($result);
				$total_y += SQL::count($result);
			}

			// count comments attached either to sections or to articles
			$query = "SELECT id FROM ".SQL::table_name('comments')." WHERE (anchor IN ('".implode("', '", $anchors)."'))";
			if($count =& SQL::query_count($query)) {
				$fields[] = "`comments`=".$count;
				$total += $count;
			}

			// count comments edited this year
			$query = "SELECT id FROM ".SQL::table_name('comments')." WHERE (anchor IN ('".implode("', '", $anchors)."')) AND (edit_date LIKE '".$current_year."%')";
			if($count =& SQL::query_count($query)) {
				$fields_y[] = "`comments`=".$count;
				$total_y += $count;
			}

			// count comments attached to private anchors
			$query = "SELECT id FROM ".SQL::table_name('comments')." WHERE (anchor IN ('".implode("', '", $anchors_private)."'))";
			if($count =& SQL::query_count($query)) {
				$fields[] = "`comments_private`=".$count;
				$total_private += $count;
			}

			// count private comments edited this year
			$query = "SELECT id FROM ".SQL::table_name('comments')." WHERE (anchor IN ('".implode("', '", $anchors_private)."')) AND (edit_date LIKE '".$current_year."%')";
			if($count =& SQL::query_count($query)) {
				$fields_y[] = "`comments_private`=".$count;
				$total_private_y += $count;
			}

			// count files attached either to sections or to articles
			$query = "SELECT id FROM ".SQL::table_name('files')." WHERE (anchor IN ('".implode("', '", $anchors)."'))";
			if($count =& SQL::query_count($query)) {
				$fields[] = "`files`=".$count;
				$total += $count;
			}

			// count files edited this year
			$query = "SELECT id FROM ".SQL::table_name('files')." WHERE (anchor IN ('".implode("', '", $anchors)."')) AND (edit_date LIKE '".$current_year."%')";
			if($count =& SQL::query_count($query)) {
				$fields_y[] = "`files`=".$count;
				$total_y += $count;
			}

			// count private files
			$query = "SELECT id FROM ".SQL::table_name('files')." WHERE (anchor IN ('".implode("', '", $anchors)."')) AND (active = 'N')";
			if($count = SQL::query_count($query)) {
				$fields[] = "`files_private`=".$count;
				$total_private += $count;
			}

			// count private files edited this year
			$query = "SELECT id FROM ".SQL::table_name('files')." WHERE (anchor IN ('".implode("', '", $anchors)."')) AND (active = 'N') AND (edit_date LIKE '".$current_year."%')";
			if($count = SQL::query_count($query)) {
				$fields_y[] = "`files_private`=".$count;
				$total_private_y += $count;
			}

			// count links attached either to sections or to articles
			$query = "SELECT id FROM ".SQL::table_name('links')." WHERE (anchor IN ('".implode("', '", $anchors)."'))";
			if($count =& SQL::query_count($query)) {
				$fields[] = "`links`=".$count;
				$total += $count;
			}

			// count links edited this year
			$query = "SELECT id FROM ".SQL::table_name('links')." WHERE (anchor IN ('".implode("', '", $anchors)."')) AND (edit_date LIKE '".$current_year."%')";
			if($count =& SQL::query_count($query)) {
				$fields_y[] = "`links`=".$count;
				$total_y += $count;
			}

			// count links attached to private anchors
			$query = "SELECT id FROM ".SQL::table_name('links')." WHERE (anchor IN ('".implode("', '", $anchors_private)."'))";
			if($count =& SQL::query_count($query)) {
				$fields[] = "`links_private`=".$count;
				$total_private += $count;
			}

			// count private links edited this year
			$query = "SELECT id FROM ".SQL::table_name('links')." WHERE (anchor IN ('".implode("', '", $anchors_private)."')) AND (edit_date LIKE '".$current_year."%')";
			if($count =& SQL::query_count($query)) {
				$fields_y[] = "`links_private`=".$count;
				$total_private_y += $count;
			}

			// total number of items in this section
			$fields[] = "`total`=".$total;
			$fields[] = "`total_private`=".$total_private;

			// add a record for this section
			$query = "INSERT INTO ".SQL::table_name('stat_sections').' SET '.implode(', ', $fields);
			$records += SQL::query($query);

			// total number of items in this section
			$fields_y[] = "`total`=".$total_y;
			$fields_y[] = "`total_private`=".$total_private_y;

			// add a record for things edited during current year
			$query = "INSERT INTO ".SQL::table_name('stat_sections_'.$current_year).' SET '.implode(', ', $fields);
			SQL::query($query);

			// go to next line
			if(!($records%70))
				echo BR;
		}

		echo BR.sprintf('%d records have been processed', $records).BR;

	}

	// stat most active users
	//
	echo sprintf('Building table %s...', SQL::table_name('stat_users')).BR;

	// drop the table first
	$query = "DROP TABLE IF EXISTS `".SQL::table_name('stat_users')."`";
	SQL::query($query);

	// create table with the appropriate structure
	$query = "CREATE TABLE `".SQL::table_name('stat_users')."` (\n"
		."user_id TEXT DEFAULT '',\n"
		."user_label TEXT DEFAULT '',\n"
		."articles INT DEFAULT 0,\n"
		."comments INT DEFAULT 0,\n"
		."files INT DEFAULT 0,\n"
		."links INT DEFAULT 0,\n"
		."sections INT DEFAULT 0,\n"
		."total INT DEFAULT 0)";
	SQL::query($query);

	// limit to most active users
	$query = "SELECT id AS user_id, CONCAT(full_name, ' (', nick_name, ')') AS user_label FROM ".SQL::table_name('users')
		." ORDER BY posts DESC LIMIT 0, 250";

	if(($users =& SQL::query($query))) {

		$records = 0;
		while($item =& SQL::fetch($users)) {

			echo '.';
			$total = 0;

			$fields = array();
			foreach($item as $name => $value)
				$fields[] = "`".$name."`='".SQL::escape($value)."'";

			// count articles posted by this user
			$query = "SELECT count(*) FROM ".SQL::table_name('articles')." WHERE create_id = ".$item['user_id'];
			if($count =& SQL::query_scalar($query)) {
				$fields[] = "`articles`=".$count;
				$total += $count;
			}

			// count comments posted by this user
			$query = "SELECT count(*) FROM ".SQL::table_name('comments')." WHERE create_id = ".$item['user_id'];
			if($count =& SQL::query_scalar($query)) {
				$fields[] = "`comments`=".$count;
				$total += $count;
			}

			// count files posted by this user
			$query = "SELECT count(*) FROM ".SQL::table_name('files')." WHERE create_id = ".$item['user_id'];
			if($count =& SQL::query_scalar($query)) {
				$fields[] = "`files`=".$count;
				$total += $count;
			}

			// count links posted by this user
			$query = "SELECT count(*) FROM ".SQL::table_name('links')." WHERE edit_id = ".$item['user_id'];
			if($count =& SQL::query_scalar($query)) {
				$fields[] = "`links`=".$count;
				$total += $count;
			}

			// count sections posted by this user
			$query = "SELECT count(*) FROM ".SQL::table_name('sections')." WHERE create_id = ".$item['user_id'];
			if($count =& SQL::query_scalar($query)) {
				$fields[] = "`sections`=".$count;
				$total += $count;
			}

			// total number of items in this section
			$fields[] = "`total`=".$total;

			$query = "INSERT INTO ".SQL::table_name('stat_users').' SET '.implode(', ', $fields);
			$records += SQL::query($query);

			// go to next line
			if(!($records%70))
				echo BR;
		}

		echo BR.sprintf('%d records have been processed', $records).BR;

	}

	// stat most active users this year
	//
	echo sprintf('Building table %s...', SQL::table_name('stat_users_'.$current_year)).BR;

	// drop the table first
	$query = "DROP TABLE IF EXISTS `".SQL::table_name('stat_users_'.$current_year)."`";
	SQL::query($query);

	// create table with the appropriate structure
	$query = "CREATE TABLE `".SQL::table_name('stat_users_'.$current_year)."` (\n"
		."user_id TEXT DEFAULT '',\n"
		."user_label TEXT DEFAULT '',\n"
		."articles INT DEFAULT 0,\n"
		."comments INT DEFAULT 0,\n"
		."files INT DEFAULT 0,\n"
		."links INT DEFAULT 0,\n"
		."sections INT DEFAULT 0,\n"
		."total INT DEFAULT 0)";
	SQL::query($query);

	// limit to most active users
	$query = "SELECT id AS user_id, CONCAT(full_name, ' (', nick_name, ')') AS user_label FROM ".SQL::table_name('users')
		." ORDER BY posts DESC LIMIT 0, 250";

	if(($users =& SQL::query($query))) {

		$records = 0;
		while($item =& SQL::fetch($users)) {

			echo '.';
			$total = 0;

			$fields = array();
			foreach($item as $name => $value)
				$fields[] = "`".$name."`='".SQL::escape($value)."'";

			// count articles posted by this user
			$query = "SELECT count(*) FROM ".SQL::table_name('articles')." WHERE (create_id = ".$item['user_id'].") AND (edit_date LIKE '".$current_year."%')";
			if($count =& SQL::query_scalar($query)) {
				$fields[] = "`articles`=".$count;
				$total += $count;
			}

			// count comments posted by this user
			$query = "SELECT count(*) FROM ".SQL::table_name('comments')." WHERE (create_id = ".$item['user_id'].") AND (edit_date LIKE '".$current_year."%')";
			if($count =& SQL::query_scalar($query)) {
				$fields[] = "`comments`=".$count;
				$total += $count;
			}

			// count files posted by this user
			$query = "SELECT count(*) FROM ".SQL::table_name('files')." WHERE (create_id = ".$item['user_id'].") AND (edit_date LIKE '".$current_year."%')";
			if($count =& SQL::query_scalar($query)) {
				$fields[] = "`files`=".$count;
				$total += $count;
			}

			// count links posted by this user
			$query = "SELECT count(*) FROM ".SQL::table_name('links')." WHERE (edit_id = ".$item['user_id'].") AND (edit_date LIKE '".$current_year."%')";
			if($count =& SQL::query_scalar($query)) {
				$fields[] = "`links`=".$count;
				$total += $count;
			}

			// count sections posted by this user
			$query = "SELECT count(*) FROM ".SQL::table_name('sections')." WHERE (create_id = ".$item['user_id'].") AND (edit_date LIKE '".$current_year."%')";
			if($count =& SQL::query_scalar($query)) {
				$fields[] = "`sections`=".$count;
				$total += $count;
			}

			// total number of items in this section
			$fields[] = "`total`=".$total;

			$query = "INSERT INTO ".SQL::table_name('stat_users_'.$current_year).' SET '.implode(', ', $fields);
			$records += SQL::query($query);

			// go to next line
			if(!($records%70))
				echo BR;
		}

		echo BR.sprintf('%d records have been processed', $records).BR;

	}

	// top editors
	//
	echo sprintf('Building table %s...', SQL::table_name('top_editors')).BR;

	// drop the table first
	$query = "DROP TABLE IF EXISTS `".SQL::table_name('top_editors')."`";
	SQL::query($query);

	// create table with the appropriate structure
	$query = "CREATE TABLE `".SQL::table_name('top_editors')."` (\n"
		."user_id TEXT DEFAULT '',\n"
		."user_label TEXT DEFAULT '',\n"
		."`".implode("` TEXT DEFAULT '', `", $all_years)."` TEXT DEFAULT '',\n"
		."total TEXT DEFAULT '')";
	SQL::query($query);

	// subset of targeted users
	$query = "SELECT \n"
		."u.id AS user_id, \n"
		."CONCAT(u.full_name, ' (', u.nick_name, ')') AS user_label, \n"
		."count(a.id) AS total \n"
		."FROM ".SQL::table_name('users')." AS u, ".SQL::table_name('articles')." AS a WHERE (a.owner_id = u.id) \n"
		."GROUP BY u.id \n"
		."ORDER BY total DESC \n"
		."LIMIT 0, 100";

	if(($result =& SQL::query($query))) {

		// one record per user
		$records = 0;
		while($item =& SQL::fetch($result)) {

			$fields = array();
			foreach($item as $name => $value)
				$fields[] = "`".$name."`='".SQL::escape($value)."'";

			// breakdown for recent years
			foreach($all_years as $year) {
				$query = "SELECT count(id) FROM ".SQL::table_name('articles')." WHERE (edit_date LIKE '".$year."%') AND (owner_id = ".$item['user_id'].")";

				if($value = SQL::query_scalar($query))
					$fields[] = "`".$year."`='".SQL::escape($value)."'";
				else
					$fields[] = "`".$year."`='0'";
			}

			$query = "INSERT INTO ".SQL::table_name('top_editors').' SET '.implode(', ', $fields);
			$records += SQL::query($query);
		}

		echo sprintf('%d records have been processed', $records).BR;

	}

	// top uploaders
	//
	echo sprintf('Building table %s...', SQL::table_name('top_uploaders')).BR;

	// drop the table first
	$query = "DROP TABLE IF EXISTS `".SQL::table_name('top_uploaders')."`";
	SQL::query($query);

	// create table with the appropriate structure
	$query = "CREATE TABLE `".SQL::table_name('top_uploaders')."` (\n"
		."user_id TEXT DEFAULT '',\n"
		."user_label TEXT DEFAULT '',\n"
		."`".implode("` TEXT DEFAULT '', `", $all_years)."` TEXT DEFAULT '',\n"
		."total TEXT DEFAULT '')";
	SQL::query($query);

	// subset of targeted users
	$query = "SELECT \n"
		."u.id AS user_id, \n"
		."CONCAT(u.full_name, ' (', u.nick_name, ')') AS user_label, \n"
		."count(a.id) AS total \n"
		."FROM ".SQL::table_name('users')." AS u, ".SQL::table_name('files')." AS a WHERE (a.edit_id = u.id) \n"
		."GROUP BY u.id \n"
		."ORDER BY total DESC \n"
		."LIMIT 0, 100";

	if(($result =& SQL::query($query))) {

		// one record per user
		$records = 0;
		while($item =& SQL::fetch($result)) {

			$fields = array();
			foreach($item as $name => $value)
				$fields[] = "`".$name."`='".SQL::escape($value)."'";

			// breakdown for recent years
			foreach($all_years as $year) {
				$query = "SELECT count(id) FROM ".SQL::table_name('files')." WHERE (edit_date LIKE '".$year."%') AND (edit_id = ".$item['user_id'].")";

				if($value = SQL::query_scalar($query))
					$fields[] = "`".$year."`='".SQL::escape($value)."'";
				else
					$fields[] = "`".$year."`='0'";
			}

			$query = "INSERT INTO ".SQL::table_name('top_uploaders').' SET '.implode(', ', $fields);
			$records += SQL::query($query);
		}

		echo sprintf('%d records have been processed', $records).BR;

	}

	// all done
	$time = round(get_micro_time() - $context['start_time'], 2);
	exit(sprintf('Script terminated in %.2f seconds.', $time).BR);

}
?>
