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

// some input is mandatory
if(!isset($_REQUEST['action']) || !$_REQUEST['action']) {
    Safe::header('Status: 400 Bad Request', TRUE, 400);
    die(i18n::s('Request is invalid.'));
}

// stop unauthorized
if(!Surfer::is_logged()) {
	Safe::header('Status: 401 Unauthorized', TRUE, 401);
	die(i18n::s('You are not allowed to perform this operation.'));
}

$output = FALSE;

switch($_REQUEST['action']) {
    
    case 'move':
	// object and target are mandatory
	if(!isset($_REQUEST['obj']) || !$_REQUEST['obj']
		|| !isset($_REQUEST['tar']) || !$_REQUEST['tar'] ) {
	    Safe::header('Status: 400 Bad Request', TRUE, 400);
	    die(i18n::s('Request is invalid.'));
	}
	
	// get the object
	$obj = Anchors::get($_REQUEST['obj']);
	
	//TODO : anchor not founded
	//TODO : check surfer permission
	
	// set the new anchor (parent)
	$fields = array('anchor' => $_REQUEST['tar']);
	
	// save in database	
	$output = $obj->set_values($fields); 
	
	break;
    default:
	// unknown action
	Safe::header('Status: 400 Bad Request', TRUE, 400);
	die(i18n::s('Request is invalid.'));	    	
    
}

// allow for data compression
render_raw();

// actual transmission 
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
	echo $output;

// the post-processing hook, then exit
finalize_page(TRUE);
?>
