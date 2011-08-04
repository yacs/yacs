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
		."anchor TEXT DEFAULT '',\n"
		."edit_date TEXT DEFAULT '',\n"
		."section_id TEXT DEFAULT '',\n"
		."section_label TEXT DEFAULT '',\n"
		."articles INT DEFAULT 0,\n"
		."comments INT DEFAULT 0,\n"
		."files INT DEFAULT 0,\n"
		."links INT DEFAULT 0,\n"
		."sections INT DEFAULT 0,\n"
		."total INT DEFAULT 0)";
	SQL::query($query);

	// one record for each second-level section
	$query = "SELECT anchor, edit_date, id AS section_id, title AS section_label FROM ".SQL::table_name('sections')." WHERE (anchor IN \n"
		."(SELECT CONCAT('section:', id) FROM ".SQL::table_name('sections')." WHERE (anchor < 's')))";

	if($sections =& SQL::query($query)) {

		$records = 0;
		while($item =& SQL::fetch($sections)) {

			echo '.';
			$total = 0;

			$fields = array();
			foreach($item as $name => $value)
				$fields[] = "`".$name."`='".SQL::escape($value)."'";

			// list children sections, as deep as necessary
			$anchors = array( 'section:'.$item['section_id'] );
			$row = $anchors;
			while(count($row)) {

				$query = "SELECT id FROM ".SQL::table_name('sections')." WHERE anchor IN ('".implode("', '", $row)."')";
				$row = array();
				if($result =& SQL::query($query)) {

					while($item =& SQL::fetch($result)) {
						$anchors[] = 'section:'.$item['id'];
						$row[] = 'section:'.$item['id'];
					}

				}

			}

			// count sub-sections
			$fields[] = "`sections`=".(count($anchors)-1);
			$total += count($anchors)-1;

			// list articles anchored to these sections
			$query = "SELECT id FROM ".SQL::table_name('articles')." WHERE anchor IN ('".implode("', '", $anchors)."')";
			if($result =& SQL::query($query)) {

				// count articles there
				$fields[] = "`articles`=".SQL::count($result);
				$total += SQL::count($result);

				while($item =& SQL::fetch($result))
					$anchors[] = 'article:'.$item['id'];

			}

			// count comments attached either to sections or to articles
			$query = "SELECT count(*) FROM ".SQL::table_name('comments')." WHERE anchor IN ('".implode("', '", $anchors)."')";
			if($count =& SQL::query_scalar($query)) {
				$fields[] = "`comments`=".$count;
				$total += $count;
			}

			// count files attached either to sections or to articles
			$query = "SELECT count(*) FROM ".SQL::table_name('files')." WHERE anchor IN ('".implode("', '", $anchors)."')";
			if($count =& SQL::query_scalar($query)) {
				$fields[] = "`files`=".$count;
				$total += $count;
			}

			// count links attached either to sections or to articles
			$query = "SELECT count(*) FROM ".SQL::table_name('links')." WHERE anchor IN ('".implode("', '", $anchors)."')";
			if($count =& SQL::query_scalar($query)) {
				$fields[] = "`links`=".$count;
				$total += $count;
			}

			// total number of items in this section
			$fields[] = "`total`=".$total;

			$query = "INSERT INTO ".SQL::table_name('stat_sections').' SET '.implode(', ', $fields);
			$records += SQL::query($query);

			if(!($records%100))
				echo BR;
		}

		echo BR.sprintf('%d records have been processed', $records).BR;

	}

	// stat second-level sections since beginning of the year
	//
	echo sprintf('Building table %s...', SQL::table_name('stat_sections_'.$current_year)).BR;

	// drop the table first
	$query = "DROP TABLE IF EXISTS `".SQL::table_name('stat_sections_'.$current_year)."`";
	SQL::query($query);

	// create table with the appropriate structure
	$query = "CREATE TABLE `".SQL::table_name('stat_sections_'.$current_year)."` (\n"
		."anchor TEXT DEFAULT '',\n"
		."edit_date TEXT DEFAULT '',\n"
		."section_id TEXT DEFAULT '',\n"
		."section_label TEXT DEFAULT '',\n"
		."articles INT DEFAULT 0,\n"
		."comments INT DEFAULT 0,\n"
		."files INT DEFAULT 0,\n"
		."links INT DEFAULT 0,\n"
		."sections INT DEFAULT 0,\n"
		."total INT DEFAULT 0)";
	SQL::query($query);

	// one record for each second-level section
	$query = "SELECT anchor, edit_date, id AS section_id, title AS section_label FROM ".SQL::table_name('sections')." WHERE (anchor IN \n"
		."(SELECT CONCAT('section:', id) FROM ".SQL::table_name('sections')." WHERE (anchor < 's')))";

	if($sections =& SQL::query($query)) {

		$records = 0;
		while($item =& SQL::fetch($sections)) {

			echo '.';
			$total = 0;

			$fields = array();
			foreach($item as $name => $value)
				$fields[] = "`".$name."`='".SQL::escape($value)."'";

			// list children sections, as deep as necessary
			$anchors = array( 'section:'.$item['section_id'] );
			$row = $anchors;
			while(count($row)) {

				$query = "SELECT id FROM ".SQL::table_name('sections')." WHERE anchor IN ('".implode("', '", $row)."')";
				$row = array();
				if($result =& SQL::query($query)) {

					while($item =& SQL::fetch($result)) {
						$anchors[] = 'section:'.$item['id'];
						$row[] = 'section:'.$item['id'];
					}

				}

			}

			// list articles anchored to these sections
			$query = "SELECT id FROM ".SQL::table_name('articles')." WHERE anchor IN ('".implode("', '", $anchors)."')";
			if($result =& SQL::query($query)) {

				while($item =& SQL::fetch($result))
					$anchors[] = 'article:'.$item['id'];

			}

			// count articles edited this year
			$query = "SELECT count(*) FROM ".SQL::table_name('articles')
				." WHERE (anchor IN ('".implode("', '", $anchors)."')) AND (edit_date LIKE '".$current_year."%')";
			if($count =& SQL::query_scalar($query)) {
				$fields[] = "`articles`=".$count;
				$total += $count;

			}

			// count comments attached either to sections or to articles
			$query = "SELECT count(*) FROM ".SQL::table_name('comments')
				." WHERE (anchor IN ('".implode("', '", $anchors)."')) AND (edit_date LIKE '".$current_year."%')";
			if($count =& SQL::query_scalar($query)) {
				$fields[] = "`comments`=".$count;
				$total += $count;
			}

			// count files attached either to sections or to articles
			$query = "SELECT count(*) FROM ".SQL::table_name('files')
				." WHERE (anchor IN ('".implode("', '", $anchors)."')) AND (edit_date LIKE '".$current_year."%')";
			if($count =& SQL::query_scalar($query)) {
				$fields[] = "`files`=".$count;
				$total += $count;
			}

			// count links attached either to sections or to articles
			$query = "SELECT count(*) FROM ".SQL::table_name('links')
				." WHERE (anchor IN ('".implode("', '", $anchors)."')) AND (edit_date LIKE '".$current_year."%')";
			if($count =& SQL::query_scalar($query)) {
				$fields[] = "`links`=".$count;
				$total += $count;
			}

			// count sections edited this year
			$query = "SELECT count(*) FROM ".SQL::table_name('sections')
				." WHERE (anchor IN ('".implode("', '", $anchors)."')) AND (edit_date LIKE '".$current_year."%')";
			if($count =& SQL::query_scalar($query)) {
				$fields[] = "`sections`=".$count;
				$total += $count;

			}

			// total number of items in this section
			$fields[] = "`total`=".$total;

			$query = "INSERT INTO ".SQL::table_name('stat_sections_'.$current_year).' SET '.implode(', ', $fields);
			$records += SQL::query($query);

			if(!($records%100))
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

	if($users =& SQL::query($query)) {

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

			if(!($records%100))
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

	if($users =& SQL::query($query)) {

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

			if(!($records%100))
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

	if($result =& SQL::query($query)) {

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

	if($result =& SQL::query($query)) {

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