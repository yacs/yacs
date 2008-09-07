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
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once '../categories/categories.php';	// to categorize a set of pages

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

// get the item from the database
$item =& Sections::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor =& Anchors::get($item['anchor']);

// which action?
$action = NULL;
if(isset($_REQUEST['action']) && $_REQUEST['action'])
	$action = $_REQUEST['action'];
$action = strip_tags($action);

// editors of upper containers have associate-like capabilities
if(Surfer::is_member() && is_object($anchor) && $anchor->is_editable())
	Surfer::empower();

// editors can do what they want on items anchored here
elseif(Surfer::is_member() && isset($item['id']) && Sections::is_assigned($item['id']))
	Surfer::empower();

// associates are always authorized
if(Surfer::is_associate())
	$permitted = TRUE;

// editors are also authorized
elseif(Surfer::is_empowered())
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load the skin, maybe with a variant
load_skin('sections', $anchor, isset($item['options']) ? $item['options'] : '');

// list selected pages
$selected_articles = '';
if(isset($_REQUEST['articles']) && ($count = @count($_REQUEST['articles']))) {

	// if more than 10 pages, list the first five, else display all
	if($count >= 10)
		$bucket = 5;
	else
		$bucket = $count;

	$items = array();
	foreach($_REQUEST['articles'] as $dummy => $id) {
		if($article = Articles::get($id)) {
			$items[] = Skin::build_link(Articles::get_permalink($article), $article['title'], 'article');
			$selected_articles .= '<input type="hidden" name="articles[]" value="'.$article['id'].'" />';
		}
		if($bucket-- <= 0)
			break;
	}

	// a hint on non-listed pages
	if($delta = $count - count($items))
		$items[] = sprintf(i18n::ns('%d other page has been selected', '%d other pages have been selected', $delta), $delta);

	// a limited compact list
	$selected_articles .= Skin::finalize_list($items, 'compact');

}

// list selected sections
$selected_sections = '';
if(isset($_REQUEST['sections']) && ($count = @count($_REQUEST['sections']))) {

	// if more than 10 pages, list the first five, else display all
	if($count >= 10)
		$bucket = 5;
	else
		$bucket = $count;

	$items = array();
	foreach($_REQUEST['sections'] as $dummy => $id) {
		if($section = Sections::get($id)) {
			$items[] = Skin::build_link(Sections::get_permalink($section), $section['title'], 'article');
			$selected_sections .= '<input type="hidden" name="sections[]" value="'.$section['id'].'" />';
		}
		if($bucket-- <= 0)
			break;
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
	$context['path_bar'] = array( 'sections/' => i18n::s('Sections') );
if(isset($item['id']))
	$context['path_bar'] = array_merge($context['path_bar'], array(Sections::get_permalink($item) => $item['title']));

// pag etitle
if(isset($item['title']))
	$context['page_title'] = sprintf(i18n::s('Manage: %s'), $item['title']);
else
	$context['page_title'] = i18n::s('Manage');

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// not found
} elseif(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No item has the provided id.'));

// access denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Sections::get_url($item['id'], 'manage')));

	// permission denied to authenticated user
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// associate selected pages
} elseif($action == 'associate_articles') {

	// articles
	if(isset($_REQUEST['articles'])) {

		// actually a form
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
			.'<input type="hidden" name="action" value="associate_confirmed" />'."\n"
			.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n";

		// target section
		$context['text'] .= '<p>'.i18n::s('Associate pages below to').' <select name=associate_to>'.Categories::get_options().'</select></p>';

		// selected pages
		$context['text'] .= $selected_articles;

		// follow-up commands
		$menu = array();
		$menu[] = Skin::build_submit_button(i18n::s('Associate'));
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), 'Cancel', 'span');
		$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

		$context['text'] .= '</form>';

	// nothing to do
	} else
		Skin::error(i18n::s('No page has been selected.'));

// associate selected pages
} elseif($action == 'associate_sections') {

	// sections
	if(isset($_REQUEST['sections'])) {
		// actually a form
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
			.'<input type="hidden" name="action" value="associate_confirmed" />'."\n"
			.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n";

		// target section
		$context['text'] .= '<p>'.i18n::s('Associate pages below to').' <select name=associate_to>'.Categories::get_options().'</select></p>';

		// selected pages
		$context['text'] .= $selected_sections;

		// follow-up commands
		$menu = array();
		$menu[] = Skin::build_submit_button(i18n::s('Associate'));
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), 'Cancel', 'span');
		$context['text'] .= Skin::finalize_list($menu, 'assistant_bar');

		$context['text'] .= '</form>';

	// nothing to do
	} else
		Skin::error(i18n::s('No section has been selected.'));

