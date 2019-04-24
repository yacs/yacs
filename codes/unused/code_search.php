<?php

/**
 * render search input form
 * 
 * @author Bernard Paques (rendering)
 * @author Alexis Raimbault (factoring)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Code_search extends Code {
    
    var $patterns = array(
        '/\[search\]/is',		// [search]
    );
    
    public function render($matches) {
        
       $text = Skin::build_block('', 'search');

        // job done
        return $text;
    }
}

