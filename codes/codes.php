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
 * Basic codes, demonstrated into [link]codes/basic.php[/link]:
 * - **...** - wiki bold text
 * - &#91;b]...[/b] - bold text
 * - //...// - italics
 * - &#91;i]...[/i] - italics
 * - __...__ - underlined
 * - &#91;u]...[/u] - underlined
 * - ##...## - monospace
 * - &#91;code]...[/code] - a short sample of fixed-size text (e.g. a file name)
 * - &#91;color]...[/color] - change font color
 * - &#91;tiny]...[/tiny] - tiny size
 * - &#91;small]...[/small] - small size
 * - &#91;big]...[/big] - big size
 * - &#91;huge]...[/huge] - huge size
 * - &#91;subscript]...[/subscript] - subscript
 * - &#91;superscript]...[/superscript] - superscript
 * - ++...++ - inserted
 * - &#91;inserted]...[/inserted] - inserted
 * - --...-- - deleted
 * - &#91;deleted]...[/deleted] - deleted
 * - &#91;flag]...[/flag] - draw attention
 * - &#91;lang=xy]...[/lang] - show some text only on matching language
 * - &#91;style=sans-serif]...[/style] - use a sans-serif font
 * - &#91;style=serif]...[/style] - use a serif font
 * - &#91;style=cursive]...[/style] - mimic hand writing
 * - &#91;style=comic]...[/style] - make it funny
 * - &#91;style=fantasy]...[/style] - guess what will appear
 * - &#91;style=my_style]...[/style] - translated to &lt;span class="my_style"&gt;...&lt;/span&gt;
 *
 * @see codes/basic.php
 *
 * Block codes, demonstrated in [link]codes/blocks.php[/link]:
 * - &#91;indent]...[/indent] - shift text to the right
 * - &#91;center]...[/center] - some centered text
 * - &#91;right]...[/right] - some right-aligned text
 * - &#91;decorated]...[/decorated] - some pretty paragraphs
 * - &#91;caution]...[/caution] - a warning paragraph
 * - &#91;note]...[/note] - a noticeable paragraph
 * - &#91;php]...[/php] - a snippet of php
 * - &#91;snippet]...[/snippet] - a snippet of fixed font data
 * - &#91;quote]...[/quote] - a block of quoted text
 * - &#91;folded]...[/folded] - click to view its content, or to fold it away
 * - &#91;folded=foo bar]...[/folded] - with title 'foo bar'
 * - &#91;unfolded]...[/unfolded] - click to fold
 * - &#91;unfolded=foo bar]...[/unfolded] - with title 'foo bar'
 * - &#91;sidebar]...[/sidebar] - a nice box aside
 * - &#91;sidebar=foo bar]...[/sidebar] - with title 'foo bar'
 * - &#91;scroller]...[/scroller] - some scrolling text
 *
 * @see codes/blocks.php
 *
 * List codes, demonstrated in [link]codes/lists.php[/link]:
 * - &#91;*] - for simple lists
 * - &#91;list]...[/list] - bulleted list
 * - &#91;list=1]...[/list] - numbered list, use numbers
 * - &#91;list=a]...[/list] - numbered list, use letters
 * - &#91;list=A]...[/list] - numbered list, use capital letters
 * - &#91;list=i]...[/list] - numbered list, use roman numbers
 * - &#91;list=I]...[/list] - numbered list, use upper case roman numbers
 *
 * @see codes/lists.php
 *
 * Codes for links, demonstrated in [link]codes/links.php[/link]:
 * - &lt;url&gt; - &lt;a href="url">url&lt;/a> or &lt;a href="url" class="external">url&lt;/a>
 * - &#91;link]&lt;url&gt;[/link] - &lt;a href="url">url&lt;/a> or &lt;a href="url" class="external">url&lt;/a>
 * - &#91;&lt;label&gt;|&lt;url&gt;] - &lt;a href="url">label&lt;/a> or &lt;a href="url" class="external">label&lt;/a>
 * - &#91;link=&lt;label&gt;]&lt;url&gt;[/link] - &lt;a href="url">label&lt;/a> or &lt;a href="url" class="external">label&lt;/a>
 * - &#91;url]&lt;url&gt;[/url] - deprecated by &#91;link]
 * - &#91;url=&lt;url&gt;]&lt;label&gt;[/url] - deprecated by &#91;link]
 * - &#91;button=&lt;label&gt;|&lt;url&gt;] - build simple buttons with css
 * - &#91;click=&lt;label&gt;|&lt;url&gt;] - a button that counts clicks
 * - &#91;clicks=&lt;url&gt;] - lists people who have clicked
 * - &lt;address&gt; - &lt;a href="mailto:address" class="email">address&lt;/a>
 * - &#91;email]&lt;address&gt;[/email] - &lt;a href="mailto:address" class="email">address&lt;/a>
 * - &#91;email=&lt;name&gt;]&lt;address&gt;[/email] - &lt;a href="mailto:address" class="email">name&lt;/a>
 * - &#91;go=&lt;name&gt;, &lt;label&gt;] - trigger the selector on 'name'
 * - &#91;&#91;&lt;name&gt;, &lt;label&gt;]] - Wiki selector
 * - &#91;article=&lt;id>] - use article title as link label
 * - &#91;article=&lt;id>, foo bar] - with label 'foo bar'
 * - &#91;article.description=&lt;id>] - insert article description
 * - &#91;next=&lt;id>] - shortcut to next article
 * - &#91;next=&lt;id>, foo bar] - with label 'foo bar'
 * - &#91;previous=&lt;id>] - shortcut to previous article
 * - &#91;previous=&lt;id>, foo bar] - with label 'foo bar'
 * - &#91;random] - pick up one page randomly
 * - &#91;random=&lt;section:id>] - one page in this section
 * - &#91;section=&lt;id>] - use section title as link label
 * - &#91;section=&lt;id>, foo bar] - with label 'foo bar'
 * - &#91;category=&lt;id>] - use category title as link label
 * - &#91;category=&lt;id>, foo bar] - with label 'foo bar'
 * - &#91;category.description=&lt;id>] - insert category description
 * - &#91;user=&lt;id>] - use nick name as link label
 * - &#91;user=&lt;id>, foo bar] - with label 'foo bar'
 * - &#91;server=&lt;id>] - use server title as link label
 * - &#91;server=&lt;id>, foo bar] - with label 'foo bar'
 * - &#91;file=&lt;id>] - use file title as link label
 * - &#91;file=&lt;id>, foo bar] - with label 'foo bar'
 * - &#91;download=&lt;id>] - a link to download a file
 * - &#91;download=&lt;id>, foo bar] - with label 'foo bar'
 * - &#91;comment=&lt;id>] - use comment id in link label
 * - &#91;comment=&lt;id>, foo bar] - with label 'foo bar'
 * - &#91;script]&lt;path/script.php&gt;[/script] - to the phpDoc page for script 'path/script.php'
 * - &#91;search] - a search form
 * - &#91;search=&lt;word&gt;] - hit Enter to search for 'word'
 * - &#91;wikipedia=&lt;keyword] - search Wikipedia
 * - &#91;wikipedia=&lt;keyword, foo bar] - search Wikipedia, with label 'foo bar'
 * - &#91;proxy]&lt;url&gt;[/proxy] - proxy a remote address
 *
 * @see codes/links.php
 *
 * Titles and questions, demonstrated in [link]codes/titles.php[/link]:
 * - &#91;toc] - table of contents
 * - ==...== - a level 1 headline
 * - &#91;title]...[/title] - a level 1 headline, put in the table of contents
 * - ===...=== - a level 2 headline
 * - &#91;subtitle]...[/subtitle] - a level 2 headline
 * - &#91;header1]...[/header1] - a level 1 headline
 * - &#91;header2]...[/header2] - a level 2 headline
 * - &#91;header3]...[/header3] - a level 3 headline
 * - &#91;header4]...[/header4] - a level 4 headline
 * - &#91;header5]...[/header5] - a level 5 headline
 * - &#91;toq] - the table of questions for this page
 * - &#91;question]...[/question] - a question-title
 * - &#91;question] - a simple question
 * - &#91;answer] - some answer in a FAQ
 *
 * @see codes/titles.php
 *
 * Tables, demonstrated in [link]codes/tables.php[/link]:
 * - &#91;table]...[/table] - one simple table
 * - &#91;table=grid]...[/table] - add a grid
 * - &#91;table].[body].[/table] - a table with headers
 * - &#91;csv]...[/csv] - import some data from a spreadsheet
 * - &#91;csv=;]...[/csv] - import some data from a spreadsheet
 * - &#91;table.json] - format a table as json
 *
 * @see codes/tables.php
 *
 * Live codes, demonstrated in [link]codes/live.php[/link]:
 * - &#91;sections] - site map
 * - &#91;sections=section:&lt;id>] - sub-sections
 * - &#91;sections=self] - sections assigned to current surfer
 * - &#91;sections=user:&lt;id>] - sections assigned to given user
 * - &#91;categories] - category tree
 * - &#91;categories=category:&lt;id>] - sub-categories
 * - &#91;categories=self] - categories assigned to current surfer
 * - &#91;categories=user:&lt;id>] - categories assigned to given user
 * - &#91;published] - most recent published pages, in a compact list
 * - &#91;published=section:&lt;id>] - articles published most recently in the given section
 * - &#91;published=category:&lt;id>] - articles published most recently in the given category
 * - &#91;published=user:&lt;id>] - articles published most recently created by given user
 * - &#91;published.decorated=self, 20] - 20 most recent pages from current surfer, as a decorated list
 * - &#91;updated] - most recent updated pages, in a compact list
 * - &#91;updated=section:&lt;id>] - articles updated most recently in the given section
 * - &#91;updated=category:&lt;id>] - articles updated most recently in the given category
 * - &#91;updated=user:&lt;id>] - articles updated most recently created by given user
 * - &#91;updated.simple=self, 12] - articles updated most recently created by current surfer, as a simple list
 * - &#91;read] - most read articles, in a compact list
 * - &#91;read=section:&lt;id>] - articles of fame in the given section
 * - &#91;read=self] - personal hits
 * - &#91;read=user:&lt;id>] - personal hits
 * - &#91;voted] - most voted articles, in a compact list
 * - &#91;voted=section:&lt;id>] - articles of fame in the given section
 * - &#91;voted=self] - personal hits
 * - &#91;voted=user:&lt;id>] - personal hits
 * - &#91;users=present] - list of users present on site
 *
 * @see codes/live.php
 *
 * Widgets, demonstrated in [link]codes/widgets.php[/link]:
 * - &#91;newsfeed=url] - integrate a newsfeed dynamically
 * - &#91;newsfeed.embed=url] - integrate a newsfeed dynamically
 * - &#91;twitter=id] - twitter updates of one person
 * - &#91;tsearch=token] - twitter search on a given topic
 * - &#91;iframe=&lt;width&gt;, &lt;height&gt;]&lt;url&gt;[/iframe] - include some external page
 * - &#91;cloud] - the tags used at this site
 * - &#91;cloud=12] - maximum count of tags used at this site
 * - &#91;calendar] - events for this month
 * - &#91;calendar=section:&lt;id>] - dates in one section
 * - &#91;locations=all] - newest locations
 * - &#91;locations=users] - map user locations on Google maps
 * - &#91;location=latitude, longitude, label] - to build a dynamic map
 *
 * @see codes/widgets.php
 *
 * Miscellaneous codes, demonstrated in [link]codes/misc.php[/link]:
 * - &#91;hint=&lt;help popup]...[/hint] - &lt;acronym tite="help popup">...&lt;/acronym>
 * - &#91;nl] - new line
 * - ----... - line break
 * - &#91;---] or &#91;___] - horizontal rule
 * - &#91;new] - something new
 * - &#91;popular] - people love it
 * - &#91;be] - country flag
 * - &#91;ca] - country flag
 * - &#91;ch] - country flag
 * - &#91;de] - country flag
 * - &#91;en] - country flag
 * - &#91;es] - country flag
 * - &#91;fr] - country flag
 * - &#91;gb] - country flag
 * - &#91;gr] - country flag
 * - &#91;it] - country flag
 * - &#91;pt] - country flag
 * - &#91;us] - country flag
 * - &#91;chart]...[/chart] - draw a dynamic chart
 * - &#91;execute=script] - include another local script
 * - &#91;redirect=link] - jump to another local page
 * - &#91;parameter=name] - value of one attribute of the global context
 * - &#91;escape]...[/escape]
 * - &#91;anonymous]...[/anonymous] - for non-logged people only
 * - &#91;authenticated]...[/authenticated] - for logged members only
 * - &#91;associate]...[/associate] - for associates only
 *
 * @see codes/misc.php
 *
 * In-line elements:
 * - &#91;embed=&lt;id>, &lt;width>, &lt;height>, &lt;flashparams>] - embed a multimedia file
 * - &#91;embed=&lt;id>, window] - render a multimedia file in a separate window
 * - &#91;sound=&lt;id>] - play a sound
 * - &#91;image=&lt;id>] - an inline image
 * - &#91;image=&lt;id>,left] - a left-aligned image
 * - &#91;image=&lt;id>,center] - a centered image
 * - &#91;image=&lt;id>,right] - a right-aligned image
 * - &#91;image]src[/image]
 * - &#91;image=&lt;alt>]src[/image]
 * - &#91;images=&lt;id1>, &lt;id2>, ...] - a stack of images
 * - &#91;img]src[/img] (deprecated)
 * - &#91;img=&lt;alt>]src[/img] (deprecated)
 * - &#91;table=&lt;id>] - an inline table
 * - &#91;location=&lt;id>] - embed a map
 * - &#91;location=&lt;id>, foo bar] - with label 'foo bar'
 * - &#91;clear] - to introduce breaks after floating elements
 *
 * @link http://www.estvideo.com/dew/index/2005/02/16/370-player-flash-mp3-leger-comme-une-plume the dewplayer page
 *
 * Other codes:
 * - &#91;menu=label]url[/menu] - one of the main menu command
 * - &#91;submenu=label]url[/submenu] - one of the second-level menu commands
 *
 * This script attempts to fight bbCode code injections by filtering strings to be used
 * as [code]src[/code] or as [code]href[/code] attributes (Thank you Mordread).
 *
 * @author Bernard Paques
 * @author Mordread Wallas
 * @author GnapZ
 * @author Alain Lesage (Lasares)
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
                "/<br\s*/>\n+/i"                            =>      BR,
                "/\n\n+/i"                                  =>      BR.BR,
                "'/\[lang=([^\]]+?)\](.*?)\[\/lang\]/is"    =>      'Codes::render_lang'
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
	public static function &beautify_title($text) {

		// suppress pairing codes
		$output =& Codes::strip($text, FALSE);

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
                
                if(!strncmp($main_target, 'http://', 7))
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
        
        private static function process($text, $patterns_map) {
            
            // ensure we have enough time to execute
            Safe::set_time_limit(30);

            foreach($patterns_map as $pattern => $action) {

                $text = preg_replace_callback($pattern, function($matches) use ($pattern, $action) {

                    // returned text
                    $replace = '';

                    // function to call
                    $func = '';
                    // array of captured element
                    $capture    = array_slice($matches, 1);

                    // test if mapped action is a callable function
                    if(is_callable($action)) { 
                        $func   = $action;
                    // test if map is a class
                    }elseif(class_exists($action)) { 
                        $func   = $action.'::render';
                    }

                    if($func) {
                        if( count($capture) ) {
                            $replace  .= call_user_func_array($func, $capture);
                        } else {
                            $replace  .= call_user_func($func);
                        }
                    } else {
                        // regular preg_replace
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
		global $context;

                // the formatting code interface
                include_once $context['path_to_root'].'codes/code.php';

		// streamline newlines, even if this has been done elsewhere
		$text = str_replace(array("\r\n", "\r"), "\n", $text);

		// prevent wysiwyg editors to bracket our own tags
		$text = preg_replace('#^<p>(\[.+\])</p>$#m', '$1', $text);

 		// initialize only once
		static $patterns_map;
               
		if(!isset($patterns_map) ) {

			// core patterns
			$patterns_map['|<!-- .* -->|i']                                             = '';                          // remove HTML comments
                        $patterns_map['|</h(\d)>\n+|i']                                             = '</h$1>'                  ;  // strip \n after title
                        $patterns_map['/\n[ \t]*(From|To|cc|bcc|Subject|Date):(\s*)/i']             = BR.'$1:$2'                ;  // common message headers
                        $patterns_map['/\[escape\](.*?)\[\/escape\]/is']                            = 'Codes::render_escaped'   ;  // [escape]...[/escape] (before everything)
                        $patterns_map['/\[php\](.*?)\[\/php\]/is']                                  = 'Codes::render_pre_php'   ;  // [php]...[/php]
                        $patterns_map['/\[snippet\](.*?)\[\/snippet\]/is']                          = 'Codes::render_pre'       ;  // [snippet]...[/snippet]
                        $patterns_map['/(\[page\].*)$/is']                                          = ''                        ;  // [page] (provide only the first one)
                        $patterns_map['/\[associate\](.*?)\[\/(associate)\]/is']                    = 'Codes::render_hidden'    ;  // [associate]...[/associate] 
                        $patterns_map['/\[member\](.*?)\[\/(member)\]/is']                          = 'Codes::render_hidden'    ;  // [member]...[/member] 
                        $patterns_map['/\[anonymous\](.*?)\[\/(anonymous)\]/is']                    = 'Codes::render_hidden'    ;  // [anonymous]...[/anonymous] 
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
                        $patterns_map['#(\s)([a-z]+?://[a-z0-9_\-\.\~\/@&;:=%$\?]+)#']              = 'Codes::render_link'      ;  // make URL clickable
                        $patterns_map['#(\s)(www\.[a-z0-9\-]+\.[a-z0-9_\-\.\~]+(?:/[^,< \r\n\)]*)?)#i'] = 'Codes::render_link'  ;  // web server url
                        $patterns_map['/http[s]*:\/\/www\.youtube\.com\/watch\?v=([a-zA-Z0-9_\-]+)[a-zA-Z0-9_\-&=]*/i'] = '<iframe class="youtube-player" type="text/html" width="445" height="364" src="http://www.youtube.com/embed/$1" frameborder="0"></iframe>'; // YouTube link
                        $patterns_map['/http[s]*:\/\/youtu\.be\/([a-zA-Z0-9_\-]+)/i']               = '<iframe class="youtube-player" type="text/html" width="445" height="364" src="http://www.youtube.com/embed/$1" frameborder="0"></iframe>'; // YouTube link too
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
			/*$dir = $context['path_to_root'].'codes/';
			if ($handle = Safe::opendir($dir)) {

				while (false !== ($file = Safe::readdir($handle))) {
				  if ($file == '..')
					continue;

				  if ($file == '.')
					continue;

				  //convention :
				  //get file only begining with code_
				  if (!(substr($file,0,5)=='code_'))
					continue;

				  include_once($dir.$file);

				  //get formatting code patterns from this class
				  $classname = stristr($file,'.',TRUE);
				  $code = new $classname;
				  $code->get_pattern($patterns);
				  unset($code);
				}
				Safe::closedir($handle);
			
		} // end setting $patterns*/

		}

		// include code extensions
//		include_once $context['path_to_root'].'scripts/scripts.php';
//		Scripts::load_scripts_at('codes/extensions');

		
                $text = Codes::process($text, $patterns_map);

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
	 * render a chart
	 *
	 * @param string chart data, in JSON format
	 * @param string chart parameters
	 * @return string the rendered text
	**/
	public static function &render_chart($data, $variant) {
		global $context;

		// split parameters
		$attributes = preg_split("/\s*,\s*/", $variant, 4);

		// set a default size
		if(!isset($attributes[0]))
			$attributes[0] = 320;
		if(!isset($attributes[1]))
			$attributes[1] = 240;

		// object attributes
		$width = $attributes[0];
		$height = $attributes[1];
		$flashvars = '';
		if(isset($attributes[2]))
			$flashvars = $attributes[2];

		// allow several charts to co-exist in the same page
		static $chart_index;
		if(!isset($chart_index))
			$chart_index = 1;
		else
			$chart_index++;

		$url = $context['url_to_home'].$context['url_to_root'].'included/browser/open-flash-chart.swf';
		$text = '<div id="open_flash_chart_'.$chart_index.'" class="no_print">Flash plugin or Javascript are turned off. Activate both and reload to view the object</div>'."\n";
			
		Page::insert_script(
			'var params = {};'."\n"
			.'params.base = "'.dirname($url).'/";'."\n"
			.'params.quality = "high";'."\n"
			.'params.wmode = "opaque";'."\n"
			.'params.allowscriptaccess = "always";'."\n"
			.'params.menu = "false";'."\n"
			.'params.flashvars = "'.$flashvars.'";'."\n"
			.'swfobject.embedSWF("'.$url.'", "open_flash_chart_'.$chart_index.'", "'.$width.'", "'.$height.'", "6", "'.$context['url_to_home'].$context['url_to_root'].'included/browser/expressinstall.swf", {"get-data":"get_open_flash_chart_'.$chart_index.'"}, params);'."\n"
			."\n"
			.'var chart_data_'.$chart_index.' = '.trim(str_replace(array('<br />', "\n"), ' ', $data)).';'."\n"
			."\n"
			.'function get_open_flash_chart_'.$chart_index.'() {'."\n"
			.'	return $.toJSON(chart_data_'.$chart_index.');'."\n"
			.'}'."\n"
			);

		return $text;

	}

	/**
	 * list clicks
	 *
	 * @param string web address that is monitored
	 * @return string the rendered text
	**/
	public static function &render_clicks($url) {
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
	 * render the cloud of tags
	 *
	 * @param string the number of items to list
	 * @return string the rendered text
	**/
	public static function &render_cloud($count=40) {
		global $context;

		// sanity check
		if(!(int)$count)
			$count = 40;

		// query the database and layout that stuff
		if(!$text =& Members::list_categories_by_count_for_anchor(NULL, 0, $count, 'cloud'))
			$text = '<p>'.i18n::s('No item has been found.').'</p>';

		// we have an array to format
		if(is_array($text))
			$text =& Skin::build_list($text, '2-columns');

		// job done
		return $text;

	}

	/**
	 * render a dynamic table
	 *
	 * @param string the table content
	 * @param string the variant, if any
	 * @return string the rendered text
	**/
	public static function &render_dynamic_table($id, $variant='inline') {
		global $context;

		// refresh on every page load
		Cache::poison();

		// get actual content
		include_once $context['path_to_root'].'tables/tables.php';

		// use SIMILE Exhibit
		if($variant == 'filter') {

			// load the SIMILE Exhibit javascript library in shared/global.php
			$context['javascript']['exhibit'] = TRUE;

			// load data
			$context['page_header'] .= "\n".'<link href="'.$context['url_to_root'].Tables::get_url($id, 'fetch_as_json').'" type="application/json" rel="exhibit/data" />';

			// exhibit data in a table
			$text = '<div ex:role="exhibit-view" ex:viewClass="Exhibit.TabularView" ex:columns="'.Tables::build($id, 'json-labels').'" ex:columnLabels="'.Tables::build($id, 'json-titles').'" ex:border="0" ex:cellSpacing="0" ex:cellPadding="0" ex:showToolbox="true" ></div>'."\n";

			// allow for filtering
			$facets = '<div class="exhibit-facet">'
				.'<div class="exhibit-facet-header"><span class="exhibit-facet-header-title">'.i18n::s('Filter').'</span></div>'
				.'<div class="exhibit-facet-body-frame" style="margin: 0 2px 1em 0;">'
				.'<div ex:role="facet" ex:facetClass="TextSearch" style="display: block;"></div>'
				.'</div></div>';

			// facets from first columns
			$facets .= Tables::build($id, 'json-facets');

			// filter and facets aside
			$context['components']['boxes'] .= $facets;

		// build sparkline
		} elseif($variant == 'bars') {
			$text = '<img border="0" align="baseline" hspace="0" src="'.$context['url_to_root'].Tables::get_url($id, 'fetch_as_png').'&order=0&gap;0.5" alt="" />';

		// buid a Flash chart
		} elseif($variant == 'chart') {

			// split parameters
			$attributes = preg_split("/\s*,\s*/", $id, 4);

			// set a default size
			if(!isset($attributes[1]))
				$attributes[1] = 480;
			if(!isset($attributes[2]))
				$attributes[2] = 360;

			// object attributes
			$width = $attributes[1];
			$height = $attributes[2];
			$flashvars = '';
			if(isset($attributes[3]))
				$flashvars = $attributes[3];

			// allow several charts to co-exist in the same page
			static $chart_index;
			if(!isset($chart_index))
				$chart_index = 1;
			else
				$chart_index++;

			// get data in the suitable format
			$data = Tables::build($attributes[0], 'chart');

			// load it through Javascript
			$url = $context['url_to_home'].$context['url_to_root'].'included/browser/open-flash-chart.swf';
			$text = '<div id="table_chart_'.$chart_index.'" class="no_print">Flash plugin or Javascript are turned off. Activate both and reload to view the object</div>'."\n";
			
			Page::insert_script(
				'var params = {};'."\n"
				.'params.base = "'.dirname($url).'/";'."\n"
				.'params.quality = "high";'."\n"
				.'params.wmode = "opaque";'."\n"
				.'params.allowscriptaccess = "always";'."\n"
				.'params.menu = "false";'."\n"
				.'params.flashvars = "'.$flashvars.'";'."\n"
				.'swfobject.embedSWF("'.$url.'", "table_chart_'.$chart_index.'", "'.$width.'", "'.$height.'", "6", "'.$context['url_to_home'].$context['url_to_root'].'included/browser/expressinstall.swf", {"get-data":"table_chart_'.$chart_index.'"}, params);'."\n"
				."\n"
				.'var chart_data_'.$chart_index.' = '.trim(str_replace(array('<br />', "\n"), ' ', $data)).';'."\n"
				."\n"
				.'function table_chart_'.$chart_index.'() {'."\n"
				.'	return $.toJSON(chart_data_'.$chart_index.');'."\n"
				.'}'."\n"
				);

		// build sparkline
		} elseif($variant == 'line') {
			$text = '<img border="0" align="baseline" hspace="0" src="'.$context['url_to_root'].Tables::get_url($id, 'fetch_as_png').'&order=2&gap=0.0" alt="" />';

		// we do the rendering ourselves
		} else
			$text = Tables::build($id, $variant);

		// put that into the web page
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
	 * embed an interactive object
	 *
	 * The id designates the target file.
	 * It can also include width and height of the target canvas, as in: '12, 100%, 250px'
	 *
	 * @param string id of the target file
	 * @return string the rendered string
	**/
	public static function &render_embed($id) {
		global $context;

		// split parameters
		$attributes = preg_split("/\s*,\s*/", $id, 4);
		$id = $attributes[0];

		// get the file
		if(!$item = Files::get($id)) {
			$output = '[embed='.$id.']';
			return $output;
		}

		// stream in a separate page
		if(isset($attributes[1]) && preg_match('/window/i', $attributes[1])) {
			if(!isset($attributes[2]))
				$attributes[2] = i18n::s('Play in a separate window');
			$output = '<a href="'.$context['url_to_home'].$context['url_to_root'].Files::get_url($item['id'], 'stream', $item['file_name']).'" onclick="window.open(this.href); return false;" class="button"><span>'.$attributes[2].'</span></a>';
			return $output;
		}

		// file extension
		$extension = strtolower(substr($item['file_name'], -3));

		// set a default size
		if(!isset($attributes[1])) {
			if(!strcmp($extension, 'gan'))
				$attributes[1] = '98%';
			elseif(!strcmp($extension, 'mm') && isset($context['skins_freemind_canvas_width']))
				$attributes[1] = $context['skins_freemind_canvas_width'];
			else
				$attributes[1] = 480;
		}
		if(!isset($attributes[2])) {
			if(!strcmp($extension, 'gan'))
				$attributes[2] = '300px';
			elseif(!strcmp($extension, 'mm') && isset($context['skins_freemind_canvas_height']))
				$attributes[2] = $context['skins_freemind_canvas_height'];
			else
				$attributes[2] = 360;
		}

		// object attributes
		$width = $attributes[1];
		$height = $attributes[2];
		$flashvars = '';
		if(isset($attributes[3]))
			$flashvars = $attributes[3];

		// rendering depends on file extension
		switch($extension) {

		// stream a video
		case '3gp':
		case 'flv':
		case 'm4v':
		case 'mov':
		case 'mp4':

			// a flash player to stream a flash video
			$flvplayer_url = $context['url_to_home'].$context['url_to_root'].'included/browser/player_flv_maxi.swf';

			// file is elsewhere
			if(isset($item['file_href']) && $item['file_href'])
				$url = $item['file_href'];

			// prevent leeching (the flv player will provide session cookie, etc)
			else
				$url = $context['url_to_home'].$context['url_to_root'].Files::get_url($item['id'], 'fetch', $item['file_name']);

			// pass parameters to the player
			if($flashvars)
				$flashvars = str_replace('autostart=true', 'autoplay=1', $flashvars).'&';
			$flashvars .= 'width='.$width.'&height='.$height;

			// if there is a static image for this video, use it
			if(isset($item['icon_url']) && $item['icon_url'])
				$flashvars .= '&startimage='.urlencode($item['icon_url']);

			// if there is a subtitle file for this video, use it
			if(isset($item['file_name']) && ($srt = 'files/'.str_replace(':', '/', $item['anchor']).'/'.str_replace('.'.$extension, '.srt', $item['file_name'])) && file_exists($context['path_to_root'].$srt))
				$flashvars .= '&srt=1&srturl='.urlencode($context['url_to_home'].$context['url_to_root'].$srt);

			// if there is a logo file in the skin, use it
			Skin::define_img_href('FLV_IMG_HREF', 'codes/flvplayer_logo.png', '');
			if(FLV_IMG_HREF)
				$flashvars .= '&top1='.urlencode(FLV_IMG_HREF.'|10|10');

			// rely on Flash
			if(Surfer::has_flash()) {

				// the full object is built in Javascript --see parameters at http://flv-player.net/players/maxi/documentation/
				$output = '<div id="flv_'.$item['id'].'" class="no_print">Flash plugin or Javascript are turned off. Activate both and reload to view the object</div>'."\n";
					
				Page::insert_script(
					'var flashvars = { flv:"'.$url.'", '.str_replace(array('&', '='), array('", ', ':"'), $flashvars).'", autoload:0, margin:1, showiconplay:1, playeralpha:50, iconplaybgalpha:30, showfullscreen:1, showloading:"always", ondoubleclick:"fullscreen" }'."\n"
					.'var params = { allowfullscreen: "true", allowscriptaccess: "always" }'."\n"
					.'var attributes = { id: "file_'.$item['id'].'", name: "file_'.$item['id'].'"}'."\n"
					.'swfobject.embedSWF("'.$flvplayer_url.'", "flv_'.$item['id'].'", "'.$width.'", "'.$height.'", "9", "'.$context['url_to_home'].$context['url_to_root'].'included/browser/expressinstall.swf", flashvars, params);'."\n"
					);

			// native support
 			} else {

				// <video> is HTML5, <object> is legacy
 				$output = '<video width="'.$width.'" height="'.$height.'" autoplay="" controls="" src="'.$url.'" >'."\n"
					.'	<object width="'.$width.'" height="'.$height.'" data="'.$url.'" type="'.Files::get_mime_type($item['file_name']).'">'."\n"
					.'		<param value="'.$url.'" name="movie" />'."\n"
					.'		<param value="true" name="allowFullScreen" />'."\n"
					.'		<param value="always" name="allowscriptaccess" />'."\n"
					.'		<a href="'.$url.'">No video playback capabilities, please download the file</a>'."\n"
 					.'	</object>'."\n"
					.'</video>'."\n";

			}

			// job done
			return $output;

		// a ganttproject timeline
		case 'gan':

			// where the file is
			$path = Files::get_path($item['anchor']).'/'.rawurlencode($item['file_name']);

			// we actually use a transformed version of the file
			$cache_id = Cache::hash($path).'.xml';

			// apply the transformation
			if(!file_exists($context['path_to_root'].$cache_id) || (filemtime($context['path_to_root'].$cache_id) < filemtime($context['path_to_root'].$path)) || (!$text = Safe::file_get_contents($context['path_to_root'].$cache_id))) {

				// transform from GanttProject to SIMILE Timeline
				$text = Files::transform_gan_to_simile($path);

				// put in cache
				Safe::file_put_contents($cache_id, $text);

			}

			// load the SIMILE Timeline javascript library in shared/global.php
			$context['javascript']['timeline'] = TRUE;

			// cache would kill the loading of the library
			cache::poison();

			// 1 week ago
			$now = gmdate('M d Y H:i:s', time()-7*24*60*60);

			// load the right file
			$output = '<div id="gantt" style="height: '.$height.'; width: '.$width.'; border: 1px solid #aaa; font-family: Trebuchet MS, Helvetica, Arial, sans serif; font-size: 8pt"></div>'."\n";
			
			Page::insert_script(
				'var simile_handle;'."\n"
				.'function onLoad() {'."\n"
				.'  var eventSource = new Timeline.DefaultEventSource();'."\n"
				.'	var theme = Timeline.ClassicTheme.create();'."\n"
				.'            theme.event.bubble.width = 350;'."\n"
				.'            theme.event.bubble.height = 300;'."\n"
				.'  var bandInfos = ['."\n"
				.'    Timeline.createBandInfo({'."\n"
				.'        eventSource:    eventSource,'."\n"
				.'        date:           "'.$now.'",'."\n"
				.'        width:          "80%",'."\n"
				.'        intervalUnit:   Timeline.DateTime.WEEK,'."\n"
				.'        intervalPixels: 200,'."\n"
				.'		  theme:          theme,'."\n"
				.'        layout:         "original"  // original, overview, detailed'."\n"
				.'    }),'."\n"
				.'    Timeline.createBandInfo({'."\n"
				.'        showEventText: false,'."\n"
				.'        trackHeight: 0.5,'."\n"
				.'        trackGap: 0.2,'."\n"
				.'        eventSource:    eventSource,'."\n"
				.'        date:           "'.$now.'",'."\n"
				.'        width:          "20%",'."\n"
				.'        intervalUnit:   Timeline.DateTime.MONTH,'."\n"
				.'        intervalPixels: 50'."\n"
				.'    })'."\n"
				.'  ];'."\n"
				.'  bandInfos[1].syncWith = 0;'."\n"
				.'  bandInfos[1].highlight = true;'."\n"
				.'  bandInfos[1].eventPainter.setLayout(bandInfos[0].eventPainter.getLayout());'."\n"
				.'  simile_handle = Timeline.create(document.getElementById("gantt"), bandInfos, Timeline.HORIZONTAL);'."\n"
				.'	simile_handle.showLoadingMessage();'."\n"
				.'  Timeline.loadXML("'.$context['url_to_home'].$context['url_to_root'].$cache_id.'", function(xml, url) { eventSource.loadXML(xml, url); });'."\n"
				.'	simile_handle.hideLoadingMessage();'."\n"
				.'}'."\n"
				."\n"
				.'var resizeTimerID = null;'."\n"
				.'function onResize() {'."\n"
				.'    if (resizeTimerID == null) {'."\n"
				.'        resizeTimerID = window.setTimeout(function() {'."\n"
				.'            resizeTimerID = null;'."\n"
				.'            simile_handle.layout();'."\n"
				.'        }, 500);'."\n"
				.'    }'."\n"
				.'}'."\n"
				."\n"
				.'// observe page major events'."\n"
				.'$(document).ready( onLoad);'."\n"
				.'$(window).resize(onResize);'."\n"
				);

			// job done
			return $output;

		// a Freemind map
		case 'mm':

			// if we have an external reference, use it
			if(isset($item['file_href']) && $item['file_href']) {
				$target_href = $item['file_href'];

			// else redirect to ourself
			} else {

				// ensure a valid file name
				$file_name = utf8::to_ascii($item['file_name']);

				// where the file is
				$path = Files::get_path($item['anchor']).'/'.rawurlencode($item['file_name']);

				// map the file on the regular web space
				$url_prefix = $context['url_to_home'].$context['url_to_root'];

				// redirect to the actual file
				$target_href = $url_prefix.$path;
			}

			// allow several viewers to co-exist in the same page
			static $freemind_viewer_index;
			if(!isset($freemind_viewer_index))
				$freemind_viewer_index = 1;
			else
				$freemind_viewer_index++;

			// load flash player
			$url = $context['url_to_home'].$context['url_to_root'].'included/browser/visorFreemind.swf';

			// variables
			$flashvars = 'initLoadFile='.$target_href.'&openUrl=_self';

			$output = '<div id="freemind_viewer_'.$freemind_viewer_index.'">Flash plugin or Javascript are turned off. Activate both and reload to view the object</div>'."\n";
			
			Page::insert_script(
				'var params = {};'."\n"
				.'params.base = "'.dirname($url).'/";'."\n"
				.'params.quality = "high";'."\n"
				.'params.wmode = "transparent";'."\n"
				.'params.menu = "false";'."\n"
				.'params.flashvars = "'.$flashvars.'";'."\n"
				.'swfobject.embedSWF("'.$url.'", "freemind_viewer_'.$freemind_viewer_index.'", "'.$width.'", "'.$height.'", "6", "'.$context['url_to_home'].$context['url_to_root'].'included/browser/expressinstall.swf", false, params);'."\n"
				);

			// offer to download a copy of the map
			$menu = array($target_href => i18n::s('Browse this map with Freemind'));

			// display menu commands below the viewer
			$output .= Skin::build_list($menu, 'menu_bar');

			// job done
			return $output;

		// native flash
		case 'swf':

			// where to get the file
			if(isset($item['file_href']) && $item['file_href'])
				$url = $item['file_href'];

			// we provide the native file because of basename
			else
				$url = $context['url_to_home'].$context['url_to_root'].'files/'.str_replace(':', '/', $item['anchor']).'/'.rawurlencode($item['file_name']);

			$output = '<div id="swf_'.$item['id'].'" class="no_print">Flash plugin or Javascript are turned off. Activate both and reload to view the object</div>'."\n";
				
			Page::insert_script(
				'var params = {};'."\n"
				.'params.base = "'.dirname($url).'/";'."\n"
				.'params.quality = "high";'."\n"
				.'params.wmode = "transparent";'."\n"
				.'params.allowfullscreen = "true";'."\n"
				.'params.allowscriptaccess = "always";'."\n"
				.'params.flashvars = "'.$flashvars.'";'."\n"
				.'swfobject.embedSWF("'.$url.'", "swf_'.$item['id'].'", "'.$width.'", "'.$height.'", "6", "'.$context['url_to_home'].$context['url_to_root'].'included/browser/expressinstall.swf", false, params);'."\n"
				);
			
			return $output;

		// link to file page
		default:

			// link label
			$text = Skin::strip( $item['title']?$item['title']:str_replace('_', ' ', $item['file_name']) );

			// make a link to the target page
			$url = Files::get_permalink($item);

			// return a complete anchor
			$output =& Skin::build_link($url, $text);
			return $output;

		}

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
	 * render a graphviz
	 *
	 * @param string the text
	 * @param string the variant
	 * @return string the rendered text
	**/
	public static function &render_graphviz($text, $variant='digraph') {
		global $context;

		// sanity check
		if(!$text)
			$text = 'Hello->World!';

		// remove tags put by WYSIWYG editors
		$text = strip_tags(str_replace(array('&gt;', '&lt;', '&amp;', '&quot;', '\"'), array('>', '<', '&', '"', '"'), str_replace(array('<br />', '</p>'), "\n", $text)));

		// build the .dot content
		switch($variant) {
		case 'digraph':
		default:
			$text = 'digraph G { '.$text.' }'."\n";
			break;
		}

		// id for this object
		$hash = md5($text);

		// path to cached files
		$path = $context['path_to_root'].'temporary/graphviz.';

		// we cache content
		if($content = Safe::file_get_contents($path.$hash.'.html'))
			return $content;

		// build a .dot file
		if(!Safe::file_put_contents($path.$hash.'.dot', $text)) {
			$content = '[error writing .dot file]';
			return $content;
		}

		// process the .dot file
		if(isset($context['dot.command']))
			$command = $context['dot.command'];
		else
			$command = 'dot';
//		$font = '"/System/Library/Fonts/Times.dfont"';
//		$command = '/sw/bin/dot -v -Nfontname='.$font
		$command .= ' -Tcmapx -o "'.$path.$hash.'.map"'
			.' -Tpng -o "'.$path.$hash.'.png"'
			.' "'.$path.$hash.'.dot"';

		if(Safe::shell_exec($command) == NULL) {
			$content = '[error while using graphviz]';
			return $content;
		}

		// produce the HTML
		$content = '<img src="'.$context['url_to_root'].'temporary/graphviz.'.$hash.'.png" usemap="#mainmap" />';
		$content .= Safe::file_get_contents($path.$hash.'.map');

		// put in cache
		Safe::file_put_contents($path.$hash.'.html', $content);

		// done
		return $content;
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
	public static function render_hidden($text, $variant) {
            
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
		if(Surfer::is_member() && ($variant == 'authenticated'))
			return $text;

		// tough luck
		$text = '';
		return $text;
	}

	/**
	 * render an iframe
	 *
	 * @param string URL to be embedded
	 * @param string iframe parameters
	 * @return string the rendered text
	**/
	public static function &render_iframe($url, $variant) {
		global $context;

		// split parameters
		$attributes = preg_split("/\s*,\s*/", $variant, 2);

		// set a default size
		if(!isset($attributes[0]))
			$attributes[0] = 320;
		if(!isset($attributes[1]))
			$attributes[1] = 240;

		$text = '<iframe src="'.$url.'" style="width: '.$attributes[0].'px; height: '.$attributes[1].'px" scrolling="no" marginwidth="0" marginheight="0" frameborder="0" vspace="0" hspace="0">'."\n"
			.i18n::s('Your browser does not accept iframes')
			.'</iframe>';

		return $text;

	}
        
        public static function render_lang($lang, $text) {
            
            $text = Codes::fix_tags($text);
            
            return i18n::filter($text, $lang);
        }
        
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
	 * render a list
	 *
	 * @param string the list content
	 * @param string the variant, if any
	 * @return string the rendered text
	**/
	public static function render_list($content, $variant='') {
		global $context;

		if(!$content = trim($content)) {
			$output = NULL;
			return $output;
		}

		// preserve existing list, if any --coming from implied beautification
		if(preg_match('#^<ul>#', $content) && preg_match('#</ul>$#', $content))
			$items = preg_replace(array('#^<ul>#', '#</ul>$#'), '', $content);

		// split items
		else {
			$content = preg_replace(array("/<br \/>\n-/s", "/\n-/s", "/^-/", '/\[\*\]/'), '[*]', $content);
			$items = '<li>'.join('</li><li>', preg_split("/\[\*\]/s", $content, -1, PREG_SPLIT_NO_EMPTY)).'</li>';
		}

		// an ordinary bulleted list
		if(!$variant) {
			$output = '<ul>'.$items.'</ul>';
			return $output;

		// style a bulleted list, but ensure it's not numbered '1 incremental'
		} elseif($variant && (strlen($variant) > 1) && ($variant[1] != ' ')) {
			$output = '<ul class="'.$variant.'">'.$items.'</ul>';
			return $output;
		}

		// type has been deprecated, use styles
		$style = '';
		switch($variant) {
		case 'a':
			$style = 'style="list-style-type: lower-alpha"';
			break;

		case 'A':
			$style = 'style="list-style-type: upper-alpha"';
			break;

		case 'i':
			$style = 'style="list-style-type: lower-roman"';
			break;

		case 'I':
			$style = 'style="list-style-type: upper-roman"';
			break;

		default:
			$style = 'class="'.encode_field($variant).'"';
			break;

		}

		// a numbered list with style
		$output = '<ol '.$style.'>'.$items.'</ol>';
		return $output;
	}

	/**
	 * render a location
	 *
	 * @param string the id, with possible options or variant
	 * @return string the rendered text
	**/
	public static function render_location($id) {
		global $context;

		// the required library
		include_once $context['path_to_root'].'locations/locations.php';

		// check all args
		$attributes = preg_split("/\s*,\s*/", $id, 3);

		// [location=latitude, longitude, label]
		if(count($attributes) === 3) {
			$item = array();
			$item['latitude'] = $attributes[0];
			$item['longitude'] = $attributes[1];
			$item['description'] = $attributes[2];

		// [location=id, label] or [location=id]
		} else {
			$id = $attributes[0];

			// a record is mandatory
			if(!$item = Locations::get($id)) {
				if(Surfer::is_member()) {
					$output = '&#91;location='.$id.']';
					return $output;
				} else {
					$output = '';
					return $output;
				}
			}

			// build a small dynamic image if we cannot use Google maps
			if(!isset($context['google_api_key']) || !$context['google_api_key']) {
				$output = BR.'<img src="'.$context['url_to_root'].'locations/map_on_earth.php?id='.$item['id'].'" width="310" height="155" alt="'.$item['geo_position'].'" />'.BR;
				return $output;
			}

			// use provided text, if any
			if(isset($attributes[1]))
				$item['description'] = $attributes[1].BR.$item['description'];

		}

		// map on Google
		$output = Locations::map_on_google(array($item));
		return $output;

	}

	/**
	 * render several locations
	 *
	 * @param string 'all' or 'users'
	 * @return string the rendered text
	**/
	public static function render_locations($id='all') {
		global $context;

		// the required library
		include_once $context['path_to_root'].'locations/locations.php';

		// get markers
		$items = array();
		switch($id) {
		case 'all':
			$items = Locations::list_by_date(0, 100, 'raw');
			break;

		case 'users':
			$items = Locations::list_users_by_date(0, 100, 'raw');
			break;

		default:
			if(Surfer::is_member()) {
				$output = '&#91;locations='.$id.']';
				return $output;
			} else {
				$output = '';
				return $output;
			}
		}

		// integrate with google maps
		$output = Locations::map_on_google($items);
		return $output;

	}

	/**
	 * render some animated news
	 *
	 * We have replaced the old fat object by a lean, clean, and valid XHTML solution.
	 * However, as explained by Jeffrey Zeldmann in his book "designing with web standards",
	 * it may happen that this way of doing don't display correctly sometimes.
	 *
	 * @param string the variant - default is 'flash'
	 * @return string the rendered text
	**/
	public static function render_news($variant) {
		global $context;

		switch($variant) {
		case 'flash':

			// sanity check
			if(!isset($context['root_flash_at_home']) || ($context['root_flash_at_home'] != 'Y'))
				$text = '';

			else {
				$url = $context['url_to_home'].$context['url_to_root'].'feeds/flash/slashdot.php';
				$flashvars = '';
				$text = '<div id="local_news" class="no_print">Flash plugin or Javascript are turned off. Activate both and reload to view the object</div>'."\n";
					
				Page::insert_script(
					'var params = {};'."\n"
					.'params.base = "'.dirname($url).'/";'."\n"
					.'params.quality = "high";'."\n"
					.'params.wmode = "transparent";'."\n"
					.'params.menu = "false";'."\n"
					.'params.flashvars = "'.$flashvars.'";'."\n"
					.'swfobject.embedSWF("'.$url.'", "local_news", "80%", "50", "6", "'.$context['url_to_home'].$context['url_to_root'].'included/browser/expressinstall.swf", false, params);'."\n"
					);
			}

			return $text;

		case 'dummy':
			$text = 'hello world';
			return $text;

		default:
			$text = '??'.$variant.'??';
			return $text;
		}
	}

	/**
	 * integrate content of a newsfeed
	 *
	 * @param string address of the newsfeed to get
	 * @return string the rendered text
	**/
	public static function render_newsfeed($url, $variant='ajax') {
		global $context;

		// we allow multiple calls
		static $count;
		if(!isset($count))
			$count = 1;
		else
			$count++;

		switch($variant) {

		case 'ajax': // asynchronous loading
		default:

			$text = '<div id="newsfeed_'.$count.'" class="no_print"></div>'."\n";
			Page::insert_script('$(function() { Yacs.spin("newsfeed_'.$count.'"); Yacs.call( { method: \'feed.proxy\', params: { url: \''.$url.'\' }, id: 1 }, function(s) { if(s.text) { $("#newsfeed_'.$count.'").html(s.text.toString()); } else { $("#newsfeed_'.$count.'").html("***error***"); } } ) } );');			

			return $text;

		case 'embed': // integrate newsfeed into the page

			include_once $context['path_to_root'].'feeds/proxy_hook.php';
			$parameters = array('url' => $url);
			if($output = Proxy_hook::serve($parameters))
				$text = $output['text'];

			return $text;

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
	 * - sound - launch dewplayer
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
				$output =& Skin::build_link($url, $text, $type);
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
				$output =& Skin::build_link($url, $text, 'article');

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
				$output =& Skin::build_link($url, $text, $type);
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
				$output =& Skin::build_link($url, $text, 'category');

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
				$output =& Skin::build_link($url, $text, 'basic');
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
					$output = $attributes[1].'<p class="details">'.i18n::s('[this file has been deleted]').'</p>';
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
						$text .= ' <p class="details">'.i18n::s('[this file has been replaced]').'</p>';
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
					$output = $attributes[1].'<p class="details">'.i18n::s('[this file has been deleted]').'</p>';
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
							$text .= '<p class="details">'.i18n::s('[this file has been replaced]').'</p>';
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
					$link = $context['url_to_root'].$attributes[0];

				// direct use of this link
				else
					$link = $image['link_url'];

			// get the <img ... /> element
			} else {

				// do not append poor titles to inline images
				if($variant == 'inline')
					$title = '';

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
			   $output =& Skin::build_image($variant, $href, $title, $link, $id);
			else
			   $output =& Skin::build_image($variant, $href, $title, $link);

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
							$link = $context['url_to_root'].$attributes[0];

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
					$label =& Skin::build_image($variant, $href, $title, $link);

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
				$output =& Skin::build_link($url, $text, 'next');
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
				$output =& Skin::build_link($url, $text, 'previous');
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
				$output =& Skin::build_link($url, $text, $type);
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
				$output =& Skin::build_link($url, $text, $type);
			}

			return $output;

		// render a sound object
		case 'sound':

			// maybe an alternate title has been provided
			$attributes = preg_split("/\s*,\s*/", $id, 2);
			$id = $attributes[0];
			$flashvars = '';
			if(isset($attributes[1]))
				$flashvars = $attributes[1];

			// get the file
			if(!$item = Files::get($id)) {
				$output = '[sound='.$id.']';
				return $output;
			}

			// where to get the file
			if(isset($item['file_href']) && $item['file_href'])
				$url = $item['file_href'];
			else
				$url = $context['url_to_home'].$context['url_to_root'].'files/'.str_replace(':', '/', $item['anchor']).'/'.rawurlencode($item['file_name']);

			// several ways to play flash
			switch(strtolower(substr(strrchr($url, '.'), 1))) {

			// stream a sound file
			case 'mp3':

				// a flash player to stream a sound
				$dewplayer_url = $context['url_to_root'].'included/browser/dewplayer.swf';
				if($flashvars)
					$flashvars = 'son='.$url.'&'.$flashvars;
				else
					$flashvars = 'son='.$url;

				$output = '<div id="sound_'.$item['id'].'" class="no_print">Flash plugin or Javascript are turned off. Activate both and reload to view the object</div>'."\n";
				
				Page::insert_script(
					'var params = {};'."\n"
					.'params.base = "'.dirname($url).'/";'."\n"
					.'params.quality = "high";'."\n"
					.'params.wmode = "transparent";'."\n"
					.'params.menu = "false";'."\n"
					.'params.flashvars = "'.$flashvars.'";'."\n"
					.'swfobject.embedSWF("'.$dewplayer_url.'", "sound_'.$item['id'].'", "200", "20", "6", "'.$context['url_to_home'].$context['url_to_root'].'included/browser/expressinstall.swf", false, params);'."\n"
					);
				return $output;

			// link to file page
			default:

				// link label
				$text = Skin::strip( $item['title']?$item['title']:str_replace('_', ' ', $item['file_name']) );

				// make a link to the target page
				$url = Files::get_download_url($item);

				// return a complete anchor
				$output =& Skin::build_link($url, $text, 'basic');
				return $output;

			}

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
				$output =& Skin::build_link($url, $text, $type);
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
			$output =& $context[$name];
			return $output;
		}

		if(!strncmp($name, 'site_', 5) && isset($context[$name])) {
			$output =& $context[$name];
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
			$items =& Articles::list_for_anchor_by('random', $anchors, 0, 1, 'raw');

		// scope is limited to one author
		} elseif(!strncmp($anchor, 'user:', 5))
			$items =& Articles::list_for_author_by('random', str_replace('user:', '', $anchor), 0, 1, 'raw');

		// consider all pages
		else
			$items =& Articles::list_by('random', 0, 1, 'raw');

		// we have an array to format
 		if($items) {
 			foreach($items as $id => $item) {

				// make a link to the target page
				$link = Articles::get_permalink($item);
				if(!$label)
					$label = Skin::strip($item['title']);
				$text =& Skin::build_link($link, $label, 'article');

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
	 * render a compact list of hits
	 *
	 * @param string the anchor (e.g. 'section:123')
	 * @param string layout to use
	 * @return string the rendered text
	**/
	public static function render_read($layout='hits', $anchor='') {
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
			$text = Articles::list_for_anchor_by('hits', $anchors, 0, $count, $layout);

		// scope is limited to pages of one surfer
		} elseif(strpos($anchor, 'user:') === 0)
			$text = Articles::list_for_user_by('hits', substr($anchor, 5), 0, $count, $layout);

		// consider all pages
		if(!$text)
			$text = Articles::list_by('hits', 0, $count, $layout);

		// we have an array to format
		if(is_array($text))
			$text = Skin::build_list($text, $layout);

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
	 * render tweetmeme button
	 *
	 * @return string the rendered text
	**/
	public static function &render_retweet() {
		global $context;

		// we return some text --$context['self_url'] already has $context['url_to_root'] in it
		Page::insert_script('tweetmeme_url = "'.$context['url_to_home'].$context['self_url'].'";');			
		Page::defer_script("http://tweetmeme.com/i/scripts/button.js");			

		// job done
		return $text;
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
	 * render a table
	 *
	 * @param string the table content
	 * @param string the variant, if any
	 * @return string the rendered text
	**/
	public static function render_static_table($content, $variant='') {
		global $context;

		// we are providing inline tables
		if($variant)
			$variant = 'inline '.$variant;
		else
			$variant = 'inline';

		// do we have headers to proceed?
		$in_body = !preg_match('/\[body\]/i', $content);

		// start at first line, except if headers have to be printed first
		if($in_body)
			$count = 1;
		else
			$count = 2;

		// split lines
		$rows = explode("\n", $content);
		if(!is_array($rows))
			return '';

		// one row per line - cells are separated by |, \t, or 2 spaces
		$text =& Skin::table_prefix($variant);
		foreach($rows as $row) {

			// skip blank lines
			if(!$row)
				continue;

			// header row
			if(!$in_body) {
				if(preg_match('/\[body\]/i', $row))
					$in_body = true;
				else
					$text .= Skin::table_row(preg_split("/([\|\t]| "." )/", $row), 'header');

			// body row
			} else
				$text .= Skin::table_row(preg_split("/([\|\t]| "." )/", $row), $count++);

		}

		// return the complete table
		$text .= Skin::table_suffix();
		return $text;
	}

	/**
	 * render a table of links
	 *
	 * @param string the variant
	 * @return string the rendered text
	**/
	public static function &render_table_of($variant) {
		global $context;

		// nothing to return yet
		$output = '';

		// list of questions for a FAQ
		if($variant == 'questions') {

			// only if the table is not empty
			global $codes_toq;
			if(isset($codes_toq) && $codes_toq) {

				// to be rendered by css, using selector .toq_box ul, etc.
				$text = '<ul>'."\n";
				foreach($codes_toq as $link)
					$text .= '<li>'.$link.'</li>'."\n";
				$text .= '</ul>'."\n";

				$output =& Skin::build_box('', $text, 'toq');

			}

		// list of titles
		} else {

			// only if the table is not empty
			global $codes_toc;
			if(isset($codes_toc) && $codes_toc) {

				// to be rendered by css, using selector .toc_box ul, etc.
				// <ul>
				// <li>1. link</li> 		0 -> 1
				// <li>1. link				1 -> 1
				//		<ul>
				//		<li>2. link</li>	1 -> 2
				//		<li>2. link</li>	2 -> 2
				//		</ul></li>
				// <li>1. link		 		2 -> 1
				//		<ul>
				//		<li>2. link</li>	1 -> 2
				//		<li>2. link</li>	2 -> 2
				//		</ul></li>
				// </ul>
				$text ='';
				$previous_level = 0;
				foreach($codes_toc as $attributes) {
					list($level, $link) = $attributes;

					if($previous_level == $level)
						$text .= '</li>'."\n";

					else {

						if($previous_level < $level) {
							$text .= '<ul>';
							$previous_level++;
							while($previous_level < $level) {
								$text .= '<li><ul>'."\n";
								$previous_level++;
							}
						}

						if($previous_level > $level) {
							$text .= '</li>';
							while($previous_level > $level) {
								$text .= '</ul></li>'."\n";
								$previous_level--;
							}
						}
					}

					$text .= '<li>'.$link;

				}

				if($previous_level > 0) {
					$text .= '</li>';
					while($previous_level > 0) {
						if($previous_level > 1)
							$text .= '</ul></li>'."\n";
						else
							$text .= '</ul>'."\n";
						$previous_level--;
					}
				}

				$output =& Skin::build_box('', $text, 'toc');

			}

		}

		return $output;
	}

	/**
	 * render a title, a sub-title, or a question
	 *
	 * @param string the text
	 * @param string the variant
	 * @return string the rendered text
	**/
	public static function &render_title($text, $variant) {
		global $codes_toc, $codes_toq, $context;

		// remember questions
		if($variant == 'question') {
			$index = count($codes_toq)+1;
			$id = 'question_'.$index;
			$url = $context['self_url'].'#'.$id;
			$codes_toq[] = Skin::build_link($url, ucfirst($text), 'basic');
			$text = QUESTION_FLAG.$text;

		// remember level 1 titles ([title]...[/title] or [header1]...[/header1])
		} elseif($variant == 'header1') {
			$index = count($codes_toc)+1;
			$id = 'title_'.$index;
			$url = $context['self_url'].'#'.$id;
			$codes_toc[] = array(1, Skin::build_link($url, ucfirst($text), 'basic'));

		// remember level 2 titles ([subtitle]...[/subtitle] or [header2]...[/header2])
		} elseif($variant == 'header2') {
			$index = count($codes_toc)+1;
			$id = 'title_'.$index;
			$url = $context['self_url'].'#'.$id;
			$codes_toc[] = array(2, Skin::build_link($url, ucfirst($text), 'basic'));

		// remember level 3 titles
		} elseif($variant == 'header3') {
			$index = count($codes_toc)+1;
			$id = 'title_'.$index;
			$url = $context['self_url'].'#'.$id;
			$codes_toc[] = array(3, Skin::build_link($url, ucfirst($text), 'basic'));

		// remember level 4 titles
		} elseif($variant == 'header4') {
			$index = count($codes_toc)+1;
			$id = 'title_'.$index;
			$url = $context['self_url'].'#'.$id;
			$codes_toc[] = array(4, Skin::build_link($url, ucfirst($text), 'basic'));

		// remember level 5 titles
		} elseif($variant == 'header5') {
			$index = count($codes_toc)+1;
			$id = 'title_'.$index;
			$url = $context['self_url'].'#'.$id;
			$codes_toc[] = array(5, Skin::build_link($url, ucfirst($text), 'basic'));
		}

		// the rendered text
		$output =& Skin::build_block($text, $variant, $id);
		return $output;
	}

	/**
	 * render twitter profile
	 *
	 * @param string twitter id to display, plus optional parameters, if any
	 * @return string the rendered text
	**/
	public static function &render_twitter($id) {
		global $context;

		// up to 4 parameters: id, width, height, styles
		$attributes = preg_split("/\s*,\s*/", $id, 4);
		$id = $attributes[0];

		// width
		if(isset($attributes[1]))
			$width = $attributes[1];
		else
			$width = 250;

		// height
		if(isset($attributes[2]))
			$height = $attributes[2];
		else
			$height = 300;

		// theme
		if(isset($attributes[3]))
			$theme = $attributes[3];
		else
			$theme = 'theme: { shell: {'."\n"
				.'      background: "#3082af",'."\n"
				.'      color: "#ffffff"'."\n"
				.'    },'."\n"
				.'    tweets: {'."\n"
				.'      background: "#ffffff",'."\n"
				.'      color: "#444444",'."\n"
				.'      links: "#1985b5"'."\n"
				.'    }}';

		// allow multiple widgets
		static $count;
		if(!isset($count))
			$count = 1;
		else
			$count++;

		// we return some text --$context['self_url'] already has $context['url_to_root'] in it
		$text = '<div id="twitter_'.$count.'"></div>'."\n"
			.'<script type="text/javascript">'."\n"
			.'$(function() { $("#twitter_'.$count.'").liveTwitter("'.$id.'", {mode: "user_timeline"}); });'."\n"
			.'</script>';

		// job done
		return $text;
	}

	/**
	 * render twitter search
	 *
	 * @param string twitter searched keywords, plus optional parameters, if any
	 * @return string the rendered text
	**/
	public static function &render_twitter_search($id) {
		global $context;

		// up to 4 parameters: id, width, height, styles
		$attributes = preg_split("/\s*,\s*/", $id, 4);
		$id = $attributes[0];

		// width
		if(isset($attributes[1]))
			$width = $attributes[1];
		else
			$width = 250;

		// height
		if(isset($attributes[2]))
			$height = $attributes[2];
		else
			$height = 300;

		// allow multiple widgets
		static $count;
		if(!isset($count))
			$count = 1;
		else
			$count++;

		// $context['self_url'] already has $context['url_to_root'] in it
		$text = '<div id="tsearch_'.$count.'"></div>'."\n"
			.'<script type="text/javascript">'."\n"
			.'$(function() { $("#tsearch_'.$count.'").liveTwitter("'.str_replace('"', '', $id).'"); });'."\n"
			.'</script>';

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
	public static function &render_users($anchor='') {
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
			$text =& Skin::build_list($text, 'compact');

		// job done
		return $text;
	}

	/**
	 * render a compact list of voted pages
	 *
	 * @param string the anchor (e.g. 'section:123')
	 * @param string layout to use
	 * @return string the rendered text
	**/
	public static function &render_voted($anchor='', $layout='simple') {
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
			$text =& Articles::list_for_anchor_by('rating', $anchors, 0, $count, $layout);

		// scope is limited to pages of one surfer
		} elseif(strpos($anchor, 'user:') === 0)
			$text =& Articles::list_for_user_by('rating', substr($anchor, 5), 0, $count, $layout);

		// consider all pages
		else
			$text =& Articles::list_by('rating', 0, $count, $layout);

		// we have an array to format
		if(is_array($text))
			$text =& Skin::build_list($text, $layout);

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
		$url = 'http://'.$language.'.wikipedia.org/wiki/Special:Search?search='.preg_replace('[\s]', '_', $id);

		// return a complete anchor
		$output = Skin::build_link($url, $text, 'wikipedia');
		return $output;

	}


	/**
	 * remove YACS codes from a string
	 *
	 * @param string embedding YACS codes
	 * @param boolean FALSE to remove only only pairing codes, TRUE otherwise
	 * @return a purged string
	 */
	public static function &strip($text, $suppress_all_brackets=TRUE) {
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
