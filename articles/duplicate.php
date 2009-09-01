<?php
/**
 * duplicate an article
 *
 * @todo add a selection of target section (Francois Charron)
 *
 * This script calls for confirmation, then actually duplicates an article.
 * Images of the original article are duplicated as well, as other attached
 * items such as files, links, or comments, but also tables and locations.
 * It updates the database, then redirects to the anchor page, or to the
 * index page for articles.
 *
 * Restrictions apply on this page:
 * - associates and authenticated editors are allowed to move forward
 * - permission is denied if the anchor is not viewable by this surfer
 * - logged surfers may decide to duplicate their own posts
 * - else permission is denied
 *
 * Accept following invocations:
 * - duplicate.php/12
 * - duplicate.php?id=12
 *
 * If this article, or one of its anchor, specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Fernand Le Chien
 * @tester Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
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

// editors can do what they want on items anchored here
if(Surfer::is_member() && is_object($anchor) && $anchor->is_editable())
	Surfer::empower();

// associates and authenticated editors can do what they want
if(Surfer::is_empowered())
	$permitted = TRUE;

// the anchor has to be viewable by this surfer
elseif(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;

// authenticated surfers may duplicate their own posts
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
	$context['page_title'] = sprintf(i18n::s('%s: %s'), i18n::s('Duplicate'), $item['title']);

// not found
if(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// action is confirmed
} elseif(isset($_REQUEST['action']) && ($_REQUEST['action'] == 'duplicate')) {

	// to duplicate related items
	$original_anchor = 'article:'.$item['id'];

	// we will get a new id and a new handle
	unset($item['id']);
	unset($item['handle']);

	// the duplicator becomes the author
	unset($item['create_address']);
	unset($item['create_date']);
	unset($item['create_id']);
	unset($item['create_name']);

	unset($item['edit_address']);
	unset($item['edit_date']);
	unset($item['edit_id']);
	unset($item['edit_name']);

	// ensure this is a copy
	$item['title'] = sprintf(i18n::s('Copy of %s'), $item['title']);

	// also duplicate the provided overlay, if any -- re-use 'overlay_type' only
	$overlay = Overlay::load($item);

	// create a new page
	if($item['id'] = Articles::post($item)) {

		// post an overlay, with the new article id
		if(is_object($overlay))
			$overlay->remember('insert', $item);

		// duplicate all related items, images, etc.
		Anchors::duplicate_related_to($original_anchor, 'article:'.$item['id']);

		// if poster is a registered user
		if(Surfer::get_id()) {

			// increment the post counter of the surfer
			Users::increment_posts(Surfer::get_id());

			// add this page to watch list
			Members::assign('article:'.$item['id'], 'user:'.Surfer::get_id());

		}

		// get the new item
		$article =& Anchors::get('article:'.$item['id'], TRUE);

		$context['page_title'] = i18n::s('Thank you for your contribution');

		// the page has been duplicated
		$context['text'] .= '<p>'.i18n::s('The page has been duplicated.').'</p>';

		// follow-up commands
		$follow_up = i18n::s('What do you want to do now?');
		$menu = array();
		$menu = array_merge($menu, array($article->get_url() => i18n::s('View the page')));
		$menu = array_merge($menu, array($article->get_url('edit') => i18n::s('Edit the page')));
		if(Surfer::may_upload()) {
			$menu = array_merge($menu, array('images/edit.php?anchor='.urlencode($article->get_reference()) => i18n::s('Add an image')));
			$menu = array_merge($menu, array('files/edit.php?anchor='.urlencode($article->get_reference()) => i18n::s('Upload a file')));
		}
		$menu = array_merge($menu, array('links/edit.php?anchor='.urlencode($article->get_reference()) => i18n::s('Add a link')));
		$follow_up .= Skin::build_list($menu, 'menu_bar');
		$context['text'] .= Skin::build_block($follow_up, 'bottom');

		// log the creation of a new article
		$label = sprintf(i18n::c('Article copy: %s'), strip_tags($article->get_title()));

		// poster and target section
		if(is_object($anchor))
			$description = sprintf(i18n::c('Sent by %s in %s'), Surfer::get_name(), $anchor->get_title());
		else
			$description = sprintf(i18n::c('Sent by %s'), Surfer::get_name());

		// title and link
		if($title = $article->get_title())
			$description .= $title."\n";
		$description = $context['url_to_home'].$context['url_to_root'].$article->get_url()."\n\n";

		// notify sysops
		Logger::notify('articles/duplicate.php', $label, $description);

	}

// action has to be confirmed
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {
	Logger::error(i18n::s('The action has not been confirmed.'));

// please confirm
} else {

	// the article or the anchor icon, if any
	$context['page_image'] = $item['icon_url'];
	if(!$context['page_image'] && is_object($anchor))
		$context['page_image'] = $anchor->get_icon_url();

	// commands
	$menu = array();
	$menu[] = Skin::build_submit_button(i18n::s('Yes, I want to duplicate this page'), NULL, NULL, 'confirmed');
	if(isset($item['id']))
		$menu[] = Skin::build_link(Articles::get_permalink($item), i18n::s('Cancel'), 'span');

	// render commands
	$context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><p>'."\n"
		.Skin::finalize_list($menu, 'menu_bar')
		.'<input type="hidden" name="id" value="'.$item['id'].'" />'."\n"
		.'<input type="hidden" name="action" value="duplicate" />'."\n"
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
	if($item['edit_name'])
		$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

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
	$context['text'] .= Anchors::stat_related_to('article:'.$item['id'], i18n::s('Following items are attached to this record and will be duplicated as well.'));

}

// render the skin
render_skin();

?>