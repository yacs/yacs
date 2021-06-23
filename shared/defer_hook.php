<?php

/**
 * Hook declaration to trigger defered scripts' execution 
 * 
 * @reference
 */

// stop hackers
defined('YACS') or exit('Script must be included');

// suscribe to finalize hook
$hooks[] = array(
	'id'		=> 'finalize',
	'type'		=> 'include',
	'script'	=> 'shared/defer.php',
	'function'	=> 'Defer::run',
	'label_en'	=> 'Trigger defered script',
	'label_fr'	=> 'Déclenche les scripts en attente',
	'description_en' => 'Run the scripts that were defered after the page being sent to surfer',
	'description_fr' => 'Exécute les scripts mis en attente après que la page soit envoyée au surfer',
	'source' => 'http://www.yacs.fr/' );