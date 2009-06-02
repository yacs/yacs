<?php
/**
 * check the integrity of the database for links
 *
 * This script first displays available options, then it performs selected action:
 * - check links
 * - check referrals
 * - normalize referrals for search engines, and extract keywords
 * - look for orphans
 *
 * Links should be checked quite often, once a week or once a month, to maintain an accurate database.
 *
 * Check links
 *
 * During this operation the script validates each link of the database, starting with more recent links,
 * and reports on broken links. It stops when 5 broken links have been found, or when the whole database has been browsed.
 *
 * If valid [code]Last-Modified[/code] headers are returned during the check, related links
 * are stamped with edit_action code '[code]link:stamp[/code]'.
 *
 *
 * Look for orphans
 *
 * Orphans are records without anchors
 *
 *
 * Of course, access to this page is restricted to associates.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'links.php';
include_once 'link.php';

// load the skin
load_skin('links');

// the absolute limit for checks
if(!defined('MAXIMUM_SIZE'))
	define('MAXIMUM_SIZE', 500);

// the size of each chunk
if(!defined('CHUNK_SIZE'))
	define('CHUNK_SIZE', 50);

// when to stop
if(!defined('ERRORS_THRESHOLD'))
	define('ERRORS_THRESHOLD', 20);

// the path to this page
$context['path_bar'] = array( 'links/' => i18n::s('Links') );

// the title of the page
$context['page_title'] = i18n::s('Maintenance');

// the user has to be an associate
if(!Surfer::is_associate()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

	// forward to the index page
	$menu = array('links/' => i18n::s('Links'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// check links through the Internet
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'check')) {

	// scan links
	$context['text'] .= '<p>'.sprintf(i18n::s('Analyzing table %s...'), SQL::table_name('links'))."</p>\n";

	// process all links
	$links_offset = 0;
	while($links_offset < MAXIMUM_SIZE) {

		// seek the database
		if(($rows = Links::list_by_date($links_offset, CHUNK_SIZE, 'review')) && count($rows)) {

			// analyze each link
			foreach($rows as $view_url => $label) {

				$prefix = $suffix = $variant = $actual_url = '';
				if(is_array($label)) {
					$prefix = $label[0];
					$suffix = $label[2];
					$variant = $label[3];
					$actual_url = $label[4];
					$label = $label[1];
				}

				// the url is valid
				include_once 'link.php';
				if($stamp = Link::validate($actual_url)) {
					$context['text'] .= '.';

					// remember Last-Modified data, if any
					if(preg_match('/\d\d\d\d-\d\d-\d\d/', $stamp)) {

						$query ="UPDATE ".SQL::table_name('links')." SET "
							."edit_action='link:stamp', "
							."edit_date='".$stamp."', "
							." WHERE links.link_url = '".$actual_url."'";
						SQL::query($query);

					}


				// remember broken links
				} else {
					$context['text'] .= '!';
					$broken[$view_url] = array($prefix, $label, $suffix, $variant, NULL);
				}

				// ensure enough execution time
				Safe::set_time_limit(30);

			}

			// process one chunk
			$context['text'] .= BR."\n";
			$links_offset += count($rows);

			// detect the end of the list
			if(count($rows) < CHUNK_SIZE)
				break;

			// stop if too many errors
			if(count($broken) >= ERRORS_THRESHOLD)
				break;

		// empty list
		} elseif($links_offset == 0) {
			$context['text'] .= '<p>'.i18n::s('No link to check.')."</p>\n";
			break;
		}

	}

	// process end
	if($links_offset > 1)
		$context['text'] .= sprintf(i18n::s('%d links have been processed.'), $links_offset).BR."\n";

	// display broken links
	if(is_array($broken) && count($broken)) {
		$context['text'] .= Skin::build_block(i18n::s('Broken links to review'), 'title');
		$context['text'] .= Skin::build_list($broken, 'decorated');

	// woaouh, a clean server
	} elseif($links_offset)
		$context['text'] .= '<p>'.i18n::s('No broken link has been found.').'</p>';

	// display the execution time
	$time = round(get_micro_time() - $context['start_time'], 2);
	$context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

	// forward to the index page
	$menu = array('links/' => i18n::s('Links'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// check referrals through the Internet
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'referrals')) {

	// scan links
	$context['text'] .= '<p>'.sprintf(i18n::s('Analyzing table %s...'), SQL::table_name('links'))."</p>\n";

	// avoid banned sources
	include_once $context['path_to_root'].'servers/servers.php';
	$banned_pattern = Servers::get_banned_pattern();

	// process all links
	$links_offset = 0;
	while($links_offset < MAXIMUM_SIZE) {

		// seek the database and check newest referrals
		include_once '../agents/referrals.php';
		if(($rows = Referrals::list_by_dates($links_offset, CHUNK_SIZE)) && count($rows)) {

			// analyze each link
			foreach($rows as $item) {

				$url = $item['referer'];

				// avoid banned sources
				if(preg_match($banned_pattern, $url)) {
					$context['text'] .= 'x';
					$banned[] = $url;

					// delete the referral from the database
					Referrals::delete($url);

				// the url is valid
				} elseif($stamp = Link::validate($url)) {
					$context['text'] .= '.';

				// remember broken links
				} else {
					$context['text'] .= '!';
					$broken[] = $url;

					// delete the referral from the database
					Referrals::delete($url);
				}

				// ensure enough execution time
				Safe::set_time_limit(30);

			}

			// process one chunk
			$context['text'] .= BR."\n";
			$links_offset += count($rows);

			// detect the end of the list
			if(count($rows) < CHUNK_SIZE)
				break;

			// stop if too many errors
			if((@count($broken) + @count($banned)) >= ERRORS_THRESHOLD)
				break;

		// empty list
		} elseif($links_offset == 0) {
			$context['text'] .= '<p>'.i18n::s('No link to check.')."</p>\n";
			break;
		}

	}

	// process end
	if($links_offset > 1)
		$context['text'] .= sprintf(i18n::s('%d links have been processed.'), $links_offset).BR."\n";

	// list processed links
	if(@count($broken) + @count($banned)) {
		$context['text'] .= Skin::build_block(i18n::s('Deleted referrals'), 'title');

		if(@count($broken)) {
			$context['text'] .= i18n::s('Following referrals have been deleted:').BR."\n";

			$context['text'] .= '<ul>';
			foreach($broken as $url)
				$context['text'] .= '<li>'.$url."</li>\n";
			$context['text'] .= '</ul>';
		}

		if(@count($banned)) {
			$context['text'] .= i18n::s('Following referrals have been banned:').BR."\n";

			$context['text'] .= '<ul>';
			foreach($banned as $url)
				$context['text'] .= '<li>'.$url."</li>\n";
			$context['text'] .= '</ul>';
		}

	// woaouh, a clean server
	} elseif($links_offset)
		$context['text'] .= '<p>'.i18n::s('No broken referral has been found.').'</p>';

	// display the execution time
	$time = round(get_micro_time() - $context['start_time'], 2);
	$context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

	// forward to the index page
	$menu = array('links/' => i18n::s('Links'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// normalize referrals
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'normalize')) {

	// scan links
	$context['text'] .= '<p>'.sprintf(i18n::s('Analyzing table %s...'), SQL::table_name('links'))."</p>\n";

	// process all links, but stop after CHUNK_SIZE updates
	$links_offset = 0;
	$changes = 0;
	while($changes < MAXIMUM_SIZE) {

		// seek the database and check newest referrals
		include_once '../agents/referrals.php';
		if($result = Referrals::list_by_dates($links_offset, CHUNK_SIZE)) {

			// analyze each link
			while($item =& SQL::fetch($result)) {
				list($link, $domain, $keywords) = Referrals::normalize($item['referer']);

				// we suppose the referral is already ok
				$ok = TRUE;

				// link has been changed
				if($item['referer'] != $link) {
					$context['text'] .= BR.'< '.htmlspecialchars($item['referer']).BR.'> '.htmlspecialchars($link).BR;
					$item['referer'] = $link;
					$ok = FALSE;
				}

				// domain has been changed
				if(!isset($item['domain']) || ($item['domain'] != $domain)) {
					if(isset($item['domain']) && $item['domain'])
						$context['text'] .= BR.'< '.htmlspecialchars($item['domain']).BR.'> '.htmlspecialchars($domain).BR;
					else
						$context['text'] .= BR.'d '.htmlspecialchars($domain).BR;
					$item['domain'] = $domain;
					$ok = FALSE;
				}

				// keywords have been found
				if($keywords && (!isset($item['keywords']) || ($item['keywords'] != $keywords))) {
					if(isset($item['keywords']) && $item['keywords'])
						$context['text'] .= BR.'< '.htmlspecialchars($item['keywords']).BR.'> '.$keywords.BR;
					else
						$context['text'] .= BR.'k '.$keywords.BR;
					$item['keywords'] = $keywords;
					$ok = FALSE;
				}

				// the link is ok
				if($ok)
					$context['text'] .= '.';

				// save updated referrals
				else {

					$query = "UPDATE ".SQL::table_name('referrals')." SET"
						." referer='".SQL::escape($item['referer'])."',"
						." domain='".SQL::escape($item['domain'])."',"
						." keywords='".SQL::escape($item['keywords'])."'"
						." WHERE id = ".$item['id'];

					SQL::query($query);

					// update statistics
					$changes += 1;

				}
			}

			// we have processed one chunk
			$links_offset += SQL::count($result);
			$context['text'] .= BR."\n";

			// ensure enough execution time
			Safe::set_time_limit(30);

			// detect the end of the list
			if(SQL::count($result) < CHUNK_SIZE)
				break;

		// empty list
		} elseif($links_offset == 0) {
			$context['text'] .= '<p>'.i18n::s('No link to check.')."</p>\n";
			break;
		}

	}

	// process end
	if($links_offset > 1)
		$context['text'] .= sprintf(i18n::s('%d links have been processed.'), $links_offset).BR."\n";

	// list broken links
	if($changes)
		$context['text'] .= sprintf(i18n::s('%d referrals have been normalized.'), $changes).BR."\n";

	// woaouh, a clean server
	elseif($links_offset)
		$context['text'] .= '<p>'.i18n::s('All referrals are looking ok.').'</p>';

	// display the execution time
	$time = round(get_micro_time() - $context['start_time'], 2);
	$context['text'] .= '<p>'.sprintf(i18n::s('Script terminated in %.2f seconds.'), $time).'</p>';

	// forward to the index page
	$menu = array('links/' => i18n::s('Links'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// look for orphans
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'orphans')) {

	// scan links
	$context['text'] .= Skin::build_block(sprintf(i18n::s('Analyzing table %s...'), SQL::table_name('links')), 'title');

	// scan many items
	$count = 0;
	$query = "SELECT id, anchor, link_url, title FROM ".SQL::table_name('links')
		." ORDER BY anchor LIMIT 0, 20000";
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
				$context['text'] .= sprintf(i18n::s('%d records have been processed.'), $count).BR."\n";

				// ensure enough execution time
				Safe::set_time_limit(30);

			}

			// check that the anchor exists, if any
			if($row['anchor'] && !Anchors::get($row['anchor'])) {
				$context['text'] .= sprintf(i18n::s('Orphan: %s'), 'link '.Skin::build_link($row['link_url'], $row['id'].' '.$row['link_url'])).BR."\n";
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
	$menu = array('links/' => i18n::s('Links'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// which check?
} else {
	// the splash message
	$context['text'] .= '<p>'.i18n::s('Please select the action to perform.')."</p>\n";

	// the form
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form">';

	// check links
	$context['text'] .= '<p><input type="radio" name="action" id="action" value="check" /> '.i18n::s('Check links through the Internet. If you click on the button above, the database will be scanned, and the server will attempt to open each link through the network. Broken URLS will be reported to you for further troubleshoting. Note that the program will stop automatically if too many broken links are found.').'</p>';

	// check referrals
	$context['text'] .= '<p><input type="radio" name="action" value="referrals" /> '.i18n::s('Check referrals through the Internet. If you click on the button above, referrals will be checked through the network. Note that the program will stop automatically if too many broken links are found.').'</p>';

	// normalize referrals
	$context['text'] .= '<p><input type="radio" name="action" value="normalize" /> '.i18n::s('Normalize referrals. Referrals from search engines will be simplified as much as possible. Also, keywords are extracted for further use.').'</p>';

	// look for orphan articles
	$context['text'] .= '<p><input type="radio" name="action" value="orphans" /> '.i18n::s('Look for orphan records').'</p>';

	// the submit button
	$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Start')).'</p>'."\n";

	// end of the form
	$context['text'] .= '</form>';

	// set the focus on the button
	$context['text'] .= JS_PREFIX
		.'$("action").focus();'."\n"
		.JS_SUFFIX."\n";

}

// render the skin
render_skin();
?>