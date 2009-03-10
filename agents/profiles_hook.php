<?php
/**
 * integrate profiling agent
 *
 * @author Bernard Paques
 * @reference
 * @see control/scan.php
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// stop hackers
defined('YACS') or exit('Script must be included');

// let this module appear in the setup script
$hooks[] = array(
	'id'		=> 'control/setup.php',
	'type'		=> 'include',
	'script'	=> 'agents/profiles.php',
	'function'	=> 'Profiles::setup',
	'label_en'	=> 'Setup tables for stats on script performance',
	'label_fr'	=> 'Cr&eacute;ation des tables pour les statistiques de performance',
	'description_en' => 'To compute script profile',
	'description_fr' => 'Pour le suivi des temps de r&eacute;ponse',
	'source' => 'http://www.yacs.fr/' );

// trigger the post-processing function
$hooks[] = array(
	'id'		=> 'finalize',
	'type'		=> 'include',
	'script'	=> 'agents/profiles.php',
	'function'	=> 'Profiles::check_request',
	'label_en'	=> 'Update stats on script performance',
	'label_fr'	=> 'Mise &agrave; jour des statistiques sur les performances',
	'description_en' => 'To compute script profile',
	'description_fr' => 'Pour le suivi des temps de r&eacute;ponse',
	'source' => 'http://www.yacs.fr/' );

?>