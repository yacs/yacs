<?php
// stop hackers
defined('YACS') or exit('Script must be included');

/**
 * the implementation of anchor for sections
 *
 * @todo process image:set_as_thumbnail in touch() like in articles/article.php
 *
 * This class implements the Anchor interface for sections.
 *
 * @see shared/anchor.php
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Section extends Anchor {

	/**
	 * get the url to display bullet images
	 *
	 * @return an anchor to the icon image
	 *
	 * @see shared/anchor.php
	 */
	function get_bullet_url() {
		if(isset($this->item['bullet_url']))
			return $this->item['bullet_url'];
		return NULL;
	}

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
			$this->anchor = Anchors::get($this->item['anchor']);

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
	 * @return an anchor to the icon image
	 *
	 * @see shared/anchor.php
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
	  * @param string the module that is invoking the anchor (e.g., 'comments')
	  * @param string the target label (e.g., 'edit_title', 'item_name', 'item_names')
	  * @param string an optional title, if any
	  * @return string the foreseen label
	  */
	 function get_label($variant, $id, $title='') {
		global $context;

		// sanity check
		if(!isset($this->item['id']))
			return FALSE;

		// load localized strings
		i18n::bind('sections');

		// a default title
		if(!$title)
			$title = $this->get_title();

		// strings for comments
		if($variant == 'comments') {

			switch($id) {

			// many comments
			case 'count_many':
				if($this->has_layout('jive'))
					return i18n::s('replies');
				if($this->has_layout('manual'))
					return i18n::s('notes');
				if($this->has_layout('wiki'))
					return i18n::s('notes');
				return i18n::s('comments');

			// one comment
			case 'count_one':
				if($this->has_layout('jive'))
					return i18n::s('reply');
				if($this->has_layout('manual'))
					return i18n::s('note');
				if($this->has_layout('wiki'))
					return i18n::s('note');
				return i18n::s('comment');

			// command to delete a comment
			case 'delete_command':
				if($this->has_layout('jive'))
					return i18n::s('Yes, I want to delete this reply');
				if($this->has_layout('manual'))
					return i18n::s('Yes, I want to delete this note');
				if($this->has_layout('wiki'))
					return i18n::s('Yes, I want to delete this note');
				return i18n::s('Yes, I want to delete this comment');

			// page title to delete a comment
			case 'delete_title':
				if($this->has_layout('jive'))
					return i18n::s('Delete a reply');
				if($this->has_layout('manual'))
					return i18n::s('Delete a note');
				if($this->has_layout('wiki'))
					return i18n::s('Delete a note');
				return i18n::s('Delete a comment');

			// command to edit content
			case 'edit_command':
				if($this->has_layout('jive'))
					return i18n::s('Edit the new reply');
				if($this->has_layout('manual'))
					return i18n::s('Edit the new note');
				if($this->has_layout('wiki'))
					return i18n::s('Edit the new note');
				return i18n::s('Edit the new comment');

			// command to promote a comment
			case 'promote_command':
				if($this->has_layout('jive'))
					return i18n::s('Yes, I want to turn this reply to an article');
				if($this->has_layout('manual'))
					return i18n::s('Yes, I want to turn this note to an article');
				if($this->has_layout('wiki'))
					return i18n::s('Yes, I want to turn this note to an article');
				return i18n::s('Yes, I want to turn this comment to an article');

			// page title to promote a comment
			case 'promote_title':
				if($this->has_layout('jive'))
					return i18n::s('Promote a reply');
				if($this->has_layout('manual'))
					return i18n::s('Promote a note');
				if($this->has_layout('wiki'))
					return i18n::s('Promote a note');
				return i18n::s('Promote a comment');

			// command to view the thread
			case 'thread_command':
				if($this->has_layout('manual'))
					return i18n::s('Go back to user notes');
				if($this->has_layout('wiki'))
					return i18n::s('Go back to the main page');
				return i18n::s('Go back to the updated thread');

			// page title to modify a comment
			case 'edit_title':
				if($this->has_layout('jive'))
					return i18n::s('Update a reply');
				if($this->has_layout('manual'))
					return i18n::s('Update a note');
				if($this->has_layout('wiki'))
					return i18n::s('Edit this note');
				return i18n::s('Edit a comment');

			// page title to list comments
			case 'list_title':
				if($this->has_layout('jive'))
					return sprintf(i18n::s('Replies: %s'), $title);
				if($this->has_layout('manual'))
					return sprintf(i18n::s('Notes: %s'), $title);
				if($this->has_layout('wiki'))
					return sprintf(i18n::s('Notes: %s'), $title);
				return sprintf(i18n::s('Discuss: %s'), $title);

			// command to create a comment
			case 'new_command':
				if($this->has_layout('jive'))
					return i18n::s('Reply to this post');
				if($this->has_layout('manual'))
					return i18n::s('Annotate this page');
				if($this->has_layout('wiki'))
					return i18n::s('Annotate this page');
				return i18n::s('Add a comment');

			// page title to create a comment
			case 'new_title':
				if($this->has_layout('jive'))
					return i18n::s('Reply to this post');
				if($this->has_layout('manual'))
					return i18n::s('Annotate this page');
				if($this->has_layout('wiki'))
					return i18n::s('Annotate this page');
				return i18n::s('Add a comment');

			// command to view content
			case 'view_command':
				if($this->has_layout('jive'))
					return i18n::s('View the reply');
				if($this->has_layout('manual'))
					return i18n::s('View the note');
				if($this->has_layout('wiki'))
					return i18n::s('View the note');
				return i18n::s('View the comment');

			// page title to view a comment
			case 'view_title':
				if($this->has_layout('jive'))
					return sprintf(i18n::s('Reply: %s'), $title);
				if($this->has_layout('manual'))
					return sprintf(i18n::s('Note: %s'), $title);
				if($this->has_layout('wiki'))
					return sprintf(i18n::s('Note: %s'), $title);
				return sprintf(i18n::s('Comment: %s'), $title);
			}

		}

		// climb the anchoring chain, if any
		if(isset($this->item['anchor']) && $this->item['anchor']) {

			// cache anchor
			if(!$this->anchor)
				$this->anchor = Anchors::get($this->item['anchor']);

			if(is_object($this->anchor))
				return $this->anchor->get_label($variant, $id);

		}

		// no match
		return 'Impossible to translate '.$id.' for module '.$variant;
	}

	/**
	 * get next and previous items, if any
	 *
	 * @param string the item type (eg, 'article', 'image', 'file', etc.)
	 * @param array the anchored item asking for neighbours
	 * @return an array($previous_url, $previous_label, $next_url, $next_label, $option_url, $option_label), or NULL
	 *
	 * @see shared/anchor.php
	 */
	function get_neighbours($type, &$item) {
		global $context;

		// no item bound
		if(!isset($this->item['id']))
			return NULL;

		// load localized strings
		i18n::bind('sections');

		// initialize components
		$previous_url = $previous_label = $next_url = $next_label = $option_url = $option_label ='';

		// previous and next articles
		if($type == 'article') {

			// determine on-going order
			if(preg_match('/\barticles_by_title\b/i', $this->item['options']))
				$order = 'title';
			elseif(preg_match('/\barticles_by_publication\b/i', $this->item['options']))
				$order = 'publication';
			elseif(preg_match('/\barticles_by_rating\b/i', $this->item['options']))
				$order = 'rating';
			elseif(preg_match('/\barticles_by_reverse_rank\b/i', $this->item['options']))
				$order = 'reverse_rank';
			else
				$order = 'date';

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

			// load the adequate library
			include_once $context['path_to_root'].'files/files.php';


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
	 * $anchor = Anchors::get($article['anchor']);
	 * $context['path_bar'] = array_merge($context['path_bar'], $anchor->get_path_bar());
	 * [/php]
	 *
	 * This function uses the cache to save on database requests.
	 *
	 * @return an array of $url => $label
	 *
	 * @see shared/anchor.php
	 */
	function get_path_bar() {
		global $context;

		// get the parent
		if(!isset($this->anchor))
			$this->anchor = Anchors::get($this->item['anchor']);

		// the parent level
		$parent = array();
		if(is_object($this->anchor))
			$parent = $this->anchor->get_path_bar();

		// this section
		$url = $this->get_url();
		include_once $context['path_to_root'].'codes/codes.php';
		$label = Codes::beautify_title($this->get_title());
		$data = array_merge($parent, array($url => $label));

		// return the result
		return $data;
	}

	/**
	 * get the reference for this anchor
	 *
	 * @return 'section:&lt;id&gt;', or NULL
	 *
	 * @see shared/anchor.php
	 */
	function get_reference() {
		if(isset($this->item['id']))
			return 'section:'.$this->item['id'];
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
	 * @param string an optional variant
	 * @return NULL, of some text
	 *
	 * @see shared/anchor.php
	 */
	function &get_teaser($variant = 'basic') {
		global $context;

		// nothing to do
		if(!isset($this->item['id'])) {
			$text = NULL;
			return $text;
		}

		// load localized strings
		i18n::bind('sections');

		// the text to be returned
		$text = '';

		// use the introduction field, if any
		if($this->item['introduction']) {
			$text = trim($this->item['introduction']);

			// may be rendered as an empty strings
			if($variant != 'hover') {

				// remove toc and toq codes
				$text = preg_replace(FORBIDDEN_CODES_IN_TEASERS, '', $text);

				// render all codes
				if(is_callable(array('Codes', 'beautify')))
					$text =& Codes::beautify($text, $this->item['options']);

				// strip all pairing YACS codes (delete tables, etc.)
				else
					$text = preg_replace('/\[(.*?).*?\](.*?)\[\/\1\]/s', '${2}', $text);

			}

			// preserve HTML
			if($variant != 'teaser') {

				// preserve breaks
				$text = preg_replace('/<(br *\/*|h1|\/h1|h2|\/h2|h3|\/h3|h4|\/h4|h5|\/h5|p|\/p|\/td)>/i', "<\\1>\n", $text);

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
			$overlay = Overlay::load($this->item);
			if(is_object($overlay))
				$text .= $overlay->get_text('list', $this->item);
		}

		// use the description field, if any
		$in_description = FALSE;
		if((!$text && ($variant != 'hover')) || ($variant == 'quote')) {
			$text .= trim($this->item['description']);
			$in_description = TRUE;

			// remove toc and toq codes
			$text = preg_replace(FORBIDDEN_CODES_IN_TEASERS, '', $text);

			// render all codes
			if(is_callable(array('Codes', 'beautify')))
				$text =& Codes::beautify($text, $this->item['options']);

			// strip all pairing YACS codes (delete tables, etc.)
			else
				$text = preg_replace('/\[(.*?).*?\](.*?)\[\/\1\]/s', '${2}', $text);

			// preserve breaks
			$text = preg_replace('/<(br *\/*|h1|\/h1|h2|\/h2|h3|\/h3|h4|\/h4|h5|\/h5|p|\/p|\/td)>/i', "<\\1>\n", $text);

			// strip most html tags
			$text = strip_tags($text, '<a><b><br><i><img><strong><u>');

			// remove new lines after breaks
			$text = preg_replace('/<(br *\/*)>\n*/i', "<\\1>", $text);

		}

		// turn html entities to unicode entities
		$text =& utf8::transcode($text);

		// now we have to process the provided text
		switch($variant) {

		// strip everything
		case 'basic':
		default:

			// preserve breaks
			$text = preg_replace('/<(br *\/*|h1|\/h1|h2|\/h2|h3|\/h3|h4|\/h4|h5|\/h5|p|\/p|\/td)>/i', "<\\1>\n", $text);

			// strip every html tags
			$text = strip_tags($text);

			// limit the number of words
			$text =& Skin::cap($text, 70);

			// done
			return $text;

		// some text for pop-up panels
		case 'hover':

			// preserve breaks
			$text = preg_replace('/<(br *\/*|h1|\/h1|h2|\/h2|h3|\/h3|h4|\/h4|h5|\/h5|p|\/p|\/td)>/i', "<\\1>\n", $text);

			// strip every html tags
			$text = strip_tags($text);

			// limit the number of words
			$text =& Skin::strip($text, 70);

			// ensure we have some text
			if(!$text)
				$text = i18n::s('Read this section');

			// mention shortcut to section
			if(Surfer::is_associate())
				$text .= ' [section='.$this->item['id'].']';

			// done
			return $text;

		// quote this
		case 'quote':

			// preserve breaks
			$text = preg_replace('/<(br *\/*|h1|\/h1|h2|\/h2|h3|\/h3|h4|\/h4|h5|\/h5|p|\/p|\/td)>/i', "<\\1>\n", $text);

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
				$this->anchor = Anchors::get($this->item['anchor']);

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
	 * @return an anchor to the thumbnail image
	 *
	 * @see shared/anchor.php
	 */
	function get_thumbnail_url() {
		if(isset($this->item['thumbnail_url']))
			return $this->item['thumbnail_url'];
		return NULL;
	}

	/**
	 * get the url to display the main page for this anchor
	 *
	 * @param string the targeted action ('view', 'print', 'edit', 'delete', ...)
	 * @return an anchor to the viewing script
	 *
	 * @see shared/anchor.php
	 */
	function get_url($action='view') {
		if(isset($this->item['id']))
			return Sections::get_url($this->item['id'], $action, $this->item['title'], $this->item['nick_name']);
		return NULL;
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
	 * @param array one user profile
	 * @param string a profiling option, including 'prefix', 'suffix', and 'extra'
	 * @return a string to be returned to the browser
	 *
	 * @see articles/view.php
	 */
	function get_user_profile($user, $variant='prefix') {
		global $context;

		// no user profile on mobiles
		if(isset($context['skin_variant']) && ($context['skin_variant'] == 'mobile'))
			return '';

		// depending on the variant considered
		switch($variant) {

		// at the beginning of the page
		case 'prefix';

//			// for discussion boards, display only the poster avatar at the beginning of the page
//			if($this->has_layout('yabb') && isset($user['avatar_url']) && $user['avatar_url'])
//				return '<img src="'.$user['avatar_url'].'" alt="avatar" style="padding: 8px 8px 8px 0; text-align: left;"'.EOT;

			// ensure the section has been configured for that
			if($this->has_option('with_prefix_profile'))
				return Skin::build_profile($user, 'prefix');

			break;

		// at the end of the page
		case 'suffix':

			// ensure the section has been configured for that
			if($this->has_option('with_suffix_profile'))
				return Skin::build_profile($user, 'suffix');

			break;

		// as a sidebox
		case 'extra':

			// ensure the section has been configured for that
			if($this->has_option('with_extra_profile'))
				return Skin::build_profile($user, 'extra');

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
	 * will feature the skin 'boxes'.
	 *
	 * @param string the option we are looking for
	 * @return TRUE or FALSE, or the value of the matching option if any
	 */
	 function has_option($option) {

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
		if(preg_match('/\b'.$option.'_(\w+?)\b/i', $this->item['content_options'], $matches))
			return $matches[1];

		// 'variant' matches with 'variant_red_background', return 'red_background'
		if(preg_match('/\b'.$option.'_(\w+?)\b/i', $this->item['options'], $matches))
			return $matches[1];

		// exact match, return TRUE
		if(preg_match('/\b'.$option.'\b/i', $this->item['content_options']))
			return TRUE;

		// exact match, return TRUE
		if(preg_match('/\b'.$option.'\b/i', $this->item['options']))
			return TRUE;

		// options that are not cascaded to sub-sections -- e.g. extra boxes aside a forum
		$screened = '/(anonymous_edit'		// security hole if cascaded
			.'|articles_by_publication' 	// no way to revert from this
			.'|articles_by_title'
			.'|auto_publish'		// e.g. extra boxes aside a forum...
			.'|files_by_title'
			.'|links_by_title'
			.'|members_edit'		// security hole if cascaded
			.'|no_comments' 		// e.g. master section vs. sub-forum
			.'|no_links'
			.'|no_neighbours'
			.'|with_bottom_tools'
			.'|with_comments'		// no way to revert from this in sub-sections
			.'|with_extra_profile'	// only in blog
			.'|with_files'			// no way to depart from this in sub-sections
			.'|with_links'			// no way ...
			.'|with_prefix_profile' // only in discussion boards
			.'|with_rating'
			.'|with_slideshow'
			.'|with_suffix_profile)/';	// only in authoring sections

		// climb the anchoring chain, if any, but only for options to be cascaded
		if(!preg_match($screened, $option) && isset($this->item['anchor']) && $this->item['anchor']) {

			// save requests
			if(!$this->anchor)
				$this->anchor = Anchors::get($this->item['anchor']);

			if(is_object($this->anchor))
				return $this->anchor->has_option($option);
		}

		// no match
		return FALSE;
	}

	/**
	 * check that the surfer is an editor of an anchor
	 *
	 * This function is used to control the authority delegation from the anchor.
	 * For example, if some editor is assigned to a complete section of the
	 * web site, he/she should be able to edit all articles in this section.
	 * you can use following code to check that:
	 * [php]
	 * $anchor = Anchors::get($article['anchor']);
	 * if($anchor->is_editable() {
	 *	 ...
	 * }
	 * [/php]
	 *
	 * A logged member is always considered as an editor if he has created the target item.
	 *
	 * Compared to the original member function in shared/anchor.php, this one also
	 * checks rights of managing editors, and allows for anonymous changes.
	 *
	 * @param int optional reference to some user profile
	 * @return TRUE or FALSE
	 */
	 function is_editable($user_id=NULL) {
		global $context;

		// cache the answer
		if(isset($this->is_editable_cache))
			return $this->is_editable_cache;

		if(isset($this->item['id'])) {

			// anonymous edition is allowed
			if($this->has_option('anonymous_edit'))
				return $this->is_editable_cache = TRUE;

			// members edition is allowed
			if(Surfer::is_member() && $this->has_option('members_edit'))
				return $this->is_editable_cache = TRUE;

			// id of requesting user
			if(!$user_id && Surfer::get_id())
				$user_id = Surfer::get_id();

			// maybe the logged surfer is the creator
//			if($this->item['create_id'] && $user_id && ($this->item['create_id'] == $user_id))
//				return $this->is_editable_cache = TRUE;

			// authenticated subscriptors cannot contribute
			if(!Surfer::is_logged() || Surfer::is_member()) {

				// maybe the current surfer has been explicitly defined as a managing editor
				if($user_id && Members::check('user:'.$user_id, 'section:'.$this->item['id']))
					return $this->is_editable_cache = TRUE;

			}

			// check the upper level container
			if(isset($this->item['anchor'])) {

				// save requests
				if(!isset($this->anchor) || !$this->anchor)
					$this->anchor = Anchors::get($this->item['anchor']);

				if(is_object($this->anchor) && $this->anchor->is_editable($user_id))
					return $this->is_editable_cache = TRUE;

			}

		}
		// sorry
		return $this->is_editable_cache = FALSE;
	 }

	/**
	 * determine if public access is allowed to the anchor
	 *
	 * This function is used to enable additional processing steps on public pages only.
	 * For example, only public pages are pinged on publication.
	 *
	 * @return TRUE or FALSE
	 *
	 * @see articles/publish.php
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
					$this->anchor = Anchors::get($this->item['anchor']);

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
	 * check that the surfer is allowed to display the anchor
	 *
	 * This function is used to control the authority delegation from the anchor.
	 *
	 * @return TRUE or FALSE
	 */
	 function is_viewable() {
		global $context;

		// cache the answer
		if(isset($this->is_viewable_cache))
			return $this->is_viewable_cache;

		if(isset($this->item['id'])) {

			// associates and editors can do what they want
			if(Surfer::is_associate() || $this->is_editable())
				return $this->is_viewable_cache = TRUE;

			// maybe the logged surfer is the creator
			if($this->item['create_id'] && Surfer::get_id() && ($this->item['create_id'] == Surfer::get_id()))
				return $this->is_viewable_cache = TRUE;

			// section has been assigned to this logged user
			if(Sections::is_assigned($this->item['id']))
				return $this->is_viewable_cache = TRUE;

			// ensure the container can be viewed
			if(isset($this->item['anchor'])) {

				// cache requests
				if(!isset($this->anchor) || !$this->anchor)
					$this->anchor = Anchors::get($this->item['anchor']);

				// parent container has been assigned to this surfer
				if(is_object($this->anchor) && $this->anchor->is_assigned())
					return $this->is_viewable_cache = TRUE;

				// parent container is not visible
				if(is_object($this->anchor) && !$this->anchor->is_viewable())
					return $this->is_viewable_cache = FALSE;

			}

			// access is restricted to authenticated surfers
			if(($this->item['active'] == 'R') && Surfer::is_logged())
				return $this->is_viewable_cache = TRUE;

			// public access to the anchor is allowed
			if($this->item['active'] == 'Y')
				return $this->is_viewable_cache = TRUE;

		}

		// sorry
		return $this->is_viewable_cache = FALSE;
	 }

	/**
	 * load the related item
	 *
	 * @param int the id of the record to load
	 * @param boolean TRUE to always fetch a fresh instance, FALSE to enable cache
	 *
	 * @see shared/anchor.php
	 */
	function load_by_id($id, $mutable=FALSE) {
		$this->item =& Sections::get($id, $mutable);
	}

	/**
	 * restore a previous version of this section
	 *
	 * @param array set of attributes to restore
	 * @return TRUE on success, FALSE otherwise
	 *
	 * @see versions/restore.php
	 */
	function restore($item) {
		global $context;

		// restore this instance
		$this->item = $item;

		// save updated state
		if($error = Sections::put($item)) {
			Skin::error($error);
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * remember the last action for this section
	 *
	 * @param string the description of the last action
	 * @param string the id of the item related to this update
	 * @param boolean TRUE for a silent update
	 *
	 * @see articles/article.php
	 * @see shared/anchor.php
	 */
	function touch($action, $origin, $silently = FALSE) {
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

		// append a reference to a new image to the description
		} elseif($action == 'image:create') {
			if(!preg_match('/\[image='.preg_quote($origin, '/').'.*?\]/', $this->item['description'])) {

				// list has already started
				if(preg_match('/\[image=[^\]]+?\]\s*$/', $this->item['description']))
					$query[] = "description = '".SQL::escape($this->item['description'].' [image='.$origin.']')."'";

				// starting a new list of images
				else
					$query[] = "description = '".SQL::escape($this->item['description']."\n\n".'[image='.$origin.']')."'";
			}

			// refresh stamp only if image update occurs within 6 hours after last edition
			if(SQL::strtotime($this->item['edit_date']) + 6*60*60 < time())
				$silently = TRUE;

		// add a reference to a new image at the top the description
		} elseif($action == 'image:insert') {
			$action = 'image:create';
			if($origin && !preg_match('/\[image='.$origin.'.*?\]/', $this->item['description']))
				$query[] = "description = '".SQL::escape('[image='.$origin.'] '.$this->item['description'])."'";

			// only refresh stamp if image update occurs within 6 hours after last edition
			if(SQL::strtotime($this->item['edit_date']) + 6*60*60 < time())
				$silently = TRUE;

		// suppress a reference to an image that has been deleted
		} elseif($action == 'image:delete') {

			// suppress reference in main description field
			if($origin && preg_match('/\[image='.$origin.'.*?\]/', $this->item['description']))
				$query[] = "description = '".SQL::escape(preg_replace('/\[image='.$origin.'.*?\]/', '', $this->item['description']))."'";

			// suppress references as icon and thumbnail as well
			include_once $context['path_to_root'].'images/images.php';
			if($image =& Images::get($origin)) {

				if($url = Images::get_icon_href($image)) {
					if($this->item['bullet_url'] == $url)
						$query[] = "bullet_url = ''";
					if($this->item['icon_url'] == $url)
						$query[] = "icon_url = ''";
					if($this->item['thumbnail_url'] == $url)
						$query[] = "thumbnail_url = ''";
				}

				if($url = Images::get_thumbnail_href($image)) {
					if($this->item['bullet_url'] == $url)
						$query[] = "bullet_url = ''";
					if($this->item['icon_url'] == $url)
						$query[] = "icon_url = ''";
					if($this->item['thumbnail_url'] == $url)
						$query[] = "thumbnail_url = ''";
				}
			}

		// set an existing image as the section bullet
		} elseif($action == 'image:set_as_bullet') {
			include_once $context['path_to_root'].'images/images.php';
			if($image =& Images::get($origin)) {
				if($url = Images::get_icon_href($image))
					$query[] = "bullet_url = '".SQL::escape($url)."'";
			}
			$silently = TRUE;

			// clear the cache for articles, because of the new bullet to be used in lists of articles
			Cache::clear('articles');

		// set an existing image as the section icon
		} elseif($action == 'image:set_as_icon') {
			include_once $context['path_to_root'].'images/images.php';
			if($image =& Images::get($origin)) {
				if($url = Images::get_icon_href($image))
					$query[] = "icon_url = '".SQL::escape($url)."'";
			}
			$silently = TRUE;

		// set an existing image as the section thumbnail
		} elseif($action == 'image:set_as_thumbnail') {
			include_once $context['path_to_root'].'images/images.php';
			if($image =& Images::get($origin)) {
				if($url = Images::get_thumbnail_href($image))
					$query[] = "thumbnail_url = '".SQL::escape($url)."'";
			}
			$silently = TRUE;

		// append a new image, and set it as the article thumbnail
		} elseif($action == 'image:set_as_both') {
			if(!preg_match('/\[image='.preg_quote($origin, '/').'.*?\]/', $this->item['description']))
				$query[] = "description = '".SQL::escape($this->item['description'].' [image='.$origin.']')."'";

			include_once $context['path_to_root'].'images/images.php';
			if($image =& Images::get($origin)) {
				if($url = Images::get_thumbnail_href($image))
					$query[] = "thumbnail_url = '".SQL::escape($url)."'";
			} elseif($origin) {
				$query[] = "thumbnail_url = '".SQL::escape($origin)."'";
			}

			// do not remember minor changes
			$silently = TRUE;

		// add a reference to a new table in the section description
		} elseif($action == 'table:create' || $action == 'table:update') {
			if(!preg_match('/\[table='.$origin.'\]/', $this->item['description']))
				$query[] = "description = '".SQL::escape($this->item['description'].' [table='.$origin.']')."'";

		// suppress a reference to a table that has been deleted
		} elseif($action == 'table:delete') {
			if($origin && preg_match('/\[table='.$origin.'\]/', $this->item['description']))
				$query[] = "description = '".SQL::escape(preg_replace('/\[table='.$origin.'\]/', '', $this->item['description']))."'";

		}

		// stamp the update
		if(!$silently)
			$query[] = "edit_name='".SQL::escape(Surfer::get_name())."',"
				."edit_id='".SQL::escape(Surfer::get_id())."',"
				."edit_address='".SQL::escape(Surfer::get_email_address())."',"
				."edit_action='$action',"
				."edit_date='".SQL::escape(gmstrftime('%Y-%m-%d %H:%M:%S'))."'";

		// update the database
		if(@count($query)) {
			$query = "UPDATE ".SQL::table_name('sections')." SET ".implode(', ',$query)
				." WHERE id = ".SQL::escape($this->item['id']);
			SQL::query($query);
		}

		// send alerts on new item
		if(preg_match('/:(create|insert)$/i', $action)) {

			// poster name, if applicable
			if(!$surfer = Surfer::get_name())
				$surfer = i18n::c('(anonymous)');

			// mail message to section watchers and to poster watchers
			$mail = array();
			$mail2 = array();

			// notification
			$notification = array();
			$notification['type'] = 'alert';
			$notification['action'] = $action;
			$notification['nick_name'] = Surfer::get_name();

			// an article has been added to the section
			if(strpos($action, 'article') === 0) {
				if((!$target = Articles::get($origin)) || !$target['id'])
					return;

				// message subjects
				$mail['subject'] = sprintf(i18n::c('%s: %s'), ucfirst(strip_tags($this->item['title'])), strip_tags($target['title']));
				$mail2['subject'] = sprintf(i18n::c('%s: %s'), ucfirst(strip_tags($this->item['title'])), strip_tags($target['title']));

				// message to section watcher
				$mail['message'] = sprintf(i18n::c('A page has been submitted by %s'), $surfer)
					."\n\n".ucfirst(strip_tags($target['title']))
					."\n".$context['url_to_home'].$context['url_to_root'].Articles::get_url($target['id'], 'view', $target['title'])
					."\n\n"
					.sprintf(i18n::c('This message has been generated automatically by %s since the new item has been posted in a web space that is part of your watch list. If you wish to stop these automatic alerts please visit the following section, or its parent sections, and click on the Forget link.'), $context['site_name'])
					."\n\n".ucfirst(strip_tags($this->item['title']))
					."\n".$context['url_to_home'].$context['url_to_root'].Sections::get_url($this->item['id'], 'view', $this->item['title'])
					."\n\n";

				// message to poster watcher
				if(Surfer::get_id()) {

					$mail2['message'] = sprintf(i18n::c('A page has been submitted by %s'), $surfer)
						."\n\n".ucfirst(strip_tags($target['title']))
						."\n".$context['url_to_home'].$context['url_to_root'].Articles::get_url($target['id'], 'view', $target['title'])
						."\n\n"
						.sprintf(i18n::c('This message has been generated automatically by %s since the new item has been posted by a user that is part of your watch list. If you wish to stop these automatic alerts please visit the following user profile and click on the Forget link.'), $context['site_name'])
						."\n\n".ucfirst(strip_tags(Surfer::get_name()))
						."\n".$context['url_to_home'].$context['url_to_root'].Users::get_url(Surfer::get_id(), 'view', Surfer::get_name())
						."\n\n";

				}

				// notification content
				$notification['address'] = $context['url_to_home'].$context['url_to_root'].Articles::get_url($target['id'], 'view', $target['title']);
				$notification['title'] = utf8::to_unicode($target['title']);

			// a file has been added to the section
			} else if(strpos($action, 'file') === 0) {
				include_once $context['path_to_root'].'files/files.php';
				if((!$target = Files::get($origin)) || !$target['id'])
					return;

				// file title
				if($target['title'])
					$title = $target['title'];
				else
					$title = $target['file_name'];

				// message subjects
				$mail['subject'] = sprintf(i18n::c('%s: %s'), ucfirst(strip_tags($this->item['title'])), strip_tags($title));
				$mail2['subject'] = sprintf(i18n::c('%s: %s'), ucfirst(strip_tags($this->item['title'])), strip_tags($title));

				// add poster name if applicable
				if(!$surfer = Surfer::get_name())
					$surfer = i18n::c('(anonymous)');

				// message to section watcher
				$mail['message'] = sprintf(i18n::c('A file has been uploaded by %s'), $surfer)
					."\n\n".ucfirst(strip_tags($title))
					."\n".$context['url_to_home'].$context['url_to_root'].Files::get_url($target['id'])
					."\n\n"
					.sprintf(i18n::c('This message has been generated automatically by %s since the new item has been posted in a web space that is part of your watch list. If you wish to stop these automatic alerts please visit the following section, or its parent sections, and click on the Forget link.'), $context['site_name'])
					."\n\n".ucfirst(strip_tags($this->item['title']))
					."\n".$context['url_to_home'].$context['url_to_root'].Sections::get_url($this->item['id'], 'view', $this->item['title'])
					."\n\n";

				// message to poster watcher
				if(Surfer::get_id()) {

					$mail2['message'] = sprintf(i18n::c('A file has been uploaded by %s'), $surfer)
						."\n\n".ucfirst(strip_tags($title))
						."\n".$context['url_to_home'].$context['url_to_root'].Files::get_url($target['id'])
						."\n\n"
						.sprintf(i18n::c('This message has been generated automatically by %s since the new item has been posted by a user that is part of your watch list. If you wish to stop these automatic alerts please visit the following user profile and click on the Forget link.'), $context['site_name'])
						."\n\n".ucfirst(strip_tags(Surfer::get_name()))
						."\n".$context['url_to_home'].$context['url_to_root'].Users::get_url(Surfer::get_id(), 'view', Surfer::get_name())
						."\n\n";

				}

				// notification content
				$notification['address'] = $context['url_to_home'].$context['url_to_root'].Files::get_url($target['id']);
				$notification['title'] = utf8::to_unicode($title);

			// a comment has been added to the section
			} else if(strpos($action, 'comment') === 0) {
				include_once $context['path_to_root'].'comments/comments.php';
				if((!$target = Comments::get($origin)) || !$target['id'])
					return;

				// add poster name if applicable
				if(!$surfer = Surfer::get_name())
					$surfer = i18n::c('(anonymous)');

				// message subjects
				$mail['subject'] = sprintf(i18n::c('%s has been commented by %s'), ucfirst(strip_tags($this->item['title'])), $surfer);
				$mail2['subject'] = sprintf(i18n::c('%s has been commented by %s'), ucfirst(strip_tags($this->item['title'])), $surfer);

				// message content
				$mail['message'] = i18n::c('Click on the following link to read the new comment')
					."\n\n".$context['url_to_home'].$context['url_to_root'].Comments::get_url($target['id'])
					."\n\n"
					.sprintf(i18n::c('This message has been generated automatically by %s since the new item has been posted in a web space that is part of your watch list. If you wish to stop these automatic alerts please visit the following section, or its parent sections, and click on the Forget link.'), $context['site_name'])
					."\n\n".ucfirst(strip_tags($this->item['title']))
					."\n".$context['url_to_home'].$context['url_to_root'].Sections::get_url($this->item['id'], 'view', $this->item['title'])
					."\n\n";

				// message to poster watcher
				if(Surfer::get_id()) {

					$mail2['message'] = i18n::c('Click on the following link to read the new comment')
						."\n\n".$context['url_to_home'].$context['url_to_root'].Comments::get_url($target['id'])
						."\n\n"
						.sprintf(i18n::c('This message has been generated automatically by %s since the new item has been posted by a user that is part of your watch list. If you wish to stop these automatic alerts please visit the following user profile and click on the Forget link.'), $context['site_name'])
						."\n\n".ucfirst(strip_tags(Surfer::get_name()))
						."\n".$context['url_to_home'].$context['url_to_root'].Users::get_url(Surfer::get_id(), 'view', Surfer::get_name())
						."\n\n";

				}

				// notification content
				$notification['address'] = $context['url_to_home'].$context['url_to_root'].Comments::get_url($target['id']);
				$notification['title'] = utf8::to_unicode($this->item['title']);

			// something else has been added to the section
			} else {

				// a label for the action
				$action_label = ucfirst(get_action_label($action));

				// add poster name if applicable
				if($surfer = Surfer::get_name())
					$action = sprintf(i18n::c('%s by %s'), $action_label, $surfer);
				else
					$action = $action_label;

				// section title
				$title = strip_tags($this->item['title']);

				// message titles
				$mail['subject'] = sprintf(i18n::c('%s in %s'), $action_label, $title);
				$mail2['subject'] = sprintf(i18n::c('%s in %s'), $action_label, $title);

				// message body
				$mail['message'] = i18n::c('A new item has been added to the following section').
					"\n\n".sprintf(i18n::c('%s in %s'), $action, $title)
					."\n".$context['url_to_home'].$context['url_to_root'].Sections::get_url($this->item['id'], 'view', $this->item['title'])
					."\n\n"
					.sprintf(i18n::c('This message has been generated automatically by %s since the new item has been posted in a web space that is part of your watch list. If you wish to stop these automatic alerts please visit the following section, or its parent sections, and click on the Forget link.'), $context['site_name'])
					."\n\n".$title
					."\n".$context['url_to_home'].$context['url_to_root'].Sections::get_url($this->item['id'], 'view', $this->item['title'])
					."\n\n";

				// message to poster watcher
				if(Surfer::get_id()) {

					$mail2['message'] = i18n::c('A new item has been added to the following section').
						"\n\n".sprintf(i18n::c('%s in %s'), $action, $title)
						."\n".$context['url_to_home'].$context['url_to_root'].Sections::get_url($this->item['id'], 'view', $this->item['title'])
						."\n\n"
						.sprintf(i18n::c('This message has been generated automatically by %s since the new item has been posted by a user that is part of your watch list. If you wish to stop these automatic alerts please visit the following user profile and click on the Forget link.'), $context['site_name'])
						."\n\n".ucfirst(strip_tags(Surfer::get_name()))
						."\n".$context['url_to_home'].$context['url_to_root'].Users::get_url(Surfer::get_id(), 'view', Surfer::get_name())
						."\n\n";

				}

				// notification content
				$notification['address'] = $context['url_to_home'].$context['url_to_root'].Sections::get_url($this->item['id'], 'view', $this->item['title']);
				$notification['title'] = utf8::to_unicode($title);

			}

			// look more precisely at this object
			$anchor = Anchors::get('section:'.$this->item['id']);

			// no watch in interactive threads
			if(is_object($anchor) && !$anchor->has_option('view_as_thread')) {

				// the path of containers to this item
				$containers = $anchor->get_focus();

				// autorized users
				$restricted = NULL;
				if(($anchor->get_active() == 'N') && ($editors = Members::list_anchors_for_member($containers))) {
					foreach($editors as $editor)
						if(strpos($editor, 'user:') === 0)
							$restricted[] = substr($editor, strlen('user:'));
				}

				// alert all watchers at once
				Users::alert_watchers($containers, $mail, $notification, $restricted);

			}

			// alert watchers of this poster
			if(Surfer::get_id())
				Users::alert_watchers('user:'.Surfer::get_id(), $mail2, $notification);

		}

		// always clear the cache, even on no update
		Cache::clear(array('sections', 'section:'.$this->item['id']));

		// get the parent
		if(!$this->anchor)
			$this->anchor = Anchors::get($this->item['anchor']);

		// propagate the touch upwards silently -- we only want to purge the cache
		if(is_object($this->anchor))
			$this->anchor->touch('article:edit', $this->item['id'], TRUE);

	}

	/**
	 * transcode some references
	 *
	 * @param array of pairs of strings to be used in preg_replace()
	 *
	 * @see images/images.php
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
		Cache::clear(array('sections', 'section:'.$this->item['id']));

	}

}
?>