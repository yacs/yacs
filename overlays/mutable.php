<?php
/**
 * allows for external update of the content
 *
 * @see overlays/overlay.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Mutable extends Overlay {

	/**
	 * display extra content
	 *
	 * @see overlays/overlay.php
	 *
	 * @param array the hosting record
	 * @return some HTML to be inserted into the resulting page
	 */
	function get_extra_text($host=NULL) {
		global $context;

		// display main content, if any
		$text = '';
		if(isset($this->attributes['extra_content']))
			$text = Codes::beautify_extra($this->attributes['extra_content']);

		return $text;

	}

	/**
	 * preserve content across page modification
	 *
	 * @see overlays/overlay.php
	 *
	 * @param the hosting attributes
	 * @return a list of ($label, $input, $hint)
	 */
	function get_fields($host, $field_pos=NULL) {
		global $context;

		// form fields
		$fields = array();

		// item identifier
		if(!isset($this->attributes['overlay_id']))
			$this->attributes['overlay_id'] = '';

		// only associates can change the overlay id
		if(Surfer::is_associate()) { // isset($host['anchor']) && ($parent =  Anchors::get($host['anchor'])) && $parent->is_assigned()) {
			$label = i18n::s('Overlay identifier');
			$input = '<input type="text" name="overlay_id" value="'.encode_field($this->attributes['overlay_id']).'" />';
		} else {
			$label = 'hidden';
			$input = '<input type="hidden" name="overlay_id" value="'.encode_field($this->attributes['overlay_id']).'" />';
		}

		// hidden attributes
		foreach($this->attributes as $name => $value) {
			if(preg_match('/_content$/', $name))
				$input .= '<input type="hidden" name="'.encode_field($name).'" value="'.encode_field($value).'" />';
		}

		// we do have something to preserve
		$fields[] = array($label, $input);

		// job done
		return $fields;
	}

	/**
	 * identify one instance
	 *
	 * This function returns a string that identify uniquely one overlay instance.
	 * When this information is saved, it can be used later on to retrieve one page
	 * and its content.
	 *
	 * @see overlays/overlay.php
	 *
	 * @returns a unique string, or NULL
	 */
	function get_id() {

		// item identifier
		if(isset($this->attributes['overlay_id']))
			return($this->attributes['overlay_id']);

		return NULL;
	}

	/**
	 * display trailer content
	 *
	 * @see overlays/overlay.php
	 *
	 * @param array the hosting record
	 * @return some HTML to be inserted into the resulting page
	 */
	function get_trailer_text($host=NULL) {
		global $context;

		// display main content, if any
		$text = '';
		if(isset($this->attributes['trailer_content']))
			$text = Codes::beautify($this->attributes['trailer_content']);

		return $text;

	}

	/**
	 * display main content
	 *
	 * @see overlays/overlay.php
	 *
	 * @param array the hosting record
	 * @return some HTML to be inserted into the resulting page
	 */
	function get_view_text($host=NULL) {
		global $context;

		// display main content, if any
		$text = '';
		if(isset($this->attributes['view_content']))
			$text = Codes::beautify($this->attributes['view_content']);

		return $text;

	}

	/**
	 * retrieve the content of one modified overlay
	 *
	 * These are data saved into the piggy-backed overlay field of the hosting record.
	 *
	 * @see overlays/overlay.php
	 *
	 * @param the fields as filled by the end user
	 */
	function parse_fields($fields) {

		$this->attributes['extra_content'] = isset($fields['extra_content']) ? $fields['extra_content'] : '';
		$this->attributes['overlay_id'] = isset($fields['overlay_id']) ? $fields['overlay_id'] : '';
		$this->attributes['trailer_content'] = isset($fields['trailer_content']) ? $fields['trailer_content'] : '';
		$this->attributes['view_content'] = isset($fields['view_content']) ? $fields['view_content'] : '';
	}

	/**
	 * update selected attributes
	 *
	 * @param array attributes to change
	 */
	function update($fields) {

		if(isset($fields['extra_content']))
			$this->attributes['extra_content'] = $fields['extra_content'];
		elseif(isset($fields['extra_clear']))
			$this->attributes['extra_content'] = '';

		if(isset($fields['trailer_content']))
			$this->attributes['trailer_content'] = $fields['trailer_content'];
		elseif(isset($fields['trailer_clear']))
			$this->attributes['trailer_content'] = '';

		if(isset($fields['view_content']))
			$this->attributes['view_content'] = $fields['view_content'];
		elseif(isset($fields['view_clear']))
			$this->attributes['view_content'] = '';

	}

}

?>