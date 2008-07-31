<?php
/**
 * display one image in situation
 *
 * Instructions are provided to save or to print the image in a sidebar.
 *
 * If several images have been posted to a single anchor, a navigation bar will be built to jump
 * directly to previous and next neighbours. The slide-show of the poor man...
 * [deleted]This is displayed as a sidebar box in the extra panel.[/deleted]
 *
 * The extra panel also features top popular referrals in a sidebar box, if applicable.
 *
 * Several HTTP headers, or &lt;meta&gt; attributes of the displayed page, are set dynamically here
 * to help advanced web usage. This includes:
 * - a link to the next image, if neighbours have been defined, enabling pref-fetching
 *
 * @link http://www.mozilla.org/projects/netlib/Link_Prefetching_FAQ.html Link Prefetching FAQ
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
 * @tester Pafois
 * @tester Lasares
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'images.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Images::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor = Anchors::get($item['anchor']);

// the anchor has to be viewable by this surfer
if(!is_object($anchor) || $anchor->is_viewable())
	$permitted = TRUE;
else
	$permitted = FALSE;

// nothing to change
if(!isset($item['id']))
	$editable = FALSE;

// associates and editors are allowed to change the file
elseif(Surfer::is_associate() || (is_object($anchor) && $anchor->is_editable()))
	$editable = TRUE;

// the original poster can change the file as well
elseif(Surfer::is($item['edit_id']))
	$editable = TRUE;

// authenticated members are allowed to modify files from others
elseif(Surfer::is_member() && (!isset($context['users_without_file_overloads']) || ($context['users_without_file_overloads'] != 'Y')))
	$editable = TRUE;

// the default is to disable change commands
else
	$editable = FALSE;

// load the skin, maybe with a variant
load_skin('images', $anchor);

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'images/' => i18n::s('Images') );

// the title of the page
if($item['title'])
	$context['page_title'] = $item['title'];
else
	$context['page_title'] = $item['image_name'];

// back to the anchor page
if(is_object($anchor) && $anchor->is_viewable())
	$context['page_menu'] = array_merge($context['page_menu'], array( $anchor->get_url() => i18n::s('Back to main page') ));

// the edit command is available to associates, editors, and poster
if($item['id'] && (Surfer::is_associate()
	|| (is_object($anchor) && $anchor->is_editable())
	|| Surfer::is($item['edit_id']))) {
	$context['page_menu'] = array_merge($context['page_menu'], array( Images::get_url($item['id'], 'edit') => i18n::s('Update') ));
}

// the delete command is available to associates and editors
if($item['id'] && (Surfer::is_associate() || (is_object($anchor) && $anchor->is_editable())))
	$context['page_menu'] = array_merge($context['page_menu'], array( Images::get_url($item['id'], 'delete') => i18n::s('Delete') ));

// not found -- help web crawlers
if(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Images::get_url($item['id'])));

	// permission denied to authenticated user
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// display the image full size
} else {

	// initialize the rendering engine
	Codes::initialize(Images::get_url($item['id']));

	// page details
	//
	$context['page_details'] .= '<p class="details">';

	// display the source, if any
	if($item['source']) {
		if(preg_match('/http:\/\/([^\s]+)/', $item['source'], $matches))
			$item['source'] = Skin::build_link($matches[0], $matches[0], 'external');
		else {
			include_once '../links/links.php';
			if($attributes = Links::transform_reference($item['source'])) {
				list($link, $title, $description) = $attributes;
				$item['source'] = Skin::build_link($link, $title);
			}
		}
		$context['page_details'] .= sprintf(i18n::s('Source: %s'), $item['source']).BR;
	}

	// other details
	$details = array();

	// the file name, if it has not already been used as title
	if(Surfer::is_associate() &&$item['title'])
		$details[] = $item['image_name'];

	// image size
	if(Surfer::is_associate() && ($item['image_size'] > 1))
		$details[] = sprintf(i18n::s('%d bytes'), $item['image_size']);

	// information on uploader
	if(Surfer::is_logged() && $item['edit_name'])
		$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

	// all details
	if(count($details))
		$context['page_details'] .= ucfirst(implode(', ', $details));

	$context['page_details'] .= "</p>\n";

	// page main content
	//

	// insert anchor prefix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_prefix();

	// retrieve slideshow links if we have an anchor
	if(is_object($anchor)) {

		// retrieve information from cache, if any
		$cache_id = 'images/view.php?id='.$item['id'].'#navigation';
		if($data =& Cache::get($cache_id))
			$data = Safe::unserialize($data);

		// build information from the database
		else {
			$data = $anchor->get_neighbours('image', $item);

			// save in cache
			$text = serialize($data);
			Cache::put($cache_id, $text, 'images');
		}

		// links to display previous and next pages, if any
		$context['text'] .= Skin::neighbours($data, 'slideshow');

		// a meta link to prefetch the next page
		if(isset($data[2]) && $data[2])
			$context['page_header'] .= "\n".'<link rel="next" href="'.$context['url_to_root'].$data[2].'" title="'.encode_field($data[3]).'"'.EOT;

	}

	// image description
	$context['text'] .= Skin::build_block($item['description'], 'description');

	// build the path to the image file
	$url = $context['url_to_root'].'images/'.$context['virtual_path'].str_replace(':', '/', $item['anchor']).'/'.$item['image_name'];
	$img = '<img src="'.$url.'" alt="'.$item['image_name'].'"'.EOT;

	// add an url, if any
	if($item['link_url']) {

		// transform local references, if any
		include_once $context['path_to_root'].'/links/links.php';
		$attributes = Links::transform_reference($item['link_url']);
		if($attributes[0])
			$link = $attributes[0];

		// direct use of this link
		else
			$link = $item['link_url'];

		// make a clickable image
		$img = Skin::build_link($link, $img, 'basic');
	}

	// display everything
	$context['text'] .= "\n<p>".$img.'</p>';

	// insert anchor suffix
	if(is_object($anchor))
		$context['text'] .= $anchor->get_suffix();

	// page tools
	//
	if($editable)
		$context['page_tools'][] = Skin::build_link(Images::get_url($item['id'], 'edit'), i18n::s('Update'), 'basic');

	// general help on this page
	//
	$help = '<p>'.i18n::s('To save this image on your hard drive, drag the mouse above the image and use the right button. A contextual pop-up menu should appear. Select the adequate command depending on the browser used.').'</p>';
	$context['extra'] .= Skin::build_box(i18n::s('Help'), $help, 'navigation', 'help');

	// thumbnail, in an extra box
	//
	if(Surfer::is_associate() && $item['thumbnail_name'] && ($item['thumbnail_name'] != $item['image_name'])) {
		$url = $context['url_to_root'].'images/'.$context['virtual_path'].str_replace(':', '/', $item['anchor']).'/'.$item['thumbnail_name'];
		$context['extra'] .= Skin::build_box(i18n::s('Thumbnail'), '<img src="'.$url.'" />', 'extra');
	}

	//
	// referrals, if any
	//
	if(Surfer::is_associate() || (isset($context['with_referrals']) && ($context['with_referrals'] == 'Y'))) {

		$cache_id = 'images/view.php?id='.$item['id'].'#referrals';
		if(!$text =& Cache::get($cache_id)) {

			// box content in a sidebar box
			include_once '../agents/referrals.php';
			if($text = Referrals::list_by_hits_for_url($context['url_to_root_parameter'].Images::get_url($item['id'])))
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