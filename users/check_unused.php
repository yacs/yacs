<?php
/**
 * Check if a nickname or email is already used in database
 *
 * This script is the back-end part in a AJAX architecture. It processes
 * data received in the parameter 'nickname' or 'email', look in the database for matching
 * users, and return TRUE if it's unused, FALSE otherwise.
 *
 * Accept following invocations:
 * - check_unused.php?nick=jhon
 * - check_unused.php?email=...
 *
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
if(!isset($_REQUEST['nick_name']) && !isset($_REQUEST['email'])) {
	Safe::header('Status: 400 Bad Request', TRUE, 400);
	die(i18n::s('Request is invalid.'));
}

// stop crawlers
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	die(i18n::s('You are not allowed to perform this operation.'));
}

// we return some text
$output = '';

// check syntax
$syntax = TRUE;
if(isset($_REQUEST['nick_name']) && (preg_match(FORBIDDEN_IN_NAMES, $_REQUEST['nick_name']) || !$_REQUEST['nick_name'])) {
        $syntax = FALSE;
        $searchin = 'nick_name';
} elseif (isset($_REQUEST['email']) && (!preg_match(VALID_RECIPIENT, $_REQUEST['email']) || !$_REQUEST['email'])) {
        $syntax = FALSE;
         $searchin   = 'email';
}

if($syntax) {

    if(isset($_REQUEST['nick_name'])) {
        $searchin = 'nick_name';
        $searchfor = $_REQUEST['nick_name'];
        $search_label = i18n::s('nick name');
    } else {
        $searchin   = 'email';
        $searchfor  = $_REQUEST['email'];
        $search_label = i18n::s('e-mail');
    }

    $query = "SELECT id FROM ".SQL::table_name('users')." WHERE ".$searchin
            ." = '".$searchfor."'";

    $found = SQL::query_first($query);

    if($found) {

        $output['can']      = false;
        $output['message']  = sprintf(i18n::s('Sorry this %s is already used.'),$search_label);
    } else {

        $output['can']      = true;
        $output['message'] = i18n::s('Ok, you can use this');
    }
} else {
        // bad syntax
        $output['can']      = false;
        if($searchin === 'nick_name')
            $output['message']  = i18n::s('Sorry some characters are forbidden here.');
        else
            $output['message']  = i18n::s('Incomplete or illegal character used');
}

$output = json_encode($output);

// allow for data compression
render_raw('application/json; charset='.$context['charset']);

// actual transmission except on a HEAD request
if(!isset($_SERVER['REQUEST_METHOD']) || ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
	echo $output;

// the post-processing hook, then exit
finalize_page(TRUE);

?>
