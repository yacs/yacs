<?php

/**
 * render a font-awesome icon
 * 
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Code_fa extends Code {
    
    var $patterns = array(
        '/\[fa=([^\]]+?)\]/is'      // [fa=icon,options]
    );
    
    public function render($matches) {
        global $context;
        
        // include lib
        include_once $context['path_to_root'].'/included/font_awesome/fa.php';
        
        $args = explode(',', $matches[0]);
        
        $icon       = $args[0];
        $options    = (isset($args[1]))?$args[1]:'';
        $title      = (isset($args[2]))?$args[2]:'';
        $aria_hide  = (isset($args[3]))?true:false;
        
        $text = fa::_($icon, $options, $title, $aria_hide);
        
        return $text;
        
    }
    
}

