<?php
/**
 * fix sections layout
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Fix sections layout';
$local['label_fr'] = 'Correction des mises en page des sections';
echo get_local('label')."<br />\n";

// select sections
$count = 0;
$query = "SELECT id FROM ".SQL::table_name('sections')." ORDER BY edit_date DESC LIMIT 0, 10000";
if($result =& SQL::query($query)) {

	// retrieve the id and a printable label
	$scanned = 0;
	while($row =& SQL::fetch($result)) {

		// ensure enough execution time
		$scanned++;
		if(!($scanned%100))
			Safe::set_time_limit(30);

		// count sub-sections
		$query = "SELECT COUNT(*) as count FROM ".SQL::table_name('sections')." AS sections WHERE (sections.anchor LIKE 'section:".$row['id']."')";
		if(SQL::query_scalar($query) == 0) {

			$query = "UPDATE ".SQL::table_name('sections')." SET sections_layout = 'none' WHERE id = ".$row['id'];
			$count += SQL::query($query, TRUE);

		}
	}
}

// basic reporting
$local['label_en'] = 'records have been processed';
$local['label_fr'] = 'enregistrements ont &eacute;t&eacute; trait&eacute;s';
echo $count.' '.get_local('label')."<br />\n";

?>