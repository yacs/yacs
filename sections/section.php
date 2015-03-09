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
 * @author Christophe Battarel
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Section extends Anchor {

	/**
	 * get the canvas for articles of this anchor
	 *
	 * @return articles canvas
	 */
	function get_articles_canvas() {
		if(isset($this->item['articles_canvas']))
			return $this->item['articles_canvas'];

		// do not transmit anything instead
		return NULL;
	}

	/**
	 * get the url to display the icon for this anchor
	 *
	 * @see shared/anchor.php
	 *
	 * @return an anchor to the icon image
	 */
	function get_icon_url() {
		if(isset($this->item['icon_url']) && $this->item['icon_url'])
			return $this->item['icon_url'];

		// do not transmit the thumbnail instead
		return NULL;
	}
	
	/**
	 * list childs of this anchor, with or without type filters
	 * 
	 * @param string set of desired childs (articles, sections...) separted by comma, or "all" keyword
	 * @param int offset to start listing
	 * @param int the maximum of items returned per type
	 * @param mixed string or object the layout to use
	 * @return an array of array with raw items sorted by type
	 */
	function get_childs($filter = 'all',$offset = 0, $max= 50, $layout='raw') {
	    
	    // we return a array
	    $childs = array();	
	    
	     // sub sections
	    if($filter == 'all' || preg_match('/\bsections?\b/i', $filter)) {
		$childs['section'] = Sections::list_by_title_for_anchor($this->get_reference(), $offset, $max, $layout);
	    }
	    
	    // sub articles
	    if($filter == 'all' || preg_match('/\barticles?\b/i', $filter)) {
		$childs['article'] = Articles::list_for_anchor_by('title', $this->get_reference(), $offset, $max, $layout);
	    }	    	   	    	    	  
	    
	    // files
	    if($filter == 'all' || preg_match('/\bfiles?\b/i', $filter)) {
		$childs['file'] = Files::list_by_title_for_anchor($this->get_reference(), $offset, $max, $layout);
	    }	
		    
	    
	    return $childs;
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

		throw new exception('function get_label() in sections/section.php has been obsoleted');

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

		// fall-back on default behavior
		return parent::get_label($variant, $id, $title);

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
	function get_neighbours($type, $item) {
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
			$option_label = Skin::build_link($this->get_url(), i18n::s('Index'), 'pager-item');

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
	 * get permalink to item
	 */
	function get_permalink() {
	    if(!isset($this->item['id']))
		    return NULL;
	    
	    $link = Sections::get_permalink($this->item);
	    return $link;
	}
	
	/**
	 * provide classe name with all static functions on this kind of anchor
	 * 
	 * @return a class name
	 */
	function get_static_group_class() {
	    return 'Sections';
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

			// remove most html
			if($variant != 'teaser')
				$text = xml::strip_visible_tags($text);

			// combine with description
			if($variant == 'quote')
				$text .= BR.BR;

		}

		// use overlay data, if any
		if(!$text) {
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

			// remove most html
			$text = xml::strip_visible_tags($text);

		}

		// turn html entities to unicode entities
		$text =& utf8::transcode($text);

		// now we have to process the provided text
		switch($variant) {

		// strip everything
		case 'basic':
		default:

			// remove most html
			$text = xml::strip_visible_tags($text);

			// limit the number of words
			$text =& Skin::cap($text, 70);

			// done
			return $text;

		// some text for pop-up panels
		case 'hover':

			// remove most html
			$text = xml::strip_visible_tags($text);

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

			// remove most html
			$text = xml::strip_visible_tags($text);

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

		// else climb the content tree
		elseif(isset($this->item['anchor'])) {

			// get the parent
			if(!isset($this->anchor))
				$this->anchor = Anchors::get($this->item['anchor']);

			// ask parent level
			if(is_object($this->anchor))
				$output = $this->anchor->get_templates_for($type);

		}

		// done
		return $output;
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
			return Sections::get_url($this->item['id'], 'view', $this->item['title'], $this->item['nick_name']).'#comments';

		// list of files
		case 'files':
			return $this->get_url().'#_attachments';

		// list of links
		case 'links':
			return $this->get_url().'#_attachments';

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
	 * list all items in the watching context
	 *
	 * If the action is related to the creation of a published page, or to the publication
	 * of a draft page, then all containing sections up to the content tree are included
	 * in the wathing context.
	 *
	 * In other cases, the watching context is limited by default to the section itself,
	 * and to its parent container. If the container has option 'forward_notifications',
	 * then the context is extended to its parent too. The forwarding is recursive
	 * until no option 'forward_notifications' is found.
	 *
	 * Called in function alert_watchers() in shared/anchor.php
	 *
	 * @param string description of the on-going action (e.g., 'file:create')
	 * @return mixed either a reference (e.g., 'article:123') or an array of references
	 */
	protected function get_watched_context($action) {
		global $context;

		// a page has been created, we will look at all sections upwards
		if(($action == 'article:publish') || ($action == 'article:submit'))
			return $this->get_focus();

		// else limit ourselves to watchers of this section, and to forwarding parent sections
		$containers = array();
		$handle = $this->get_reference();
		while($handle && ($container = Anchors::get($handle))) {

			// add watchers of this level
			$containers[] = $handle;

			// should we forward notifications upwards
			if(!$container->has_option('forward_notifications', FALSE))
				break;

			// add watchers of next level
			$handle = $container->get_parent();
		}

		// by default, limit to direct watchers of this anchor
		return $containers;
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
				$this->anchor = Anchors::get($this->item['anchor']);

			if(is_object($this->anchor))
				return $this->anchor->has_option($option, $leaf);
		}

		// no match
		return FALSE;
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
		$this->item = Sections::get($id, $mutable);
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
	 */
	function touch($action, $origin=NULL, $silently=FALSE) {
		global $context;

		// we make extensive use of comments below
		include_once $context['path_to_root'].'comments/comments.php';

		// don't go further on import
		if(preg_match('/import$/i', $action))
			return;

		// no section bound
		if(!isset($this->item['id']))
			return;
                
                // delegate to overlay
                if(is_object($this->overlay) && $overlay->touch($action, $origin, $silently) === false) {
                        return; // stop on false
                }

		// sanity check
		if(!$origin) {
			logger::remember('sections/section.php: unexpected NULL origin at touch()');
			return;
		}

		// components of the query
		$query = array();

		// a new page has been added to the section
		if(($action == 'article:publish') || ($action == 'article:submit')) {

			// limit the number of items attached to this section
			if(isset($this->item['maximum_items']) && ($this->item['maximum_items'] > 10))
				Articles::purge_for_anchor('section:'.$this->item['id'], $this->item['maximum_items']);

		// a new comment has been posted
		} elseif($action == 'comment:create') {

			// purge oldest comments
			Comments::purge_for_anchor('section:'.$this->item['id']);

		// file upload
		} elseif(($action == 'file:create') || ($action == 'file:upload')) {

			// actually, several files have been added
			$label = '';
			if(!$origin) {
				$fields = array();
				$fields['anchor'] = 'section:'.$this->item['id'];
				$fields['description'] = i18n::s('Several files have been added');
				$fields['type'] = 'notification';
				Comments::post($fields);

			// one file has been added
			} elseif(!Codes::check_embedded($this->item['description'], 'embed', $origin) && ($item = Files::get($origin, TRUE))) {

				// this file is eligible for being embedded in the page
				if(isset($item['file_name']) && Files::is_embeddable($item['file_name'])) {

					// the overlay may prevent embedding
					if(is_object($this->overlay) && !$this->overlay->should_embed_files())
						;

					// else use a yacs code to implement the embedded object
					else
						$label = '[embed='.$origin.']';

				// else add a comment to take note of the upload
				} elseif(Comments::allow_creation($this->item, null, 'section')) {
					$fields = array();
					$fields['anchor'] = 'section:'.$this->item['id'];
					if($action == 'file:create')
						$fields['description'] = '[file='.$item['id'].','.$item['file_name'].']';
					else
						$fields['description'] = '[download='.$item['id'].','.$item['file_name'].']';
					Comments::post($fields);

				}

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
			    
				// the overlay may prevent embedding
				if(is_object($this->overlay) && !$this->overlay->should_embed_files())
						;    
				else {
				    // list has already started
				    if(preg_match('/\[image=[^\]]+?\]\s*$/', $this->item['description']))
					    $query[] = "description = '".SQL::escape($this->item['description'].' [image='.$origin.']')."'";

				    // starting a new list of images
				    else
					    $query[] = "description = '".SQL::escape($this->item['description']."\n\n".'[image='.$origin.']')."'";
				}
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
			if($image = Images::get($origin)) {

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
			if($image = Images::get($origin)) {
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
			if($image = Images::get($origin)) {

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
			if($image = Images::get($origin)) {

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

		// always clear the cache, even on no update
		Sections::clear($this->item);

		// get the parent
		if(!$this->anchor)
			$this->anchor = Anchors::get($this->item['anchor']);

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
		$query = "UPDATE ".SQL::table_name('sections')." SET "
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
