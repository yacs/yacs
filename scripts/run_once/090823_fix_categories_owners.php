<?php
/**
 * set category owners
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Set category owners';
$local['label_fr'] = 'Assignation des propi&eacute;taires des cat&eacute;gories';
echo get_local('label')."<br />\n";

// set owner ids
$query = "UPDATE ".SQL::table_name('categories')
			." SET owner_id = create_id";
$count = SQL::query($query, TRUE);

// basic reporting
$local['label_en'] = 'records have been processed';
$local['label_fr'] = 'enregistrements ont &eacute;t&eacute; trait&eacute;s';
echo $count.' '.get_local('label')."<br />\n";

?>