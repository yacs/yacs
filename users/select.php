<?php
/**
 * assign users to any object
 *
 * This script displays assigned users to an anchor, and list users that could be assigned as well.
 *
 * This is the main tool used by associates to assign editors to pages they are managing.
 *
 * Only associates can use this script to assign users to sections and to articles.
 * This means that editors cannot delegate their power to someone else.
 *
 * Users are allowed to manage other users in their watch list.
 *
 * Accept following invocations:
 * - select.php?member=article:12
 *
 * If this anchor, or one of its anchor, specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// users are assigned to this anchor, passed as member
$anchor = NULL;
if(isset($_REQUEST['member']))
	$anchor = Anchors::get($_REQUEST['member']);

// associates can do what they want
if(Surfer::is_associate())
	$permitted = TRUE;

// the page of the authenticated surfer
elseif(is_object($anchor) && Surfer::get_id() && ($anchor->get_reference() == 'user:'.Surfer::get_id()))
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load the skin, maybe with a variant
load_skin('users', $anchor);

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'users/' => i18n::s('People') );

// the title of the page
if(is_object($anchor)) {
	if(preg_match('/^user:/', $anchor->get_reference()))
		$context['page_title'] = sprintf(i18n::s('People watched by %s'), $anchor->get_title());
	else
		$context['page_title'] = sprintf(i18n::s('People assigned to %s'), $anchor->get_title());
}

// an anchor is mandatory
if(!is_object($anchor))
	Skin::error(i18n::s('No anchor has been found.'));

// permission denied
elseif(!$permitted) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// build a form to associates some users to this item
} else {

	// look for the user through his nick name
	if(isset($_REQUEST['assigned_name']) && ($user = Users::get($_REQUEST['assigned_name'])))
		$_REQUEST['anchor'] = 'user:'.$user['id'];

	// assign a user, and also update his watch list
	if(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'set') && isset($_REQUEST['anchor']) && isset($_REQUEST['member'])) {
		Members::assign($_REQUEST['anchor'], $_REQUEST['member']);
		if(!preg_match('/^user:/', $_REQUEST['anchor']))
			Members::assign($_REQUEST['member'], $_REQUEST['anchor']);

	// break an assignment, and also purge the watch list
	} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'reset') && isset($_REQUEST['anchor']) && isset($_REQUEST['member'])) {
		Members::free($_REQUEST['anchor'], $_REQUEST['member']);
		if(!preg_match('/^user:/', $_REQUEST['anchor']))
			Members::free($_REQUEST['member'], $_REQUEST['anchor']);

	}

	// insert anchor prefix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_prefix();

	// splash
	$context['text'] .= '<p>'.i18n::s('Type some letters to look for some name, then select one user at a time.').'</p>';

	// the form to link additional users
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'
		.i18n::s('Assign').' <input type="text" name="assigned_name" id="name" size="45" maxlength="255" /><div id="name_choices" class="autocomplete"></div> <span id="ajax_spinner" style="display: none"><img src="'.$context['url_to_root'].'skins/_reference/ajax_completer.gif" alt="Working..." /></span>'
		.'<input type="hidden" name="member" value="'.encode_field($anchor->get_reference()).'">'
		.'<input type="hidden" name="action" value="set">'
		.'</p></form>'."\n";

	// enable autocompletion
	$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
		."\n"
		.'// set the focus on first form field'."\n"
		.'Event.observe(window, "load", function() { $("name").focus() });'."\n"
		."\n"
		.'// enable tags autocompletion'."\n"
		.'Event.observe(window, "load", function() { new Ajax.Autocompleter("name", "name_choices", "'.$context['url_to_root'].'users/complete.php", { paramName: "q", minChars: 1, frequency: 0.4, tokens: ",", afterUpdateElement: function(text, li) { $("ajax_spinner").style.display = "inline"; $("main_form").submit() }, indicator: "ajax_spinner" }); });'."\n"
		.'// ]]></script>';

	// the current list of linked users
	$assigned_users = array();
	if(($users =& Members::list_users_by_posts_for_member($anchor->get_reference(), 0, 5*USERS_LIST_SIZE, 'raw')) && count($users)) {

		// browse the list
		foreach($users as $id => $user) {

			// this one has already been assigned
			$assigned_users[] = $id;

			// make an url
			$url = Users::get_url($id, 'view', isset($user['nick_name'])?$user['nick_name']:'');

			// gather information on this user
			$prefix = $suffix = $type = $icon = '';
			if(isset($user['full_name']) && $user['full_name'])
				$label = $user['full_name'].' ('.$user['nick_name'].')';
			else
				$label = $user['nick_name'];

			// add a link to unselect the user
			$link = $context['script_url'].'?anchor=user:'.$id.'&amp;member='.urlencode($anchor->get_reference()).'&amp;action=reset';
			$suffix .= ' - <span class="details">'.Skin::build_link($link, i18n::s('unassign'), 'basic').'</span>';

			// format the item
			$new_users[$url] = array($prefix, $label, $suffix, $type, $icon);

		}

		// display attached users with unlink buttons
		$context['text'] .= '<p>'.Skin::build_list($new_users, 'compact').'</p>';

	}

	// back to the anchor page
	$links = array();
	$links[] = Skin::build_link($anchor->get_url(), i18n::s('Done'), 'button');
	$context['text'] .= Skin::finalize_list($links, 'assistant_bar');

	// insert anchor suffix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_suffix();

}

// render the skin
render_skin();

?>