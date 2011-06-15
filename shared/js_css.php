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
     * return metatags to declare external js libraries
     * @see shared/global.php
     *
     * @param string folder where to search js file, from included/browsers
     * @return string
     */
    function get_js_libraries($folder='') {
	global $context;

	// we provide html tags links to scripts files
	$tags = '';
	
	// path to search for js file, default is "include/browser"
	$path = 'included/browser/'.(($folder)?$folder.'/':'');

	// scan for js files
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
	    // TODO minify&merge in production mode
	    // TODO select XHTML or HTML5 format
	    foreach ($js_libs as $js) {
		$tags .= '<script type="text/javascript" src="'
			.$context['url_to_root'].$path.$js
			.'"></script>'."\n";
	    }
	}

	return $tags;
    }

    function add_external_js($script_path,$target='footer') {

    }

    function add_inline_js($script_text,$target='footer') {

    }
}
?>