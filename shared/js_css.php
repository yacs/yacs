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
	foreach(Safe::glob($context['path_to_root'].$path.'*.js') as $file) {
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

}
?>