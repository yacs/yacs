<?php
include_once 'meeting.php';

/**
 * meet on a BigBlueButton server
 *
 * This overlay integrates meeting facility provided by a BigBlueButton server.
 * It is adapted to the BigBlueButton API version 0.8.
 *
 * @link http://bigbluebutton.org/
 *
 * The Start button is used to trigger the actual meeting session, and the Stop button kills it.
 * The Join button drives attendees to the meeting.
 *
 * The overlays drives the back-end service using the regular BigBlueButton API. This enables
 * a very high level of integration into yacs:
 * - Meeting is started and stopped by page owner, using buttons on yacs page.
 * - If files have been added to the page prior the meeting, they are uploaded automatically to the web conference
 * - Names of participants are those used into the yacs server, and people don't have to enter their
 * identity or credentials to join the meeting after they have been authenticated by yacs.
 * - Details of the meeting (title, schedule, chairman) are displayed into the chat area.
 * - When participants leave the meeting they are driven back to the yacs page.
 *
 * This overlay uses following parameters:
 * - chairman
 * - number of seats
 *
 * The host name of the BigBlueButton back-end has to be entered into the dedicated configuration
 * panel.
 *
 * @see overlays/bbb_meetings/configure.php
 *
 * This configuration panel is integrated into the control panel through the hooking
 * file at overlays/bbb_meetings/hook.php
 *
 * @see overlays/bbb_meetings/hook.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class BBB_Meeting extends Meeting {

	/**
	 * allow or block operations
	 *
	 * @param string the kind of item to handle
	 * @param string the foreseen operation ('edit', 'new', ...)
	 * @return TRUE if the operation is accepted, FALSE otherwise
	 */
	function allows($type, $action) {
		global $context;

		// we filter only file downloads
		if($type != 'file')
			return TRUE;
		if($action != 'fetch')
			return TRUE;

		// reject download if caller is not bigbluebutton server
		if(isset($_SERVER['HTTP_HOST']) && isset($context['bbb_server']) && ($_SERVER['HTTP_HOST'] == $context['bbb_server']))
			return TRUE;

		// blocked
		return FALSE;
	}

	/**
	 * build a secured link to the server
	 *
	 * @param string the API action (e.g., 'create', 'join', 'end')
	 * @param mixed the query string
	 * @return string the safe URL to be used
	 */
	function build_link($action, $parameters) {
		global $context;

		// default salt
		if(!isset($context['bbb_salt']))
			$context['bbb_salt'] = '';

		// link to the server
		$url = 'http://'.$context['bbb_server'].'/bigbluebutton/api/'.$action;

		// build a string of parameters
		if(is_array($parameters))
			$parameters = join($parameters, '&');

		// add security salt
		if($context['bbb_salt'])
			$parameters .= '&checksum='.sha1($action.$parameters.$context['bbb_salt']);

		// job done
		return $url.'?'.$parameters;
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
	 * the URL to join the meeting
	 *
	 * @see overlays/events/join.php
	 *
	 * @return string the URL to redirect the user to the meeting, or NULL on error
	 */
	function get_join_url() {
		global $context;

		// parameters to join the meeting
		$parameters = array();

		// use page id as meeting id
		$parameters[] = 'meetingID='.urlencode($this->attributes['id']);

		// surfer name, as authenticated by yacs
		$parameters[] = 'fullName='.urlencode(Surfer::get_name());

		// almost random passwords
		$this->initialize_passwords();

		// join as a moderator or not
		if(isset($this->anchor) && $this->anchor->is_owned())
			$parameters[] = 'password='.urlencode($this->moderator_password);
		else
			$parameters[] = 'password='.urlencode($this->attendee_password);

		// link to join the meeting
		$url = $this->build_link('join', $parameters);
		return $url;
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

		// parameters to create a meeting
		$parameters = array();

		// use page id as meeting id
		$parameters[] = 'name='.urlencode($this->attributes['id']);
		$parameters[] = 'meetingID='.urlencode($this->attributes['id']);

		// surfer name, as authenticated by yacs
		$parameters[] = 'fullName='.urlencode(Surfer::get_name());

		// moderator password
		$parameters[] = 'moderatorPW='.urlencode($this->moderator_password);

		// participant password
		$parameters[] = 'attendeePW='.urlencode($this->attendee_password);

		// ensure that the bridge number fits in the dialing plan
		$parameters[] = 'voiceBridge='.urlencode(substr('7'.$this->attributes['id'].'1234', 0, 5));

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
		if(is_object($this->anchor) && ($user = Users::get($this->anchor->get_value('owner_id'))))
			$welcome .= sprintf(i18n::s('%s: %s'), i18n::s('Chairman'), $user['full_name'])."\n";

		// welcome message
		$parameters[] = 'welcome='.urlencode($welcome);

		// return URL
		if(is_callable(array($this->anchor, 'get_url')))
			$parameters[] = 'logoutURL='.urlencode($context['url_to_home'].$context['url_to_root'].$this->anchor->get_url());

		// should we record this session?
		if($this->with_session_recording()) {
			$parameters[] = 'record=true';
			$parameters[] = 'duration=125'; // 2 hours max per recording
		}

		// link to create the meeting
		$url = $this->build_link('create', $parameters);

		// list most recent files that have been attached to this page
		$headers = NULL;
		$body = NULL;
		if(is_object($this->anchor) && ($files = Files::list_by_date_for_anchor($this->anchor->get_reference(), 0, 5, 'raw'))) {
			$headers = array("Content-Type: text/xml");

			// instruct the presentation module to pre-load these files
			$body = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
				.'<modules>'."\n"
				.'	<module name="presentation">'."\n";

			// list web address of each file
			foreach($files as $file)
				$body .= "\t\t".'<document'
					.' url="'.$context['url_to_home'].$context['url_to_root'].utf8::to_xml(Files::get_url($file['id'], 'fetch', $file['file_name'])).'"'
					.' name="'.utf8::to_xml($file['file_name']).'"'
					.' />'."\n";

			// end of the list of files
			$body .= '   </module>'."\n"
				.'</modules>'."\n";

		}

		// do create the meeting
		if(($response = http::proceed_natively($url, $headers, $body))
				&& ($xml = simplexml_load_string($response))
				&& ($xml->returncode == 'SUCCESS')) {

			// parameters to join the meeting
			$parameters = array();

			// use page id as meeting id
			$parameters[] = 'meetingID='.urlencode($xml->meetingID);

			// surfer name, as authenticated by yacs
			$parameters[] = 'fullName='.urlencode(Surfer::get_name());

			// moderator password
			$parameters[] = 'password='.urlencode($xml->moderatorPW);

			// link to join the meeting
			$url = $this->build_link('join', $parameters);
			return $url;
		}

		// problem, darling!
		return NULL;
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

		// almost random passwords
		$this->initialize_passwords();

		// parameters to end the meeting
		$parameters = array();
		$parameters[] = 'meetingID='.urlencode($this->attributes['id']);
		$parameters[] = 'password='.urlencode($this->moderator_password);
		$url = $this->build_link('end', $parameters);

		// we don't care about the return code
		http::proceed($url);

		// back to the yacs page
		if(is_callable(array($this->anchor, 'get_url')))
			return $context['url_to_home'].$context['url_to_root'].$this->anchor->get_url();

		// this should not happen
		return NULL;
	}

	/**
	 * display available recording, if any
	 *
	 * This function retrieves the list of available recordings from the BBB server,
	 * and displays it to the surfer.
	 */
	function get_view_text_extension() {
		global $context;

		// parameters to list recordings
		$parameters = array();
		$parameters[] = 'meetingID='.urlencode($this->attributes['id']);

		// link to list recordings
		$url = $this->build_link('getRecordings', $parameters);

		// query the BBB back-end
		if(($response = http::proceed_natively($url))
				&& ($xml = simplexml_load_string($response))
				&& ($xml->returncode == 'SUCCESS')) {

				// ok, we have some recording available
				if($recordings = $xml->recordings->children()) {

					// enumerate recordings, but use only the first one
					foreach($recordings as $name => $node) {

						// sanity checks
						if($name != 'recording')
							continue;

						if(!isset($node->playback))
							continue;
						if(!isset($node->playback->format))
							continue;
						if(!isset($node->playback->format->url))
							continue;

						// a button to start the replay in a separate window
						$menu = array();
						$menu[] = Skin::build_link((string)$node->playback->format->url, 'Play the presentation', 'tee');
						return Skin::build_box('Presentation', Skin::finalize_list($menu, 'menu_bar'));
					}

				}

		}

	}

	/**
	 * initialize this instance
	 *
	 * @see overlays/bbb_meetings/configure.php
	 * @see overlays/overlay.php
	 *
	 */
	function initialize() {
		global $context;

		// load current parameters, if any
		Safe::load('parameters/overlays.bbb_meetings.include.php');

		// default host
		if(!isset($context['bbb_server']) || !$context['bbb_server'])
			$context['bbb_server'] = $context['host_name'];

	}

	/**
	 * initialize passwords for this instance
	 *
	 */
	protected function initialize_passwords() {
		global $context;

		// build moderator and attendees passwords
		$buffer = $this->attributes['id'];
		if(isset($context['bbb_salt']))
			$buffer .= $context['bbb_salt'];
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

	}

	/**
	 * BigBlueButton meetings start on demand
	 *
	 * @return boolean FALSE
	 */
	function with_automatic_start() {
		return FALSE;
	}

	/**
	 * BigBlueButton meetings stop on demand
	 *
	 * @return boolean FALSE
	 */
	function with_automatic_stop() {
		return FALSE;
	}

	/**
	 * should we record this session?
	 *
	 * @return boolean TRUE or FALSE
	 */
	function with_session_recording() {
		return FALSE;
	}

}

?>
