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
    
    /**
     *
     * @param object $entity an containning anchor (section or category)
     */
    private function get_sub_level($entity) {
	
	// data of subelements of this entity
	$data = array();
	// html formated sub elements 	
	$details = array();
	$sub = "\n".'<ul class="sub_elems">'."\n";
	
	$class = $this->listed_type;
	
	// look for sub-containers, should be either categories or sections
	$data = $entity->get_childs($class,0,200,'raw');	    	   			

	// layout hierarchy
	if(isset($data [$class])) {
	    foreach($data [$class] as $elem ) {

		    // transform data to obj interface
		    $elem = new $class($elem);
		    
		    // go deeper in tree hierarchy		    
		    $deeper = $this->get_sub_level($elem);
		    
		    // layout sub container
		    $details[] = '<li class="drag drop" data-ref="'.$elem->get_reference().'"><span class="folder">'.$elem->get_title().'</span>'.$deeper.'</li>'; 
		}		
	}
	
	// look for section but ongly for categories browsing
	if($this->listed_type == 'category') {
	    $data = $entity->get_childs('section', 0, 200, 'raw');
	    
	    // layout articles
	    if(isset($data ['section'])) {
		foreach($data['section'] as $sec) {

		    // transform data to obj interface
		    $sec = new Section($sec);

		    // layout articles
		    $details[] = '<li class="drag" data-ref="'.$sec->get_reference().'"><span class="page details">'.$sec->get_title().'</span></li>';

		}
	    }
	    
	    
	}
	
	// look for articles and users of this level
	$data = $entity->get_childs('article, user', 0, 200, 'raw');
	
	// layout articles
	if(isset($data ['article'])) {
	    foreach($data['article'] as $art) {
		
		// transform data to obj interface
		$art = new Article($art);
		
		// layout articles
		$details[] = '<li class="drag" data-ref="'.$art->get_reference().'"><span class="page details">'.$art->get_title().'</span></li>';
		
	    }
	}
	
	// layout users
	if(isset($data ['user'])) {
	    foreach($data['article'] as $usr) {
		
		// transform data to obj interface
		$usr = new User($usr);
		
		// layout articles
		$details[] = '<li class="drag" data-ref="'.$usr->get_reference().'"><span class ="user details">'.$usr->get_title().'</span></li>';
		
	    }
	}
	
	
	// combine all sub elements	   	 
	if(count($details))
	    $sub .= implode("\n",$details);

	$sub .= '</ul>'."\n";
	
	return $sub;	
    }

    public function layout($result) {
	global $context;
	
	// we return some text
	$text = '';		
	
	// empty list
	if(!SQL::count($result))
	    return $text;
	
	// type of listed object
	$items_type = $this->listed_type;
	
	// this level root reference
	if(isset($context['current_item']) && $context['current_item'])
	    $root_ref = $context['current_item'];
	else 
	    $root_ref = 'index';
	
	// drag&drop zone
	$text .= '<div class="ddz drop" data-ref='.$root_ref.'>'."\n".'<ul class="sub_elems root">'."\n";
	
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
	    
	    // sub elements of this entity	    	    
	    $sub = $this->get_sub_level($entity);	
	    
	    // one <li> per entity of this level of the tree
	    $text .= '<li class="drag drop" data-ref="'.$entity->get_reference().'">'.$title.$sub.'</li>'."\n";		    	    	    
	    
	}
	
	// we have bounded styles and scripts
	$this->load_scripts_n_styles();
	
	// init js
	Page::insert_script("TreeManager.init();");
	
	// end drag drop zone
	$text .= '</ul></div>'."\n";		
	
	// end of processing
	SQL::free($result);
	return $text;
    }
}

?>
