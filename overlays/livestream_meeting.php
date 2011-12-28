<?php
include_once 'meeting.php';

/**
 * meet on a livestream channel
 *
 * This overlay integrates the web TV facility provided by Livestream.
 * It stores the name of the channel, and integrates the beam in an iframe.
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
class Livestream_Meeting extends Meeting {

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
		$label = i18n::s('Livestream channel');
		$input = $this->get_meeting_id_input();
		$hint = sprintf(i18n::s('As registered at %s'), Skin::build_link('http://www.livestream.com/', 'Livestream', 'external'));
		$fields[] = array($label, $input, $hint);

		// embed into the form
		return $fields;
	}

	/**
	 * add text to the main event page
	 *
	 * This is invoked from within get_view_text(), to support integrated streams
	 *
	 * @return text to be integrated into the resulting page
	 */
	function get_view_text_extension() {

		// nothing to display yet
		$text = '';

		// meeting is not on-going
		if($this->attributes['status'] != 'started')
			;

		// no meeting information
		elseif(!isset($this->attributes['meeting_id']) || !$this->attributes['meeting_id'])
			;

		// display the stream to people who have joined
		else {
			$text = '<iframe width="560" height="340"'
				.' src="http://cdn.livestream.com/embed/'.$this->attributes['meeting_id'].'?layout=4&amp;autoplay=false"'
				.' id="iframeplayer" style="border:0;outline:0;margin:2em 0;" frameborder="0" scrolling="no"></iframe>';

			// remember participation
			if(!$this->has_joined())
				$this->join_meeting();
		}

		// display the show within the main page
		return $text;

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
				Skin::build_link('http://www.livestream.com/'.$this->attributes['meeting_id'], i18n::s('the meeting page'), 'external'));

		// nothing to display to page owner
		return NULL;
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

}

?>