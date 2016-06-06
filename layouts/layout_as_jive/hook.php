<?php

/**
 * declaration of jive layout
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
	'id'		=> 'jive',
        'type'		=> 'layout',
	'supported'	=> 'article,section',
	'script'	=> 'layouts/layout_as_jive/layout_as_jive.php',
	'label_en'	=> 'A layout for discussion forum',
	'label_fr'	=> 'Un layout pour forum de discussion',

        'source'        => 'http://www.yacs.fr'
	);

