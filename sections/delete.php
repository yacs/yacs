<?php
/**
 * delete a section.
 *
 * This script calls for confirmation, then actually deletes the comment.
 * The script updates the database, then redirects to the sections index page.
 *
 * Restrictions apply on this page:
 * - associates can proceed
 * - managing editors of containing anchors can proceed too
 * - logged members are allowed to delete a section they have created
 * - permission denied is the default
 *
 * Note that a managing editor can delete a sub-section, but not the section he is managing.
 *
 * Also, a section cannot be deleted until it contains some sub-section or some article.
 *
 * Accept following invocations:
 * - delete.php/12
 * - delete.php?id=12
 *
 * If this section, or one of its anchor, specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Jan Boen
 * @tester Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Sections::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor =& Anchors::get($item['anchor']);

// editors of upper containers have associate-like capabilities
if(is_object($anchor) && $anchor->is_editable())
	Surfer::empower();

// associates and editors are always authorized
if(Surfer::is_empowered())
	$permitted = TRUE;

// creator may delete his section
elseif(Surfer::is_member() && isset($item['create_id']) && (Surfer::get_id() == $item['create_id']))
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load the skin, maybe with a variant
load_skin('sections', $anchor, isset($item['options']) ? $item['options'] : '');

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor)&& $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'sections/' => i18n::s('Sections'));

if($item['id'] && $item['title'])
	$context['path_bar'] = array_merge($context['path_bar'], array(Sections::get_permalink($item) => $item['title'] ));

// the title of the page
if(isset($item['title']))
	$context['page_title'] = sprintf(i18n::s('Delete: %s'), $item['title']);

// not found
if(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No item has the provided id.'));

// access denied
} elseif(!$permitted) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// deletion is confirmed
} elseif(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'yes')) {

	// touch the related anchor before actual deletion, since the image has to be accessible at that time
	if(is_object($anchor))
		$anchor->touch('section:delete', $item['id'], TRUE);

	// attempt to delete
	if(Sections::delete($item['id'])) {

		// this can appear anywhere
		Cache::clear();

		// back to the anchor page or to the index page
		if(is_object($anchor))
			Safe::redirect($context['url_to_home'].$context['url_to_root'].$anchor->get_url());
		else
			Safe::redirect($context['url_to_home'].$context['url_to_root'].'sections/');
	}

// deletion has to be confirmed
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST'))
	Skin::error(i18n::s('The deletion has not been confirmed.'));

// the form
else {

	// all sub-sections have not been deleted
	if(($stats = Sections::stat_for_anchor('section:'.$item['id'])) && $stats['count'])
		Skin::error(i18n::s('Warning: related content will be deleted as well.'));

	// all articles have not been deleted
	if($count = Articles::count_for_anchor('section:'.$item['id']))
		Skin::error(i18n::s('Warning: related content will be deleted as well.'));

	// the submit button
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
		.Skin::build_submit_button(i18n::s('Yes, I want to suppress this section'), NULL, NULL, 'confirmed')."\n"
		.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n"
		.'<input type="hidden" name="confirm" value="yes" />'."\n"
		.'</p></form>'."\n";

	// set the focus
	$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
		.'// set the focus on first form field'."\n"
		.'$("confirmed").focus();'."\n"
		.'// ]]></script>'."\n";

	// the title of the section
	if($item['title'])
		$context['text'] .= Skin::build_block($item['title'], 'title');

	// information on last editor
	if($item['edit_name'])
		$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

	// hits
	if($item['hits'] > 1)
		$details[] = Skin::build_number($item['hits'], i18n::s('hits'));

	// all details
	$context['text'] .= '<p class="details">'.ucfirst(implode(', ', $details)).'</p>'.BR."\n";

	// the introduction text, if any
	$context['text'] .= Skin::build_block($item['introduction'], 'introduction');

	// main text
	$context['text'] .= Skin::build_block($item['description'], 'description', '', $item['options']);

	// count items related to this section
	$context['text'] .= Anchors::stat_related_to('section:'.$item['id'], i18n::s('Following items are attached to this record and will be deleted as well.'));

}

// render the skin
render_skin();

?>