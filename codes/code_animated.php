<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * [scroller]...[/scroller]
 *
 * @author Alexis Raimbault
 */

class Code_animated extends Code {
    
    function get_pattern_replace(&$pattern,&$replace) {
        $regexp_pattern = array('/\[scroller\](.*?)\[\/scroller\]/ise');
        $php_replace_command = array("Code_animated::render(Codes::fix_tags('$1'), 'scroller')");

        $pattern = array_merge($pattern, $regexp_pattern);
        $replace = array_merge($replace, $php_replace_command);
    }

    /**
     * render an animated block of text
     *
     * @param string the text
     * @param string the variant
     * @return string the rendered text
    **/
    function &render($text, $variant) {
            global $context, $scroller_counter;

            $scroller_counter++;
            $output = '<marquee id="scroller_'.$scroller_counter.'">'.$text.'</marquee>';
            return $output;
    }
}
?>
