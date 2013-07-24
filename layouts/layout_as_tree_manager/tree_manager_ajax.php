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

switch($_REQUEST['action']) {
    
    case 'create':
	// reference to anchor and new title are mandatory
	if(!isset($_REQUEST['anchor']) || !$_REQUEST['anchor'] 
		|| !isset($_REQUEST['title']) || !$_REQUEST['title'])
	    die_on_invalid ();
	
	list($type,$anchor_id) = explode(":", $_REQUEST['anchor']);
	
	if($anchor_id =='index')	    
	    $_REQUEST['anchor'] = '';
	    
	// create obj interface
	$newitem = new $type();
	
	// try to post 
	if($output['success'] = $newitem->post($_REQUEST['anchor'],$_REQUEST['title'])) {				   
	    
		$output['title'] = $newitem->get_title();
		$output['ref']	= $newitem->get_reference();	   
	}		
	
	break;
    case 'delete':
	// reference to anchor is mandatory
	if(!isset($_REQUEST['anchor']) || !$_REQUEST['anchor'])
	    die_on_invalid ();	
	
	// get obj interface
	$to_delete = Anchors::get($_REQUEST['anchor']);
	
	if(!$to_delete)
	    $output['success'] = false;
	else
	    $output['success'] = $to_delete->delete();	    
	
        break;
    case 'move':
	// reference to object and target are mandatory
	if(!isset($_REQUEST['obj']) || !$_REQUEST['obj']
		|| !isset($_REQUEST['tar']) || !$_REQUEST['tar'] )
	    die_on_invalid ();
	
	
	// get the objects
	$obj = Anchors::get($_REQUEST['obj']);
	if(!preg_match('/index^/', $_REQUEST['tar']))	    
	  $tar = Anchors::get($_REQUEST['tar']);
	else
	  $_REQUEST['tar'] = '';  // empty anchor means index
	
	
	if(!is_object($obj) || !isset($tar)) {
	    // anchors not founded
	    $output['success'] = false;	    	    
	} elseif ($_REQUEST['obj'] == $_REQUEST['tar'] || $_REQUEST['tar'] == $obj->item['anchor']) {
	    // wrong move : to itself or same parent
	    $output['success'] = false;
	} else {

	    //TODO : check surfer permission	

	    // set the new anchor (=parent)
	    $fields = array('anchor' => $_REQUEST['tar']);

	    // save in database	
	    $output['success'] = $obj->set_values($fields); 
	
	}		
	
	break;
    case 'zoom' :
	// reference to anchor is mandatory
	if(!isset($_REQUEST['anchor']) || !$_REQUEST['anchor'])
	    die_on_invalid ();
	
	if(!$anchor = Anchors::get($_REQUEST['anchor'])) {
	    $output['success'] = false;
	    break;
	}
	// warn other script this is a ajax request
	$context['AJAX_REQUEST'] = true;		
	
	// tell other scripts who we are viewing
	$context['current_item'] = $anchor->get_reference();
	
	// this is rendering operation, we may need some constants & functions
	load_skin();
	
	// get the content
	$childs = $anchor->get_childs($anchor->get_type(), 0, 200, 'tree_manager');
	if(isset($childs[$anchor->get_type()])) {		
		$output['success'] = true;
		$output['content'] = $childs[$anchor->get_type()];		
		$output['title'] = $anchor->get_title();
		$output['crumbs_separator'] = CRUMBS_SEPARATOR;
		$output['crumbs_suffix'] = CRUMBS_SUFFIX;
	} else 
	    $output['success'] = false;
	    	
	break;
    default:
	// unknown action
	die_on_invalid ();	    	
    
}

render_raw('application/json');
$output = json_encode($output);

// actual transmission 
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
	echo $output;

// the post-processing hook, then exit
finalize_page(TRUE);
?>
