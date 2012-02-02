<?php
/**
 * one-to-one meeting page
 *
 * @links http://www.tokbox.com/opentok
 *
 * Of course, this works only at yacs servers where OpenTok parameters have been set.
 *
 * @see services/configure.php
 *
 * On page intial loading, the page offers either to start a new Face Me session, or to join an existing one.
 * Any surfer is entitled to create a new session, and to get a private link that can be shared with one other person.
 *
 * When a new session has been successfully created at OpenTok back-end, a random number is generated
 * and this information is saved in a text file. The link to join the session is displayed.
 *
 * When a person joins the page and provides the id, session information is read from the text file
 * in order to join the right OpenTok session.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

// common definitions and initial processing
include_once 'shared/global.php';

// look for the id
$id = NULL;
if(isset($_REQUEST['id']))
	$id = $_REQUEST['id'];
elseif(isset($context['arguments'][0]))
	$id = $context['arguments'][0];
$id = trim(strip_tags($id));

// load localized strings
i18n::bind('root');

// load localized strings
load_skin('faceme');

// populate page attributes
$context['page_title'] = sprintf(i18n::s('Face Me'), $id);

// no OpenTok parameters have been set
if(!isset($context['opentok_api_key']) || !$context['opentok_api_key']) {
	Logger::error(i18n::s('The service is not available'));

// create a new OpenTok session through AJAX call
} elseif(isset($_REQUEST['start_session'])) {

	// create a random id
	$id = (string)rand(100000, 999999);

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
//	$parameters['p2p.preference'] = 'enabled';

	// get a new session id
	if(!$response = http::proceed_natively($url, $headers, $parameters, 'faceme.php'))
		Logger::error(sprintf('OpenTok: %s', 'response is not valid XML'));

	// invalid xml response
	elseif(!$xml = @simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA))
		Logger::error(sprintf('OpenTok: %s', 'response is not valid XML'));

	// error code returned by the server
	elseif($errors = $xml->xpath("//error")) {
		if($message = $errors[0]->xpath("//@message"))
			$message = (string)$message[0]['message'];
		else
			$message = 'unknown error';
		Logger::error(sprintf('OpenTok: %s', $xml->error['code'].' '.$xml->error->children()->getName().': '.$message));

	// no session id in the response
	} elseif(!isset($xml->Session->session_id))
		Logger::error(sprintf('OpenTok: %s', 'no session id has been provided'));

	// a session has been started
	else {

		// save the pair meeting id - OpenTok id in a text file
		$line = $id.'|'.(string)$xml->Session->session_id;
		if($handle = Safe::fopen($context['path_to_root'].'temporary/faceme.txt', 'a')) {
			fwrite($handle, $line."\n");
			fclose($handle);
		}

		// return the id to caller
		$result = array( 'id' => $id );

		// handle the output correctly
		render_raw('application/json; charset='.$context['charset']);

		// actual transmission except on a HEAD request
		if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
			echo Safe::json_encode($result);

		// the post-processing hook
		finalize_page();
		return;

	}

	// some error has occured
	Safe::header('Status: 500 Internal Error', TRUE, 500);
	die(Logger::error_pop());

// retrieve session data through AJAX call
} elseif(isset($_REQUEST['describe_session'])) {

	// we need some session id
	if(!$id)
		Logger::error(sprintf('OpenTok: %s', 'no session id has been provided'));

	// we need some stored ids
	elseif(!$data = Safe::file_get_contents($context['path_to_root'].'temporary/faceme.txt'))
		Logger::error(i18n::s('The service is not available'));

	// the provided id is unknown
	elseif(($head = strpos($data, $id)) === FALSE)
		Logger::error(i18n::s('The service is not available'));

	// retrieve session data
	else {
		$response = array();

		// retrieve session id from the file
		$head += strlen($id)+1;

		$count = strlen($data);
		for($tail = $head+1; $tail <= $count; $tail++) {
			if($data[ $tail ] == "\n")
				break;
		}

		// to be returned to caller
		$response['session_id'] = substr($data, $head, $tail-$head);

		// prepare the authentication token
		$credentials = 'session_id='.$response['session_id']
			.'&create_time='.time()
			.'&role=publisher'
			.'&nonce='.microtime(true).mt_rand();

		// hash credentials using secret
        $hash = hash_hmac('sha1', $credentials, $context['opentok_api_secret']);

		// finalize the authentication token expected by OpenTok
        $response['token'] = 'T1=='.base64_encode('partner_id='.$context['opentok_api_key'].'&sig='.$hash.':'.$credentials);

		// handle the output correctly
		render_raw('application/json; charset='.$context['charset']);

		// actual transmission except on a HEAD request
		if(isset($_SERVER['REQUEST_METHOD']) && ($_SERVER['REQUEST_METHOD'] != 'HEAD'))
			echo Safe::json_encode($response);

		// the post-processing hook
		finalize_page();
		return;

	}

	// some error has occured
	Safe::header('Status: 400 Bad Request', TRUE, 400);
	die(Logger::error_pop());

// manage the OpenTok session
} else {

	// leverage URL rewriting if possible
	switch($context['with_friendly_urls']) {
	case 'Y':
		$link = $context['url_to_home'].$context['url_to_root'].'faceme.php/';
		break;
	case 'R':
		$link = $context['url_to_home'].$context['url_to_root'].'faceme/';
		break;
	default:
		$link = $context['url_to_home'].$context['url_to_root'].'faceme.php?id=';
		break;
	}


	// the spinning wheel
	$wheel = '<img style="padding: 0 3px; vertical-align: middle" src="'.$context['url_to_root'].'skins/_reference/ajax/ajax_spinner.gif" alt="*" />';

	// should be moved to yacs.css
	$context['page_header'] .= "\n".'<style type="text/css">'

		.'#initialPanel {'."\n"
		.'	position: relative;'."\n"
		.'	margin: 1em 0 3em 0;'."\n"
		.'}'."\n"

		.'.facemeStart, .facemeJoin {'."\n"
		.'	width: 250px;'."\n"
		.'	height: 250px;'."\n"
		.'}'."\n"

		.'#sessionAddress {'."\n"
		.'	width: 220px;'."\n"
		.'}'."\n"

		.'#meetingPanel {'."\n"
		.'	position: relative;'."\n"
		.'	margin: 1em 0 3em 0;'."\n"
		.'}'."\n"

		.'#meetingPanel .myFace {'."\n"
		.'	background-color: black;'."\n"
		.'	color: white;'."\n"
		.'	width: 220px;'."\n"
		.'	height: 240px;'."\n"
		.'}'."\n"

		.'#meetingPanel .myFace .onAir{'."\n"
		.'	margin: auto;'."\n"
		.'}'."\n"

		.'#meetingPanel .yourFace {'."\n"
		.'	background-color: black;'."\n"
		.'	color: white;'."\n"
		.'	width: 320px;'."\n"
		.'	height: 240px;'."\n"
		.'}'."\n"

		.'#linkToShare {'."\n"
		.'	width: 240px;'."\n"
		.'}'."\n"

		.'</style>';

	// start area
	$start = '<p>'.i18n::s('Invite one person').'</p>'
		.'<p style="font-size: 7pt"><span class="details">'
			.i18n::s('Click the button to start a face-to-face meeting. Each meeting has a unique web address.')
		.'</span></p>'
		.'<button class="bigButton" onclick="OpenTok.startSession()" type="button">'.i18n::s('Start a session').'</button>';

	// join area
	$join = '<div>'
		.'<form>'
		.	'<p>'.i18n::s('Meet a person').'</p>'
		.	'<p style="font-size: 7pt"><input type="text" id="sessionAddress" />'.BR.'<span class="details">'.i18n::s('Paste the number or web address given to you').'</span></p>'
		.	'<button class="bigButton" onclick="OpenTok.goTo($(\'#sessionAddress\').val())" type="button">'
		.	i18n::s('Join this session')
		.	'</button>'
		.'</form>'
		.'</div>';

	// initial panel
	$context['text'] .= '<table id="initialPanel" class="layout" style="display: none"><tbody><tr>'
		.'<td style="text-align: center; vertical-align: middle" class="facemeStart">'.$start.'</td>'
		.'<td style="text-align: center; vertical-align: middle" class="facemeJoin">'.$join.'</td>'
		.'</tr></tbody></table>';

	// meeting panel
	$context['text'] .= '<div id="meetingPanel" style="display: none">';

	// sharing panel
	$context['text'] .= '<div id="sharingPanel" style="text-align: center; vertical-align: middle">'
			.'<p>'.i18n::s('Copy and paste this link into an email or instant message:').'</p>'
			.'<input type="text" id="linkToShare" value="'.$link.$id.'" onclick="this.select();" readonly="readonly" />'
		.'</div>';

	// growl-like notifications
	$context['text'] .= '<div class="growl" style="height: 1.6em;" > </div>';

	// video streams
	$context['text'] .= '<table class="layout"><tbody><tr>'
		.'<td style="text-align: center; vertical-align: middle" class="myFace"></td>'
		.'<td style="text-align: center; vertical-align: middle" class="yourFace">'
			.'<img style="vertical-align: middle" src="'.$context['url_to_root'].'skins/_reference/ajax/ajax_spinner_black.gif" alt="*" />'
			.BR.BR.'<span class="small">'.i18n::s('Waiting for the other person to join').'</span>'
		.'</td>'
		.'</tr></tbody></table>';

	// end of meeting panel
	$context['text'] .= '</div>'."\n";

	// we don't have a session id yet
	if(!$id) {

		// show the initial panel
		$context['page_footer'] .= JS_PREFIX
			.'$(document).ready(function() {'."\n"
			.'	$("#sessionAddress").keyup(function(event) {'."\n"
			.'		if(event.which == 13) {'."\n"
			.'			event.preventDefault();'."\n"
			.'			OpenTok.goTo($(this).val());'."\n"
			.'		}'."\n"
			.'	});'."\n"
			.'	$("#initialPanel").slideDown();'
			.'});'."\n"
			.JS_SUFFIX;

	// join an existing session
	} else {

		// show the meeting panel and load OpenTok
		$context['page_footer'] .= JS_PREFIX
			.'$(document).ready(function() { $("#meetingPanel").slideDown(); OpenTok.joinSession("'.$id.'"); });'."\n"
			.JS_SUFFIX;

	}

	// load the OpenTok javascript library in shared/global.php
	$context['javascript']['opentok'] = TRUE;

	// our interface with the OpenTok API
	$context['page_footer'] .= JS_PREFIX
		.'var OpenTok = {'."\n"
		."\n"
		.'	apiKey: '.$context['opentok_api_key'].','."\n"
		.'	sessionId: "***no_session_id_yet***",'."\n"
		.'	tokenString: "***no_token_yet***",'."\n"

		.'	endpoint: "'.$context['url_to_home'].$context['url_to_root'].'faceme.php",'."\n"
		.'	link: "'.$link.'",'."\n"

		.'	publisherWidth: 220,'."\n" // publisher video is cropped horizontally
		.'	publisherHeight: 240,'."\n"

		.'	subscriberWidth: 320,'."\n"
		.'	subscriberHeight: 240,'."\n"

		.'	deviceManager: null,'."\n"
		.'	publisher: null,'."\n"
		.'	session: null,'."\n"
		.'	subscribers: {},'."\n"
		.'	tentatives: 3,'."\n"
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
		.'		if(event.cameras.length == 0 || event.microphones.length == 0) {'."\n"
		.'			OpenTok.growl("'.i18n::s('A webcam is required to be visible').'");'."\n"
		.'			return;'."\n"
		.'		}'."\n"
		."\n"
		.'	},'."\n"
		."\n"
		.'	// for some reason the user is not publishing anymore'."\n"
		.'	deviceInactiveHandler: function(event) {'."\n"
		.'		if(event.camera) {'."\n"
		.'			OpenTok.growl("'.i18n::s('You are not visible').'");'."\n"
		.'		}'."\n"
		.'		if(event.microphone) {'."\n"
		.'			OpenTok.growl("'.i18n::s('You have been muted').'");'."\n"
		.'		}'."\n"
		.'	},'."\n"
		."\n"
		.'	// the echo cancellation mode has changed'."\n"
		.'	echoCancellationModeChangedHandler: function(event) {'."\n"
		.'		switch(OpenTok.publisher.getEchoCancellationMode()) {'."\n"
		.'		case "fullDuplex":'."\n"
		.'			OpenTok.growl("'.i18n::s('Full-duplex mode').'");'."\n"
		.'			break;'."\n"
		.'		case "none":'."\n"
		.'			OpenTok.growl("'.i18n::s('No echo cancellation').'");'."\n"
		.'			break;'."\n"
		.'		}'."\n"
		.'	},'."\n"
		."\n"
		.'	// we have been killed by an asynchronous exception'."\n"
		.'	exceptionHandler: function(event) {'."\n"
		."\n"
		.'		OpenTok.tentatives--;'."\n"
		.'		if((OpenTok.tentatives > 0) && (event.code === 1006 || event.code === 1008 || event.code === 1014)) {'."\n"
		.'			OpenTok.growl("'.i18n::s('Error while connecting to OpenTok').'");'."\n"
		.'			OpenTok.session.connecting = false;'."\n"
		.'			window.setTimeout("OpenTok.connectAgain()", 3000);'."\n"
		.'			return;'."\n"
		.'		}'."\n"
		."\n"
		.'		OpenTok.growl(event.code + " " + event.title + " - " + event.message);'."\n"
		.'	},'."\n"
		."\n"
		.'	// go to a named session'."\n"
		.'	goTo: function(data) {'."\n"
		.'		if(data.match(/^[0-9]{6}$/i))'."\n"
		.'			data = "'.$link.'"+data;'."\n"
		.'		window.location.href = data;'."\n"
		.'		return false;'."\n"
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
		.'		$("#meetingPanel .growl").append(\'<span id="growl\'+myId+\'">\'+message+"</span>");'."\n"
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
		.'			// bind to the API via a session'."\n"
		.'			OpenTok.session = TB.initSession(OpenTok.sessionId);'."\n"
//		.'			OpenTok.session.addEventListener("microphoneLevelChanged", OpenTok.microphoneLevelChangedHandler);'."\n"
		.'			OpenTok.session.addEventListener("sessionConnected", OpenTok.sessionConnectedHandler);'."\n"
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
		.'	// i join an existing session'."\n"
		.'	joinSession: function(id) {'."\n"
		.'		Yacs.startWorking();'."\n"
		.'		$.ajax(OpenTok.endpoint, {'."\n"
		.'			type: "get",'."\n"
		.'			dataType: "json",'."\n"
		.'			data: { "describe_session": true, "id": id },'."\n"
		.'			success: function(data) {'."\n"
		.'				OpenTok.sessionId = data.session_id;'."\n"
		.'				OpenTok.tokenString = data.token;'."\n"
		.'				Yacs.stopWorking();'."\n"
		.'				OpenTok.initialize();'."\n"
		.'			},'."\n"
		.'			error: function(data) {'."\n"
		.'				Yacs.stopWorking();'."\n"
		.'				alert(data.responseText);'."\n"
		.'				$("#meetingPanel").slideUp();'."\n"
		.'				$("#initialPanel").slideDown();'."\n"
		.'			}'."\n"
		.'		});'."\n"
		.'	},'."\n"
		."\n"
		.'	// monitor levels of sound captured in microphones'."\n"
		.'	microphoneLevelChangedHandler: function(event) {'."\n"
//		.'		console.log("The microphone level for stream " + event.streamId + " is: " + event.volume);'."\n"
		.'	},'."\n"
		."\n"
		.'	// successful connection to the OpenTok back-end servers'."\n"
		.'	sessionConnectedHandler: function(event) {'."\n"
		."\n"
		.'		// create one placeholder div for my own camera'."\n"
		.'		OpenTok.growl("'.i18n::s('Adding local video stream').'");'."\n"
		.'		$("#meetingPanel .myFace").append(\'<div class="frame"><div id="placeholder"></div></div>\');'."\n"
		.'		$("#meetingPanel .myFace .frame").css({width: OpenTok.publisherWidth, height: OpenTok.publisherHeight });'."\n"
		."\n"
		.'		// bind this div with my own camera'."\n"
		.'		var streamProps = {encodedWidth: OpenTok.subscriberWidth, encodedHeight: OpenTok.subscriberHeight,'."\n"
		.'				width: OpenTok.publisherWidth, height: OpenTok.publisherHeight,'."\n"
		.'				name: "'.str_replace('"', "'", Surfer::get_name()).'" };'."\n"
		.'		OpenTok.publisher = OpenTok.session.publish("placeholder", streamProps);'."\n"
		."\n"
		.'		// monitor the publishing session'."\n"
		.'		OpenTok.publisher.addEventListener("deviceInactive", OpenTok.deviceInactiveHandler);'."\n"
		.'		OpenTok.publisher.addEventListener("echoCancellationModeChanged", OpenTok.echoCancellationModeChangedHandler);'."\n"
		."\n"
		.'		// display streams already attached to this session'."\n"
		.'		OpenTok.subscribeToStreams(event.streams);'."\n"
		."\n"
		.'		// bind to local hardware via a device manager'."\n"
		.'		OpenTok.deviceManager = TB.initDeviceManager(OpenTok.apiKey);'."\n"
		.'		OpenTok.deviceManager.addEventListener("devicesDetected", OpenTok.devicesDetectedHandler);'."\n"
		."\n"
		.'		// attach the local webcam and microphone if detected'."\n"
		.'		OpenTok.deviceManager.detectDevices();'."\n"
		.'	},'."\n"
		."\n"
		.'	// i start a new session'."\n"
		.'	startSession: function() {'."\n"
		.'		Yacs.startWorking();'."\n"
		.'		$.ajax(OpenTok.endpoint, {'."\n"
		.'			type: "get",'."\n"
		.'			dataType: "json",'."\n"
		.'			data: { "start_session": true },'."\n"
		.'			success: function(data) {'."\n"
		.'				window.location.href = "'.$link.'"+data.id;'."\n"
		.'			},'."\n"
		.'			error: function(data) {'."\n"
		.'				Yacs.stopWorking();'."\n"
		.'				alert(data.responseText);'."\n"
		.'			}'."\n"
		.'		});'."\n"
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
		.'			$("#faceme_"+stream.streamId).remove();'."\n"
		.'		}'."\n"
		.'	},'."\n"
		."\n"
		.'	// a stream has started or stopped'."\n"
		.'	streamPropertyChangedHandler: function(event) {'."\n"
//		.'		console.log(event);'."\n"
		.'		switch(event.changedProperty) {'."\n"
		.'			case "hasAudio":'."\n"
		.'				if(event.newValue) {'."\n"
		.'					OpenTok.growl("'.i18n::s("%s is talking").'".replace(/%s/, event.stream.name));'."\n"
		.'				} else {'."\n"
		.'					OpenTok.growl("'.i18n::s("%s is listening").'".replace(/%s/, event.stream.name));'."\n"
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
//		.'			console.log(stream);'."\n"
		."\n"
		.'			// subscribe to all streams, except my own camera'."\n"
		.'			if(stream.connection.connectionId != OpenTok.session.connection.connectionId) {'."\n"
		."\n"
		.'				// create one div per subscribed stream and give it the id of the stream'."\n"
		.'				$("#meetingPanel .yourFace").html(\'<div id="opentok_\'+stream.streamId+\'"><span id="placeholder"></span></div>\');'."\n"
		.'				$("#faceme_"+stream.streamId).css({width: OpenTok.subscriberWidth, height: OpenTok.subscriberHeight });'."\n"
		."\n"
		.'				// bind the stream to this div'."\n"
		.'				var streamProps = {width: OpenTok.subscriberWidth, height: OpenTok.subscriberHeight};'."\n"
		.'				OpenTok.subscribers[stream.streamId] = OpenTok.session.subscribe(stream, "placeholder", streamProps);'."\n"
		."\n"
		.'				// hide the sharing panel'."\n"
		.'				$("#sharingPanel").slideUp();'."\n"
		.'			} else {'."\n"
		.'				$("#meetingPanel .myFace .frame").addClass("onAir");'."\n"
		.'			}'."\n"
		.'		}'."\n"
		.'	}'."\n"
		."\n"
		.'}'."\n"
		.JS_SUFFIX;

}

// render the page according to the loaded skin
render_skin();

?>