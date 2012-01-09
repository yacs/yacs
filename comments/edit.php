<?php
/**
 * post a new comment or update an existing one
 *
 * A button-based editor is used for the description field.
 * It's aiming to introduce most common [link=codes]codes/index.php[/link] supported by YACS.
 * Also, sample smilies are displayed, and may be used to introduce related codes into the description field.
 *
 * Surfers are given the opportunity to attach a file to new comments.
 * When this happens, file are actually attached to anchor's comment, and
 * referenced in comments.
 *
 * A preview mode is available before actually saving the comment in the database.
 *
 * This script attempts to validate the new or updated comment against a standard PHP XML parser.
 * The objective is to spot malformed or unordered HTML and XHTML tags. No more, no less.
 *
 * On anonymous usage YACS attempts to stop robots by generating a random string and by asking user to type it.
 *
 * On new comment by a non-associate a mail is sent to the system operator.
 * Also, a mail message is sent to the article creator if he/she has a valid address.
 *
 * Associates have the opportunity to attach this comment to another article.
 *
 * The permission assessment is based upon following rules applied in the provided orders:
 * - on modification, associates and authenticated editors are allowed to move forward
 * - on new post, associates and editors are allowed to move forward
 * - permission is denied if the anchor is not viewable
 * - surfer created the comment
 * - this is a new post and the surfer is an authenticated user
 * - this is a new post and anonymous posts have been allowed
 * - permission denied is the default
 *
 * When permission is denied, anonymous (not-logged) surfer are invited to register to be able to post new comments.
 *
 * Accepted calls:
 * - edit.php/&lt;type&gt;/&lt;id&gt;			create a new comment for this anchor
 * - edit.php?anchor=&lt;type&gt;:&lt;id&gt;	create a new comment for this anchor
 * - edit.php/quote/&lt;id&gt;					quote an existing comment
 * - edit.php?quote=&lt;id&gt;					quote an existing comment
 * - edit.php/reply/&lt;id&gt;					reply to an existing comment
 * - edit.php?reply=&lt;id&gt;					reply to an existing comment
 * - edit.php/&lt;id&gt;						modify an existing comment
 * - edit.php?id=&lt;id&gt; 					modify an existing comment
 *
 * If the anchor for this item specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @author Vincent No&euml;l
 * @author GnapZ
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @tester GnapZ
 * @tester Manuel Lopez Gallego
 * @tester Eoin
 * @tester Thierry Pinelli (ThierryP)
 * @tester Agnes
 * @tester Macnana
 * @tester Alain Lesage (Lasares)
 * $tester Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../shared/xml.php';	// input validation
include_once 'comments.php';

// what should we do?
$action = '';
$id = NULL;
$target_anchor = NULL;

// parameters transmitted through friendly urls -- login_hook is used only if authentication is required
$login_hook = '';
if(isset($context['arguments'][0]) && $context['arguments'][0]) {

	// quote an existing comment
	if($context['arguments'][0] == 'quote') {
		$action = 'quote';
		$id = $context['arguments'][1];
		$login_hook = 'quote='.$id;

	// reply to an existing comment
	} elseif($context['arguments'][0] == 'reply') {
		$action = 'reply';
		$id = $context['arguments'][1];
		$login_hook = 'reply='.$id;

	// create a new comment for the provided anchor
	} elseif(isset($context['arguments'][1]) && $context['arguments'][1]) {
		$action = 'new';
		$target_anchor = $context['arguments'][0].':'.$context['arguments'][1];
		$login_hook = 'anchor='.$target_anchor;

	// modify an existing comment
	} else {
		$action = 'edit';
		$id = $context['arguments'][0];
		$login_hook = 'id='.$id;
	}

// parameters transmitted in the query string
} elseif(isset($_REQUEST['quote']) && $_REQUEST['quote']) {
	$action = 'quote';
	$id = $_REQUEST['quote'];
	$login_hook = 'quote='.$id;

} elseif(isset($_REQUEST['reply']) && $_REQUEST['reply']) {
	$action = 'reply';
	$id = $_REQUEST['reply'];
	$login_hook = 'reply='.$id;

} elseif(isset($_REQUEST['id']) && $_REQUEST['id']) {
	$action = 'edit';
	$id = $_REQUEST['id'];
	$login_hook = 'id='.$id;

} elseif(isset($_REQUEST['anchor']) && $_REQUEST['anchor']) {
	$action = 'new';
	$target_anchor = $_REQUEST['anchor'];
	$login_hook = 'anchor='.$target_anchor;
}

// fight hackers
$id = strip_tags($id);
$target_anchor = strip_tags($target_anchor);

// get the item from the database
$item =& Comments::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor =& Anchors::get($item['anchor']);
elseif($target_anchor)
	$anchor =& Anchors::get($target_anchor);

// associates and authenticated editors can modify any comment
if(($action != 'edit') && Comments::allow_creation($anchor))
	$permitted = TRUE;

// modification is allowed
elseif(($action == 'edit') && Comments::allow_modification($anchor, $item))
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// do not always show the edition form
$with_form = FALSE;

// load the skin, maybe with a variant
load_skin('comments', $anchor);

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'comments/' => i18n::s('Comments') );

if(is_object($anchor))
	$context['page_title'] = sprintf(i18n::s('Comment: %s'), $anchor->get_title());
else
	$context['page_title'] = i18n::s('Post a comment');

// validate input syntax only if required
if(isset($_REQUEST['option_validate']) && ($_REQUEST['option_validate'] == 'Y')) {
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
	if(!Surfer::is_logged() && (!isset($context['users_with_anonymous_comments']) || ($context['users_with_anonymous_comments'] != 'Y')))
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('comments/edit.php?'.$login_hook));

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// an error occured
} elseif(count($context['error'])) {
	$item = $_REQUEST;
	$with_form = TRUE;

// stop robots
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST') && Surfer::may_be_a_robot()) {
	Logger::error(i18n::s('Please prove you are not a robot.'));
	$item = $_REQUEST;
	$with_form = TRUE;

// process uploaded data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// protect from hackers
	if(isset($_REQUEST['edit_name']))
		$_REQUEST['edit_name'] = preg_replace(FORBIDDEN_IN_NAMES, '_', $_REQUEST['edit_name']);
	if(isset($_REQUEST['edit_address']))
		$_REQUEST['edit_address'] =& encode_link($_REQUEST['edit_address']);

	// track anonymous surfers
	Surfer::track($_REQUEST);

	// remove default string, if any
	$_REQUEST['description'] = preg_replace('/^'.preg_quote(i18n::s('Contribute to this page!'), '/').'/', '', ltrim($_REQUEST['description']));

	// append to previous comment during 10 minutes
	if(!isset($item['id'])
		&& ($newest = Comments::get_newest_for_anchor($anchor->get_reference()))
		&& ($newest['type'] != 'notification')
		&& Surfer::get_id() && (isset($newest['create_id']) && (Surfer::get_id() == $newest['create_id']))
		&& ($newest['edit_date'] > gmstrftime('%Y-%m-%d %H:%M:%S', time() - 600))) {

		// copy from previous comment record
		$_REQUEST['id'] 			= $newest['id'];
		$_REQUEST['create_address']	= $newest['create_address'];
		$_REQUEST['create_date']	= $newest['create_date'];
		$_REQUEST['create_id']		= $newest['create_id'];
		$_REQUEST['create_name']	= $newest['create_name'];
		$_REQUEST['description']	= $newest['description'].BR.$_REQUEST['description'];
		$_REQUEST['previous_id']	= $newest['previous_id'];
		$_REQUEST['type']			= $newest['type'];

	}

	// attach some file
	$file_path = Files::get_path($anchor->get_reference());
	if(isset($_FILES['upload']) && $file = Files::upload($_FILES['upload'], $file_path, $anchor->get_reference())) {
		if(!$_REQUEST['description'])
			$_REQUEST['description'] .= '<p>&nbsp;</p>';
		$_REQUEST['description'] .= '<div style="margin-top: 1em;">'.$file.'</div>';
	}

	// preview mode
	if(isset($_REQUEST['preview']) && ($_REQUEST['preview'] == 'Y')) {
		$item = $_REQUEST;
		$with_form = TRUE;

	// display the form on error
	} elseif(!$_REQUEST['id'] = Comments::post($_REQUEST)) {
		$item = $_REQUEST;
		$with_form = TRUE;

	// reward the poster for new posts
	} elseif(!isset($item['id'])) {

		// touch the related anchor
		$anchor->touch('comment:create', $_REQUEST['id'],
			isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y'),
			isset($_REQUEST['notify_watchers']) && ($_REQUEST['notify_watchers'] == 'Y'),
			isset($_REQUEST['notify_followers']) && ($_REQUEST['notify_followers'] == 'Y'));

		// clear cache
		Comments::clear($_REQUEST);

		// increment the post counter of the surfer
		Users::increment_posts(Surfer::get_id());

		// thanks
		$context['page_title'] = i18n::s('Thank you for your contribution');

		// actual content
		$context['text'] .= Codes::beautify($_REQUEST['description']);

		// list persons that have been notified
		$context['text'] .= Mailer::build_recipients();

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		if($anchor->has_layout('alistapart'))
			$menu = array_merge($menu, array($anchor->get_url('parent') => $anchor->get_label('permalink_command', 'comments', i18n::s('View the page'))));
		else
			$menu = array_merge($menu, array($anchor->get_url('comments') => $anchor->get_label('permalink_command', 'comments', i18n::s('View the page'))));
		if(Surfer::is_logged())
			$menu = array_merge($menu, array(Comments::get_url($_REQUEST['id'], 'edit') => $anchor->get_label('edit_command', 'comments')));
		$follow_up .= Skin::build_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

		// comment author
		if($author = Surfer::get_name())
			$author = sprintf(i18n::c('Comment by %s'), $author);
		else
			$author = i18n::c('Anonymous comment');

		// log the submission of a new comment
		$label = sprintf(i18n::c('%s: %s'), $author, strip_tags($anchor->get_title()));
                $link = $context['url_to_home'].$context['url_to_root'].Comments::get_url($_REQUEST['id']);
		$description = '<a href="'.$link.'">'.$link.'</a>';

		// notify sysops
		Logger::notify('comments/edit.php', $label, $description);

	// update of an existing comment
	} else {

		// remember the previous version
		if($item['id']) {
			include_once '../versions/versions.php';
			Versions::save($item, 'comment:'.$item['id']);
		}

		// touch the related anchor
		$anchor->touch('comment:update', $item['id'],
			isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y'),
			isset($_REQUEST['notify_watchers']) && ($_REQUEST['notify_watchers'] == 'Y'),
			isset($_REQUEST['notify_followers']) && ($_REQUEST['notify_followers'] == 'Y'));

		// clear cache
		Comments::clear($_REQUEST);

		// forward to the updated thread
		Safe::redirect($context['url_to_home'].$context['url_to_root'].$anchor->get_url('comments'));

	}

// display the form on GET
} else
	$with_form = TRUE;

// display the form
if($with_form) {

	$reference_item = array();

	// preview a comment
	if(isset($_REQUEST['preview']) && ($_REQUEST['preview'] == 'Y')) {
		$context['text'] .= Skin::build_block(i18n::s('Preview of your post:'), 'title')
			.Codes::beautify($item['description']);

		$context['text'] .= Skin::build_block(i18n::s('Edit your post below'), 'title');

	// quote the previous comment, if any
	} elseif(isset($item['id']) && ($action == 'quote')) {
		$reference_item = $item;
		$item['id'] = '';
		$id = '';
		$item['description'] = '[quote]'.$item['description'].'[/quote]'."\n\n";

	// reply to a previous comment, if any
	} elseif(isset($item['id']) && ($action == 'reply')) {
		$reference_item = $item;
		$item['id'] = '';
		$id = '';

		// isolate first name of initial contributor
		if($item['create_name'])
			list($first_name, $dummy) = explode(' ', ucfirst($item['create_name']), 2);
		elseif($item['edit_name'])
			list($first_name, $dummy) = explode(' ', ucfirst($item['edit_name']), 2);
		else
			$first_name = '';

		// insert it in this contribution
		if($first_name)
			$item['description'] = sprintf(i18n::s('%s:'), $first_name)."\n\n";
		else
			$item['description'] = '';

	}

	// the form to edit a comment
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form" enctype="multipart/form-data"><div>';

	// reference the anchor page
	$context['text'] .= '<input type="hidden" name="anchor" value="'.$anchor->get_reference().'" />';

	// display info on current version
	if(isset($item['id']) && !preg_match('/(new|quote|reply)/', $action)) {

		// the creator
		if(isset($item['create_date'])) {
			$label = i18n::s('Posted by');
			$text = Users::get_link($item['create_name'], $item['create_address'], $item['create_id'])
				.' '.Skin::build_date($item['create_date']);
			$fields[] = array($label, $text);
		}

		// the last editor
		if(isset($item['edit_id']) && isset($item['create_id']) && ($item['edit_id'] != $item['create_id'])) {
			$label = i18n::s('Edited by');
			$text = Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id'])
				.' '.Skin::build_date($item['edit_date']);
			$fields[] = array($label, $text);
		}

	// additional fields for anonymous surfers
	} elseif(!Surfer::is_logged()) {

		// splash
		if(isset($item['id']))
			$login_url = $context['url_to_root'].'users/login.php?url='.urlencode('comments/edit.php?id='.$item['id'].'&anchor='.$target_anchor);
		else
			$login_url = $context['url_to_root'].'users/login.php?url='.urlencode('comments/edit.php?anchor='.$target_anchor);
		$context['text'] .= '<p>'.sprintf(i18n::s('If you have previously registered to this site, please %s. Then the server will automatically put your name and address in following fields.'), Skin::build_link($login_url, i18n::s('authenticate')))."</p>\n";

		// the name, if any
		$label = i18n::s('Your name');
		$input = '<input type="text" name="edit_name" size="45" maxlength="128" accesskey="n" value="'.encode_field(isset($_REQUEST['edit_name']) ? $_REQUEST['edit_name'] : Surfer::get_name(' ')).'" />';
		$hint = i18n::s('This optional field can be left blank if you wish.');
		$fields[] = array($label, $input, $hint);

		// the address, if any
		$label = i18n::s('Your address');
		$input = '<input type="text" name="edit_address" size="45" maxlength="128" accesskey="a" value="'.encode_field(isset($_REQUEST['edit_address']) ? $_REQUEST['edit_address'] : Surfer::get_email_address()).'" />';
		$hint = i18n::s('e-mail or web address; this field is optional');
		$fields[] = array($label, $input, $hint);

		// stop robots
		if($field = Surfer::get_robot_stopper())
			$fields[] = $field;

	}

	// the type
	if(is_object($anchor)) {
		$label = i18n::s('Your intent');
		if(isset($item['type']))
			$type = $item['type'];
		elseif(isset($_REQUEST['type']))
			$type = $_REQUEST['type'];
		else
			$type = '';
		$input = Comments::get_radio_buttons('type', $type);
		$hint = i18n::s('Please carefully select a type adequate for your comment.');
		$fields[] = array($label, $input, $hint);
	}

	// the description
	$label = i18n::s('Your contribution');

	// use the editor if possible
	$input = Surfer::get_editor('description', isset($item['description']) ? $item['description'] : '');
	$fields[] = array($label, $input);

	// add a file on first post, and if allowed
	if(Surfer::may_upload() && (!isset($item['id']) || ($action == 'quote') || ($action == 'reply'))) {

		// attachment label
		$label = i18n::s('Add a file');

		// an upload entry
		$input = '<input type="hidden" name="file_type" value="upload" />'
			.'<input type="file" name="upload" size="30" />'
			.' (&lt;&nbsp;'.$context['file_maximum_size'].i18n::s('bytes').')';

		$fields[] = array($label, $input);

	}

	// build the form
	$context['text'] .= Skin::build_form($fields);

	// bottom commands
	$menu = array();
	$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's', 'submit_button');
	$menu[] = '<a href="#" onclick="$(\'#preview_flag\').attr(\'value\', \'Y\'); $(\'#submit_button\').click(); return false;" accesskey="p" title="'.i18n::s('Press [p] for preview').'"><span>'.i18n::s('Preview').'</span></a>';
	if(is_object($anchor))
		$menu[] = Skin::build_link($anchor->get_url('comments'), i18n::s('Cancel'), 'span');
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// optional checkboxes
	$context['text'] .= '<p>';

	// do not process notifications for draft articles
	if(strncmp($anchor->get_reference(), 'article:', strlen('article:')) || ($anchor->get_value('publish_date', NULL_DATE) > NULL_DATE)) {

		// notify watchers
		if($action != 'edit')
			$context['text'] .= '<input type="checkbox" name="notify_watchers" value="Y" checked="checked" /> '.i18n::s('Notify watchers').BR;

		// notify people following me
		if(($action != 'edit') && Surfer::get_id() && !$anchor->is_hidden())
			$context['text'] .= '<input type="checkbox" name="notify_followers" value="Y" /> '.i18n::s('Notify my followers').BR;

	}

	// associates and editors may decide to not stamp changes -- complex command
	if((Surfer::is_associate() || (is_object($anchor) && $anchor->is_assigned())) && Surfer::has_all())
		$context['text'] .= '<input type="checkbox" name="silent" value="Y" /> '.i18n::s('Do not change modification date of the main page.').BR;

	// validate page content
	$context['text'] .= '<input type="checkbox" name="option_validate" value="Y" checked="checked" /> '.i18n::s('Ensure this post is valid XHTML.').'</p>';

	// transmit the id as a hidden field
	if(isset($item['id']) && $item['id'])
		$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'" />';

	// enable comments threading
	if(isset($reference_item['id']) && $reference_item['id'])
		$context['text'] .= '<input type="hidden" name="previous_id" value="'.$reference_item['id'].'" />';

	// preserve threading through preview
	elseif(isset($_REQUEST['previous_id']))
		$context['text'] .= '<input type="hidden" name="previous_id" value="'.$_REQUEST['previous_id'].'" />';

	// allow post preview
	$context['text'] .= '<input type="hidden" name="preview" value="N" id="preview_flag" />';

	// end of the form
	$context['text'] .= '</div></form>';

	// the script used for form handling at the browser
	$context['text'] .= JS_PREFIX
		.'// check that main fields are not empty'."\n"
		.'func'.'tion validateDocumentPost(container) {'."\n"
		."\n"
		.'	// description is mandatory, but only if the field is visible'."\n"
		.'	if(!container.description.value && (container.description.style.display != \'none\')) {'."\n"
		.'		alert("'.i18n::s('Please type a valid comment').'");'."\n"
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
		.JS_SUFFIX;

	// reply or quote
	if(isset($reference_item['description']) && $reference_item['description'] && (($action == 'quote') || ($action == 'reply'))) {
		$excerpt = Skin::strip($reference_item['description'], 200);
		$poster = $reference_item['create_name'];

	// insert anchor excerpt
	} elseif(is_object($anchor)) {
		$excerpt = $anchor->get_teaser('quote');
		if($poster = $anchor->get_poster())
			$poster = $poster['nick_name'];
	}

	// show the excerpt, if any
	if($excerpt) {

		// poster information -- second %s is to save one string in .po files
		if($poster)
			$excerpt = sprintf(i18n::s('posted by %s %s'), $poster, '')."\n\n".$excerpt;

		// we are populating a form field
		$excerpt = strip_tags(preg_replace('/<br(\s*\/)>\n*/i', "\n", $excerpt));

		$context['text'] .= '<p>'.i18n::s('You can cut and paste some text from the field below to shape your post.').'</p>'."\n"
			.'<textarea readonly="readonly" rows="20" cols="60">'.$excerpt.'</textarea>'."\n";
	}

	// page help
	$help = '<p>'.i18n::s('Hearty discussion and unpopular viewpoints are welcome, but please keep comments on-category and civil. Flaming, trolling, and smarmy comments are discouraged and may be deleted. In fact, we reserve the right to delete any post for any reason. Don\'t make us do it.').'</p>';
	if(!Surfer::is_logged())
		$help .= '<p>'.i18n::s('Since you are posting anonymously, most HTML tags and web addresses are removed.');
	elseif(!Surfer::is_associate())
		$help .= '<p>'.i18n::s('Most HTML tags are removed.');
	else
		$help .= '<p>';
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

}

// render the skin
render_skin();

?>
