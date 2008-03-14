<?php
/**
 * change anchor fields in members
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// feed-back
echo 'Change anchors in members...<br />'."\n";

// split membership components
$query = "UPDATE ".SQL::table_name('members')
			." SET anchor=(@aside:=anchor)"		// save anchor before changing it
			.", anchor = member"
			.", member = @aside"				// restore previous anchor
			.", member_type = SUBSTRING_INDEX(member, ':', 1)"
			.", member_id = SUBSTRING_INDEX(member, ':', -1)"
			." WHERE anchor LIKE 'user:%'";
if($count = SQL::query($query, TRUE))
	echo $count.' records have been updated.<br />'."\n";

?>