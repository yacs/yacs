<?php
/**
 * to set session cookie in a different host name
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

header('Access-Control-Allow-Origin: http://' . $_REQUEST["origin"]);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: origin, content-type, accept');

// use the id provided
if($_REQUEST['id']) {

	// share session id created at another host name
	session_id($_REQUEST['id']);

	// bind it to this host name
	session_start();

}

echo 'done';

?>