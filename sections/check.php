<?php
/**
 * check the database integrity for sections
 *
 * This page is used to check and update the database. Its usage is restricted to associates.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// include the global declarations
include_once '../shared/global.php';

// load the skin
load_skin('sections');

// the path to this page
$context['path_bar'] = array( 'sections/' => i18n::s('Site map') );

// the title of the page
$context['page_title'] = i18n::s('Maintenance');

// the user has to be an associate
if(!Surfer::is_associate()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

	// forward to the index page
	$menu = array('sections/' => i18n::s('Site map'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// look for handles
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'handles')) {

	// scan sections
	$context['text'] .= Skin::build_block(sprintf(i18n::s('Analyzing table %s...'), SQL::table_name('sections')), 'title');

	// scan up to 10000 sections
	$count = 0;
	$query = "SELECT * FROM ".SQL::table_name('sections')
		." WHERE LENGTH(handle) < 8"
		." ORDER BY anchor LIMIT 0, 10000";

	// parse the whole list
	if($result = SQL::query($query)) {

		// retrieve the id and a printable label
		$errors_count = 0;
		while($row = SQL::fetch($result)) {

			// set a new handle
			$handle = md5(mt_rand().$row['id'].$row['handle'].$row['title']);
			$query = "UPDATE ".SQL::table_name('sections')
				." SET handle = '".$handle."'"
				." WHERE id = ".$row['id'];
			SQL::query($query);

			// report to the surfer
			$count++;
			$context['text'] .= sprintf(i18n::s('Handle: %s'), 'section '.Skin::build_link(Sections::get_permalink($row), $row['id'].' '.$row['title'], 'section')).BR."\n";


			// ensure enough execution time
			if(!($count%100))
				Safe::set_time_limit(30);
		}
	}

	// ending message
	$context['text'] .= sprintf(i18n::s('%d records have been processed'), $count).BR."\n";

	// display the execution time
	$time = round(get_micro_time() - $context['start_time'], 2);
	$context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

	// forward to the index page
	$menu = array('sections/' => i18n::s('Site map'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// look for orphans
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'orphans')) {

	// scan sections
	$context['text'] .= Skin::build_block(sprintf(i18n::s('Analyzing table %s...'), SQL::table_name('sections')), 'title');

	// scan up to 10000 sections
	$count = 0;
	$query = "SELECT id, anchor, title FROM ".SQL::table_name('sections')
		." ORDER BY anchor LIMIT 0, 10000";

	// parse the whole list
	if($result = SQL::query($query)) {

		// retrieve the id and a printable label
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
				$context['text'] .= sprintf(i18n::s('Orphan: %s'), 'section '.Skin::build_link(Sections::get_permalink($row), $row['id'].' '.$row['title'], 'section')).BR."\n";
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
	$menu = array('sections/' => i18n::s('Site map'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// which check?
} else {

	// the splash message
	$context['text'] .= '<p>'.i18n::s('Please select the action to perform.')."</p>\n";

	// the form
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form">';

	// look for orphan items
	$context['text'] .= '<p><input type="radio" name="action" id="action" value="orphans" /> '.i18n::s('Look for orphan records').'</p>';

	// ensure that each item has a unique handle
	$context['text'] .= '<p><input type="radio" name="action" id="action" value="handles" /> '.i18n::s('Ensure that each item has a unique handle').'</p>';

	// the submit button
	$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Start')).'</p>'."\n";

	// end of the form
	$context['text'] .= '</form>';

	// set the focus on the button
	$context['text'] .= JS_PREFIX
		.'$("#action").focus();'."\n"
		.JS_SUFFIX."\n";

}

// render the skin
render_skin();

?>
