<?php
/**
 * set section owner
 *
 * This script allows to transfer ownership of a section to another person.
 *
 * Of course, only associates and section owners can proceed.
 *
 * Accepted calls:
 * - own.php/&lt;id&gt;
 * - own.php?id=&lt;id&gt;
 *
 * @author Bernard Paques
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
$item =& Sections::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor =& Anchors::get($item['anchor']);

// only strict section owners can proceed
if(Sections::is_owned($item, $anchor, TRUE) || Surfer::is_associate())
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load the skin, maybe with a variant
load_skin('sections', $anchor, isset($item['options']) ? $item['options'] : '');

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'sections/' => i18n::s('Site map') );
if(isset($item['id']) && isset($item['title']))
	$context['path_bar'] = array_merge($context['path_bar'], array(Sections::get_permalink($item) => $item['title']));

// page title
if(isset($item['title']))
	$context['page_title'] = sprintf(i18n::s('Owner of %s'), $item['title']);

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// not found
} elseif(!isset($item['id'])) {
	include '../error.php';

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Sections::get_url($item['id'], 'own')));

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// do the job
} elseif(!count($context['error'])) {

	// look for the user through his nick name
	if(isset($_REQUEST['assigned_name']) && ($user = Users::get($_REQUEST['assigned_name'])))
		$_REQUEST['anchor'] = 'user:'.$user['id'];

	// transfer ownership
	if(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'set') && isset($_REQUEST['anchor'])) {

		// assign a user, and also update his watch list
		$attributes = array( 'id' => $item['id'], 'owner_id' => $user['id'] );
		Sections::put_attributes($attributes);
		Members::assign($_REQUEST['anchor'], 'section:'.$item['id']);
		Members::assign('section:'.$item['id'], $_REQUEST['anchor']);

		$context['text'] .= '<p>'.sprintf(i18n::s('Current owner is %s'), Users::get_link($user['full_name'], $user['email'], $user['id'])).'</p>';

	// name current owner
	} elseif(isset($item['owner_id']) && ($owner =& Users::get($item['owner_id']))) {
		$context['text'] .= '<p>'.sprintf(i18n::s('Current owner is %s'), Users::get_link($owner['full_name'], $owner['email'], $owner['id'])).'</p>';

	}

	// delegate to another person
	$context['text'] .= '<p style="margin-top: 2em;">'.i18n::s('To transfer ownership to another person, type some letters of the name you are looking for.').'</p>';

	// the form to link additional users
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'
		.'<input type="text" name="assigned_name" id="name" size="45" maxlength="255" /><div id="name_choices" class="autocomplete"></div> <span id="ajax_spinner" style="display: none"><img src="'.$context['url_to_root'].'skins/_reference/ajax/ajax_completer.gif" alt="Working..." /></span>'
		.'<input type="hidden" name="id" value="'.encode_field($item['id']).'">'
		.'<input type="hidden" name="action" value="set">'
		.'</p></form>'."\n";

	// enable autocompletion
	$context['text'] .= JS_PREFIX
		."\n"
		.'// set the focus on first form field'."\n"
		.'$(document).ready( function() { $("#name").focus() });'."\n"
		."\n"
		.'// enable name autocompletion'."\n"
		.'$(document).ready( function() {'."\n"
		.' Yacs.autocomplete_names("#name",true);'."\n"
		.'});  '."\n"
		.JS_SUFFIX;

	// back to the anchor page
	$links = array();
	$links[] = Skin::build_link(Sections::get_permalink($item).'#_users', i18n::s('Done'), 'button');
	$context['text'] .= Skin::finalize_list($links, 'assistant_bar');

}

// render the skin
render_skin();

?>
