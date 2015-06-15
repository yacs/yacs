<?php

/**
 * The formatting code interface
 *
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

abstract class Code {
    
    //$patterns = 'your regular expressions';
    var $patterns = array();

    /** 
     * function called to build patterns detection array
     * for formatting code
     * @see codes::render()
     * 
     * @param array $patterns_map of whole formatting code
     */
    final function get_pattern(&$patterns_map) {
        
        $r = array_fill(0, count($this->patterns), get_class($this));
        $p = array_combine($this->patterns, $r);
        
        $patterns_map = array_merge($patterns_map, $p);
    }

    /**
     * Perform calculation to give replacement text
     * To be overloaded into derivated class
     *
     *
     * @param string (0 to N) corresponding to capturing parenthesis in you regular expression
     * @return string the replacement text
     */
    public static function render($matches) {
        
        ;
    }

    function get_samples($variant) {
        $text = '';

        switch($variant) {
            case 'family':
                $text = 'abstract';
                return $text;
            case 'samples':  default :
                return $text;
        }
        
    }

    function format_sample($title, $begin, $arg1='', $arg2='', $text='', $end='') {
        $sample = '';


        return $sample;
    }

}
?>