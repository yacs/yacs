<?php
/**
 * upload a new table or update an existing one
 *
 * This script attempts to validate the new or updated article description against a standard PHP XML parser.
 * The objective is to spot malformed or unordered HTML and XHTML tags. No more, no less.
 *
 * Only associates and owners can post and edit tables.
 *
 * Accepted calls:
 * - edit.php/&lt;type&gt;/&lt;id&gt;			create a new table for this anchor
 * - edit.php?anchor=&lt;type&gt;:&lt;id&gt;	create a new table for the anchor
 * - edit.php/&lt;id&gt;					modify an existing table
 * - edit.php?id=&lt;id&gt; 			modify an existing table
 *
 * If the anchor for this item specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @author Vincent No&euml;l
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../shared/xml.php';	// input validation
include_once 'tables.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]) && !isset($context['arguments'][1]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Tables::get($id);

// look for the target anchor on item creation
$target_anchor = NULL;
if(isset($_REQUEST['anchor']))
	$target_anchor = $_REQUEST['anchor'];
if(!isset($target_anchor) && isset($context['arguments'][1]))
	$target_anchor = $context['arguments'][0].':'.$context['arguments'][1];

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']))
	$anchor =& Anchors::get($item['anchor']);
elseif($target_anchor)
	$anchor =& Anchors::get($target_anchor);

// associates and owners can do what they want
if(Surfer::is_associate() || (is_object($anchor) && $anchor->is_owned()))
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// do not always show the edition form
$with_form = FALSE;

// load the skin, maybe with a variant
load_skin('tables', $anchor);

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'tables/' => i18n::s('Tables') );

// the title of the page
if(isset($item['id']))
	$context['page_title'] = i18n::s('Edit a table');
else
	$context['page_title'] = i18n::s('Add a table');

// validate input syntax only if required
if(isset($_REQUEST['option_validate']) && ($_REQUEST['option_validate'] == 'Y')) {
	if(isset($_REQUEST['introduction']))
		xml::validate($_REQUEST['introduction']);
	if(isset($_REQUEST['description']))
		xml::validate($_REQUEST['description']);
}

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// an anchor is mandatory
} elseif(!is_object($anchor)) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No anchor has been found.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged()) {

		if(isset($item['id']))
			$link = Tables::get_url($item['id'], 'edit');
		elseif(isset($_REQUEST['anchor']))
			$link = 'tables/edit.php?anchor='.urlencode($_REQUEST['anchor']);
		else
			$link = 'tables/edit.php';

		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode($link));
	}

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// maybe posts are not allowed here
} elseif(!isset($item['id']) && $anchor->has_option('locked') && !Surfer::is_empowered()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
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
	if(!$_REQUEST['id'] = Tables::post($_REQUEST)) {
		$item = $_REQUEST;
		$with_form = TRUE;

	// post-processing
	} else {

		// a new post
		if(!$item['id']) {

			// touch the related anchor
			$anchor->touch('table:create', $_REQUEST['id'], isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y'));

			// clear cache
			Tables::clear($_REQUEST);

			// increment the post counter of the surfer
			Users::increment_posts(Surfer::get_id());

		// an update
		} else {

			// touch the related anchor
			$anchor->touch('table:update', $_REQUEST['id'], isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y'));

			// clear cache
			Tables::clear($_REQUEST);

		}

		// go to the updated page
		Safe::redirect($next);
	}

// display the form on GET
} else
	$with_form = TRUE;

// display the form
if($with_form) {

	// reference the anchor page
	if(is_object($anchor) && $anchor->is_viewable())
		$context['text'] .= '<p>'.sprintf(i18n::s('In: %s'), Skin::build_link($anchor->get_url(), $anchor->get_title()))."</p>\n";

	// the form to edit an table
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>';

	// encode fields
	$fields = array();

	// display info on current version
	if(isset($item['id'])) {

		// the last poster
		if(isset($item['edit_id'])) {
			$text = Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id'])
				.' '.Skin::build_date($item['edit_date']);
			$fields[] = array(i18n::s('Posted by'), $text);
		}
	}

	// the title
	$label = i18n::s('Title');
	$input = '<textarea name="title" id="title" rows="2" cols="50">'.encode_field(isset($item['title']) ? $item['title'] : '').'</textarea>';
	$hint = i18n::s('Please provide a meaningful title.');
	$fields[] = array($label, $input, $hint);

	// the query
	$label = i18n::s('SQL Query');
	$input = '<textarea name="query" rows="15" cols="50">'.encode_field(isset($item['query']) ? $item['query'] : '').'</textarea>';
	$hint = i18n::s('The SELECT command submitted to the database');
	$fields[] = array($label, $input, $hint);

	// is the first row an url to the zoom page?
	$label = i18n::s('First column');
	$input = '<input type="radio" name="with_zoom" value="N"';
	if(!isset($item['with_zoom']) || ($item['with_zoom'] == 'N'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('First column contains useful data')
		.BR."\n".'<input type="radio" name="with_zoom" value="T"';
	if(isset($item['with_zoom']) && ($item['with_zoom'] == 'T'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('First column refers to time')
		.BR."\n".'<input type="radio" name="with_zoom" value="Y"';
	if(isset($item['with_zoom']) && ($item['with_zoom'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('First column provides a web link');
	$fields[] = array($label, $input);

	// the description
	$label = i18n::s('Description');
	$input = '<textarea name="description" rows="5" cols="50">'.encode_field(isset($item['description']) ? $item['description'] : '').'</textarea>';
	$hint = i18n::s('As this field may be searched by surfers, please choose adequate searchable words');
	$fields[] = array($label, $input, $hint);

	// build the form
	$context['text'] .= Skin::build_form($fields);

	// bottom commands
	$menu = array();
	$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');
	if(is_object($anchor) && $anchor->is_viewable())
		$menu[] = Skin::build_link($anchor->get_url(), i18n::s('Cancel'), 'span');
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// associates may decide to not stamp changes -- complex command
	if(Surfer::is_associate() && Surfer::has_all())
		$context['text'] .= '<p><input type="checkbox" name="silent" value="Y" /> '.i18n::s('Do not change modification date of the main page.').'</p>';

	// validate page content
	$context['text'] .= '<p><input type="checkbox" name="option_validate" value="Y" checked="checked" /> '.i18n::s('Ensure this post is valid XHTML.').'</p>';

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
		.'		// query is mandatory'."\n"
		.'		if(!container.query.value) {'."\n"
		.'			alert("'.i18n::s('Please type a valid SQL query.').'");'."\n"
		.'			Yacs.stopWorking();'."\n"
		.'			return false;'."\n"
		.'		}'."\n"
		."\n"
		.'		// successful check'."\n"
		.'		return true;'."\n"
		.'	}'."\n"
		."\n"
		.'// set the focus on first form field'."\n"
		.'$("#title").focus();'."\n"
		.JS_SUFFIX."\n";

	// the help panel
	$help = '<p>'.i18n::s('Please ensure you are using a compliant and complete SQL SELECT statement.').'</p>'
		.'<p>'.sprintf(i18n::s('For more information check the %s.'), Skin::build_link('http://dev.mysql.com/doc/mysql/en/select.html', i18n::s('MySQL reference page'), 'external')).'</p>'
		.'<p>'.sprintf(i18n::s('%s and %s are available to enhance text rendering.'), Skin::build_link('codes/', i18n::s('YACS codes'), 'help'), Skin::build_link('smileys/', i18n::s('smileys'), 'help')).'</p>';
	$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'boxes', 'help');

}

// render the skin
render_skin();

?>
