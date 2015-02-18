<?php

/* 
 * A layout that return nothing !
 * Sometimes "no layout" does list someting. this is to cancel 
 *  
 * @author Alexis Raimbault
 */

Class Layout_as_void extends Layout_interface {
    
    public function layout($result) {
        
        
        $text = '';
        
        
        // end of processing
	SQL::free($result);
        return $text;
    }   
    
}



