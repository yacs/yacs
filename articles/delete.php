<?php
/**
 * delete an article
 *
 * This script calls for confirmation, then actually deletes an article.
 * It updates the database, then redirects to the anchor page, or to the index page for articles.
 *
 * Restrictions apply on this page:
 * - associates and authenticated editors are allowed to move forward
 * - permission is denied if the anchor is not viewable by this surfer
 * - permission is denied if the article has been published, and if revision are forbidden ('users_without_revision' == 'Y')
 * - logged surfers may decide to delete their own posts
 * - else permission is denied
 *
 * Accept following invocations:
 * - delete.php/12
 * - delete.php?id=12
 *
 * If this article, or one of its anchor, specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// look for the article id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Articles::get($id);

// get the related anchor
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor =& Anchors::get($item['anchor']);

// get the related overlay, if any
$overlay = NULL;
include_once '../overlays/overlay.php';
if(isset($item['overlay']))
	$overlay = Overlay::load($item);

// editors have associate-like capabilities
if(Surfer::is_member() && is_object($anchor) && $anchor->is_editable())
	Surfer::empower();

// associates and section editors can do what they want
if(Surfer::is_associate() || (Surfer::is_member() && is_object($anchor) && $anchor->is_editable()))
	$permitted = TRUE;

// page editors may be allowed to proceed
elseif(isset($item['id']) && Articles::is_assigned($item['id']) && is_object($anchor) && $anchor->has_option('with_deletions'))
	$permitted = TRUE;

// the anchor has to be viewable by this surfer
elseif(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;

// revision of published pages is prohibited
elseif(($item['publish_date'] > NULL_DATE) && isset($context['users_without_revision']) && ($context['users_without_revision'] == 'Y'))
	$permitted = FALSE;

// authenticated surfers may suppress their own posts
elseif(Surfer::is($item['create_id']))
	$permitted = TRUE;

// the default is to deny access
else
	$permitted = FALSE;

// load the skin, maybe with a variant
load_skin('articles', $anchor, isset($item['options']) ? $item['options'] : '');

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// path to this page
if(is_object($anchor))
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'articles/' => i18n::s('All pages') );
if(isset($item['id']))
	$context['path_bar'] = array_merge($context['path_bar'], array(Articles::get_permalink($item) => $item['title']));

// page title
if(isset($item['id']))
	$context['page_title'] = sprintf(i18n::s('%s: %s'), i18n::s('Delete'), $item['title']);

// not found
if(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// deletion is confirmed
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'delete')) {

	// touch the related anchor before actual deletion, since the image has to be accessible at that time
	if(is_object($anchor))
		$anchor->touch('article:delete', $item['id']);

	// attempt to delete
	if(Articles::delete($item['id'])) {

		// this can appear anywhere
		Cache::clear();

		// back to the anchor page or to the index page
		if(!is_object($anchor))
			Safe::redirect($context['url_to_home'].$context['url_to_root'].'articles/');
		elseif($anchor->is_viewable())
			Safe::redirect($context['url_to_home'].$context['url_to_root'].$anchor->get_url());
		elseif($id = Surfer::get_id())
			Safe::redirect($context['url_to_home'].$context['url_to_root'].Users::get_url($id, 'contact'));
		else
			Safe::redirect($context['url_to_home'].$context['url_to_root'].'articles/');

	}

// deletion has to be confirmed
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST'))
	Logger::error(i18n::s('The action has not been confirmed.'));

// please confirm
else {

	// the article or the anchor icon, if any
	$context['page_image'] = $item['icon_url'];
	if(!$context['page_image'] && is_object($anchor))
		$context['page_image'] = $anchor->get_icon_url();

	// commands
	$menu = array();
	$menu[] = Skin::build_submit_button(i18n::s('Yes, I want to delete this page'), NULL, NULL, 'confirmed');
	if(isset($item['id']))
		$menu[] = Skin::build_link(Articles::get_permalink($item), i18n::s('Cancel'), 'span');

	// render commands
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
		.Skin::finalize_list($menu, 'menu_bar')
		.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n"
		.'<input type="hidden" name="action" value="delete" />'."\n"
		.'</p></form>'."\n";

	// set the focus
	$context['text'] .= JS_PREFIX
		.'// set the focus on first form field'."\n"
		.'$("confirmed").focus();'."\n"
		.JS_SUFFIX;

	// the title of the action
	$context['text'] .= Skin::build_block($item['title'], 'title');

	// the introduction text, if any
	$context['text'] .= '<div style="margin: 1em 0;">'.Codes::beautify($item['introduction']).'</div>'."\n";

	// get text related to the overlay, if any
	if(is_object($overlay))
		$context['text'] .= $overlay->get_text('view', $item);

	// details
	$details = array();

	// last edition
	if($item['edit_name']) {
		$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));
	}

	// hits
	if($item['hits'] > 1)
		$details[] = Skin::build_number($item['hits'], i18n::s('hits'));

	// all details
	if(@count($details))
		$context['text'] .= '<p class="details">'.ucfirst(implode(', ', $details))."</p>\n";

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
		$context['text'] .= '<p class="details">'.sprintf(i18n::s('Source: %s'), $item['source'])."</p>\n";
	}

	// count items related to this article
	$context['text'] .= Anchors::stat_related_to('article:'.$item['id'], i18n::s('Following items are attached to this record and will be deleted as well.'));

}

// render the skin
render_skin();

?>