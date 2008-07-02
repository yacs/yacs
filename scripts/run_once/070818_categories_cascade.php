<?php
/**
 * cascade access rights from categories
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

// list top categories
$query = "SELECT categories.title AS title, categories.id AS id, categories.active AS active FROM ".SQL::table_name('categories')." AS categories"
	." WHERE (categories.anchor='' OR categories.anchor IS NULL) ORDER BY title";
if(!$result = SQL::query($query))
	return;

// process every item
$count = 0;
while($item =& SQL::fetch($result)) {

	// feed-back to surfer
	echo "Processing category: ".$item['title']."...<br />\n";
	Anchors::cascade('category:'.$item['id'], $item['active']);

	// next one
	$count += 1;
	Safe::set_time_limit(30);
}

// basic reporting
$local['label_en'] = 'categories have been processed';
$local['label_fr'] = 'cat&eacute;gories ont &eacute;t&eacute; trait&eacute;es';
echo $count.' '.get_local('label')."<br />\n";
?>