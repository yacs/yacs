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
 * If OpenTok has been activated, webcams of participants are triggered automatically,
 * and a series of videos are displayed above the chatting area.
 * The OpenTok signalling facility is also used to make the chat almost real-time.
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
					Logger::error(sprintf('OpenTok: %s', 'response is not valid XML'));
					return NULL;

				// error code returned by the server
				} elseif($errors = $xml->xpath("//error")) {
					if($message = $errors[0]->xpath("//@message"))
						$message = (string)$message[0]['message'];
					else
						$message = 'unknown error';
					Logger::error(sprintf('OpenTok: %s', $xml->error['code'].' '.$xml->error->children()->getName().': '.$message));
					return NULL;

				// no session id in the response
				} elseif(!isset($xml->Session->session_id)) {
					Logger::error(sprintf('OpenTok: %s', 'no session id has been provided'));
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
	 * add text to the bottom of the page
	 *
	 * This is where video streams from OpenTok are included
	 *
	 * @see overlays/event.php
	 *
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_trailer_text($host=NULL) {
		global $context;

		// meeting is not on-going
		$text = '';
		if($this->attributes['status'] != 'started')
			return $text;

		// use services/configure.php to activate OpenTok
		if(!isset($context['opentok_api_key']) || !$context['opentok_api_key'])
			return $text;

		// no session id!
		if(!isset($this->attributes['session_id']) || !$this->attributes['session_id']) {
			Logger::error(sprintf('OpenTok error: %s', 'no session id has been found'));
			return $text;
		}

		// prepare the authentication token
		$credentials = 'session_id='.$this->attributes['session_id']
			.'&create_time='.time()
			.'&role=publisher'
			.'&nonce='.microtime(true).mt_rand();

		// hash credentials using secret
        $hash = hash_hmac('sha1', $credentials, $context['opentok_api_secret']);

		// finalize the authentication token expected by OpenTok
        $token = 'T1=='.base64_encode('partner_id='.$context['opentok_api_key'].'&sig='.$hash.':'.$credentials);

        // delegate audio processing to OpenTok too
        $with_audio = 'true';

        // except if twilio has been activated instead
        if(isset($context['twilio_account_sid']) && $context['twilio_account_sid'])
        	$with_audio = 'false';

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
			.'	deviceManager: null,'."\n"
			.'	publisher: null,'."\n"
			.'	session: null,'."\n"
			.'	subscribers: {},'."\n"
			.'	tentatives: 3,'."\n"
			.'	watchdog: null,'."\n"
			.'	withAudio: '.$with_audio.','."\n"
			."\n"
			.'	// user has denied access to the camera from Flash'."\n"
			.'	accessDeniedHandler: function() {'."\n"
			.'		$("#opentok .me").empty();'."\n"
			.'	},'."\n"
			."\n"
			.'	// attempt to reconnect to the server'."\n"
			.'	connectAgain: function() {'."\n"
			.'		OpenTok.growl("'.i18n::s('Connecting again to OpenTok').'");'."\n"
			.'		OpenTok.session.connect(OpenTok.apiKey, OpenTok.tokenString);'."\n"
			.'	},'."\n"
			."\n"
			.'	// successful detection of local devices'."\n"
			.'	devicesDetectedHandler: function(event) {'."\n"
			."\n"
			.'		// no adequate hardware to move forward'."\n"
			.'		if(event.cameras.length == 0) {'."\n"
			.'			OpenTok.growl("'.i18n::s('A webcam is required to be visible').'");'."\n"
			."\n"
			.'		// at least one camera is available'."\n"
			.'		} else {'."\n"
			."\n"
			.'			// create one placeholder div for my own camera'."\n"
			.'			OpenTok.growl("'.i18n::s('Adding local video stream').'");'."\n"
			.'			$("#opentok .me").append(\'<div class="frame subscriber"><div id="placeholder"></div></div>\');'."\n"
			."\n"
			.'			// bind this div with my own camera'."\n"
			.'			var streamProps = {width: 120, height: 90,'."\n"
			.'					publishAudio: false, publishVideo: true, name: "'.str_replace('"', "'", Surfer::get_name()).'" };'."\n"
			.'			OpenTok.publisher = OpenTok.session.publish("placeholder", streamProps);'."\n"
			."\n"
			.'			// monitor the publishing session'."\n"
			.'			OpenTok.publisher.addEventListener("accessDenied", OpenTok.accessDeniedHandler);'."\n"
			.'			OpenTok.publisher.addEventListener("deviceInactive", OpenTok.deviceInactiveHandler);'."\n"
			."\n"
			.'		}'."\n"
			."\n"
			.'	},'."\n"
			."\n"
			.'	// for some reason the user is not publishing anymore'."\n"
			.'	deviceInactiveHandler: function(event) {'."\n"
			.'		if(event.camera) {'."\n"
			.'			OpenTok.growl("'.i18n::s('You are not visible').'");'."\n"
//			.'			$("#opentok .me").empty();'."\n"
			.'		}'."\n"
			.'		if(event.microphone) {'."\n"
			.'			OpenTok.growl("'.i18n::s('You have been muted').'");'."\n"
			.'			$("#opentok .me .frame").removeClass("talker");'."\n"
			.'		}'."\n"
			.'	},'."\n"
			."\n"
			.'	// we have been killed by an asynchronous exception'."\n"
			.'	exceptionHandler: function(event) {'."\n"
			."\n"
			.'		OpenTok.growl(event.code + " " + event.title + " - " + event.message);'."\n"
			."\n"
			.'		OpenTok.tentatives--;'."\n"
			.'		if((OpenTok.tentatives > 0) && (event.code === 1006 || event.code === 1008 || event.code === 1014)) {'."\n"
			.'			OpenTok.growl("'.i18n::s('Error while connecting to OpenTok').'");'."\n"
			.'			OpenTok.session.connecting = false;'."\n"
			.'			window.setTimeout("OpenTok.connectAgain()", 3000);'."\n"
			.'		}'."\n"
			.'	},'."\n"
			."\n"
			.'	// display a message for some seconds'."\n"
			.'	growl: function(message) {'."\n"
			.'		if(typeof OpenTok.growlId != "number") {'."\n"
			.'			OpenTok.growlId = 1;'."\n"
			.'		} else {'."\n"
			.'			OpenTok.growlId++;'."\n"
			.'		}'."\n"
			.'		var myId = OpenTok.growlId++;'."\n"
			.'		$("#opentok .growl").append(\'<span id="growl\'+myId+\'">\'+message+"</span>");'."\n"
			.'		window.setTimeout("$(\'#growl"+myId+"\').fadeOut(\'slow\')", 5000);'."\n"
			.'	},'."\n"
			."\n"
			.'	// launch the video chat based on OpenTok'."\n"
			.'	initialize: function() {'."\n"
			."\n"
			.'		// report on error, if any'."\n"
			.'		TB.setLogLevel(TB.DEBUG);'."\n"
			.'		TB.addEventListener("exception", OpenTok.exceptionHandler);'."\n"
			."\n"
			.'		// check system capabilities before activating the service'."\n"
			.'		if(TB.checkSystemRequirements() == TB.HAS_REQUIREMENTS) {'."\n"
			."\n"
			.'			// slide to page bottom, because this is not obvious to end-user'."\n"
			.'			OpenTok.growl("'.i18n::s('Connecting to OpenTok').'");'."\n"
			."\n"
			.'			// bind to local hardware via a device manager'."\n"
			.'			OpenTok.deviceManager = TB.initDeviceManager(OpenTok.apiKey);'."\n"
			.'			OpenTok.deviceManager.addEventListener("devicesDetected", OpenTok.devicesDetectedHandler);'."\n"
			."\n"
			.'			// bind to the API via a session'."\n"
			.'			OpenTok.session = TB.initSession(OpenTok.sessionId);'."\n"
			.'			OpenTok.session.addEventListener("sessionConnected", OpenTok.sessionConnectedHandler);'."\n"
			.'			OpenTok.session.addEventListener("signalReceived", OpenTok.signalReceivedHandler);'."\n"
			.'			OpenTok.session.addEventListener("streamCreated", OpenTok.streamCreatedHandler);'."\n"
			.'			OpenTok.session.addEventListener("streamDestroyed", OpenTok.streamDestroyedHandler);'."\n"
			.'			OpenTok.session.addEventListener("streamPropertyChanged", OpenTok.streamPropertyChangedHandler);'."\n"
			."\n"
			.'			// connect to back-end servers'."\n"
			.'			OpenTok.session.connect(OpenTok.apiKey, OpenTok.tokenString);'."\n"
			."\n"
			.'		// no way to use the service'."\n"
			.'		} else {'."\n"
			.'			OpenTok.growl("'.i18n::s('This system is not supported by OpenTok').'");'."\n"
			.'		}'."\n"
			.'	},'."\n"
			."\n"
			.'	// successful connection to the OpenTok back-end servers'."\n"
			.'	sessionConnectedHandler: function(event) {'."\n"
			."\n"
			.'		// display streams already attached to this session'."\n"
			.'		OpenTok.subscribeToStreams(event.streams);'."\n"
			."\n"
			.'		// attach the local webcam and microphone if detected'."\n"
			.'		OpenTok.deviceManager.detectDevices();'."\n"
			.'	},'."\n"
			."\n"
			.'	// send a signal to other parties'."\n"
			.'	signal: function() {'."\n"
			.'		if(OpenTok.session)'."\n"
			.'			OpenTok.session.signal();'."\n"
			.'	},'."\n"
			."\n"
			.'	// signal received, refresh the page'."\n"
			.'	signalReceivedHandler: function(event) {'."\n"
			."\n"
			.'		// refresh the chat area'."\n"
			.'		if(typeof Comments == "object")'."\n"
			.'			Comments.subscribe();'."\n"
			.'	},'."\n"
			."\n"
			.'	// i start to talk'."\n"
			.'	startTalking: function() {'."\n"
			.'		for(var i = 0; i < OpenTok.subscribers.length; i++) {'."\n"
			.'			OpenTok.subscribers[i].subscribeToAudio(false);'."\n"
			.'		}'."\n"
			.'		OpenTok.publisher.publishAudio(true);'."\n"
			."\n"
			.'		document.getElementById("pushToTalk").onclick = OpenTok.stopTalking;'."\n"
			.'		document.getElementById("pushToTalk").value = "'.i18n::s('Stop talking').'";'."\n";

		// identify the chairman or, if unknown, the owner of this page
		$chairman = array();
		if(isset($this->attributes['chairman']) && $this->attributes['chairman'])
			$chairman =& Users::get($this->attributes['chairman']);
		if(!isset($chairman['id']) && ($owner = $this->anchor->get_value('owner_id')))
			$chairman =& Users::get($owner);

		// if this surfer is the chairman of this meeting, he will take over after three seconds of silence
		if(isset($chairman['id']) && Surfer::is($chairman['id']))
			$context['page_footer'] .= 'OpenTok.watchdog = setInterval(function () {'
				.	'if(!$("#opentok .talker").length) {OpenTok.startTalking();}'
				.'}, 3000);'."\n";

		// end of javascript snippet
		$context['page_footer'] .= '	},'."\n"
			."\n"
			.'	// i am back to listening mode'."\n"
			.'	stopTalking: function() {'."\n"
			.'		if(OpenTok.watchdog) { clearInterval(OpenTok.watchdog); OpenTok.watchdog = null; }'."\n"
			.'		OpenTok.publisher.publishAudio(false);'."\n"
			.'		for(var i = 0; i < OpenTok.subscribers.length; i++) {'."\n"
			.'			OpenTok.subscribers[i].subscribeToAudio(true);'."\n"
			.'		}'."\n"
			."\n"
			.'		document.getElementById("pushToTalk").onclick = OpenTok.startTalking;'."\n"
			.'		document.getElementById("pushToTalk").value = "'.i18n::s('Start talking').'";'."\n"
			.'	},'."\n"
			."\n"
			.'	// display new streams on people arrival'."\n"
			.'	streamCreatedHandler: function(event) {'."\n"
			.'		OpenTok.subscribeToStreams(event.streams);'."\n"
			.'	},'."\n"
			."\n"
			.'	// remove a stream that has been destroyed'."\n"
			.'	streamDestroyedHandler: function(event) {'."\n"
			.'		for(i = 0; i < event.streams.length; i++) {'."\n"
			.'			var stream = event.streams[i];'."\n"
			.'			$("#opentok_"+stream.streamId).remove();'."\n"
			.'		}'."\n"
			.'	},'."\n"
			."\n"
			.'	// a stream has started or stopped'."\n"
			.'	streamPropertyChangedHandler: function(event) {'."\n"
			.'		switch(event.changedProperty) {'."\n"
			.'			case "hasAudio":'."\n"
			.'				if(event.newValue) {'."\n"
			.'					OpenTok.growl("'.i18n::s("%s is talking").'".replace(/%s/, event.stream.name));'."\n"
			.'					if(event.stream.connection.connectionId != OpenTok.session.connection.connectionId) {'."\n"
			.'						$("#opentok_"+event.stream.streamId).addClass("talker");'."\n"
			.'						OpenTok.stopTalking();'."\n"
			.'					} else {'."\n"
			.'						$("#opentok .me .frame").addClass("talker");'."\n"
			.'					}'."\n"
			.'				} else {'."\n"
			.'					OpenTok.growl("'.i18n::s("%s is listening").'".replace(/%s/, event.stream.name));'."\n"
			.'					if(event.stream.connection.connectionId != OpenTok.session.connection.connectionId) {'."\n"
			.'						$("#opentok_"+event.stream.streamId).removeClass("talker");'."\n"
			.'					} else {'."\n"
			.'						$("#opentok .me .frame").removeClass("talker");'."\n"
			.'					}'."\n"
			.'				}'."\n"
			.'				break;'."\n"
			.'			case "hasVideo":'."\n"
			.'				if(!event.newValue) {'."\n"
			.'					OpenTok.growl("'.i18n::s("%s is not visible").'".replace(/%s/, event.stream.name));'."\n"
			.'				}'."\n"
			.'				break;'."\n"
//			.'			case "quality":'."\n"
//			.'				OpenTok.growl(event.stream.name+" has "+event.newValue.networkQuality+" network connection");'."\n"
//			.'				break;'."\n"
			.'		}'."\n"
			.'	},'."\n"
			."\n"
			.'	// add new streams to the user interface'."\n"
			.'	subscribeToStreams: function(streams) {'."\n"
			."\n"
			.'		// some remote stream in the list?'."\n"
			.'		for(i = 0; i < streams.length; i++) {'."\n"
			.'			if(streams[i].connection.connectionId != OpenTok.session.connection.connectionId) {'."\n"
			.'				OpenTok.growl("'.i18n::s('Adding remote video streams').'");'."\n"
			.'				break;'."\n"
			.'			}'."\n"
			.'		}'."\n"
			."\n"
			.'		for(i = 0; i < streams.length; i++) {'."\n"
			.'			var stream = streams[i];'."\n"
			."\n"
			.'			// subscribe to all streams, except my own camera'."\n"
			.'			if(stream.connection.connectionId != OpenTok.session.connection.connectionId) {'."\n"
			."\n"
			.'				// create one div per subscribed stream and give it the id of the stream'."\n"
			.'				$("#opentok .others").append(\'<span id="opentok_\'+stream.streamId+\'"><span id="placeholder"></span></span>\');'."\n"
			.'				$("#opentok_"+stream.streamId).addClass("ibox subscriber").css({width: 120, height: 90 });'."\n"
			."\n"
			.'				// bind the stream to this div'."\n"
			.'				var streamProps = {width: 120, height: 90, subscribeToAudio: true, subscribeToVideo: true};'."\n"
			.'				OpenTok.subscribers[stream.streamId] = OpenTok.session.subscribe(stream, "placeholder", streamProps);'."\n"
			."\n"
			.'				// the remote stream is active'."\n"
			.'				if(stream.hasAudio) {'."\n"
			.'					OpenTok.growl("'.i18n::s("%s is talking").'".replace(/%s/, stream.name));'."\n"
			.'					$("#opentok_"+stream.streamId).addClass("talker");'."\n"
			.'				}'."\n"
			."\n"
			.'			// the default is to push to talk'."\n"
			.'			} else if(OpenTok.withAudio) {'."\n"
			.'				$("#opentok .me").append(\'<div style="text-align: center; padding: 2px 0;">'
			.					'<input type="button" id="pushToTalk" value="'.i18n::s('Start talking').'" onClick="OpenTok.startTalking()" />'
			.					'</div>\');'."\n"
			.'				OpenTok.growl("'.i18n::s("Click on the button before talking").'");'."\n"
			.'			}'."\n"
			.'		}'."\n"

			.'		$("#opentok .me .frame").addClass("subscriber").css({width: 120, height: 90});'."\n"
			.'		$("#description").focus();'."\n"

			.'	}'."\n"
			."\n"
			.'}'."\n"
			."\n"
			.'// bind to OpenTok'."\n"
			.'$(document).ready(function() { OpenTok.initialize(); });'."\n"
			."\n"
			.JS_SUFFIX;

		// video streams are put above the chat area
		$text = '<div id="opentok">'
			.	'<div class="growl" style="height: 1.6em;" > </div>'
			.	'<table class="layout"><tr>'
			.	'<td class="me"></td>'
			.	'<td class="others"></td>'
			.	'</tr></table>'
			.'</div>'."\n";
		return $text;

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
	 * notify watchers or not?
	 *
	 * Disable notifications during the interactive chat.
	 *
	 * @see overlays/event.php
	 *
	 * @param array if provided, a notification that can be sent to customised recipients
	 * @return boolean always FALSE for events, since notifications are made through enrolment
	 */
	function should_notify_watchers($mail=NULL) {
		global $context;

		// no e-mail if we are chatting
		if($this->attributes['status'] == 'started')
			return FALSE;

		// else rely on parent class
		return parent::should_notify_watchers($mail);
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
