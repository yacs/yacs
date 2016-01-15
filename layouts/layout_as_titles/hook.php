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
	'id'		=> 'titles',
        'type'		=> 'layout',
	'supported'	=> 'article,section,category,user',
	'script'	=> 'layouts/layout_as_titles/layout_as_titles.php',
	'label_en'	=> 'Layout as a set of titles with thumbnails.',
	'label_fr'	=> 'Affiche des blocs avec titre et vignette.',

        'source'        => 'http://www.yacs.fr'
	);

