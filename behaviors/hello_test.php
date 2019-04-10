<?php

/** 
 * This is a test behavior on allow access.
 * This will not block any access but write a sentence in main stream
 * 
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

class hello_test extends Behavior {
    
    
    Public function allow($script, $anchor = NULL) {
        global $context;
        
        $text = '';
        
        // say hello
        $text .= 'This is Hello_Test behavior !';
        
        // add params if any
        if(isset($this->parameters) && $this->parameters) {
            $text .= ' Instancied with parameter(s) "'.$this->parameters.'".';
        }
        
        // wrap in a <p>
        $context['text'] .= tag::p($text);
        
        // do not block, it's just a hello test
        return TRUE;
    }
}

