<?php

/**
 * declaration of columns layout
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
	'id'		=> 'colums',
        'type'		=> 'layout',
	'supported'	=> 'article,section,category',
	'script'	=> 'layouts/layout_as_columns/layout_as_columns.php',
	'label_en'	=> 'Layout items as columns with image and short list of sub-item',
	'label_fr'	=> 'Affiche les éléments en colonnes avec image et courte liste de sous-éléments',

        'source'        => 'http://www.yacs.fr'
	);

