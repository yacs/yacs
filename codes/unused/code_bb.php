<?php

/**
 * render a bunch of BB-code like formatting codes
 * 
 * @author Bernard Paques (rendering)
 * @author Alexis Raimbault (factoring)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Code_bb extends Code {
    
    var $patterns = array(
        '/\[(image|img)(?:=([^\]]+?))?\](.*?)\[\/\1\]/i',                                       // [image]src[/image] [img]src[/img] [image=alt]src[/image] [img=alt]src[/img]
        '/\[(decorated)\](.*?)\[\/decorated\]/is',                                              // [decorated]...[/decorated], yacs's style list
        '/\[(style|abbr|color)=([^\]]+?)\](.*?)\[\/\1\]/is',                                    // [style=variant]...[/style] block with named style, hint of coloured text
        '/\[(tiny|small|big|huge|sub|sup|ins|del)\](.*?)\[\/\1\]/is',                           // [tiny]...[/tiny] ... explicit effects
        '/\[(---+|___+)\]\s*/is',                                                               // [---], [___] --- ( declare before inserted ) horiz. rule
        '/^(-----*)/m',                                                                         // ---- horiz. rule
        '/ (--)(\S.*?\S)--(?!([^<]+)?>)/is',                                                    // --...-- barred
        '/(\*\*)(\S.*?\S)\*\*/is',                                                              // **...** bold
        '/\[(b|i|u|li)\](.*?)\[\/\1\]/is',                                                      // [b]...[/b] : bold, italic, underlined, list elem
        '/(\/\/)(\S.*?\w)\/\/(?!([^<]+)?>)/is',                                                // //...// italic
        '/(__)(\S.*?\S)__(?!([^<]+)?>)/is',                                                     // __...__ underlined
        '/\[(list)(?:=([^\]]+?))?\](.*?)\[\/list\]/is',                                         // [list]...[/list] [list=1]...[/list] list encaps
        '/\n\n+[ \t]*\[(\*)\][ \t]*/i',                                                         // [*] (outside [list]...[/list]) add a bullet icon
        '/\n?[ \t]*\[(\*)\][ \t]*/i'
    );    
    
    public function render($matches) {
        
        $text = '';
        $mode = $matches[0];
        
        // detect horiz ruler
        if(preg_match('/(---|___)/',$mode)) $mode = 'hrule';
        
        switch ($mode) {
            case 'image':
            case 'img':
                if(count($matches) === 3) {
                    $alt = $matches[1];
                    $src = $matches[2];
                } else {
                    $alt = 'image';
                    $src = $matches[1];
                }
                $text = '<div class="external_image"><img src="'.encode_link($src).'" alt="'.encode_link($alt).'" /></div>'."\n";
                break;
            case 'decorated':
                $text = Skin::build_block(Codes::fix_tags($matches[1]), 'decorated');
                break;
            case 'style':
                $text = Skin::build_block(Codes::fix_tags($matches[2]), $matches[1]);
                break;
            case 'abbr':
                $text = '<abbr title="'.$matches[1].'">'.$matches[2].'</abbr>';
                break;
            case 'color':
                $text = '<span style="color:'.$matches[1].'">'.$matches[2].'</span>';
                break;
            case 'tiny' :
            case 'small':
            case 'big'  :
            case 'huge' :
                $text = Skin::build_block(Codes::fix_tags($matches[1]), $mode);
                break;
            case 'sub':
            case 'sup':
            case 'ins':
            case 'del':
            case 'li' :
                $text = '<'.$mode.'>'.$matches[1].'</'.$mode.'>';
                break;
            case 'hrule':
                $text = HORIZONTAL_RULER;
                break;
            case '--':
                $text = '<del>'.$matches[1].'</del>';
                break;
            case '**':
            case 'b':
                $text = '<strong>'.$matches[1].'</strong>';
                break;
            case '//':
            case 'i':
                $text = '<em>'.$matches[1].'</em>';
                break;
            case '__':
            case 'u':
                $text = '<span style="text-decoration:underline;">'.$matches[1].'</span>';
                break;
            case 'list':
                if(count($matches) === 3) {
                    $variant = $matches[1];
                    $content = $matches[2];
                } else {
                    $variant = '';
                    $content = $matches[1];
                }
                $text = self::render_list(Codes::fix_tags($content), $variant);
                break;
            case '*':
                $text = BR.BULLET_IMG.'&nbsp;';
                break;
            default:
                break;
        }
        
        return $text;
    }
    
    /**
    * render a list
    *
    * @param string the list content
    * @param string the variant, if any
    * @return string the rendered text
    **/
    public static function render_list($content, $variant='') {
           global $context;

           if(!$content = trim($content)) {
                   $output = NULL;
                   return $output;
           }

           // preserve existing list, if any --coming from implied beautification
           if(preg_match('#^<ul>#', $content) && preg_match('#</ul>$#', $content))
                   $items = preg_replace(array('#^<ul>#', '#</ul>$#'), '', $content);

           // split items
           else {
                   $content = preg_replace(array("/<br \/>\n-/s", "/\n-/s", "/^-/", '/\[\*\]/'), '[*]', $content);
                   $items = '<li>'.join('</li><li>', preg_split("/\[\*\]/s", $content, -1, PREG_SPLIT_NO_EMPTY)).'</li>';
           }

           // an ordinary bulleted list
           if(!$variant) {
                   $output = '<ul>'.$items.'</ul>';
                   return $output;

           // style a bulleted list, but ensure it's not numbered '1 incremental'
           } elseif($variant && (strlen($variant) > 1) && ($variant[1] != ' ')) {
                   $output = '<ul class="'.$variant.'">'.$items.'</ul>';
                   return $output;
           }

           // type has been deprecated, use styles
           $style = '';
           switch($variant) {
           case 'a':
                   $style = 'style="list-style-type: lower-alpha"';
                   break;

           case 'A':
                   $style = 'style="list-style-type: upper-alpha"';
                   break;

           case 'i':
                   $style = 'style="list-style-type: lower-roman"';
                   break;

           case 'I':
                   $style = 'style="list-style-type: upper-roman"';
                   break;

           default:
                   $style = 'class="'.encode_field($variant).'"';
                   break;

           }

           // a numbered list with style
           $output = '<ol '.$style.'>'.$items.'</ol>';
           return $output;
    }
}

