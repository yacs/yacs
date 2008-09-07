<?php
/**
 * handle content of the database through the wire
 *
 * This script helps either:
 * - to backup the database, by providing a file made of SQL statements that reflects database content
 * - to restore the database, by processing a set of SQL statements in a file
 * - to change database content, by processing some SQL statements typed directly in a text area
 * - to backup user files, by providing a zip file
 * - to backup user images, by providing a zip file
 * - to backup all YACS parameters files, packed in a single zip file
 * - to backup the current skin, with all files packed in a single zip file
 *
 * With a simple click you can create a backup version of the database contents.
 * Without another click you can restore data when necessary.
 *
 * Returned backup data that are files containing standard SQL-queries,
 * that reconstruct the state of the database as it was when the backup was created.
 * It's strongly encouraged to take a backup regularly (weekly or so).
 *
 * The backup function can be limited to the current table prefix, if a
 * database hosts several instances of YACS. it also support latest versions
 * of MySQL drivers, when column types are returned as integers.
 *
 * Backup statements are placed in a temporary file in the directory
 * ##temporary##, and YACS require adequate permissions there
 * to have this work properly.
 *
 * This script is also able to process uploaded SQL statements, separated with the character ';' (semicolon).
 * Comment lines starting with the character '#' are also supported.
 *
 * In the case of very large files that exceed the allowed size for PHP uploads,
 * you can upload files using FTP to the directory ##inbox/database##, and then
 * ask the script to use one of them.
 *
 * Please note that you can use the restore facility as a convenient method to inject new records in your database.
 *
 * Another facility has been added to ease bulk changes of the database. You just have to type one or several
 * statements in a web form and hit the submit button.
 * Statements have to be separated with the character ';' (semicolon).
 * Comment lines starting with the character '#' are also supported.
 * This is a simple way to achieve small bulk changes of the database when necessary, without the overhead of creating a file.
 *
 * If you are in trouble with your database, you can try one of following options.
 *
 * Option 1: If you have been authenticated as a valid associate, trigger this script manually
 * from your web browser. Upload the backup file and let YACS process SQL statements.
 *
 * Option 2: When triggered, the script complains that you are not an associate.
 * In this case, use your FTP account to delete the switch file, at the YACS installation directory.
 * The file can have either the name [code]parameters/switch.on[/code] or [code]parameters/switch.off[/code].
 * Then restart the script to make it accept your upload request.
 * On next click you may have a strange, but harmless, label from setup.php because
 * it will automatically recreate [code]parameters/switch.on[/code].
 *
 * Option 3: Use phpMyAdmin, or an equivalent web-based interface to MySQL.
 * Use your web browser to trigger phpMyAdmin, and use the backup file to setup and populate tables.
 *
 * Option 4: use MySQL directly.
 * If you have a shell account, restoring a backup can be done by running the mysql program with the following
 * arguments:
 *
 * [snippet]
 * mysql -u username -p -h hostname databasename < backupfile.sql
 * [/snippet]
 *
 * In all cases, you should run the [script]control/setup.php[/script] after any restore, in order to
 * recreate index files that are used by YACS to fasten access to data.
 *
 * To restore other kinds of backup archives (user files, user images, parameter files, or skin),
 * you have to explode the archive manually and to upload files individually.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Edige
 * @tester Lucrecius
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 *
 * @link http://www.phpMyAdmin.org/
 *
 * @see control/setup.php
 */

// common definitions and initial processing
include_once '../shared/global.php';

// load localized strings
i18n::bind('control');

// load the skin
load_skin('control');

// the path to this page
$context['path_bar'] = array( 'control/' => i18n::s('Control Panel') );

// the title of the page
$context['page_title'] = i18n::s('Backup/Restore');

