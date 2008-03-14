<?php
/**
 * cache anchors in decisions to speed up queries
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// feed-back
echo 'Speeding queries in decisions...'."<br />\n";

// split membership components
$query = "UPDATE ".SQL::table_name('decisions')
			." SET anchor_type = SUBSTRING_INDEX(anchor, ':', 1)"
			.", anchor_id = SUBSTRING_INDEX(anchor, ':', -1)";
if(!@mysql_query($query, $context['connection'])) {
	echo $query.BR.@mysql_errno($context['connection']).': '.@mysql_error($context['connection']).BR."\n";
	return;
} else
	echo mysql_affected_rows($context['connection']).' records have been updated.'.BR."\n";

?>