<?php
/**
 * post a new decision or update an existing one
 *
 * A button-based editor is used for the description field.
 * It's aiming to introduce most common [link=codes]codes/index.php[/link] supported by YACS.
 * Also, sample smilies are displayed, and may be used to introduce related codes into the description field.
 *
 * A preview mode is available before actually saving the decision in the database.
 *
 * This script attempts to validate the new or updated decision against a standard PHP XML parser.
 * The objective is to spot malformed or unordered HTML and XHTML tags. No more, no less.
 *
 * On new decision by a non-associate a mail is sent to the system operator.
 * Also, a mail message is sent to the article creator if he/she has a valid address.
 *
 * As a very first countermeasure against weblog spammers URI are not allowed into anonymous decisions.
 *
 * The permission assessment is based upon following rules applied in the provided orders:
 * - the anchor may disallow the action
 * - associates are allowed to move forward
 * - permission is denied if the anchor is not viewable
 * - surfer created the decision
 * - this is a new post and the surfer is an authenticated member
 * - permission denied is the default
 *
 * When permission is denied, anonymous (not-logged) surfer are invited to register to be able to post new decisions.
 *
 * Accepted calls:
 * - edit.php/&lt;type&gt;/&lt;id&gt;			create a new decision for this anchor
 * - edit.php?anchor=&lt;type&gt;:&lt;id&gt;	create a new decision for this anchor
 * - edit.php/&lt;id&gt;						modify an existing decision
 * - edit.php?id=&lt;id&gt; 					modify an existing decision
 *
 * If the anchor for this item specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
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
include_once 'decisions.php';

// what should we do?
$action = '';
$id = NULL;
$target_anchor = NULL;

// parameters transmitted through friendly urls
if(isset($context['arguments'][0]) && $context['arguments'][0]) {

	// create a new decision for the provided anchor
	if(isset($context['arguments'][1]) && $context['arguments'][1]) {
		$action = 'new';
		$target_anchor = $context['arguments'][0].':'.$context['arguments'][1];
		$login_hook = 'anchor='.$target_anchor;

	// modify an existing decision
	} else {
		$action = 'edit';
		$id = $context['arguments'][0];
		$login_hook = 'id='.$id;
	}

// parameters transmitted in the query string
} elseif(isset($_REQUEST['id']) && $_REQUEST['id']) {
	$action = 'edit';
	$id = strip_tags($_REQUEST['id']);
	$login_hook = 'id='.$id;

} elseif(isset($_REQUEST['anchor']) && $_REQUEST['anchor']) {
	$action = 'new';
	$target_anchor = strip_tags($_REQUEST['anchor']);
	$login_hook = 'anchor='.$target_anchor;
}

// fight hackers
$id = strip_tags($id);
$target_anchor = strip_tags($target_anchor);

// get the item from the database
$item =& Decisions::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor =& Anchors::get($item['anchor']);
elseif($target_anchor)
	$anchor =& Anchors::get($target_anchor);

// load the skin, maybe with a variant
load_skin('decisions', $anchor);

// ensure no more than one item per surfer per anchor
if(is_object($anchor) && ($ballot = Decisions::get_ballot($anchor->get_reference()))) {
	$action = 'edit';
	$item =& Decisions::get($ballot);
	$_REQUEST['id'] = $item['id'];
}

// the anchor may control the script
if(is_object($anchor) && is_callable(array($anchor, 'allows')) && !$anchor->allows('decision', $action))
	$permitted = FALSE;

// associates, but not editors, can do what they want
elseif(Surfer::is_associate())
	$permitted = TRUE;

// the anchor has to be viewable by this surfer
elseif(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;

// surfer created the decision
elseif(isset($item['create_id']) && Surfer::is($item['create_id']))
	$permitted = TRUE;

// only authenticated members can post new decisions
elseif(($action == 'new') && Surfer::is_member())
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// do not always show the edition form
$with_form = FALSE;

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'decisions/' => i18n::s('Decisions') );

// page title
if(is_object($anchor))
	$context['page_title'] = sprintf(i18n::s('Decide: %s'), $anchor->get_title());

// always validate input syntax
if(isset($_REQUEST['description']))
	xml::validate($_REQUEST['description']);

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// an anchor is mandatory
} elseif(!is_object($anchor)) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No anchor has been found.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged() && ($context['users_with_anonymous_decisions'] != 'Y'))
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode('decisions/edit.php?'.$login_hook));

	// permission denied to authenticated user
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

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

	// only members are allowed to post links
	if(!Surfer::is_member())
		$_REQUEST['description'] = preg_replace('/(http:|https:|ftp:|mailto:)[\w@\/\.]+/', '!!!', $_REQUEST['description']);

	// preview mode
	if(isset($_REQUEST['preview']) && $_REQUEST['preview']) {
		$item = $_REQUEST;
		$with_form = TRUE;

	// display the form on error
	} elseif(!$_REQUEST['id'] = Decisions::post($_REQUEST)) {
		$item = $_REQUEST;
		$with_form = TRUE;

	// reward the poster for new posts
	} elseif(!isset($item['id'])) {

		// touch the related anchor
		$anchor->touch('decision:create', $_REQUEST['id'], isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y'));

		// clear cache
		Decisions::clear($_REQUEST);

		// increment the post counter of the surfer
		Users::increment_posts(Surfer::get_id());

		// thanks
		$context['page_title'] = i18n::s('Thank you for your contribution');

		// the type
		if(is_object($anchor)) {
			$context['text'] .= '<p>'.Decisions::get_img($_REQUEST['type']);

			// the label
			switch($_REQUEST['type']) {
			case 'no':
				$context['text'] .= ' '.i18n::s('This has been rejected');
				break;
			case 'yes':
				$context['text'] .= ' '.i18n::s('Your approval has been recorded');
				break;
			}

			$context['text'] .= '</p>';
		}

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu = array_merge($menu, array($anchor->get_url() => i18n::s('Back to main page')));
		$menu = array_merge($menu, array(Decisions::get_url($_REQUEST['id'], 'view') => i18n::s('View this decision')));
		$menu = array_merge($menu, array(Decisions::get_url($_REQUEST['id'], 'edit') => i18n::s('Edit the decision')));
		$follow_up .= Skin::build_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

		// log the submission of a new decision by a non-associate
		if(!Surfer::is_associate()) {
			$label = sprintf(i18n::c('New decision: %s'), strip_tags($anchor->get_title()));
			$description = $context['url_to_home'].$context['url_to_root'].Decisions::get_url($_REQUEST['id']);
			Logger::notify('decisions/edit.php', $label, $description);
		}

	// update of an existing decision
	} else {

		// touch the related anchor
		$anchor->touch('decision:update', $item['id'], isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y') );

		// clear cache
		Decisions::clear($_REQUEST);

		// forward to the updated thread
		Safe::redirect($context['url_to_home'].$context['url_to_root'].Decisions::get_url($_REQUEST['id'], 'view'));
	}

// display the form on GET
} else
	$with_form = TRUE;

// display the form
if($with_form) {

	$reference_item = array();

	// preview a decision
	if(isset($_REQUEST['preview']) && $_REQUEST['preview']) {
		$context['text'] .= Skin::build_box(i18n::s('Preview of your post:'), Codes::beautify($item['description']));

		$context['text'] .= Skin::build_block(i18n::s('Edit your post below'), 'title');

	}

	// the form to edit a decision
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" onsubmit="return validateDocumentPost(this)" id="main_form"><div>';

	// reference the anchor page
	if(is_object($anchor) && $anchor->is_viewable())
		$context['text'] .= '<p>'.$anchor->get_teaser('teaser')."</p>\n";

	// fields in the form
	$fields = array();

	// review the page on another window
	$label = i18n::s('Page to review');
	$input = '<a href="'.$anchor->get_url().'" class="button" onclick="window.open(this.href); return false;" onkeypress="window.open(this.href); return false;"><span>'.i18n::s('Browse in a separate window').'</span></a>';
	$fields[] = array($label, $input);

	// display info on current version
	if(isset($item['id']) && !preg_match('/(new|quote|reply)/', $action) && !(isset($_REQUEST['preview']) && $_REQUEST['preview'])) {

		// the creator
		$label = i18n::s('Posted by');
		$text = Users::get_link($item['create_name'], $item['create_address'], $item['create_id'])
			.' '.Skin::build_date($item['create_date']);
		$fields[] = array($label, $text);

		// the last editor
		if($item['edit_id'] != $item['create_id']) {
			$label = i18n::s('Edited by');
			$text = Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id'])
				.' '.Skin::build_date($item['edit_date']);
			$fields[] = array($label, $text);
		}

	// additional fields for anonymous surfers
	} elseif(!Surfer::is_logged()) {

		// splash
		if(isset($item['id']))
			$login_url = $context['url_to_root'].'users/login.php?url='.urlencode('decisions/edit.php?id='.$item['id'].'&anchor='.$_REQUEST['anchor']);
		else
			$login_url = $context['url_to_root'].'users/login.php?url='.urlencode('decisions/edit.php?anchor='.$_REQUEST['anchor']);
		$context['text'] .= '<p>'.sprintf(i18n::s('If you have previously registered to this site, please %s. Then the server will automatically put your name and address in following fields.'), Skin::build_link($login_url, i18n::s('authenticate')))."</p>\n";

		// the name, if any
		$label = i18n::s('Your name');
		$input = '<input type="text" name="edit_name" size="45" maxlength="128" accesskey="n" />';
		$hint = i18n::s('This optional field can be left blank if you wish');
		$fields[] = array($label, $input, $hint);

		// the address, if any
		$label = i18n::s('Your address');
		$input = '<input type="text" name="edit_address" size="45" maxlength="128" accesskey="a" />';
		$hint = i18n::s('e-mail or web address; this field is optional');
		$fields[] = array($label, $input, $hint);

	}

	// the type
	if(is_object($anchor)) {
		if(!isset($item['id']) || !$item['id'])
			$label = i18n::s('Your decision');
		else
			$label = i18n::s('Decision');

		if(isset($item['type']))
			$type = $item['type'];
		elseif(isset($_REQUEST['type']))
			$type = $_REQUEST['type'];
		else
			$type = '';

		// capture decision
		if(!isset($item['id']) || !$item['id'])
			$input = Decisions::get_radio_buttons('type', $type);

		// decision cannot be modified afterwards
		else {
			$input = Decisions::get_img($item['type']);

			// the label
			switch($item['type']) {
			case 'no':
				$input .= ' '.i18n::s('Rejected');
				break;
			case 'yes':
				$input .= ' '.i18n::s('Approved');
				break;
			}

		}

		$fields[] = array($label, $input);
	}

	// the description
	if(!isset($item['id']) || !$item['id'])
		$label = i18n::s('Your motivation');
	else
		$label = i18n::s('Motivation');

	// use the editor if possible
	$input = Surfer::get_editor('description', isset($item['description']) ? $item['description'] : '');
	$fields[] = array($label, $input);

	// build the form
	$context['text'] .= Skin::build_form($fields);

	//
	// bottom commands
	//
	$menu = array();

	// the submit button
	$menu[] = Skin::build_submit_button(i18n::s('Submit'), i18n::s('Press [s] to submit data'), 's');

	// cancel button
	$menu[] = Skin::build_link($anchor->get_url(), i18n::s('Cancel'), 'span');

	// insert the menu in the page
	$context['text'] .= Skin::finalize_list($menu, 'menu_bar');

	// associates may decide to not stamp changes -- complex command
	if(Surfer::is_associate() && Surfer::has_all())
		$context['text'] .= '<p><input type="checkbox" name="silent" value="Y" /> '.i18n::s('Do not change modification date of the main page.').'</p>';

	// transmit the id as a hidden field
	if(isset($item['id']) && $item['id'])
		$context['text'] .= '<input type="hidden" name="id" value="'.$item['id'].'" />';

	if(is_object($anchor))
		$context['text'] .= '<input type="hidden" name="anchor" value="'.$anchor->get_reference().'" />';

	// end of the form
	$context['text'] .= '</div></form>';

//	// the script used for form handling at the browser
//	$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
//		.'// check that main fields are not empty'."\n"
//		.'func'.'tion validateDocumentPost(container) {'."\n"
//		."\n"
//		.'	// description is mandatory'."\n"
//		.'	if(!container.description.value) {'."\n"
//		.'		alert("'.i18n::s('Please type a valid decision').'");'."\n"
//		.'		Yacs.stopWorking();'."\n"
//		.'		return false;'."\n"
//		.'	}'."\n"
//		."\n"
//		.'	// successful check'."\n"
//		.'	return true;'."\n"
//		.'}'."\n"
//		."\n"
//		.'// ]]></script>'."\n";

}

// render the skin
render_skin();

?>