<?php

/**
 * Backfill the decomposed anchor columns (anchor_type, anchor_id)
 *
 * Every table carrying a textual anchor 'type:id' now declares the two
 * decomposed columns anchor_type and anchor_id in its setup(), and every
 * write mirrors the anchor string into them (see Anchors::get_sql_set()).
 * This script populates the new columns on pre-existing rows.
 *
 * Only anchored rows are touched (anchor LIKE '%:%'), so root sections and
 * categories keep the 'no anchor' sentinel (anchor_type='', anchor_id=0).
 * The script is idempotent: rows already decomposed (anchor_id != 0) are
 * skipped, and it can be replayed safely at any time.
 *
 * The versions table holds the full edition history and may be huge: it is
 * processed in batches of 5000 rows to keep table locks short.
 *
 * @author Christian Loubechine
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en']      = 'Backfill of the decomposed anchor columns (anchor_type, anchor_id)';
$local['label_fr']      = 'Remplissage des colonnes anchor décomposées (anchor_type, anchor_id)';
$local['table_en']      = '%s: %d rows have been updated';
$local['table_fr']      = '%s : %d lignes ont été mises à jour';
$local['fail_en']       = '%s: the update has failed!';
$local['fail_fr']       = '%s : échec de la mise à jour !';
$local['done_en']       = 'All anchors have been decomposed';
$local['done_fr']       = 'Tous les anchors ont été décomposés';

// display the goal
echo get_local('label')."<br />\n";

// every table carrying a textual anchor; versions is processed separately
$tables = array('articles', 'categories', 'comments', 'dates', 'enrolments',
	'files', 'images', 'issues', 'links', 'locations', 'members', 'sections', 'tables');

foreach($tables as $table) {

	// decompose anchored rows not converted yet
	$query = "UPDATE ".SQL::table_name($table)
		." SET anchor_type = SUBSTRING_INDEX(anchor, ':', 1)"
		.", anchor_id = SUBSTRING_INDEX(anchor, ':', -1)"
		." WHERE (anchor LIKE '%:%') AND (anchor_id = 0)";

	// report on this table
	if(($count = SQL::query($query, TRUE)) === FALSE)
		echo sprintf(get_local('fail'), $table)."<br />\n";
	else
		echo sprintf(get_local('table'), $table, $count)."<br />\n";

	// avoid timeouts
	Safe::set_time_limit(30);
}

// versions may be a very large table -- proceed in short batches
$total = 0;
do {

	$query = "UPDATE ".SQL::table_name('versions')
		." SET anchor_type = SUBSTRING_INDEX(anchor, ':', 1)"
		.", anchor_id = SUBSTRING_INDEX(anchor, ':', -1)"
		." WHERE (anchor LIKE '%:%') AND (anchor_id = 0)"
		." LIMIT 5000";

	if(($count = SQL::query($query, TRUE)) === FALSE) {
		echo sprintf(get_local('fail'), 'versions')."<br />\n";
		break;
	}
	$total += $count;

	// avoid timeouts between batches
	Safe::set_time_limit(30);

} while($count);
echo sprintf(get_local('table'), 'versions', $total)."<br />\n";

// basic reporting
echo get_local('done')."<br />\n";
