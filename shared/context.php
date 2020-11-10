<?php

/** 
 * Helper class to deal with global $context
 * 
 * @author Alexis Raimbault
 * @Reference
 */

Class Context extends ArrayObject {
    
    /**
     * provide the value of $context at $index if exists
     * set and return $default elsewhere
     * 
     * @param string $index
     * @param mixed $default
     * @return mixed
     */
    public function gs($index, $default) {
        if($this->offsetExists($index)) {
            return $this->contents[$index];
        }
        $this->offsetSet($index, $default);
        return $default;
    }
}