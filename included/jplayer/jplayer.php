<?php

/**
 * Class helper to include jplayer 
 * for reading audio and video files
 * 
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class jplayer {
    
    
    /**
     * Load js & css to display the player
     * 
     * @param string $skin the name of the skin to use
     */
    public static function load($skin='blue.monday') {
        global $context;
        
        $path_to_jskin = $context['path_to_root'].'included/jplayer/skin/'.$skin;
        
        // check skin folder
        if(!is_dir($path_to_jskin)) {
                logger::debug ('failed to load jplayer with skin named '.$skin, 'jplayer');
                return;
        }
        
        
        // load css, try min version first
        $css = $path_to_jskin.'/css/jplayer.' . $skin;
        
        if(file_exists($css . '.min.css')) {
            Page::load_style('included/jplayer/skin/'.$skin . '/css/jplayer.' . $skin .'.min.css');
        } elseif(file_exists($css . '.css')) {
            Page::load_style('included/jplayer/skin/'.$skin . '/css/jplayer.' . $skin .'.css');
        } else {
            logger::debug ('failed to load jplayer css '.$css.'(.min).css', 'jplayer');
            return;
        }
        
        // load js
        if(file_exists($context['path_to_root'].'included/jplayer/jquery.jplayer.min.js')) {
            Page::defer_script('included/jplayer/jquery.jplayer.min.js');
        } else {
            logger::debug ('Missing jplayer main js lib', 'jplayer');
            return;
        }
        
        // flag that we are loaded
        $context['javascript']['jplayer'] = $skin;
        
    }
    
    /**
     * 
     * @param string $mustache
     * @param mixed $id_suffix
     * @param json $js_options
     */
    public static function render($mustache='audio.single', $id_suffix=NULL, $js_options=array(), $media=NULL) {
        global $context;
        static $autosuffix= 1;
        
        // check jplayer loaded
        if(!isset($context['javascript']['jplayer'])) {
            jplayer::load();
        }
        if(!$skin = $context['javascript']['jplayer']){
            logger::debug('lib or skin not loaded', 'jplayer');
            return;
        }
        
        // check if mustache exists in the skin
        $path_to_mustache = $context['path_to_root'].'included/jplayer/skin/'.$skin.'/mustache/jplayer.'.$skin.'.'.$mustache.'.html';
        if(!file_exists($path_to_mustache)) {
            logger::debug('`Mustache` not found '.$mustache, 'jplayer');
            return;
        }
        
        // automatic id suffix if not specified
        if(!$id_suffix) {
            
            $id_suffix = $autosuffix;
            $autosuffix++;
        }
        
        // html id for tags
        $player     = 'jp_player_'.$id_suffix;
        $wrapper    = 'jp_container_'.$id_suffix;
        
        // set wrapper in options
        $js_options['cssSelectorAncestor'] = "'#".$wrapper."'";
        
        $patterns       = array('/{{JPLAYER}}/','/{{WRAPPER}}/');
        $remplacements  = array($player, $wrapper);
        
        // load mustache
        $template = preg_replace($patterns, $remplacements, Safe::file_get_contents($path_to_mustache));
        
        // insert media into options
        
        
        // include_once $context['path_to_root'].'included/json.php';
        
        // js to start the jplayer
        Page::insert_script(''
                . '$(document).ready(function(){'
                . ' $("#'.$player.'").jPlayer('
                //. json_encode($js_options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_FORCE_OBJECT)
                . jplayer::to_json($js_options)
                . ' );'
                . '});');
        
        // return html output
        return $template;
    }
    
    /**
     * Create a jplayer for a media with default options
     * autodetect file type to choose audio or video player
     * 
     * @param mixed file id or url path
     */
    public static function play($media) {
        global $context;
        
        $output = '';
        
        // check media
        if(!$media = jplayer::check_media($media)) return $output;
        
        // choose player variant
        $variant = jplayer::select_variant($media['ext']);
        
        // sanity check
        if(!$variant) {
            logger::error(sprintf(i18n::s('File %s cannot be taken in charge by jplayer'), $media));
            return;
        }
        
        // set default options
        $mustache = $variant.'.single';
        
        $js_options                     = array();
        $js_options['solution']         = "'html, flash'";
        $js_options['volume']           = 0.8;
        $js_options['muted']            = "false";
        $js_options['useStateClassSkin']= "true";
        
        // error reporting depends on yacs
        $debug = ($context['with_debug']==='Y')?'true':'false';
        
        $js_options['errorAlerts']      = $debug;
        $js_options['warningAlerts']    = $debug;
        $js_options['swfPath']          = "'".$context['path_to_root'].'included/jplayer'."'";
        $js_options['wmode']            = "'window'";
        $js_options['keyEnabled']       = "true";
        $js_options['remainingDuration']= "true";
        $js_options['toggleDuration']   = "true";
        
        // add media
        $js_options = array_merge($js_options, jplayer::format_media($media));
        
        $output .= jplayer::render($mustache, null, $js_options, $media);
        
        return $output;
        
    }
    
    private static function to_json($js_options) {
        
        $json = '{'."\n";
        
        foreach($js_options as $key => $option) {
            
            $json .= "\t" . $key . ' : ' . $option . ",\n";
            
        }
        
        $json .= '}'."\n";
        
        return $json;
        
    }
    
    private static function format_media($media_info) {
        
        /*ready: function (event) {
			$(this).jPlayer("setMedia", {
				title: "Bubble",
				m4a: "http://jplayer.org/audio/m4a/Miaow-07-Bubble.m4a",
				oga: "http://jplayer.org/audio/ogg/Miaow-07-Bubble.ogg"
			});
		},*/
        
        // supplied: "m4a, oga",
        
        $formated = array();
        
        
        $formated['ready']      = 'function (event) {$(this).jPlayer("setMedia", {title:"'.$media_info['title'].'", '.$media_info['ext'].': "'.$media_info['url'].'"});}';
        $formated['supplied']   =  "'".$media_info['ext']."'";
        
        return $formated;
        
    }
    
    private static function check_media($media) {
        
        
        $media_info = array();
        
        // maybe a yacs file
        if(is_numeric($media) && $file=Files::get($media)) {
            
            $media_info['title'] = $file['title'];
            $media_info['url']   = Files::get_url($file['id'], 'view', $file['file_name']);
            $media_info['ext']   = pathinfo($file['file_name'], PATHINFO_EXTENSION);
            
        // or a web link    
        } elseif($url = parse_url($media)) {
         
            $media_info['title'] = pathinfo($url['path'], PATHINFO_BASENAME);
            $media_info['url']   = Skin::build_link($media, NULL, 'raw');
            $media_info['ext']   = pathinfo($url['path'], PATHINFO_EXTENSION);
            
        } else 
            return NULL;
        
        return $media_info;
    }
    
    private static function select_variant($ext) {
        
        $variant = NULL;
        
        switch($ext) {
            case 'mp3': 
            case 'm4a':
            case 'ogg':
            case 'oga': 
            case 'webma':
            case 'wav':
            case 'fla':
                $variant = 'audio';
                break;
            case 'mp4':
            case 'm4v':
            case 'ogv':
            case 'webm':
            case 'webmv':
            case 'flv':
                $variant = 'video';
                break;
            default:
                $variant = NULL;
        }
        
        return $variant;
    }
    
}