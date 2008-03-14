<?php
/**
 * cascase access rights from sections
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Cascade access rights';
$local['label_fr'] = 'Cascade des droits d\'acc&egrave;s';
echo get_local('label')."<br />\n";

// list top sections
$query = "SELECT sections.title AS title, sections.id AS id, sections.active AS active FROM ".SQL::table_name('sections')." AS sections"
	." WHERE (sections.anchor='' OR sections.anchor IS NULL) ORDER BY title";
if(!$result = SQL::query($query))
	return;

// process every item
$count = 0;
while($item =& SQL::fetch($result)) {

	// feed-back to surfer
	echo "Processing section: ".$item['title']."...<br />\n";
	Anchors::cascade('section:'.$item['id'], $item['active']);

	// next one
	$count += 1;
	Safe::set_time_limit(30);
}

// basic reporting
$local['label_en'] = 'sections have been processed';
$local['label_fr'] = 'sections ont &eacute;t&eacute; trait&eacute;es';
echo $count.' '.get_local('label')."<br />\n";
?>