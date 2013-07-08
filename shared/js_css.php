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
     * @param string $forced_position = 'header' of 'footer' to specify where to
     * load a js file.
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
		$target = ($forced_position)?$forced_position:'footer';
		
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

    public static function include_file($path) {

    }


    private static function add_css($css) {
	global $context;

	if(!isset($context['page_header']))
		    $context['page_header'] = '';

	if(substr($css,-1)!="\n") $css .= "\n";

	$context['page_header'] .= $css;

    }

    /**
     */
    private static function add_js($js,$target='footer') {
	global $context;

	if(!isset($context['javascript'][$target]))
		$context['javascript'][$target] = '';

	if(substr($js,-1)!="\n") $js .= "\n";

	$context['javascript'][$target] .= $js;
    }

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
			    $html = Js_Css::build_js_declaration($context['url_to_root'].$path);
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
		    $html .= Js_Css::build_js_declaration($context['url_to_root'].$path.$js);
		    }
	    }

	    if($other_files) {
		    if(gettype($other_files)=='string')
		    $other_files = (array) $other_files;

		    foreach ($other_files as $file) {
		    if(file_exists($context['path_to_root'].$file))
			    $html .= Js_Css::build_js_declaration($context['url_to_root'].$file);
		    }

	    }

	    return $html;
    }	
    
    
	/**
	 * create table for js_css
	 * to count js and css files calls over time
	 *
	 * @see control/setup.php
	 */
	public static function setup() {
	    
	    $fields = array();
	    $fields['id']		= "VARCHAR(32) NOT NULL";
	    $fields['path']		= "VARCHAR(255) DEFAULT '' NOT NULL";
	    $fields['calls']		= "MEDIUMINT UNSIGNED DEFAULT 1 NOT NULL";	
	    
	    $indexes = array();
	    $indexes['PRIMARY KEY'] 	= "(id)";
	    
	    return SQL::setup_table('js_css_calls', $fields, $indexes);
	    
	}

}
?>