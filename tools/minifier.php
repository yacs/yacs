<?php

/* 
 * Minify a script
 * meant to be called by proceed_bckg
 * @see shared/js_css.php:link_file()
 * 
 * @author Alexis Raimbault
 * @reference;
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

include_once '../shared/global.php';

// we need only js_css, safe and logger classes
include_once '../shared/js_css.php';
include_once '../shared/safe.php';
include_once '../shared/logger.php';

// some input is mandatory
if(!isset($_REQUEST['script']) || !$_REQUEST['script']) {
    echo 'please provide a script path';
    die;
} else
    $script = $_REQUEST['script'];

// do the job
$writen = Js_css::minify($script);

if($writen) {
    logger::remember('minification',$script);
    echo 'job done';
} else {
    echo 'nothing done'; 
}
