<?php
/**
 * change anchor fields in files
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// feed-back
echo 'Change anchors in files...<br />'."\n";

// split membership components
$query = "UPDATE ".SQL::table_name('files')
			." SET anchor_type = SUBSTRING_INDEX(anchor, ':', 1)"
			.", anchor_id = SUBSTRING_INDEX(anchor, ':', -1)";
if($count = SQL::query($query, TRUE))
	echo $count.' records have been updated.<br />'."\n";

?>