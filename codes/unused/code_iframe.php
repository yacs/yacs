<?php

/**
 * render a url in a iframe
 * 
 * @author Bernard Paques (rendering)
 * @author Alexis Raimbault (factoring)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Code_iframe extends Code {
    
    var $patterns = array(
        '/\[iframe(?:=([^\]]+?))?\](.*?)\[\/iframe\]/is'   // [iframe]<url>[/iframe] [iframe=<width>, <height>]<url>[/iframe]
    );    
    
    
    public function render($matches) {
        $text = '';
        $variant = $url = '';
       
        
        if(count($matches) > 1) {
            list($variant, $url) = $matches;
        } else {
            $url = $matches[0];
        }
        
        $text = self::render_iframe($url, $variant);
        
        return $text;
    }
    
    /**
    * render an iframe
    *
    * @param string URL to be embedded
    * @param string iframe parameters
    * @return string the rendered text
    **/
    public static function render_iframe($url, $variant) {
           global $context;

           // split parameters
           $attributes = preg_split("/\s*,\s*/", $variant, 2);

           // set a default size
           if(!isset($attributes[0]))
                   $attributes[0] = 320;
           if(!isset($attributes[1]))
                   $attributes[1] = 240;

           $text = '<iframe src="'.$url.'" style="width: '.$attributes[0].'px; height: '.$attributes[1].'px" scrolling="no" marginwidth="0" marginheight="0" frameborder="0" vspace="0" hspace="0">'."\n"
                   .i18n::s('Your browser does not accept iframes')
                   .'</iframe>';

           return $text;

    }
}

