<?php
/**
 * layout interface definition
 *
 * @author Bernard Paques
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
abstract Class Layout_interface {
    
	
	/**
	 *  the reference focused by this layout, if any
	 */
	var $focus = '';	 
    
        /**
	 * the variant for this layout, if any
	 */
	var $layout_variant;
	
	/**
	 * type of listed objects
	 */
	var $listed_type;
	
	/**
	 * array of complexe data used for the rendering
	 * that you can provide thru the scripts, in required	 
	 */
	var $data = array();

	/**
	 * the preferred order for items
	 *
	 * This can be used to customize SQL requests used to fetch records
	 * that will be passed to layout instance.
	 *
	 * Example of use:
	 * [php]
	 * // get a layout
	 * $layout = new MyOwn_Layout();
	 *
	 * // get records order
	 * if(!$order = $layout->items_order())
	 *    $order = 'publication';
	 *
	 * // query the database
	 * $items =& Articles::list_for_anchor_by($order, $anchor, 0, 10, $layout);
	 * [/php]
	 *
	 * @return string to be used in requests to the database
	 */
	function items_order() {
		return 'edition';
	}

	/**
	 * the preferred number of items for this layout
	 *
	 * @return int the optimised count of items for this layout
	 */
	function items_per_page() {
		return 50;
	}

	/**
	 * layout one set of results
	 *
	 * @param resource the SQL result of some query
	 * @return mixed the rendered text, or an array to be further formatted
	 */
	abstract function layout($result);

	
	/**
	 * help to retrieve a option in layout variant
	 * 
	 * @param string the search option
	 * @return mixed, false or true if option is present or option value
	 * if its a parameter
	 */
	function has_variant($option) {
	    
	    if(!isset($this->layout_variant))
		    return FALSE;	    
	    
	    // 'per_page' matches with 'per_page_50', return '50'
	    if(preg_match('/\b'.$option.'_(.+?)\b/i', $this->layout_variant, $matches))
			return $matches[1];

	    // exact match, return TRUE
	    if(isset($option_set) && (strpos($this->layout_variant, $option) !== FALSE))
			return TRUE;
	    
	    // bad luck
	    return FALSE;
	}
	
	/**
	 * add data entries for the layout
	 * 
	 * @param mixed $data array build before or string a key for storing
	 * @param mixed $value, what you want to store 
	 */
	function set_data($data,$value='') {
	    
	    if(is_array($data))
		$this->data = array_merge($this->data,$data);
	    elseif(is_string($data))
		$this->data[$data] = $value;
		
	}
	
	/**
	 *  set the focus of this layout
	 *  @see sections/view.php
	 * 
	 *  @param string $reference eg section:<id>
	 */
	function set_focus($reference) {
	    $this->focus = $reference;
	}
	
	
	/**
	 * change the behaviour of this layout
	 *
	 * @param string the variant for this layout, if any
	 * @return void
	 */
	function set_variant($variant = '') {
		$this->layout_variant = $variant;
	}
	
	/**
	 * Load in current page style sheets and javascript 
	 * files binded with the layout. Called by the constructor
	 * 
	 * Filenames must be same as classname, plus extension. 
	 * 
	 * Note : the function will also call dependancies of parent class.
	 * Note : skin must have been loaded (@see shared/global.php, load_skin() )
	 *
	 * @param type $myclass, argument used by the recursive call.
	 */
	final protected function load_scripts_n_styles($myclass='') {
	    global $context;
	    
	    if(!$myclass)
		$myclass = get_class($this);
	   
	    
	    $parent = get_parent_class($myclass);	    
	    
	    // load scripts (if exist)
	    Page::load_style(strtolower('layouts/'.$myclass.'/'.$myclass.'.css'));
	    Page::defer_script(strtolower('layouts/'.$myclass.'/'.$myclass.'.js'));
	    
	    // recursive call to parent class, stop on "Overlay"
	    if($parent!= '' && $parent!='Layout_interface')
		$parent::load_scripts_n_styles($parent);	    
	}

}

?>