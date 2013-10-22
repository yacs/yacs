<?php
/**
 * the anchor interface used by a related item
 *
 * Anchors are used throughout this server to loosely link related items of information.
 * For example, articles can be linked to sections. Threads of messages can be linked
 * to articles, sections or categories, etc.
 *
 * An anchor is simply encoded as a type followed by an id. For example, putting
 * 'section:23' in the anchor field of an article means that this article
 * is tied to the section with the id 23.
 *
 * To retrieve items related to one anchor, you will have to query the database with
 * specific SELECT statement. Usually, well-written scripts will provide a function
 * to do that.
 *
 * For example, in a page showing the section content, you will retrieve
 * the first 30 articles related to this section with following code:
 * [php]
 * $rows = Articles::select_related_by_title('section:'.$id, 0, 30);
 * $context['text'] .= Skin::build_list($rows, 'compact');
 * [/php]
 *
 * To retrieve anchor information linked to one item, things are a little bit
 * more complicated. You would like to link threads to sections
 * (to build a bulletin board), to articles (to let people react on published pages)
 * or to categories (to build dedicated bulletin boards). But at the same time,
 * you would like to have a consistent interface to retrieve information from
 * sections, articles, or categories. The Anchor class defined here is aiming to define a standard
 * interface to related items that want to display context information coming from
 * various sources.
 *
 * Webmasters can expand the Anchors::get() function defined in shared/anchors.php to
 * ease the integration of new anchors in their system.
 *
 * The Anchor class defines following member functions that can be used in scripts related
 * to linked items:
 * - ceil_rights() -- maximize actual rights to some item
 * - check() -- retrieve date of last modification
 * - diff() -- visualize diffeences for some attribute
 * - get_active() -- get the active attribute
 * - get_behaviors() -- to build the stack of all behaviors
 * - get_focus() -- to retrieve the contextual path in content tree
 * - get_handle() -- to control access to protected resources
 * - get_icon_url() -- to reuse icons set for anchors in sub-items
 * - get_label() -- to adapt attached items to anchor's context
 * - get_neighbours() -- to locate next and previous items, if any
 * - get_nick_name() -- to get anchor nick name, if any
 * - get_overlay() -- to apply a default overlay option
 * - get_parent() -- to climb the anchoring chain
 * - get_path_bar() -- to build a path bar linked to the anchor
 * - get_poster() -- to retrieve the original poster of the anchor
 * - get_prefix() -- to insert text from the anchor, if any
 * - get_reference() -- to put 'article:12' or 'section:45' in the database
 * - get_suffix() -- to insert additional text from the anchor, if any
 * - get_teaser() -- to put comments in context
 * - get_templates_for() -- to get a list of models, if any
 * - get_thumbnail_url() -- to reuse thumbnails set for anchors in sub-items
 * - get_title() -- to reuse the anchor title
 * - get_url() -- to get the address of the viewing page for the anchor
 * - get_value() -- to fetch the value of one attribute
 * - has_layout() -- to enforce layout settings where applicable
 * - has_option() -- to cascade options from an anchor to related items
 * - has_value() -- check the value of some attribute
 * - is_assigned() -- to check explicit assignments for the current surfer
 * - is_public() -- to prevent actions on restricted sections
 * - is_viewable() -- to check visibility to surfer
 * - load_by_content() -- a trick to turn an array to an object
 * - load_by_id() -- re-build an instance in memory
 * - restore() -- to reverse to a previous version of the anchor
 * - touch() -- to reflect changes in the anchor
 * - transcode() -- to be used on duplication, etc.
 *
 * For example, suppose you are in a page that displays the full content
 * of one thread of messages. If this thread is linked to an article, you would
 * like to embed in your page useful information coming from this article.
 *
 * [php]
 * // get an anchor (an article, a section, etc.)
 * $anchor = Anchors::get('article:123');
 *
 * // show the path bar
 * $context['path_bar'] = array_merge($context['path_bar'], $anchor->get_path_bar());
 *
 * // use the title of the anchor as the title for this page
 * $context['page_title'] = $anchor->get_title();
 *
 * // link this page to the anchor
 * $context['text'] .= '<a href="'.$anchor->get_url().'">'.i18n::s('Back').'</a>';
 *
 * // display the anchor icon, if any
 * if($icon = $anchor->get_icon_url())
 *	 $context['text'] .= '<img src="'.$icon.'" alt="" />';
 *
 * // use text from the anchor to better introduce the thread
 * $context['text'] .= $anchor->get_prefix('thread');
 *
 * // the surfer may edit this item if he/she is an editor of the section
 * if($anchor->is_assigned()) {
 *	 ...
 * }
 *
 * // adapt the layout depending on anchor options
 * if($anchor->has_option('with_thread_alternate_layout')) {
 *	 ...
 * } else {
 *	 ...
 * }
 *
 * // use text from the anchor for the bottom of the page
 * $context['text'] .= $anchor->get_suffix('thread');
 *
 * // reflect the thread update in the anchor
 * $anchor->touch('thread:update');
 * [/php]
 *
 * To dig into details you should probably check following implementations of the Anchor interface:
 * - [script]articles/article.php[/script]
 * - [script]categories/category.php[/script]
 * - [script]sections/section.php[/script]
 * - [script]users/user.php[/script]
 *
 * @author Bernard Paques
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
abstract class Anchor {

	// the related item
	var $item;

	// its related overlay, if any
	var $overlay;

	// its related anchor, if any
	var $anchor;
	
	/** 
	 * class constructor 
	 * 
	 * may accept data
	 * @param array $item
	 */
	function __construct($item=NULL) {
	    
	    if($item) {		
		if(isset($item['anchor']))
		    $anchor = Anchors::get($item['anchor']);
		else
		    $anchor = NULL;
		// bind item, anchor and overlay data to object
		$this->load_by_content($item, $anchor);
	    }
	}
	
	/**
	 * Using object like a string get its reference
	 * 
	 * @return string 
	 */
	function __toString() {
	    return $this->get_reference();
	}
	
	/**
	 * allow or block operations to current surfer
	 *	 
	 * @param string the foreseen operation ('modification', 'creation', 'your keyword'...)
	 * @param string the kind of sub-item to handle, if needed
	 * @return TRUE if the operation is accepted, FALSE otherwise
	 */
	final function allows($action, $type ='') {
		global $context;

		// cache the overlay, if any
		if(!isset($this->overlay) && isset($this->item['overlay']))
			$this->overlay = Overlay::load($this->item, $this->get_type().':'.$this->item['id']);

		// delegate the validation to the overlay
		if(isset($this->overlay) && is_object($this->overlay) && is_callable(array($this->overlay, 'allows'))) {
			$reply =  $this->overlay->allows($action, $type);
			if($reply != 'PASS')
			    return $reply;
		}
			
		$allow_func = 'allow_'.$action;
		// delegate validation to legacy group class function, depending on 'action'
		$group_class = $this->get_static_group_class();		
		if(is_callable(array($group_class,$allow_func)))
			return $group_class::$allow_func($this->item, $this->anchor, $type);
		
		// delegate validation to class
		if(is_callable(array($this,$allow_func)))
			return $this->$allow_func($type);

		// blocked by default
		return FALSE;
	}
		
	/**
	 * creation rights on anchor. By default a associate can do everything on a anchor
	 * To be derivated in child class for other rules
	 *  
	 */
	function allow_creation($type='') {
	    
	    if(Surfer::is_associate())
		return true;
	    
	    return false;
	}
	
	/**
	 * deletion rights on anchor. By default a associate can do everything on a anchor
	 * To be derivated in child class for other rules
	 *  
	 */
	function allow_deletion($type='') {
	    
	    if(Surfer::is_associate())
		return true;
	    
	    return false;
	}
	
	/**
	 * modification rights on anchor. By default a associate can do everything on a anchor
	 * To be derivated in child class for other rules
	 *  
	 */
	function allow_modification($type='') {
	    
	    if(Surfer::is_associate())
		return true;
	    
	    return false;
	}

	/**
	 * alert watchers of this anchor
	 *
	 * @param array message attributes, such as 'subject', 'message', 'headers'
	 * @param string description of the on-going action (e.g., 'file:create')
	 * @param boolean TRUE if access to the target object is restricted, FALSE otherwise
	 * @return boolean TRUE on success, FALSE otherwise
	 */
	function alert_watchers($mail, $action=NULL, $restricted=FALSE) {
		global $context;

		// do not notify watchers if overlay prevents it
		if(is_object($this->overlay) && !$this->overlay->should_notify_watchers($mail))
			return FALSE;

		// list all items in the watching context
		$containers = $this->get_watched_context($action);

		// finalize the message
		$mail['message'] = Mailer::build_notification($mail['notification'], 1);

		// allow for message threading
		if(!isset($mail['headers']))
			$mail['headers'] = Mailer::set_thread($this->get_reference());

		// we are private, so consider only watchers who are also editors
		if($this->item['active'] == 'N')
			$restricted = TRUE;

		// list editors if access is restricted
		$editors = NULL;
		if($restricted)
			$editors = Members::list_editors_for_member($this->get_focus(), 0, 10000, 'ids');

		// do the job
		return Users::alert_watchers($containers, $mail, $editors);

	}

	/**
	 * maximise access rights
	 *
	 * @param string checked from child (e.g., 'Y', 'R', or 'N')
	 * @return string resulting value (e.g., 'Y', 'R', or 'N')
	 */
	function ceil_rights($set) {

		if($this->item['active'] == 'N')
			return 'N';

		if($this->item['active'] == 'R')
			if($set == 'N')
				return 'N';
			else
				return 'R';

		return $set;

	}

	/**
	 * get date of last modification
	 *
	 * @see services/check.php
	 *
	 * @return array the attribute 'timestamp' contains time of last update
	 */
	function &check() {

		$response = array();

		// 'timestamp'
		if(!isset($this->item['edit_date']))
			$response['timestamp'] = '';
		else
			$response['timestamp'] = SQL::strtotime($this->item['edit_date']);

		// 'name'
		if(!isset($this->item['edit_name']))
			$response['name'] = '';
		else
			$response['name'] = strip_tags($this->item['edit_name']);

		return $response;
	}
	
	/**
	 * delete this entry from database
	 * 
	 * @return boolean success of operation
	 */
	function delete() {
	    
	    // nothing to delete
	    if(!isset($this->item['id']))
		    return false;
	    
	    $group_class = $this->get_static_group_class();
	    
	    if($result =  $group_class::delete($this->item['id'])) {
		
		// be coherent with database
		$this->item = NULL;
		$this->anchor = NULL;
		$this->overlay = NULL;
	    }
	    
	    return $result;
	}

	/**
	 * visualize differences for some attribute
	 *
	 * @param string name of the target attribute
	 * @param string previous value
	 * @return string HTML showing differences
	 */
	function &diff($name, $value) {
		global $context;

		// previous text
		$value = Codes::beautify($value);

		// target attribute does not exist
		if(!isset($this->item[$name])) {
			$output = '<ins>'.$value.'</ins>';
			return $output;
		}

		// current text
		$current = Codes::beautify($this->item[$name]);

		// highlight differences
		include_once $context['path_to_root'].'scripts/scripts.php';
		$output =& Scripts::hdiff($value, $current);
		return $output;

	}

	/**
	 * get the active attribute
	 *
	 * @return string resulting value, or NULL on error
	 */
	function get_active() {

		if(isset($this->item['active']))
			return $this->item['active'];

		return NULL;

	}

	/**
	 * get behaviors for this anchor, and for parent anchors
	 *
	 * This function is used to compile the entire list of behaviors started to the current anchor.
	 *
	 * This function uses the cache to save on database requests.
	 *
	 * @return a string containing behaviors of this item, and of parent items
	 */
	function get_behaviors() {
		global $context;

		// cache the answer
		if(isset($this->get_behaviors_cache))
			return $this->get_behaviors_cache;

		// get the parent
		if(!$this->anchor && isset($this->item['anchor']))
			$this->anchor = Anchors::get($this->item['anchor']);

		// the parent level
		$text = '';
		if(is_object($this->anchor) && method_exists($this->anchor, 'get_behaviors'))
			$text = trim($this->anchor->get_behaviors());

		// get behaviors from this instance, if any
		if(is_array($this->item) && array_key_exists('behaviors', $this->item))
			$text = trim($text . "\n" . $this->item['behaviors']);

		// cache and return the result
		return $this->get_behaviors_cache =& $text;
	}
	
	/**
	 * list childs of this anchor, with or without types filter
	 * 
	 * to be overloaded in child classes
	 * 
	 * @todo : add optional order parameter as this : "articles by_name, sections by_title, ..."
	 * 
	 * @param string set of desired childs (articles, sections...) separted by comma, or "all" keyword
	 * @param int offset to start listing
	 * @param int the maximum of items returned per type
	 * @param mixed string or object the layout to use
	 * @return an array of layouted items sorted by type
	 */
	function get_childs($filter = 'all',$offset, $max, $layout='raw') {
	     return NULL;
	 }

	/**
	 * get the focus for this anchor
	 *
	 * This function lists containers of the content tree,
	 * from top level down to this item.
	 *
	 * @return array of anchor references (e.g., array('section:123', 'article:456') )
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
			$focus[] = $this->get_reference();

		return $focus;
	}

	/**
	 * get the handle of this anchor
	 *
	 * The goal is to support direct access to some protected resource.
	 * One example of this situation is a web form sent by mail.
	 * In this case, the surfer has not been authenticated to the form,
	 * but if the handle is provided he will be granted access to it.
	 *
	 * @return a secret handle, or NULL
	 */
	function get_handle() {
		if(is_array($this->item))
			return $this->item['handle'];
		return NULL;
	}

	/**
	 * get the url to load the icon set for the anchor
	 *
	 * A common concern of modern webmaster is to apply a reduced set of icons throughout all pages.
	 * This function is aiming to retrieve the full-size icon characterizing one anchor.
	 * It should be used in pages to display one single image near the top of the page.
	 *
	 * For example, if you are displaying a thread related to an article,
	 * you can display at the top of the page the article icon with the following code:
	 * [php]
	 * $anchor = Anchors::get($thread['anchor']);
	 * if($icon = $anchor->get_icon_url())
	 *	 $context['text'] .= '<img src="'.$icon.'" alt="" />';
	 * [/php]
	 *
	 *
	 * @return a valid url to be used in an <img> tag
	 */
	public function get_icon_url() {
		if(isset($this->item['icon_url']) && $this->item['icon_url'])
			return $this->item['icon_url'];
		return $this->get_thumbnail_url();
	}
	
	/**
	 * get the introduction of this anchor
	 * 
	 * @return a string
	 */
	function get_introduction() {
	    
	    // use overlay if any
	    if(is_object($this->overlay))
		return $this->overlay->get_text('introduction', $this->item);
	    elseif(isset($this->item['introduction']))
		return trim($this->item['introduction']);
	    
	    return NULL;
	    
	}

	/**
	 * get the named url for this anchor
	 *
	 * If the anchor as been named, this function returns the related url.
	 *
	 *
	 * @return an url to view the anchor page, or NULL
	 */
	function get_named_url() {
		if(isset($this->item['nick_name']) && $this->item['nick_name'])
			return normalize_shortcut($this->item['nick_name']);
		return NULL;
	}

	/**
	 * get data related to next and previous items, if any
	 *
	 * This function is used to add navigation links to pages.
	 *
	 * In most cases, its result is passed to [code]Skin::neighbours()[/code], like in following example:
	 * [php]
	 *	// buttons to display previous and next images, if any
	 *	if(is_object($anchor)) {
	 *		$neighbours = $anchor->get_neighbours('location', $item);
	 *		$context['text'] .= Skin::neighbours($neighbours, 'sidebar');
	 * }
	 * [/php]
	 *
	 * @see skins/skin_skeleton.php
	 *
	 * @param string the item type (eg, 'image', 'file', etc.)
	 * @param array the anchored item asking for neighbours
	 * @return an array (previous_url, previous_label, next_url, next_label, option_url, option_label)
	 */
	function get_neighbours($type, $item) {
		return array('', '', '', '', '', NULL);
	}

	/**
	 * get anchor nick name, if any
	 *
	 * @return string nick name, or NULL
	 */
	function get_nick_name() {
		if(isset($this->item['nick_name']))
			return $this->item['nick_name'];
		return NULL;
	}

	/**
	 * get the default overlay type for anchored items, if any
	 *
	 * This function is mainly used to associate overlays with sections.
	 *
	 * @param string name of the attribute that contains overlay class
	 * @return a string
	 */
	 function get_overlay($name='content_overlay') {
	 	$overlay = NULL;
		if($this->item && isset($this->item[$name]))
			$overlay = Overlay::bind($this->item[$name]);
		return $overlay;
	 }

	/**
	 * get the reference of the container of this anchor
	 *
	 * To be overloaded into derived class
	 *
	 * @return a string such as 'article:123', or 'section:456', etc.
	 */
	function get_parent() {
		if($this->item && isset($this->item['anchor']))
			return $this->item['anchor'];
		return NULL;
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
	 * To be overloaded into derived class
	 *
	 * @return an array of $url => $label
	 */
	function get_path_bar() {
		global $context;

		// no item bound
		if(!isset($this->item['id']))
			return NULL;

		// get the parent
		if(!isset($this->anchor))
			$this->anchor = Anchors::get($this->item['anchor']);

		// the parent level
		$parent = array();
		if(is_object($this->anchor))
			$parent = $this->anchor->get_path_bar();

		// this item
		$url = $this->get_url();
		$label = Codes::beautify_title($this->get_title());
		$path = array_merge($parent, array($url => $label));

		// return the result
		return $path;
	}
	
	/**
	 * get permalink to anchor
	 */
	function get_permalink() {
	    return NULL;
	}

	/**
	 * get the initial poster
	 *
	 * This function retrieves information from the anchor record, and attempts
	 * to load the related user record from the database, based on user id, if
	 * this exits, or on user e-mail address, if it has been provided.
	 *
	 * The array returned either contains a full user record, including a valid
	 * user id, or a reduced set of attributes, consisting of data in the anchor
	 * record itself
	 *
	 * @return an array of poster attributes
	 */
	function &get_poster() {
		global $context;

		// look for a user record from id or from address
		$poster = NULL;
		if(isset($this->item['create_id']))
			$poster = Users::get($this->item['create_id']);
		elseif(isset($this->item['create_address']))
			$poster = Users::get($this->item['create_address']);

		 // some anchors do not feature create attributes
		elseif(isset($this->item['edit_id']))
			$poster = Users::get($this->item['edit_id']);
		elseif(isset($this->item['edit_address']))
			$poster = Users::get($this->item['edit_address']);

		// no user record has been found, use anchor data
		if(!isset($poster['id'])) {
			$poster = array();
			if(isset($this->item['create_address']))
				$poster['email'] = $this->item['create_address'];
			elseif(isset($this->item['edit_address']))
				$poster['email'] = $this->item['edit_address'];

		}

		// nick name may have changed over time
		if(isset($this->item['create_name']))
			$poster['nick_name'] = $this->item['create_name'];
		elseif(isset($this->item['edit_name']))
			$poster['nick_name'] = $this->item['edit_name'];

		// return poster atributes
		return $poster;
	}

	/**
	 * get the prefix text
	 *
	 * This function is used to enhance the presentation of an item linked to
	 * an anchor. For example, a thread item can have a neat text, coming from the
	 * anchor, to introduce it:
	 * [php]
	 * $anchor = Anchors::get($thread['anchor']);
	 * $context['text'] .= $anchor->get_prefix('thread');
	 * [/php]
	 *
	 * To be overloaded into derived class
	 *
	 * @param string an indication to the anchor of the expected result
	 * @return a string
	 */
	function get_prefix($variant='') {
		global $context;

		// sanity check
		if(!isset($this->item) || !is_array($this->item) || !isset($this->item['prefix']) || !$this->item['prefix'])
			return '';

		// attempt to beautify this text
		if(is_callable(array('codes', 'beautify')))
			return '<div class="prefix">'.Codes::beautify($this->item['prefix']).'</div>';

		// else return raw string
		return '<div class="prefix">'.$this->item['prefix'].'</div>';
	}

	/**
	 * get the reference for this anchor
	 *
	 * This function is used to retrieve a reference to be placed into the database.
	 * For example:
	 * [php]
	 * $anchor = Anchors::get($article['anchor']);
	 * $context['text'] .= '<input type="hidden" name="anchor" value="'.$anchor->get_reference().'" />';
	 * [/php]
	 *
	 *
	 * @return a string such as 'article:123', or 'section:456', etc.
	 */
	final public function get_reference() {
	    $reference = NULL;
	    if(isset($this->item['id']))
		    $reference = $this->get_type().":".$this->item['id'];
	    
	    return $reference;
	}

	/**
	 * get the short url for this anchor
	 *
	 * If the anchor has one, this function returns a minimal url.
	 *
	 * To be overloaded into derived class
	 *
	 * @return an url to view the anchor page, or NULL
	 */
	function get_short_url() {
		return NULL;
	}
	
	/**
	 * provide classe name with all static functions on this kind of anchor
	 * 
	 * @return a class name
	 */
	function get_static_group_class() {
	    return 'Anchors';
	}

	/**
	 * get suffix text
	 *
	 * This function is used to enhance the presentation of an item linked to
	 * an anchor. For example, a thread item can have a neat text, coming from the
	 * anchor, to conclude the page:
	 * [php]
	 * $anchor = Anchors::get($thread['anchor']);
	 * $context['text'] .= $anchor->get_suffix('thread');
	 * [/php]
	 *
	 * To be overloaded into derived class
	 *
	 * @param string an indication to the anchor of the expected result
	 * @return a string
	 */
	function get_suffix($variant='') {
		global $context;

		// sanity check
		if(!isset($this->item) || !is_array($this->item) || !isset($this->item['suffix']) || !$this->item['suffix'])
			return '';

		// attempt to beautify this text
		if(is_callable(array('codes', 'beautify')))
			return '<div class="suffix">'.Codes::beautify($this->item['suffix']).'</div>';

		// else return raw string
		return '<div class="suffix">'.$this->item['suffix'].'</div>';
	}

	/**
	 * get some introductory text from this anchor
	 *
	 * This function is used to introduce comments, or any sub-item related to an anchor.
	 *
	 * This basic version does not care about the provided parameter.
	 *
	 * @param string an optional variant
	 * @return string some text or NULL
	 */
	function &get_teaser($variant = 'basic') {

		// nothing to do
		if(!is_array($this->item))
			$text = NULL;

		// use the introduction field, if any
		elseif($intro = $this->get_introduction ())
			$text = Codes::beautify($intro, $this->item['options']);

		// else use the description field
		else
			$text = Skin::cap(Codes::beautify($this->item['description'], $this->item['options']), 70);

		// done
		return $text;
	}		

	/**
	 * get available templates
	 *
	 * To be overloaded into derived class
	 *
	 * @param string the type of content to be created e.g., 'article', etc.
	 * @return array a list of models to consider, or NULL
	 */
	function get_templates_for($type='article') {
		return NULL;
	}

	/**
	 * get the url to load the thumbnail set for the anchor
	 *
	 * A common concern of modern webmaster is to apply a reduced set of icons throughout all pages.
	 * This function is aiming to retrieve the small-size icon characterizing one anchor.
	 * It should be used in pages to display several images into lists of anchored items.
	 *
	 * Note: This function returns a URL to the thumbnail that is created by default
	 * when an icon is set for the anchor. However, the webmaster can decide to
	 * NOT display anchor thumbnails throughout the server. In this case, he/she
	 * has just to suppress the thumbnail URL in each anchor and that's it.
	 *
	 *
	 * @return a valid url to be used in an <img> tag
	 */
	public function get_thumbnail_url() {
		if(isset($this->item['thumbnail_url']) && $this->item['thumbnail_url'])
			return $this->item['thumbnail_url'];
		return NULL;
	}

	/**
	 * get the title for this anchor
	 *
	 * This function is used to display a title relative to the anchor.
	 * For example, if you are displaying a thread related to an article,
	 * you will use the title of the article as the general page title.
	 * You can use following code to do that:
	 * [php]
	 * $anchor = Anchors::get($thread['anchor']);
	 * $context['page_title'] = $anchor->get_title();
	 * [/php]
	 *
	 * To be overloaded into derived class if title has a different name
	 *
	 * @return a string
	 */
	function get_title($use_overlay=true) {
	        
	    // use overlay if any
	    if(is_object($this->overlay) && $use_overlay)
		return $this->overlay->get_text('title', $this->item);
	    elseif(isset($this->item['title']))
		return trim(str_replace('& ', '&amp; ', $this->item['title']));
	    else
		return $this->get_reference();	    
	}

	/**
	 * get the type of this anchor
	 *
	 * @return string e.g., 'article', 'category', 'section'
	 */
	final public function get_type() {
		
		 $type = strtolower(get_class($this));
		 return $type;		 
	}

	/**
	 * get the url to display the main page for this anchor
	 *
	 * This function is used to link a page to a main one.
	 * For example, if you are displaying a thread related to an article,
	 * you can add a link to display the article with the following code:
	 * [php]
	 * $anchor = Anchors::get($thread['anchor']);
	 * $context['text'] .= '<a href="'.$anchor->get_url().'">'.i18n::s('Back').'</a>';
	 * [/php]
	 *
	 * To be overloaded into derived class
	 *
	 * @param string the targeted action ('view', 'print', 'edit', 'delete', ...)
	 * @return an url to view the anchor page
	 */
	function get_url($action='view') {
		return '** no url **';
	}

	/**
	 * get the value of one attribute
	 *
	 * This function avoids direct looking at internal storage.
	 *
	 * @param string attribute name
	 * @param mixed default value, if any
	 * @return mixed attribute value, of default value if attribute is not set
	 */
	function get_value($name, $default_value=NULL) {

		// attribute has a value
		if(isset($this->item[$name]))
			return $this->item[$name];

		// use default value
		return $default_value;

	}

	/**
	 * get all attributes
	 *
	 * @return array all attributes of this instance
	 */
	function get_values() {
		return $this->item;
	}

	/**
	 * list all items in the watching context
	 *
	 * Items that are included in the watching context are not always the same.
	 * For example, when the action is the creation of an article in a section,
	 * all sections up to the top of the content tree are included in the watching context.
	 * However, when an article is updated, the scope is limited to the containing section.
	 *
	 * Called in function alert_watchers() in shared/anchor.php
	 *
	 * @param string description of the on-going action (e.g., 'file:create')
	 * @return mixed either a reference (e.g., 'article:123') or an array of references
	 */
	protected function get_watched_context($action) {
		global $context;

		// by default, limit to direct watchers of this anchor
		return $this->get_reference();
	}

	/**
	 * check if an anchor implements a given layout
	 *
	 * To be overloaded into derived class, if necessary
	 *
	 * @param string the layout we are looking for
	 * @return TRUE or FALSE, or the value of the matching option if any
	 */
	function has_layout($option) {

		// sanity check
		if(!$this->item)
			return FALSE;

		// climb the anchoring chain, if any
		if((!isset($this->item['articles_layout']) || !$this->item['articles_layout'])
			&& isset($this->item['anchor']) && $this->item['anchor']) {

			// save requests
			if(!$this->anchor)
				$this->anchor = Anchors::get($this->item['anchor']);

			if(is_object($this->anchor))
				return $this->anchor->has_layout($option);

		// exact match, return TRUE
		} elseif(preg_match('/\b'.$option.'\b/i', $this->item['articles_layout']))
			return TRUE;

		// no match
		return FALSE;
	}

	/**
	 * check that an option has been set for this anchor
	 *
	 * This function is used to control, from the anchor, the behaviour of linked items.
	 *
	 * For example, the layout of a thread may vary from one section to another.
	 * To check that, you can use following code:
	 * [php]
	 * $anchor = Anchors::get($thread['anchor']);
	 * if($anchor->option('with_thread_alternate_layout') {
	 *	 ...
	 * } else {
	 *	 ...
	 * }
	 * [/php]
	 *
	 * Another potential usage is to select a skin variant.
	 * For example, if the options field has been set with the value 'variant_red_background',
	 * the variant can be retrieved from anchored items with the following code:
	 * [php]
	 * $anchor = Anchors::get($thread['anchor']);
	 * if($variant = $anchor->option('variant') {
	 *	 load_skin($variant);
	 * } else {
	 *	 load_skin('my_type');
	 * }
	 * [/php]
	 *
	 * This function recursively invokes upstream anchors, if any.
	 * For example, if the option 'skin_boxes' is set at the section level,
	 * all articles, but also all attached files and images of these articles,
	 * will feature the skin 'boxes'.
	 *
	 * To be overloaded into derived class, if necessary
	 *
	 * @param string the option we are looking for
	 * @param boolean TRUE if coming from content leaf, FALSE if coming from content branch
	 * @return TRUE or FALSE, or the value of the matching option if any
	 */
	function has_option($option, $leaf=TRUE) {

		// sanity check
		if(!$this->item)
			return FALSE;

		// 'locked' or not --stick at only one level
		if($option == 'locked') {
			if(isset($this->item['locked']) && ($this->item['locked'] == 'Y'))
				return TRUE;
			else
				return FALSE;
		}

		// 'variant' matches with 'variant_red_background', return 'red_background'
		if(isset($this->item['options']) && preg_match('/\b'.$option.'_(.+?)\b/i', $this->item['options'], $matches))
			return $matches[1];

		// exact match, return TRUE
		if(isset($this->item['options']) && (strpos($this->item['options'], $option) !== FALSE))
			return TRUE;

		// climb the anchoring chain
		if($leaf && isset($this->item['anchor']) && $this->item['anchor']) {

			// cache requests
			if(!$this->anchor)
				$this->anchor = Anchors::get($this->item['anchor']);

			// ask the anchor
			if(is_object($this->anchor))
				return $this->anchor->has_option($option, $leaf);
		}

		// no match
		return FALSE;
	}

	/**
	 * check the value of one attribute
	 *
	 * @param string attribute name
	 * @param mixed expected value
	 * @return boolean TRUE or FALSE
	 */
	function has_value($name, $value) {

		// attribute has no value
		if(!isset($this->item[$name]))
			return FALSE;

		// exact match is required
		return !strcmp($this->item[$name], $value);

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
	 * if($anchor->is_assigned() {
	 *	 ...
	 * }
	 * [/php]
	 *
	 * A logged member is always considered as an editor if he has created the target item.
	 *
	 * An anonymous surfer is considered as an editor if he has provided the secret handle.
	 *
	 * To be overloaded into derived class if field has a different name
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

		// surfer has provided the secret handle
		if(isset($this->item['handle']) && Surfer::may_handle($this->item['handle']))
			return $this->is_assigned_cache[$user_id] = TRUE;

		// surfer owns this item
		if($user_id && isset($this->item['owner_id']) && ($user_id == $this->item['owner_id']))
			return $this->is_assigned_cache[$user_id] = TRUE;
		
		// anchor has been assigned to this surfer
		if($user_id && Members::check('user:'.$user_id, $this->get_reference()))
			return $this->is_assigned_cache[$user_id] = TRUE;
		
		// anonymous edition is allowed
		if(($this->item['active'] == 'Y') && $this->has_option('anonymous_edit'))
			return $this->is_assigned_cache[$user_id] = TRUE;
		
		// members edition is allowed
		if(($this->item['active'] == 'Y') && Surfer::is_empowered('M') && $this->has_option('members_edit'))
			return $this->is_assigned_cache[$user_id] = TRUE;

		// check parent container
		if($cascade && isset($this->item['anchor'])) {

			// save requests
			if(!isset($this->anchor) || !$this->anchor)
				$this->anchor = Anchors::get($this->item['anchor']);

			// check for ownership
			if(is_object($this->anchor))
				return $this->is_assigned_cache[$user_id] = $this->anchor->is_assigned($user_id);

		}

		// sorry
		return $this->is_assigned_cache[$user_id] = FALSE;
	}

	/**
	 * determine if only selected persons can access this anchor
	 *
	 * To be overloaded into derived class if field has a different name
	 *
	 * @return TRUE or FALSE
	 */
	 function is_hidden() {

		// sanity check
		if(!is_array($this->item))
			return FALSE;

		// the anchor is public
		if(isset($this->item['active']) && ($this->item['active'] == 'N'))
			return TRUE;

		// not hidden
		return FALSE;
	}

	/**
	 * check that the surfer owns an anchor
	 *
	 * To be overloaded into derived class if attribute has a different name than 'owner_id'.
	 *
	 * @param int optional reference to some user profile
	 * @param boolean FALSE to not cascade the check to parent containers
	 * @return TRUE or FALSE
	 */
	 function is_owned($user_id=NULL, $cascade=TRUE) {
		global $context;

		// id of requesting user
		if(!$user_id) {
			if(!Surfer::get_id())
				return FALSE;
			$user_id = Surfer::get_id();
		}

		// surfer owns this item
		if(isset($this->item['owner_id']) && ($user_id == $this->item['owner_id']))
			return TRUE;

		// do not cascade
		if(!$cascade)
			return FALSE;

		// associates can always do it, except if not cascading
		if(($user_id == Surfer::get_id()) && Surfer::is_associate())
			return TRUE;

		// if surfer manages parent container it's ok too
		if(isset($this->item['anchor'])) {

			// save requests
			if(!isset($this->anchor) || !$this->anchor)
				$this->anchor = Anchors::get($this->item['anchor']);

			// test strict ownership
			if(is_object($this->anchor) && $this->anchor->is_owned($user_id))
				return TRUE;

		}

		// sorry
		return FALSE;
	}

	/**
	 * determine if public access is allowed to the anchor
	 *
	 * This function is used to enable additional processing steps on public pages only.
	 * For example, only public pages are pinged on publication.
	 *
	 * To be overloaded into derived class if field has a different name
	 *
	 * @return TRUE or FALSE
	 */
	 function is_public() {	     

		// cache the answer
		if(isset($this->is_public_cache))
			return $this->is_public_cache;    
	     

		// not set
		if(!is_array($this->item))
			return $this->is_public_cache = FALSE;
		

		// ensure the container allows for public access
		if(isset($this->item['anchor'])) {

			// save requests
			if(!$this->anchor)
				$this->anchor = Anchors::get($this->item['anchor']);

			if(is_object($this->anchor) && !$this->anchor->is_public())
				return $this->is_public_cache = FALSE;

		}

		// the anchor is public
		if(isset($this->item['active']) && ($this->item['active'] == 'Y'))
			return $this->is_public_cache = TRUE;

		// sorry
		return $this->is_public_cache = FALSE;
	}

	/**
	 * check that the surfer is allowed to display the anchor
	 *
	 * This function is used to control the authority delegation from the anchor.
	 *
	 * To be overloaded into derived class if field has a different name
	 *
	 * @param int optional reference to some user profile
	 * @return TRUE or FALSE
	 */
	 function is_viewable($user_id=NULL) {
		global $context;

		// we need some data to proceed
		if(!isset($this->item['id']))
			return FALSE;

		// surfer is a trusted host
		if(Surfer::is_trusted())
			return TRUE;

		// section is public
		if(isset($this->item['active']) && ($this->item['active'] == 'Y'))
			return TRUE;

		// id of requesting user
		if(!$user_id )
		    $user_id = Surfer::get_id();

		// anonymous is allowed
		if(!$user_id)
			$user_id = 0;

		// section is opened to members
		if($user_id && isset($this->item['active']) && ($this->item['active'] == 'R'))
			return TRUE;

		// anchor has to be assigned
		return ($this->is_assigned($user_id) || Surfer::is_associate());

	 }	 	

	/**
	 * load the related item
	 *
	 * This function is useful to create an object from an array of values
	 *
	 * @param array attributes to set this instance
	 * @param array attributes of the anchor, if any
	 */
	function load_by_content($item, $anchor=NULL) {
		global $context;

		// save attributes of this instance
		$this->item = $item;

		// save attributes of the anchor
		if($anchor)
			$this->anchor = $anchor;

		// get the related overlay, if any
		$this->overlay = NULL;
		if(isset($this->item['overlay']))
			$this->overlay = Overlay::load($this);

	}

	/**
	 * load the related item
	 *
	 * To be overloaded into derived class
	 *
	 * @param int the id of the record to load
	 * @param boolean TRUE to always fetch a fresh instance, FALSE to enable cache
	 */
	function load_by_id($id, $mutable=FALSE) {
		return NULL;
	}
	
	/**
	 * post a new item
	 * 
	 * @param mixed object or string the anchor where to post
	 * @param string the title of the new item
	 * @return boolean success of operation
	 */
	function post($anchor=NULL,$title='') {
	    
	    // we have already a id
	    if(isset($this->item['id']))
		    return false;
	    
	    // check the anchor ...
	    
	    // .. use provided object, will crush previous one if any
	    if(is_object($anchor)) {
		$this->anchor = $anchor;	
	    }
	    
	    // .. use object
	    if(isset($this->anchor))
		$anchor = $this->anchor->get_reference();	 	    
	    // .. nothing provided
	    elseif(!isset($anchor))
		return false;
	    // .. checking given reference
	    elseif($anchor && !is_object($anchor) && !$this->anchor = Anchors::get($anchor))
		return false;	    
	    
	    $this->item['anchor'] = $anchor; // now a valid reference or empty string for root anchor
	   
	    // check the title
	    if($title)
		// use provided one
		$this->item['title'] = $title;
	    elseif(!isset($this->item['title']))
		// make a defaut one    
		$this->item['title'] = $this->get_type ();
		   		    		
	    
	    $group_class = $this->get_static_group_class();	    
	    if($id = $group_class::post($this->item)) {
		// re-get all fields from database 
		$this->load_by_id($id);			
		return true;
	    }
	
	    // bad luck
	    return false;	   
	}

	/**
	 * restore a previous version of this anchor
	 *
	 * @see versions/restore.php
	 *
	 * @param array set of attributes to restore
	 * @return TRUE on success, FALSE otherwise
	 */
	function restore($item) {
		return FALSE;
	}

	/**
	 * change some attributes of an anchor
	 *
	 * @param array of (name, value)
	 * @return boolean TRUE on success, FALSE otherwise
	 */
	function set_values($fields) {
		return FALSE;
	}

	/**
	 * change the edit stamp of this anchor
	 *
	 * This function is used to reflect changes from sub-items into related items.
	 * For example, if a thread is linked to a section, one update of this thread
	 * will be considered as an update of the section itself.
	 * [php]
	 * $anchor = Anchors::get($thread['anchor']);
	 * $anchor->touch('thread:update');
	 * [/php]
	 *
	 * Following actions have been defined:
	 * - 'article:publish'
	 * - 'article:submit'
	 * - 'article:update'
	 * - 'section:create'
	 * - 'section:update'
	 * - 'file:create'
	 * - 'file:update'
	 * - 'image:create'
	 * - 'image:update'
	 * - 'image:set_as_icon'
	 * - 'user:create'
	 * - 'user:update'
	 *
	 * It is assumed that the surfer (i.e., Surfer::get_name()) is the author of the modification.
	 *
	 * To be overloaded into derived class
	 *
	 * @see articles/article.php
	 * @see sections/section.php
	 *
	 * @param string the description of the last action
	 * @param string the id of the item related to this update
	 * @param boolean TRUE to not change the edit date of this anchor, default is FALSE
	 */
	function touch($action, $origin=NULL, $silently=FALSE) {
		return;
	}

	/**
	 * transcode some references
	 *
	 * To be overloaded into derived class
	 *
	 * @see images/images.php
	 *
	 * @param array of pairs of strings to be used in preg_replace()
	 */
	function transcode($transcoded) {
	}

}
?>
