<?php
/**
 * Static functions used to load JavaScript libraries and Cascading Style Sheet
 *
 * @author  Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Js_Css {
    
    /**
     * prepare a link to .js or .css file for declare in final template
     * will search of a minified version in production mode
     * 
     * @global type $context
     * @param string $path, relative from yacs root, or external url
     * @param string $forced_type = 'js' or 'css', if path does not end by .js or .css
     * @param string $forced_position = 'header', 'defer' of 'footer' to specify where to
     * load a js file (defer always before footer).
     * @return false if unsucceed 
     */
    public static function link_file($path, $forced_type='',$forced_position='') {
	global $context;
	
	// avoid linking twice the same file
	$key = md5($path);
	if(isset($context['linked_files'][$key]))
	    return true;
	else
	    $context['linked_files'][$key] = $path;
	
	// gather info on file
	$path_parts = pathinfo($path);
	
	// just to avoid warnings
	if(!isset($path_parts['extension'])) $path_parts['extension']=''; 
	
	// how the script will be considered ?
	$ext = ($forced_type)?$forced_type:$path_parts['extension'];

	// we need a extension	    
	if(!isset($ext)) {
	    // TODO : log error
	    return false;
	}

	// if path is a local file 	    
	if(strncmp($path, 'http', 4)!=0) {
	    
	    // check if file exists
	    if(!file_exists($context['path_to_root'].$path)) return false;

	    // and we are in production mode 
	    // and file not already minified 	    
	    if ( $context['with_debug']=='N' 
		&& !preg_match('/\.min\./', $path_parts['filename'])) {					    		

		// minified version path
		$min_v = $path_parts['dirname'].'/'.$path_parts['filename'].'.min.'.$path_parts['extension'];

		if(file_exists($min_v)) $path = $min_v;		    		    		    			        

	    // TODO : warning case exept if .core. ;
	    }

	    // add root url
	    $path = $context['url_to_master'].$context['url_to_root'].$path;
	}	    

	// css ou js ?
	switch($ext) {

	    case 'css' :
		Js_css::add_css(Js_css::build_css_declaration($path));
		break;
	    case 'js' :
		// target is header if .head. is part of filename
		if(!$forced_position && preg_match('/\.head\./', $path_parts['filename'])) 
			$forced_position = 'header';

		// by default .js goes to page footer
		$target = ($forced_position)?$forced_position:'defer';
		
		Js_css::add_js(Js_css::build_js_declaration($path),$target);
		break;
	    default :
		// error		    
		return false;
	}
	
	
	// count files calls over time
	if($context['with_debug']=='Y') {
	    $query = 'INSERT INTO '.SQL::table_name('js_css_calls').' SET'
		    .' id = "'.$key.'",'
		    .' path = "'.$path.'",'
		    .' calls = 1'
		    .' ON DUPLICATE KEY UPDATE calls=calls+1';

	    SQL::query($query, TRUE);
	}
	

    }
	
    /**
     * This function insert some javascript of css styles in the page
     * 
     * @param string $script javascript code or css rules
     * @param string $type to tell the function what can of script it is
     * by default 'js' because it is the main use. 
     */
    public static function insert($script, $type='js') {
	global $context;

	switch($type) {
	    case 'css' :

		Js_css::add_css('<style> '.$script.' </style>');		    
		break;
	    case 'js' :

		$type = (SKIN_HTML5)?'':' type="text/javascript" ';
		// minification lib
		//include_once $context['path_to_root'].'included/jsmin.php';		    

		Js_css::add_js('<script'.$type.'> '.$script.'</script>', 'footer');

		break;
	    default :
		break;
	}
    }

    /**
     * Add declaration of a css file to the preparation of the page
     * 
     * @param string $css 
     */
    private static function add_css($css) {
	global $context;

	if(!isset($context['page_header']))
		    $context['page_header'] = '';

	if(substr($css,-1)!="\n") $css .= "\n";

	$context['page_header'] .= $css;

    }

    /**
     *  Add a js file declaration to the preparation of the page
     *  Target could be header, defer or footer
     * 
     *  @param string $js the path to the file
     *  @param string target the place where to include the declaration
     */
    private static function add_js($js,$target='footer') {
	global $context;

	if(!isset($context['javascript'][$target]))
		$context['javascript'][$target] = '';

	if(substr($js,-1)!="\n") $js .= "\n";

	$context['javascript'][$target] .= $js;
    }

    /**
     * build declaration of a css style sheet
     * 
     * @param string $path to the file
     * @param string $media concerned by the file (all, screen, print...)
     * @return string the declaration 
     */
    public static function build_css_declaration($path,$media="all") {

	    $type = (SKIN_HTML5)?'':' type="text/css" ';

	    $html = '<link rel="stylesheet" href="'.$path.'"'.$type.' media="'.$media.'" />'."\n";
	    return $html;
    }

    /**
     * build html declaration to a js file
     *	 
     *
     * @param string path to the file
     * @return string
     */
    public static function build_js_declaration($path) {

	$type = (SKIN_HTML5)?'':' type="text/javascript" ';

	$html = '<script'.$type.' src="'.$path.'"></script>'."\n";
	return $html;
    }
        
    
    /**
     * This function takes all urls of script that should be deferred
     * and put them in a array variable
     * Yacs will use this variable to load script later with a getScript ajax
     * request
     * This is used by the overlaid viewing mode
     * 
     * @return void
     */
    private static function extract_src_from_deferred_scripts() {
	global $context;
	
	$reset = 'delete scripts_to_load';	
	
	if(!isset($context['javascript']['defer'])) {
	    Js_css::insert($reset);
	    return;
	}
	
	$src= array();
	if(!preg_match_all('/src="(.*?)"/sim', $context['javascript']['defer'], $src)) {
	    Js_css::insert($reset);
	    return;
	}
	
	array_shift($src); // remove matches[0] with all pattern
	$to_load = 'var scripts_to_load = ["'.implode('","',$src[0]).'"];';
	
	Js_css::insert($to_load);
    }

    /**
     * return <script> html tags to declare external js libraries stored 
     * in a given folder
     * @see shared/global.php to see use with /included/browser
     *     
     *
     * @param string, folder where to search js file, from included/browsers
     * @param string or array of strings, relative path to other libraries
     * to deal with (e.g. shared/yacs.js)
     * @return string in html format
     */
    public static function get_js_libraries($folder='',$other_files='') {
	    global $context;

	    // we provide html tags links to scripts files
	    $html = '';

	    // in production mode, provide link without scanning if compressed lib is present
	    if($context['with_debug']=='N') {
		    $path = 'included/browser/library_'.$folder.'.min.js';
		    if(file_exists($context['path_to_root'].$path)) {
			    $html = Js_Css::build_js_declaration($context['url_to_master'].$context['url_to_root'].$path);
		    return $html;
		    }
	    }

	    // path to search for js file, default is "include/browser"
	    $path = 'included/browser/'.(($folder)?$folder.'/':'');

	    // scan for js files in folder
	    $js_libs = array();
	    if(!$files = Safe::glob($context['path_to_root'].$path.'*.js')) return $html;
	    foreach($files as $file) {
		    $js_libs[]= basename($file);
	    }

	    if($js_libs) {
		    // files can be renamed with a letter prefix to sort loading from browsers
		    natsort($js_libs);

		    // build declarations file by file
		    foreach ($js_libs as $js) {
		    $html .= Js_Css::build_js_declaration($context['url_to_master'].$context['url_to_root'].$path.$js);
		    }
	    }

	    if($other_files) {
		    if(gettype($other_files)=='string')
		    $other_files = (array) $other_files;

		    foreach ($other_files as $file) {
		    if(file_exists($context['path_to_root'].$file))
			    $html .= Js_Css::build_js_declaration($context['url_to_master'].$context['url_to_root'].$file);
		    }

	    }

	    return $html;
    }
    
    /**
     * overlaying a page thru a ajax call needs special preparation
     * for javascript files in order to be loaded and executed properly
     * 
     * 
     * @see shared/global.php 
     * @see shared/yacs.js, yacs.displayOverlaid()
     */
    public static function prepare_scripts_for_overlaying() {
		
	// call functions in this order : wrap_footer, extract_src
	Js_css::wrap_footer_scripts();
	Js_css::extract_src_from_deferred_scripts();
		
    }
    
    
    /**
     * create table for js_css
     * to count js and css files calls over time
     *
     * @see control/setup.php
     */
    public static function setup() {

	$fields = array();
	$fields['id']		    = "VARCHAR(32) NOT NULL";
	$fields['path']		    = "VARCHAR(255) DEFAULT '' NOT NULL";
	$fields['calls']	    = "MEDIUMINT UNSIGNED DEFAULT 1 NOT NULL";	

	$indexes = array();
	$indexes['PRIMARY KEY']	    = "(id)";

	return SQL::setup_table('js_css_calls', $fields, $indexes);

    }
    
    /**
     * This function wrap all end of page javascript snippets
     * in a special function "execute_after_loading"
     * The function will be called later by yacs.js when script
     * are ready to be executed
     * This is because you cannot relay on $.ready() for a script loaded asynchroniusly
     * the function has to pay attention about portion of codes that are only declaration of a function or variable
     * they should not be nested in the closure of execute_after_loading
     * 
     * @return void 
     */
    private static function wrap_footer_scripts() {
	global $context;
	
	$reset = 'delete execute_after_loading;';
	
	if(!isset($context['javascript']['footer'])) {
	    Js_css::insert($reset);
	    return;
	}
	
	// extract code from <script></script> tags
	$scripts = array();
	if(!preg_match_all('/<script.*?>(.*?)<\/script/sim', $context['javascript']['footer'], $scripts)) 
	    return;
	
	array_shift($scripts); // remove matches[0] with all pattern
		
	// parse array and look for function declarations	
	$declare_only = array();
	for($i = 0; $i < count($scripts[0]); $i++) {

	    $matches = array();
	    if(preg_match_all('/(function\ [a-zA-Z_]+\ ?\([a-zA-Z0-9_,$\-\ ]*\)\ ?(\{ ( (?>[^{}]+) | (?-2) )* \}) )/simx'
		    , $scripts[0][$i], $matches)) {
		
		// consider matches for the whole pattern
		foreach($matches[0] as $match) {
		    // store them
		    $declare_only[] = $match;
		    // remove this part of the script
		    $scripts[0][$i] = str_replace($match, '', $scripts[0][$i]);
		}
				
		// unset if void
		if(!trim($scripts[0][$i]))
		    unset ($scripts[0][$i]);
	    }
	}
	
	$wrapped = implode("\n", $declare_only)."\n";
	$wrapped .= 'function execute_after_loading() {'.implode("\n", $scripts[0]).' Yacs.updateModalBox(true)'."\n".'}'."\n";
					
	// erase footer
	$context['javascript']['footer'] = '';
	// fill with new wrapped code
	Js_css::insert($wrapped);
	
	return;
	
    }

}
?>