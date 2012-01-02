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
 * If OpenTok has been activated, then the webcams of participants are triggered automatically,
 * and a series of videos are displayed above the chatting area. Echo cancellation is managed
 * to allow for a smmother video chat experience of all participants.
 *
 * @link http://www.tokbox.com/
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

		// create an OpenTok session
		if(isset($context['opentok_api_key']) && $context['opentok_api_key']) {

			// server to connect to
			if(isset($context['opentok_api_url']) && $context['opentok_api_url'])
				$url = $context['opentok_api_url'];
			else
				$url = 'https://staging.tokbox.com/hl';

			// link to create a session
			$url .= '/session/create';

			// authenticate through HTTP headers
			$headers = array( 'X-TB-PARTNER-AUTH: '.$context['opentok_api_key'].':'.$context['opentok_api_secret'] );

			// parameters of this session
			$parameters = array();
			$parameters['api_key'] = $context['opentok_api_key'];
			$parameters['location'] = $_SERVER['REMOTE_ADDR'];

			// get a new session id
			if($response = http::proceed_natively($url, $headers, $parameters, 'overlays/chat_meeting.php')) {

				// invalid xml response
				if(!$xml = @simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA)) {
					Logger::error(sprintf('OpenTok error: %s', 'response is not valid XML'));
					return NULL;

				// error code returned by the server
				} elseif($errors = $xml->xpath("//error")) {
					if($message = $errors[0]->xpath("//@message"))
						$message = (string)$message[0]['message'];
					else
						$message = 'unknown error';
					Logger::error(sprintf('OpenTok error: %s', $xml->error['code'].' '.$xml->error->children()->getName().': '.$message));
					return NULL;

				// no session id in the response
				} elseif(!isset($xml->Session->session_id)) {
					Logger::error(sprintf('OpenTok error: %s', 'no session id has been provided'));
					return NULL;
				}

				// save the session id along averlay data and forward it to all participants
				$fields = array( 'session_id' => (string)$xml->Session->session_id );
				$this->set_values($fields);

			}

		}


		// redirect to the main page
		if(is_object($this->anchor))
			return $context['url_to_home'].$context['url_to_root'].$this->anchor->get_url();

		// problem, darling!
		return NULL;
	}

	/**
	 * add text to the main event page
	 *
	 * This is invoked from within get_view_text(), to support integrated streams
	 *
	 * @see overlays/event.php
	 *
	 * @return some HTML to be inserted into the resulting page
	 */
	function get_view_text_extension() {
		global $context;

		// meeting is not on-going
		if($this->attributes['status'] != 'started')
			return '';

		// use services/configure.php to activate OpenTok
		if(!isset($context['opentok_api_key']) || !$context['opentok_api_key'])
			return '';

		// no session id!
		if(!isset($this->attributes['session_id']) || !$this->attributes['session_id']) {
			Logger::error(sprintf('OpenTok error: %s', 'no session id has been found'));
			return '';
		}

		// prepare the authentication token
		$credentials = 'session_id='.$this->attributes['session_id']
			.'&create_time='.time()
			.'&role=publisher'
			.'&nonce='.microtime(true).mt_rand();

		// hash credentials using secret
        $hash = hash_hmac('sha1', $credentials, $context['opentok_api_secret']);

		// finalize the authentication token expected by OpenTok
        $token = "T1==".base64_encode('partner_id='.$context['opentok_api_key'].'&sig='.$hash.':'.$credentials);

		// load the OpenTok javascript library in shared/global.php
		$context['javascript']['opentok'] = TRUE;

		// interface with the OpenTok API
		$context['page_footer'] .= JS_PREFIX
			.'var OpenTok = {'."\n"
			."\n"
			.'	apiKey: '.$context['opentok_api_key'].','."\n"
			.'	sessionId: "'.$this->attributes['session_id'].'",'."\n"
			.'	tokenString: "'.$token.'",'."\n"
			."\n"
			.'	session: null,'."\n"
			.'	publisher: null,'."\n"
			.'	pushToTalk: true,'."\n"
			.'	subscribers: {},'."\n"
			."\n"
			.'	// successful detection of local devices'."\n"
			.'	devicesDetectedHandler: function(event) {'."\n"
			."\n"
			.'		if(event.cameras.length == 0 || event.microphones.length == 0)'."\n"
			.'			return;'."\n"
			."\n"
			.'		// create one div for my own camera, with the id opentok_publisher'."\n"
			.'		var parentDiv = document.getElementById("myCamera");'."\n"
			.'		var publisherDiv = document.createElement("div");'."\n"
			.'		publisherDiv.setAttribute("id", "opentok_publisher");'."\n"
			.'		var followingDiv = document.getElementById("push-to-talk");'."\n"
			.'		parentDiv.insertBefore(publisherDiv, followingDiv);'."\n"
			."\n"
			.'		// bind this div with my own camera'."\n"
			.'		var subscriberProps = {width: 120, height: 90, publishAudio: !OpenTok.pushToTalk};'."\n"
			.'		OpenTok.publisher = OpenTok.session.publish(publisherDiv.id, subscriberProps);'."\n"
			."\n"
			.'		// manage echo cancellation'."\n"
			.'		OpenTok.publisher.addEventListener("echoCancellationModeChanged", OpenTok.echoCancellationModeChangedHandler);'."\n"
			.'	},'."\n"
			."\n"
			.'	// echo can be cancelled, or not'."\n"
			.'	echoCancellationModeChangedHandler: function(event) {'."\n"
			.'		switch(OpenTok.publisher.getEchoCancellationMode()) {'."\n"
			."\n"
			.'			// listen to everybody, and no need for push-to-talk'."\n"
			.'			case "fullDuplex":'."\n"
			.'				for (var i = 0; i < OpenTok.subscribers.length; i++) {'."\n"
			.'					OpenTok.subscribers[i].subscribeToAudio(true);'."\n"
			.'				}'."\n"
			.'				OpenTok.publisher.publishAudio(true);'."\n"
			.'				document.getElementById("push-to-talk").style.display = "none";'."\n"
			.'				OpenTok.pushToTalk = false;'."\n"
			.'				break;'."\n"
			."\n"
			.'			// listen to one audio stream at a time, and activate push-to-talk'."\n"
			.'			case "none":'."\n"
			.'				OpenTok.stopTalking();'."\n"
			.'				document.getElementById("push-to-talk").style.display = "block";'."\n"
			.'				OpenTok.pushToTalk = true;'."\n"
			.'				break;'."\n"
			.'		}'."\n"
			.'	},'."\n"
			."\n"
			.'	// launch the video chat based on OpenTok'."\n"
			.'	initialize: function() {'."\n"
			."\n"
			.'		// check system capabilities before activating the service'."\n"
			.'		if (TB.checkSystemRequirements() == TB.HAS_REQUIREMENTS) {'."\n"
			."\n"
			.'			// tell the API who we are'."\n"
			.'			OpenTok.session = TB.initSession(OpenTok.sessionId);'."\n"
			."\n"
			.'			// listen to events received from the session'."\n"
			.'			OpenTok.session.addEventListener("sessionConnected", OpenTok.sessionConnectedHandler);'."\n"
			.'			OpenTok.session.addEventListener("streamCreated", OpenTok.streamCreatedHandler);'."\n"
			."\n"
			.'			// connect to the back-end servers'."\n"
			.'			$("#myCamera").html(\'<img style="padding: 3px;" src="'.$context['url_to_root'].'skins/_reference/ajax/ajax_spinner.gif" alt="loading..." />\');'."\n"
			.'			OpenTok.session.connect(OpenTok.apiKey, OpenTok.tokenString);'."\n"
			.'		}'."\n"
			.'	},'."\n"
			."\n"
			.'	// successful connection to the OpenTok back-end servers'."\n"
			.'	sessionConnectedHandler: function(event) {'."\n"
			."\n"
			.'		// display streams already attached to this session'."\n"
			.'		$("#myCamera").html("");'."\n"
			.'		OpenTok.subscribeToStreams(event.streams);'."\n"
			."\n"
			.'		// attach the local webcam and microphone if detected'."\n"
			.'		var deviceManager = TB.initDeviceManager(OpenTok.apiKey);'."\n"
			.'		deviceManager.addEventListener("devicesDetected", OpenTok.devicesDetectedHandler);'."\n"
			.'		deviceManager.detectDevices();'."\n"
			.'	},'."\n"
			."\n"
			.'	// a user click push-to-talk'."\n"
			.'	startTalking: function() {'."\n"
			.'		for (var i = 0; i < OpenTok.subscribers.length; i++) {'."\n"
			.'			OpenTok.subscribers[i].subscribeToAudio(false);'."\n"
			.'		}'."\n"
			.'		OpenTok.publisher.publishAudio(true);'."\n"
			."\n"
			.'		document.getElementById("push-to-talk").onclick = OpenTok.stopTalking;'."\n"
			.'		document.getElementById("push-to-talk").value = "'.i18n::s('Click to mute').'";'."\n"
			.'	},'."\n"
			."\n"
			.'	// a user releases push-to-talk'."\n"
			.'	stopTalking: function() {'."\n"
			.'		OpenTok.publisher.publishAudio(false);'."\n"
			.'		for (var i = 0; i < OpenTok.subscribers.length; i++) {'."\n"
			.'			OpenTok.subscribers[i].subscribeToAudio(true);'."\n"
			.'		}'."\n"
			."\n"
			.'		document.getElementById("push-to-talk").onclick = OpenTok.startTalking;'."\n"
			.'		document.getElementById("push-to-talk").value = "'.i18n::s('Click to talk').'";'."\n"
			.'	},'."\n"
			."\n"
			.'	// successful creation of new OpenTok streams'."\n"
			.'	streamCreatedHandler: function(event) {'."\n"
			.'		// display new streams on people arrival'."\n"
			.'		OpenTok.subscribeToStreams(event.streams);'."\n"
			.'	},'."\n"
			."\n"
			.'	// add new streams to the user interface'."\n"
			.'	subscribeToStreams: function(streams) {'."\n"
			.'		for (i = 0; i < streams.length; i++) {'."\n"
			.'			var stream = streams[i];'."\n"
			.'			// subscribe to all streams there, except my own camera'."\n"
			.'			if(stream.connection.connectionId != OpenTok.session.connection.connectionId) {'."\n"
			.'				// create one div per subscribed stream and give it the id of the stream'."\n"
			.'				var subscriberDiv = document.createElement("div");'."\n"
			.'				subscriberDiv.setAttribute("id", stream.streamId);'."\n"
			.'				document.getElementById("subscribers").appendChild(subscriberDiv);'."\n"
			.'				// bind the stream to this div'."\n"
			.'				var subscriberProps = {width: 120, height: 90, publishAudio: !OpenTok.pushToTalk};'."\n"
			.'				OpenTok.subscribers[stream.streamId] = OpenTok.session.subscribe(stream, subscriberDiv.id, subscriberProps);'."\n"
			.'			}'."\n"
			.'		}'."\n"
			.'	}'."\n"
			."\n"
			.'}'."\n"
			."\n"
			.'// bind to OpenTok'."\n"
			.'$(document).ready(function() { OpenTok.initialize(); });'."\n"
			.JS_SUFFIX;

		// video streams are put above the chat area
		return '<table class="layout"><tr><td><div id="myCamera" style="float: left">'
			.	'<input type="button" id="push-to-talk" value="'.i18n::s('Click to talk').'" onclick="OpenTok.startTalking()" style="display: none" />'
			.'</div>'
			.'<div id="subscribers"></div>'
			.'</td></tr></table>'."\n";

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
