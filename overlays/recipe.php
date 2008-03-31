<?php
/**
 * describe one recipe
 *
 * @see overlays/overlay.php
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Recipe extends Overlay {

	/**
	 * build the list of fields for one overlay
	 *
	 * @see overlays/overlay.php
	 *
	 * @param the hosting attributes
	 * @return a list of ($label, $input, $hint)
	 */
	function get_fields($host) {
		global $context;

		// the number of plates
		$label = i18n::s('Persons to be served');
		$input = '<input type="text" name="people" value ="'.encode_field($this->attributes['people']).'" />';
		$hint = i18n::s('Try to standardize your recipes for four people');
		$fields[] = array($label, $input, $hint);

		// the time for the preparation
		$label = i18n::s('Time to prepare');
		$input = '<input type="text" name="preparation_time" value ="'.encode_field($this->attributes['preparation_time']).'" />';
		$hint = i18n::s('Do not take into account cooking time');
		$fields[] = array($label, $input, $hint);

		// the time for cooking
		$label = i18n::s('Time to cook');
		$input = '<input type="text" name="cooking_time" value ="'.encode_field($this->attributes['cooking_time']).'" />';
		$hint = i18n::s('Do not take into account time to heat the owen');
		$fields[] = array($label, $input, $hint);

		// the ingredients
		$label = i18n::s('Ingredients');
		$input = '<textarea name="ingredients" rows="6" cols="50">'.encode_field($this->attributes['ingredients']).'</textarea>';
		$hint = i18n::s('Type each ingredient on one separate line starting with a \'-\' character');
		$fields[] = array($label, $input, $hint);

		return $fields;
	}

	/**
	 * get an overlaid label
	 *
	 * Accepted action codes:
	 * - 'edit' the modification of an existing object
	 * - 'delete' the deleting form
	 * - 'new' the creation of a new object
	 * - 'view' a displayed object
	 *
	 * @see overlays/overlay.php
	 *
	 * @param string the target label
	 * @param string the on-going action
	 * @return the label to use
	 */
	function get_label($name, $action='view') {
		global $context;

		// the target label
		switch($name) {

		// description label
		case 'description':
			return i18n::s('Preparation steps');

		// help panel
		case 'help':
			if(($action == 'new') || ($action == 'edit'))
				return '<p>'.i18n::s('We are trying to standardize recipes for 4 people.').'</p>';
			return NULL;

		// page title
		case 'page_title':

			switch($action) {

			case 'edit':
				return i18n::s('Edit a recipe');

			case 'delete':
				return i18n::s('Delete a recipe');

			case 'new':
				return i18n::s('Add a recipe');

			case 'view':
			default:
				// use the article title as the page title
				return NULL;

			}
		}

		// no match
		return NULL;
	}

	/**
	 * display the content of one recipe
	 *
	 * Accepted variant codes:
	 * - 'view' - embedded into the main viewing page
	 *
	 * @see overlays/overlay.php
	 *
	 * @param string the variant code
	 * @param array the hosting record
	 * @return some HTML to be inserted into the resulting page
	 */
	function get_text($variant='view', $host=NULL) {
		global $context;

		// add something to zooming views only
		if($variant != 'view')
			return '';

		// text to return
		$text = '';

		// the number of people
		$text .= '<p>'.sprintf(i18n::s('People to be served: %s'), $this->attributes['people'])."</p>\n";

		// the preparation_time
		$text .= '<p>'.sprintf(i18n::s('Time to prepare: %s'), $this->attributes['preparation_time'])."</p>\n";

		// the cooking_time
		$text .= '<p>'.sprintf(i18n::s('Cooking time: %s'), $this->attributes['cooking_time'])."</p>\n";

		// the ingredients
		$text .= '<p>'.sprintf(i18n::s('Ingredients: %s'), BR.$this->attributes['ingredients'])."</p>\n";

		return Codes::beautify($text);
	}

	/**
	 * retrieve the content of one modified overlay
	 *
	 * @see overlays/overlay.php
	 *
	 * @param the fields as filled by the end user
	 * @return the updated fields
	 */
	function parse_fields($fields) {
		$this->attributes['people'] = isset($fields['people']) ? $fields['people'] : '';
		$this->attributes['preparation_time'] = isset($fields['preparation_time']) ? $fields['preparation_time'] : '';
		$this->attributes['cooking_time'] = isset($fields['cooking_time']) ? $fields['cooking_time'] : '';
		$this->attributes['ingredients'] = isset($fields['ingredients']) ? $fields['ingredients'] : '';

		return $this->attributes;
	}

}

?>