<?php
/**
 * promote a comment
 *
 * This script calls for confirmation, then turns a comment to an article.
 *
 * This page is to be used by associates and authenticated editors only, while they are editing comments.
 * The script updates the database, then redirects to the new article.
 *
 * Restrictions apply on this page:
 * - associates and authenticated editors are allowed to move forward
 * - else permission is denied
 *
 * Accept following invocations:
 * - promote.php/12
 * - promote.php?id=12
 *
 * If the anchor for this item specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'comments.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Comments::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor =& Anchors::get($item['anchor']);

// actual capability of this surfer
if(is_object($anchor) && $anchor->is_editable())
	Surfer::empower();

// associates and authenticated editors can do what they want
if(Surfer::is_associate() || (Surfer::is_member() && is_object($anchor) && $anchor->is_editable()))
	$permitted = TRUE;

// the default is to deny access
else
	$permitted = FALSE;

// load the skin, maybe with a variant
load_skin('comments', $anchor);

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'comments/' => i18n::s('Threads') );

// the title of the page
if(is_object($anchor) && $anchor->is_viewable())
	$context['page_title'] = $anchor->get_label('comments', 'promote_title');
else
	$context['page_title'] = i18n::s('Promote a comment');

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// not found
} elseif(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Comments::get_url($item['id'], 'delete')));

	// permission denied to authenticated user
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// deletion is confirmed
} elseif(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'yes')) {

	// touch the related anchor before actual deletion, since the item has to be accessible at that time
	if(is_object($anchor))
		$anchor->touch('comment:promote', $item['id'], TRUE);

	// prepare a new article
	$fields = array();

	// get an anchor
	$fields['anchor'] = $anchor->get_reference();
	if(!preg_match('/^section/i', $fields['anchor']))
		$fields['anchor'] = $anchor->get_parent();

	// a title
	$fields['title'] = Skin::strip($item['description'], 10, NULL, '');

	// a body
	$fields['description'] = $item['description'];

	// initial poster
	$fields['create_name'] = $item['create_name'];
	$fields['create_id'] = $item['create_id'];
	$fields['create_address'] = $item['create_address'];
	$fields['create_date'] = $item['create_date'];

	// make an article
	if(!$fields['id'] = Articles::post($fields))
		Logger::error(i18n::s('Impossible to add a page.'));

	// delete the comment and jump to the new article
	elseif(Comments::delete($item['id'])) {
		Comments::clear($item);
		Safe::redirect($context['url_to_home'].$context['url_to_root'].Articles::get_permalink($fields));
	}

// promotion has to be confirmed
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {
	Logger::error(i18n::s('The action has not been confirmed.'));

// ask for confirmation
} else {

	// the submit button
	if(is_object($anchor))
		$label = $anchor->get_label('comments', 'promote_command');
	else
		$label = i18n::s('Yes, I want to promote this comment to an article');

	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
		.Skin::build_submit_button($label, NULL, NULL, 'confirmed')."\n"
		.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n"
		.'<input type="hidden" name="confirm" value="yes" />'."\n"
		.'</p></form>'."\n";

	// set the focus
	$context['text'] .= JS_PREFIX
		.'// set the focus on first form field'."\n"
		.'$("confirmed").focus();'."\n"
		.JS_SUFFIX;

	// the title of the comment
	if(isset($item['title']) && $item['title'])
		$context['text'] .= Skin::build_block($item['title'], 'title');

	// the poster of this comment
	$details[] = sprintf(i18n::s('by %s %s'), Users::get_link($item['create_name'], $item['create_address'], $item['create_id']), Skin::build_date($item['create_date']));

	// the last edition of this comment
	if($item['create_name'] != $item['edit_name'])
		$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

	// the complete details
	if($details)
		$context['text'] .= '<p class="details">'.ucfirst(implode(', ', $details))."</p>\n";

	// display the full comment
	$context['text'] .= Skin::build_block($item['description'], 'description');

}

// render the skin
render_skin();

?>