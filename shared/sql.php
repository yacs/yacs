<?php
/**
 * virtualize interactions with the database
 *
 * This script is fully dedicated to MySQL. It uses the improved MySQL PHP
 * extension instead of the legacy one where applicable.
 *
 * Where possible, the database and tables are set to support Unicode character
 * set, namely, 'utf8'.
 *
 * @link http://www.artfulsoftware.com/infotree/queries.php Common queries in MySQL 5
 *
 * @author Bernard Paques
 * @tester Thierry Pinelli (ThierryP)
 * @tester Neige1963
 * @tester Alain Lesage
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class SQL {

	/**
	 * check presence of SQL back-end
	 *
	 * @return TRUE or FALSE
	 */
	public static function check() {

		return (is_callable('mysqli_connect') || is_callable('mysql_connect'));

	}

	/**
	 * connect to the database server
	 *
	 * @param string server host name
	 * @param string account
	 * @param string password
	 * @param string database to use
	 * @return a valid resource, or FALSE on failure
	 */
	public static function connect($host, $user, $password, $database) {

		// no resource yet
		$handle = FALSE;

		// regular connection --mask warning that popups in safe mode
		if(is_callable('mysqli_connect'))
			$handle = @mysqli_connect($host, $user, $password, $database);
		elseif(is_callable('mysql_connect')) {
			if(($handle = mysql_connect($host, $user, $password)) && !mysql_select_db($database, $handle))
				$handle = FALSE;
		} else
			exit('no support for MySQL'.BR);

		// end of job
		return $handle;

	}

	/**
	 * count selected rows
	 *
	 * @param resource
	 * @return TRUE on success, FALSE on failure
	 */
	public static function count(&$result) {
		if(!$result)
			return 0;
		elseif(is_callable('mysqli_num_rows'))
			return mysqli_num_rows($result);
		else
			return mysql_num_rows($result);
	}

	/**
	 * count tables in database
	 *
	 * @param string database name
	 * @param resource connection to the database server, if any
	 * @return int number of tables, or FALSE on failure
	 */
	public static function count_tables($name=NULL, $connection=NULL) {
		global $context;

		// sanity check
		if(!$name)
			$name = $context['database'];

		// use the default connection
		if(!$connection) {

			// we do need a connection to the database
			if(!isset($context['connection']) || !$context['connection'])
				return FALSE;

			$connection = $context['connection'];
		}

		// the query to list tables
		$query = 'SHOW TABLES';

		// count tables
		return SQL::query_count($query, TRUE, $connection);
	}

	/**
	 * debug a SQL query
	 *
	 * This function may be invoked explicitly during query optimization.
	 * To do that, invoke it instead of the standard SQL::query(...)
	 * and resulting data will be put in temporary/debug.txt for analysis.
	 *
	 * On SELECT this function queries the database two times: a first time to explain the statement,
	 * and second time to actually do the job.
	 *
	 * @param string the SQL query
	 * @param boolean optional TRUE to not report on any error
	 * @param resource connection to be considered, if any
	 * @return the resource returned by the database server, or the number of affected rows, or FALSE on error
	 */
	public static function debug($query, $silent=FALSE, $connection=NULL) {
		global $context;

		// allow for reference
		$result = FALSE;

		// on SELECT
		if(!strncmp($query, 'SELECT ', 7)) {

			// statement cannot be explained
			$query2 = 'EXPLAIN '.$query;
			if(!$result = SQL::query($query2, TRUE, $connection)) {
				Logger::remember('shared/sql.php', 'SQL::explain', SQL::error()."\n\n".$query, 'debug');
				return $result;
			}

			// turns results to text
			$at_first_line = TRUE;
			$header = $text = '';
			while($attributes = SQL::fetch($result)) {
				foreach($attributes as $name => $value) {
					if($at_first_line)
						$header .= $name."\t";
					$text .= $value."\t";
				}
				if($at_first_line) {
					$header .= "\n";
					$at_first_line = FALSE;
				}
				$text .= "\n";

			}

			// remember the outcome --special label split for debug
			Logger::remember('shared/sql.php', 'SQL:'.':debug', $query."\n\n".$header."\n".$text, 'debug');

		// other statement
		} else {

			// remember the query itself --sepcial label split for debug
			Logger::remember('shared/sql.php', 'SQL:'.':debug', $query, 'debug');

		}

		// actually do the job
		$output = SQL::query($query, $silent, $connection);
		return $output;

	}

	/**
	 * retrieve last error code, if any
	 *
	 * @param resource connection to the database server, if any
	 * @return int code of last error, or 0
	 */
	public static function errno($connection=NULL) {
		global $context;

		// use the default connection
		if(!$connection) {

			// we do need a connection to the database
			if(!isset($context['connection']) || !$context['connection'])
				return 0;

			$connection = $context['connection'];
		}

		if(is_callable('mysqli_errno'))
			return mysqli_errno($connection);
		else
			return mysql_errno($connection);
	}

	/**
	 * build standard error message
	 *
	 * @param resource connection to the database server, if any
	 * @return string or NULL
	 */
	public static function error($connection=NULL) {
		global $context;

		// use the default connection
		if(!$connection) {

			// we do need a connection to the database
			if(!isset($context['connection']) || !$context['connection'])
				return NULL;

			$connection = $context['connection'];
		}

		if(is_callable('mysqli_errno'))
			return mysqli_errno($connection).': '.mysqli_error($connection);
		else
			return mysql_errno($connection).': '.mysql_error($connection);
	}

	/**
	 * escape a string
	 *
	 * @param string to be escaped
	 * @return string safe version
	 */
	public static function escape($text) {
		global $context;

		// do not quote numbers
		if(!is_string($text))
			$text = (string)$text;

		// store binary Unicode, if possible
		if(isset($context['database_is_utf8']) && $context['database_is_utf8'] && is_callable('utf8', 'from_unicode'))
			$text = utf8::from_unicode($text);

		// we do need a connection to the database
		if(!isset($context['connection']) || !$context['connection'])
			return $text;

		// use MySQL built-in functions for strings
		elseif(is_callable('mysqli_real_escape_string'))
			$text = mysqli_real_escape_string($context['connection'], $text);
		elseif(is_callable('mysql_real_escape_string'))
			$text = mysql_real_escape_string($text, $context['connection']);
		else
			$text = mysql_escape_string($text);

		// allow access by reference
		return $text;
	}

	/**
	 * fetch next result as an associative array
	 *
	 * @param resource set of rows
	 * @return array related to next row, or FALSE if there is no more row
	 */
	public static function fetch(&$result) {
		if(is_bool($result))
			$output = FALSE;
		elseif(is_callable('mysqli_fetch_array'))
			$output = mysqli_fetch_array($result, MYSQLI_ASSOC);
		else
			$output = mysql_fetch_array($result, MYSQL_ASSOC);
		return $output;
	}

	/**
	 * fetch next field
	 *
	 * @param resource set of rows
	 * @return object related to next field, or FALSE if there is no more field
	 */
	public static function fetch_field(&$result) {
		if(is_callable('mysqli_fetch_field'))
			$output = mysqli_fetch_field($result);
		else
			$output = mysql_fetch_field($result);
		return $output;
	}

	/**
	 * fetch next result as a bare array
	 *
	 * @param resource set of rows
	 * @return array related to next row, or FALSE if there is no more row
	 */
	public static function fetch_row(&$result) {
		if(is_callable('mysqli_fetch_row'))
			$output = mysqli_fetch_row($result);
		else
			$output = mysql_fetch_row($result);
		return $output;
	}

	/**
	 * release a resource
	 *
	 * @param resource to be released
	 * @return TRUE on success, FALSE on failure
	 */
	public static function free(&$result) {
		if(is_callable('mysqli_free_result'))
			return mysqli_free_result($result);
		else
			return mysql_free_result($result);
	}

	/**
	 * get the id of the most recent item
	 *
	 * @param resource the database connection to look at
	 * @return int or FALSE
	 */
	public static function get_last_id($connection) {
		global $context;

		// use the default connection
		if(!isset($connection) || !$connection) {

			// we do need a connection to the database
			if(!isset($context['connection']) || !$context['connection'])
				return NULL;

			$connection = $context['connection'];
		}

		// query the database --turn the id to a string because of Unicode encoding
		if(is_callable('mysqli_insert_id'))
			return (string)mysqli_insert_id($connection);
		elseif(is_callable('mysql_insert_id'))
			return (string)mysql_insert_id($connection);

		// sorry
		return FALSE;
	}

	/**
	 * ensure a database exists
	 *
	 * @param string database name
	 * @param resource handle to server
	 * @return boolean TRUE or FALSE
	 */
	public static function has_database($name=NULL, $connection=NULL) {
		global $context;

		// sanity check
		if(!$name)
			$name = $context['database'];

		// use the default connection
		if(!isset($connection) || !$connection) {

			// we do need a connection to the database
			if(!isset($context['connection']) || !$context['connection'])
				return NULL;

			$connection = $context['connection'];
		}

		// ensure we have a database --order of parameters is inverted...
		if(is_callable('mysqli_select_db'))
			return mysqli_select_db($connection, $name);
		else
			return mysql_select_db($name, $connection);

	}

	/**
	 * check if a table exists
	 *
	 * @param string the name of the table to setup
	 * @return boolean TRUE or FALSE
	 */
	public static function has_table($table) {
		global $context;

		// sanity check
		if(!$table)
			return FALSE;

		// build the tables list only once
		static $tables;
		if(!isset($tables)) {
			$tables = array();
			$query = 'SHOW TABLES';
			if(!$rows = SQL::query($query))
				return '<p>'.SQL::error().'</p>';
			while($row = SQL::fetch_row($rows))
				$tables[] = strtolower($row[0]);
			SQL::free($rows);
		}

		// if the table does exist
		$target = strtolower($context['table_prefix'].$table);
		if(count($tables) && in_array($target, $tables))
			return TRUE;

		// no table yet
		return FALSE;
	}

	/**
	 * initialize connections to the database
	 *
	 * @return TRUE on success, FALSE on failure
	 */
	public static function initialize() {
		global $context;

		// no database parameters
		if(!isset($context['database_server'])	|| !isset($context['database_user']) || !isset($context['database_password']) || !isset($context['database']))
			;

		// attempt to connect to the database
		elseif(!$context['connection'] = SQL::connect($context['database_server'], $context['database_user'], $context['database_password'], $context['database'])) {

			// exit if batch mode
			if(!isset($_SERVER['REMOTE_ADDR']))
				exit(sprintf(i18n::s('Impossible to connect to %s.'), $context['database']));

			// else jump to the control panel, if not in it already
			if(!preg_match('/(\/control\/|\/included\/|setup|login\.php$)/i', $context['script_url']))
				Safe::redirect($context['url_to_home'].$context['url_to_root'].'control/');

		}

		// connect to the database for user records
		if(isset($context['users_database_server']) && $context['users_database_server']) {

			// additional connection for users table
			$context['users_connection'] = SQL::connect($context['users_database_server'], $context['users_database_user'], $context['users_database_password'], $context['users_database']);

		} elseif(isset($context['connection']))
			$context['users_connection'] = $context['connection'];

		// the table prefix
		if(!isset($context['table_prefix']))
			$context['table_prefix'] = 'yacs_';

		// sanity check
		if(!$context['connection'])
			return FALSE;

		// ensure we are talking utf8 to the database server
		$query = "SET NAMES 'utf8'";
		SQL::query($query);

		// detect utf8 database, if any
		if(!isset($_SESSION['database_is_utf8'])) {
			$_SESSION['database_is_utf8'] = FALSE;
			$query = "SHOW VARIABLES LIKE 'character_set_database'";
			if(($result = SQL::query_first($query)) && ($result['Value'] == 'utf8'))
				$_SESSION['database_is_utf8'] = TRUE;
		}

		// ask only once per session
		$context['database_is_utf8'] = $_SESSION['database_is_utf8'];

		// database ok
		return TRUE;

	}

	/**
	 * list tables in database
	 *
	 * @param string database name
	 * @param resource connection to the database server, if any
	 * @return int number of tables, or FALSE on failure
	 */
	public static function list_tables($name=NULL, $connection=NULL) {
		global $context;

		// sanity check
		if(!$name)
			$name = $context['database'];

		// use the default connection
		if(!$connection) {

			// we do need a connection to the database
			if(!isset($context['connection']) || !$context['connection'])
				return FALSE;

			$connection = $context['connection'];
		}

		// the query to list tables
		$query = 'SHOW TABLES';

		// list tables
		$output = SQL::query($query, TRUE, $connection);
		return $output;
	}

	/**
	 * ping the database
	 *
	 * This function has to be activated from time to time to keep a live connection to the SQL server.
	 *
	 * @param resource handle to server, if any
	 * @return boolean TRUE on success, FALSE when the database is not reachable
	 *
	 */
	public static function ping($connection=NULL) {
		global $context;

		// we do need a connection to the database
		if(!$connection && isset($context['connection']))
			$connection = $context['connection'];

		// we do need a connection to the database
		if(!$connection)
			return FALSE;

		// reopen a connection if database is not reachable anymore
		if(is_callable('mysqli_ping'))
			$reached = mysqli_ping($connection);
		else
			$reached = mysql_ping($connection);

		// also ping the database for users, if any
		if(isset($context['users_connection']) && $context['users_connection'] && ($context['users_connection'] !== $connection)) {

			if(is_callable('mysqli_ping'))
				mysqli_ping($context['users_connection']);
			else
				mysql_ping($context['users_connection']);

		}

		// job done
		return $reached;

	}

	/**
	 * process statements from a file
	 *
	 * Page is updated on error.
	 *
	 * @param string file name
	 * @return integer number of processed statements, or FALSE on error
	 */
	public static function process($name) {
		global $context;

		// uncompress while reading
		if(!$handle = gzopen($name, 'rb')) {
			$context['text'] .= '<p>'.sprintf(i18n::s('Impossible to read %s.'), $name)."</p>\n";
			return FALSE;
		}

		// ensure enough execution time
		Safe::set_time_limit(30);

		// process every line
		$query = '';
		$queries = 0;
		$count = 0;
		$errors = 0;
		while(!gzeof($handle)) {

			// lines can be quite big...
			$line = gzgets($handle, 262144);

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
			} else
				$execute = FALSE;


			// a statement
			$query .= trim($line);

			// end of statement - process it
			if($query && ($execute || gzeof($handle))) {

				// execute the statement
				if(!SQL::query($query, TRUE) && SQL::errno()) {
					$errors++;
					if($errors < 50) // display the faulty query on first errors
						$context['text'] .= '<p>'.$here.': '.$query.BR.SQL::error()."</p>\n";
					else // only report line in error afterwards
						$context['text'] .= '<p>'.$here.': '.SQL::error()."</p>\n";
				}

				// next query
				$query = '';
				$queries++;

				// ensure we have enough time
				if(!($queries%50))
					Safe::set_time_limit(30);

			}
		}

		// the number of processed queries
		return $queries;

	}

	/**
	 * purge idle space
	 *
	 * This function OPTIMIZEs tables that may create overheads because of
	 * frequent deletions, including: cache, links, members, messages,
	 * notifications, values, versions, visits.
	 *
	 * Last purge is recorded as value 'sql.tick'.
	 *
	 * @param boolean optional TRUE to not report on any error
	 * @return a string to be displayed in resulting page, if any
	 */
	public static function purge($silent=FALSE) {
		global $context;

		// useless if we don't have a valid database connection
		if(!$context['connection'])
			return;

		// remember start time
		$stamp = get_micro_time();

		// get date of last tick
		include_once $context['path_to_root'].'shared/values.php';
		$record = Values::get_record('sql.tick', NULL_DATE);

		// wait at least 8 hours = 24*3600 seconds between ticks
		if(isset($record['edit_date']))
			$target = SQL::strtotime($record['edit_date']) + 8*3600;
		else
			$target = time();

		// request to be delayed
		if($target > time())
			return 'shared/sql.php: wait until '.gmdate('r', $target).' GMT'.BR;

		// recover unused bytes
		$query = 'OPTIMIZE TABLE '.SQL::table_name('cache')
			.', '.SQL::table_name('links')
			.', '.SQL::table_name('members')
			.', '.SQL::table_name('messages')
			.', '.SQL::table_name('notifications')
			.', '.SQL::table_name('values')
			.', '.SQL::table_name('versions')
			.', '.SQL::table_name('visits');
		$result = SQL::query($query, $silent);

		// remember tick date and resulting text
		Values::set('sql.tick', 'purge');

		// compute execution time
		$time = round(get_micro_time() - $stamp, 2);

		// report on work achieved
		if($result)
			return 'shared/sql.php: unused bytes have been recovered ('.$time.' seconds)'.BR;
		else
			return 'shared/sql.php: nothing to recover ('.$time.' seconds)'.BR;
	}

	/**
	 * query the database
	 *
	 * This function populates the error context, where applicable.
	 *
	 * @param string the SQL query
	 * @param boolean optional TRUE to not report on any error
	 * @param resource connection to be considered, if any
	 * @return the resource returned by the database server, or the number of affected rows, or FALSE on error
	 */
	public static function query(&$query, $silent=FALSE, $connection=NULL) {
		global $context;

		// allow for reference
		$output = FALSE;

		// use the default connection
		if(!$connection) {

			// we do need a connection to the database
			if(!isset($context['connection']) || !$context['connection'])
				return $output;

			$connection = $context['connection'];
		}

		// reopen a connection if database is not reachable anymore
		if((get_micro_time() - $context['start_time'] > 1.0) && !SQL::ping($connection)) {

			// remember the error, if any -- we may not have a skin yet
			if(!$silent) {
				if(is_callable(array('Skin', 'error')))
					Logger::error(i18n::s('Connection to the database has been lost'));
				else
					die(i18n::s('Connection to the database has been lost'));
			}

			// query cannot be processed
			return $output;

		}

		// ensure enough execution time
		Safe::set_time_limit(30);

		// profile database requests
		$query_stamp = get_micro_time();

		// do the job
		if(is_callable('mysqli_query'))
			$result = mysqli_query($connection, $query);
		else
			$result = mysql_query($query, $connection);

		// finalize result
		if($result) {

			// provide more than a boolean result
			if($result === TRUE) {
				if(is_callable('mysqli_affected_rows'))
					$result = mysqli_affected_rows($connection);
				else
					$result = mysql_affected_rows($connection);
			}

			// flag slow requests
			$duration = (get_micro_time() - $query_stamp);
			if(($duration >= 0.5) && ($context['with_debug'] == 'Y'))
				Logger::remember('shared/sql.php', 'SQL::query() slow request', $duration."\n\n".$query, 'debug');

			// return the set of selected rows
			return $result;
		}

		// remember the error, if any
		if(SQL::errno($connection)) {

			// display some error message
			if(!$silent) {
				if(is_callable(array('Skin', 'error')))
					Logger::error($query.'<br />'.SQL::error($connection));
				else
					die($query.'<br />'.SQL::error($connection));
			}

			// log the error at development host
			if($context['with_debug'] == 'Y')
				Logger::remember('shared/sql.php', 'SQL::query()', SQL::error($connection)."\n\n".$query, 'debug');

		}


		// no valid result
		return $output;
	}

	/**
	 * query the database and count rows
	 *
	 * This function populates the error context, where applicable.
	 *
	 * @param string the SQL query
	 * @param boolean optional TRUE to not report on any error
	 * @param resource connection to be considered, if any
	 * @return int number of rows, or NULL on error
	 */
	public static function query_count(&$query, $silent=FALSE, $connection=NULL) {
		global $context;

		// use the default connection
		if(!$connection) {

			// we do need a connection to the database
			if(!isset($context['connection']) || !$context['connection'])
				return NULL;

			$connection = $context['connection'];
		}

		// submit statement to the database
		if(!$result = SQL::query($query, $silent, $connection))
			return NULL;

		// count rows
		$count = SQL::count($result);
		SQL::free($result);
		return $count;
	}

	/**
	 * query the database and return first item
	 *
	 * This function populates the error context, where applicable.
	 *
	 * @param string the SQL query
	 * @param boolean optional TRUE to not report on any error
	 * @param resource connection to be considered, if any
	 * @return array containing first item, or NULL on error
	 */
	public static function query_first(&$query, $silent=FALSE, $connection=NULL) {
		global $context;

		// allow for reference
		$output = NULL;

		// use the default connection
		if(!$connection) {

			// we do need a connection to the database
			if(!isset($context['connection']) || !$context['connection'])
				return $output;

			$connection = $context['connection'];
		}

		// submit statement to the database
		if(!$result = SQL::query($query, $silent, $connection))
			return $output;

		// empty list
		if(!SQL::count($result))
			return $output;

		// the first item of the list
		$output = SQL::fetch($result);
		SQL::free($result);
		return $output;
	}

	/**
	 * query the database and return result as a scalar
	 *
	 * Actually, this function returns the first column of the first row
	 * generated by the submitted query.
	 *
	 * This function populates the error context, where applicable.
	 *
	 * @param string the SQL query
	 * @return mixed containing query result, or NULL on error
	 */
	public static function query_scalar(&$query, $silent=FALSE, $connection=NULL) {
		global $context;

		// allow for reference
		$output = NULL;

		// use the default connection
		if(!$connection) {

			// we do need a connection to the database
			if(!isset($context['connection']) || !$context['connection'])
				return $output;

			$connection = $context['connection'];
		}

		// submit statement to the database
		if(!$result = SQL::query($query, $silent, $connection))
			return $output;

		// empty list
		if(!SQL::count($result))
			return $output;

		// get first column of first row
		if(($row = SQL::fetch_row($result)) && isset($row[0]))
			$output = $row[0];

		// job done
		SQL::free($result);
		return $output;
	}

	/**
	 * Create or alter the structure of one table
	 *
	 * @param string the name of the table to setup
	 * @param array of $field_name => $field_declaration
	 * @param array of $index_name => $index_declaration
	 * @return a text string to print
	 */
	public static function setup_table($table, $fields, $indexes) {
		global $context;

		// sanity check
		if(!$table)
			return '';

		// if the table does not exist
		if(!SQL::has_table($table)) {

			// create it
			$query = "CREATE TABLE ".SQL::table_name($table)." ( ";
			$count = 0;
			foreach($fields as $field => $definition) {
				if($count++)
					$query .= ", ";
				$query .= '`'.$field.'` '.$definition;
			}
			foreach($indexes as $index => $definition) {
				if($count++)
					$query .= ", ";
				$query .= $index.' '.$definition;
			}
			$query .= " )";

		// else if the table exists
		} else {

			// check its structure
			$query = "ALTER TABLE ".SQL::table_name($table)." ";

			// analyse table structure
			$query2 = "DESCRIBE ".SQL::table_name($table);
			if(!$result = SQL::query($query2))
				return '<p>'.Logger::error_pop()."</p>\n";

			// build the list of fields
			while($row = SQL::fetch($result))
				$actual[] = $row['Field'];

			// check all fields
			$count = 0;
			foreach($fields as $field => $definition) {
				if($count++)
					$query .= ", ";
				if(in_array($field, $actual))
					$query .= "MODIFY";
				else
					$query .= "ADD";
				$query .= ' `'.$field.'` '.$definition;
			}

			// drop the primary index
			$query .= ", DROP PRIMARY KEY";

			// list existing indexes
			$query2 = "SHOW INDEX FROM ".SQL::table_name($table);
			if(!$result = SQL::query($query2))
				return '<p>'.Logger::error_pop()."</p>\n";

			// drop other indexes
			while($row = SQL::fetch($result)) {
				if(($row['Seq_in_index'] == 1) && ($row['Key_name'] != 'PRIMARY'))
					$query .= ', DROP INDEX '.$row['Key_name'];
			}
			SQL::free($result);

			// build new indexes
			foreach($indexes as $index => $definition) {
				$query .= ", ADD ".$index.' '.$definition;
			}
		}

		// execute the query
		if(SQL::query($query) !== FALSE) {

			// message to the user
			$text = BR.i18n::s('The table')." '".$table."'";

			// it's a success
			if(strpos($query, 'CREATE') === 0)
				$text .= ' '.i18n::s('has been created');
			else
				$text .= ' '.i18n::s('has been updated');

			// ensure utf8 character set for this table
			$query = "ALTER TABLE ".SQL::table_name($table)."  DEFAULT CHARACTER SET utf8";
			if(SQL::query($query) !== FALSE)
				$text .= ' (utf8)';

			// silently analyze table
			$query = "ANALYZE TABLE ".SQL::table_name($table);
			if( ($result = SQL::query($query))
				&& ($row = SQL::fetch($result))
				&& ($row['Msg_type'] == 'status') ) {

				$text .= ' '.i18n::s('and analyzed');
				SQL::free($result);

			}

			// silently optimize table
			$query = "OPTIMIZE TABLE ".SQL::table_name($table);
			if( ($result = SQL::query($query))
				&& ($row = SQL::fetch($result))
				&& ($row['Msg_type'] == 'status') ) {

				$text .= ' '.i18n::s('and optimized');
				SQL::free($result);

			}

		// houston, we got a problem
		} else {

			// message to the user
			$text = '<p>'.sprintf(i18n::s('ERROR for the table %s'), $table).BR.$query.BR.SQL::error().'</p>';

		}

		return $text;
	}

	/**
	 * convert a stamp to an integer
	 *
	 * Use this function to convert dates fetched from the database to
	 * time stamps.
	 *
	 * Since dates saved in the database are always aligned to UTC time zone,
	 * this function also adjusts the provided string to the server time zone.
	 *
	 * @param string a stamp written on the 'YYYY-MM-DD HH:MM:SS' model
	 * @return int the number of seconds since 1st January of 1970
	 */
	public static function strtotime($stamp) {
		return gmmktime(intval(substr($stamp, 11, 2)), intval(substr($stamp, 14, 2)), intval(substr($stamp, 17, 2)), intval(substr($stamp, 5, 2)), intval(substr($stamp, 8, 2)), intval(substr($stamp, 0, 4)));
	}

	/**
	 * create a table name
	 *
	 * Note: this function handles properly the specific case of the table for user profiles.
	 * However, you may also have to use a different database connection for queries to this table.
	 *
	 * @param string bare table name
	 * @return string an extensive name suitable for MySQL requests
	 */
	public static function table_name($name) {
		global $context;

		// maybe we are looking for user records elsewhere
		if($name == 'users') {
			if(isset($context['users_table_prefix']) && $context['users_table_prefix'])
				return $context['users_table_prefix'].$name;
			elseif(isset($context['table_prefix']))
				return $context['table_prefix'].$name;
			else
				return $name.'`';

		// a regular table
		} elseif(isset($context['table_prefix']))
			return $context['table_prefix'].$name;
		else
			return $name;
	}

	/**
	 * count of rows in a table
	 *
	 * @param string name of table to analyze
	 * @return NULL, or an array(count, min_date, max_date)
	 */
	public static function table_stat($table) {
		global $context;

		// accept foreign user profiles
		if($table == 'users')
			$connection = $context['users_connection'];

		// regular tables
		else
			$connection = $context['connection'];

		// query the database
		$query = "SELECT count(*), min(edit_date), max(edit_date) FROM ".SQL::table_name($table);
		if($result = SQL::query($query))
			if($row = SQL::fetch_row($result))
				return $row;

		return NULL;
	}

	/**
	 * get MySQL database version
	 *
	 * @return string mentioning the back-end version number, or NULL on error
	 */
	public static function version($connection=NULL) {
		global $context;

		// use the default connection
		if(!$connection) {

			// we do need a connection to the database
			if(!isset($context['connection']) || !$context['connection'])
				return NULL;

			$connection = $context['connection'];
		}

		// get database server version
		if(is_callable('mysqli_get_server_info'))
			return mysqli_get_server_info($connection);
		if(is_callable('mysql_get_server_info'))
			return mysql_get_server_info($connection);

		return NULL;

	}
}

// required by MySQL v5
if(!defined('NULL_DATE'))
	define('NULL_DATE', '0000-00-00 00:00:00');

// legacy support
if(!function_exists('table_name')) {

	function table_name($name) {
		return SQL::table_name($name);
	}

}


?>
