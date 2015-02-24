<?php

/**
 * Move a file to a new anchor
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
if(!isset($_REQUEST['file']) && !isset($_REQUEST['target'])) {
	Safe::header('Status: 400 Bad Request', TRUE, 400);
	die(i18n::s('Request is invalid.'));
}

// préparation de la variable de sortie
$output = array('success' => null);

// get file to move
$file = new File(files::get($_REQUEST['file']));
// get target
$target = Anchors::get($_REQUEST['target']);

// check again
if (!isset($file->item) || !isset($target->item)) {
	Safe::header('Status: 400 Bad Request', TRUE, 400);
	die(i18n::s('Request is invalid.'));
}

// remember last parent
$last_parent = $file->anchor;

// check rights
if(!$file->is_assigned() || !$target->is_assigned()) {
    Safe::header('Status: 401 Unauthorized', TRUE, 401);
    die(i18n::s('You are not allowed to perform this operation.'));
}

// target may override work
if(isset($target->overlay) && is_callable(array($target->overlay,'hook'))) {
    $output['success'] = $target->overlay->hook('move_file_to',$file);
    $output['isAlias']   = 'true'; 
}
// regular move    
if($output['success'] === null) {

    // do it
    $fields = array('anchor' => $target->get_reference());
    if($file->item['thumbnail_url']) {
        // set new thumbnail url
        $fields['thumbnail_url'] = $context['url_to_master'].$context['url_to_root'].Files::get_path($target->get_reference()).'/thumbs/'.urlencode($file->item['file_name']);
    }
    $output['success'] = $file->set_values($fields);

    // move file physicaly
    if($output['success']) {

        $from   = $context['path_to_root'].Files::get_path($last_parent->get_reference()).'/'.$file->item['file_name'];
        $dir    = $context['path_to_root'].Files::get_path($target->get_reference());
        $to     = $dir.'/'.$file->item['file_name'];

        // check that dir exists
        if(!is_dir($dir)) Safe::mkdir ($dir);
        Safe::rename($from, $to);

        // move thumb if any
        if($file->item['thumbnail_url']) {
            $from   = Files::get_path($last_parent->get_reference()).'/thumbs/'.$file->item['file_name'];
            // make directory thumbs
            $to     = $dir.'/thumbs/'.$file->item['file_name'];
            // check that dir exist
            if(!is_dir($dir.'/thumbs')) Safe::mkdir ($dir.'/thumbs');

            Safe::rename($from, $to);
        }
    }
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
