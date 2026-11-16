<?php

/**
 * Deny web access to the parameters directory
 *
 * Each time a configuration panel is saved, the previous file is kept as
 * *.include.php.bak. Such a backup is not run by PHP, and a web server that
 * has not been told otherwise serves it as plain text.
 *
 * This script adds parameters/.htaccess to servers already installed, since
 * updates never write in parameters/. An existing file is left untouched.
 *
 * The script is idempotent and can be replayed safely at any time.
 *
 * @author Christian Loubechine
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// the place where the file is built
include_once $context['path_to_root'].'scripts/scripts.php';

// splash message
global $local;
$local['label_en']      = 'Protection of the parameters directory';
$local['label_fr']      = 'Protection du répertoire parameters';
$local['done_en']       = 'parameters/.htaccess is in place';
$local['done_fr']       = 'parameters/.htaccess est en place';
$local['fail_en']       = 'parameters/.htaccess could not be created!';
$local['fail_fr']       = 'parameters/.htaccess n\'a pas pu être créé !';

// display the goal
echo get_local('label')."<br />\n";

// report on this script
if(Scripts::protect_parameters())
	echo get_local('done')."<br />\n";
else
	echo get_local('fail')."<br />\n";
