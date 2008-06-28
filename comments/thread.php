<?php
/**
 * manage contributions to a thread
 *
 * @see articles/view_as_thread.php
 *
 * Accept following invocations:
 * - thread.php/12 (visit article #12)
 * - thread.php/article/12 (visit article #12)
 * - thread.php/section/2 (visit section #2)
 * - thread.php?id=12 (visit article #12)
 * - thread.php?id=article:12 (visit article #12)
 * - thread.php?id=section:2 (visit section #2)
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'comments.php';

// ensure browser always look for fresh data
Safe::header("Cache-Control: no-cache, must-revalidate");
Safe::header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

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

load_skin('comments');

// an anchor is mandatory
if(!is_object($anchor)) {
	Logger::profile_dump();

	Safe::header('Status: 404 Not Found', TRUE, 404);
	die(i18n::s('No anchor has been found.'));

// the anchor has to be viewable by this surfer
} elseif(is_object($anchor) && !$anchor->is_viewable()) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	die(i18n::s('You are not allowed to perform this operation.'));

// robots cannot contribute
} elseif(isset($_REQUEST['message']) && Surfer::may_be_a_robot()) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	die(i18n::s('You are not allowed to perform this operation.'));

// this anchor does not accept contributions
} elseif(isset($_REQUEST['message']) && is_object($anchor) && !Comments::are_allowed($anchor)) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	die(i18n::s('You are not allowed to perform this operation.'));

// a new contribution has been submitted
} elseif(isset($_REQUEST['message']) && trim($_REQUEST['message'])) {

	// sanitize the message
	$_REQUEST['message'] = str_replace(array("\r\n", "\r"), "\n", trim($_REQUEST['message']));

	// protect from hackers
	if(isset($_REQUEST['edit_name']))
		$_REQUEST['edit_name'] = preg_replace(FORBIDDEN_CHARS_IN_NAMES, '_', $_REQUEST['edit_name']);
	if(isset($_REQUEST['edit_address']))
		$_REQUEST['edit_address'] = preg_replace(FORBIDDEN_CHARS_IN_URLS, '_', $_REQUEST['edit_address']);

	// append to previous comment during 10 minutes
	$continuity_limit = gmstrftime('%Y-%m-%d %H:%M:%S', time() - 600);

	// this is the first contribution to the thread
	if(!$item = Comments::get_newest_for_anchor($anchor->get_reference())) {
		$fields = array();
		$fields['anchor'] = $anchor->get_reference();
		$fields['description'] = $_REQUEST['message'];

	// this is a continuated contribution from this authenticated surfer
	} elseif(Surfer::get_id() && (isset($item['create_id']) && (Surfer::get_id() == $item['create_id'])) && ($continuity_limit < $item['edit_date'])) {
		$item['description'] .= BR.$_REQUEST['message'];
		$fields = $item;

	// else process the contribution as a new comment
	} else {
		$fields = array();
		$fields['anchor'] = $anchor->get_reference();
		$fields['description'] = $_REQUEST['message'];

	}

	// actual database update
	if(!$fields['id'] = Comments::post($fields)) {
		Safe::header('Status: 500 Internal Error', TRUE, 500);
		die(i18n::s('Your contribution has not been posted.'));
	}

	// touch the related anchor
	$anchor->touch('comment:create', $fields['id']);

	// clear cache
	Comments::clear($fields);

	// we do not increment the post counter of the surfer during a chat
//	Users::increment_posts(Surfer::get_id());

	// thread update will trigger screen repaint through separate pending call of this script
	die('OK');

// get some updates
} else {

	// we are running
	global $pending;
	$pending = TRUE;

	// invoked on shutdown
	function on_shutdown() {
		global $pending;

		// we were waiting for changes, and this is an internal error
		if($pending && !headers_sent())
			header('Status: 504 Gateway Timeout', TRUE, 504);
	}

	// attempt to manage timeouts properly
	if(is_callable('register_shutdown_function'))
		register_shutdown_function('on_shutdown');

	// else wait for some update --on time out, browser will recall us anyway
	$response =& Comments::pull($anchor->get_reference(), isset($_REQUEST['timestamp']) ? gmstrftime('%Y-%m-%d %H:%M:%S', $_REQUEST['timestamp']) : 0);

	// shutdown is not an error anymore
	$pending = FALSE;

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