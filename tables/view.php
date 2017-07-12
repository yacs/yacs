<?php
/**
 * display one table in situation
 *
 * Offer commands to download the table in Excel, or using the XML format.
 *
 * The extra panel has following elements:
 * - The top popular referrals, if any
 *
 * Access is granted only if the surfer is allowed to view the anchor page.
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
include_once 'tables.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// encode ISO-8859-1 argument, if any
if(isset($_SERVER['HTTP_ACCEPT_CHARSET']) && preg_match('/^iso-8859-1/i', $_SERVER['HTTP_ACCEPT_CHARSET']))
	$id = utf8_encode($id);

// get the item from the database
$item = Tables::get($id);

// get the related anchor
$anchor = NULL;
if(isset($item['anchor']))
	$anchor = Anchors::get($item['anchor']);

// the anchor has to be viewable by this surfer
if(!is_object($anchor) || $anchor->is_viewable())
	$permitted = TRUE;
else
	$permitted = FALSE;

// load the skin, maybe with a variant
load_skin('tables', $anchor);

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// current item
if(isset($item['id']))
	$context['current_item'] = 'table:'.$item['id'];

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'tables/index.php' => i18n::s('Tables') );

// the title of the page
if(isset($item['title']) && $item['title'])
	$context['page_title'] = $item['title'];
elseif(isset($item['table_name']) && $item['table_name'])
	$context['page_title'] = $item['table_name'];
elseif(is_object($anchor) && $anchor->is_viewable())
	$context['page_title'] = $anchor->get_title();
else
	$context['page_title'] = i18n::s('View a table');

// not found -- help web crawlers
if(!isset($item['id'])) {
	include '../error.php';

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Tables::get_url($item['id'])));

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// re-enforce the canonical link
} elseif($context['self_url'] && ($canonical = $context['url_to_home'].$context['url_to_root'].Tables::get_url($item['id'])) && strncmp($context['self_url'], $canonical, strlen($canonical))) {
	Safe::header('Status: 301 Moved Permanently', TRUE, 301);
	Safe::header('Location: '.$canonical);
	Logger::error(Skin::build_link($canonical));

// display the table
} else {

	// initialize the rendering engine
	Codes::initialize(Tables::get_url($item['id']));

	// insert anchor prefix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_prefix();

	// display the full text
	$context['text'] .= Skin::build_block($item['description'], 'description');

	// execute the query string to build the table
	if(isset($item['query']) && $item['query'])
		$context['text'] .= Tables::build($item['id'], 'sortable');

	// display the query string to associates and editors
	if(isset($item['query']) && $item['query'] && (Surfer::is_associate() || (is_object($anchor) && $anchor->is_assigned())))
		$context['text'] .= Skin::build_box(i18n::s('Query string'), Skin::build_block(encode_field($item['query']), 'code'), 'folded');

	// add some details
	$details = array();

	// information on poster
	if(Surfer::is_member())
		$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']),Skin::build_date($item['edit_date']));

	// page details
	if(count($details))
		$context['text'] .= '<p '.tag::_class('details').'>'.ucfirst(implode(', ', $details))."</p>\n";

	// insert anchor suffix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_suffix();

	// back to the anchor page
	if(is_object($anchor) && $anchor->is_viewable()) {
		$menu = array(Skin::build_link($anchor->get_url(), i18n::s('Back to main page'), 'button'));
		$context['text'] .= Skin::build_block(Skin::finalize_list($menu, 'menu_bar'), 'bottom');
	}

	// page tools
	//
	$context['page_tools'][] = Skin::build_link(Tables::get_url($id, 'fetch_as_csv'), i18n::s('CSV (Excel)'));
	$context['page_tools'][] = Skin::build_link(Tables::get_url($id, 'fetch_as_json'), i18n::s('JSON'));
	$context['page_tools'][] = Skin::build_link(Tables::get_url($id, 'fetch_as_xml'), i18n::s('XML'));
	if(Surfer::is_associate() || (is_object($anchor) && $anchor->is_assigned())) {
		$context['page_tools'][] = Skin::build_link(Tables::get_url($id, 'edit'), i18n::s('Edit'), 'basic', i18n::s('Press [e] to edit'), FALSE, 'e');
		$context['page_tools'][] = Skin::build_link(Tables::get_url($id, 'delete'), i18n::s('Delete'));
	}

	// referrals, if any
	$context['components']['referrals'] = Skin::build_referrals(Tables::get_url($item['id']));

}

// render the skin
render_skin();

?>
