<?php

/**
 * declaration of decorated layout
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
	'id'		=> 'decorated',
        'type'		=> 'layout',
	'supported'	=> 'section',
	'script'	=> 'sections/layout_sections.php',
	'label_en'	=> 'This is the default layout',
	'label_fr'	=> 'Layout par défaut',

        'source'        => 'http://www.yacs.fr'
	);

