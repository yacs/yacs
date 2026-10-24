<?php

/**
 * Delete backup files left in the temporary directory
 *
 * Up to now control/backup.php built its files in temporary/ under fixed
 * names (database dump, archives of files, images, parameters and skin),
 * and never deleted them. Now these files have random names and are deleted
 * once sent; this script removes the ones left by previous versions.
 *
 * The script is idempotent and can be replayed safely at any time.
 *
 * @author Christian Loubechine
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// splash message
global $local;
$local['label_en']      = 'Deletion of backup files left in the temporary directory';
$local['label_fr']      = 'Suppression des fichiers de sauvegarde laissés dans le répertoire temporary';
$local['file_en']       = '%s has been deleted';
$local['file_fr']       = '%s a été supprimé';
$local['fail_en']       = '%s could not be deleted!';
$local['fail_fr']       = '%s n\'a pas pu être supprimé !';
$local['done_en']       = '%d files have been deleted';
$local['done_fr']       = '%d fichiers ont été supprimés';

// display the goal
echo get_local('label')."<br />\n";

// database dumps and archives built by control/backup.php
$count = 0;
foreach(array('*.sql', '*.sql.gz', 'backup_*.zip') as $pattern) {

	if(!$files = Safe::glob($context['path_to_root'].'temporary/'.$pattern))
		continue;

	foreach($files as $file) {

		if(Safe::unlink($file)) {
			echo sprintf(get_local('file'), 'temporary/'.basename($file))."<br />\n";
			$count++;
		} else
			echo sprintf(get_local('fail'), 'temporary/'.basename($file))."<br />\n";
	}
}

// report on this script
echo sprintf(get_local('done'), $count)."<br />\n";
