<?php

/**
 * The formatting code interface
 *
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class code {

    function set_pattern_replace(&$pattern,&$replace) {
        $regexp_pattern = array();
        $php_replace_command = array("code::render()");

        $pattern = array_merge($pattern, $regexp_pattern);
        $replace = array_merge($replace, $php_replace_command);
    }

    function render() {
        $text ='';

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
