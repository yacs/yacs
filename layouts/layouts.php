<?php
/**
 * managing layouts
 *
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
abstract Class Layouts {
    
	/**
	 * Create a layout object by searching class file
	 * according to naming convention
	 * 
	 * @see layouts/layout.php 
	 * @see sections/view.php
	 * 
	 * @param string layout name as recorded in database
	 * @param string type of object that will be listed by the layout
	 * @param boolean to flag that we want a layout variant for index.php
	 * @param boolean flag to avoid warning if layout is not founded 
	 * @return object, the layout
	 */
	public static function new_($name,$item_type,$home=FALSE,$silent=FALSE) {
	    global $context;
            
            // sanity check
            if(is_null($name)) $name = '';

	    // lazy time : layout already loaded
	    if(is_object($name))
		return $name;

	    $layout = $variant = NULL;
            
            // case "none" keyword
            if($name==='none') return $layout;
	    
	    // separate variant from layout name, if any
	    $attributes = explode(' ', $name, 2);
	    if(isset($attributes[1]))
		list($name,$variant) = $attributes;
	    
	    // get listed item groupe name
	    // mainly to deal with former layouts files
	    // be also friend to DEVs and accept singular or plurial item_type writing
	    switch($item_type) {
		case 'articles':
		    $item_type = 'article';
		case 'article':
		    $family = 'articles';
		    break;
		case 'sections':
		    $item_type = 'section';
		case 'section':
		    $family = 'sections';
		    break;
		case 'files':
		    $item_type = 'file';
		case 'file':
		    $family = 'files';
		    break;
                case 'images':
                    $item_type = 'image';
                case 'image':
                    $family = 'images';
                    break;
		case 'users' :
		    $item_type = 'user';
		case 'user':
		    $family = 'users';
		    break;
		case 'categories' :
		    $item_type = 'category';
		case 'category':
		    $family = 'categories';
		    break;
		default:
		    return NULL;
	    }
	    
	    
	    ////// look for former layout files
	    
	    // folder to files and filename are different for customized layouts on home page
	    $path = ($home)?'skins':$family;
	    $home = ($home)?'home_':'';
            
            if($name == 'map' || $name == 'yahoo') {
		$name = 'columns'; // new layout that replaces map and yahoo
 	    }
	    
	    // lookup for layout file
	    if($name == 'decorated' || $name == 'full') {
		include_once $context['path_to_root'].$family.'/layout_'.$family.'.php';
		$name = 'Layout_'.$family;
		$layout = new $name();
            } elseif(is_readable($context['path_to_root'].$path.'/layout_'.$home.$family.'_as_'.$name.'.php')) {
		$name = 'layout_'.$home.$family.'_as_'.$name;
		include_once $context['path_to_root'].$path.'/'.$name.'.php';
		$layout = new $name;
	    } 
	    // look for refactored layout files
	    elseif (is_readable($context['path_to_root'].'layouts/layout_as_'.$name.'/layout_as_'.$name.'.php')) {
		$name = 'layout_as_'.$name;
		include_once $context['path_to_root'].'layouts/'.$name.'/'.$name.'.php';
		$layout = new $name;
		$layout->listed_type = $item_type;
		
	    }
	    
	    // no layout, fallback to default one
	    if(!$layout) {		

		// useful warning for associates
		if(Surfer::is_associate() && !$silent && $name)
			Logger::error(sprintf(i18n::s('Warning: No script exists for the customized layout %s'), $name));

		include_once $context['path_to_root'].$family.'/layout_'.$family.'.php';
		$variant = $name;
		$name = 'Layout_'.$family;
		$layout = new $name();		
	    }
	    
	    if($variant)
		$layout->set_variant($variant);
	    
	    return $layout;
		    
	}       

}