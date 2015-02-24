<?php

/**
 * Sort files in a given manual order
 * from a ajax request
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

// stop crawlers
if(Surfer::is_crawler()) {
    Safe::header('Status: 401 Unauthorized', TRUE, 401);
    die(i18n::s('You are not allowed to perform this operation.'));
}

// some input is mandatory
if(!isset($_REQUEST['ranks']) || !is_array($_REQUEST['ranks']) || !isset($_REQUEST['anchor'])) {
	Safe::header('Status: 400 Bad Request', TRUE, 400);
	die(i18n::s('Request is invalid.'));
}

// get anchor
if(!$anchor = Anchors::get($_REQUEST['anchor'])) {
        Safe::header('Status: 400 Bad Request', TRUE, 400);
        die(i18n::s('Anchor is invalid.'));
}

// préparation de la variable de sortie
$output = array('success' => null);

// anchor may override work
if(isset($anchor->overlay) && is_callable(array($anchor->overlay,'hook'))) {
    $output['success'] = $anchor->overlay->hook('sort_file',$_REQUEST['ranks']);
}

// regular sorting
if($output['success'] === null) {
    // be optimist
    $success = true;
    // loop on files to sort
    foreach($_REQUEST['ranks'] as $ref => $rank) {

        if(!$file= Anchors::get($ref)) {
            $success = false;
            continue;
        }

        $fields = array('rank'=>($rank+1)*10);
        $success = $success && $file->set_values($fields);

    }
    
    $output['success'] = $success;
}

// we return some JSON
$output = json_encode($output);

// allow for data compression
render_raw('application/json; charset='.$context['charset']);

// actual transmission except on a HEAD request
if(!isset($_SERVER['REQUEST_METHOD']) || ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
	echo $output;

// the post-processing hook, then exit
finalize_page(TRUE);