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
     * @return string the html code to build a "create" button related to a folder
     */
    private function btn_create() {
	
	$btn = '<a class="tm-cmd tm-create details" title="'.sprintf(i18n::s('Add a %s'),$this->listed_type).'">+</a>'."\n";	
	
	return $btn;
    }
    
    /**     
     * @return string the html code to build a "create" button related to a folder
     */
    private function btn_delete() {
	
	$btn = '<a class="tm-cmd tm-delete details" title="'.i18n::s('Delete').'">x</a>'."\n";	
	
	return $btn;
    }
        
    /**
     * layout sub-level of tree hierarchy
     * with a recursive search
     * 
     * @param object $entity an containning anchor (section or category)
     */
    private function get_sub_level($entity) {
	
	// data of subelements of this entity
	$data = array();
	// html formated sub elements 	
	$details = array();
	$sub = "\n".'<ul class="tm-sub_elems">'."\n";
	
	$class = $this->listed_type;
	
	// look for sub-containers, should be either categories or sections
	$data = $entity->get_childs($class,0,200,'raw');	    	   			

	// layout hierarchy
	if(isset($data [$class])) {
	    foreach($data [$class] as $elem ) {

		    // transform data to obj interface
		    $elem = new $class($elem);
		    
		    // go deeper in tree hierarchy with a recursive call
		    $deeper = $this->get_sub_level($elem);
		    
		    // build commands menu
		    $cmd = $this->btn_create().$this->btn_delete();
		    
		    // layout sub container
		    $details[] = '<li class="tm-drag tm-drop" data-ref="'.$elem->get_reference()
			    .'"><a class="tm-zoom" href="'.$elem->get_permalink().'">'
			    .'<span class="tm-folder">'.$elem->get_title().'</span></a>'."\n"
			    .$cmd
			    .$deeper.'</li>'; 
		}		
	}
	
	// look for section as pages, but only for categories browsing
	if($this->listed_type == 'category') {
	    $data = $entity->get_childs('section', 0, 200, 'raw');
	    
	    // layout articles
	    if(isset($data ['section'])) {
		foreach($data['section'] as $sec) {

		    // transform data to obj interface
		    $sec = new Section($sec);

		    // layout articles
		    $details[] = '<li class="tm-drag" data-ref="'.$sec->get_reference().'"><span class="tm-page details">'.$sec->get_title().'</span></li>';

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
		
		// build commands menu
		$cmd = $this->btn_delete();
		
		// layout articles
		$details[] = '<li class="tm-drag" data-ref="'.$art->get_reference().'"><span class="tm-page details">'
			.$art->get_title().'</span>'.$cmd.'</li>';
		
	    }
	}
	
	// layout users
	if(isset($data ['user'])) {
	    foreach($data['user'] as $usr) {
		
		// transform data to obj interface
		$usr = new User($usr);
		
		// build commands menu
		$cmd = $this->btn_delete();
		
		// layout articles
		$details[] = '<li class="tm-drag" data-ref="'.$usr->get_reference().'"><span class ="tm-user details">'
			.$usr->get_title().'</span>'.$cmd.'</li>';
		
	    }
	}
	
	
	// combine all sub elements	   	 
	if(count($details))
	    $sub .= implode("\n",$details);

	$sub .= '</ul>'."\n";
	
	return $sub;	
    }

    /**
     * main function to render layout
     * 
     * @param type $result MySQL object
     * @return string the rendering
     */
    public function layout($result) {
	global $context;
	
	// we return some text
	$text = '';			
	
	// type of listed object
	$items_type = $this->listed_type;
	
	// this level root reference
	if(isset($context['current_item']) && $context['current_item'])
	    $root_ref = $context['current_item'];
	else 
	    $root_ref = $items_type.':index';
	
	// drag&drop zone
	$text .= '<div class="tm-ddz tm-drop" data-ref='.$root_ref.'>'."\n";
	
	// root create command
	$text .= $this->btn_create();
	
	// root ul
	$text .=  '<ul class="tm-sub_elems tm-root">'."\n";
	
	while($item = SQL::fetch($result)) {
	    
	    // get the object interface, this may load parent and overlay
	    $entity = new $items_type($item);	   
	    
	    // title
	    $title = '<a class="tm-zoom" href="'.$entity->get_permalink().'"><span class="tm-folder">'.$entity->get_title().'</span></a>';
	    
	    // sub elements of this entity	    	    
	    $sub = $this->get_sub_level($entity);	
	    
	    // command related to this entity
	    $cmd = $this->btn_create().$this->btn_delete();
	    
	    // one <li> per entity of this level of the tree
	    $text .= '<li class="tm-drag tm-drop" data-ref="'.$entity->get_reference().'">'.$title.$cmd.$sub.'</li>'."\n";		    	    	    
	    
	}		
	
	// we have bound styles and scripts, do not provide on ajax requests
	if(!isset($context['AJAX_REQUEST']) || !$context['AJAX_REQUEST']) {
	    $this->load_scripts_n_styles();
	
	    // init js
	    Page::insert_script("TreeManager.init();");
	}
	
	// end drag drop zone
	$text .= '</ul></div>'."\n";		
	
	// end of processing
	SQL::free($result);
	return $text;
    }
}

?>