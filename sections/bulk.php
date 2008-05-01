<?php
/**
 * bulk operation on section content.
 *
 * @todo reset hits on duplicate
 *
 * This script allows for several bulk operations, namely:
 * - duplicate the section and its content at once
 * - publish all pages at once
 * - move all pages to another section at once
 * - delete all pages at once
 *
 * The script always calls for confirmation before actually starting the job.
 *
 * On duplication a twin section is created, with duplicated sub-sections and
 * articles. Images of the original section are duplicated as well, as are other
 * attached items such as files, links, or comments, but also tables and locations.
 *
 * Restrictions apply on this page:
 * - associates can proceed
 * - managing editors can proceed too, but only for local actions
 * - permission denied is the default
 *
 * Accept following invocations:
 * - bulk.php/12
 * - bulk.php?id=12
 * - bulk.php/12/duplicate
 * - bulk.php?id=12&action=duplicate
 * - bulk.php/12/duplicate/yes
 * - bulk.php?id=12&action=duplicate&confirm=yes
 *
 * If this section, or one of its anchor, specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
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
	$anchor = Anchors::get($item['anchor']);

// which action?
$action = NULL;
if(isset($_REQUEST['action']) && $_REQUEST['action'])
	$action = $_REQUEST['action'];
elseif(isset($context['arguments'][1]))
	$action = $context['arguments'][1];
$action = strip_tags($action);

// is this confirmed?
$confirmed = FALSE;
if(isset($_REQUEST['confirm']) && !strcmp($_REQUEST['confirm'], 'yes'))
	$confirmed = TRUE;
elseif(isset($context['arguments'][1]) && !strcmp($context['arguments'][1], 'yes'))
	$confirmed = TRUE;

// editors can do what they want on items anchored here
if(Surfer::is_member() && is_object($anchor) && $anchor->is_editable())
	Surfer::empower();

// // editors of upper containers have associate-like capabilities
// elseif((isset($item['id']) && Sections::is_assigned($item['id']) && Surfer::is_member()) || (is_object($anchor) && $anchor->is_editable()))
//	Surfer::empower();

// associates are always authorized
if(Surfer::is_associate())
	$permitted = TRUE;

// editors are also authorized, but only for local actions
elseif(Surfer::is_empowered() && ($action != 'duplicate') && ($action != 'move'))
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
if(is_object($anchor))
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'sections/' => i18n::s('Sections') );
if(isset($item['id']))
	$context['path_bar'] = array_merge($context['path_bar'], array(Sections::get_url($item['id'], 'view', $item['title'], $item['nick_name']) => $item['title']));

// the title of the page
$context['page_title'] = i18n::s('Bulk operations');

// not found
if(!$item['id']) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No item has the provided id.'));

// access denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Sections::get_url($item['id'], 'purge')));

	// permission denied to authenticated user
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// duplication has been confirmed
} elseif(($action == 'duplicate') && $confirmed) {

	// remember original anchor
	$original_anchor = 'section:'.$item['id'];

	// we will get a new id
	unset($item['id']);

	// the duplicator becomes the author
	unset($item['create_address']);
	unset($item['create_date']);
	unset($item['create_id']);
	unset($item['create_name']);

	unset($item['edit_address']);
	unset($item['edit_date']);
	unset($item['edit_id']);
	unset($item['edit_name']);

	// change the handle
	unset($item['handle']);

	// ensure this is a copy
	$item['title'] = sprintf(i18n::s('Copy of %s'), $item['title']);

	// create a new section
	if($id = Sections::post($item)) {

		// duplicate all related items, images, etc.
		Anchors::duplicate_related_to($original_anchor, 'section:'.$id);

		// touch the related anchor
		if(is_object($anchor))
			$anchor->touch('section:create', $id);

		// increment the post counter of the surfer
		Users::increment_posts(Surfer::get_id());

		// get the new item
		$section = Anchors::get('section:'.$id);

		// reward the poster
		$context['page_title'] = i18n::s('Thank you for your contribution');

		// the section has been duplicated
		$context['text'] .= '<p>'.i18n::s('The section has been duplicated.').'</p>';

		// follow-up commands
		$context['text'] .= '<p>'.i18n::s('What do you want to do now?').'</p>';
		$menu = array();
		$menu = array_merge($menu, array($section->get_url() => i18n::s('View the section')));
		$menu = array_merge($menu, array($section->get_url('edit') => i18n::s('Edit the section')));
		if(Surfer::may_upload()) {
			$menu = array_merge($menu, array('images/edit.php?anchor='.urlencode('section:'.$id) => i18n::s('Add an image')));
			$menu = array_merge($menu, array('files/edit.php?anchor='.urlencode('section:'.$id) => i18n::s('Upload a file')));
		}
		$menu = array_merge($menu, array('links/edit.php?anchor='.urlencode('section:'.$id) => i18n::s('Add a link')));
		$context['text'] .= Skin::build_list($menu, 'menu_bar');

		// log the creation of a new section
		$label = sprintf(i18n::c('New duplication: %s'), strip_tags($section->get_title()));
		if(is_object($anchor))
			$description = sprintf(i18n::c('Triggered by %s in %s'), Surfer::get_name(), $anchor->get_title());
		else
			$description = sprintf(i18n::c('Triggered by %s'), Surfer::get_name());
		$description .= "\n\n".$section->get_teaser('basic')
			."\n\n".$context['url_to_home'].$context['url_to_root'].$section->get_url();
		Logger::notify('sections/duplicate.php', $label, $description);

	}

// duplication has to be confirmed
} elseif($action == 'duplicate') {

	// the submit button
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
		.Skin::build_submit_button(i18n::s('Yes, I want to duplicate this section'), NULL, NULL, 'confirmed')."\n"
		.'<input type="hidden" name="action" value="duplicate" />'."\n"
		.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n"
		.'<input type="hidden" name="confirm" value="yes" />'."\n"
		.'</p></form>'."\n";

	// set the focus
	$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
		.'// set the focus on first form field'."\n"
		.'document.getElementById("confirmed").focus();'."\n"
		.'// ]]></script>'."\n";

	// the section or the anchor icon, if any
	$context['page_image'] = $item['icon_url'];
	if(!$context['page_image'] && is_object($anchor))
		$context['page_image'] = $anchor->get_icon_url();

	// the title of the section
	if($item['title'])
		$context['text'] .= Skin::build_block($item['title'], 'title');

	// last edition
	if($item['edit_name'])
		$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

	// hits
	if($item['hits'] > 1)
		$details[] = sprintf(i18n::s('%d hits'), number_format($item['hits']));

	// all details
	if(@count($details))
		$context['text'] .= '<p class="details">'.ucfirst(implode(', ', $details))."</p>\n";

	// the introduction text, if any
	if($item['introduction'])
		$context['text'] .= Skin::build_block($item['introduction'], 'introduction');

	// count items related to this section
	$context['text'] .= Anchors::stat_related_to('section:'.$item['id'], i18n::s('Following items are attached to this record and will be duplicated as well.'));

// duplication has been confirmed
} elseif(($action == 'move') && $confirmed && isset($_REQUEST['target_anchor']) && preg_match('/^\w+:\d+$/', $_REQUEST['target_anchor'])) {

	// move all articles at once
	if($count = Articles::move('section:'.$id, $_REQUEST['target_anchor'])) {

		// reward the poster
		$context['page_title'] = i18n::s('Operation has successfully completed');

		// provide feed-back
		$context['text'] .= '<p>'.sprintf(i18n::ns('%d page has been moved', '%d pages have been moved', $count), $count).'</p>';

		// follow-up commands
		$context['text'] .= '<p>'.i18n::s('What do you want to do now?').'</p>';
		$menu = array();
		$menu = array_merge($menu, array(Sections::get_url($id) => i18n::s('Go back to the origin section')));
		$menu = array_merge($menu, array(Sections::get_url(str_replace('section:', '', $_REQUEST['target_anchor'])) => i18n::s('View the target section')));
		$context['text'] .= Skin::build_list($menu, 'menu_bar');

		// log the move
		$label = i18n::c('Bulk move');
		$description = sprintf(i18n::c('Triggered by %s'), Surfer::get_name())
			."\n\n".i18n::c('from').' '.$context['url_to_home'].$context['url_to_root'].Sections::get_url($id)
			."\n\n".i18n::c('to').' '.$context['url_to_home'].$context['url_to_root'].Sections::get_url(str_replace('section:', '', $_REQUEST['target_anchor']));
		Logger::notify('sections/bulk.php', $label, $description);

	} else
		$context['text'] = i18n::s('No move has taken place.');

// move has to be confirmed
} elseif($action == 'move') {

	// the section is empty
	if(!$count = Articles::count_for_anchor('section:'.$item['id'])) {
		$context['text'] .= '<p>'.i18n::s('This section has no page.')."</p>\n";

		$menu = array( Sections::get_url($item['id']) => i18n::s('Cancel and go back to the section page') );
		$context['text'] .= Skin::build_list($menu, 'menu_bar');

	// confirmation is required
	} else {

		// a splash message
		$context['text'] .= Skin::build_block(sprintf(i18n::s('This script will attach pages of this section to another section. If you would like to move the section itself, including its content, please %s and select another anchor.'), Skin::build_link(Sections::get_url($id, 'edit'), 'use the edit form')), 'caution')."\n";

		$context['text'] .= '<p>'.sprintf(i18n::s('You are about to move %d pages at once. Are you sure you want to do this?'), $count)."</p>\n";

		// the submit button
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
			.Skin::build_submit_button(i18n::s('Yes, I want to move all pages to the following section'), NULL, NULL, 'confirmed')."\n"
			.'<select name="target_anchor">'.Sections::get_options('section:'.$id).'</select>'
			.'<input type="hidden" name="action" value="move" />'."\n"
			.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n"
			.'<input type="hidden" name="confirm" value="yes" />'."\n"
			.'</p></form>'."\n";

		// set the focus
		$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
			.'// set the focus on first form field'."\n"
			.'document.getElementById("confirmed").focus();'."\n"
			.'// ]]></script>'."\n";

		// count items related to this section
		$context['text'] .= Anchors::stat_related_to('section:'.$item['id'], i18n::s('Following items are attached to this section.'));
	}

// publication has been confirmed
} elseif(($action == 'publish') && $confirmed) {

	// look for draft pages
	if($items = Articles::list_for_anchor_by('draft', 'section:'.$id, 0, 100, 'raw')) {

		// adjust date to UTC time zone
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// process articles
		$count = 0;
		foreach($items as $dummy => $item) {
			Articles::stamp($item['id'], $now);
			$count++;
		}

		// reward the poster
		$context['page_title'] = i18n::s('Operation has successfully completed');

		// provide feed-back
		$context['text'] .= '<p>'.sprintf(i18n::ns('%d page has been published', '%d pages have been published', $count), $count).'</p>';

		// follow-up commands
		$context['text'] .= '<p>'.i18n::s('What do you want to do now?').'</p>';
		$menu = array(Sections::get_url($id) => i18n::s('View the updated section'));
		$context['text'] .= Skin::build_list($menu, 'menu_bar');

		// log the move
		$label = i18n::c('Bulk publication');
		$description = sprintf(i18n::c('Triggered by %s'), Surfer::get_name())
			."\n\n".$context['url_to_home'].$context['url_to_root'].Sections::get_url($id);
		Logger::notify('sections/bulk.php', $label, $description);

	} else
		$context['text'] = i18n::s('No page has been published.');

// publication has to be confirmed
} elseif($action == 'publish') {

	// the section is empty
	if(!$count = Articles::count_for_anchor('section:'.$item['id'])) {
		$context['text'] .= '<p>'.i18n::s('This section has no page.')."</p>\n";

		$menu = array( Sections::get_url($item['id']) => i18n::s('Cancel and go back to the section page') );
		$context['text'] .= Skin::build_list($menu, 'menu_bar');

	// confirmation is required
	} else {
		$context['text'] .= '<p>'.i18n::s('You are about to publish all draft pages at once.  Are you sure you want to do this?')."</p>\n";

		// the submit button
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
			.Skin::build_submit_button(i18n::s('Yes, I want to publish all draft pages in this section'), NULL, NULL, 'confirmed')."\n"
			.'<input type="hidden" name="action" value="publish" />'."\n"
			.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n"
			.'<input type="hidden" name="confirm" value="yes" />'."\n"
			.'</p></form>'."\n";

		// set the focus
		$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
			.'// set the focus on first form field'."\n"
			.'document.getElementById("confirmed").focus();'."\n"
			.'// ]]></script>'."\n";

		// the title of the section
		if($item['title'])
			$context['text'] .= Skin::build_block($item['title'], 'title');

		// information on last editor
		if($item['edit_name'])
			$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

		// hits
		if($item['hits'] > 1)
			$details[] = sprintf(i18n::s('%d hits'), $item['hits']);

		// all details
		$context['text'] .= '<p class="details">'.ucfirst(implode(', ', $details)).'</p>'.BR."\n";

		// the introduction text, if any
		if($item['introduction'])
			$context['text'] .= Skin::build_block($item['introduction'], 'introduction');

		// count items related to this section
		$context['text'] .= Anchors::stat_related_to('section:'.$item['id'], i18n::s('Following items are attached to this section.'));
	}

// purge has been confirmed
} elseif(($action == 'purge') && $confirmed) {

	// delete and redirect to the section index page
	Articles::delete_for_anchor('section:'.$id);
	Safe::redirect($context['url_to_home'].$context['url_to_root'].Sections::get_url($id));

// purge has to be confirmed
} elseif($action == 'purge') {

	// the section is empty
	if(!$count = Articles::count_for_anchor('section:'.$item['id'])) {
		$context['text'] .= '<p>'.i18n::s('This section has no page.')."</p>\n";

		$menu = array( Sections::get_url($item['id']) => i18n::s('Cancel and go back to the section page') );
		$context['text'] .= Skin::build_list($menu, 'menu_bar');

	// confirmation is required
	} else {
		$context['text'] .= '<p>'.sprintf(i18n::s('You are about to delete %d pages at once.  Are you sure you want to do this?'), $count)."</p>\n";

		// the submit button
		$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
			.Skin::build_submit_button(i18n::s('Yes, I want to suppress all pages in this section'), NULL, NULL, 'confirmed')."\n"
			.'<input type="hidden" name="action" value="purge" />'."\n"
			.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n"
			.'<input type="hidden" name="confirm" value="yes" />'."\n"
			.'</p></form>'."\n";

		// set the focus
		$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
			.'// set the focus on first form field'."\n"
			.'document.getElementById("confirmed").focus();'."\n"
			.'// ]]></script>'."\n";

		// the title of the section
		if($item['title'])
			$context['text'] .= Skin::build_block($item['title'], 'title');

		// information on last editor
		if($item['edit_name'])
			$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

		// hits
		if($item['hits'] > 1)
			$details[] = sprintf(i18n::s('%d hits'), $item['hits']);

		// all details
		$context['text'] .= '<p class="details">'.ucfirst(implode(', ', $details)).'</p>'.BR."\n";

		// the introduction text, if any
		if($item['introduction'])
			$context['text'] .= Skin::build_block($item['introduction'], 'introduction');

		// count items related to this section
		$context['text'] .= Anchors::stat_related_to('section:'.$item['id'], i18n::s('Following items are attached to this record and will be suppressed as well.'));
	}

// which operation?
} else {

	// the splash message
	$context['text'] .= '<p>'.i18n::s('Please select below the operation to perform.')."</p>\n";

	// the form
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>';

	// duplicate the section and its content
	if(Surfer::is_associate())
		$context['text'] .= '<p><input type="radio" name="action" id="action" value="duplicate" /> '.i18n::s('Duplicate this section and its content').'</p>';

	// publish all pages
	$context['text'] .= '<p><input type="radio" name="action" id="action" value="publish" /> '.i18n::s('Publish all draft pages').'</p>';

	// move all pages
	if(Surfer::is_associate())
		$context['text'] .= '<p><input type="radio" name="action" id="action" value="move" /> '.i18n::s('Move all pages to another section').'</p>';

	// delete all pages
	$context['text'] .= '<p><input type="radio" name="action" id="action" value="purge" /> '.i18n::s('Delete all pages').'</p>';

	// the submit button
	$context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Start')).'</p>'."\n";

	// the target section
	$context['text'] .= '<input type="hidden" name="id" value="'.encode_field($item['id']).'" />';

	// end of the form
	$context['text'] .= '</div></form>';

	// set the focus on the button
	$context['text'] .= '<script type="text/javascript">// <![CDATA['."\n"
		.'document.getElementById("action").focus();'."\n"
		.'// ]]></script>'."\n";

}

// render the skin
render_skin();

?>