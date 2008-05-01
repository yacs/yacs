<?php
/**
 * create a new personal section
 *
 * This script allows authenticated members to extend their personal web space.
 *
 * Simply speaking, it asks for a name, creates a new section,
 * and set current surfer as a managing editor of the new section.
 *
 * Following restrictions apply to this page:
 * - surfer has to be an authenticated member
 * - the maximum number for managed sections has not been reached
 *
 * The maximum number of managed sections is the parameter 'users_maximum_managed_sections'
 * set in [script]users/configure.php[/script].
 *
 * @see users/configure.php
 *
 * Accepted calls:
 * - new.php
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// the maximum number of personal sections per user
if(!isset($context['users_maximum_managed_sections']))
	$context['users_maximum_managed_sections'] = 0;

// load the skin before assessing permissions
load_skin('sections');

// surfer has to be an authenticated member --not accessible to subscribers
if(!Surfer::is_member())
	$permitted = FALSE;

// ensure a maximum number of managed sections
elseif(($existing_sections = Surfer::personal_sections()) && (count($existing_sections) >= $context['users_maximum_managed_sections']))
	$permitted = FALSE;

// all checks have been passed
else
	$permitted = TRUE;

// do not always show the edition form
$with_form = FALSE;

// the path to this page
$context['path_bar'] = array( 'sections/' => i18n::s('Site map') );

// the title of the page
$context['page_title'] = i18n::s('Extend my personal web space');

// command to go back
if(Surfer::get_id())
	$context['page_menu'] = array( Users::get_url(Surfer::get_id()) => i18n::s('Back to my profile') );

// always validate input syntax
if(isset($_REQUEST['introduction']))
	validate($_REQUEST['introduction']);
if(isset($_REQUEST['description']))
	validate($_REQUEST['description']);

// access denied
if(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('sections/new.php'));

	// permission denied to authenticated user
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// an error occured
} elseif(count($context['error'])) {
	$item = $_REQUEST;
	$with_form = TRUE;

// post a new section
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// put all sections at the same place
	if(!($anchor =& Sections::get('personal_spaces'))) {

		// dedicate a top-level section to personal spaces
		$fields = array();
		$fields['nick_name'] = 'personal_spaces';
		$fields['create_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', time());
		$fields['edit_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', time());
		$fields['locked'] = 'Y'; // no direct contributions
		$fields['home_panel'] = 'none'; // not mentioned at the home page
		$fields['rank'] = 40000; // at the end of the list
		$fields['title'] = i18n::c('Personal spaces');
		$fields['description'] = i18n::c('Sections created by members');
		if(!$new_id = Sections::post($fields)) {
			Logger::remember('feeds/feeds.php', 'Impossible to add a section.');
			return;
		}

		// retrieve the new section
		$anchor =& Sections::get('personal_spaces');

	}

	// anchor the new section here
	include_once 'section.php';
	$section =& new Section();
	$section->load_by_content($anchor);
	$anchor = $section;
	$_REQUEST['anchor'] = $anchor->get_reference();
	$_REQUEST['active_set'] = $_REQUEST['active'];

	// display the form on error
	if(!$id = Sections::post($_REQUEST)) {
		$item = $_REQUEST;
		$with_form = TRUE;

	// post-processing
	} else {

		// make the surfer an editor of this section
		Members::assign('user:'.Surfer::get_id(), 'section:'.$id);

		// touch the related anchor
		if(is_object($anchor))
			$anchor->touch('section:create', $id, isset($_REQUEST['active']) && ($_REQUEST['active'] != 'Y'));

		// increment the post counter of the surfer
		Users::increment_posts(Surfer::get_id());

		// reward the poster
		$context['page_title'] = i18n::s('Congratulation, you have successfully extended your web space');

		$context['text'] .= '<p>'.i18n::s('Please review this new web space carefully and fix possible errors rapidly.').'</p>';

		// get the new item
		$section = Anchors::get('section:'.$id);

		// follow-up commands
		$context['text'] .= '<p>'.i18n::s('What do you want to do now?').'</p>';
		$menu = array();

		$menu = array_merge($menu, array($section->get_url() => i18n::s('View the section')));

		if(Surfer::may_upload())
			$menu = array_merge($menu, array('images/edit.php?anchor='.urlencode('section:'.$id) => i18n::s('Add an image')));

		if(preg_match('/\bwith_files\b/i', $section->item['options']) && Surfer::may_upload())
			$menu = array_merge($menu, array('files/edit.php?anchor='.urlencode('section:'.$id) => i18n::s('Upload a file')));

		if(preg_match('/\bwith_links\b/i', $section->item['options']))
			$menu = array_merge($menu, array('links/edit.php?anchor='.urlencode('section:'.$id) => i18n::s('Add a link')));

		$context['text'] .= Skin::build_list($menu, 'menu_bar');

	}

// display the form on GET
} else
	$with_form = TRUE;

// display the form
if($with_form) {

	// by default it will be a blog
	if(!isset($item['articles_layout']))
		$item['articles_layout'] = 'daily';

	// default title
	if(!isset($item['title'])) {

		// first section
		if(!isset($existing_sections) || !count($existing_sections))
			$item['title'] = sprintf(i18n::s('The personal space of %s'), Surfer::get_name());

		// second section
		elseif(count($existing_sections) == 1)
			$item['title'] = sprintf(i18n::s('Another personal space of %s'), Surfer::get_name());

		// subsequent section
		else
			$item['title'] = sprintf(i18n::s('Another personal space of %s (%d)'), Surfer::get_name(), count($existing_sections));
	}

	// the form to edit a section
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>';

	// the title
	$label = i18n::s('Title');
	$input = '<textarea name="title" id="title" rows="2" cols="50" accesskey="t">'.encode_field(isset($item['title']) ? $item['title'] : '').'</textarea>';
	$hint = i18n::s('Please provide a meaningful title.');
	$fields[] = array($label, $input, $hint);

	// the introduction
	$label = i18n::s('Introduction');
	$input = '<textarea name="introduction" rows="2" cols="50" accesskey="i">'.encode_field(isset($item['introduction']) ? $item['introduction'] : '').'</textarea>';
	$hint = i18n::s('Appears at the site map, near section title');
	$fields[] = array($label, $input, $hint);

	// the description
	$label = i18n::s('Description');

	// use the editor if possible
	$input = Surfer::get_editor('description', isset($item['description'])?$item['description']:'');
	$fields[] = array($label, $input);

	// layout for related articles
	$label = i18n::s('Content');
	$input = i18n::s('What are you aiming to create?');
	$input .= BR.'<input type="radio" name="articles_layout" value="daily"';
	if(isset($item['articles_layout']) && ($item['articles_layout'] == 'daily'))
		$input .= ' checked="checked"';
	$input .= EOT.i18n::s('A blog to share my daily mood, opinions and ideas');
	$input .= BR.'<input type="radio" name="articles_layout" value="yabb"';
	if(isset($item['articles_layout']) && ($item['articles_layout'] == 'yabb'))
		$input .= ' checked="checked"';
	$input .= EOT.i18n::s('A discussion board I will moderate');
	$input .= BR.'<input type="radio" name="articles_layout" value="decorated"';
	$input .= EOT.i18n::s('Something different');
	$fields[] = array($label, $input);

	// do not list new articles at the index page of personal spaces
	$context['text'] .= '<input type="hidden" name="index_panel" value="none" />';

	// do not break home page layout
	$context['text'] .= '<input type="hidden" name="home_panel" value="none" />';

	// make it personal and avoid publishing step
	$context['text'] .= '<input type="hidden" name="content_options" value="with_extra_profile auto_publish" />';

	// the active flag: Yes/public, Restricted/logged, No/associates
	$label = i18n::s('Visibility');
	$input = '<input type="radio" name="active" value="Y" accesskey="v"';
	if(!isset($item['active']) || ($item['active'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Anyone may read this section').' '
		.BR.'<input type="radio" name="active" value="R"';
	if(isset($item['active']) && ($item['active'] == 'R'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Access is restricted to authenticated members').' '
		.BR.'<input type="radio" name="active" value="N"';
	if(isset($item['active']) && ($item['active'] == 'N'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Access is restricted to me and associates');
	$fields[] = array($label, $input);

	// locked: Yes / No
	$label = i18n::s('Locked');
	$input = '<input type="radio" name="locked" value="N"';
	if(!isset($item['locked']) || ($item['locked'] != 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('No - Contributions are accepted').' '
		.BR.'<input type="radio" name="locked" value="Y"';
	if(isset($item['locked']) && ($item['locked'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= EOT.' '.i18n::s('Yes - Only me and associates can add or modify content');
	$fields[] = array($label, $input);

	// actually build the form
	$context['text'] .= Skin::build_form($fields);
	$fields = array();

	// the submit button
	$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

	// end of the form
	$context['text'] .= '</div></form>';

	// append the script used for data checking on the browser
	$context['page_footer'] .= '<script type="text/javascript">// <![CDATA['."\n"
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
		.'// set the focus on first form field'."\n"
		.'document.getElementById("title").focus();'."\n"
		.'// ]]></script>'."\n";

	// general help on this form
	$help = '<p>'.sprintf(i18n::s('%s and %s are available to enhance text rendering.'), Skin::build_link('codes/', 'YACS codes', 'help'), Skin::build_link('smileys/', 'smileys', 'help')).'</p>';

 	// change to another editor
	$help .= '<form><p><select name="preferred_editor" onchange="Yacs.setCookie(\'surfer_editor\', this.value); window.location = window.location;">';
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

	if(Surfer::is_associate())
		$help .= '<p>'.sprintf(i18n::s('Use the %s if you are lost.'), Skin::build_link('control/populate.php', 'Content Assistant', 'shortcut')).'</p>'."\n";
	$context['extra'] .= Skin::build_box(i18n::s('Help'), $help, 'navigation', 'help');

}

// render the skin
render_skin();

?>