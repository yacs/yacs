<?php
include_once 'event.php';

/**
 * describe one specific day
 *
 * This overlay streamlines the setup of bare events, such as concerts, etc.
 * The assumption is that most information related to such events will be managed outside
 * the web environment. Therefore, data management is really minimum, and the user
 * interface is simplified to the maximum extend.
 *
 * Transitions to 'started' or to 'stopped' status are provided automatically, based
 * on meeting starting date and time, and on its planned duration.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Day extends Event {


	/**
	 * get parameters for one meeting facility
	 *
	 * @return an array of fields or NULL
	 */
	function get_event_fields() {
		global $context;

		// returned fields
		$fields = array();
		return $fields;
	}

	/**
	 * text to be displayed to page owner
	 *
	 * @see overlays/event.php
	 *
	 * @return string some instructions to page owner
	 */
	function get_event_status() {
		return NULL;
	}

	/**
	 * get form fields to change the day
	 *
	 * @see overlays/overlay.php
	 *
	 * @param array hosting attributes
	 * @return a list of ($label, $input, $hint)
	 */
	function get_fields($host) {
		global $context;

		$options = '<input type="hidden" name="time_stamp" value="12:00" />'
			.'<input type="hidden" name="duration" value="1440" />';

		// default value is now
		if(!isset($this->attributes['date_stamp']) || ($this->attributes['date_stamp'] <= NULL_DATE))
			$this->attributes['date_stamp'] = gmstrftime('%Y-%m-%d %H:%M', time() + (Surfer::get_gmt_offset() * 3600));

		// adjust to surfer time zone
		else
			$this->attributes['date_stamp'] = Surfer::from_GMT($this->attributes['date_stamp']);

		// split date from time
		list($date, $time) = explode(' ', $this->attributes['date_stamp']);

		// event time
		$label = i18n::s('Date');
		$input = Skin::build_input('date_stamp', $date, 'date').$options;
		$hint = i18n::s('Use format YYYY-MM-DD');
		$fields[] = array($label, $input, $hint);

		// ensure that we do have a date
		$context['page_footer'] .= JS_PREFIX
			.'// ensure that some overlay fields are not empty'."\n"
			.'func'.'tion validateOnSubmit(container) {'."\n"
			."\n"
			.'	if(!Yacs.trim(container.date_stamp.value)) {'."\n"
			.'		alert("'.i18n::s('Please provide a date.').'");'."\n"
			.'		container.date_stamp.focus();'."\n"
			.'		Yacs.stopWorking();'."\n"
			.'		return false;'."\n"
			.'	}'."\n\n"
			.'	return true;'."\n"
			.'}'."\n"
			."\n"
			.JS_SUFFIX;

		return $fields;
	}

	/**
	 * the URL to join the meeting
	 *
	 * @see overlays/events/join.php
	 *
	 * @return string the URL to redirect the user to the meeting, or NULL on error
	 */
	function get_join_url() {
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

		// edit command
		case 'edit_command':
			return i18n::s('Edit this meeting');
			break;

		// new command
		case 'new_command':
			return i18n::s('Add a meeting');
			break;

		// page title
		case 'page_title':

			switch($action) {

			case 'edit':
				return i18n::s('Edit a meeting');

			case 'delete':
				return i18n::s('Delete a meeting');

			case 'new':
				return i18n::s('New meeting');

			case 'view':
			default:
				// use article title as the page title
				return NULL;

			}
			break;
		}

		// no match
		return NULL;
	}

	/**
	 * get an input field to capture meeting web address
	 *
	 * @return string to be integrated into the editing form
	 */
	function get_meeting_url_input() {
		global $context;

		return NULL;

	}

	/**
	 * text to be displayed to page owner for the start of the meeting
	 *
	 * @see overlays/event.php
	 *
	 * @return string some instructions to page owner
	 */
	function get_start_status() {

		// nothing to display to page owner
		return NULL;
	}

	/**
	 * get a label for a given status code
	 *
	 * @param string the status code
	 * @return string the label to display
	 */
	function get_status_label($status) {
		global $context;

		switch($status) {
		case 'created':
		default:
			return i18n::s('Meeting is under preparation');

		case 'open':
			return i18n::s('Enrolment is open');

		case 'lobby':
			return i18n::s('Meeting has not started yet');

		case 'started':
			return i18n::s('Meeting has started');

		case 'stopped':
			return i18n::s('Meeting is over');

		}
	}

	/**
	 * add some tabs
	 *
	 * @see overlays/overlay.php
	 *
	 * @param string the on-going action
	 * @param array the hosting record
	 * @return an empty array
	 */
	function &get_tabs($variant='view', $host=NULL) {
		global $context, $local;

		// returned tabs
		$tabs = array();
		return $tabs;
	}

	/**
	 * display the content of one instance
	 *
	 * @see overlays/overlay.php
	 *
	 * @param array the hosting record
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_view_text($host=NULL) {
		global $context;

		$text = '';
		return $text;
	}

	/**
	 * retrieve meeting specific parameters
	 *
	 * @see overlays/event.php
	 *
	 * @param the fields as filled by the end user
	 */
	function parse_event_fields($fields) {
	}

	/**
	 * an external meeting can be stopped before its planned end
	 *
	 * @return boolean FALSE
	 */
	function with_automatic_stop() {
		return FALSE;
	}

	/**
	 * should we manage enrolment?
	 *
	 * @return boolean TRUE to manage the full list of participants, FALSE otherwise
	 *
	 */
	function with_enrolment() {
		return FALSE;
	}

	/**
	 * should we open a separate window for the joigning place?
	 *
	 * @return boolean TRUE
	 */
	function with_new_window() {
		return TRUE;
	}

}

?>