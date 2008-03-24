<?php
/**
 * move an article to another section
 *
 * The permission assessment is based upon following rules applied in the provided orders:
 * - the anchor is editable and the surfer has been authenticated
 * - the anchor is viewable and the target section is mentioned in behaviors of source section
 * - permission denied is the default
 *
 * Accepted calls:
 * - move.php/article_id/target_section_id;
 * - move.php?id=&lt;article_id&gt;&amp;action=&lt;target_section_id&gt;
 *
 * If this article, or one of its anchor, specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
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
$item =& Articles::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor = Anchors::get($item['anchor']);

// get the related overlay, if any
$overlay = NULL;
include_once '../overlays/overlay.php';
if(isset($item['overlay']) && $item['overlay'])
	$overlay = Overlay::load($item);

// maybe this anonymous surfer is allowed to handle this item
if(isset($item['handle']) && Surfer::may_handle($item['handle']))
	Surfer::empower();

// editors can do what they want on items anchored here
elseif(isset($item['id']) && Articles::is_assigned($item['id']) || (is_object($anchor) && $anchor->is_editable()))
	Surfer::empower();

// associates and editors can do what they want
if(Surfer::is_empowered())
	$permitted = TRUE;

// surfer created the page and the page has not been published
elseif(Surfer::get_id() && isset($item['create_id']) && ($item['create_id'] == Surfer::get_id())
	&& (!isset($item['publish_date']) || ($item['publish_date'] <= NULL_DATE)) )
	$permitted = TRUE;

// surfer has created the published page and revisions are allowed
elseif(Surfer::get_id() && isset($item['create_id']) && ($item['create_id'] == Surfer::get_id())
	&& isset($item['publish_date']) && ($item['publish_date'] > NULL_DATE)
	&& (!isset($context['users_without_revision']) || ($context['users_without_revision'] != 'Y')) )
	$permitted = TRUE;

// the anchor has to be viewable by this surfer
elseif(is_object($anchor) && !$anchor->is_viewable())
	$permitted = FALSE;

// the default is to disallow access
else
	$permitted = FALSE;

// load localized strings
i18n::bind('articles');

// load the skin, maybe with a variant
load_skin('articles', $anchor, isset($item['options']) ? $item['options'] : '');

// get the target section
$destination = NULL;
if(isset($_REQUEST['action']))
	$destination = $_REQUEST['action'];
elseif(isset($context['arguments'][1]))
	$destination = $context['arguments'][1];

// ensure that the target section is defined in behaviors of the source section
if($destination && is_object($anchor)) {
	$behaviors = $anchor->get_behaviors();
	if(!preg_match('/move_on_article_access\s+'.preg_quote($destination, '/').'/i', $behaviors)) {
		Safe::header('Status: 400 Bad Request', TRUE, 400);
		Skin::error(i18n::s('Request is invalid.'));
		$destination = NULL;
	}
}

// load the target section, by id , or with a full reference
$destination = Anchors::get($destination);

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'articles/' => i18n::s('Articles') );
if(isset($item['id']) && isset($item['title']))
	$context['path_bar'] = array_merge($context['path_bar'], array(Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']) => $item['title']));

// the title of the page
if(is_object($overlay) && ($label = $overlay->get_label('page_title', isset($item['id'])?'edit':'new')))
	$context['page_title'] = $label;
elseif(isset($item['title']) && $item['title'])
	$context['page_title'] = sprintf(i18n::s('Move: %s'), $item['title']);
else
	$context['page_title'] = i18n::s('Move a page');

// command to go back
if(isset($item['id']))
	$context['page_menu'] = array( Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']) => i18n::s('Back to the page') );

// an error has occurred
if(count($context['error']))
	;

// not found
elseif(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No item has the provided id.'));

// a destination anchor is mandatory
} elseif(!is_object($destination)) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No anchor has been found.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged()) {
		$link = Articles::get_url($item['id'], 'move', $destination->get_reference());
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode($link));
	}

	// permission denied to authenticated user
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// maybe this article cannot be modified anymore
} elseif(isset($item['locked']) && ($item['locked'] == 'Y') && !Surfer::is_empowered()) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('This page has been locked and you are not allowed to modify it.'));

// do the job
} else {

	// attributes to change
	$fields = array();
	$fields['id'] = $item['id'];
	$fields['anchor'] = $destination->get_reference();

	// do the change
	if(Articles::put_attributes($fields)) {

		// touch the related anchor
		$destination->touch('article:update', $item['id'], isset($_REQUEST['silent']) && ($_REQUEST['silent'] == 'Y') );

		// add a comment to make the move explicit
		include_once $context['path_to_root'].'comments/comments.php';
		$fields = array();
		$fields['anchor'] = 'article:'.$item['id'];
		$fields['description'] = sprintf(i18n::s('Moved by %s from %s to %s'), Surfer::get_name(), $anchor->get_title(), $destination->get_title());
		Comments::post($fields);

		// switch to the updated page
		Safe::redirect($context['url_to_home'].$context['url_to_root'].Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']));

	}

}

// render the skin
render_skin();

?>