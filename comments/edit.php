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
 * @tester Manuel López Gallego
 * @tester Eoin
 * @tester ThierryP
 * @tester Agnes
 * @tester Macnana
 * @tester Lasares
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../shared/xml.php';	// input validation
include_once 'comments.php';

// the maximum size for uploads
$file_maximum_size = str_replace('M', '000000', Safe::get_cfg_var('upload_max_filesize'));
if(!$file_maximum_size || ($file_maximum_size > 50000000))
	$file_maximum_size = 5000000;

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
if(isset($item['id']) && Comments::are_editable($anchor, $item))
	$permitted = TRUE;

// the anchor has to be viewable by this surfer
elseif(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;

// surfer created the comment
elseif(isset($item['create_id']) && Surfer::is($item['create_id']))
	$permitted = TRUE;

// maybe posts are not allowed here
elseif(($action != 'edit') && is_object($anchor) && $anchor->has_option('locked') && !Surfer::is_empowered())
	$permitted = FALSE;

// only authenticated surfers can post new comments, except if anonymous posts have been allowed
elseif(($action != 'edit') && (Surfer::is_logged() || (isset($context['users_with_anonymous_comments']) && ($context['users_with_anonymous_comments'] == 'Y'))))
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
	$context['page_title'] = i18n::s('Add a comment');

// always validate input syntax
if(isset($_REQUEST['description']))
	xml::validate($_REQUEST['description']);

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// an anchor is mandatory
} elseif(!is_object($anchor)) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No anchor has been found.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged() && (!isset($context['users_with_anonymous_comments']) || ($context['users_with_anonymous_comments'] != 'Y')))
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('comments/edit.php?'.$login_hook));

	// permission denied to authenticated user
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// an error occured
} elseif(count($context['error'])) {
	$item = $_REQUEST;
	$with_form = TRUE;

// stop robots
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST') && Surfer::may_be_a_robot()) {
	Skin::error(i18n::s('Please prove you are not a robot.'));
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

	// only authenticated surfers are allowed to post links
//	if(!Surfer::is_logged())
//		$_REQUEST['description'] = preg_replace('/(http:|https:|ftp:|mailto:)[\w@\/\.]+/', '!!!', $_REQUEST['description']);

	// attach file from member, if any
	if(Surfer::is_member() && isset($_FILES['upload']['name']) && $_FILES['upload']['name'] && ($_FILES['upload']['name'] != 'none')) {

		// access the temporary uploaded file
		$file_upload = $_FILES['upload']['tmp_name'];

		// $_FILES transcoding to utf8 is not automatic
		$_FILES['upload']['name'] = utf8::encode($_FILES['upload']['name']);

		// enhance file name
		$file_name = $_FILES['upload']['name'];
		$file_extension = '';
		$position = strrpos($_FILES['upload']['name'], '.');
		if($position !== FALSE) {
			$file_name = substr($_FILES['upload']['name'], 0, $position);
			$file_extension = strtolower(substr($_FILES['upload']['name'], $position+1));
		}
		$_FILES['upload']['name'] = str_replace(array('.', '_', '%20'), ' ', $file_name);
		if($file_extension)
			$_FILES['upload']['name'] .= '.'.$file_extension;

		// ensure we have a file name
		$file_name = utf8::to_ascii($_FILES['upload']['name']);

		// ensure type is allowed
		include_once '../files/files.php';
		if(!Files::is_authorized($_FILES['upload']['name']))
			Skin::error(i18n::s('This type of file is not allowed.'));

		// size exceeds php.ini settings -- UPLOAD_ERR_INI_SIZE
		elseif(isset($_FILES['upload']['error']) && ($_FILES['upload']['error'] == 1))
			Skin::error(i18n::s('The size of this file is over limit.'));

		// size exceeds form limit -- UPLOAD_ERR_FORM_SIZE
		elseif(isset($_FILES['upload']['error']) && ($_FILES['upload']['error'] == 2))
			Skin::error(i18n::s('The size of this file is over limit.'));

		// partial transfer -- UPLOAD_ERR_PARTIAL
		elseif(isset($_FILES['upload']['error']) && ($_FILES['upload']['error'] == 3))
			Skin::error(i18n::s('No file has been transmitted.'));

		// no file -- UPLOAD_ERR_NO_FILE
		elseif(isset($_FILES['upload']['error']) && ($_FILES['upload']['error'] == 4))
			Skin::error(i18n::s('No file has been transmitted.'));

		// zero bytes transmitted
		elseif(!$_FILES['upload']['size'])
			Skin::error(i18n::s('No file has been transmitted.'));

		// check provided upload name
		elseif(!Safe::is_uploaded_file($file_upload))
			Skin::error(i18n::s('Possible file attack.'));

		// process uploaded data
		else {

			// create folders
			$file_path = 'files/'.str_replace(':', '/', $anchor->get_reference());
			Safe::make_path($file_path);

			// make an absolute path
			$file_path = $context['path_to_root'].$file_path.'/';

			// move the uploaded file
			if(!Safe::move_uploaded_file($file_upload, $file_path.$file_name))
				Skin::error(sprintf(i18n::s('Impossible to move the upload file to %s.'), $file_path.$file_name));

			// this will be filtered by umask anyway
			else {
				Safe::chmod($file_path.$file_name, $context['file_mask']);

				// update an existing record for this anchor
				if($match =& Files::get_by_anchor_and_name($anchor->get_reference(), $file_name))
					$fields = $match;

				// create a new file record
				else {
					$fields = array();
					$fields['file_name'] = $file_name;
					$fields['file_size'] = $_FILES['upload']['size'];
					$fields['file_href'] = '';
					$fields['anchor'] = $anchor->get_reference();
				}

				// create the record in the database, and remember this post in comment
				if($file_id = Files::post($fields))
					$_REQUEST['description'] .= "\n\n[file=".$file_id.']';

			}
		}
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
		$anchor->touch('comment:create', $_REQUEST['id'], isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y'));

		// clear cache
		Comments::clear($_REQUEST);

		// increment the post counter of the surfer
		Users::increment_posts(Surfer::get_id());

		// thanks
		$context['page_title'] = i18n::s('Thank you for your contribution');

		// the type, except on wikis and manuals
		if(is_object($anchor) && !$anchor->has_layout('manual') && !$anchor->has_layout('wiki'))
			$context['text'] .= Comments::get_img($_REQUEST['type']);

		// actual content
		$context['text'] .= Codes::beautify($_REQUEST['description']);

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		if($anchor->has_layout('alistapart'))
			$menu = array_merge($menu, array($anchor->get_url('parent') => $anchor->get_label('comments', 'thread_command')));
		else
			$menu = array_merge($menu, array($anchor->get_url('discuss') => $anchor->get_label('comments', 'thread_command')));
		if(Surfer::is_logged())
			$menu = array_merge($menu, array(Comments::get_url($_REQUEST['id'], 'edit') => $anchor->get_label('comments', 'edit_command')));
		$follow_up .= Skin::build_list($menu, 'page_menu');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

		// comment author
		if($author = Surfer::get_name())
			$author = sprintf(i18n::c('Comment by %s'), $author);
		else
			$author = i18n::c('Anonymous comment');

		// log the submission of a new comment
		$label = sprintf(i18n::c('%s: %s'), $author, strip_tags($anchor->get_title()));
		$description = $context['url_to_home'].$context['url_to_root'].Comments::get_url($_REQUEST['id']);

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
		$anchor->touch('comment:update', $item['id'], isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y') );

		// clear cache
		Comments::clear($_REQUEST);

		// forward to the updated thread
		Safe::redirect($context['url_to_home'].$context['url_to_root'].$anchor->get_url('discuss'));

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
		if($item['create_name'])
			$item['description'] = sprintf(i18n::s('%s:'), ucfirst($item['create_name']))."\n\n";
		elseif($item['edit_name'])
			$item['description'] = sprintf(i18n::s('%s:'), ucfirst($item['edit_name']))."\n\n";
		else
			$item['description'] = '';

	}

	// the form to edit a comment
	$context['text'] .= '<form method="post" enctype="multipart/form-data" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>';

	// reference the anchor page
	if(is_object($anchor))
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
		$input = '<input type="text" name="edit_name" size="45" maxlength="128" accesskey="n" value="'.encode_field(isset($_REQUEST['edit_name']) ? $_REQUEST['edit_name'] : Surfer::get_name()).'" />';
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

	// the type, except on wikis and manuals
	if(is_object($anchor) && !$anchor->has_layout('manual') && !$anchor->has_layout('wiki')) {
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

	// append surfer signature, if any
	if(!isset($item['description']) && Surfer::get_id() && ($user =& Users::get(Surfer::get_id())) && isset($user['signature']) && trim($user['signature'])) {
		if(isset($_SESSION['surfer_editor']) && ($_SESSION['surfer_editor'] != 'yacs'))
			$item['description'] = "<p>&nbsp;</p><div>-----<br />".$user['signature'].'</div>';
		else
			$item['description'] = "\n\n\n\n-----\n".$user['signature'];
	}

	// use the editor if possible
	$input = Surfer::get_editor('description', isset($item['description']) ? $item['description'] : '');
	$fields[] = array($label, $input);

	// attach a file on first post, and if allowed
	if(Surfer::may_upload() && (!isset($item['id']) || ($action == 'quote') || ($action == 'reply'))) {

		// attachment label
		$label = i18n::s('Upload a file');

		// an upload entry
		$input = '<input type="hidden" name="file_type" value="upload" />'
			.'<input type="hidden" name="MAX_FILE_SIZE" value="'.$file_maximum_size.'" />'
			.'<input type="file" name="upload" size="30" />';

		// upload hint
		$size_hint = preg_replace('/000$/', 'k', preg_replace('/000000$/', 'M', $file_maximum_size));
		$hint = sprintf(i18n::s('You can upload a file of less than %s'), Skin::build_number($size_hint, i18n::s('bytes')))."\n";

		$fields[] = array($label, $input, $hint);

	}

	// build the form
	$context['text'] .= Skin::build_form($fields);

	// bottom commands
	$menu = array();
	$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's', 'submit_button');
	$menu[] = '<a href="#" onclick="$(\'preview_flag\').setAttribute(\'value\', \'Y\'); $(\'submit_button\').click(); return false;" accesskey="p" title="'.i18n::s('Press [p] for preview').'"><span>'.i18n::s('Preview').'</span></a>';
	if(is_object($anchor))
		$menu[] = Skin::build_link($anchor->get_url('discuss'), i18n::s('Cancel'), 'span');
	$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

	// associates and editors may decide to not stamp changes -- complex command
	if((Surfer::is_associate() || (is_object($anchor) && $anchor->is_editable())) && Surfer::has_all())
		$context['text'] .= '<p><input type="checkbox" name="silent" value="Y" /> '.i18n::s('Do not change modification date of the main page.').'</p>';

	// transmit the id as a hidden field
	if(isset($item['id']) && $item['id'])
		$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'" />';

	// enable comments threading
	if(isset($reference_item['id']) && $reference_item['id'])
		$context['text'] .= '<input type="hidden" name="previous_id" value="'.$reference_item['id'].'" />';

	// allow post preview
	$context['text'] .= '<input type="hidden" name="preview" value="N" id="preview_flag" />';

	// end of the form
	$context['text'] .= '</div></form>';

	// the script used for form handling at the browser
	$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
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
		.'// detect changes in form'."\n"
		.'func'.'tion detectChanges() {'."\n"
		."\n"
		.'	var nodes = $$("form#main_form input");'."\n"
		.'	for(var index = 0; index < nodes.length; index++) {'."\n"
		.'		var node = nodes[index];'."\n"
		.'		Event.observe(node, "change", function() { $("preferred_editor").disabled = true; });'."\n"
		.'	}'."\n"
		."\n"
		.'	nodes = $$("form#main_form textarea");'."\n"
		.'	for(var index = 0; index < nodes.length; index++) {'."\n"
		.'		var node = nodes[index];'."\n"
		.'		Event.observe(node, "change", function() { $("preferred_editor").disabled = true; });'."\n"
		.'	}'."\n"
		."\n"
		.'	nodes = $$("form#main_form select");'."\n"
		.'	for(var index = 0; index < nodes.length; index++) {'."\n"
		.'		var node = nodes[index];'."\n"
		.'		Event.observe(node, "change", function() { $("preferred_editor").disabled = true; });'."\n"
		.'	}'."\n"
		.'}'."\n"
		."\n"
		.'// observe changes in form'."\n"
		.'Event.observe(window, "load", detectChanges);'."\n"
		."\n"
		.'// ]]></script>'."\n";

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
	$help .= ' '.sprintf(i18n::s('%s and %s are available to enhance text rendering.'), Skin::build_link('codes/', i18n::s('YACS codes'), 'help'), Skin::build_link('smileys/', i18n::s('smileys'), 'help')).'</p>';

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
	$context['extra'] .= Skin::build_box(i18n::s('Help'), $help, 'navigation', 'help');

}

// render the skin
render_skin();

?>