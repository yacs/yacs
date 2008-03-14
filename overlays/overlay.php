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
 * - [code]get_fields()[/code] -- build a form to modify overlay content
 * - [code]get_id()[/code] -- to retrieve an overlay
 * - [code]get_label()[/code] -- specialize the overlaid page
 * - [code]get_text()[/code] -- use overlay data in normal pages
 * - [code]get_type()[/code] -- basic information
 * - [code]parse_fields()[/code] -- capture form content
 * - [code]remember()[/code] -- for specific post-processing steps
 *
 * Following functions are aiming to simplify external calls:
 * - [code]export()[/code] -- to generate some XML
 * - [code]save()[/code] -- to serialize overlay content
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @autor GnapZ
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
		$type = preg_replace(FORBIDDEN_STRINGS_IN_PATHS, '', strip_tags($type));

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
			$overlay =& new $type;
			$overlay->attributes = array();
			$overlay->attributes['overlay_type'] = $type;
			$overlay->attributes['overlay_parameters'] = $parameters;
			return $overlay;
		}

		// houston, we've got a problem -- Skin::error() is buggy here
		if(isset($context['with_debug']) && ($context['with_debug'] == 'Y'))
			Logger::remember('overlays/overlay.php', 'overlay::bind() unknown overlay type', $type, 'debug');
		return NULL;
	}

	/**
	 * export an overlay as XML
	 *
	 * @return some XML to be inserted into the resulting page
	 */
	function export() {
		$text .=  ' <overlay>'."\n";
		foreach($this->attributes as $label => $value) {
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
	 * display the content of one overlay in a box
	 *
	 * To be overloaded into derivated class
	 *
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_box_text($host=NULL) {
		$text = NULL;
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
	 * @see articles/fetch_for_palm.php
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
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_list_text($host=NULL) {
		$text = NULL;
		return $text;
	}

	/**
	 * display a live title
	 *
	 * To be overloaded into derivated class
	 *
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_live_title($host=NULL) {
		$text = Codes::beautify_title($host['title']);
		return $text;
	}

	/**
	 * display the content of one overlay
	 *
	 * Accepted variant codes:
	 * - 'box' - displayed in a box
	 * - 'list' - part of a list
	 * - 'title' - as a live title
	 * - 'view' - in the main viewing panel
	 *
	 * To be overloaded into derivated class
	 *
	 * @param string the variant code
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_text($variant='view', $host=NULL) {
		switch($variant) {

		// container is displayed in a small box
		case 'box':
			$text =& $this->get_box_text($host);
			return $text;

		// container is one item of a list
		case 'list':
			$text =& $this->get_list_text($host);
			return $text;

		// live title
		case 'title':
			$text =& $this->get_live_title($host);
			return $text;

		// full page of the container
		case 'view':
		default:
			$text =& $this->get_view_text($host);
			return $text;
		}
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
	 * display the content of one overlay in main view panel
	 *
	 * To be overloaded into derivated class
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

		if(!is_array($attributes) || !isset($attributes['overlay_type']))
			return NULL;

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

		// just serialize
		return serialize($this->attributes);

	}

}

?>