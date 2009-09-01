<?php
/**
 * AJAX back-end support for articles
 *
 * This script can be invoked from Javascript either to retrieve one or several
 * attributes, which are returned as a JSON-encoded string, or to update one or
 * several attributes.
 *
 * Restrictions apply on this page:
 * - associates and editors are allowed to move forward
 * - access is restricted ('active' field == 'R'), but the surfer is an authenticated member
 * - public access is allowed ('active' field == 'Y')
 * - permission denied is the default
 *
 * Accept following invocations:
 * - element.php/12/introduction-description -- get related attributes
 * - element.php/12 -- to POST new attributes
 * - element.php?id=12&action=introduction, description
 * - element.php?id=12&introduction=hello%20world
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// ensure browser always look for fresh data
Safe::header("Cache-Control: no-cache, must-revalidate");
Safe::header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// encode ISO-8859-1 argument, if any
if(isset($_SERVER['HTTP_ACCEPT_CHARSET']) && preg_match('/^iso-8859-1/i', $_SERVER['HTTP_ACCEPT_CHARSET']))
	$id = utf8_encode($id);

// get the item from the database
$item =& Articles::get($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($item['anchor']) && $item['anchor'])
	$anchor =& Anchors::get($item['anchor']);

// get the related overlay, if any
$overlay = NULL;
include_once '../overlays/overlay.php';
if(isset($item['overlay']))
	$overlay = Overlay::load($item);

// look for the action
$action = NULL;
if(isset($_REQUEST['action']))
	$action = $_REQUEST['action'];
elseif(isset($context['arguments'][1]))
	$action = $context['arguments'][1];
$action = strip_tags($action);

// maybe this anonymous surfer is allowed to handle this item
if(isset($item['handle']) && Surfer::may_handle($item['handle']))
	Surfer::empower();

// editors can do what they want on items anchored here
elseif(isset($item['id']) && Articles::is_assigned($item['id']) || (is_object($anchor) && $anchor->is_editable()))
	Surfer::empower();

// anonymous edition is allowed here
elseif(isset($item['options']) && $item['options'] && preg_match('/\banonymous_edit\b/i', $item['options']))
	Surfer::empower();

// members edition is allowed here
elseif(Surfer::is_member() && isset($item['options']) && $item['options'] && preg_match('/\bmembers_edit\b/i', $item['options']))
	Surfer::empower();

// associates and editors can do what they want
if(Surfer::is_empowered())
	$permitted = TRUE;

// the default is to disallow access
else
	$permitted = FALSE;

// no not kill script validation
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
	return;

// not found -- help web crawlers
if(!isset($item['id'])) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	die(i18n::s('No item has the provided id.'));

// permission denied
} elseif(!$permitted) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	die(i18n::s('You are not allowed to perform this operation.'));

// retrieve some attributes
} elseif($action) {
	$response = Articles::get_attributes($item['id'], $action);

	// encode result in JSON
	$output = Safe::json_encode($response);

	// allow for data compression
	render_raw('application/json; charset='.$context['charset']);

	// actual transmission except on a HEAD request
	if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
		echo $output;

	// the post-processing hook, then exit
	finalize_page(TRUE);

// update some attributes
} else {
	if(Articles::put_attributes($_REQUEST))
		die('OK');

	// some error has occured
	Safe::header('Status: 500 Internal Error', TRUE, 500);
}

?>