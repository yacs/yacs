<?php
/**
 * help to build final web pages
 *
 * This is a library of function to be used from within skin templates.
 *
 * This template implements following access keys at all pages:
 * - hit 1 to jump to the front page of the site
 * - hit 2 to skip the header and jump to the main area of the page
 * - 9 to go to the control panel
 * - 0 to go to the help page
 *
 * @author Bernard Paques
 * @author Rod
 * @author Alexis Raimbault
 * @tester Agnes
 * @tester Thierry Pinelli
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

 Class Page {

	/**
	 * start page body
	 *
	 * This function generates a &lt;body&gt; tag with id and class.
	 * The id is the skin variant, as found in [code]$context['skin_variant'][/code].
	 * The class is set to 'extra' if [code]$context['extra'][/code] is not empty.
	 *
	 */
	public static function body($classes='') {
		global $context;

		// body id is derived from skin variant
		$id = '';
		if($context['skin_variant'])
			$id = ' id="'.$context['skin_variant'].'"';

		// we do have some extra content to render
		if($context['extra'])
			$classes .= ' extra';

		// build classes declaration
		$classes = ' class="'.$classes.'"';

		// start the body
		echo '<body'.$id.$classes.'>'."\n";

		// shortcuts for text readers
		echo '<p '.tag::_class('away').'>';

		// skip header -- access key 2
		if(is_callable(array('i18n', 's')))
			echo '<a href="#main_panel" accesskey="2">'.i18n::s('Skip to main content').'</a> ';

		// help page -- access key 0
		if(is_callable(array('i18n', 's')))
			echo '<a href="'.$context['url_to_root'].'help/" accesskey="0" rel="nofollow" >'.i18n::s('Help').'</a> ';

		// control panel -- access key 9
		if(is_callable(array('i18n', 's')))
			echo '<a href="'.$context['url_to_root'].'control/" accesskey="9" rel="nofollow" >'.i18n::s('Control Panel').'</a> ';

		// end of shortcuts
		echo '</p>'."\n";

	}

	/**
	 * show bread crumbs
	 *
	 * Show the content of [code]$content['path_bar'][/code] as a list of links.
	 *
	 * The length of the list depends of the value of the first parameter:
	 * - 0 - prefix the list with a link to the front page
	 * - 1 - use the list as it is
	 * - 2 - remove first level of the list
	 * - n - remove n-1 levels of the list
	 *
	 * @param int index of the first link to display from list
	 * @param boolean TRUE to display the site slogan when at top level, FALSE otherwise
	 * @return a string to be send to the browser
	 */
	public static function bread_crumbs($start_level=1, $with_slogan=FALSE) {
		global $context;

		// add a link to the front page
		if(!$start_level && count($context['path_bar']) && is_callable(array('i18n', 's'))) {
			$context['path_bar'] = array_merge(array($context['url_to_root'] => i18n::s('Home')), $context['path_bar']);
		}

		// remove top levels, if required to do so
		if(count($context['path_bar'])) {
			while($start_level-- > 1)
				array_shift($context['path_bar']);
		}

		// actually render bread crumbs
		if(count($context['path_bar']))
				echo Skin::build_list($context['path_bar'], 'crumbs')."\n";

		// no bread crumbs
		elseif($with_slogan) {

			// display site slogan instead
			if(isset($context['site_slogan']))
				echo '<p id="crumbs">'.$context['site_slogan'].'</p>'."\n";

			// fix the layout
			else
				echo '<p id="crumbs">&nbsp;</p>';

		// fix the layout
		} else
			echo '<p id="crumbs">&nbsp;</p>';

	}

	/**
	 * echo one skin component
	 *
	 * This function looks for a function into the skin library, then for a function in this script,
	 * then it checks $context['components'].
	 *
	 * For example, for a component named 'foo', it checks successively:
	 * - if Skin has a static member function named 'echo_foo'
	 * - if Page has a static member function named 'echo_foo'
	 * - if a variable $context['components']['foo'] has been set
	 * - if an article named 'foo' exists
	 *
	 * @param string name of the component to output
	 * @return boolean TRUE if the component has been echoed, FALSE otherwise
	 *
	 * @see skins/configure.php
	 */
	public static function component($name, $variant='navigation') {
		global $context;

		// sanity check
		$name = trim($name);

		// for component 'foo' we are looking for member function 'echo_foo'  from the skin
		$from_skin = array('Skin', 'echo_'.$name);
		if(is_callable($from_skin)) {
			call_user_func($from_skin);
			return TRUE;
		}

		// for component 'foo' we are looking for member function 'echo_foo' in skins/page.php
		$from_skin = array('Page', 'echo_'.$name);
		if(is_callable($from_skin)) {
			call_user_func($from_skin);
			return TRUE;
		}

		// for component 'foo', we look for data in $context['components']['foo']
		if(isset($context['components'][ $name ])) {
			echo $context['components'][ $name ];
			return TRUE;
		}

		// we have no database back-end
		if(defined('NO_MODEL_PRELOAD'))
			return FALSE;

		// look a named page, but only during regular operation
		if(file_exists($context['path_to_root'].'parameters/switch.on') && is_callable(array('Articles', 'get')) && is_callable(array('Codes', 'beautify'))) {
			if($item = Articles::get($name)) {
				echo Skin::build_box(Codes::beautify_title($item['title']), Codes::beautify($item['description']), $variant, 'component_'.$name);
				return TRUE;
			}
		}

		// this component is unknown
		return FALSE;
	}
	
	/**
	 * link a javascript file in the bottom of the page
	 * 
	 * @param string $path 
	 */
	public static function defer_script($path,$now=false) {
	    
	    $job = Js_css::link_file($path, 'js','defer',$now);
            if($now) echo $job;
	}

	/**
	 * send the main content of the page
	 *
	 * If no parameter is provided, then yacs looks at the global parameter
	 * $context['skins_main_components'] which is set into the configuration panel
	 * for the page factory.
	 *
	 * You can override this behavior and pass a hard-coded list of elements to include, with following tokens:
	 * - 'bar' - page menu bar
	 * - 'details' - page details, if any
	 * - 'error' - the error block, if any
	 * - 'image' - page image, if any
	 * - 'tags' - page tags, if any
	 * - 'text' - main content area
	 * - 'title' - page title
	 * - any value from $context array
	 *
	 * For example, call Page::content('title special tags error icon text details menu') to
	 * embed $context['special'] string below page title, and to display tags towards top of page.
	 *
	 * The parameter can also have a boolean value, which is equivalent to the following:
	 * - TRUE = 'title error icon text tags details menu'
	 * - FALSE = 'title error icon text tags details'
	 *
	 * @param mixed a string of tokens, or a boolean
	 */
	public static function content($names=NULL, $wrap = true) {
		global $context;

		// display the prefix, if any
		if(isset($context['prefix']) && $context['prefix'])
			echo $context['prefix']."\n";

		// turn a boolean to a string
		if(is_bool($names)) {
			if($names)
				$names = 'title error text tags details bar';
			else
				$names = 'title error text tags details';

		// ensure we have something
		} elseif(!$names) {
			if(isset($context['skins_main_components']))
				$names = $context['skins_main_components'];
			else
				$names = 'title error text tags details bar';
		}

		// a list of components
		if(is_string($names))
			$names = explode(' ', $names);

		if (isset( $context['content_wrap'] ) && $wrap ) {
			if (isset($context['content_wrap_attributes']))
			echo '<'.$context['content_wrap'].' '.$context['content_wrap_attributes'].'>';
			else
			echo '<'.$context['content_wrap'].'>';
		}

		// actual component generation
		foreach($names as $name)
			Page::component($name);

		if (isset( $context['content_wrap'] ) && $wrap)
			echo '</'.$context['content_wrap'].'>';

		// display the suffix, if any
		if(isset($context['suffix']) && $context['suffix'])
			echo $context['suffix']."\n";

		// debug output, if any
		if(is_array($context['debug']) && count($context['debug']))
			echo "\n".'<ul id="debug">'."\n".'<li>'.implode('</li>'."\n".'<li>', $context['debug']).'</li>'."\n".'</ul>'."\n";

	}

	/**
	 * echo the menu bar
	 *
	 * You can override this function into your skin
	 */
	public static function echo_bar() {
		global $context;

		// commands are listed into $context
		if(isset($context['page_menu']) && (@count($context['page_menu']) > 0))
			echo Skin::build_list($context['page_menu'], 'page_menu');

	}

	/**
	 * echo page details
	 *
	 * You can override this function into your skin
	 */
	public static function echo_details() {
		global $context;

		// from $context
		if(isset($context['page_details']) && $context['page_details'])
			echo '<div id="page_details">'.$context['page_details']."</div>\n";
	}

	/**
	 * echo the extra panel in a 2-column layout
	 *
	 * You can override this function into your skin
	 */
	public static function echo_extra() {
		global $context;

		// we don't want to create a full extra division
		$names = explode(' ', $context['skins_extra_components']);

		// populate the extra panel
		foreach($names as $name)
			Page::component($name, 'extra');

		// display complementary information, if any
		if($context['extra'])
			echo $context['extra'];

	}

	/**
	 * echo error messages
	 *
	 * You can override this function into your skin
	 */
	public static function echo_error() {
		global $context;

		// delegate this to the skin
		if(is_callable(array('Skin', 'build_error_block')))
			echo Skin::build_error_block();
	}

	/**
	 * echo page main image
	 *
	 * You can override this function into your skin
	 */
	public static function echo_image() {
		global $context;

		// the URL to use is in $context
		if($context['page_image']) {

			// additional styles
			$more_styles = '';
			if(isset($context['classes_for_icon_images']) && $context['classes_for_icon_images'])
				$more_styles = ' '.encode_field($context['classes_for_icon_images']);

			echo ICON_PREFIX.'<img src="'.$context['page_image'].'" style="margin: 0 0 2em 0;" class="icon'.$more_styles.'" alt="" />'.ICON_SUFFIX;

		}
	}
        
        /**
         * echo switcher to other language version for the page, if any
         * as a country flag for each language.
         * 
         * @param array $displayed, ['en, 'fr' ...] containing entries you wish to propose to switch language even if current page does not exist with those version
         * @param boolean $legend to add a text next to the flag ('In English, En Français ...)
         */
        
        public static function echo_local_switcher($displayed=null, $legend=false) {
            global $context;
            
            $switch             = '';       // html rendering
            $page_languages     = array();  // to memorize detected language, completed with $displayed
            
            if(is_array($context['hreflang']) && count($context['hreflang'])) {
                
                // get current id, to exclude it
                list($cur_type,$cur_id) = explode(":", $context['current_item']);
                
                foreach($context['hreflang'] as $page) {
                    
                        // do not include current page
                        if($cur_id == $page['id']) continue;
                    
                        $lang               = $page['lang'];
                        $text               = ($legend)?'&nbsp;'.i18n::s(sprintf('to_local_%s',$lang)):'';
                        $switch            .= '<a href="'.$page['url'].'">'.Codes::beautify('['.$lang.']').$text.'</a>'."\n";
                        // memorize as proposed language
                        $page_languages[]   = $lang;
                    
                }
             
            }
            
            if(is_array($displayed) && count($displayed)) {
                // language that are not already proposed
                $to_add = array_diff($displayed, $page_languages); 
                foreach ($to_add as $lang) {
                    
                    $text               = ($legend)?'&nbsp;'.i18n::s(sprintf('to_local_%s',$lang)):'';
                    $switch            .= '<a href="'.http::add_url_param($_SERVER['REQUEST_URI'], "lang", $lang).'">'.Codes::beautify('['.$lang.']').$text.'</a>'."\n";
                }
            }
            
            // wrap
            if($switch) {
                $tag = (SKIN_HTML5)?'aside':'div';
                $switch = tag::_($tag, tag::_class('local-switcher'),$switch);
            }
            
            
            echo $switch;
        }

	/**
	 * echo the site menu
	 *
	 * You can override this function into your skin
	 */
	public static function echo_menu() {
		global $context;

		// ensure normal conditions
		if(file_exists($context['path_to_root'].'parameters/switch.on') && is_callable(array('Articles', 'get')) && is_callable(array('Codes', 'beautify'))) {

			// use content of a named global page
			if($item = Articles::get('menu'))
				echo Skin::build_box(Codes::beautify_title($item['title']), Codes::beautify($item['description']), 'navigation', 'main_menu');
		}
	}

	/**
	 * echo the mini toolbox for quick access
	 *
	 * You can override this function into your skin
	 */
	public static function echo_minitools() {
		global $context;

		// tools are listed into $context
		if(isset($context['page_minitools']) && (@count($context['page_minitools']) > 0))
			echo Skin::build_box(NULL, Skin::finalize_list($context['page_minitools'], 'menu'), 'extra', 'page_minitools');

	}

	/**
	 * echo tags
	 *
	 * You can override this function into your skin
	 */
	public static function echo_tags() {
		global $context;

		// tags are listed into $context
		if(isset($context['page_tags']) && $context['page_tags'])
                        echo tag::_('p', tag::_class('tags'), sprintf(i18n::s('Tags: %s'), $context['page_tags']));

	}

	/**
	 * echo main text
	 *
	 * You can override this function into your skin
	 */
	public static function echo_text() {
		global $context;

		// from $context
		echo $context['text'];
		$context['text'] = '';

		// display the dynamic content, if any
		if(is_callable('send_body'))
			send_body();

		// maybe some additional text has been created in send_body()
		echo $context['text'];

	}

	/**
	 * echo page title
	 *
	 * You can override this function into your skin
	 */
	public static function echo_title() {
		global $context;

		// main page title has already been given
		if(defined('without_page_title'))
			return;

		// from $context
		if(isset($context['page_title']) && $context['page_title'])
			echo Skin::build_block($context['page_title'], 'page_title');

	}

	/**
	 * echo the toolbox
	 *
	 * You can override this function into your skin
	 */
	public static function echo_tools() {
		global $context;

		// tools are listed into $context
		if(isset($context['page_tools']) && (@count($context['page_tools']) > 0))
			echo Skin::build_box(i18n::s('Tools'), Skin::finalize_list($context['page_tools'], 'newlines'), 'extra', 'page_tools');

	}

	/**
	 * echo the user menu
	 *
	 * You can override this function into your skin
	 */
	public static function echo_user() {
		global $context;

		// build menu content dynamically
		if(is_callable(array('Users', 'get_url')) && ($menu = Skin::build_user_menu('basic')) && is_callable(array('i18n', 's'))) {
			if(Surfer::is_logged()) {
				$box_title = Surfer::get_name();

/** not fully integrated yet

				if(($item = Users::get(Surfer::get_id())) && isset($item['avatar_url']) && $item['avatar_url'])
					$box_title = '<img src="'.$item['avatar_url'].'" alt=" " title="'.encode_field(Surfer::get_name()).'" class="box_title" />'.$box_title;

*/
			} else
				$box_title = i18n::s('User login');
			echo Skin::build_box($box_title, $menu, 'navigation', 'user_menu');
		}

	}

	/**
	 * echo the list of visited pages
	 *
	 * You can override this function into your skin
	 */
	public static function echo_visited() {
		global $context;

		// visited pages are listed in session space
		if(isset($_SESSION['visited']) && count($_SESSION['visited']) && is_callable(array('i18n', 's')))
			echo Skin::build_box(i18n::s('Visited'), Skin::build_list($_SESSION['visited'], 'compact'), 'extra', 'visited_pages');

	}

	/**
	 * show the extra panel of the page
	 *
	 * If no parameter is provided, the global attribute $context['skins_extra_components'] is used instead.
	 *
	 * @param string hard-coded list of components to put aside
	 * @param boolean TRUE to generate the div#extra_panel, FALSE otherwise
	 */
	public static function extra_panel($names=NULL, $in_division=TRUE) {
		global $context;

		// use regular parameters
		if(!$names)
			$names = $context['skins_extra_components'];

		// nothing to do
		if(!$names && !$context['extra'])
			return;

		// avoid deadlocks due to misconfiguration
		$names = str_replace('extra', '', $names);

		// in a separate division
		if($in_division) {
		    $tag = (SKIN_HTML5)?'aside':'div';
		    echo '<'.$tag.' id="extra_panel">';
		}

		// a list of component
		if(is_string($names))
			$names = explode(' ', $names);

		// populate the extra panel
		foreach($names as $name)
			Page::component($name, 'extra');

		// display complementary information, if any
		if($context['extra'])
			echo $context['extra'];

		// close the extra panel
		if($in_division)
			echo '</'.$tag.">\n";

	}

	/**
	 * echo the standard footer
	 *
	 * Note that this one does not echo $context['page_footer'], and you have
	 * to do it yourself.
	 *
	 * @param string footer prefix, if any
	 * @param string footer suffix, if any
	 */
	public static function footer($prefix='', $suffix='') {
		global $context;

		// the last paragraph
		echo '<p>';

		// add footer prefix
		echo $prefix;

		$details = array();

		// execution time and surfer name, for logged user only (not for indexing robots!)
		if(is_callable(array('Surfer', 'get_name')) && is_callable(array('i18n', 's'))) {
			$execution_time = round(get_micro_time() - $context['start_time'], 2);
			$details[] = sprintf(i18n::s('page prepared in %.2f seconds for %s'), $execution_time, ucwords(Surfer::get_name()));
		}

		// site copyright
		if(isset($context['site_copyright']) && $context['site_copyright'])
			$details[] = '&copy; '.$context['site_copyright']."\n";

		// a command to authenticate
		if(is_callable(array('Surfer', 'is_logged')) && !Surfer::is_logged() && is_callable(array('i18n', 's')))
			$details[] = Skin::build_link('users/login.php', i18n::s('login'), 'basic');

		// about this site
		if(is_callable(array('i18n', 's')) && is_callable(array('Articles', 'get_url')))
			$details[] = Skin::build_link(Articles::get_url('about'), i18n::s('about this site'), 'basic');

		// privacy statement
		if(is_callable(array('i18n', 's')) && is_callable(array('Articles', 'get_url')))
			$details[] = Skin::build_link(Articles::get_url('privacy'), i18n::s('privacy statement'), 'basic');

		// a reference to YACS
		if(is_callable(array('i18n', 's')) && ($context['host_name'] != 'www.yacs.fr'))
			$details[] = sprintf(i18n::s('powered by %s'), Skin::build_link(i18n::s('http://www.yacs.fr/'), 'Yacs', 'external'));

		// all our feeds
		if(is_callable(array('i18n', 's')))
			$details[] = Skin::build_link('feeds/', i18n::s('information channels'), 'basic');

		echo join(' -&nbsp;', $details);

		// add footer suffix
		echo $suffix;

		// end of the last paragraph
		echo '</p>'."\n";
	}

	/**
	 * generate content of the &lt;head&gt; tag
	 */
	public static function meta() {
		global $context;

		// other head directives
		echo $context['page_header'];

		// display the dynamic header, if any
		if(is_callable('send_meta'))
			send_meta();

	}
        
        public static function meta_hreflang() {
            global $context;
            
            $meta_hreflang          = array();
            $context['hreflang']    = $meta_hreflang;
            $sisters                = array();
            
            //// detect other language for articles or sections
            if($context['current_item'] && strpos($context['current_item'],'article') === FALSE && strpos($context['current_item'],'section') === FALSE )
                return $meta_hreflang;
            
            // get the entity
            if($anchor = Anchors::get($context['current_item'])) {
                
                // if anchor as a little name
                if($nick = $anchor->get_nick_name()){
                        $class = $anchor->get_static_group_class();
                        $sisters = $class::list_for_name($nick,null,'raw');
                }
            }
            
            if(count($sisters) >= 2) {
                foreach($sisters as $page) {
                    if($page['language'] && $page['language'] != 'none') {
                        
                        $url                    = http::add_url_param($class::get_permalink($page), "lang", $page['language']);
                        $meta_hreflang[]        = '<link rel="alternate" hreflang="'.$page['language'].'" href="'.$url.'" />';
                        // memorize this for page::echo_local_switcher()
                        $context['hreflang'][]  = array('lang' => $page['language'], 'url' => $url, 'id' => $page['id']);
                    }
                }

             
            }
            
            return $meta_hreflang;
        }

	/**
	 * build a header panel with background
	 *
	 * This function builds a nice header panel that may include following elements:
	 * - a background image (that can be selected randomly in a set)
	 * - site name
	 * - site slogan
	 * - top-level tabs
	 *
	 * This function can prove handy to change background images randomly.
	 * To put this in place add a couple of images to the sub-directory #"images## of the current skin.
	 * Then list file in an array and pass this as first parameter of this function.
	 * For example:
	 * [php]
	 * // three images to alternate, all placed in sub-directory images
	 * $images = array('1.jpg', '2.jpg', '3.jpg');
	 *
	 * // draw the header panel
	 * Page::header_panel($images);
	 * [/php]
	 *
	 * When tabs are activated, which is the default behavior, it is useless to call Page::tabs() separately.
	 *
	 * @param mixed either an image, or an array of images
	 * @param string image attributes
	 * @param boolean TRUE to display site name, FALSE otherwise
	 * @param boolean TRUE to display site slogan, FALSE otherwise
	 * @param boolean TRUE to display tabs, FALSE otherwise
	 */
	public static function header_panel($images=NULL, $attributes='top left repeat-x', $with_name=TRUE, $with_slogan=TRUE, $with_tabs=TRUE) {
		global $context;

		// change rendering according to skin
		$tag = (SKIN_HTML5)?'header':'div';

		// put an image in panel background
		if($images) {

			// select a random image
			if(is_array($images))
				$image = $images[ array_rand($images) ];

			// a fixed image
			else
				$image = $images;

			// get a random index in table
			$index = array_rand($images);

			// the header panel comes before everything
			echo '<'.$tag.' id="header_panel" style="background: transparent url('.$context['url_to_root'].$context['skin'].'/images/'.$images[$index].') '.$attributes.';">'."\n";

		// no image in the background
		} else
			echo '<'.$tag.' id="header_panel">'."\n";

		// the site name -- can be replaced, through CSS, by an image -- access key 1
		if($context['site_name'] && $with_name)
			echo '<p id="header_title"><a href="'.$context['url_to_root'].'" title="'.encode_field(i18n::s('Front page')).'" accesskey="1"><span>'.$context['site_name'].'</span></a></p>'."\n";

		// site slogan -- can be replaced, through CSS, by an image
		if(isset($context['site_slogan']) && $with_slogan)
			echo '<p id="header_slogan"><span>'.$context['site_slogan']."</span></p>\n";

		// horizontal tabs
		if($with_tabs)
			Page::tabs();

		// end of the header panel
		echo '</'.$tag.'>'."\n";

	}
	
	/**
	 * insert javascript to the end of the page
	 * 
	 * @param string $js_script the javascript code without html tag around
	 */
	public static function insert_script($js_script) {
	    
	    Js_css::insert($js_script);
	}
	
	/**
	 * insert css rules into the header of the page
	 * 
	 * @param string $css_style without html tag around
	 */
	public static function insert_style($css_style) {
	    
	    Js_css::insert($css_style,'css');
	}
	
	/**
	 * link a javascript file in the header of the page
	 * 
	 * @param string $path 
	 */
	public static function load_script($path,$now=false) {
	    
	    $job = Js_css::link_file($path,'js','header',$now);
            if($now) echo $job;
	}
	
	/**
	 * link a cascading style sheet in the header of the page
	 * 
	 * @param string $path 
	 */
	public static function load_style($path,$now=false) {
	    
            $job = js_css::link_file($path,'','',$now);
            if($now) echo $job;
	}

	/**
	 * show the side panel of the page
	 *
	 * If no parameter is provided, the global attribute $context['skins_navigation_components'] is used instead.
	 *
	 * @param string hard-coded list of components to put aside
	 */
	public static function side($names=NULL) {
		global $context;

		// we have no database back-end
		if(!is_callable(array('sql', 'query')))
			return;

		// use regular parameters
		if(!$names)
			$names = $context['skins_navigation_components'];

		// a list of components
		if(is_string($names))
			$names = explode(' ', $names);

		// populate the extra panel
		foreach($names as $name)
			Page::component($name, 'navigation');

		// append other items to the navigation panel
		if($context['navigation'])
			echo $context['navigation']."\n";

	}

	/**
	 * show site tabs
	 *
	 * Tabs are derived by top-level sections of the server.
	 *
	 * Prefix and suffix tabs can be provided as links packaged in arrays of ( $url => array($label_prefix, $label, $label_suffix, $link_class) )
	 *
	 * @param boolean TRUE to add a tab to the front page, FALSE otherwise
	 * @param boolean TRUE to reverse order of tabs, FALSE otherwise
	 * @param array of links to be used as tabs before the regular set
	 * @param array of links to be used as tabs after the regular set
	 * @param string layout name to use for listing sub-sections (horizontal drop down menu)
	 */
	public static function tabs($with_home=TRUE, $with_reverse=FALSE, $prefix=NULL, $suffix=NULL, $layout_subsections=NULL) {
		global $context;

		// only for live servers Or Associate
		if(!file_exists($context['path_to_root'].'parameters/switch.on') && !Surfer::is_associate())
			return;

		// we have no database back-end
		if(!is_callable(array('sql', 'query')))
			return;

		// limit listing for drop-down menu
		if (!defined('TABS_DROP_LIST_SIZE'))
			define('TABS_DROP_LIST_SIZE',5);

		// cache this across requests
		$cache_id = 'skins/page.php#tabs';
		if(!$text = Cache::get($cache_id)) {

			// an array of tabs
			$site_bar = array();

			// prefix tabs, if any
			if(is_array($prefix) && count($prefix))
				$site_bar = array_merge($site_bar, $prefix);

			// the first tab links to the front page
			if($with_home && is_callable(array('i18n', 's')))
				$site_bar = array_merge($site_bar, array($context['url_to_root'] => array('', i18n::s('Home'), '', 'home')));

			// default number of sections to list
			if(!isset($context['root_sections_count_at_home']) || ($context['root_sections_count_at_home'] < 1))
				$context['root_sections_count_at_home'] = 5;

			// query the database to get dynamic tabs
			if((file_exists($context['path_to_root'].'parameters/switch.on')) && $items = Sections::list_by_title_for_anchor(NULL, 0, $context['root_sections_count_at_home'], 'main_tabs'))
				if(count($items)) {
					//query subsections if layout is provided
					if($layout_subsections) {
						//Parse mother-sections previously selected to get sub-sections
						foreach($items as $url => $item) {
							//get id of mother section
							$mother_id = str_replace('_',':',$item[3]);

							//get subsections list
							$subsections = '';
							$subsections =  Sections::list_by_title_for_anchor($mother_id, 0, TABS_DROP_LIST_SIZE, $layout_subsections);

							//transform list into string if necessary (depend layout output)
							if(is_array($subsections))
								$subsections = Skin::build_list($subsections, $layout_subsections);

							//get real number of subsections
							$nb_subsections = Sections::count_for_anchor($mother_id);
							
                                                        $hint = '';
							if($nb_subsections > TABS_DROP_LIST_SIZE) {
                                                                //hint unlisted subsections if any
                                                                $hint = '<p '.tag::_class('details').'>';
								$hint .= '( ';
								$hint .= sprintf(i18n::ns('%d section', '%d sections', $nb_subsections), $nb_subsections);
								$hint .= ' )</p>';

							}	
								
							$subsections = $hint.$subsections;

							//store sub-sections list into "suffix" of this tab's label
							if($subsections)
								$items[$url][2] = "\n".tag::_('div', tag::_class('dropmenu'), $subsections);

						}
					}
					$site_bar = array_merge($site_bar, $items);
				}

			// suffix tabs, if any
			if(is_array($suffix) && count($suffix))
				$site_bar = array_merge($site_bar, $suffix);

			// the skin will reverse the order
			if($with_reverse)
				$site_bar = array_reverse($site_bar);

			// shape tabs
			$text = Skin::build_list($site_bar, 'tabs')."\n";

			// cache result
			Cache::put($cache_id, $text, 'sections');
		}
		echo $text;

	}
        
        public static function tab_custom($content, $id='', $url = '_', $icon= '', $title = '') {
            
            $param = array($url => array(null, $content, null, $id, $icon, $title));
            
            return $param;
        }

	/**
	 * identify top level focus tab
	 *
	 * @param string prefix that applies
	 * @return string for example: 'tab_home', or 'tab_section_123', or NULL
	 */
	public static function top_focus($prefix='tab_') {
		global $context;

		// not sure there is a focus
		$output = NULL;

		// we are at the topmost page
		if(($context['script_url'] == '/index.php') && ($context['url_to_root'] != '/'))
			$output = 'slash';

		// focus on home tab
		elseif($context['skin_variant'] == 'home')
			$output = 'home';

		// else get top level
		elseif(isset($context['current_focus']) && count($context['current_focus']))
			$output = str_replace(':', '_', $context['current_focus'][0]);
                
                elseif(isset($context['current_item']))
                        $output = str_replace(':', '_', $context['current_item']);
                
                elseif(isset($context['script_url'])) {
                        $script_name = substr($context['script_url'], strrpos($context['script_url'],'/')+1);
                        $output      = str_replace('.', '_', $script_name);
                    
                }

		// prepend the prefix
		if($output && $prefix)
			$output = $prefix.$output;

		// done
		return $output;

	}

}
?>
