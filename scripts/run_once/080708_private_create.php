<?php
/**
 * a section for private pages
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Add private pages';
$local['label_fr'] = 'Ajout des pages priv&eacute;es';
echo i18n::l($local, 'label')."<br />\n";

$local['title_en'] = 'Private pages';
$local['title_fr'] = 'Pages priv&eacute;es';
$local['check_en'] = 'A section "%s" already exists.';
$local['check_fr'] = 'Une section "%s" existe.';
$local['done_en'] = 'A section "%s" has been created.';
$local['done_fr'] = 'Une section "%s" a &eacute;t&eacute; cr&eacute;&eacute;e.';

if($section = Sections::get('private')) {
	echo sprintf(i18n::l($local, 'check'), i18n::l($local, 'title'))."<br />\n";
} else {
	$fields = array();
	$fields['nick_name'] = 'private';
	$fields['title'] = i18n::l($local, 'title');
	$fields['locked'] = 'N'; // no direct contributions
	$fields['home_panel'] = 'none'; // content is not pushed at the front page
	$fields['index_map'] = 'N'; // this is a special section
	$fields['sections_layout'] = 'none'; // prevent creation of sub-sections
	$fields['articles_layout'] = 'yabb'; // these are threads
	$fields['content_options'] = 'with_deletions with_export_tools'; // allow editors to delete pages here
	$fields['maximum_items'] = 20000; // limit the overall number of threads
	if(Sections::post($fields))
		echo sprintf(i18n::l($local, 'done'), $fields['title'])."<br />\n";
}

?>