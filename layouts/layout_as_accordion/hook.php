<?php

/**
 * declaration of accordion layout
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
	'id'		=> 'accordion',
        'type'		=> 'layout',
	'supported'	=> 'article,section',
	'script'	=> 'layouts/layout_as_accordion/layout_as_accordion.php',
	'label_en'	=> 'layout items as folded boxes in an accordion',
	'label_fr'	=> 'liste les éléments en tant que boîtes pliantes en accordéon',

        'source'        => 'http://www.yacs.fr'
	);

