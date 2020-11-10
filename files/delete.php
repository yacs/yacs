<?php
/**
 * delete a file
 *
 * This script calls for confirmation, then actually deletes the file.
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
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'files.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item = Files::get($id);

// current item
if(isset($item['id']))
	$context['current_item'] = 'file:'.$item['id'];

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor = Anchors::get($item['anchor']);

// get the related overlay, if any
$overlay = NULL;
if(isset($item['overlay']))
	$overlay = Overlay::load($item, 'file:'.$item['id']);

// the surfer can proceed
if(Files::allow_deletion($item, $anchor)) {
	Surfer::empower();
	$permitted = TRUE;

// the default is to deny access
} else
	$permitted = FALSE;

// load the skin, maybe with a variant
load_skin('files', $anchor);

// do not crawl this page
$context->sif('robots','noindex');

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor))
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'files/' => i18n::s('Files') );

// the title of the page
if(isset($item['file_name']))
	$context['page_title'] = sprintf(i18n::s('%s: %s'), i18n::s('Delete'), $item['file_name']);

// not found
if(!isset($item['id'])) {
	include '../error.php';

// permission denied
} elseif(!$permitted) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// file has been reserved
} elseif(isset($item['assign_id']) && $item['assign_id'] && !Surfer::is($item['assign_id'])) {

	// prevent updates
	$context['text'] .= Skin::build_block(sprintf(i18n::s('This file has been reserved by %s %s, and it is likely that an updated version will be made available soon.'), Users::get_link($item['assign_name'], $item['assign_address'], $item['assign_id']), Skin::build_date($item['assign_date'])), 'caution');

	// follow-up commands
	$menu = array();
	$menu[] = Skin::build_link($anchor->get_url('files'), i18n::s('Done'), 'button');
	$menu[] = Skin::build_link(Files::get_url($item['id'], 'release'), i18n::s('Release reservation'), 'span');
	$context['text'] .= Skin::build_block(Skin::finalize_list($menu, 'menu_bar'), 'bottom');

// deletion is confirmed
} elseif(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'yes')) {

	// touch the related anchor before actual deletion, since the file has to be accessible at that time
	if(is_object($anchor))
		$anchor->touch('file:delete', $item['id']);

	// if no error, back to the anchor or to the index page
	if(Files::delete($item['id'])) {

		// log item deletion
		$label = sprintf(i18n::c('Deletion: %s'), strip_tags($item['title']));
		$description = Files::get_permalink($item);
		Logger::remember('files/delete.php: '.$label, $description);

		Files::clear($item);
                
        if($render_overlaid) {
            echo 'delete done';
            die;
        }

		if(is_object($anchor))
			Safe::redirect($anchor->get_url().'#_attachments');
		else
			Safe::redirect($context['url_to_home'].$context['url_to_root'].'files/');
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
        
        if(!is_object($overlay) || !$delete_text = mb_strtolower($overlay->get_label('delete_command')))
             $delete_text = i18n::s('delete this file');
        
	$menu[] = Skin::build_submit_button(sprintf(i18n::s('Yes, I want to %s'),$delete_text), NULL, NULL,  'confirmed', $class_submit);
        if(!$render_overlaid) {
            if(isset($item['id']))
		$menu[] = Skin::build_link(Files::get_permalink($item), i18n::s('Cancel'), 'span');
        } else
                $menu[] = '<span><a href="javascript:;" onclick="Yacs.closeModalBox();">'.i18n::s('Cancel').'</a></span>';

	// the submit button
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
		.Skin::finalize_list($menu, 'menu_bar')
		.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n"
		.'<input type="hidden" name="confirm" value="yes" />'."\n"
		.'</p></form>'."\n";

	// set the focus
	Page::insert_script('$("#confirmed").focus();');

	// use a table for the layout
	$context['text'] .= Skin::table_prefix('form');
	$lines = 1;

	// the title
	if($item['title']) {
		$cells = array(i18n::s('Title'), 'left='.$item['title']);
		$context['text'] .= Skin::table_row($cells, $lines++);
	}

	// the description
	if($item['description']) {
		$cells = array(i18n::s('Description'), 'left='.$item['description']);
		$context['text'] .= Skin::table_row($cells, $lines++);
	}

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
		$cells = array(i18n::s('Source'), 'left='.$item['source']);
		$context['text'] .= Skin::table_row($cells, $lines++);
	}

	// actual file name
	$cells = array(i18n::s('Actual file'), 'left='.$item['file_name']);
	$context['text'] .= Skin::table_row($cells, $lines++);

	// file size
	$cells = array(i18n::s('File size'), 'left='.sprintf(i18n::s('%d bytes'), $item['file_size']));
	$context['text'] .= Skin::table_row($cells, $lines++);

	// hits
	if($item['hits'] > 1) {
		$cells = array(i18n::s('Downloads'), 'left='.Skin::build_number($item['hits'], i18n::s('downloads')));
		$context['text'] .= Skin::table_row($cells, $lines++);
	}

	// the first poster
	if($item['create_name']) {
		$cells = array(i18n::s('Posted by'), $item['create_name']);
		$context['text'] .= Skin::table_row($cells, $lines++);
	}

	// the last poster
	if($item['edit_name'] != $item['create_name']) {
		$cells = array(i18n::s('Updated by'), $item['edit_name']);
		$context['text'] .= Skin::table_row($cells, $lines++);
	}

	// date of last action
	$cells = array(i18n::s('Last action'), Skin::build_date($item['edit_date']));
	$context['text'] .= Skin::table_row($cells, $lines++);

	// associates may change the active flag: Yes/public, Restricted/logged, No/associates
	if(Surfer::is_associate()) {
		if($item['active'] == 'N' && Surfer::is_associate())
			$context['text'] .= Skin::table_row(array(i18n::s('Access'), 'left='.i18n::s('Private - Access is restricted to selected persons')), $lines++);
		elseif($item['active'] == 'R' && Surfer::is_member())
			$context['text'] .= Skin::table_row(array(i18n::s('Access'), 'left='.i18n::s('Community -Access is granted to any identified surfer')), $lines++);
	}

	// end of the table
	$context['text'] .= Skin::table_suffix();

	// count items related to this file
	$context['text'] .= Anchors::stat_related_to('file:'.$item['id'], i18n::s('Following items are attached to this record and will be deleted as well.'));

}

// render the skin
render_skin();

?>
