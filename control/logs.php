<?php
/**
 * display log files of the server
 *
 * Files [code]temporary/log.txt[/code] and [code]temporary/debug.txt[/code]
 * cannot be fetched directly from the web (see [code]temporary/.htaccess[/code]).
 * This script displays them as plain text.
 *
 * The database is not used, so that log files can be read even if it is down.
 *
 * To run this script, the surfer has to be an associate, or to come from a
 * trusted host (see [code]users_trusted_hosts[/code] in users/configure.php).
 *
 * Accepted calls:
 * - logs.php display temporary/log.txt
 * - logs.php?file=debug display temporary/debug.txt
 *
 * @see agents/index.php
 * @see control/purge.php
 *
 * @author Christian Loubechine
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// no decision, no extension
define('NO_CONTROLLER_PRELOAD', TRUE);

// no need for access to the database
define('NO_MODEL_PRELOAD', TRUE);

// common definitions and initial processing
include_once '../shared/global.php';

// trusted hosts are set in users parameters, which are not loaded without the database
Safe::load('parameters/users.include.php');

// only associates and trusted hosts can proceed
if(!Surfer::is_associate() && !Surfer::is_trusted()) {
	Safe::header('Status: 403 Forbidden', TRUE, 403);
	exit('You are not allowed to perform this operation.');
}

// the file to display
if(isset($_REQUEST['file']) && ($_REQUEST['file'] == 'debug'))
	$file = 'temporary/debug.txt';
else
	$file = 'temporary/log.txt';

// plain text, neither cached nor indexed
Safe::header('Content-Type: text/plain; charset=utf-8');
Safe::header('Cache-Control: no-store');
Safe::header('X-Robots-Tag: noindex');

// nothing to display
if(!is_readable($context['path_to_root'].$file))
	exit($file.' does not exist'."\n");

// the content of the file
readfile($context['path_to_root'].$file);
