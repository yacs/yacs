<?php

/**
 * The formatting code interface
 *
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

abstract class Code {

    function get_pattern_replace(&$pattern,&$replace) {
        
        //$regexp_pattern = array('your regular expression');
        //$php_replace_command = array("code_yourclass::render($1...)");

        //$pattern = array_merge($pattern, $regexp_pattern);
        //$replace = array_merge($replace, $php_replace_command);
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
