<?php
/**
 * display one date in situation
 *
 * If several dates have been posted to a single anchor, a navigation bar will be built to jump
 * directly to previous and next neighbours.
 * This is displayed as a sidebar box in the extra panel.
 *
 * The extra panel also features top popular referrals in a sidebar box, if applicable.
 *
 * Accept following invocations:
 * - view.php/12
 * - view.php?id=12
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
include_once 'dates.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Dates::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor =& Anchors::get($item['anchor']);

// load the skin, maybe with a variant
load_skin('dates', $anchor);

// the path to this page
if(is_object($anchor))
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'dates/' => i18n::s('Dates') );

// the title of the page
if(is_object($anchor))
	$context['page_title'] = $anchor->get_title();

// not found -- help web crawlers
if(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No item has the provided id.'));

// display the date full size
} else {

	// initialize the rendering engine
	Codes::initialize(Dates::get_url($item['id']));
	// insert anchor prefix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_prefix();

	// date
	if($item['date_stamp']) {
		$context['text'] .= '<p>'.sprintf(i18n::s('%s: %s'), i18n::s('Target date'), Skin::build_date($item['date_stamp'], 'full'))."</p>\n";
	}

	$details = array();

	// information on uploader
	if(Surfer::is_member() && $item['edit_name'])
		$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

	// page details
	if(is_array($details))
		$context['text'] .= '<p class="details">'.ucfirst(implode(', ', $details))."</p>\n";

	// insert anchor suffix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_suffix();

	// back to the anchor page
	if(is_object($anchor) && $anchor->is_viewable()) {
		$menu = array(Skin::build_link($anchor->get_url(), i18n::s('Back to main page'), 'button'));
		$context['text'] .= Skin::build_block(Skin::finalize_list($menu, 'menu_bar'), 'bottom');
	}

	//
	// populate the extra panel
	//

	// commands for associates and editors
	if(Surfer::is_associate() || (is_object($anchor) && $anchor->is_assigned())) {
		$context['page_tools'][] = Skin::build_link(Dates::get_url($id, 'edit'), i18n::s('Edit'));
		$context['page_tools'][] = Skin::build_link(Dates::get_url($id, 'delete'), i18n::s('Delete'));

	// commands for the author
	} elseif(Surfer::is($item['edit_id'])) {
		$context['page_tools'][] = Skin::build_link(Dates::get_url($id, 'edit'), i18n::s('Edit'));
	}

	// the navigation sidebar
	//
	// buttons to display previous and next pages, if any
	if(is_object($anchor)) {
		$neighbours = $anchor->get_neighbours('date', $item);
		$text .= Skin::neighbours($neighbours, 'sidebar');
	}

	// build a nice sidebar box
	if($text)
		$text =& Skin::build_box(i18n::s('Navigation'), $text, 'neighbours', 'neighbours');

	$context['components']['neighbours'] = $text;

	// the referrals, if any, in a sidebar
	//
	$context['components']['referrals'] =& Skin::build_referrals(Dates::get_url($item['id']));

}

// render the skin
render_skin();

?>