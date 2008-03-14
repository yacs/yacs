<?php
/**
 * check the integrity of the database for actions
 *
 * This page is used to check and update the database. Its usage is restricted to associates.
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// include libraries
include_once '../shared/global.php';
include_once 'actions.php';

// load localized strings
i18n::bind('actions');

// load the skin
load_skin('actions');

// the path to this page
$context['path_bar'] = array( 'actions/' => i18n::s('actions') );

// the title of the page
$context['page_title'] = i18n::s('Actions validation');

// the user has to be an associate
if(!Surfer::is_associate()) {
	$context['text'] .= i18n::s('You are not allowed to perform this operation.');

	// forward to the index page
	$menu = array('actions/' => i18n::s('Recent actions'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// look for orphans
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'orphans')) {

	// scan actions
	$context['text'] .= Skin::build_block(i18n::s('Looking for orphans in actions table...'), 'subtitle');

	// scan up to 10000 items
	$count = 0;
	$query = "SELECT id, anchor, title FROM ".SQL::table_name('actions')
		." ORDER BY anchor LIMIT 0, 10000";
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

			// check that the anchor exists, if any
			if($row['anchor'] && !Anchors::get($row['anchor'])) {
				$context['text'] .= sprintf(i18n::s('Orphan: action %s'), Skin::build_link(Actions::get_url($row['id']), $row['id'].' '.$row['title'])).BR."\n";
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
	$menu = array('actions/' => i18n::s('All actions'));
	$context['text'] .= Skin::build_list($menu, 'menu_bar');

// select the action to perform
} else {

	// the splash message
	$context['text'] .= '<p>'.i18n::s('Please select below the check to perform.')."</p>\n";

	// the form
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form">';

	// look for orphan records
	$context['text'] .= '<p><input type="radio" name="action" id="action" value="orphans" /> '.i18n::s('Look for orphan records').'</p>';

	// the submit button
	$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Start')).'</p>'."\n";

	// end of the form
	$context['text'] .= '</form>';

	// set the focus on the button
	$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
		.'document.getElementById("action").focus();'."\n"
		.'// ]]></script>'."\n";

}

// render the skin
render_skin();
?>