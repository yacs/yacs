<?php
/**
 * a script to update content of one single table in the database
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'tables.php';

// load the skin
load_skin('tables');

// the path to this page
$context['path_bar'] = array( 'tables/' => i18n::s('Tables') );

// the title of the page
$context['page_title'] = i18n::s('Import table content');

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// anonymous users are invited to log in or to register
} elseif(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('tables/import.php'));

// only associates can proceed
elseif(!Surfer::is_associate()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

	// forward to the control panel
	$menu = array('control/' => i18n::s('Control Panel'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// import data
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'import')) {

	// default delimiter
	if(!isset($_REQUEST['field_delimiter']))
		$delimiter = "\t";
	else switch($_REQUEST['field_delimiter']) {
		default:
		case 'tab':
			$delimiter = "\t";
			break;
		case 'colon':
			$delimiter = ';';
			break;
		case 'comma':
			$delimiter = ',';
			break;
		case 'pipe':
			$delimiter = '|';
			break;
	}

	// default enclosure
	if(!isset($_REQUEST['field_enclosure']))
		$enclosure = '"';
	else switch($_REQUEST['field_enclosure']) {
		default:
		case 'double':
			$enclosure = '"';
			break;
		case 'single':
			$enclosure = "'";
			break;
	}

	// no table name has been provided
	if(!$_REQUEST['table_name'])
		$context['text'] .= '<p>'.i18n::s('No table name has been provided.')."</p>\n";

	// no file has been uploaded
	elseif(!$_FILES['upload']['name'] || ($_FILES['upload']['name'] == 'none'))
		$context['text'] .= '<p>'.i18n::s('Nothing has been received.')."</p>\n";

	// no bytes have been transmitted
	elseif(!$_FILES['upload']['size']) {
		$context['text'] .= '<p>'.i18n::s('Nothing has been received.')."</p>\n";

	// check provided upload name
	} elseif(!Safe::is_uploaded_file($_FILES['upload']['tmp_name']))
		$context['text'] .= '<p>'.i18n::s('Possible file attack.')."</p>\n";

	// open the file for reading
	elseif(!$handle = Safe::fopen($_FILES['upload']['tmp_name'], 'rb'))
		$context['text'] .= '<p>'.sprintf(i18n::s('Impossible to read %s.'), $_FILES['upload']['tmp_name'])."</p>\n";

	// read the first line, with headers
	elseif(!$tokens = fgetcsv($handle, 2048, $delimiter, $enclosure))
		$context['text'] .= '<p>'.sprintf(i18n::s('No headers to read from %s.'), $_FILES['upload']['name'])."</p>\n";

	// process statements
	else {

		// importing data
		$context['text'] .= '<p>'.sprintf(i18n::s('Importing data into %s from %s...'), $_REQUEST['table_name'], $_FILES['upload']['name'])."</p>\n";

		// ensure enough execution time
		Safe::set_time_limit(30);

		// drop the table, if it already exists
		$query = "DROP TABLE IF EXISTS ".SQL::escape($_REQUEST['table_name']);
		SQL::query($query);

		// create a table
		$query = "CREATE TABLE ".SQL::escape($_REQUEST['table_name'])." (\n";

		// use provided headers
		$headers = '';
		$index = 0;
		foreach($tokens as $token) {

			// make the token acceptable by the database engine
			$token = trim(str_replace(array(' ', '/', '\\', '.'), '_', $token));

			// ensure each column has a name
			if(!$token)
				continue;

			// separate items
			if($index++) {
				$query .= ",\n";
				$headers .= ', ';
			}

			// encode column names
			$query .= ' `'.SQL::escape($token).'` TEXT';
			$headers .= '`'.SQL::escape($token).'`';
		}

		// finalize the statement
		$query .= "\n) TYPE=MyISAM";

		// actual table creation
		SQL::query($query);

		// process every other line
		$queries = 0;
		$count = 0;
		while($tokens = fgetcsv($handle, 2048, $delimiter, $enclosure)) {

			// insert one record at a time
			$query = "INSERT INTO ".SQL::escape($_REQUEST['table_name'])." (".$headers.") VALUES (";

			// use all provided tokens
			$index = 0;
			foreach($tokens as $token) {
				if($index++)
					$query .= ', ';
				$query .= "'".SQL::escape($token)."'";
			}

			// finalize the statement
			$query .= ')';

			// execute the statement
			if(!SQL::query($query, TRUE) && SQL::errno())
				$context['text'] .= '<p>'.$here.': '.$query.BR.SQL::error()."</p>\n";
			$queries++;

			// ensure we have enough time
			if(!($queries%50))
				Safe::set_time_limit(30);

		}

		// clear the cache
		Cache::clear();

		// report of script data
		$time = round(get_micro_time() - $context['start_time'], 2);
		$context['text'] .= '<p>'.sprintf(i18n::s('%d SQL statements have been processed in %.2f seconds.'), $queries, $time).'</p>';

		// remember this in log as well
		Logger::remember('tables/import.php', 'Data has been imported into '.$_REQUEST['table_name'], $queries.' SQL statements have been processed in '.$time.' seconds.');

	}

// prepare the import
} else {

	// the splash label
	$context['text'] .= '<p>'.i18n::s('This script allows for the upload of data to some table of the database.')."</p>\n";

	// the form to upload a file
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" enctype="multipart/form-data"><div>'
		.'<input type="hidden" name="action" value="import" />';

	// encode fields
	$fields = array();

	// select a file
	$label = i18n::s('File');
	$input = '<input type="file" name="upload" id="upload" size="30" accesskey="i" title="'.encode_field(i18n::s('Press to select a local file')).'" />'
		.' (&lt;&nbsp;'.$context['file_maximum_size'].i18n::s('bytes').')';
	$hint = i18n::s('Please select a CSV file (.csv or .xls)');
	$fields[] = array($label, $input, $hint);

	// table name
	$label = i18n::s('Table name');
	$input = '<input type="text" name="table_name" size="45" maxlength="128" accesskey="n" value="yacs_import" />';
	$hint = i18n::s('Table will be dropped and re-created');
	$fields[] = array($label, $input, $hint);

	// fields delimiter
	$label = i18n::s('Field delimiter');
	$input = '<input type="radio" name="field_delimiter" value="tab" checked="checked" /> '.i18n::s('Tabulation character')
		.BR."\n".'<input type="radio" name="field_delimiter" value="comma" /> '.i18n::s('Comma character ","')."\n"
		.BR."\n".'<input type="radio" name="field_delimiter" value="colon" /> '.i18n::s('Colon character ";"')."\n"
		.BR."\n".'<input type="radio" name="field_delimiter" value="pipe" /> '.i18n::s('Pipe character "|"')."\n";
	$fields[] = array($label, $input);

	// fields enclosure
	$label = i18n::s('Field enclosure');
	$input = '<input type="radio" name="field_enclosure" value="double" checked="checked" /> '.i18n::s('Double quote character')
		.BR."\n".'<input type="radio" name="field_enclosure" value="single" /> '.i18n::s('Single quote character')."\n";
	$fields[] = array($label, $input);

	// build the form
	$context['text'] .= Skin::build_form($fields);

	// the submit button
	$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Import data'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

	// end of the form
	$context['text'] .= '</div></form>';

	// this may take several minutes
	$context['text'] .= i18n::s('When you will click on the button the server will be immediately requested to proceed. However, because of the so many things to do on the back-end, you may have to wait for minutes before getting a response displayed. Thank you for your patience.');

}

// render the skin
render_skin();

?>
