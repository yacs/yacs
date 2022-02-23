<?php

/** 
 * hooks for automatic tracking with matomo
 * 
 * @author devalxr
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// stop hackers
defined('YACS') or exit('Script must be included');

// track pages with matomo
$hooks[] = array(
	'id'		=> 'finalize',
	'type'		=> 'include',
	'script'	=> 'included/tomato/tracker.php',
	'function'	=> 'tracker::trackHook',
	'label_en'	=> 'Track pages visits with Matomo',
	'label_fr'	=> 'Trace les visites de pages avec Matomo',
	'description_en' => 'If you have a matomo instance for statistics, let yacs connect to it to send data. See configuration in included/matomo/configure.php',
	'description_fr' => 'Si vous avez une installation de matomo pour les statistiques, laisser yacs se connecter pour envoyer les données. Voir la configuration dans included/matomo/configure.php',
	'source' => 'http://www.yacs.fr/' );

// Ping activity to matomo
$hooks[] = array(
	'id'		=> 'heartbeat',
	'type'		=> 'include',
	'script'	=> 'included/tomato/tracker.php',
	'function'	=> 'tracker::pingHook',
	'label_en'	=> 'Ping Matomo for surfer\'s presence',
	'label_fr'	=> 'Signale à Matomo la présence de l\'internaute',
	'description_en' => 'If you have a matomo instance for statistics, let yacs ping it while surfer still viewing a page',
	'description_fr' => 'Si vous avez une installation de matomo pour les statistiques, laisser yacs lui signaler la présence d\'un internaute encore sur la page',
	'source' => 'http://www.yacs.fr/' );

// Display a configuration link in control panel
$hooks[] = array(
        'id'            => 'control/index.php#configure',
        'type'          => 'link',
        'script'        => 'included/tomato/configure.php',
        'label_en'	=> 'Matomo statistics tracking configuration',
	'label_fr'	=> 'Configuration du suivi statistique par Matomo',
	'description_en' => 'Allow Yacs to record pages visits into your Matomo server',
	'description_fr' => 'Permettez à Yacs d\'enregistrer les visites de pages dans votre serveur Matomo',
	'source' => '' );