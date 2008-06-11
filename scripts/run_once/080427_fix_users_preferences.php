<?php
/**
 * change user preferences
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Change user preferences';
$local['label_fr'] = 'Modification des pr&eacute;f&eacute;rences utilisateur';
echo i18n::l($local, 'label')."<br />\n";

// update records of all associates
$query = "UPDATE ".SQL::table_name('users')
			." SET interface='C'"
			." WHERE capability='A'";
$count = SQL::query($query, TRUE);

// basic reporting
$local['label_en'] = 'user profiles have been processed';
$local['label_fr'] = 'profils utilisateur ont &eacute;t&eacute; trait&eacute;s';
echo $count.' '.i18n::l($local, 'label')."<br />\n";

?>