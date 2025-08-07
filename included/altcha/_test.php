<?php

/** 
 * test altcha integration
 * 
 * @æuthor devalxr
 */

// common definitions and initial processing
include_once '../../shared/global.php';
include_once $context['path_to_root'].'included/altcha/yaltcha.php';

// do not index this page
$context->sif('robots','noindex');

// load the skin -- parameter enables to load an alternate template, if any -- see function definition in shared/global.php
load_skin('altcha');

$context['page_title'] = 'test ALTCHA ANTIBOT';


// process uploaded data
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'POST')) {
 
    $is_robot = !yaltcha::verify_challenge();
    if($is_robot) {
        $context['text'] .= 'YOU SHALL NOT PASS !';
    } else {
        $context['text'] .= 'WELCOME MY LORDS, TO ISENGARD';
    }
    
 // display the form
} else {
    
    $fields = array();
    
    $context['text'] .= tag::_p('Test du Altcha');
    
    // where to upload data
    $context['text'] .= '<form method="post" action="'.$context['script_url'].'" id="main_form"><div>';
    $input = yaltcha::insert_widget();
    $fields[] = array('', $input, '');
    
    // build the form
    $context['text'] .= Skin::build_form($fields);
    
    // the submit button
    $context['text'] .= '<p>'.Skin::build_submit_button(i18n::s('Submit')).'</p>'."\n";

    // end of the form
    $context['text'] .= '</div></form>';
    
    
    yaltcha::loadjs();
}

// render the page according to the loaded skin
render_skin();