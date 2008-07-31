<?php
/**
 * lock/unlock a page
 *
 * This script is an easy way to lock or unlock a page.
 *
 * The permission assessment is based upon following rules applied in the provided orders:
 * - associates and editors are allowed to move forward
 * - surfer created the page and the page has not been published
 * - permission denied is the default
 *
 * Accepted calls:
 * - lock.php/&lt;id&gt;
 * - lock.php?id=&lt;id&gt;
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
	$anchor = Anchors::get($item['anchor']);

// editors can do what they want on items anchored here
if(is_object($anchor) && $anchor->is_editable())
	Surfer::empower();

// associates and editors can do what they want
if(Surfer::is_empowered() || (is_object($anchor) && $anchor->is_editable()))
	$permitted = TRUE;

// surfer created the page and the page has not been published
elseif(Surfer::get_id() && isset($item['create_id']) && ($item['create_id'] == Surfer::get_id())
	&& (!isset($item['publish_date']) || ($item['publish_date'] <= NULL_DATE)) )
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// load the skin, maybe with a variant
load_skin('articles', $anchor, isset($item['options']) ? $item['options'] : '');

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// not found
} elseif(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Articles::get_url($item['id'], 'lock')));

	// permission denied to authenticated user
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// an error occured
} elseif(count($context['error']))
	;

// do the toggle
elseif(Articles::lock($item['id'], $item['locked'])) {

	// clear the cache
	Articles::clear($item);

	// redirect to the page
	Safe::redirect($context['url_to_home'].$context['url_to_root'].Articles::get_permalink($item));
}

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
$context['page_title'] = i18n::s('Lock');

// failed operation
$context['text'] .= '<p>'.i18n::s('Operation has failed.').'</p>';

// render the skin
render_skin();

?>