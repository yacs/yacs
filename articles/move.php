<?php
/**
 * move an article to another section
 *
 * Surfer is allowed to proceed only if he can edit the container of the target page.
 *
 * Also, the target section has to be mentioned in behaviour of the origin section.
 *
 * Accepted calls:
 * - move.php/article_id/target_section_id;
 * - move.php?id=&lt;article_id&gt;&amp;action=&lt;target_section_id&gt;
 *
 * If this article, or one of its anchor, specifies a specific skin (option keyword '[code]skin_xyz[/code]'),
 * or a specific variant (option keyword '[code]variant_xyz[/code]'), they are used instead default values.
 *
 * @author Bernard Paques
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
	$anchor =& Anchors::get($item['anchor']);

// get the related overlay, if any
$overlay = NULL;
include_once '../overlays/overlay.php';
if(isset($item['overlay']) && $item['overlay'])
	$overlay = Overlay::load($item);

// owners can do what they want
if(Articles::is_owned($anchor, $item))
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

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
		Logger::error(i18n::s('Request is invalid.'));
		$destination = NULL;
	}
}

// load the target section, by id , or with a full reference
$destination =& Anchors::get($destination);

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'articles/' => i18n::s('All pages') );
if(isset($item['id']) && isset($item['title']))
	$context['path_bar'] = array_merge($context['path_bar'], array(Articles::get_permalink($item) => $item['title']));

// page title
$context['page_title'] = i18n::s('Move a page');

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// an error has occured
} elseif(count($context['error']))
	;

// not found
elseif(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No item has the provided id.'));

// a destination anchor is mandatory
} elseif(!is_object($destination)) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No anchor has been found.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged()) {
		$link = Articles::get_url($item['id'], 'move', $destination->get_reference());
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode($link));
	}

	// permission denied to authenticated user
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// maybe this article cannot be modified anymore
} elseif(isset($item['locked']) && ($item['locked'] == 'Y') && !Surfer::is_empowered()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	Logger::error(i18n::s('This page has been locked.'));

// do the job
} else {

	// attributes to change
	$fields = array();
	$fields['id'] = $item['id'];
	$fields['anchor'] = $destination->get_reference();

	// do the change
	if(Articles::put_attributes($fields)) {

		// add a comment to make the move explicit
		include_once $context['path_to_root'].'comments/comments.php';
		$fields = array();
		$fields['anchor'] = 'article:'.$item['id'];
		$fields['description'] = sprintf(i18n::s('Moved by %s from %s to %s'), Surfer::get_name(), $anchor->get_title(), $destination->get_title());
		Comments::post($fields);

		// update previous container
		Cache::clear($anchor->get_reference());

		// switch to the updated page
		Safe::redirect($context['url_to_home'].$context['url_to_root'].Articles::get_permalink($item));

	}

}

// render the skin
render_skin();

?>