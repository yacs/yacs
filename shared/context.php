<?php

/** 
 * Helper class to deal with global $context
 * 
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
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
            return $this->offsetGet($index);
        }
        $this->offsetSet($index, $default);
        return $default;
    }
    
    /**
     * check if an index exists and if its
     * value is not null or '' 
     * 
     * @param type $index
     * @return boolean
     */
    public function has($index) {
        
        if(!$this->offsetExists($index)) return FALSE;
        
        $val = $this->offsetGet($index);
        
        if(is_null($val) || $val=== '') return FALSE;
        
        return TRUE;
    }
    
    /**
     * Set a value if not exist of equal to false
     * 
     * @param string $index
     * @param mixed $value
     * @return boolean
     */
    public function sif($index, $value) {
        if($this->offsetExists($index) && $this->offsetGet($index)) {
            return FALSE;
        }
        return $this->offsetSet($index, $value);
    }
}