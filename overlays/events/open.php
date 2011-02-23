<?php
/**
 * open enrolment to an event
 *
 * Accepted calls:
 * - open.php/article/&lt;id&gt;
 * - open.php?id=&lt;article:id&gt;
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../../shared/global.php';
include_once '../../overlays/overlay.php';

// look for the id --actually, a reference
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]) && isset($context['arguments'][1]))
	$id = $context['arguments'][0].':'.$context['arguments'][1];
$id = strip_tags($id);

// get the anchor
$anchor =& Anchors::get($id);

// get the related overlay, if any
$overlay = NULL;
if(is_object($anchor)) {
	$fields = array();
	$fields['id'] = $anchor->get_value('id');
	$fields['overlay'] = $anchor->get_value('overlay');
	$overlay = Overlay::load($fields, $anchor->get_reference());
}

// load the skin, maybe with a variant
load_skin('articles', $anchor);

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// not found
} elseif(!is_object($anchor)) {
	include '../../error.php';

// permission denied
} elseif(!$anchor->is_owned()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// no overlay
} elseif(!is_object($overlay) || !is_callable(array($overlay, 'get_open_url')))
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// an error occured
elseif(count($context['error']))
	;

// get the address to open enrolment and to update the meeting page
elseif(!$follow_up = $overlay->get_open_url())
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// proceed with the action
else {

	// remember the action
	$overlay->open_event();

	// redirect to the meeting page
	Safe::redirect($follow_up);
}

// page title
if(is_object($anchor))
	$context['page_title'] = $anchor->get_title();

// render the skin
render_skin();

?>