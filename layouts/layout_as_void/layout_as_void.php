<?php

/* 
 * A layout that return nothing !
 * Sometimes "no layout" does list someting. this is to cancel 
 *  
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Layout_as_void extends Layout_interface {
    
    public function layout($result) {
        
        
        $text = '';
        
        
        // end of processing
	SQL::free($result);
        return $text;
    }   
    
}



