<?php

/**
 * declaration of mosaic layout
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
	'id'		=> 'mosaic',
        'type'		=> 'layout',
	'supported'	=> 'article,section,category,user',
	'script'	=> 'layouts/layout_as_mosaic/layout_as_mosaic.php',
	'label_en'	=> 'Display elements as a mosaic of blocks',
	'label_fr'	=> 'Affiche les éléments en mosaïque',

        'source'        => 'http://www.yacs.fr'
	);

