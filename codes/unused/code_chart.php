<?php

/**
 * render a chart (swf object)
 * 
 * @author Bernard Paques (rendering)
 * @author Alexis Raimbault (factoring)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Code_chart extends Code {
    
    var $patterns = array(
            '/\[chart=([^\]]+?)\](.*?)\[\/chart\]/is'       // [chart=<width>, <height>, <params>]...[/chart]
        );
    
    public function render($matches) {
        $text = '';
        list($variant, $data) = $matches;
        
        $text = self::render_chart(Codes::fix_tags($data), $variant);
        
        return $text;
    }
    
    /**
    * render a chart
    *
    * @param string chart data, in JSON format
    * @param string chart parameters
    * @return string the rendered text
    **/
    public static function render_chart($data, $variant) {
           global $context;

           // split parameters
           $attributes = preg_split("/\s*,\s*/", $variant, 4);

           // set a default size
           if(!isset($attributes[0]))
                   $attributes[0] = 320;
           if(!isset($attributes[1]))
                   $attributes[1] = 240;

           // object attributes
           $width = $attributes[0];
           $height = $attributes[1];
           $flashvars = '';
           if(isset($attributes[2]))
                   $flashvars = $attributes[2];

           // allow several charts to co-exist in the same page
           static $chart_index;
           if(!isset($chart_index))
                   $chart_index = 1;
           else
                   $chart_index++;

           $url = $context['url_to_home'].$context['url_to_root'].'included/browser/open-flash-chart.swf';
           $text = '<div id="open_flash_chart_'.$chart_index.'" class="no_print">Flash plugin or Javascript are turned off. Activate both and reload to view the object</div>'."\n";

           Page::insert_script(
                   'var params = {};'."\n"
                   .'params.base = "'.dirname($url).'/";'."\n"
                   .'params.quality = "high";'."\n"
                   .'params.wmode = "opaque";'."\n"
                   .'params.allowscriptaccess = "always";'."\n"
                   .'params.menu = "false";'."\n"
                   .'params.flashvars = "'.$flashvars.'";'."\n"
                   .'swfobject.embedSWF("'.$url.'", "open_flash_chart_'.$chart_index.'", "'.$width.'", "'.$height.'", "6", "'.$context['url_to_home'].$context['url_to_root'].'included/browser/expressinstall.swf", {"get-data":"get_open_flash_chart_'.$chart_index.'"}, params);'."\n"
                   ."\n"
                   .'var chart_data_'.$chart_index.' = '.trim(str_replace(array('<br />', "\n"), ' ', $data)).';'."\n"
                   ."\n"
                   .'function get_open_flash_chart_'.$chart_index.'() {'."\n"
                   .'	return $.toJSON(chart_data_'.$chart_index.');'."\n"
                   .'}'."\n"
                   );

           return $text;

    }
}