// actual association
} elseif($action == 'associate_confirmed') {

	// nothing to do
	if(!isset($_REQUEST['associate_to']) || (!$destination =& Anchors::get($_REQUEST['associate_to'])))
		Skin::error(i18n::s('Bad request.'));

	// articles
	elseif(isset($_REQUEST['articles'])) {

		$count = 0;
		foreach($_REQUEST['articles'] as $dummy => $id) {
			if(!$error = Members::assign($_REQUEST['associate_to'], 'article:'.$id))
				$count++;
		}

		// report on results
		$context['text'] .= '<p>'.sprintf(i18n::ns('%d page has been associated with %s.', '%d pages have been associated with %s.', $count),
			$count, Skin::build_link($destination->get_url(), $destination->get_title(), 'category')).'</p>';

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu[] = Skin::build_link(Sections::get_permalink($item), 'View the section', 'span');
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), 'Manage it', 'span');
		$follow_up .= Skin::finalize_list($menu, 'page_menu');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	// sections
	} elseif(isset($_REQUEST['sections'])) {

		$count = 0;
		foreach($_REQUEST['sections'] as $dummy => $id) {
			if(!$error = Members::assign($_REQUEST['associate_to'], 'section:'.$id))
				$count++;
		}

		// report on results
		$context['text'] .= '<p>'.sprintf(i18n::ns('%d page has been associated with %s.', '%d pages have been associated with %s.', $count),
			$count, Skin::build_link($destination->get_url(), $destination->get_title(), 'category')).'</p>';

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu[] = Skin::build_link(Sections::get_permalink($item), 'View the section', 'span');
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), 'Manage it', 'span');
		$follow_up .= Skin::finalize_list($menu, 'page_menu');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	// nothing to do
	} else
		Skin::error(i18n::s('No page has been selected.'));

// delete selected pages
} elseif($action == 'delete_articles') {

	// articles
	if(isset($_REQUEST['articles'])) {

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
		Skin::error(i18n::s('No page has been selected.'));

// delete selected pages
} elseif($action == 'delete_sections') {

	// sections
	if(isset($_REQUEST['sections'])) {

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
		Skin::error(i18n::s('No section has been selected.'));

// actual deletion
} elseif($action == 'delete_confirmed') {

	// articles
	if(isset($_REQUEST['articles'])) {

		$count = 0;
		foreach($_REQUEST['articles'] as $dummy => $id) {
			if(Articles::delete($id))
				$count++;
		}

		// report on results
		$context['text'] .= '<p>'.sprintf(i18n::ns('%d page has been deleted.', '%d pages have been deleted.', $count), $count).'</p>';

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu[] = Skin::build_link(Sections::get_permalink($item), 'View the section', 'span');
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), 'Manage it', 'span');
		$follow_up .= Skin::finalize_list($menu, 'page_menu');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	// sections
	} elseif(isset($_REQUEST['sections'])) {

		$count = 0;
		foreach($_REQUEST['sections'] as $dummy => $id) {
			if(Sections::delete($id))
				$count++;
		}

		// report on results
		$context['text'] .= '<p>'.sprintf(i18n::ns('%d page has been deleted.', '%d pages have been deleted.', $count), $count).'</p>';

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu[] = Skin::build_link(Sections::get_permalink($item), 'View the section', 'span');
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), 'Manage it', 'span');
		$follow_up .= Skin::finalize_list($menu, 'page_menu');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	// nothing to do
	} else
		Skin::error(i18n::s('No page has been selected.'));

// draft pages
} elseif($action == 'draft_articles') {

	// articles
	if(isset($_REQUEST['articles'])) {

		$count = 0;
		foreach($_REQUEST['articles'] as $dummy => $id) {

			// the article to de-publish
			if(($article =& Articles::get($id)) && ($article['publish_date'] > NULL_DATE)) {

				$attributes = array();
				$attributes['id'] = $article['id'];
				$attributes['publish_date'] = NULL_DATE;
				if(Articles::put_attributes($attributes))
					$count++;

			}
		}

		// report on results
		$context['text'] .= '<p>'.sprintf(i18n::ns('%d page has been changed to draft mode.', '%d pages have been changed to draft mode.', $count), $count).'</p>';

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu[] = Skin::build_link(Sections::get_permalink($item), 'View the section', 'span');
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), 'Manage it', 'span');
		$follow_up .= Skin::finalize_list($menu, 'page_menu');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	// nothing to do
	} else
		Skin::error(i18n::s('No page has been selected.'));

