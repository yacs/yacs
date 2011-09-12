<?php
/**
 * change anchor fields in decisions
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Change anchors in decisions';
$local['label_fr'] = 'Modification des ancres de d&eacute;cisions';
echo get_local('label')."<br />\n";

// split membership components
$query = "UPDATE ".SQL::table_name('decisions')
			." SET anchor_type = SUBSTRING_INDEX(anchor, ':', 1)"
			.", anchor_id = SUBSTRING_INDEX(anchor, ':', -1)";
$count = SQL::query($query, TRUE);

// basic reporting
$local['label_en'] = 'records have been processed';
$local['label_fr'] = 'enregistrements ont &eacute;t&eacute; trait&eacute;s';
echo $count.' '.get_local('label')."<br />\n";

?>