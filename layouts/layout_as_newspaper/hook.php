<?php

/**
 * declaration of newspaper layout
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
	'id'		=> 'newspaper',
        'type'		=> 'layout',
	'supported'	=> 'article',
	'script'	=> 'layouts/layout_as_newspaper/layout_as_newspaper.php',
	'label_en'	=> 'First article, then three side-by-side, with their thumbnai if present, then decorated list.',
	'label_fr'	=> 'Premier article, puis 3 côte à côte, avec leur vignette si présente, puis liste décorée',

        'source'        => 'http://www.yacs.fr'
	);

