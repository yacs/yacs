<?php
/**
 * change some sections
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en'] = 'Update some sections';
$local['label_fr'] = 'Mise &agrave; jour de certaines sections';
echo i18n::user('label')."<br />\n";

// update the section 'groups'
if($item = Sections::get('groups')) {
	$fields = array();
	$fields['id'] = $item['id'];
	$fields['sections_layout'] = 'directory';
	$fields['articles_templates'] = 'discussion_template, chat_template, event_template, wiki_template';
	if(Sections::put_attributes($fields)) {
		$local['label_en'] = 'Section "%s" has been updated';
		$local['label_fr'] = 'La section "%s" a &eacute;t&eacute; mise &agrave; jour';
		echo sprintf(i18n::user('label'), 'groups')."<br />\n";
	} else
		$text .= Logger::error_pop().BR."\n";
}

// update the section 'templates'
if($item = Sections::get('templates')) {
	$fields = array();
	$fields['id'] = $item['id'];
	$fields['active_set'] = 'Y';
	if(Sections::put_attributes($fields)) {
		$local['label_en'] = 'Section "%s" has been updated';
		$local['label_fr'] = 'La section "%s" a &eacute;t&eacute; mise &agrave; jour';
		echo sprintf(i18n::user('label'), 'templates')."<br />\n";
	} else
		$text .= Logger::error_pop().BR."\n";
}

// update the section 'threads'
if($item = Sections::get('threads')) {
	$fields = array();
	$fields['id'] = $item['id'];
	$fields['active_set'] = 'Y';
	$fields['articles_layout'] = 'yabb'; // these are threads
	$fields['articles_templates'] = 'thread_template, chat_template, event_template';
	$fields['content_options'] = 'with_export_tools auto_publish';
	$fields['index_map'] = 'N';
	$fields['maximum_items'] = 20000; // limit the overall number of threads
	if(Sections::put_attributes($fields)) {
		$local['label_en'] = 'Section "%s" has been updated';
		$local['label_fr'] = 'La section "%s" a &eacute;t&eacute; mise &agrave; jour';
		echo sprintf(i18n::user('label'), 'threads')."<br />\n";
	} else
		$text .= Logger::error_pop().BR."\n";
}

?>