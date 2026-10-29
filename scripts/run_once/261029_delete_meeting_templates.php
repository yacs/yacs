<?php

/**
 * Delete the page templates of meeting overlays
 *
 * The meeting overlays have been removed from the core, but the templates
 * that control/populate.php created for them are still in the database.
 * They are found by their nick name, and deleted only if they are still in
 * the templates section with the overlay set by control/populate.php.
 *
 * These nick names are also removed from the lists of templates offered by
 * sections, where chat_template was set by default.
 *
 * Pages created by webmasters with these overlays are kept, and displayed
 * without overlay.
 *
 * The script is idempotent and can be replayed safely at any time.
 *
 * @author Christian Loubechine
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
include_once $context['path_to_root'].'articles/articles.php';
include_once $context['path_to_root'].'sections/sections.php';

// splash message
global $local;
$local['label_en']      = 'Deletion of the page templates of meeting overlays';
$local['label_fr']      = 'Suppression des pages modèles des overlays de réunion';
$local['page_en']       = 'Page "%s" has been deleted';
$local['page_fr']       = 'La page "%s" a été supprimée';
$local['kept_en']       = 'Page "%s" is not a template of this kind anymore, and has been kept';
$local['kept_fr']       = 'La page "%s" n\'est plus un modèle de ce type, elle a été conservée';
$local['section_en']    = 'Templates of section %d have been updated';
$local['section_fr']    = 'Les modèles de la section %d ont été mis à jour';
$local['done_en']       = '%d pages have been deleted';
$local['done_fr']       = '%d pages ont été supprimées';

// display the goal
echo get_local('label')."<br />\n";

// nick names and overlays set by control/populate.php
$templates = array(
	'bbb_template'          => 'bbb_meeting',
	'chat_template'         => 'chat_meeting',
	'etherpad_template'     => 'etherpad_meeting',
	'external_template'     => 'meeting',
	'livestream_template'   => 'livestream_meeting',
	'ustream_template'      => 'ustream_meeting');

// the section of templates
$section = Sections::get('templates');

$count = 0;
foreach($templates as $nick_name => $overlay_type) {

	if(!$item = Articles::get($nick_name))
		continue;

	// not in the templates section anymore, or bound to another overlay
	if(!$section || ($item['anchor'] != 'section:'.$section['id'])
		|| !isset($item['overlay']) || !preg_match('/"overlay_type";s:\d+:"'.$overlay_type.'";/', $item['overlay'])) {
		echo sprintf(get_local('kept'), $nick_name)."<br />\n";
		continue;
	}

	if(Articles::delete($item['id'])) {
		echo sprintf(get_local('page'), $nick_name)."<br />\n";
		$count++;
	}
}

// templates offered by sections
$where = array();
foreach(array_keys($templates) as $nick_name)
	$where[] = "articles_templates LIKE '%".$nick_name."%'";
$query = "SELECT id, articles_templates FROM ".SQL::table_name('sections')." WHERE ".implode(' OR ', $where);
if($result = SQL::query($query)) {
	while($row = SQL::fetch($result)) {

		$names = array();
		$changed = FALSE;
		foreach(explode(',', $row['articles_templates']) as $name) {
			if(!$name = trim($name))
				continue;
			if(isset($templates[$name]))
				$changed = TRUE;
			else
				$names[] = $name;
		}

		// '_' is a wildcard for LIKE
		if(!$changed)
			continue;

		$query = "UPDATE ".SQL::table_name('sections')." SET articles_templates='".SQL::escape(implode(', ', $names))."' WHERE id = ".intval($row['id']);
		if(SQL::query($query) !== FALSE)
			echo sprintf(get_local('section'), $row['id'])."<br />\n";
	}
}

// report on this script
echo sprintf(get_local('done'), $count)."<br />\n";
