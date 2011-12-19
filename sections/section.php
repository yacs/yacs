<?php
/**
 * the implementation of anchor for sections
 *
 * This class implements the Anchor interface for sections.
 *
 * @see shared/anchor.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Section extends Anchor {

	/**
	 * get the focus for this anchor
	 *
	 * This function lists containers of the content tree,
	 * from top level down to this item.
	 *
	 * @return array of anchor references
	 */
	 function get_focus() {

		// get the parent
		if(!isset($this->anchor))
			$this->anchor =& Anchors::get($this->item['anchor']);

		// the parent level
		if(is_object($this->anchor))
			$focus = $this->anchor->get_focus();
		else
			$focus = array();

		// append this level
		if(isset($this->item['id']))
			$focus[] = 'section:'.$this->item['id'];

		return $focus;
	 }

	/**
	 * get the url to display the icon for this anchor
	 *
	 * @see shared/anchor.php
	 *
	 * @return an anchor to the icon image
	 */
	function get_icon_url() {
		if(isset($this->item['icon_url']))
			return $this->item['icon_url'];

		// do not transmit the thumbnail instead
		return NULL;
	}

	 /**
	  * provide a custom label
	  *
	  * @param string the target label (e.g., 'edit_title', 'item_name', 'item_names')
	  * @param string the module that is invoking the anchor (e.g., 'comments')
	  * @param string an optional title, if any
	  * @return string the foreseen label
	  */
	 function get_label($id, $variant, $title='') {
		global $context;

		// sanity check
		if(!isset($this->item['id']))
			return FALSE;

		// a default title
		if(!$title)
			$title = $this->get_title();

		// strings for comments
		if($variant == 'comments') {

			switch($id) {

			// title for these
			case 'title':
				if($this->has_layout('jive'))
					return i18n::s('Replies');
				if($this->has_layout('manual'))
					return i18n::s('Notes');
				return i18n::s('Comments');

			// many comments
			case 'list_title':
				if($this->has_layout('jive'))
					return i18n::s('Replies');
				if($this->has_layout('manual'))
					return i18n::s('Notes');
				return i18n::s('Comments');

			// command to delete a comment
			case 'delete_command':
				if($this->has_layout('jive'))
					return i18n::s('Yes, I want to delete this reply');
				if($this->has_layout('manual'))
					return i18n::s('Yes, I want to delete this note');
				return i18n::s('Yes, I want to delete this comment');

			// page title to delete a comment
			case 'delete_title':
				if($this->has_layout('jive'))
					return i18n::s('Delete a reply');
				if($this->has_layout('manual'))
					return i18n::s('Delete a note');
				return i18n::s('Delete a comment');

			// command to edit content
			case 'edit_command':
				if($this->has_layout('jive'))
					return i18n::s('Edit the new reply');
				if($this->has_layout('manual'))
					return i18n::s('Edit the new note');
				return i18n::s('Edit the new comment');

			// command to promote a comment
			case 'promote_command':
				if($this->has_layout('jive'))
					return i18n::s('Yes, I want to turn this reply to an article');
				if($this->has_layout('manual'))
					return i18n::s('Yes, I want to turn this note to an article');
				return i18n::s('Yes, I want to turn this comment to an article');

			// page title to promote a comment
			case 'promote_title':
				if($this->has_layout('jive'))
					return i18n::s('Promote a reply');
				if($this->has_layout('manual'))
					return i18n::s('Promote a note');
				return i18n::s('Promote a comment');

			// command to view the thread
			case 'thread_command':
				return i18n::s('View the page');

			// page title to modify a comment
			case 'edit_title':
				if($this->has_layout('jive'))
					return i18n::s('Update a reply');
				if($this->has_layout('manual'))
					return i18n::s('Update a note');
				return i18n::s('Edit a comment');

			// page title to list comments
			case 'list_title':
				if($this->has_layout('jive'))
					return sprintf(i18n::s('Replies: %s'), $title);
				if($this->has_layout('manual'))
					return sprintf(i18n::s('Notes: %s'), $title);
				return sprintf(i18n::s('Discuss: %s'), $title);

			// command to create a comment
			case 'new_command':
				if($this->has_layout('jive'))
					return i18n::s('Reply to this post');
				if($this->has_layout('manual'))
					return i18n::s('Annotate this page');
				return i18n::s('Post a comment');

			// page title to create a comment
			case 'new_title':
				if($this->has_layout('jive'))
					return i18n::s('Reply to this post');
				if($this->has_layout('manual'))
					return i18n::s('Annotate this page');
				return i18n::s('Post a comment');

			// command to view content
			case 'view_command':
				if($this->has_layout('jive'))
					return i18n::s('View the reply');
				if($this->has_layout('manual'))
					return i18n::s('View the note');
				return i18n::s('View this comment');

			// page title to view a comment
			case 'view_title':
				if($this->has_layout('jive'))
					return sprintf(i18n::s('Reply: %s'), $title);
				if($this->has_layout('manual'))
					return sprintf(i18n::s('Note: %s'), $title);
				return $title;
			}

		}

		// climb the anchoring chain, if any
		if(isset($this->item['anchor']) && $this->item['anchor']) {

			// cache anchor
			if(!$this->anchor)
				$this->anchor =& Anchors::get($this->item['anchor']);

			if(is_object($this->anchor))
				return $this->anchor->get_label($variant, $id, $title);

		}

		// use default title
		if($title)
			return $title;

		// no match
		return 'Impossible to translate '.$id.' for module '.$variant;
	}

	/**
	 * get the named url for this anchor
	 *
	 * If the anchor as been named, this function returns the related url.
	 *
	 * @return an url to view the anchor page, or NULL
	 */
	function get_named_url() {
		if(isset($this->item['nick_name']) && $this->item['nick_name'])
			return normalize_shortcut($this->item['nick_name']);
		return NULL;
	}

	/**
	 * get next and previous items, if any
	 *
	 * @see shared/anchor.php
	 *
	 * @param string the item type (eg, 'article', 'image', 'file', etc.)
	 * @param array the anchored item asking for neighbours
	 * @return an array($previous_url, $previous_label, $next_url, $next_label, $option_url, $option_label), or NULL
	 */
	function get_neighbours($type, &$item) {
		global $context;

		// no item bound
		if(!isset($this->item['id']))
			return NULL;

		// initialize components
		$previous_url = $previous_label = $next_url = $next_label = $option_url = $option_label ='';

		// previous and next articles
		if($type == 'article') {

			// determine on-going order
			if(!$order = $this->has_option('articles_by', FALSE))
				$order = 'edition';

			// get previous url
			if($previous = Articles::get_previous_url($item, 'section:'.$this->item['id'], $order)) {
				if(is_array($previous))
					list($previous_url, $previous_label) = $previous;
				else {
					$previous_url = $previous;
					$previous_label = i18n::s('Previous');
				}
			}

			// get next url
			if($next = Articles::get_next_url($item, 'section:'.$this->item['id'], $order)) {
				if(is_array($next))
					list($next_url, $next_label) = $next;
				else {
					$next_url = $next;
					$next_label = i18n::s('Next');
				}
			}

			// go up
			$option_label = Skin::build_link($this->get_url(), i18n::s('Index'), 'basic');

		// previous and next comments
		} elseif($type == 'comment') {

			// load the adequate library
			include_once $context['path_to_root'].'comments/comments.php';

			$order = 'date';

			// get previous url
			if($previous_url = Comments::get_previous_url($item, 'section:'.$this->item['id'], $order))
				$previous_label = i18n::s('Previous');

			// get next url
			if($next_url = Comments::get_next_url($item, 'section:'.$this->item['id'], $order))
				$next_label = i18n::s('Next');

		// previous and next files
		} elseif($type == 'file') {

			// select appropriate order
			if(preg_match('/\bfiles_by_title\b/', $this->item['options']))
				$order = 'title';
			else
				$order = 'date';

			// get previous url
			if($previous_url = Files::get_previous_url($item, 'section:'.$this->item['id'], $order))
				$previous_label = i18n::s('Previous');

			// get next url
			if($next_url = Files::get_next_url($item, 'section:'.$this->item['id'], $order))
				$next_label = i18n::s('Next');

		// previous and next images
		} elseif($type == 'image') {

			// load the adequate library
			include_once $context['path_to_root'].'images/images.php';

			// extract all images references from the description
			preg_match_all('/\[image=(\d+)/', $this->item['description'], $matches);

			// locate the previous image, if any
			$previous = NULL;
			reset($matches[1]);
			$index = 0;
			while(list($key, $value) = each($matches[1])) {
				$index++;
				if($item['id'] == $value)
					break;
				$previous = $value;
			}

			// make a link to the previous image
			if($previous) {
				$previous_url = Images::get_url($previous);
				$previous_label = i18n::s('Previous');
			}

			// locate the next image, if any
			if(!list($key, $next) = each($matches[1]))
				$next = NULL;

			// make a link to the next image
			else {
				$next_url = Images::get_url($next);
				$next_label = i18n::s('Next');
			}

			// add a label
			$option_label = sprintf(i18n::s('Image %d of %d'), $index, count($matches[1]));

		// previous and next location
		} elseif($type == 'location') {

			// load the adequate library
			include_once $context['path_to_root'].'locations/locations.php';

			// extract all location references from the description
			preg_match_all('/\[location=(\d+)/', $this->item['description'], $matches);

			// locate the previous location, if any
			$previous = NULL;
			reset($matches[1]);
			$index = 0;
			while(list($key, $value) = each($matches[1])) {
				$index++;
				if($item['id'] == $value)
					break;
				$previous = $value;
			}

			// make a link to the previous location
			if($previous) {
				$previous_url = Locations::get_url($previous);
				$previous_label = i18n::s('Previous');
			}

			// locate the next location, if any
			if(!list($key, $next) = each($matches[1]))
				$next = NULL;

			// make a link to the next image
			else {
				$next_url = Locations::get_url($next);
				$next_label = i18n::s('Next');
			}

			// add a label
			$option_label = sprintf(i18n::s('Location %d of %d'), $index, count($matches[1]));

		}

		// return navigation info
		return array($previous_url, $previous_label, $next_url, $next_label, $option_url, $option_label);
	}

	/**
	 * get the path bar for this anchor
	 *
	 * This function is used to build a path bar relative to the anchor.
	 * For example, if you are displaying an article related to a section,
	 * the path bar has to mention the section. You can use following code
	 * to do that:
	 * [php]
	 * $anchor =& Anchors::get($article['anchor']);
	 * $context['path_bar'] = array_merge($context['path_bar'], $anchor->get_path_bar());
	 * [/php]
	 *
	 * This function uses the cache to save on database requests.
	 *
	 * @see shared/anchor.php
	 *
	 * @return an array of $url => $label
	 */
	function get_path_bar() {
		global $context;

		// get the parent
		if(!isset($this->anchor))
			$this->anchor =& Anchors::get($this->item['anchor']);

		// the parent level
		$parent = array();
		if(is_object($this->anchor) && $this->anchor->is_viewable())
			$parent = $this->anchor->get_path_bar();

		// this section
		$url = $this->get_url();
		$label = Codes::beautify_title($this->get_title());
		$data = array_merge($parent, array($url => $label));

		// return the result
		return $data;
	}

	/**
	 * get the reference for this anchor
	 *
	 * @see shared/anchor.php
	 *
	 * @return 'section:&lt;id&gt;', or NULL
	 */
	function get_reference() {
		if(isset($this->item['id']))
			return 'section:'.$this->item['id'];
		return NULL;
	}

	/**
	 * get the short url for this anchor
	 *
	 * If the anchor has one, this function returns a minimal url.
	 *
	 * @return an url to view the anchor page, or NULL
	 */
	function get_short_url() {
		if(isset($this->item['id']))
			return 's~'.reduce_number($this->item['id']);;
		return NULL;
	}

	/**
	 * get some introductory text from a section
	 *
	 * This function is used to introduce comments, or any sub-item related to an anchor.
	 * Compared to the standard anchor implementation, this one adds the ability to handle overlay data.
	 *
	 * If there is some introductory text, it is used. Else the description text is used instead.
	 * The number of words is capped in both cases.
	 *
	 * Also, the number of remaining words is provided.
	 *
	 * Following variants may be selected to adapt to various situations:
	 * - 'basic' - strip every tag, we want almost plain ASCII - maybe this will be send in a mail message
	 * - 'hover' - some text to be displayed while hovering a link
	 * - 'quote' - transform YACS codes, then strip most HTML tags
	 * - 'teaser' - limit the number of words, tranform YACS codes, and link to permalink
	 *
	 * @see shared/anchor.php
	 *
	 * @param string an optional variant
	 * @return NULL, of some text
	 */
	function &get_teaser($variant = 'basic') {
		global $context;

		// nothing to do
		if(!isset($this->item['id'])) {
			$text = NULL;
			return $text;
		}

		// the text to be returned
		$text = '';

		// use the introduction field, if any
		if($this->item['introduction']) {
			$text = trim($this->item['introduction']);

			// may be rendered as an empty strings
			if($variant != 'hover') {

				// remove toc and toq codes
				$text = preg_replace(FORBIDDEN_IN_TEASERS, '', $text);

				// render all codes
				if(is_callable(array('Codes', 'beautify')))
					$text =& Codes::beautify($text, $this->item['options']);

			}

			// preserve HTML
			if($variant != 'teaser') {

				// preserve breaks
				$text = preg_replace('/<(br *\/{0,1}|h1|\/h1|h2|\/h2|h3|\/h3|h4|\/h4|h5|\/h5|p|\/p|\/td)>/i', "<\\1>\n", $text);

				// strip most html tags
				$text = strip_tags($text, '<a><b><br><i><img><strong><u>');
			}

			// combine with description
			if($variant == 'quote')
				$text .= BR.BR;

		}

		// use overlay data, if any
		if(!$text) {
			include_once $context['path_to_root'].'overlays/overlay.php';
			$overlay = Overlay::load($this->item, 'section:'.$this->item['id']);
			if(is_object($overlay))
				$text .= $overlay->get_text('list', $this->item);
		}

		// use the description field, if any
		$in_description = FALSE;
		if((!$text && ($variant != 'hover')) || ($variant == 'quote')) {
			$text .= trim($this->item['description']);
			$in_description = TRUE;

			// remove toc and toq codes
			$text = preg_replace(FORBIDDEN_IN_TEASERS, '', $text);

			// render all codes
			if(is_callable(array('Codes', 'beautify')))
				$text =& Codes::beautify($text, $this->item['options']);

			// preserve breaks
			$text = preg_replace('/<(br *\/{0,1}|h1|\/h1|h2|\/h2|h3|\/h3|h4|\/h4|h5|\/h5|p|\/p|\/td)>/i', "<\\1>\n", $text);

			// strip most html tags
			$text = strip_tags($text, '<a><b><br><i><img><strong><u>');

			// remove new lines after breaks
			$text = preg_replace('/<(br *\/{0,1})>\n*/i', "<\\1>", $text);

		}

		// turn html entities to unicode entities
		$text =& utf8::transcode($text);

		// now we have to process the provided text
		switch($variant) {

		// strip everything
		case 'basic':
		default:

			// preserve breaks
			$text = preg_replace('/<(br *\/{0,1}|h1|\/h1|h2|\/h2|h3|\/h3|h4|\/h4|h5|\/h5|p|\/p|\/td)>/i', "<\\1>\n", $text);

			// strip every html tags
			$text = strip_tags($text);

			// limit the number of words
			$text =& Skin::cap($text, 70);

			// done
			return $text;

		// some text for pop-up panels
		case 'hover':

			// preserve breaks
			$text = preg_replace('/<(br *\/{0,1}|h1|\/h1|h2|\/h2|h3|\/h3|h4|\/h4|h5|\/h5|p|\/p|\/td)>/i', "<\\1>\n", $text);

			// strip every html tags
			$text = strip_tags($text);

			// limit the number of words
			$text =& Skin::strip($text, 70);

			// ensure we have some text
			if(!$text)
				$text = i18n::s('View the page');

			// mention shortcut to section
			if(Surfer::is_associate())
				$text .= ' [section='.$this->item['id'].']';

			// done
			return $text;

		// quote this
		case 'quote':

			// preserve breaks
			$text = preg_replace('/<(br *\/{0,1}|h1|\/h1|h2|\/h2|h3|\/h3|h4|\/h4|h5|\/h5|p|\/p|\/td)>/i', "<\\1>\n", $text);

			// strip most html tags
			$text = strip_tags($text, '<a><b><br><i><img><strong><u>');

			// limit the number of words
			$text =& Skin::cap($text, 300);

			// done
			return $text;

		// preserve as much as possible
		case 'teaser':

			// limit the number of words
			$text =& Skin::cap($text, 12, $this->get_url());

			// done
			return $text;

		}

	}

	/**
	 * get available templates
	 *
	 * This function climbs the tree of anchors if necessary.
	 *
	 * @param string the type of content to be created e.g., 'article', etc.
	 * @return array a list of models to consider, or NULL
	 */
	function get_templates_for($type='article') {

		// nothing found yet
		$output = NULL;

		if(($type == 'article') && isset($this->item['articles_templates']) && trim($this->item['articles_templates']))
			$output = trim($this->item['articles_templates']);

		// we already are at the top level
		elseif(isset($this->item['anchor'])) {

			// get the parent
			if(!isset($this->anchor))
				$this->anchor =& Anchors::get($this->item['anchor']);

			// ask parent level
			if(is_object($this->anchor))
				$output = $this->anchor->get_templates_for($type);

		}

		// done
		return $output;
	}

	/**
	 * get the url to display the thumbnail for this anchor
	 *
	 * A common concern of modern webmaster is to apply a reduced set of icons throughout all pages.
	 * This function is aiming to retrieve the small-size icon characterizing one anchor.
	 * It should be used in pages to display several images into lists of anchored items.
	 *
	 * Note: This function returns a URL to the thumbnail that is created by default
	 * when an icon is set for the section. However, the webmaster can decide to
	 * NOT display section thumbnails throughout the server. In this case, he/she
	 * has just to suppress the thumbnail URL in each section and that's it.
	 *
	 * @see shared/anchor.php
	 *
	 * @return an anchor to the thumbnail image
	 */
	function get_thumbnail_url() {
		if(isset($this->item['thumbnail_url']))
			return $this->item['thumbnail_url'];
		return NULL;
	}

	/**
	 * get the url to display the main page for this anchor
	 *
	 * @see shared/anchor.php
	 *
	 * @param string the targeted action ('view', 'print', 'edit', 'delete', ...)
	 * @return an anchor to the viewing script
	 */
	function get_url($action='view') {

		// sanity check
		if(!isset($this->item['id']))
			return NULL;

		switch($action) {

		// view comments
		case 'comments':
			if($this->has_option('view_as_tabs', FALSE))
				return $this->get_url().'#_discussion';
			return Sections::get_permalink($this->item).'#comments';

		// list of files
		case 'files':
			if($this->has_option('view_as_tabs', FALSE))
				return $this->get_url().'#_attachments';
			return Sections::get_permalink($this->item).'#files';

		// list of links
		case 'links':
			if($this->has_option('view_as_tabs', FALSE))
				return $this->get_url().'#_attachments';
			return Sections::get_permalink($this->item).'#links';

		// the permalink page
		case 'view':
			return Sections::get_permalink($this->item);

		// another action
		default:
			return Sections::get_url($this->item['id'], $action, $this->item['title'], $this->item['nick_name']);

		}
	}

	/**
	 * integrate a user profile, if applicable
	 *
	 * The process depends on following keywords being in the option field:
	 *
	 * [*] [code]with_prefix_profile[/code] -- if the variant is '[code]prefix[/code]',
	 * returns a full description of the poster
	 *
	 * [*] [code]with_suffix_profile[/code] -- if the variant is '[code]suffix[/code]',
	 * returns a textual description of the poster
	 *
	 * [*] [code]with_extra_profile[/code] -- if the variant is '[code]extra[/code]',
	 * returns a full description of the poster in a sidebox
	 *
	 * [*] Also, if the '[code]yabb[/code]' layout has been selected -- if the variant is '[code]prefix[/code]',
	 * displays an avatar for the user
	 *
	 *
	 * @see articles/view.php
	 *
	 * @param array one user profile
	 * @param string a profiling option, including 'prefix', 'suffix', and 'extra'
	 * @param string more information
	 * @return a string to be returned to the browser
	 */
	function get_user_profile($user, $variant='prefix', $more='') {
		global $context;

		// depending on the variant considered
		switch($variant) {

		// at the beginning of the page
		case 'prefix';

			// ensure the section has been configured for that
			if($this->has_option('with_prefix_profile'))
				return Skin::build_profile($user, 'prefix', $more);

			break;

		// at the end of the page
		case 'suffix':

			// ensure the section has been configured for that
			if($this->has_option('with_suffix_profile'))
				return Skin::build_profile($user, 'suffix', $more);

			break;

		// as a sidebox
		case 'extra':

			// ensure the section has been configured for that
			if($this->has_option('with_extra_profile'))
				return Skin::build_profile($user, 'extra', $more);

			break;

		}

		// nothing to do
		return '';


	}

	/**
	 * check that an option has been set for this section
	 *
	 * This function is used to control, from the anchor, the behaviour of linked items.
	 *
	 * This function recursively invokes upstream anchors, if any.
	 * For example, if the option 'skin_boxes' is set at the section level,
	 * all articles, but also all attached files and images of these articles,
	 * will feature the theme 'boxes'.
	 *
	 * If second parameter is TRUE, we are looking for an option that applies to articles of this section.
	 * Else we are looking for an option that applies to this section only.
	 *
	 * @param string the option we are looking for
	 * @param boolean TRUE if coming from content leaf, FALSE if coming from content branch
	 * @return TRUE or FALSE, or the value of the matching option if any
	 */
	 function has_option($option, $leaf=TRUE) {

		// sanity check
		if(!isset($this->item['id']))
			return FALSE;

		// 'locked' or not --at this level, do not climb the anchoring chain
		if($option == 'locked') {
			if(isset($this->item['locked']) && ($this->item['locked'] == 'Y'))
				return TRUE;
			else
				return FALSE;
		}

		// 'variant' matches with 'variant_red_background', return 'red_background'
		if($leaf && preg_match('/\b'.$option.'_(\w+?)\b/i', $this->item['content_options'], $matches))
			return $matches[1];

		// 'variant' matches with 'variant_red_background', return 'red_background'
		if(!$leaf && preg_match('/\b'.$option.'_(\w+?)\b/i', $this->item['options'], $matches))
			return $matches[1];

		// exact match, return TRUE
		if($leaf && preg_match('/\b'.$option.'\b/i', $this->item['content_options']))
			return TRUE;

		// exact match, return TRUE
		if(!$leaf && preg_match('/\b'.$option.'\b/i', $this->item['options']))
			return TRUE;

		// options that are not cascaded to sub-sections, because there is no way to revert from this setting
		$screened = '/(articles_by_publication' 	// no way to revert from this
			.'|articles_by_title'
			.'|comments_as_wall'
			.'|files_by_title'
			.'|forward_notifications'
			.'|links_by_title'
			.'|no_comments' 		// e.g. master section vs. sub-forum
			.'|no_links'
			.'|with_comments'		// no way to revert from this in sub-sections
			.'|with_extra_profile'	// only in blog
			.'|with_files'			// no way to depart from this in sub-sections
			.'|with_links'			// no way ...
			.'|with_prefix_profile' // only in discussion boards
			.'|with_suffix_profile)/';	// only in authoring sections

		// cascade options for this parent, if any
		if(!preg_match($screened, $option) && preg_match('/\b'.$option.'\b/i', $this->item['options']))
			return TRUE;

		// climb the anchoring chain, if any, but only for options to be cascaded
		if(!preg_match($screened, $option) && isset($this->item['anchor']) && $this->item['anchor']) {

			// save requests
			if(!$this->anchor)
				$this->anchor =& Anchors::get($this->item['anchor']);

			if(is_object($this->anchor))
				return $this->anchor->has_option($option, $leaf);
		}

		// no match
		return FALSE;
	}

	/**
	 * check that the surfer can edit this section
	 *
	 * This function is used to control the authority delegation from the anchor.
	 * For example, if some editor is assigned to a complete section of the
	 * web site, he/she should be able to edit all articles in this section.
	 * you can use following code to check that:
	 * [php]
	 * $anchor =& Anchors::get($article['anchor']);
	 * if($anchor->is_assigned() {
	 *	 ...
	 * }
	 * [/php]
	 *
	 * Compared to the original member function in shared/anchor.php, this one also
	 * checks rights of managing editors, and allows for anonymous changes.
	 *
	 * @param int optional reference to some user profile
	 * @param boolean TRUE to climb the list of containers up to the top
	 * @return TRUE or FALSE
	 */
	 function is_assigned($user_id=NULL, $cascade=TRUE) {
		global $context;

		// we need some data to proceed
		if(!isset($this->item['id']))
			return FALSE;

		// id of requesting user
		if(!$user_id)
			$user_id = Surfer::get_id();

		// anonymous is allowed
		if(!$user_id)
			$user_id = 0;

		// create the cache
		if(!isset($this->is_assigned_cache))
			$this->is_assigned_cache = array();

		// cache the answer
		if(isset($this->is_assigned_cache[$user_id]))
			return $this->is_assigned_cache[$user_id];

		// anonymous surfer has provided the secret handle
		if(isset($this->item['handle']) && Surfer::may_handle($this->item['handle']))
			return $this->is_assigned_cache[$user_id] = TRUE;

		// surfer owns this item
		if($user_id && isset($this->item['owner_id']) && ($user_id == $this->item['owner_id']))
			return $this->is_assigned_cache[$user_id] = TRUE;

		// section has been assigned to this surfer
		if($user_id && Members::check('user:'.$user_id, 'section:'.$this->item['id']))
			return $this->is_assigned_cache[$user_id] = TRUE;

		// anonymous edition is allowed
		if(($this->item['active'] == 'Y') && $this->has_option('anonymous_edit'))
			return $this->is_assigned_cache[$user_id] = TRUE;

		// members edition is allowed
		if(($this->item['active'] == 'Y') && Surfer::is_empowered('M') && $this->has_option('members_edit'))
			return $this->is_assigned_cache[$user_id] = TRUE;

		// check the upper level container
		if($cascade && isset($this->item['anchor'])) {

			// save requests
			if(!isset($this->anchor) || !$this->anchor)
				$this->anchor =& Anchors::get($this->item['anchor']);

			// check for ownership
			if(is_object($this->anchor))
				return $this->is_assigned_cache[$user_id] = $this->anchor->is_assigned($user_id);

		}

		// sorry
		return $this->is_assigned_cache[$user_id] = FALSE;
	 }

	/**
	 * determine if public access is allowed to the anchor
	 *
	 * This function is used to enable additional processing steps on public pages only.
	 * For example, only public pages are pinged on publication.
	 *
	 * @see articles/publish.php
	 *
	 * @return TRUE or FALSE
	 */
	 function is_public() {
		global $context;

		// cache the answer
		if(isset($this->is_public_cache))
			return $this->is_public_cache;

		if(isset($this->item['id'])) {

			// ensure the container allows for public access
			if(isset($this->item['anchor'])) {

				// save requests
				if(!isset($this->anchor) || !$this->anchor)
					$this->anchor =& Anchors::get($this->item['anchor']);

				if(is_object($this->anchor) && !$this->anchor->is_viewable())
					return $this->is_public_cache = FALSE;

			}

			// publicly available
			if(isset($this->item['active']) && ($this->item['active'] == 'Y'))
				return $this->is_public_cache = TRUE;

		}

		// sorry
		return $this->is_public_cache = FALSE;
	}

	/**
	 * load the related item
	 *
	 * @see shared/anchor.php
	 *
	 * @param int the id of the record to load
	 * @param boolean TRUE to always fetch a fresh instance, FALSE to enable cache
	 */
	function load_by_id($id, $mutable=FALSE) {
		$this->item =& Sections::get($id, $mutable);
	}

	/**
	 * restore a previous version of this section
	 *
	 * @see versions/restore.php
	 *
	 * @param array set of attributes to restore
	 * @return TRUE on success, FALSE otherwise
	 */
	function restore($item) {
		global $context;

		// restore this instance
		$this->item = $item;

		// save updated state
		return Sections::put($item);
	}

	/**
	 * change some attributes of an anchor
	 *
	 * @see shared/anchor.php
	 *
	 * @param array of (name, value)
	 * @return TRUE on success, FALSE otherwise
	 */
	function set_values($fields) {

		// add our id
		$fields['id'] = $this->item['id'];

		// save in the database
		return Sections::put_attributes($fields);

	}

	/**
	 * remember the last action for this section
	 *
	 * @see articles/article.php
	 * @see shared/anchor.php
	 *
	 * @param string the description of the last action
	 * @param string the id of the item related to this update
	 * @param boolean TRUE to not change the edit date of this anchor, default is FALSE
	 * @param boolean TRUE to notify section watchers, default is FALSE
	 * @param boolean TRUE to notify poster followers, default is FALSE
	 */
	function touch($action, $origin=NULL, $silently=FALSE, $to_watchers=FALSE, $to_followers=FALSE) {
		global $context;

		// don't go further on import
		if(preg_match('/import$/i', $action))
			return;

		// no section bound
		if(!isset($this->item['id']))
			return;

		// sanity check
		if(!$origin) {
			logger::remember('sections/section.php', 'unexpected NULL origin at touch()');
			return;
		}

		// components of the query
		$query = array();

		// a new page has been added to the section
		if($action == 'article:create') {

			// limit the number of items attached to this section
			if(isset($this->item['maximum_items']) && ($this->item['maximum_items'] > 10))
				Articles::purge_for_anchor('section:'.$this->item['id'], $this->item['maximum_items']);

		// a new comment has been posted
		} elseif($action == 'comment:create') {

			// purge oldest comments
			include_once $context['path_to_root'].'comments/comments.php';
			Comments::purge_for_anchor('section:'.$this->item['id']);

		// a new file has been attached
		} elseif(($action == 'file:create')) {

			// identify specific files
			$label = '';
			if(!Codes::check_embedded($this->item['description'], 'embed', $origin) && ($item = Files::get($origin))) {

				// give it to the Flash player
				if(isset($item['file_name']) && Files::is_embeddable($item['file_name']))
					$label = '[embed='.$origin.']';


			}

			// include flash videos in a regular page
			if($label)
				$query[] = "description = '".SQL::escape($this->item['description'].' '.$label)."'";


		// suppress references to a deleted file
		} elseif($action == 'file:delete') {

			// suppress reference in main description field
			$text = Codes::delete_embedded($this->item['description'], 'download', $origin);
			$text = Codes::delete_embedded($text, 'embed', $origin);
			$text = Codes::delete_embedded($text, 'file', $origin);

			// save changes
			$query[] = "description = '".SQL::escape($text)."'";

		// append a reference to a new image to the description
		} elseif($action == 'image:create') {
			if(!Codes::check_embedded($this->item['description'], 'image', $origin)) {

				// list has already started
				if(preg_match('/\[image=[^\]]+?\]\s*$/', $this->item['description']))
					$query[] = "description = '".SQL::escape($this->item['description'].' [image='.$origin.']')."'";

				// starting a new list of images
				else
					$query[] = "description = '".SQL::escape($this->item['description']."\n\n".'[image='.$origin.']')."'";
			}

			// also use it as thumnail if none has been defined yet
			if(!isset($this->item['thumbnail_url']) || !trim($this->item['thumbnail_url'])) {
				include_once $context['path_to_root'].'images/images.php';
				if(($image = Images::get($origin)) && ($url = Images::get_thumbnail_href($image)))
					$query[] = "thumbnail_url = '".SQL::escape($url)."'";
			}

			// refresh stamp only if image update occurs within 6 hours after last edition
			if(SQL::strtotime($this->item['edit_date']) + 6*60*60 < time())
				$silently = TRUE;

		// suppress a reference to an image that has been deleted
		} elseif($action == 'image:delete') {

			// suppress reference in main description field
			$query[] = "description = '".SQL::escape(Codes::delete_embedded($this->item['description'], 'image', $origin))."'";

			// suppress references as icon and thumbnail as well
			include_once $context['path_to_root'].'images/images.php';
			if($image =& Images::get($origin)) {

				if($url = Images::get_icon_href($image)) {
					if($this->item['icon_url'] == $url)
						$query[] = "icon_url = ''";
					if($this->item['thumbnail_url'] == $url)
						$query[] = "thumbnail_url = ''";
				}

				if($url = Images::get_thumbnail_href($image)) {
					if($this->item['icon_url'] == $url)
						$query[] = "icon_url = ''";
					if($this->item['thumbnail_url'] == $url)
						$query[] = "thumbnail_url = ''";
				}
			}

		// set an existing image as the section icon
		} elseif($action == 'image:set_as_icon') {
			include_once $context['path_to_root'].'images/images.php';
			if($image =& Images::get($origin)) {
				if($url = Images::get_icon_href($image))
					$query[] = "icon_url = '".SQL::escape($url)."'";

				// also use it as thumnail if none has been defined yet
				if(!(isset($this->item['thumbnail_url']) && trim($this->item['thumbnail_url'])) && ($url = Images::get_thumbnail_href($image)))
					$query[] = "thumbnail_url = '".SQL::escape($url)."'";

			} elseif($origin) {
				$query[] = "icon_url = '".SQL::escape($origin)."'";
			}
			$silently = TRUE;

		// set an existing image as the section thumbnail
		} elseif($action == 'image:set_as_thumbnail') {
			include_once $context['path_to_root'].'images/images.php';
			if($image =& Images::get($origin)) {

				// use the thumbnail for large files, or the image itself for smaller files
				if($image['image_size'] > $context['thumbnail_threshold'])
					$url = Images::get_thumbnail_href($image);
				else
					$url = Images::get_icon_href($image);
				$query[] = "thumbnail_url = '".SQL::escape($url)."'";

			} elseif($origin)
				$query[] = "thumbnail_url = '".SQL::escape($origin)."'";
			$silently = TRUE;

		// append a new image, and set it as the article thumbnail
		} elseif($action == 'image:set_as_both') {
			if(!Codes::check_embedded($this->item['description'], 'image', $origin))
				$query[] = "description = '".SQL::escape($this->item['description'].' [image='.$origin.']')."'";

			include_once $context['path_to_root'].'images/images.php';
			if($image =& Images::get($origin)) {

				// use the thumbnail for large files, or the image itself for smaller files
				if($image['image_size'] > $context['thumbnail_threshold'])
					$url = Images::get_thumbnail_href($image);
				else
					$url = Images::get_icon_href($image);
				$query[] = "thumbnail_url = '".SQL::escape($url)."'";

			} elseif($origin)
				$query[] = "thumbnail_url = '".SQL::escape($origin)."'";

			// do not remember minor changes
			$silently = TRUE;

		// add a reference to a new table in the section description
		} elseif($action == 'table:create') {
			if(!Codes::check_embedded($this->item['description'], 'table', $origin))
				$query[] = "description = '".SQL::escape($this->item['description'].' [table='.$origin.']')."'";

		// suppress a reference to a table that has been deleted
		} elseif($action == 'table:delete') {
			$query[] = "description = '".SQL::escape(Codes::delete_embedded($this->item['description'], 'table', $origin))."'";

		}

		// stamp the update
		if(!$silently)
			$query[] = "edit_name='".SQL::escape(Surfer::get_name())."',"
				."edit_id=".SQL::escape(Surfer::get_id()).","
				."edit_address='".SQL::escape(Surfer::get_email_address())."',"
				."edit_action='$action',"
				."edit_date='".SQL::escape(gmstrftime('%Y-%m-%d %H:%M:%S'))."'";

		// update the database
		if(@count($query)) {
			$query = "UPDATE ".SQL::table_name('sections')." SET ".implode(', ',$query)
				." WHERE id = ".SQL::escape($this->item['id']);
			SQL::query($query);
		}

		// send alerts on new item, or on article modification, or on section modification
		if(preg_match('/:create$/i', $action)
			|| !strncmp($action, 'article:', strlen('article:')) || !strncmp($action, 'section:', strlen('section:'))) {

			// poster name
			$surfer = Surfer::get_name();

			// mail message
			$mail = array('subject' => '', 'message' => '', 'headers' => '');

			// message subject
			$mail['subject'] = sprintf(i18n::c('%s: %s'), i18n::c('Contribution'), strip_tags($this->item['title']));

			// a comment has been added to a page in this section
			if($action == 'article:comment') {
				if(($target = Articles::get($origin, TRUE)) && $target['id']) {

					// mail subject
					$mail['subject'] = sprintf(i18n::c('%s: %s'), i18n::c('Contribution'), strip_tags($target['title']));

					// look for the last comment
					include_once $context['path_to_root'].'comments/comments.php';
					if($comment = Comments::get_newest_for_anchor('article:'.$origin)) {

						// mail content
						$mail['content'] = Comments::build_notification($comment);

						// threads messages
						$mail['headers'] = Mailer::set_thread('comment:'.$comment['id'], 'article:'.$target['id']);

						// message to watchers
						$mail['message'] = Mailer::build_notification($mail['content'], 1);

						// special case of article watchers
						if($to_watchers)
							Users::alert_watchers('article:'.$target['id'], $mail);

					}
				}

			// an article has been added to the section
			} elseif($action == 'article:create') {
				if(($target = Articles::get($origin, TRUE)) && $target['id']) {

					// message subject
					$mail['subject'] = sprintf(i18n::c('%s: %s'),
						ucfirst(strip_tags($this->item['title'])),
						ucfirst(strip_tags($target['title'])));

					// mail content
					$mail['content'] = Articles::build_notification($target, 'create');

					// threads messages
					$mail['headers'] = Mailer::set_thread('article:'.$target['id'], $this->get_reference());

				}

			// a file has been added to a page in this section
			} elseif($action == 'article:file') {
				if(($target = Articles::get($origin, TRUE)) && $target['id']) {

					// mail subject
					$mail['subject'] = sprintf(i18n::c('%s: %s'), i18n::c('Contribution'), strip_tags($target['title']));

					// look for the last upload
					if($file = Files::get_newest_for_anchor('article:'.$origin)) {

						// mail content
						$mail['content'] = Files::build_notification($file);

						// threads messages
						$mail['headers'] = Mailer::set_thread('file:'.$file['id'], 'article:'.$target['id']);

						// message to watchers
						$mail['message'] = Mailer::build_notification($mail['content'], 1);

						// special case of article watchers
						if($to_watchers)
							Users::alert_watchers('article:'.$target['id'], $mail);

					}
				}

			// an article has been published
			} elseif($action == 'article:publish') {
				if(($target = Articles::get($origin, TRUE)) && $target['id']) {

					// message subject
					$mail['subject'] = sprintf(i18n::c('%s: %s'),
						ucfirst(strip_tags($this->item['title'])),
						ucfirst(strip_tags($target['title'])));

					// mail content
					$mail['content'] = Articles::build_notification($target, 'publish');

					// threads messages
					$mail['headers'] = Mailer::set_thread('', 'article:'.$target['id']);

					// message to watchers
					$mail['message'] = Mailer::build_notification($mail['content'], 1);

					// special case of article watchers
					if($to_watchers)
						Users::alert_watchers('article:'.$target['id'], $mail);

				}

			// an article has been updated
			} elseif($action == 'article:update') {
				if(($target = Articles::get($origin, TRUE)) && $target['id']) {

					// mail subject
					$mail['subject'] = sprintf(i18n::c('%s: %s'), i18n::c('Contribution'), strip_tags($target['title']));

					// mail content
					$mail['content'] = Articles::build_notification($target, 'update');

					// threads messages
					$mail['headers'] = Mailer::set_thread('', 'article:'.$target['id']);

					// message to watchers
					$mail['message'] = Mailer::build_notification($mail['content'], 1);

					// special case of article watchers
					if($to_watchers)
						Users::alert_watchers('article:'.$target['id'], $mail);

				}

			// a file has been added to the section
			} else if($action == 'file:create') {
				if(($target = Files::get($origin, TRUE)) && $target['id']) {

					// mail content
					$mail['content'] = Files::build_notification($target);

					// threads messages
					$mail['headers'] = Mailer::set_thread('file:'.$target['id'], $this->get_reference());

				}

			// a comment has been added to the section
			} else if($action == 'comment:create') {
				include_once $context['path_to_root'].'comments/comments.php';
				if(($target = Comments::get($origin, TRUE)) && $target['id']) {

					// mail content
					$mail['content'] = Comments::build_notification($target);

					// threads messages
					$mail['headers'] = Mailer::set_thread('comment:'.$target['id'], $this->get_reference());

				}

			// a section has been added to the section
			} elseif($action == 'section:create') {
				if(($target = Sections::get($origin, TRUE)) && $target['id']) {

					// mail content
					$mail['content'] = Sections::build_notification($target, 'create');

					// threads messages
					$mail['headers'] = Mailer::set_thread('', $this->get_reference());

				}

			// a section has been updated
			} elseif($action == 'section:update') {
				if(($target = Sections::get($origin, TRUE)) && $target['id']) {

					// mail content
					$mail['content'] = Sections::build_notification($target, 'update');

					// threads messages
					$mail['headers'] = Mailer::set_thread('', 'section:'.$target['id']);

					// message to watchers
					$mail['message'] = Mailer::build_notification($mail['content'], 1);

					// special case of section watchers
					if($to_watchers)
						Users::alert_watchers('section:'.$target['id'], $mail);

				}

			// something else has been added to the section
			} else {

				// headline
				$headline  = sprintf(i18n::c('%s by %s'),
					Anchors::get_action_label($action),
					'<a href="'.$context['url_to_home'].$context['url_to_root'].Surfer::get_permalink().'">'.Surfer::get_name().'</a>');

				// start the notification
				$mail['content'] = Skin::build_mail_content($headline);

				// call for action
				$title = sprintf(i18n::c('%s in %s'), ucfirst($action), strip_tags($this->item['title']));
				$link = $context['url_to_home'].$context['url_to_root'].Sections::get_permalink($this->item);
				$menu = array(Skin::build_mail_button($link, $title, TRUE));
				$mail['content'] .= Skin::build_mail_menu($menu);

				// threads messages
				$mail['headers'] = Mailer::set_thread('', $this->get_reference());

			}

			// message to watchers
			$mail['message'] = Mailer::build_notification($mail['content'], 1);

			// scope of notifications is the originating page, and its parent section
			if(!strncmp($action, 'article:', strlen('article:')) && ($action != 'article:create') && ($action != 'article:publish')) {

				// we are the parent section
				$containers = array($this->get_reference());

				// forward notifications to grand-parent section too
				if(preg_match('/\bforward_notifications\b/i', $this->item['options']) && ($parent = $this->item['anchor']))
					$containers[] = $this->item['anchor'];

				// users assigned to this section only
				$restricted = NULL;
				if(($this->get_active() == 'N') && ($editors =& Members::list_anchors_for_member($this->get_focus()))) {
					foreach($editors as $editor)
						if(strpos($editor, 'user:') === 0)
							$restricted[] = substr($editor, strlen('user:'));
				}

				// alert watchers of this section
				if($to_watchers)
					Users::alert_watchers($containers, $mail, $restricted);

			// scope of notification is the originating section, and its parent section
			} elseif(!strncmp($action, 'section:', strlen('section:')) && ($action != 'section:create')) {

				// we will re-use the message sent to section watchers
				if(isset($mail['message']) && ($container = $this->get_parent())) {

					// users assigned only to parent section
					$restricted = NULL;
					if(($this->get_active() == 'N') && ($editors =& Members::list_anchors_for_member($this->get_focus()))) {
						foreach($editors as $editor)
							if(strpos($editor, 'user:') === 0)
								$restricted[] = substr($editor, strlen('user:'));
					}

					// alert all watchers at once
					if($to_watchers)
						Users::alert_watchers($container, $mail, $restricted);

				}

			// alert watchers of all sections upwards
			} else {

				// the path of containers to this item
				$containers = $this->get_focus();

				// autorized users
				$restricted = NULL;
				if(($this->get_active() == 'N') && ($editors =& Members::list_anchors_for_member($containers))) {
					foreach($editors as $editor)
						if(strpos($editor, 'user:') === 0)
							$restricted[] = substr($editor, strlen('user:'));
				}

				// alert all watchers at once
				if($to_watchers)
					Users::alert_watchers($containers, $mail, $restricted);

				// alert connections, except on private pages
				if(Surfer::get_id() && $to_followers && ($this->item['active'] != 'N')) {

					// message to connections
					$mail['message'] = Mailer::build_notification($mail['content'], 2);

					// alert connections
					Users::alert_watchers('user:'.Surfer::get_id(), $mail);
				}
			}
		}

		// always clear the cache, even on no update
		Sections::clear($this->item);

		// get the parent
		if(!$this->anchor)
			$this->anchor =& Anchors::get($this->item['anchor']);

		// propagate the touch upwards silently -- we only want to purge the cache
		if(is_object($this->anchor))
			$this->anchor->touch('section:touch', $this->item['id'], TRUE);

	}

	/**
	 * transcode some references
	 *
	 * @see images/images.php
	 *
	 * @param array of pairs of strings to be used in preg_replace()
	 */
	function transcode($transcoded) {
		global $context;

		// no item bound
		if(!isset($this->item['id']))
			return;

		// prepare preg_replace()
		$from = array();
		$to = array();
		foreach($transcoded as $pair) {
			$from[] = $pair[0];
			$to[] = $pair[1];
		}

		// transcode various fields
		$this->item['introduction'] = preg_replace($from, $to, $this->item['introduction']);
		$this->item['description'] = preg_replace($from, $to, $this->item['description']);

		// update the database
		$query = "UPDATE ".SQL::table_name('articles')." SET "
			." introduction = '".SQL::escape($this->item['introduction'])."',"
			." description = '".SQL::escape($this->item['description'])."'"
			." WHERE id = ".SQL::escape($this->item['id']);
		SQL::query($query);

		// always clear the cache, even on no update
		Sections::clear($this->item);

	}

}

// stop hackers
defined('YACS') or exit('Script must be included');

// load localized strings
if(is_callable(array('i18n', 'bind')))
	i18n::bind('sections');

?>
