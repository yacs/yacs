<?php
/**
 * change overlay fields in sections
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// feed-back
echo 'Change overlay fields in sections...'."<br />\n";

// split membership components
$query = "UPDATE ".SQL::table_name('sections')
			." SET content_overlay = overlay"
			.", overlay = ''";
if($count = SQL::query($query, TRUE))
	echo $count.' records have been updated.'.BR."\n";

?>