<?php
/**
 * Display sub-containers with a javascript interface enableling
 * drag & drop actions. Containers are displayed like folders-tree in
 * a file manager program.
 *
 * @author Alexis Raimbault
 * @reference
 */
class Layout_as_tree_manager extends Layout_interface {
    
    
    public function layout($result) {
	global $context;
	
	// we return some text
	$text = '';		
	
	// empty list
	if(!SQL::count($result))
	    return $text;
	
	// type of listed object
	$items_type = $this->listed_type;
	
	// drag&drop zone
	$text .= '<ul class="ddz root">'."\n";
	
	while($item = SQL::fetch($result)) {
	    
	    // get the object interface, this may load parent and overlay
	    $entity = new $items_type($item);	   
	    
	    // title
	    if(is_object($entity->overlay))
		$title = Codes::beautify_title($entity->overlay->get_text('title', $item));
	    else
		$title = Codes::beautify_title($item['title']);
	    
	    // permalink
	    $url = $entity->get_permalink();
	    $title = Skin::build_link($url, $title, 'basic');	    	        	       	    	    
	    
	    // data of subelements of this entity
	    $data = array();
	    // html formated sub elements 	
	    $details = array();
	    $sub = "\n".'<ul class="sub_elems">'."\n";
	    
	    // look for sub-categories
	    $data = $entity->get_childs('categories',0,200,'raw');	    	   			
		
	    if(isset($data ['categories'])) {
		foreach($data ['categories'] as $cat ) {
			
			// transform data to obj interface
			$cat = new category($cat);
			// layout sub category
			$details[] = '<li class="drag" data-ref="'.$cat->get_reference().'">'.$cat->get_title().'</li>'; 
		    }		   
	    }
			   	    
	    // layout details	   	 
	    if(count($details))
		$sub .= implode("\n",$details);
	    
	    $sub .= '</ul>'."\n";	
	    
	    // one <li> per entity of this level of the three
	    $text .= '<li class="main drag drop" data-ref="'.$entity->get_reference().'">'.$title.$sub.'</li>'."\n";		    	    	    
	    
	}
	
	// we have bounded styles and scripts
	$this->load_scripts_n_rules();
	
	// init js
	Page::insert_script("TreeManager.init();");
	
	// end drag drop zone
	$text .= '</ul>';
	
	// end of processing
	SQL::free($result);
	return $text;
    }
}

?>
