<?php

/** 
 * Exemple of a hook to declare a layout as a extension
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

/**
 * please uncomment following declaration within the hook for your layout
$hooks[] = array(
	'id'		=> '<name>',                                // setted according to your layout_as_<name>
        'type'		=> 'layout',                                // must be setted to layout
	'supported'	=> 'article,section,category',              // provide here supported entities to be layouted. Could be article,section,category,image,file,comment,user...
	'script'	=> 'layouts/yourlayout/yourlayout.php',     // relative path from root to the layout interface 
	'label_en'	=> 'English description of layout',
	'label_fr'	=> 'Description française du layout',       // note : you may provide description from other languages
                        
        'source'        => 'http://www.yacs.fr'                     // where does this layout come from ?
	);
*/

