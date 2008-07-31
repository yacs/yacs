<?php
/**
 * help to complete tags
 *
 * This script is the back-end part in a AJAX architecture. It processes
 * data received in the parameter 'q', look in the database for matching
 * categories, and return an unordered list of keywords. If an introduction
 * has been set for the keyword, it is provided as well.
 *
 * @link http://wiki.script.aculo.us/scriptaculous/show/Ajax.Autocompleter
 *
 * Accept following invocations:
 * - complete.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'categories.php';

// ensure browser always look for fresh data
Safe::header("Cache-Control: no-cache, must-revalidate");
Safe::header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

// stop here on scripts/validate.php
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
	return;

// load the skin, maybe with a variant
load_skin('categories');

// page title
$context['page_title'] = i18n::s('Complete tags');

// some input is mandatory
if(!isset($_REQUEST['q']) || !$_REQUEST['q']) {
	Safe::header('Status: 400 Bad Request', TRUE, 400);
	die(i18n::s('Request is invalid.'));
}

// just for sanity
$_REQUEST['q'] = preg_replace(FORBIDDEN_IN_NAMES, '_', $_REQUEST['q']);

// we return some text
$output = '';

// look for matching items
$items = Categories::list_keywords($_REQUEST['q']);

// build an unordered list
if(count($items)) {
	$output .= '<ul>'."\n";

	foreach($items as $label => $more) {
		$output .= "\t".'<li>'.$label;

		// append contextual information, if any --specific to scriptaculous
		if($more)
			$output .= '<span class="informal details"> -&nbsp;'.Skin::strip($more, 7, NULL, NULL, FALSE).'</span>';

		$output .= '</li>'."\n";
	}

	$output .= '</ul>';
}

// allow for data compression
render_raw();

// actual transmission except on a HEAD request
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
	echo $output;

// the post-processing hook, then exit
finalize_page(TRUE);

?>