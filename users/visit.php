<?php
/**
 * manage presence information
 *
 * This script can be invoked remotely in AJAX to record on-going visits and
 * to list current visitors.
 *
 * On error it will provide both a HTTP status code, and explanations in HTML.
 * Else a 200 status code is provided, and a list of current visitors is provided.
 *
 * Accept following invocations:
 * - visit.php/12 (visit article #12)
 * - visit.php/article/12 (visit article #12)
 * - visit.php/section/2 (visit section #2)
 * - visit.php?id=12 (visit article #12)
 * - visit.php?id=article:12 (visit article #12)
 * - visit.php?id=section:2 (visit section #2)
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'visits.php';

// ensure browser always look for fresh data
http::expire(0);

// stop here on scripts/validate.php
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
	return;

// visited item
$anchor = NULL;
if(isset($_REQUEST['id']))
	$anchor = $_REQUEST['id'];
elseif(isset($context['arguments'][1]))
	$anchor = $context['arguments'][0].':'.$context['arguments'][1];
elseif(isset($context['arguments'][0]))
	$anchor = $context['arguments'][0];
$anchor = strip_tags($anchor);

// default anchor type is article
if($anchor && !strpos($anchor, ':'))
	$anchor = 'article:'.$anchor;

// get the related anchor, if any
if($anchor)
	$anchor = Anchors::get($anchor);

// required to format the roster
load_skin('users');

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	Logger::error(i18n::s('You are not allowed to perform this operation.'));

// an anchor is mandatory
} elseif(!is_object($anchor)) {
	Safe::header('Status: 404 Not Found', TRUE, 404);
	die(i18n::s('No anchor has been found.'));

// provide updated information for this anchor
} else {

	// silently record this visit
	Visits::track($anchor->get_reference(), $anchor->get_active());

	// return an updated list of current visitors, to be used in AJAX
	$output = Visits::list_users_at_anchor($anchor->get_reference());

	// ensure we are producing some text -- open links in separate pages
	if(is_array($output))
		$output = Skin::build_list($output, 'compact', NULL, TRUE);

	// actual transmission except on a HEAD request
	if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
		echo $output;

	// the post-processing hook, then exit
	finalize_page(TRUE);

}

?>
