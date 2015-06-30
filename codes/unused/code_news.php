<?php

/**
 * render animated news 
 * - internal with news=...
 * - from rss feeds with newsfeed=...
 * @author Bernard Paques (rendering)
 * @author Alexis Raimbault (factoring)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Code_news extends Code {
    
    var $patterns = array(
        '/\[(news)=([^\]]+?)\]/is',                              // [news=flash]
        '/\[(newsfeed)(?:\.([^=\]]+?))?=([^\]]+?)\]/is',	 // [newsfeed.variant=url]
    );
    
    
    public function render($matches) {
        $text = '';
        $mode = $matches[0];
        
        switch ($mode) {
            case 'news':
                $text = self::render_news($matches[1]);
                break;
            case 'newsfeed':
                if(count($matches) === 3) {
                    $variant    = $matches[1];
                    $url        = $matches[2];
                } else {
                    $variant    = 'ajax';
                    $url        = $matches[1];
                }
                $text = self::render_newsfeed($url, $variant);
                break;
            default:
                break;
        }
        
        return $text;
    }
    
    /**
    * render some animated news
    *
    * We have replaced the old fat object by a lean, clean, and valid XHTML solution.
    * However, as explained by Jeffrey Zeldmann in his book "designing with web standards",
    * it may happen that this way of doing don't display correctly sometimes.
    *
    * @param string the variant - default is 'flash'
    * @return string the rendered text
    **/
    public static function render_news($variant) {
           global $context;

           switch($variant) {
           case 'flash':

                   // sanity check
                   if(!isset($context['root_flash_at_home']) || ($context['root_flash_at_home'] != 'Y'))
                           $text = '';

                   else {
                           $url = $context['url_to_home'].$context['url_to_root'].'feeds/flash/slashdot.php';
                           $flashvars = '';
                           $text = '<div id="local_news" class="no_print">Flash plugin or Javascript are turned off. Activate both and reload to view the object</div>'."\n";

                           Page::insert_script(
                                   'var params = {};'."\n"
                                   .'params.base = "'.dirname($url).'/";'."\n"
                                   .'params.quality = "high";'."\n"
                                   .'params.wmode = "transparent";'."\n"
                                   .'params.menu = "false";'."\n"
                                   .'params.flashvars = "'.$flashvars.'";'."\n"
                                   .'swfobject.embedSWF("'.$url.'", "local_news", "80%", "50", "6", "'.$context['url_to_home'].$context['url_to_root'].'included/browser/expressinstall.swf", false, params);'."\n"
                                   );
                   }

                   return $text;

           case 'dummy':
                   $text = 'hello world';
                   return $text;

           default:
                   $text = '??'.$variant.'??';
                   return $text;
           }
    }
    
    /**
    * integrate content of a newsfeed
    *
    * @param string address of the newsfeed to get
    * @return string the rendered text
    **/
    public static function render_newsfeed($url, $variant='ajax') {
           global $context;

           // we allow multiple calls
           static $count;
           if(!isset($count))
                   $count = 1;
           else
                   $count++;

           switch($variant) {

           case 'ajax': // asynchronous loading
           default:

                   $text = '<div id="newsfeed_'.$count.'" class="no_print"></div>'."\n";
                   Page::insert_script('$(function() { Yacs.spin("newsfeed_'.$count.'"); Yacs.call( { method: \'feed.proxy\', params: { url: \''.$url.'\' }, id: 1 }, function(s) { if(s.text) { $("#newsfeed_'.$count.'").html(s.text.toString()); } else { $("#newsfeed_'.$count.'").html("***error***"); } } ) } );');			

                   return $text;

           case 'embed': // integrate newsfeed into the page

                   include_once $context['path_to_root'].'feeds/proxy_hook.php';
                   $parameters = array('url' => $url);
                   if($output = Proxy_hook::serve($parameters))
                           $text = $output['text'];

                   return $text;

           }
    }
}

