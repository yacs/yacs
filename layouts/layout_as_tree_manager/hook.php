<?php

/**
 * declaration of tree manager layout
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
	'id'		=> 'tree_manager',
        'type'		=> 'layout',
	'supported'	=> 'section,category',
	'script'	=> 'layouts/layout_as_tree_manager/layout_as_tree_manager.php',
	'label_en'	=> 'Display sub-containers with a interface enableling drag & drop actions',
	'label_fr'	=> 'Affiche les sous-dossiers avec une interface permettant des actions glisser/déposer',

        'source'        => 'http://www.yacs.fr'
	);

