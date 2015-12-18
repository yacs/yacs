<?php
/**
 * Static functions used to load JavaScript libraries and Cascading Style Sheet
 *
 * @author  Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
include_once('global.php');

Class Js_Css {
    
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
    
    public static function call_skin_css() {
        global $context;
        
        if(!isset($context['skin'])) return '';
        
        // retrieve base name of the skin (remove "skin/")
        $skin = substr($context['skin'],5);
            
        return Js_css::link_file($context['skin'].$skin.'.css','now');
        
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

                            $revision = Js_css::get_revision($context['path_to_root'].$path);

			    $html = Js_Css::build_js_declaration($context['url_to_master'].$context['url_to_root'].$path.$revision);
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

                        $revision = Js_css::get_revision($context['path_to_root'].$path.$js);

                        $html .= Js_Css::build_js_declaration($context['url_to_master'].$context['url_to_root'].$path.$js.$revision);
		    }
	    }

	    if($other_files) {
		    if(gettype($other_files)=='string')
		    $other_files = (array) $other_files;

		    foreach ($other_files as $file) {
                        if(file_exists($context['path_to_root'].$file)) {

                            $revision = Js_css::get_revision($context['path_to_root'].$file);

                            $html .= Js_Css::build_js_declaration($context['url_to_master'].$context['url_to_root'].$file.$revision);
                        }
		    }

	    }

	    return $html;
    }

    // get last revision date
    // this will be added to file url as fake param to force
    // cache updating of surfer's browser
    private static function get_revision($path) {

            $udp_stamp = filemtime($path);
            $revision  = ($udp_stamp)? '?'.date('ymdHi',$udp_stamp) : '' ;

            return $revision;
    }
    
    /**
     * This function insert some javascript of css styles in the page
     *
     * @param string $script javascript code or css rules
     * @param string $type to tell the function what can of script it is
     * by default 'js' because it is the main use.
     * you may force position for example "js:header"
     */
    public static function insert($script, $type='js') {
	global $context;
        
        // separate type from target position, if any
        if(strpos($type, ":") !== FALSE)
                list($type,$target) = explode (':', $type);
        else
                $target = 'footer';

	switch($type) {
	    case 'css' :

		Js_css::add_css('<style> '.$script.' </style>');
		break;
	    case 'js' :

		$type = (SKIN_HTML5)?'':' type="text/javascript" ';
		// minification lib
		//include_once $context['path_to_root'].'included/jsmin.php';

		Js_css::add_js('<script'.$type.'> '.$script.'</script>', $target);

		break;
	    default :
		break;
	}
    }
    
    /**
     * internal function to exit link_file with boolean or string output
     * 
     * @param boolean $goodorbad the state of the reply
     * @param string $path the file that was looked for
     * @param boolean $reply_as_string , do like this if true
     * @return mixed boolean or string
     */
    private static function link_exit ($goodorbad, $path, $reply_as_string) {
        
        if($goodorbad === true)
            return ( ($reply_as_string)? '' : true );
        else {
            if( $reply_as_string)
                return '<!-- error with file "'.$path.'" --->';
            else 
                return false;
        }
    }

    /**
     * prepare a link to .js or .css file for declare in final template
     * will search of a minified version in production mode
     *
     * @global type $context
     * @param string $path, relative from yacs root, or external url
     * @param string $forced_type = 'js' or 'css', if path does not end by .js or .css.
     * @param string $forced_position = 'header', 'defer' of 'footer' to specify where to
     * load a js file (defer always before footer).
     * @param boolean $straitnow to get the link without adding it to stack
     * @return false if unsucceed
     */
    public static function link_file($path, $forced_type='',$forced_position='', $straitnow=false) {
	global $context;
        
        // enable shorter function call
        if (strtolower($forced_type) === 'now') {
            $straitnow = true;
            $forced_type='';
        }

	// avoid linking twice the same file
	$key = md5($path);
	if(isset($context['linked_files'][$key])) {
                return Js_Css::link_exit(true, $path, $straitnow);
        } else {
	    $context['linked_files'][$key] = $path;
        }
        
	// gather info on file
	$path_parts = pathinfo($path);

	// just to avoid warnings
	if(!isset($path_parts['extension'])) $path_parts['extension']='';

	// how the script will be considered ?
	$ext = ($forced_type)?$forced_type:$path_parts['extension'];

	// we need a extension
	if(!isset($ext)) {
	    return Js_Css::link_exit(false, $path, $straitnow);
	}

	// if path is a local file
	if(strncmp($path, 'http', 4)!=0) {
            
            $realpath = Safe::realpath($path);

	    // check if file exists
	    if(!file_exists($realpath)) return Js_Css::link_exit(false, $path, $straitnow);

	    // and we are in production mode
	    // and file not already minified
	    if ( $context['with_debug']=='N'
		&& !preg_match('/\.min\.?/', $path_parts['filename'])) {

		// minified version path
		$min_v      = $path_parts['dirname'].'/'.$path_parts['filename'].'.min.'.$path_parts['extension'];
                $real_min_v = Safe::realpath($min_v);
                
                $date_src = $date_min = filemtime($realpath);
                if(file_exists($real_min_v)) {
                    $date_min = filemtime($real_min_v);
                }
                
                // compare minified file last update to original src file
		if($date_src < $date_min) {
                    $path = $min_v; 
                } else {
                    // need minification for next time, but launch it as a background process
                    proceed_bckg('tools/minifier.php?script='.Safe::realpath($path));
                }

	    }

            // get last revision date
            $revision  = Js_css::get_revision(Safe::realpath($path));

	    // add root url
            if(isset($context['static_subdom']) && $context['static_subdom'] )
                $path = $context['static_subdom'].$path;
            else
                $path = $context['url_to_master'].$context['url_to_root'].$path;
	} else
            // we can't know the revision date of external files
            $revision = '';

	// css or js ?
	switch($ext) {

	    case 'css' :
                $tag = Js_css::build_css_declaration($path.$revision);
                
                if($straitnow) return $tag;
                  
		Js_css::add_css($tag);
		break;
	    case 'js' :
                
                $tag = Js_css::build_js_declaration($path.$revision);
                
                if($straitnow) return $tag;
                
		// target is header if .head. is part of filename
		if(!$forced_position && preg_match('/\.head\./', $path_parts['filename']))
			$forced_position = 'header';

		// by default .js goes to page footer
		$target = ($forced_position)?$forced_position:'defer';

		Js_css::add_js($tag,$target);
		break;
	    default :
		// error
		return Js_Css::link_exit(false, $path, $straitnow);
	}


	// count files calls over time
	if($context['with_debug']=='Y'&& !defined('NO_MODEL_PRELOAD')) {
	    $query = 'INSERT INTO '.SQL::table_name('js_css_calls').' SET'
		    .' id = "'.$key.'",'
		    .' path = "'.$path.'",'
		    .' calls = 1'
		    .' ON DUPLICATE KEY UPDATE calls=calls+1';

	    SQL::query($query, TRUE);
	}

        return Js_Css::link_exit(true, $path, $straitnow);
    }
    
    /**
     * Minify a script using a external API service
     * 
     * @param string $path local to the file to minify
     * @param string $ext of the file
     */
    public static function minify($path) {
        
        // get file content
        $to_minify = safe::file_get_contents($path);
        
        // sanity check
        if(!$to_minify) {
            logger::remember('shared/js_css.php', 'cannot get file to minify : '.$path);
            return false;
        }
        
        // gather info on file
        $path_parts = pathinfo($path);
        
        // build http query depending on file extension
        switch ($path_parts['extension']) {
            case 'css':
                $url = 'http://cssminifier.com/raw';
                
                //// do a part of minification job to limit the size of transmited content
                
                // Normalize whitespace
                $to_minify = preg_replace( '/\s+/', ' ', $to_minify );

                // Remove spaces before and after comment
                $to_minify = preg_replace( '/(\s+)(\/\*(.*?)\*\/)(\s+)/', '$2', $to_minify );
                
                // Remove comment blocks, everything between /* and */, unless
                // preserved with /*! ... */ or /** ... */
                $to_minify = preg_replace( '~/\*(?![\!|\*])(.*?)\*/~', '', $to_minify );

                break;
            case 'js':
                $url = 'https://javascript-minifier.com/raw';

                break;
            default:
                $url = '';
                break;
        }
        
        if($url) {
            
            // try with cURL 
            if(is_callable(curl_init)) {
                $data = 'input='.urlencode($to_minify);
                
                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                
                $minified = curl_exec($ch);
    
                if($minified === false) {
                    logger::remember('shared/js_css','cURL error: ' . curl_error($ch));
                } 
                
            
            // try with file_get_contents, must have allow_url_fopen = yes
            } else {
                $data = array('input' => $to_minify);


                $postdata = array('http' => array(
                    'method'  => 'POST',
                    'header'  => 'Content-type: application/x-www-form-urlencoded',
                    'content' => http_build_query( array('input' => $to_minify) ) ) );

                $minified = file_get_contents($url, false, stream_context_create($postdata));
            }
            
            

            ///// save the $minified version
            // build the path
            $min_path = $path_parts['dirname'].'/'.$path_parts['filename'].'.min.'.$path_parts['extension'];
            
            // delete previous one
            Safe::unlink($min_path);
            
            // save new
            if($minified) {
                return Safe::file_put_contents($min_path, $minified);
            }
            
        }
        
        return false;
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