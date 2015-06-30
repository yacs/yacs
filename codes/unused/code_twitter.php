<?php

/**
 * twitter widgets
 * 
 * needs jquery.livetwitter.js (in included/browser/js_endpage)
 * NOTE : seems to be obsolete !! twitter's API has changed !
 * 
 * @author Bernard Paques (rendering)
 * @author Alexis Raimbault (factoring)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Code_twitter extends Code {
    
    var $patterns = array(
        '/\[(twitter)=([^\]]+?)\]/is', // twits of a given account
        '/\[(tsearch)=([^\]]+?)\]/is', // twits for a given hashtag
        '/\[(retweet)\]/is'            // twits about your page  
        );
    
    public function render($matches) {
        
        $text = '';
        $render = $matches[0];
        
        switch ($render) {
            case 'twitter':
                $text = self::render_twitter($matches[1]);

                break;
            case 'tsearch':
                $text = self::render_twitter_search($matches[1]);
                break;
            
            case 'retweet':
                $text = self::render_retweet();

            default:
                break;
        }
        
        // job done
        return $text;
    }
    
    /**
    * render tweetmeme button
    *
    * @return string the rendered text
    **/
    public static function render_retweet() {
           global $context;

           // we return some text --$context['self_url'] already has $context['url_to_root'] in it
           Page::insert_script('tweetmeme_url = "'.$context['url_to_home'].$context['self_url'].'";');			
           Page::defer_script("http://tweetmeme.com/i/scripts/button.js");			

           // job done
           return $text;
    }
    
    /**
    * render twitter profile
    *
    * @param string twitter id to display, plus optional parameters, if any
    * @return string the rendered text
    */
    public static function render_twitter($id) {
           global $context;

           // up to 4 parameters: id, width, height, styles
           $attributes = preg_split("/\s*,\s*/", $id, 4);
           $id = $attributes[0];

           // width
           if(isset($attributes[1]))
                   $width = $attributes[1];
           else
                   $width = 250;

           // height
           if(isset($attributes[2]))
                   $height = $attributes[2];
           else
                   $height = 300;

           // theme
           if(isset($attributes[3]))
                   $theme = $attributes[3];
           else
                   $theme = 'theme: { shell: {'."\n"
                           .'      background: "#3082af",'."\n"
                           .'      color: "#ffffff"'."\n"
                           .'    },'."\n"
                           .'    tweets: {'."\n"
                           .'      background: "#ffffff",'."\n"
                           .'      color: "#444444",'."\n"
                           .'      links: "#1985b5"'."\n"
                           .'    }}';

           // allow multiple widgets
           static $count;
           if(!isset($count))
                   $count = 1;
           else
                   $count++;

           // we return some text --$context['self_url'] already has $context['url_to_root'] in it
           $text = '<div id="twitter_'.$count.'"></div>'."\n";
           
           Page::insert_script(
                   '$(function() { $("#twitter_'.$count.'").liveTwitter("'.$id.'", {mode: "user_timeline"}); });'
                 );

           // job done
           return $text;
    }
   
   /**
    * render twitter search
    *
    * @param string twitter searched keywords, plus optional parameters, if any
    * @return string the rendered text
    */
    public static function render_twitter_search($id) {
           global $context;

           // up to 4 parameters: id, width, height, styles
           $attributes = preg_split("/\s*,\s*/", $id, 4);
           $id = $attributes[0];

           // width
           if(isset($attributes[1]))
                   $width = $attributes[1];
           else
                   $width = 250;

           // height
           if(isset($attributes[2]))
                   $height = $attributes[2];
           else
                   $height = 300;

           // allow multiple widgets
           static $count;
           if(!isset($count))
                   $count = 1;
           else
                   $count++;

           // $context['self_url'] already has $context['url_to_root'] in it
           $text = '<div id="tsearch_'.$count.'"></div>'."\n";
           Page::insert_script(
                   '$(function() { $("#tsearch_'.$count.'").liveTwitter("'.str_replace('"', '', $id).'"); });'
           );

           // job done
           return $text;
    }
    
}