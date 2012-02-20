<?php
/**
 * change templates in sections
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

// redo the basic steps of data creation
$context['populate_follow_up'] = 'none';
include_once $context['path_to_root'].'control/populate.php';
echo $context['text'];
$context['text'] = '';

// change 'threads'
$query = "UPDATE ".SQL::table_name('sections')
			." SET articles_templates = 'information_template, question_template, chat_template, event_template' WHERE nick_name = 'threads'";
$count += SQL::query($query);

// change 'groups'
$query = "UPDATE ".SQL::table_name('sections')
			." SET articles_templates = 'information_template, question_template, chat_template, event_template, wiki_template' WHERE nick_name = 'groups'";
$count += SQL::query($query);

// change 'forums' --sample section
$query = "UPDATE ".SQL::table_name('sections')
			." SET articles_templates = 'information_template, question_template' WHERE nick_name = 'forums'";
$count += SQL::query($query);

// change 'yabb_board' --sample section
$query = "UPDATE ".SQL::table_name('sections')
			." SET articles_templates = 'information_template, question_template' WHERE nick_name = 'yabb_board'";
$count += SQL::query($query);

// change 'jive_board' --sample section
$query = "UPDATE ".SQL::table_name('sections')
			." SET articles_templates = 'information_template, question_template' WHERE nick_name = 'jive_board'";
$count += SQL::query($query);

// change 'project_private' --sample section
$query = "UPDATE ".SQL::table_name('sections')
			." SET articles_templates = 'information_template, question_template, chat_template' WHERE nick_name = 'project_private'";
$count += SQL::query($query);

// basic reporting
$local['label_en'] = 'records have been processed';
$local['label_fr'] = 'enregistrements ont &eacute;t&eacute; trait&eacute;s';
echo $count.' '.get_local('label')."<br />\n";

?>