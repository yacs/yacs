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

class Code_Animated extends Code {
    
    function get_pattern_replace(&$pattern,&$replace) {
        
        // [scroller]...[/scroller]
        $pattern[] ='/\[scroller\](.*?)\[\/scroller\]/ise';
        $replace[] ="Code_Animated::render(Codes::fix_tags('$1'), 'scroller')";
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