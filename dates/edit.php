<?php
/**
 * set a new date or update an existing one
 *
 * Restrictions apply on this page:
 * - anonymous (not-logged) surfer are invited to register to be able to post new dates
 * - members can post new dates, and modify their dates afterwards
 * - associates and editors can do what they want
 *
 * Accepted calls:
 * - edit.php?anchor=&lt;type&gt;:&lt;id&gt;	add a new date for the anchor
 * - edit.php/&lt;id&gt;					modify an existing date
 * - edit.php?id=&lt;id&gt; 			modify an existing date
 *
 * If the anchor for this item specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'dates.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]) && !isset($context['arguments'][1]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Dates::get($id);

// look for the target anchor on item creation
$target_anchor = NULL;
if(isset($_REQUEST['anchor']))
	$target_anchor = $_REQUEST['anchor'];
elseif(isset($context['arguments'][1]))
	$target_anchor = $context['arguments'][0].':'.$context['arguments'][1];
$target_anchor = strip_tags($target_anchor);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']))
	$anchor =& Anchors::get($item['anchor']);
elseif($target_anchor)
	$anchor =& Anchors::get($target_anchor);

// do not always show the edition form
$with_form = FALSE;

// load the skin, maybe with a variant
load_skin('dates', $anchor);

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'dates/' => i18n::s('Dates') );

// the title of the page
if(isset($item['id']))
	$context['page_title'] = i18n::s('Edit a date');
else
	$context['page_title'] = i18n::s('Add a date');

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// anonymous users are invited to log in or to register
} elseif(!Surfer::is_logged())
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('dates/edit.php?id='.$id.'&anchor='.$_REQUEST['anchor']));

// anyone can modify a date he/she posted previously; associates and editors can modify everything
elseif(isset($item['id']) && ($item['edit_id'] != Surfer::get_id())
	&& !Surfer::is_associate() && is_object($anchor) && !$anchor->is_editable()) {

	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// an anchor is mandatory
} elseif(!is_object($anchor)) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No anchor has been found.'));

// maybe posts are not allowed here
} elseif(!isset($item['id']) && is_object($anchor) && $anchor->has_option('locked') && !Surfer::is_empowered()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('This page has been locked.'));

// an error occured
} elseif(count($context['error'])) {
	$item = $_REQUEST;
	$with_form = TRUE;

// process uploaded data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// the follow-up page
	$next = $context['url_to_home'].$context['url_to_root'].$anchor->get_url();

	// display the form on error
	if(!$_REQUEST['id'] = Dates::post($_REQUEST)) {
		$item = $_REQUEST;
		$with_form = TRUE;

	// reward the poster for new posts
	} elseif(!isset($item['id'])) {

		// increment the post counter of the surfer
		Users::increment_posts(Surfer::get_id());

		// thanks
		$context['page_title'] = i18n::s('Thank you for your contribution');

		// the date
		$context['text'] .= '<p>'.sprintf(i18n::s('%s: %s'), i18n::s('Target date'), Skin::build_date($item['date_stamp'], 'full')).'</p>';

		// the action
		$context['text'] .= '<p>'.i18n::s('The date has been saved.').'</p>';

		// touch the related anchor
		$anchor->touch('date:create', $_REQUEST['id'], isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y'));

		// clear cache
		Dates::clear($_REQUEST);

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu = array_merge($menu, array($anchor->get_url() => i18n::s('View the page')));
		$menu = array_merge($menu, array($anchor->get_url('edit') => i18n::s('Edit the page')));
		$follow_up .= Skin::build_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	// update of an existing date
	} else {

		// increment the post counter of the surfer
		Users::increment_posts(Surfer::get_id());

		// touch the related anchor
		$anchor->touch('date:update', $_REQUEST['id'], isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y'));

		// clear cache
		Dates::clear($_REQUEST);

		// forward to the view page
		Safe::redirect($context['url_to_home'].$context['url_to_root'].Dates::get_url($_REQUEST['id']));

	}

// display the form on GET
} else
	$with_form = TRUE;

// display the form
if($with_form) {

	// reference the anchor page
	if(is_object($anchor) && $anchor->is_viewable())
		$context['text'] .= '<p>'.Skin::build_link($anchor->get_url(), $anchor->get_title())."</p>\n";

	// the form to edit an date
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>';

	// form fields
	$fields = array();

	// display info on current version
	if(isset($item['id'])) {

		// the last poster
		if(isset($item['edit_id']) && $item['edit_id']) {
			$text = Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id'])
				.' '.Skin::build_date($item['edit_date']);
			$fields[] = array(i18n::s('Posted by'), $text);
		}

	}

	// date
	$label = i18n::s('Target date');
	$input = Skin::build_input('date_stamp', $item['date_stamp'], 'date');
	$hint = i18n::s('YYYY-MM-DD');
	$fields[] = array($label, $input, $hint);

	// build the form
	$context['text'] .= Skin::build_form($fields);
	$fields = array();

	// associates may decide to not stamp changes -- complex command
	if(Surfer::is_associate() && Surfer::has_all())
		$context['text'] .= '<p><input type="checkbox" name="silent" value="Y" /> '.i18n::s('Do not change modification date of the main page.').'</p>';

	// the submit button
	$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

	// transmit the id as a hidden field
	if(isset($item['id']) && $item['id'])
		$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'" />';

	// other hidden fields
	$context['text'] .= '<input type="hidden" name="anchor" value="'.$anchor->get_reference().'" />';

	// end of the form
	$context['text'] .= '</div></form>';

	// the script used for form handling at the browser
	$context['text'] .= JS_PREFIX
		.'	// check that main fields are not empty'."\n"
		.'	func'.'tion validateDocumentPost(container) {'."\n"
		."\n"
		.'		// date is mandatory'."\n"
		.'		if(!container.date.value) {'."\n"
		.'			alert("'.i18n::s('Please provide a date.').'");'."\n"
		.'			Yacs.stopWorking();'."\n"
		.'			return false;'."\n"
		.'		}'."\n"
		."\n"
		.'		// successful check'."\n"
		.'		return true;'."\n"
		.'	}'."\n"
		."\n"
		.'// set the focus on first form field'."\n"
		.'$("date").focus();'."\n"
		.JS_SUFFIX;

	// general help on this form
	$help = '<p>'.sprintf(i18n::s('%s and %s are available to enhance text rendering.'), Skin::build_link('codes/', i18n::s('YACS codes'), 'help'), Skin::build_link('smileys/', i18n::s('smileys'), 'help')).'</p>';
	$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'navigation', 'help');

}

// render the skin
render_skin();

?>