<?php
/**
 * lock/unlock a section
 *
 * This script is an easy way to lock or unlock a section.
 *
 * The permission assessment is based upon following rules applied in the provided orders:
 * - associates and owners are allowed to move forward
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
$item = Sections::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor = Anchors::get($item['anchor']);

// load the skin, maybe with a variant
load_skin('sections', $anchor, isset($item['options']) ? $item['options'] : '');

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'sections/' => i18n::s('Site map') );
if(isset($item['id']) && isset($item['title']))
	$context['path_bar'] = array_merge($context['path_bar'], array(Sections::get_permalink($item) => $item['title']));

// the title of the page
$context['page_title'] = i18n::s('Lock');

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// not found
} elseif(!isset($item['id'])) {
	include '../error.php';

// permission denied
} elseif(!Sections::is_owned($item, $anchor) && !Surfer::is_associate()) {

	// anonymous users are invited to log in or to register
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Sections::get_url($item['id'], 'lock')));

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// an error occured
} elseif(count($context['error']))
	;

// do the toggle and redirect to the page
elseif(Sections::lock($item['id'], $item['locked'])) {

	// clear the cache
	Sections::clear($item);

	// redirect to the page
	Safe::redirect(Sections::get_permalink($item));

// failed operation
} else
	$context['text'] .= '<p>'.i18n::s('Operation has failed.').'</p>';

// render the skin
render_skin();

?>
