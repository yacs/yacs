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
	 * allow or block operations
	 *
	 * @param string the kind of item to handle
	 * @param string the foreseen operation ('edit', 'new', ...)
	 * @return TRUE if the operation is accepted, FALSE otherwise
	 */
	function allows($type, $action) {
		global $context;

		// cache the overlay, if any
		include_once $context['path_to_root'].'overlays/overlay.php';
		if(!isset($this->overlay) && isset($this->item['overlay']))
			$this->overlay = Overlay::load($this->item, 'article:'.$this->item['id']);

		// delegate the validation to the overlay
		if(isset($this->overlay) && is_object($this->overlay) && is_callable(array($this->overlay, 'allows')))
			return $this->overlay->allows($type, $action);

		// allowed
		return TRUE;
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
			$this->anchor =& Anchors::get($this->item['anchor']);

		// the parent level
		if(is_object($this->anchor))
			$focus = $this->anchor->get_focus();
		else
			$focus = array();

		// append this level
		if(isset($this->item['id']))
			$focus[] = 'article:'.$this->item['id'];

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
		if(isset($this->item['icon_url']) && $this->item['icon_url'])
			return $this->item['icon_url'];
		return $this->get_thumbnail_url();
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
	 * get the path bar for this anchor
	 *
	 * For articles, the path bar is made of one stem for the section, then one stem for the article itself.
	 *
	 * @see shared/anchor.php
	 *
	 * @return an array of $url => $label, or NULL
	 */
	function get_path_bar() {
		global $context;

		// no item bound
		if(!isset($this->item['id']))
			return NULL;

		// get the parent
		if(!isset($this->anchor))
			$this->anchor =& Anchors::get($this->item['anchor']);

		// the parent level
		$parent = array();
		if(is_object($this->anchor))
			$parent = $this->anchor->get_path_bar();

		// this item
		$url = $this->get_url();
		$label = $this->get_title();
		$data = array_merge($parent, array($url => $label));

		// return the result
		return $data;

	}

	/**
	 * get the reference for this anchor
	 *
	 * This function is used to retrieve a reference to be placed into the database.
	 * For example:
	 * [php]
	 * $anchor =& Anchors::get($article['anchor']);
	 * $context['text'] .= '<input type="hidden" name="anchor" value="'.$anchor->get_reference().'" />';
	 * [/php]
	 *
	 * @see shared/anchor.php
	 *
	 * @return 'article:&lt;id&gt;', or NULL on error
	 */
	function get_reference() {
		if(isset($this->item['id']))
			return 'article:'.$this->item['id'];
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
			return 'a~'.reduce_number($this->item['id']);;
		return NULL;
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
			include_once $context['path_to_root'].'overlays/overlay.php';
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
	 * get the url to display the thumbnail for this anchor
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
			if($this->has_option('view_as_tabs'))
				return $this->get_url().'#_discussion';

			if($this->has_option('view_as_chat'))
				return $this->get_url().'#comments';

			// start threads on a separate page
			if($this->has_layout('alistapart'))
				return Comments::get_url($this->get_reference(), 'list');

			// layouts that start at the article page
			return Articles::get_permalink($this->item).'#comments';

		// list of files
		case 'files':
			if($this->has_option('view_as_tabs'))
				return $this->get_url().'#_attachments';
			return Articles::get_permalink($this->item).'#files';

		// list of links
		case 'links':
			if($this->has_option('view_as_tabs'))
				return $this->get_url().'#_attachments';
			return Articles::get_permalink($this->item).'#links';

		// jump to parent page
		case 'parent':
			if(!isset($this->anchor))
				$this->anchor =& Anchors::get($this->item['anchor']);

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
	 * check that the surfer is an editor of an article
	 *
	 * An anonymous surfer is considered as an editor if he has provided the secret handle.
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
		if(!$user_id && Surfer::get_id())
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

		// article has been assigned to this surfer
		if($user_id && Members::check('user:'.$user_id, 'article:'.$this->item['id']))
			return $this->is_assigned_cache[$user_id] = TRUE;

		// anonymous edition is allowed
		if(($this->item['active'] == 'Y') && $this->has_option('anonymous_edit'))
			return $this->is_assigned_cache[$user_id] = TRUE;

		// members edition is allowed
		if(($this->item['active'] == 'Y') && Surfer::is_empowered('M') && $this->has_option('members_edit'))
			return $this->is_assigned_cache[$user_id] = TRUE;

		// container may be edited
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
		$this->item['description'] = trim(preg_replace_callback('/<(.*?)>(.*?)<\/\\1>/is', array(&$this, 'parse_match'),  $text))."\n\n";

		// text contains an implicit anchor to an article or to a section
// 		if(preg_match('/##(article|section):([^#]+?)##/', $text, $matches) && ($anchor =& Anchors::get($matches[1].':'.$matches[2])))
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
	 * Moreover, on any change that impact the edition date (i.e., not in silent mode),
	 * a message is sent to the article creator, if different from the current surfer
	 * and a message is sent to watchers as well.
	 *
	 * @see articles/article.php
	 * @see articles/edit.php
	 * @see shared/anchor.php
	 *
	 * @param string one of the pre-defined action code
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

		// sanity check
		if(!$origin) {
			logger::remember('articles/article.php', 'unexpected NULL origin at touch()');
			return;
		}

		// components of the query
		$query = array();

		// a new comment has been posted
		if($action == 'comment:create') {

			// purge oldest comments
			include_once $context['path_to_root'].'comments/comments.php';
			Comments::purge_for_anchor('article:'.$this->item['id']);

		// a new file has been attached
		} elseif(($action == 'file:create')) {

			// identify specific files
			$label = '';
			if(!Codes::check_embedded($this->item['description'], 'embed', $origin) && ($item = Files::get($origin))) {

				// give it to the Flash player
				if(isset($item['file_name']) && Files::is_embeddable($item['file_name']))
					$label = '[embed='.$origin.']';

			}

			// we are in some interactive thread
			if($this->has_option('view_as_chat')) {

				// default is to download the file
				if(!$label)
					$label = '[download='.$origin.']';

				// this is the first contribution to the thread
				include_once $context['path_to_root'].'comments/comments.php';
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

				// actual creation in the database, but silently
				Comments::post($fields);

			// include flash videos in a regular page
			} elseif($label)
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
					$this->item['description'] .= ' [image='.$origin.']';

				// starting a new list of images
				else
					$this->item['description'] .= "\n\n".'[image='.$origin.']';

				$query[] = "description = '".SQL::escape($this->item['description'])."'";
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

		// set an existing image as the article icon
		} elseif($action == 'image:set_as_icon') {
			include_once $context['path_to_root'].'images/images.php';
			if($image = Images::get($origin)) {
				if($url = Images::get_icon_href($image))
					$query[] = "icon_url = '".SQL::escape($url)."'";

				// also use it as thumnail if none has been defined yet
				if(!(isset($this->item['thumbnail_url']) && trim($this->item['thumbnail_url'])) && ($url = Images::get_thumbnail_href($image)))
					$query[] = "thumbnail_url = '".SQL::escape($url)."'";

			}

		// set an existing image as the article thumbnail
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

			// do not remember minor changes
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
			} elseif($origin) {
				$query[] = "thumbnail_url = '".SQL::escape($origin)."'";
			}

			// do not remember minor changes
			$silently = TRUE;

		// add a reference to a location in the article description
		} elseif($action == 'location:create') {
			if(!Codes::check_embedded($this->item['description'], 'location', $origin))
				$query[] = "description = '".SQL::escape($this->item['description'].' [location='.$origin.']')."'";

		// suppress a reference to a location that has been deleted
		} elseif($action == 'location:delete') {
			$query[] = "description = '".SQL::escape(Codes::delete_embedded($this->item['description'], 'location', $origin))."'";

		// add a reference to a new table in the article description
		} elseif($action == 'table:create') {
			if(!Codes::check_embedded($this->item['description'], 'table', $origin))
				$query[] = "description = '".SQL::escape($this->item['description']."\n".'[table='.$origin.']'."\n")."'";

		// suppress a reference to a table that has been deleted
		} elseif($action == 'table:delete') {
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

		// load the anchor, if any
		$anchor = NULL;
		if(isset($this->item['anchor']) && $this->item['anchor'])
			$anchor =& Anchors::get($this->item['anchor']);

		// do not signal the change to watchers if the page is on draft mode
		if(!isset($this->item['publish_date']) ||  ($this->item['publish_date'] <= NULL_DATE)) {
			$to_watchers = FALSE;
			$to_followers = FALSE;
		}

		// do not forward this to followers if the page is private
		if($this->item['active'] == 'N')
			$to_followers = FALSE;

		// send alert only on new stuff
		if(preg_match('/:create$/i', $action)) {

			// poster name
			$surfer = Surfer::get_name();

			// mail message
			$mail = array();

			// mail subject
			$mail['subject'] = sprintf(i18n::c('%s: %s'), i18n::c('Contribution'), strip_tags($this->item['title']));

			// nothing done yet
			$summary = $title = $link = '';

			// a file has been added to the page
			if($action == 'file:create') {
				if(($target = Files::get($origin)) && $target['id']) {

					// file title
					if($target['title'])
						$title = $target['title'];
					else
						$title = $target['file_name'];

					// message components
					$summary = sprintf(i18n::c('A file has been uploaded by %s'), $surfer);
					if($description = trim($item['description']))
						$summary .= '<p> </p><div>'.$description.'</div>';
					$link = $context['url_to_home'].$context['url_to_root'].Files::get_permalink($target);

					// threads messages
					$mail['headers'] = Mailer::set_thread('file:'.$target['id'], $this->get_reference());

				}

			// a comment has been added to the page
			} else if($action == 'comment:create') {
				include_once $context['path_to_root'].'comments/comments.php';
				if(($target = Comments::get($origin)) && $target['id']) {

					// title with link to the commented page
					$page_title_link = '<a href="'.$context['url_to_home']
					    .$context['url_to_root']
					    .Articles::get_permalink($this->item)
					    .'">'.$this->item['title'].'</a>';

					// insert the full content of the comment, to provide the full information
					$summary = '<p>'.sprintf(i18n::c('%s has contributed to %s'), $surfer, $page_title_link).'</p>'
						.'<div style="margin: 1em 0;">'.Codes::beautify($target['description']).'</div>';

					// offer to react to the comment
					$title = i18n::s('Reply');
					$link = $context['url_to_home'].$context['url_to_root'].Comments::get_url($target['id'], 'reply');

					// threads messages
					$mail['headers'] = Mailer::set_thread('comment:'.$target['id'], $this->get_reference());

				}

			// something else has been added to the page
			} else {

				// add poster name if applicable
				$summary = sprintf(i18n::c('%s by %s'), Anchors::get_action_label($action), Surfer::get_name());

				// message components
				$title = sprintf(i18n::c('%s in %s'), ucfirst($action), strip_tags($this->item['title']));
				$link = $context['url_to_home'].$context['url_to_root'].Articles::get_permalink($this->item);

				// threads messages
				$mail['headers'] = Mailer::set_thread('', $this->get_reference());

			}

			// message to watchers
			$mail['message'] =& Mailer::build_notification($summary, $title, $link, 1);

			// we only have mail address of page creator
			if(!$this->item['create_id'] && $this->item['create_address'])
				Mailer::notify(Surfer::from(), $this->item['create_address'], $mail['subject'], $mail['message'], isset($mail['headers'])?$mail['headers']:'');

			// alert watchers
			if($to_watchers)
				Users::alert_watchers('article:'.$this->item['id'], $mail);

			// alert connexions, except on private pages
			if(Surfer::get_id() && $to_followers) {

				// message to connexions
				$mail['message'] =& Mailer::build_notification($summary, $title, $link, 2);

				// alert connexions
				Users::alert_watchers('user:'.Surfer::get_id(), $mail);
			}
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
			$this->anchor =& Anchors::get($this->item['anchor']);

		// propagate the touch upwards
		if(is_object($this->anchor)) {

			// notify the full contribution to section watcher
			if($action == 'comment:create')
				$action = 'article:comment';

			// default case
			else
				$action = 'article:update';

			$this->anchor->touch($action, $this->item['id'], TRUE, $to_watchers);
		}

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
