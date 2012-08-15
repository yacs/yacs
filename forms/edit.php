<?php
/**
 * create a new form or edit an existing one
 *
 * @todo add a grid control to capture tabular information (Justin)
 * @todo introduce hidden fields as another kind of text input field, to capture constants
 *
 * This is the main script used to post a new form, or to modify an existing one.
 *
 * Only site associates are allowed to create or to modify forms for the site they manage.
 *
 * Accepted calls:
 * - edit.php create a new form, start by selecting a section
 * - edit.php?name=&lt;nick_name&gt; create a new form and name it
 * - edit.php?anchor=section:&lt;id&gt; create a new form targeting the given section
 * - edit.php/&lt;id&gt; modify an existing form
 * - edit.php?id=&lt;id&gt; modify an existing form
 *
 * If no anchor data is provided, a list of sections is proposed to let the surfer select one of them.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'forms.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item = Forms::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor = Anchors::get($item['anchor']);
elseif(isset($_REQUEST['anchor']) && $_REQUEST['anchor'])
	$anchor = Anchors::get($_REQUEST['anchor']);

// do not always show the edition form
$with_form = FALSE;

// load the skin
load_skin('forms');

// the path to this page
$context['path_bar'] = array( 'forms/' => i18n::s('Forms') );
if(isset($item['id']) && isset($item['title']))
	$context['path_bar'] = array_merge($context['path_bar'], array(Forms::get_url($item['id'], 'view', $item['title']) => $item['title']));

// the title of the page
if(isset($item['title']) && $item['title'])
	$context['page_title'] = sprintf(i18n::s('Edit: %s'), $item['title']);
else
	$context['page_title'] = i18n::s('Add a form');

// validate input syntax
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

// anonymous users are invited to log in or to register
} elseif(!Surfer::is_logged()) {

	if(isset($item['id']))
		$link = Forms::get_url($item['id'], 'edit');
	elseif(isset($_REQUEST['anchor']))
		$link = 'forms/edit.php?anchor='.$_REQUEST['anchor'];
	else
		$link = 'forms/edit.php';

	Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode($link));

// only associates can add or change a form
} elseif(!Surfer::is_associate()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// an anchor is mandatory
} elseif(!is_object($anchor) && !isset($item['id'])) {
	$context['text'] .= '<p>'.i18n::s('Please carefully select a section for your form.')."</p>\n";

	// no need for a title yet
	$with_title = FALSE;

	// list assigned sections, if any
	include_once '../sections/layout_sections_as_select.php';
	$layout = new Layout_sections_as_select();
	$layout->set_variant('forms/edit.php?anchor=section:');
	if(($assigned = Surfer::assigned_sections()) && count($assigned)) {

		// one section at a time
		$items = array();
		foreach($assigned as $assigned_id) {
			if($item = Sections::get($assigned_id)) {

				// strip locked sections, except to associates and editors
				if(isset($item['locked']) && ($item['locked'] == 'Y') && !Surfer::is_empowered())
					continue;

				// format this item
				$items = array_merge($items, $layout->one($item));

			}
		}

		// one box for all sections
		if(count($items)) {
			$context['text'] .= Skin::build_box(i18n::s('Your sections'), Skin::build_list($items, '2-columns'), 'header1', 'assigned_sections');
			$with_title = TRUE;

		}

	}

	// list regular top-level sections
	if($items = Sections::list_by_title_for_anchor(NULL, 0, 20, $layout)) {

		if(count($items))
			$items = Skin::build_list($items, '2-columns');

		$title = '';
		if($with_title)
			$title = i18n::s('Regular sections');

		$context['text'] .= Skin::build_box($title, $items, 'header1', 'regular_sections');

	} else
		$context['text'] .= '<p>'.sprintf(i18n::s('Use the %s to populate this server.'), Skin::build_link('help/populate.php', i18n::s('Content Assistant'), 'shortcut')).'</p>';

	// also list special sections to associates
	if(Surfer::is_associate()) {

		// query the database and layout that stuff
		if($text = Sections::list_inactive_by_title_for_anchor(NULL, 0, 50, $layout)) {

			// we have an array to format
			if(is_array($text))
				$text =& Skin::build_list($text, '2-columns');

			// displayed as another box
			$context['text'] .= Skin::build_box(i18n::s('Other sections'), $text, 'header1', 'other_sections');

		}
	}

// an error occured
} elseif(count($context['error'])) {
	$item = $_REQUEST;
	$with_form = TRUE;

// process uploaded data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// protect from hackers
	if(isset($_REQUEST['edit_name']))
		$_REQUEST['edit_name'] = preg_replace(FORBIDDEN_IN_NAMES, '_', $_REQUEST['edit_name']);
	if(isset($_REQUEST['edit_address']))
		$_REQUEST['edit_address'] = encode_link($_REQUEST['edit_address']);

	// update an existing form
	if(isset($item['id'])) {

		// remember the previous version
		if($item['id']) {
			include_once '../versions/versions.php';
			Versions::save($item, 'form:'.$item['id']);
		}

		// stop on error
		if(!Forms::post($_REQUEST)) {
			$item = $_REQUEST;
			$with_form = TRUE;

		// else display the updated page
		} else {
			Forms::clear($_REQUEST);
			Safe::redirect($context['url_to_home'].$context['url_to_root'].Forms::get_url($item['id'], 'view', $item['title']));
		}

	// create a new page
	} elseif(!$_REQUEST['id'] = Forms::post($_REQUEST)) {
		$item = $_REQUEST;
		$with_form = TRUE;

	// successful post
	} else {
		Forms::clear($_REQUEST);

		// increment the post counter of the surfer
		Users::increment_posts(Surfer::get_id());

		// get the new item
		$form = Forms::get($_REQUEST['id']);

		// page title
		$context['page_title'] = i18n::s('Thank you for your contribution');

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu = array_merge($menu, array(Forms::get_url($form['id'], 'view', $form['title']) => i18n::s('Use the form')));
		$menu = array_merge($menu, array(Forms::get_url($form['id'], 'edit') => i18n::s('Edit again')));
		$follow_up .= Skin::build_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

		// log the creation of a new form
		$label = sprintf(i18n::c('New form: %s'), strip_tags($form['title']));

		// poster and target section
		$description = sprintf(i18n::s('Sent by %s'), Surfer::get_name())."\n\n";

		// title and link
		if($title = $form['title'])
			$description .= $title."\n";
               $link = $context['url_to_home'].$context['url_to_root'].Forms::get_url($form['id'], 'view', $form['title']);
               $description .= '<a href="'.$link.'">'.$link.'</a>'."\n\n";

		// notify sysops
		Logger::notify('forms/edit.php', $label, $description);

	}

// display the form on GET
} else
	$with_form = TRUE;

// display the form
if($with_form) {

	// the form to edit an form
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="$(\'#content\').val(Forms.toJSON(\'#form_panel\')); return validateDocumentPost(this)" id="main_form" enctype="multipart/form-data"><div>';

	// this form has several panels
	$panels = array();
	$panels['information'] = '';
	$panels['content'] = '';
	$panels['processing'] = '';
	$fields = array();

	// the information panel
	//

	// the title
	$label = i18n::s('Title');
	$value = '';
	if(isset($item['title']) && $item['title'])
		$value = $item['title'];
	$input = '<textarea name="title" id="title" rows="2" cols="50" accesskey="t">'.encode_field($value).'</textarea>';
	$hint = i18n::s('Please provide a meaningful title.');
	$fields[] = array($label, $input, $hint);

	// the introduction
	$label = i18n::s('Introduction');
	$value = '';
	if(isset($item['introduction']) && $item['introduction'])
		$value = $item['introduction'];
	$input = '<textarea name="introduction" rows="5" cols="50" accesskey="i">'.encode_field($value).'</textarea>';
	$hint = i18n::s('Also complements the title in lists featuring this page');
	$fields[] = array($label, $input, $hint);

	// the description
	$label = i18n::s('Usage');
	$value = '';
	if(isset($item['description']) && $item['description'])
		$value = $item['description'];
	$input = Surfer::get_editor('description', $value);
	$fields[] = array($label, $input);

	// additional options for this post
	$label = i18n::s('Post processing');
	$input = '<input type="checkbox" name="option_validate" value="Y" checked="checked" /> '.i18n::s('Ensure this post is valid XHTML.').BR;
	$fields[] = array($label, $input, '');

	// we are now entering extended content
	$panels['information'] .= Skin::build_form($fields);
	$fields = array();

	// the content panel
	//

	// the place to build the form interactively
	$panels['content'] .= '<div id="form_wrapper">'."\n";

	// where we display form fields for the end user
	$panels['content'] .= '<div id="form_panel">'.'</div>'."\n";

	// user commands
	$menu = array();

	// add items
	$menu[] = '<a href="#" onclick="Forms.appendLabel();return false;"><span>'.i18n::s('Add some text').'</span></a>';
	$menu[] = '<a href="#" onclick="Forms.appendTextInput();return false;"><span>'.i18n::s('Add a string input field').'</span></a>';
	$menu[] = '<a href="#" onclick="Forms.appendListInput();return false;"><span>'.i18n::s('Add a selection input field').'</span></a>';
	$menu[] = '<a href="#" onclick="Forms.appendFileInput();return false;"><span>'.i18n::s('Enable file upload').'</span></a>';

	// display all commands
	$panels['content'] .= '<div id="form_input_panel">'.i18n::s('Use links below to append new fields.').Skin::finalize_list($menu, 'menu_bar')."</div>\n";

	// end of the wrapper
	$panels['content'] .= '</div>'."\n";

	// the place to serialize the form
	$panels['content'] .= '<input type="hidden" name="content" id="content" />'."\n";

	// the processing panel
	//

	// the nick name
	$label = i18n::s('Nick name');
	$value = '';
	if(isset($item['nick_name']) && $item['nick_name'])
		$value = $item['nick_name'];
	$input = '<input type="text" name="nick_name" size="32" value="'.encode_field($value).'" maxlength="64" accesskey="n" />';
	$hint = sprintf(i18n::s('To designate a page by its name in the %s'), Skin::build_link('go.php', 'page selector', 'open'));
	$fields[] = array($label, $input, $hint);

	// the active flag: Yes/public, Restricted/logged, No/associates --we don't care about inheritance, to enable security changes afterwards
	$label = i18n::s('Access');

	// maybe a public page
	$input = '<input type="radio" name="active" value="Y" accesskey="v"';
	if(!isset($item['active']) || ($item['active'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Public - Everybody, including anonymous surfers').BR;

	// maybe a restricted page
	$input .= '<input type="radio" name="active" value="R"';
	if(isset($item['active']) && ($item['active'] == 'R'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Community - Access is granted to any identified surfer').BR;

	// or a hidden page
	$input .= '<input type="radio" name="active" value="N"';
	if(isset($item['active']) && ($item['active'] == 'N'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Private - Access is restricted to selected persons')."\n";

	// expand the form
	$fields[] = array($label, $input);

	// the target section
	if(isset($item['anchor']) || isset($_REQUEST['anchor'])) {
		$label = i18n::s('Capture');
		$input = i18n::s('Select below the section to capture this form').BR
			.'<select name="anchor">'.Sections::get_options($item['anchor'] ? $item['anchor'] : $_REQUEST['anchor']).'</select>';
		$fields[] = array($label, $input);
	}

	// finalize this panel
	$panels['processing'] .= Skin::build_form($fields);
	$fields = array();

	// show all tabs
	//
	$all_tabs = array(
		array('information', i18n::s('Information'), 'information_panel', $panels['information']),
		array('content', i18n::s('Content'), 'content_panel', $panels['content']),
		array('processing', i18n::s('Processing'), 'processing_panel', $panels['processing'])
		);

	// let YACS do the hard job
	$context['text'] .= Skin::build_tabs($all_tabs);

	// commands at page bottom
	$menu = array();

	// the submit button
	$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');

	// command to go back
	if(isset($item['id']))
		$menu[] = Skin::build_link(Forms::get_url($item['id'], 'view', $item['title']), i18n::s('Cancel'), 'span');

	// insert commands
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// validate page content
	$context['text'] .= '<p><input type="checkbox" name="option_validate" value="Y" checked="checked" /> '.i18n::s('Ensure this post is valid XHTML.').'</p>';

	// transmit the id as a hidden field
	if(isset($item['id']) && $item['id'])
		$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'" />';

	// end of the form
	$context['text'] .= '</div></form>';

	// the script used for form handling at the browser
	$context['page_footer'] .= JS_PREFIX
		.'// check that main fields are not empty'."\n"
		.'func'.'tion validateDocumentPost(container) {'."\n"
		."\n"
		.'	// title is mandatory'."\n"
		.'	if(!container.title.value) {'."\n"
		.'		alert("'.i18n::s('Please provide a meaningful title.').'");'."\n"
		.'		Yacs.stopWorking();'."\n"
		.'		return false;'."\n"
		.'	}'."\n"
		."\n"
		.'	// successful check'."\n"
		.'	return true;'."\n"
		.'}'."\n"
		."\n"
		.'// disable editor selection on change in form'."\n"
                .'$("#main_form textarea, #main_form input, #main_form select").change(function() {'."\n"
                .'      $("#preferred_editor").attr("disabled",true);'."\n"
                .'});'."\n"
		."\n"
		.'$(function() { $("#title").focus() });'."\n" // set the focus on first form field
		."\n"
		.JS_SUFFIX."\n";

	// content of the help box
	$help = '<p>';
	$help .= i18n::s('If you paste some existing HTML content and want to avoid the implicit formatting insert the code <code>[formatted]</code> at the very beginning of the description field.');
	$help .= ' '.sprintf(i18n::s('%s and %s are available to enhance text rendering.'), Skin::build_link('codes/', i18n::s('YACS codes'), 'open'), Skin::build_link('smileys/', i18n::s('smileys'), 'open')).'</p>';

 	// change to another editor
	$help .= '<form action=""><p><select name="preferred_editor" id="preferred_editor" onchange="Yacs.setCookie(\'surfer_editor\', this.value); window.location = window.location;">';
	$selected = '';
	if(!isset($_SESSION['surfer_editor']) || ($_SESSION['surfer_editor'] == 'tinymce'))
		$selected = ' selected="selected"';
	$help .= '<option value="tinymce"'.$selected.'>'.i18n::s('TinyMCE')."</option>\n";
	$selected = '';
	if(isset($_SESSION['surfer_editor']) && ($_SESSION['surfer_editor'] == 'fckeditor'))
		$selected = ' selected="selected"';
	$help .= '<option value="fckeditor"'.$selected.'>'.i18n::s('FCKEditor')."</option>\n";
	$selected = '';
	if(isset($_SESSION['surfer_editor']) && ($_SESSION['surfer_editor'] == 'yacs'))
		$selected = ' selected="selected"';
	$help .= '<option value="yacs"'.$selected.'>'.i18n::s('Textarea')."</option>\n";
	$help .= '</select></p></form>';

	// in a sidebar box
	$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'boxes', 'help');

	// the AJAX part
	//
	$context['page_footer'] .= '<script type="text/javascript" src="'.$context['url_to_root'].'forms/forms.js"></script>'."\n";

	// the script used to restore previous state
	if(isset($item['content']) && $item['content']) {
		$context['page_footer'] .= JS_PREFIX
			.'// restore form fields'."\n"
			.'$(function() { Forms.fromJSON("#form_panel", '.utf8::encode($item['content']).') });'."\n"
			.JS_SUFFIX."\n";
	}

}

// render the skin -- don't use HTTP cache, because of AJAX updates of the form panel
render_skin(-1);

?>
