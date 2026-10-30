<?php

/**
 * render files using interactive interface
 * (video, audio)
 * 
 * @author Bernard Paques (rendering)
 * @author Alexis Raimbault (factoring)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Code_embed extends Code {
    
    var $patterns = array(
        // [embed=<id>] or [embed=<id>, window, <label>]
        // [sound=<id>]
        // [flash=<id>] is a legacy alias of [embed=<id>]
        '/\[(embed|flash|sound)=([^\]]+?)\]/i'
    );
    
    public function render($matches) {
        return self::render_embed(codes::fix_tags($matches[1]));
    }
    
    /**
    * embed an interactive object
    *
    * The id designates the target file.
    * Add 'window' to play it in a separate window, as in: '12, window, <label>'
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
           $extension = strtolower(pathinfo($item['file_name'], PATHINFO_EXTENSION));

           // rendering depends on file extension
           switch($extension) {

           // stream a video/audio
            case 'mp3': 
            case 'm4a':
            case 'ogg':
            case 'oga': 
            case 'webma':
            case 'wav':
            case 'mp4':
            case 'm4v':
            case 'ogv':
            case 'webm':
            case 'webmv':

                   include_once $context['path_to_root'].'/included/jplayer/jplayer.php';
                
                   // file is elsewhere
                   if(isset($item['file_href']) && $item['file_href'])
                           $url = $item['file_href'];

                   // jplayer picks its player from the extension of the url, as in Files::interact()
                   else
                           $url = $context['url_to_master'].$context['url_to_root'].Files::get_url($item['id'], 'direct', $item['file_name']);


                   $output = jplayer::play($url);

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

