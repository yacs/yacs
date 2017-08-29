<?php

/**
 * render headers, question & answers, table of content, table of questions
 * 
 * 
 * @author Bernard Paques (rendering)
 * @author Alexis Raimbault (factoring)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class code_headers extends Code {
    
        var $patterns = array(
            '#\[(h(?:eader)?[1-5])\](.*?)\[/\1\]\n*#is',            // [h<x>]...[/h<x>] or [header<x>]...[/header<x>]
            '/<(?:br \/|p)>(==+)(\S.*?\S)\1<(?:br \/|\/p)>/m',      // ==...==, ===...===, ....
            '/^(==+)(\S.*?\S)\1/m',                                 // ==...==, ===...===, ....
            '/\[(question)\](.*?)\[\/question\]\n*/is',             // [question]...[/question] ( must be declared before [question] )
            '/\[(question)\]/is',                                   // [question]
            '/\[(answer)\]/is',                                     // [answer]
            '/\[(toq)\]\n*/is',                                     // [toq] table of questions
            '/\[(toc)\]\n*/is',                                     // [toc] table of content
        );
    
    
    public function render($matches) {
        
        $text = '';
        $mode = $matches[0];
        
        if(strpos($mode, '=') !== false) {
            $level = strlen($mode) - 1;
            $mode = 'header';
        }
        
        if(preg_match('/^h(?:eader)?[1-5]$/', $mode)) {
            $level = substr($mode, -1);
            $mode = "header";
        }
        
        if(isset($matches[1])) $text = $matches[1];
        
        switch($mode) {
            case 'header':
                $text = self::render_title($text, 'header'.$level);
                break;
            case 'question':
                if($text) {
                    $text = self::render_title($text, 'question');
                } else {
                    $text = QUESTION_FLAG;
                }
                break;
            case 'answer':
                $text = ANSWER_FLAG;
                break;
            case 'toc':
                $text = self::render_table_of('content');
                break;
            case 'toq':
                $text = self::render_table_of('questions');
                break;
            default:
                break;
        }
        
        // job done
        return $text;
        
    }
    
    
    /**
    * render a title, a sub-title, or a question
    *
    * @param string the text
    * @param string the variant
    * @return string the rendered text
    **/
    public static function render_title($text, $variant) {
           global $codes_toc, $codes_toq, $context;

           // remember questions
           if($variant == 'question') {
                   $index = count($codes_toq)+1;
                   $id = 'question_'.$index;
                   $url = $context['self_url'].'#'.$id;
                   $codes_toq[] = Skin::build_link($url, ucfirst($text), 'basic');
                   $text = QUESTION_FLAG.$text;

           // remember level 1 titles ([title]...[/title] or [header1]...[/header1])
           } elseif($variant == 'header1') {
                   $index = count($codes_toc)+1;
                   $id = 'title_'.$index;
                   $url = $context['self_url'].'#'.$id;
                   $codes_toc[] = array(1, Skin::build_link($url, ucfirst($text), 'basic'));

           // remember level 2 titles ([subtitle]...[/subtitle] or [header2]...[/header2])
           } elseif($variant == 'header2') {
                   $index = count($codes_toc)+1;
                   $id = 'title_'.$index;
                   $url = $context['self_url'].'#'.$id;
                   $codes_toc[] = array(2, Skin::build_link($url, ucfirst($text), 'basic'));

           // remember level 3 titles
           } elseif($variant == 'header3') {
                   $index = count($codes_toc)+1;
                   $id = 'title_'.$index;
                   $url = $context['self_url'].'#'.$id;
                   $codes_toc[] = array(3, Skin::build_link($url, ucfirst($text), 'basic'));

           // remember level 4 titles
           } elseif($variant == 'header4') {
                   $index = count($codes_toc)+1;
                   $id = 'title_'.$index;
                   $url = $context['self_url'].'#'.$id;
                   $codes_toc[] = array(4, Skin::build_link($url, ucfirst($text), 'basic'));

           // remember level 5 titles
           } elseif($variant == 'header5') {
                   $index = count($codes_toc)+1;
                   $id = 'title_'.$index;
                   $url = $context['self_url'].'#'.$id;
                   $codes_toc[] = array(5, Skin::build_link($url, ucfirst($text), 'basic'));
           }

           // the rendered text
           $output = Skin::build_block($text, $variant, $id);
           return $output;
    }
    
    
    /**
    * render a table of links
    *
    * @param string the variant
    * @return string the rendered text
    **/
    public static function render_table_of($variant) {
           global $context;

           // nothing to return yet
           $output = '';

           // list of questions for a FAQ
           if($variant == 'questions') {

                   // only if the table is not empty
                   global $codes_toq;
                   if(isset($codes_toq) && $codes_toq) {

                           // to be rendered by css, using selector .toq_box ul, etc.
                           $text = '<ul>'."\n";
                           foreach($codes_toq as $link)
                                   $text .= '<li>'.$link.'</li>'."\n";
                           $text .= '</ul>'."\n";

                           $output = Skin::build_box('', $text, 'toq');

                   }

           // list of titles
           } else {

                   // only if the table is not empty
                   global $codes_toc;
                   if(isset($codes_toc) && $codes_toc) {

                           // to be rendered by css, using selector .toc_box ul, etc.
                           // <ul>
                           // <li>1. link</li> 		0 -> 1
                           // <li>1. link				1 -> 1
                           //		<ul>
                           //		<li>2. link</li>	1 -> 2
                           //		<li>2. link</li>	2 -> 2
                           //		</ul></li>
                           // <li>1. link		 		2 -> 1
                           //		<ul>
                           //		<li>2. link</li>	1 -> 2
                           //		<li>2. link</li>	2 -> 2
                           //		</ul></li>
                           // </ul>
                           $text ='';
                           $previous_level = 0;
                           foreach($codes_toc as $attributes) {
                                   list($level, $link) = $attributes;

                                   if($previous_level == $level)
                                           $text .= '</li>'."\n";

                                   else {

                                           if($previous_level < $level) {
                                                   $text .= '<ul>';
                                                   $previous_level++;
                                                   while($previous_level < $level) {
                                                           $text .= '<li><ul>'."\n";
                                                           $previous_level++;
                                                   }
                                           }

                                           if($previous_level > $level) {
                                                   $text .= '</li>';
                                                   while($previous_level > $level) {
                                                           $text .= '</ul></li>'."\n";
                                                           $previous_level--;
                                                   }
                                           }
                                   }

                                   $text .= '<li>'.$link;

                           }

                           if($previous_level > 0) {
                                   $text .= '</li>';
                                   while($previous_level > 0) {
                                           if($previous_level > 1)
                                                   $text .= '</ul></li>'."\n";
                                           else
                                                   $text .= '</ul>'."\n";
                                           $previous_level--;
                                   }
                           }

                           $output = Skin::build_box('', $text, 'toc');

                   }

           }

           return $output;
    }
}

