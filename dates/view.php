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
	$anchor = Anchors::get($item['anchor']);

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
elseif(isset($item['id']) && $item['id'])
	$context['page_title'] = i18n::s('View some date');

// back to the anchor page
if(is_object($anchor))
	$context['page_menu'] = array_merge($context['page_menu'], array( $anchor->get_url() => i18n::s('Back to main page') ));

// commands for associates and editors
if(Surfer::is_associate() || (is_object($anchor) && $anchor->is_editable())) {
	$context['page_menu'] = array_merge($context['page_menu'], array( dates::get_url($id, 'edit') => i18n::s('Edit') ));
	$context['page_menu'] = array_merge($context['page_menu'], array( dates::get_url($id, 'delete') => i18n::s('Delete') ));

// commands for the author
} elseif(Surfer::is($item['edit_id'])) {
	$context['page_menu'] = array_merge($context['page_menu'],
		array( dates::get_url($item['id'], 'edit') => i18n::s('Edit') ));
}

// not found -- help web crawlers
if(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No item has the provided id.'));

// display the date full size
} else {

	// initialize the rendering engine
	Codes::initialize(dates::get_url($item['id']));

	// information on uploader
	if(Surfer::is_member() && $item['edit_name'])
		$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

	// page details
	if(is_array($details))
		$context['page_details'] .= '<p class="details">'.ucfirst(implode(', ', $details))."</p>\n";

	// insert anchor prefix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_prefix();

	// date
	if($item['date_stamp']) {
		$context['text'] .= '<p>'.sprintf(i18n::s( 'Target date: %s'), $item['date_stamp'])."</p>\n";
	}

	// insert anchor suffix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_suffix();

	//
	// populate the extra panel
	//

	//
	// the navigation sidebar
	//
	$cache_id = 'dates/view.php?id='.$item['id'].'#navigation';
	if(!$text =& Cache::get($cache_id)) {

		// buttons to display previous and next pages, if any
		if(is_object($anchor)) {
			$neighbours = $anchor->get_neighbours('date', $item);
			$text .= Skin::neighbours($neighbours, 'sidebar');
		}

		// build a nice sidebar box
		if($text)
			$text =& Skin::build_box(i18n::s('Navigation'), $text, 'navigation', 'neighbours');

		// save in cache
		Cache::put($cache_id, $text, 'dates');
	}
	$context['extra'] .= $text;

	//
	// the referrals, if any, in a sidebar
	//
	if(Surfer::is_associate() || (isset($context['with_referrals']) && ($context['with_referrals'] == 'Y'))) {

		$cache_id = 'dates/view.php?id='.$item['id'].'#referrals';
		if(!$text =& Cache::get($cache_id)) {

			// box content
			include_once '../agents/referrals.php';
			$text = Referrals::list_by_hits_for_url($context['url_to_root_parameter'].dates::get_url($item['id']));

			// in a sidebar box
			if($text)
				$text =& Skin::build_box(i18n::s('Referrals'), $text, 'navigation', 'referrals');

			// save in cache for one hour 60 * 60 = 3600
			Cache::put($cache_id, $text, 'referrals', 3600);

		}

		// in the extra panel
		$context['extra'] .= $text;
	}

}

// render the skin
render_skin();

?>