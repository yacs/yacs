<?php
/**
 * adjust number of sub-sections
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Adjust count of sub-sections';
$local['label_fr'] = 'Ajustement du nombre de sous-sections';
echo get_local('label')."<br />\n";

// update all records at once
$query = "UPDATE ".SQL::table_name('sections')
			." SET sections_count = 50";
$count = SQL::query($query, TRUE);

// basic reporting
if($count) {
	$local['label_en'] = 'records have been processed';
	$local['label_fr'] = 'enregistrements ont &eacute;t&eacute; trait&eacute;s';
	echo $count.' '.get_local('label')."<br />\n";
} else {
	$local['label_en'] = 'No record has been processed';
	$local['label_fr'] = 'Aucun enregistrement n\'a &eacute;t&eacute; trait&eacute;';
	echo $count.' '.get_local('label')."<br />\n";
}
?>