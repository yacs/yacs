<?php

/**
 * declaration of daily layout
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
	'id'		=> 'daily',
        'type'		=> 'layout',
	'supported'	=> 'article',
	'script'	=> 'layouts/layout_as_daily/layout_as_daily.php',
	'label_en'	=> 'layout articles as a daily weblog do',
	'label_fr'	=> 'Liste les articles comme un blog journalier',

        'source'        => 'http://www.yacs.fr'
	);

