<?php

/**
 * The formatting code interface
 *
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

abstract class Code {

    /** 
     * Give the patterns to detect formatting codes in text, 
     * and the corresponding replacement texts (or function to call)
     * 
     * Works with regular expression.
     *
     * To be overloaded into derivated class
     * 
     * @param array $pattern, add your patterns at it
     * @param array $replace, add your replacement-texts at it
     */
    function get_pattern_replace(&$pattern,&$replace) {

        //$pattern[] = 'your regular expression';
        //$replace[] = "code_yourclass::render($1...)";
    }

    /**
     * Perform calculation to give replacement text
     * To be overloaded into derivated class
     *
     * Create a render function if you need and call it within get_pattern_replace
     *
     * @param string the argument of formatting code (could be more than one)
     * @return string the replacement text
     */
    function render($text) {

        return $text;
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