<?php

/**
 * class helper for jssor use
 *  
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class jssor {
    
    /**
     * Load jssor libs 
     * 
     * @global array $context
     * @param boolean $withCSS option to load defaut CSS for jssor slider
     */
    public static function Load($withCSS=true) {
        global $context;
        
        // note : we assume jquery is loaded
        
        if(isset($context['with_debug']) && $context['with_debug'] === 'Y') {
            Page::defer_script('included/jssor/js/jssor.js');
            Page::defer_script('included/jssor/js/jssor.slider.js');
        } else {
            // contains both dev files minified
            Page::defer_script('included/jssor/js/jssor.slider.mini.js');
        }
        
        if($withCSS) {
            Page::load_style('included/jssor/jssor.css');
        }
    }
    
    /**
     * build the HTML structure for 1 jssor slider
     * 
     * @param array $slides of images & captions & thumbs
     * @param array $options to provide with this instance
     * @return string formated HTML
     */
    public static function Make($slides, $options=null) {
        
        $slider = '';
        
        // unique id for the slider
        static $slidenum;
        $slidenum = (isset($slidenum))? $slidenum+1 : 1;
        
        // main div
        $slider .= '<div id="slider'.$slidenum.'_container" class="sor-container" >'."\n";
        
        // loading screen, if required
        if(isset($option['loading_screen'])) {
            $slider .= '<!-- Loading Screen -->'."\n"
                    .  '<div data-u="loading" class="sor-loading">'."\n"
                    .  $option['loading_screen']."\n"
                    .  '</div>'."\n";
        }
        
        // slider container
        $slider .= '<!-- Slides Container -->'."\n"
                .  '<div data-u="slides" class="sor-slides">'."\n";
        
        // Parse $slides to make slides
        Foreach($slides as $slide) {
            // start slide
             $slider .= '<div>'."n";
            // main image
            if(isset($slide['image_src'])) {
                $slider .= '<img data-u="image" src="'.$slide['image_src'].'" />'."\n";
            }
            // caption
            if(isset($slide['caption'])) {
                $slider .= '<div data-u="caption" data-t="caption-transition-name" class="sor-caption">'."\n"
                        .  $slide['caption']."\n"
                        .  '</div>'."\n";
            }
            // thumb
            
            // close slide
            $slider .= '</div>'."n";
        }  
        
        // end slider container
        $slider .= '</div>'."\n";
        
        // end main div
        $slider .= '</div>'."\n";
        
        // javascript initalization
        $js_options = (isset($option['js']))? $option['js'] : array('$AutoPlay' => 'true');
        $rootname   = 'slider'.$slidenum.'_';
        Page::insert_script(
              '$(document).ready(function ($) {'."\n"
              . 'var '.$rootname.'options = '.  json_encode($js_options).";\n"
              . 'var '.$rootname.'jssor = new $JssorSlider$("'.$rootname.'container", '.$rootname.'options );'
              . '});'
              );
        
        return $slider;
    }
    
    
}

