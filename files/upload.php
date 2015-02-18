<?php

/* 
 * Alexis Raimbault
 * 
 * this page to search about ajax upload
 * loads file in temporary folder
 */

// common definitions and initial processing
include_once '../shared/global.php';

// ensure browser always look for fresh data
http::expire(0);

// lang
i18n::bind('files');

// stop here on scripts/validate.php
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
    return;

// stop if forbidden
if(Surfer::is_crawler() || !Surfer::may_upload()) {
    Safe::header('Status: 401 Unauthorized', TRUE, 401);
    die(i18n::s('You are not allowed to perform this operation.'));
}

// some input is mandatory
if(!isset($_REQUEST['name'])) {
	Safe::header('Status: 400 Bad Request', TRUE, 400);
	outputJSON(i18n::s('Request is invalid.'));
} else
    $name = $_REQUEST['name'];

$action = (isset($_REQUEST['action']))?$_REQUEST['action']:'';

// Output JSON
function outputJSON($msg, $status = 'error', $preview = ''){
    global $context;
    
    Js_css::prepare_scripts_for_overlaying();
    $js = $context['javascript']['footer'];
    
    header('Content-Type: application/json');
    die(json_encode(array(
        'data'     => $msg,
        'status'   => $status,
        'preview'  => $preview,
        'js'       => $js
          
    )));
}

load_skin();

// we need a file
if(isset($_FILES[$name]) && count($_FILES[$name])) {
        // Check for errors
        if($_FILES[$name]['error'] > 0){
	    Safe::header('Status: Internal 500 server error', TRUE, 500);
            outputJSON(i18n::s('An error ocurred when uploading.'));
        }

        /*if(!getimagesize($_FILES['SelectedFile']['tmp_name'])){
            outputJSON('Please ensure you are uploading an image.');
        }*/

        // Check filetype
        if(!Files::is_authorized($_FILES[$name]['name'])){
	    Safe::header('Status: 415 Unsupported media', TRUE, 415);
            outputJSON(i18n::s('Unsupported filetype uploaded.'));
        }

        // Check filesize
        /*if($_FILES['SelectedFile']['size'] > Safe::get_cfg_var('upload_max_filesize')){
            outputJSON('File uploaded exceeds maximum upload size.');
        }*/

        // Check if the file exists
        if(file_exists('temporary/' . $_FILES[$name]['name'])){
	    Safe::header('Status: 500 Internal server error', TRUE, 500);
            outputJSON(i18n::s('File with that name already exists in temporary folder.'));
        }

        // Upload file
	$path = $context['path_to_root'].'temporary/' . $_FILES[$name]['name'];
        if(!Safe::move_uploaded_file($_FILES[$name]['tmp_name'], $path)){
	    Safe::header('Status: 500 Internal server error', TRUE, 500);
            outputJSON(i18n::s('Error uploading file - check destination is writeable.'));
	    
        } else {
	
	    // memorize info about uploaded file
	    $_SESSION['last_uploaded'][$name]			= $_FILES[$name];
	    $_SESSION['last_uploaded'][$name]['tmp_name']	= $path;
            // @see safe::is_uploaded_file()
	    $_SESSION['last_uploaded']['pathes'][]		= $path;
            
            $preview = Files::preview($path, $name);
	    
	    // Success!
	    outputJSON('File uploaded successfully to "' . 'temporary/' . $_FILES[$name]['name'] . '".', 'success', $preview);
	}

        

} // destroy just uploaded file
  elseif($action === 'destroy') {
      
    if($name === "all") {
        foreach($_SESSION['last_uploaded'] as $up) {
            // destroy file
            if(isset($up['tmp_name']))
                Safe::unlink($up['tmp_name']);
        }
        unset($_SESSION['last_uploaded']);
        outputJSON(i18n::s('all temporary file destroyed'),'success');
    }  
      
    if(isset($_SESSION['last_uploaded'][$name])) {
        $filename = $_SESSION['last_uploaded'][$name]['name'];
        // destroy file
        Safe::unlink($_SESSION['last_uploaded'][$name]['tmp_name']);
        // destroy session memory
        unset($_SESSION['last_uploaded'][$name]);
        outputJSON(sprintf(i18n::s('temporary file %s destroyed'),$filename),'success', Skin::build_input_file($name));
    }
}


////// no direct access
Safe::header('Status: 401 Unauthorized', TRUE, 401);
Logger::error(i18n::s('You are not allowed to perform this operation.'));

render_skin();
