<?php

/*
 * Class helper to build html tag
 * 
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// constant to use as second argument for _class() method, 
// to make your code more readable
define('NO_CLASS_PREFIX', true);  

class tag {
    
        /**
         * Magic static method to handle a simplifed syntax of tag creation
         * tag::_<tag>('content','class and/or id','extra attributes')
         * 
         * examples
         * tag::_a('a nice cms','button','href="http://yacs.fr"');
         * tag::_p('un para graphe de détail','details');
         * tag::_div('un div avec id et class','/mycustomclass #thediv');
         * 
         * @param type $name
         * @param type $arguments
         */
        public static function __callStatic($name, $arguments) {
            
            $tag = NULL;
            // check name must begin by underscore
            $matches = array();
            if(!preg_match('/([a-z]+)/', $name, $matches)) {
           
                
                return null;
            } else {
                $tag = $matches[1];
            }
                
            
            $content = $attributes = $variant = $extra = '';
            
            // content
            $content .= (isset($arguments[0]))?$arguments[0]:'';
            // class
            $variant .= (isset($arguments[1]))?$arguments[1]:'';
            // extra
            $extra   .= (isset($arguments[2]))?$arguments[2]:'';
            
            // separate id and class
            if(preg_match('/#([a-zA-Z0-9_-]+)/',$variant,$matches)) {
                    // separate id from variant if any
                    
                    $attributes .= tag::_id($matches[1]);
                    $variant = str_replace('#'.$matches[1], '', $variant);
            }
            
            $attributes .= tag::_class($variant).' '.$extra;
            
            $html = tag::_($tag, $attributes, $content);
            
            return $html;
        }
    
        /** 
         * Build a html tag
         * 
         * @param string $type of the tag
         * @param string $attributes to define
         * @param string $content of the tag
         */
        public static function _($type, $attributes='', $content='') {
            global $code_rendering;
            
            // self closer tag
            $self_closers = array('input','img','hr','br','meta','link');
            
            // lowercase
            $type = strtolower($type);
            
            // start
            $tag = '<'.$type;
            
            //add attributes
            if($attributes) {
                $tag .= ' '.trim($attributes);
            }
            
            //closing
            if(!in_array($type,$self_closers)) {
                $tag .= ' >'.$content.'</'.$type.'>';
                if(!$code_rendering) $tag .= "\n";
            } else {
                $tag.= ' />';
            }
            
            return $tag;
        }
        
        
        /**
         * build a attribute
         * 
         * @param string $name of the attribute
         * @param string $value of the attribute
         */
        public static function _attr($name, $value) {
            
            $attribute = '';
            
            // lowercase
            $name = strtolower(trim(strip_tags($name)));
            
            // sanity check
            if(!$name) return $attribute;
            
            // do it
            $attribute .= ' '.$name.'="'.addslashes($value).'"';
            
            return $attribute;
            
        }
        
        /**
         * Build a data attributes for html tag
         * 
         * @param string $name of the data field
         * @param type $value of the data field
         * @return string
         */
        public static function _data($name,$value) {
            
            $attribute = '';
            
            // lowercase
            $name = strtolower(trim(strip_tags($name)));
            
            // sanity check
            if(!$name) return $attribute;
            
            // do it
            $attribute .= ' data-'.$name.'="'.addslashes($value).'"';
        
            return $attribute;
        }
    
        /**
         * build a class attribute for a html tag.
         * by default class are prefixed with YACSS_PREFIX constant value
         * 
         * if you use "k/" before the keyword it would be prefixed with 
         * KNACSS_PREFIX constant value
         * 
         * if you use "/" only, no prefix will be used
         * 
         * @param string $classes a chain of keyword separated by spaces
         * @param boolean $escape not to consider about prefix
         * @return string
         */
        public static function _class($classes, $escape=false) {
            
            // sanity check
            if(!$classes) return '';
            
            // empty prefix ~"" (less syntax)
            $kna = (KNACSS_PREFIX == '~""')?'':KNACSS_PREFIX;
            $yac = (YACSS_PREFIX  == '~""')?'':YACSS_PREFIX;
            
            // start
            $attribute = ' class="';
            
            if(!$escape) {
                // explode that
                $classes = explode(' ', $classes);

                // apply prefix
                foreach($classes as $class) {
                    
                    if(!$class) continue;
                    
                    if(substr($class, 0, 1) === '/') {
                        $attribute .= ltrim($class,'/').' ';
                    } elseif(substr($class, 0, 2) === 'k/') {
                        $attribute .= $kna.substr($class,2).' ';
                    } elseif(substr($class, 0, 2) === 'y/') {
                        $attribute .= $yac.substr($class,2).' ';
                    } else {
                        $attribute .= $yac.$class.' ';
                    }
                }
            } else {
                // take it as is
                $attribute .= $classes;
            }
            
            // remove last space and close
            $attribute = rtrim($attribute).'"';
            
            return $attribute;
        }
        
        /**
         * build a id attribute for a html tag
         * 
         * @param string $id to define
         */
        public static function _id($id) {
            
            // sanity check
            if(!$id) return '';
            
            // remove # if any
            $id = str_replace('#', '', $id);
            
            // do it
            $attribute = ' id="'.$id.'"';
            
            return $attribute;
        }
    
    
    
}
