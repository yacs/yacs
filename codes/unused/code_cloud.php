<?php

/**
 * render categories as a cloud
 * 
 * @author Bernard Paques (rendering)
 * @author Alexis Raimbault (factoring)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Code_cloud extends Code {
    
    var $patterns = array(
        '/\[cloud(?:=(\d+?))?\]/is',		// [cloud] [cloud=12]
    );
    
    public function render($matches) {
        
        $count = (isset($matches[0]))?$matches[0]:20;
        
        // sanity check
        if(!(int)$count)
                $count = 20;

        // query the database and layout that stuff
        if(!$text = Members::list_categories_by_count_for_anchor(NULL, 0, $count, 'cloud'))
                $text = '<p>'.i18n::s('No item has been found.').'</p>';

        // we have an array to format
        if(is_array($text))
                $text = Skin::build_list($text, '2-columns');

        // job done
        return $text;
    }
}

