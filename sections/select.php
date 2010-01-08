<?php
/**
 * assign sections to any object
 *
 * This script displays assigned sections to an anchor, and list sections that could be assigned as well.
 *
 * This is the main tool used by associates to manage all sections assigned to one user at once.
 *
 * For any assigned section, the surfer:
 * - can unlink the section to the anchor
 * - can assign a sub-section, if any exists
 * - can reuse the section thumbnail in the article, if any
 *
 * Associates can use this script to assign editors across the content tree.
 *
 * Accept following invocations:
 * - select.php?anchor=user:12
 *
 * If this section, or one of its anchor, specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Agnes
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// find the target anchor in path args (e.g., http:.../sections/select.php?anchor=article:15)
$anchor = NULL;
if(isset($_REQUEST['anchor']))
	$anchor =& Anchors::get($_REQUEST['anchor']);

// load the skin, maybe with a variant
load_skin('sections', $anchor);

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'sections/' => i18n::s('Site map') );

// the title of the page
if(is_object($anchor) && $anchor->is_viewable())
	$context['page_title'] = sprintf(i18n::s('Sections of %s'), $anchor->get_title());

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// an anchor is mandatory
} elseif(!is_object($anchor)) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No anchor has been found.'));

// security screening
} elseif(!Surfer::is_associate()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// build a form to assign some sections to this item
} else {

	// assign a section, and add it to the watch list
	if(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'set') && isset($_REQUEST['member'])) {
		Members::assign($_REQUEST['anchor'], $_REQUEST['member']);
		if(preg_match('/^user:/', $_REQUEST['anchor']))
			Members::assign($_REQUEST['member'], $_REQUEST['anchor']);

	// break an assignment, and also purge the watch list
	} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'reset') && isset($_REQUEST['member'])) {
		Members::free($_REQUEST['anchor'], $_REQUEST['member']);
		if(preg_match('/^user:/', $_REQUEST['anchor']))
			Members::free($_REQUEST['member'], $_REQUEST['anchor']);

	}

	// insert anchor prefix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_prefix();

	// the current list of linked sections
	$sections =& Members::list_sections_by_title_for_anchor($anchor->get_reference(), 0, SECTIONS_LIST_SIZE, 'raw');

	// the form to link additional sections
	if(!is_array($sections) || (count($sections) < SECTIONS_LIST_SIZE)) {
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'"><p>'
			.i18n::s('To assign a section, look in the content tree below and assign one section at a time').BR.'<select name="member">'.Sections::get_options(NULL, $sections).'</select>'
			.' '.Skin::build_submit_button(' >> ')
			.'<input type="hidden" name="anchor" value="'.encode_field($anchor->get_reference()).'">'
			.'<input type="hidden" name="action" value="set">'
			.'</p></form>'."\n";
	}

	// splash
	$context['text'] .= '<p style="margin-top: 2em;">'.sprintf(i18n::s('This is the list of sections assigned to %s'), $anchor->get_title()).'</p>';

	// layout assigned sections
	if($sections) {

		// flag sections updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// browse the list
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'links/links.php';
		include_once $context['path_to_root'].'overlays/overlay.php';
		foreach($sections as $id => $section) {

			// get the related overlay, if any
			$overlay = Overlay::load($section);

			// get parent anchor
			$parent =& Anchors::get($section['anchor']);

			// the url to view this item
			$url =& Sections::get_permalink($section);

			// use the title to label the link
			if(is_object($overlay))
				$title = Codes::beautify_title($overlay->get_text('title', $section));
			else
				$title = Codes::beautify_title($section['title']);

			// initialize variables
			$prefix = $suffix = $icon = '';

			// flag sticky pages
			if($section['rank'] < 10000)
				$prefix .= STICKY_FLAG;

			// signal restricted and private sections
			if($section['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($section['active'] == 'R')
				$section .= RESTRICTED_FLAG;

			// flag sections that are dead, or created or updated very recently
			if(($section['expiry_date'] > NULL_DATE) && ($section['expiry_date'] <= $now))
				$prefix .= EXPIRED_FLAG;
			elseif($section['create_date'] >= $dead_line)
				$suffix .= NEW_FLAG;
			elseif($section['edit_date'] >= $dead_line)
				$suffix .= UPDATED_FLAG;

			// info on related comments
			if($count = Comments::count_for_anchor('section:'.$section['id'], TRUE))
				$suffix .= ' ('.$count.')';

			// details
			$details = array();

			// info on related sections
			if($count = Sections::count_for_anchor('section:'.$section['id']))
				$details[] = sprintf(i18n::ns('%d section', '%d sections', $count), $count);

			// info on related articles
			if($count = Articles::count_for_anchor('section:'.$section['id']))
				$details[] = sprintf(i18n::ns('%d page', '%d pages', $count), $count);

			// info on related files
			if($count = Files::count_for_anchor('section:'.$section['id'], TRUE))
				$details[] = sprintf(i18n::ns('%d file', '%d files', $count), $count);

			// info on related links
			if($count = Links::count_for_anchor('section:'.$section['id'], TRUE))
				$details[] = sprintf(i18n::ns('%d link', '%d links', $count), $count);

			// the parent link
			if(is_object($parent))
				$details[] = sprintf(i18n::s('in %s'), Skin::build_link($parent->get_url(), ucfirst($parent->get_title()), 'section'));

			// combine in-line details
			if(count($details))
				$suffix .= ' - <span class="details">'.trim(implode(', ', $details)).'</span>';

			// surfer cannot be deselected
			if(!strcmp($anchor->get_reference(), 'user:'.$section['owner_id']))
				$suffix .= ' - <span class="details">'.i18n::s('owner').'</span>';

			// build a unlink button for this section
			elseif(Surfer::is_associate()) {
				$link = $context['script_url'].'?anchor='.urlencode($anchor->get_reference()).'&amp;member=section:'.$section['id'].'&amp;action=reset';
				$suffix .= ' - <span class="details">'.Skin::build_link($link, i18n::s('unassign'), 'basic').'</span>';
			}

			// format the item
			$new_sections[$url] = array($prefix, $title, $suffix, 'section', $icon);

		}

		// display attached sections with unlink buttons
		$context['text'] .= Skin::build_list($new_sections, 'decorated');

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