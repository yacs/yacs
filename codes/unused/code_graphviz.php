<?php
/**
 * render a graphviz
 * @see graphviz.org
 * 
 * note : seems to need a soft installed on server
 * 
 * @author Bernard Paques (rendering)
 * @author Alexis Raimbault (factoring)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

class Code_graphviz extends Code {
    
    // [digraph]name[/digraph]
    var $patterns = array('/\[digraph\](.*?)\[\/(digraph)\]/is');

    /**
     * render graphviz object
     *
     * @return string the rendered text
    **/
    public function render($matches) {
            global $context;
            
            list($text, $variant) = $matches;

            // sanity check
            if(!$text)
                    $text = 'Hello->World!';

            // remove tags put by WYSIWYG editors
            $text = strip_tags(str_replace(array('&gt;', '&lt;', '&amp;', '&quot;', '\"'), array('>', '<', '&', '"', '"'), str_replace(array('<br />', '</p>'), "\n", $text)));

            // build the .dot content
            switch($variant) {
            case 'digraph':
            default:
                    $text = 'digraph G { '.$text.' }'."\n";
                    break;
            }

            // id for this object
            $hash = md5($text);

            // path to cached files
            $path = $context['path_to_root'].'temporary/graphviz.';

            // we cache content
            if($content = Safe::file_get_contents($path.$hash.'.html'))
                    return $content;

            // build a .dot file
            if(!Safe::file_put_contents($path.$hash.'.dot', $text)) {
                    $content = '[error writing .dot file]';
                    return $content;
            }

            // process the .dot file
            if(isset($context['dot.command']))
                    $command = $context['dot.command'];
            else
                    $command = 'dot';
//		$font = '"/System/Library/Fonts/Times.dfont"';
//		$command = '/sw/bin/dot -v -Nfontname='.$font
            $command .= ' -Tcmapx -o "'.$path.$hash.'.map"'
                    .' -Tpng -o "'.$path.$hash.'.png"'
                    .' "'.$path.$hash.'.dot"';

            if(Safe::shell_exec($command) == NULL) {
                    $content = '[error while using graphviz]';
                    return $content;
            }

            // produce the HTML
            $content = '<img src="'.$context['url_to_root'].'temporary/graphviz.'.$hash.'.png" usemap="#mainmap" />';
            $content .= Safe::file_get_contents($path.$hash.'.map');

            // put in cache
            Safe::file_put_contents($path.$hash.'.html', $content);

            // done
            return $content;
    }
}