// duplicate selected pages
} elseif($action == 'duplicate_articles') {

	// articles
	if(isset($_REQUEST['articles'])) {

		// actually a form
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
			.'<input type="hidden" name="action" value="duplicate_confirmed" />'."\n"
			.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n";

		// target section
		$context['text'] .= '<p>'.i18n::s('Duplicate following pages in').' <select name=duplicate_to>'.Sections::get_options('section:'.$item['id']).'</select></p>';

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
		Skin::error(i18n::s('No page has been selected.'));

// duplicate selected pages
} elseif($action == 'duplicate_sections') {

	// sections
	if(isset($_REQUEST['sections'])) {

		// actually a form
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
			.'<input type="hidden" name="action" value="duplicate_confirmed" />'."\n"
			.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n";

		// target section
		$context['text'] .= '<p>'.i18n::s('Duplicate following pages in').' <select name=duplicate_to>'.Sections::get_options('section:'.$item['id']).'</select></p>';

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
		Skin::error(i18n::s('No section has been selected.'));

// actual duplication
} elseif($action == 'duplicate_confirmed') {

	// nothing to do
	if(!isset($_REQUEST['duplicate_to']) || (!$destination =& Anchors::get($_REQUEST['duplicate_to'])))
		Skin::error(i18n::s('Bad request.'));

	// articles
	elseif(isset($_REQUEST['articles'])) {

		$count = 0;
		foreach($_REQUEST['articles'] as $dummy => $id) {

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

				// change the handle
				unset($article['handle']);

				// ensure this is a copy
				$article['title'] = sprintf(i18n::s('Copy of %s'), $article['title']);

				// target anchor
				$article['anchor'] = $_REQUEST['duplicate_to'];

				// actual duplication
				if($new_id = Articles::post($article)) {

					// duplicate elements related to this item
					Anchors::duplicate_related_to('article:'.$old_id, 'article:'.$new_id);

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
		$menu[] = Skin::build_link(Sections::get_permalink($item), 'View the section', 'span');
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), 'Manage it', 'span');
		$follow_up .= Skin::finalize_list($menu, 'page_menu');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	// sections
	} elseif(isset($_REQUEST['sections'])) {

		$count = 0;
		foreach($_REQUEST['sections'] as $dummy => $id) {

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
				if($new_id = Sections::post($section)) {

					// duplicate elements related to this item
					Anchors::duplicate_related_to('section:'.$old_id, 'section:'.$new_id);

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
		$menu[] = Skin::build_link(Sections::get_permalink($item), 'View the section', 'span');
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), 'Manage it', 'span');
		$follow_up .= Skin::finalize_list($menu, 'page_menu');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	// nothing to do
	} else
		Skin::error(i18n::s('No page has been selected.'));

// lock pages
} elseif($action == 'lock_articles') {

	// articles
	if(isset($_REQUEST['articles'])) {

		$count = 0;
		foreach($_REQUEST['articles'] as $dummy => $id) {

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

		// report on results
		$context['text'] .= '<p>'.sprintf(i18n::ns('%d page has been locked.', '%d pages have been locked.', $count), $count).'</p>';

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu[] = Skin::build_link(Sections::get_permalink($item), 'View the section', 'span');
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), 'Manage it', 'span');
		$follow_up .= Skin::finalize_list($menu, 'page_menu');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	// nothing to do
	} else
		Skin::error(i18n::s('No page has been selected.'));

// lock pages
} elseif($action == 'lock_sections') {

	// sections
	if(isset($_REQUEST['sections'])) {

		$count = 0;
		foreach($_REQUEST['sections'] as $dummy => $id) {

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

		// report on results
		$context['text'] .= '<p>'.sprintf(i18n::ns('%d page has been locked.', '%d pages have been locked.', $count), $count).'</p>';

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu[] = Skin::build_link(Sections::get_permalink($item), 'View the section', 'span');
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), 'Manage it', 'span');
		$follow_up .= Skin::finalize_list($menu, 'page_menu');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	// nothing to do
	} else
		Skin::error(i18n::s('No section has been selected.'));

// move selected pages
} elseif($action == 'move_articles') {

	// articles
	if(isset($_REQUEST['articles'])) {

		// actually a form
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
			.'<input type="hidden" name="action" value="move_confirmed" />'."\n"
			.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n";

		// target section
		$context['text'] .= '<p>'.i18n::s('Move following pages to').' <select name=move_to>'.Sections::get_options('section:'.$item['id']).'</select></p>';

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
		Skin::error(i18n::s('No page has been selected.'));

// move selected pages
} elseif($action == 'move_sections') {

	// sections
	if(isset($_REQUEST['sections'])) {

		// actually a form
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
			.'<input type="hidden" name="action" value="move_confirmed" />'."\n"
			.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n";

		// target section
		$context['text'] .= '<p>'.i18n::s('Move following pages to').' <select name=move_to>'.Sections::get_options('section:'.$item['id']).'</select></p>';

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
		Skin::error(i18n::s('No page has been selected.'));

// actual move
} elseif($action == 'move_confirmed') {

	// nothing to do
	if(!isset($_REQUEST['move_to']) || (!$destination =& Anchors::get($_REQUEST['move_to'])))
		Skin::error(i18n::s('Bad request.'));

	// articles
	elseif(isset($_REQUEST['articles'])) {

		$count = 0;
		foreach($_REQUEST['articles'] as $dummy => $id) {
			$attributes = array();
			$attributes['id'] = $id;
			$attributes['anchor'] = $_REQUEST['move_to'];
			if(Articles::put_attributes($attributes))
				$count++;
		}

		// clear cache for origin container
		Sections::clear($item);

		// report on results
		$context['text'] .= '<p>'.sprintf(i18n::ns('%d page has been moved to %s.', '%d pages have been moved to %s.', $count),
			$count, Skin::build_link($destination->get_url(), $destination->get_title(), 'section')).'</p>';

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu[] = Skin::build_link(Sections::get_permalink($item), 'View the section', 'span');
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), 'Manage it', 'span');
		$follow_up .= Skin::finalize_list($menu, 'page_menu');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	// sections
	} elseif(isset($_REQUEST['sections'])) {

		$count = 0;
		foreach($_REQUEST['sections'] as $dummy => $id) {
			$attributes = array();
			$attributes['id'] = $id;
			$attributes['anchor'] = $_REQUEST['move_to'];
			if(Sections::put_attributes($attributes))
				$count++;
		}

		// clear cache for origin container
		Sections::clear($item);

		// report on results
		$context['text'] .= '<p>'.sprintf(i18n::ns('%d page has been moved to %s.', '%d pages have been moved to %s.', $count),
			$count, Skin::build_link($destination->get_url(), $destination->get_title(), 'section')).'</p>';

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu[] = Skin::build_link(Sections::get_permalink($item), 'View the section', 'span');
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), 'Manage it', 'span');
		$follow_up .= Skin::finalize_list($menu, 'page_menu');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	// nothing to do
	} else
		Skin::error(i18n::s('No page has been selected.'));

