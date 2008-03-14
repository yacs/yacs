<?php
/**
 * use category thumbnail into an anchor
 *
 * Only associates can use this script
 *
 * Accept following invocations:
 * - set_as_thumbnail.php/123?anchor=article:12
 * - set_as_thumbnail.php?id=123&anchor=section:32
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
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

// look for the anchor
$anchor = NULL;
if(isset($_REQUEST['article']) && ($anchor_id = $_REQUEST['article']))
	$anchor = 'article:'.$anchor_id;
elseif(isset($context['arguments'][1]))
	$anchor = $context['arguments'][1].':'.$context['arguments'][2];
elseif(isset($_REQUEST['anchor']))
	$anchor = $_REQUEST['anchor'];
$anchor = strip_tags($anchor);

// get the item from the database
include_once 'categories.php';
$item =& Categories::get($id);

// get the anchor as well
$anchor = Anchors::get($anchor);

// load localized strings
i18n::bind('categories');

// load the skin
load_skin('categories');

// the path to this page
if(is_object($anchor) && $anchor->is_viewable())
	$context['path_bar'] = $anchor->get_path_bar();
else
	$context['path_bar'] = array( 'index.php' => i18n::s('Categories') );

// the title of the page
$context['page_title'] = i18n::s('Set an image as the page thumbnail');

// not found
if(!$item['id']) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No item has been found.'));

// no anchor
} elseif(!is_object($anchor)) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	Skin::error(i18n::s('No anchor has been found.'));

// operation is restricted to associates and editors
} elseif(!Surfer::is_associate() && !$anchor->is_editable()) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	Skin::error(i18n::s('You are not allowed to perform this operation.'));

// set this image as the anchor thumbnail
} else {

	// no error
	if(!($error = $anchor->touch('image:set_as_thumbnail', $item['thumbnail_url']))) {

		// back to the anchor page
		Safe::redirect($context['url_to_home'].$context['url_to_root'].$anchor->get_url());
	}
	Skin::error($error);
}

// failed operation
$context['text'] .= '<p>'.i18n::s('Impossible to update the anchor.').'</p>';

// render the skin
render_skin();

?>