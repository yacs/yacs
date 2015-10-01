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
    public static function Make($slides, $options=array()) {
        
        $slider = '';
        
        // unique id for the slider
        static $slidenum;
        $slidenum = (isset($slidenum))? $slidenum+1 : 1;
        
        // width and/or height are setted
        $container_style = '';
        if(isset($options['width'])) {
            $container_style        .= 'with='.$options['width'].'px;';
            $options['js']['$SlideWidth']  = $options['width'];
        }
        if(isset($options['height'])) {
            $container_style        .= 'height='.$options['height'].'px;';
            $options['js']['$SlideHeight']  = $options['height'];
        }
        if($container_style) $container_style = 'style="'.$container_style.'"';
        
        // main div
        $slider .= '<div id="slider'.$slidenum.'_container" class="sor-container" '.$container_style.'>'."\n";
        
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
             $slider .= '<div>'."\n";
            // main image
            if(isset($slide['image_src'])) {
                $slider .= '<img data-u="image" src="'.$slide['image_src'].'" />'."\n";
            }
            
            // HTML slide content 
            if(isset($slide['html'])) {
                $slider .= $slide['html'];
            }
            
            // caption
            if(isset($slide['caption'])) {
                $slider .= '<div data-u="caption" data-t="caption-transition-name" class="sor-caption">'."\n"
                        .  $slide['caption']."\n"
                        .  '</div>'."\n";
            }
            // thumb
            
            // close slide
            $slider .= '</div>'."\n";
        }  
        
        // end slider container
        $slider .= '</div>'."\n";
        
        // end main div
        $slider .= '</div>'."\n";
        
        // javascript initalization
        $js_options = (isset($options['js']))? $options['js'] : array('$AutoPlay' => true);
        $rootname   = 'slider'.$slidenum.'_';
        $js_script  = 
              '$(document).ready(function ($) {'."\n"
              . 'var '.$rootname.'options = '.  json_encode($js_options).";\n"
              . 'var '.$rootname.'jssor = new $JssorSlider$("'.$rootname.'container", '.$rootname.'options );'."\n";
        
        if(isset($options['fullwidth'])) {
               $js_script  .= Jssor::Makefullwidth($slidenum, $options['fullwidth']);
        }
         
        $js_script .= '});'."\n";
        
        Page::insert_script($js_script);
              
        
        return $slider;
    }
    
    private static function Makefullwidth($id, $fitTo) {
        
        
        $js_script = 'function ScaleSlider'.$id.'() {'."\n";
        
        
        switch ($fitTo) {
            case 'parent':
                
                $js_script .= 'var sliderW = slider'.$id.'_jssor.$Elmt.parentNode.clientWidth;'."\n";

                break;
            case 'body':
            default:
                
                 $js_script .= 'var sliderW = document.body.clientWidth;'."\n";
                
                break;
        }
        
        $js_script .= 
                'if(sliderW)'."\n"
              . '   slider'.$id.'_jssor.$ScaleWidth(Math.min(sliderW, 1920));'."\n"
              . 'else'."\n"
              . '   window.setTimeout(ScaleSlider'.$id.', 30);'."\n";
        
        $js_script .= '}'."\n";
        
        $js_script .= 'ScaleSlider'.$id.'();'

                   . '$(window).bind("load", ScaleSlider'.$id.');'."\n"
                   . '$(window).bind("resize", ScaleSlider'.$id.');'."\n"
                   . '$(window).bind("orientationchange", ScaleSlider'.$id.');'."\n";
        
        return $js_script;
        
    }
}

