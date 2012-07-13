<?php
/**
 * manage section content.
 *
 * This script allows to select several items, and to apply one command to the set.
 *
 * Available operations for articles include:
 * - associate pages to one category (with confirmation)
 * - duplicate selected pages, in a different section if necessary (with confirmation)
 * - publish selected pages
 * - change selected pages to draft mode
 * - lock /unlock several pages
 * - move pages to another section at once (with confirmation)
 * - delete pages at once (with confirmation)
 * - change page rankings
 *
 * Available operations for sections include:
 * - associate pages to one category (with confirmation)
 * - duplicate selected pages, in a different section if necessary (with confirmation)
 * - lock /unlock several pages
 * - move pages to another section at once (with confirmation)
 * - delete pages at once (with confirmation)
 * - change page rankings
 *
 * Restrictions apply on this page:
 * - associates can proceed
 * - managing editors can proceed too, but only for local actions
 * - permission denied is the default
 *
 * Accept following invocations:
 * - manage.php/12
 * - manage.php?id=12
 * - manage.php/12/duplicate
 * - manage.php?id=12&action=duplicate
 * - manage.php/12/duplicate
 * - manage.php?id=12&action=duplicate_confirmed
 *
 * If this section, or one of its anchor, specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @tester Alain Lesage (Lasares)
 * @tester Francois Charron
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

// no follow-up page yet
$zoom_type = '';
$zoom_index = 1;

// manage.php?id=12&sections=2
if(isset($_REQUEST['sections']) && ($zoom_index = $_REQUEST['sections']))
	$zoom_type = 'sections';

// manage.php?id=12&articles=2
elseif(isset($_REQUEST['articles']) && ($zoom_index = $_REQUEST['articles']))
	$zoom_type = 'articles';

// manage.php?id=12&comments=2
elseif(isset($_REQUEST['comments']) && ($zoom_index = $_REQUEST['comments']))
	$zoom_type = 'comments';

// manage.php?id=12&files=2
elseif(isset($_REQUEST['files']) && ($zoom_index = $_REQUEST['files']))
	$zoom_type = 'files';

// manage.php?id=12&links=2
elseif(isset($_REQUEST['links']) && ($zoom_index = $_REQUEST['links']))
	$zoom_type = 'links';

// manage.php/12/files/2
elseif(isset($context['arguments'][1]) && isset($context['arguments'][2])) {
	$zoom_type = $context['arguments'][1];
	$zoom_index = $context['arguments'][2];
}

// sanity check
if($zoom_index < 1)
	$zoom_index = 1;

// get the item from the database
$item =& Sections::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor = Anchors::get($item['anchor']);

// which action?
$action = NULL;
if(isset($_REQUEST['act_on_articles']) && ($_REQUEST['act_on_articles'][0] != '-'))
	$action = $_REQUEST['act_on_articles'];
elseif(isset($_REQUEST['act_on_sections']) && ($_REQUEST['act_on_sections'][0] != '-'))
	$action = $_REQUEST['act_on_sections'];
elseif(isset($_REQUEST['action']) && $_REQUEST['action'])
	$action = $_REQUEST['action'];
$action = strip_tags($action);

// only owners can proceed
if(Sections::is_owned($item, $anchor) || Surfer::is_associate()) {
	Surfer::empower();
	$permitted = TRUE;

// the default is to disallow access
} else
	$permitted = FALSE;

// load the skin, maybe with a variant
load_skin('sections', $anchor, isset($item['options']) ? $item['options'] : '');

// list selected pages
$selected_articles = '';
if(isset($_REQUEST['selected_articles']) && ($count = @count($_REQUEST['selected_articles']))) {

	// if more than 10 pages, list the first five, else display all
	if($count >= 10)
		$bucket = 5;
	else
		$bucket = $count;

	$items = array();
	foreach($_REQUEST['selected_articles'] as $dummy => $id) {
		if($article =& Articles::get($id)) {
			if($bucket-- >= 0)
				$items[] = Skin::build_link(Articles::get_permalink($article), $article['title'], 'article');
			$selected_articles .= '<input type="hidden" name="selected_articles[]" value="'.$article['id'].'" />';
		}
	}

	// a hint on non-listed pages
	if($delta = $count - count($items))
		$items[] = sprintf(i18n::ns('%d other page has been selected', '%d other pages have been selected', $delta), $delta);

	// a limited compact list
	$selected_articles .= Skin::finalize_list($items, 'compact');

}

// list selected sections
$selected_sections = '';
if(isset($_REQUEST['selected_sections']) && ($count = @count($_REQUEST['selected_sections']))) {

	// if more than 10 pages, list the first five, else display all
	if($count >= 10)
		$bucket = 5;
	else
		$bucket = $count;

	$items = array();
	foreach($_REQUEST['selected_sections'] as $dummy => $id) {
		if($section =& Sections::get($id)) {
			if($bucket-- >= 0)
				$items[] = Skin::build_link(Sections::get_permalink($section), $section['title'], 'article');
			$selected_sections .= '<input type="hidden" name="selected_sections[]" value="'.$section['id'].'" />';
		}
	}

	// a hint on non-listed pages
	if($delta = $count - count($items))
		$items[] = sprintf(i18n::ns('%d other page has been selected', '%d other pages have been selected', $delta), $delta);

	// a limited compact list
	$selected_sections .= Skin::finalize_list($items, 'compact');

}

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor))
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'sections/' => i18n::s('Site map') );
if(isset($item['id']))
	$context['path_bar'] = array_merge($context['path_bar'], array(Sections::get_permalink($item) => $item['title']));

// pag etitle
if(isset($item['title']))
	$context['page_title'] = sprintf(i18n::s('Manage: %s'), $item['title']);
else
	$context['page_title'] = i18n::s('Manage');

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// not found
} elseif(!isset($item['id'])) {
	include '../error.php';

// access denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Sections::get_url($item['id'], 'manage')));

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// associate selected pages
} elseif($action == 'associate_articles') {

	// articles
	if(isset($_REQUEST['selected_articles'])) {

		// actually a form
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
			.'<input type="hidden" name="action" value="associate_confirmed" />'."\n"
			.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n";

		// target section
		$context['text'] .= '<p>'.i18n::s('Associate pages below to').BR.'<select name=associate_to>'.Categories::get_options().'</select></p>';

		// selected pages
		$context['text'] .= $selected_articles;

		// follow-up commands
		$menu = array();
		$menu[] = Skin::build_submit_button(i18n::s('Categorize'));
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), 'Cancel', 'span');
		$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

		$context['text'] .= '</form>';

	// nothing to do
	} else
		Logger::error(i18n::s('No page has been selected.'));

// associate selected pages
} elseif($action == 'associate_sections') {

	// sections
	if(isset($_REQUEST['selected_sections'])) {
		// actually a form
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
			.'<input type="hidden" name="action" value="associate_confirmed" />'."\n"
			.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n";

		// target section
		$context['text'] .= '<p>'.i18n::s('Associate pages below to').BR.'<select name=associate_to>'.Categories::get_options().'</select></p>';

		// selected pages
		$context['text'] .= $selected_sections;

		// follow-up commands
		$menu = array();
		$menu[] = Skin::build_submit_button(i18n::s('Categorize'));
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), 'Cancel', 'span');
		$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

		$context['text'] .= '</form>';

	// nothing to do
	} else
		Logger::error(i18n::s('No section has been selected.'));

// actual association
} elseif($action == 'associate_confirmed') {

	// nothing to do
	if(!isset($_REQUEST['associate_to']) || (!$destination = Anchors::get($_REQUEST['associate_to'])))
		Logger::error(i18n::s('Bad request.'));

	// articles
	elseif(isset($_REQUEST['selected_articles'])) {

		// do the action, and clear the cache
		$count = 0;
		foreach($_REQUEST['selected_articles'] as $dummy => $id) {
			if(!$error = Members::assign($_REQUEST['associate_to'], 'article:'.$id))
				$count++;
		}

		// report on results
		$context['text'] .= '<p>'.sprintf(i18n::ns('%d page has been associated with %s.', '%d pages have been associated with %s.', $count),
			$count, Skin::build_link($destination->get_url(), $destination->get_title(), 'category')).'</p>';

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu[] = Skin::build_link(Sections::get_permalink($item), i18n::s('View the section'), 'span');
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), i18n::s('Manage it'), 'span');
		$follow_up .= Skin::finalize_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	// sections
	} elseif(isset($_REQUEST['selected_sections'])) {

		// do it, and clear the cache
		$count = 0;
		foreach($_REQUEST['selected_sections'] as $dummy => $id) {
			if(!$error = Members::assign($_REQUEST['associate_to'], 'section:'.$id))
				$count++;
		}

		// report on results
		$context['text'] .= '<p>'.sprintf(i18n::ns('%d page has been associated with %s.', '%d pages have been associated with %s.', $count),
			$count, Skin::build_link($destination->get_url(), $destination->get_title(), 'category')).'</p>';

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu[] = Skin::build_link(Sections::get_permalink($item), i18n::s('View the section'), 'span');
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), i18n::s('Manage it'), 'span');
		$follow_up .= Skin::finalize_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	// nothing to do
	} else
		Logger::error(i18n::s('No page has been selected.'));

// delete selected pages
} elseif($action == 'delete_articles') {

	// articles
	if(isset($_REQUEST['selected_articles'])) {

		// splash
		$context['text'] .= '<p>'.i18n::s('Please confirm that you want to delete following pages.').'</p>';

		// actually a form
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
			.'<input type="hidden" name="action" value="delete_confirmed" />'."\n"
			.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n"
			.$selected_articles;

		// follow-up commands
		$menu = array();
		$menu[] = Skin::build_submit_button(i18n::s('Delete'));
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), 'Cancel', 'span');
		$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

		$context['text'] .= '</form>';

	// nothing to do
	} else
		Logger::error(i18n::s('No page has been selected.'));

// delete selected pages
} elseif($action == 'delete_sections') {

	// sections
	if(isset($_REQUEST['selected_sections'])) {

		// splash
		$context['text'] .= '<p>'.i18n::s('Please confirm that you want to delete following pages.').'</p>';

		// actually a form
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
			.'<input type="hidden" name="action" value="delete_confirmed" />'."\n"
			.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n"
			.$selected_sections;

		// follow-up commands
		$menu = array();
		$menu[] = Skin::build_submit_button(i18n::s('Delete'));
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), 'Cancel', 'span');
		$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

		$context['text'] .= '</form>';

	// nothing to do
	} else
		Logger::error(i18n::s('No section has been selected.'));

// actual deletion
} elseif($action == 'delete_confirmed') {

	// articles
	if(isset($_REQUEST['selected_articles'])) {

		$count = 0;
		foreach($_REQUEST['selected_articles'] as $dummy => $id) {
			if(Articles::delete($id))
				$count++;
		}

		// clear the cache for this section
		Sections::clear($item);

		// report on results
		$context['text'] .= '<p>'.sprintf(i18n::ns('%d page has been deleted.', '%d pages have been deleted.', $count), $count).'</p>';

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu[] = Skin::build_link(Sections::get_permalink($item), i18n::s('View the section'), 'span');
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), i18n::s('Manage it'), 'span');
		$follow_up .= Skin::finalize_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	// sections
	} elseif(isset($_REQUEST['selected_sections'])) {

		$count = 0;
		foreach($_REQUEST['selected_sections'] as $dummy => $id) {
			if(Sections::delete($id))
				$count++;
		}

		// clear the cache for this section
		Sections::clear($item);

		// report on results
		$context['text'] .= '<p>'.sprintf(i18n::ns('%d page has been deleted.', '%d pages have been deleted.', $count), $count).'</p>';

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu[] = Skin::build_link(Sections::get_permalink($item), i18n::s('View the section'), 'span');
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), i18n::s('Manage it'), 'span');
		$follow_up .= Skin::finalize_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	// nothing to do
	} else
		Logger::error(i18n::s('No page has been selected.'));

// draft pages
} elseif($action == 'draft_articles') {

	// articles
	if(isset($_REQUEST['selected_articles'])) {

		$count = 0;
		foreach($_REQUEST['selected_articles'] as $dummy => $id) {

			// the article to de-publish
			if(($article =& Articles::get($id)) && ($article['publish_date'] > NULL_DATE)) {

				$attributes = array();
				$attributes['id'] = $article['id'];
				$attributes['publish_date'] = NULL_DATE;
				$attributes['silent'] = 'Y';
				if(Articles::put_attributes($attributes))
					$count++;

			}
		}

		// clear the cache for this section
		Sections::clear($item);

		// report on results
		$context['text'] .= '<p>'.sprintf(i18n::ns('%d page has been changed to draft mode.', '%d pages have been changed to draft mode.', $count), $count).'</p>';

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu[] = Skin::build_link(Sections::get_permalink($item), i18n::s('View the section'), 'span');
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), i18n::s('Manage it'), 'span');
		$follow_up .= Skin::finalize_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	// nothing to do
	} else
		Logger::error(i18n::s('No page has been selected.'));

// duplicate selected pages
} elseif($action == 'duplicate_articles') {

	// articles
	if(isset($_REQUEST['selected_articles'])) {

		// actually a form
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
			.'<input type="hidden" name="action" value="duplicate_confirmed" />'."\n"
			.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n";

		// target section
		$context['text'] .= '<p>'.i18n::s('Duplicate following pages in').BR.'<select name=duplicate_to>'.Sections::get_options('section:'.$item['id']).'</select></p>';

		// selected pages
		$context['text'] .= $selected_articles;

		// follow-up commands
		$menu = array();
		$menu[] = Skin::build_submit_button(i18n::s('Duplicate'));
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), 'Cancel', 'span');
		$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

		$context['text'] .= '</form>';

	// nothing to do
	} else
		Logger::error(i18n::s('No page has been selected.'));

// duplicate selected pages
} elseif($action == 'duplicate_sections') {

	// sections
	if(isset($_REQUEST['selected_sections'])) {

		// actually a form
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
			.'<input type="hidden" name="action" value="duplicate_confirmed" />'."\n"
			.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n";

		// target section
		$context['text'] .= '<p>'.i18n::s('Duplicate following pages in').BR.'<select name=duplicate_to>'.Sections::get_options('section:'.$item['id']).'</select></p>';

		// selected pages
		$context['text'] .= $selected_sections;

		// follow-up commands
		$menu = array();
		$menu[] = Skin::build_submit_button(i18n::s('Duplicate'));
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), 'Cancel', 'span');
		$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

		$context['text'] .= '</form>';

	// nothing to do
	} else
		Logger::error(i18n::s('No section has been selected.'));

// actual duplication
} elseif($action == 'duplicate_confirmed') {

	// nothing to do
	if(!isset($_REQUEST['duplicate_to']) || (!$destination = Anchors::get($_REQUEST['duplicate_to'])))
		Logger::error(i18n::s('Bad request.'));

	// articles
	elseif(isset($_REQUEST['selected_articles'])) {

		$count = 0;
		foreach($_REQUEST['selected_articles'] as $dummy => $id) {

			// the article to duplicate
			if($article =& Articles::get($id)) {

				// a new id will be allocated
				$old_id = $article['id'];
				unset($article['id']);

				// the duplicator becomes the author
				unset($article['create_address']);
				unset($article['create_date']);
				unset($article['create_id']);
				unset($article['create_name']);

				unset($article['edit_address']);
				unset($article['edit_date']);
				unset($article['edit_id']);
				unset($article['edit_name']);

				// page will have to be published after modification
				unset($article['publish_address']);
				unset($article['publish_date']);
				unset($article['publish_id']);
				unset($article['publish_name']);

				// ensure this is a copy
				$article['title'] = sprintf(i18n::s('Copy of %s'), $article['title']);

				// target anchor
				$article['anchor'] = $_REQUEST['duplicate_to'];

				// actual duplication
				if($article['id'] = Articles::post($article)) {

					// also duplicate the provided overlay, if any -- re-use 'overlay_type' only
					$overlay = Overlay::load($article, 'article:'.$article['id']);

					// post an overlay, with the new article id
					if(is_object($overlay))
						$overlay->remember('insert', $article, 'article:'.$article['id']);

					// duplicate elements related to this item
					Anchors::duplicate_related_to('article:'.$old_id, 'article:'.$article['id']);

					// stats
					$count++;
				}
			}
		}

		// report on results
		$context['text'] .= '<p>'.sprintf(i18n::ns('%d page has been duplicated.', '%d pages have been duplicated.', $count), $count).'</p>';

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu[] = Skin::build_link(Sections::get_permalink($item), i18n::s('View the section'), 'span');
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), i18n::s('Manage it'), 'span');
		$follow_up .= Skin::finalize_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	// sections
	} elseif(isset($_REQUEST['selected_sections'])) {

		$count = 0;
		foreach($_REQUEST['selected_sections'] as $dummy => $id) {

			// the section to duplicate
			if($section =& Sections::get($id)) {

				// a new id will be allocated
				$old_id = $section['id'];
				unset($section['id']);

				// the duplicator becomes the author
				unset($section['create_address']);
				unset($section['create_date']);
				unset($section['create_id']);
				unset($section['create_name']);

				unset($section['edit_address']);
				unset($section['edit_date']);
				unset($section['edit_id']);
				unset($section['edit_name']);

				// change the handle
				unset($section['handle']);

				// ensure this is a copy
				$section['title'] = sprintf(i18n::s('Copy of %s'), $section['title']);

				// target anchor
				$section['anchor'] = $_REQUEST['duplicate_to'];

				// actual duplication
				if($section['id'] = Sections::post($section, FALSE)) {

					// also duplicate the provided overlay, if any -- re-use 'overlay_type' only
					$overlay = Overlay::load($section, 'section:'.$section['id']);

					// post an overlay, with the new section id
					if(is_object($overlay))
						$overlay->remember('insert', $section, 'section:'.$section['id']);

					// duplicate elements related to this item
					Anchors::duplicate_related_to('section:'.$old_id, 'section:'.$section['id']);

					// stats
					$count++;
				}
			}
		}

		// report on results
		$context['text'] .= '<p>'.sprintf(i18n::ns('%d page has been duplicated.', '%d pages have been duplicated.', $count), $count).'</p>';

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu[] = Skin::build_link(Sections::get_permalink($item), i18n::s('View the section'), 'span');
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), i18n::s('Manage it'), 'span');
		$follow_up .= Skin::finalize_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	// nothing to do
	} else
		Logger::error(i18n::s('No page has been selected.'));

// lock pages
} elseif($action == 'lock_articles') {

	// articles
	if(isset($_REQUEST['selected_articles'])) {

		$count = 0;
		foreach($_REQUEST['selected_articles'] as $dummy => $id) {

			// an article to lock
			if(($article =& Articles::get($id)) && ($article['locked'] != 'Y')) {

				$attributes = array();
				$attributes['id'] = $article['id'];
				$attributes['locked'] = 'Y';
				$attributes['silent'] = 'Y';	// too minor to be noted
				if(Articles::put_attributes($attributes))
					$count++;

			}
		}

		// clear the cache for this section
		Sections::clear($item);

		// report on results
		$context['text'] .= '<p>'.sprintf(i18n::ns('%d page has been locked.', '%d pages have been locked.', $count), $count).'</p>';

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu[] = Skin::build_link(Sections::get_permalink($item), i18n::s('View the section'), 'span');
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), i18n::s('Manage it'), 'span');
		$follow_up .= Skin::finalize_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	// nothing to do
	} else
		Logger::error(i18n::s('No page has been selected.'));

// lock pages
} elseif($action == 'lock_sections') {

	// sections
	if(isset($_REQUEST['selected_sections'])) {

		$count = 0;
		foreach($_REQUEST['selected_sections'] as $dummy => $id) {

			// an section to lock
			if(($section =& Sections::get($id)) && ($section['locked'] != 'Y')) {

				$attributes = array();
				$attributes['id'] = $section['id'];
				$attributes['locked'] = 'Y';
				$attributes['silent'] = 'Y';	// too minor to be noted
				if(Sections::put_attributes($attributes))
					$count++;

			}
		}

		// clear the cache for this section
		Sections::clear($item);

		// report on results
		$context['text'] .= '<p>'.sprintf(i18n::ns('%d page has been locked.', '%d pages have been locked.', $count), $count).'</p>';

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu[] = Skin::build_link(Sections::get_permalink($item), i18n::s('View the section'), 'span');
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), i18n::s('Manage it'), 'span');
		$follow_up .= Skin::finalize_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	// nothing to do
	} else
		Logger::error(i18n::s('No section has been selected.'));

// move selected pages
} elseif($action == 'move_articles') {

	// articles
	if(isset($_REQUEST['selected_articles'])) {

		// actually a form
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
			.'<input type="hidden" name="action" value="move_confirmed" />'."\n"
			.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n";

		// target section
		$context['text'] .= '<p>'.i18n::s('Move following pages to').BR.'<select name=move_to>'.Sections::get_options('section:'.$item['id']).'</select></p>';

		// selected pages
		$context['text'] .= $selected_articles;

		// follow-up commands
		$menu = array();
		$menu[] = Skin::build_submit_button(i18n::s('Move'));
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), 'Cancel', 'span');
		$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

		$context['text'] .= '</form>';

	// nothing to do
	} else
		Logger::error(i18n::s('No page has been selected.'));

// move selected pages
} elseif($action == 'move_sections') {

	// sections
	if(isset($_REQUEST['selected_sections'])) {

		// actually a form
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
			.'<input type="hidden" name="action" value="move_confirmed" />'."\n"
			.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n";

		// target section
		$context['text'] .= '<p>'.i18n::s('Move following pages to').BR.'<select name=move_to>'.Sections::get_options('section:'.$item['id']).'</select></p>';

		// selected pages
		$context['text'] .= $selected_sections;

		// follow-up commands
		$menu = array();
		$menu[] = Skin::build_submit_button(i18n::s('Move'));
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), 'Cancel', 'span');
		$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

		$context['text'] .= '</form>';

	// nothing to do
	} else
		Logger::error(i18n::s('No page has been selected.'));

// actual move
} elseif($action == 'move_confirmed') {

	// nothing to do
	if(!isset($_REQUEST['move_to']) || (!$destination = Anchors::get($_REQUEST['move_to'])))
		Logger::error(i18n::s('Bad request.'));

	// articles
	elseif(isset($_REQUEST['selected_articles'])) {

		$count = 0;
		foreach($_REQUEST['selected_articles'] as $dummy => $id) {
			$attributes = array();
			$attributes['id'] = $id;
			$attributes['anchor'] = $destination->get_reference();
			$attributes['silent'] = 'Y'; // preserve dates of actual modifications
			if(Articles::put_attributes($attributes))
				$count++;
		}

		// clear cache for origin and destination containers
		Cache::clear(array($item, $destination->get_reference()));

		// report on results
		$context['text'] .= '<p>'.sprintf(i18n::ns('%d page has been moved to %s.', '%d pages have been moved to %s.', $count),
			$count, Skin::build_link($destination->get_url(), $destination->get_title(), 'section')).'</p>';

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu[] = Skin::build_link(Sections::get_permalink($item), i18n::s('View the section'), 'span');
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), i18n::s('Manage it'), 'span');
		$follow_up .= Skin::finalize_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	// sections
	} elseif(isset($_REQUEST['selected_sections'])) {

		$count = 0;
		foreach($_REQUEST['selected_sections'] as $dummy => $id) {
			$attributes = array();
			$attributes['id'] = $id;
			$attributes['anchor'] = $destination->get_reference();
			$attributes['silent'] = 'Y'; // preserve dates of actual modifications
			if(Sections::put_attributes($attributes))
				$count++;
		}

		// clear cache for origin and destination containers
		Cache::clear(array($item, $destination->get_reference()));

		// report on results
		$context['text'] .= '<p>'.sprintf(i18n::ns('%d page has been moved to %s.', '%d pages have been moved to %s.', $count),
			$count, Skin::build_link($destination->get_url(), $destination->get_title(), 'section')).'</p>';

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu[] = Skin::build_link(Sections::get_permalink($item), i18n::s('View the section'), 'span');
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), i18n::s('Manage it'), 'span');
		$follow_up .= Skin::finalize_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	// nothing to do
	} else
		Logger::error(i18n::s('No page has been selected.'));

// publish pages
} elseif($action == 'publish_articles') {

	// articles
	if(isset($_REQUEST['selected_articles'])) {

		$count = 0;
		foreach($_REQUEST['selected_articles'] as $dummy => $id) {

			// the article to publish
			if(($article =& Articles::get($id)) && ($article['publish_date'] <= NULL_DATE)) {

				if(!Articles::stamp($article['id'], gmstrftime('%Y-%m-%d %H:%M:%S'), ''))
					$count++;

			}
		}

		// clear cache for containing section
		Sections::clear($item);

		// report on results
		$context['text'] .= '<p>'.sprintf(i18n::ns('%d page has been published.', '%d pages have been published.', $count), $count).'</p>';

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu[] = Skin::build_link(Sections::get_permalink($item), i18n::s('View the section'), 'span');
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), i18n::s('Manage it'), 'span');
		$follow_up .= Skin::finalize_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	// nothing to do
	} else
		Logger::error(i18n::s('No page has been selected.'));

// update rankings
} elseif(($action == 'rank_articles') || ($action == 'rank_sections')) {

	// actual processing
	$count = 0;
	foreach($_REQUEST as $name => $value) {

		if(($action == 'rank_articles') && (strpos($name, 'article_rank_') === 0)) {
			$attributes = array();
			$attributes['id'] = intval(substr($name, 13));
			$attributes['rank'] = intval($value);
			$attributes['silent'] = 'Y'; // preserve dates of actual modifications
			if(Articles::put_attributes($attributes))
				$count++;
		}

		if(($action == 'rank_sections') && (strpos($name, 'section_rank_') === 0)) {
			$attributes = array();
			$attributes['id'] = intval(substr($name, 13));
			$attributes['rank'] = intval($value);
			$attributes['silent'] = 'Y'; // preserve dates of actual modifications
			if(Sections::put_attributes($attributes))
				$count++;
		}
	}

	// clear cache for containing section
	Sections::clear($item);

	// report on results
	$context['text'] .= '<p>'.sprintf(i18n::ns('%d page has been updated.', '%d pages have been updated.', $count), $count).'</p>';

	// follow-up commands
	$follow_up = i18n::s('What do you want to do now?');
	$menu = array();
	$menu[] = Skin::build_link(Sections::get_permalink($item), i18n::s('View the section'), 'span');
	$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), i18n::s('Manage it'), 'span');
	$follow_up .= Skin::finalize_list($menu, 'menu_bar');
	$context['text'] .= Skin::build_block($follow_up, 'bottom');

// unlock pages
} elseif($action == 'unlock_articles') {

	// articles
	if(isset($_REQUEST['selected_articles'])) {

		$count = 0;
		foreach($_REQUEST['selected_articles'] as $dummy => $id) {

			// an article to lock
			if(($article =& Articles::get($id)) && ($article['locked'] == 'Y')) {

				$attributes = array();
				$attributes['id'] = $article['id'];
				$attributes['locked'] = 'N';
				$attributes['silent'] = 'Y';	// too minor to be noted
				if(Articles::put_attributes($attributes))
					$count++;

			}
		}

		// clear cache for containing section
		Sections::clear($item);

		// report on results
		$context['text'] .= '<p>'.sprintf(i18n::ns('%d page has been unlocked.', '%d pages have been unlocked.', $count), $count).'</p>';

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu[] = Skin::build_link(Sections::get_permalink($item), i18n::s('View the section'), 'span');
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), i18n::s('Manage it'), 'span');
		$follow_up .= Skin::finalize_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	// nothing to do
	} else
		Logger::error(i18n::s('No page has been selected.'));

// unlock pages
} elseif($action == 'unlock_sections') {

	// sections
	if(isset($_REQUEST['selected_sections'])) {

		$count = 0;
		foreach($_REQUEST['selected_sections'] as $dummy => $id) {

			// an section to lock
			if(($section =& Sections::get($id)) && ($section['locked'] == 'Y')) {

				$attributes = array();
				$attributes['id'] = $section['id'];
				$attributes['locked'] = 'N';
				$attributes['silent'] = 'Y';	// too minor to be noted
				if(Sections::put_attributes($attributes))
					$count++;

			}
		}

		// clear cache for containing section
		Sections::clear($item);

		// report on results
		$context['text'] .= '<p>'.sprintf(i18n::ns('%d page has been unlocked.', '%d pages have been unlocked.', $count), $count).'</p>';

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu[] = Skin::build_link(Sections::get_permalink($item), i18n::s('View the section'), 'span');
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), i18n::s('Manage it'), 'span');
		$follow_up .= Skin::finalize_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	// nothing to do
	} else
		Logger::error(i18n::s('No section has been selected.'));

// which operation?
} else {

	// the form
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>';

	// panels
	//
	$panels = array();

	// one tab for articles
	//
	$text = '';

	// managed articles
	include_once '../articles/layout_articles_as_manage.php';
	$layout = new Layout_articles_as_manage();

	// avoid links to this page
	if(is_object($layout) && is_callable(array($layout, 'set_variant')))
		$layout->set_variant('section:'.$item['id']);

	// the maximum number of articles per page
	if(is_object($layout))
		$items_per_page = $layout->items_per_page();
	else
		$items_per_page = ARTICLES_PER_PAGE;

	// list content
 	$offset = ($zoom_index - 1) * $items_per_page;
	if($items =& Articles::list_for_anchor('section:'.$item['id'], $offset, $items_per_page, $layout)) {

		// splash
		$text .= Skin::build_block(i18n::s('Select items you want to manage, and click some button at the bottom of the page.'), 'introduction');

		// the actual content
		$text .= $items;

		// some script to actually do the job
		$text .= JS_PREFIX
			.'function count_selected_articles() {'."\n"
			.'	var count = 0;'."\n"
			.'	$("div#articles_panel input[type=\'checkbox\'].row_selector").each('."\n"
			.'		function() { count++;}'."\n"
			.'	);'."\n"
			.'	return count;'."\n"
			.'}'."\n"
			."\n"
			.'function submit_selected_articles() {'."\n"
			.'	if(count_selected_articles() < 1) {'."\n"
			.'		alert("'.i18n::s('No page has been selected.').'");'."\n"
			.'	} else {'."\n"
			.'		$("#main_form").submit();'."\n"
			.'	}'."\n"
			.'}'."\n"
			."\n"
			.JS_SUFFIX."\n";

		// a list of commands
		$options = '<select name="act_on_articles" id="act_on_articles"><option>'.i18n::s('For the selection:').'</option>';

		// categorize selected pages
		$options .= '<option value="associate_articles">'.i18n::s('Categorize').'</option>';

		// publish selected pages
		$options .= '<option value="publish_articles">'.i18n::s('Publish').'</option>';

		// draft selected pages
		$options .= '<option value="draft_articles">'.i18n::s('Draft').'</option>';

		// lock selected pages
		$options .= '<option value="lock_articles">'.i18n::s('Lock').'</option>';

		// unlock selected pages
		$options .= '<option value="unlock_articles">'.i18n::s('Unlock').'</option>';

		// duplicate selected pages
		$options .= '<option value="duplicate_articles">'.i18n::s('Duplicate').'</option>';

		// move selected pages
		$options .= '<option value="move_articles">'.i18n::s('Move').'</option>';

		// delete selected pages
		$options .= '<option value="delete_articles">'.i18n::s('Delete').'</option>';

		// order pages
		$options .= '<option value="rank_articles">'.i18n::s('Order').'</options>';

		// end of options
		$options .= '</select> <a href="#" class="button" onclick="submit_selected_articles(); return false;"><span>'.i18n::s('Go').'</span></a> ';

		// all commands
		$menu = array();
		$menu[] = $options;

		// back to section
		$menu[] = Skin::build_link(Sections::get_permalink($item), i18n::s('Cancel'), 'span');

		// finalize the menu
		$text .= BR.Skin::finalize_list($menu, 'menu_bar');

		// count the number of articles in this section
		$menu = array();
		if($count = Articles::count_for_anchor('section:'.$item['id'])) {
			if($count > min(5, $items_per_page))
				$menu = array_merge($menu, array('_count' => sprintf(i18n::ns('%d page', '%d pages', $count), $count)));

			// navigation commands for articles
			$home = Sections::get_url($item['id'], 'manage');
			$prefix = Sections::get_url($item['id'], 'manage', 'articles');
			$menu = array_merge($menu, Skin::navigate($home, $prefix, $count, $items_per_page, $zoom_index));

		}
		if(count($menu))
			$text .= Skin::build_list($menu, 'menu_bar');

	}

	// diplay in a separate panel
	if(trim($text))
		$panels[] = array('articles', i18n::s('Pages'), 'articles_panel', $text);

	// one tab for sections
	//
	$text = '';

	// managed sections
	include_once '../sections/layout_sections_as_manage.php';
	$layout = new Layout_sections_as_manage();

	// avoid links to this page
	if(is_object($layout) && is_callable(array($layout, 'set_variant')))
		$layout->set_variant('section:'.$item['id']);

	// the maximum number of articles per page
	if(is_object($layout))
		$items_per_page = $layout->items_per_page();
	else
		$items_per_page = SECTIONS_PER_PAGE;

	// list content
	$offset = ($zoom_index - 1) * $items_per_page;
	if($items =& Sections::list_by_title_for_anchor('section:'.$item['id'], $offset, $items_per_page, $layout)) {

		// splash
		$text .= Skin::build_block(i18n::s('Select items you want to manage, and click some button at the bottom of the page.'), 'introduction');

		// the actual content
		$text .= $items;

		// some script to actually do the job
		$text .= JS_PREFIX
			.'function count_selected_sections() {'."\n"
			.'	var count = 0;'."\n"
			.'	$("div#sections_panel input[type=\'checkbox\'].row_selector").each('."\n"
			.'		function() { count++;}'."\n"
			.'	);'."\n"
			.'	return count;'."\n"
			.'}'."\n"
			."\n"
			.'function submit_selected_sections() {'."\n"
			.'	if(count_selected_sections() < 1) {'."\n"
			.'		alert("'.i18n::s('No section has been selected.').'");'."\n"
			.'	} else {'."\n"
			.'		$("#main_form").submit();'."\n"
			.'	}'."\n"
			.'}'."\n"
			."\n"
			.JS_SUFFIX."\n";

		// a list of commands
		$options = '<select name="act_on_sections" id="act_on_sections"><option>'.i18n::s('For the selection:').'</option>';

		// categorize selected pages
		$options .= '<option value="associate_sections">'.i18n::s('Categorize').'</option>';

		// lock selected pages
		$options .= '<option value="lock_sections">'.i18n::s('Lock').'</option>';

		// unlock selected pages
		$options .= '<option value="unlock_sections">'.i18n::s('Unlock').'</option>';

		// duplicate selected pages
//		$options .= '<option value="duplicate_sections">'.i18n::s('Duplicate').'</option>';

		// move selected pages
		$options .= '<option value="move_sections">'.i18n::s('Move').'</option>';

		// delete selected pages
		$options .= '<option value="delete_sections">'.i18n::s('Delete').'</option>';

		// order pages
		$options .= '<option value="rank_sections">'.i18n::s('Order').'</option>';

		// end of options
		$options .= '</select> <a href="#" class="button" onclick="submit_selected_sections(); return false;"><span>'.i18n::s('Go').'</span></a> ';

		// all commands
		$menu = array();
		$menu[] = $options;

		// back to section
		$menu[] = Skin::build_link(Sections::get_permalink($item), i18n::s('Cancel'), 'span');

		// finalize the menu
		$text .= Skin::finalize_list($menu, 'menu_bar');

		// count the number of articles in this section
		$menu = array();
		if($count = Sections::count_for_anchor('section:'.$item['id'])) {
			if($count > min(5, $items_per_page))
				$menu = array_merge($menu, array('_count' => sprintf(i18n::ns('%d section', '%d sections', $count), $count)));

			// navigation commands for sections
			$home = Sections::get_url($item['id'], 'manage');
			$prefix = Sections::get_url($item['id'], 'manage', 'sections');
			$menu = array_merge($menu, Skin::navigate($home, $prefix, $count, $items_per_page, $zoom_index));

		}
		if(count($menu))
			$text .= Skin::build_list($menu, 'menu_bar');

	}

	// display in a separate panel
	if(trim($text))
		$panels[] = array('sections', i18n::s('Sections'), 'sections_panel', $text);

	// assemble all tabs
	//
	if(count($panels))
		$context['text'] .= Skin::build_tabs($panels);
	else
		$context['text'] .= '<p>'.i18n::s('This section has no content to manage.').'</p>';

	// the target section
	$context['text'] .= '<input type="hidden" name="id" value="'.encode_field($item['id']).'" />';

	// end of the form
	$context['text'] .= '</div></form>';

}

// render the skin
render_skin();

?>
