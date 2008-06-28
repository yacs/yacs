<?php
/**
 * integrate the agent building stats on browsers
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
	'script'	=> 'agents/browsers.php',
	'function'	=> 'Browsers::setup',
	'label_en'	=> 'Setup tables for stats on browsers',
	'label_fr'	=> 'Cr&eacute;ation des tables pour les statistiques sur les navigateurs',
	'description_en' => 'Stats on user agents',
	'description_fr' => 'Statistiques sur les navigateurs',
	'source' => 'http://www.yetanothercommunitysystem.com/' );

// trigger the post-processing function
$hooks[] = array(
	'id'		=> 'finalize',
	'type'		=> 'include',
	'script'	=> 'agents/browsers.php',
	'function'	=> 'Browsers::check_request',
	'label_en'	=> 'Update stats on browsers',
	'label_fr'	=> 'Mise &agrave; jour des statistiques sur les navigateurs',
	'description_en' => 'Stats on user agents',
	'description_fr' => 'Statistiques sur les navigateurs',
	'source' => 'http://www.yetanothercommunitysystem.com/' );

?>