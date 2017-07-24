<?php

/**
 * [files=anchor:id, layout]
 * 
 * list files from a entity with a given layout
 *
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

class code_files extends Code {
    // [files=section:id] [files=article:id] [files.layout=section:id]
    var $patterns = array('/\[files(?:\.([^\]=]+?))?(?:=([^\]]+?))?\]\n*/is');
    
    public function render($matches) {
        
        // default choice
        $layout = 'simple';
        $anchor = (isset($context['current_item']))?$context['current_item']:'';
        
        // get choices form syntaxe
        if(count($matches)) {
            $arg1 = $matches[0];
            if(strpos($arg1,':') > 0) {
                $anchor = $arg1;
            } else {
                $layout = $arg1;
            }
          
            if(isset($matches[1]) && $arg2 = $matches[1]) {
               $anchor = $arg2; 
            }
        }
        
        $list = Files::list_by_date_for_anchor($anchor, 0, 50, $layout);
        
        if(is_array($list)) {
            $list = Skin::finalize_list($list, $layout);
        }
        
        return $list;
    }
    
}
