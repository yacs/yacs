<?php
/**
 * break a session
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once '../shared/global.php';

// if it was a HEAD request, stop here
if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] == 'HEAD'))
	return;

// destroy surfer session
Surfer::reset();

// redirect to another page
if(isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] && !preg_match('/login\.php/i', $_SERVER['HTTP_REFERER']))
	Safe::redirect($_SERVER['HTTP_REFERER']);
else
	Safe::redirect($context['url_to_home'].$context['url_to_root'].'index.php');

?>