<?php
/**
 * Receiving ajax requests sent from Tree Manager layout 
 * javascript interface
 * @see layouts/layouts_as_tree_manager/
 * 
 * all replies are JSON formated
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

// will be used further many times
function die_on_invalid() {
    Safe::header('Status: 400 Bad Request', TRUE, 400);
    die(i18n::s('Request is invalid.'));    
}

// action input is always mandatory
if(!isset($_REQUEST['action']) || !$_REQUEST['action'])
    die_on_invalid;

// diffrent things to do depending on "action"
switch($_REQUEST['action']) {
    
    // assign or free a anchor to a category
    case 'bind':
	// reference to object and target are mandatory
	if(!isset($_REQUEST['anchor']) || !$_REQUEST['anchor']
		|| !isset($_REQUEST['cat']) || !$_REQUEST['cat'] )
	    die_on_invalid ();
	
	// assign or free ? assign by default
	$way = (isset($_REQUEST['way']))?$_REQUEST['way']:'assign';
	
	// get object interface
	$anchor = Anchors::get($_REQUEST['anchor']);
	$cat =Anchors::get($_REQUEST['cat']);
	
	// check
	if(!is_object($anchor) && !is_object($cat) && $cat->get_type() != 'category') {
	    $output['success'] = false;
	    break;
	}
	
	if($way == 'assign')
	    $output['success'] = Members::assign ($cat->get_reference(), $anchor->get_reference ());	    
	else
	    $output['success'] = Members::free ($cat->get_reference(), $anchor->get_reference ());
		
	break;
	    
    // create a new object under a given anchor, same kind as anchor
    // this means to build a hierarchy, eg sections or categories
    case 'create':
	// reference to anchor and new title are mandatory
	if(!isset($_REQUEST['anchor']) || !$_REQUEST['anchor'] 
		|| !isset($_REQUEST['title']) || !$_REQUEST['title'])
	    die_on_invalid ();
	
	// get type of anchor from given reference
	list($type,$anchor_id) = explode(":", $_REQUEST['anchor']);
	
	// 'index' is keyword used by the layout to point out the root,
	// witch is a empty string in database.
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
	
    // delete the anchor with the given reference	
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
    
    // move an anchor under another	
    case 'move':
	// reference to object and target are mandatory
	if(!isset($_REQUEST['anchor']) || !$_REQUEST['anchor']
		|| !isset($_REQUEST['tar']) || !$_REQUEST['tar'] )
	    die_on_invalid ();
	
	
	// get the object to move
	$obj = Anchors::get($_REQUEST['anchor']);
	// get the target object, exept if it is root
	if(!preg_match('/index^/', $_REQUEST['tar']))	    
	  $tar = Anchors::get($_REQUEST['tar']);
	else
	  $_REQUEST['tar'] = '';  // empty anchor means root
	
	
	if(!is_object($obj) || !isset($tar)) {
	    // anchors not founded
	    $output['success'] = false;	    	    
	} elseif ($_REQUEST['anchor'] == $_REQUEST['tar'] || $_REQUEST['tar'] == $obj->item['anchor']) {
	    // wrong move : to itself or same parent, nothing to do
	    $output['success'] = false;
	} else {	    

	    // set the new anchor (=parent)
	    $fields = array('anchor' => $_REQUEST['tar']);

	    // save in database	
	    $output['success'] = $obj->set_values($fields); 	
	}		
	
	break;
    
    // rename a anchor with a given title
    case 'rename':
	// reference to anchor and new title are mandatory
	if(!isset($_REQUEST['anchor']) || !$_REQUEST['anchor'] 
		|| !isset($_REQUEST['title']) || !$_REQUEST['title'])
	    die_on_invalid ();
	
	// get obj interface
	$to_rename = Anchors::get($_REQUEST['anchor']);
	
	if(!$to_rename) {
	    $output['success'] = false;
	    break;
	}
	
	// set the new title
	$fields = array('title' => $_REQUEST['title']);
	    
	// save in database	
	$output['success'] = $to_rename->set_values($fields); 	
	
	break;
    
    // render tree hierachy form a given anchor, say any anchor under the one
    // the rendering started
    case 'zoom' :
	// reference to anchor is mandatory
	if(!isset($_REQUEST['anchor']) || !$_REQUEST['anchor'])
	    die_on_invalid ();
	
	// get the obj interface
	if(!$anchor = Anchors::get($_REQUEST['anchor'])) {
	    $output['success'] = false;
	    break;
	}
	// warn other script this is a ajax request
	$context['AJAX_REQUEST'] = true;		
	
	// tell other scripts what we are viewing
	$context['current_item'] = $anchor->get_reference();
	
	// this is a rendering operation, we may need some
	// constants & functions defined in skin
	load_skin();
	
	// layout the content under this anchor, searching the same kind of objects
	// we are building a tree hierarchy (sections or categories)
	$childs = $anchor->get_childs($anchor->get_type(), 0, 200, 'tree_manager');
	
	// prepare json reply
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

// output is JSON formated
render_raw('application/json');
$output = json_encode($output);

// actual transmission 
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
	echo $output;

// the post-processing hook, then exit
finalize_page(TRUE);
?>
