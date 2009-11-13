<?php
/**
 * a flexible skin for yacs
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// stop hackers
defined('YACS') or exit('Script must be included');

// let share this skin
global $skins;
$skins['skins/flexible'] = array(
	'label_en' => 'flexible',
	'description_en' => 'This theme features a nice configuration panel, that does not require CSS expertise to tune the visual rendering.',
	'description_fr' => 'Ce th&egrave;me dispose d\'un panneau de configuration pour faciliter le travail d\adaption des webmestres.',
	'thumbnail' => 'preview.jpg',
	'home_url' => 'http://www.yacs.fr/' );
?>