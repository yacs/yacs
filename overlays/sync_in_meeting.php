<?php
include_once 'event.php';

/**
 * collaborate to some common text
 *
 * This overlay integrates collaboraton facility provided by sync.in
 *
 * @link http://sync.in/
 *
 * The Start button is used to trigger the actual meeting session, and the Stop button kills it.
 * The Join button drives attendees to the collaboration session.
 *
 * The overlays drives the back-end service using the regular Etherpad API. This enables
 * a very high level of integration into yacs:
 * - Meeting is started and stopped by page owner, using buttons on yacs page.
 * - Names of participants are those used into the yacs server, and people don't have to enter their
 * identity or credentials to join the meeting after they have been authenticated by yacs.
 *
 * This overlay uses following parameters:
 * - chairman
 * - number of seats
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class sync_in_Meeting extends Event {

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
	 * get name of etherpad server
	 *
	 * @return string host name of the target server
	 */
	function get_hostname() {

		// the server that is providing the service to us
		return 'sync.in';

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

		// link to join the meeting
		$url = 'http://'.$this->get_hostname().'/'.$this->attributes['meeting_id'];

		// re-use surfer name in collaboration session
		if($name = Surfer::get_name())
			$url .= '?displayName='.urlencode($name);

		return $url;
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
	 * the URL to start the meeting
	 *
	 * @see overlays/events/join.php
	 *
	 * @return string the URL to redirect the user to the meeting, or NULL on error
	 */
	function get_start_url() {
		global $context;

/* this does not work as expected

		// link to create the meeting
		$url = 'http://'.$this->get_hostname().'/ep/pad/create';

		$data = array();
		$data['padId'] = $this->attributes['meeting_id'];

		// do create the meeting
		if($response = http::proceed($url, NULL, $data, 'overlays/sync_in_meeting.php')) {

			// link to import data
			$url = 'http://'.$this->get_hostname().'/ep/pad/impexp/import';

			// prepare some content
			$headers = array();
			$headers[] = 'Content-Type: multipart/form-data; boundary=---------------------------955793517264236671351016652';

			// initial content
			$data = '-----------------------------955793517264236671351016652'."\n"
				.'Content-Disposition: form-data; name="file"; filename="imported.txt"'."\n"
				.'Content-Type: text/plain'."\n"
				."\n"
				.'ga bu zo meu'."\n"
				."\n"
				.'-----------------------------955793517264236671351016652'."\n"
				.'Content-Disposition: form-data; name="padId"'."\n"
				."\n"
				.$this->attributes['meeting_id']."\n"
				."\n"
				.'-----------------------------955793517264236671351016652--'."\n";

			// don't stop on error
			http::proceed($url, $headers, $data, 'overlays/sync_in_meeting.php');

		}

*/

		// link to join the meeting
		$url = 'http://'.$this->get_hostname().'/ep/pad/create?padId='.urlencode($this->attributes['meeting_id']);

		// re-use surfer name in collaboration session
		if($name = Surfer::get_name())
			$url .= '&displayName='.urlencode($name);

		return $url;
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
	 * the URL to stop and to leave a meeting
	 *
	 * @see overlays/events/stop.php
	 *
	 * @return string the URL to redirect the user from the meeting, or NULL on error
	 */
	function get_stop_url() {
		global $context;

		// export pad content
		$url = 'http://'.$this->get_hostname().'/ep/pad/export/'.$this->attributes['meeting_id'].'/latest?format=html';

		// we don't care about the return code
		if(($result = http::proceed($url)) && preg_match('|\<body[^>]*>(.*)</body[^>]*>|isU', $result, $matches)) {

			// put the text of the pad in a comment
			include_once $context['path_to_root'].'comments/comments.php';
			$fields = array();
			$fields['anchor'] = $this->anchor->get_reference();
			$fields['description'] = $matches[1];
			$fields['type'] = 'notification';
			Comments::post($fields);

		}

		// back to the yacs page
		if(is_callable(array($this->anchor, 'get_url')))
			return $context['url_to_home'].$context['url_to_root'].$this->anchor->get_url();

		// this should not happen
		return NULL;
	}

	/**
	 * initialize this instance
	 *
	 * @see overlays/overlay.php
	 *
	 */
	function initialize() {
		global $context;

		// use some unique id
		if(!isset($this->attributes['meeting_id']))
			$this->attributes['meeting_id'] = md5(mt_rand());

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

	}

	/**
	 * sessions start on demand
	 *
	 * @return boolean FALSE
	 */
	function with_automatic_start() {
		return FALSE;
	}

	/**
	 * sessions stop on demand
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