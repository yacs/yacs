<?php

/* 
 * Alexis Raimbault
 * 
 * this page to search about ajax upload
 * loads file in temporary folder
 */

// common definitions and initial processing
include_once '../shared/global.php';

// Output JSON
function outputJSON($msg, $status = 'error'){
    header('Content-Type: application/json');
    die(json_encode(array(
        'data' => $msg,
        'status' => $status
    )));
}


if(isset($_FILES['SelectedFile']) && count($_FILES['SelectedFile'])) {
        // Check for errors
        if($_FILES['SelectedFile']['error'] > 0){
            outputJSON('An error ocurred when uploading.');
        }

        /*if(!getimagesize($_FILES['SelectedFile']['tmp_name'])){
            outputJSON('Please ensure you are uploading an image.');
        }*/

        // Check filetype
        /*if($_FILES['SelectedFile']['type'] != 'image/png'){
            outputJSON('Unsupported filetype uploaded.');
        }*/

        // Check filesize
        /*if($_FILES['SelectedFile']['size'] > Safe::get_cfg_var('upload_max_filesize')){
            outputJSON('File uploaded exceeds maximum upload size.');
        }*/

        // Check if the file exists
        if(file_exists('temporary/' . $_FILES['SelectedFile']['name'])){
            outputJSON('File with that name already exists.');
        }

        // Upload file
        if(!move_uploaded_file($_FILES['SelectedFile']['tmp_name'], $context['path_to_root'].'temporary/' . $_FILES['SelectedFile']['name'])){
            outputJSON('Error uploading file - check destination is writeable.');
        }

        // Success!
        outputJSON('File uploaded successfully to "' . 'temporary/' . $_FILES['SelectedFile']['name'] . '".', 'success');

}

/////// RENDERING A PAGE

// load localized strings
i18n::bind('tools');

// load the skin
load_skin('tools');

// the path to this page
$context['path_bar'] = array( 'tools/' => i18n::s('Tools') );

// page title
$context['page_title'] = 'developping ajax upload of a file';

Page::insert_style("
            .container {
                width: 500px;
                margin: 0 auto;
            }
            .progress_outer {
                border: 1px solid #000;
            }
            .progress {
                width: 20%;
                background: #DEDEDE;
                height: 20px;  
            }
    ");

$context['text'] = "<div class='container'>
                    <p>
                        Select File: <input type='file' id='_file'> <input type='button' id='_submit' value='Upload!'>
                    </p>
                    <div class='progress_outer'>
                        <div id='_progress' class='progress'></div>
                    </div>
                </div>";

Page::defer_script('tools/ajax_upload.js');


// render the page according to the loaded skin
render_skin();
