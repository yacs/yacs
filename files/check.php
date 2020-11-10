<?php
/**
 * check the integrity of the database for files
 *
 * This page is used to check and update the database. Its usage is restricted to associates.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// include libraries
include_once '../shared/global.php';
include_once 'files.php';

// load the skin
load_skin('files');

// do not crawl this page
$context->sif('robots','noindex');

// the path to this page
$context['path_bar'] = array( 'files/' => i18n::s('Files') );

// the title of the page
$context['page_title'] = i18n::s('Maintenance');

// the user has to be an associate
if(!Surfer::is_associate()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

	// forward to the index page
	$menu = array('files/' => i18n::s('Files'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// look for orphans
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'orphans')) {

	// scan files
	$context['text'] .= Skin::build_block(sprintf(i18n::s('Analyzing table %s...'), SQL::table_name('files')), 'subtitle');

	// limit the number of scans items
	$count = 0;
	$query = "SELECT id, anchor, title FROM ".SQL::table_name('files')."
		ORDER BY anchor LIMIT 0, 500000";
	if(!($result = SQL::query($query))) {
		$context['text'] .= Logger::error_pop().BR."\n";
		return;

	// parse the whole list
	} else {

		// fetch one anchor and the linked member
		$errors_count = 0;
		while($row = SQL::fetch($result)) {

			// animate user screen and take care of time
			$count++;
			if(!($count%100)) {
				$context['text'] .= sprintf(i18n::s('%d records have been processed'), $count).BR."\n";

				// ensure enough execution time
				Safe::set_time_limit(30);

			}

			// check that the anchor exists, if any
			if($row['anchor'] && !Anchors::get($row['anchor'])) {
				$context['text'] .= sprintf(i18n::s('Orphan: %s'), 'file '.Skin::build_link(Files::get_url($row['id']), $row['id'].' '.$row['title'])).BR."\n";
				if(++$errors_count >= 5) {
					$context['text'] .= i18n::s('Too many successive errors. Aborted').BR."\n";
					break;
				}

			} else
				$errors_count = 0;

		}
	}

	// ending message
	$context['text'] .= sprintf(i18n::s('%d records have been processed'), $count).BR."\n";

	// display the execution time
	$time = round(get_micro_time() - $context['start_time'], 2);
	$context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

	// forward to the index page
	$menu = array('files/' => i18n::s('Files'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

} elseif (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'missing')) {


	// scan up to 10000 items
	$count = 0;
	$query = "SELECT id, anchor, file_name FROM ".SQL::table_name('files')."
		ORDER BY anchor LIMIT 0, 500000";
	if(!($result = SQL::query($query))) {
		$context['text'] .= Logger::error_pop().BR."\n";
		return;

	// parse the whole list
	} else {

		// fetch one anchor and the linked member
		$errors_count = 0;
		while($row = SQL::fetch($result)) {

			// animate user screen and take care of time
			$count++;
			if(!($count%100)) {
				$context['text'] .= sprintf(i18n::s('%d records have been processed'), $count).BR."\n";

				// ensure enough execution time
				Safe::set_time_limit(30);

			}

			$file_from = Files::get_path($row['anchor']);

			if(!file_exists($context['path_to_root'].$file_from.'/'.$row['file_name'])) {
				$errors_count++;
				$anchor = Anchors::get($row['anchor']);

				$context['text'] .= sprintf(i18n::s('Missing: %s'), 'file '.Skin::build_link(Files::get_url($row['id']), $row['id'].' '.$row['file_name'])).' '.i18n::s('in').' '.((is_object($anchor))?Skin::build_link($anchor->get_url(), $row['anchor']):'').BR."\n";
			}

		}

	}

	// ending message
	$context['text'] .= sprintf(i18n::s('%d records have been processed'), $count).BR."\n";
	$context['text'] .= sprintf(i18n::s('%d missing files'), $errors_count).BR."\n";

	// display the execution time
	$time = round(get_micro_time() - $context['start_time'], 2);
	$context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

	// forward to the index page
	$menu = array('images/' => i18n::s('Images'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');


// which check?
} else {
	// the splash message
	$context['text'] .= '<p>'.i18n::s('Please select the action to perform.')."</p>\n";

	// the form
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form">';

	// look for orphan records
	$context['text'] .= '<p><input type="radio" name="action" id="action" value="orphans" /> '.i18n::s('Look for orphan records').'</p>';

	// look for missing files
	$context['text'] .= '<p><input type="radio" name="action" value="missing" /> '.i18n::s('Look for missing files').'</p>';

	// the submit button
	$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Start')).'</p>'."\n";

	// end of the form
	$context['text'] .= '</form>';

	// set the focus on the button
	Page::insert_script('$("#action").focus();');

}

// render the skin
render_skin();
?>
