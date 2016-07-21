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
     * transform array into json object. Used internaly to set options for js
     * string values that begins by "$" must not be quoted
     * (jssor syntaxe)
     * 
     * @param array $data
     * @return string
     */
    private static function array_2_js_object( array $data ) {

        $result = '{';

        $separator = '';
        foreach( $data as $key=>$val ) {
           $result .= $separator . $key . ':';

           if( is_int( $val ) ) {
              $result .= $val;
           } elseif( is_string( $val ) ) {
              if($val[0] === '$') {
                    $result .= $val;
              } else {
                    $result .= '"' . str_replace( '"', '\"', $val) . '"';
              }
           } elseif( is_bool( $val ) ) {
              $result .= $val ? 'true' : 'false';
           } elseif( is_array( $val )) {
               $result .= Jssor::array_2_js_object($val);
           } else {
              $result .= $val;
           }

           $separator = ', ';
        }

        $result .= '}';

        return $result;
     }

    
    /**
     * Load jssor libs 
     * 
     * @global array $context
     * @param boolean $withCSS option to load defaut CSS for jssor slider
     */
    public static function Load($withCSS=true) {
        global $context;
        
        // note : we assume jquery is loaded
        Page::defer_script('included/jssor/js/jssor.slider.min.js');
        if($withCSS) {
            Page::load_style('included/jssor/css/jssor.css');
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
        
        $slider = '<!-- #region Jssor Slider Begin -->'."\n";
        
        // unique id for the slider
        static $slidenum;
        $slidenum = (isset($slidenum))? $slidenum+1 : 1;
        
        // width and/or height are setted
        $container_style = '';
        $moreclass       = '';
        if(isset($options['width'])) {
            $container_style        .= 'width:'.$options['width'].'px;';
            //$options['js']['$SlideWidth']  = $options['width'];
        }
        if(isset($options['height'])) {
            $container_style        .= 'height:'.$options['height'].'px;';
            //$options['js']['$SlideHeight']  = $options['height'];
        }
        
        page::insert_style('#slider'.$slidenum.' .sor-container {'.$container_style.'}');
        
        // special class if with thumbnails
        if(isset($options['thumbnails']))
              $moreclass .= ' wthumbs';
        
        // autoplay
        if(isset($options['autoplay'])) {
            $options['autoplay'] = true;
            $options['js']['$AutoPlay'] = 1;
        } else {
            $options['autoplay'] = false;
            $options['js']['$AutoPlay'] = 0;
        }
        
        // fillmode
        if(isset($options['fillmode'])) {
           $options['js']['$FillMode'] = (int) $options['fillmode'];
        }
        
        // main div
        $slider .= '<div id="slider'.$slidenum.'"><div id="slider'.$slidenum.'_container" class="sor-container'.$moreclass.'" >'."\n";
        
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
            
            // thumb
            if(isset($slide['thumb_src']) && isset($options['thumbnails'])) {
                $slider .= '<img data-u="thumb" src="'.$slide['thumb_src'].'" />'."\n";
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

            
            // close slide
            $slider .= '</div>'."\n";
        }  
        
        // end slider container
        $slider .= '</div>'."\n";
        
        // bullet navigator
        if(isset($options['bullets'])) {
            $slider .= '<!-- bullet navigator container -->'."\n";
            $slider .= '<div data-u="navigator" class="jssorb21" style="bottom: 26px; right: 6px;">'."\n";
            $slider .= '<!-- bullet navigator item prototype -->'."\n";
            $slider .= '<div data-u="prototype"></div>'."\n";
            $slider .= '</div>'."\n";
            $slider .= '<!-- Bullet Navigator Skin End -->'."\n";
        }
        
        // thumbnail navigator
        if(isset($options['thumbnails'])) {
            $slider .= '<!-- Thumbnail Navigator -->' . "\n";
            $slider .= '<div data-u="thumbnavigator" class="jssort01" style="position:absolute;left:0px;bottom:0px;width:600px;height:100px;" data-autocenter="1">' . "\n";
            $slider .= '    <!-- Thumbnail Item Skin Begin -->' . "\n";
            $slider .= '    <div data-u="slides" style="cursor: default;">' . "\n";
            $slider .= '        <div data-u="prototype" class="p">' . "\n";
            $slider .= '           <div class="w">' . "\n";
            $slider .= '              <div data-u="thumbnailtemplate" class="t"></div>' . "\n";
            $slider .= '           </div>' . "\n";
            $slider .= '           <div class="c"></div>' . "\n";
            $slider .= '        </div>' . "\n";
            $slider .= '    </div>' . "\n";
            $slider .= '</div>' . "\n";
            $slider .= '<!-- Thumbnail Item Skin End -->' . "\n";
        }
        
        // arrow navigator
        if(isset($options['arrows'])) {
            $slider .= '<!-- Arrow Navigator Skin Begin -->'."\n";
            $slider .= '<span data-u="arrowleft" class="jssora21l" style="top: 123px; left: 8px;">'.'</span>'."\n";
            $slider .= '<span data-u="arrowright" class="jssora21r" style="top: 123px; right: 8px;">'.'</span>'."\n";
            $slider .= '<!-- Arrow Navigator Skin End -->'."\n";
        }
        
        // end main div
        $slider .= '</div></div>'."\n";
        
        // javascript initalization
        //$js_options = new stdClass();
        $js_options = (isset($options['js']))? $options['js'] : array('$AutoPlay' => $options['autoplay']);
        
        
        // transitions
        static $transition_fuse = false; // declare transitions only once
        if(!$transition_fuse && $options['autoplay']) {
            $jssor_SlideshowTransitions = 'var $slider_transitions = [
              {$Duration:1200,x:0.3,$During:{$Left:[0.3,0.7]},$Easing:{$Left:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2},
              {$Duration:1200,x:-0.3,$SlideOut:true,$Easing:{$Left:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2},
              {$Duration:1200,x:-0.3,$During:{$Left:[0.3,0.7]},$Easing:{$Left:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2},
              {$Duration:1200,x:0.3,$SlideOut:true,$Easing:{$Left:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2},
              {$Duration:1200,y:0.3,$During:{$Top:[0.3,0.7]},$Easing:{$Top:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2},
              {$Duration:1200,y:-0.3,$SlideOut:true,$Easing:{$Top:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2},
              {$Duration:1200,y:-0.3,$During:{$Top:[0.3,0.7]},$Easing:{$Top:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2},
              {$Duration:1200,y:0.3,$SlideOut:true,$Easing:{$Top:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2},
              {$Duration:1200,x:0.3,$Cols:2,$During:{$Left:[0.3,0.7]},$ChessMode:{$Column:3},$Easing:{$Left:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2},
              {$Duration:1200,x:0.3,$Cols:2,$SlideOut:true,$ChessMode:{$Column:3},$Easing:{$Left:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2},
              {$Duration:1200,y:0.3,$Rows:2,$During:{$Top:[0.3,0.7]},$ChessMode:{$Row:12},$Easing:{$Top:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2},
              {$Duration:1200,y:0.3,$Rows:2,$SlideOut:true,$ChessMode:{$Row:12},$Easing:{$Top:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2},
              {$Duration:1200,y:0.3,$Cols:2,$During:{$Top:[0.3,0.7]},$ChessMode:{$Column:12},$Easing:{$Top:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2},
              {$Duration:1200,y:-0.3,$Cols:2,$SlideOut:true,$ChessMode:{$Column:12},$Easing:{$Top:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2},
              {$Duration:1200,x:0.3,$Rows:2,$During:{$Left:[0.3,0.7]},$ChessMode:{$Row:3},$Easing:{$Left:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2},
              {$Duration:1200,x:-0.3,$Rows:2,$SlideOut:true,$ChessMode:{$Row:3},$Easing:{$Left:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2},
              {$Duration:1200,x:0.3,y:0.3,$Cols:2,$Rows:2,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$ChessMode:{$Column:3,$Row:12},$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2},
              {$Duration:1200,x:0.3,y:0.3,$Cols:2,$Rows:2,$During:{$Left:[0.3,0.7],$Top:[0.3,0.7]},$SlideOut:true,$ChessMode:{$Column:3,$Row:12},$Easing:{$Left:$Jease$.$InCubic,$Top:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2},
              {$Duration:1200,$Delay:20,$Clip:3,$Assembly:260,$Easing:{$Clip:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2},
              {$Duration:1200,$Delay:20,$Clip:3,$SlideOut:true,$Assembly:260,$Easing:{$Clip:$Jease$.$OutCubic,$Opacity:$Jease$.$Linear},$Opacity:2},
              {$Duration:1200,$Delay:20,$Clip:12,$Assembly:260,$Easing:{$Clip:$Jease$.$InCubic,$Opacity:$Jease$.$Linear},$Opacity:2},
              {$Duration:1200,$Delay:20,$Clip:12,$SlideOut:true,$Assembly:260,$Easing:{$Clip:$Jease$.$OutCubic,$Opacity:$Jease$.$Linear},$Opacity:2}
            ];';
            
            $transition_fuse = true;
        } else
            $jssor_SlideshowTransitions = '';
        
        
        
        $js_options['$SlideshowOptions'] = array(
            '$Class'                    => '$JssorSlideshowRunner$');
        
        if($options['autoplay']) {
            $js_options['$SlideshowOptions']['$Transitions']      = '$slider_transitions';
            $js_options['$SlideshowOptions']['$TransitionsOrder'] = 1;
        }
        
        // options for bullet navigator
        if(isset($options['bullets'])) {
            $js_options['$BulletNavigatorOptions'] = array(
                '$Class'              => '$JssorBulletNavigator$',        //[Required] Class to create navigator instance
                '$ChanceToShow'       => 2,                               //[Required] 0 Never, 1 Mouse Over, 2 Always
                '$AutoCenter'         => 1,                               //[Optional] Auto center navigator in parent container, 0 None, 1 Horizontal, 2 Vertical, 3 Both, default value is 0
                '$Steps'              => 1,                               //[Optional] Steps to go for each navigation request, default value is 1
                '$Lanes'              => 1,                               //[Optional] Specify lanes to arrange items, default value is 1
                '$SpacingX'           => 8,                               //[Optional] Horizontal space between each item in pixel, default value is 0
                '$SpacingY'           => 8,                               //[Optional] Vertical space between each item in pixel, default value is 0
                '$Orientation'        => 1                                //[Optional] The orientation of the navigator, 1 horizontal, 2 vertical, default value is 1
            );
        }
        
        // option for Arrow Navigator
        if(isset($options['arrows'])) {
            $js_options['$ArrowNavigatorOptions'] = array(
                '$Class'              => '$JssorArrowNavigator$',         //[Required] Class to create arrow navigator instance
                '$ChanceToShow'       => 1,                               //[Required] 0 Never, 1 Mouse Over, 2 Always
                '$AutoCenter'         => 2,                               //[Optional] Auto center arrows in parent container, 0 No, 1 Horizontal, 2 Vertical, 3 Both, default value is 0
                '$Steps'              => 1                                //[Optional] Steps to go for each navigation request, default value is 1
            );
        }
        
        //option for thumbnails navigator
        if(isset($options['thumbnails'])) {
            $js_options['$ThumbnailNavigatorOptions'] = array(
                '$Class'            => '$JssorThumbnailNavigator$',
                '$Cols'             => 10,
                '$SpacingX'         => 8,
                '$SpacingY'         => 8,
                '$AutoCenter'       => 1,
                '$Align'            => 360
            );
        }
        
        $rootname   = 'slider'.$slidenum.'_';
        $js_script  = 
              'var '.$rootname.'options = {};var '.$rootname.'jssor = {};'
              . '$(document).ready(function ($) {'."\n"
              . $jssor_SlideshowTransitions."\n"
              . $rootname.'options = '.  Jssor::array_2_js_object( $js_options ) .";\n"
              . $rootname.'jssor = new $JssorSlider$("'.$rootname.'container", '.$rootname.'options );'."\n";
        
        if(isset($options['fullwidth'])) {
               $js_script  .= Jssor::Makefullwidth($slidenum, $options['fullwidth']);
        }
         
        $js_script .= '});'."\n";
        
        Page::insert_script($js_script);
              
        $slider .= '<!-- #endregion Jssor Slider End -->'."\n";
        
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