// the user table should be empty, or the surfer has to be an associate, or the switch file has been deleted
$query = "SELECT count(*) FROM ".SQL::table_name('users');
if((SQL::query($query) !== FALSE) && !Surfer::is_associate()
	&& (file_exists($context['path_to_root'].'parameters/switch.on') || file_exists($context['path_to_root'].'parameters/switch.off'))) {

	// prevent access to this script
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

	// forward to the control panel
	$menu = array('control/' => i18n::s('Control Panel'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// backup the database
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'backup')) {

	// save in a temporary file
	$statements = 'statements.sql';
	if(isset($context['host_name']) && isset($context['database']))
		$statements = $context['host_name'].'_'.$context['database'].'.sql';

	// put all statements in a file
	if(is_callable('gzopen') && ($handle = gzopen($context['path_to_root'].'temporary/'.$statements.'.gz', 'wb9')))
		$compressed = TRUE;
	elseif($handle = Safe::fopen('temporary/'.$statements, 'wb'))
		$compressed = FALSE;
	else {
		Safe::header('Status: 500 Internal Error', TRUE, 500);
		die('Impossible to write to temporary file.');
	}

	// file header
	$sql = '#'."\n"
		.'# This file has been created by the configuration script control/backup.php'."\n"
		.'# on '.gmdate("F j, Y, g:i a").' GMT, for '.Surfer::get_name().'.'."\n"
		.'# Please avoid to modify it manually.'."\n"
		.(isset($_REQUEST['backup_prefix']) ? '#'."\n".'# Only tables with the prefix "'.$_REQUEST['backup_prefix'].'" have been considered'."\n" : '')
		.'#'."\n\n";
	if($compressed)
		gzwrite($handle, $sql);
	else
		fwrite($handle, $sql);

	// skip some tables
	$to_avoid = array();
	if(isset($_REQUEST['backup_avoid']) && ($tokens = explode(' ', $_REQUEST['backup_avoid']))) {
		foreach($tokens as $token)
			$to_avoid[] = str_replace('`', '', SQL::table_name($token));
	}

	//enumerate tables
	$queries = 0;
	$tables = SQL::list_tables($context['database']);
	while($row = SQL::fetch_row($tables)) {

		// table name
		$table_name=$row[0];

		// skip unmatched prefixes
		if(isset($_REQUEST['backup_prefix']) && !preg_match('/'.preg_quote($_REQUEST['backup_prefix'], '/').'/i', $table_name))
			continue;

		// the string to re-create table structure
		$query = "SHOW CREATE TABLE ".$table_name;
		if((!$result =& SQL::query_first($query)) || !isset($result['Create Table']))
			continue;

		// strip constraints and keep only engine definition
		$create_query = preg_replace('/(ENGINE=\w+)\b.*$/i', '\\1', $result['Create Table']);

		// split lines
		$create_query = str_replace('\n', "\n", $create_query);

		// build the table creation query
		$sql = 'DROP TABLE IF EXISTS '.$table_name.";\n\n"
			.$create_query.";\n\n";
		if($compressed)
			gzwrite($handle, $sql);
		else
			fwrite($handle, $sql);

		// skip content of some tables
		if(in_array($table_name, $to_avoid))
			continue;

		// read all lines
		$query = "SELECT * FROM $table_name";
		if(!$result =& SQL::query($query)) {
			$context['text'] .= Skin::error_pop().BR."\n";
			continue;
		}

		//parse the field info first
		$field_list = '';
		$index = 0;
		while($field =& SQL::fetch_field($result)) {
			$field_list .= $field->name.', ';

			$is_numeric = FALSE;
			switch(strtolower($field->type)) {
			case "int":
			case 9:
				$is_numeric = TRUE;
				break;

			case "blob":
			case 252:
				$is_numeric = FALSE;
				break;

			case "real":
				$is_numeric = TRUE;
				break;

			case "string":
			case 253:
			case 254:
				$is_numeric = FALSE;
				break;

			case "unknown":
				$is_numeric = TRUE;
				break;

			case "timestamp":
				$is_numeric = TRUE;
				break;

			case "date":
			case 12:
				$is_numeric = FALSE;
				break;

			case "datetime":
				$is_numeric = FALSE;
				break;

			case "time":
				$is_numeric = FALSE;
				break;

			default:
				$is_numeric = TRUE;
				break;
			}

			$is_unsigned = FALSE;
			if(strpos($field->type, "unsigned"))
				$is_unsigned = TRUE;

			//we need some of the info generated in this loop later in the algorythm...save what we need to arrays
			$ina[$index] = $is_numeric;
			$inu[$index] = $is_unsigned;

			// next field
			$index++;
		}

		// remove last comma
		$field_list = rtrim($field_list, ', ');

		//parse out the table's data and generate the SQL INSERT statements in order to replicate the data itself...
		while($row = SQL::fetch_row($result)) {

			$sql = 'INSERT INTO '.$table_name.' ('.$field_list.') VALUES (';

			for($d=0; $d < count($row); $d++) {

				if($inu[$d] == TRUE)
					$sql .= $row[$d]; // unsigned, do not use intval()
				elseif($ina[$d] == TRUE)
					$sql .= intval($row[$d]);
				else
					$sql .= "'".SQL::escape(strval($row[$d]))."'";

				if($d < (count($row)-1))
					$sql.=", ";

			}

			$sql .= ");\n";

			if($compressed)
				gzwrite($handle, $sql);
			else
				fwrite($handle, $sql);

			// ensure we have enough time
			$queries++;
			if(!($queries%100))
				Safe::set_time_limit(30);

		}
		SQL::free($result);

		if($compressed)
			gzwrite($handle, "\n\n");
		else
			fwrite($handle, "\n\n");

	}

	if($compressed)
		gzclose($handle);
	else
		fclose($handle);

	// if possible, make a gzip file
	if($compressed) {

		// suggest a download
		Safe::header('Content-Disposition: attachment; filename="'.$statements.'.gz"');

		// send gzip file
		Safe::header('Content-Type: application/x-gzip');
		Safe::header('Content-Length: '.filesize($context['path_to_root'].'temporary/'.$statements.'.gz'));
		Safe::header('Content-Transfer-Encoding: binary'); // already encoded
		$result = readfile($context['path_to_root'].'temporary/'.$statements.'.gz');

	// else send an ascii file
	} else {

		// suggest a download
		Safe::header('Content-Disposition: attachment; filename="'.$statements.'"');

		// send sql statements as-is
		Safe::header('Content-Type: application/octet-stream');
		Safe::header('Content-Length: '.filesize($context['path_to_root'].'temporary/'.$statements));
		$result = readfile($context['path_to_root'].'temporary/'.$statements);

	}

	// remember this in log as well
	Logger::remember('control/backup.php', 'The database has been saved');

	// do not allow for regular rendering
	exit;

// restore the database by reading a local file
} elseif(isset($_REQUEST['id']) || isset($context['arguments'][0])) {

	// restoring the database
	$context['text'] .= '<p>'.i18n::s('Updating the database...')."</p>\n";

	$id = NULL;
	if(isset($_REQUEST['id']))
		$id = $_REQUEST['id'];
	elseif(isset($context['arguments'][0]))
		$id = $context['arguments'][0];

	// fight against hackers
	$id = basename(preg_replace(FORBIDDEN_IN_PATHS, '', strip_tags($id)));

	// scope is limited to the inbox
	if($queries = SQL::process($context['path_to_root'].'inbox/database/'.$id)) {

		// report of script data
		$time = round(get_micro_time() - $context['start_time'], 2);
		$context['text'] .= '<p>'.sprintf(i18n::s('%d SQL statements have been processed in %.2f seconds.'), $queries, $time).'</p>';

		// clear the cache
		Cache::clear();

		// recreates index as well
		$menu = array('control/setup.php?action=build' => i18n::s('Reindex tables and optimize data storage'));
		$context['text'] .= Skin::build_list($menu, 'menu_bar');

		// remember this in log as well
		Logger::remember('control/backup.php', 'The database has been restored', $queries.' SQL statements have been processed in '.$time.' seconds.');

	}

// restore the database
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'restore')) {

	// restoring the database
	$context['text'] .= '<p>'.i18n::s('Updating the database...')."</p>\n";

	// no file has been uploaded
	if(!$_FILES['upload']['name'] || ($_FILES['upload']['name'] == 'none'))
		$context['text'] .= '<p>'.i18n::s('Nothing has been received.')."</p>\n";

	// no bytes have been transmitted
	elseif(!$_FILES['upload']['size']) {
		$context['text'] .= '<p>'.i18n::s('Nothing has been received.')."</p>\n";

		// access the temporary uploaded file
		$file_upload = $_FILES['upload']['tmp_name'];

	// check provided upload name
	} elseif(!Safe::is_uploaded_file($_FILES['upload']['tmp_name']))
		$context['text'] .= '<p>'.i18n::s('Possible file attack.')."</p>\n";

	// process statements
	elseif($queries = SQL::process($_FILES['upload']['tmp_name'])) {

		// report of script data
		$time = round(get_micro_time() - $context['start_time'], 2);
		$context['text'] .= '<p>'.sprintf(i18n::s('%d SQL statements have been processed in %.2f seconds.'), $queries, $time).'</p>';

		// also delete files if requested to do so
		if(isset($_REQUEST['delete_files']) && ($_REQUEST['delete_files'] == 'Y')) {
			$milestone = get_micro_time();

			/**
			 * delete a directory and all of its content
			 *
			 * @param string the directory to delete
			 */
			function delete_all($path) {
				global $context;

				$path_translated = str_replace('//', '/', $context['path_to_root'].'/'.$path);
				if($handle = Safe::opendir($path_translated)) {

					while(($node = Safe::readdir($handle)) !== FALSE) {

						if($node == '.' || $node == '..')
							continue;

						// make a real name
						$target = str_replace('//', '/', $path.'/'.$node);
						$target_translated = str_replace('//', '/', $path_translated.'/'.$node);

						// delete a sub directory
						if(is_dir($target_translated))
							delete_all($path.'/'.$node);

						// delete the node
						Safe::unlink($context['path_to_root'].$path.'/'.$node);
						Safe::rmdir($context['path_to_root'].$path.'/'.$node);
						global $deleted_nodes;
						$deleted_nodes++;

					}
					Safe::closedir($handle);
				}

			}

			// locations with user files
			$paths = array();
			$paths[] = 'files/article';
			$paths[] = 'files/category';
			$paths[] = 'files/section';
			$paths[] = 'files/user';
			$paths[] = 'images/article';
			$paths[] = 'images/category';
			$paths[] = 'images/section';
			$paths[] = 'images/user';

			$context['text'] .= '<p>'.i18n::s('Purging...').'</p><ul>';
			foreach($paths as $path) {
				$context['text'] .= '<li>'.$path.'</li>';
				delete_all($path);
			}
			$context['text'] .= '</ul>';

			// report of script data
			global $deleted_nodes;
			if($deleted_nodes) {
				$time = round(get_micro_time() - $milestone, 2);
				$context['text'] .= '<p>'.sprintf(i18n::s('%d items have been deleted'), $deleted_nodes).'</p>';
			}

		}

		// clear the cache
		Cache::clear();

		// recreates index as well
		$menu = array('control/setup.php?action=build' => i18n::s('Reindex tables and optimize data storage'));
		$context['text'] .= Skin::build_list($menu, 'menu_bar');

		// remember this in log as well
		Logger::remember('control/backup.php', 'The database has been restored', $queries.' SQL statements have been processed in '.$time.' seconds.');

	}

// update the database
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'update')) {

	// updating the database
	$context['text'] .= '<p>'.i18n::s('Updating the database...')."</p>\n";

	// no file has been uploaded
	if(!$_REQUEST['statements'])
		$context['text'] .= '<p>'.i18n::s('No statement has been uploaded.')."</p>\n";

	// else read plain content
	elseif(!$lines = explode("\n", $_REQUEST['statements']))
		$context['text'] .= '<p>'.i18n::s('No statement has been uploaded.')."</p>\n";

	// process statements
	else {

		// ensure enough execution time
		Safe::set_time_limit(30);

		// process every line
		$query = '';
		$queries = 0;
		$count = 0;
		foreach($lines as $line) {

			// current line, for possible error reporting
			$count++;
			if(!$query)
				$here = $count;

			// transcode unicode entities --including those with a &#
			$line =& utf8::from_unicode($line);

			// skip empty lines
			$line = trim($line, " \t\r\n\0\x0B");
			if(!$line)
				continue;
			if($line == '--')
				continue;

			// skip line comments
			if($line[0] == '#')
				continue;
			if(strncmp($line, '-- ', 3) == 0)
				continue;

			// look for closing ";"
			$last = strlen($line)-1;
			if($line[$last] == ';') {
				if($last == 0)
					$line = '';
				else
					$line = substr($line, 0, $last);
				$execute = TRUE;
			} elseif($count >= count($lines))
				$execute = TRUE;
			else
				$execute = FALSE;

			// a statement
			$query .= $line."\n";

			// end of statement - process it
			if($query && $execute) {
				$context['text'] .= '<pre>'.$query.'</pre>'."\n";

				// execute the statement
				if(($affected = SQL::query($query, TRUE)) === FALSE)
					$context['text'] .= '<p>'.$here.': '.$query.BR.SQL::error()."</p>\n";

				// display query and results
				else
					$context['text'] .= '<p>'.sprintf(i18n::s('%d records have been processed'), $affected)."</p>\n";

				// next query
				$query = '';
				$queries++;

				// ensure we have enough time
				if(!($queries%1000))
					Safe::set_time_limit(30);

			}
		}

		// clear the cache
		Cache::clear();

		// report of script data
		$time_end = get_micro_time();
		$time = round($time_end - $context['start_time'], 2);
		$context['text'] .= '<p>'.sprintf(i18n::s('%d SQL statements have been processed in %.2f seconds.'), $queries, $time).'</p>';

		// recreates indes as well
		$menu = array('control/setup.php' => i18n::s('Reindex tables and optimize data storage'));
		$context['text'] .= Skin::build_list($menu, 'menu_bar');

		// remember this in log as well
		Logger::remember('control/backup.php', 'The database has been updated', $queries.' SQL statements have been processed in '.$time.' seconds.');

	}

// backup user files
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'files')) {

	// list files
	$context['text'] .= '<p>'.i18n::s('Listing files...').BR."\n";

	// locate files
	include_once '../scripts/scripts.php';
	$file_path = 'files/';
	$datafiles = Scripts::list_files_at($file_path);

	if(is_array($datafiles)) {
		$context['text'] .= BR.sprintf(i18n::s('%d files have been found.'), count($datafiles)).'</p>'."\n";

		// splash message
		$context['text'] .= '<p>'.i18n::s('On-going archive preparation...').'</p>'."\n";

		// build a zip archive
		include_once '../shared/zipfile.php';
		$zipfile = new zipfile();

		// process every files/ file
		$index = 0;
		foreach($datafiles as $datafile) {

			// let's go
			list($path, $filename) = $datafile;
			$path = str_replace(array('files/', 'file'), '', $path);
			if($path)
				$file = $path.'/'.$filename;
			else
				$file = $filename;

			// do not include icons path
			if($path == 'icons')
				continue;

			// do not include php scripts
			if(preg_match('/\.(php|php\.bak)$/i', $file))
				continue;

			// read file content
			if(($content = Safe::file_get_contents('../'.$file_path.$file)) !== FALSE) {

				// store binary data
				$zipfile->store('files/'.$file, Safe::filemtime('../'.$file_path.$file), $content);

				// avoid timeouts
				if(!(($index++)%50)) {
					Safe::set_time_limit(30);
					SQL::ping();
				}
			}
		}

		// suggest a download
		Safe::header('Content-Type: application/zip');
		Safe::header('Content-Disposition: attachment; filename="backup_files.zip"');

		// send the archive content
		echo $zipfile->get();

		// do not allow for regular rendering
		return;

	// no file
	} else
		$context['text'] .= BR.i18n::s('No file have been found.').'</p>'."\n";

// backup user images
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'images')) {

	// list images
	$context['text'] .= '<p>'.i18n::s('Listing images...').BR."\n";

	// locate images
	include_once '../scripts/scripts.php';
	$file_path = 'images/';
	$datafiles = Scripts::list_files_at($file_path);

	if(is_array($datafiles)) {
		$context['text'] .= BR.sprintf(i18n::s('%d images have been found.'), count($datafiles)).'</p>'."\n";

		// splash message
		$context['text'] .= '<p>'.i18n::s('On-going archive preparation...').'</p>'."\n";

		// build a zip archive
		include_once '../shared/zipfile.php';
		$zipfile = new zipfile();

		// process every images/ file
		$index = 0;
		foreach($datafiles as $datafile) {

			// let's go
			list($path, $filename) = $datafile;
			$path = str_replace(array('images/', 'images'), '', $path);
			if($path)
				$file = $path.'/'.$filename;
			else
				$file = $filename;

			// do not include php scripts
			if(preg_match('/\.(php|php\.bak)$/i', $file))
				continue;

			// read file content
			if(($content = Safe::file_get_contents('../'.$file_path.$file)) !== FALSE) {

				// store binary data
				$zipfile->store('images/'.$file, Safe::filemtime('../'.$file_path.$file), $content);

				// avoid timeouts
				if(!(($index++)%50)) {
					Safe::set_time_limit(30);
					SQL::ping();
				}
			}
		}

		// suggest a download
		Safe::header('Content-Type: application/zip');
		Safe::header('Content-Disposition: attachment; filename="backup_images.zip"');

		// send the archive content
		echo $zipfile->get();

		// do not allow for regular rendering
		return;

	// no file
	} else
		$context['text'] .= BR.i18n::s('No image have been found.').'</p>'."\n";

// backup parameter files
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'parameters')) {

	// splash message
	$context['text'] .= '<p>'.i18n::s('On-going archive preparation...').'</p>'."\n";

	// build a zip archive
	include_once '../shared/zipfile.php';
	$zipfile = new zipfile();

	// list parameter files
	if(($files = Safe::glob($context['path_to_root'].'parameters/*.include.php')) !== FALSE) {

		// process every parameter file
		foreach($files as $file) {

			// store binary data
			if(($content = Safe::file_get_contents($file)) !== FALSE)
				$zipfile->store(basename($file), Safe::filemtime($file), $content);

		}
	}

	// suggest a download
	Safe::header('Content-Type: application/zip');
	Safe::header('Content-Disposition: attachment; filename="backup_parameters.zip"');

	// send the archive content
	echo $zipfile->get();

	// do not allow for regular rendering
	return;

// backup current skin
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'skin')) {

	// list files
	$context['text'] .= '<p>'.i18n::s('Listing files...').BR."\n";

	// locate skin
	include_once '../scripts/scripts.php';
	$file_path = $context['skin'];
	$datafiles = Scripts::list_files_at($file_path.'/');

	if(is_array($datafiles)) {
		$context['text'] .= BR.sprintf(i18n::s('%d files have been found.'), count($datafiles)).'</p>'."\n";

		// splash message
		$context['text'] .= '<p>'.i18n::s('On-going archive preparation...').'</p>'."\n";

		// build a zip archive
		include_once '../shared/zipfile.php';
		$zipfile = new zipfile();

		// process every skin/current_skin/ file
		$index = 0;
		foreach($datafiles as $datafile) {

			// let's go
			list($path, $filename) = $datafile;
			$path = str_replace(array($file_path.'/', $file_path), '', $path);
			if($path)
				$file = $path.'/'.$filename;
			else
				$file = $filename;

			// read file content
			if(($content = Safe::file_get_contents('../'.$file_path.'/'.$file)) !== FALSE) {

				// store binary data
				$zipfile->store($file, Safe::filemtime('../'.$file_path.'/'.$file), $content);

				// avoid timeouts
				if(!(($index++)%50)) {
					Safe::set_time_limit(30);
					SQL::ping();
				}
			}
		}

		// suggest a download
		Safe::header('Content-Type: application/zip');
		Safe::header('Content-Disposition: attachment; filename="backup_'.$context['skin'].'.zip"');

		// send the archive content
		echo $zipfile->get();

		// do not allow for regular rendering
		return;

	// no file
	} else
		$context['text'] .= BR.i18n::s('No item has been found.').'</p>'."\n";

// select the operation to perform
} else {

	// the splash label
	$context['text'] .= '<p>'.i18n::s('Please select the action to perform.')."</p>\n";

	// backup
	$context['text'] .= Skin::build_block(i18n::s('Backup database content'), 'title');

	// introductory text
	$context['text'] .= '<p>'.i18n::s('This script will generate SQL statements necessary to rebuild your database from scratch. Please file it at your computer for backup purpose.')."</p>\n";

	// backup optimization
	$context['text'] .= '<p>'.sprintf(i18n::s('Since the full content of your database will be downloaded, you would like to %s it before proceeding.'), Skin::build_link('control/purge.php', i18n::s('purge'), 'shortcut'))."</p>\n";

	// use the same script
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>';

	// the submit button
	$context['text'] .= '<p><input type="hidden" name="action" value="backup" />'
		.Skin::build_submit_button(i18n::s('Yes, I want to download database content'), NULL, NULL, 'go', 'no_spin_on_click');

	// table prefix
	if(isset($context['table_prefix']) && $context['table_prefix']) {
		$context['text'] .= BR.'<input type="checkbox" name="backup_prefix" value="'.$context['table_prefix'].'" checked="checked" /> '.sprintf(i18n::s('Consider only tables with the prefix %s'), $context['table_prefix']);
	}

	// minimum data
	$context['text'] .= BR.'<input type="checkbox" name="backup_avoid" value="cache notifications phpdoc versions visits" checked="checked" /> '.sprintf(i18n::s('Skip transient data and minimize size of backup file'));

	// end of this form
	$context['text'] .= '</p></div></form>';

	// set the focus on the backup button
	$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
		.'$("go").focus();'."\n"
		.'// ]]></script>'."\n";

	// this may take several minutes
	$context['text'] .= '<p>'.i18n::s('When you will click on the button the server will be immediately requested to proceed. However, because of the so many things to do on the back-end, you may have to wait for minutes before getting a response displayed. Thank you for your patience.')."</p>\n";

	// restore
	$context['text'] .= Skin::build_block(i18n::s('Restore database content'), 'title');

	// introductory text
	$context['text'] .= '<p>'.i18n::s('Use this script to upload and process a set of SQL statements. WARNING!!! If you upload a backup file existing data will be destroyed prior the restauration.')."</p>\n";

	// the form to restore a file
	$context['text'] .= '<form method="post" enctype="multipart/form-data" action="'.$context['script_url'].'"><div>'
		.'<input type="hidden" name="action" value="restore" />';

	// the maximum size for uploads
	$file_maximum_size = str_replace('M', ' M', Safe::get_cfg_var('upload_max_filesize'));
	if(!$file_maximum_size)
		$file_maximum_size = '2 M';

	// select a file
	$context['text'] .= '<p>'.i18n::s('Select the file to upload')
		.' (&lt;&nbsp;'.$file_maximum_size.i18n::s('bytes').')'.BR
		.'<input type="file" name="upload" size="30" /></p>';

	// find available database files
	$archives = array();
	if($dir = Safe::opendir("../inbox/database")) {

		// scan the file system
		while(($file = Safe::readdir($dir)) !== FALSE) {

			// skip special entries
			if($file == '.' || $file == '..')
				continue;

			// skip special files
			if(($file[0] == '.') || ($file[0] == '~'))
				continue;

			// skip non-archive files
			if(!preg_match('/(\.dump|\.sql|\.sql.gz)/i', $file))
				continue;

			// this is an archive to consider
			$archives[] = $file;

		}
		Safe::closedir($dir);

		// alphabetical order
		if(@count($archives))
			sort($archives);

	}

	// list available archives
	if(count($archives)) {

		$context['text'] .= '<p>'.sprintf(i18n::s('or use the following file from directory %s:'), 'inbox/database').'</p><ul>';
		foreach($archives as $archive) {
			$context['text'] .= '<li>'.Skin::build_link('control/backup.php?id='.urlencode($archive), sprintf(i18n::s('Restore %s'), $archive), 'basic').'</li>';
		}
		$context['text'] .= '</ul>';

	}

	// the submit button
	$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Apply these SQL statements'), i18n::s('Press [s] to submit data'), 's')."\n";

	// purge files and images
	$context['text'] .= BR.'<input type="checkbox" name="delete_files" value="Y" checked="checked" />'.i18n::s('Delete files and images related to previous database content');

	// end of the form
	$context['text'] .= '</p></div></form>';

	// this may take several minutes
	$context['text'] .= i18n::s('When you will click on the button the server will be immediately requested to proceed. However, because of the so many things to do on the back-end, you may have to wait for minutes before getting a response displayed. Thank you for your patience.');

	// update
	$context['text'] .= Skin::build_block(i18n::s('Update database content'), 'title');

	// introductory text
	$context['text'] .= '<p>'.i18n::s('Type one or several SQL statements below to change the content of the database. WARNING!!! Be sure to understand the conceptual data model before proceeding, else you would corrupt database content.')."</p>\n";

	// the form to apply statements
	$context['text'] .= '<form method="post" enctype="multipart/form-data" action="'.$context['script_url'].'"><div>'
		.'<input type="hidden" name="action" value="update" />';

	// direct statements
	$context['text'] .= i18n::s('UPDATE commands separated by ;').BR
		.'<textarea name="statements" rows="12" cols="50"></textarea>';

	// the submit button
	$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Apply these SQL statements'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

	// end of the form
	$context['text'] .= '</div></form>';

	// this may take several minutes
	$context['text'] .= '<p>'.i18n::s('When you will click on the button the server will be immediately requested to proceed. However, because of the so many things to do on the back-end, you may have to wait for minutes before getting a response displayed. Thank you for your patience.')."</p>\n";

	// backup data
	$context['text'] .= Skin::build_block(i18n::s('Backup data'), 'title');

	// introductory text
	$context['text'] .= '<p>'.i18n::s('Use this script to download archives of all files, images, parameters or current skin. Please file them at your computer for backup purpose.').'</p>'."\n";

	// the submit button for files
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'">';
	$context['text'] .= '<p>'
		.'<input type="hidden" name="action" value="files" />'
		.Skin::build_submit_button(i18n::s('Yes, I want to download files'), i18n::s('Press [f] to save files'), 'f', NULL, 'no_spin_on_click')
		.'</p>'."\n".'</form>';

	// the submit button for images
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'">';
	$context['text'] .= '<p>'
		.'<input type="hidden" name="action" value="images" />'
		.Skin::build_submit_button(i18n::s('Yes, I want to download images'), i18n::s('Press [i] to save images'), 'f', NULL, 'no_spin_on_click')
		.'</p>'."\n".'</form>';

	// the submit button for parameters
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'">';
	$context['text'] .= '<p>'
		.'<input type="hidden" name="action" value="parameters" />'
		.Skin::build_submit_button(i18n::s('Yes, I want to download parameter files'), i18n::s('Press [p] to save parameters'), 'f', NULL, 'no_spin_on_click')
		.'</p>'."\n".'</form>';

	// the submit button for current skin
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'">';
	$context['text'] .= '<p>'
		.'<input type="hidden" name="action" value="skin" />'
		.Skin::build_submit_button(i18n::s('Yes, I want to download the current skin'), i18n::s('Press [k] to save the skin'), 'k', NULL, 'no_spin_on_click')
		.'</p>'."\n".'</form>';

	// this may take several minutes
	$context['text'] .= '<p>'.i18n::s('When you will click on the button the server will be immediately requested to proceed. However, because of the so many things to do on the back-end, you may have to wait for minutes before getting a response displayed. Thank you for your patience.').'</p>'."\n";

}

// render the skin
render_skin();

?>