<?php
/**
 * view one decision
 *
 * If several decisions have been posted to a single anchor, a navigation bar will be built to jump
 * directly to previous and next neighbours.
 * This is displayed as a sidebar box in the extra panel.
 *
 * Commands to post new decisions are added if the surfer has been authenticated has a valid member.
 *
 * The extra panel also features top popular referrals in a sidebar box, if applicable.
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
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'decisions.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Decisions::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor = Anchors::get($item['anchor']);

// the anchor has to be viewable by this surfer
if(!is_object($anchor) || $anchor->is_viewable())
	$permitted = TRUE;
else
	$permitted = FALSE;

// use a specific skin, if any
if(is_object($anchor) && ($skin = $anchor->has_option('skin')) && is_string($skin))
	$context['skin'] = 'skins/'.$skin;

// load the skin, maybe with a variant
load_skin('decisions', $anchor);

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'decisions/' => i18n::s('Decisions') );

// the title of the page
if(is_object($anchor))
	$context['page_title'] = sprintf(i18n::s('Decision: %s'), $anchor->get_title());
else
	$context['page_title'] = i18n::s('View a decision');

// back to the anchor page
if(is_object($anchor) && $anchor->is_viewable())
	$context['page_menu'] = array_merge($context['page_menu'], array( $anchor->get_url() => i18n::s('Main page') ));

// commands for associates and author, but not for editors
if($item['id'] && (Surfer::is_associate() || Surfer::is($item['create_id'])) )
	$context['page_menu'] = array_merge($context['page_menu'], array( decisions::get_url($item['id'], 'edit') => i18n::s('Edit') ));

// commands for associates
if($item['id'] && Surfer::is_associate())
	$context['page_menu'] = array_merge($context['page_menu'], array( decisions::get_url($item['id'], 'delete') => i18n::s('Delete') ));

// not found -- help web crawlers
if(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(decisions::get_url($item['id'])));

	// permission denied to authenticated user
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// display the decision
} else {

	// insert anchor  icon
	if(is_object($anchor))
		$context['page_image'] = $anchor->get_icon_url();

	// initialize the rendering engine
	Codes::initialize(Decisions::get_url($item['id']));

	// the last edition of this decision
	if($item['create_name'] != $item['edit_name'])
		$context['pag_details'] .= '<p class="detail">'.sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date'], 'with_hour')).'</p>';

	// insert anchor prefix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_prefix();

	// retrieve navigation links if we have an anchor
	if(is_object($anchor)) {

		// retrieve information from cache, if any
		$cache_id = 'decisions/view.php?id='.$item['id'].'#navigation';
		if($data =& Cache::get($cache_id))
			$data = unserialize($data);

		// build information from the database
		else {

			$data = $anchor->get_neighbours('decision', $item);

			// serialize data
			$text = serialize($data);

			// save in cache
			Cache::put($cache_id, $text, 'images');
		}

		// links to display previous and next pages, if any
		$context['text'] .= Skin::neighbours($data, 'slideshow');

		// a meta link to prefetch the next page
		if(isset($data[2]) && $data[2])
			$context['page_header'] .= "\n".'<link rel="next" href="'.$context['url_to_root'].$data[2].'" title="'.encode_field($data[3]).'"'.EOT;

	}

	// some details about this item
	$details = array();

	// the type
	if(is_object($anchor))
		$details[] = Decisions::get_img($item['type']);

	// the label
	switch($item['type']) {
	case 'no':
		$details[] = i18n::s('Rejected');
		break;
	case 'yes':
		$details[] = ' '.i18n::s('Approved');
		break;
	}

	// the poster of this decision
	$details[] = sprintf(i18n::s('by %s %s'), Users::get_link($item['create_name'], $item['create_address'], $item['create_id']), Skin::build_date($item['create_date'], 'with_hour'));

	// the complete details
	if($details)
		$context['text'] .= '<p>'.ucfirst(implode(' ', $details))."</p>\n";

	// display the full decision
	if($item['description']) {

		// beautify the complete decision
		$text = Codes::beautify($item['description']);

		// show the description
		$context['text'] .= '<p></p>'.$text."<p></p>\n";
	}

	// insert anchor suffix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_suffix();

	//
	// the referrals, if any, in a sidebar
	//
	if(Surfer::is_associate() || (isset($context['with_referrals']) && ($context['with_referrals'] == 'Y'))) {

		$cache_id = 'decisions/view.php?id='.$item['id'].'#referrals';
		if(!$text =& Cache::get($cache_id)) {

			// box content in a sidebar box
			include_once '../agents/referrals.php';
			if($text = Referrals::list_by_hits_for_url($context['url_to_root_parameter'].decisions::get_url($item['id'])))
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