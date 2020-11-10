<?php
/**
 * delete an image
 *
 * This script calls for confirmation, then actually deletes the image.
 * It updates the database, then redirects to the anchor page.
 *
 * Restrictions apply on this page:
 * - associates and authenticated editors are allowed to move forward
 * - permission is denied if the anchor is not viewable by this surfer
 * - permission is granted if the anchor is the profile of this member
 * - authenticated users may suppress their own posts
 * - else permission is denied
 *
 * Accept following invocations:
 * - delete.php/12
 * - delete.php?id=12
 *
 * If the anchor for this item specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Guillaume Perez
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

// case of strait ajax deletion
if(isset($_REQUEST['strait'])) {
    // ensure browser always look for fresh data
    http::expire(0);
    // auto-confirm
    $_REQUEST['confirm'] = 'yes';
    // prepare output
    $output = array('success' => false);
}

// get the item from the database
$item = Images::get($id);

// current item
if(isset($item['id']))
	$context['current_item'] = 'image:'.$item['id'];

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor = Anchors::get($item['anchor']);

// associates and authenticated editors can do what they want
if(Surfer::is_associate() || (Surfer::is_member() && is_object($anchor) && $anchor->is_assigned()))
	$permitted = TRUE;

// the anchor has to be viewable by this surfer
elseif(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;

// the item is anchored to the profile of this member
elseif(Surfer::is_member() && !strcmp($item['anchor'], 'user:'.Surfer::get_id()))
	$permitted = TRUE;

// authenticated surfers may suppress their own posts --no create_id yet...
elseif(isset($item['edit_id']) && Surfer::is($item['edit_id']))
	$permitted = TRUE;

// the default is to deny access
else
	$permitted = FALSE;

// load the skin, maybe with a variant
load_skin('images', $anchor);

// do not crawl this page
$context->sif('robots','noindex');

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'images/' => i18n::s('Images') );

// the title of the page
$context['page_title'] = i18n::s('Delete an image');

// handle overlaid view
global $render_overlaid;

// not found
if(!isset($item['id'])) {
	include '../error.php';

// permission denied
} elseif(!$permitted) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// deletion is confirmed
} elseif(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'yes')) {

	// touch the related anchor before actual deletion, since the image has to be accessible at that time
	if(is_object($anchor))
		$anchor->touch('image:delete', $item['id']);

	// if no error, back to the anchor or to the index page
	if(Images::delete($item['id'])) {
		Images::clear($item);
                if(isset($_REQUEST['strait'])) {
                    $output['success'] = true;
                    
                    // provide a new field if required
                    if(isset($_REQUEST['newfield'] )) {
                        $indice = ($_REQUEST['newfield'])?$_REQUEST['newfield']:''; 
                        $output['replace'] = Skin::build_input_file('upload'.$indice);
                    }
                    
                }elseif(isset($_REQUEST['follow_up']))
                        Safe::redirect($_REQUEST['follow_up']);
		elseif(is_object($anchor))
			Safe::redirect($anchor->get_url());
		else
			Safe::redirect($context['url_to_home'].$context['url_to_root'].'images/');
	}

// deletion has to be confirmed
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST'))
	Logger::error(i18n::s('The action has not been confirmed.'));

// ask for confirmation
else {
    
        // give context
        $context['current_action'] = 'delete';

	// commands
	$menu = array();
        
        $class_submit   = ( $render_overlaid )?'submit-overlaid':'button';
	$menu[] = Skin::build_submit_button(i18n::s('Yes, I want to delete this image'), NULL, NULL, 'confirmed', $class_submit);
	if(isset($item['id'])) {
            if( $render_overlaid ) {
                $menu[] = Skin::build_link($anchor->get_permalink(), i18n::s('Cancel'), 'overlaid');
            } else {
                $menu[] = Skin::build_link(Images::get_url($item['id']), i18n::s('Cancel'), 'span');
            }
        }

	// the submit button
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
		.Skin::finalize_list($menu, 'menu_bar')
		.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n"
		.'<input type="hidden" name="confirm" value="yes" />'."\n";
		
        // transmit the follow_up param if any
        if(isset($_REQUEST['follow_up']))
            $context['text'] .= '<input type="hidden" name="follow_up" value="'.$_REQUEST['follow_up'].'" />'."\n";
        
        $context['text'] .='</p></form>'."\n";

	// set the focus
	Page::insert_script('$("#confirmed").focus();');

	// the title of the image
	if($item['title'])
		$context['text'] .= Skin::build_block($item['title'], 'title');
	else
		$context['text'] .= Skin::build_block($item['image_name'], 'title');

	// display the full text
	$context['text'] .= '<div style="margin: 1em 0;">'.Codes::beautify($item['description']).'</div>'."\n";

	// build the path to the image file
	list($anchor_type, $anchor_id) = explode(':', $item['anchor']);
	$url = $anchor_type.'/'.$anchor_id.'/'.$item['image_name'];
	$context['text'] .= "\n<p>".'<img src="'.$context['url_to_root'].'images/'.$url.'" alt="" /></p>';

	// details
	$details = array();

	// the image name, if it has not already been used as title
	if($item['title'])
		$details[] = $item['image_name'];

	// file size
	if($item['image_size'] > 1)
		$details[] = number_format($item['image_size']).'&nbsp;'.i18n::s('bytes');

	// information on uploader
	if(Surfer::is_member())
		$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

	// the complete details
	if($details)
		$context['text'] .= '<p '.tag::_class('details').'>'.ucfirst(implode(', ', $details))."</p>\n";

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
		$context['text'] .= '<p '.tag::_class('details').'>'.sprintf(i18n::s('Source: %s'), $item['source'])."</p>\n";
	}

}

if(isset($_REQUEST['strait'])) {
    $output = json_encode($output);
    // allow for data compression
    render_raw('application/json; charset='.$context['charset']);

    // actual transmission except on a HEAD request
    if(!isset($_SERVER['REQUEST_METHOD']) || ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
	echo $output;
    
    finalize_page(true);
} else
    // render the skin
    render_skin();