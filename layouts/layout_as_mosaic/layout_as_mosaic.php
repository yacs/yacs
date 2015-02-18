<?php
/**
 * Display elements as a mosaic of blocks.
 * Use masonry.js
 * 
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// width in px for layout's grid 
// blocks won't be smaller than that
if(!defined('MC_GRID_DEFAULT'))
    define('MC_GRID_DEFAULT',100);

class Layout_as_mosaic extends Layout_interface {
    
    
     function layout($result) {
	
	global $context;
        
        // load skin for ajax request
        load_skin();
	
	// we return some text
	$text = '';	
	
	// empty list
	if(!SQL::count($result))
	    return $text;		
	
	// infinite scroll is a option
	$infinite = $this->has_variant('infinite');
	
	// column grid width may have been setted thru variant
	if(!$grid_width = $this->has_variant('grid'))
	    $grid_width = MC_GRID_DEFAULT;
	    
	// wrappers
	if($infinite) $text .= '<div class="mc-infinite">';
	$text .= '<div class="mc-wrap" >'."\n";
	
	while($item = SQL::fetch($result)) {
	    
	    // get the object interface, this may load parent and overlay
	    $entity = new $this->listed_type($item);
	    
	    // link
	    $url = $entity->get_permalink();
	    
	    // title
	    $title = $entity->get_title();
	    
	    // intro
	    $intro = Codes::beautify_introduction($entity->get_introduction());
	    if($intro)
		$intro = BR.$intro;
	    
	    // image
            $thumb = '';
            if( !$this->has_variant('no_thumb') ) {
                if($thumb = trim($entity->get_thumbnail_url())) {	    	

                     // use parameter of the control panel for this one
                    $options = '';
                    if(isset($context['classes_for_thumbnail_images']))
                            $options = 'class="'.$context['classes_for_thumbnail_images'].'" ';

                    // build the complete HTML element
                    $thumb = '<img src="'.$thumb.'" alt="" title="'.encode_field($title).'" '.$options.' />'."\n";


                } else
                    $thumb = MAP_IMG;
            }
	    // use the image as a link to the target page
            if($thumb)
                $thumb = Skin::build_link($url, $thumb, 'basic', $title);
	    
            // use list text of overlay if any
            $list = $entity->overlay->get_list_text();
            
	    // list articles, if any
	    $childs = $entity->get_childs('articles', 0, 5, 'alistapart');
	    
	    // content
	    $content = $thumb.$intro.$list;
	    if(isset($childs['article']))
		$content .= $childs['article'];
	    
	    // add a block, guess the html tag from context
	    if(isset($context['SKIN_HTML5'])) {
		switch($this->listed_type) {

		    case'article':
			$tag = 'article';
			break;
		    case 'section':
		    case 'category':
			$tag = 'section';
			break;
		    case 'user':
		    default:
			$tag = 'div';
		}
	    } else
		$tag = 'div';
	    
	    $text .= '<'.$tag.' class="mc-block">'.'<h3>'.$title.'</h3>'.$content.'</'.$tag.'>'."\n";
	    
	}
	
	// end wrappers
	if($infinite) $text .= '</div>';
	$text .= '</div>'."\n";
	
        if(!isset($context['AJAX_REQUEST']) || $context['AJAX_REQUEST'] == false) {
        
            // we have bound styles and scripts
            $this->load_scripts_n_styles();	

            // initialize js
            Page::insert_script('Mosaic.init('.$grid_width.')');

            // infinite scroll js lib
            if($infinite) {
                Page::defer_script('layouts/layout_as_mosaic/jquery.infinitescroll.min.js');	    
                Page::insert_script('Mosaic.infiniteScroll()');
            }
        
        } else {
            // insert javascript init at the end of text
            $text .= '<script>'.'Mosaic.init('.$grid_width.')</script>'."\n";
            if($infinite) {
                $text .= '<script>'.'Mosaic.infiniteScroll()</script>'."\n";
            }
            
        } 
            
	
	// end of processing
	SQL::free($result);
	return $text;
    }
    
}
?>