// publish pages
} elseif($action == 'publish_articles') {

	// articles
	if(isset($_REQUEST['articles'])) {

		$count = 0;
		foreach($_REQUEST['articles'] as $dummy => $id) {

			// the article to publish
			if(($article =& Articles::get($id)) && ($article['publish_date'] <= NULL_DATE)) {

				if(!Articles::stamp($article['id'], gmstrftime('%Y-%m-%d %H:%M:%S'), ''))
					$count++;

			}
		}

		// report on results
		$context['text'] .= '<p>'.sprintf(i18n::ns('%d page has been published.', '%d pages have been published.', $count), $count).'</p>';

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu[] = Skin::build_link(Sections::get_permalink($item), 'View the section', 'span');
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), 'Manage it', 'span');
		$follow_up .= Skin::finalize_list($menu, 'page_menu');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	// nothing to do
	} else
		Skin::error(i18n::s('No page has been selected.'));

// update rankings
} elseif(($action == 'rank_articles') || ($action == 'rank_sections')) {

	// actual processing
	$count = 0;
	foreach($_REQUEST as $name => $value) {

		if(($action == 'rank_articles') && (strpos($name, 'article_rank_') === 0)) {
			$attributes = array();
			$attributes['id'] = intval(substr($name, 13));
			$attributes['rank'] = intval($value);
			if(Articles::put_attributes($attributes))
				$count++;
		}

		if(($action == 'rank_sections') && (strpos($name, 'section_rank_') === 0)) {
			$attributes = array();
			$attributes['id'] = intval(substr($name, 13));
			$attributes['rank'] = intval($value);
			if(Sections::put_attributes($attributes))
				$count++;
		}
	}

	// report on results
	$context['text'] .= '<p>'.sprintf(i18n::ns('%d page has been updated.', '%d pages have been updated.', $count), $count).'</p>';

	// follow-up commands
	$follow_up = i18n::s('What do you want to do now?');
	$menu = array();
	$menu[] = Skin::build_link(Sections::get_permalink($item), 'View the section', 'span');
	$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), 'Manage it', 'span');
	$follow_up .= Skin::finalize_list($menu, 'page_menu');
	$context['text'] .= Skin::build_block($follow_up, 'bottom');

