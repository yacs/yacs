<?php
/**
 * the overlay interface
 *
 * Overlays are a straightforward way to extend web pages.
 * For example, articles can be transformed to recipes, or to other pages that has to include some structured data.
 *
 * Overlay data is saved along standard yacs objects (e.g., articles) as a serialized snippet.
 * The encoding and decoding of this field requires a specialized class.
 *
 * The overlay interface masks these details and offers convenient methods to create, access and save piggy-back data.
 *
 * As visible in [script]articles/edit.php[/script], the creation of a new overlay
 * or the update of an existing one is achieved through following sequence:
 * [php]
 * // create a new overlay
 * $overlay = Overlay::bind($overlay_type);
 *
 * // get form fields used to updated the overlay
 * $fields = $overlay->get_fields($item);
 *
 * // get a label for some ordinary field
 * $label = $overlay->get_label('title', 'edit');
 *
 * ...
 *
 * // process posted data
 * $overlay->parse_fields($_POST);
 *
 * // save article and serialized overlay as well
 * $_POST['overlay'] = $overlay->save();
 * $_POST['overlay_id'] = $overlay->get_id();
 * $id = Articles::post($_POST);
 *
 * // save overlay state
 * $overlay->remember('insert', $item, 'article:'.$id);
 * [/php]
 *
 * As visible into [script]articles/view.php[/script], an overlay is handled
 * with following calls:
 * [php]
 * // extract overlay data from a record
 * $overlay = Overlay::load($item, 'article:'.$item['id']);
 *
 * // get text related to this instance
 * $text = $overlay->get_text('view');
 *
 * // get additional tabs
 * $text = $overlay->get_tabs('view', $item);
 * [/php]
 *
 * Also, post-processing steps can include the removal of the hosting record,
 * as shown in [script]articles/delete.php[/script]
 * [php]
 * // extract overlay data from a record
 * $overlay = Overlay::load($item, 'article:'.$item['id']);
 *
 * // post-processing steps specific to the overlay
 * $overlay->remember('delete', $item, 'article:'.$item['id']);
 * [/php]
 *
 *
 * As a consequence, this class has two constructors:
 * - [code]bind()[/code] -- create an instance from scratch
 * - [code]load()[/code] -- create an instance from one article of the database
 *
 * The interface itself is made of following member functions,
 * that have to be overloaded into child functions:
 * - [code]allows()[/code] -- re-enforce access rights
 * - [code]get_extra_text()[/code] -- to be integrated into page side
 * - [code]get_fields()[/code] -- build a form to modify overlay content
 * - [code]get_id()[/code] -- to retrieve an overlay
 * - [code]get_label()[/code] -- specialize the overlaid page
 * - [code]get_list_text()[/code] -- to be integrated into a list of items
 * - [code]get_live_introduction()[/code] -- change page introduction
 * - [code]get_live_title()[/code] -- change page title
 * - [code]get_tabs()[/code] -- add information in panels
 * - [code]get_text()[/code] -- use overlay data in normal pages
 * - [code]get_trailer_text()[/code] -- to be appended after the description
 * - [code]get_type()[/code] -- basic information
 * - [code]get_view_text()[/code] -- to be integrated into the main page, between introduction and description
 * - [code]parse_fields()[/code] -- capture form content
 * - [code]render()[/code] -- delegate rendering to the overlay
 * - [code]remember()[/code] -- for specific post-processing steps
 *
 * Following functions are aiming to simplify external calls:
 * - [code]export()[/code] -- to generate some XML
 * - [code]save()[/code] -- to serialize overlay content
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Neige1963
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Overlay {

	/**
	 * attributes specific to this overlay
	 */
	var $attributes = array();

	/**
	 * previous version of attributes, when overlay content is updated
	 */
	var $snapshot = array();
	
	/**
	 * the parent object
	 */
	var $anchor = NULL;

	/**
	 * allow or block operations
	 *
	 * @see overlays/petition.php
	 *	 
	 * @param string the foreseen operation ('edit', 'new', ...)
	 * @param string the kind of item to handle ('approval', ...)
	 * @return TRUE if the operation is accepted, FALSE otherwise
	 */
	function allows($action, $type ='') {
		return FALSE;
	}

	/**
	 * create a new overlay from scratch
	 *
	 * This function creates an instance of the Overlay class based on the given type.
	 * For the type '[code]foo[/code]', the script file '[code]overlays/foo.php[/code]' is loaded.
	 *
	 * Example:
	 * [php]
	 * // create a new overlay
	 * $overlay = Overlay::bind('recipe');
	 * [/php]
	 *
	 * The provided string may include parameters after the type.
	 * These parameters, if any, are saved along overlay attributes.
	 *
	 * Example:
	 * [php]
	 * // this overlay will preserve past events
	 * $overlay = Overlay::bind('day without_past_dates');
	 * [/php]
	 *
	 * This function calls the member function initialize() to allow for additional
	 * generic initialization steps, if required. Example: loading of an external configuration
	 * file.
	 *
	 * @see articles/edit.php
	 * @see overlays/day.php
	 * @see sections/edit.php
	 *
	 * @param string overlay type
	 * @return a brand new instance
	 */
	final public static function bind($type) {
		global $context;

		// sanity check
		if(!$type || !trim($type))
			return NULL;

		// stop hackers, if any
		$type = preg_replace(FORBIDDEN_IN_PATHS, '', strip_tags($type));

		// remove side spaces
		$type = trim($type);

		// localize overlays strings --not related to Overlay::bind() at all...
		i18n::bind('overlays');

		// extract parameters, if any
		$parameters = '';
		if((strlen($type) > 1) && (($separator = strpos($type, ' ', 1)) !== FALSE)) {
			$parameters = substr($type, $separator+1);
			$type = substr($type, 0, $separator);
		}

		// reject hooks
		if(preg_match('/hook$/i', $type))
			return NULL;

		// load the overlay class file
		$file = $context['path_to_root'].'overlays/'.$type.'.php';
		if(is_readable($file))
			include_once $file;

		// create the instance
		if(class_exists($type)) {
			$overlay = new $type;
			$overlay->attributes = array();
			$overlay->attributes['overlay_type'] = $type;
			$overlay->attributes['overlay_parameters'] = $parameters;

			// allow for internal initialization of the overlay
			$overlay->initialize();

			return $overlay;
		}

		// houston, we've got a problem -- Logger::error() is buggy here
		if($context['with_debug'] == 'Y')
			Logger::remember('overlays/overlay.php: overlay::bind() unknown overlay type', $type, 'debug');
		return NULL;
	}

	/**
	 * export an overlay as XML
	 *
	 * @return some XML to be inserted into the resulting page
	 */
	function export() {
		if(isset($this->attributes['overlay_type']))
			$class = ' class="'.$this->attributes['overlay_type'].'"';
		else
			$class = '';
		if(isset($this->attributes['overlay_parameters']))
			$parameters = ' parameters="'.$this->attributes['overlay_parameters'].'"';
		else
			$parameters = '';
		$text =  "\t".'<overlay'.$class.$parameters.'>'."\n";
		foreach($this->attributes as $label => $value) {
			if($label == 'overlay_type')
				continue;
			if($label == 'overlay_parameters')
				continue;
			if(is_array($value)) {
				$text .=  "\t\t".' <'.$label.'><array>'."\n";
				foreach($value as $sub_value) {
					$text .=  "\t\t\t".' <item>';
					if(is_array($sub_value)) {
						$text .=  '<array>';
						foreach($sub_value as $sub_sub_value)
							$text .=  '<item>'.encode_field($sub_sub_value).'</item>';
						$text .=  '</array>';
					} else
						$text .=  encode_field($sub_value);
					$text .=  '</item>'."\n";
				}
				$text .=  "\t\t".' </array></'.$label.'>'."\n";
			} else
				$text .=  "\t\t".' <'.$label.'>'.encode_field($value).'</'.$label.'>'."\n";
		}
		$text .=  "\t".'</overlay>'."\n";

		return $text;
	}

	/**
	 * text to be inserted at page bottom
	 *
	 * To be overloaded into derived class
	 *
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_details_text($host=NULL) {
		$text = '';
		return $text;
	}

	/**
	 * text to be inserted into a mail notification
	 *
	 * This function is called to generate notifications sent to watchers when an item
	 * is either created or edited.
	 *
	 * To be overloaded into derived class
	 *
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function get_diff_text($host=NULL) {
		return $this->get_view_text($host);
	}

	/**
	 * text to be inserted aside
	 *
	 * To be overloaded into derived class
	 *
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_extra_text($host=NULL) {
		$text = '';
		return $text;
	}

	/**
	 * build the list of fields for one overlay
	 *
	 * This function extends the regular form with fields that are specific to the overlay.
	 *
	 * If $host['id'] is not set, then you can assume that this is the initial capture
	 * of data, before any record is created.
	 *
	 * If $host['id'] has a value, then you should provide the set of fields required
	 * for an update.
	 *
	 * If $this->anchor is an object, then you can call $this->anchor->is_owned() to adapt to
	 * specific access rules. Else you have to assume that the surfer is creating a new item,
	 * and that he is the actual owner.
	 *
	 * To be overloaded into derived class.
	 *
	 * @see articles/edit.php
	 * @see articles/edit_as_simple.php
	 * @see articles/edit_as_thread.php
	 * @see sections/edit.php
	 * @see users/edit.php
	 *
	 * @param the hosting attributes
	 * @return a list of ($label, $input, $hint) to be integrated into the form.
	 */
	function get_fields($host) {
		return array();
	}

	/**
	 * identify one instance
	 *
	 * This function returns a string that identify uniquely one overlay instance.
	 * When this information is saved, it can be used later on to retrieve one page
	 * and its content.
	 *
	 * To be overloaded into derived class
	 *
	 * @see articles/edit.php
	 *
	 * @returns a unique string, or NULL
	 */
	function get_id() {
		return NULL;
	}

	/**
	 * get an overlaid label
	 *
	 * This function changes strings used to describe an overlaid item.
	 *
	 * If the name is a regular attribute label, such as 'title', 'description', 'title_hint',
	 * then the action code describes the context of the action:
	 * - 'edit' modification form of an existing object
	 * - 'delete' deletion form
	 * - 'new' creation form of a new object
	 * - 'view' regular rendering of the object
	 *
	 * If the name applies to a command, such as 'new_command', then the action string
	 * describes the context of this command:
	 * - 'articles' apply to pages
	 * - 'comments' apply to comments
	 *
	 * To be overloaded into derived class
	 *
	 * @see articles/delete.php
	 * @see articles/duplicate.php
	 * @see articles/edit.php
	 * @see articles/fetch_as_msword.php
	 * @see articles/fetch_as_pdf.php
	 * @see articles/layout_articles_as_alistapart.php
	 * @see articles/print.php
	 * @see articles/view.php
	 * @see sections/section.php
	 *
	 * @param string the target label
	 * @param string the on-going action
	 * @return the label to use, or NULL if no default label has been found
	 */
	function get_label($name, $action='view') {
		return NULL;
	}

	/**
	 * display the content of one overlay in a list
	 *
	 * To be overloaded into derived class
	 *
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_list_text($host=NULL) {
		$text = '';
		return $text;
	}

	/**
	 * display a live description
	 *
	 * To be overloaded into derived class
	 *
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_live_description($host=NULL) {
		$text = $host['description'];
		return $text;
	}

	/**
	 * display a live introduction
	 *
	 * To be overloaded into derived class
	 *
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_live_introduction($host=NULL) {
		$text = $host['introduction'];
		return $text;
	}

	/**
	 * display a live title
	 *
	 * To be overloaded into derived class
	 *
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_live_title($host=NULL) {
		$text = $host['title'];
		return $text;
	}

	/**
	 * add some tabbed panels
	 *
	 * Display additional information in panels.
	 *
	 * Accepted action codes:
	 * - 'view' - embedded into the item viewing page
	 * - 'edit' - embedded into the item form page
	 *
	 * If $host['id'] is not set, then you can assume that this is the initial capture
	 * of data, before any record is created.
	 *
	 * If $host['id'] has a value, then you should provide the set of fields required
	 * for an update.
	 *
	 * If $this->anchor is an object, then you can call $this->anchor->is_owned() to adapt to
	 * specific access rules. Else you have to assume that the surfer is creating a new item,
	 * and that he is the actual owner.
	 *
	 * To be overloaded into derived class
	 *
	 * @see articles/edit.php
	 * @see articles/edit_as_simple.php
	 * @see articles/edit_as_thread.php
	 * @see articles/view.php
	 * @see sections/edit.php
	 * @see sections/view.php
	 * @see users/edit.php
	 * @see users/view.php
	 *
	 * @param string the on-going action
	 * @param array the hosting record
	 * @return an array of array('tab_id', 'tab_label', 'panel_id', 'panel_content') or NULL
	 */
	function &get_tabs($variant='view', $host=NULL) {
		$output = NULL;
		return $output;
	}

	/**
	 * display the content of one overlay
	 *
	 * Accepted variant codes:
	 * - 'description' - as a live description
	 * - 'details' - details at page bottom
	 * - 'extra' - displayed aside
	 * - 'introduction' - as a live introduction
	 * - 'list' - part of a list
	 * - 'title' - as a live title
	 * - 'trailer' - displayed at the bottom
	 * - 'view' - in the main viewing panel
	 *
	 * To be overloaded into derived class
	 *
	 * @param string the variant code
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_text($variant='view', $host=NULL) {
		switch($variant) {

		// live description
		case 'description':
			$text =& $this->get_live_description($host);
			return $text;

		// small details
		case 'details':
			$text =& $this->get_details_text($host);
			return $text;

		// diff from a previous version, for e-mail notifications
		case 'diff':
			$text = $this->get_diff_text($host);
			return $text;

		// extra side of the page
		case 'extra':
			$text =& $this->get_extra_text($host);
			return $text;

		// live introduction
		case 'introduction':
			$text =& $this->get_live_introduction($host);
			return $text;

		// container is one item of a list
		case 'list':
			$text =& $this->get_list_text($host);
			return $text;

		// live title
		case 'title':
			$text =& $this->get_live_title($host);
			return $text;

		// at the bottom of the page, after the description field
		case 'trailer':
			$text =& $this->get_trailer_text($host);
			return $text;

		// full page of the container
		case 'view':
		default:
			$text =& $this->get_view_text($host);
			return $text;
		}
	}

	/**
	 * text to come after page description
	 *
	 * To be overloaded into derived class
	 *
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_trailer_text($host=NULL) {
		$text = '';
		return $text;
	}

	/**
	 * retrieve overlay type
	 *
	 * @see articles/edit.php
	 *
	 * @returns string
	 */
	function get_type() {
		return $this->attributes['overlay_type'];
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

		// use reflection
		$method = 'get_'.$name.'_value';
		if(is_callable(array($this, $method)))
			return call_user_func(array($this, $method), $default_value);

		// attribute has a value
		if(isset($this->attributes[$name]))
			return $this->attributes[$name];

		// use default value
		return $default_value;

	}

	/**
	 * display the content of one overlay in main view panel
	 *
	 * To be overloaded into derived class
	 *
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_view_text($host=NULL) {
		$text = '';
		foreach($this->attributes as $label => $value) {
			$text .= '<p>'.$label.': '.$value."</p>\n";
		}
		return $text;
	}

	/**
	 * initialize this instance
	 *
	 * This function is called automatically when an instance is loaded in memory,
	 * to allow for any complementary setup, such as:
	 * - load parameters from an external file
	 * - read data from some sensor
	 * - build a cache of data useful to the overlay
	 *
	 * Warning: this is a low-level function that is called before the settings of
	 * $this->attributes and of $this->anchor so you can't rely on these variables here.
	 *
	 * To be overloaded into derived class
	 *
	 */
	function initialize() {
	}

	/**
	 * restore an instance
	 *
	 * This function unserializes piggy-back data and uses it to populate an overlay instance.
	 *
	 * [php]
	 * // get the record from the database
	 * $item = Articles::get($id);
	 *
	 * // extract overlay data from $item['overlay']
	 * $overlay = Overlay::load($item, 'article:'.$item['id']);
	 * [/php]
	 *
	 * @see articles/delete.php
	 * @see articles/edit.php
	 * @see articles/view.php
	 *
	 * @param array the hosting array
	 * @param string reference of the containing page (e.g., 'article:123')
	 * @return a restored instance, or NULL
	 */
	final public static function load($host, $reference) {
		global $context;

		// no overlay yet
		if(!isset($host['overlay']) || !$host['overlay'])
			return NULL;

		// retrieve the content of the overlay
		if(($attributes = Safe::unserialize($host['overlay'])) === FALSE)
			return NULL;

		// restore unicode entities
		foreach($attributes as $name => $value) {
			if(is_string($value))
				$attributes[$name] = utf8::from_unicode($value);
		}

		// we need a type
		if(!is_array($attributes) || !isset($attributes['overlay_type']))
			return NULL;

		// bind this to current page
		if(isset($host['id']))
			$attributes['id'] = $host['id'];

		// use one particular overlay instance
		$overlay = Overlay::bind($attributes['overlay_type']);
		if(is_object($overlay)) {
			$overlay->attributes = $attributes;

			// expose all of the anchor interface to the contained overlay
			$overlay->anchor = Anchors::get($reference);

			// ready to use!
			return $overlay;
		}

		// unknown overlay type or empty overlay
		return NULL;
	}
	
	/**
	 * Load in current page style sheets and javascript 
	 * files binded with the overlay.
	 * 
	 * Filenames must be same as classname, plus extension. 
	 * 
	 * Usage :  $this->load_scripts_n_styles();
	 * within parts of your overlay witch need those dependancies.
	 * (render(), get_view_text() ... )
	 * 
	 * Note the function will also call dependancies of parent class.
	 *
	 * @param type $myclass, argument used by the recursive call.
	 */
	final protected function load_scripts_n_styles($myclass='') {
	    
	    if(!$myclass)
		$myclass = get_class($this);
	    
	    $parent = get_parent_class($myclass);
	    
	    // load scripts (if exist)
	    Page::load_style(strtolower('overlays/'.$myclass.'/'.$myclass.'.css'));
	    Page::defer_script(strtolower('overlays/'.$myclass.'/'.$myclass.'.js'));
	    
	    // recursive call to parent class, stop on "Overlay"
	    if($parent!= '' && $parent!='Overlay')
		$parent::load_scripts_n_styles($parent);	    
	}

	/**
	 * capture form content
	 *
	 * This function allows to save, within the overlay itself, some of the data submitted to a web form.
	 * You should overload this function, to ensure that selected attributes from $fields are copied
	 * or initialized in $this->attributes.
	 *
	 * Content of $this->attributes is automatically serialized and saved into the piggy-backed
	 * attribute 'overlay' of the hosting record.
	 *
	 * Example code from overlays/recipe.php for this function:
	 * [php]
	 * 	$this->attributes['people'] = isset($fields['people']) ? $fields['people'] : '';
	 * 	$this->attributes['preparation_time'] = isset($fields['preparation_time']) ? $fields['preparation_time'] : '';
	 * 	$this->attributes['cooking_time'] = isset($fields['cooking_time']) ? $fields['cooking_time'] : '';
	 * 	$this->attributes['ingredients'] = isset($fields['ingredients']) ? $fields['ingredients'] : '';
	 * [/php]
	 *
	 * To be overloaded into derived class.
	 *
	 * @see articles/edit.php
	 *
	 * @param array data transmitted to the server through a web form
	 */
	function parse_fields($fields) {
	}

	/**
	 * render some page component
	 *
	 * To be overloaded into derived class
	 *
	 * @see sections/view.php
	 *
	 * @param string type of component to render, e.g., 'articles'
	 * @param string anchor reference, such as 'section:123'
	 * @param int page
	 * @return mixed some text, or NULL
	 */
	function render($type, $reference, $page=1) {
		return NULL;
	}

	/**
	 * remember an action once it's done
	 *
	 * This function enables a cascaded synchronization with some external storage facility.
	 * For example, a secondary table can be created in the database to derive information from some overlay instance.
	 *
	 * To be overloaded into derived class
	 *
	 * @see articles/delete.php
	 * @see articles/edit.php
	 *
	 * @param string the action 'insert', 'update' or 'delete'
	 * @param array the request containing data transmitted to the server through a web form
	 * @param string reference of the hosting record (e.g., 'article:123')
	 * @return FALSE on error, TRUE otherwise
	 */
	function remember($action, $request, $reference) {
		return TRUE;
	}

	/**
	 * serialize overlay content
	 *
	 *
	 * @see articles/edit.php
	 *
	 * @return the serialized string
	 */
	function save() {

		// ensure we put only pure ASCII in strings
		utf8::to_unicode($this->attributes);

		// just serialize
		return serialize($this->attributes);

	}

	/**
	 * set and store some attributes
	 *
	 * Use this function to change some attributes of your overlay, and also
	 * to serialize data into the original container (article, section, etc.)
	 *
	 * @param array of ($name => $value) pairs
	 * @return boolean TRUE on success, FALSE otherwise
	 */
	function set_values($fields) {

		// set attributes in memory
		foreach($fields as $name => $value)
			$this->attributes[$name] = $value;

		// store this permanently
		if(is_object($this->anchor)) {
			$fields = array();
			$fields['overlay'] = $this->save();
			$fields['overlay_id'] = $this->get_id();
			return $this->anchor->set_values($fields);
		}

	}

	/**
	 * embed embeddable files or not?
	 *
	 * By default, when an embeddable file is attached to a page, a yacs code is placed
	 * in the description field of this page to feature the new file.
	 * To prevent this behaviour in some pages, you can program the overlay to return
	 * FALSE to this function call.
	 *
	 * To be overloaded into derived class
	 *
	 * @see overlays/embed.php
	 *
	 * @return boolean TRUE by default, but can be changed in derived overlay
	 */
	function should_embed_files() {
		return TRUE;
	}

	/**
	 * notify followers or not?
	 *
	 * This function is used in articles/publish.php to prevent notification of followers.
	 *
	 * To be overloaded into derived class
	 *
	 * @see articles/publish.php
	 *
	 * @return boolean FALSE by default, but can be changed in derived overlay
	 */
	function should_notify_followers() {
		return FALSE;
	}

	/**
	 * notify watchers or not?
	 *
	 * This function is used in various scripts to prevent notification of watchers.
	 *
	 * To be overloaded into derived class
	 *
	 * @see articles/edit.php
	 * @see articles/publish.php
	 *
	 * @param array if provided, a notification that can be sent to customised recipients
	 * @return boolean TRUE by default, but can be changed in derived overlay, such as events
	 */
	function should_notify_watchers($mail=NULL) {
		return TRUE;
	}

	/**
	 * make a shallow copy of attributes
	 *
	 * This function allows to detect changes when content of an overlay is modified.
	 *
	 */
	function snapshot() {

		// to be compared with $this->attributes
		$this->snapshot = array();

		// shallow copy should be enough
		foreach($this->attributes as $name => $value) {
			if(is_object($value))
				$this->snapshot[$name] = clone $value;
			else
				$this->snapshot[$name] = $value;
		}

	}
}

?>
