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
        $mode = $matches[0];
        
        switch ($mode) {
            case 'sound':
                $text = self::render_sound(codes::fix_tags($matches[1]));

                break;

            default:
                $text = self::render_embed(codes::fix_tags($matches[1]));
                break;
        }
        
        return $text;
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
                   elseif(!strcmp($extension, 'mm') && isset($context['skins_freemind_canvas_width']))
                           $attributes[1] = $context['skins_freemind_canvas_width'];
                   else
                           $attributes[1] = 480;
           }
           if(!isset($attributes[2])) {
                   if(!strcmp($extension, 'gan'))
                           $attributes[2] = '300px';
                   elseif(!strcmp($extension, 'mm') && isset($context['skins_freemind_canvas_height']))
                           $attributes[2] = $context['skins_freemind_canvas_height'];
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

           // stream a video
           case '3gp':
           case 'flv':
           case 'm4v':
           case 'mov':
           case 'mp4':

                   // a flash player to stream a flash video
                   $flvplayer_url = $context['url_to_home'].$context['url_to_root'].'included/browser/player_flv_maxi.swf';

                   // file is elsewhere
                   if(isset($item['file_href']) && $item['file_href'])
                           $url = $item['file_href'];

                   // prevent leeching (the flv player will provide session cookie, etc)
                   else
                           $url = $context['url_to_home'].$context['url_to_root'].Files::get_url($item['id'], 'fetch', $item['file_name']);

                   // pass parameters to the player
                   if($flashvars)
                           $flashvars = str_replace('autostart=true', 'autoplay=1', $flashvars).'&';
                   $flashvars .= 'width='.$width.'&height='.$height;

                   // if there is a static image for this video, use it
                   if(isset($item['icon_url']) && $item['icon_url'])
                           $flashvars .= '&startimage='.urlencode($item['icon_url']);

                   // if there is a subtitle file for this video, use it
                   if(isset($item['file_name']) && ($srt = 'files/'.str_replace(':', '/', $item['anchor']).'/'.str_replace('.'.$extension, '.srt', $item['file_name'])) && file_exists($context['path_to_root'].$srt))
                           $flashvars .= '&srt=1&srturl='.urlencode($context['url_to_home'].$context['url_to_root'].$srt);

                   // if there is a logo file in the skin, use it
                   Skin::define_img_href('FLV_IMG_HREF', 'codes/flvplayer_logo.png', '');
                   if(FLV_IMG_HREF)
                           $flashvars .= '&top1='.urlencode(FLV_IMG_HREF.'|10|10');

                   // rely on Flash
                   if(Surfer::has_flash()) {

                           // the full object is built in Javascript --see parameters at http://flv-player.net/players/maxi/documentation/
                           $output = '<div id="flv_'.$item['id'].'" class="no_print">Flash plugin or Javascript are turned off. Activate both and reload to view the object</div>'."\n";

                           Page::insert_script(
                                   'var flashvars = { flv:"'.$url.'", '.str_replace(array('&', '='), array('", ', ':"'), $flashvars).'", autoload:0, margin:1, showiconplay:1, playeralpha:50, iconplaybgalpha:30, showfullscreen:1, showloading:"always", ondoubleclick:"fullscreen" }'."\n"
                                   .'var params = { allowfullscreen: "true", allowscriptaccess: "always" }'."\n"
                                   .'var attributes = { id: "file_'.$item['id'].'", name: "file_'.$item['id'].'"}'."\n"
                                   .'swfobject.embedSWF("'.$flvplayer_url.'", "flv_'.$item['id'].'", "'.$width.'", "'.$height.'", "9", "'.$context['url_to_home'].$context['url_to_root'].'included/browser/expressinstall.swf", flashvars, params);'."\n"
                                   );

                   // native support
                   } else {

                           // <video> is HTML5, <object> is legacy
                           $output = '<video width="'.$width.'" height="'.$height.'" autoplay="" controls="" src="'.$url.'" >'."\n"
                                   .'	<object width="'.$width.'" height="'.$height.'" data="'.$url.'" type="'.Files::get_mime_type($item['file_name']).'">'."\n"
                                   .'		<param value="'.$url.'" name="movie" />'."\n"
                                   .'		<param value="true" name="allowFullScreen" />'."\n"
                                   .'		<param value="always" name="allowscriptaccess" />'."\n"
                                   .'		<a href="'.$url.'">No video playback capabilities, please download the file</a>'."\n"
                                   .'	</object>'."\n"
                                   .'</video>'."\n";

                   }

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

           // a Freemind map
           case 'mm':

                   // if we have an external reference, use it
                   if(isset($item['file_href']) && $item['file_href']) {
                           $target_href = $item['file_href'];

                   // else redirect to ourself
                   } else {

                           // ensure a valid file name
                           $file_name = utf8::to_ascii($item['file_name']);

                           // where the file is
                           $path = Files::get_path($item['anchor']).'/'.rawurlencode($item['file_name']);

                           // map the file on the regular web space
                           $url_prefix = $context['url_to_home'].$context['url_to_root'];

                           // redirect to the actual file
                           $target_href = $url_prefix.$path;
                   }

                   // allow several viewers to co-exist in the same page
                   static $freemind_viewer_index;
                   if(!isset($freemind_viewer_index))
                           $freemind_viewer_index = 1;
                   else
                           $freemind_viewer_index++;

                   // load flash player
                   $url = $context['url_to_home'].$context['url_to_root'].'included/browser/visorFreemind.swf';

                   // variables
                   $flashvars = 'initLoadFile='.$target_href.'&openUrl=_self';

                   $output = '<div id="freemind_viewer_'.$freemind_viewer_index.'">Flash plugin or Javascript are turned off. Activate both and reload to view the object</div>'."\n";

                   Page::insert_script(
                           'var params = {};'."\n"
                           .'params.base = "'.dirname($url).'/";'."\n"
                           .'params.quality = "high";'."\n"
                           .'params.wmode = "transparent";'."\n"
                           .'params.menu = "false";'."\n"
                           .'params.flashvars = "'.$flashvars.'";'."\n"
                           .'swfobject.embedSWF("'.$url.'", "freemind_viewer_'.$freemind_viewer_index.'", "'.$width.'", "'.$height.'", "6", "'.$context['url_to_home'].$context['url_to_root'].'included/browser/expressinstall.swf", false, params);'."\n"
                           );

                   // offer to download a copy of the map
                   $menu = array($target_href => i18n::s('Browse this map with Freemind'));

                   // display menu commands below the viewer
                   $output .= Skin::build_list($menu, 'menu_bar');

                   // job done
                   return $output;

           // native flash
           case 'swf':

                   // where to get the file
                   if(isset($item['file_href']) && $item['file_href'])
                           $url = $item['file_href'];

                   // we provide the native file because of basename
                   else
                           $url = $context['url_to_home'].$context['url_to_root'].'files/'.str_replace(':', '/', $item['anchor']).'/'.rawurlencode($item['file_name']);

                   $output = '<div id="swf_'.$item['id'].'" class="no_print">Flash plugin or Javascript are turned off. Activate both and reload to view the object</div>'."\n";

                   Page::insert_script(
                           'var params = {};'."\n"
                           .'params.base = "'.dirname($url).'/";'."\n"
                           .'params.quality = "high";'."\n"
                           .'params.wmode = "transparent";'."\n"
                           .'params.allowfullscreen = "true";'."\n"
                           .'params.allowscriptaccess = "always";'."\n"
                           .'params.flashvars = "'.$flashvars.'";'."\n"
                           .'swfobject.embedSWF("'.$url.'", "swf_'.$item['id'].'", "'.$width.'", "'.$height.'", "6", "'.$context['url_to_home'].$context['url_to_root'].'included/browser/expressinstall.swf", false, params);'."\n"
                           );

                   return $output;

           // link to file page
           default:

                   // link label
                   $text = Skin::strip( $item['title']?$item['title']:str_replace('_', ' ', $item['file_name']) );

                   // make a link to the target page
                   $url = Files::get_permalink($item);

                   // return a complete anchor
                   $output =& Skin::build_link($url, $text);
                   return $output;

           }

    }
    
    /** 
     * render a sound object with dewplayer
     * 
     * @global type $context
     * @param type $id
     * @return string 
     */
    public static function render_sound($id) {
        global $context;
        
        // maybe an alternate title has been provided
        $attributes = preg_split("/\s*,\s*/", $id, 2);
        $id = $attributes[0];
        $flashvars = '';
        if(isset($attributes[1]))
                $flashvars = $attributes[1];

        // get the file
        if(!$item = Files::get($id)) {
                $output = '[sound='.$id.']';
                return $output;
        }

        // where to get the file
        if(isset($item['file_href']) && $item['file_href'])
                $url = $item['file_href'];
        else
                $url = $context['url_to_home'].$context['url_to_root'].'files/'.str_replace(':', '/', $item['anchor']).'/'.rawurlencode($item['file_name']);

        // several ways to play flash
        switch(strtolower(substr(strrchr($url, '.'), 1))) {

        // stream a sound file
        case 'mp3':

                // a flash player to stream a sound
                $dewplayer_url = $context['url_to_root'].'included/browser/dewplayer.swf';
                if($flashvars)
                        $flashvars = 'son='.$url.'&'.$flashvars;
                else
                        $flashvars = 'son='.$url;

                $output = '<div id="sound_'.$item['id'].'" class="no_print">Flash plugin or Javascript are turned off. Activate both and reload to view the object</div>'."\n";

                Page::insert_script(
                        'var params = {};'."\n"
                        .'params.base = "'.dirname($url).'/";'."\n"
                        .'params.quality = "high";'."\n"
                        .'params.wmode = "transparent";'."\n"
                        .'params.menu = "false";'."\n"
                        .'params.flashvars = "'.$flashvars.'";'."\n"
                        .'swfobject.embedSWF("'.$dewplayer_url.'", "sound_'.$item['id'].'", "200", "20", "6", "'.$context['url_to_home'].$context['url_to_root'].'included/browser/expressinstall.swf", false, params);'."\n"
                        );
                return $output;

        // link to file page
        default:

                // link label
                $text = Skin::strip( $item['title']?$item['title']:str_replace('_', ' ', $item['file_name']) );

                // make a link to the target page
                $url = Files::get_download_url($item);

                // return a complete anchor
                $output =& Skin::build_link($url, $text, 'basic');
                return $output;

        }
        
    }
}

