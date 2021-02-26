<?php
/**
 * the Comment anchor
 * 
 * 
 * 
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Comment extends Anchor {
    
    /**
    * provide classe name with all static functions on this kind of anchor
    * 
    * @return a class name
    */
    function get_static_group_class() {
        return 'Comments';
    }
    
    /**
    * get the title for this anchor
    *
    * @return a string
    *
    * @see shared/anchor.php
    */
    function get_title($use_overlay=true) {
        
        if(!isset($this->item['id']))
            return '??';
        
        
        if(isset($this->item['create_name']))
            return sprintf(i18n::s('Comment by %s'), $this->item['create_name']); 
            
        return i18n::s('Comment');
    }
    
    /**
    * load the related item
    *
    * @see shared/anchor.php
    *
    * @param int the id of the record to load
    * @param boolean TRUE to always fetch a fresh instance, FALSE to enable cache
    */
    function load_by_id($id, $mutable=FALSE) {
            $this->item = Comments::get($id);
    }
    
}

// stop hackers
defined('YACS') or exit('Script must be included');

// load localized strings
if(is_callable(array('i18n', 'bind')))
	i18n::bind('comments');