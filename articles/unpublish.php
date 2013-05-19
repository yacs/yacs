<?php
/**
 * unpublish an article
 *
 * Unpublishing an article means that any publication information is lost.
 * Therefore, the page will appear again in the review queue.
 *
 * Usually this script is used after some time, when an article is becoming too old to be
 * displayed at the front page, or when it obviously require modifications.
 *
 * This page is to be used by associates and editors only, while they are reviewing articles.
 * The script updates the database, then redirects to view.php.
 *
 * Accept following invocations:
 * - unpublish.php/12
 * - unpublish.php?id=12
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

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the item from the database
$item = Articles::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']))
	$anchor = Anchors::get($item['anchor']);

// surfer can proceed
if(Articles::allow_publication($anchor, $item)) {
	Surfer::empower();
	$permitted = TRUE;

// disallow access
} else
	$permitted = FALSE;

// load the skin, maybe with a variant
load_skin('articles', $anchor, isset($item['options']) ? $item['options'] : '');

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// not found
} elseif(!isset($item['id'])) {
	include '../error.php';

// publication is restricted
} elseif(!$permitted) {

	// anonymous users are invited to log in
	if(!Surfer::is_logged())
		Safe::redirect($context['url_to_home'].$context['url_to_root'].'users/login.php?url='.urlencode(Articles::get_url($item['id'], 'unpublish')));

	// permission denied to authenticated user
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// update the database
} elseif($error = Articles::unpublish($item['id']))
	$context['text'] .= $error;

// then jump to the target page
else {
	// clear the cache
	Articles::clear($item);

	// display the updated page
	Safe::redirect(Articles::get_permalink($item));
}

// clear the tab we are in, if any
if(is_object($anchor))
	$context['current_focus'] = $anchor->get_focus();

// path to this page
$context['path_bar'] = Surfer::get_path_bar($anchor);

// page title
$context['page_title'] = i18n::s('Draft');

// render the skin
render_skin();

?>
