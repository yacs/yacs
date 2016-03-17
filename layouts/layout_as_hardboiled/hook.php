<?php

/**
 * declaration of hardboiled layout
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
	'id'		=> 'hardboiled',
        'type'		=> 'layout',
	'supported'	=> 'article',
	'script'	=> 'layouts/layout_as_hardboiled/layout_as_hardboiled.php',
	'label_en'	=> 'The two very first articles are displayed side-by-side, with a thumbnail image if one is present',
	'label_fr'	=> 'Les deux premiers artciles côte à côte, avec une vignette si présente',

        'source'        => 'http://www.yacs.fr'
	);

