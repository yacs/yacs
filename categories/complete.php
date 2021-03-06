<?php
/**
 * help to complete tags
 *
 * This script is the back-end part in a AJAX architecture. It processes
 * data received in the parameter 'term', look in the database for matching
 * categories, and return an unordered list of keywords. If an introduction
 * has been set for the keyword, it is provided as well.
 *
 * Accept following invocations:
 * - complete.php?term=abc
 *
 * @author Bernard Paques
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'categories.php';

// ensure browser always look for fresh data
http::expire(0);

// stop here on scripts/validate.php
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
	return;

// load the skin, maybe with a variant
load_skin('categories');

// page title
$context['page_title'] = i18n::s('Complete tags');

// some input is mandatory
if(!isset($_REQUEST['term']) || !$_REQUEST['term']) {
	Safe::header('Status: 400 Bad Request', TRUE, 400);
	die(i18n::s('Request is invalid.'));
}

// just for sanity
$_REQUEST['term'] = preg_replace(FORBIDDEN_IN_NAMES, '_', $_REQUEST['term']);

// may focus tag under a mothercat
$mothercat = NULL;
if(isset($_REQUEST['cat']))
    $mothercat = $_REQUEST['cat'];

// we return some text
$output = '';

// look for matching items
$items = Categories::list_keywords($_REQUEST['term'], $mothercat);

// build an unordered list
if(count($items)) {
	$output .= '[';
	$i = 0;
	foreach($items as $label => $more) {
		if ($i > 0)
		  $output .= ',';
		$i++;
		$output .= '"'.$label.'"';
	}

	$output .= ']';
} else {
    // empty array
    $output = json_encode(array());
}

// allow for data compression
render_raw('application/json; charset='.$context['charset']);

// actual transmission except on a HEAD request
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
	echo $output;

// the post-processing hook, then exit
finalize_page(TRUE);

?>