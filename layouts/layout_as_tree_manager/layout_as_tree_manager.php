<?php
/**
 * Display sub-containers with a javascript interface enableling
 * drag & drop actions. Containers are displayed like folders-tree in
 * a file manager program.
 *
 * variant :
 * - tree_only, to list folders composing the hierachy, but not the objects contained
 * 
 * 
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Layout_as_tree_manager extends Layout_interface {     
    
    
    var $tree_only = false;
    
    /**      
     * @return string the html code to build a "create" button related to a folder
     */
    private function btn_create() {
	
	$btn = '<a href="#" class="tm-cmd tm-create details" title="'.sprintf(i18n::s('Add a %s'),$this->listed_type).'">+</a>'."\n";	
	
	return $btn;
    }
    
    /**     
     * @return string the html code to build a "delete" button related to a folder
     */
    private function btn_delete() {
	
	$btn = '<a href="#" class="tm-cmd tm-delete details" title="'.i18n::s('Delete').'">x</a>'."\n";	
	
	return $btn;
    }
    
    /**
     * @return string the html code to build a "rename" button related to a folder
     */
    private function btn_rename() {
	
	$btn = '<a href="#" class="tm-cmd tm-rename details" title="'.i18n::s('Rename').'">r</a>'."\n";	
	
	return $btn;	
    }
        
    /**
     * layout sub-level of tree hierarchy
     * with a recursive search
     * 
     * @param object $entity an containning anchor (section or category)
     */
    private function get_sub_level($entity, $no_folders = false) {
	
	// we return a string
	$sub = '';
	// data of subelements of this entity
	$data = array();
	// html formated sub elements 	
	$details = array();
	if(!$no_folders)
	    $sub .= "\n".'<ul class="tm-sub_elems">'."\n";
	
	$class = $this->listed_type;
	
	if(!$no_folders) {
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
			$cmd = $this->btn_create().$this->btn_rename().$this->btn_delete();

			// layout sub container
			$details[] = '<li class="tm-drag tm-drop" data-ref="'.$elem->get_reference()
				.'"><a class="tm-zoom" href="'.$elem->get_permalink().'">'
				.'<span class="tm-folder">'.$elem->get_title().'</span></a>'."\n"
				.$cmd
				.$deeper.'</li>'; 
		    }		
	    }
	}
	
	// look for section as pages, but only for categories browsing
	if($this->listed_type == 'category' && !$this->tree_only) {
	    $data = $entity->get_childs('section', 0, 200, 'raw');
	    
	    // layout sections
	    if(isset($data ['section'])) {
		foreach($data['section'] as $sec) {

		    // transform data to obj interface
		    $sec = new Section($sec);
		    
		    // build commands menu
		    $cmd = $this->btn_delete();

		    // layout articles
		    $details[] = '<li class="tm-drag" data-ref="'.$sec->get_reference().'"><span class="tm-page details">'
			    .$sec->get_title().'</span>'.$cmd.'</li>';

		}
	    }
	    
	    
	}
	
	// look for articles and users of this level
	if( !$this->tree_only ) {
	    $data = $entity->get_childs('article, user', 0, 200, 'raw');

	    // layout articles
	    if(isset($data ['article'])) {
		foreach($data['article'] as $art) {

		    // transform data to obj interface
		    $art = new Article($art);

		    // build commands menu
		    $cmd = $this->btn_rename().$this->btn_delete();

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
	}
	
	
	// combine all sub elements	   	 
	if(count($details))
	    $sub .= implode("\n",$details);

	if(!$no_folders)
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
	if(isset($this->focus) && $this->focus)
	    $root_ref = $this->focus;
	elseif(isset($context['current_item']) && $context['current_item'])
	    $root_ref = $context['current_item'];
	else 
	    $root_ref = $items_type.':index';		
	
	$this->tree_only = $this->has_variant('tree_only');
	
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
	    $cmd = $this->btn_create().$this->btn_rename().$this->btn_delete();
	    
	    // one <li> per entity of this level of the tree
	    $text .= '<li class="tm-drag tm-drop" data-ref="'.$entity->get_reference().'">'.$title.$cmd.$sub.'</li>'."\n";		    	    	    
	    
	}	
	
	// this level may have childs that are not folders (exept index)
	// do not search in variant tree_only is setted
	
	if(!preg_match('/index$/', $root_ref) && !$this->tree_only)  {
	    
	    $thislevel = Anchors::get($root_ref);	    
	    $text .= $this->get_sub_level($thislevel,true); // do not search for folders	    	    
	}
	    	
	// we have bound styles and scripts, but do not provide on ajax requests
	if(!isset($context['AJAX_REQUEST']) || !$context['AJAX_REQUEST']) {
	    $this->load_scripts_n_styles();	    
	
	    // init js depending on user privilege for this level
	    if(isset($thislevel))
		// get surfer privilege for this level
		$powered = $thislevel->allows('creation');
	    else
		$powered = Surfer::is_associate ();
		    
	    $powered = ($powered)?'powered':''; // cast to string
	    Page::insert_script('TreeManager.init("'.$powered.'");');
	}
	
	// end drag drop zone
	$text .= '</ul></div>'."\n";		
	
	// end of processing
	SQL::free($result);
	return $text;
    }
}

?>