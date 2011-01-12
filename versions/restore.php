<?php
/**
 * restore an old version
 *
 * Restoring a version means the anchor state is changed to a past version.
 * Also, the version, and more recent versions, are suppressed from the database.
 *
 * Only anchor owners can proceed
 *
 * Accept following invocations:
 * - restore.php/12
 * - restore.php?id=12
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
include_once 'versions.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item =& Versions::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor =& Anchors::get($item['anchor']);

// you have to own the object to handle versions
if(is_object($anchor) && $anchor->is_owned())
	$permitted = TRUE;

// the default is to deny access
else
	$permitted = FALSE;

// load the skin, maybe with a variant
load_skin('versions', $anchor);

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'versions/' => 'versions' );

// the title of the page
if(is_object($anchor))
	$context['page_title'] = sprintf(i18n::s('Restore: %s'), $anchor->get_title());

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// not found
} elseif(!isset($item['id'])) {
	include '../error.php';

// an anchor is mandatory
} elseif(!is_object($anchor)) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Logger::error(i18n::s('No anchor has been found.'));

// permission denied
} elseif(!$permitted) {

	// surfer has to be authenticated
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Versions::get_url($item['id'], 'restore')));

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// restoration
} else {

	// update the database
	if(Versions::restore($item['id'])) {

		// provide some feed-back
		$context['text'] .= '<p>'.i18n::s('The page has been successfully restored.').'</p>';

		// follow-up commands
		$context['text'] .= Skin::build_link($anchor->get_url(), i18n::s('Done'), 'button');

		// clear the cache; the article may be listed at many places
		Cache::clear();

	}

}

// render the skin
render_skin();

?>
