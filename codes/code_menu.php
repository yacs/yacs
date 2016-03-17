<?php

/**
 * legacy syntaxe to make some links in yacs
 * 
 * @author Alexis Raimbault (factoring)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Code_menu extends Code {
    
    
    var $patterns = array(
        // [menu]url[/menu]
	// [menu=label]url[/menu]
	// [submenu]url[/submenu]
	// [submenu=label]url[/submenu]
        // [script]url[/script]
        '/\[(menu|submenu|script)(?:=([^\]]+?))*\](.*?)\[\/\1\]\n{0,1}/is'
    );
    
     public function render($matches) {
         $text  = '';
         $mode  = $matches[0];
         if($mode=='menu') $variant = 'menu_1';
         elseif($mode='submenu') $variant = 'menu_2';
         else $variant = $mode;
      
         $url   = (isset($matches[2]))?encode_link($matches[2]):encode_link($matches[1]);
         $label = (isset($matches[2]))?Codes::fix_tags($matches[1]):$matches[1];
         
         $text  = Skin::build_link($url, $label, $variant);
         
         return $text;
     }
}