<?php

/**
 * autologin to compagnon virtual domains 
 *
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// ensure browser always look for fresh data
http::expire(0);

// stop unauthorized
if(Surfer::is_crawler()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	die(i18n::s('You are not allowed to perform this operation.'));
}

// we will send a JSON reply
$reply = array();
// no login by default
$reply['login'] = false;

if ( isset($_SESSION['cross_domain_login_required']) && $_SESSION['cross_domain_login_required'] == true ) {

    $domain	= $context['host_name'];
    if(($parent_domain = strstr($domain, '.')) && strpos($parent_domain, '.', 1))
	$domain = $parent_domain;

    $hosts	= $context['virtual_domains'];
    $hosts[]	= $context['master_host'];
    
    // http or https ?
    $protocol = "http://";
    if(isset($_SERVER['SERVER_PORT']) && ($_SERVER['SERVER_PORT'] == 443)) {
        $protocol = "https://";
    }

    foreach ($hosts as $host) {

	if($host == $domain) continue;

	// list url to target
	$reply['domains'][] = $protocol.$host.$context['url_to_root'];

    }

    // remember origin domain
    $reply['origin']	= $domain;
    // provide session id
    $reply['sessid']	= session_id();
    // yes, something as to be done
    $reply['login']	= true;
}

// output is JSON formated
render_raw('application/json');
$reply = json_encode($reply);

// actual transmission
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
	echo $reply;

?>
