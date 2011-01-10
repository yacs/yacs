<?php
/**
 * duplicate a section
 *
 * This script calls for confirmation, then actually duplicates a section.
 * Images of the original section are duplicated as well, as other attached
 * items such as files, links, or comments, but also tables and locations.
 * It updates the database, then redirects to the anchor page, or to the
 * index page for sections.
 *
 * Accept following invocations:
 * - duplicate.php/12
 * - duplicate.php?id=12
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// look for the section id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Sections::get($id);

// get the related anchor
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor =& Anchors::get($item['anchor']);

// get the related overlay, if any
$overlay = NULL;
include_once '../overlays/overlay.php';
if(isset($item['overlay']))
	$overlay = Overlay::load($item);

// load the skin, maybe with a variant
load_skin('sections', $anchor, isset($item['options']) ? $item['options'] : '');

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// path to this page
$context['path_bar'] = Surfer::get_path_bar($anchor);
if(isset($item['id']))
	$context['path_bar'] = array_merge($context['path_bar'], array(sections::get_permalink($item) => $item['title']));

// page title
if(isset($item['id']))
	$context['page_title'] = sprintf(i18n::s('%s: %s'), i18n::s('Duplicate'), $item['title']);

// not found
if(!isset($item['id'])) {
	include '../error.php';

// permission denied
} elseif(!Sections::is_owned($item, $anchor)) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// action is confirmed
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'duplicate')) {

	// to duplicate related items
	$original_anchor = 'section:'.$item['id'];

	// we will get a new id and a new handle
	unset($item['id']);
	unset($item['handle']);

	// the duplicator becomes the author
	unset($item['create_address']);
	unset($item['create_date']);
	unset($item['create_id']);
	unset($item['create_name']);

	unset($item['edit_address']);
	unset($item['edit_date']);
	unset($item['edit_id']);
	unset($item['edit_name']);

	// ensure this is a copy
	$item['title'] = sprintf(i18n::s('Copy of %s'), $item['title']);
	$item['index_title'] = $item['title'];

	// also duplicate the provided overlay, if any -- re-use 'overlay_type' only
	$overlay = Overlay::load($item);

	// create a new page
	if($item['id'] = Sections::post($item, FALSE)) {

		// post an overlay, with the new section id
		if(is_object($overlay))
			$overlay->remember('insert', $item);

		// duplicate all related items, images, etc.
		Anchors::duplicate_related_to($original_anchor, 'section:'.$item['id']);

		// if poster is a registered user
		if(Surfer::get_id()) {

			// increment the post counter of the surfer
			Users::increment_posts(Surfer::get_id());

			// add this page to watch list
			Members::assign('section:'.$item['id'], 'user:'.Surfer::get_id());

		}

		// get the new item
		$section =& Anchors::get('section:'.$item['id'], TRUE);

		$context['page_title'] = i18n::s('Thank you for your contribution');

		// the page has been duplicated
		$context['text'] .= '<p>'.i18n::s('The section has been duplicated.').'</p>';

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu = array_merge($menu, array($section->get_url() => i18n::s('View the section')));
		$menu = array_merge($menu, array($section->get_url('edit') => i18n::s('Edit this section')));
		$follow_up .= Skin::build_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

		// log the creation of a new section
		$label = sprintf(i18n::c('Section copy: %s'), strip_tags($section->get_title()));

		// poster and target section
		if(is_object($anchor))
			$description = sprintf(i18n::c('Sent by %s in %s'), Surfer::get_name(), $anchor->get_title());
		else
			$description = sprintf(i18n::c('Sent by %s'), Surfer::get_name());

		// title and link
		if($title = $section->get_title())
			$description .= $title."\n";
		$description = $context['url_to_home'].$context['url_to_root'].$section->get_url()."\n\n";

		// notify sysops
		Logger::notify('sections/duplicate.php', $label, $description);

	}

// action has to be confirmed
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {
	Logger::error(i18n::s('The action has not been confirmed.'));

// please confirm
} else {

	// the section or the anchor icon, if any
	$context['page_image'] = $item['icon_url'];
	if(!$context['page_image'] && is_object($anchor))
		$context['page_image'] = $anchor->get_icon_url();

	// commands
	$menu = array();
	$menu[] = Skin::build_submit_button(i18n::s('Yes, I want to duplicate this section'), NULL, NULL, 'confirmed');
	if(isset($item['id']))
		$menu[] = Skin::build_link(sections::get_permalink($item), i18n::s('Cancel'), 'span');

	// render commands
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
		.Skin::finalize_list($menu, 'menu_bar')
		.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n"
		.'<input type="hidden" name="action" value="duplicate" />'."\n"
		.'</p></form>'."\n";

	// set the focus
	$context['text'] .= JS_PREFIX
		.'// set the focus on first form field'."\n"
		.'$("confirmed").focus();'."\n"
		.JS_SUFFIX;

	// the title of the action
	$context['text'] .= Skin::build_block($item['title'], 'title');

	// the introduction text, if any
	$context['text'] .= '<div style="margin: 1em 0;">'.Codes::beautify($item['introduction']).'</div>'."\n";

	// get text related to the overlay, if any
	if(is_object($overlay))
		$context['text'] .= $overlay->get_text('view', $item);

	// details
	$details = array();

	// last edition
	if($item['edit_name'])
		$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

	// hits
	if($item['hits'] > 1)
		$details[] = Skin::build_number($item['hits'], i18n::s('hits'));

	// all details
	if(@count($details))
		$context['text'] .= '<p class="details">'.ucfirst(implode(', ', $details))."</p>\n";

	// count items related to this section
	$context['text'] .= Anchors::stat_related_to('section:'.$item['id'], i18n::s('Following items are attached to this record and will be duplicated as well.'));

}

// render the skin
render_skin();

?>
