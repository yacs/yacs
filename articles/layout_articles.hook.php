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
	'id'		=> 'decorated',
        'type'		=> 'layout',
	'supported'	=> 'article',
	'script'	=> 'articles/layout_articles.php',
	'label_en'	=> 'This is the default layout for articles',
	'label_fr'	=> 'Layout par défaut pour les articles',

        'source'        => 'http://www.yacs.fr'
	);

