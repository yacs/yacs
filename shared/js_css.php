<?php
/**
 * Static functions used to load JavaScript libraries and Cascading Style Sheet.
 * 
 * Handle scss compilation and js/css minification.
 * Build html tags of links to files, including a version number to force 
 * updating cache of browsers.
 * 
 * Build main style sheet, including by default fontawsome and knacss.
 * Allow overiding trought tune.scss from active skin.
 * 
 * Manage css and js even when a page is loaded by ajax and overlaid.
 * 
 * All compiled and minified files are stored in /temporary folder. Make sure
 * php user has the right to write there. Relative paths within CSS are 
 * automatically updated for this final location.
 * 
 * Usage of css and js scripts is counted in table `js_css_calls` of database.
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
    
    /**
     * Provide a link to the main css file of the active skin
     *
     * The sheet is built in temporary/<skin>.min.css by build_skin_css().
     * In production mode (with_debug = 'N') it is only built if it does not
     * exist yet: rebuild it with tools/build_css.php after a change.
     * In development mode it is rebuilt each time a source is newer.
     *
     * @see tools/build_css.php
     *
     * return string
     */
    public static function call_skin_css() {
        global $context;

        // we need a skin
        if(!isset($context['skin'])) return '';

        // retrieve base name of the skin (remove "skins/")
        $skin = substr($context['skin'],6);

        // the main sheet, as built
        $output = 'temporary/'.$skin.'.min.css';
        $built  = Safe::filemtime($context['path_to_root'].$output);

        // nothing to serve yet, or development mode with newer sources
        if(!$built || (($context['with_debug'] == 'Y') && ($built < Js_css::skin_css_stamp()))) {

            if(Js_css::build_skin_css() !== true)
                return Js_Css::link_exit(false, 'skin main style sheet' , 'now');

        // production mode: only tell associates that sources have changed
        } elseif(($context['with_debug'] != 'Y') && Js_css::is_associate() && ($built < Js_css::skin_css_stamp()))
            Js_css::warn_outdated_styles();

        // build declaration
        return Js_css::link_file($output,'now');
    }

    /**
     * Compile and minify the main css file of the active skin
     *
     * Content is, depending on constants set by skin.php:
     * - knacss as a reset and framework
     * - fontawesome
     * - yacss, general styles for the system
     * - the specific style of the skin, <skin>.scss or <skin>.css,
     *   with variables of tune.scss
     *
     * The result is written in temporary/<skin>.min.css
     *
     * @return mixed true on success, or an error message
     */
    public static function build_skin_css() {
        global $context;

        // we need a skin
        if(!isset($context['skin']))
            return 'no skin';

        // retrieve base name of the skin (remove "skins/")
        $skin = substr($context['skin'],6);

        // load scssphp
        $scss = js_css::prepare_scss_compiler();

        // set import directories
        $scss->setImportPaths(
              array(
                  $context['path_to_root'].'included/knacss/',
                  $context['path_to_root'].'skins/_reference/',
                  $context['path_to_root'].'included/font_awesome/scss',
                  $context['path_to_root'].$context['skin'].'/',
                  ));

        // build import directives
        $import = '';
        if(file_exists($context['path_to_root'].$context['skin'].'/tune.scss'))
            $import .= '@import "tune.scss";';
        if(Js_css::with_lib('knacss'))
            $import .= '@import "knacss.scss";';
        if(Js_css::with_lib('fontawesome'))
            $import .= '@import "fontawesome.scss";';
        if(Js_css::with_lib('yacss'))
            $import .= '@import "variables.scss";@import "yacss.scss";';

        if(file_exists($context['path_to_root'].$context['skin'].'/'.$skin.'.scss')) {
            $import   .= '@import "'.$skin.'.scss";';
        } elseif(file_exists($context['path_to_root'].$context['skin'].'/'.$skin.'.css')) {
            // append pure css
            $import   .= Safe::file_get_contents($context['path_to_root'].$context['skin'].'/'.$skin.'.css');
        }

        // write next to the source, so that relative paths are updated by the minifier
        return Js_css::compile_and_minify($scss, $import,
            $context['path_to_root'].$context['skin'].'/'.$skin.'.compiled.css',
            $context['path_to_root'].'temporary/'.$skin.'.min.css');
    }

    /**
     * Compile and minify a scss file of a layout, of an overlay, or of the skin
     *
     * Variables of tune.scss, of yacss and of knacss are available to the file.
     *
     * @param string $inputFile path to scss file
     * @param string $outputFile path to css file
     * @return mixed true on success, or an error message
     */
    public static function build_scss($inputFile, $outputFile) {
        global $context;

        // load scssphp
        $scss        = js_css::prepare_scss_compiler();

        // load variables from skin if any
        $to_compile  = Safe::file_get_contents($context['path_to_root'].$context['skin'].'/tune.scss');

        // load variables from reference
        if(Js_css::with_lib('yacss'))
            $to_compile .= Safe::file_get_contents($context['path_to_root'].'skins/_reference/variables.scss');

        // load variables from knacss
        if(Js_css::with_lib('knacss'))
            $to_compile .= Safe::file_get_contents($context['path_to_root'].'included/knacss/_config/_variables.scss');

        // load input file
        $to_compile .= Safe::file_get_contents($inputFile);

        // write next to the source, so that relative paths are updated by the minifier
        return Js_css::compile_and_minify($scss, $to_compile, $inputFile.'comp', $outputFile);
    }

    /**
     * Compile scss file if needed
     *
     * In production mode (with_debug = 'N') the file is only compiled if
     * the css file does not exist yet: rebuild it with tools/build_css.php
     * after a change.
     * In development mode it is compiled each time a source is newer.
     *
     * @see tools/build_css.php
     *
     * @param string $inputFile path to scss file
     * @param string $outputFile path to css file
     * @return boolean false if the css file could not be built
     */
    public static function check_compile($inputFile, $outputFile) {
        global $context;

        $built = Safe::filemtime($outputFile);

        // nothing to serve yet, or development mode with newer sources
        if(!$built || (($context['with_debug'] == 'Y') && ($built < Js_css::scss_stamp($inputFile))))
            return (Js_css::build_scss($inputFile, $outputFile) === true);

        // production mode: only tell associates that sources have changed
        if(($context['with_debug'] != 'Y') && Js_css::is_associate() && ($built < Js_css::scss_stamp($inputFile)))
            Js_css::warn_outdated_styles();

        return true;
    }

    /**
     * Check if style sheets have to be rebuilt for the use of yacs in
     * production mode
     *
     * Style sheets are the main sheet of the active skin, and the
     * scss and css files of layouts and overlays.
     *
     * @see tools/build_css.php
     *
     * @return boolean is a rebuild needed
     */
    public static function check_production_styles() {
        global $context;

        foreach(Js_css::list_styles() as $source => $output) {
            if(Safe::filemtime($output) < Js_css::source_stamp($source))
                return true;
        }

        return false;
    }

    /**
     * Build a style sheet listed by list_styles()
     *
     * @param string $source path to the source, or 'skin' for the main sheet of the skin
     * @param string $output path to the built file
     * @return mixed true on success, or an error message
     */
    public static function build_style($source, $output) {

        if($source == 'skin')
            return Js_css::build_skin_css();
        if(substr($source, -5) == '.scss')
            return Js_css::build_scss($source, $output);
        return Js_css::build_css($source, $output);
    }

    /**
     * Most recent modification of the sources of a style sheet listed by list_styles()
     *
     * @param string $source path to the source, or 'skin' for the main sheet of the skin
     * @return int timestamp
     */
    public static function source_stamp($source) {

        if($source == 'skin')
            return Js_css::skin_css_stamp();
        if(substr($source, -5) == '.scss')
            return Js_css::scss_stamp($source);
        return (int)Safe::filemtime($source);
    }

    /**
     * List style sheets to build for the active skin
     *
     * Keys are paths to sources, and values paths to built files.
     * The main sheet of the skin has the key 'skin'.
     * Other scss files of the skin are listed only if they have been built
     * once, because most of them are partials of the main sheet.
     * Css files that are already minified (*.min.css) are skipped.
     *
     * @return array source => output
     */
    public static function list_styles() {
        global $context;

        $styles = array();

        // we need a skin
        if(!isset($context['skin']))
            return $styles;

        // retrieve base name of the skin (remove "skins/")
        $skin = substr($context['skin'],6);

        // the main sheet of the skin
        $styles['skin'] = $context['path_to_root'].'temporary/'.$skin.'.min.css';

        // scss and css files of layouts and overlays
        foreach(array('layouts', 'overlays') as $folder) {
            foreach(array('scss', 'css') as $ext) {
                if($files = Safe::glob($context['path_to_root'].$folder.'/*/*.'.$ext)) {
                    foreach($files as $file) {
                        if(preg_match('/\.min$/', pathinfo($file, PATHINFO_FILENAME)))
                            continue;
                        $styles[$file] = $context['path_to_root'].'temporary/'.pathinfo($file, PATHINFO_FILENAME).'.min.css';
                    }
                }
            }
        }

        // other style sheets of the skin -- scss files only if they have been built once
        foreach(array('scss', 'css') as $ext) {
            if($files = Safe::glob($context['path_to_root'].$context['skin'].'/*.'.$ext)) {
                foreach($files as $file) {
                    $name = pathinfo($file, PATHINFO_FILENAME);
                    if(($name == $skin) || preg_match('/\.min$/', $name))
                        continue;
                    $output = $context['path_to_root'].'temporary/'.$name.'.min.css';
                    if(($ext == 'css') || file_exists($output))
                        $styles[$file] = $output;
                }
            }
        }

        return $styles;
    }

    /**
     * Most recent modification of sources of the main sheet of the skin
     *
     * @return int timestamp
     */
    private static function skin_css_stamp() {
        global $context;

        // retrieve base name of the skin (remove "skins/")
        $skin = substr($context['skin'],6);

        $sources = array();
        if(Js_css::with_lib('knacss'))
            $sources[] = 'included/knacss/knacss.scss';
        if(Js_css::with_lib('fontawesome'))
            $sources[] = 'included/font_awesome/scss/fontawesome.scss';
        if(Js_css::with_lib('yacss')) {
            $sources[] = 'skins/_reference/variables.scss';
            $sources[] = 'skins/_reference/yacss.scss';
        }
        $sources[] = $context['skin'].'/'.$skin.'.css';

        $stamp = 0;
        foreach($sources as $source)
            $stamp = max($stamp, (int)Safe::filemtime($context['path_to_root'].$source));

        // tune.scss and partials imported by <skin>.scss may change alone: consider all *.scss of the skin
        if($partials = Safe::glob($context['path_to_root'].$context['skin'].'/*.scss')) {
            foreach($partials as $partial)
                $stamp = max($stamp, (int)Safe::filemtime($partial));
        }

        return $stamp;
    }

    /**
     * Most recent modification of a scss file and of the variables it receives
     *
     * @param string $inputFile path to scss file
     * @return int timestamp
     */
    private static function scss_stamp($inputFile) {
        global $context;

        $stamp = max((int)Safe::filemtime($inputFile), (int)Safe::filemtime($context['path_to_root'].$context['skin'].'/tune.scss'));
        if(Js_css::with_lib('yacss'))
            $stamp = max($stamp, (int)Safe::filemtime($context['path_to_root'].'skins/_reference/variables.scss'));
        if(Js_css::with_lib('knacss'))
            $stamp = max($stamp, (int)Safe::filemtime($context['path_to_root'].'included/knacss/_config/_variables.scss'));

        return $stamp;
    }

    /**
     * Is a library part of the main sheet?
     *
     * Constants NO_KNACSS, NO_YACSS and NO_FONTAWESOME may be defined by skin.php
     *
     * @param string $lib 'knacss', 'yacss' or 'fontawesome'
     * @return boolean
     */
    private static function with_lib($lib) {
        global $context;

        switch($lib) {
            case 'knacss':
                return !(defined('NO_KNACSS') && NO_KNACSS == true)
                    && file_exists($context['path_to_root'].'included/knacss/knacss.scss');
            case 'yacss':
                return !(defined('NO_YACSS') && NO_YACSS == true)
                    && file_exists($context['path_to_root'].'skins/_reference/yacss.scss');
            case 'fontawesome':
                return !(defined('NO_FONTAWESOME') && NO_FONTAWESOME == true)
                    && file_exists($context['path_to_root'].'included/font_awesome/scss/fontawesome.scss');
        }
        return false;
    }

    /**
     * Compile scss, minify the result and write it to its final place
     *
     * @param object $scss the prepared compiler
     * @param string $source scss to compile
     * @param string $compiled path to an intermediate css file, next to the source
     * @param string $output path to the final css file
     * @return mixed true on success, or an error message
     */
    private static function compile_and_minify($scss, $source, $compiled, $output) {

        // compile into a css file, catch errors
        try {
            $compilation = $scss->compile($source);
        } catch (Exception $e) {
            logger::debug("fatal error: " . $e->getMessage(), 'SCSS compilation');
            return $e->getMessage();
        }

        if(!$compilation)
            return 'empty compilation';

        // write into a temporary file next to source
        if(!Safe::file_put_contents($compiled, $compilation))
            return 'cannot write '.$compiled;

        // minify next to the target
        $written = Js_css::build_css($compiled, $output);

        // suppress temporary compiled file
        Safe::unlink($compiled);

        return $written;
    }

    /**
     * Minify a css file and write it to its final place
     *
     * The final file is replaced in one step, so that it is never missing
     * for pages served at the same time.
     *
     * @param string $inputFile path to css file
     * @param string $outputFile path to minified file
     * @return mixed true on success, or an error message
     */
    public static function build_css($inputFile, $outputFile) {

        // load a minifier and provide the file
        $minifier = js_css::prepare_minifier('css');
        $minifier->add($inputFile);

        // execute minification next to the target
        $minified = $outputFile.'.'.getmypid().'.tmp';
        $minifier->minify($minified);

        // replace the target in one step
        if(!file_exists($minified))
            return 'cannot write '.$minified;
        if(!rename($minified, $outputFile)) {
            Safe::unlink($outputFile);
            if(!rename($minified, $outputFile)) {
                Safe::unlink($minified);
                return 'cannot write '.$outputFile;
            }
        }

        return true;
    }

    /**
     * Is the surfer an associate?
     *
     * @return boolean
     */
    private static function is_associate() {
        return is_callable(array('Surfer', 'is_associate')) && Surfer::is_associate();
    }

    /**
     * Tell associates, once per page, that style sheets have to be rebuilt
     */
    private static function warn_outdated_styles() {
        global $context;
        static $warned = false;

        if($warned)
            return;
        $warned = true;

        Logger::error(sprintf(i18n::s('Style sheets have been changed, %s to apply changes.'),
            '<a href="'.$context['url_to_root'].'tools/build_css.php">'.i18n::s('rebuild them').'</a>'));
    }
    
    /**
     * Check if javascript libs need to be compressed for the use of
     * yacs in production mode
     * 
     * libs are :
     * - all .js in /included/brower/js_header
     * - all .js in /included/brower/js_endpage
     * - /shared/yacs.js
     * 
     * @return boolean is compression needed
     */
    public static function check_production_libs() {
        global $context;
        
        // date of production libs
        $lib_head       = Safe::filemtime($context['path_to_root'].'temporary/library_js_header.min.js');
        $lib_endp       = Safe::filemtime($context['path_to_root'].'temporary/library_js_endpage.min.js');
        
        $stamp_header   = 0;    // newest timestamp
        $names          = '';   // concat file names
        // search libs in header for bigger timestamp
        if($files = Safe::glob($context['path_to_root'].'included/browser/js_header/'.'*.js')) {
            
            foreach($files as $file) {
                
                $stamp = Safe::filemtime($file);
                $stamp_header = ($stamp > $stamp_header)?$stamp:$stamp_header;
                $names       .= basename($file);
            }
            
            if( $stamp_header > $lib_head) return true;
            
            $names_check = md5($names);
            // check last compression have same checksum
            if(!$checksum = Safe::filemtime('included/browser/js_header/'.$names_check.'.auto.sum')) {
                // file does not exist, so redo
                return true;
            }
        }
        
        $stamp_endpage  = 0; // timestamp
        $names          = '';   // concat file names
        // search libs in endpage for bigger timestamp
        if($files = Safe::glob($context['path_to_root'].'included/browser/js_endpage/'.'*.js')) {
            
            foreach($files as $file) {
                
                $stamp = Safe::filemtime($file);
                $stamp_endpage = ($stamp > $stamp_endpage)?$stamp:$stamp_endpage; 
                $names       .= basename($file);
            }
            
            if( $stamp_endpage > $lib_endp) return true;
            
            $names_check = md5($names);
            // check last compression have same checksum
            if(!$checksum = Safe::filemtime('included/browser/js_endpage/'.$names_check.'.auto.sum')) {
                // file does not exist, so redo
                return true;
            }
        }
        
        // check /shared/yacs.js
        $yacs_js        = Safe::filemtime($context['path_to_root'].'shared/yacs.js');
        if( $stamp_endpage > $yacs_js) return true;
        
        // ready to run in prod
        return false;
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
		    $path = 'temporary/library_'.$folder.'.min.js';
		    if(file_exists($context['path_to_root'].$path)) {

                            Js_css::set_revision($context['path_to_root'].$path, $path);

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

                        Js_css::set_revision($context['path_to_root'].$path.$js, $js);

                        $html .= Js_Css::build_js_declaration($context['url_to_master'].$context['url_to_root'].$path.$js);
		    }
	    }

	    if($other_files) {
		    if(gettype($other_files)=='string')
		    $other_files = (array) $other_files;

		    foreach ($other_files as $file) {
                        if(file_exists($context['path_to_root'].$file)) {

                            Js_css::set_revision($context['path_to_root'].$file, $file);

                            $html .= Js_Css::build_js_declaration($context['url_to_master'].$context['url_to_root'].$file);
                        }
		    }

	    }

	    return $html;
    }

    /** 
     * get last revision date
     * this will be added to file url to force
     * cache updating of surfer's browser
     * 
     * The function modify the filename according to url_rewrite configuration
     * - no rewriting   : include version as a query string at end of filename
     * - with rewriting : include version as digit just before extension
     * 
     * 
     * @param string $path the complete path to file on HDD
     * @param string $file the filename, including relative path from web root
     * 
     */
    
    private static function set_revision($path, &$file) {
            global $context;

            $udp_stamp = date('ymdHi',filemtime($path));
            
            // use url_rewrite only if activated and js file not related to tiny_mce
            if($context['with_friendly_urls'] == 'R' && !preg_match('/tiny_mce/',$path)) {
                
                $file = preg_replace('/\.(js|css)$/', '.'.$udp_stamp.'.$1', $file);
            
                
            } else {
                
                $file .= '?'.$udp_stamp;
                
            }
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
     * accept also .less files, and compile them in .css
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
            
            // this is a SCSS file, we may have to compile it
            if($ext === 'scss') {
                
                // compiled scss will be always minified
                $min = '.min';
                
                // ext, path and output filname
                $ext    = 'css';
                $path   = 'temporary/'.$path_parts['filename'].$min.'.'.$ext;
                $output = Safe::realpath($path);
                
                // check compilation
                if (!js_css::check_compile($realpath, $output)) {
                    
                    return Js_Css::link_exit(false, $path, $straitnow);
                }
                

	    // js or css file
            // and we are in production mode
	    // and file not already minified
            } elseif ( $context['with_debug']=='N'
		&& !preg_match('/\.min\.?/', $path_parts['filename'])) {

		// minified version path
                $min_v      = 'temporary/'.$path_parts['filename'].'.min.'.$path_parts['extension'];
                $real_min_v = Safe::realpath($min_v);
                
                $date_src = $date_min = filemtime($realpath);
                if(file_exists($real_min_v)) {
                    $date_min = filemtime($real_min_v);
                }
                
                // compare minified file last update to original src file
		if($date_src < $date_min) {
                    $path = $min_v;
                } elseif($ext === 'css') {
                    // css files are minified on demand by tools/build_css.php
                    ;
                } else {
                    // need minification for next time, but launch it as a background process
                    Defer::queue('tools/minifier.php?script='.Safe::realpath($path));
                }

	    }

            // get last revision date
            Js_css::set_revision(Safe::realpath($path), $path);

	    // add root url
            if(isset($context['static_subdom']) && $context['static_subdom'] )
                $path = $context['static_subdom'].$path;
            else
                $path = $context['url_to_master'].$context['url_to_root'].$path;
	} 

	// css or js ?
	switch($ext) {

	    case 'css' :
                $tag = Js_css::build_css_declaration($path);
                
                if($straitnow) return $tag;
                  
		Js_css::add_css($tag);
		break;
	    case 'js' :
                
                $tag = Js_css::build_js_declaration($path);
                
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
	if($context['with_debug']=='Y'&& !defined('NO_MODEL_PRELOAD') && $context->has('server_on')) {
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
     * if a path is provided, the extention is determined automatically
     * 
     * @param string $path local to the file to minify or plain text
     * @param string &$script if we want to concat the result to
     * @param string $ext, 'js' or 'css' to provide the script type if plain text is given
     */
    public static function minify($path, &$script=null, $ext=null) {
        global $context;
        
        
        if(!$ext) {
            // a path to a file should have been provided
        
            // get file content
            // $to_minify = safe::file_get_contents($path);
            $check = Safe::filesize($path);

            // sanity check
            if(!$check) {
                logger::remember('shared/js_css.php', 'cannot get file to minify : '.$path);
                return false;
            }
            
            // gather info on file
            $path_parts = pathinfo($path);
            $ext        = isset($path_parts['extension'])?$path_parts['extension']:null;
        
        } 
            
        // pass plain text or path to file    
        $to_minify = $path;
        
        
        switch ($ext) {
            case 'css':
                
                // load css minifier
                $minifier = js_css::prepare_minifier('css');

                break;
            case 'js':
                
                // load JS minifier
                $minifier = js_css::prepare_minifier('js');

                break;
            default:
                $minifier = null;
                logger::remember('shared/js_css.php', 'extension not known');
                break;
        }
        
        if($minifier) {
            
            // provide content to minifier
            $minifier->add($to_minify);
            
            // output mode choice
            if($script !== null) {
                
                // append to variable
                $script .= ";\n".$minifier->minify();
            } else {
                
                // build the path
                $min_path = $context['path_to_root'].'temporary/'.$path_parts['filename'].'.min.'.$path_parts['extension'];

                // delete previous one
                Safe::unlink($min_path);
                
                // write to file
                $minifier->minify($min_path);
            }
            return true;
               
        }
        
        return false;
    }
    
    /**
     * Return a instance of Matthias Mullie css/js Minifier
     * @see /included/minifier
     * 
     * @global array $context
     * @param string $sort, 'js' or 'css'
     * @return \MatthiasMullie\Minify\Minify
     */
    private static function prepare_minifier($sort='css') {
        global $context;
        
        $minifier = null;
        
        // load minifier common files
        include_once $context['path_to_root'].'included/minifier/src/ConverterInterface.php';
        include_once $context['path_to_root'].'included/minifier/src/Converter.php';
        include_once $context['path_to_root'].'included/minifier/src/Minify.php';
        
        Switch($sort) {
            
            case 'css':
                include_once $context['path_to_root'].'included/minifier/src/CSS.php';
                $minifier = new MatthiasMullie\Minify\CSS();
                
                break;
            case 'js' :
                include_once $context['path_to_root'].'included/minifier/src/JS.php';
                $minifier = new MatthiasMullie\Minify\JS();
                
                break;
        }
        
        return $minifier;
    }
    
    /**
     * Common preparation of scss compiler
     * used in link_file() and call_skin_css()
     * 
     * @global type $context
     * @return object the scss compiler;
     */
     private static function prepare_scss_compiler() {
        global $context;
        
        // include lessphp lib
        include_once $context['path_to_root'].'included/scss/scss.inc.php';
        $scss = new \ScssPhp\ScssPhp\Compiler;
        
        // specific function to provide absolute path of ressources within sheet
        // this will provide root url
        // TODO : improve using static domain if any
        $scss->registerFunction('rPath', function($arg) use($context) {
                return $context['url_to_master'].$context['url_to_root'];
        });
        
        // define namespaces
        $scss->setVariables(array(
            "y-prefix" => YACSS_PREFIX,
        ));
        
        // return prepared compiler
        return $scss;
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