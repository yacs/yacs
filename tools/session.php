<?php
/**
 * to set session cookie in a different host name
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// use the id provided
if($_REQUEST['id']) {

	// share session id created at another host name
	session_id($_REQUEST['id']);

	// bind it to this host name
	session_start();

}

echo 'done';

?>