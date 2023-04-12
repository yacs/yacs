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
$term = preg_replace(FORBIDDEN_IN_NAMES, '_', $_REQUEST['term']);

// may focus tag under a mothercat
$mothercat = NULL;
if(isset($_REQUEST['cat']))
    $mothercat = $_REQUEST['cat'];

// we return some text
$output = '';

// look for matching items
$items = Categories::list_keywords($term, $mothercat);

// build an unordered list
if(count($items)) {
	$output .= '[';
	$i = 0;
	foreach($items as $label) {
		if ($i > 0)
		  $output .= ',';
		$i++;
                
                // label may contain comma
                $strings    = preg_split('/[ \t]*,\s*/', $label);
                
                // prune entries that does not fit with term
                $refine     = array_filter($strings, function($k) use ($term) { 
                    return (strcmp($term, substr($k, 0, strlen($term))) === 0);
                    
                });
                
                // double quote every words
                $chain      = '"'.implode('","',$refine).'"';
		
                $output .= $chain;
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