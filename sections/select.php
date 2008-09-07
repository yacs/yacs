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
 * Only associates can use this script. This means that editors cannot delegate their power to someone else.
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
	$context['path_bar'] = array( 'sections/' => i18n::s('Sections') );

// the title of the page
if(is_object($anchor))
	$context['page_title'] = sprintf(i18n::s('Sections for %s'), $anchor->get_title());

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// an anchor is mandatory
} elseif(!is_object($anchor)) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No anchor has been found.'));

// surfer has to be an associate
} elseif(!Surfer::is_associate()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

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

	// back to the anchor page
	$context['page_menu'] = array($anchor->get_url() => i18n::s('Back to main page'));

	// insert anchor prefix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_prefix();

	// splash
	$context['text'] .= '<p>'.i18n::s('Use this page to select or to deselect some sections.').'</p>';

	// the current list of linked sections
	if(($sections =& Members::list_sections_by_title_for_anchor($anchor->get_reference(), 0, SECTIONS_LIST_SIZE, 'raw')) && count($sections)) {

		// flag sections updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// browse the list
		foreach($sections as $id => $section) {

			// make an url
			$url = Sections::get_permalink($section);

			// gather information on this section
			$prefix = $suffix = $type = $icon = '';

			// flag sections that are draft, dead, or created or updated very recently
			if($section['activation_date'] >= $now)
				$prefix .= DRAFT_FLAG;
			elseif(($section['expiry_date'] > NULL_DATE) && ($section['expiry_date'] <= $now))
				$prefix .= EXPIRED_FLAG;
			elseif($section['create_date'] >= $dead_line)
				$suffix .= NEW_FLAG;
			elseif($section['edit_date'] >= $dead_line)
				$suffix .= UPDATED_FLAG;

			// signal restricted and private sections
			if($section['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($section['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// the introductory text
			if($section['introduction'])
				$suffix .= ' -&nbsp;'.Codes::beautify($section['introduction'], $section['options']);

			// use the title to label the link
			$label = Codes::beautify_title($section['title']);

			// the icon to put in the left column
			if($section['thumbnail_url'])
				$icon = $section['thumbnail_url'];

			// build a unlink button for this section
			if(Surfer::is_associate()) {
				$suffix .= BR.'<form method="post" action="'.$context['script_url'].'"><div>'
					.'<input type="hidden" name="anchor" value="'.encode_field($anchor->get_reference()).'">'
					.'<input type="hidden" name="member" value="section:'.$section['id'].'">'
					.'<input type="hidden" name="action" value="reset">'
					.Skin::build_submit_button(i18n::s('Deselect'))
					.'</div></form>';
			}

			// list sub-sections to be linked, if any
			// display active and restricted items
			$where = "sections.active='Y'";
			if(Surfer::is_member())
				$where .= " OR sections.active='R'";
			if(Surfer::is_associate())
				$where .= " OR sections.active='N'";

			// only consider live sections
			$where = '('.$where.') '
				.' AND ((sections.expiry_date is NULL)'
				."	OR (sections.expiry_date <= '".NULL_DATE."') OR (sections.expiry_date > '".$now."'))";

			// limit the query to top level only
			$query = "SELECT sections.id, sections.title "
				." FROM ".SQL::table_name('sections')." AS sections "
				." WHERE (".$where.") AND (sections.anchor='section:".$section['id']."')"
				." ORDER BY sections.title";
			$result =& SQL::query($query);
			$sub_sections = array();
			while($result && ($option =& SQL::fetch($result)))
				$sub_sections['section:'.$option['id']] = $option['title'];

			// format the item
			$new_sections[$url] = array($prefix, $label, $suffix, $type, $icon);

		}

		// display attached sections with unlink buttons
		$context['text'] .= Skin::build_list($new_sections, 'decorated');

	}

	// the form to link additional sections
	if(!is_array($sections) || (count($sections) < SECTIONS_LIST_SIZE)) {
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'"><p>'
			.i18n::s('Select another section:').' <select name="member">'.Sections::get_options($sections).'</select>'
			.' '.Skin::build_submit_button(' >> ')
			.'<input type="hidden" name="anchor" value="'.encode_field($anchor->get_reference()).'">'
			.'<input type="hidden" name="action" value="set">'
			.'</p></form>'."\n";
	}

	// insert anchor suffix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_suffix();

}

// render the skin
render_skin();

?>