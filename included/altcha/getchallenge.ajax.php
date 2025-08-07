<?php

/** 
 * URL endpoint for altcha js widget to get a challenge
 * 
 * reply with JSON
 * 
 * @reference
 * @author devalxr
 */

// common definitions and initial processing
include_once '../../shared/global.php';

include_once $context['path_to_root'].'included/altcha/yaltcha.php';

// generate a challenge 
$challenge = yaltcha::get_challenge();

$output = $challenge;

// allow for data compression
render_raw('application/json; charset='.$context['charset']);

// actual transmission except on a HEAD request
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
	echo $output;