<?php
/**
 * the article anchor
 *
 * This class implements the Anchor interface for published articles.
 *
 * @see shared/anchor.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Alexis Raimbault
 * @tester Guillaume Perez
 * @tester Dobliu
 * @tester Christian Loubechine
 * @tester Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Article extends Anchor {
	
	
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
	    
	    // files
	    if($filter == 'all' || preg_match('/\bfiles?\b/i', $filter)) {
		$childs['file'] = Files::list_by_title_for_anchor($this->get_reference(), $offset, $max, $layout);
	    }			    
	    
	    return $childs;
	 }

	/**
	 * get next and previous items, if any
	 *
	 * @see shared/anchor.php
	 *
	 * @param string the item type (eg, 'image', 'file', etc.)
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

		// previous and next comments
		if($type == 'comment') {

			// load the adequate library
			include_once $context['path_to_root'].'comments/comments.php';

			$order = 'date';

			// get previous url
			if($previous_url = Comments::get_previous_url($item, 'article:'.$this->item['id'], $order))
				$previous_label = i18n::s('Previous');

			// get next url
			if($next_url = Comments::get_next_url($item, 'article:'.$this->item['id'], $order))
				$next_label = i18n::s('Next');

		// previous and next files
		} elseif($type == 'file') {

			// select appropriate order
			if(preg_match('/\bfiles_by_title\b/', $this->item['options']))
				$order = 'title';
			else
				$order = 'date';

			// get previous url
			if($previous_url = Files::get_previous_url($item, 'article:'.$this->item['id'], $order))
				$previous_label = i18n::s('Previous');

			// get next url
			if($next_url = Files::get_next_url($item, 'article:'.$this->item['id'], $order))
				$next_label = i18n::s('Next');

		// previous and next images
		} elseif($type == 'image') {

			// load the adequate library
			include_once $context['path_to_root'].'images/images.php';

			// extract all images references from the introduction and from the description
			preg_match_all('/\[image=(\d+)/', $this->item['introduction'].$this->item['description'], $matches);

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

			// extract all location references from the introduction and from the description
			preg_match_all('/\[location=(\d+)/', $this->item['introduction'].$this->item['description'], $matches);

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
	    
	    $link = Articles::get_permalink($this->item);
	    return $link;
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
			return 'a~'.reduce_number($this->item['id']);;
		return NULL;
	}
	
	/**
	 * provide classe name with all static functions on this kind of anchor
	 * 
	 * @return a class name
	 */
	function get_static_group_class() {
	    return 'Articles';
	}

	/**
	 * get some introductory text from an article
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
	 * - 'quote' - strip most HTML tags
	 * - 'teaser' - limit the number of words, tranform YACS codes, and link to permalink
	 *
	 * @see shared/anchor.php
	 *
	 * @param string an optional variant, including
	 * @return NULL, of some text
	 */
	function &get_teaser($variant = 'basic') {
		global $context;

		// no item bound
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
					$text = Codes::beautify($text, $this->item['options']);

			}

			// combine with description
			if($variant == 'quote')
				$text .= BR.BR;

		}

		// use overlay data, if any
		if(!$text) {
			if(!isset($this->overlay) && isset($this->item['overlay']))
				$this->overlay = Overlay::load($this->item, 'article:'.$this->item['id']);
			if(is_object($this->overlay))
				$text .= $this->overlay->get_text('list', $this->item);
		}

		// use the description field, if any
		$in_description = FALSE;
		if(!$text && ($variant != 'hover')) {
			$text .= trim($this->item['description']);
			$in_description = TRUE;

			// remove toc and toq codes
			$text = preg_replace(FORBIDDEN_IN_TEASERS, '', $text);

			// render all codes
			if(($variant == 'teaser') && is_callable(array('Codes', 'beautify')))
				$text = Codes::beautify($text, $this->item['options']);

		}

		// turn html entities to unicode entities
		$text = utf8::transcode($text);

		// now we have to process the provided text
		switch($variant) {

		// strip everything
		case 'basic':
		default:

			// strip every HTML and limit the size
			if(is_callable(array('Skin', 'strip')))
				$text = Skin::strip($text, 70, NULL, '');

			// done
			return $text;

		// some text for pop-up panels
		case 'hover':

			// strip every HTML and limit the size
			if(is_callable(array('Skin', 'strip')))
				$text = Skin::strip($text, 70, NULL, '');

			// ensure we have some text
			if(!$text)
				$text = i18n::s('View the page');

			// mention shortcut to article
			if(Surfer::is_associate())
				$text .= ' [article='.$this->item['id'].']';

			// done
			return $text;

		// quote this
		case 'quote':

			// strip every HTML and limit the size
			if(is_callable(array('Skin', 'strip')))
				$text = Skin::strip($text, 300, NULL, '<a><b><br><i><img><strong><u>');

			// done
			return $text;

		// preserve as much as possible
		case 'teaser':

			// lower level of titles
 			$text = str_replace(array('<h4', '</h4'), array('<h5', '</h5'), $text);
 			$text = str_replace(array('<h3', '</h3'), array('<h4', '</h4'), $text);
 			$text = str_replace(array('<h2', '</h2'), array('<h3', '</h3'), $text);

			// limit the number of words
			if(is_callable(array('Skin', 'cap')))
				$text = Skin::cap($text, WORDS_IN_TEASER, $this->get_url());

			// done
			return $text;

		}

	}

	/**
	 * get the url to display the main page for this anchor
	 *
	 * @see shared/anchor.php
	 *
	 * @param string the targeted action ('view', 'print', 'edit', 'delete', ...)
	 * @return an anchor to the viewing script, or NULL on error
	 */
	function get_url($action='view') {

		// sanity check
		if(!isset($this->item['id']))
			return NULL;

		switch($action) {

		// view comments
		case 'comments':
			// variants that start at the article page
			if($this->has_option('view_as_chat'))
				return $this->get_url().'#comments';

			// start threads on a separate page
			if($this->has_layout('alistapart'))
				return Comments::get_url($this->get_reference(), 'list');

			// layouts that start at the article page --assume we have at least one comment, on a tab
			return Articles::get_permalink($this->item).'#_discussion';

		// list of files
		case 'files':
			return $this->get_url().'#_attachments';

		// list of links
		case 'links':
			return $this->get_url().'#_attachments';

		// jump to parent page
		case 'parent':
			if(!isset($this->anchor))
				$this->anchor = Anchors::get($this->item['anchor']);

			return $this->anchor->get_url();

		// the permalink page
		case 'view':
			return Articles::get_permalink($this->item);

		// another action
		default:
			return Articles::get_url($this->item['id'], $action, $this->item['title'], $this->item['nick_name']);

		}

	}

	/**
	 * list all items in the watching context
	 *
	 * For articles, the watching context is limited by default to the page itself,
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

		// notifications should be sent to watchers of these containers
		$containers = array();

		// i am a container
		$containers[] = $this->get_reference();

		// if the page has been published
		if($this->item['publish_date'] > NULL_DATE) {

			// look at my parents
			$handle = $this->get_parent();
			while($handle && ($container = Anchors::get($handle))) {

				// add watchers of this level
				$containers[] = $handle;

				// should we forward notifications upwards
				if(($action != 'article:publish') && !$container->has_option('forward_notifications', FALSE))
					break;

				// add watchers of next level
				$handle = $container->get_parent();
			}

		}

		// by default, limit to direct watchers of this anchor
		return $containers;
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
		$this->item = Articles::get($id, $mutable);
	}

	/**
	 * parse one article from a piece of text
	 *
	 * This script extracts following tags from the submitted piece of text:
	 * - anchor - the complete anchor for this post (e.g. 'article:123')
	 * - author - nick name, id or e-mail address of the poster (if distinct from e-mail poster)
	 * - blogid or section - nick name or id of the target section for the post (e.g., 'my_blog')
	 * - tags (or categories) - a list of comma-separated topics to be linked to this post
	 * - introduction - some introductory text for this post
	 * - source - a web reference to original material, if any
	 * - title - to override the Subject: field of the message
	 *
	 * Sample usage:
	 * [php]
	 * // parse article content
	 * include_once $context['path_to_root'].'articles/article.php';
	 * $article = new Article();
	 * $fields = $article->parse($content['description'], $item);
	 * [/php]
	 *
	 * @see agents/messages.php
	 $ @see agents/uploads.php
	 * @see services/blog.php
	 *
	 * @param string the input text
	 * @param array previous attributes for the page
	 * @return an array of updated attributes
	 */
	function parse($text, $item = NULL) {
		global $context;

		// initialize values, if any
		if(!is_string($text))
			$text = '';

		if(is_array($item))
			$this->item = $item;

		// improve the job done by Microsoft Live Writer, and close img tags
		preg_replace('|<img (.+?[^/])>|mi', '<img $1 />', $text);

		// separate headers from body, if any
		$items = explode(CRLF.CRLF, $text, 2);
		$headers = $items[0];
		if(isset($items[1]))
			$body = "\n\n".$items[1];
		else
			$body = '';

		// parse simple fields in headers
		$headers = trim(preg_replace_callback('/^(\w+?):\s*(.*?)$/im', array(&$this, 'parse_match'),  $headers))."\n\n";

		// rebuild a complete message
		$text = $headers.$body;

		// parse embedded fields based on XML tags
		$this->item['description'] = trim(preg_replace_callback('/<(.*?)>(.*?)<\/$1>/is', array(&$this, 'parse_match'),  $text))."\n\n";

		// text contains an implicit anchor to an article or to a section
// 		if(preg_match('/##(article|section):([^#]+?)##/', $text, $matches) && ($anchor = Anchors::get($matches[1].':'.$matches[2])))
// 			$this->item['anchor'] = $anchor->get_reference();

		return $this->item;

	}

	/**
	 * called from within a preg_replace_callback() in Article::parse()
	 *
	 */
	function parse_match($matches) {
		global $context;

		// useful if they are a lot of tags to process
		Safe::set_time_limit(30);

		switch($matches[1]) {

		case 'anchor':
			$this->item['anchor'] = $matches[2];
			break;

		case 'author':
			if($user = Users::get($matches[2])) {
				$this->item['create_name'] = $user['nick_name'];
				$this->item['create_id'] = $user['id'];
				$this->item['create_address'] = $user['email'];
				$this->item['publish_name'] = $user['nick_name'];
				$this->item['publish_id'] = $user['id'];
				$this->item['publish_address'] = $user['email'];
				$this->item['edit_name'] = $user['nick_name'];
				$this->item['edit_id'] = $user['id'];
				$this->item['edit_address'] = $user['email'];
			}
			break;

		case 'blogid':
		case 'section':
			if($section = Sections::get($matches[2]))
				$this->item['anchor'] = 'section:'.$section['id'];
			break;

		case 'introduction':
			if(isset($this->item['introduction']))
				$this->item['introduction'] .= $matches[2].' ';
			else
				$this->item['introduction'] = $matches[2].' ';
			break;

		case 'source':
			$this->item['source'] = $matches[2];
			break;

		case 'tags':		// web form
		case 'category':	// xml-rpc
		case 'categories':	// legacy
			if(isset($this->item['tags']))
				$this->item['tags'] .= $matches[2].' ';
			else
				$this->item['tags'] = $matches[2].' ';
			break;

		case 'title':
			if(isset($this->item['title']))
				$this->item['title'] .= $matches[2].' ';
			else
				$this->item['title'] = $matches[2].' ';
			break;

		default:
			return $matches[0];
		}

		return '';
	}

	/**
	 * restore a previous version of this article
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
		return Articles::put($item);
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
		return Articles::put_attributes($fields);

	}

	/**
	 * remember the last action for this article
	 *
	 * This function is called by related items. What does it do?
	 * - On image creation, the adequate code is added to the description field to let the image be displayed inline
	 * - On icon selection, the icon field is updated
	 * - On thumbnail image selection, the thumbnail image field is updated
	 * - On location creation, some code is inserted in the description field to display location name inline
	 * - On table creation, some code is inserted in the description field to display the table inline
	 *
	 * @see articles/article.php
	 * @see articles/edit.php
	 * @see shared/anchor.php
	 *
	 * @param string one of the pre-defined action code
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

		// no article bound
		if(!isset($this->item['id']))
			return;

		// clear floating objects
		if($action == 'clear') {
			$this->item['description'] .= ' [clear]';
			$query = "UPDATE ".SQL::table_name('articles')." SET description='".SQL::escape($this->item['description'])."'"
				." WHERE id = ".SQL::escape($this->item['id']);
			SQL::query($query);

			return;
		}

		// get the related overlay, if any
		if(!isset($this->overlay)) {
			$this->overlay = NULL;
			if(isset($this->item['overlay']))
				$this->overlay = Overlay::load($this->item, 'article:'.$this->item['id']);
		}

		// components of the query
		$query = array();

		// a new comment has been posted
		if($action == 'comment:create') {

			// purge oldest comments
			Comments::purge_for_anchor('article:'.$this->item['id']);

		// file upload
		} elseif(($action == 'file:create') || ($action == 'file:upload')) {

			// actually, several files have been added
			$label = '';
			if(!$origin) {

				// only when comments are allowed
				if(!Articles::has_option('no_comments', $this->anchor, $this->item)) {

					// remember this as an automatic notification
					$fields = array();
					$fields['anchor'] = 'article:'.$this->item['id'];
					$fields['description'] = i18n::s('Several files have been added');
					$fields['type'] = 'notification';
					Comments::post($fields);

				}

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
				} else {

					// only when comments are allowed
					if(!Articles::has_option('no_comments', $this->anchor, $this->item)) {

						// remember this as an automatic notification
						$fields = array();
						$fields['anchor'] = 'article:'.$this->item['id'];
						if($action == 'file:create')
							$fields['description'] = '[file='.$item['id'].','.$item['file_name'].']';
						else
							$fields['description'] = '[download='.$item['id'].','.$item['file_name'].']';
						Comments::post($fields);

					}

				}

			}

			// we are in some interactive thread
			if($origin && $this->has_option('view_as_chat')) {

				// default is to download the file
				if(!$label)
					$label = '[download='.$origin.']';

				// this is the first contribution to the thread
				if(!$comment = Comments::get_newest_for_anchor('article:'.$this->item['id'])) {
					$fields = array();
					$fields['anchor'] = 'article:'.$this->item['id'];
					$fields['description'] = $label;

				// this is a continuated contribution from this authenticated surfer
				} elseif(($comment['type'] != 'notification') && Surfer::get_id() && (isset($comment['create_id']) && (Surfer::get_id() == $comment['create_id']))) {
					$comment['description'] .= BR.$label;
					$fields = $comment;

				// else process the contribution as a new comment
				} else {
					$fields = array();
					$fields['anchor'] = 'article:'.$this->item['id'];
					$fields['description'] = $label;

				}

				// only when comments are allowed
				if(!Articles::has_option('no_comments', $this->anchor, $this->item))
					Comments::post($fields);

			// include flash videos in a regular page
			} elseif($origin && $label)
				$query[] = "description = '".SQL::escape($this->item['description'].' '.$label)."'";


		// suppress references to a deleted file
		} elseif(($action == 'file:delete') && $origin) {

			// suppress reference in main description field
			$text = Codes::delete_embedded($this->item['description'], 'download', $origin);
			$text = Codes::delete_embedded($text, 'embed', $origin);
			$text = Codes::delete_embedded($text, 'file', $origin);

			// save changes
			$query[] = "description = '".SQL::escape($text)."'";

		// append a reference to a new image to the description
		} elseif(($action == 'image:create') && $origin) {
			if(!Codes::check_embedded($this->item['description'], 'image', $origin)) {
			    
				// the overlay may prevent embedding
				if(is_object($this->overlay) && !$this->overlay->should_embed_files())
						;

				else {
				    // list has already started
				    if(preg_match('/\[image=[^\]]+?\]\s*$/', $this->item['description']))
					    $this->item['description'] .= ' [image='.$origin.']';

				    // starting a new list of images
				    else
					    $this->item['description'] .= "\n\n".'[image='.$origin.']';

				    $query[] = "description = '".SQL::escape($this->item['description'])."'";
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
		} elseif(($action == 'image:delete') && $origin) {

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

		// set an existing image as the article icon
		} elseif(($action == 'image:set_as_icon') && $origin) {
			include_once $context['path_to_root'].'images/images.php';
			if($image = Images::get($origin)) {
				if($url = Images::get_icon_href($image))
					$query[] = "icon_url = '".SQL::escape($url)."'";

				// also use it as thumnail if none has been defined yet
				if(!(isset($this->item['thumbnail_url']) && trim($this->item['thumbnail_url'])) && ($url = Images::get_thumbnail_href($image)))
					$query[] = "thumbnail_url = '".SQL::escape($url)."'";

			}

		// set an existing image as the article thumbnail
		} elseif(($action == 'image:set_as_thumbnail') && $origin) {
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

		// append a new image, and set it as the article thumbnail
		} elseif(($action == 'image:set_as_both') && $origin) {
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
			} elseif($origin) {
				$query[] = "thumbnail_url = '".SQL::escape($origin)."'";
			}

			// do not remember minor changes
			$silently = TRUE;

		// add a reference to a location in the article description
		} elseif(($action == 'location:create') && $origin) {
			if(!Codes::check_embedded($this->item['description'], 'location', $origin))
				$query[] = "description = '".SQL::escape($this->item['description'].' [location='.$origin.']')."'";

		// suppress a reference to a location that has been deleted
		} elseif(($action == 'location:delete') && $origin) {
			$query[] = "description = '".SQL::escape(Codes::delete_embedded($this->item['description'], 'location', $origin))."'";

		// add a reference to a new table in the article description
		} elseif(($action == 'table:create') && $origin) {
			if(!Codes::check_embedded($this->item['description'], 'table', $origin))
				$query[] = "description = '".SQL::escape($this->item['description']."\n".'[table='.$origin.']'."\n")."'";

		// suppress a reference to a table that has been deleted
		} elseif(($action == 'table:delete') && $origin) {
			$query[] = "description = '".SQL::escape(Codes::delete_embedded($this->item['description'], 'table', $origin))."'";

		}

		// stamp the update
		if(!$silently)
			$query[] = "edit_name='".SQL::escape(Surfer::get_name())."',"
				."edit_id=".SQL::escape(Surfer::get_id()).","
				."edit_address='".SQL::escape(Surfer::get_email_address())."',"
				."edit_action='".SQL::escape($action)."',"
				."edit_date='".gmstrftime('%Y-%m-%d %H:%M:%S')."'";

		// update the database
		if(count($query)) {
			$query = "UPDATE ".SQL::table_name('articles')." SET ".implode(', ',$query)
				." WHERE id = ".SQL::escape($this->item['id']);
			SQL::query($query);
		}

		// add this page to the watch list of the contributor, on any action
		if(Surfer::get_id())
			Members::assign('article:'.$this->item['id'], 'user:'.Surfer::get_id());

		// surfer is visiting this page
		Surfer::is_visiting($this->get_url(), $this->get_title(), 'article:'.$this->item['id'], $this->item['active']);

		// always clear the cache, even on no update
		Articles::clear($this->item);

		// get the parent
		if(!$this->anchor)
			$this->anchor = Anchors::get($this->item['anchor']);

		// propagate the touch upwards
		if(is_object($this->anchor))
			$this->anchor->touch('article:update', $this->item['id'], TRUE);

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

		// always clear the cache
		Articles::clear($this->item);

	}

}

// stop hackers
defined('YACS') or exit('Script must be included');

// load localized strings
if(is_callable(array('i18n', 'bind')))
	i18n::bind('articles');

?>
