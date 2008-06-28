<?php
/**
 * handle user presence in the background
 *
 * This script is a AJAX back-end, that can jointly receive and process new
 * notifications, or transmit live notifications to present surfers.
 *
 * @see shared/yacs.js
 *
 * The minimum period of time to call this script should be some seconds,
 * because of potential network latency.
 *
 * The maximum period of time between calls should be 120 seconds,
 * since notifications die after 180 seconds anyway.
 *
 * Notifications are described in [script]users/notifications.php[/script], and
 * the AJAX front-end has been implemented in shared/yacs.js
 *
 * This script is also used to refresh session data. This is an effective way
 * to preserve idle sessions at some rough ISP.
 *
 * Accept following invocations:
 * - heartbit.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';
include_once 'notifications.php';

// ensure browser always look for fresh data
Safe::header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
Safe::header("Cache-Control: no-store, no-cache, must-revalidate");
Safe::header("Cache-Control: post-check=0, pre-check=0", false);
Safe::header("Pragma: no-cache");

// surfer has to be logged --provide a short response
if(!Surfer::get_id()) {
	Logger::profile_dump();

	Safe::header('Status: 403 Forbidden', TRUE, 403);
	die(i18n::s('You are not allowed to perform this operation.'));

// a new notification has been submitted
} elseif(isset($_REQUEST['recipient']) && isset($_REQUEST['type'])) {

	// record the notification
	$fields = array();
	$fields['nick_name'] = Surfer::get_name();
	$fields['recipient'] = $_REQUEST['recipient'];
	$fields['type'] = $_REQUEST['type'];

	if(isset($_REQUEST['address']))
		$fields['address'] = preg_replace(FORBIDDEN_CHARS_IN_URLS, '_', $_REQUEST['address']);
	if(isset($_REQUEST['message']))
		$fields['message'] = strip_tags($_REQUEST['message']);

	// vaidate notification attributes
	switch($fields['type']) {

	case 'browse':
		if(!isset($_REQUEST['address'])) {
			Safe::header('Status: 400 Bad Request', TRUE, 400);
			die(i18n::s('Request is invalid.'));
		}
		break;

	case 'hello':
		if(!isset($_REQUEST['message'])) {
			Safe::header('Status: 400 Bad Request', TRUE, 400);
			die(i18n::s('Request is invalid.'));
		}
		break;

	default:
		Safe::header('Status: 400 Bad Request', TRUE, 400);
		die(i18n::s('Request is invalid.'));

	}

	// save in the database
	Notifications::post($fields);

	// thread update will trigger screen repaint through separate pending call of this script
	die('OK');

// look for some notification
} elseif(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD')) {

	// change session data to extend life of related file
	if(!isset($_SESSION['heartbit']))
		$_SESSION['heartbit'] = 0;
	$_SESSION['heartbit']++;

	// refresh the watchdog
	$_SESSION['watchdog'] = time();

	// update surfer presence
	$query = "UPDATE ".SQL::table_name('users')
		." SET click_date='".gmstrftime('%Y-%m-%d %H:%M:%S')."'"
		." WHERE (id = ".SQL::escape(Surfer::get_id()).")";
	SQL::query($query, FALSE, $context['users_connection']);

	// look for one notification -- script will be be killed if none is available
	$response =& Notifications::pull();

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