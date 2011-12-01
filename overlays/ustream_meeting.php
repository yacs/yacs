<?php
include_once 'event.php';

/**
 * meet on a ustream channel
 *
 * This overlay integrates the web TV facility provided by Ustream.
 * It stores the name of the channel, and integrates the beam in an iframe.
 *
 * @link http://www.ustream.tv/
 *
 * The URL is provided to meeting participants through the Join button.
 *
 * This overlay does not use the Start and Stop buttons, and the actual external meeting page
 * has to be managed separately.
 *
 * Transitions to 'started' or to 'stopped' status are provided automatically, based
 * on meeting starting date and time, and on its planned duration.
 *
 * This overlay uses following parameters:
 * - chairman
 * - number of seats
 * - URL of the meeting page
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class ustream_Meeting extends Event {

	/**
	 * get the layout for comments
	 *
	 * This function adapts the layout of comments to the internal state of the overlay.
	 *
	 * @see articles/view_as_chat.php
	 */
	function get_comments_layout_value($default_value) {
		global $context;

		switch($this->attributes['status']) {

		case 'created':
		case 'open':
		case 'lobby':
			return 'yabb';

		case 'started':
			return 'chat';

		case 'stopped':
			return 'excerpt';

		default:
			return $default_value;

		}
	}

	/**
	 * get parameters for one meeting facility
	 *
	 * @return an array of fields or NULL
	 */
	function get_event_fields() {
		global $context;

		// returned fields
		$fields = array();

		// chairman
		$label = i18n::s('Chairman');
		$input = $this->get_chairman_input();
		$fields[] = array($label, $input);

		// number of seats
		$label = i18n::s('Seats');
		$input = $this->get_seats_input();
		$hint = i18n::s('Maximum number of participants.');
		$fields[] = array($label, $input, $hint);

		// external address
		$label = i18n::s('Show name');
		$input = $this->get_meeting_id_input();
		$hint = sprintf(i18n::s('As registered at %s'), Skin::build_link('http://www.ustream.tv/', 'USTREAM', 'external'));
		$fields[] = array($label, $input, $hint);

		// embed into the form
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

		// ensure we have an address for the meeting
		if(!isset($this->attributes['meeting_id']) || !$this->attributes['meeting_id'])
			return i18n::s('No information has been set for this meeting');

		// everything is ok
		return NULL;
	}

	/**
	 * the URL to join the meeting
	 *
	 * @see overlays/events/join.php
	 *
	 * @return string the URL to redirect the user to the meeting, or NULL on error
	 */
	function get_join_url() {
		global $context;

		// lead to the external address
		if(isset($this->attributes['meeting_id']))
			return 'http://www.ustream.tv/channel/'.$this->attributes['meeting_id'];

		// tough luck
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

		switch($name.':'.$action) {

		case 'edit_command:articles':
			return i18n::s('Edit this event');

		case 'new_command:articles':
			return i18n::s('Add an event');

		case 'page_title:edit':
			return i18n::s('Edit an event');

		case 'page_title:delete':
			return i18n::s('Delete an event');

		case 'page_title:new':
			return i18n::s('Add an event');

		}

		// no match
		return NULL;
	}

	/**
	 * get an input field to capture meeting web address
	 *
	 * @return string to be integrated into the editing form
	 */
	function get_meeting_id_input() {
		global $context;

		// capture channel name
		if(!isset($this->attributes['meeting_id']))
			$this->attributes['meeting_id'] = '';
		return '<input type="text" name="meeting_id" value ="'.encode_field($this->attributes['meeting_id']).'"  size="50" maxlength="1024" />';

	}

	/**
	 * text to be displayed to page owner for the start of the meeting
	 *
	 * @see overlays/event.php
	 *
	 * @return string some instructions to page owner
	 */
	function get_start_status() {

		// remind the external link to activate to page owner
		if(isset($this->attributes['meeting_id']) && $this->attributes['meeting_id'])
			return sprintf(i18n::s('You have to activate %s externally, before the planned start of the meeting'),
				Skin::build_link('http://www.ustream.tv/channel/'.$this->attributes['meeting_id'], i18n::s('the meeting page'), 'external'));

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
	 * retrieve meeting specific parameters
	 *
	 * @see overlays/event.php
	 *
	 * @param the fields as filled by the end user
	 */
	function parse_event_fields($fields) {

		// chairman
		$this->attributes['chairman'] = isset($fields['chairman']) ? $fields['chairman'] : '';

		// seats
		$this->attributes['seats'] = isset($fields['seats']) ? $fields['seats'] : 20;

		// meeting url
		$this->attributes['meeting_id'] = isset($fields['meeting_id']) ? $fields['meeting_id'] : '';

	}

	/**
	 * a show can be stopped before its planned end
	 *
	 * @return boolean FALSE
	 */
	function with_automatic_stop() {
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