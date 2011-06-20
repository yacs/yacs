<?php
/**
 * Static functions used to load JavaScript libraries and Cascading Style Sheet
 *
 * will optimize the use of external scripts (automatic minifying and merging)
 *
 * @author  Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Js_Css {

    /**
     * add declaration for occasionnal external javascript file
     * @see shared/global.php
     *
     * @param string relative path to local script file
     * @param string place in page to load the script, "footer" or "header"
     * @return <type> 
     */
    function add_external_js($path,$target='footer') {
	global $context;
	
	if(!file_exists($context['path_to_root'].$path))
	    return;

	if(!isset($context['javascript'][$target]))
	    $context['javascript'][$target] = '';

	$html = Js_Css::build_js_declaration($context['url_to_root'].$path);
	$context['javascript'][$target] .= $html;
    }

    function add_inline_js($script_text,$target='footer') {

    }

    function build_css_declaration($path,$media="all") {

	$html = '<link rel="stylesheet" href="'.$path.'"type="text/css" media="'.$media.'" />'."\n";
	return $html;
    }

    /**
     * build html declaration to a js file
     * 
     * @todo select XHTML or HTML5 format
     * 
     * @param string path to the file
     * @return string 
     */
    function build_js_declaration($path) {

	$html = '<script type="text/javascript" src="'.$path.'"></script>'."\n";
	return $html;
    }

    /**
     * return <script> html tags to declare external js libraries
     * @see shared/global.php
     *
     * @todo minify&merge files in production mode
     *
     * @param string, folder where to search js file, from included/browsers
     * @param string or array of strings, relative path to other libraries
     * to deal with (e.g. shared/yacs.js)
     * @return string in html format
     */
    function get_js_libraries($folder='',$other_files='') {
	global $context;

	// we provide html tags links to scripts files
	$html = '';

	// cache this across requests
	$cache_id = 'shared/js_css.php#lib_'.$folder;
	if($html =& Cache::get($cache_id))
	    return $html;
	
	// path to search for js file, default is "include/browser"
	$path = 'included/browser/'.(($folder)?$folder.'/':'');

	// scan for js files in folder
	if($dir = Safe::opendir($path)) {

	    $js_libs = array();
	    while(($item = Safe::readdir($dir)) !== FALSE) {
		    if(($item[0] == '.') || is_dir($context['path_to_root'].$path.'/'.$item))
			    continue;
		    if(!preg_match('/^.*\.js$/i', $item))
			    continue;
		    $js_libs[] = $item;
	    }
	    Safe::closedir($dir);

	}
	
	if($js_libs) {
	    // files can be renamed with a letters prefix to sort loading from browsers
	    natsort($js_libs);
	    // build declaration	    
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

	// cache for 1 month
	Cache::put($cache_id, $html, 'stable',2592000);
	return $html;
    }
}
?>