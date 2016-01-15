<?php

/**
 * declaration of yabb layout
 *
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// stop hackers
if(count(get_included_files()) < 3 || !defined('YACS')) {
	exit('Script must be included');
}

global $hooks;

$hooks[] = array(
	'id'		=> 'yabb',
        'type'		=> 'layout',
	'supported'	=> 'article,section',
	'script'	=> 'layouts/layout_as_yabb/layout_as_yabb.php',
	'label_en'	=> 'Layout entites as boards in a YaBB forum.',
	'label_fr'	=> 'Affiche les éléments en panneau dans un forum YaBB.',

        'source'        => 'http://www.yacs.fr'
	);

