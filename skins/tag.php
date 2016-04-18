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
         * Build a html tag
         * 
         * @param string $type of the tag
         * @param string $attributes to define
         * @param string $content of the tag
         */
        public static function _($type, $attributes='', $content='') {
            
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
                $tag .= ' >'.$content.'</'.$type.'>'."\n";
            } else {
                $tag.= ' />';
            }
            
            return $tag;
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
                        $attribute .= KNACSS_PREFIX.$class.' ';
                    } elseif(substr($class, 0, 2) === 'y/') {
                        $attribute .= YACSS_PREFIX.$class.' ';
                    } else {
                        $attribute .= YACSS_PREFIX.$class.' ';
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
            
            // do it
            $attribute = ' id="'.$id.'"';
            
            return $id;
        }
    
    
    
}
