<?php
/**
 * make threads of comments as flat as possible
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Make one-level threads of comments';
$local['label_fr'] = 'Aplatir les fils de discussion &agrave; un seul niveau de r&eacute;ponse';
echo i18n::user('label')."<br />\n";
$count = 0;

// modify comments
$query = "UPDATE ".SQL::table_name('comments')." targets,"

	// records with grand-fathers
	." (SELECT a.id, a.previous_id, b.previous_previous_id FROM ".SQL::table_name('comments')." AS a"
	." INNER JOIN (SELECT id, previous_id previous_previous_id FROM ".SQL::table_name('comments')." WHERE previous_id >0) AS b"
	." ON ( a.previous_id = b.id )) findings"

	// flatten the cascading chain
	." SET targets.previous_id = findings.previous_previous_id"
	." WHERE targets.id = findings.id";

// do it several time to achieve flat threads
while($modified = SQL::query($query))
	$count += $modified;

// basic reporting
$local['label_en'] = 'records have been processed';
$local['label_fr'] = 'enregistrements ont &eacute;t&eacute; trait&eacute;s';
echo $count.' '.i18n::user('label')."<br />\n";


?>