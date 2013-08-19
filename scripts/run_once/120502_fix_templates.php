<?php
/**
 * change templates
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Change templates for new articles';
$local['label_fr'] = 'Modification des mod&egrave;les pour les nouvelles pages';
echo get_local('label')."<br />\n";
$count = 0;

// change 'bbb_template'
$query = "UPDATE ".SQL::table_name('articles')
			." SET options = 'edit_as_thread comments_as_wall' WHERE nick_name = 'bbb_template'";
$count += SQL::query($query);

// change 'etherpad_template'
$query = "UPDATE ".SQL::table_name('articles')
			." SET options = 'edit_as_thread comments_as_wall' WHERE nick_name = 'etherpad_template'";
$count += SQL::query($query);

// change 'event_template'
$query = "UPDATE ".SQL::table_name('articles')
			." SET options = 'comments_as_wall' WHERE nick_name = 'event_template'";
$count += SQL::query($query);

// change 'external_template'
$query = "UPDATE ".SQL::table_name('articles')
			." SET options = 'edit_as_thread comments_as_wall' WHERE nick_name = 'external_template'";
$count += SQL::query($query);

// change 'ustream_template'
$query = "UPDATE ".SQL::table_name('articles')
			." SET options = 'edit_as_thread comments_as_wall' WHERE nick_name = 'ustream_template'";
$count += SQL::query($query);

// change 'wiki_template'
$query = "UPDATE ".SQL::table_name('articles')
			." SET options = 'edit_as_simple members_edit view_as_wiki comments_as_wall' WHERE nick_name = 'wiki_template'";
$count += SQL::query($query);

// basic reporting
$local['label_en'] = 'records have been processed';
$local['label_fr'] = 'enregistrements ont &eacute;t&eacute; trait&eacute;s';
echo $count.' '.get_local('label')."<br />\n";

?>