// unlock pages
} elseif($action == 'unlock_articles') {

	// articles
	if(isset($_REQUEST['articles'])) {

		$count = 0;
		foreach($_REQUEST['articles'] as $dummy => $id) {

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

		// report on results
		$context['text'] .= '<p>'.sprintf(i18n::ns('%d page has been unlocked.', '%d pages have been unlocked.', $count), $count).'</p>';

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu[] = Skin::build_link(Sections::get_permalink($item), 'View the section', 'span');
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), 'Manage it', 'span');
		$follow_up .= Skin::finalize_list($menu, 'page_menu');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	// nothing to do
	} else
		Skin::error(i18n::s('No page has been selected.'));

// unlock pages
} elseif($action == 'unlock_sections') {

	// sections
	if(isset($_REQUEST['sections'])) {

		$count = 0;
		foreach($_REQUEST['sections'] as $dummy => $id) {

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

		// report on results
		$context['text'] .= '<p>'.sprintf(i18n::ns('%d page has been unlocked.', '%d pages have been unlocked.', $count), $count).'</p>';

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu[] = Skin::build_link(Sections::get_permalink($item), 'View the section', 'span');
		$menu[] = Skin::build_link(Sections::get_url($item['id'], 'manage'), 'Manage it', 'span');
		$follow_up .= Skin::finalize_list($menu, 'page_menu');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

	// nothing to do
	} else
		Skin::error(i18n::s('No section has been selected.'));

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
		$text .= '<script type="text/javascript">// <![CDATA['."\n"
			.'function count_selected_articles() {'."\n"
			.'	var count = 0;'."\n"
			.'	var checkers = $$("div#articles_panel input[type=\'checkbox\'].row_selector");'."\n"
			.'	for(var index=0; index < checkers.length; index++) {'."\n"
			.'		if(checkers[index].checked) {'."\n"
			.'			count++;'."\n"
			.'		}'."\n"
			.'	}'."\n"
			.'	return count;'."\n"
			.'}'."\n"
			."\n"
			.'function submit_selected_articles(action) {'."\n"
			.'	if(count_selected_articles() < 1) {'."\n"
			.'		alert("'.i18n::s('No page has been selected.').'");'."\n"
			.'	} else {'."\n"
			.'		$("action").value = action;'."\n"
			.'		$("main_form").submit();'."\n"
			.'	}'."\n"
			.'}'."\n"
			."\n"
			.'// ]]></script>'."\n";

		// all commands
		$menu = array();

		// categorize selected pages
		$menu[] = '<a href="#" class="button" onclick="submit_selected_articles(\'associate_articles\'); return false;"><span>'.i18n::s('Associate').'</span></a>';

		// publish selected pages
		$menu[] = '<a href="#" class="button" onclick="submit_selected_articles(\'publish_articles\'); return false;"><span>'.i18n::s('Publish').'</span></a>';

		// draft selected pages
		$menu[] = '<a href="#" class="button" onclick="submit_selected_articles(\'draft_articles\'); return false;"><span>'.i18n::s('Draft').'</span></a>';

		// lock selected pages
		$menu[] = '<a href="#" class="button" onclick="submit_selected_articles(\'lock_articles\'); return false;"><span>'.i18n::s('Lock').'</span></a>';

		// unlock selected pages
		$menu[] = '<a href="#" class="button" onclick="submit_selected_articles(\'unlock_articles\'); return false;"><span>'.i18n::s('Unlock').'</span></a>';

		// duplicate selected pages
		$menu[] = '<a href="#" class="button" onclick="submit_selected_articles(\'duplicate_articles\'); return false;"><span>'.i18n::s('Duplicate').'</span></a>';

		// move selected pages
		$menu[] = '<a href="#" class="button" onclick="submit_selected_articles(\'move_articles\'); return false;"><span>'.i18n::s('Move').'</span></a>';

		// delete selected pages
		$menu[] = '<a href="#" class="button" onclick="submit_selected_articles(\'delete_articles\'); return false;"><span>'.i18n::s('Delete').'</span></a>';

		// order pages
		$menu[] = '<a href="#" class="button" onclick="$(\'action\').value = \'rank_articles\'; $(\'main_form\').submit(); return false;"><span>'.i18n::s('Update rankings').'</span></a>';

		// back to section
		$menu[] = Skin::build_link(Sections::get_permalink($item), i18n::s('Cancel'), 'span');

		// finalize the menu
		$text .= BR.Skin::finalize_list($menu, 'page_menu');

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
		$panels[] = array('articles_tab', i18n::s('Pages'), 'articles_panel', $text);

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
		$text .= '<script type="text/javascript">// <![CDATA['."\n"
			.'function count_selected_sections() {'."\n"
			.'	var count = 0;'."\n"
			.'	var checkers = $$("div#sections_panel input[type=\'checkbox\'].row_selector");'."\n"
			.'	for(var index=0; index < checkers.length; index++) {'."\n"
			.'		if(checkers[index].checked) {'."\n"
			.'			count++;'."\n"
			.'		}'."\n"
			.'	}'."\n"
			.'	return count;'."\n"
			.'}'."\n"
			."\n"
			.'function submit_selected_sections(action) {'."\n"
			.'	if(count_selected_sections() < 1) {'."\n"
			.'		alert("'.i18n::s('No section has been selected.').'");'."\n"
			.'	} else {'."\n"
			.'		$("action").value = action;'."\n"
			.'		$("main_form").submit();'."\n"
			.'	}'."\n"
			.'}'."\n"
			."\n"
			.'// ]]></script>'."\n";

		// all commands
		$menu = array();

		// categorize selected pages
		$menu[] = '<a href="#" class="button" onclick="submit_selected_sections(\'associate_sections\'); return false;">'.i18n::s('Associate').'</a>';

		// lock selected pages
		$menu[] = '<a href="#" class="button" onclick="submit_selected_sections(\'lock_sections\'); return false;">'.i18n::s('Lock').'</a>';

		// unlock selected pages
		$menu[] = '<a href="#" class="button" onclick="submit_selected_sections(\'unlock_sections\'); return false;">'.i18n::s('Unlock').'</a>';

		// duplicate selected pages
//		$menu[] = '<a href="#" class="button" onclick="submit_selected_sections(\'duplicate_sections\'); return false;">'.i18n::s('Duplicate').'</a>';

		// move selected pages
		$menu[] = '<a href="#" class="button" onclick="submit_selected_sections(\'move_sections\'); return false;">'.i18n::s('Move').'</a>';

		// delete selected pages
		$menu[] = '<a href="#" class="button" onclick="submit_selected_sections(\'delete_sections\'); return false;">'.i18n::s('Delete').'</a>';

		// order pages
		$menu[] = '<a href="#" class="button" onclick="$(\'action\').value = \'rank_sections\'; $(\'main_form\').submit(); return false;">'.i18n::s('Update rankings').'</a>';

		// back to section
		$menu[] = Skin::build_link(Sections::get_permalink($item), i18n::s('Cancel'), 'span');

		// finalize the menu
		$text .= Skin::finalize_list($menu, 'page_menu');

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
		$panels[] = array('sections_tab', i18n::s('Sections'), 'sections_panel', $text);

	// assemble all tabs
	//
	if(count($panels))
		$context['text'] .= Skin::build_tabs($panels);
	else
		$context['text'] .= '<p>'.i18n::s('This section has no content to manage.').'</p>';

	// the target section
	$context['text'] .= '<input type="hidden" name="id" value="'.encode_field($item['id']).'" />';

	// the action to proceed
	$context['text'] .= '<input type="hidden" name="action" id="action" />';

	// end of the form
	$context['text'] .= '</div></form>';

}

// render the skin
render_skin();

?>