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
	'id'		=> 'smartlist',
        'type'		=> 'layout',
	'supported'	=> 'article,section,category,file',
	'script'	=> 'layouts/layout_as_smartlist/layout_as_smartlist.php',
	'label_en'	=> 'Layout items as a unordered list with icon (thumbs) title and introduction',
	'label_fr'	=> 'Liste les éléments avec icone (vignette) titre et introduction',

        'source'        => 'http://www.yacs.fr'
	);

