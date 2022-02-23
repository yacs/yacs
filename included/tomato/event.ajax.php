<?php

/** 
 * To be called remotely by ajax, to record an event in matomo
 * @see matomo.event.js
 * @see tracker.php
 * 
 * File has to be in /included/tomato and not /included/matomo
 * otherwise browser's extension like ublock will block the request
 * 
 * @author devalxr
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

include_once '../../shared/global.php';
include_once 'tracker.php';

// ensure browser always look for fresh data
http::expire(0);

// stop here on scripts/validate.php
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
	return;

// some input is mandatory
if(!isset($_REQUEST['category']) || !$_REQUEST['category']) {
	Safe::header('Status: 400 Bad Request', TRUE, 400);
	die('Request is invalid.');
}

if(!isset($_REQUEST['action']) || !$_REQUEST['action']) {
	Safe::header('Status: 400 Bad Request', TRUE, 400);
	die('Request is invalid.');
}

if(!isset($_REQUEST['name'])) $_REQUEST['name'] = false;
if(!isset($_REQUEST['value'])) $_REQUEST['value'] = false;

$tracker    = new tracker();
$job        = $tracker->trackEvent($_REQUEST['category'], $_REQUEST['action'], $_REQUEST['name'], $_REQUEST['value']);

$output = array('result' => $job);

// allow for data compression
render_raw('application/json; charset='.$context['charset']);

// actual transmission except on a HEAD request
if(!isset($_SERVER['REQUEST_METHOD']) || ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
	echo json_encode($output);

die();