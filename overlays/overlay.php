<?php
/**
 * the overlay interface used by articles
 *
 * Overlays are a straightforward way to extend YACS content pages.
 * For example, articles can be transformed to recipes, or to other pages that has to include some structured data.
 *
 * Overlay data is saved along standard articles as a serialized snippet.
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
 * Articles::post($_POST);
 *
 * // save overlay state
 * $overlay->remember('insert', $item);
 * [/php]
 *
 * As visible into [script]articles/view.php[/script], an overlay is handled
 * with following calls:
 * [php]
 * // extract overlay data from a record
 * $overlay = Overlay::load($item);
 *
 * // get text related to this instance
 * $text = $overlay->get_text('view');
 *
 * // get additional tabs
 * $text = $overlay->get_tabs('view');
 * [/php]
 *
 * Also, post-processing steps can include the removal of the hosting record,
 * as shown in [script]articles/delete.php[/script]
 * [php]
 * // extract overlay data from a record
 * $overlay = Overlay::load($item);
 *
 * // post-processing steps specific to the overlay
 * $overlay->remember('delete', $item);
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
	var $attributes;

	/**
	 * allow or block operations
	 *
	 * @param string the kind of item to handle ('decision', ...)
	 * @param string the foreseen operation ('edit', 'new', ...)
	 * @return TRUE if the operation is accepted, FALSE otherwise
	 */
	function allows($type, $action) {
		return TRUE;
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
	 * @see articles/edit.php
	 * @see sections/edit.php
	 *
	 * @param string overlay type
	 * @return a brand new instance
	 *
	 * @see articles/edit.php
	 */
	function bind($type) {
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

		// reject hooks as well
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
			return $overlay;
		}

		// houston, we've got a problem -- Logger::error() is buggy here
		if($context['with_debug'] == 'Y')
			Logger::remember('overlays/overlay.php', 'overlay::bind() unknown overlay type', $type, 'debug');
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
		$text =  ' <overlay'.$class.$parameters.'>'."\n";
		foreach($this->attributes as $label => $value) {
			if($label == 'overlay_type')
				continue;
			if($label == 'overlay_parameters')
				continue;
			if(is_array($value)) {
				$text .=  "\t".' <'.$label.'><array>'."\n";
				foreach($value as $sub_value) {
					$text .=  "\t\t".' <item>';
					if(is_array($sub_value)) {
						$text .=  '<array>';
						foreach($sub_value as $sub_sub_value)
							$text .=  '<item>'.encode_field($sub_sub_value).'</item>';
						$text .=  '</array>';
					} else
						$text .=  encode_field($sub_value);
					$text .=  '</item>'."\n";
				}
				$text .=  "\t".' </array></'.$label.'>'."\n";
			} else
				$text .=  "\t".' <'.$label.'>'.encode_field($value).'</'.$label.'>'."\n";
		}
		$text .=  ' </overlay>'."\n";

		return $text;
	}

	/**
	 * text to be inserted at page bottom
	 *
	 * To be overloaded into derivated class
	 *
	 * @param array the hosting record, if any
	 * @param mixed any other options
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_details_text($host=NULL, $options=NULL) {
		$text = '';
		return $text;
	}

	/**
	 * text to be inserted aside
	 *
	 * To be overloaded into derivated class
	 *
	 * @param array the hosting record, if any
	 * @param mixed any other options
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_extra_text($host=NULL, $options=NULL) {
		$text = '';
		return $text;
	}

	/**
	 * build the list of fields for one overlay
	 *
	 * This function is used to create forms aiming to change overlay data.
	 * To be overloaded into derivated class.
	 *
	 * @param the hosting attributes
	 * @return a list of ($label, $input, $hint)
	 *
	 * @see articles/edit.php
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
	 * @returns a unique string, or NULL
	 *
	 * @see articles/edit.php
	 */
	function get_id() {
		return NULL;
	}

	/**
	 * get an overlaid label
	 *
	 * This function changes strings used to describe an overlaid item.
	 *
	 * Accepted action codes:
	 * - 'edit' modification of an existing object
	 * - 'delete' deletion form
	 * - 'new' creation of a new object
	 * - 'view' rendering of the object
	 *
	 * To be overloaded into derivated class
	 *
	 * @param string the target label
	 * @param string the on-going action
	 * @return the label to use, or NULL if no default label has been found
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
	 */
	function get_label($name, $action='view') {
		return NULL;
	}

	/**
	 * display the content of one overlay in a list
	 *
	 * To be overloaded into derivated class
	 *
	 * @param array the hosting record, if any
	 * @param mixed any other options
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_list_text($host=NULL, $options=NULL) {
		$text = '';
		return $text;
	}

	/**
	 * display a live description
	 *
	 * To be overloaded into derivated class
	 *
	 * @param array the hosting record, if any
	 * @param mixed any other options
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_live_description($host=NULL, $options=NULL) {
		$text = $host['description'];
		return $text;
	}

	/**
	 * display a live introduction
	 *
	 * To be overloaded into derivated class
	 *
	 * @param array the hosting record, if any
	 * @param mixed any other options
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_live_introduction($host=NULL, $options=NULL) {
		$text = $host['introduction'];
		return $text;
	}

	/**
	 * display a live title
	 *
	 * To be overloaded into derivated class
	 *
	 * @param array the hosting record, if any
	 * @param mixed any other options
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_live_title($host=NULL, $options=NULL) {
		$text = $host['title'];
		return $text;
	}

	/**
	 * add some tabbed panels
	 *
	 * Display additional information in panels.
	 *
	 * Accepted action codes:
	 * - 'view' - embedded into the main viewing page
	 * - 'edit' - embedded into the main form page
	 *
	 * @see overlays/overlay.php
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
	 * To be overloaded into derivated class
	 *
	 * @param string the variant code
	 * @param array the hosting record, if any
	 * @param mixed any other options
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_text($variant='view', $host=NULL, $options=NULL) {
		switch($variant) {

		// live description
		case 'description':
			$text =& $this->get_live_description($host, $options);
			return $text;

		// small details
		case 'details':
			$text =& $this->get_details_text($host, $options);
			return $text;

		// extra side of the page
		case 'extra':
			$text =& $this->get_extra_text($host, $options);
			return $text;

		// live introduction
		case 'introduction':
			$text =& $this->get_live_introduction($host, $options);
			return $text;

		// container is one item of a list
		case 'list':
			$text =& $this->get_list_text($host, $options);
			return $text;

		// live title
		case 'title':
			$text =& $this->get_live_title($host, $options);
			return $text;

		// at the bottom of the page, after the description field
		case 'trailer':
			$text =& $this->get_trailer_text($host, $options);
			return $text;

		// full page of the container
		case 'view':
		default:
			$text =& $this->get_view_text($host, $options);
			return $text;
		}
	}

	/**
	 * text to come after page description
	 *
	 * To be overloaded into derivated class
	 *
	 * @param array the hosting record, if any
	 * @param mixed any other options
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_trailer_text($host=NULL, $options=NULL) {
		$text = '';
		return $text;
	}

	/**
	 * retrieve overlay type
	 *
	 * @returns string
	 *
	 * @see articles/edit.php
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
	 * To be overloaded into derivated class
	 *
	 * @param array the hosting record, if any
	 * @param mixed any other options
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_view_text($host=NULL, $options=NULL) {
		$text = '';
		foreach($this->attributes as $label => $value) {
			$text .= '<p>'.$label.': '.$value."</p>\n";
		}
		return $text;
	}

	/**
	 * restore an instance
	 *
	 * This function unserializes piggy-back data and uses it to populate an overlay instance.
	 *
	 * [php]
	 * // get the record from the database
	 * $item =& Articles::get($id);
	 *
	 * // extract overlay data from $item['overlay']
	 * $overlay = Overlay::load($item);
	 * [/php]
	 *
	 * @param array the hosting array
	 * @param string the attribute which contains overlay data
	 * @return a restored instance, or NULL
	 *
	 * @see articles/delete.php
	 * @see articles/edit.php
	 * @see articles/view.php
	 */
	function load($host, $name='overlay') {
		global $context;

		// no overlay yet
		if(!isset($host[$name]) || !$host[$name])
			return NULL;

		// retrieve the content of the overlay
		if(($attributes = Safe::unserialize($host[$name])) === FALSE)
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
			return $overlay;
		}

		// unknown overlay type or empty overlay
		return NULL;
	}

	/**
	 * capture form content
	 *
	 * This function is used to actually change some overlay data.
	 *
	 * To be overloaded into derivated class.
	 *
	 * @param array data transmitted to the server through a web form
	 * @param aray of updated attributes
	 *
	 * @see articles/edit.php
	 */
	function parse_fields($fields) {
		return $this->attributes;
	}

	/**
	 * render some page component
	 *
	 * @param string type of component to render, e.g., 'articles'
	 * @param string anchor reference, such as 'section:123'
	 * @param int page
	 * @return mixed some text, or NULL
	 *
	 * @see sections/view.php
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
	 * To be overloaded into derivated class
	 *
	 * @see articles/delete.php
	 * @see articles/edit.php
	 *
	 * @param string the action 'insert', 'update' or 'delete'
	 * @param array the hosting record
	 * @return FALSE on error, TRUE otherwise
	 */
	function remember($variant, $host) {
		return TRUE;
	}

	/**
	 * serialize overlay content
	 *
	 *
	 * @return the serialized string
	 *
	 * @see articles/edit.php
	 */
	function save() {

		foreach($this->attributes as $name => $value)
			$this->attributes[$name] = utf8::to_unicode($value);
//			$this->attributes[$name] = utf8::to_unicode(str_replace('"', '', $value);

		// just serialize
		return serialize($this->attributes);

	}

}

?>