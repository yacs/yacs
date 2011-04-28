<?php
/**
 * leave a section or a page
 *
 * This script allows end users to remove some editor assignment by themselves.
 *
 * Accept following invocations:
 * - leave.php?id=article:12
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// users are assigned to this anchor, passed as member
$anchor = NULL;
if(isset($_REQUEST['id']))
	$anchor =& Anchors::get($_REQUEST['id']);

// associates can do what they want
if(Surfer::is_associate())
	$permitted = TRUE;

// a member who manages his editor rights
elseif(is_object($anchor) && $anchor->is_assigned(Surfer::get_id(), FALSE))
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

// an anchor is mandatory
if(!is_object($anchor))
	Logger::error(i18n::s('No anchor has been found.'));

// permission denied
elseif(!$permitted) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

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
		.'<input type="hidden" name="id" value="'.$anchor->get_reference().'" />'."\n"
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