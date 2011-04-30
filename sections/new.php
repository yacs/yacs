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
 * @author Bernard Paques
 * @author GnapZ
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../shared/xml.php';	// input validation
include_once '../comments/comments.php';	// initiate the wall

// the maximum number of personal sections per user
if(!isset($context['users_maximum_managed_sections']))
	$context['users_maximum_managed_sections'] = 0;

// count sections owned by this surfer
$existing_sections = Sections::count_for_owner();

// load the skin before assessing permissions
load_skin('sections');

// surfer has to be an authenticated member --not accessible to subscribers
if(!Surfer::is_member())
	$permitted = FALSE;

// associates can always add sections
elseif(Surfer::is_associate())
	$permitted = TRUE;

// ensure a maximum number of managed sections
elseif($existing_sections >= $context['users_maximum_managed_sections'])
	$permitted = FALSE;

// all checks have been passed
else
	$permitted = TRUE;

// do not always show the edition form
$with_form = FALSE;

// the path to this page
$context['path_bar'] = array( 'sections/' => i18n::s('Site map') );

// the title of the page
$context['page_title'] = i18n::s('Add some space for your pages');

// always validate input syntax
if(isset($_REQUEST['introduction']))
	xml::validate($_REQUEST['introduction']);
if(isset($_REQUEST['description']))
	xml::validate($_REQUEST['description']);

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// access denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('sections/new.php'));

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// an error occured
} elseif(count($context['error'])) {
	$item = $_REQUEST;
	$with_form = TRUE;

// post a new section
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// put all groups at the same place
	if(!($anchor =& Sections::get('groups'))) {

		$fields = array();
		$fields['nick_name'] = 'groups';
		$fields['articles_layout'] = 'none';
		$fields['articles_templates'] = 'discussion_template, chat_template, event_template, wiki_template';
		$fields['create_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', time());
		$fields['edit_date'] = gmstrftime('%Y-%m-%d %H:%M:%S', time());
		$fields['home_panel'] = 'none'; // not mentioned at the home page
		$fields['locked'] = 'Y'; // no direct contributions
		$fields['options'] = 'no_contextual_menu';
		$fields['rank'] = 40000; // at the end of the list
		$fields['sections_layout'] = 'directory';
		$fields['title'] = i18n::c('Groups');
		if(!$fields['id'] = Sections::post($fields, FALSE)) {
			Logger::remember('sections/new.php', 'Impossible to add a section.');
			return;
		}

		// retrieve the new section
		$anchor =& Sections::get('groups');

	}

	// we are creating a blog
	if(isset($_REQUEST['space_type']) && ($_REQUEST['space_type'] == 'blog')) {
		$_REQUEST['articles_layout'] = 'daily';
		$_REQUEST['articles_templates'] = 'simple_template';
		$_REQUEST['content_options'] = 'with_extra_profile with_neighbours';
		$_REQUEST['options'] = 'with_extra_profile';
		$_REQUEST['locked'] = 'Y'; // no direct contributions

	// we are creating a project
	} elseif(isset($_REQUEST['space_type']) && ($_REQUEST['space_type'] == 'project')) {
		$_REQUEST['articles_layout'] = 'none';
		$_REQUEST['locked'] = 'Y'; // only section owner can create sub-sections
		$_REQUEST['options'] = 'view_as_tabs';
		$_REQUEST['sections_layout'] = 'decorated'; // several sub-sections

	// default is to create a group
	} else {
		$_REQUEST['articles_layout'] = 'yabb';
		$_REQUEST['content_options'] = 'with_extra_profile auto_publish comments_as_wall view_as_tabs with_neighbours';
		$_REQUEST['options'] = 'view_as_tabs';
		$_REQUEST['space_type'] = 'group'; // just to be sure...
	}

	// anchor the new section here
	include_once 'section.php';
	$section = new Section();
	$section->load_by_content($anchor);
	$anchor = $section;
	$_REQUEST['anchor'] = $anchor->get_reference();
	$_REQUEST['active_set'] = $_REQUEST['active'];

	// do not break home page layout
	$_REQUEST['home_panel'] = 'none';

	// display the form on error
	if(!$_REQUEST['id'] = Sections::post($_REQUEST)) {
		$item = $_REQUEST;
		$with_form = TRUE;

	// post-processing
	} else {

		// make the surfer an editor of this section
		Members::assign('user:'.Surfer::get_id(), 'section:'.$_REQUEST['id']);

		// also update its watch list
		Members::assign('section:'.$_REQUEST['id'], 'user:'.Surfer::get_id());

		// touch the related anchor
		if(is_object($anchor))
			$anchor->touch('section:create', $_REQUEST['id'], isset($_REQUEST['active']) && ($_REQUEST['active'] != 'Y'));

		// add content to the new group
		if($_REQUEST['space_type'] == 'group') {

			// a sticky page to define group rules
			$fields = array();
			$fields['anchor'] = 'section:'.$_REQUEST['id'];
			$fields['description'] = i18n::c('This is the right place to describe ways of working in this group.');
			$fields['locked'] = 'Y'; // no direct contributions
			$fields['home_panel'] = 'none'; // not mentioned at the home page
			$fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
			$fields['rank'] = 1000; // sticky page
			$fields['title'] = i18n::c('Group policy');
			Articles::post($fields);

			// a welcome thread
			$fields = array();
			$fields['anchor'] = 'section:'.$_REQUEST['id'];
			$fields['home_panel'] = 'none'; // not mentioned at the home page
			$fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
			$fields['title'] = sprintf(i18n::c('Welcome to "%s"'), $_REQUEST['title']);
			Articles::post($fields);

		}

		// add content to the new project
		if($_REQUEST['space_type'] == 'project') {

			// a blogging sub-section, to provide news
			$fields = array();
			$fields['anchor'] = 'section:'.$_REQUEST['id'];
			$fields['articles_layout'] = 'daily';
			$fields['articles_templates'] = 'simple_template';
			$fields['content_options'] = 'with_neighbours';
			$fields['home_panel'] = 'none'; // not mentioned at the home page
			$fields['locked'] = 'Y'; // for the owner only
			$fields['options'] = 'articles_by_publication view_as_tabs'; // to list watchers of the news
			$fields['rank'] = 1000;
			$fields['thumbnail_url'] = $context['url_to_root'].'skins/_reference/thumbnails/news.gif';
			$fields['title'] = i18n::c("What is new?");
			if($id = Sections::post($fields)) {

				// a welcome thread
				$fields = array();
				$fields['anchor'] = 'section:'.$id;
				$fields['home_panel'] = 'none'; // not mentioned at the home page
				$fields['options'] = 'edit_as_simple';
				$fields['publish_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
				$fields['title'] = sprintf(i18n::c('Welcome to "%s"'), $_REQUEST['title']);
				Articles::post($fields);
			}


			// a yabb sub-section, for team joint activities
			$fields = array();
			$fields['anchor'] = 'section:'.$_REQUEST['id'];
			$fields['articles_layout'] = 'yabb';
			$fields['content_options'] = 'auto_publish comments_as_wall view_as_tabs with_neighbours';
			$fields['home_panel'] = 'none'; // not mentioned at the home page
			$fields['introduction'] = i18n::c('Working together');
			$fields['rank'] = 2000;
			$fields['thumbnail_url'] = $context['url_to_root'].'skins/_reference/thumbnails/meeting.gif';
			$fields['title'] = i18n::c('Activities');
			Sections::post($fields);

			// a wiki sub-section, for project documentation
			$fields = array();
			$fields['anchor'] = 'section:'.$_REQUEST['id'];
			$fields['articles_layout'] = 'tagged';
			$fields['articles_templates'] = 'simple_template';
			$fields['content_options'] = 'articles_by_title auto_publish comments_as_wall members_edit view_as_wiki with_neighbours';
			$fields['home_panel'] = 'none'; // not mentioned at the home page
			$fields['introduction'] = i18n::c('Information pages');
			$fields['rank'] = 3000;
			$fields['thumbnail_url'] = $context['url_to_root'].'skins/_reference/thumbnails/information.gif';
			$fields['title'] = i18n::c('Documentation');
			Sections::post($fields);

			// a private sub-section, for internal work
			if($_REQUEST['active'] != 'N') {
				$fields = array();
				$fields['active_set'] = 'N';
				$fields['anchor'] = 'section:'.$_REQUEST['id'];
				$fields['articles_layout'] = 'yabb';
				$fields['content_options'] = 'auto_publish comments_as_wall view_as_tabs with_neighbours';
				$fields['introduction'] = i18n::c('Reserved to project members');
				$fields['home_panel'] = 'none'; // not mentioned at the home page
				$fields['options'] = 'view_as_tabs'; // to list editors and watchers explicitly
				$fields['rank'] = 4000;
				$fields['thumbnail_url'] = $context['url_to_root'].'skins/_reference/thumbnails/meeting.gif';
				$fields['title'] = i18n::c('Private activities');
				Sections::post($fields);
			}
		}

		// increment the post counter of the surfer
		Users::increment_posts(Surfer::get_id());

		// reward the poster
		$context['page_title'] = i18n::s('Congratulation, you have successfully added a new web space');

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		if($_REQUEST['space_type'] == 'blog')
			$menu = array_merge($menu, array(Sections::get_permalink($_REQUEST) => i18n::s('View the new blog')));
		elseif($_REQUEST['space_type'] == 'project')
			$menu = array_merge($menu, array(Sections::get_permalink($_REQUEST) => i18n::s('View the new project')));
		else
			$menu = array_merge($menu, array(Sections::get_permalink($_REQUEST) => i18n::s('View the new group')));
		if(Surfer::may_upload())
			$menu = array_merge($menu, array('images/edit.php?anchor='.urlencode('section:'.$_REQUEST['id']) => i18n::s('Add an image')));
		if(preg_match('/\bwith_files\b/i', $section->item['options']) && Surfer::may_upload())
			$menu = array_merge($menu, array('files/edit.php?anchor='.urlencode('section:'.$_REQUEST['id']) => i18n::s('Upload a file')));
		if(preg_match('/\bwith_links\b/i', $section->item['options']))
			$menu = array_merge($menu, array('links/edit.php?anchor='.urlencode('section:'.$_REQUEST['id']) => i18n::s('Add a link')));
		$follow_up .= Skin::build_list($menu, 'menu_bar');
		$context['text'] .= $follow_up;

	}

// display the form on GET
} else
	$with_form = TRUE;

// display the form
if($with_form) {

	// by default it will be a group space
	if(!isset($item['articles_layout']))
		$item['articles_layout'] = 'group';

	// default title
	if(!isset($item['title'])) {

		// first section
		if(!isset($existing_sections) || !$existing_sections)
			$item['title'] = sprintf(i18n::s('The personal space of %s'), Surfer::get_name());

		// second section
		elseif($existing_sections == 1)
			$item['title'] = sprintf(i18n::s('Another personal space of %s'), Surfer::get_name());

		// subsequent section
		else
			$item['title'] = sprintf(i18n::s('Another personal space of %s (%d)'), Surfer::get_name(), $existing_sections+1);
	}

	// the form to edit a section
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>';

	// layout for related articles
	$label = i18n::s('Purpose');
	$input = i18n::s('What are you aiming to create?');
	$input .= BR.'<input type="radio" name="space_type" value="group" checked="checked"';
	$input .= EOT.i18n::s('A group, for people interested in some topic');
	$input .= BR.'<input type="radio" name="space_type" value="blog"';
	$input .= EOT.i18n::s('A blog, to express your thoughts and to lead the pack');
	$input .= BR.'<input type="radio" name="space_type" value="project"';
	$input .= EOT.i18n::s('A project, to support teamwork');
	$fields[] = array($label, $input);

	// the title
	$label = i18n::s('Title');
	$input = '<textarea name="title" id="title" rows="2" cols="50" accesskey="t">'.encode_field(isset($item['title']) ? $item['title'] : '').'</textarea>';
	$fields[] = array($label, $input);

	// the introduction
	$label = i18n::s('Introduction');

	// introduce the web space
	if(!isset($item['introduction']))
		$item['introduction'] = i18n::s('What is this new web space about?');

	$input = '<textarea name="introduction" rows="5" cols="50" accesskey="i">'.encode_field($item['introduction']).'</textarea>';
	$fields[] = array($label, $input);

	// the active flag: Yes/public, Restricted/logged, No/associates
	$label = i18n::s('Access');
	$input = '<input type="radio" name="active" value="Y" accesskey="v"';
	if(!isset($item['active']) || ($item['active'] == 'Y'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Public - Everybody, including anonymous surfers').' '
		.BR.'<input type="radio" name="active" value="R"';
	if(isset($item['active']) && ($item['active'] == 'R'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Community - Access is granted to any identified surfer').' '
		.BR.'<input type="radio" name="active" value="N"';
	if(isset($item['active']) && ($item['active'] == 'N'))
		$input .= ' checked="checked"';
	$input .= '/> '.i18n::s('Private - Access is restricted to selected persons');
	$fields[] = array($label, $input);

	// actually build the form
	$context['text'] .= Skin::build_form($fields);
	$fields = array();

	//
	// bottom commands
	//
	$menu = array();

	// the submit button
	$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');

	// cancel button
	if(Surfer::get_id())
		$menu[] = Skin::build_link(Users::get_url(Surfer::get_id()), i18n::s('Back to my profile'), 'span');

	// insert the menu in the page
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// end of the form
	$context['text'] .= '</div></form>';

	// append the script used for data checking on the browser
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
		.'// detect changes in form'."\n"
		.'func'.'tion detectChanges() {'."\n"
		."\n"
		.'	var nodes = $("form#main_form input");'."\n"
		.'	for(var index = 0; index < nodes.length; index++) {'."\n"
		.'		var node = nodes[index];'."\n"
		.'		$(node).change(function() { $("#preferred_editor").disabled = true; });'."\n"
		.'	}'."\n"
		."\n"
		.'	nodes = $("form#main_form textarea");'."\n"
		.'	for(var index = 0; index < nodes.length; index++) {'."\n"
		.'		var node = nodes[index];'."\n"
		.'		$(node).change(function() { $("#preferred_editor").disabled = true; });'."\n"
		.'	}'."\n"
		."\n"
		.'	nodes = $("form#main_form select");'."\n"
		.'	for(var index = 0; index < nodes.length; index++) {'."\n"
		.'		var node = nodes[index];'."\n"
		.'		$(node).change(function() { $("#preferred_editor").disabled = true; });'."\n"
		.'	}'."\n"
		.'}'."\n"
		."\n"
		.'// observe changes in form'."\n"
		.'$(document).ready( detectChanges);'."\n"
		."\n"
		.'// set the focus on first form field'."\n"
		.'$("#title").focus();'."\n"
		.JS_SUFFIX."\n";

	// general help on this form
	$help = '<p>'.sprintf(i18n::s('%s and %s are available to enhance text rendering.'), Skin::build_link('codes/', 'YACS codes', 'help'), Skin::build_link('smileys/', 'smileys', 'help')).'</p>';
	$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'boxes', 'help');

}

// render the skin
render_skin();

?>
