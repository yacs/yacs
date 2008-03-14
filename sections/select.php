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
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
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
	$anchor = Anchors::get($_REQUEST['anchor']);

// load localized strings
i18n::bind('sections');

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
else
	$context['page_title'] = i18n::s('Select sections for this anchor');

// an anchor is mandatory
if(!is_object($anchor))
	Skin::error(i18n::s('No anchor has been found.'));

// surfer has to be an associate
elseif(!Surfer::is_associate())
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// build a form to assign some sections to this item
else {

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
	$context['page_menu'] = array( $anchor->get_url() => sprintf(i18n::s('Back to %s'), $anchor->get_title()) );

	// insert anchor prefix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_prefix();

	// splash
	$context['text'] .= '<p>'.i18n::s('Use this page to select or to deselect some sections.').'</p>';

	// the current list of linked sections
	if(($sections = Members::list_sections_by_title_for_anchor($anchor->get_reference(), 0, SECTIONS_LIST_SIZE, 'references')) && count($sections)) {

		// browse the list
		foreach($sections as $reference => $section) {

			// extract the section id from the reference
			$section_id = str_replace('section:', '', $reference);

			// make an url
			$url = Sections::get_url($section_id);

			// gather information on this section
			$prefix = $suffix = $type = $icon = '';
			if(is_array($section)) {
				$prefix = $section[0];
				$suffix = $section[2];
				$type = $section[3];
				$icon = $section[4];
				$label = $section[1];
			}

			// build a unlink button for this section
			if(Surfer::is_associate()) {
				$suffix .= BR.'<form method="post" action="'.$context['script_url'].'"><div>'
					.'<input type="hidden" name="anchor" value="'.encode_field($anchor->get_reference()).'">'
					.'<input type="hidden" name="member" value="section:'.$section_id.'">'
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
			$now = gmstrftime('%Y-%m-%d %H:%M:%S');
			$where = '('.$where.') '
				.' AND ((sections.expiry_date is NULL)'
				."	OR (sections.expiry_date <= '".NULL_DATE."') OR (sections.expiry_date > '".$now."'))";

			// limit the query to top level only
			$query = "SELECT sections.id, sections.title "
				." FROM ".SQL::table_name('sections')." AS sections "
				." WHERE (".$where.") AND (sections.anchor='section:".$section_id."')"
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