<?php
/**
 * assign users to any object
 *
 * This script displays assigned users to an anchor, and list users that could be assigned as well.
 *
 * This is the main tool used by associates to assign editors to pages they are managing.
 *
 * Associates can use this script to assign users to sections and to articles.
 *
 * Accept following invocations:
 * - select.php?anchor=article:12 to list watchers
 * - select.php?member=article:12 to list and manage editors
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
elseif(isset($_REQUEST['anchor']))
	$anchor = Anchors::get($_REQUEST['anchor']);

// only looking at watchers
if(isset($_REQUEST['anchor']) && !isset($_REQUEST['action']))
	$permitted = 'watchers';

// associates can do what they want
elseif(Surfer::is_associate())
	$permitted = 'all';

// a member who manages his connections
elseif(is_object($anchor) && Surfer::get_id() && ($anchor->get_reference() == 'user:'.Surfer::get_id()))
	$permitted = 'all';

// a surfer who manages a parent section
elseif(Surfer::is_logged() && is_object($anchor) && $anchor->is_owned())
	$permitted = 'all';

// a member who manages his editor rights
elseif(is_object($anchor) && $anchor->is_viewable())
	$permitted = 'editors';

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
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// list all watchers
} elseif($permitted == 'watchers') {

	// page title
	$context['page_title'] = sprintf(i18n::s('Watchers of %s'), $anchor->get_title());

	// watchers of a page
	if(!strncmp($anchor->get_reference(), 'article:', 8))
		$users = Articles::list_watchers_by_name($anchor->item, 0, 5*USERS_LIST_SIZE, 'raw');

	// watchers of a section
	elseif(!strncmp($anchor->get_reference(), 'section:', 8))
		$users = Sections::list_watchers_by_name($anchor->item, 0, 5*USERS_LIST_SIZE, 'raw');

	else {
		$anchors = array($anchor->get_reference());
		$handle = $anchor->get_parent();
		while($handle && ($parent = Anchors::get($handle))) {
			$anchors[] = $handle;
			$handle = $parent->get_parent();
		}

		// authorized users
		$restricted = NULL;
		if($anchor->is_hidden() && ($editors =& Members::list_anchors_for_member($anchors))) {
			foreach($editors as $editor)
				if(strpos($editor, 'user:') === 0)
					$restricted[] = substr($editor, strlen('user:'));
		}

		$users = Members::list_watchers_by_name_for_anchor($anchors, 0, 5*USERS_LIST_SIZE, 'raw', $restricted);

	}
	// the current list of watchers
	if(count($users)) {

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

			// format the item
			$new_users[$url] = array($prefix, $label, $suffix, $type, $icon);

		}

		// display attached users with unlink buttons
		$context['text'] .= '<p>'.Skin::build_list($new_users, 'compact').'</p>';

	}

	// back to the anchor page
	$links = array();
	$url = $anchor->get_url();
	$links[] = Skin::build_link($url, i18n::s('Done'), 'button');
	$context['text'] .= Skin::finalize_list($links, 'assistant_bar');

// manage all users linked to this item
} elseif($permitted == 'all') {

	// page title
	if(!strncmp($anchor->get_reference(), 'user:', 5))
		$context['page_title'] = i18n::s('Manage followers');
	elseif(!strncmp($anchor->get_reference(), 'category:', 9))
		$context['page_title'] = i18n::s('Manage members');
	else
		$context['page_title'] = i18n::s('Manage participants');

	// look for the user through his nick name
	if(isset($_REQUEST['assigned_name']) && ($user = Users::get($_REQUEST['assigned_name'])))
		$_REQUEST['anchor'] = 'user:'.$user['id'];

	// sanity check --we won't loops
	if(isset($_REQUEST['anchor']) && ($_REQUEST['anchor'] == $_REQUEST['member']))
		;

	// set a new assignment
	elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'assign') && isset($_REQUEST['anchor']) && isset($_REQUEST['member'])) {

		// add a category member
		if(!strncmp($_REQUEST['member'], 'category:', 9)) {
			Members::assign($_REQUEST['anchor'], $_REQUEST['member']);
			Members::assign($_REQUEST['member'], $_REQUEST['anchor']);

		// add a follower to this person
		} elseif(!strncmp($_REQUEST['member'], 'user:', 5)) {
			Members::assign($_REQUEST['anchor'], $_REQUEST['member']);

			// notify a person that is followed
			if(($follower = Anchors::get($_REQUEST['member'])) && isset($user['email']) && $user['email'] && ($user['without_alerts'] != 'Y')) {

				// notify target user by e-mail
				$subject = sprintf(i18n::c('%s is following you'), strip_tags($follower->get_title()));

				// headline
				$headline = sprintf(i18n::c('%s is following you'),
					'<a href="'.$context['url_to_home'].$context['url_to_root'].$follower->get_url().'">'.$follower->get_title().'</a>');

				// information
				$message = '<p>'.sprintf(i18n::c('%s will receive notifications when you will update your followers at %s'), $follower->get_title(), $context['site_name']).'</p>'
					.'<p><a href="'.$context['url_to_home'].$context['url_to_root'].$follower->get_url().'">'.ucfirst(strip_tags($follower->get_title())).'</a></p>';

				// assemble main content of this message
				$message = Skin::build_mail_content($headline, $message);

				// a set of links
				$menu = array();

				// call for action
				$link = $context['url_to_home'].$context['url_to_root'].$follower->get_url();
				$menu[] = Skin::build_mail_button($link, $follower->get_title(), TRUE);

				// finalize links
				$message .= Skin::build_mail_menu($menu);

				// enable threading
				$headers = Mailer::set_thread($follower->get_reference());

				// allow for cross-referencing
				Mailer::notify(Surfer::from(), $user['email'], $subject, $message, $headers);
			}

		// regular container
		} else {

			// always update the watch list
			Members::assign($_REQUEST['member'], $_REQUEST['anchor']);

			// editor link has to be added explicitly on non-private items
			if(!$anchor->is_hidden() && (!isset($_REQUEST['assignment']) || ($_REQUEST['assignment'] != 'editor')))
				;

			// assign an editor (the anchor) to this object (the member)
			else
				Members::assign($_REQUEST['anchor'], $_REQUEST['member']);
		}

	// set editor
	} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'set') && isset($_REQUEST['editor']) && isset($_REQUEST['member'])) {
		Members::assign($_REQUEST['editor'], $_REQUEST['member']);

	// reset editor
	} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'reset') && isset($_REQUEST['editor']) && isset($_REQUEST['member'])) {
		Members::free($_REQUEST['editor'], $_REQUEST['member']);

		// purge watch list too
		if($anchor->is_hidden())
			Members::free($_REQUEST['member'], $_REQUEST['editor']);

	// set watcher
	} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'set') && isset($_REQUEST['watcher']) && isset($_REQUEST['member'])) {
		Members::assign($_REQUEST['member'], $_REQUEST['watcher']);

	// reset watcher
	} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'reset') && isset($_REQUEST['watcher']) && isset($_REQUEST['member'])) {
		Members::free($_REQUEST['member'], $_REQUEST['watcher']);

	// break an assignment
	} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'reset') && isset($_REQUEST['anchor']) && isset($_REQUEST['member'])) {
		Members::free($_REQUEST['anchor'], $_REQUEST['member']);

		// following links between users are not symmetrical
		if(!preg_match('/^user:/', $_REQUEST['member']))
			Members::free($_REQUEST['member'], $_REQUEST['anchor']);

	}

	// the form to link additional users
	$form = '<form method="post" action="'.$context['script_url'].'" id="main_form">';

	// horizontal layout
	$cells = array();

	// users only have followers
	if(!strncmp($anchor->get_reference(), 'user:', 5))
		;

	// categories only have members
	elseif(!strncmp($anchor->get_reference(), 'category:', 9))
		;

	// two different roles, except for private items
	elseif(!$anchor->is_hidden()) {
		$cells[] = '<span class="small">'
			.	'<input type="radio" name="assignment" value="watcher" checked="checked" /> '.i18n::s('notify on update (watcher)')
			.	BR
			.	'<input type="radio" name="assignment" value="editor" /> '.i18n::s('moderate content (editor)')
			.'</span>';
	}

	// capture a new name with auto completion
	$cells[] = '<input type="text" name="assigned_name" id="assigned_name" size="45" maxlength="255" />'
		.' <input type="submit" id="submit_button" value="'.i18n::s('Submit').'" style="display: none;" />'
		.'<p class="details">'.i18n::s('To add a person, type some letters to look for a name, then select one profile at a time.').'</p>';

	// finalize the capture form
	$form .= Skin::layout_horizontally($cells)
		.'<input type="hidden" name="member" value="'.encode_field($anchor->get_reference()).'">'
		.'<input type="hidden" name="action" value="assign">'
		.'</form>'."\n";

	// enable autocompletion
	$form .= JS_PREFIX
		.'$(function() {'."\n"
		.'	// set the focus on first form field'."\n"
		.'	$("#assigned_name").focus();'."\n"
		.'	// enable name autocompletion'."\n"
		.'	Yacs.autocomplete_names("assigned_name",true, "", function(data) { Yacs.startWorking(); $("#submit_button").show().click(); });'."\n"
		.'});  '."\n"
 		.JS_SUFFIX;

	// title says it all
	if(!strncmp($anchor->get_reference(), 'user:', 5)) {
		if(Surfer::is(intval(substr($anchor->get_reference(), 5))))
			$title = i18n::s('Who do you want to follow?');
		else
			$title = sprintf(i18n::s('Persons followed by %s'), $anchor->get_title());
	} elseif(!strncmp($anchor->get_reference(), 'category:', 9))
		$title = sprintf(i18n::s('Add a member to %s'), $anchor->get_title());
	else
		$title = i18n::s('Add a participant');

	// finalize the box
	$context['text'] .= Skin::build_box($title, $form, 'header1');

	// looking at category members
	if(!strncmp($anchor->get_reference(), 'category:', 9)) {

		// current list of category members
		if($users =& Members::list_users_by_name_for_anchor($anchor->get_reference(), 0, 5*USERS_LIST_SIZE, 'raw')) {

			// browse the list
			$new_users = array();
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

			// splash message
			$context['text'] .= Skin::build_box(sprintf(i18n::s('Members of %s'), $anchor->get_title()),
				Skin::build_list($new_users, 'compact'), 'header1');
		}

	// looking at user followers
	} elseif(!strncmp($anchor->get_reference(), 'user:', 5)) {

		// the current list of user followers
		if($users =& Members::list_connections_for_user($anchor->get_reference(), 0, 5*USERS_LIST_SIZE, 'raw')) {

			// browse the list
			$new_users = array();
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

			// splash message
			$context['text'] .= Skin::build_box(sprintf(i18n::s('Persons followed by %s'), $anchor->get_title()),
				Skin::build_list($new_users, 'compact'), 'header1');

		}

	// watchers and editors of this anchor
	} elseif($users =& Members::list_users_by_name_for_reference($anchor->get_reference(), 'raw')) {

		// browse the list
		foreach($users as $id => $user) {

			// accept only editors at private spaces --in case the space would be on watching list
			if($anchor->is_hidden() && !$user['editor'])
				continue;

			// make an url
			$url = Users::get_permalink($user);

			// gather information on this user
			$prefix = $suffix = $type = $icon = '';
			if(isset($user['full_name']) && $user['full_name'])
				$label = $user['full_name'].' ('.$user['nick_name'].')';
			else
				$label = $user['nick_name'];

			// details about this assignment
			$details = array();

			// surfer cannot be deselected
			$disabled ='';
			if($anchor->is_owned($id, FALSE)) {
				$details[] = i18n::s('owner');
				$disabled =' disabled="disabled"';
			}

			// toggle from watching list
			if($user['watcher']) {
				$link = $context['script_url'].'?member='.urlencode($anchor->get_reference()).'&amp;watcher=user:'.$id.'&amp;action=reset';
				$details[] = '<input type="checkbox"'.$disabled.' checked="checked" onclick="Yacs.startWorking();window.location=\''.$link.'\'" />'.i18n::s('watcher');
			} else {
				$link = $context['script_url'].'?member='.urlencode($anchor->get_reference()).'&amp;watcher=user:'.$id.'&amp;action=set';
				$details[] = '<input type="checkbox"'.$disabled.' onclick="Yacs.startWorking();window.location=\''.$link.'\'" />'.i18n::s('watcher');
			}

			// toggle from editors
			if($user['editor']) {
				$link = $context['script_url'].'?member='.urlencode($anchor->get_reference()).'&amp;editor=user:'.$id.'&amp;action=reset';
				$details[] = '<input type="checkbox"'.$disabled.' checked="checked" onclick="Yacs.startWorking();window.location=\''.$link.'\'" />'.i18n::s('editor');
			} else {
				$link = $context['script_url'].'?member='.urlencode($anchor->get_reference()).'&amp;editor=user:'.$id.'&amp;action=set';
				$details[] = '<input type="checkbox"'.$disabled.' onclick="Yacs.startWorking();window.location=\''.$link.'\'" />'.i18n::s('editor');
			}

			if($details)
				$suffix .= ' - <span class="details">'.join(' ', $details).'</span>';

			// format the item
			$new_users[$url] = array($prefix, $label, $suffix, $type, $icon);

		}

		// splash message
		$context['text'] .= Skin::build_box(sprintf(i18n::s('Participants to %s'), $anchor->get_title()),
			Skin::build_list($new_users, 'compact'), 'header1');

		// list also watchers and editors of parent containers
		$inherited = '';
		$forwarding = TRUE;
		$handle = $anchor->get_parent();
		while($handle && ($parent = Anchors::get($handle))) {

			// watchers and editors of this container
			if(($users =& Members::list_users_by_name_for_reference($parent->get_reference(), 'raw')) && count($users)) {

				// browse the list
				$items = array();
				foreach($users as $id => $user) {

					// accept only editors at private spaces --in case the space would be on watching list
					if($anchor->is_hidden() && !$user['editor'])
						continue;

					// make an url
					$url = Users::get_permalink($user);

					// gather information on this user
					$prefix = $suffix = $type = $icon = '';
					if(isset($user['full_name']) && $user['full_name'])
						$label = $user['full_name'].' ('.$user['nick_name'].')';
					else
						$label = $user['nick_name'];

					// details about this assignment
					$details = array();

					// surfer cannot be deselected
					$disabled =' disabled="disabled"';
					if($parent->is_owned($id, FALSE))
						$details[] = i18n::s('owner');

					// allow associates to toggle watchers and editors at upper levels of the content tree
					elseif(Surfer::is_associate())
						$disabled ='';

					// it is not relevant to list watchers of this container
					if(!$forwarding)
						;

					// watcher?
					elseif($user['watcher']) {
						$link = $context['script_url'].'?member='.urlencode($parent->get_reference()).'&amp;watcher=user:'.$id.'&amp;action=reset';
						$details[] = '<input type="checkbox"'.$disabled.' checked="checked" onclick="Yacs.startWorking();window.location=\''.$link.'\'" />'.i18n::s('watcher');
					} else {
						$link = $context['script_url'].'?member='.urlencode($parent->get_reference()).'&amp;watcher=user:'.$id.'&amp;action=set';
						$details[] = '<input type="checkbox"'.$disabled.' onclick="Yacs.startWorking();window.location=\''.$link.'\'" />'.i18n::s('watcher');
					}

					// editor?
					if($user['editor']) {
						$link = $context['script_url'].'?member='.urlencode($parent->get_reference()).'&amp;editor=user:'.$id.'&amp;action=reset';
						$details[] = '<input type="checkbox"'.$disabled.' checked="checked" onclick="Yacs.startWorking();window.location=\''.$link.'\'" />'.i18n::s('editor');
					} else {
						$link = $context['script_url'].'?member='.urlencode($parent->get_reference()).'&amp;editor=user:'.$id.'&amp;action=set';
						$details[] = '<input type="checkbox"'.$disabled.' onclick="Yacs.startWorking();window.location=\''.$link.'\'" />'.i18n::s('editor');
					}

					if($details)
						$suffix .= ' - <span class="details">'.join(' ', $details).'</span>';

					// format the item
					$items[$url] = array($prefix, $label, $suffix, $type, $icon);

				}

				// distinguish forwarding containers
				if($forwarding)
					$template = i18n::s('Participants to %s');
				else
					$template = i18n::s('Editors of %s');

				// display assigned users with unlink buttons
				if($items)
					$inherited .= Skin::build_box(sprintf($template, $parent->get_title()), Skin::build_list($items, 'compact'), 'folded');

			}

			// limit the horizon of watching for next level
			if($forwarding && !strncmp($anchor->get_reference(), 'article:', 8) && !$parent->has_option('forward_notifications', FALSE))
				$forwarding = FALSE;

			// explore next level
			$handle = $parent->get_parent();
		}

		if($inherited)
			$context['text'] .= Skin::build_box(i18n::s('Inherited assignments'), $inherited, 'header1');

	}

	// back to the anchor page
	$links = array();
	$url = $anchor->get_url();
	if(!strncmp($anchor->get_reference(), 'user:', 5))
		$url .= '#_followers';
	$links[] = Skin::build_link($url, i18n::s('Done'), 'button');
	$context['text'] .= Skin::finalize_list($links, 'assistant_bar');

	// adding editors
	if(strncmp($_REQUEST['member'], 'user:', 5)) {
		if(Surfer::may_mail()) {
			$help = sprintf(i18n::s('%s if you have to assign new persons and to notify them in a single operation.'), Skin::build_link($anchor->get_url('invite'), i18n::s('Invite participants')));

			// in a side box
			$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'boxes', 'help');
		}

	// adding followers
	} else {
		if(Surfer::may_mail()) {
			$help = i18n::s('Each new person will be notified that you are following him.');

			// in a side box
			$context['components']['boxes'] = Skin::build_box(i18n::s('Help'), $help, 'boxes', 'help');
		}
	}

// list editors
} elseif($permitted == 'editors') {

	// the title of the page
	if(is_object($anchor)) {
		if(!strncmp($anchor->get_reference(), 'user:', 5)) {
			if(Surfer::is(intval(substr($anchor->get_reference(), 5))))
				$context['page_title'] = i18n::s('Persons that I am following');
			else
				$context['page_title'] = sprintf(i18n::s('Persons followed by %s'), $anchor->get_title());
		} elseif(!strncmp($anchor->get_reference(), 'category:', 9)) {
			$context['page_title'] = sprintf(i18n::s('Members of %s'), $anchor->get_title());
		} else {
			$context['page_title'] = i18n::s('Editors');

		}
	}

	// look for the user through his nick name
	if(isset($_REQUEST['assigned_name']) && ($user = Users::get($_REQUEST['assigned_name'])))
		$_REQUEST['anchor'] = 'user:'.$user['id'];

	// the current list of category members
	if(!strncmp($anchor->get_reference(), 'category:', 9) && ($users =& Members::list_users_by_posts_for_anchor($anchor->get_reference(), 0, 5*USERS_LIST_SIZE, 'raw')) && count($users)) {

		// splash message
		$context['text'] .= '<div style="margin-top: 2em;">'.sprintf(i18n::s('Persons assigned to %s'), $anchor->get_title());

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

			// format the item
			$new_users[$url] = array($prefix, $label, $suffix, $type, $icon);

		}

		// display attached users with unlink buttons
		$context['text'] .= Skin::build_list($new_users, 'compact').'</div>';

	// the current list of linked users
	} elseif(!strncmp($anchor->get_reference(), 'user:', 5) && ($users =& Members::list_connections_for_user($anchor->get_reference(), 0, 5*USERS_LIST_SIZE, 'raw')) && count($users)) {

		// splash message
		$context['text'] .= '<div style="margin-top: 2em;">'.sprintf(i18n::s('Persons followed by %s'), $anchor->get_title());

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

			// format the item
			$new_users[$url] = array($prefix, $label, $suffix, $type, $icon);

		}

		// display attached users with unlink buttons
		$context['text'] .= Skin::build_list($new_users, 'compact').'</div>';

	// users assigned to this anchor
	} elseif(($users =& Members::list_users_by_posts_for_member($anchor->get_reference(), 0, 50*USERS_LIST_SIZE, 'raw')) && count($users)) {

		// splash message
		$context['text'] .= '<div style="margin-top: 2em;">'.sprintf(i18n::s('Persons assigned to %s'), $anchor->get_title());

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

			// format the item
			$new_users[$url] = array($prefix, $label, $suffix, $type, $icon);

		}

		// display attached users with unlink buttons
		$context['text'] .= Skin::build_list($new_users, 'compact').'</div>';

	}

	// list also editors of parent containers
	$inherited = '';
	$handle = $anchor->get_parent();
	while($handle && ($parent = Anchors::get($handle))) {
		$handle = $parent->get_parent();

		if(($users =& Members::list_users_by_posts_for_member($parent->get_reference(), 0, 50*USERS_LIST_SIZE, 'raw')) && count($users)) {

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
		$url .= '#_followers';
	$links[] = Skin::build_link($url, i18n::s('Done'), 'button');
	$context['text'] .= Skin::finalize_list($links, 'assistant_bar');

}

// render the skin
render_skin();

?>
