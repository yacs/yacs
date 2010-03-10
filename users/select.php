<?php
/**
 * assign users to any object
 *
 * This script displays assigned users to an anchor, and list users that could be assigned as well.
 *
 * This is the main tool used by associates to assign editors to pages they are managing.
 *
 * Associates can use this script to assign users to sections and to articles.
 * Editors can also call this script to remove rights they have on a section.
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
	$anchor =& Anchors::get($_REQUEST['member']);

// associates can do what they want
if(Surfer::is_associate())
	$permitted = 'all';

// a member who manages his connections
elseif(is_object($anchor) && Surfer::get_id() && ($anchor->get_reference() == 'user:'.Surfer::get_id()))
	$permitted = 'all';

// a surfer who manages a parent section
elseif(Surfer::is_logged() && is_object($anchor) && $anchor->is_owned())
	$permitted = 'all';

// a member who manages his editor rights
elseif(is_object($anchor) && $anchor->is_assigned(Surfer::get_id(), FALSE))
	$permitted = 'me';

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

// an anchor is mandatory
if(!is_object($anchor))
	Logger::error(i18n::s('No anchor has been found.'));

// permission denied
elseif(!$permitted) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// build a form to manage all users linked to this item
} elseif($permitted == 'all') {

	// the title of the page
	if(is_object($anchor)) {
		if(!strncmp($anchor->get_reference(), 'user:', 5)) {
			if(Surfer::is(intval(substr($anchor->get_reference(), 5))))
				$context['page_title'] = i18n::s('My contacts');
			else
				$context['page_title'] = sprintf(i18n::s('Contacts of %s'), $anchor->get_title());
		} elseif(!strncmp($anchor->get_reference(), 'category:', 9)) {
			$context['page_title'] = sprintf(i18n::s('Members of %s'), $anchor->get_title());
		} else {
			$context['page_title'] = i18n::s('Manage editors');

		}
	}

	// look for the user through his nick name
	if(isset($_REQUEST['assigned_name']) && ($user = Users::get($_REQUEST['assigned_name'])))
		$_REQUEST['anchor'] = 'user:'.$user['id'];

	// set a new assignment
	if(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'set') && isset($_REQUEST['anchor']) && isset($_REQUEST['member'])) {
		if(!Members::check($_REQUEST['anchor'], $_REQUEST['member'])) {

			// assign a person (the anchor) to this object (the member)
			Members::assign($_REQUEST['anchor'], $_REQUEST['member']);

			// notify a person that is followed
			if(!strncmp($_REQUEST['member'], 'user:', 5) && ($follower = Anchors::get($_REQUEST['member'])) && isset($user['email']) && $user['email'] && ($user['without_alerts'] != 'Y')) {

				// contact target user by e-mail
				$subject = sprintf(i18n::c('%s is following you'), strip_tags($follower->get_title()));
				$message = sprintf(i18n::c('%s will receive notifications when you will create new content at %s'), $follower->get_title(), $context['site_name'])
					."\n\n".ucfirst(strip_tags($follower->get_title()))
					."\n".$context['url_to_home'].$context['url_to_root'].$follower->get_url();

				// enable threading
				$headers = Mailer::set_thread('', $anchor);

				// allow for cross-referencing
				Mailer::post(Surfer::from(), $user['email'], $subject, $message, NULL, $headers);
			}
		}

		// update the watch list of this person (the anchor)
		if(strncmp($_REQUEST['member'], 'user:', 5))
			Members::assign($_REQUEST['member'], $_REQUEST['anchor']);

	// break an assignment, and also purge the watch list
	} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'reset') && isset($_REQUEST['anchor']) && isset($_REQUEST['member'])) {
		Members::free($_REQUEST['anchor'], $_REQUEST['member']);
		if(!preg_match('/^user:/', $_REQUEST['member']))
			Members::free($_REQUEST['member'], $_REQUEST['anchor']);

	}

	// splash
	$context['text'] .= '<p>'.i18n::s('To add a person, type some letters to look for a name, then select one profile at a time.').'</p>';

	// the form to link additional users
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'
		.'<input type="text" name="assigned_name" id="name" size="45" maxlength="255" /><div id="name_choices" class="autocomplete"></div> <span id="ajax_spinner" style="display: none"><img src="'.$context['url_to_root'].'skins/_reference/ajax/ajax_completer.gif" alt="Working..." /></span>'
		.'<input type="hidden" name="member" value="'.encode_field($anchor->get_reference()).'">'
		.'<input type="hidden" name="action" value="set">'
		.'</p></form>'."\n";

	// enable autocompletion
	$context['text'] .= JS_PREFIX
		."\n"
		.'// set the focus on first form field'."\n"
		.'Event.observe(window, "load", function() { $("name").focus() });'."\n"
		."\n"
		.'// enable tags autocompletion'."\n"
		.'Event.observe(window, "load", function() { new Ajax.Autocompleter("name", "name_choices", "'.$context['url_to_root'].'users/complete.php", { paramName: "q", minChars: 1, frequency: 0.4, tokens: ",", afterUpdateElement: function(text, li) { $("ajax_spinner").style.display = "inline"; $("main_form").submit() }, indicator: "ajax_spinner" }); });'."\n"
		.JS_SUFFIX;

	// the current list of category members
	if(!strncmp($anchor->get_reference(), 'category:', 9) && ($users =& Members::list_users_by_posts_for_anchor($anchor->get_reference(), 0, 5*USERS_LIST_SIZE, 'raw')) && count($users)) {

		// splash message
		$context['text'] .= '<p style="margin-top: 2em;">'.sprintf(i18n::s('Persons assigned to %s'), $anchor->get_title()).'</p>';

		// browse the list
		foreach($users as $id => $user) {

			// make an url
			$url = Users::get_permalink($user);

			// gather information on this user
			$prefix = $suffix = $type = $icon = '';
			if(isset($user['full_name']) && $user['full_name'])
				$label = $user['full_name'].' ('.$user['nick_name'].')';
			else
				$label = $user['nick_name'];

			// surfer cannot be deselected
			if($anchor->is_owned($id, FALSE))
				$suffix .= ' - <span class="details">'.i18n::s('owner').'</span>';

			// add a link to unselect the user
			else {
				$link = $context['script_url'].'?anchor=user:'.$id.'&amp;member='.urlencode($anchor->get_reference()).'&amp;action=reset';
				$suffix .= ' - <span class="details">'.Skin::build_link($link, i18n::s('unassign'), 'basic').'</span>';
			}

			// format the item
			$new_users[$url] = array($prefix, $label, $suffix, $type, $icon);

		}

		// display attached users with unlink buttons
		$context['text'] .= '<p>'.Skin::build_list($new_users, 'compact').'</p>';

	// the current list of linked users
	} elseif(!strncmp($anchor->get_reference(), 'user:', 5) && ($users =& Members::list_connections_for_user($anchor->get_reference(), 0, 5*USERS_LIST_SIZE, 'raw')) && count($users)) {

		// splash message
		$context['text'] .= '<p style="margin-top: 2em;">'.sprintf(i18n::s('Contacts of %s'), $anchor->get_title()).'</p>';

		// browse the list
		foreach($users as $id => $user) {

			// make an url
			$url = Users::get_permalink($user);

			// gather information on this user
			$prefix = $suffix = $type = $icon = '';
			if(isset($user['full_name']) && $user['full_name'])
				$label = $user['full_name'].' ('.$user['nick_name'].')';
			else
				$label = $user['nick_name'];

			// surfer cannot be deselected
			if($anchor->is_owned($id, FALSE))
				$suffix .= ' - <span class="details">'.i18n::s('owner').'</span>';

			// add a link to unselect the user
			else {
				$link = $context['script_url'].'?anchor=user:'.$id.'&amp;member='.urlencode($anchor->get_reference()).'&amp;action=reset';
				$suffix .= ' - <span class="details">'.Skin::build_link($link, i18n::s('unassign'), 'basic').'</span>';
			}

			// format the item
			$new_users[$url] = array($prefix, $label, $suffix, $type, $icon);

		}

		// display attached users with unlink buttons
		$context['text'] .= '<p>'.Skin::build_list($new_users, 'compact').'</p>';

	// users assigned to this anchor
	} elseif(($users =& Members::list_users_by_posts_for_member($anchor->get_reference(), 0, 5*USERS_LIST_SIZE, 'raw')) && count($users)) {

		// splash message
		$context['text'] .= '<p style="margin-top: 2em;">'.sprintf(i18n::s('Persons assigned to %s'), $anchor->get_title()).'</p>';

		// browse the list
		foreach($users as $id => $user) {

			// make an url
			$url = Users::get_permalink($user);

			// gather information on this user
			$prefix = $suffix = $type = $icon = '';
			if(isset($user['full_name']) && $user['full_name'])
				$label = $user['full_name'].' ('.$user['nick_name'].')';
			else
				$label = $user['nick_name'];

			// surfer cannot be deselected
			if($anchor->is_owned($id, FALSE))
				$suffix .= ' - <span class="details">'.i18n::s('owner').'</span>';

			// add a link to unselect the user
			else {
				$link = $context['script_url'].'?anchor=user:'.$id.'&amp;member='.urlencode($anchor->get_reference()).'&amp;action=reset';
				$suffix .= ' - <span class="details">'.Skin::build_link($link, i18n::s('unassign'), 'basic').'</span>';
			}

			// format the item
			$new_users[$url] = array($prefix, $label, $suffix, $type, $icon);

		}

		// display attached users with unlink buttons
		$context['text'] .= '<p>'.Skin::build_list($new_users, 'compact').'</p>';

	}

	// list also editors of parent containers
	$inherited = '';
	$handle = $anchor->get_parent();
	while($handle && ($parent = Anchors::get($handle))) {
		$handle = $parent->get_parent();

		if(($users =& Members::list_users_by_posts_for_member($parent->get_reference(), 0, 5*USERS_LIST_SIZE, 'raw')) && count($users)) {

			// browse the list
			$items = array();
			foreach($users as $id => $user) {

				// make an url
				$url = Users::get_permalink($user);

				// gather information on this user
				$prefix = $suffix = $type = $icon = '';
				if(isset($user['full_name']) && $user['full_name'])
					$label = $user['full_name'].' ('.$user['nick_name'].')';
				else
					$label = $user['nick_name'];

				// surfer cannot be deselected
				if($parent->is_owned($id, FALSE))
					$suffix .= ' - <span class="details">'.i18n::s('owner').'</span>';

				// add a link to unselect the user
				elseif(Surfer::is_associate()) {
					$link = $context['script_url'].'?anchor=user:'.$id.'&amp;member='.urlencode($parent->get_reference()).'&amp;action=reset';
					$suffix .= ' - <span class="details">'.Skin::build_link($link, i18n::s('unassign'), 'basic').'</span>';
				}

				// format the item
				$items[$url] = array($prefix, $label, $suffix, $type, $icon);

			}

			// display attached users with unlink buttons
			$inherited .= Skin::build_box(sprintf(i18n::s('Persons assigned to %s'), $parent->get_title()), Skin::build_list($items, 'compact'), 'folded');

		}
	}

	if($inherited) {
		$context['text'] .= '<div style="margin-top: 2em;">'.i18n::s('Following editors inherit from assignments at parent containers').'</div>'.$inherited;
	}

	// back to the anchor page
	$links = array();
	$url = $anchor->get_url();
	if(!strncmp($anchor->get_reference(), 'user:', 5))
		$url .= '#_contacts';
	$links[] = Skin::build_link($url, i18n::s('Done'), 'button');
	$context['text'] .= Skin::finalize_list($links, 'assistant_bar');

	// adding editors
	if(strncmp($_REQUEST['member'], 'user:', 5)) {
		if(Surfer::may_mail()) {
			$help = sprintf(i18n::s('%s if you have to assign new persons and to notify them in a single operation.'), Skin::build_link($anchor->get_url('invite'), i18n::s('Invite participants')));

			// in a side box
			$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'boxes', 'help');
		}

	// adding contacts
	} else {
		if(Surfer::may_mail()) {
			$help = i18n::s('Each new contact will be notified that you are following him.');

			// in a side box
			$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'boxes', 'help');
		}
	}



// please suppress editor rights to this item
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'leave')) {

	// break an assignment, and also purge the watch list
	Members::free('user:'.Surfer::get_id(), $anchor->get_reference());

	// don't break symetric connections from another user
	if($anchor->get_type() != 'user')
		Members::free($anchor->get_reference(), 'user:'.Surfer::get_id());

	// page title
	$type = $anchor->get_type();
	if($type == 'section')
		$label = i18n::s('a section');
	else
		$label = i18n::s('a page');
	$context['page_title'] = sprintf(i18n::s('You have left %s'), $label);

	// splash message
	$context['text'] .= '<p>'.sprintf(i18n::s('The operation has completed, and you have no specific access rights to %s.'), Skin::build_link($anchor->get_url(), $anchor->get_title())).'</p>';

	// back to the anchor page
	$links = array();
	$url = Surfer::get_permalink();
	$links[] = Skin::build_link($url, i18n::s('Done'), 'button');
	$context['text'] .= Skin::finalize_list($links, 'assistant_bar');

// confirm that i want to suppress my editor rights
} else {

	// page title
	$type = $anchor->get_type();
	if($type == 'section')
		$label = i18n::s('a section');
	else
		$label = i18n::s('a page');
	$context['page_title'] = sprintf(i18n::s('Leave %s'), $label);

	// splash message
	if($type == 'section')
		$context['text'] .= '<p>'.sprintf(i18n::s('You have been assigned as an editor of %s, and this allows you to post new content, to contribute to pages from other persons, and to be notified of changes.'), Skin::build_link($anchor->get_url(), $anchor->get_title())).'</p>';
	else
		$context['text'] .= '<p>'.sprintf(i18n::s('You have been assigned as an editor of %s, and this allows you to contribute to this page, and to be notified of changes.'), Skin::build_link($anchor->get_url(), $anchor->get_title())).'</p>';

	// cautioon on private areas
	if($anchor->get_active() == 'N') {
		if($type == 'section')
			$context['text'] .= '<p>'.i18n::s('Access to this section is restricted. If you continue, it will become invisible to you, and you will not be able to even browse its content anymore.').'</p>';
		else
			$context['text'] .= '<p>'.i18n::s('Access to this page is restricted. If you continue, it will become invisible to you, and you will not be able to even browse its content anymore.').'</p>';
	}

	// ask for confirmation
	if($type == 'section')
		$context['text'] .= '<p>'.i18n::s('You are about to suppress all your editing rights on this section.').'</p>';
	else
		$context['text'] .= '<p>'.i18n::s('You are about to suppress all your editing rights on this page.').'</p>';

	$bottom = '<p>'.i18n::s('Are you sure?').'</p>';

	// commands
	$menu = array();
	$menu[] = Skin::build_submit_button(i18n::s('Yes'), NULL, NULL, 'confirmed');
	$menu[] = Skin::build_link($anchor->get_url(), i18n::s('No'), 'span');

	// render commands
	$bottom .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
		.Skin::finalize_list($menu, 'menu_bar')
		.'<input type="hidden" name="member" value="'.$anchor->get_reference().'" />'."\n"
		.'<input type="hidden" name="action" value="leave" />'."\n"
		.'</p></form>'."\n";

	//
	$context['text'] .= Skin::build_block($bottom, 'bottom');

	// set the focus
	$context['text'] .= JS_PREFIX
		.'// set the focus on first form field'."\n"
		.'$("confirmed").focus();'."\n"
		.JS_SUFFIX;


}

// render the skin
render_skin();

?>