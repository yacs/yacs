<?php
/**
 * Static functions used to produce HTML code.
 *
 * @todo potential bug on creating a link out of a flag [link=[ca]]www.there.com[/link] ?
 *
 * Declare here all things used to build some HTML, but only HTML-related things.
 *
 * @link http://www.joemaller.com script to protect e-mail addresses from spam
 *
 * @author Bernard Paques
 * @tester Olivier
 * @tester Nuxwin
 * @tester ClaireFormatrice
 * @tester Jos&eacute;
 * @tester Mordread Wallas
 * @tester Ghjmora
 * @tester Thierry Pinelli (ThierryP)
 * @tester Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Skin_Skeleton {

	/**
	 * build a box part of one accordion
	 *
	 * @param string the box title, if any
	 * @param string the box content
	 * @param string the accordion id, used as CSS class
	 * @return the HTML to display
	 */
	function &build_accordion_box($title, $content, $id) {
		global $context;

		// we need a clickable title
		if(!$title)
			$title = i18n::s('Click to slide');

		// maybe we have an image to enhance rendering
		$img = '';

		// the icon to close accordion boxes
		Skin::define_img_href('ACCORDION_CLOSE_IMG_HREF', 'layouts/accordion_minus.jpg');

		// the icon to open accordion boxes
		Skin::define_img_href('ACCORDION_OPEN_IMG_HREF', 'layouts/accordion_plus.jpg');

		// detect first box of the accordion
		static $fused;
		if(!isset($fused))
			$fused = array();

		// first box is always open
		if(!isset($fused[ $id ])) {

			$style = '';

			if(ACCORDION_CLOSE_IMG_HREF)
				$img = '<img src="'.ACCORDION_CLOSE_IMG_HREF.'" alt="" title="'.encode_field(i18n::s('Click to slide')).'" class="handle" /> ';

			// close following boxes
			$fused[ $id ] = TRUE;

		// following boxes are closed
		} else {

			$style = ' style="display: none"';

			if(ACCORDION_OPEN_IMG_HREF)
				$img = '<img src="'.ACCORDION_OPEN_IMG_HREF.'" alt="" title="'.encode_field(i18n::s('Click to slide')).'" class="handle" /> ';

		}

		// Yacs.toggle_folder() is in shared/yacs.js -- div.accordion_content div is required for slide effect to work
		$text = '<div class="accordion_handle '.$id.'"><a href="#" class="accordion_link" onclick="javascript:Yacs.toggle_accordion(this, \''.ACCORDION_OPEN_IMG_HREF.'\', \''.ACCORDION_CLOSE_IMG_HREF.'\', \''.$id.'\'); return false;">'.$img.$title.'</a>'
			.'<div class="accordion_content"'.$style.'><div>'.$content."</div></div></div>\n";

		// pass by reference
		return $text;

	}

	/**
	 * build the field to restrict access
	 *
	 * @see articles/edit.php
	 *
	 * @param array the item to be edited
	 * @return string tags to be put in the form
	 */
	function build_active_set_input($item) {
		global $context;

		// maybe a public item
		$text = '<input type="radio" name="active_set" value="Y" accesskey="v"';
		if(!isset($item['active_set']) || ($item['active_set'] == 'Y'))
			$text .= ' checked="checked"';
		$text .= '/> '.i18n::s('Public - Everybody, including anonymous surfers').BR;

		// maybe a restricted item
		$text .= '<input type="radio" name="active_set" value="R"';
		if(isset($item['active_set']) && ($item['active_set'] == 'R'))
			$text .= ' checked="checked"';
		$text .= '/> '.i18n::s('Community - Access is granted to any identified surfer').BR;

		// or a hidden item
		$text .= '<input type="radio" name="active_set" value="N"';
		if(isset($item['active_set']) && ($item['active_set'] == 'N'))
			$text .= ' checked="checked"';
		$text .= '/> '.i18n::s('Private - Access is restricted to selected persons')."\n";

		return $text;
	}

	/**
	 * build the hint related to access restrictions
	 *
	 * @param object anchor of the edited item, if any
	 * @return string tags to be put in the form
	 */
	function build_active_set_hint($anchor) {
		global $context;

		// combine this with inherited access right
		if(is_object($anchor) && $anchor->is_hidden())
			$hint = i18n::s('Parent is private, and this will be re-enforced anyway');
		elseif(is_object($anchor) && !$anchor->is_public())
			$hint = i18n::s('Parent is not public, and this will be re-enforced anyway');
		else
			$hint = '';

		return $hint;

	}

	/**
	 * build an assistant-like bottom of the page
	 *
	 * @param mixed text to be put right after the horizontal separation
	 * @param array a bar of commands to be put in a menu
	 * @param mixed additional content put after the menu
	 * @param string current value of tags
	 * @return string text to be put in the rendered page
	 */
	function &build_assistant_bottom($prefix='', $menu=NULL, $suffix='', $tags=NULL) {
		global $context;

		// we return some text
		$text = '';

		// insert prefix
		if(is_array($prefix))
			$text .= '<div>'.implode(BR, $prefix).'</div>';
		else
			$text .= $prefix;

		// insert the menu in the page
		$text .= Skin::finalize_list($menu, 'menu_bar');

		// insert suffix
		if(is_array($suffix))
			$text .= '<div>'.implode(BR, $suffix).'</div>';
		else
			$text .= $suffix;

		// insert tags after options
		if($tags !== NULL) {
			$text .= '<p style="margin: 1em 0;">'.i18n::s('Tags')
				.' '.'<input type="text" name="tags" id="tags" value="'.encode_field($tags).'" size="45" maxlength="255" accesskey="t" />'
				.' <span class="tiny">'.i18n::s('Keywords separated by commas').'</span></p>';
		}

		// make it a bottom block
		$text = Skin::build_block($text, 'bottom');

		// job done
		return $text;
	}

	/**
	 * decorate some text
	 *
	 * Useful for highlighting snippets of code or other types of text information
	 *
	 * Accepted variants:
	 * - 'bottom' the last div in the content area
	 * - 'caution' get reader attention
	 * - 'center' some centered text
	 * - 'code' a snippet of code
	 * - 'decorated' to add on beauty
	 * - 'description' main content
	 * - 'error' an error message
	 * - 'indent' an indented block
	 * - 'introduction' some decorated text at the beginning of the page
	 * - 'note' get reader attention
	 * - 'page_title' the single page title
	 * - 'question' as a title
	 * - 'quote' some quoted text
	 * - 'right' some right-centered text
	 * - 'search' a form to search some text
	 * - 'subtitle' a second-level title
	 * - 'title' a first-level title
	 * - default make a &lt;span class=...>
	 *
	 * Example to build a title:
	 * [php]
	 * echo Skin::build_block($title, 'title');
	 * [/php]
	 *
	 * Example to build a subtitle:
	 * [php]
	 * echo Skin::build_block($title', 'subtitle');
	 * [/php]
	 *
	 * The access key 4 for the search box has been suggested by Mark Pilgrim.
	 *
	 * @link http://diveintoaccessibility.org/day_29_making_everything_searchable.html Dive Into Accessibility: Making everything searchable
	 *
	 * @param mixed the text, or an array of strings
	 * @param the variant, if any
	 * @param string a unique object id, if any
	 * @param mixed rendering parameters, if any
	 * @return string the rendered text
	 *
	 * @see shared/codes.php
	**/
	function &build_block($text, $variant='', $id='', $options=NULL) {
		global $context;

		// turn list to a string
		if(is_array($text)) {
			$concatenated = '';
			foreach($text as $line)
				$concatenated .= '<p>'.$line.'</p>'."\n";
			$text = $concatenated;
		}

		// sanity check
		$text = trim($text);

		// make the id explicit
		if($id)
			$id = ' id="'.$id.'" ';

		// depending on variant
		switch($variant) {

		case 'bottom':
			if($text)
				$text = '<div class="bottom"'.$id.'>'.$text.'</div>';
			break;

		case 'caution':
			Skin::define_img('CAUTION_FLAG', 'codes/caution.gif', i18n::s('<b>Warning:</b> '), '!!!');
			if($text)
				$text = '<div class="caution"'.$id.'>'.CAUTION_FLAG.$text.'</div>';
			break;

		case 'center':
			if($text)
				$text = '<div class="center"'.$id.'>'.$text.'</div>';
			break;

		case 'code':
			if($text)
				$text = '<pre'.$id.'>'.$text.'</pre>';
			break;

		case 'decorated':
			if($text)
				$text = '<table class="decorated"'.$id.'><tr>'
				.'<td class="image">'.DECORATED_IMG.'</td>'
				.'<td class="content">'.$text.'</td>'
				."</tr></table>\n";
			break;

		case 'description':
			if($text)
				$text = '<div class="description"'.$id.'>'.Codes::beautify($text, $options).'</div>'."\n";
			break;

		case 'error':
			if($text)
				$text = Skin::build_error_block($text, $id);
			break;

		case 'header1':
		case 'title':
			if($text)
				$text = '<h2'.$id.'><span>'.Codes::beautify_title($text).'</span></h2>';
			break;

		case 'header2':
		case 'subtitle':
			if($text)
				$text = '<h3'.$id.'><span>'.Codes::beautify_title($text).'</span></h3>';
			break;

		case 'header3':
			if($text)
				$text = '<h4'.$id.'>'.Codes::beautify_title($text).'</h4>';
			break;

		case 'header4':
			if($text)
				$text = '<h5'.$id.'>'.Codes::beautify_title($text).'</h5>';
			break;

		case 'header5':
			if($text)
				$text = '<h6'.$id.'>'.Codes::beautify_title($text).'</h6>';
			break;

		case 'indent':
			if($text)
				$text = '<div class="indent"'.$id.'>'.$text.'</div>';
			break;

		case 'introduction':
			if($text)
				$text = '<div class="introduction"'.$id.'>'.Codes::beautify($text, $options).'</div>'."\n";
			break;

		case 'note':
			Skin::define_img('NOTICE_FLAG', 'codes/note.gif', i18n::s('<b>Note:</b> '));
			if($text)
				$text = '<div class="note"'.$id.'>'.NOTICE_FLAG.$text.'</div>';
			break;

		case 'page_title':
			if($text)
				$text = '<h1'.$id.'><span>'.Codes::beautify_title($text)."</span></h1>\n";
			break;

		case 'question':
			if($text)
				$text = '<h2'.$id.' class="question"><span>'.Codes::beautify_title($text).'</span></h2>';
			break;

		case 'quote':
			if($text)
				$text =& Skin::build_quote_block($text);
			break;

		case 'right':
			if($text)
				$text = '<div'.$id.' class="right">'.$text.'</div>';
			break;

		case 'search':

			// delegate search requests to an external web address
			if(isset($context['skins_delegate_search']) && ($context['skins_delegate_search'] == 'Y') && isset($context['skins_search_form']) && $context['skins_search_form'])
				$text = str_replace('%s', encode_field($text), $context['skins_search_form']);

			// combine search in content and search in users
			elseif(isset($context['skins_delegate_search']) && (($context['skins_delegate_search'] == 'S') || ($context['skins_delegate_search'] == 'X'))) {
				if(!$text)
					$text = i18n::s('Search...');

				$text = '<form action="'.$context['url_to_root'].'search.php" method="get" id="search_box">'
					.'<p style="margin: 0; padding: 0;">'
					.'<input type="text" name="search" size="10" value="'.encode_field($text).'" onfocus="this.value=\'\'" maxlength="128" />'
					.Skin::build_submit_button(i18n::s('Go')).BR
					.'<input type="radio" name="s" id="s_content" onchange="$(\'#search_box\').action=\''.$context['url_to_root'].'search.php\'" checked="checked" style="margin: 3px 2px 1px 0; padding: 0" /><label for="s_content">'.Skin::build_link('sections/', i18n::s('Content'), 'basic').'</label>'
					.BR.'<input type="radio" name="s" id="s_persons" onchange="$(\'#search_box\').action=\''.$context['url_to_root'].'users/search.php\'" style="margin: 2px 3px 1px 0; padding: 0" /><label for="s_persons">'.Skin::build_link('users/', i18n::s('Persons'), 'basic').'</label>'
					.'</p>'
					.'</form>';

			// simples search form
			} else {
				if(!$text)
					$text = i18n::s('Search...');

				$text = '<form action="'.$context['url_to_root'].'search.php" method="get" id="search_box">'
					.'<p style="margin: 0; padding: 0;">'
					.'<input type="text" name="search" size="10" value="'.encode_field($text).'" onfocus="this.value=\'\'" maxlength="128" />'
					.Skin::build_submit_button(i18n::s('Go'))
					.'</p>'
					.'</form>';

			}
			break;

		case 'sidecolumn':
			if($text)
				$text = '<div'.$id.' class="sidecolumn">'.$text.'</div>';
			break;

		default:
			if($variant && $text)
				$text = '<span class="'.$variant.'"'.$id.'>'.$text.'</span>';
			break;

		}

		// job done
		return $text;
	}

	/**
	 * build a box
	 *
	 * Accept following variants:
	 * - 'extra' for a flashy box on page side
	 * - 'floating' for a box floated to the left
	 * - 'folded' for a folded box with content
	 * - 'gadget' for additional content in the main part of the page
	 * - 'header1' with a level 1 title
	 * - 'header2' with a level 2 title
	 * - 'header3' with a level 3 title
	 * - 'navigation' for additional navigational information on page side
	 * - 'sidebar' for some extra information in the main part of the page
	 * - 'sidecolumn' for some extra information in the main part of the page
	 * - 'sliding' for sliding content
	 * - 'toc' for a table of content
	 * - 'toq' for a table of questions
	 * - 'unfolded' for a folded box with content
	 *
	 * @param string the box title, if any
	 * @param string the box content
	 * @param string the box variant, if any
	 * @param string a unique object id, if any
	 * @param string a link to add to the title, if any
	 * @param string the popup to display while hovering the link, if any
	 * @return the HTML to display
	 *
	 */
	function &build_box($title, $content, $variant='header1', $id='', $url='', $popup='') {
		global $context;

		// accept line breaks in box titles
		$title = str_replace("\n", BR, $title);

		// append a link to the title, if any
		if($url)
			$title =& Skin::build_box_title($title, $url, $popup);

		// depending on variant
		switch($variant) {

		case 'extra':
			$output =& Skin::build_extra_box($title, $content, $id);
			break;

		case 'floating':
			$output =& Skin::build_floating_box($title, $content, $id);
			break;

		case 'folded':
		case 'folder': // obsoleted
			$output =& Skin::build_folded_box($title, $content, $id);
			break;

		case 'gadget':
			$output =& Skin::build_gadget_box($title, $content, $id);
			break;

		case 'header1':
		case 'header2':
		case 'header3':
			$output =& Skin::build_header_box($title, $content, $id, $variant);
			break;

		case 'navigation':
			$output =& Skin::build_navigation_box($title, $content, $id);
			break;

		case 'section': // legacy
			$output =& Skin::build_header_box($title, $content, $id);
			break;

		case 'sidebar':
			$output =& Skin::build_sidebar_box($title, $content, $id);
			break;

		case 'sidecolumn':
			$output =& Skin::build_sidecolumn_box($title, $content, $id);
			break;

		case 'sliding':
			$output =& Skin::build_sliding_box($title, $content, $id);
			break;

		case 'toc':
			$output =& Skin::build_toc_box($title, $content, $id);
			break;

		case 'toq':
			$output =& Skin::build_toq_box($title, $content, $id);
			break;

		case 'unfolded':
			$output =& Skin::build_unfolded_box($title, $content, $id);
			break;

		default:

			// displayed in the navigation panel
			if(isset($context['skins_navigation_components']) && (strpos($context['skins_navigation_components'], $variant) !== FALSE))
				$output =& Skin::build_navigation_box($title, $content, $id);

			// displayed in the extra panel
			elseif(isset($context['skins_extra_components']) && (strpos($context['skins_extra_components'], $variant) !== FALSE))
				$output =& Skin::build_extra_box($title, $content, $id);

			else
				$output =& Skin::build_header_box($title, $content, $id, $variant);
			break;

		}

		// job done
		return $output;
	}

	/**
	 * append a link to a title
	 *
	 * @param string the box title
	 * @param string a link to add to the title
	 * @return the HTML to display
	 *
	 */
	function &build_box_title($title, $url, $popup='') {
		global $context;

		$text = $title.' '.Skin::build_link($url, TITLE_SHORTCUT, 'more', $popup);

		return $text;
	}

	/**
	 * add some content to a page
	 *
	 * @param string box unique id
	 * @param string box title
	 * @param string box content
	 * @param array top menu bar
	 * @param array bottom menu bar
	 * @return string to be integrated into final rendering
	 */
	function &build_content($id, $title, $content, $top=NULL, $bottom=NULL) {
		global $context;

		// we return some text
		$text = '';

		// top menu bar
		if(isset($top) && is_array($top) && count($top))
			$text .= Skin::build_list($top, 'menu_bar');

		// actual content
		$text .= $content;

		// bottom menu bar
		if(isset($bottom) && is_array($bottom) && count($bottom))
			$text .= Skin::build_list($bottom, 'menu_bar');

		// shape a complete box with title, id, etc.
		$text = Skin::build_box($title, $text, 'header1', $id);

		// job done
		return $text;
	}

	/**
	 * build a contextual menu for this page
	 *
	 * You can overload this function in your own skin to change this behaviour.
	 *
	 * @param array path of anchors to the current page
	 * @return string to be inserted in the returned web page
	 *
	 * @see articles/view.php
	 * @see sections/view.php
	 */
	function &build_contextual_menu($anchors) {
		global $context;

		// build the contextual tree
		$tree = array();

		// list underneath level
		if($children =& Sections::get_children_of_anchor($anchors[count($anchors)-1], 'index')) {

			// place children
			foreach($children as $child) {
				if($anchor =& Anchors::get($child))
					$tree[] = array($anchor->get_url(), NULL, $anchor->get_title(), NULL, 'below', NULL, $anchor->get_teaser('hover'));

			}

		}

		// ensure minimum depth
		if(count($anchors) < 2) {
			$text = Skin::build_tree($tree);
			return $text;
		}

		// current level
		if($anchor =& Anchors::get($anchors[count($anchors)-1]))
			$tree = array(array_merge(array($anchor->get_url(), NULL, $anchor->get_title(), NULL, 'current', NULL, $anchor->get_teaser('hover')), array($tree)));

		// upper levels
		for($index=count($anchors)-2; $index >= 0; $index--) {

			// get nibbles
			if($nibbles =& Sections::get_children_of_anchor($anchors[$index], 'index')) {

				// list nibbles
				$insert = TRUE;
				$prefix = array();
				foreach($nibbles as $nibble) {

					// match the item that has the focus
					if($nibble == $anchors[$index+1]) {
						$insert = FALSE;
						continue;
					}

					// list nibble before or after the one that has he focus
					if($anchor =& Anchors::get($nibble)) {
						if($insert)
							$prefix[] = array($anchor->get_url(), NULL, $anchor->get_title(), NULL, 'close', NULL, $anchor->get_teaser('hover'));
						else
							$tree[] = array($anchor->get_url(), NULL, $anchor->get_title(), NULL, 'close', NULL, $anchor->get_teaser('hover'));
					}

				}

				// preserve ordering
				$tree = array_merge($prefix, $tree);

			}

			// move up the contextual path
			if(($index > 0) && ($anchor =& Anchors::get($anchors[$index])))
				$tree = array(array_merge(array($anchor->get_url(), NULL, $anchor->get_title(), NULL, 'open', NULL, $anchor->get_teaser('hover')), array($tree)));

		}

		// transform this structure to XHTML
		$text =& Skin::build_tree($tree, 0, 'contextual_menu_focus');
		return $text;
	}

	/**
	 * Format a date
	 *
	 * Accept either a time stamp, or a formatted string as input parameter:
	 * - YYYY-MM-DD HH:MM:SS
	 * - YYMMDD HH:MM:SS GMT
	 *
	 * The variant is processed as follows:
	 * - 'day' - only day, month and year --no time information
	 * - 'with_hour' - adapts to time scale, and mention hours for today and yesterday
	 * - 'no_hour' - adapts to time scale, but don't mention hours
	 * - 'full' - display the full date
	 * - 'month' - only month and year
	 * - 'publishing' - with a draft icon in the future, else equivalent to 'no_hour'
	 * - 'standalone' - like full, but without the 'on ' prefix
	 * - 'iso8601' - special format
	 * - 'plain' - example 'Feb 26 2010 22:30:31 GMT'
	 * - 'yyyy-mm-dd' - only day, month and year --no time information
	 *
	 * The date provided is considered to be GMT-based.
	 * It is adjusted to the time zone of the surfer, if applicable.
	 * This adjustment does not apply to following variants:
	 * - 'day'
	 * - 'iso8601'
	 * - 'standalone'
	 *
	 *
	 * @link http://www.w3.org/TR/NOTE-datetime the w3c profile of ISO 8601
	 * @link http://www.cs.tut.fi/~jkorpela/iso8601.html short description of ISO 8601
	 * @link http://fr2.php.net/date Code for ISO 8601 formatting
	 *
	 * @param int or string the date to be displayed
	 * @param string the variant
	 * @param string the language to express this stamp
	 * @param int offset to GMT of the provided date, if any
	 * @return the HTML to be used
	 */
	function &build_date($stamp, $variant='with_hour', $language=NULL, $gmt_offset=0) {
		global $context, $local;

		// return by reference
		$output = '';

		// sanity check
		if(!isset($stamp) || !$stamp)
			return $output;

		// surfer offset, except on 'day' and 'iso8601'
		if(($variant == 'day') || ($variant == 'iso8601') || ($variant == 'standalone'))
			$surfer_offset = 0;
		else
			$surfer_offset = Surfer::get_gmt_offset();

		// YYMMDD-HH:MM:SS GMT -- this one is natively GMT
		if(preg_match('/GMT$/', $stamp) && (strlen($stamp) == 19)) {

			// YYMMDD-HH:MM:SS GMT -> HH, MM, SS, MM, DD, YY
			$actual_stamp = mktime(substr($stamp, 7, 2), substr($stamp, 10, 2), substr($stamp, 13, 2),
				substr($stamp, 2, 2), substr($stamp, 4, 2), substr($stamp, 0, 2));

			// adjust to surfer time zone
			$actual_stamp += ($surfer_offset * 3600);

		// time()-like stamp
		} elseif(intval($stamp) > 10000000) {

			// adjust to surfer time zone
			$actual_stamp = intval($stamp) + (($surfer_offset - $gmt_offset) * 3600);

		// YYYY-MM-DD HH:MM:SS, or a string that can be readed
		} elseif(($actual_stamp = strtotime($stamp)) != -1) {

			// adjust to surfer time zone
			$actual_stamp += (($surfer_offset - $gmt_offset) * 3600);

		} else {
			$output = '*'.$stamp.'*';
			return $output;
		}

		if(!$items = @getdate($actual_stamp)) {
			$output = '*'.$stamp.'*';
			return $output;
		}

		// if undefined language, use preferred language for absolute formats
		if($language)
			;
		elseif(($variant == 'full') || ($variant == 'iso8601'))
			$language = $context['preferred_language'];
		else
			$language = $context['language'];

		// in French '1' -> '1er'
		if(($language == 'fr') && ($items['mday'] == 1))
			$items['mday'] = '1er';

		// months
		if($language == 'fr')
			$months = array( '*', 'janvier', 'f&eacute;vrier', 'mars', 'avril', 'mai', 'juin', 'juillet', 'ao&ucirc;t', 'septembre',
				 'octobre', 'novembre', 'd&eacute;cembre' );
		else
			$months = array( '*', 'Jan.', 'Feb.', 'Mar.', 'Apr.', 'May', 'June', 'Jul.', 'Aug.', 'Sep.', 'Oct.', 'Nov.', 'Dec.' );

		// now
		$now = time();

		// server actual offset -- provided as a parameter
//		$gmt_offset = intval((strtotime(date('M d Y H:i:s')) - strtotime(gmdate('M d Y H:i:s'))) / 3600);

		// server stamp, as seen by surfer
		$today = getdate($now + (($surfer_offset - $gmt_offset) * 3600));

		// time stamp only
		if(preg_match('/00:00/', date('H:i', $actual_stamp)))
			$time = '';
		else {
			$trailer = '';
			if(!$surfer_offset)
				$trailer = ' GMT';
			$time = sprintf(i18n::s(' at %s%s'), date(i18n::s('h:i a'), $actual_stamp), $trailer);
		}

		// format a date as an absolute string
		if($variant == 'full') {
			if($language == 'fr')
				$output .= $items['mday'].' '.$months[$items['mon']].' '.($items['year']).$time;
			else
				$output .= $months[$items['mon']].' '.$items['mday'].' '.($items['year']).$time;
			return $output;
		}

		// publishing date is in the future
		if(($variant == 'publishing') && (strcmp($stamp, $context['now']) > 0))
			$output .= DRAFT_FLAG;

		// don't display publishing hour
		if($variant == 'publishing')
			$variant = 'no_hour';

		// the same, but without prefix
		if($variant == 'standalone') {
			if($language == 'fr')
				$output .= $items['mday'].' '.$months[$items['mon']].' '.($items['year']).$time;
			else
				$output .= $months[$items['mon']].' '.$items['mday'].' '.($items['year']).$time;
			return $output;
		}

		// month only
		if($variant == 'month') {
			if($language == 'fr')
				$output .= $months[$items['mon']].' '.($items['year']);
			else
				$output .= $months[$items['mon']].' '.($items['year']);
			return $output;
		}

		// day only
		if($variant == 'day') {

			// same year, don't mention it
			if($items['year'] == $today['year']) {
				if($language == 'fr')
					$output .= $items['mday'].' '.$months[$items['mon']];
				else
					$output .= $months[$items['mon']].' '.$items['mday'];

			// different year
			} else {
				if($language == 'fr')
					$output .= $items['mday'].' '.$months[$items['mon']].' '.($items['year']);
				else
					$output .= $months[$items['mon']].' '.$items['mday'].' '.($items['year']);
			}

			return $output;
		}

		// in a calendar
		if($variant == 'calendar') {
			include_once $context['path_to_root'].'dates/dates.php';
			$month_link =& Skin::build_link(Dates::get_url($items['year'].'/'.$items['mon'], 'month'), $months[$items['mon']], 'basic', i18n::s('Calendar of this month'));
			$year_link =& Skin::build_link(Dates::get_url($items['year'], 'year'), $items['year'], 'basic', i18n::s('Calendar of this year'));
			if($language == 'fr')
				$output .= $items['mday'].' '.$month_link.' '.$year_link;
			else
				$output .= $month_link.' '.$items['mday'].' '.$year_link;
			return $output;
		}

		// format a date according to ISO 8601 format
		if($variant == 'iso8601') {
			$tzd = date('O', $actual_stamp);
			$tzd = $tzd[0].str_pad((int)($tzd / 100), 2, "0", STR_PAD_LEFT).':'.str_pad((int)($tzd % 100), 2, "0", STR_PAD_LEFT);
			$output = date('Y-m-d\TH:i:s', $actual_stamp).$tzd;
			return $output;
		}

		// format a raw date
		if($variant == 'yyyy-mm-dd') {
			$output = date('Y-m-d', $actual_stamp);
			return $output;
		}

		// plain date
		if($variant == 'plain') {
			$output = date('M d Y H:i:s', $actual_stamp).' GMT';
			return $output;
		}

		// less than 24 hours
		if(($items['yday'] == $today['yday']) && ($items['year'] == $today['year'])) {
			$time = date('H:i', $actual_stamp);
			if(($variant == 'no_hour') || preg_match('/00:00/', $time)){
				$local['today_en'] = 'today';
				$local['today_fr'] = 'aujourd\'hui';
			} else {

				$trailer = '';
				if(!$surfer_offset)
					$trailer = ' GMT';

				$local['today_en'] = 'today at '.date('h:i a', $actual_stamp).$trailer;
				$local['today_fr'] = 'aujourd\'hui &agrave; '.date('H:i', $actual_stamp).$trailer;
			}
			$output = i18n::user('today');
			return $output;

		// less than 48 hours
		} elseif(($items['yday']+1 == $today['yday']) && ($items['year'] == $today['year'])) {
			$time = date('H:i', $actual_stamp);
			if(($variant == 'no_hour') || preg_match('/00:00/', $time)){
				$local['yesterday_en'] = 'yesterday';
				$local['yesterday_fr'] = 'hier';
			} else {

				$trailer = '';
				if(!$surfer_offset)
					$trailer = ' GMT';

				$local['yesterday_en'] = 'yesterday at '.date('h:i a', $actual_stamp).$trailer;
				$local['yesterday_fr'] = 'hier &agrave; '.date('H:i', $actual_stamp).$trailer;
			}
			$output = i18n::user('yesterday');
			return $output;

		// this year
		} elseif(($stamp <= $now) && ($items['year'] == $today['year'])) {
			if($language == 'fr')
				$output .= 'le '.$items['mday'].' '.$months[$items['mon']];
			else
				$output .= 'on '.$months[$items['mon']].' '.$items['mday'];
			return $output;

		// date in fr: le dd mmm yy
		} elseif($language == 'fr') {
			$output .= 'le '.$items['mday'].' '.$months[$items['mon']].' '.($items['year']);
			return $output;

		// date in en: on mmm dd yy
		} else {
			$output .= 'on '.$months[$items['mon']].' '.$items['mday'].' '.($items['year']);
			return $output;
		}

	}

	/**
	 * build a block of error messages
	 *
	 * @param mixed string or array of strings
	 * @param string a unique object id, if any
	 * @return the HTML to display
	 */
	function &build_error_block($text='', $id='') {
		global $context;

		// use context information
		if(!$text)
			$text = $context['error'];

		// turn list to a string
		if(is_array($text)) {
			$concatenated = '';
			foreach($text as $line)
				$concatenated .= '<p>'.$line.'</p>'."\n";
			$text = $concatenated;
		}

		// make the id explicit
		if($id)
			$id = ' id="'.$id.'" ';

		// format the block
		if($text)
			$text = '<div class="error"'.$id.'>'.$text.'</div>'."\n";
		return $text;
	}

	/**
	 * build an extra box
	 *
	 * @param string the box title, if any
	 * @param string the box content
	 * @param string an optional unique id for this box
	 * @return the HTML to display
	 */
	function &build_extra_box($title, &$content, $id='') {
		global $context;

		// this box has a unique id
		if($id)
			$id = ' id="'.$id.'" ';

		// else create our own unique id
		else {
			static $global_extra_box_index;
			if(!isset($global_extra_box_index))
				$global_extra_box_index = 0;
			$id = ' id="extra_'.++$global_extra_box_index.'" ';
		}

		// external div boundary
		$text = '<dl class="extra_box"'.$id.'>'."\n";

		// always add a header
		$text .= '<dt><span>'.$title."</span></dt>\n";

		// box content
		$text .= '<dd>'.$content.'</dd>';

		// external div boundary
		$text .= '</dl>'."\n";

		return $text;
	}

	/**
	 * build a floating box
	 *
	 * @param string the box title, if any
	 * @param string the box content
	 * @param string an optional unique id for this box
	 * @return the HTML to display
	 */
	function &build_floating_box($title, &$content, $id='') {
		global $context;

		// box id, if any
		if($id)
			$id = ' id="'.$id.'"';

		// external div boundary
		$text = '<dl class="floating_box"'.$id.'>'."\n";

		// box content --add clear at the end to align images
		$text .= '<dd>'.$content.'</dd>';

		// always add a header
		$text .= '<dt><span>'.$title.'</span></dt>'."\n";

		// external div boundary
		$text .= '</dl>'."\n";

		// pass by reference
		return $text;

	}

	/**
	 * build a folded box
	 *
	 * Based on DHTML and DOM. The Javascript [code]toggle_folder()[/code] code
	 * is inserted at the bottom of the page in [script]shared/global.php[/script]
	 *
	 * @link http://www.dddekerf.dds.nl/DHTML_Treeview/DHTML_Treeview.htm Easy DHTML TreeView
	 *
	 * @param string the box title, if any
	 * @param string the box content
	 * @param string an optional unique id for this box
	 * @return the HTML to display
	 *
	 * @see articles/edit.php
	 * @see sections/edit.php
	 * @see users/edit.php
	 */
	function &build_folded_box($title, $content, $id='') {
		global $context;

		// the icon used to stretch folder divisions
		Skin::define_img_href('FOLDER_EXTEND_IMG_HREF', 'layouts/folder_plus.gif');

		// the icon used to pack folder divisions
		Skin::define_img_href('FOLDER_PACK_IMG_HREF', 'layouts/folder_minus.gif');

		// we need a clickable title
		if(!$title)
			$title = i18n::s('Click to slide');

		if($id)
			$id = ' id="'.$id.'"';

		// maybe we have an image to enhance rendering
		$img = '';
		if(FOLDER_EXTEND_IMG_HREF)
			$img = '<img src="'.FOLDER_EXTEND_IMG_HREF.'" alt="" title="'.encode_field(i18n::s('Click to slide')).'" /> ';

		// Yacs.toggle_folder() is in shared/yacs.js -- div.folder_body div is required for slide effect to work
		$text = '<div class="folder_box"'.$id.'><a href="#" class="folder_header" onclick="javascript:Yacs.toggle_folder(this, \''.FOLDER_EXTEND_IMG_HREF.'\', \''.FOLDER_PACK_IMG_HREF.'\'); return false;">'.$img.$title.'</a>'
			.'<div class="folder_body" style="display: none"><div>'.$content."</div></div></div>\n";

		// pass by reference
		return $text;

	}

	/**
	 * build a flag
	 *
	 * @param string the variant: 'updated', 'new', etc.
	 * @return string the rendered text
	 *
	 * @see shared/codes.php
	**/
	function &build_flag($variant) {
		global $context;

		$text = '';
		if($variant)
			$text = ' <span class="flag">'.$variant.'</span> ';
		 return $text;
	}

	/**
	 * build a form
	 *
	 * Accepted variants:
	 * - '1-column'
	 * - '2-columns'
	 *
	 * @todo use fieldset and legend http://www.webcredible.co.uk/user-friendly-resources/css/css-forms.shtml
	 *
	 * @param an array of ($label, $input, $hint)
	 * @param the visual variant
	 * @return an HTML string to be displayed
	 */
	function &build_form(&$fields, $variant='2-columns') {

		// we return some text
		$text = '';

		// sanity check
		if(!$fields || !is_array($fields))
			return $text;

		// use a table for the layout
		$text .= Skin::table_prefix('layout');
		$lines = 1;

		// parse each field
		$hidden = '';
		foreach($fields as $field) {

			// if this is only a label, make a title out of it
			if(!is_array($field)) {
				$text .= Skin::table_suffix().Skin::build_block($field, 'title').Skin::table_prefix('form');
				continue;
			}

			$label = $field[0];

			$input = '';
			if(isset($field[1]))
				$input = $field[1];

			$hint = '';
			if(isset($field[2]))
				$hint = $field[2];

			// hidden field are put at the end, with no label
			if($label == 'hidden') {
				$hidden .= $input;
				continue;
			}

			// put the hint after the field
			if($hint)
				$input .= '<br style="clear: both;" /><span class="tiny">'.$hint.'</span>';

			$cells = array();
			switch($variant) {
			case '1-column':
				$cells[] = $label.BR."\n".$input;
				break;
			case '2-columns':
			default:
				$cells[] = 'west='.$label;
				$cells[] = 'east='.$input;
				break;
			}
			$text .= Skin::table_row($cells, $lines++);
		}

		// end of the table
		$text .= Skin::table_suffix();

		// append hidden fields, if any
		$text .= $hidden;

		// return the whole string
		return $text;
	}

	/**
	 * build a gadget box
	 *
	 * @param string the box title, if any
	 * @param string the box content
	 * @param string an optional unique id for this box
	 * @return the HTML to display
	 */
	function &build_gadget_box($title, &$content, $id='') {
		global $context;

		// this box has a unique id
		if($id)
			$id = ' id="'.$id.'" ';

		// else create our own unique id
		else {
			static $global_gadget_box_index;
			if(!isset($global_gadget_box_index))
				$global_gadget_box_index = 0;
			$id = ' id="gadget_'.++$global_gadget_box_index.'" ';
		}

		// external div boundary
		$text = '<dl class="gadget_box"'.$id.'>'."\n";

		// always add a header
		$text .= '<dt><span>'.$title."</span></dt>\n";

		// box content --add clear at the end to align images
		$text .= '<dd>'.$content.'<br style="clear: both;" /></dd>';

		// external div boundary
		$text .= '</dl>'."\n";

		return $text;
	}

	/**
	 * build a box with a title
	 *
	 * The variant can be: 'header1', 'header2' or 'header3' depending of the
	 * level of title to consider.
	 *
	 * @param string the box title, if any
	 * @param string the box content
	 * @param string an optional unique id for this box
	 * @param string level of title to consider
	 * @return the HTML to display
	 */
	function &build_header_box($title, &$content, $id='', $variant='header1') {
		global $context;

		// this box has a unique id
		if($id)
			$id = ' id="'.$id.'" ';

		// else create our own unique id
		else {
			static $box_index;
			if(!isset($box_index))
				$box_index = 0;
			$id = ' id="header_box_'.++$box_index.'" ';
		}

		// external div boundary
		$text = '<div class="box"'.$id.'>'."\n";

		// map the level to a given tag
		if($variant == 'header3')
			$tag = 'h4';
		elseif($variant == 'header2')
			$tag = 'h3';
		else
			$tag = 'h2';

		// add a header, but only if there is a title
		if($title)
			$text .= '<'.$tag.'><span>'.$title.'</span></'.$tag.">\n";

		// box content
		$text .= '<div class="content">'.$content.'</div>';

		// external div boundary
		$text .= '</div>'."\n";

		return $text;
	}

	/**
	 * build tags for an image
	 *
	 * @link http://realworldstyle.com/thumb_float.html Floating Thumbnails
	 * @link http://www.hypergurl.com/span.html spans with titles
	 *
	 * Accept following variants:
	 * - 'center' for an image with a centered label
	 * - 'inline' for in-line images; caption is only displayed in hovering pop-up
	 * - 'left' for a left-aligned image
	 * - 'right' for a right-aligned image
	 * - 'thumbnail' for aligned thumbnail images
	 * - any other value is translated to css style
	 *
	 * Any of previous variants can be prefixed with the keyword 'large', like in 'large inline'.
	 * This may be used by web designers to trigger specific features, such as frames around large images.
	 *
	 * If a title has been given to the image, it is provided as a hovering label.
	 * It is also added as caption in following situations:
	 * - variant does not have the keyword 'thumbnail'
	 * - or global parameter 'thumbnails_without_caption' has not been set to 'Y'
	 *
	 * @param string the image variant
	 * @param string the image href
	 * @param string the image title
	 * @param string a link to make a clickable image, if any
	 * @param string id of image, to display edition direct link, if desired
	 * @return the HTML to display
	 *
	 */
	function &build_image($variant, $href, $title, $link='',$id='') {
		global $context;

		// sanity check
		if(!$variant)
			$variant = 'inline';

		// sanity check
		if(!$href) {
			$output = '';
			return $output;
		}

		// provide absolute references
		if($href && ($href[0] == '/'))
			$href = $context['url_to_home'].$href;
		if($link && ($link[0] == '/'))
			$link = $context['url_to_home'].$link;

		// always display captions aside large images
		if(preg_match('/\blarge\b/i', $variant))
			$with_caption = TRUE;

		// add captions to other images
		elseif(isset($context['thumbnails_without_caption']) && ($context['thumbnails_without_caption'] == 'Y'))
			$with_caption = FALSE;
		else
			$with_caption = TRUE;

		// document the image
		if($title)
			$hover = $title;
		else
			$hover = '';

		// remove YACS codes from alternate label and hovering title
		if(is_callable(array('Codes', 'strip')))
			$hover =& Codes::strip($hover);

		// split components of the variant
		if($position = strpos($variant, ' ')) {
			$complement = substr($variant, 0, $position);
			$variant = substr($variant, $position+1);
		} else
			$complement = '';

		// wrapper begins --don't use div, because of surrounding link
		$text = "\n".'<span class="'.encode_field($variant).'_image">';

		// styling freedom --outside anchor, which is inline
		if($complement)
			$text .= '<span class="'.$complement.'">';

		// configured styles
		$more_styles = '';
		if(($complement == 'large') && isset($context['classes_for_large_images']) && $context['classes_for_large_images'])
			$more_styles = ' class="'.encode_field($context['classes_for_large_images']).'"';
		elseif(($variant == 'thumbnail') && isset($context['classes_for_thumbnail_images']) && $context['classes_for_thumbnail_images'])
			$more_styles = ' class="'.encode_field($context['classes_for_thumbnail_images']).'"';
		elseif(($variant == 'avatar') && isset($context['classes_for_avatar_images']) && $context['classes_for_avatar_images'])
			$more_styles = ' class="'.encode_field($context['classes_for_avatar_images']).'"';

		// the image itself
		$image = '<span><img src="'.$href.'" alt=""  title="'.encode_field(strip_tags($hover)).'"'.$more_styles.' /></span>';

		// add a link
		if($link && preg_match('/\.(gif|jpeg|jpg|png)$/i', $link) && !preg_match('/\blarge\b/', $variant))
			$text .= '<a href="'.$link.'" class="image_show">'.$image.'</a>';
		elseif($link) {
			$external = FALSE;
			if(!strncmp($link, 'http:', 5) && strncmp($link, 'http://'.$context['host_name'], strlen('http://'.$context['host_name'])))
				$external = TRUE;
			elseif(!strncmp($link, 'https:', 6) && strncmp($link, 'https://'.$context['host_name'], strlen('https://'.$context['host_name'])))
				$external = TRUE;

			if($external)
				$text .= '<a href="'.$link.'" onclick="window.open(this.href); return false;">'.$image.'</a>';
			else
				$text .= '<a href="'.$link.'">'.$image.'</a>';

		} else
			$text .= $image;

		// make the title visible as a caption
		if($title && $with_caption)
			$text .= '<span class="image_caption">'.ucfirst($title).'</span>';

		// end of freedom
		if($complement)
			$text .= '</span>';

		//edit image direct access
		if((($variant=='center')||($variant=='right')||($variant=='left')||($variant=='thumbnail')||($complement=='large')) && $id) {
			Skin::define_img('IMAGES_EDIT_IMG', 'images/edit.gif');
			$text .= '<span class="image_edit">'.Skin::build_link(Images::get_url($id,'edit'), IMAGES_EDIT_IMG, NULL, i18n::s('Update this image').' ['.$id.']').'</span>';
		}

		// end of wrapper
		$text .= '</span>';

		// job done
		return $text;

	}

	/**
	 * build an input field in a form
	 *
	 * Type can have one of following values:
	 * - 'date' - to enter a date
	 * - 'date_time' - to enter a date and time at once
	 *
	 * @param string the field name and id
	 * @param string value to display to the surfer
	 * @param string input type
	 * @param string any text to be inserted in the input tag
	 * @return the HTML to display
	 *
	 */
	function &build_input($name, $value, $type, $onchange=NULL) {
		global $context;

		// some javascript to call on change
		if($onchange)
			$onchange = ' onchange="'.$onchange.'" ';
		else
			$onchange = '';

		switch($type) {
		case 'date':

			// do not display 0s on screen
			if($value <= '0000-00-00')
				$value = '';

			// date stamps are handled in regular text fields
			$text = '<input type="text" name="'.$name.'" id="'.$name.'" value="'.encode_field($value).'" size="15" maxlength="15" '.$onchange.'/>'
				.'<img src="'.$context['url_to_root'].'included/jscalendar/img.gif" id="'.$name.'_trigger" style="border: none; cursor: pointer;" title="Date selector" onmouseover="this.style.background=\'red\';" onmouseout="this.style.background=\'\'" alt="" />';

			// these are enhanced with jsCalendar, if present
			if(file_exists($context['path_to_root'].'included/jscalendar/calendar.js') || file_exists($context['path_to_root'].'included/jscalendar/calendar.js.jsmin')) {
				$text .= JS_PREFIX
					.'$(document).ready( function() { Calendar.setup({'."\n"
					.'	inputField	:	"'.$name.'",'."\n"
					.'	ifFormat	:	"%Y-%m-%d",'."\n"
					.'	showsTime	:	false,'."\n"
					.'	button		:	 "'.$name.'_trigger",'."\n"
					.'	align		:	 "CC",'."\n"
					.'	singleClick :	 true'."\n"
					.'}); });'."\n"
					.JS_SUFFIX."\n";

				// load the jscalendar library
				$context['javascript']['calendar'] = TRUE;
			}

			return $text;

		case 'date_time':

			// do not display 0s on screen
			if($value <= '0000-00-00 00:00:00')
				$value = '';

			// date stamps are handled in regular text fields
			$text = '<input type="text" name="'.$name.'" id="'.$name.'" value="'.encode_field($value).'" size="20" maxlength="255" '.$onchange.' />'
				.'<img src="'.$context['url_to_root'].'included/jscalendar/img.gif" id="'.$name.'_trigger" style="border: none; cursor: pointer;" title="Date selector" onmouseover="this.style.background=\'red\';" onmouseout="this.style.background=\'\'" alt="" />';

			// these are enhanced with jsCalendar, if present
			if(file_exists($context['path_to_root'].'included/jscalendar/calendar.js') || file_exists($context['path_to_root'].'included/jscalendar/calendar.js.jsmin')) {
				$text .= JS_PREFIX
					.'$(document).ready( function() { Calendar.setup({'."\n"
					.'	inputField	:	"'.$name.'",'."\n"
					.'	ifFormat	:	"%Y-%m-%d %H:%M",'."\n"
					.'	showsTime	:	true,'."\n"
					.'	timeFormat	:	"24",'."\n"
					.'	button		:	 "'.$name.'_trigger",'."\n"
					.'	align		:	 "CC",'."\n"
					.'	singleClick :	 true'."\n"
					.'}); });'."\n"
					.JS_SUFFIX."\n";

				// load the jscalendar library
				$context['javascript']['calendar'] = TRUE;
			}

			return $text;

		default:

			$text = 'Unknown type '.$type.' submitted to Skin::build_input()';
			return $text;

		}
	}

	/**
	 * build a link
	 *
	 * Accept following variants:
	 * - 'article' - jump to an article page
	 * - 'basic' - a very basic link - also the default
	 * - 'button' - a link that looks like a button
	 * - 'category' - jump to a category page
	 * - 'click' - a button that records clicks
	 * - 'comment' - jump to a comment page
	 * - 'day' - a one day calendar
	 * - 'email' - a mailto: link
	 * - 'external' - jump to the outside world
	 * - 'file' - see file details
	 * - 'internal' - jump to the outside world, but stay in this window
	 * - 'help' - open a separate window to display some help
	 * - 'menu_1' - one top level link (&#91;menu]...&#91;/menu])
	 * - 'menu_2' - one secondary level link (&#91;submenu]...&#91;/submenu])
	 * - 'month' - a one month calendar
	 * - 'more' - a follow-up link
	 * - 'raw' - return only the url
	 * - 'xml' - this has to be processed by a specialized software
	 * - 'script' - see some phpDoc page
	 * - 'section' - jump to a section page
	 * - 'server' - jump to a server profile (news, ftp, etc.)
	 * - 'shortcut' - stay within the site
	 * - 'span' - like 'basic', but insert a <span> around the label
	 * - 'tag' - a folksonomy
	 * - 'user' - a person profile
	 * - 'year' - a full year calendar
	 *
	 * @link http://www.texastar.com/tips/2004/target_blank.shtml XHTML 1.1 Modularization Anchor Element Target Attribute
	 *
	 * @param string the url, if any
	 * @param string a label, if any
	 * @param string an optional variant, as described above
	 * @param string an optional title to add to the link
	 * @param boolean open the link in a separate page if TRUE
	 * @param string to access this link with keyboard only
	 * @return string the rendered text, or the bare url if $variant = 'raw'
	**/
	function &build_link($url, $label=NULL, $variant=NULL, $href_title=NULL, $new_window=FALSE, $access_key=NULL) {
		global $context;

		// don't create a link if there is no url - strip everything that begins with '_'
		if(!$url || (strpos($url, '_') === 0))
			return $label;

		// be sure to have a label
		if(!$label)
			$label = $url;

		// limit the size of labels when they are links
		if(!strncmp($label, 'http:', 5) || !strncmp($label, 'https:', 6) || !strncmp($label, 'ftp:', 4)) {
			if(strlen($label) > 50)
				$label = substr_replace($label, '...', 30, -15);
		}

		// guess the type of this link
		if(!$variant) {

			if(!strncmp($url, '/', 1))
				$variant = 'basic';

			elseif(!strncmp($url, 'actions/view.php', 16))
				$variant = 'action';
			elseif(!strncmp($url, 'action-', 7))
				$variant = 'action';

			elseif(!strncmp($url, 'articles/view.php', 17))
				$variant = 'article';
			elseif(!strncmp($url, 'article-', 8))
				$variant = 'article';

			elseif(!strncmp($url, 'categories/view.php', 19))
				$variant = 'category';
			elseif(!strncmp($url, 'category-', 9))
				$variant = 'category';

			elseif(!strncmp($url, 'comments/view.php', 17))
				$variant = 'comment';
			elseif(!strncmp($url, 'comment-', 8))
				$variant = 'comment';

			elseif(!strncmp($url, 'files/view.php', 14))
				$variant = 'file';
			elseif(!strncmp($url, 'file-', 5))
				$variant = 'file';

			elseif(!strncmp($url, 'images/view.php', 15))
				$variant = 'basic'; // the thumbnail is the image to click on
			elseif(!strncmp($url, 'image-', 6))
				$variant = 'basic'; // the thumbnail is the image to click on

			elseif(!strncmp($url, 'locations/view.php', 18))
				$variant = 'location';
			elseif(!strncmp($url, 'location-', 9))
				$variant = 'location';

			elseif(!strncmp($url, 'sections/view.php', 17))
				$variant = 'section';
			elseif(!strncmp($url, 'section-', 8))
				$variant = 'section';

			elseif(!strncmp($url, 'servers/view.php', 16))
				$variant = 'server';
			elseif(!strncmp($url, 'server-', 7))
				$variant = 'server';

			elseif(!strncmp($url, 'tables/view.php', 15))
				$variant = 'table';
			elseif(!strncmp($url, 'table-', 6))
				$variant = 'table';

			elseif(!strncmp($url, 'users/view.php', 14))
				$variant = 'user';
			elseif(!strncmp($url, 'user-', 5))
				$variant = 'user';

			elseif(!strncmp($url, 'mailto:', 7))
				$variant = 'email';

		}

		// open in a separate window if asked explicitly or on file streaming
		if($new_window || (strpos($url, 'files/stream.php') !== FALSE) || (strpos($url, 'file-stream/') !== FALSE))
			$attributes = ' onclick="window.open(this.href); window.location.reload(); return false;" onkeypress="window.open(this.href); window.location.reload(); return false;"';
		else
			$attributes = '';

		// access key
		if($access_key)
			$attributes .= ' accesskey="'.$access_key.'"';

		// use the link as-is
		if($variant == 'click')
			;

		// malformed url '//server/path' --> 'http://server/path'
		elseif(!strncmp($url, '//', 2))
			$url = 'http:'.$url;

		// fix relative path
		elseif(!preg_match('/^(\/|[a-zA-Z]+:)/', $url)) {

			// email address
			if($variant == 'email')
				$url = 'mailto:'.$url;

			// don't touch script url
			elseif($variant == 'script')
				;

			// ftp server
			elseif(!strncmp($url, 'ftp.', 4))
				$url = 'ftp://'.$url;

			// irc server
			elseif(!strncmp($url, 'irc.', 4))
				$url = 'irc://'.$url;

			// news server
			elseif(!strncmp($url, 'nntp.', 5) || !strncmp($url, 'news.', 5))
				$url = 'news://'.$url;

			// web server
			elseif(!strncmp($url, 'www.', 4))
				$url = 'http://'.$url;

			// adjust self pointers
			elseif(!strncmp($url, '#', 1))
				$url = $context['self_url'].$url;

			// internal address
			elseif($variant != 'external')
				$url = $context['url_to_root'].$url;

		}

		// help crawlers and do not count clicks
		if(is_callable(array('Surfer', 'is_crawler')) && Surfer::is_crawler()) {
			$variant = 'basic';
			$href_title = '';

		// format for a human being
		} else {

			// flag external links
			$external = ($variant == 'external');
			if(!strncmp($url, 'http:', 5) && strncmp($url, 'http://'.$context['host_name'], strlen('http://'.$context['host_name'])))
				$external = TRUE;
			elseif(!strncmp($url, 'https:', 6) && strncmp($url, 'https://'.$context['host_name'], strlen('https://'.$context['host_name'])))
				$external = TRUE;
			elseif(!strncmp($url, 'ftp:', 4) && strncmp($url, 'ftp://'.$context['host_name'], strlen('ftp://'.$context['host_name'])))
				$external = TRUE;

			// default tagging for external links
			if(!$variant && $external)
				$variant = 'external';

			// default processing for external links
			if($external) {

				// finalize the hovering title
				if(!$href_title)
					$href_title = ' title="'.encode_field(i18n::s('Browse in a separate window')).'"';
				else
					$href_title = ' title="'.encode_field(strip_tags($href_title)).'"';

			// internal link
			} else {

				// finalize the hovering title
				if($href_title)
					$href_title = ' title="'.encode_field(strip_tags($href_title)).'"';

			}

		}

		// depending on variant
		switch($variant) {

		case 'article':

			// a default title
			if(!$href_title)
				$href_title = ' title="'.encode_field(i18n::s('View the page')).'"';

			$text = '<a href="'.$url.'"'.$href_title.' class="article"'.$attributes.'>'.$label.'</a>';
			break;

		case 'basic':

			$text = '<a href="'.$url.'"'.$href_title.$attributes.'>'.$label.'</a>';
			break;

		case 'button':

			// always stay in the same window
			$text = '<a href="'.$url.'"'.$href_title.' class="button" '.$attributes.'><span>'.$label.'</span></a>';

			break;

		case 'button-in-email':

			// build a standalone attractive button in an e-mail message
			$text = '<table cellpadding=6 cellspacing=1>'
				.'<tr>'
				.	'<td bgcolor="#ffe86c" style="border: #ccc 1px solid; padding: 9px">'
				.		'<p style="border: none; padding: 0cm; text-align:center">'
				.			'<a href="'.$url.'"'.$href_title.' '.$attributes.' target="_blank">'
				.				'<span style="color: #333333; font-weight: bold">'.$label.'</span>'
				.			'</a>'
				.		'</p>'
				.	'</td>'
				.'</tr>'
				.'</table>';

			break;

		case 'category':

			// a default title
			if(!$href_title)
				$href_title = ' title="'.encode_field(i18n::s('View the category')).'"';

			$text = '<a href="'.$url.'"'.$href_title.' class="category"'.$attributes.'>'.$label.'</a>';
			break;

		case 'click':

			// always count clicks
			$url = $context['url_to_root'].'links/click.php?url='.urlencode($url);

			// always open in a separate window
			$text = '<a href="'.$url.'"'.$href_title.' class="button" onclick="window.open(this.href); window.location.reload(); return false;"><span>'.$label.'</span></a>';

			break;

		case 'comment':

			// a default title
			if(!$href_title)
				$href_title = ' title="'.encode_field(i18n::s('View this comment')).'"';

			$text = '<a href="'.$url.'"'.$href_title.' class="comment"'.$attributes.'>'.$label.'</a>';
			break;

		case 'day':

			// a default title
			if(!$href_title)
				$href_title = ' title="'.encode_field(i18n::s('Daily calendar')).'"';

			$text = ' <a href="'.$url.'"'.$href_title.' class="day"'.$attributes.'>'.$label.'</a> ';
			break;

		case 'email':

			// note that mailto: prefix and obscufacation have to be done beforehand

			// a default title
			if(!$href_title)
				$href_title = ' title="'.encode_field(i18n::s('Send a message')).'"';

			// use obfuscated reference
			$text = '<a href="'.$url.'"'.$href_title.' class="email"'.$attributes.' rel="nofollow">'.$label.'</a>';
			break;

		case 'external':

			$text = '<a href="'.$url.'"'.$href_title.' class="external" onclick="window.open(this.href); window.location.reload(); return false;">'.$label.'</a>';
			break;

		case 'file':

			$text = '<a href="'.$url.'"'.$href_title.' class="file"'.$attributes.'>'.$label.'</a>';
			break;

		case 'help':
			$text = '<a href="'.$url.'"'.$href_title.' class="help"'
				.' onclick="window.open(this.href); return false;"'
				.' onkeypress="window.open(this.href); return false;" rel="nofollow"><span>'.$label.'</span></a>';
			break;

		case 'internal': // like external, but stay in the same window

			// count external clicks
//			$url = $context['url_to_root'].'links/click.php?url='.urlencode($url);

			// a default title
			if(!$href_title)
				$href_title = ' title="'.encode_field(i18n::s('View the page')).'"';

			$text = '<a href="'.$url.'"'.$href_title.' class="external">'.$label.'</a>';
			break;

		case 'menu_1':
			$text = MENU_1_PREFIX.'<a href="'.$url.'"'.$href_title.' class="menu_1"'.$attributes.'><span>'.$label.'</span></a>'.MENU_1_SUFFIX;
			break;

		case 'menu_2':
			$text = MENU_2_PREFIX.'<a href="'.$url.'"'.$href_title.' class="menu_2"'.$attributes.'><span>'.$label.'</span></a>'.MENU_2_SUFFIX;
			break;

		case 'month':

			// a default title
			if(!$href_title)
				$href_title = ' title="'.encode_field(i18n::s('Monthly calendar')).'"';

			$text = ' <a href="'.$url.'"'.$href_title.' class="month"'.$attributes.'>'.$label.'</a> ';
			break;

		case 'more':

			// a default title
			if(!$href_title)
				$href_title = ' title="'.encode_field(i18n::s('More')).'"';

			$text = '<a href="'.$url.'"'.$href_title.$attributes.'>'.$label.'</a>';
			break;

		case 'next':

			// add an icon, except if there is already an image
			if(!preg_match('/<img/i', $label) && defined('NEXT_IMG'))
				$label .= NEXT_IMG;

			// a default title
			if(!$href_title)
				$href_title = ' title="'.encode_field(i18n::s('Next')).'"';

			$text = '<a href="'.$url.'"'.$href_title.' class="next"'.$attributes.'>'.$label.'</a>';
			break;

		case 'previous':

			// add an icon, except if there is already an image
			if(!preg_match('/<img/i', $label) && defined('PREVIOUS_IMG'))
				$label = PREVIOUS_IMG.$label;

			// a default title
			if(!$href_title)
				$href_title = ' title="'.encode_field(i18n::s('Previous')).'"';

			$text = '<a href="'.$url.'"'.$href_title.' class="previous"'.$attributes.'>'.$label.'</a>';
			break;

		case 'raw':
			$text = $url;
			break;

		case 'script':

			// if we have built the documentation, use it
			if(file_exists($context['path_to_root'].'scripts/reference/footprints.php')) {
				if($context['with_friendly_urls'] == 'Y')
					$prefix = 'scripts/view.php/';
				else
					$prefix = 'scripts/view.php?script=';
				$url = $context['url_to_root'].$prefix.$url;

			// else look at the reference server
			} elseif(is_readable($context['path_to_root'].'parameters/scripts.include.php')) {
				include_once $context['path_to_root'].'parameters/scripts.include.php';

				if(!$context['reference_server'])
					$context['reference_server'] = i18n::s('www.yacs.fr');

				// reference server have to be installed at the root
				$url = 'http://'.$context['reference_server'].'/scripts/view.php?script='.$url;

			// or, ultimately, check our origin server -- reference server have to be installed at the root
			} else
				$url = 'http://www.yacs.fr/scripts/view.php/'.$url;

			// a default title
			if(!$href_title)
				$href_title = ' title="'.encode_field(i18n::s('Go to the documentation page')).'"';

			// a link to the phpdoc page
			$text = '<a href="'.$url.'"'.$href_title.' class="script"'.$attributes.' rel="nofollow">'.$label.'</a>';
			break;

		case 'section':

			// a default title
			if(!$href_title)
				$href_title = ' title="'.encode_field(i18n::s('View the page')).'"';

			$text = '<a href="'.$url.'"'.$href_title.' class="section"'.$attributes.'>'.$label.'</a>';
			break;

		case 'server':

			// a default title
			if(!$href_title)
				$href_title = ' title="'.encode_field(i18n::s('See server profile')).'"';

			$text = '<a href="'.$url.'"'.$href_title.' class="server"'.$attributes.'>'.$label.'</a>';
			break;

		case 'shortcut':

			// a default title
			if(!$href_title)
				$href_title = ' title="'.encode_field(i18n::s('Shortcut')).'"';

			$text = '<a href="'.$url.'"'.$href_title.' class="shortcut"'.$attributes.'>'.$label.'</a>';
			break;

		case 'span':

			$text = '<a href="'.$url.'"'.$href_title.$attributes.'><span>'.$label.'</span></a>';
			break;

		case 'idle user':
		case 'user':

			// a default title
			if(!$href_title)
				$href_title = ' title="'.encode_field(i18n::s('View person profile')).'"';

			$text = '<a href="'.$url.'"'.$href_title.' class="'.$variant.'"'.$attributes.'>'.$label.'</a>';
			break;

		case 'xml':

			// a default title
			if(!$href_title)
				$href_title = ' title="'.encode_field(i18n::s('Provide this link to specialized software, such as a RSS news reader')).'"';

			Skin::define_img('XML_IMG', 'tools/xml.gif');
			$text = '<a href="'.$url.'"'.$href_title.' class="xml"'
				.' onclick="window.open(this.href); return false;"'
				.' onkeypress="window.open(this.href); return false;" rel="nofollow">'.XML_IMG.$label.'</a>';
			break;

		case 'year':

			// a default title
			if(!$href_title)
				$href_title = ' title="'.encode_field(i18n::s('Yearly calendar')).'"';

			$text = ' <a href="'.$url.'"'.$href_title.' class="year"'.$attributes.'>'.$label.'</a> ';
			break;

		default:
			if($variant)
				$text = '<a href="'.$url.'"'.$href_title.' class="'.$variant.'"'.$attributes.'>'.$label.'</a>';
			else
				$text = '<a href="'.$url.'"'.$href_title.$attributes.'>'.$label.'</a>';
			break;

		}

		// job done
		return $text;
	}

	/**
	 * build a list of links
	 *
	 * Calls [code]Skin::build_link()[/code] to actually build links, depending on the variant provided.
	 *
	 * Items provided should at list have links and labels.
	 * For advanced lists, each label is replaced by an array of strings.
	 *
	 * Accept following variants:
	 * - '1-column' - links are stacked vertically
	 * - '2-columns' - two stacks of links
	 * - 'assistant_bar' - the bar of commands at the bottom of a page
	 * - 'comma' - a trivial 'xxx, yyy, zzz' list
	 * - 'comma5' - the same but truncated after 5 items
	 * - 'compact' - a <ul class="compact"> list
	 * - 'crumbs' - a list of containers -- should be called once per page
	 * - 'decorated' - to prefix each item with an icon
	 * - 'details' - a compact list of details
	 * - 'menu' - an in-line set of commands (e.g., edit, delete, etc.)
	 * - 'menu_bar' - an horizontal bar of commands
	 * - 'news' - a list of articles, with ids to handle them through Javascript
	 * - 'numbers' - a numbered list
	 * - 'page_menu' - the main list of commands for the page -- should be called once per page
	 * - 'rows' - a vertical stack of links, also suitable for scrolling text
	 * - 'stack' - a stack of items -- used to rotate images in [images=id1, id2, ...], for example
	 * - 'tabs' - the horizontal bar of links repeated everywhere -- should be called once per page
	 * - 'unordered' - a standard <ul> list - also the default
	 *
	 * For variant 'tabs', $link_type actually conveys unique ids.
	 *
	 * To create a set of tabbed panels inside the page, used the function
	 * Skin::build_tabs() instead.
	 *
	 * @param an array of $url => $label or or $url => array($prefix, $label, $suffix, $link_type, $icon_url, $link_title)
	 * @param a variant (e.g., 'bullets', 'numbers', 'compact', ...)
	 * @param absolute path to an image, if any; useful with variants 'decorated' and '2-columns'
	 * @param boolean open links in a separate page if TRUE
	 * $return the HTML code
	*/
	function &build_list(&$items, $variant='unordered', $default_icon=NULL, $new_window=FALSE) {
		global $context;

		// sanity check
		if(!is_array($items))
			return $items;
		if(!count($items)) {
			$output = '';
			return $output;
		}

		// split the list in separate columns
		if(($variant == '2-columns') || ($variant == 'yahoo')) {

			// split the list
			$column_1 = array();
			$column_2 = array();
			$left = TRUE;
			foreach($items as $url => $label) {
				if($left)
					$column_1[$url] = $label;
				else
					$column_2[$url] = $label;
				$left = !$left;
			}

			// align columns
			$text = '<p class="columns_prefix" />';

			// build the left column
			$text .= Skin::build_list($column_1, 'column_1', MAP_IMG, $new_window);

			// build the right column
			if(count($column_2))
				$text .= Skin::build_list($column_2, 'column_2', MAP_IMG, $new_window);

			// clear text after columns
			$text .= '<p class="columns_suffix" />';

			// done
			return $text;
		}

		// a bare reference to an image
		if($default_icon && strncmp($default_icon, '<img ', 5))
			$default_icon = '<img src="'.$default_icon.'" alt="" class="reflect" />';
		elseif($default_icon)
			;

		// some variants require a default icon
		elseif($variant == 'decorated')
			$default_icon = DECORATED_IMG;
		elseif(($variant == '1-column') || ($variant == '2-columns'))
			$default_icon = MAP_IMG;

		// parse and transform the list
		$list = array();
		foreach($items as $url => $label) {

			// split $label as $prefix $label $suffix $type $icon $title
			$prefix = $link = $suffix = $icon = $title = NULL;
			$type = 'basic';
			if(is_array($label) && isset($label[1])) {
				if(isset($label[0]))
					$prefix = $label[0];
				if(isset($label[2]))
					$suffix = $label[2];
				if(isset($label[3]))
					$type	= $label[3];
				if(isset($label[4]))
					$icon	= $label[4];
				if(isset($label[5]))
					$title	= $label[5];
				$label	= $label[1];

			// another host
			} elseif(!strncmp($url, 'http:', 5) && strncmp($url, 'http://'.$context['host_name'], strlen('http://'.$context['host_name'])))
				$type = 'external';
			elseif(!strncmp($url, 'https:', 6) && strncmp($url, 'https://'.$context['host_name'], strlen('https://'.$context['host_name'])))
				$type = 'external';
			elseif(!strncmp($url, 'ftp:', 4) && strncmp($url, 'ftp://'.$context['host_name'], strlen('ftp://'.$context['host_name'])))
				$type = 'external';

			// pass elements ids of the site bar
			$id = '';
			if(($variant == 'tabs') && $type)
				$id = ' id="tab_'.$type.'"';

			// clean labels at occasions --codes have already been transformed here
			if(($variant == 'crumbs') || ($variant == 'tabs'))
				$label = strip_tags($label, '<img>');

			if(($variant == 'column_1') || ($variant == 'column_2'))
				$label = '<span class="box_header">'.$label.'</span>';

			// ease the handling of css, but only for links
			if(($variant == 'tabs') || ($variant == 'menu_bar') || ($variant == 'page_menu')) {
				if(count($list) == 0)
					$label = '<span class="first">'.$label.'</span>';
				elseif(count($list)+1 == count($items))
					$label = '<span class="last">'.$label.'</span>';
				else
					$label = '<span>'.$label.'</span>';
			}

			// the beautified link --if $url is '_', Skin::build_link() will return the label alone
			$link = '';
			if($label != '_')
				$link =& Skin::build_link($url, $label, $type, $title, $new_window);

			// this link comes with an attached image
			if(strpos($icon, '<img ') !== FALSE)
				;

			// we just have a link
			elseif($icon) {

				// fix relative path
				if(!preg_match('/^(\/|http:|https:|ftp:)/', $icon))
					$icon = $context['url_to_root'].$icon;

				// adjust the class
				$class= '';
				if((($variant == 'column_1') || ($variant == 'column_2')) && isset($context['classes_for_thumbnail_images']) && $context['classes_for_thumbnail_images'])
					$class = 'class="'.$context['classes_for_thumbnail_images'].'" ';

				// build the complete HTML element
				$icon = '<img src="'.$icon.'" alt="" title="'.encode_field(strip_tags($label)).'" '.$class.'/>';

			// use default icon if nothing to display
			} else
				$icon = $default_icon;

			// use the image as a link to the target page
			if($icon) {
				if(!$title)
					$title = i18n::s('View the page');
				$icon =& Skin::build_link($url, $icon, 'basic', $title, $new_window);
			}

			// append to the list
			$list[] = array($prefix.$link.$suffix, $icon, $id);
		}

		// finalize the list
		$text =& Skin::finalize_list($list, $variant);

		return $text;
	}

	/**
	 * build a navigation box
	 *
	 * @param string the box title, if any
	 * @param string the box content
	 * @param string an optional unique id for this box
	 * @return the HTML to display
	 */
	function &build_navigation_box($title, &$content, $id='') {
		global $context;

		// this box has a unique id
		if($id)
			$id = ' id="'.$id.'" ';

		// else create our own unique id
		else {
			global $global_navigation_box_index;
			if(!isset($global_navigation_box_index))
				$global_navigation_box_index = 0;
			$id = ' id="navigation_'.++$global_navigation_box_index.'" ';
		}

		// external div boundary
		$text = '<dl class="navigation_box"'.$id.'>'."\n";

		// always add a header
		$text .= '<dt><span>'.$title."</span></dt>\n";

		// box content
		$text .= '<dd>'.$content.'</dd>';

		// external div boundary
		$text .= '</dl>'."\n";

		return $text;
	}

	/**
	 * format a number
	 *
	 * @param the number to format
	 * @param the variant, if any
	 * @return a printable string
	 */
	function &build_number($value, $variant='') {
		global $context;

		$decimals = 0;
		$unit = '';

		// more than one mega
		if($value >= 1048576) {
			$value = $value / 1048576;
			$decimals = 2;
			$unit = 'M';

//		// more than one kilo byte
//		} elseif($value >= 1024) {
//			$value = $value / 1024;
//			$decimals = 2;
//			$unit = 'k';
		}

		// use language standards
		if($context['language'] == 'fr')
			$output = number_format($value, $decimals, ',', ' ').' '.$unit.$variant;
		else
			$output = number_format($value, $decimals, '.', ',').' '.$unit.$variant;

		return $output;
	}

	/**
	 * show presence information
	 *
	 * Supports 'aim', 'icq', 'irc', 'jabber', 'msn', 'skype', 'twitter' and 'yahoo' variants.
	 *
	 * @link http://www.mozilla.org/projects/rt-messaging/chatzilla/irc-urls.html irc: urls in Mozilla
	 * @link http://forum.osnn.net/archive/index.php/t-2010.html sending a message to MSN user w/java script
	 *
	 * @param string the contact identification string
	 * @param string the kind of contact
	 * @return a string with a web link to page the contact
	 *
	 * @see users/layout_users.php
	 */
	function &build_presence($text, $variant) {
		global $context;

		switch($variant) {

		case 'aim':
			$url = 'aim:goim?screenname='.urlencode(trim($text));
			Skin::define_img('AIM_IMG', 'pagers/aim.gif', 'AIM', 'AIM');
			$output = '<a href="'.$url.'" title="'.encode_field(i18n::s('If AOL Instant Messenger has been installed, click to open a session')).'">'.AIM_IMG.'</a>';
			break;

		case 'icq':
			$url = 'http://www.icq.com/whitepages/wwp.php?to='.urlencode(trim($text)).'&amp;action=message';
			Skin::define_img('ICQ_IMG', 'pagers/icq.gif', 'ICQ', 'ICQ');
			$output = '<a href="'.$url.'" title="'.encode_field(i18n::s('If ICQ has been installed, click to open a session')).'">'.ICQ_IMG.'</a>';
			break;

		case 'irc':
			$url = 'irc://'.urlencode(trim($text));
			Skin::define_img('IRC_IMG', 'pagers/irc.gif', 'IRC', 'IRC');
			$output = '<a href="'.$url.'" title="'.encode_field(i18n::s('If some IRC software has been installed, click to open a session')).'">'.IRC_IMG.'</a>';
			break;

		case 'jabber':
			// as per http://juberti.blogspot.com/2006/11/gtalk-uri.html
			$url = 'gtalk:chat?jid='.urlencode(trim($text));
			Skin::define_img('JABBER_IMG', 'pagers/jabber.gif', 'Jabber', 'Jabber');
			$output = '<a href="'.$url.'" title="'.encode_field(i18n::s('If some Jabber software has been installed, click to open a session')).'">'.JABBER_IMG.'</a>';
			break;

		case 'msn':
			$url = "javascript:MsgrApp.LaunchIMUI('".trim($text)."')";
			Skin::define_img('MSN_IMG', 'pagers/msn.gif', 'MSN', 'MSN');
			$output = '<a href="'.$url.'" title="'.encode_field(i18n::s('If Windows Live Messenger has been installed, click to open a session')).'">'.MSN_IMG.'</a>';
			break;

		case 'skype':
			$url = 'callto://'.urlencode(trim($text));
			Skin::define_img('SKYPE_IMG', 'pagers/skype.gif', 'Skype', 'Skype');
			$output = '<a href="'.$url.'" title="'.encode_field(i18n::s('If Skype software is installed, click to open a Skype session')).'">'.SKYPE_IMG.'</a>';
			break;

		case 'twitter':
			$url = 'http://www.twitter.com/'.urlencode(trim($text));
			Skin::define_img('TWITTER_IMG', 'pagers/twitter.gif', 'Twitter', 'Twitter');
			$output = '<a href="'.$url.'" title="'.encode_field(i18n::s('Visit the Twitter page')).'">'.TWITTER_IMG.'</a>';
			break;

		case 'yahoo':
			$url = 'ymsgr:sendim?'.urlencode(trim($text));
			Skin::define_img('YAHOO_IMG', 'pagers/yahoo.gif', 'Yahoo!', 'Yahoo!');
			$output = '<a href="'.$url.'" title="'.encode_field(i18n::s('If Yahoo Messenger has been installed, click to open session')).'">'.YAHOO_IMG.'</a>';
			break;

		default:
			$output = '???';
			break;
		}

		// job done
		return $output;

	}

	/**
	 * build a user profile
	 *
	 *
	 * @param array one user profile
	 * @param string a profiling option, including 'prefix', 'suffix', and 'extra'
	 * @param string more information
	 * @return a string to be returned to the browser
	 *
	 * @see sections/section.php
	 */
	function &build_profile(&$user, $variant='prefix', $more='') {
		global $context;

		// we return some text
		$text = '';

		// label
		$label = (isset($user['full_name'])&&$user['full_name']) ? $user['full_name'] : $user['nick_name'];

		// link to the user profile
		$url = Users::get_permalink($user);

		// configured styles
		$more_styles = '';
		if(isset($context['classes_for_avatar_images']) && $context['classes_for_avatar_images'])
			$more_styles = ' '.encode_field($context['classes_for_avatar_images']);

		// depending of what we want to do
		switch($variant) {

		// at the beginning of the page
		case 'prefix':
		default:

			// avatar
			$avatar = '';
			if(isset($user['avatar_url']) && $user['avatar_url']) {
				$thumb = '';
				if($position = strrpos($user['avatar_url'], '/'))
					$thumb = substr($user['avatar_url'], 0, $position).'/thumbs'.substr($user['avatar_url'], $position);
				if(is_readable($context['path_to_root'].str_replace($context['url_to_root'], '', $thumb)))
					$avatar =& Skin::build_link($url, '<img src="'.$thumb.'" alt="" title="avatar" class="avatar left_image" />', 'basic');
				else
					$avatar =& Skin::build_link($url, '<img src="'.$user['avatar_url'].'" alt="" title="avatar" class="avatar left_image'.$more_styles.'" />', 'basic');
			}

			// several items
			$details = array();

			// date of post
			if($more)
				$details[] = $more;

			// from where
			if(isset($user['from_where']) && $user['from_where'])
				$details[] = sprintf(i18n::s('from %s'), Codes::beautify($user['from_where']));

			// display details
			if(count($details))
				$text .= '<span class="details">'.implode(', ', $details).'</span>'.BR;

			// use the introduction field, if any
			if(isset($user['introduction']) && $user['introduction'])
				$text .= Codes::beautify($user['introduction']);

			// suffix after the full name
			if($text)
				$text = ' -- '.$text;

			$text = '<div class="top">'.$avatar.Skin::build_link($url, $label, 'user').$text.'</div><br style="clear: left;" />';
			break;

		// at the end of the page
		case 'suffix':

			// avatar
			$avatar = '';
			if(isset($user['avatar_url']) && $user['avatar_url']) {
				$thumb = '';
				if($position = strrpos($user['avatar_url'], '/'))
					$thumb = substr($user['avatar_url'], 0, $position).'/thumbs'.substr($user['avatar_url'], $position);
				if(is_readable($context['path_to_root'].str_replace($context['url_to_root'], '', $thumb)))
					$avatar =& Skin::build_link($url, '<img src="'.$thumb.'" alt="" title="avatar" class="avatar left_image" />', 'basic');
				else
					$avatar =& Skin::build_link($url, '<img src="'.$user['avatar_url'].'" alt="" title="avatar" class="avatar left_image'.$more_styles.'" />', 'basic');
			}

			// date of post
			if($more)
				$text .= $more.' ';

			// from where
			if(isset($user['from_where']) && $user['from_where'])
				$text .= sprintf(i18n::s('from %s'), Codes::beautify($user['from_where']));

			// display details
			if($text)
				$text = '<span class="details">'.$text.'</span>'.BR;

			// use the introduction field, if any
			if(isset($user['introduction']) && $user['introduction'])
				$text .= Codes::beautify($user['introduction']);

			// suffix after the full name
			if($text)
				$text = ' -- '.$text;

			$text = '<address>'.$avatar.Skin::build_link($url, $label, 'user').$text.'</address><br style="clear: left;" />';
			break;

		// in a sidebox
		case 'extra':

			// details attributes
			$details = array();

			// avatar
			if(isset($user['avatar_url']) && $user['avatar_url'])
				$details[] =& Skin::build_link($url, '<img src="'.$user['avatar_url'].'" alt="" title="avatar" class="avatar'.$more_styles.'" />', 'basic');
			else if(Surfer::is_empowered()) {
				Skin::define_img('IMAGES_ADD_IMG', 'images/add.gif');
				$details[] =& Skin::build_link(Users::get_url($user['id'], 'select_avatar'), IMAGES_ADD_IMG.i18n::s('Add picture'), 'basic');
			}


			// date of post
			if($more)
				$details[] = $more;

			// from where
			if(isset($user['from_where']) && $user['from_where'])
				$details[] = sprintf(i18n::s('from %s'), Codes::beautify($user['from_where']));

			// details first
			if(count($details))
				$text .= '<p class="details">'.join(BR, $details).'</p>';

			// do not use description because of codes such as location, etc
			if(isset($user['introduction']) && $user['introduction'])
				$text .= Codes::beautify($user['introduction']);

			// show contact information
			if(Surfer::may_contact()) {

				$contacts = Users::build_presence($user);
				if($contacts)
					$text .= BR.$contacts;
			}

			// everything in an extra box
			$text = Skin::build_box($label, $text, 'profile');
			break;
		}

		// return by reference
		return $text;
	}

	/**
	 * build some quote block
	 *
	 * @param string the block content
	 * @return the HTML to display
	 */
	function &build_quote_block($text) {
		global $context;

		$output = '<blockquote><span class="quote_prefix"> &quot; </span>'
			.$text
			.'<span class="quote_suffix"> &quot; </span></blockquote>';
		return $output;
	}

	/**
	 * display some rating
	 *
	 * @param int the rating (1, 2, 3, 4, or 5)
	 * @return the HTML to display
	 */
	function &build_rating_img($rating) {
		global $context;

		if($rating == 1) {
			Skin::define_img('RATING_1_IMG', 'rating/rated_1.gif', '*', '*');
			$output = RATING_1_IMG;
		} elseif($rating == 2) {
			Skin::define_img('RATING_2_IMG', 'rating/rated_2.gif', '**', '**');
			$output = RATING_2_IMG;
		} elseif($rating == 3) {
			Skin::define_img('RATING_3_IMG', 'rating/rated_3.gif', '***', '***');
			$output = RATING_3_IMG;
		} elseif($rating == 4) {
			Skin::define_img('RATING_4_IMG', 'rating/rated_4.gif', '****', '****');
			$output = RATING_4_IMG;
		} elseif($rating == 5) {
			Skin::define_img('RATING_5_IMG', 'rating/rated_5.gif', '*****', '*****');
			$output = RATING_5_IMG;
		} else
			$output = '';
		return $output;
	}

	/**
	 * display the list of referrals
	 *
	 * @param string script name
	 * @return string to be displayed in the extra panel
	 */
	function &build_referrals($script) {
		global $context;

		$output = '';
		if(Surfer::is_associate() || (isset($context['with_referrals']) && ($context['with_referrals'] == 'Y'))) {

			$cache_id = $script.'#referrals';
			if(!$output =& Cache::get($cache_id)) {

				// box content in a sidebar box
				include_once $context['path_to_root'].'agents/referrals.php';
				if($items = Referrals::list_by_hits_for_url($context['url_to_root_parameter'].$script))
					$output =& Skin::build_box(i18n::s('Referrals'), $items, 'referrals', 'referrals');

				// save in cache for 5 minutes 60 * 5 = 300
				Cache::put($cache_id, $output, 'stable', 300);

			}
		}

		return $output;
	}

	/**
	 * build a sidebar box
	 *
	 * @param string the box title, if any
	 * @param string the box content
	 * @param string an optional unique id for this box
	 * @return the HTML to display
	 */
	function &build_sidebar_box($title, &$content, $id) {
		global $context;

		// this box has a unique id
		if($id)
			$id = ' id="'.$id.'" ';

		// else create our own unique id
		else {
			static $global_sidebar_box_index;
			if(!isset($global_sidebar_box_index))
				$global_sidebar_box_index = 0;
			$id = ' id="sidebar_'.++$global_sidebar_box_index.'" ';
		}

		// external div boundary
		$text = '<div class="sidebar_box"'.$id.'>'."\n";

		// always add a header
		if($title)
			$text .= '<h3><span>'.$title."</span></h3>\n";

		// box content
		$text .= '<div class="sidebar_body">'.$content.'</div>';

		// external div boundary
		$text .= '</div>'."\n";

		return $text;
	}

	/**
	 * build sliding content
	 *
	 * @param string the box title, if any
	 * @param string the box content
	 * @param string an optional unique id for this box
	 * @param boolean TRUE to align left border of the sliding panel
	 * @return the HTML to display
	 */
	function &build_sliding_box($title, &$content, $id=NULL, $onLeft=TRUE, $down=TRUE) {
		global $context;

		// the icon used to slide down
		Skin::define_img_href('SLIDE_DOWN_IMG_HREF', 'layouts/slide_down.gif');

		// the icon used to slide up
		Skin::define_img_href('SLIDE_UP_IMG_HREF', 'layouts/slide_up.gif');

		// we need a clickable title
		if(!$title)
			$title = i18n::s('Content');

		// this box has a unique id
		if($id)
			$id = ' id="'.$id.'" ';

		// external boundary
		$text = '<div class="sliding_box" style="display: inline;"'.$id.'>'."\n";

		// an image to enhance rendering
		$img = '';
		if(SLIDE_DOWN_IMG_HREF)
			$img = '<img src="'.SLIDE_DOWN_IMG_HREF.'" alt="" title="'.encode_field(i18n::s('Click to slide')).'" /> ';

		if($onLeft === FALSE)
			$onLeft = ', false';
		else
			$onLeft = ', true';

		if($down === FALSE)
			$down = ', false';
		else
			$down = ', true';

		// title is optional
		$text .= '<a href="#" class="handle" onclick="javascript:Yacs.slidePanel(this, \''.SLIDE_DOWN_IMG_HREF.'\', \''.SLIDE_UP_IMG_HREF.'\''.$onLeft.$down.'); return false;"><span>'.$title.'</span>'.$img.'</a>';

		// box content has no div, it is already structured
		$text .= '<div class="panel" style="display: none;">'.$content.'</div>';

		// external boundary
		$text .= '</div>'."\n";

		return $text;
	}

	/**
	 * build a list of popular subscribing services
	 *
	 * You can derive this into your skin.php to change the number and kind
	 * of featured links.
	 *
	 * @param string link to one of our feed
	 * @param string title to be used
	 * @return array a list of links to subscribers
	 */
	function build_subscribers($url, $title='') {
		global $context;

		// sanity check
		if(!$title)
			$title = $context['site_name'];

		// subscribing links
		$items = array();

		// an easy link to addthis bookmarks
		if(file_exists($context['path_to_root'].'skins/_reference/feeds/addthis0-bm.gif'))
			$items[] = '<a href="http://www.addthis.com/bookmark.php?pub='.urlencode($context['site_name']).'&amp;url='.urlencode($url).'&amp;title='.urlencode($title).'" onclick="window.open(this.href); return false;">'
				.'<img src="'.$context['url_to_root'].'skins/_reference/feeds/addthis0-bm.gif" width="83" height="16" alt="AddThis Social Bookmark Button" />'
				.'</a>';

		// an easy link to addthis feeds
		if(file_exists($context['path_to_root'].'skins/_reference/feeds/addthis0-fd.gif'))
			$items[] = '<a href="http://www.addthis.com/feed.php?&amp;h1='.urlencode($url).'&amp;t1='.urlencode($title).'pub='.urlencode($context['site_name']).'" onclick="window.open(this.href); return false;">'
				.'<img src="'.$context['url_to_root'].'skins/_reference/feeds/addthis0-fd.gif" width="83" height="16" alt="AddThis Feed Button" />'
				.'</a>';

		// job done
		return $items;
	}

	/**
	 * build a submit button
	 *
	 * @param string button label
	 * @param string popup to be displayed on hovering, if any
	 * @param string access key, if any
	 * @param string object name and id, if any
	 * @param string class, if any
	 * @return the HTML to display
	 */
	function &build_submit_button($label, $title=NULL, $access_key=NULL, $id=NULL, $class='button') {
		global $context;

		// sanity check
		if(!$label)
			$label = i18n::s('Submit');

		// this is an input button
		$text = '<span class="button"><button type="submit"';

		// hovering title
		if($title)
			$text .= ' title="'.encode_field($title).'"';

		// access key
		if($access_key)
			$text .= ' accesskey="'.encode_field($access_key).'"';

		// style id
		if($id)
			$text .= ' id="'.encode_field($id).'" name="'.encode_field($id).'"';

		// style class
		if($class)
			$text .= ' class="'.encode_field($class).'"';

		// button label
		$text .= '>'.$label;

		// end of button
		$text .= '</button></span>';

		return $text;
	}

	/**
	 * build tabs
	 *
	 * [php]
	 * $context['text'] .= Skin::build_tabs(array(
	 *	 array('tab1', 'Tab1', 'panel1', 'content of first panel'),
	 *	 array('tab2', 'Tab2', 'panel2', 'content of second panel', '/panel2_update.html')
	 *	 array('tab3', 'Tab3', 'panel3', NULL, '/panel3_update.html')
	 *	 ));
	 * [/php]
	 *
	 * @param array the list of target tabs and panels
	 * @return string the HTML snippet
	 *
	 * @see users/view.php
	 */
	function &build_tabs($tabs) {
		global $context;

		// the generated text
		$tabs_text = '';
		$panels_text = '';
		$js_text = '';

		// sanity check
		if(!@count($tabs))
			return $tabs_text;

		// only one tab to be displayed
		if(count($tabs) == 1) {
			$tabs_text .= '<div id="'.$tabs[0][2].'" style="margin-top: 1em;">'.$tabs[0][3].'</div>';
			return $tabs_text;
		}

		// process each parameter separately -- style names are hardcoded in shared/ajax.js
		$index = 0;
		$js_lines = array();
		foreach($tabs as $tab) {

			// populate tabs
			$tabs_text .= '<li id="_'.$tab[0].'"';

			if(!$index)
				$tabs_text .= ' class="tab-foreground"';
			else
				$tabs_text .= ' class="tab-background"';

			$tabs_text .= '><a href="#_'.$tab[0].'">'.$tab[1].'</a></li>'."\n";

			// populate panels
			$panels_text .= '<div id="'.$tab[2].'"';

			if(!$index)
				$panels_text .= ' class="panel-foreground"';
			else
				$panels_text .= ' class="panel-background"';

			$panels_text .= '>';

			if(isset($tab[3]))
				$panels_text .= $tab[3];

			$panels_text .= '</div>'."\n";

			// populate the javascript loader
			if(isset($tab[4]))
				$js_lines[] = "'_".$tab[0]."': [ '".$tab[2]."', '".$context['url_to_home'].$context['url_to_root'].$tab[4]."' ]";
			else
				$js_lines[] = "'_".$tab[0]."': [ '".$tab[2]."' ]";

			// next tab
			$index++;
		}

		// finalize tabs
		$tabs_text = "\n".'<div id="tabs_bar"><ul>'."\n".$tabs_text.'</ul></div>'."\n";

		// finalize panels
		$panels_text = "\n".'<div id="tabs_panels">'."\n".$panels_text.'</div>'."\n";

		// finalize javascript loader
		$js_text .= "\n".JS_PREFIX
			.'// use the YACS AJAX library to manage tabs'."\n"
			."$(document).ready(function() { Yacs.tabs({"."\n"
			."\t".implode(",\n\t", $js_lines)."}, {})\n"
			."\t});"."\n"
			.JS_SUFFIX."\n";

		// package all components together
		$text = "\n".$tabs_text.$panels_text.$js_text."\n";
		return $text;
	}

	/**
	 * build linked tags
	 *
	 * @param string the full list of tags
	 * @return string HTML tags to be put in the resulting page
	 */
	function &build_tags($tags) {
		global $context;

		$text = '';

		// list existing tags
		$tags = explode(',', $tags);
		foreach($tags as $tag) {
			if(!$tag = trim($tag))
				continue;
			if($category = Categories::get_by_keyword($tag)) {

				// add background color to distinguish this category against others
				if(isset($category['background_color']) && $category['background_color'])
					$tag = '<span style="background-color: '.$category['background_color'].'; padding: 0 3px 0 3px;">'.$tag.'</span>';

				$text .= Skin::build_link(Categories::get_permalink($category), $tag, 'basic').' ';
			} else
				$text .= $tag.' ';
		}
		$text = rtrim($text, ' ');

		// a link to add a tag

		return $text;
	}

	/**
	 * format a time stamp
	 *
	 * Accept either a time stamp, or a formatted string as input parameter:
	 * - YYYY-MM-DD HH:MM:SS
	 * - YYMMDD HH:MM:SS GMT
	 *
	 * @param int or string the time to be displayed
	 * @param string the variant -- reserved for future use
	 * @param string the language to express this stamp
	 * @return string the HTML to be used
	 */
	function &build_time($stamp, $variant=NULL, $language=NULL) {
		global $context, $local;

		// return by reference
		$output = '';

		// sanity check
		if(!isset($stamp) || !$stamp)
			return $output;

		// surfer offset
		$surfer_offset = Surfer::get_gmt_offset();

		// YYMMDD-HH:MM:SS GMT -- this one is natively GMT
		if(preg_match('/GMT$/', $stamp) && (strlen($stamp) == 19)) {

			// YYMMDD-HH:MM:SS GMT -> HH, MM, SS, MM, DD, YY
			$actual_stamp = mktime(substr($stamp, 7, 2), substr($stamp, 10, 2), substr($stamp, 13, 2),
				substr($stamp, 2, 2), substr($stamp, 4, 2), substr($stamp, 0, 2));

			// adjust to surfer time zone
			$actual_stamp += ($surfer_offset * 3600);

		// time()-like stamp
		} elseif(intval($stamp) > 1000000000) {

			// adjust to surfer time zone
			$actual_stamp = intval($stamp) + ($surfer_offset * 3600);

		// YYYY-MM-DD HH:MM:SS, or a string that can be readed
		} elseif(($actual_stamp = strtotime($stamp)) != -1) {

			// adjust to surfer time zone
			$actual_stamp += ($surfer_offset * 3600);

		} else {
			$output = '*'.$stamp.'*';
			return $output;
		}

		if(!$items = @getdate($actual_stamp)) {
			$output = '*'.$stamp.'*';
			return $output;
		}

		// if undefined language, use surfer language
		if(!$language)
			$language = $context['language'];

		// format the time
		$local['label_en'] = date('h:i a', $actual_stamp);
		$local['label_fr'] = date('H:i', $actual_stamp);
		$output = i18n::user('label');
		return $output;

	}

	/**
	 * build a table of content
	 *
	 * @param string the box title, if any
	 * @param string the box content
	 * @param string an optional unique id for this box
	 * @return the HTML to display
	 */
	function &build_toc_box($title, &$content, $id) {
		global $context;

		// the icon used to slide down
		Skin::define_img_href('SLIDE_DOWN_IMG_HREF', 'layouts/slide_down.gif');

		// the icon used to slide up
		Skin::define_img_href('SLIDE_UP_IMG_HREF', 'layouts/slide_up.gif');

		// we need a clickable title
		if(!$title)
			$title = i18n::s('Content');

		// this box has a unique id
		if($id)
			$id = ' id="'.$id.'" ';

		// external div boundary
		$text = '<div class="toc_box"'.$id.'>'."\n";

		// an image to enhance rendering
		$img = '';
		if(SLIDE_DOWN_IMG_HREF)
			$img = '<img src="'.SLIDE_DOWN_IMG_HREF.'" alt="" title="'.encode_field(i18n::s('Click to slide')).'" /> ';

		// title is optional
		$text .= '<a href="#" class="handle" onclick="javascript:Yacs.slidePanel(this, \''.SLIDE_DOWN_IMG_HREF.'\', \''.SLIDE_UP_IMG_HREF.'\'); return false;"><span>'.$title.'</span>'.$img.'</a>';

		// box content has no div, it is already structured
		$text .= '<div class="panel" style="display: none;">'.$content.'</div>';

		// external div boundary
		$text .= '</div>'."\n";

		return $text;
	}

	/**
	 * build a table of questions
	 *
	 * @param string the box title, if any
	 * @param string the box content
	 * @param string an optional unique id for this box
	 * @return the HTML to display
	 */
	function &build_toq_box($title, &$content, $id) {
		global $context;

		// we need a clickable title
		if(!$title)
			$title = i18n::s('Questions');

		// this box has a unique id
		if($id)
			$id = ' id="'.$id.'" ';

		// external div boundary
		$text = '<div class="toq_box"'.$id.'>'."\n";

		// title is optional
		if($title)
			$text .= '<h3 class="handle"><span>'.$title."</span></h3>\n";

		// box content
		$text .= '<div class="content">'.$content.'</div>';

		// external div boundary
		$text .= '</div>'."\n";

		return $text;
	}

	/**
	 * build a tree of links
	 *
	 * @param an array of array($url, $prefix, $label, $suffix, $link_type, $icon_url, $link_title, $sub_items)
	 * @param int depth level
	 * @return string
	 */
	function &build_tree($data, $level=0, $current_id='') {
		global $context;

		// sanity check
		if(!count($data)) {
			$output = NULL;
			return $output;
		}

		// item class
		$class = 'tree_level_'.($level+1);

		// top level
		if(!$level)
			$class = 'tree '.$class;

		// use unordered lists
		$text = '<ul class="'.$class.'">';

		// process each item
		$count = count($data);
		foreach($data as $item) {

			// sanity check
			if(!is_array($item))
				continue;

			// split $item as $prefix $label $suffix $type $icon $title $items
			$url	= $item[0];
			$prefix = $item[1];
			$label	= $item[2];
			$suffix = $item[3];
			$type = NULL;
			if(isset($item[4]))
				$type = $item[4];
			$icon = NULL;
			if(isset($item[5]))
				$icon = $item[5];
			$title = NULL;
			if(isset($item[6]))
				$title = $item[6];
			$items = NULL;
			if(isset($item[7]))
				$items = $item[7];

			// secure the label
			$label = Skin::strip($label);

			// feature minimum information
			$label = preg_replace('/\s+\(.+?\)/', '', $label);

			// process sub_items, if any
			if(is_array($items))
				$items =& Skin::build_tree($items, $level+1, $current_id);

			// special rendering for the item that has the focus
			$id = ' ';
			if(($type == 'current') && $current_id)
				$id .= 'id="'.$current_id.'" ';

			// item class
			$class = '';

			// special rendering for open items
			if($type == 'open')
				$class .= 'open ';

			// special rendering for last item
			if(!--$count)
				$class .= 'last ';

			// finalize this item
			if($class)
				$text .= '<li'.$id.'class="'.$class.'">';
			else
				$text .= '<li'.$id.'>';
			$text .= $prefix.Skin::build_link($url, $label, $type, $title).$suffix
				.$items
				.'</li>';

		}

		// finalize this level
		$text .= '</ul>';

		return $text;
	}

	/**
	 * build a unfolded box
	 *
	 * Based on DHTML and DOM. The Javascript [code]toggle_folder()[/code] code
	 * is inserted at the bottom of the page in [script]shared/global.php[/script]
	 *
	 * @link http://www.dddekerf.dds.nl/DHTML_Treeview/DHTML_Treeview.htm Easy DHTML TreeView
	 *
	 * @param string the box title, if any
	 * @param string the box content
	 * @param string an optional unique id for this box
	 * @return the HTML to display
	 *
	 * @see articles/edit.php
	 * @see sections/edit.php
	 * @see users/edit.php
	 */
	function &build_unfolded_box($title, &$content, $id='') {
		global $context;

		// the icon used to stretch folder divisions
		Skin::define_img_href('FOLDER_EXTEND_IMG_HREF', 'layouts/folder_plus.gif');

		// the icon used to pack folder divisions
		Skin::define_img_href('FOLDER_PACK_IMG_HREF', 'layouts/folder_minus.gif');

		// we need a clickable title
		if(!$title)
			$title = i18n::s('Click to slide');

		if($id)
			$id = ' id="'.$id.'"';

		// maybe we have an image to enhance rendering
		$img = '';
		if(FOLDER_PACK_IMG_HREF)
			$img = '<img src="'.FOLDER_PACK_IMG_HREF.'" alt="" title="'.encode_field(i18n::s('Click to slide')).'" /> ';

		// Yacs.toggle_folder() is in shared/yacs.js
		$text = '<div class="folder_box"'.$id.'><a href="#" class="folder_header" onclick="javascript:Yacs.toggle_folder(this, \''.FOLDER_EXTEND_IMG_HREF.'\', \''.FOLDER_PACK_IMG_HREF.'\'); return false;">'.$img.$title.'</a>'
			.'<div class="folder_body" style="display: block"><div>'.$content."</div></div></div>\n";

		// pass by reference
		return $text;

	}

	/**
	 * build the navigation menu for this surfer
	 *
	 * Actually delegates the job to [code]Surfer::build_user_menu()[/code].
	 * You can overload this function in your own skin to change this behaviour.
	 *
	 * @param string the type of each link
	 * @return string to be displayed as user menu
	 *
	 * @see shared/surfer.php
	 */
	function &build_user_menu($type = 'submenu') {
		global $context;

		$output =& Surfer::build_user_menu($type);
		return $output;
	}

	/**
	 * cap the number of words in an HTML string
	 *
	 *
	 * @param string the string to abbreviate
	 * @param int the maximum number of words
	 * @param string the link to get the full version
	 * @param string a label for the follow-up link, if any
	 * @return the HTML to display
	 */
	function &cap($input, $count=300, $url=NULL, $label = '') {
		global $context;

		// mention tags used as block boundary, no more -- else execution time will ramp up...
		$areas = preg_split('/<(blockquote|code|div|dl|h1|h2|h3|noscript|ol|p|pre|script|table|ul)(.*?)>(.*?)<\/\1>/is', $input, -1, PREG_SPLIT_DELIM_CAPTURE);

		// process each pair
		$text = '';
		$index = 0;
		foreach($areas as $area) {

			switch($index%4) {
			case 0: // outside pairing tags

				// cap on paragraph boundary (<br /><br />) as well
				$paragraphs = preg_split('/<br\s*?\/{0,1}><br\s*?\/{0,1}>/i', $area, -1, PREG_SPLIT_NO_EMPTY);
				$area = '';
				foreach($paragraphs as $paragraph) {
					if($area)
						$area .= BR.BR;

					// we won't dedicate too much CPU cycles to parse further
					$paragraph = strip_tags($paragraph);

					// count words
					$words = preg_split("/([\s,\.;\?!]+)/", $paragraph, -1, PREG_SPLIT_OFFSET_CAPTURE);

					// limit number of words
					if(count($words) > $count)
						$area .= substr($paragraph, 0, $words[$count][1]);
					else
						$area .= $paragraph;

					// stop on limit
					$count -= count($words);
					if($count < 0)
						break;
				}

				$text .= $area;
				break;
			case 1: // area boundary
				$tag = $area;
				break;
			case 2: // area boundary attributes
				$attributes = $area;
				break;
			case 3: // inside area

				// don't touch tables and lists
				$text .= '<'.$tag.$attributes.'>'.$area.'</'.$tag.'>';

				// approximate number of words in area
				$words = preg_split("/([\s,\.;\?!]+)/", strip_tags($area));
				if(count($words))
					$count -= count($words);
				break;
			}
			$index++;

			// stop on limit
			if($count < 0)
				break;
		}

		// remove trailing punctuation chars
		$text = rtrim($text, " \t\n\r\f.,:");

		// signal that some text is missing
		if($count < 0)
			$text .= ' ...';

		// add a link to the full version
		if(($count < 0) && $url) {
			if(!$label)
				$label = i18n::s('more').MORE_IMG;
			$text .= ' '.Skin::build_link($url, $label, 'more').' ';
		}

		return $text;

	}

	/**
	 * define a constant img tag
	 *
	 * mainly called from initialize(), in skins/skin_skeleton.php, and as Skin::initialize()
	 *
	 * @param string constant name, in upper case
	 * @param string file name for this image
	 * @param string to be used if the file does not exists
	 * @param string to be displayed in textual browsers
	 * @param string options to be integrated into the img tag, if any
	 */
	function define_img($name, $file, $default='', $alternate='', $options='') {
		global $context;

		// sanity check
		if(defined($name))
			return;

		// make an absolute path to image, in case of export (freemind, etc.)
		if($size = Safe::GetImageSize($context['path_to_root'].$context['skin'].'/'.$file))
			define($name, '<img src="'.$context['url_to_home'].$context['url_to_root'].$context['skin'].'/'.$file.'" '.$size[3].' alt="'.$alternate.'" '.$options.'/> ');
		elseif($size = Safe::GetImageSize($context['path_to_root'].'skins/_reference/'.$file))
			define($name, '<img src="'.$context['url_to_home'].$context['url_to_root'].'skins/_reference/'.$file.'" '.$size[3].' alt="'.$alternate.'" '.$options.'/> ');
		else
			define($name, $default);
	}

	/**
	 * define a constant img tag
	 *
	 * mainly called from initialize(), in skins/skin_skeleton.php, and as Skin::initialize()
	 *
	 * @param string constant name, in upper case
	 * @param string file name for this image
	 */
	function define_img_href($name, $file, $default='') {
		global $context;

		// sanity check
		if(defined($name))
			return;

		// make an absolute path to image, in case of export (freemind, etc.)
		if(file_exists($context['path_to_root'].$context['skin'].'/'.$file))
			define($name, $context['url_to_home'].$context['url_to_root'].$context['skin'].'/'.$file);
		elseif(file_exists($context['path_to_root'].'skins/_reference/'.$file))
			define($name, $context['url_to_home'].$context['url_to_root'].'skins/_reference/'.$file);
		else
			define($name, $default);
	}

	/**
	 * add some error message
	 *
	 * @obsolete
	 * @param string the additional error message
	 *
	 */
	function error($line) {
		Logger::error($line);
	}

	/**
	 * pop last error message
	 *
	 * @obsolete
	 * @return string most recent error message, or NULL
	 */
	function error_pop() {
		return Logger::error_pop();
	}

	/**
	 * finalize context before page rendering
	 *
	 * This function is used to finalize the $context array before actual page rendering.
	 * You can overlay this function in your skin to populate $context['components'] and then
	 * use the configuration panel for the rendering factory to have these taken into account by yacs.
	 *
	 * @see skins/configure.php
	 *
	 */
	function finalize_context() {
		global $context;

	}

	/**
	 * finalize a list
	 *
	 * This function is called at the end of ##Skin::build_list## to assemble
	 * all links together. It can also be invoked directly, for example to
	 * put some Javascript in a menu.
	 *
	 * @param array a list of string, or of array($link, $icon)
	 * @param string the list variant - see available values in Skin::build_list()
	 * @return string text to be put in page
	 */
	function &finalize_list($list, $variant) {
		global $context;

		// decorate the list according to the selected variant
		$text = '';
		switch($variant) {

			// actually, a definition list to be shaped through css with selector: dl.column
			case '1-column':

				foreach($list as $item) {
					list($label, $icon) = $item;
					if($icon)
						$text .= '<dt>'.$icon.'</dt>';
					$text .= '<dd>'.$label.'</dd>'."\n";
				}

				$text = '<dl class="column">'."\n".$text.'</dl>'."\n";
				break;

			// use css selector: p.assistant_bar, or customize constants in skin.php -- icons are dropped, if any
			case 'assistant_bar':

				$line_count = 0;
				foreach($list as $label) {

					// between two items
					if($line_count++) {
						if(MENU_SEPARATOR)
							$text .= MENU_SEPARATOR;
						else
							$text .= ' ';
					}

					// drop the icon
					if(is_array($label))
						$label = $label[0];

					// mark first and last items
					if($line_count == 1)
						$text .= '<span class="first">'.$label.'</span>';
					elseif($line_count == count($list))
						$text .= '<span class="last">'.$label.'</span>';
					else
						$text .= $label;
				}

				$text = Skin::build_block('<p class="menu_bar">'.MENU_PREFIX.$text.MENU_SUFFIX.'</p>', 'bottom');
				break;

			// left and right columns for the 2-columns layout; actually, a definition list to be shaped through css with selectors: dl.column_1 and dl.column_2
			case 'column_1':
			case 'column_2':

				foreach($list as $item) {
					list($label, $icon) = $item;
					if($icon)
						$text .= '<dt>'.$icon.'</dt>';
					$text .= '<dd>'.$label.'</dd>'."\n";
				}

				$text = '<dl class="'.$variant.'">'."\n".$text.'</dl>'."\n";
				break;

			// separate items with commas
			case 'comma':

				$line_count = 0;
				foreach($list as $label) {

					// between two items
					if($line_count++)
						$text .= ', ';

					// drop the icon
					if(is_array($label))
						$label = $label[0];

					$text .= $label;
				}

				break;

			// separate items with commas
			case 'comma5':

				$line_count = 0;
				foreach($list as $label) {

					// between two items
					if($line_count++)
						$text .= ', ';

					// limit ourselves to 5 items
					if($line_count > 5) {
						$text .= '...';
						break;
					}

					// drop the icon
					if(is_array($label))
						$label = $label[0];

					$text .= $label;
				}

				break;

			// use css selector: ul.compact, or customize constants in skin.php -- icons are dropped, if any
			case 'compact':
			case 'details':
			case 'hits':
			case 'rating':
			case 'simple':

				// basic rendering for hard printouts
				if(isset($skin['variant']) && ($skin['variant'] == 'print')) {

					$line_count = 0;
					foreach($list as $label) {

						// drop the icon
						if(is_array($label))
							$label = $label[0];

						// make it small
						if($variant == 'details')
							$label = '<span class="details">'.$label.'</span>';

						$text .= '<li>'.$label.'</li>'."\n";
					}

					$text =  '<ul class="compact">'."\n".$text.'</ul>'."\n";

				// regular rendering
				} else {

					$line_count = 0;
					foreach($list as $label) {

						// between two items
						if($line_count++)
							$text .= COMPACT_LIST_SEPARATOR;

						// drop the icon
						if(is_array($label))
							$label = $label[0];

						// make it small
						if($variant == 'details')
							$label = '<span class="details">'.$label.'</span>';

						$text .= '<li>'.COMPACT_LIST_ITEM_PREFIX.$label.COMPACT_LIST_ITEM_SUFFIX.'</li>'."\n";
					}

					$text = COMPACT_LIST_PREFIX.'<ul class="compact">'."\n".$text.'</ul>'.COMPACT_LIST_SUFFIX."\n";

				}
				break;

			// use css selector: p#crumbs, or customize constants in skin.php -- icons are dropped, if any
			case 'crumbs':

				$line_count = 0;
				foreach($list as $label) {

					// between two items
					if($line_count++) {
						if(CRUMBS_SEPARATOR)
							$text .= CRUMBS_SEPARATOR;
						else
							$text .= ' ';
					}

					// drop the icon
					if(is_array($label))
						$label = $label[0];

					$text .= $label;
				}

				$text = '<p id="crumbs">'.CRUMBS_PREFIX.$text.CRUMBS_SUFFIX."</p>\n";
				break;

			// items are neatly aligned in a table; use css selectors: table.decorated, td.odd, td.even
			case 'decorated':

				$line_count = 0;
				foreach($list as $item) {

					// flag oddity
					if($line_count++%2)
						$text .= '<tr class="odd">';
					else
						$text .= '<tr class="even">';

					list($label, $icon) = $item;
					$text .= '<td class="image" style="text-align: center">'.$icon.'</td><td class="content">'.$label.'</td></tr>'."\n";
				}

				$text = '<table class="decorated">'."\n".$text.'</table>'."\n";
				break;

			// an in-line menu to be customized through constants in skin.php
			case 'menu':

				$line_count = 0;
				foreach($list as $label) {

					// between two items
					if($line_count++) {
						if(INLINE_MENU_SEPARATOR)
							$text .= INLINE_MENU_SEPARATOR;
						else
							$text .= ' ';
					}

					// drop the icon
					if(is_array($label))
						$label = $label[0];

					$text .= $label;
				}

				// separate from preceding and following text
				$text = ' '.INLINE_MENU_PREFIX.$text.INLINE_MENU_SUFFIX.' ';
				break;

			// use css selector: p.menu_bar, or customize constants in skin.php -- icons are dropped, if any
			case 'menu_bar':

				$line_count = 0;
				foreach($list as $label) {

					// between two items
					if($line_count++) {
						if(MENU_SEPARATOR)
							$text .= MENU_SEPARATOR;
						else
							$text .= ' ';
					}

					// drop the icon
					if(is_array($label))
						$label = $label[0];

					// mark first and last items
					if($line_count == 1)
						$text .= '<span class="first">'.$label.'</span>';
					elseif($line_count == count($list))
						$text .= '<span class="last">'.$label.'</span>';
					else
						$text .= $label;

				}

				$text = '<p class="menu_bar">'.MENU_PREFIX.$text.MENU_SUFFIX."</p>\n";
				break;

			// some news, that can be statically displayed, scrolled or rotated
			case 'news':

				// process each news
				$news = array();
				for($index = 0; $index < count($list); $index++) {

					// image comes after text
					list($label, $icon) = $list[$index];
					if($icon)
						$label .= BR.$icon;

					// create a unique id for each news item
					static $global_news_index;
					if(!isset($global_news_index))
						$global_news_index = 0;
					$id = 'id="news_'.++$global_news_index.'"';

					// separator between items
					$separator = '';
					if($index+1 < count($list))
						$separator = NEWS_SEPARATOR;

					// one division per item
					$news[] = '<li '.$id.'>'.$label.$separator.'</li>';
				}

				// use constants to finalize rendering, where applicable
				$text = NEWS_PREFIX.'<ul>'.implode("\n", $news).'</ul>'.NEWS_SUFFIX;
				break;

			// the regular <ol> list -- icons are dropped, if any
			case 'numbers':

				foreach($list as $label) {

					if(is_array($label))
						$label = $label[0];

					$text .= '<li>'.$label.'</li>'."\n";
				}

				$text = '<ol>'."\n".$text.'</ol>'."\n";
				break;

			// use css selector: p#page_menu, or customize constants in skin.php -- icons are dropped, if any
			case 'page_menu':

				$line_count = 0;
				foreach($list as $label) {

					// between two items
					if($line_count++) {
						if(PAGE_MENU_SEPARATOR)
							$text .= PAGE_MENU_SEPARATOR;
						else
							$text .= ' ';
					}

					// drop the icon
					if(is_array($label))
						$label = $label[0];

					$text .= $label;
				}

				$text = '<p id="page_menu">'.PAGE_MENU_PREFIX.$text.PAGE_MENU_SUFFIX."</p>\n";
				break;

			// items are stacked; use css selectors: div.odd, div.even
			case 'rows':

				$line_count = 0;
				foreach($list as $label) {

					// flag oddity
					if($line_count++%2)
						$text .= '<div class="odd">';
					else
						$text .= '<div class="even">';

					// image comes after text
					if(is_array($label))
						$label = $label[0].BR.$label[1];

					$text .= $label.'</div>'."\n";
				}

				break;

			// a stack of items, that can be statically displayed, scrolled or rotated
			case 'stack':

				// process each item
				$stack = array();
				for($index = 0; $index < count($list); $index++) {

					// image comes after text
					if(is_array($list[$index]))
						$label = $list[$index][0].BR.$list[$index][1];
					else
						$label = $list[$index];

					// create a unique id for each stack item
					static $global_stack_index;
					if(!isset($global_stack_index))
						$global_stack_index = 0;
					$id = 'id="stack_'.++$global_stack_index.'"';

					// one division per item
					$stack[] = '<div '.$id.'>'.$label.'</div>';
				}

				// use constants to finalize rendering, where applicable
				$text = '<div class="stack">'.implode("\n", $stack).'</div>';
				break;

			// to handle tabs; use css selector div.tabs, etc. or override constants in skin.php
			case 'tabs':

				$line_count = 0;
				foreach($list as $item) {

					// between two items
					if($line_count++)
						$text .= TABS_SEPARATOR;

					// drop the icon, but use the id -- label is already bracketed with prefix and suffix
					list($label, $icon, $id) = $item;
					$text .= '<li'.$id.'>'.TABS_ITEM_PREFIX.$label.TABS_ITEM_SUFFIX.'</li>'."\n";
				}

				$text = '<div class="tabs">'.TABS_PREFIX.'<ul>'."\n".$text.'</ul>'.TABS_SUFFIX."</div>\n";
				break;

			// similar to compact
			case 'newlines':
			case 'tools':

				if($list) {
					foreach($list as $label) {

						$icon = '';
						if(is_array($label))
							list($label, $icon) = $label;

						if($text)
							$text .= BR;
						$text .= $icon.$label;
					}
				}

				break;

			// the regular <ul> list -- icons are dropped, if any
			default:
			case 'unordered':

				$first = TRUE;
				foreach($list as $label) {

					$class = '';
					if($first) {
						$class = ' class="first"';
						$first = FALSE;
					}

					if(is_array($label))
						$label = $label[0];
					$text .= '<li'.$class.'>'.$label.'</li>'."\n";
				}

				$text = '<ul>'."\n".$text.'</ul>'."\n";
				break;

		}

		return $text;
	}

	/**
	 * define constants used with this skin
	 *
	 * Some constants may refer to existing images.
	 * Normally such images are in the sub-directory 'icons' and below.
	 * Other directories, including 'images', are reserved to inclusion from the template itself, or through CSS.
	 *
	 */
	function initialize() {
		global $context;

		// the maximum number of actions per page
		if(!defined('ACTIONS_PER_PAGE'))
			define('ACTIONS_PER_PAGE', 10);

		// the maximum number of articles per page
		if(!defined('ARTICLES_PER_PAGE'))
			define('ARTICLES_PER_PAGE', 50);

		// define new lines
		if(!defined('BR')) {

			// invoked through the web
			if(isset($_SERVER['REMOTE_ADDR']))
				define('BR', "<br />\n");

			// we are running from the command line
			else
				define('BR', "\n");
		}

		// end of tags -- we are XHTML
		if(!defined('EOT'))
			define('EOT', ' />');

		// the HTML to signal an answer
		if(is_callable(array('i18n', 's')))
			$text = i18n::s('A: ');
		else
			$text = 'A: ';
		Skin::define_img('ANSWER_FLAG', 'codes/answer.gif', $text, '!!');

		// the bullet used to prefix list items
		Skin::define_img('BULLET_IMG', 'codes/bullet.gif', '*', '-');

		// the HTML string inserted between categories
		if(!defined('CATEGORY_PATH_SEPARATOR'))
			define('CATEGORY_PATH_SEPARATOR', ' &gt; ');

		// the maximum number of categories attached to an anchor -- see categories/select.php
		if(!defined('CATEGORIES_LIST_SIZE'))
			define('CATEGORIES_LIST_SIZE', 40);

		// the maximum number of categories per page -- see articles/view.php, sections/index.php, sections/view.php
		if(!defined('CATEGORIES_PER_PAGE'))
			define('CATEGORIES_PER_PAGE', 40);

		// the maximum number of comments per page
		if(!defined('COMMENTS_PER_PAGE'))
			define('COMMENTS_PER_PAGE', 50);

		// the bullet prefix for items of compact lists
		if(!defined('COMPACT_LIST_ITEM_PREFIX'))
			define('COMPACT_LIST_ITEM_PREFIX', '');

		// the item suffix for items of compact lists
		if(!defined('COMPACT_LIST_ITEM_SUFFIX'))
			define('COMPACT_LIST_ITEM_SUFFIX', '');

		// the bullet prefix for compact lists
		if(!defined('COMPACT_LIST_PREFIX'))
			define('COMPACT_LIST_PREFIX', '');

		// the item suffix for compact lists
		if(!defined('COMPACT_LIST_SEPARATOR'))
			define('COMPACT_LIST_SEPARATOR', '');

		// maximum number of elements in a compact list
		if(!defined('COMPACT_LIST_SIZE'))
			define('COMPACT_LIST_SIZE', 7);

		// the item suffix for compact lists
		if(!defined('COMPACT_LIST_SUFFIX'))
			define('COMPACT_LIST_SUFFIX', '');

		// the HTML to be inserted before bread crumbs
		if(!defined('CRUMBS_PREFIX'))
			define('CRUMBS_PREFIX', '');

		// the HTML to be inserted between bread crumbs
		if(!defined('CRUMBS_SEPARATOR'))
			define('CRUMBS_SEPARATOR', '&nbsp;&laquo; &nbsp; ');

		// the HTML to be appended to bread crumbs
		if(!defined('CRUMBS_SUFFIX'))
			define('CRUMBS_SUFFIX', '&nbsp;&laquo; &nbsp; ');

		// use parameter of the control panel for this one
		$options = '';
		if(isset($context['classes_for_thumbnail_images']))
			$options = 'class="'.$context['classes_for_thumbnail_images'].'" ';

		// the img tag used with the [decorated] code; either a decorating icon, or equivalent to the bullet
		Skin::define_img('DECORATED_IMG', 'layouts/decorated.gif', BULLET_IMG, '*', $options);

		// the bullet used to signal pages to be published
		if(is_callable(array('i18n', 's')))
			$text = i18n::s('to publish');
		else
			$text = 'to publish';
		Skin::define_img('DRAFT_FLAG', 'tools/draft.gif', '!', $text);

		// the bullet used to signal expired pages
		if(is_callable(array('i18n', 's')))
			$text = i18n::s('expired');
		else
			$text = 'expired';
		Skin::define_img('EXPIRED_FLAG', 'tools/expired.gif', '!', $text);

		// the HTML to be inserted before section family
		if(!defined('FAMILY_PREFIX'))
			define('FAMILY_PREFIX', '');

		// the HTML to be appended to some section family
		if(!defined('FAMILY_SUFFIX'))
			define('FAMILY_SUFFIX', BR);

		// the maximum number of files per page
		if(!defined('FILES_PER_PAGE'))
			define('FILES_PER_PAGE', 30);

		// the horizontal ruler
		if(!defined('HORIZONTAL_RULER'))
			define('HORIZONTAL_RULER', '<hr />');

		// the prefix icon used for hot threads
		Skin::define_img('HOT_THREAD_IMG', 'articles/hot_thread.gif');

		// the HTML to be inserted before an icon
		if(!defined('ICON_PREFIX'))
			define('ICON_PREFIX', '');

		// the HTML to be appended to an icon
		if(!defined('ICON_SUFFIX'))
			define('ICON_SUFFIX', '');

		// the HTML string used to prefix an in-line menu
		if(!defined('INLINE_MENU_PREFIX'))
			define('INLINE_MENU_PREFIX', '<span class="details menu">');

		// the HTML string inserted between items of an in-line menu
		if(!defined('INLINE_MENU_SEPARATOR'))
			define('INLINE_MENU_SEPARATOR', ' &middot;&nbsp;');

		// the HTML string appended to an in-line menu
		if(!defined('INLINE_MENU_SUFFIX'))
			define('INLINE_MENU_SUFFIX', '</span>');

		// the maximum number of links per page
		if(!defined('LINKS_PER_PAGE'))
			define('LINKS_PER_PAGE', 200);

		// the HTML used to signal a locked page
		Skin::define_img('LOCKED_FLAG', 'tools/locked.gif', '%');

		// the img tag used with 2-columns list; either a folder icon, or equivalent to the bullet
		Skin::define_img('MAP_IMG', 'layouts/map.gif', DECORATED_IMG, '*', $options);

		// the HTML string used to prefix topmenu items [menu]
		if(!defined('MENU_1_PREFIX'))
			define('MENU_1_PREFIX', '');

		// the HTML string appended to topmenu items [menu]
		if(!defined('MENU_1_SUFFIX'))
			define('MENU_1_SUFFIX', '');

		// the HTML string used to prefix submenu items [submenu]
		if(!defined('MENU_2_PREFIX'))
			define('MENU_2_PREFIX', '');

		// the HTML string appended to submenu items [submenu]
		if(!defined('MENU_2_SUFFIX'))
			define('MENU_2_SUFFIX', '');

		// the HTML string used to prefix a menu
		if(!defined('MENU_PREFIX'))
			define('MENU_PREFIX', '');

		// the HTML string inserted between menu items
		if(!defined('MENU_SEPARATOR'))
			define('MENU_SEPARATOR', ' &middot;&nbsp;');

		// the HTML string appended to a menu
		if(!defined('MENU_SUFFIX'))
			define('MENU_SUFFIX', '');

		// the HTML used to append to a stripped text
		Skin::define_img('MORE_IMG', 'tools/more.gif', ' &raquo;');

		// the bullet used to signal new pages
		if(!defined('NEW_FLAG')) {
			if(is_callable(array('i18n', 's')))
				$text = i18n::s('new');
			else
				$text = 'new';
			define('NEW_FLAG', '<span class="new flag"><span> ('.$text.') </span>&nbsp;</span>');
		}

		// the HTML string used to prefix some news
		if(!defined('NEWS_PREFIX'))
			define('NEWS_PREFIX', '');

		// the HTML string inserted between news items
		if(!defined('NEWS_SEPARATOR'))
			define('NEWS_SEPARATOR', '<hr />');

		// the HTML string appended to news
		if(!defined('NEWS_SUFFIX'))
			define('NEWS_SUFFIX', '');

		// the suffix for [next=...] links
		if(!defined('NEXT_IMG'))
			define('NEXT_IMG', '<span> &raquo; </span>');

		// the HTML string used to prefix the main menu
		if(!defined('PAGE_MENU_PREFIX'))
			define('PAGE_MENU_PREFIX', '');

		// the HTML string inserted between menu items
		if(!defined('PAGE_MENU_SEPARATOR'))
			define('PAGE_MENU_SEPARATOR', ' &middot;&nbsp;');

		// the HTML string appended to the main page menu
		if(!defined('PAGE_MENU_SUFFIX'))
			define('PAGE_MENU_SUFFIX', '');

		// the bullet used to signal popular pages
		if(!defined('POPULAR_FLAG')) {
			if(is_callable(array('i18n', 's')))
				$text = i18n::s('popular');
			else
				$text = 'popular';
			define('POPULAR_FLAG', '<span class="popular flag"><span> ('.$text.') </span>&nbsp;</span>');
		}

		// the prefix for [previous=...] links
		if(!defined('PREVIOUS_IMG'))
			define('PREVIOUS_IMG', '<span> &laquo; </span>');

		// the bullet used to signal private pages
		if(is_callable(array('i18n', 's')))
			$text = i18n::s('private');
		else
			$text = 'private';
		Skin::define_img('PRIVATE_FLAG', 'tools/private.gif', '('.$text.')');

		// the HTML to signal a question
		if(is_callable(array('i18n', 's')))
			$text = i18n::s('Q: ');
		else
			$text = 'Q: ';
		Skin::define_img('QUESTION_FLAG', 'codes/question.gif', $text, '?');

		// the bullet used to signal restricted pages
		if(is_callable(array('i18n', 's')))
			$text = i18n::s('restricted');
		else
			$text = 'restricted';
		Skin::define_img('RESTRICTED_FLAG', 'tools/restricted.gif', '('.$text.')');

		// the maximum number of sections attached to an anchor -- see sections/select.php
		if(!defined('SECTIONS_LIST_SIZE'))
			define('SECTIONS_LIST_SIZE', 40);

		// the maximum number of sections per page
		if(!defined('SECTIONS_PER_PAGE'))
			define('SECTIONS_PER_PAGE', 50);

		// the HTML to be inserted before the site name
		if(!defined('SITE_NAME_PREFIX'))
			define('SITE_NAME_PREFIX', '');

		// the HTML to be appended to the site name
		if(!defined('SITE_NAME_SUFFIX'))
			define('SITE_NAME_SUFFIX', '');

		// the HTML used to signal sticky pages
		if(!defined('STICKY_FLAG'))
			define('STICKY_FLAG', '');

		// the HTML string appended to each item of the site bar
		if(!defined('TABS_ITEM_SUFFIX'))
			define('TABS_ITEM_SUFFIX', '');

		// the HTML string used to prefix each item of the site bar
		if(!defined('TABS_ITEM_PREFIX'))
			define('TABS_ITEM_PREFIX', '');

		// the HTML string used to prefix the site bar
		if(!defined('TABS_PREFIX'))
			define('TABS_PREFIX', '');

		// the HTML string inserted between site bar items
		if(!defined('TABS_SEPARATOR'))
			define('TABS_SEPARATOR', '');

		// the HTML string appended to the site bar
		if(!defined('TABS_SUFFIX'))
			define('TABS_SUFFIX', '');

		// the prefix icon used for regular threads
		Skin::define_img('THREAD_IMG', 'articles/thread.gif');

		// the maximum number of threads per page -- comments/index.php
		if(!defined('THREADS_PER_PAGE'))
			define('THREADS_PER_PAGE', 50);

		// the HTML to signal a shortcut after a title
		if(!defined('TITLE_SHORTCUT'))
			define('TITLE_SHORTCUT', '&raquo;');

		// the bullet used to signal updated pages
		if(!defined('UPDATED_FLAG')) {
			if(is_callable(array('i18n', 's')))
				$text = i18n::s('updated');
			else
				$text = 'updated';
			define('UPDATED_FLAG', '<span class="updated flag"><span> ('.$text.') </span>&nbsp;</span>');
		}

		// the maximum number of users attached to an anchor -- see sections/select.php
		if(!defined('USERS_LIST_SIZE'))
			define('USERS_LIST_SIZE', 100);

		// the maximum number of watched users per page
		if(!defined('USERS_PER_PAGE'))
			define('USERS_PER_PAGE', 20);

		// number of words in a teaser
		if(!defined('WORDS_IN_TEASER'))
			define('WORDS_IN_TEASER', 100);

		// maximum number of elements for each section in Yahoo!-like layout
		if(!defined('YAHOO_LIST_SIZE'))
			define('YAHOO_LIST_SIZE', 12);

		// prefix of each item in a Yahoo-like list
		if(!defined('YAHOO_ITEM_PREFIX'))
			define('YAHOO_ITEM_PREFIX', '&raquo;&nbsp;');

		// suffix of each item in a Yahoo-like list
		if(!defined('YAHOO_ITEM_SUFFIX'))
			define('YAHOO_ITEM_SUFFIX', '');

	}

	/**
	 * layout the cover article
	 *
	 * @param array article attributes
	 * @return some HTML to be inserted into the front page
	 *
	 * @see index.php
	 */
	function &layout_cover_article(&$item) {
		global $context;

		// process steps similar to an ordinary article -- see articles/view.php
		$text = '<div id="cover_article">';

		// the article icon, if any
		if(isset($item['icon_url']) && $item['icon_url'])
			$text .= ICON_PREFIX.'<img src="'.$item['icon_url'].'" class="icon" alt="" />'.ICON_SUFFIX;

		// the introduction text, if any
		if(isset($item['introduction']) && $item['introduction'])
			$text .= Skin::build_block($item['introduction'], 'introduction');

		// get a body
		$text .= Codes::beautify($item['description'], $item['options']).'</div>';
		return $text;
	}

	/**
	 * layout strings horizontally
	 *
	 * @param mixed either an array of strings, or a string
	 * @param string optional strings
	 * @return string text to be returned to the browser
	 */
	function &layout_horizontally() {

		// we return some text
		$text = '';

		// list all arguments
		$count = func_num_args();
		$arguments = func_get_args();
		$style = 'west';
		for($index = 0; $index < $count; $index++) {
			$argument = $arguments[$index];

			// do the layout
			if(is_array($argument)) {
				$marker = count($argument);
				foreach($argument as $token) {

					// moving to last element
					$marker -= 1;
					if(!$marker)
						$style = 'east';

					// adjust alignment if required
					if(!strncmp($token, 'left=', 5))
						$text .= '<td class="'.$style.'"><div style="text-align: left;">'.substr($token, 5).'</div></td>';
					elseif(!strncmp($token, 'center=', 7))
						$text .= '<td class="'.$style.'"><div style="text-align: center;">'.substr($token, 7).'</div></td>';
					elseif(!strncmp($token, 'right=', 6))
						$text .= '<td class="'.$style.'"><div style="text-align: right;">'.substr($token, 6).'</div></td>';
					else
						$text .= '<td class="'.$style.'">'.$token.'</td>';

					// move to the center
					$style = 'center';
				}
			} else {

				// moving to last element
				if($index + 1 == $count)
					$style = 'east';

				// adjust alignment if required
				if(!strncmp($argument, 'left=', 5))
					$text .= '<td class="'.$style.'"><div style="text-align: left;">'.substr($argument, 5).'</div></td>';
				elseif(!strncmp($argument, 'center=', 7))
					$text .= '<td class="'.$style.'"><div style="text-align: center;">'.substr($argument, 7).'</div></td>';
				elseif(!strncmp($argument, 'right=', 6))
					$text .= '<td class="'.$style.'"><div style="text-align: right;">'.substr($argument, 6).'</div></td>';
				else
					$text .= '<td class="'.$style.'">'.$argument.'</td>';
			}

			// move from west to center
			if(!$index)
				$style = 'center';

		}

		// package the resulting string
		if($text)
			$text = '<table class="layout"><tbody><tr>'.$text.'</tr></tbody></table>';

		// job is done
		return $text;
	}

	/**
	 * layout strings vertically
	 *
	 * @param mixed either an array of strings, or a string
	 * @param string optional strings
	 * @return string text to be returned to the browser
	 */
	function &layout_vertically() {

		// we return some text
		$text = '';

		// list all arguments
		$count = func_num_args();
		$arguments = func_get_args();
		$style = 'north';
		for($index = 0; $index < $count; $index++) {
			$argument = $arguments[$index];

			// do the layout
			if(is_array($argument)) {
				$marker = count($argument);
				foreach($argument as $token) {

					// moving to last element
					$marker -= 1;
					if(!$marker)
						$style = 'south';

					// adjust alignment if required
					if(!strncmp($token, 'left=', 5))
						$text .= '<tr><td class="'.$style.'"><div style="text-align: left;">'.substr($token, 5).'</div></td></tr>';
					elseif(!strncmp($token, 'center=', 7))
						$text .= '<tr><td class="'.$style.'"><div style="text-align: center;">'.substr($token, 7).'</div></td></tr>';
					elseif(!strncmp($token, 'right=', 6))
						$text .= '<tr><td class="'.$style.'"><div style="text-align: right;">'.substr($token, 6).'</div></td></tr>';
					else
						$text .= '<tr><td class="'.$style.'">'.$token.'</td></tr>';

					// move to the center
					$style = 'equator';
				}
			} else {

				// moving to last element
				if($index + 1 == $count)
					$style = 'south';

				// adjust alignment if required
				if(!strncmp($argument, 'left=', 5))
					$text .= '<tr><td class="'.$style.'"><div style="text-align: left;">'.substr($argument, 5).'</div></td></tr>';
				elseif(!strncmp($argument, 'center=', 7))
					$text .= '<tr><td class="'.$style.'"><div style="text-align: center;">'.substr($argument, 7).'</div></td></tr>';
				elseif(!strncmp($argument, 'right=', 6))
					$text .= '<tr><td class="'.$style.'"><div style="text-align: right;">'.substr($argument, 6).'</div></td></tr>';
				else
					$text .= '<tr><td class="'.$style.'">'.$argument.'</td></tr>';
			}

			// move from north to center
			if(!$index)
				$style = 'equator';

		}

		// package the resulting string
		if($text)
			$text = '<table class="layout"><tbody>'.$text.'</tbody></table>';

		// job is done
		return $text;
	}

	/**
	 * load a skin, and initialize everything
	 *
	 */
	function load() {

		// set constants
		Skin::initialize();

		// set other constants, if any
		Skin_skeleton::initialize();

	}

	/**
	 * build a navigation bar for pages
	 *
	 * This is used to browse long lists of links.
	 *
	 * For example, if you are at page 3, and if the count of items equals the page size, this function
	 * will produce a list of url as follows:
	 * - back: /sections/view.php/23 (the main page, which also displays some articles)
	 * - to page 2: /sections/view.php/23/articles/2 (another page of links to articles)
	 * - to page 3: /sections/view.php/23/articles/3 (a third set of links to articles)
	 * - to next page: /sections/view.php/23/articles/3 (a fourth set of links to articles)
	 *
	 * In some cases (e.g., to display lists of comments) we would like to display the first page of links twice.
	 * To achieve that just set $zooming to TRUE.
	 *
	 * For example, if you are at page 3, and if the count of items equals the page size, this function
	 * will produce a list of url as follows:
	 * - back: /articles/view.php/23 (the main page, which displays some 'packed' comments)
	 * - to page 1: /articles/view.php/23/comments/1 (Some full-sized comments)
	 * - to page 2: /articles/view.php/23/comments/2 (another page of links to comments)
	 * - to page 3: /articles/view.php/23/comments/3 (a third set of links to comments)
	 * - to next page: /articles/view.php/23/comments/4 (a fourth set of links to comments)
	 *
	 * @param string the url to go back to the main page (e.g., '/sections/view.php/23')
	 * @param string the prefix for page links (e.g., '/sections/view.php/23/articles/')
	 * @param the items, or the count of items in the current page
	 * @param int the page size
	 * @param int current page index, starting at 1 (e.g., 3)
	 * @param boolean TRUE to add a link to page 1
	 * @param string to be appended to each link created by the script
	 * @return an array of ( $url => $label )
	 *
	 * @see actions/index.php
	 * @see actions/list.php
	 * @see articles/index.php
	 * @see articles/view.php
	 * @see categories/index.php
	 * @see categories/view.php
	 * @see comments/index.php
	 * @see files/index.php
	 * @see images/index.php
	 * @see links/index.php
	 * @see locations/index.php
	 * @see sections/index.php
	 * @see sections/view.php
	 * @see servers/index.php
	 * @see tables/index.php
	 * @see users/index.php
	 * @see users/view.php
	 */
	function &navigate($back, $prefix, $range, $page_size, $page_index, $zooming=FALSE, $to_next_page=FALSE, $suffix='') {
		global $context;

		// no next page yet
		$next_page = NULL;

		// we are building an horizontal bar
		$bar = array();

		// back to the main page
		if($zooming)
			$bar = array_merge($bar, array( $back => i18n::s('Back to main page') ));

		if(is_array($range))
			$range = count($range);

		if(($range < 1) && ($page_index < 2))
			return $bar;
		if(($range < $page_size) && ($page_index < 2))
			return $bar;

		// links to previous pages
		$pages = range(1, $page_index);
		$last = 0;
		$skipped = FALSE;
		foreach($pages as $index) {

			// this page
			if($index == $page_index) {
				$url = '_'.$index;
				$first = ($page_size * ($index-1)) + 1;
				$count = min($page_size, $range-$first+1);
				$last = $first + $count - 1;
				if($first < $last)
					$label = $first.'-'.$last;
				else
					$label = $first;

			// not the current page
			} else {
				if($index == 1 && !$zooming)
					$url = $back;
				else
					$url = $prefix.$index;

				$first = ($page_size * ($index-1)) + 1;
				$last = $first + $page_size - 1;
				if($first < $last)
					$label = $first.'-'.$last;
				else
					$label = $first;

				if(($index != 1) && ($index < $page_index - 2)) {
					if(!$skipped) {
						$url = '_';
						$label = '...';
						$skipped = TRUE;
					} else
						continue;
				}

			}

			$bar = array_merge($bar, array( $url.$suffix => array('', $label, '', 'basic') ));
		}

		// commands to see next pages
		if($range > $last+1) {

			$count = 0;
			while($range > $last) {
				$page_index++;
				if(!$next_page)
					$next_page = $prefix.$page_index;

				$first = ($page_size * ($page_index-1)) + 1;
				$last = $first + $page_size - 1;
				if($last > $range)
					$last = $range;

				if($first < $last)
					$label = $first.'-'.$last;
				else
					$label = $first;

				$bar = array_merge($bar, array( $prefix.$page_index.$suffix => array('', $label, '', 'basic') ));

				if((++$count >= 2) && ($last + $page_size < $range)) {
					$bar[] = '...';
					break;
				}
			}

		// link to next page
		} elseif($range == $last+1) {
			$page_index++;
			if(!$next_page)
				$next_page = $prefix.$page_index;

			$bar = array_merge($bar, array( $prefix.$page_index.$suffix => array('', i18n::s('More'), '', 'basic') ));

		}

		// append a link to the next page, if required -- # is to not overload the existing link
		if($to_next_page)
			$bar = array_merge($bar, array( $next_page.'#' => array('', i18n::s('Next'), '', 'basic') ));

		return $bar;
	}

	/**
	 * build a navigation bar to neighbours
	 *
	 * This function is useful to help surfers browse sets of images attached to some page.
	 * In this case it is invoked from [script]images/view.php[/script], and the anchoring page
	 * is supposed to provide URLs to previous and next neighbours ([code]$anchor->get_neighbours()[/code])
	 *
	 * @param an array of (previous_url, previous_label, next_url, next_label, option_url, option_label)
	 * @param string describing the intended layout (ie, 'sidebar', 'manual', 'slideshow')
	 * @result some text to be inserted in the page
	 *
	 * @see actions/view.php
	 * @see articles/view.php
	 * @see comments/view.php
	 * @see files/view.php
	 * @see images/view.php
	 * @see locations/view.php
	 * @see shared/anchor.php
	 */
	function &neighbours(&$data, $layout='sidebar') {
		global $context;

		// return by reference
		$output = NULL;

		// sanity check
		if(!is_array($data))
			return $output;

		// extract navigation information from parameters
		$previous_url = '';
		if(isset($data[0]) && $data[0])
			$previous_url = $data[0];

		if(!isset($data[1]))
			$previous_label = $previous_hover = '';
		elseif($data[1] && ($layout != 'manual')) {
			$previous_label = Codes::strip($data[1]);
			$previous_hover = i18n::s('Previous');
		} else {
			$previous_label = i18n::s('Previous');
			$previous_hover = Codes::strip($data[1]);
		}

		$next_url = '';
		if(isset($data[2]) && $data[2])
			$next_url = $data[2];

		if(!isset($data[3]))
			$next_label = $next_hover = '';
		elseif($data[3] && ($layout != 'manual')) {
			$next_label = Codes::strip($data[3]);
			$next_hover = i18n::s('Next');
		} else {
			$next_label = i18n::s('Next');
			$next_hover = Codes::strip($data[3]);
		}

		$option_url = '';
		if(isset($data[4]) && $data[4])
			$option_url = $data[4];

		$option_label = '';
		if(isset($data[5]) && $data[5])
			$option_label = Codes::strip($data[5]);

		// nothing to do
		if(!$previous_url && !$next_url)
			return $output;

		// select a layout
		if(!$layout) {
			$layout = 'compact';
			if(preg_match('/\bimg\b/', $previous_label.' '.$next_label))
				$layout = 'table';
		}

		// format labels
		switch($layout) {

		case 'manual':		// articles/view.php
			break;

		case 'sidebar':
		default:
			$previous_label = '&laquo; '.$previous_label;
			$next_label = $next_label.' &raquo;';
			break;

		case 'slideshow':	// images/view.php

			Skin::define_img('PREVIOUS_PREFIX', 'tools/previous.gif', '&laquo; ');
			$previous_label = PREVIOUS_PREFIX.$previous_label;

			Skin::define_img('NEXT_SUFFIX', 'tools/next.gif', ' &raquo;');
			$next_label = $next_label.NEXT_SUFFIX;

			break;

		}

		// a link to go backwards
		$previous = '';
		if($previous_url)
			$previous =& Skin::build_link($previous_url, $previous_label, 'basic', $previous_hover);

		// a link to go forward
		$next = '';
		if($next_url)
			$next =& Skin::build_link($next_url, $next_label, 'basic', $next_hover);

		// an option, if any
		$option = '';
		if($option_url)
			$option =& Skin::build_link($option_url, $option_label, 'basic');
		elseif($option_label)
			$option = $option_label;

		// layout everything
		$text = '';
		switch($layout) {
		case 'manual':
			$items = array();
			if($previous)
				$items[] = $previous;
			if($next)
				$items[] = $next;
			if($option)
				$items[] = $option;
			$text .= '<p class="tiny" style="text-align: right; margin-top: 8px; margin-bottom: 8px">'.implode(' / ', $items).'</p>'."\n";
			break;

		case 'sidebar':
		default:
			$text .= '<ul>';
			if($previous)
				$text .= '<li class="previous">'.$previous.'</li>';
			if($next)
				$text .= '<li class="next">'.$next.'</li>';
			$text .= '</ul>';
			break;

		case 'slideshow':
			$text .= '<table class="neighbours"><tr>'
				.'<td class="previous">'.($previous?$previous:'&nbsp;').'</td>'
				.'<td class="option">'.($option?$option:'&nbsp;').'</td>'
				.'<td class="next">'.($next?$next:'&nbsp;').'</td>'
				.'</tr></table>'."\n";
			break;
		}

		return $text;

	}

	/**
	 * to page within a page
	 *
	 * @see articles/view.php
	 *
	 * @param string URL to the first page
	 * @param string prefix to paging URLs, without the end number
	 * @param int current page, starting at 1
	 * @param int total number of pages
	 * @result array to be provided to Skin::neighbours()
	 */
	function pager($home, $prefix, $page, $count) {
		global $context;

		// go back to previous page
		$previous_url = '';
		$previous_label = '';
		if($page > 1) {
			if($page == 2)
				$previous_url = $home;
			else
				$previous_url = $prefix.($page - 1);
			$previous_label = i18n::s('Previous page');
		}

		// where we are
		$option_label = array();
		for($index = 1; $index <= $count; $index++) {

			if($index == $page) {
				$option_label[] = '<span class="pager-current">'.$index.'</span>';
			} else {
				if($index == 1)
					$url = $home;
				else
					$url = $prefix.$index;
				$option_label[] = Skin::build_link($url, $index, 'pager-item');
			}

		}
		$option_label = join(' &nbsp; ', $option_label);

		// go forward to next page
		$next_url = '';
		$next_label = '';
		if($page < $count) {
			$next_url = $prefix.($page + 1);
			$next_label = i18n::s('Next page');
		}

		// the HTML code for this
		$data = array($previous_url, $previous_label, $next_url, $next_label, NULL, $option_label);

		return $data;
	}

	/**
	 * rotate some news through Javascript executed on client side
	 *
	 * This function matches the id of each news item, and append the Javascript code
	 * aiming to rotate them periodically.
	 *
	 * If Javascript is disabled, all items will be shown statically, as usual.
	 *
	 * @param string, the set of news to animate
	 * @return text to be returned to the browser
	 *
	 * @see index.php
	 */
	function &rotate($text) {
		global $context;

		// list news ids
		preg_match_all('/id="(.*?)"/', $text, $matches);

		// rotate only if we have at least two items
		if(@count($matches[1]) < 2)
			return $text;

		// append some javascript in-line, to cache it correctly -- do not use $context['page_footer']
		$text .= JS_PREFIX;

		// hide every news item, except the first one
		$text .= '// hide every news item, except the first one'."\n";
		for($index = 1; $index < count($matches[1]); $index++)
			$text .= 'handle = $("#'.$matches[1][$index].'");'."\n"
				.'handle.style.display="none";'."\n";

		// one function per rotating step
		for($index = 0; $index < count($matches[1]); $index++) {
			$from = $matches[1][$index];
			$to = $matches[1][ ($index+1 == count($matches[1])) ? 0 : ($index+1) ];

			$text .= "\n".'// from item '.$from.' to item '.$to."\n"
				.'function rotate_'.$from.'() {'."\n"
				.'	new Effect.Fade($("#'.$from.'"), {duration:.3, scaleContent:false});'."\n"
				.'	new Effect.Appear($("#'.$to.'"), {duration:.3, scaleContent:false, queue:\'end\'});'."\n"
				.'	setTimeout("rotate_'.$to.'()",5000);'."\n"
				.'}'."\n";
		}

		// trigger the rotation
		$text .= "\n".'// start'."\n"
			.'setTimeout("rotate_'.$matches[1][0].'()",5000)'."\n";

		// end of javascript
		$text .= JS_SUFFIX."\n";

		return $text;
	}

	/**
	 * scroll some text, through Javascript code executed on client side
	 *
	 * [code]setTimeout()[/code] changed to [code]setInterval()[/code], like in DOMnews
	 *
	 * @link http://www.onlinetools.org/tools/domnews/ DOMnews
	 *
	 * @param string the set of news to animate
	 * @param string either 'vertical' or 'horizontal'
	 * @return text to be returned to the browser
	 *
	 * @see index.php
	 */
	function &scroll($content, $direction='vertical', $class='scroller') {
		global $context;

		// horizontal
		if($direction == 'horizontal') {
			$container_limit = 'offsetWidth';
			$object_parameter = 'left';

		// vertical
		} else {
			$container_limit = 'offsetHeight';
			$object_parameter = 'top';
		}

		// get a unique id for this object
		static $scroller_id;
		if(!isset($scroller_id))
			$scroller_id = 0;
		$scroller_id += 1;

		// build a scroller
		$text = '<div class="'.$class.'_outside">'."\n"
			.'<div class="'.$class.'_inside" id="scroller_'.$scroller_id.'" onMouseover="scroller_'.$scroller_id.'_speed=0" onMouseout="scroller_'.$scroller_id.'_speed=2">'
			.$content."\n"
			.'</div></div>'."\n";

		// embed javascript here to correctly cache it -- do not use $context['page_footer']
		$text .= JS_PREFIX
			."\n"
			.'// the scrolling speed'."\n"
			.'var scroller_'.$scroller_id.'_speed = 3;'."\n"
			."\n"
			.'// where scrolling starts'."\n"
			.'var scroller_'.$scroller_id.'_start;'."\n"
			."\n"
			.'// where scrolling ends'."\n"
			.'var scroller_'.$scroller_id.'_stop;'."\n"
			."\n"
			.'// the actual scroller'."\n"
			.'function scroll_scroller_'.$scroller_id.'() {'."\n"
			.'	handle = $("#scroller_'.$scroller_id.'");'."\n"
			.'	current = parseInt(handle.style.'.$object_parameter.') - scroller_'.$scroller_id.'_speed;'."\n"
			.'	if(current < scroller_'.$scroller_id.'_stop)'."\n"
			.'		current = scroller_'.$scroller_id.'_start;'."\n"
			.'	handle.css({\'position\': \'absolute\', \''.$object_parameter.'\': current+"px"});'."\n"
			.'}'."\n"
			."\n"
			.'// initialise scroller when window loads'."\n"
			.'$(document).ready(function() {'."\n"
			."\n"
			.'	// locate the inside div'."\n"
			.'	handle = $("#scroller_'.$scroller_id.'");'."\n"
			."\n"
			.'	// start at the bottom of the outside container'."\n"
			.'	scroller_'.$scroller_id.'_start = handle.parentNode.'.$container_limit.' - 20;'."\n"
			."\n"
			.'	// stop when content is not visible'."\n"
			.'	scroller_'.$scroller_id.'_stop = Math.min(handle.parentNode.'.$container_limit.' - handle.'.$container_limit.', 0);'."\n"
			."\n"
			.'	// initialize the scroller -- else Firefox does not start'."\n"
			.'	handle.style.'.$object_parameter.' = "0px";'."\n"
			."\n"
			.'	// start scrolling'."\n"
			.'	interval = setInterval("scroll_scroller_'.$scroller_id.'()", 200);'."\n"
			."\n"
			.'})'."\n"
			."\n"
			.JS_SUFFIX."\n";

		return $text;
	}

	/**
	 * strip some text, by suppressing codes, html and limiting words
	 *
	 * To limit the size of a string and preserve HTML tagging, you should rather consider [code]cap()[/code].
	 *
	 * A stripped string is a short set of words aiming to introduce a longer page. It is
	 * usually followed by a link to jump to the full text.
	 *
	 * Alternatively, this function may also be used to limit the number of words
	 * for a plain text string.
	 *
	 * In both cases the calling script should be able to use the resulting directly, without any additional processing.
	 *
	 * Most HTML tags are suppressed by [code]Skin::strip()[/code], except &lt;a&gt;, &lt;br&gt;, &lt;img&gt; and  &lt;span&gt;.
	 * You can provide the full list of allowed tags.
	 *
	 * This function will strip YACS codes as well, except if you explicitly ask for codes to be processed.
	 *
	 *
	 * Example:
	 * [snippet]
	 * Skin::strip('This is my very interesting page', 3);
	 * [/snippet]
	 *
	 * will return:
	 * [snippet]
	 * 'This is my...'
	 * [/snippet]
	 *
	 * @param string the text to abbreviate
	 * @param int the maximum number of words in the output
	 * @param an optional url to go to the full version, if any
	 * @param the list of allowed HTML tags, if any
	 * @param boolean set to TRUE if YACS codes should be rendered, else codes will be removed
	 * @return the HTML to display
	 */
	function &strip($text, $count=20, $url=NULL, $allowed_html='<a><br><img><span>', $render_codes=FALSE) {
		global $context;

		// no follow-up yet
		$with_more = FALSE;

		// process YACS codes
		if($render_codes) {

			// suppress dynamic tables, they would probably take too much space
			if(preg_match('/\[table=(.+?)\]/s', $text)) {
				$text = preg_replace(array('/\[table=(.+?)\]/s'), ' (table) ', $text);

				// append a link to the full page
				$with_more = TRUE;
			}

			// render all codes
			$text = Codes::beautify($text);

		// suppress all pairing yacs codes, but not unitary codes (could be added by overlay at end of titles)
		}
// 		} else
// 			$text = Codes::strip($text, FALSE);

		// suppress all javascript
		$text = preg_replace('#<script[^>]*>.*?</script>#is', '', $text);

		// preserve breaks
		$text = preg_replace('/<(br *\/{0,1}|h1|\/h1|h2|\/h2|h3|\/h3|h4|\/h4|h5|\/h5|p|\/p|\/td)>/i', "<\\1>\n", $text);

		// strip most html, except <a> for anchored names, <br> for new lines, <img> for bullets and <span> for css
		if($allowed_html)
			$text = trim(strip_tags($text, $allowed_html));
		else
			$text = trim(strip_tags($text));

		// count overall words
		$overall = count(preg_split("/[ \t,\.;\?!]+/", $text, -1, PREG_SPLIT_NO_EMPTY));

		// no parsing overhead in case of short labels
		if($overall <= $count)
			return $text;

		// skip html tags
		$areas = preg_split('/(<\/{0,1}[\w]+[^>]*>)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		$text = '';
		$index = 0;
		$overall = 0;
		foreach($areas as $area) {

			// tag to be preserved
			if($index%2) {
				$text .= $area;

				// check boundary after last HTML tag -- else <a>text</a> could loose closing tag
				if($count < 0)
					break;

			// regular text
			} else {

				// count words from this area
				$words = preg_split("/([ \t,\.;\?!]+)/", $area, -1, PREG_SPLIT_DELIM_CAPTURE);

				// we still have some room
				if(count($words) <= (2*$count))
					$text .= $area;

				// limit has been reached
				elseif($count > 0) {
					$overall += intval(count($words)/2 - $count);

					// drop the tail
					array_splice($words, 2*$count);

					// reassemble displayed words
					$text .= implode('', $words).'...';

					// append a link to the full page
					$with_more = TRUE;

				} else
					$overall += intval(count($words)/2);

				// less words to accept
				$count -= intval(count($words)/2);

			}
			$index++;
		}

		// there is more to read
		if($with_more && $url) {

			// indicate the number of words to read, if significant text to read
			if($overall > 30)
				$text .= ' ('.sprintf(i18n::s('%d words to read'), $overall).') ';

			// add a link
			$text .= ' '.Skin::build_link($url, MORE_IMG, 'more', i18n::s('View the page')).' ';
		}

		return $text;
	}

	/**
	 * build a complete table
	 *
	 * @param array headers
	 * @param array rows of cells
	 * @param string a variant, if any, as decribed for table_prefix()
	 * @return a string to be sent to the browser
	 */
	function &table($headers, &$rows, $variant='') {
		$text =& Skin::table_prefix($variant);
		if(isset($headers) && is_array($headers))
			$text .= Skin::table_row($headers, 'sortable');
		$row_count = 1;
		if(is_array($rows)) {
			foreach($rows as $cells)
				$text .= Skin::table_row($cells, $row_count++);
		}
		$text .= Skin::table_suffix();
		return $text;
	}

	/**
	 * open a table
	 *
	 * Accepted variants:
	 * - 'form' layout for a form
	 * - 'grid' force a grid to be drawn
	 * - '100%' force to max width
	 *
	 * @param string the table variant, if any
	 * @return the HTML to display
	 */
	function &table_prefix($variant='') {
		global $current_table_id;
		$current_table_id++;

		global $current_table_has_body;
		$current_table_has_body = FALSE;

		switch($variant) {
		case '100%':
		case 'wide':
			$text = '<table class="wide">'."\n";
			break;
		default:
			if($variant)
				$text = '<table class="'.$variant.'">'."\n";
			else
				$text = '<table>'."\n";
			break;
		}
		return $text;
	}

	/**
	 * build one row of a table
	 *
	 * Accept following variants:
	 * - 'header' for a heading row
	 * - 'sortable' for a heading row
	 * - the row number, to distinguish odd and even backgrounds
	 *
	 * @param array the content of cells
	 * @param string the variant, if any; 'header' for the header row, if %2, use one color set, if !%2, use another set
	 * @return the HTML to display
	 */
	function &table_row($cells, $variant=0) {
		global $context;
		global $current_table_id;
		global $current_table_has_body;

		// sanity check
		if(!is_array($cells)) {
			$output = '';
			return $output;
		}

		// the header line
		if($variant && ($variant == 'header')) {

			$row_prefix = '<tr class="even">';
			$row_suffix = '</tr>'."\n";
			$cell_prefix = '<th>';
			$cell_opened = '<th';
			$cell_suffix = '</th>';
			$current_table_has_body = FALSE;

		// a sortable table
		} elseif($variant && ($variant == 'sortable')) {

			$row_prefix = '<thead><tr class="even">';
			$row_suffix = '</tr></thead>'."\n".'<tbody id="table_'.$current_table_id.'">'."\n";
			$cell_prefix = '<th>';
			$cell_opened = '<th';
			$cell_suffix = '</th>';
			$current_table_has_body = TRUE;

			// column headers are clickable links
			$index = 0;
			foreach($cells as $cell) {

				// take care of cell alignment
				$prefix = '';

				// right=...
				if(preg_match('/^\s*right=(.*)$/is', $cell, $matches)) {
					$prefix = 'right=';
					$cell = $matches[1];
				// center=...
				} elseif(preg_match('/^\s*center=(.*)$/is', $cell, $matches)) {
					$prefix = 'center=';
					$cell = $matches[1];
				// left=...
				} elseif(preg_match('/^\s*left=(.*)$/is', $cell, $matches)) {
					$prefix = 'left=';
					$cell = $matches[1];
				}

				// empty header
				if(!trim($cell)) {
					$links[] = '&nbsp;';
					$index++;

				// a clickable header
				} else {

					$links[] = $prefix.'<a onclick="javascript:this.blur(); return Yacs.sortTable(\'table_'.$current_table_id.'\', '.($index++).', false);"'
						.' title="'.i18n::s('Click to sort').'">'
						.ucfirst($cell).'</a>';
				}
			}
			$cells = $links;
			$current_table_id++;

		// an odd row
		} elseif($variant%2) {
			$row_prefix = '<tr class="odd">';
			$row_suffix = '</tr>'."\n";
			$cell_prefix = '<td>';
			$cell_opened = '<td';
			$cell_suffix = '</td>';

		// an even row
		} else {
			$row_prefix = '<tr class="even">';
			$row_suffix = '</tr>'."\n";
			$cell_prefix = '<td>';
			$cell_opened = '<td';
			$cell_suffix = '</td>';
		}

		// process every cell
		$text = '';
		$count = 1;
		foreach($cells as $cell) {
			// west=...
			if(preg_match('/^west=(.*)$/is', $cell, $matches))
				$text .= $cell_opened.' class="west">'.$matches[1].$cell_suffix;
			// east=...
			elseif(preg_match('/^east=(.*)$/is', $cell, $matches))
				$text .= $cell_opened.' class="east">'.$matches[1].$cell_suffix;
			// right=...
			elseif(preg_match('/^\s*right=(.*)$/is', $cell, $matches))
				$text .= $cell_opened.' style="text-align: right;">'.$matches[1].$cell_suffix;
			// center=...
			elseif(preg_match('/^\s*center=(.*)$/is', $cell, $matches))
				$text .= $cell_opened.' style="text-align: center;">'.$matches[1].$cell_suffix;
			// left=...
			elseif(preg_match('/^\s*left=(.*)$/is', $cell, $matches))
				$text .= $cell_opened.' style="text-align: left;">'.$matches[1].$cell_suffix;
			else
				$text .= $cell_prefix.$cell.$cell_suffix;
		}

		// return a complete row
		$text = $row_prefix.$text.$row_suffix;
		return $text;
	}

	/**
	 * close a table
	 *
	 * @return the HTML to display
	 */
	function &table_suffix() {

		// close tbody
		global $current_table_has_body;
		if($current_table_has_body)
			$text = '</tbody></table>'."\n";

		// simple end
		else
			$text = '</table>'."\n";

		return $text;

	}

}

// load localized strings
if(is_callable(array('i18n', 'bind')))
	i18n::bind('skins');

?>
