<?php

/**
 * render files using interactive interface
 * (video, audio, mindmap...)
 * 
 * @author Bernard Paques (rendering)
 * @author Alexis Raimbault (factoring)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Code_embed extends Code {
    
    var $patterns = array(
        // [embed=<id>, <width>, <height>, <params>] or [embed=<id>, window]
        // [flash=<id>, <width>, <height>, <params>] or [flash=<id>, window]
        // [sound=<id>]
        '/\[(embed|flash|sound)=([^\]]+?)\]/i'
    );
    
    public function render($matches) {
        $text = '';
        

        $text = self::render_embed(codes::fix_tags($matches[1]));
               
    }
    
    /**
    * embed an interactive object
    *
    * The id designates the target file.
    * It can also include width and height of the target canvas, as in: '12, 100%, 250px'
    *
    * @param string id of the target file
    * @return string the rendered string
    **/
    public static function render_embed($id) {
           global $context;

           // split parameters
           $attributes = preg_split("/\s*,\s*/", $id, 4);
           $id = $attributes[0];

           // get the file
           if(!$item = Files::get($id)) {
                   $output = '[embed='.$id.']';
                   return $output;
           }

           // stream in a separate page
           if(isset($attributes[1]) && preg_match('/window/i', $attributes[1])) {
                   if(!isset($attributes[2]))
                           $attributes[2] = i18n::s('Play in a separate window');
                   $output = '<a href="'.$context['url_to_home'].$context['url_to_root'].Files::get_url($item['id'], 'stream', $item['file_name']).'" onclick="window.open(this.href); return false;" class="button"><span>'.$attributes[2].'</span></a>';
                   return $output;
           }

           // file extension
           $extension = strtolower(substr($item['file_name'], -3));

           // set a default size
           if(!isset($attributes[1])) {
                   if(!strcmp($extension, 'gan'))
                           $attributes[1] = '98%';
     
                   else
                           $attributes[1] = 480;
           }
           if(!isset($attributes[2])) {
                   if(!strcmp($extension, 'gan'))
                           $attributes[2] = '300px';
                   
                   else
                           $attributes[2] = 360;
           }

           // object attributes
           $width = $attributes[1];
           $height = $attributes[2];
           $flashvars = '';
           if(isset($attributes[3]))
                   $flashvars = $attributes[3];

           // rendering depends on file extension
           switch($extension) {

           // stream a video/audio
            case 'mp3': 
            case 'm4a':
            case 'ogg':
            case 'oga': 
            case 'webma':
            case 'wav':
            case 'fla':
            case 'mp4':
            case 'm4v':
            case 'ogv':
            case 'webm':
            case 'webmv':
            case 'flv':     

                   include_once $context['path_to_root'].'/included/jplayer/jplayer.php';
                
                   // file is elsewhere
                   if(isset($item['file_href']) && $item['file_href'])
                           $url = $item['file_href'];

                   // prevent leeching (the flv player will provide session cookie, etc)
                   else
                           $url = $context['url_to_root'].Files::get_url($item['id'], 'fetch', $item['file_name']);


                   $output = jplayer::play($url);

                   // job done
                   return $output;

           // a ganttproject timeline
           case 'gan':

                   // where the file is
                   $path = Files::get_path($item['anchor']).'/'.rawurlencode($item['file_name']);

                   // we actually use a transformed version of the file
                   $cache_id = Cache::hash($path).'.xml';

                   // apply the transformation
                   if(!file_exists($context['path_to_root'].$cache_id) || (filemtime($context['path_to_root'].$cache_id) < filemtime($context['path_to_root'].$path)) || (!$text = Safe::file_get_contents($context['path_to_root'].$cache_id))) {

                           // transform from GanttProject to SIMILE Timeline
                           $text = Files::transform_gan_to_simile($path);

                           // put in cache
                           Safe::file_put_contents($cache_id, $text);

                   }

                   // load the SIMILE Timeline javascript library in shared/global.php
                   $context['javascript']['timeline'] = TRUE;

                   // cache would kill the loading of the library
                   cache::poison();

                   // 1 week ago
                   $now = gmdate('M d Y H:i:s', time()-7*24*60*60);

                   // load the right file
                   $output = '<div id="gantt" style="height: '.$height.'; width: '.$width.'; border: 1px solid #aaa; font-family: Trebuchet MS, Helvetica, Arial, sans serif; font-size: 8pt"></div>'."\n";

                   Page::insert_script(
                           'var simile_handle;'."\n"
                           .'function onLoad() {'."\n"
                           .'  var eventSource = new Timeline.DefaultEventSource();'."\n"
                           .'	var theme = Timeline.ClassicTheme.create();'."\n"
                           .'            theme.event.bubble.width = 350;'."\n"
                           .'            theme.event.bubble.height = 300;'."\n"
                           .'  var bandInfos = ['."\n"
                           .'    Timeline.createBandInfo({'."\n"
                           .'        eventSource:    eventSource,'."\n"
                           .'        date:           "'.$now.'",'."\n"
                           .'        width:          "80%",'."\n"
                           .'        intervalUnit:   Timeline.DateTime.WEEK,'."\n"
                           .'        intervalPixels: 200,'."\n"
                           .'		  theme:          theme,'."\n"
                           .'        layout:         "original"  // original, overview, detailed'."\n"
                           .'    }),'."\n"
                           .'    Timeline.createBandInfo({'."\n"
                           .'        showEventText: false,'."\n"
                           .'        trackHeight: 0.5,'."\n"
                           .'        trackGap: 0.2,'."\n"
                           .'        eventSource:    eventSource,'."\n"
                           .'        date:           "'.$now.'",'."\n"
                           .'        width:          "20%",'."\n"
                           .'        intervalUnit:   Timeline.DateTime.MONTH,'."\n"
                           .'        intervalPixels: 50'."\n"
                           .'    })'."\n"
                           .'  ];'."\n"
                           .'  bandInfos[1].syncWith = 0;'."\n"
                           .'  bandInfos[1].highlight = true;'."\n"
                           .'  bandInfos[1].eventPainter.setLayout(bandInfos[0].eventPainter.getLayout());'."\n"
                           .'  simile_handle = Timeline.create(document.getElementById("gantt"), bandInfos, Timeline.HORIZONTAL);'."\n"
                           .'	simile_handle.showLoadingMessage();'."\n"
                           .'  Timeline.loadXML("'.$context['url_to_home'].$context['url_to_root'].$cache_id.'", function(xml, url) { eventSource.loadXML(xml, url); });'."\n"
                           .'	simile_handle.hideLoadingMessage();'."\n"
                           .'}'."\n"
                           ."\n"
                           .'var resizeTimerID = null;'."\n"
                           .'function onResize() {'."\n"
                           .'    if (resizeTimerID == null) {'."\n"
                           .'        resizeTimerID = window.setTimeout(function() {'."\n"
                           .'            resizeTimerID = null;'."\n"
                           .'            simile_handle.layout();'."\n"
                           .'        }, 500);'."\n"
                           .'    }'."\n"
                           .'}'."\n"
                           ."\n"
                           .'// observe page major events'."\n"
                           .'$(document).ready( onLoad);'."\n"
                           .'$(window).resize(onResize);'."\n"
                           );

                   // job done
                   return $output;

          
           // link to file page
           default:

                   // link label
                   $text = Skin::strip( $item['title']?$item['title']:str_replace('_', ' ', $item['file_name']) );

                   // make a link to the target page
                   $url = Files::get_permalink($item);

                   // return a complete anchor
                   $output = Skin::build_link($url, $text);
                   return $output;

           }

    }
    
    
        
   
}

