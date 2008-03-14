<?php
/**
 * contact information
 *
 * This overlay is aiming to capture contact information related to individuals.
 * It also shows how to put the information in a separate table of the database.
 *
 * It can be derived and transformed to accomodate any variation.
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author Agn&egrave;s Rambaud
 * @author GnapZ
 * @author Geasm
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Contact extends Overlay {

	/**
	 * get one record from the dedicated table
	 *
	 * @param int record id
	 * @return the resulting $row array
	 */
	function &get($id) {
		global $context;

		// sanity check
		if(!$id) {
			$output = NULL;
			return $output;
		}

		// select among available items -- exact match
		$query = "SELECT * FROM ".SQL::table_name('contacts')." AS contacts"
			." WHERE (contacts.id LIKE '".SQL::escape($id)."')";
		$output =& SQL::query_first($query);
		return $output;
	}

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

		// a list of fields
		$fields = array();

		// mail address
		$label = i18n::s('Mail address:');
		if(!isset($this->attributes['mail']) || !$this->attributes['mail'])
			$this->attributes['mail'] = '';
		$input = '<textarea name="mail" rows="5" cols="50">'.encode_field($this->attributes['mail']).'</textarea>';
		$hint = i18n::s('This field is hardcoded');
		$fields[] = array($label, $input, $hint);

		// main phone number
		$label = i18n::s('Main phone number:');
		if(!isset($this->attributes['phone_number']) || !$this->attributes['phone_number'])
			$this->attributes['phone_number'] = '';
		$input = '<input type="text" name="phone_number" size="20" value ="'.encode_field($this->attributes['phone_number']).'" maxlength="20" />';
		$hint = i18n::s('Use the international notation, starting with a + sign');
		$fields[] = array($label, $input, $hint);

		// alternate phone number
		$label = i18n::s('Alternate phone number:');
		if(!isset($this->attributes['alternate_number']) || !$this->attributes['alternate_number'])
			$this->attributes['alternate_number'] = '';
		$input = '<input type="text" name="alternate_number" size="20" value ="'.encode_field($this->attributes['alternate_number']).'" maxlength="20" />';
		$hint = i18n::s('Use the international notation, starting with a + sign');
		$fields[] = array($label, $input, $hint);

		// e-mail address
		$label = i18n::s('Email address:');
		if(!isset($this->attributes['e_mail']) || !$this->attributes['e_mail'])
			$this->attributes['e_mail'] = '';
		$input = '<input type="text" name="e_mail" size="30" value ="'.$this->attributes['e_mail'].'" maxlength="255" />';
		$hint = i18n::s('Type a valid e-mail address');
		$fields[] = array($label, $input, $hint);

		// web address
		$label = i18n::s('Web address:');
		if(!isset($this->attributes['web']) || !$this->attributes['web'])
			$this->attributes['web'] = '';
		$input = '<input type="text" name="web" size="30" value ="'.$this->attributes['web'].'" maxlength="255" />';
		$hint = i18n::s('Type a valid web address (http://...)');
		$fields[] = array($label, $input, $hint);

		return $fields;
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
		if(isset($this->attributes['id']))
			return ':contact:'.$this->attributes['id'];
		return NULL;
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
			return i18n::s('Additional data');

		// page title
		case 'page_title':

			switch($action) {

			case 'edit':
				return i18n::s('Edit one contact');

			case 'delete':
				return i18n::s('Delete one contact');

			case 'new':
				return i18n::s('New contact');

			case 'view':
			default:
				// use the article title as the page title
				return NULL;

			}

		// title label
		case 'title':
			return i18n::s('Contact');

		// title hint
		case 'title_hint':
			return i18n::s('Full name');

		}

		// no match
		return NULL;
	}

	/**
	 * display the content of one record
	 *
	 * Accepted variant codes:
	 * - 'box' - displayed in a box
	 * - 'list' - part of a list
	 * - 'view' - in the main viewing panel
	 *
	 * @see overlays/overlay.php
	 *
	 * @param string the on-going action
	 * @param array the hosting record
	 * @return some HTML to be inserted into the resulting page
	 */
	function get_text($variant='view', $host=NULL) {
		global $context;

		switch($variant) {

		// nothing in a box
		case 'box':
			return NULL;

		// in a list of items, show only the address
		case 'list';
			return '<div class="overlay">'.str_replace("\n", BR, $this->attributes['mail']).'</div>';

		// default case
		case 'view':
		default:

			// build a table
			$rows = array();

			// address
			if(isset($this->attributes['mail']) && $this->attributes['mail'])
				$rows[] = array(i18n::s('Mail address'), str_replace("\n", '<br />', $this->attributes['mail']));

			// phone number
			if(($variant == 'view') && isset($this->attributes['phone_number']) && $this->attributes['phone_number'])
				$rows[] = array(i18n::s('Phone number'), $this->attributes['phone_number']);

			// alternate number
			if(($variant == 'view') && isset($this->attributes['alternate_number']) && $this->attributes['alternate_number'])
				$rows[] = array(i18n::s('Alternate number'), $this->attributes['alternate_number']);

			// e-mail
			if(($variant == 'view') && isset($this->attributes['e_mail']) && $this->attributes['e_mail'])
				$rows[] = array(i18n::s('E-mail'), Skin::build_link('mailto:'.$this->attributes['e_mail'], $this->attributes['e_mail'], 'email'));

			// web
			if(($variant == 'view') && isset($this->attributes['web']) && $this->attributes['web'])
				$rows[] = array(i18n::s('Web'), Skin::build_link($this->attributes['web'], $this->attributes['web']));

			// finalize the table
			return Skin::table(NULL, $rows, 'overlay');

		}
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

		$this->attributes['alternate_number'] = isset($fields['alternate_number']) ? $fields['alternate_number'] : '';
		$this->attributes['e_mail'] = isset($fields['e_mail']) ? $fields['e_mail'] : '';
		$this->attributes['mail'] = isset($fields['mail']) ? $fields['mail'] : '';
		$this->attributes['phone_number'] = isset($fields['phone_number']) ? $fields['phone_number'] : '';
		$this->attributes['web'] = isset($fields['web']) ? $fields['web'] : '';

		return $this->attributes;
	}

	/**
	 * remember an action once it's done
	 *
	 * Following actions are recognized:
	 * - 'insert' - insert a new record in the side table
	 * - 'update' - update an existing record
	 * - 'delete' - suppress a record in the database
	 *
	 * To enforce database consistency, and in case of 'update' the function
	 * deletes the record and create it again.
	 *
	 * @param string the action 'insert', 'update' or 'delete'
	 * @param array the hosting record
	 * @return FALSE on error, TRUE otherwise
	 */
	function remember($variant, $host) {
		global $context;

		// id cannot be empty
		if(!$host['id'] || !is_numeric($host['id']))
			return;

		// article title is also contact full name
		if(isset($host['full_name']))
			$title = $host['full_name'];
		else
			$title = $host['title'];

		// set default values for this editor
		$this->attributes = Surfer::check_default_editor($this->attributes);

		// build the update query
		switch($variant) {

		// delete a record
		case 'delete':
			$query = "DELETE FROM ".SQL::table_name('contacts')." WHERE id = ".SQL::escape($host['id']);
			SQL::query($query);
			return TRUE;

		// delete the record, then re-create it -- to survive database inconsistencies
		case 'update':
			$query = "DELETE FROM ".SQL::table_name('contacts')." WHERE id = ".SQL::escape($host['id']);
			SQL::query($query);

		// in sert a new record in the database
		case 'insert':
			$query = "INSERT INTO ".SQL::table_name('contacts')." SET \n"
				."id='".SQL::escape($host['id'])."', \n"
				."alternate_number='".SQL::escape($this->attributes['alternate_number'])."', \n"
				."anchor='".SQL::escape(isset($host['self_reference']) ? $host['self_reference'] : '')."', \n"
				."anchor_url='".SQL::escape(isset($host['self_url']) ? $host['self_url'] : '')."', \n"
				."e_mail='".SQL::escape($this->attributes['e_mail'])."', \n"
				."mail='".SQL::escape($this->attributes['mail'])."', \n"
				."phone_number='".SQL::escape($this->attributes['phone_number'])."', \n"
				."title='".SQL::escape($title)."', \n"
				."web='".SQL::escape($this->attributes['web'])."', \n"
				."edit_name='".SQL::escape($this->attributes['edit_name'])."', \n"
				."edit_id='".SQL::escape($this->attributes['edit_id'])."', \n"
				."edit_address='".SQL::escape($this->attributes['edit_address'])."', \n"
				."edit_action='create', \n"
				."edit_date='".SQL::escape($this->attributes['edit_date'])."'";
			return SQL::query($query);

		}

		// unknown action
		return TRUE;
	}

	/**
	 * create tables for classic_cars
	 *
	 */
	function setup() {
		global $context;

		$fields = array();
		$fields['id']			= "MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT";
		$fields['alternate_number'] = "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['anchor']		= "VARCHAR(64) DEFAULT 'section:1' NOT NULL";				// up to 64 chars
		$fields['anchor_url']	= "VARCHAR(255) DEFAULT '' NOT NULL";						// up to 255 chars
		$fields['e_mail']		= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['edit_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";						// item modification
		$fields['edit_id']		= "MEDIUMINT DEFAULT 1 NOT NULL";
		$fields['edit_address'] = "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_action']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_date']	= "DATETIME";
		$fields['mail'] 		= "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['phone_number'] = "VARCHAR(255) DEFAULT '' NOT NULL";
		$fields['title']		= "VARCHAR(255) DEFAULT '' NOT NULL";						// up to 255 chars
		$fields['web']			= "VARCHAR(255) DEFAULT '' NOT NULL";

		$indexes = array();
		$indexes['PRIMARY KEY'] 	= "(id)";
		$indexes['INDEX title'] 	= "(title(255))";
		$indexes['INDEX edit_id']	= "(edit_id)";
		$indexes['INDEX edit_date'] = "(edit_date)";

		return SQL::setup_table('contacts', $fields, $indexes);
	}

}

?>