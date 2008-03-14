<?php
/**
 * set a new action or update an existing one
 *
 * This script is used to add a new action to a to-do list, or to modify an existing item.
 *
 * When a new action has been recorded:
 * - an e-mail message is sent to the target user
 * - the event is logged if the action has not been created by an associate
 *
 * A button-based editor is used for the description field.
 * It's aiming to introduce most common [link=codes]codes/index.php[/link] supported by YACS.
 *
 * This script attempts to validate the new or updated article description against a standard PHP XML parser.
 * The objective is to spot malformed or unordered HTML and XHTML tags. No more, no less.
 *
 * The anchor is always touched silently, and there is no related option displayed in the edit form.
 *
 * Restrictions apply on this page:
 * - associates and editors are allowed to move forward
 * - permission is denied if the anchor is not viewable by this surfer
 * - permission is granted if the anchor is the profile of this member
 * - poster of the action can modify it as well
 * - else permission is denied
 *
 * Accepted calls:
 * - edit.php?anchor=&lt;type&gt;:&lt;id&gt;	add a new action for the anchor
 * - edit.php/&lt;id&gt;					modify an existing action
 * - edit.php?id=&lt;id&gt;				modify an existing action
 *
 * If the anchor for this item specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author Vincent No&euml;l
 * @author GnapZ
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
include_once 'actions.php';
$item =& Actions::get($id);

// look for the target anchor on item creation
$target_anchor = NULL;
if(isset($_REQUEST['anchor']))
    $target_anchor = $_REQUEST['anchor'];
elseif(isset($context['arguments'][1]))
    $target_anchor = $context['arguments'][0].':'.$context['arguments'][1];
$target_anchor = strip_tags($target_anchor);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor = Anchors::get($item['anchor']);
elseif($target_anchor)
    $anchor = Anchors::get($target_anchor);

// associates and editors can do what they want
if(Surfer::is_associate() || (is_object($anchor) && $anchor->is_editable()))
	$permitted = TRUE;

// the anchor has to be viewable by this surfer
elseif(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;

// the action is anchored to the profile of this member
elseif(Surfer::is_member() && (($target_anchor == 'user:'.Surfer::get_id()) || ($item['anchor'] == 'user:'.Surfer::get_id())))
	$permitted = TRUE;

// the action has been created by this member
elseif(Surfer::is_member() && $item['create_id'] && ($item['create_id'] == Surfer::get_id()))
	$permitted = TRUE;

// members are allowed to post new actions
elseif(Surfer::is_member() && !$item['id'])
	$permitted = TRUE;

// the default is to deny access
else
	$permitted = FALSE;

// do not always show the edition form
$with_form = FALSE;

// load localized strings
i18n::bind('actions');

// load the skin, maybe with a variant
load_skin('actions', $anchor);

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
    $context['path_bar'] = $anchor->get_path_bar();
else
    $context['path_bar'] = array( 'actions/' => i18n::s('Actions') );

// the title of the page
if($item['id'])
    $context['page_title'] = i18n::s('Update an action');
else
    $context['page_title'] = i18n::s('Add an action');

// always validate input syntax
if(isset($_REQUEST['description']))
	validate($_REQUEST['description']);

// permission denied
if(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged() && isset($_REQUEST['anchor']))
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('actions/edit.php?id='.$id.'&anchor='.$target_anchor));

	// permission denied to authenticated user
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// an anchor is mandatory
} elseif(!is_object($anchor)) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No anchor has been found.'));

// maybe posts are not allowed here
} elseif(is_object($anchor) && $anchor->has_option('locked') && !Surfer::is_associate()) {

	if(isset($item['id']))
		$label = i18n::s('This page has been locked. It cannot be modified anymore.');
	else
		$label = i18n::s('Posts are not allowed anymore here.');
	Skin::error($label);

// an error occured
} elseif(count($context['error'])) {
	$item = $_REQUEST;
	$with_form = TRUE;

// change editor
} elseif(isset($_REQUEST['preferred_editor']) && $_REQUEST['preferred_editor'] && ($_REQUEST['preferred_editor'] != $_SESSION['surfer_editor'])) {
	$_SESSION['surfer_editor'] = $_REQUEST['preferred_editor'];
	$item = $_REQUEST;
	$with_form = TRUE;

// process uploaded data
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {

	// the follow-up page
	$next = $context['url_to_root'].$anchor->get_url();

	// protect from hackers
	$_REQUEST['target_url'] = preg_replace(FORBIDDEN_CHARS_IN_URLS, '_', $_REQUEST['target_url']);

	// remember status changes only
	if($item['status'] && ($item['status'] == $_REQUEST['status'])) {
		unset($_REQUEST['status']);
		unset($_REQUEST['status_date']);
	}

	// an error has already been encountered
	if(count($context['error'])) {
		$item = $_REQUEST;
		$with_form = TRUE;

	// display the form on error
	} elseif(!$id = Actions::post($_REQUEST)) {
		$item = $_REQUEST;
		$with_form = TRUE;

	// reward the poster for new posts
	} elseif(!$item['id']) {

		// touch the related anchor silently
		$anchor->touch('action:create', $id, TRUE);

		// increment the post counter of the surfer
		Users::increment_posts(Surfer::get_id());

		// thanks
		$context['page_title'] = i18n::s('The action has been put in the to-do list');

 		// action overview
 		$context['text'] .= Skin::build_block(i18n::s('Overview of your post:'), 'title')
 			.'<p><b>'.Codes::beautify_title($_REQUEST['title']).'</b></p>'
 			.Codes::beautify($_REQUEST['description']);

	    // splash message
 		$context['text'] .= Skin::build_block(i18n::s('What do you want to do now?'), 'title');

		// follow-up commands
		$menu = array();
		if(is_object($anchor))
			$menu = array_merge($menu, array($anchor->get_url() => i18n::s('View the main page')));
		$menu = array_merge($menu, array(Actions::get_url($id, 'edit') => i18n::s('Edit the action')));
 		$context['text'] .= Skin::build_list($menu, 'menu_bar');

		// send an e-mail message to the target end user, if any
		if(($to = $anchor->get_value('email')) && preg_match('/.+@.+/', $to)) {

			// message subject
			$subject = sprintf(i18n::s('New to-do: %s'), strip_tags($_REQUEST['title']));

			// message body
			$message = sprintf(i18n::s("The following action has been added to your to-do list. Please process it as soon as possible to ensure minimal delay.\n\nSender: %s\n\n%s\n\n%s\n\n"), Surfer::get_name(), strip_tags(preg_replace('/<br *\/*>/i', "\n", Codes::beautify($_REQUEST['description']))), $context['url_to_home'].$context['url_to_root'].Actions::get_url($id));

			// actual post - don't stop on error
		    include_once $context['path_to_root'].'shared/mailer.php';
			Mailer::notify($to, $subject, $message);

		}

		// log the submission of a new comment by a non-associate
		if(!Surfer::is_associate()) {
			$label = sprintf(i18n::c('New action for %s'), strip_tags($anchor->get_title()));
			$description = $context['url_to_home'].$context['url_to_root'].Actions::get_url($id);
			Logger::notify('actions/edit.php', $label, $description);
		}

	// update of an existing action
	} else {

		// touch the related anchor silently
		$anchor->touch('action:update', $item['id'], TRUE);

		// forward to the updated anchor page
    	Safe::redirect($context['url_to_home'].$context['url_to_root'].$anchor->get_url().'#actions');
	}

// display the form on GET
} else
	$with_form = TRUE;

// display the form
if($with_form) {

	// the anchor thumbnail, if any
	if(is_object($anchor))
		$context['page_image'] = $anchor->get_thumbnail_url();

	// insert anchor prefix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_prefix();

	// splash message
	$context['text'] .= '<p>'.i18n::s('You can use actions to notify immediately other members of new things to do. Actions are sent by email where possible. Else they are listed at login time.')."</p>\n";

	// reference the anchor page
	if(is_object($anchor) && $anchor->is_viewable())
		$context['text'] .= '<p>'.sprintf(i18n::s('Action assigned to: %s'), Skin::build_link($anchor->get_url(), $anchor->get_title()))."</p>\n";

	// the form to edit an action
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>';

	// display info on current version
	if($item['id']) {

		// the initial poster
		if($item['create_id']) {
			$text = Users::get_link($item['create_name'], $item['create_address'], $item['create_id'])
				.' '.Skin::build_date($item['create_date']);
			$fields[] = array(i18n::s('Posted by'), $text);
		}

		// the last editor
		if($item['edit_id']) {
			$text = Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id'])
				.' '.Skin::build_date($item['edit_date']);
			$fields[] = array(i18n::s('Edited by'), $text);
		}

	}

	// title
	$label = i18n::s('Title');
	$input = '<input type="text" name="title" id="title" size="40" value="'.encode_field($item['title']).'" />';
	$hint = i18n::s('Be straigthforward');
	$fields[] = array($label, $input, $hint);

	// the description
	$label = i18n::s('Description');

	// use the editor if possible
	$input = Surfer::get_editor('description', $item['description']);
	$fields[] = array($label, $input);

	// the target web address, if any
	$label = i18n::s('Target address');
	$input = '<input type="text" name="target_url" size="40" value="'.encode_field($item['target_url']).'" />';
	$hint = i18n::s('The URL of the target page for this action, if any');
	$fields[] = array($label, $input, $hint);

	// action status
    $label = i18n::s('Status');
    $input = '<input type="radio" name="status" value="O"';
    if(($item['status'] == 'O') || !$item['status'])
    	$input .= ' checked="checked"';
    $input .= EOT.' '.i18n::s('On-going').' ';
    $input .= '<input type="radio" name="status" value="C"';
    if($item['status'] == 'C')
    	$input .= ' checked="checked"';
    $input .= EOT.' '.i18n::s('Completed').' ';
    $input .= '<input type="radio" name="status" value="R"';
    if($item['status'] == 'R')
    	$input .= ' checked="checked"';
    $input .= EOT.' '.i18n::s('Rejected')."\n";
	$fields[] = array($label, $input);

	// build the form
	$context['text'] .= Skin::build_form($fields);

	// the submit button
	$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's').'</p>'."\n";

	// transmit the id as a hidden field
	if(isset($item['id']) && $item['id'])
	    $context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'" />';

	// other hidden fields
    $context['text'] .= '<input type="hidden" name="anchor" value="'.$anchor->get_reference().'" />';

	// end of the form
	$context['text'] .= '</div></form>';

	// insert anchor suffix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_suffix();

	// the script used for form handling at the browser
	$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
		.'	// check that main fields are not empty'."\n"
		.'	func'.'tion validateDocumentPost(container) {'."\n"
		."\n"
		.'		// title is mandatory'."\n"
		.'		if(!container.title.value) {'."\n"
		.'			alert("'.i18n::s('No title has been provided.').'");'."\n"
		.'			Yacs.stopWorking();'."\n"
		.'			return false;'."\n"
		.'		}'."\n"
		."\n"
	    .'		// successful check'."\n"
		.'		return true;'."\n"
		.'	}'."\n"
		."\n"
		.'// set the focus on first form field'."\n"
		.'document.getElementById("title").focus();'."\n"
		.'// ]]></script>'."\n";

	// general help on this form
	$context['extra'] .= Skin::build_box(i18n::s('Help'),
		sprintf(i18n::s('<p>If possible, describe the whole action in its title. The description field should be used for additional non-essentiel details.</p><p>%s and %s are available to beautify your post.</p><p>Use the target field to designate the main web resource involved in the action.</p>'), Skin::build_link('codes/', i18n::s('YACS codes'), 'help'), Skin::build_link('smileys/', i18n::s('smileys'), 'help')), 'navigation', 'help');

}

// render the skin
render_skin();

?>