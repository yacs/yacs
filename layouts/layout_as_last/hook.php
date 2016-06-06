<?php

/**
 * declaration of last layout
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
	'id'		=> 'last',
        'type'		=> 'layout',
	'supported'	=> 'article',
	'script'	=> 'layouts/layout_as_last/layout_as_last.php',
	'label_en'	=> 'Layout articles as topics, including last contribution',
	'label_fr'	=> 'Liste les articles comme un sujet, avec la dernière contribution',

        'source'        => 'http://www.yacs.fr'
	);

