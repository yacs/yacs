<?php
/**
 * help to look for users
 *
 * This script is the back-end part in a AJAX architecture. It processes
 * data received in the parameter 'q', look in the database for matching
 * users, and return an unordered list of keywords. If full name or e-mail
 * address has been set, it is provided as well.
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

// ensure browser always look for fresh data
http::expire(0);

// stop here on scripts/validate.php
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
	return;

// some input is mandatory
if(!isset($_REQUEST['q']) || !$_REQUEST['q']) {
	Safe::header('Status: 400 Bad Request', TRUE, 400);
	die(i18n::s('Request is invalid.'));
}

// just for sanity
$_REQUEST['q'] = preg_replace(FORBIDDEN_IN_NAMES, '_', $_REQUEST['q']);

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Forbidden', TRUE, 401);
	die(i18n::s('You are not allowed to perform this operation.'));
}

// we return some text
$output = '';

// look for matching items
$items = Users::search($_REQUEST['q'], 0, 50, 'complete');

// build an unordered list
if(count($items)) {
	$output .= '<ul>'."\n";

	foreach($items as $label => $more) {
		$output .= "\t".'<li>'.$label;

		// append contextual information, if any --specific to scriptaculous
		if($more)
			$output .= '<span class="informal details"> -&nbsp;'.$more.'</span>';

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