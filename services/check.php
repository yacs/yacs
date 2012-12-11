<?php
/**
 * check updates
 *
 * @see articles/view.php
 * @see articles/view_as_chat.php
 *
 * Accept following invocations:
 * - check.php/12 (visit article #12)
 * - check.php/article/12 (visit article #12)
 * - check.php/section/2 (visit section #2)
 * - check.php?id=12 (visit article #12)
 * - check.php?id=article:12 (visit article #12)
 * - check.php?id=section:2 (visit section #2)
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

// look for the anchor reference
$anchor = NULL;
if(isset($_REQUEST['id']))
	$anchor = $_REQUEST['id'];
elseif(isset($context['arguments'][1]))
	$anchor = $context['arguments'][0].':'.$context['arguments'][1];
elseif(isset($context['arguments'][0]))
	$anchor = $context['arguments'][0];
$anchor = strip_tags($anchor);

// default anchor type is article
if(!strpos($anchor, ':'))
	$anchor = 'article:'.$anchor;

// get the related anchor, if any
if($anchor)
	$anchor = Anchors::get($anchor);

// an anchor is mandatory
if(!is_object($anchor)) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	die(i18n::s('No anchor has been found.'));

// the anchor has to be viewable by this surfer
} elseif(is_object($anchor) && !$anchor->is_viewable()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	die(i18n::s('You are not allowed to perform this operation.'));

// robots cannot contribute
} elseif(isset($_REQUEST['message']) && Surfer::may_be_a_robot()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	die(i18n::s('You are not allowed to perform this operation.'));

// check time stamp
} else {
	$response = $anchor->check();

	// encode result in JSON
	$output = Safe::json_encode($response);

	// allow for data compression
	render_raw('application/json; charset='.$context['charset']);

	// actual transmission except on a HEAD request
	if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
		echo $output;

	// the post-processing hook, then exit
	finalize_page(TRUE);

}

?>
