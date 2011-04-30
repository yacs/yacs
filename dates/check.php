<?php
/**
 * check the integrity of the database for dates
 *
 * This page is used to check and update the database. Its usage is restricted to associates.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// include libraries
include_once '../shared/global.php';
include_once 'dates.php';

// load the skin
load_skin('dates');

// the path to this page
$context['path_bar'] = array( 'dates/' => i18n::s('Dates') );

// the title of the page
$context['page_title'] = i18n::s('Maintenance');

// the user has to be an associate
if(!Surfer::is_associate()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

	// forward to the index page
	$menu = array('dates/' => i18n::s('Dates'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// look for unused dates
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'unused')) {

	// scan dates
	$context['text'] .= Skin::build_block(sprintf(i18n::s('Analyzing table %s...'), SQL::table_name('dates')), 'subtitle');

	// scan up to 10000 items
	$count = 0;
	$query = "SELECT id, anchor, date_stamp FROM ".SQL::table_name('dates')
		." ORDER BY anchor LIMIT 0, 10000";
	if(!($result =& SQL::query($query))) {
		$context['text'] .= Logger::error_pop().BR."\n";

	// parse the whole list
	} else {

		// fetch one anchor and the linked member
		$errors_count = 0;
		while($row =& SQL::fetch($result)) {

			// animate user screen and take care of time
			$count++;
			if(!($count%100)) {
				$context['text'] .= sprintf(i18n::s('%d records have been processed'), $count).BR."\n";

				// ensure enough execution time
				Safe::set_time_limit(30);

			}

			// look only in articles
			if(preg_match('/article:(.*)/', $row['anchor'], $matches) && ($article =& Articles::get($matches[1]))) {

				// check that the description has a reference to this date, or that overlay date has been used
				if(!preg_match('/\[date='.$row['id'].'.*\]/', $article['description']) && !preg_match('/date_stamp/', $article['overlay'])) {
					$context['text'] .= sprintf(i18n::s('Unused: %s'), 'date '.Skin::build_link(Dates::get_url($row['id']), $row['id'].' '.$row['date_stamp'])).BR."\n";
					if(++$errors_count >= 25) {
						$context['text'] .= i18n::s('Too many successive errors. Aborted').BR."\n";
						break;
					}

				} else
					$errors_count = 0;
			}
		}
	}

	// ending message
	$context['text'] .= sprintf(i18n::s('%d records have been processed'), $count).BR."\n";

	// display the execution time
	$time = round(get_micro_time() - $context['start_time'], 2);
	$context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

	// forward to the index page
	$menu = array('dates/' => i18n::s('Dates'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// look for orphans
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'orphans')) {

	// scan dates
	$context['text'] .= Skin::build_block(sprintf(i18n::s('Analyzing table %s...'), SQL::table_name('dates')), 'subtitle');

	// scan up to 10000 items
	$count = 0;
	$query = "SELECT id, anchor, date_stamp FROM ".SQL::table_name('dates')
		." ORDER BY anchor LIMIT 0, 10000";
	if(!($result =& SQL::query($query))) {
		$context['text'] .= Logger::error_pop().BR."\n";
		return;

	// parse the whole list
	} else {

		// fetch one anchor and the linked member
		$errors_count = 0;
		while($row =& SQL::fetch($result)) {

			// animate user screen and take care of time
			$count++;
			if(!($count%100)) {
				$context['text'] .= sprintf(i18n::s('%d records have been processed'), $count).BR."\n";

				// ensure enough execution time
				Safe::set_time_limit(30);

			}

			// check that the anchor exists, if any
			if($row['anchor'] && !Anchors::get($row['anchor'])) {
				$context['text'] .= sprintf(i18n::s('Orphan: %s'), 'date '.Skin::build_link(Dates::get_url($row['id']), $row['id'].' '.$row['date_stamp'])).BR."\n";
				if(++$errors_count >= 25) {
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
	$menu = array('dates/' => i18n::s('Dates'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// which check?
} else {

	// the splash message
	$context['text'] .= '<p>'.i18n::s('Please select the action to perform.')."</p>\n";

	// the form
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form">';

	// look for unused dates
	$context['text'] .= '<p><input type="radio" name="action" id="action" value="unused" /> '.i18n::s('Look for unused dates').'</p>';

	// look for orphan records
	$context['text'] .= '<p><input type="radio" name="action" value="orphans" /> '.i18n::s('Look for orphan records').'</p>';

	// the submit button
	$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Start')).'</p>'."\n";

	// end of the form
	$context['text'] .= '</form>';

	// set the focus on the button
	$context['text'] .= JS_PREFIX
		.'$("#action").focus();'."\n"
		.JS_SUFFIX;

}

// render the skin
render_skin();

?>
