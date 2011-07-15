<?php
/**
 * change activists ids
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Change surfer ids in activities';
$local['label_fr'] = 'Assignation des surfeurs pour les activit&eacute;s';
echo get_local('label')."<br />\n";

// set surfer ids
$query = "UPDATE ".SQL::table_name('activities')
			." SET edit_id = user_id";
$count = SQL::query($query);

// basic reporting
$local['label_en'] = 'records have been processed';
$local['label_fr'] = 'enregistrements ont &eacute;t&eacute; trait&eacute;s';
echo $count.' '.get_local('label')."<br />\n";


// drop the previous column
$query = "ALTER TABLE ".SQL::table_name('activities')." DROP user_id";
SQL::query($query);


?>