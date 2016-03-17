<?php

/**
 * Clean temporary folder from failed image uploads
 *  
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// stop hackers
defined('YACS') or exit('Script must be included');

// trigger the post-processing function
$hooks[] = array(
	'id'		=> 'hourly',
	'type'		=> 'include',
	'script'	=> 'files/files.php',
	'function'	=> 'files::clean_uploaded',
	'label_en'	=> 'Clean temporary folder from files upload',
	'label_fr'	=> 'Nettoyage du dossier temporaire des téléversements',
	'description_en' => 'Delete temporary files older than a hour',
	'description_fr' => 'Supprime les fichiers plus vieux d\'une heure',
	'source' => 'http://www.yacs.fr/' );

