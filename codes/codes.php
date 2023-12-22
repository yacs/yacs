<?php
/**
 * Transform some text containing UBB-like code sequences.
 *
 * @todo CDATA for proxy http://javascript.about.com/library/blxhtml.htm
 * @todo &#91;files] - most recent files, in a compact list
 * @todo &#91;files=section:&lt;id>] - files attached in the given section
 * @todo &#91;links] - most recent links, in a compact list
 * @todo &#91;links=section:&lt;id>] - links attached in the given section
 * @todo for [read, add hits aside
 * @todo add a code to link images with clickable maps
 * @todo replace marquee with our own customizable scroller
 * @todo WiKi rendering for lists
 *
 * This module uses the Skin class for the actual rendering.
 *
 *
 * This script attempts to fight bbCode code injections by filtering strings to be used
 * as [code]src[/code] or as [code]href[/code] attributes (Thank you Mordread).
 *
 * @author Bernard Paques
 * @author Mordread Wallas
 * @author GnapZ
 * @author Alain Lesage (Lasares)
 * @author Alexis Raimbault
 * @tester Viviane Zaniroli
 * @tester Agnes
 * @tester Pat
 * @tester Guillaume Perez
 * @tester Fw_crocodile
 * @tester Christian Piercot
 * @tester Christian Loubechine
 * @tester Daniel Dupuis
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// flag that we are in the process of rendering yacs code.
global $code_rendering;
$code_rendering = false;

Class Codes {

	/**
	 * beautify some text for final rendering
	 *
	 * This function is used to transform some text before sending it back to the browser.
	 * It actually performs following analysis:
	 * - implicit formatting
	 * - formatting codes
	 * - smileys
	 *
	 * If the keyword [escape][formatted][/escape] appears at the first line of text,
	 * or if options have the keyword ##formatted##, no implicit formatting is performed.
	 *
	 * If the keyword [escape][hardcoded][/escape] appears at the first line of text,
	 * or if options have the keyword ##hardcoded##, the only transformation is new lines to breaks.
	 *
	 * If options feature the keyword ##compact##, then YACS codes that may
	 * generate big objects are removed, such as [escape][table]...[/table][/escape]
	 * and [escape][location][/escape].
	 *
	 * @param string the text to beautify
	 * @param string the set of options that apply to this text
	 * @return the beautified text
	 *
	 * @see articles/view.php
	 */
	public static function beautify($text, $options='') {
		global $context;

		// save CPU cycles
		$text = trim($text);
		if(!$text)
			return $text;
                
                // sanity check
                if(is_null($options)) $options = '';

		//
		// looking for compact content
		//
		if(preg_match('/\bcompact\b/i', $options))
			$text = preg_replace(array('/\[table.+?\/table\]/', '/\[location.+?\]/'), '', $text);

		//
		// implicit formatting
		//

		// new lines will have to be checked
		$new_lines = 'proceed';

		// text is already formatted
		if(!strncmp($text, '[formatted]', 11)) {
			$new_lines = 'none';
			$text = substr($text, 11);

		// text is already formatted (through options)
		} elseif(preg_match('/\bformatted\b/i', $options))
			$new_lines = 'none';

		// newlines are hard coded
		elseif(!strncmp($text, '[hardcoded]', 11)) {
			$new_lines = 'hardcoded';
			$text = substr($text, 11);

		// newlines are hard coded (through options)
		} elseif(preg_match('/\bhardcoded\b/i', $options))
			$new_lines = 'hardcoded';

		// render codes
		$text = Codes::render($text);

		// render smileys after codes, else it will break escaped strings
		if(is_callable(array('Smileys', 'render_smileys')))
			$text = Smileys::render_smileys($text);

		// relocate images
		$text = str_replace('"skins/', '"'.$context['path_to_root'].'skins/', $text);

		//
		// adjust end of lines
		//

		// newlines are hard coded
		if($new_lines == 'hardcoded')
			$text = nl2br($text);

		// implicit formatting
		elseif($new_lines == 'proceed') {
                        
                        $text = preg_replace(array("|<br\s*/>\n+|i", "|\n\n+|i"), array( BR, BR.BR ), $text );
                        
                }

		return $text;
	}

	/**
	 * beautify some text in the extra panel
	 *
	 * @param string the text to beautify
	 * @return the beautified text
	 *
	 * @see articles/view.php
	 */
	public static function beautify_extra($text) {
		global $context;

		// process extra and navigation boxes
                $text = preg_replace_callback('/\[box\.(navigation|extra)=([^\]]+?)\](.*?)\[\/box\]/is', function($matches) {
                    
                    return Skin::build_box(stripslashes($matches[2]), stripslashes($matches[3]), $matches[1]);
                    
                }, $text);

		// regular rendering
		$text = Codes::beautify($text);

		return $text;

	}

	/**
	 * format an introduction
	 *
	 * @param string raw introduction
	 * @return string finalized title
	 */
	public static function beautify_introduction($text) {

		// render codes
		$output = Codes::render($text);

		// render smileys after codes, else it will break escaped strings
		if(is_callable(array('Smileys', 'render_smileys')))
			$output = Smileys::render_smileys($output);

		// return by reference
		return $output;
	}
        
        /**
         * function to format meta desc. Enable the use of newline
         * and [lang=xx] formatting code.
         * @see shared/global.php
         * 
         * @param string $text
         * @return string
         */
        public static function beautify_meta_desc($text) {
            
            // streamline newlines, even if this has been done elsewhere
            $text = str_replace(array("\r\n", "\r"), "\n", $text);
            
            $patterns_map = array(
                "|<br\s*/>\n+|i"                            =>      BR,
                "|\n\n+|i"                                  =>      BR.BR,
                "/\[lang=([^\]]+?)\](.*?)\[\/lang\]/is"    =>      'Codes::render_lang'
            );
            
            $formatted = Codes::process($text, $patterns_map);
            
            return $formatted;
        }

	/**
	 * format a title
	 *
	 * New lines and images are the only things accepted in titles.
	 * The goal is to provide a faster service than beautify()
	 *
	 * @param string raw title
	 * @return string finalized title
	 */
	public static function beautify_title($text) {

		// suppress pairing codes
		$output = Codes::strip($text, FALSE);

		// the only code transformed in titles
		$output = str_replace(array('[nl]', '[NL]'), '<br />', $output);

		// remove everything, except links, breaks and images, and selected tags
		$output = strip_tags($output, '<a><abbr><acronym><b><big><br><code><del><div><dfn><em><i><img><ins><p><q><small><span><strong><sub><sup><tt><u>');

		// return by reference
		return $output;
	}

	/**
	 * determine if a code is already in some text
	 *
	 * @param string the text to check
	 * @param string code to check (e.g., 'embed')
	 * @param int the id of the object
	 * @return boolean TRUE if the code is present, false otherwise
	 */
	public static function check_embedded($text, $code, $id) {

		// we check the string of digits
		$id = strval($id);

		// parse the full string
		$count = strlen($text);
		$position = 0;

		// look for '[embed' or similar
		while(($position = strpos($text, '['.$code, $position)) !== FALSE) {
			$position += 1+strlen($code);

			// parse remaining chars
			while($position < $count) {

				// digits just follow the '=' sign
				if($text[$position] == '=') {
					$position++;

					// exact match
					if(($position + 2 + strlen($id) < $count) && !strcmp(substr($text, $position, strlen($id)), $id))
						return TRUE;

					// not in this code, look at next one
					break;

				// malformed code
				} elseif($text[$position] == ']') {
					$position++;
					break;
				}

				// next char
				$position++;
			}
		}

		// not found
		return FALSE;
	}

	/**
	 * delete a code if it is present in some text
	 *
	 * @param string the text to check
	 * @param string code to check (e.g., 'embed')
	 * @param int the id of the object
	 * @return string the resulting string
	 */
	public static function delete_embedded($text, $code, $id) {

		// we check the string of digits
		$id = strval($id);

		// parse the full string
		$count = strlen($text);
		$position = 0;

		// look for '[embed' or similar
		while(($position = strpos($text, '['.$code, $position)) !== FALSE) {

			// we have to take everything before that point
			$prefix = $position;

			// next char
			$position += 1+strlen($code);

			// parse remaining chars
			while($position < $count) {

				// digits just follow the '=' sign
				if($text[$position] == '=') {
					$position++;

					// exact match
					if(($position + strlen($id) <= $count) && !strcmp(substr($text, $position, strlen($id)), $id)) {
						$position += strlen($id);

						// look for ']'
						while($position < $count) {
							if($text[$position] == ']') {
								$position++;
								break;
							}
							$position++;
						}

						// do the deletion
						$modified = '';
						if($prefix > 0)
							$modified .= substr($text, 0, $prefix);
						if($position < $count)
							$modified .= substr($text, $position, $count-$position);
						return $modified;
					}

					// not in this code, look at next one
					break;

				// malformed code
				} elseif($text[$position] == ']') {
					$position++;
					break;
				}

				// next char
				$position++;
			}
		}

		// not found
		return $text;
	}

	/**
	 * fix line breaks
	 *
	 * This function moves unclosed tags to the beginning of content.
	 *
	 * @param string input
	 * @return string original or modified content
	 */
	public static function fix_tags($text) {

		// look for opening tag at content end
		$last_open = strrpos($text, '<p>');
		$last_close = strrpos($text, '</p');
		if($last_open && (($last_close === FALSE) || ($last_open > $last_close))) {

			// trail
			$trail = '';
			if(strlen($text) > $last_open + 3)
				$trail = substr($text, $last_open + 3);

			// move it to content start to restore pairing tags
			$text = '<p>'.substr($text, 0, $last_open).$trail;

		}

		// also fix broken img tags, if any
		$text = preg_replace('#<(img[^</]+)>#i', '<$1 />', $text);

		// remove slashes added by preg_replace -- only for double quotes
		$text = str_replace('\"', '"', $text);

		// done
		return $text;
	}

	/**
	 * reset global variables used for rendering
	 *
	 * This function should be called between the processing of different articles in a loop
	 *
	 * @param string the target URL for this rendering (e.g., 'articles/view.php/123')
	 */
	public static function initialize($main_target=NULL) {
		global $context;
                
                if(preg_match('/^https?:\/\//',$main_target))
                      ;
                else
                    $main_target = $context['url_to_root'].$main_target;

		if($main_target)
			//$context['self_url'] = $context['url_to_root'].$main_target;
                        $context['self_url'] = $main_target;

	}

	/**
	 * list all ids matching some code
	 *
	 * @param string the text to check
	 * @param string code to check (e.g., 'embed')
	 * @return array the list of matching ids
	 */
	public static function list_embedded($text, $code='embed') {

		// all ids we have found
		$ids = array();

		// parse the full string
		$count = strlen($text);
		$position = 0;

		// look for '[embed' or similar
		while(($position = strpos($text, '['.$code, $position)) !== FALSE) {
			$position += 1+strlen($code);

			// parse remaining chars
			while($position < $count) {

				// digits just follow the '=' sign
				if($text[$position] == '=') {
					$position++;

					// capture all digits
					$id = '';
					while($position < $count) {
						if(($text[$position] >= '0') && ($text[$position] <= '9')) {
							$id .= $text[$position];
							$position++;
						} else
							break;
					}

					// save this id
					if(strlen($id))
						$ids[] = $id;

					// look at next code
					break;

				// malformed code
				} elseif($text[$position] == ']') {
					$position++;
					break;
				}

				// next char
				$position++;
			}
		}

		// job done
		return $ids;
	}
        
        /**
         * Internal function to process text replacement according
         * to codes' patterns.
         * uses preg_replace_callback and do the following treatment with priority :
         * 1. try to find a function among loaded script ( could be this class, or Skin class)
         * 2. try to find a class within codes extensions (in /codes/code_*.php) to perform the rendering
         * 3. perform a regular preg_replace
         * 4. let the text as is 
         * 
         * @global array $context
         * @param string $text to transform
         * @param array $patterns_map all the patterns to check and action to do with them
         * @return string transformed text.
         */
        private static function process($text, $patterns_map) {
            global $context;
            
            // ensure we have enough time to execute
            Safe::set_time_limit(30);

            foreach($patterns_map as $pattern => $action) {
                
                // use lowercase, we may look for a file with this
                $action = strtolower($action);
                
                // use of preg_replace_callback with an anonymous function
                $text = preg_replace_callback($pattern, function($matches) use ($pattern, $action, $context) {

                    // returned text
                    $replace = '';

                    // function to call
                    $func = '';
                    // array of captured element
                    $capture    = array_slice($matches, 1);

                    // test if mapped action is a callable function (case 1)
                    if(is_callable($action)) { 
                        $func   = $action;
                    // test if map is a class
                    }elseif(Safe::filesize('codes/'.$action.'.php')) { 
                        // delegate rendering to an extension (case 2)
                        include_once $context['path_to_root'].'codes/'.$action.'.php';
                        $code = new $action();
                        $replace = $code->render($capture);
                        unset($code);
                        return $replace;
                    }

                    
                    if($func) {
                        // call of class Codes method, with or without parameters (case 1)
                        if( count($capture) ) {
                            $replace  .= call_user_func_array($func, $capture);
                        } else {
                            $replace  .= call_user_func($func);
                        }
                    } else {
                        // regular preg_replace (case 3 and 4)
                        $replace   .= preg_replace($pattern, $action, $matches[0]);
                    }

                    return $replace;

                }
                , $text);
            }
            
            
            return $text;
        }

	/**
	 * transform codes to html
	 *
	 * [php]
	 * // build the page
	 * $context['text'] .= ...
	 *
	 * // transform codes
	 * $context['text'] = Codes::render($context['text']);
	 *
	 * // final rendering
	 * render_skin();
	 * [/php]
	 *
	 * @link http://pureform.wordpress.com/2008/01/04/matching-a-word-characters-outside-of-html-tags/
	 *
	 * @param string the input string
	 * @return string the transformed string
	 */
	public static function render($text) {
		global $context, $code_rendering;
                
                // sanity check
                if(empty($text)) return '';
                
                // say we are now rendering code
                $code_rendering = true;

                // the formatting code interface
                include_once $context['path_to_root'].'codes/code.php';

		// streamline newlines, even if this has been done elsewhere
		$text = str_replace(array("\r\n", "\r"), "\n", $text);

		// prevent wysiwyg editors to bracket our own tags
		$text = preg_replace('#^<p>(\[.+\])</p>$#m', '$1', $text);

 		// initialize only once
		static $patterns_map;
               
		if(!isset($patterns_map) ) {
                    
                    if(Safe::filesize('codes/patterns.auto.php')) {
                    
                        include_once $context['path_to_root'].'codes/patterns.auto.php';
                    }else{

			// core patterns
			$patterns_map['|<!-- .* -->|i']                                             = '';                          // remove HTML comments
                        $patterns_map['|</h(\d)>\n+|i']                                             = '</h$1>'                  ;  // strip \n after title
                        $patterns_map['/\n[ \t]*(From|To|cc|bcc|Subject|Date):(\s*)/i']             = BR.'$1:$2'                ;  // common message headers
                        $patterns_map['/\[escape\](.*?)\[\/escape\]/is']                            = 'Codes::render_escaped'   ;  // [escape]...[/escape] (before everything)
                        $patterns_map['/\[php\](.*?)\[\/php\]/is']                                  = 'Codes::render_pre_php'   ;  // [php]...[/php]
                        $patterns_map['/\[snippet\](.*?)\[\/snippet\]/is']                          = 'Codes::render_pre'       ;  // [snippet]...[/snippet]
                        $patterns_map['/(\[page\].*)$/is']                                          = ''                        ;  // [page] (provide only the first one)
                        $patterns_map['/\[(associate|member|anonymous|hidden|restricted|authenticated)\](.*?)\[\/\1\]/is']  = 'Codes::render_hidden'    ;  // [associate]...[/associate] 
                        $patterns_map['/\[redirect=([^\]]+?)\]/is']                                 = 'Codes::render_redirect'  ;  // [redirect=<link>]
                        $patterns_map['/\[execute=([^\]]+?)\]/is']                                  = 'Codes::render_execute'   ;  // [execute=<name>]
                        $patterns_map['/\[parameter=([^\]]+?)\]/is']                                = 'Codes::render_parameter' ;  // [parameter=<name>]
                        $patterns_map['/\[lang=([^\]]+?)\](.*?)\[\/lang\]/is']                      = 'Codes::render_lang'      ;  // [lang=xy]...[/lang]
                        $patterns_map['/\[(images)=([^\]]+?)\]/i']                                  = 'Codes::render_object'    ;  // [images=<ids>] (before other links)
                        $patterns_map['/\[(image)=([^\]]+?)\]/i']                                   = 'Codes::render_object'    ;  // [image=<id>]
                        $patterns_map['/##(\S.*?\S)##/is']                                          = '<code>$1</code>'         ;  // ##...##
                        $patterns_map['/\[code\](.*?)\[\/code\]/is']                                = '<code>$1</code>'         ;  // [code]...[/code]
                        $patterns_map['/\[indent\](.*?)\[\/(indent)\]/is']                          = 'Skin::build_block'       ;  // [indent]...[indent]
                        $patterns_map['/\[quote\](.*?)\[\/(quote)\]/is']                            = 'Skin::build_block'       ;  // [quote]...[quote]
                        $patterns_map['/\[folded(?:=([^\]]+?))?\](.*?)\[\/(folded)\]\s*/is']        = 'Skin::build_box'         ;  // [folded=title]...[/folded],[folded]...[/folded] 
                        $patterns_map['/\[unfolded(?:=([^\]]+?))?\](.*?)\[\/(unfolded)\]\s*/is']    = 'Skin::build_box'         ;  // [unfolded=title]...[/unfolded],[unfolded]...[/unfolded]
                        $patterns_map['/\[sidebar(?:=([^\]]+?))?\](.*?)\[\/(sidebar)\]\s*/is']      = 'Skin::build_box'         ;  // [sidebar=title]...[/sidebar],[sidebar]...[/sidebar]
                        $patterns_map['/\[note\](.*?)\[\/(note)\]\s*/is']                           = 'Skin::build_block'       ;  // [note]...[/note]
                        $patterns_map['/\[caution\](.*?)\[\/(caution)\]\s*/is']                     = 'Skin::build_block'       ;  // [caution]...[/caution]
                        $patterns_map['/\[center\](.*?)\[\/(center)\]/is']                          = 'Skin::build_block'       ;  // [center]...[/center]
                        $patterns_map['/\[right\](.*?)\[\/(right)\]/is']                            = 'Skin::build_block'       ;  // [right]...[/right]
                        $patterns_map['/\[(go)=([^\]]+?)\]/i']                                      = 'Codes::render_object'    ;  // [go=<name>]
                        $patterns_map['/\[(article\.description)=([^\]]+?)\]/i']                    = 'Codes::render_object'    ;  // [article.description=<id>]
                        $patterns_map['/\[(article)=([^\]]+?)\]/i']                                 = 'Codes::render_object'    ;  // [article=<id>] or [article=<id>, title]
                        $patterns_map['/\[(next)=([^\]]+?)\]/i']                                    = 'Codes::render_object'    ;  // [next=<id>]
                        $patterns_map['/\[(previous)=([^\]]+?)\]/i']                                = 'Codes::render_object'    ;  // [previous=<id>]
                        $patterns_map['/\[random(?:=([^\]]+?))?\]/i']                               = 'Codes::render_random'    ;  // [random], [random=section:<id>] or [random=category:<id>]
                        $patterns_map['/\[(section)=([^\]]+?)\]/i']                                 = 'Codes::render_object'    ;  // [section=<id>] or [section=<id>, title]
                        $patterns_map['/\[(category(?:\.description)?)=([^\]]+?)\]\n*/is']          = 'Codes::render_object'    ;  // [category=<id>], [category=<id>, title] or [category.description=<id>]
                        $patterns_map['/\[(user)=([^\]]+?)\]/i']                                    = 'Codes::render_object'    ;  // [user=<id>]
                        $patterns_map['/\[(users)=([^\]]+?)\]/i']                                   = 'Codes::render_users'     ;  // [users=present]
                        $patterns_map['/\[(file|download)=([^\]]+?)\]/i']                           = 'Codes::render_object'    ;  // [file=<id>] or [file=<id>, title] or download=<id>] or [download=<id>, title]
                        $patterns_map['/\[(comment)=([^\]]+?)\]/i']                                 = 'Codes::render_object'    ;  // [comment=<id>] or [comment=<id>, title]
                        $patterns_map['/\[(link)(?:=([^\]]+?))?\](.*?)\[\/link\]/is']               = 'Codes::render_link'      ;  // [link]url[/link] or [link=label]url[/link]
                        $patterns_map['/\[(button)=([^\]]+?)\](.*?)\[\/button\]/is']                = 'Codes::render_link'      ;  // [button=label]url[/button]
                        $patterns_map['/\[(button)=([^\|]+?)\|([^\]]+?)]/is']                       = 'Codes::render_link'      ;  // [button=label|url]
                        $patterns_map['/\[(click)=([^\|]+?)\|([^\]]+?)]/is']                        = 'Codes::render_link'      ;  // [click=label|url]
                        $patterns_map['/(\[)([^ ][^\]\|]+?[^ ])\|([^ ][^\]]+?[^ ])\]/is']           = 'Codes::render_link'      ;  // [label|url]
                        $patterns_map['/<p>http[s]*:\/\/www\.youtube\.com\/watch\?v=([a-zA-Z0-9_\-]+)[a-zA-Z0-9_\-&=]*<\/p>/i'] = '<iframe class="youtube-player" type="text/html" width="445" height="364" src="https://www.youtube.com/embed/$1" frameborder="0"></iframe>'; // YouTube link
                        $patterns_map['/<p>http[s]*:\/\/youtu\.be\/([a-zA-Z0-9_\-]+)<\/p>/i']               = '<iframe class="youtube-player" type="text/html" width="445" height="364" src="https://www.youtube.com/embed/$1" frameborder="0"></iframe>'; // YouTube link too
                        $patterns_map['#(^|\s)([a-z]+?://[a-zA-Z0-9_\-\.\~\/@&;:=%$\?]+)#']                  = 'Codes::render_link'  ;  // make URL clickable
                        $patterns_map['#(^|\s)(www\.[a-z0-9\-]+\.[a-zA-Z0-9_\-\.\~]+(?:/[^,< \r\n\)]*)?)#i'] = 'Codes::render_link'  ;  // web server url                        
                        $patterns_map['/\[clicks=([^\]]+?)]/is']                                    = 'Codes::render_clicks'    ;  // [clicks=url]  // @TODO: put in extension
                        $patterns_map['/\[email\](.*?)\[\/email\]/is']                              = 'Codes::render_email'     ;  // [email]url[/email]
                        $patterns_map['/(\s)([a-z0-9_\-\.\~]+?@[a-z0-9_\-\.\~]+\.[a-z0-9_\-\.\~]+)/i']  = 'Codes::render_email' ;  //  mail address
                        $patterns_map['/\[published(?:\.([^\]=]+?))?(?:=([^\]]+?))?\]\n*/is']       = 'Codes::render_published' ;  // [published(.decorated)], [published=section:4029], [published.decorated=section:4029,x]
                        $patterns_map['/\[updated(?:\.([^\]=]+?))?(?:=([^\]]+?))?\]\n*/is']         = 'Codes::render_updated'   ;  // [updated(.decorated)], [updated(.decorated)=section:4029,x]
                        $patterns_map['/\[sections(?:\.([^\]=]+?))?(?:=([^\]]+?))?\]\n*/is']        = 'Codes::render_sections'  ;  // [sections(.decorated)] (site map), [sections(.decorated)=section:4029] (sub-sections), [sections.simple=self] (assigned)
                        $patterns_map['/\[categories(?:\.([^\]=]+?))?(?:=([^\]]+?))?\]\n*/is']      = 'Codes::render_categories';  // [categories(.decorated)] (category tree), [categories(.decorated)=categories:4029] (sub-categories)
                        $patterns_map['/\[wikipedia=([^\]]+?)\]/is']                                = 'Codes::render_wikipedia' ;  // [wikipedia=keyword] or [wikipedia=keyword, title]
                        $patterns_map['/\[be\]/i']                                                  = ' <img src="'.$context['url_to_root'].'skins/_reference/flags/be.gif" alt="belgian flag" /> ' ;   // [be] belgian flag
                        $patterns_map['/\[ca\]/i']                                                  = ' <img src="'.$context['url_to_root'].'skins/_reference/flags/ca.gif" alt="canadian flag" /> ' ;  // [ca] canadian flag
                        $patterns_map['/\[ch\]/i']                                                  = ' <img src="'.$context['url_to_root'].'skins/_reference/flags/ch.gif" alt="swiss flag" /> ' ;     // [ch] swiss flag
                        $patterns_map['/\[de\]/i']                                                  = ' <img src="'.$context['url_to_root'].'skins/_reference/flags/de.gif" alt="german flag" /> ' ;    // [de] german flag
                        $patterns_map['/\[en\]/i']                                                  = ' <img src="'.$context['url_to_root'].'skins/_reference/flags/gb.gif" alt="english flag" /> ' ;   // [en] english flag
                        $patterns_map['/\[es\]/i']                                                  = ' <img src="'.$context['url_to_root'].'skins/_reference/flags/es.gif" alt="spanish flag" /> ' ;   // [es] spanish flag
                        $patterns_map['/\[fr\]/i']                                                  = ' <img src="'.$context['url_to_root'].'skins/_reference/flags/fr.gif" alt="french flag" /> '  ;   // [fr] french flag
                        $patterns_map['/\[gr\]/i']                                                  = ' <img src="'.$context['url_to_root'].'skins/_reference/flags/gr.gif" alt="greek flag" /> '   ;   // [gr] greek flag
                        $patterns_map['/\[it\]/i']                                                  = ' <img src="'.$context['url_to_root'].'skins/_reference/flags/it.gif" alt="italian flag" /> ';    // [it] italian flag
                        $patterns_map['/\[pt\]/i']                                                  = ' <img src="'.$context['url_to_root'].'skins/_reference/flags/pt.gif" alt="portugal flag" /> ';   // [pt] portugal flag
                        $patterns_map['/\[us\]/i']                                                  = ' <img src="'.$context['url_to_root'].'skins/_reference/flags/us.gif" alt="us flag" /> ';         // [us] us flag
                        $patterns_map['/\[clear\]\n*/i']                                            = ' <br style="clear: both;" /> ';  // [clear]
                        $patterns_map['/\[nl\]\n*/i']                                               = BR;  // [nl] new line
                        
                        
                        
                        // load formatting codes from files
			$dir = $context['path_to_root'].'codes/';
			if ($handle = Safe::opendir($dir)) {

				while (false !== ($file = Safe::readdir($handle))) {
				  if ($file == '..')
					continue;

				  if ($file == '.')
					continue;

				  //convention :
				  //get file only begining with code_
				  if (!(substr($file,0,5)==='code_'))
					continue;
                                  
                                  // skip .bak files
                                  if (substr($file,-4)==='.bak')
                                        continue;

				  include_once($dir.$file);

				  //get formatting code patterns from this class
				  $classname = stristr($file,'.',TRUE);
				  $code = new $classname;
				  $code->get_pattern($patterns_map);
				  unset($code);
				}
				Safe::closedir($handle);
                        }
                        // cache all patterns in one unique file for next time
                        Codes::save_patterns($patterns_map);
			
                    } // end generating patterns from scratch
                    

		} // end setting $patterns

		
                $text = Codes::process($text, $patterns_map);
                
                // end of job
                $code_rendering = false;

		// done
		return $text;

	}

	/**
	 * render a list of categories
	 *
	 * The provided anchor can reference:
	 * - a section 'category:123'
	 * - a user 'user:789'
	 * - 'self'
	 * - nothing
	 *
	 * @param string the anchor (e.g. 'section:123')
	 * @param string layout to use
	 * @return string the rendered text
	**/
	public static function render_categories($layout='compact', $anchor='') {
		global $context;

		// we return some text;
		$text = '';

		// number of items to display
		$count = YAHOO_LIST_SIZE;
		if(($position = strpos($anchor, ',')) !== FALSE) {
			$count = (integer)trim(substr($anchor, $position+1));
			if(!$count)
				$count = YAHOO_LIST_SIZE;

			$anchor = trim(substr($anchor, 0, $position));
		}

		// scope is limited to current surfer
		if(($anchor == 'self') && Surfer::get_id()) {
			$anchor = 'user:'.Surfer::get_id();

			// refresh on every page load
			Cache::poison();

		}

		// scope is limited to one category
		if(strpos($anchor, 'category:') === 0)
			$text = Categories::list_by_title_for_anchor($anchor, 0, $count, $layout);

		// scope is limited to one author
		elseif(strpos($anchor, 'user:') === 0)
			$text = Members::list_categories_by_title_for_member($anchor, 0, $count, $layout);

		// consider all pages
		if(!$text)
			$text = Categories::list_by_title_for_anchor(NULL, 0, $count, $layout);

		// we have an array to format
		if(is_array($text))
			$text = Skin::build_list($text, $layout);

		// job done
		return $text;
	}

	/**
	 * list clicks
	 *
	 * @param string web address that is monitored
	 * @return string the rendered text
	**/
	public static function render_clicks($url) {
		global $context;

		$text = '';

		// sanity check
		if(!$url)
			return $text;

		// if we received only an id, assume file access
		if(preg_match('/^[0-9]+$/', $url))
			$url = 'file:'.$url;

		// the list of people who have followed this link
		if($users = Activities::list_at($url, array('click', 'fetch'), 50, 'comma')) {

			$count = Activities::count_at($url, array('click', 'fetch'));
			$text .= sprintf(i18n::ns('%d named person has followed the link: %s', '%d named persons have followed the link: %s', $count), $count, $url);

		} else
			$text .= i18n::s('No authenticated person has used this link yet');

		return $text;

	}

	/**
	 * render an email address
	 *
	 * @param string the address
	 * @param string the label
	 * @return string the rendered text
	**/
	public static function render_email($address, $text=null) {
            
                // be sure to have a address
                if((!$address && $text) || preg_match('/^\s$/', $address)) 
                    $address = encode_link($text);

		// be sure to display something
		if(!$text)
			$text = $address;

		// specify a scheme if not done yet
		if(!preg_match('/^[a-z]+:/i', $address))
			$address = 'mailto:'.$address;

		// return a complete anchor
		$output = Skin::build_link($address, $text, 'email');
		return $output;
	}

	/**
	 * escape code sequences
	 *
	 * @param string the text
	 * @return string the rendered text
	**/
	public static function render_escaped($text) {
            
                $text = Codes::fix_tags($text);

		// replace strings --initialize only once
		static $from, $to;
		if(!isset($from)) {

			// chars or strings to be escaped
			$tags = array(
				'##' => '&#35;&#35;',
				'*' => '&#42;',
				'+' => '&#43;',
				'-' => '&#45;',
				'/' => '&#47;',
				':' => '&#58;',
				'=' => '&#61;',
				'[' => '&#91;',
				']' => '&#93;',
				'_' => '&#95;',
				'<' => '&#139;' // escape HTML as well
			);

			// initialize only once
			$from = array();
			$to = array();
			foreach($tags as $needle => $replace) {
				$from[] = $needle;
				$to[] = $replace;
			}
		}

		// do the job
		$text = str_replace($from, $to, $text);

		$output = '<code>'.nl2br($text).'</code>';
		return $output;
	}

	/**
	 * include some external PHP script
	 *
	 * @param string name of the script to include
	 * @param mixed default value, if any
	 * @return text generated during the inclusion
	 */
	public static function render_execute($name) {
		global $context;

		// check path to the file
		while(TRUE) {

			// remove leading /
			if($name[0] == '/') {
				$name = substr($name, 1);
				continue;
			}

			// avoid reference to current directory
			if(!strncmp($name, './', 2)) {
				$name = substr($name, 2);
				continue;
			}

			// can't go outside this instance of yacs
			if(!strncmp($name, '../', 3)) {
				$name = substr($name, 3);
				continue;
			}

			break;
		}

		// capture the output of the next script in memory
		ob_start();

		// load this file, somewhere below the installation directory
		include $context['path_to_root'].$name;

		// retrieve all text generated by the script
		$output = ob_get_contents();
		ob_end_clean();

		// display the text where the script was included
		return $output;
	}


	/**
	 * render or not some text
	 *
	 * If variant = 'anonymous' and surfer is not logged, then display the block.
	 * If the surfer is an associate, then display the text.
	 * Else if the surfer is an authenticated member and variant = 'authenticated', then display the text
	 * Else return an empty string
	 *
	 * @param string the text
	 * @param either 'anonymous', or 'restricted' or 'hidden'
	 * @return string the rendered text
	**/
	public static function render_hidden($variant, $text ) {
            
                $text = Codes::fix_tags($text);

		// this block should only be visible from non-logged surfers
		if($variant == 'anonymous') {
			if(Surfer::is_logged())
				$text = '';
			return $text;
		}

		// associates may see everything else
		if(Surfer::is_associate())
			return $text;

		// this block is restricted to members
		if(Surfer::is_member() && ($variant == 'member' || $variant == 'restricted' || $variant == 'authenticated'))
			return $text;

		// tough luck
		$text = '';
		return $text;
	}
        
        /**
         * render [lang] formatting code
         * 
         * @param string $lang declared
         * @param string $text to filter
         * @return string
         */
        public static function render_lang($lang, $text) {
            
            $text = Codes::fix_tags($text);
            
            return i18n::filter($text, $lang);
        }
        
        /**
         * render links formatting codes
         * [link]...[link]
         * [link=label]...
         * [label|url]
         * and standalone url
         * 
         * standalone links detection won't provide label+url so 
         * their url is received in $label parameter
         * 
         * @param string $type detected of the link
         * @param string $label for the link
         * @param string $url for the link
         * @return string the formatted link
         */
        public static function render_link($type,$label,$url='') {
            
            $url = ($url)?encode_link($url):encode_link($label);
            $label = Codes::fix_tags($label);
            $whitespace = '';
            
            if(preg_match('/^\s$/', $type)) {
                $whitespace = $type;
                $type = 'standalone';
            }
            
            switch ($type) {
                case 'link':
                case '[':
                case 'standalone':
                    return $whitespace.Skin::build_link($url, $label);
                    break;
                default:
                    return $whitespace.Skin::build_link($url, $label, $type);
            }

        }

	/**
	 * render a link to an object
	 *
	 * Following types are supported:
	 * - article - link to an article page
	 * - category - link to a category page
	 * - comment - link to a comment page
	 * - download - link to a download page
	 * - file - link to a file page
	 * - flash - display a file as a native flash object, or play a flash video
	 * - go
	 * - image - display an in-line image
	 * - next - link to an article page
	 * - previous - link to an article page
	 * - section - link to a section page
	 * - server - link to a server page
	 * - user - link to a user page
	 *
	 * @param string the type
	 * @param string the id, with possible options or variant
	 * @return string the rendered text
	**/
	public static function render_object($type, $id) {
		global $context;
                
                $id = Codes::fix_tags($id);

		// depending on type
		switch($type) {

		// link to an article
		case 'article':

			// maybe an alternate title has been provided
			$attributes = preg_split("/\s*,\s*/", $id, 2);
			$id = $attributes[0];

			// load the record from the database
			if(!$item = Articles::get($id))
				$output = '[article='.$id.']';

			else {

				// ensure we have a label for this link
				if(isset($attributes[1])) {
					$text = $attributes[1];
					$type = 'basic';
				} else
					$text = Skin::strip($item['title']);

				// make a link to the target page
				$url = Articles::get_permalink($item);

				// return a complete anchor
				$output = Skin::build_link($url, $text, $type);
			}

			return $output;

		// insert article description
		case 'article.description':

			// maybe an alternate title has been provided
			$attributes = preg_split("/\s*,\s*/", $id, 2);
			$id = $attributes[0];

			// load the record from the database
			if(!$item = Articles::get($id))
				$output = '[article.description='.$id.']';

			else {

				// ensure we have a label for this link
				if(isset($attributes[1])) {
					$text = $attributes[1];
					$type = 'basic';
				} else
					$text = Skin::strip($item['title']);

				// make a link to the target page
				$url = Articles::get_permalink($item);

				// return a complete anchor
				$output = Skin::build_link($url, $text, 'article');

				// the introduction text, if any
				$output .= BR.Codes::beautify($item['introduction']);

				// load overlay, if any
				if(isset($item['overlay']) && $item['overlay']) {
					$overlay = Overlay::load($item, 'article:'.$item['id']);

					// get text related to the overlay, if any
					if(is_object($overlay))
						$output .= $overlay->get_text('view', $item);

				}

				// the description, which is the actual page body
				$output .= '<div>'.Codes::beautify($item['description']).'</div>';

			}

			return $output;

		// link to a category
		case 'category':

			// maybe an alternate title has been provided
			$attributes = preg_split("/\s*,\s*/", $id, 2);
			$id = $attributes[0];

			// load the record from the database
			if(!$item = Categories::get($id))
				$output = '[category='.$id.']';

			else {

				// ensure we have a label for this link
				if(isset($attributes[1])) {
					$text = $attributes[1];
					$type = 'basic';
				} else
					$text = Skin::strip($item['title']);

				// make a link to the target page
				$url = Categories::get_permalink($item);

				// return a complete anchor
				$output = Skin::build_link($url, $text, $type);
			}

			return $output;

		// insert category description
		case 'category.description':

			// maybe an alternate title has been provided
			$attributes = preg_split("/\s*,\s*/", $id, 2);
			$id = $attributes[0];

			// load the record from the database
			if(!$item = Categories::get($id))
				$output = '[category.description='.$id.']';

			else {

				// ensure we have a label for this link
				if(isset($attributes[1])) {
					$text = $attributes[1];
					$type = 'basic';
				} else
					$text = Skin::strip($item['title']);

				// make a link to the target page
				$url = Categories::get_permalink($item);

				// return a complete anchor
				$output = Skin::build_link($url, $text, 'category');

				// the introduction text, if any
				$output .= BR.Codes::beautify($item['introduction']);

				// load overlay, if any
				if(isset($item['overlay']) && $item['overlay']) {
					$overlay = Overlay::load($item, 'category:'.$item['id']);

					// get text related to the overlay, if any
					if(is_object($overlay))
						$output .= $overlay->get_text('view', $item);

				}

				// the description, which is the actual page body
				$output .= '<div>'.Codes::beautify($item['description']).'</div>';

			}

			return $output;

		// link to a comment
		case 'comment':
			include_once $context['path_to_root'].'comments/comments.php';

			// maybe an alternate title has been provided
			$attributes = preg_split("/\s*,\s*/", $id, 2);
			$id = $attributes[0];

			// load the record from the database
			if(!$item = Comments::get($id))
				$output = '[comment='.$id.']';

			else {

				// ensure we have a label for this link
				if(isset($attributes[1]))
					$text = $attributes[1];
				else
					$text = i18n::s('View this comment');

				// make a link to the target page
				$url = $context['url_to_home'].$context['url_to_root'].Comments::get_url($item['id']);

				// return a complete anchor
				$output = Skin::build_link($url, $text, 'basic');
			}

			return $output;

		// link to a download
		case 'download':

			// maybe an alternate title has been provided
			$attributes = preg_split("/\s*,\s*/", $id, 2);
			$id = $attributes[0];

			// load the record from the database
			if(!$item = Files::get($id))

				// file does not exist anymore
				if((isset($attributes[1]) && $attributes[1]))
					$output = $attributes[1].'<p '.tag::_class('details').'>'.i18n::s('[this file has been deleted]').'</p>';
				else
					$output = '[download='.$id.']';

			else {

				// label for this file
				$prefix = $text = $suffix = '';

				// signal restricted and private files
				if($item['active'] == 'N')
					$prefix .= PRIVATE_FLAG;
				elseif($item['active'] == 'R')
					$prefix .= RESTRICTED_FLAG;

				// ensure we have a label for this link
				if(isset($attributes[1]) && $attributes[1]) {
					$text .= $attributes[1];

					// this may describe a previous file, which has been replaced
					if(($item['edit_action'] != 'file:create') && ($attributes[1] != $item['file_name'])) {
						$text .= ' <p '.tag::_class('details').'>'.i18n::s('[this file has been replaced]').'</p>';
						$output = $prefix.$text.$suffix;
						return $output;
					}

				} else
					$text = Skin::strip( $item['title']?$item['title']:str_replace('_', ' ', $item['file_name']) );

				// flag files uploaded recently
				if($item['create_date'] >= $context['fresh'])
					$suffix .= NEW_FLAG;
				elseif($item['edit_date'] >= $context['fresh'])
					$suffix .= UPDATED_FLAG;

				// always download the file
				$url = $context['url_to_home'].$context['url_to_root'].Files::get_url($item['id'], 'fetch', $item['file_name']);

				// return a complete anchor
				$output = $prefix.Skin::build_link($url, $text, 'file').$suffix;
			}

			return $output;

		// link to a file
		case 'file':

			// maybe an alternate title has been provided
			$attributes = preg_split("/\s*,\s*/", $id, 2);
			$id = $attributes[0];

			// load the record from the database --ensure we get a fresh copy of the record, not a cached one
			if(!$item = Files::get($id, TRUE))

				// file does not exist anymore
				if((isset($attributes[1]) && $attributes[1]))
					$output = $attributes[1].'<p '.tag::_class('details').'>'.i18n::s('[this file has been deleted]').'</p>';
				else
					$output = '[file='.$id.']';

			else {

				// maybe we want to illustrate this file
				if((($item['edit_action'] != 'file:create') && isset($attributes[1]) && $attributes[1]) || (!$output = Files::interact($item))) {

					// label for this file
					$output = $prefix = $text = $suffix = '';

					// signal restricted and private files
					if($item['active'] == 'N')
						$prefix .= PRIVATE_FLAG;
					elseif($item['active'] == 'R')
						$prefix .= RESTRICTED_FLAG;

					// ensure we have a label for this link
					if(isset($attributes[1]) && $attributes[1]) {
						$text .= $attributes[1];

						// this may describe a previous file, which has been replaced
						if(($item['edit_action'] != 'file:create') && ($attributes[1] != $item['file_name'])) {
							$text .= '<p '.tag::_class('details').'>'.i18n::s('[this file has been replaced]').'</p>';
							$output = $prefix.$text.$suffix;
							return $output;
						}

					} else
						$text .= Skin::strip( $item['title']?$item['title']:str_replace('_', ' ', $item['file_name']) );

					// flag files uploaded recently
					if($item['create_date'] >= $context['fresh'])
						$suffix .= NEW_FLAG;
					elseif($item['edit_date'] >= $context['fresh'])
						$suffix .= UPDATED_FLAG;

					// make a link to the target page
					$url = Files::get_download_url($item);

					// return a complete anchor
					$output .= $prefix.Skin::build_link($url, $text, 'basic').$suffix;

				}
			}
			return $output;

		// invoke the selector
		case 'go':

			// extract the label, if any
			$attributes = preg_split("/\s*,\s*/", $id, 2);
			$name = $attributes[0];

			// ensure we have a label for this link
			if(isset($attributes[1]))
				$text = $attributes[1];
			else
				$text = $name;

			// return a complete anchor
			$output = Skin::build_link($context['url_to_home'].$context['url_to_root'].normalize_shortcut($name), $text, 'basic');
			return $output;

		// embed an image
		case 'image':
			include_once $context['path_to_root'].'images/images.php';

			// get the variant, if any
			$attributes = preg_split("/\s*,\s*/", $id, 2);
			$id = $attributes[0];
			if(isset($attributes[1]))
				$variant = $attributes[1];
			else
				$variant = 'inline';

			// get the image record
			if(!$image = Images::get($id)) {
				$output = '[image='.$id.']';
				return $output;
			}

			// a title for the image --do not force a title
			if(isset($image['title']))
				$title = $image['title'];
			else
				$title = '';

			// provide thumbnail if not defined, or forced, or for large images
			if(!$image['use_thumbnail']
				|| ($image['use_thumbnail'] == 'A')
				|| (($image['use_thumbnail'] == 'Y') && ($image['image_size'] > $context['thumbnail_threshold'])) ) {

				// not inline anymore, but thumbnail --preserve other variants
				if($variant == 'inline')
					$variant = 'thumbnail';

				// where to fetch the image file
				$href = Images::get_thumbnail_href($image);

				// to drive to plain image
				$link = Images::get_icon_href($image);

			// add an url, if any
			} elseif($image['link_url']) {

				// flag large images
				if($image['image_size'] > $context['thumbnail_threshold'])
					$variant = rtrim('large '.$variant);

				// where to fetch the image file
				$href = Images::get_icon_href($image);

				// transform local references, if any
				include_once $context['path_to_root'].'/links/links.php';
				$attributes = Links::transform_reference($image['link_url']);
				if($attributes[0])
					$link = $attributes[0];

				// direct use of this link
				else
					$link = $image['link_url'];

			// get the <img ... /> element
			} else {


				// flag large images
				if($image['image_size'] > $context['thumbnail_threshold'])
					$variant = rtrim('large '.$variant);

				// where to fetch the image file
				$href = Images::get_icon_href($image);

				// no link
				$link = '';

			}

			// use the skin
			if(Images::allow_modification($image['anchor'],$id))
			   // build editable image
			   $output = Skin::build_image($variant, $href, $title, $link, $id);
			else
			   $output = Skin::build_image($variant, $href, $title, $link);

			return $output;

		// embed a stack of images
		case 'images':
			include_once $context['path_to_root'].'images/images.php';

			// get the list of ids
			$ids = preg_split("/\s*,\s*/", $id);
			if(!count($ids)) {
				$output =  '[images=id1, id2, ...]';
				return $output;
			}

			// build the list of images
			$items = array();
			foreach($ids as $id) {

				// get the image record
				if($image = Images::get($id)) {

					// a title for the image --do not force a title
					if(isset($image['title']))
						$title = $image['title'];
					else
						$title = '';

					// provide thumbnail if not defined, or forced, or for large images
					$variant = 'inline';
					if(!$image['use_thumbnail']
						|| ($image['use_thumbnail'] == 'A')
						|| (($image['use_thumbnail'] == 'Y') && ($image['image_size'] > $context['thumbnail_threshold'])) ) {

						// not inline anymore, but thumbnail
						$variant = 'thumbnail';

						// where to fetch the image file
						$href = Images::get_thumbnail_href($image);

						// to drive to plain image
						$link = $context['url_to_root'].Images::get_url($id);

					// add an url, if any
					} elseif($image['link_url']) {

						// flag large images
						if($image['image_size'] > $context['thumbnail_threshold'])
							$variant = rtrim('large '.$variant);

						// where to fetch the image file
						$href = Images::get_icon_href($image);

						// transform local references, if any
						include_once $context['path_to_root'].'/links/links.php';
						$attributes = Links::transform_reference($image['link_url']);
						if($attributes[0])
							$link = $attributes[0];

						// direct use of this link
						else
							$link = $image['link_url'];

					// get the <img ... /> element
					} else {

						// flag large images
						if($image['image_size'] > $context['thumbnail_threshold'])
							$variant = rtrim('large '.$variant);

						// where to fetch the image file
						$href = Images::get_icon_href($image);

						// no link
						$link = '';

					}

					// use the skin
					$label = Skin::build_image($variant, $href, $title, $link);

					// add item to the stack
					$items[]  = $label;

				}

			}

			// format the list
			$output = '';
			if(count($items)) {

				// stack items
				$output = Skin::finalize_list($items, 'stack');

				// rotate items
				$output = Skin::rotate($output);
			}

			// done
			return $output;

		// link to the next article
		case 'next':

			// maybe an alternate title has been provided
			$attributes = preg_split("/\s*,\s*/", $id, 2);
			$id = $attributes[0];

			// load the record from the database
			if(!$item = Articles::get($id))
				$output = '[next='.$id.']';

			else {

				// ensure we have a label for this link
				if(isset($attributes[1]))
					$text = $attributes[1];
				else
					$text = Skin::strip($item['title']);

				// make a link to the target page
				$url = Articles::get_permalink($item);

				// return a complete anchor
				$output = Skin::build_link($url, $text, 'next');
			}

			return $output;

		// link to the previous article
		case 'previous':

			// maybe an alternate title has been provided
			$attributes = preg_split("/\s*,\s*/", $id, 2);
			$id = $attributes[0];

			// load the record from the database
			if(!$item = Articles::get($id))
				$output = '[previous='.$id.']';

			else {

				// ensure we have a label for this link
				if(isset($attributes[1]))
					$text = $attributes[1];
				else
					$text = Skin::strip($item['title']);

				// make a link to the target page
				$url = Articles::get_permalink($item);

				// return a complete anchor
				$output = Skin::build_link($url, $text, 'previous');
			}

			return $output;

		// link to a section
		case 'section':

			// maybe an alternate title has been provided
			$attributes = preg_split("/\s*,\s*/", $id, 2);
			$id = $attributes[0];

			// load the record from the database
			if(!$item = Sections::get($id))
				$output = '[section='.$id.']';

			else {

				// ensure we have a label for this link
				if(isset($attributes[1])) {
					$text = $attributes[1];
					$type = 'basic';
				} else
					$text = Skin::strip($item['title']);

				// make a link to the target page
				$url = Sections::get_permalink($item);

				// return a complete anchor
				$output = Skin::build_link($url, $text, $type);
			}

			return $output;

		// link to a server
		case 'server':
			include_once $context['path_to_root'].'servers/servers.php';

			// maybe an alternate title has been provided
			$attributes = preg_split("/\s*,\s*/", $id, 2);
			$id = $attributes[0];

			// load the record from the database
			if(!$item = Servers::get($id))
				$output = '[server='.$id.']';

			else {

				// ensure we have a label for this link
				if(isset($attributes[1])) {
					$text = $attributes[1];
					$type = 'basic';
				} else
					$text = Skin::strip($item['title']);

				// make a link to the target page
				$url = $context['url_to_home'].$context['url_to_root'].Servers::get_url($id);

				// return a complete anchor
				$output = Skin::build_link($url, $text, $type);
			}

			return $output;

		// link to a user
		case 'user':

			// maybe an alternate title has been provided
			$attributes = preg_split("/\s*,\s*/", $id, 2);
			$id = $attributes[0];

			// load the record from the database
			if(!$item = Users::get($id))
				$output = '[user='.$id.']';

			else {

				// ensure we have a label for this link
				if(isset($attributes[1])) {
					$text = $attributes[1];
					$type = 'basic';
				} elseif(isset($item['full_name']) && $item['full_name'])
					$text = ucfirst($item['full_name']);
				else
					$text = ucfirst($item['nick_name']);

				// make a link to the target page
				$url = Users::get_permalink($item);

				// return a complete anchor
				$output = Skin::build_link($url, $text, $type);
			}

			return $output;

		// invalid type
		default:
			$output = '['.$type.']';
			return $output;

		}

	}

	/**
	 * get the value of one global parameter
	 *
	 * Parameter is taken from the global $context array, and its name has to start with
	 * prefix 'page_', or 'site_', for obvious security reasons.
	 *
	 * @param string name of the parameter
	 * @param mixed default value, if any
	 * @return the actual value of this parameter, else the default value, else ''
	 */
	public static function render_parameter($name, $default='') {
		global $context;

		if(!strncmp($name, 'page_', 5) && isset($context[$name])) {
			$output = $context[$name];
			return $output;
		}

		if(!strncmp($name, 'site_', 5) && isset($context[$name])) {
			$output = $context[$name];
			return $output;
		}

		$output = $default;
		return $output;
	}

	/**
	 * render a block of code
	 *
	 * @param string the text
	 * @return string the rendered text
	**/
	public static function render_pre($text, $variant='snippet') {
            
                $text = Codes::fix_tags($text);

		// change new lines
		$text = trim(str_replace("\r", '', str_replace(array("<br>\n", "<br/>\n", "<br />\n", '<br>', '<br/>', '<br />'), "\n", $text)));

		// caught from tinymce
		if(preg_match('/<p>(.*)<\/p>$/s', $text, $matches)) {
			$text = $matches[1];
			$text = str_replace(array('&amp;', '<p>', '</p>'), array('&', '', "\n"), $text);
		}

		// match some php code
		$explicit = FALSE;
		if(preg_match('/<\?php\s/', $text))
			$variant = 'php';
		elseif(($variant == 'php') && !preg_match('/<\?'.'php.+'.'\?'.'>/', $text)) {
			$text = '<?'.'php'."\n".$text."\n".'?'.'>';
			$explicit = TRUE;
		}

		// highlight php code, if any
		if($variant == 'php') {

			// fix some chars set by wysiwig editors
			$text = str_replace(array('&lt;', '&gt;', '&nbsp;', '&quot;'), array('<', '>', ' ', '"'), $text);

			// wrap long lines if necessary
// 			$lines = explode("\n", $text);
// 			$text = '';
// 			foreach($lines as $line)
// 				$text .= wordwrap($line, 100, " \n", 0)."\n";

			// handle newlines and indentations properly
			$text = str_replace(array("\n<span", "\n</code", "\n</pre", "\n</span"), array('<span', '</code', '</pre', '</span'), Safe::highlight_string($text));

			// remove explicit php prefix and suffix -- dependant of highlight_string() evolution
			if($explicit)
				$text = preg_replace(array('/&lt;\?php<br\s*\/>/', '/\?&gt;/'), '', $text);

		// or prevent html rendering
		} else
			$text = str_replace(array('<', "\n"), array('&lt;', '<br/>'), $text);

		// prevent additional transformations
		$search = array(	'[',		']',		':',		'//',			'##',			'**',			'++',			'--',			'__');
		$replace = array(	'&#91;',	'&#93;',	'&#58;',	'&#47;&#47;',	'&#35;&#35;',	'&#42;&#42;',	'&#43;&#43;',	'&#45;&#45;',	'&#95;&#95;');
		$output = '<pre>'.str_replace($search, $replace, $text).'</pre>';

		return $output;

	}
        
        /**
         * render [php]...[php] code
         * 
         * @param string $text
         * @return string the formatted block
         */
        public static function render_pre_php($text) {
            return Codes::render_pre($text,'php');
        }

	/**
	 * render a compact list of recent publications
	 *
	 * The provided anchor can reference:
	 * - a section 'section:123'
	 * - a category 'category:456'
	 * - a user 'user:789'
	 * - 'self'
	 * - nothing
	 *
	 * @param string the anchor (e.g. 'section:123')
	 * @param string layout to use
	 * @return string the rendered text
	**/
	public static function render_published($layout='simple', $anchor='') {
		global $context;

		// we return some text;
		$text = '';

		// number of items to display
		$count = COMPACT_LIST_SIZE;
		if($position = strrpos($anchor, ',')) {
			$count = (integer)trim(substr($anchor, $position+1));
			if(!$count)
				$count = COMPACT_LIST_SIZE;

			$anchor = trim(substr($anchor, 0, $position));
		}

		// scope is limited to current surfer
		if(($anchor == 'self') && Surfer::get_id()) {
			$anchor = 'user:'.Surfer::get_id();

			// refresh on every page load
			Cache::poison();

		}

		// scope is limited to one section
		if(strpos($anchor, 'section:') === 0) {

			// look at this branch of the content tree
			$anchors = Sections::get_branch_at_anchor($anchor);

			// query the database and layout that stuff
			$text = Articles::list_for_anchor_by('publication', $anchors, 0, $count, $layout);

		// scope is limited to one category
		} elseif(strpos($anchor, 'category:') === 0) {

			// first level of depth
			$anchors = array();

			// get sections linked to this category
			if($topics = Members::list_sections_by_title_for_anchor($anchor, 0, 50, 'raw')) {
				foreach($topics as $id => $not_used)
					$anchors = array_merge($anchors, array('section:'.$id));
			}

			// second level of depth
			if(count($topics) && (count($anchors) < 2000)) {
				$topics = Sections::get_children_of_anchor($anchors);
				$anchors = array_merge($anchors, $topics);
			}

			// third level of depth
			if(count($topics) && (count($anchors) < 2000)) {
				$topics = Sections::get_children_of_anchor($anchors);
				$anchors = array_merge($anchors, $topics);
			}

			// fourth level of depth
			if(count($topics) && (count($anchors) < 2000)) {
				$topics = Sections::get_children_of_anchor($anchors);
				$anchors = array_merge($anchors, $topics);
			}

			// fifth level of depth
			if(count($topics) && (count($anchors) < 2000)) {
				$topics = Sections::get_children_of_anchor($anchors);
				$anchors = array_merge($anchors, $topics);
			}

			// the category itself is an anchor
			$anchors[] = $anchor;

			// ensure anchors are referenced only once
			$anchors = array_unique($anchors);

			// query the database and layout that stuff
			$text = Members::list_articles_by_date_for_anchor($anchors, 0, $count, $layout);

		// scope is limited to one author
		} elseif(strpos($anchor, 'user:') === 0)
			$text = Articles::list_for_author_by('publication', str_replace('user:', '', $anchor), 0, $count, $layout);

		// consider all pages
		else
			$text = Articles::list_by('publication', 0, $count, $layout);

		// we have an array to format
		if(is_array($text))
			$text = Skin::build_list($text, $layout);

		// job done
		return $text;
	}

	/**
	 * select a random page
	 *
	 * The provided anchor can reference:
	 * - a section 'section:123'
	 * - a category 'category:456'
	 * - a user 'user:789'
	 * - 'self'
	 * - nothing
	 *
	 * @param string the anchor (e.g. 'section:123')
	 * @param string layout to use
	 * @return string the rendered text
	**/
	public static function render_random($anchor='', $layout='') {
		global $context;

		// we return some text;
		$text = '';

		// label is explicit
		$label = '';
		if($position = strrpos($anchor, ',')) {
			$label = trim(substr($anchor, $position+1));
			$anchor = trim(substr($anchor, 0, $position));
		}

		// scope is limited to current surfer
		if(($anchor == 'self') && Surfer::get_id()) {
			$anchor = 'user:'.Surfer::get_id();

			// refresh on every page load
			Cache::poison();

		}

		// scope is limited to one section
		if(!strncmp($anchor, 'section:', 8)) {

			// look at this branch of the content tree
			$anchors = Sections::get_branch_at_anchor($anchor);

			// query the database and layout that stuff
			$items = Articles::list_for_anchor_by('random', $anchors, 0, 1, 'raw');

		// scope is limited to one author
		} elseif(!strncmp($anchor, 'user:', 5))
			$items = Articles::list_for_author_by('random', str_replace('user:', '', $anchor), 0, 1, 'raw');

		// consider all pages
		else
			$items = Articles::list_by('random', 0, 1, 'raw');

		// we have an array to format
 		if($items) {
 			foreach($items as $id => $item) {

				// make a link to the target page
				$link = Articles::get_permalink($item);
				if(!$label)
					$label = Skin::strip($item['title']);
				$text = Skin::build_link($link, $label, 'article');

				if($layout == 'description') {

					// the introduction text, if any
					$text .= BR.Codes::beautify($item['introduction']);

					// load overlay, if any
					if(isset($item['overlay']) && $item['overlay']) {
						$overlay = Overlay::load($item, 'article:'.$item['id']);

						// get text related to the overlay, if any
						if(is_object($overlay))
							$text .= $overlay->get_text('view', $item);

					}

					// the description, which is the actual page body
					$text .= '<div>'.Codes::beautify($item['description']).'</div>';

				}

				// we take only one item
				break;
			}
 		}

		// job done
		return $text;
	}

	/**
	 * redirect dynamically from this page to any local web address
	 *
	 * This is typically useful to have a regular yacs page redirected to a specific PHP script.
	 *
	 * @param string target link
	 * @return text generated during the inclusion
	 */
	public static function render_redirect($link) {
		global $context;

		// turn external links to clickable things
		if(preg_match('/^(ftp:|http:|https:|www\.)/i', $link)) {
			$output = '<p>'.Skin::build_link($link).'</p>';
			return $output;
		}

		// only while viewing real pages
		if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'GET')) {
			$output = '<p>'.Skin::build_link($link).'</p>';
			return $output;
		}
                
                // redirect to a reference
                if(preg_match('/^(article|section|category):[0-9]+$/',$link)) {
                    $anchor = Anchors::get($link);
                    if($anchor)
                        Safe::redirect($anchor->get_permalink());
                    else {
                        Logger::error(sprintf(i18n::s('redirection to unknown reference : %s'),$link));
                        $output = '';
                        return $output;
                    }
                }

		// check path to the file
		while(TRUE) {

			// remove leading /
			if($link[0] == '/') {
				$link = substr($link, 1);
				continue;
			}

			// avoid reference to current directory
			if(!strncmp($link, './', 2)) {
				$link = substr($link, 2);
				continue;
			}

			// can't go outside this instance of yacs
			if(!strncmp($link, '../', 3)) {
				$link = substr($link, 3);
				continue;
			}

			break;
		}

		// forward to the target page
		Safe::redirect($context['url_to_home'].$context['url_to_root'].$link);

	}

	/**
	 * render a list of sections
	 *
	 * The provided anchor can reference:
	 * - a section 'section:123'
	 * - a user 'user:789'
	 * - 'self'
	 * - nothing
	 *
	 * @param string the anchor (e.g. 'section:123')
	 * @param string layout to use
	 * @return string the rendered text
	**/
	public static function render_sections($layout='simple', $anchor='') {
		global $context;

		// we return some text;
		$text = '';

		// number of items to display
		$count = YAHOO_LIST_SIZE;
		if(($position = strpos($anchor, ',')) !== FALSE) {
			$count = (integer)trim(substr($anchor, $position+1));
			if(!$count)
				$count = YAHOO_LIST_SIZE;

			$anchor = trim(substr($anchor, 0, $position));
		}

		// scope is limited to current surfer
		if(($anchor == 'self') && Surfer::get_id()) {
			$anchor = 'user:'.Surfer::get_id();

			// refresh on every page load
			Cache::poison();

		}

		// scope is limited to one section
		if(strpos($anchor, 'section:') === 0)
			$text = Sections::list_by_title_for_anchor($anchor, 0, $count, $layout);

		// scope is limited to one author
		elseif(strpos($anchor, 'user:') === 0)
			$text = Members::list_sections_by_title_for_anchor($anchor, 0, $count, $layout);

		// consider all pages
		else
			$text = Sections::list_by_title_for_anchor(NULL, 0, $count, $layout);

		// we have an array to format
		if(is_array($text))
			$text = Skin::build_list($text, $layout);

		// job done
		return $text;
	}

	

	/**
	 * render a compact list of recent modifications
	 *
	 * The provided anchor can reference:
	 * - a section 'section:123'
	 * - a category 'category:456'
	 * - a user 'user:789'
	 * - 'self'
	 * - nothing
	 *
	 * @param string the anchor (e.g. 'section:123')
	 * @param string layout to use
	 * @return string the rendered text
	**/
	public static function render_updated($layout='simple', $anchor='') {
		global $context;

		// we return some text;
		$text = '';

		// number of items to display
		$count = COMPACT_LIST_SIZE;
		if(($position = strpos($anchor, ',')) !== FALSE) {
			$count = (integer)trim(substr($anchor, $position+1));
			if(!$count)
				$count = COMPACT_LIST_SIZE;

			$anchor = trim(substr($anchor, 0, $position));
		}

		// scope is limited to current surfer
		if(($anchor == 'self') && Surfer::get_id()) {
			$anchor = 'user:'.Surfer::get_id();

			// refresh on every page load
			Cache::poison();

		}

		// scope is limited to one section
		if(strpos($anchor, 'section:') === 0) {

			// look at this branch of the content tree
			$anchors = Sections::get_branch_at_anchor($anchor);

			// query the database and layout that stuff
			$text = Articles::list_for_anchor_by('edition', $anchors, 0, $count, $layout);

		// scope is limited to one category
		} elseif(strpos($anchor, 'category:') === 0) {

			// first level of depth
			$anchors = array();

			// get sections linked to this category
			if($topics = Members::list_sections_by_title_for_anchor($anchor, 0, 50, 'raw')) {
				foreach($topics as $id => $not_used)
					$anchors = array_merge($anchors, array('section:'.$id));
			}

			// second level of depth
			if(count($topics) && (count($anchors) < 2000)) {
				$topics = Sections::get_children_of_anchor($anchors);
				$anchors = array_merge($anchors, $topics);
			}

			// third level of depth
			if(count($topics) && (count($anchors) < 2000)) {
				$topics = Sections::get_children_of_anchor($anchors);
				$anchors = array_merge($anchors, $topics);
			}

			// fourth level of depth
			if(count($topics) && (count($anchors) < 2000)) {
				$topics = Sections::get_children_of_anchor($anchors);
				$anchors = array_merge($anchors, $topics);
			}

			// fifth level of depth
			if(count($topics) && (count($anchors) < 2000)) {
				$topics = Sections::get_children_of_anchor($anchors);
				$anchors = array_merge($anchors, $topics);
			}

			// the category itself is an anchor
			$anchors[] = $anchor;

			// ensure anchors are referenced only once
			$anchors = array_unique($anchors);

			// query the database and layout that stuff
			$text = Members::list_articles_by_date_for_anchor($anchors, 0, $count, $layout);

		// scope is limited to pages of one surfer
		} elseif(strpos($anchor, 'user:') === 0)
			$text = Articles::list_for_user_by('edition', substr($anchor, 5), 0, $count, $layout);

		// consider all pages
		else
			$text = Articles::list_by('edition', 0, $count, $layout);

		// we have an array to format
		if(is_array($text))
			$text = Skin::build_list($text, $layout);

		// job done
		return $text;
	}

	/**
	 * render a compact list of users present on site
	 *
	 * @param string the anchor (e.g. 'present')
	 * @return string the rendered text
	**/
	public static function render_users($anchor='') {
		global $context;

		// we return some text;
		$text = '';

		// number of items to display
		$count = COMPACT_LIST_SIZE;
		if(($position = strpos($anchor, ',')) !== FALSE) {
			$count = (integer)trim(substr($anchor, $position+1));
			if(!$count)
				$count = COMPACT_LIST_SIZE;

			$anchor = trim(substr($anchor, 0, $position));
		}

	    //  the list of users present on the site
    	$text = Users::list_present(0, $count, 'compact');

	  	// also mention the total number of users present, if larger than the number of users displayed
  		$stat = Users::stat_present();
	  	if($stat['count'] > $count)
  			$text = array_merge($text, array('_' => sprintf(i18n::ns('%d active now', '%d active now', $stat['count']), $stat['count'])));

		// we have an array to format
		if(is_array($text))
			$text = Skin::build_list($text, 'compact');

		// job done
		return $text;
	}

	/**
	 * render a link to Wikipedia
	 *
	 * @param string the id, with possible options or variant
	 * @return string the rendered text
	**/
	public static function render_wikipedia($id) {
		global $context;
                
                $id = Codes::fix_tags($id);

		// maybe an alternate title has been provided
		$attributes = preg_split("/\s*,\s*/", $id, 2);
		$id = $attributes[0];

		// ensure we have a label for this link
		if(isset($attributes[1]))
			$text = $attributes[1];
		else
			$text = '';

		// select the language
		$language = $context['preferred_language'];

		// take the navigator language if possible
		if (isset($context['language']) && $context['without_language_detection']=='N')
			$language = $context['language'];

		// make a link to the target page
		$url = 'https://'.$language.'.wikipedia.org/wiki/Special:Search?search='.preg_replace('[\s]', '_', $id);

		// return a complete anchor
		$output = Skin::build_link($url, $text, 'wikipedia');
		return $output;

	}
        
        /**
         * internal method to store patterns of this serveur in a file
         * this to spare time for parsing each extension in /codes at page loading
         * @see method render
         * 
         * @global array $context
         * @param array $patterns_map
         */
        private static function save_patterns($patterns_map) {
            global $context;
            
            // backup the old version
            Safe::unlink($context['path_to_root'].'codes/patterns.auto.php.bak');
            Safe::rename($context['path_to_root'].'codes/patterns.auto.php', $context['path_to_root'].'codes/auto.patterns.auto.php.bak');
            
            $content = '<?php'."\n"
		.'// This file has been created by the script codes/codes.php'."\n"
		.'// on '.gmdate("F j, Y, g:i a").' GMT, Please do not modify it manually.'."\n";
            
            foreach($patterns_map as $pattern => $action) {
                $content .= '$patterns_map[\'' . $pattern . '\']="' . addcslashes(str_replace("\n",'\n',$action),'"') . "\";\n";
            }
            
            if(!Safe::file_put_contents('codes/patterns.auto.php', $content)) {

		Logger::error(sprintf(i18n::s('ERROR: Impossible to write to the file %s. The configuration has not been saved.'), 'codes/patterns.auto.php'));
            }
            
            
        }


	/**
	 * remove YACS codes from a string
	 *
	 * @param string embedding YACS codes
	 * @param boolean FALSE to remove only only pairing codes, TRUE otherwise
	 * @return a purged string
	 */
	public static function strip($text, $suppress_all_brackets=TRUE) {
		global $context;

		// suppress pairing codes
		$output = preg_replace('#\[(\w+?)[^\]]*\](.*?)\[\/\1\]#s', '${2}', $text);

		// suppress bracketed words
		if($suppress_all_brackets)
			$output = trim(preg_replace('/\[(.+?)\]/s', ' ', $output));

		return $output;
	}
}

?>
