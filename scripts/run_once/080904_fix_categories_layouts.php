<?php
/**
 * change from 'raw' to 'decorated'
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Update categories';
$local['label_fr'] = 'Modification des cat&eacute;gories';
echo i18n::l($local, 'label')."<br />\n";

// update records
$query = "UPDATE ".SQL::table_name('categories')
			." SET categories_layout='decorated'"
			." WHERE categories_layout='raw'";
$count = SQL::query($query, TRUE);

// update records
$query = "UPDATE ".SQL::table_name('categories')
			." SET sections_layout='decorated'"
			." WHERE sections_layout='raw'";
$count += SQL::query($query, TRUE);

// update records
$query = "UPDATE ".SQL::table_name('categories')
			." SET articles_layout='decorated'"
			." WHERE articles_layout='raw'";
$count += SQL::query($query, TRUE);

// basic reporting
$local['label_en'] = 'fields have been processed';
$local['label_fr'] = 'champs ont &eacute;t&eacute; trait&eacute;s';
echo $count.' '.i18n::l($local, 'label')."<br />\n";

?>