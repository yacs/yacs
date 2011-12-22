<?php
include_once 'meeting.php';

/**
 * manage a chat meeting
 *
 * This overlay must be used jointly with page option 'view_as_chat'.
 *
 * A chat meeting is a page where comments are laid out according to meeting status:
 * - 'yabb' is used before the meeting, to capture and to answer questions,
 * or to provide instructions to participants
 * - 'chat' is used during the meeting itself, to provide a real-time interaction facility
 * - 'excerpt' is used after the meeting, to report on past interactions
 *
 * The Start button triggers the actual chat, while the Stop button terminates the meeting
 * and locks all comments.
 *
 * There is no Join button, since the event page is also the place where the chat is taking place.
 *
 * This overlay uses following parameters:
 * - chairman
 * - number of seats
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Chat_meeting extends Meeting {

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

			// no specific rule for enrolment
			if(!isset($this->attributes['enrolment']))
				return 'chat';

			// chat is opened to any visitor --confirm participation of surfer
			if($this->attributes['enrolment'] == 'none') {
				if(!$this->has_joined())
					$this->join_meeting();
				return 'chat';
			}

			// surfer has not applied
			if(!$enrolment = enrolments::get_record($this->anchor->get_reference()))
				return 'excluded';

			// registration has not been approved
			if(!isset($enrolment['approved']) || ($enrolment['approved'] != 'Y'))
				return 'excluded';

			// surfer is allowed to participate to restricted chat
			if(!$this->has_joined())
				$this->join_meeting();
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

		// embed into the form
		return $fields;
	}

	/**
	 * the URL to start and to join the event
	 *
	 * @see overlays/events/start.php
	 *
	 * @return string the URL to redirect the user to the meeting, or NULL on error
	 */
	function get_start_url() {
		global $context;

		// redirect to the main page
		if(is_object($this->anchor))
			return $context['url_to_home'].$context['url_to_root'].$this->anchor->get_url();

		// problem, darling!
		return NULL;
	}

	/**
	 * retrieve meeting specific parameters
	 *
	 * @param the fields as filled by the end user
	 */
	function parse_event_fields($fields) {

		// chairman
		$this->attributes['chairman'] = isset($fields['chairman']) ? $fields['chairman'] : '';

		// seats
		$this->attributes['seats'] = isset($fields['seats']) ? $fields['seats'] : 20;

	}

	/**
	 * chat meetings start on demand
	 *
	 * @return boolean FALSE
	 */
	function with_automatic_start() {
		return FALSE;
	}

	/**
	 * chat meetings stop on demand
	 *
	 * @return boolean FALSE
	 */
	function with_automatic_stop() {
		return FALSE;
	}
}

?>
