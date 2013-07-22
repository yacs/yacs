<?php
/**
 * traitement for ajax requests sent from Tree Manager layout
 * 
 * @author : Alexis raimbault
 * @reference
 */

// common definitions and initial processing
include_once '../../shared/global.php';

// ensure browser always look for fresh data
http::expire(0);

// stop here on scripts/validate.php
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
	return;

// stop unauthorized
if(!Surfer::is_logged()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	die(i18n::s('You are not allowed to perform this operation.'));
}

function die_on_invalid() {
    Safe::header('Status: 400 Bad Request', TRUE, 400);
    die(i18n::s('Request is invalid.'));    
}

// some input is mandatory
if(!isset($_REQUEST['action']) || !$_REQUEST['action'])
    die_on_invalid;

$result = false;

switch($_REQUEST['action']) {
    
    case 'move':
	// reference to object and target are mandatory
	if(!isset($_REQUEST['obj']) || !$_REQUEST['obj']
		|| !isset($_REQUEST['tar']) || !$_REQUEST['tar'] )
	    die_on_invalid ();
	
	
	// get the objects
	$obj = Anchors::get($_REQUEST['obj']);
	if($_REQUEST['tar'] != 'index')	    
	  $tar = Anchors::get($_REQUEST['tar']);
	else
	  $_REQUEST['tar'] = '';  // empty anchor means index
	
	// anchors not founded
	if(!is_object($obj) || !isset($tar))
	    die_on_invalid ();
	
	// wrong move : to itself or same parent
	if($_REQUEST['obj'] == $_REQUEST['tar'] || $_REQUEST['tar'] == $obj->item['anchor'])
	    break;

	//TODO : check surfer permission	
	
	// set the new anchor (=parent)
	$fields = array('anchor' => $_REQUEST['tar']);
	
	// save in database	
	$result = $obj->set_values($fields); 
	
	break;
    default:
	// unknown action
	die_on_invalid ();	    	
    
}

// allow for data compression
render_raw('application/json');

$output = json_encode(array('success' => $result));

// actual transmission 
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
	echo $output;

// the post-processing hook, then exit
finalize_page(TRUE);
?>
