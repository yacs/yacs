<?php
/**
 * record some explicit agreement
 *
 * This script is called from within behaviors that ask for formal agreement by end users.
 *
 * Accept following invocations:
 * - agree.php/article:123
 * - agree.php?id=article%A3123
 *
 * Parameters provided on invocation is saved in session data, as a new item of array $_SESSION['agreements'].
 * This means that this script should not been used for permanent nor for long-live agreements.
 *
 * After the update of session data the surfer is redirected to the referring page.
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
include_once '../../shared/global.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = strip_tags($id);

// get the related anchor, if any
$anchor = NULL;
if(isset($id))
	$anchor = Anchors::get($id);

// load localized strings
i18n::bind('behaviors');

// load the skin, maybe with a variant
load_skin('agreements', $anchor);

// no subject
if(!is_object($anchor))
	Logger::error(i18n::s('No item has the provided id.'));

// update session data
else {

	// initialize the list of agreements
	if(!isset($_SESSION['agreements']) || !is_array($_SESSION['agreements']))
		$_SESSION['agreements'] = array();

	// append the new agreement
	$_SESSION['agreements'][] = $anchor->get_reference();

	// revisit referer
	if(isset($_SERVER['HTTP_REFERER']))
		Safe::redirect($_SERVER['HTTP_REFERER']);
}

// the path to this page
if(is_object($anchor))
	$context['path_bar'] = $anchor->get_path_bar();

// the title of the page
if(is_object($anchor))
	$context['page_title'] = $anchor->get_title();

// render the skin
render_skin();

?>