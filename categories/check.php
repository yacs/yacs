<?php
/**
 * check the database integrity for categories
 *
 * This page is used to check and update the database. Its usage is restricted to associates.
 * Following commands have been implemented:
 *
 * - Remember publication dates
 * - Rebuild title paths
 * - Look for orphans
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Thierry Pinelli (ThierryP)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// include the global declarations
include_once '../shared/global.php';
include_once 'categories.php';

// load the skin
load_skin('categories');

// the path to this page
$context['path_bar'] = array( 'categories/' => i18n::s('Categories') );

// the title of the page
$context['page_title'] = i18n::s('Maintenance');

// the user has to be an associate
if(!Surfer::is_associate()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

	// forward to the index page
	$menu = array('categories/' => i18n::s('Categories'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// remember publication dates
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'remember')) {

	// scan categories
	$context['text'] .= Skin::build_block(sprintf(i18n::s('Analyzing table %s...'), SQL::table_name('articles')), 'title');

	// scan only published articles
	$where = 'NOT ((articles.publish_date is NULL) OR (articles.publish_date <= \'0000-00-00\'))';

	// only consider live articles
	$now = gmstrftime('%Y-%m-%d %H:%M:%S');
	$where = '('.$where.')'
		.' AND ((articles.expiry_date is NULL)'
		."	OR (articles.expiry_date <= '".NULL_DATE."') OR (articles.expiry_date > '".$now."'))";

	// list up to 10000 most recent articles from active sections
	$query = "SELECT articles.id, articles.publish_date FROM ".SQL::table_name('articles')." AS articles"
		." WHERE ".$where
		." ORDER BY articles.rank, articles.edit_date DESC LIMIT 0, 10000";
	if($result =& SQL::query($query)) {

		// scan the list
		$count = 0;
		$errors_count = 0;
		while($row =& SQL::fetch($result)) {
			$count++;
			if($error = Categories::remember('article:'.$row['id'], $row['publish_date'])) {
				$context['text'] .= $error.BR."\n";
				if(++$errors_count >= 5) {
					$context['text'] .= i18n::s('Too many successive errors. Aborted').BR."\n";
					break;
				}
			} else
				$errors_count = 0;

			// animate user screen and take care of time
			if(!($count%100)) {
				$context['text'] .= sprintf(i18n::s('%d records have been processed'), $count).BR."\n";

				// ensure enough execution time
				Safe::set_time_limit(30);

			}

		}
	}

	// ending message
	$context['text'] .= sprintf(i18n::s('%d records have been processed'), $count).BR."\n";

	// display the execution time
	$time = round(get_micro_time() - $context['start_time'], 2);
	$context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

	// forward to the index page
	$menu = array('categories/' => i18n::s('Categories'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// rebuild title paths
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'paths')) {

	// scan categories
	$context['text'] .= Skin::build_block(sprintf(i18n::s('Analyzing table %s...'), SQL::table_name('categories')), 'title');

	// scan up to 1000 categories (52 weekly + 12 monthly = 60 per year)
	$count = 0;
	if($items = Categories::list_by_date(0, 1000, 'raw')) {

		// retrieve the id and all attributes
		$errors_count = 0;
		foreach($items as $id => $item) {

			// animate user screen and take care of time
			$count++;
			if(!($count%100)) {
				$context['text'] .= sprintf(i18n::s('%d records have been processed'), $count).BR."\n";

				// ensure enough execution time
				Safe::set_time_limit(30);

			}

			// rebuild titles path
			$path = '';
			if($item['anchor'])
				$path .= Categories::build_path($item['anchor']).'|';
			$path .= strip_tags($item['title']);

			// save in the database
			$query = "UPDATE ".SQL::table_name('categories')." SET "
				." path='".SQL::escape($path)."'"
				." WHERE id = ".SQL::escape($item['id']);
			if(SQL::query($query) === FALSE) {
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
	$menu = array('categories/' => i18n::s('Categories'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// look for orphans
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'orphans')) {

	// scan categories
	$context['text'] .= Skin::build_block(sprintf(i18n::s('Analyzing table %s...'), SQL::table_name('categories')), 'title');

	// scan up to 10000 categories
	$count = 0;
	if($items = Categories::list_by_date(0, 10000, 'raw')) {

		// retrieve the id and all attributes
		$errors_count = 0;
		foreach($items as $id => $item) {

			// animate user screen and take care of time
			$count++;
			if(!($count%100)) {
				$context['text'] .= sprintf(i18n::s('%d records have been processed'), $count).BR."\n";

				// ensure enough execution time
				Safe::set_time_limit(30);

			}

			// check that the anchor exists, if any
			if($item['anchor'] && !Anchors::get($item['anchor'])) {
				$context['text'] .= sprintf(i18n::s('Orphan: %s'), 'category '.Skin::build_link(Categories::get_permalink($item), $id.' '.$label, 'category')).BR."\n";
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

	// scan members
	$context['text'] .= Skin::build_block(sprintf(i18n::s('Analyzing table %s...'), SQL::table_name('members')), 'title');

	// scan up to 50000 members
	$count = 0;
	$query = "SELECT id, anchor, member FROM ".SQL::table_name('members')." LIMIT 0, 50000";
	if(!($result =& SQL::query($query)))
		return;

	// parse the whole list
	else {

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

			// fetch the member
			if($row['member'] && !$item =& Anchors::get($row['member'])) {

				// delete this entry
				$query = "DELETE FROM ".SQL::table_name('members')." WHERE id = ".SQL::escape($row['id']);
				SQL::query($query);

				$context['text'] .= sprintf(i18n::s('Unknown member %s, record has been deleted'), $row['member']).BR."\n";
				if(++$errors_count >= 50) {
					$context['text'] .= i18n::s('Too many successive errors. Aborted').BR."\n";
					break;
				}

			// check that the anchor exists, if any
			} elseif($row['anchor'] && !Anchors::get($row['anchor'])) {

				// delete this entry
				$query = "DELETE FROM ".SQL::table_name('members')." WHERE id = ".SQL::escape($row['id']);
				SQL::query($query);

				$context['text'] .= sprintf(i18n::s('Unknown anchor %s, record has been deleted'), $row['anchor']).BR."\n";
				if(++$errors_count >= 50) {
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
	$menu = array('categories/' => i18n::s('Categories'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// which check?
} else {
	// the splash message
	$context['text'] .= '<p>'.i18n::s('Please select the action to perform.')."</p>\n";

	// the form
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form">';

	// remember previous publications
	$context['text'] .= '<p><input type="radio" name="action" id="action" value="remember" /> '.i18n::s('Scan pages and remember publication dates').'</p>';

	// rebuild path information
	$context['text'] .= '<p><input type="radio" name="action" value="paths" /> '.i18n::s('Rebuild title paths').'</p>';

	// look for orphan articles
	$context['text'] .= '<p><input type="radio" name="action" value="orphans" /> '.i18n::s('Look for orphan records').'</p>';

	// the submit button
	$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Start')).'</p>'."\n";

	// end of the form
	$context['text'] .= '</form>';

	// set the focus on the button
	$context['text'] .= JS_PREFIX
		.'$("action").focus();'."\n"
		.JS_SUFFIX;


}

// render the skin
render_skin();

?>