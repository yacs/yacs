<?php

/**
 * render article sorted by hits or votes
 * 
 * @author Bernard Paques (rendering)
 * @author Alexis Raimbault (factoring)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Code_read_vote extends Code {
    
    var $patterns = array(
        '/\[(read|voted)(?:\.([^\]=]+?))?(?:=([^\]]+?))?\]\n*/is'   // [read], [read.decorated], [read=section:4029], [read.decorated=section:4029,x]
    );
    
    public function render($matches) {
        $text = '';
        $anchor = $layout = '';
        
        // extract parameters
        $mode = $matches[0];
        if(isset($matches[1])) {
            if(strpos($matches[1],':') !== false) {
                $anchor = $matches[1];
            } elseif(isset($matches[2])) {
                $anchor = $matches[2];
                $layout = $matches[1];
            } else {
            $layout = $matches[1];
            }
        }
        
        switch ($mode) {
            case 'read':
                $text = self::render_read($layout, $anchor);
                break;
            case 'voted':
                $text = self::render_voted($anchor, $layout);
                break;
            default:
                break;
        }
        
        return $text;
    }
    
    /**
    * render a compact list of hits
    *
    * @param string the anchor (e.g. 'section:123')
    * @param string layout to use
    * @return string the rendered text
    **/
    public static function render_read($layout='hits', $anchor='') {
           global $context;

           // we return some text;
           $text = '';

           // number of items to display
           $count = COMPACT_LIST_SIZE;
           if(($position = strpos($anchor, ',')) !== FALSE) {
                   $count = (integer)trim(substr($anchor, $position+1));
                   if(!$count)
                           $count = COMPACT_LIST_SIZE;

                   $anchor = trim(substr($anchor, 0, $position));
           }

           // scope is limited to current surfer
           if(($anchor == 'self') && Surfer::get_id()) {
                   $anchor = 'user:'.Surfer::get_id();

                   // refresh on every page load
                   Cache::poison();

           }

           // scope is limited to one section
           if(strpos($anchor, 'section:') === 0) {

                   // look at this branch of the content tree
                   $anchors = Sections::get_branch_at_anchor($anchor);

                   // query the database and layout that stuff
                   $text = Articles::list_for_anchor_by('hits', $anchors, 0, $count, $layout);

           // scope is limited to pages of one surfer
           } elseif(strpos($anchor, 'user:') === 0)
                   $text = Articles::list_for_user_by('hits', substr($anchor, 5), 0, $count, $layout);

           // consider all pages
           if(!$text)
                   $text = Articles::list_by('hits', 0, $count, $layout);

           // we have an array to format
           if(is_array($text))
                   $text = Skin::build_list($text, $layout);

           // job done
           return $text;
    }
    
    /**
    * render a compact list of voted pages
    *
    * @param string the anchor (e.g. 'section:123')
    * @param string layout to use
    * @return string the rendered text
    **/
    public static function render_voted($anchor='', $layout='simple') {
           global $context;

           // we return some text;
           $text = '';

           // number of items to display
           $count = COMPACT_LIST_SIZE;
           if(($position = strpos($anchor, ',')) !== FALSE) {
                   $count = (integer)trim(substr($anchor, $position+1));
                   if(!$count)
                           $count = COMPACT_LIST_SIZE;

                   $anchor = trim(substr($anchor, 0, $position));
           }

           // scope is limited to current surfer
           if(($anchor == 'self') && Surfer::get_id()) {
                   $anchor = 'user:'.Surfer::get_id();

                   // refresh on every page load
                   Cache::poison();

           }

           // scope is limited to one section
           if(strpos($anchor, 'section:') === 0) {

                   // look at this branch of the content tree
                   $anchors = Sections::get_branch_at_anchor($anchor);

                   // query the database and layout that stuff
                   $text = Articles::list_for_anchor_by('rating', $anchors, 0, $count, $layout);

           // scope is limited to pages of one surfer
           } elseif(strpos($anchor, 'user:') === 0)
                   $text = Articles::list_for_user_by('rating', substr($anchor, 5), 0, $count, $layout);

           // consider all pages
           else
                   $text = Articles::list_by('rating', 0, $count, $layout);

           // we have an array to format
           if(is_array($text))
                   $text = Skin::build_list($text, $layout);

           // job done
           return $text;
    }
}

