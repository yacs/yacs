<?php
/**
 * help to look for users
 *
 * This script is the back-end part in a AJAX architecture. It processes
 * data received in the parameter 'term', look in the database for matching
 * users, and return an unordered list of keywords. If full name or e-mail
 * address has been set, it is provided as well.
 *
 * @link http://jqueryui.com/demos/autocomplete/
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

// ensure browser always look for fresh data
http::expire(0);

// stop here on scripts/validate.php
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
	return;

// some input is mandatory
if(!isset($_REQUEST['term']) || !$_REQUEST['term']) {
	Safe::header('Status: 400 Bad Request', TRUE, 400);
	die(i18n::s('Request is invalid.'));
}

// just for sanity
$_REQUEST['term'] = preg_replace(FORBIDDEN_IN_NAMES, '_', $_REQUEST['term']);

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	die(i18n::s('You are not allowed to perform this operation.'));
}

// we return some text
$output = '';

// look for matching items
$items = Users::search($_REQUEST['term'], 0, 50, 'complete');

// build an unordered list
if(count($items)) {
	$output .= '[';
	$i = 0;

	foreach($items as $label => $more) {
	    if ($i > 0)
	      $output .= ',';
	    $i++;

	    $output .= '{"value":"'.$label.'","label":"'.$more.'"}';

	}

	$output .= ']';
}

// allow for data compression
render_raw();

// actual transmission except on a HEAD request
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
	echo $output;

// the post-processing hook, then exit
finalize_page(TRUE);

?>
