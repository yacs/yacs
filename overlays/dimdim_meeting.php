<?php
include_once 'event.php';

/**
 * meet on a Dimdim server
 *
 * This overlay integrates meeting facility provided by Dimdim.
 *
 * @link http://www.dimdim.com/
 *
 * Okay, Dimdim has been acquired by salesforce.com, and people with an account won't be able
 * to use the service after March, 2011. However, this code can be useful to integrate another
 * meeting service, as its implements a quite complicated API to authenticate people and to
 * drive people to the meeting place.
 *
 * The Start button is used to trigger the actual meeting session,
 * and the Join button drives attendees to the meeting.
 *
 * This overlay uses following parameters:
 * - chairman
 * - number of seats
 * - Dimdim credentials
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class DimDim_Meeting extends Event {

	/**
	 * get parameters for one meeting facility
	 *
	 * @see overlays/event.php
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

		// dimdim account
		$label = i18n::s('Account');
		if(!isset($this->attributes['account']))
			$this->attributes['account'] = '';
		$input = '<input type="text" name="account" value ="'.encode_field($this->attributes['account']).'" />';
		$hint = sprintf(i18n::s('Enter a valid %s account'), Skin::build_link('http://www.dimdim.com/', 'DimDim', 'basic'));
		$fields[] = array($label, $input, $hint);

		// dimdim password
		$label = i18n::s('Password');
		if(!isset($this->attributes['password']))
			$this->attributes['password'] = '';
		$input = '<input type="text" name="password" value ="'.encode_field($this->attributes['password']).'" />';
		$fields[] = array($label, $input);

		// add these tabs
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
		global $context;

		// almost random passwords
		$this->initialize_passwords();

		// link to create a meeting
		$url = 'https://my.dimdim.com/api/conf/join_meeting';

		// provide authentication token
		$headers = 'X-Dimdim-Auth-Token: '.$this->attributes['token'].CRLF;

		// parameters to create a meeting
		$parameters = array();
		$parameters['authToken'] = $this->attributes['token'];
		$parameters['account'] = $this->attributes['account'];
		$parameters['clientId'] = Surfer::get_id();
		$parameters['displayName'] = Surfer::get_name();
		$parameters['meetingKey'] = $this->attendee_password;

		// encode in json
		$data = array('request' => Safe::json_encode($parameters));

		// do authenticate
		if($response = http::proceed($url, $headers, $data, 'overlays/dimdim_meeting.php')) {

			// successful authentication
			$output = Safe::json_decode($response);
			if(isset($output['result']) && $output['result']) {

				// enter the meeting room
				return 'https://my.dimdim.com/redirect?clientId='.urlencode(Surfer::get_id()).'&amp;account='.urlencode($this->attributes['account']);
			}
		}

		// don't know where to go
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
			return i18n::s('Edit this meeting');

		case 'new_command:articles':
			return i18n::s('Add a meeting');

		case 'page_title:edit':
			return i18n::s('Edit a meeting');

		case 'page_title:delete':
			return i18n::s('Delete a meeting');

		case 'page_title:new':
			return i18n::s('Add a meeting');

		}

		// no match
		return NULL;
	}

	/**
	 * the URL to start and to join the meeting
	 *
	 * @see overlays/events/start.php
	 *
	 * @return string the URL to redirect the user to the meeting, or NULL on error
	 */
	function get_start_url() {
		global $context;

		// almost random passwords
		$this->initialize_passwords();

		// link to authenticate
		$url = 'https://my.dimdim.com/api/auth/login';

		// parameters to authenticate
		$parameters = array();
		$parameters['account'] = $this->attributes['account'];
		$parameters['password'] = $this->attributes['password'];
		$parameters['group'] = 'all';

		// encode in json
		$data = array('request' => Safe::json_encode($parameters));

		// do authenticate
		if($response = http::proceed($url, '', $data, 'overlays/dimdim_meeting.php')) {

			// successful authentication
			$output = Safe::json_decode($response);
			if(isset($output['result']) && $output['result']) {

				// remember the authentication token
				$fields = array('token' => $output['response']['authToken']);
				$this->set_values($fields);

				// link to create a meeting
				$url = 'https://my.dimdim.com/api/conf/start_meeting';

				// provide authentication token
				$headers = 'X-Dimdim-Auth-Token: '.$this->attributes['token'].CRLF;

				// parameters to create a meeting
				$parameters = array();
				$parameters['authToken'] = $this->attributes['token'];
				$parameters['account'] = $this->attributes['account'];
				$parameters['clientId'] = Surfer::get_id();
				$parameters['displayName'] = Surfer::get_name();
				$parameters['meetingName'] = $this->anchor->get_title();
				$parameters['roomName'] = $this->anchor->get_title();
				$parameters['meetingLengthMinutes'] = $this->attributes['duration'];
				$parameters['attendeeKey'] = $this->attendee_password;
				$parameters['assistantEnabled'] = 'false';

				// disable some features
				$parameters['displayDialInfo'] = 'false';

				// message displayed within the BigBlueButton session
				$welcome = '';

				// meeting title
				if(is_object($this->anchor))
					$welcome .= sprintf(i18n::s('%s: %s'), i18n::s('Title'), $this->anchor->get_title())."\n";

				// meeting date
				if(isset($this->attributes['date_stamp']))
					$welcome .= sprintf(i18n::s('%s: %s'), i18n::s('Date'), Skin::build_date($this->attributes['date_stamp'], 'standalone'))."\n";

				// meeting duration
				if(isset($this->attributes['duration']))
					$welcome .= sprintf(i18n::s('%s: %s'), i18n::s('Duration'), $this->attributes['duration'].' '.i18n::s('minutes'))."\n";

				// build a link to the owner page, if any
				if(is_object($this->anchor) && ($user =& Users::get($this->anchor->get_value('owner_id'))))
					$welcome .= sprintf(i18n::s('%s: %s'), i18n::s('Chairman'), $user['full_name'])."\n";

				// welcome message
				$parameters['agenda'] = $welcome;

				// return URL
				if(is_callable(array($this->anchor, 'get_url')))
					$parameters['returnurl'] = $context['url_to_home'].$context['url_to_root'].$this->anchor->get_url();

				// encode in json
				$data = array('request' => Safe::json_encode($parameters));

				// do the transaction
				if($response = http::proceed($url, $headers, $data, 'overlays/dimdim_meeting.php')) {

					// successful transaction
					$output = Safe::json_decode($response);
					if(isset($output['result']) && $output['result']) {

						// redirect to the target page
						return 'https://my.dimdim.com/redirect?clientId='.urlencode(Surfer::get_id()).'&amp;account='.urlencode($this->attributes['account']);
					}
				}
			}
		}

		// don't know where to go
		return NULL;

	}

	/**
	 * initialize passwords for this instance
	 *
	 */
	private function initialize_passwords() {
		global $context;

		// build moderator and attendees passwords
		$buffer = $this->attributes['id'];
		$this->moderator_password = dechex(crc32($buffer.'moderator'));
		$this->attendee_password = dechex(crc32($buffer.'attendee'));

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

		// dimdim account to use for this meeting
		$this->attributes['account'] = isset($fields['account']) ? $fields['account'] : 'your_dimdim_id';
		$this->attributes['password'] = isset($fields['password']) ? $fields['password'] : 'your_dimdim_password';

	}

	/**
	 * DimDim meetings start on demand
	 *
	 * @return boolean FALSE
	 */
	function with_automatic_start() {
		return FALSE;
	}

	/**
	 * DimDim meetings stop on demand
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
