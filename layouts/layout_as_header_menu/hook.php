<?php

/**
 * declaration of header_menu layout
 *
 * @author Alexis Raimbault
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// stop hackers
if(count(get_included_files()) < 3 || !defined('YACS')) {
	exit('Script must be included');
}

global $hooks;

$hooks[] = array(
	'id'		=> 'header_menu',
        'type'		=> 'layout',
	'supported'	=> 'section',
	'script'	=> 'layouts/layout_as_header_menu/layout_as_header_menu.php',
	'label_en'	=> 'Layout for header menu',
	'label_fr'	=> 'Layout pour le menu d'en-tête',

        'source'	=> 'http://www.yacs.fr'
	);
