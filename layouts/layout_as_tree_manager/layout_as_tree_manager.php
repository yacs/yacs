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
	$text .= '<div class="ddz">'."\n";
	
	while($item = SQL::fetch($result)) {
	    
	    // get the object interface, this may load parent and overlay
	    $entity = new $items_type($item);
	    
	    // one dl box per entity
	    $text .= '<dl class="drop" data-ref="'.$entity->get_reference().'">'."\n";
	    
	    // title
	    if(is_object($entity->overlay))
		$title = Codes::beautify_title($entity->overlay->get_text('title', $item));
	    else
		$title = Codes::beautify_title($item['title']);
	    
	    // permalink
	    $url = $entity->get_permalink();
	    $title = Skin::build_link($url, $title, 'basic');
	    
	    // box header
	    $text .= '<dt><span class="drag" data-ref="'.$entity->get_reference().'">'.$title.'</span></dt>'."\n";
	    
	    // content
	    $text .= '<dt>'."\n";
		
	    // add sub-categories on index pages
	    $details = array();
	    if($related = Categories::list_for_anchor($entity->get_reference(), 'raw')) {
		    foreach($related as $sub_elem ) {
			    
			    $sub_elem = new $items_type($sub_elem);
			    $details[] = '<li class="drag" data-ref="'.$sub_elem->get_reference().'">'.$sub_elem->get_title().'</li>'; 
			}		   
	    }
	    
	    // layout details
	    if(count($details))
		$text .= '<ul class="sub_elems">'.implode("\n",$details)."</ul>\n";
	    else
		$text .= '<ul class="sub_elems"></ul>'."\n";
	    
	    $text .= '</dt>'."\n";
	    
	    // end box
	    $text .= '</dl>'."\n";	    	    
	    
	}
	
	// we have bounded styles and scripts
	$this->load_scripts_n_rules();
	
	// init js
	Page::insert_script("TreeManager.init();");
	
	// end drag drop zone
	$text .= '</div>';
	
	// end of processing
	SQL::free($result);
	return $text;
    }
}

?>
