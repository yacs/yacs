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
$item =& Files::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor =& Anchors::get($item['anchor']);

// associates and authenticated editors can do what they want
if(Surfer::is_associate() || (Surfer::is_member() && is_object($anchor) && $anchor->is_editable()))
	$permitted = TRUE;

// the anchor has to be viewable by this surfer
elseif(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;

// the item is anchored to the profile of this member
elseif(Surfer::is_member() && !strcmp($item['anchor'], 'user:'.Surfer::get_id()))
	$permitted = TRUE;

// authenticated surfers may suppress their own posts
// elseif(isset($item['create_id']) && Surfer::is($item['create_id']))
//	$permitted = TRUE;

// the default is to deny access
else
	$permitted = FALSE;

// load the skin, maybe with a variant
load_skin('files', $anchor);

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// not found
if(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// deletion is confirmed
} elseif(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'yes')) {

	// touch the related anchor before actual deletion, since the file has to be accessible at that time
	if(is_object($anchor))
		$anchor->touch('file:delete', $item['id'], TRUE);

	// if no error, back to the anchor or to the index page
	if(Files::delete($item['id'])) {
		Files::clear($item);
		if(is_object($anchor))
			Safe::redirect($context['url_to_home'].$context['url_to_root'].$anchor->get_url().'#files');
		else
			Safe::redirect($context['url_to_home'].$context['url_to_root'].'files/');
	}

// deletion has to be confirmed
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST'))
	Logger::error(i18n::s('The deletion has not been confirmed.'));

// the path to this page
if(is_object($anchor))
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'files/' => i18n::s('Files') );

// the title of the page
if(isset($item['file_name']))
	$context['page_title'] = sprintf(i18n::s('%s: %s'), i18n::s('Delete'), $item['file_name']);

// display the confirmation form
if($item['id']) {

	// display the confirmation button only to allowed surfers
	if(Surfer::is_associate() || (Surfer::is_member() && is_object($anchor) && $anchor->is_editable())
		|| Surfer::is($item['create_id'])) {

		// the submit button
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
			.Skin::build_submit_button(i18n::s('Yes, I want to delete this file'), NULL, NULL, 'confirmed')."\n"
			.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n"
			.'<input type="hidden" name="confirm" value="yes" />'."\n"
			.'</p></form>'."\n";

		// set the focus
		$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
			.'// set the focus on first form field'."\n"
			.'$("confirmed").focus();'."\n"
			.'// ]]></script>'."\n";

	}

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
			$context['text'] .= Skin::table_row(array(i18n::s('Visibility'), 'left='.i18n::s('Access is restricted to associates and editors')), $lines++);
		elseif($item['active'] == 'R' && Surfer::is_member())
			$context['text'] .= Skin::table_row(array(i18n::s('Visibility'), 'left='.i18n::s('Access is restricted to authenticated members')), $lines++);
	}

	// end of the table
	$context['text'] .= Skin::table_suffix();

	// count items related to this file
	$context['text'] .= Anchors::stat_related_to('file:'.$item['id'], i18n::s('Following items are attached to this record and will be deleted as well.'));

}

// render the skin
render_skin();

?>