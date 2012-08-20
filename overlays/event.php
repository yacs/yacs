<?php
/**
 * manage a physical event
 *
 * An event has following successive phases:
 * - preparation
 * - enrolment
 * - meeting
 * - follow-up
 *
 * During preparation the event page is under construction, and enrolment is not allowed.
 * An induction message is displayed to alert potential attendees of the event.
 *
 * During enrolment, the page allows for registration of participants. The page displays the
 * induction message and adds event details, such as event location. Details can be changed in
 * derived class if necessary. Instructions are provided to join the meeting as well.
 *
 * Three types of enrolment are provided:
 * - any visitor can participate, and actual participation can be regulated if necessary by adjusting access rights of the page itself
 * - visitors ask for an invitation, and page owner confirms enrolment.
 * Enrolment is confirmed by an e-mail notification.
 * The Join the meeting button is provided only to enrolled persons
 * - page owner enrolls people manually, and each of them receives a notification by e-mail.
 *
 * The event page can be populated with pre-reading material, if necessary, or with links to
 * related pages. Comments in a wall can be used both to provide news and to answer questions, if any.
 * Page owner is provided an additional button to invite people by e-mail, or to send a reminder to
 * potential participants.
 *
 * The lobby is opened one hour before the planned beggining of the event, and a welcome message is
 * displayed automatically at the planned date for the start. Then a button is displayed to allow participants
 * to join the event. Comments in the wall can be used to provide support if necessary.
 *
 * At the end of the event, a follow-up message is displayed.
 * Comments in the wall can be used to provide additional directions, or to answer after-thought questions.
 *
 * Workflow status is captured in one single parameter, with following values:
 * - default - the induction message is displayed
 * - 'open' - enrolment message is displayed, audience can confirm attendance
 * - 'lobby' - welcome message is displayed, a count-down is displayed
 * - 'started' - joining message is displayed, actual attendance is captured
 * - 'stopped' - follow-up message is displayed
 *
 * On this specific overlay the start and the stop transitions are done automatically.
 * However this can be changed in derived classes, to better adapt to various event back-ends.
 * This class provides a framework that allows page owners to manage transitions through following actions:
 * - 'open' to activate enrolment
 * - 'start' to actually initiate the meeting, and to let attendees participate to it
 * - 'stop' to stop the meeting
 *
 * @see overlays/bbb_meeting.php
 * @see overlays/chat_meeting.php
 * @see overlays/dimdim_meeting.php
 * @see overlays/meeting.php
 *
 * When configured as being the overlay of pages in a section, this overlay formats events as
 * a dynamic calendar. Following parameters can be used to change the default behavior:
 *
 * - layout_as_list - When this parameter is used, dates are just listed.
 * Else dates are laid out in monthly calendars.
 *
 * - with_past_dates - When this parameter is provided, all dates are listed.
 * When the parameter is absent, only future dates are included, and a
 * separate list shows past dates to associates and to section editors.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Event extends Overlay {

	/**
	 * filter invitation message
	 *
	 * @see articles/invite.php
	 *
	 * @param string captured message
	 * @return string to be actually sent
	 */
	function filter_invite_message($text) {
		global $context;

		switch($this->attributes['enrolment']) {

		case 'manual': // call for review
			break;

		case 'none': // call for confirmation
		default:
			if(!isset($_REQUEST['force_enrolment']) || ($_REQUEST['force_enrolment'] != 'Y'))
				$text .= '<div><p>'.i18n::c('Thanks to confirm your participation.').'</p></div>';
			break;

		case 'validate': // call for application
			if(!isset($_REQUEST['force_enrolment']) || ($_REQUEST['force_enrolment'] != 'Y'))
				$text .= '<div><p>'.i18n::c('Ask for an invitation if you are interested.').'</p></div>';
			break;

		}

		// how to update the calendar?
		$text .= '<div><p>'.i18n::s('Use the file attached to update your calendar.').'</p></div>';

		// done
		return $text;
	}

	/**
	 * get an input field to capture meeting chairman
	 *
	 * @return string to be integrated into the editing form
	 */
	function get_chairman_input() {
		global $context;

		// capture chairman's id
		if(isset($this->attributes['chairman']) && ($user = Users::get($this->attributes['chairman'])))
			$value = $user['nick_name'];
		else
			$value = '';
		$input = '<input type="text" name="chairman" id="chairman" value ="'.encode_field($value).'" size="25" maxlength="32" />'
			.BR.'<span class="small">'.i18n::s('Type some letters of the name and select in the list').'</span></div>';
		// append the script used for autocompletion
		$context['page_footer'] .= JS_PREFIX
			.'$(function() { Yacs.autocomplete_names("chairman",true); });'."\n" // enable chairman autocompletion
			.JS_SUFFIX;
		// done
		return $input;
	}

	/**
	 * text to be inserted into a mail notification
	 *
	 * This function is useless for this overlay since notifications refer to
	 * .ics data that are attached to them.
	 *
	 * @see overlays/overlay.php
	 *
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function get_diff_text($host=NULL) {
		return '';
	}

	/**
	 * get label for event details
	 *
	 * @return string text to be displayed, or NULL
	 */
	function get_event_details_label() {
		global $context;

		return i18n::s('Meeting location');
	}

	/**
	 * get event details
	 *
	 * @return string text to be displayed, or NULL
	 */
	function get_event_details_text() {
		global $context;

		// format the event location
		if(isset($this->attributes['meeting_location']))
			return Codes::beautify($this->attributes['meeting_location'], 'hardcoded');

		// nothing to do
		return NULL;
	}

	/**
	 * get input fields for event details
	 *
	 * @return an array of fields or NULL
	 */
	function get_event_fields() {
		global $context;

		// returned fields
		$fields = array();

		// meeting location
		$label = $this->get_event_details_label();
		if(!isset($this->attributes['meeting_location']))
			$this->attributes['meeting_location'] = '';
		$input = '<textarea name="meeting_location" rows="4" cols="50">'.encode_field($this->attributes['meeting_location']).'</textarea>';
		$hint = i18n::s('Provide directions to join the meeting');
		$fields[] = array($label, $input, $hint);

		// add these tabs
		return $fields;
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

		// default value is now
		if(!isset($this->attributes['date_stamp']) || ($this->attributes['date_stamp'] <= NULL_DATE))
			$this->attributes['date_stamp'] = gmstrftime('%Y-%m-%d %H:%M', time() + (Surfer::get_gmt_offset() * 3600));

		// adjust to surfer time zone
		else
			$this->attributes['date_stamp'] = Surfer::from_GMT($this->attributes['date_stamp']);

		// split date from time
		list($date, $time) = explode(' ', $this->attributes['date_stamp']);

		// a list for time
		$options = '<select name="time_stamp">';
		list($hours, $minutes) = explode(':', $time);
		if($hours > '23')
			$minutes = '23';
		if($minutes > '30')
			$minutes = '30';
		else
			$minutes = '00';

		if(($hours == '00') && ($minutes == '00'))
			$options .= '<option selected="selected">00:00</option>';
		else
			$options .= '<option>00:00</option>';
		if(($hours == '00') && ($minutes == '30'))
			$options .= '<option selected="selected">00:30</option>';
		else
			$options .= '<option>00:30</option>';

		if(($hours == '01') && ($minutes == '00'))
			$options .= '<option selected="selected">01:00</option>';
		else
			$options .= '<option>01:00</option>';
		if(($hours == '01') && ($minutes == '30'))
			$options .= '<option selected="selected">01:30</option>';
		else
			$options .= '<option>01:30</option>';

		if(($hours == '02') && ($minutes == '00'))
			$options .= '<option selected="selected">02:00</option>';
		else
			$options .= '<option>02:00</option>';
		if(($hours == '02') && ($minutes == '30'))
			$options .= '<option selected="selected">02:30</option>';
		else
			$options .= '<option>02:30</option>';

		if(($hours == '03') && ($minutes == '00'))
			$options .= '<option selected="selected">03:00</option>';
		else
			$options .= '<option>03:00</option>';
		if(($hours == '03') && ($minutes == '30'))
			$options .= '<option selected="selected">03:30</option>';
		else
			$options .= '<option>03:30</option>';

		if(($hours == '04') && ($minutes == '00'))
			$options .= '<option selected="selected">04:00</option>';
		else
			$options .= '<option>04:00</option>';
		if(($hours == '04') && ($minutes == '30'))
			$options .= '<option selected="selected">04:30</option>';
		else
			$options .= '<option>04:30</option>';

		if(($hours == '05') && ($minutes == '00'))
			$options .= '<option selected="selected">05:00</option>';
		else
			$options .= '<option>05:00</option>';
		if(($hours == '05') && ($minutes == '30'))
			$options .= '<option selected="selected">05:30</option>';
		else
			$options .= '<option>05:30</option>';

		if(($hours == '06') && ($minutes == '00'))
			$options .= '<option selected="selected">06:00</option>';
		else
			$options .= '<option>06:00</option>';
		if(($hours == '06') && ($minutes == '30'))
			$options .= '<option selected="selected">06:30</option>';
		else
			$options .= '<option>06:30</option>';

		if(($hours == '07') && ($minutes == '00'))
			$options .= '<option selected="selected">07:00</option>';
		else
			$options .= '<option>07:00</option>';
		if(($hours == '07') && ($minutes == '30'))
			$options .= '<option selected="selected">07:30</option>';
		else
			$options .= '<option>07:30</option>';

		if(($hours == '08') && ($minutes == '00'))
			$options .= '<option selected="selected">08:00</option>';
		else
			$options .= '<option>08:00</option>';
		if(($hours == '08') && ($minutes == '30'))
			$options .= '<option selected="selected">08:030</option>';
		else
			$options .= '<option>08:30</option>';

		if(($hours == '09') && ($minutes == '00'))
			$options .= '<option selected="selected">09:00</option>';
		else
			$options .= '<option>09:00</option>';
		if(($hours == '09') && ($minutes == '30'))
			$options .= '<option selected="selected">09:30</option>';
		else
			$options .= '<option>09:30</option>';

		if(($hours == '10') && ($minutes == '00'))
			$options .= '<option selected="selected">10:00</option>';
		else
			$options .= '<option>10:00</option>';
		if(($hours == '10') && ($minutes == '30'))
			$options .= '<option selected="selected">10:30</option>';
		else
			$options .= '<option>10:30</option>';

		if(($hours == '11') && ($minutes == '00'))
			$options .= '<option selected="selected">11:00</option>';
		else
			$options .= '<option>11:00</option>';
		if(($hours == '11') && ($minutes == '30'))
			$options .= '<option selected="selected">11:30</option>';
		else
			$options .= '<option>11:30</option>';

		if(($hours == '12') && ($minutes == '00'))
			$options .= '<option selected="selected">12:00</option>';
		else
			$options .= '<option>12:00</option>';
		if(($hours == '12') && ($minutes == '30'))
			$options .= '<option selected="selected">12:30</option>';
		else
			$options .= '<option>12:30</option>';

		if(($hours == '13') && ($minutes == '00'))
			$options .= '<option selected="selected">13:00</option>';
		else
			$options .= '<option>13:00</option>';
		if(($hours == '13') && ($minutes == '30'))
			$options .= '<option selected="selected">13:30</option>';
		else
			$options .= '<option>13:30</option>';

		if(($hours == '14') && ($minutes == '00'))
			$options .= '<option selected="selected">14:00</option>';
		else
			$options .= '<option>14:00</option>';
		if(($hours == '14') && ($minutes == '30'))
			$options .= '<option selected="selected">14:30</option>';
		else
			$options .= '<option>14:30</option>';

		if(($hours == '15') && ($minutes == '00'))
			$options .= '<option selected="selected">15:00</option>';
		else
			$options .= '<option>15:00</option>';
		if(($hours == '15') && ($minutes == '30'))
			$options .= '<option selected="selected">15:30</option>';
		else
			$options .= '<option>15:30</option>';

		if(($hours == '16') && ($minutes == '00'))
			$options .= '<option selected="selected">16:00</option>';
		else
			$options .= '<option>16:00</option>';
		if(($hours == '16') && ($minutes == '30'))
			$options .= '<option selected="selected">16:30</option>';
		else
			$options .= '<option>16:30</option>';

		if(($hours == '17') && ($minutes == '00'))
			$options .= '<option selected="selected">17:00</option>';
		else
			$options .= '<option>17:00</option>';
		if(($hours == '17') && ($minutes == '30'))
			$options .= '<option selected="selected">17:30</option>';
		else
			$options .= '<option>17:30</option>';

		if(($hours == '18') && ($minutes == '00'))
			$options .= '<option selected="selected">18:00</option>';
		else
			$options .= '<option>18:00</option>';
		if(($hours == '18') && ($minutes == '30'))
			$options .= '<option selected="selected">18:30</option>';
		else
			$options .= '<option>18:30</option>';

		if(($hours == '19') && ($minutes == '00'))
			$options .= '<option selected="selected">19:00</option>';
		else
			$options .= '<option>19:00</option>';
		if(($hours == '19') && ($minutes == '30'))
			$options .= '<option selected="selected">19:30</option>';
		else
			$options .= '<option>19:30</option>';

		if(($hours == '20') && ($minutes == '00'))
			$options .= '<option selected="selected">20:00</option>';
		else
			$options .= '<option>20:00</option>';
		if(($hours == '20') && ($minutes == '30'))
			$options .= '<option selected="selected">20:30</option>';
		else
			$options .= '<option>20:30</option>';

		if(($hours == '21') && ($minutes == '00'))
			$options .= '<option selected="selected">21:00</option>';
		else
			$options .= '<option>21:00</option>';
		if(($hours == '21') && ($minutes == '30'))
			$options .= '<option selected="selected">21:30</option>';
		else
			$options .= '<option>21:30</option>';

		if(($hours == '22') && ($minutes == '00'))
			$options .= '<option selected="selected">22:00</option>';
		else
			$options .= '<option>22:00</option>';
		if(($hours == '22') && ($minutes == '30'))
			$options .= '<option selected="selected">22:30</option>';
		else
			$options .= '<option>22:30</option>';

		if(($hours == '23') && ($minutes == '00'))
			$options .= '<option selected="selected">23:00</option>';
		else
			$options .= '<option>23:00</option>';
		if(($hours == '23') && ($minutes == '30'))
			$options .= '<option selected="selected">23:30</option>';
		else
			$options .= '<option>23:30</option>';

		$options .= '</options>';

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

		// duration
		$label = i18n::s('Duration');
		$input = '<select name="duration">';
		if(!isset($this->attributes['duration']) || ($this->attributes['duration'] < 15) || ($this->attributes['duration'] > 1440))
			$this->attributes['duration'] = 60;
		$input .= '<option value="15"'.(($this->attributes['duration'] == 15)?' selected="selected"':'').'>'.sprintf(i18n::s('%d minutes'), 15).'</option>';
		$input .= '<option value="30"'.(($this->attributes['duration'] == 30)?' selected="selected"':'').'>'.sprintf(i18n::s('%d minutes'), 30).'</option>';
		$input .= '<option value="45"'.(($this->attributes['duration'] == 45)?' selected="selected"':'').'>'.sprintf(i18n::s('%d minutes'), 45).'</option>';
		$input .= '<option value="60"'.(($this->attributes['duration'] == 60)?' selected="selected"':'').'>'.i18n::s('one hour').'</option>';
		$input .= '<option value="120"'.(($this->attributes['duration'] == 120)?' selected="selected"':'').'>'.i18n::s('two hours').'</option>';
		$input .= '<option value="1440"'.(($this->attributes['duration'] == 1440)?' selected="selected"':'').'>'.i18n::s('all day').'</option>';
		$input .= '</select>';
		$fields[] = array($label, $input);

		return $fields;
	}

	/**
	 * get the default message for event follow-up
	 *
	 * This function is invoked from the form used to capture event details.
	 *
	 * @return string the message
	 */
	function get_follow_up_default_message() {
		global $context;

		return '';
	}

	/**
	 * get event description in ICS format
	 *
	 * @param string either 'PUBLISH', 'REQUEST', 'CANCEL', ...
	 * @return string content of the ICS
	 *
	 * @see articles/invite.php
	 * @see overlays/events/fetch_ics.php
	 */
	function get_ics($method='PUBLISH') {
		global $context;

		// begin calendar
		$text = 'BEGIN:VCALENDAR'.CRLF
			.'VERSION:2.0'.CRLF
			.'PRODID:'.$context['host_name'].CRLF;

		// method
		$text .= 'METHOD:'.$method.CRLF;

		// begin event
		$text .= 'BEGIN:VEVENT'.CRLF;

		// date of creation
		if($value = $this->anchor->get_value('create_date'))
			$text .= 'DTSTAMP:'.gmdate('Ymd\THis\Z', SQL::strtotime($value)).CRLF;

		// date of last modification --also required by Outlook
		if($value = $this->anchor->get_value('edit_date'))
			$text .= 'LAST-MODIFICATION:'.gmdate('Ymd\THis\Z', SQL::strtotime($value)).CRLF;

		// the event spans limited time --duration is expressed in minutes
		if(isset($this->attributes['duration']) && $this->attributes['duration'] && ($this->attributes['duration'] < 1440)) {
			$text .= 'DTSTART:'.str_replace(array('-', ' ', ':'), array('', 'T', ''), $this->attributes['date_stamp']).'Z'.CRLF;
			$text .= 'DTEND:'.gmdate('Ymd\THis\Z', SQL::strtotime($this->attributes['date_stamp'])+($this->attributes['duration']*60)).CRLF;

		// a full-day event
		} elseif(isset($this->attributes['date_stamp'])) {
			$text .= 'DTSTART;VALUE=DATE:'.date('Ymd', SQL::strtotime($this->attributes['date_stamp'])).CRLF;
			$text .= 'DTEND;VALUE=DATE:'.date('Ymd', SQL::strtotime($this->attributes['date_stamp'])+86400).CRLF;
		}

		// url to view the date
		$text .= 'URL:'.$context['url_to_home'].$context['url_to_root'].$this->anchor->get_url().CRLF;;

		// location is the yacs server
		$text .= 'LOCATION:'.$context['host_name'].CRLF;

		// build a valid title
		if($value = $this->anchor->get_title())
			$text .= 'SUMMARY:'.Codes::beautify_title($value).CRLF;

		// description is partially automated
		$text .= 'DESCRIPTION:';
		if($value = $this->anchor->get_title())
			$text .= sprintf(i18n::s('%s: %s'), i18n::s('Topic'), str_replace(array("\n", "\r"), ' ', Codes::beautify_title($value))).'\n';

		// dates
		if(isset($this->attributes['date_stamp']) && $this->attributes['date_stamp'])
			$text .= sprintf(i18n::s('%s: %s'), i18n::s('Date'), Skin::build_date($this->attributes['date_stamp'], 'standalone')).'\n';
		if(isset($this->attributes['duration']) && $this->attributes['duration'] && ($this->attributes['duration'] < 1440))
			$text .= sprintf(i18n::s('%s: %s'), i18n::s('Duration'), $this->attributes['duration'].' '.i18n::s('minutes')).'\n';

		// build a link to the chairman page, if any
		if(isset($this->attributes['chairman']) && ($user = Users::get($this->attributes['chairman'])))
			$text .= sprintf(i18n::s('%s: %s'), i18n::s('Chairman'), $user['full_name']).'\n';
		elseif(($owner = $this->anchor->get_value('owner_id')) && ($user = Users::get($owner)))
			$text .= sprintf(i18n::s('%s: %s'), i18n::s('Chairman'), $user['full_name']).'\n';

		// location
		if($method != 'CANCEL')
			$text .= sprintf(i18n::s('%s: %s'), i18n::s('Location'), $context['url_to_home'].$context['url_to_root'].$this->anchor->get_url()).'\n';

		// meeting has been cancelled
		if($method == 'CANCEL') {
			$text .= '\n\n'.i18n::c('Meeting has been cancelled.');

		// regular meeting
		} else {

			// copy content of the introduction field, if any
			if($value = $this->anchor->get_value('introduction'))
				$text .= '\n'.strip_tags(str_replace(array('</', '/>', "\n", "\r", ','), array('\n</', '/>\n', '\n', ' ', '\,'), Codes::beautify($value)));

			// copy the induction message, if any
			if(isset($this->attributes['induction_message']))
				$text .= '\n'.strip_tags(str_replace(array('</', '/>', "\n", "\r", ','), array('\n</', '/>\n', '\n', ' ', '\,'), Codes::render($this->attributes['induction_message'])));

		}

		// end of the description field
		$text .= CRLF;

		// may be used for updates or for cancellation --required by Outlook 2003
		$text .= 'UID:'.crc32($this->anchor->get_reference().':'.$context['url_to_root']).'@'.$context['host_name'].CRLF;

		// maybe this one has been cancelled
		if($method == 'CANCEL')
			$text .= 'STATUS:CANCELLED'.CRLF;

		// sequence is incremented on each revision
		include_once $context['path_to_root'].'versions/versions.php';
		$versions = Versions::count_for_anchor($this->anchor->get_reference());
		$text .= 'SEQUENCE:'.$versions.CRLF;

		// more attributes
		$text .= 'PRIORITY:5'.CRLF
			.'TRANSP:OPAQUE'.CRLF  // block a slot in calendar
			.'CLASS:PUBLIC'.CRLF;

		// alarm to remind the meeting
		if($method != 'CANCEL')
			$text .= 'BEGIN:VALARM'.CRLF
				.'TRIGGER:-PT15M'.CRLF
				.'DESCRIPTION:Reminder'.CRLF
				.'ACTION:DISPLAY'.CRLF
				.'END:VALARM'.CRLF;

		// close event
		$text .= 'END:VEVENT'.CRLF;

		// close calendar
		$text .= 'END:VCALENDAR'.CRLF;

		// done!
		return $text;
	}

	/**
	 * identify one instance
	 *
	 * This function returns a string that identify uniquely one overlay instance.
	 * When this information is saved, it can be used later on to retrieve one page
	 * and its content.
	 *
	 * @returns a unique string, or NULL
	 *
	 * @see articles/edit.php
	 */
	function get_id() {
		if(isset($this->attributes['date_stamp']))
			return 'event:'.$this->attributes['date_stamp'];
		return NULL;
	}

	/**
	 * get the default message for event induction
	 *
	 * This function is invoked from the form used to capture event details.
	 *
	 * @return string the message
	 */
	function get_induction_default_message() {
		global $context;

		return '';
	}

	/**
	 * get invitation attachments
	 *
	 * To be transmitted to invitation messages.
	 *
	 * @see articles/invite.php
	 *
	 * @param string either 'PUBLISH' or 'CANCEL'

	 * @return mixed an array of file names, or NULL
	 */
	function get_invite_attachments($method='PUBLISH') {
		global $context;

		// attach an invitation file
		return array('text/calendar; method="'.$method.'"; charset="UTF-8"; name="'.str_replace('"', "'", $this->anchor->get_title()).'.ics"' => $this->get_ics($method));
	}

	/**
	 * get invitation default message
	 *
	 * This is put in the invitation form.
	 *
	 * @see articles/invite.php
	 *
	 * @param string 'PUBLISH' or 'CANCEL'
	 * @return string to be put in the web form
	 */
	function get_invite_default_message($method='PUBLISH') {
		global $context;

		// to be displayed into the web form for this invitation
		$text = '';

		if($value = $this->anchor->get_title())
			$text .= sprintf(i18n::s('%s: %s'), i18n::s('Topic'), Skin::build_link($context['url_to_home'].$context['url_to_root'].$this->anchor->get_url(), Codes::beautify_title($value))).BR;

		// dates
		if(isset($this->attributes['date_stamp']) && $this->attributes['date_stamp'])
			$text .= sprintf(i18n::s('%s: %s'), i18n::s('Date'), Skin::build_date($this->attributes['date_stamp'], 'standalone')).BR;
		if(isset($this->attributes['duration']) && $this->attributes['duration'] && ($this->attributes['duration'] < 1440))
			$text .= sprintf(i18n::s('%s: %s'), i18n::s('Duration'), $this->attributes['duration'].' '.i18n::s('minutes')).BR;

		// build a link to the chairman page, if any
		if(isset($this->attributes['chairman']) && ($user = Users::get($this->attributes['chairman'])))
			$text .= sprintf(i18n::s('%s: %s'), i18n::s('Chairman'), Users::get_link($user['full_name'], NULL, $user['id'])).BR;

		// meeting has been cancelled
		if($method == 'CANCEL')
			$text .= '<div><p>'.i18n::c('Meeting has been cancelled.').'</p></div>';

		// regular message
		else {

			// copy content of the introduction field, if any
			if($value = $this->anchor->get_value('introduction'))
				$text .= '<div>'.Codes::beautify('<p>'.$value.'</p>').'</div>';

			// copy the induction message, if any
			if(isset($this->attributes['induction_message']))
				$text .= '<div>'.Codes::render($this->attributes['induction_message']).'</div>';

		}

		// done
		return $text;
	}

	/**
	 * allow for different role
	 *
	 * @return string to be put in web form
	 */
	function get_invite_roles() {
		global $context;

		switch($this->attributes['enrolment']) {
		case 'manual':
			return '';
		case 'none':
		default:
			return '<p><input type="radio" name="force_enrolment" value="N" /> '.i18n::s('to review this event and confirm their participation')
				.BR.'<input type="radio" name="force_enrolment" value="Y" checked="checked" /> '.i18n::s('to be notified of their enrolment').'</p><hr>';
			break;
		case 'validate':
			return '<p><input type="radio" name="force_enrolment" value="N" checked="checked" /> '.i18n::s('to review this event and ask for an invitation')
				.BR.'<input type="radio" name="force_enrolment" value="Y" /> '.i18n::s('to be notified of their enrolment').'</p><hr>';
			break;
		}

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
	 * @param boolean TRUE to localize as per surfer settings, FALSE to localize as per community settings
	 * @return the label to use
	 */
	function get_label($name, $action='view', $surfer=TRUE) {
		global $context;

		switch($name.':'.$action) {

		case 'edit_command:articles':
		case 'edit_command:sections':
			if($surfer)
				return i18n::s('Edit this event');
			return i18n::c('Edit this event');

		case 'new_command:articles':
		case 'new_command:sections':
			if($surfer)
				return i18n::s('Add an event');
			return i18n::c('Add an event');

		case 'permalink_command:articles':
		case 'permalink_command:sections':
			if($surfer)
				return i18n::s('View event details');
			return i18n::c('View event details');

		case 'page_title:edit':
			if($surfer)
				return i18n::s('Edit an event');
			return i18n::c('Edit an event');

		case 'page_title:delete':
			if($surfer)
				return i18n::s('Delete an event');
			return i18n::c('Delete an event');

		case 'page_title:new':
			if($surfer)
				return i18n::s('Add an event');
			return i18n::c('Add an event');

		}

		// no match
		return NULL;
	}

	/**
	 * display a live title
	 *
	 * Add the actual date to page title
	 *
	 * @see overlays/overlay.php
	 *
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_live_title($host=NULL) {

		$text = $host['title'];

		if(isset($this->attributes['date_stamp']) && ($this->attributes['date_stamp'] > NULL_DATE))
			$text .= ' ['.Skin::build_date($this->attributes['date_stamp'], 'day').']';

		return $text;
	}

	/**
	 * get the default message for people entering the lobby
	 *
	 * This function is invoked from the form used to capture event details.
	 *
	 * @return string the message
	 */
	function get_lobby_default_message() {
		global $context;

		return '';
	}

	/**
	 * get the label to put aside message specific to internal state
	 *
	 * @return string to be used as message title
	 */
	function get_message_label() {

		switch($this->attributes['status']) {
		case 'created':
		case 'open':
		default:
			return i18n::s('Description');
		case 'lobby':
		case 'started':
			return i18n::s('Welcome');
		case 'stopped':
			return i18n::s('Follow-up');
		}

	}

	/**
	 * the URL to open enrolment and to update the hosting page
	 *
	 * @see overlays/events/open.php
	 *
	 * @return string the URL to redirect the user to the meeting, or NULL on error
	 */
	function get_open_url() {
		global $context;

		// sanity check
		if(($this->attributes['status'] != 'created') && ($this->attributes['status'] != 'open'))
			return $context['url_to_home'].$context['url_to_root'].$this->anchor->get_url();

		// enroll some participants
		if(isset($this->attributes['enrolment']) && ($this->attributes['enrolment'] == 'manual'))
			return $this->get_url('enroll');

		// demonstrate that enrolment is now open
		return $context['url_to_home'].$context['url_to_root'].$this->anchor->get_url();
	}

	/**
	 * get an input field to capture the number of seats
	 *
	 * @return string to be integrated into the editing form
	 */
	function get_seats_input() {
		global $context;

		// number of seats
		if(!isset($this->attributes['seats']) || ($this->attributes['seats'] < 1))
			$this->attributes['seats'] = 20;
		return '<input type="text" name="seats" value="'.encode_field($this->attributes['seats']).'" size="2" />';

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
			return i18n::s('Event is under preparation');

		case 'open':
			return i18n::s('Enrolment is open');

		case 'lobby':
			return i18n::s('Event has not started yet');

		case 'started':
			return i18n::s('Event has started');

		case 'stopped':
			return i18n::s('Event is over');

		}
	}

	/**
	 * the URL to stop the event
	 *
	 * @see overlays/events/stop.php
	 *
	 * @return string the URL to redirect the user after the event, or NULL on error
	 */
	function get_stop_url() {
		global $context;

		// redirect to the main page
		if(is_object($this->anchor))
			return $context['url_to_home'].$context['url_to_root'].$this->anchor->get_url();

		// problem, darling!
		return NULL;
	}

	/**
	 * add some tabs
	 *
	 * Manage the event in a separate panel
	 *
	 * Accepted action codes:
	 * - 'edit' - embedded into the main form page
	 *
	 * @see overlays/overlay.php
	 *
	 * @param string the on-going action
	 * @param array the hosting record
	 * @return an array of array('tab_id', 'tab_label', 'panel_id', 'panel_content') or NULL
	 */
	function &get_tabs($variant='view', $host=NULL) {
		global $context, $local;

		// returned tabs
		$tabs = array();

		$now = strftime('%Y-%m-%d %H:%M:%S', time() + ((Surfer::get_gmt_offset() - intval($context['gmt_offset'])) * 3600));

		// trackings
		//
		$manage = '';
		$fields = array();

		// manage the event
		if(($variant == 'edit')) {

			// event preparation
			$manage .= Skin::build_block(i18n::s('Event preparation'), 'header2');

			// induction message
			$label = i18n::s('Induction message');
			if(!isset($this->attributes['induction_message']))
				$this->attributes['induction_message'] = $this->get_induction_default_message();
			$input = Surfer::get_editor('induction_message', $this->attributes['induction_message']);
			$hint = i18n::s('Displayed until the beginning of the event.');
			$fields[] = array($label, $input, $hint);

			// other event details
			$fields = array_merge($fields, $this->get_event_fields());

			// should we manage enrolment?
			if($this->with_enrolment()) {

				// enrolment
				$label = i18n::s('Enrolment');

				// none
				if(!isset($this->attributes['enrolment']))
					$this->attributes['enrolment'] = 'none';
				$input = '<input type="radio" name="enrolment" value="none"';
				if(!isset($this->attributes['enrolment']) || ($this->attributes['enrolment'] == 'none'))
					$input .= ' checked="checked"';
				$input .= '/> '.i18n::s('Any page visitor can participate').BR;

				// apply-and-validate
				$input .= '<input type="radio" name="enrolment" value="validate"';
				if(!isset($this->attributes['enrolment']) || ($this->attributes['enrolment'] == 'validate'))
					$input .= ' checked="checked"';
				$input .= '/> '.i18n::s('Accept applications, to be confirmed by page owner').BR;

				// manual registration
				$input .= '<input type="radio" name="enrolment" value="manual"';
				if(isset($this->attributes['enrolment']) && ($this->attributes['enrolment'] == 'manual'))
					$input .= ' checked="checked"';
				$input .= '/> '.i18n::s('Registration is managed by page owner').BR;

				// expand the form
				$fields[] = array($label, $input);

			}

			$manage .= Skin::build_form($fields);
			$fields = array();

			// meeting initiation
			$manage .= Skin::build_block(i18n::s('During the event'), 'header2');

			// lobby message
			$label = i18n::s('Lobby message');
			if(!isset($this->attributes['lobby_message']))
				$this->attributes['lobby_message'] = $this->get_lobby_default_message();
			$input = '<textarea name="lobby_message" rows="2" cols="50">'.encode_field($this->attributes['lobby_message']).'</textarea>';
			$hint = i18n::s('Displayed one hour ahead the beginning of the event.');
			$fields[] = array($label, $input, $hint);

			// welcome message
			$label = i18n::s('Welcome message');
			if(!isset($this->attributes['welcome_message']))
				$this->attributes['welcome_message'] = '';
			$input = Surfer::get_editor('welcome_message', $this->attributes['welcome_message']);
			$hint = i18n::s('Displayed only during the event.');
			$fields[] = array($label, $input, $hint);

			// assemble the form
			$manage .= Skin::build_form($fields);
			$fields = array();

			// event follow-up
			$manage .= Skin::build_block(i18n::s('After the event'), 'header2');

			// splash message
			$label = i18n::s('Follow-up message');
			if(!isset($this->attributes['follow_up_message']))
				$this->attributes['follow_up_message'] = $this->get_follow_up_default_message();
			$input = Surfer::get_editor('follow_up_message', $this->attributes['follow_up_message']);
			$hint = i18n::s('Congratulate participants, and drive people to complementary information or action.');
			$fields[] = array($label, $input, $hint);

			$manage .= Skin::build_form($fields);
			$fields = array();

		}

		// finalize this tab
		if($manage)
			$tabs[] = array('management', i18n::s('Management'), 'management_panel', $manage);

		// add these tabs
		return $tabs;
	}

	/**
	 * get the address for some control function
	 *
	 * @param string function to execute
	 * @return string the full URL to activate the function, or NULL on error
	 */
	function get_url($action) {
		global $context;

		// we need to have an anchor
		if(!is_callable(array($this->anchor, 'get_reference')))
			return NULL;

		switch($action) {

		case 'apply':	// ask for an invitation
			return $context['url_to_home'].$context['url_to_root'].'overlays/events/apply.php?id='.urlencode($this->anchor->get_reference());

		case 'enroll':	// manage registrations
			return $context['url_to_home'].$context['url_to_root'].'overlays/events/enroll.php?id='.urlencode($this->anchor->get_reference());

		case 'fetch_ics':	// update calendar
			return $context['url_to_home'].$context['url_to_root'].'overlays/events/fetch_ics.php?id='.urlencode($this->anchor->get_reference());

		case 'join':	// join the meeting
		default:
			return $context['url_to_home'].$context['url_to_root'].'overlays/events/join.php?id='.urlencode($this->anchor->get_reference());

		case 'open':	// open the event to enrolment
			return $context['url_to_home'].$context['url_to_root'].'overlays/events/open.php?id='.urlencode($this->anchor->get_reference());

		case 'start':	// start the meeting
			return $context['url_to_home'].$context['url_to_root'].'overlays/events/start.php?id='.urlencode($this->anchor->get_reference());

		case 'stop':	// stop the meeting
			return $context['url_to_home'].$context['url_to_root'].'overlays/events/stop.php?id='.urlencode($this->anchor->get_reference());

		}
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

		// we may look at enrolments
		include_once $context['path_to_root'].'shared/enrolments.php';

		// minutes to go
		if(isset($this->attributes['date_stamp']) && ($this->attributes['date_stamp'] > NULL_DATE))
			$this->minutes_before_start = (sql::strtotime($this->attributes['date_stamp']) - time()) / 60;
		else
			$this->minutes_before_start = 120; // prevent automatic start

		// minutes since the end of the event
		if(isset($this->attributes['duration']) && $this->attributes['duration'])
			$this->minutes_since_stop = - ($this->attributes['duration'] + $this->minutes_before_start);
		else
			$this->minutes_since_stop = (-120); // prevent automatic stop

		// tabular information
		$rows = array();

		// initialize feed-back to end-user
		$this->feed_back = array('message' => '',
			'status' => array(),	// several lines to report on meeting status
			'menu' => array(),		// invite, enroll, confirm, ...
			'commands' => array(),	// start, join, stop, ...
			'reload_this_page' => FALSE);;

		// maybe a bare instance
		if(!isset($this->attributes['status']))
			$this->attributes['status'] = 'created';

		// step 5 - end of the event
		if($this->attributes['status'] == 'stopped') {

			// list enrolment for this meeting
			$query = "SELECT * FROM ".SQL::table_name('enrolments')." WHERE anchor LIKE '".SQL::escape($this->anchor->get_reference())."'";
			if($result = SQL::query($query)) {

				// browse the list
				$items = array();
				while($item = SQL::fetch($result)) {

					// a user registered on this server
					if($item['user_id'] && ($user = Users::get($item['user_id']))) {

						// make an url
						$url = Users::get_permalink($user);

						// gather information on this user
						if(isset($user['full_name']) && $user['full_name'])
							$label = $user['full_name'].' ('.$user['nick_name'].')';
						else
							$label = $user['nick_name'];

						$items[] = Skin::build_link($url, $label, 'user');

					// we only have some e-mail address
					} else
						$items[] = $item['user_email'];

				}

				// shape a compact list
				if(count($items))
					$this->feed_back['status'][] = Skin::build_folded_box(i18n::s('Enrolment').' ('.count($items).')', Skin::finalize_list($items, 'compact'));

			}

			// signal that the event is over
			$this->feed_back['status'][] = i18n::s('Event is over');

			// display the follow-up message
			if(isset($this->attributes['follow_up_message']) && $this->attributes['follow_up_message'])
				$this->feed_back['message'] .= Codes::render($this->attributes['follow_up_message']);

		// possible transition to state 'stopped'
		} else
			$this->transition_to_stopped();

		// step 4 - event has started
		if($this->attributes['status'] == 'started') {

			// display the welcome message
			if(isset($this->attributes['welcome_message']))
				$this->feed_back['message'] .= Codes::render($this->attributes['welcome_message']);

		// possible transition to state 'started'
		} else
			$this->transition_to_started();

		// step 3 - waiting for event start
		if($this->attributes['status'] == 'lobby') {

			// display the lobby message
			if(isset($this->attributes['lobby_message']))
				$this->feed_back['message'] .= Codes::render($this->attributes['lobby_message']);

		// possible transition to state 'lobby'
		} else
			$this->transition_to_lobby();

		// step 2 - enrolment has been opened
		if($this->attributes['status'] == 'open') {

			// display the induction message
			if(isset($this->attributes['induction_message']))
				$this->feed_back['message'] .= Codes::render($this->attributes['induction_message']);

		// possible transition to state 'open'
		} else
			$this->transition_to_open();

		// step 1 - at the very beginning of the workflow
		if(!isset($this->attributes['status']) || ($this->attributes['status'] == 'created')) {

			// display the induction message
			if(isset($this->attributes['induction_message']))
				$this->feed_back['message'] .= Codes::render($this->attributes['induction_message']);

		// possible transition to state 'created'
		} else
			$this->transition_to_created();

		// event details
		if($details = $this->get_event_details_text())
			$rows[] = array($this->get_event_details_label(), $details);

		// meeting date
		if(isset($this->attributes['date_stamp']) && $this->attributes['date_stamp']) {

			// offer to update the calendar
			$button = '';
			if($this->attributes['status'] == 'stopped')
				;
			elseif(enrolments::get_record($this->anchor->get_reference()))
				$button = ' '.Skin::build_link($this->get_url('fetch_ics'), '<img src="'.$context['url_to_root'].'included/jscalendar/img.gif" style="border: none; cursor: pointer;" title="'.i18n::s('Update my calendar').'" onmouseover="this.style.background=\'red\';" onmouseout="this.style.background=\'\'" alt="'.i18n::s('Update my calendar').'" />', 'basic');

			$rows[] = array(i18n::s('Date'), Skin::build_date($this->attributes['date_stamp'], 'full').$button);
		}

		// meeting duration
		if(isset($this->attributes['duration']) && $this->attributes['duration'] && ($this->attributes['duration'] < 1440)) {
			switch($this->attributes['duration']) {
			case 60:
				$duration = i18n::s('one hour');
				break;
			case 120:
				$duration = i18n::s('two hours');
				break;
			default:
				$duration = sprintf(i18n::s('%d minutes'), $this->attributes['duration']);
				break;
			}
			$rows[] = array(i18n::s('Duration'), $duration);
		}

		// build a link to the owner page, if any
		if(isset($this->attributes['chairman']) && $this->attributes['chairman']) {
			if($user = Users::get($this->attributes['chairman']))
				$label = Users::get_link($user['full_name'], NULL, $user['id']);
			else
				$label = $this->attributes['chairman'];
			$rows[] = array(i18n::s('Chairman'), $label);
		}

		// finalize status
		if(is_callable(array($this, 'finalize_status')))
			$this->feed_back['status'] = $this->finalize_status($this->feed_back['status']);

		// finalize menu
		if(is_callable(array($this, 'finalize_menu')))
			$this->feed_back['menu'] = $this->finalize_menu($this->feed_back['menu']);

		// we have to refresh the page
		if($this->feed_back['reload_this_page']) {
			$reload_through_javascript = '<img alt="*" src="'.$context['url_to_home'].$context['url_to_root'].'skins/_reference/ajax/ajax_spinner.gif" style="vertical-align:-3px" /> '
					.JS_PREFIX
					.'window.location.reload(true);'."\n"
					.JS_SUFFIX;
			$rows[] = array(i18n::s('Status'), $reload_through_javascript);

		// display the status line and/or buttons
		} elseif(count($this->feed_back['status']) || count($this->feed_back['menu'])) {
			$status = '';

			// embed status line
			if(count($this->feed_back['status']))
				$status .= implode(BR, $this->feed_back['status']);

			// embed menu bar
			if(count($this->feed_back['menu']))
				$status .= Skin::finalize_list($this->feed_back['menu'], 'menu_bar');

			$rows[] = array(i18n::s('Status'), $status);
		}

		// display commands to page owner
		if(count($this->feed_back['commands']))
			$rows[] = array('', Skin::finalize_list($this->feed_back['commands'], 'menu_bar'));

		// format text in a table
		$text = Skin::table(NULL, $rows, 'grid');

		// finalize feed-back
		if($this->feed_back['message'])
			$text .= Skin::build_box($this->get_message_label(), $this->feed_back['message']);

		// allow for extensions
		if(is_callable(array($this, 'get_view_text_extension')))
			$text .= $this->get_view_text_extension();

		// job done
		return $text;
	}

	/**
	 * has the surfer joined this event page?
	 *
	 * @return boolean TRUE or FALSE
	 */
	function has_joined() {
		global $context;

		// sanity check
		if(!is_callable(array($this->anchor, 'get_reference')))
			return FALSE;

		// cookie is present
		elseif(isset($_SESSION['event_'.$this->anchor->get_reference()]))
			return TRUE;

		// not joined
		return FALSE;
	}

	/**
	 * enroll one user
	 *
	 * This function ensure that an invited person has been enrolled to this event.
	 *
	 * @see articles/invite.php
	 *
	 * @param int id of the enrolled person
	 */
	function invite($id) {
		global $context;

		// force enrolment only if required
		if(!isset($_REQUEST['force_enrolment']) || ($_REQUEST['force_enrolment'] != 'Y'))
			return;

		// get matching profile
		if(($user = Users::get($id)) && is_callable(array($this->anchor, 'get_reference'))) {

			// if there is no enrolment record yet
			$query = "SELECT id FROM ".SQL::table_name('enrolments')." WHERE (anchor LIKE '".SQL::escape($this->anchor->get_reference())."') AND (user_id = ".SQL::escape($user['id']).")";
			if(!SQL::query_count($query)) {

				// fields to save
				$query = array();

				// reference to the meeting page
				$query[] = "anchor = '".SQL::escape($this->anchor->get_reference())."'";

				// direct enrolment
				$query[] = "approved = 'Y'";

				// save user id
				$query[] = "user_id = ".SQL::escape($user['id']);

				// save user e-mail address
				$query[] = "user_email = '".SQL::escape($user['email'])."'";

				// insert a new record
				$query = "INSERT INTO ".SQL::table_name('enrolments')." SET ".implode(', ', $query);
				SQL::query($query);

			}
		}

	}

	/**
	 * is the event on-going?
	 *
	 * @return boolean TRUE if the meeting is on-going, FALSE otherwise
	 */
	function is_running() {
		global $context;

		// check our status
		if(isset($this->attributes['status']) && ($this->attributes['status'] == 'started'))
			return TRUE;

		// probably no
		return FALSE;
	}

	/**
	 * remember that surfer is joining a meeting
	 *
	 */
	function join_meeting() {
		global $context;

		// sanity check
		if(!is_callable(array($this->anchor, 'get_reference')))
			return;

		// create a comment only on first join, and if not a robot
		if(!Surfer::is_crawler() && !isset($_SESSION['event_'.$this->anchor->get_reference()])) {

			// track the new participant
			include_once $context['path_to_root'].'comments/comments.php';
			$fields = array();
			$fields['anchor'] = $this->anchor->get_reference();
			$fields['description'] = sprintf(i18n::s('%s has joined the meeting'), Surfer::get_name());
			$fields['type'] = 'notification';
			Comments::post($fields);
		}

		// remember that you joined the event
		$_SESSION['event_'.$this->anchor->get_reference()] = TRUE;

		// additional steps only for authenticated users
		if(!Surfer::get_id())
			return;

		// add this page to the watching list of this surfer
		Members::assign($this->anchor->get_reference(), 'user:'.Surfer::get_id());

		// update enrolment
		include_once $context['path_to_root'].'shared/enrolments.php';
		enrolments::confirm($this->anchor->get_reference());

	}

	/**
	 * open enrolment to the event
	 *
	 */
	function open_event() {
		global $context;

		// move to the 'open' status
		$fields = array('status' => 'open');
		$this->set_values($fields);

		// track the beginning of event enrolment but only when users are asking for some invitation
		if($this->attributes['enrolment'] == 'validate') {
			include_once $context['path_to_root'].'comments/comments.php';
			if(is_callable(array($this->anchor, 'get_reference'))) {
				$fields = array();
				$fields['anchor'] = $this->anchor->get_reference();
				$fields['description'] = sprintf(i18n::s('%s has open enrolment to the event'), Surfer::get_name());
				$fields['type'] = 'notification';
				Comments::post($fields);
			}
		}

		// update enrolment for the surfer doing the action
		include_once $context['path_to_root'].'shared/enrolments.php';
		if(is_callable(array($this->anchor, 'get_reference')))
			enrolments::confirm($this->anchor->get_reference());

	}

	/**
	 * retrieve meeting specific parameters
	 *
	 * @param the fields as filled by the end user
	 */
	function parse_event_fields($fields) {

		// meeting location
		$this->attributes['meeting_location'] = isset($fields['meeting_location']) ? $fields['meeting_location'] : '';

	}

	/**
	 * retrieve the content of one modified overlay
	 *
	 * @see overlays/overlay.php
	 *
	 * @param the fields as filled by the end user
	 */
	function parse_fields($fields) {

		// set initial status
		if(!isset($this->attributes['status']))
			$this->attributes['status'] = 'created';

		// date and time
		$this->attributes['date_stamp'] = isset($fields['date_stamp']) ? $fields['date_stamp'] : '';
		if($this->attributes['date_stamp'] && isset($fields['time_stamp']) && $fields['time_stamp'])
			$this->attributes['date_stamp'] .= ' '.$fields['time_stamp'];

		// convert date and time from surfer time zone to GMT
		$this->attributes['date_stamp'] = Surfer::to_GMT($this->attributes['date_stamp']);

		// duration
		$this->attributes['duration'] = isset($fields['duration']) ? $fields['duration'] : 60;

		// enrolment
		if($this->with_enrolment())
			$this->attributes['enrolment'] = isset($fields['enrolment']) ? $fields['enrolment'] : 'none';

		// static messages
		$this->attributes['follow_up_message'] = isset($fields['follow_up_message']) ? $fields['follow_up_message'] : '';
		$this->attributes['induction_message'] = isset($fields['induction_message']) ? $fields['induction_message'] : '';
		$this->attributes['lobby_message'] = isset($fields['lobby_message']) ? $fields['lobby_message'] : '';
		$this->attributes['welcome_message'] = isset($fields['welcome_message']) ? $fields['welcome_message'] : '';

		// process event specific attributes
		$this->parse_event_fields($fields);

	}

	/**
	 * remember an action once it's done
	 *
	 * To be overloaded into derived class
	 *
	 * @param string the action 'insert', 'update' or 'delete'
	 * @param array the hosting record
	 * @param string reference of the hosting record (e.g., 'article:123')
	 * @return FALSE on error, TRUE otherwise
	 */
	function remember($action, $host, $reference) {
		global $context;

		// remember the id of the master record
		$id = $host['id'];

		// set default values for this editor
		Surfer::check_default_editor($this->attributes);

		// we use the existing back-end for dates
		include_once $context['path_to_root'].'dates/dates.php';

		// build the update query
		switch($action) {

		case 'delete':

			// no need to notify participants after the date planned for the event, nor if the event has been initiated
			if(isset($this->attributes['date_stamp']) && ($this->attributes['date_stamp'] > gmstrftime('%Y-%m-%d %H:%M'))
				&& isset($this->attributes['status']) && ($this->attributes['status'] != 'started') && ($this->attributes['status'] != 'stopped')) {

				// send a cancellation message to participants
				$query = "SELECT user_email FROM ".SQL::table_name('enrolments')." WHERE (anchor LIKE '".$reference."') AND (approved LIKE 'Y')";
				$result = SQL::query($query);
				while($item = SQL::fetch($result)) {

					// sanity check
					if(!preg_match(VALID_RECIPIENT, $item['user_email']))
						continue;

					// message title
					$subject = sprintf('%s: %s', i18n::c('Cancellation'), strip_tags($this->anchor->get_title()));

					// headline
					$headline = sprintf(i18n::c('%s has cancelled %s'), Surfer::get_link(), $this->anchor->get_title());

					// message to reader
					$message = $this->get_invite_default_message('CANCEL');

					// assemble main content of this message
					$message = Skin::build_mail_content($headline, $message);

					// threads messages
					$headers = Mailer::set_thread($this->anchor->get_reference());

					// get attachment from the overlay
					$attachments = $this->get_invite_attachments('CANCEL');

					// post it
					Mailer::notify(Surfer::from(), $item['user_email'], $subject, $message, $headers, $attachments);

				}
			}

			// delete dates for this anchor
			Dates::delete_for_anchor($reference);

			// also delete related enrolment records
			$query = "DELETE FROM ".SQL::table_name('enrolments')." WHERE anchor LIKE '".$reference."'";
			SQL::query($query);
			break;

		case 'insert':

			// bind one date to this record
			if(isset($this->attributes['date_stamp']) && $this->attributes['date_stamp']) {

				$fields = array();
				$fields['anchor'] = $reference;
				$fields['date_stamp'] = $this->attributes['date_stamp'];

				// update the database
				if(!$fields['id'] = Dates::post($fields)) {
					Logger::error(i18n::s('Impossible to add an item.'));
					return FALSE;
				}

			}

			// enroll page creator
			include_once $context['path_to_root'].'shared/enrolments.php';
			enrolments::confirm($reference);

			// reload the anchor through the cache to reflect the update
			if($reference)
				$this->anchor = Anchors::get($reference, TRUE);

			// send a confirmation message to event creator
			$query = "SELECT * FROM ".SQL::table_name('enrolments')." WHERE (anchor LIKE '".$reference."')";
			$result = SQL::query($query);
			while($item = SQL::fetch($result)) {

				// a user registered on this server
				if($item['user_id'] && ($watcher = Users::get($item['user_id']))) {

					// sanity check
					if(!preg_match(VALID_RECIPIENT, $item['user_email']))
						continue;

					// use this email address
					if($watcher['full_name'])
						$recipient = Mailer::encode_recipient($watcher['email'], $watcher['full_name']);
					else
						$recipient = Mailer::encode_recipient($watcher['email'], $watcher['nick_name']);

					// message title
					$subject = sprintf(i18n::c('Meeting: %s'), strip_tags($this->anchor->get_title()));

					// headline
					$headline = sprintf(i18n::c('you have arranged %s'),
						'<a href="'.$context['url_to_home'].$context['url_to_root'].$this->anchor->get_url().'">'.$this->anchor->get_title().'</a>');

					// message to reader
					$message = $this->get_invite_default_message('PUBLISH');

					// assemble main content of this message
					$message = Skin::build_mail_content($headline, $message);

					// a set of links
					$menu = array();

					// call for action
					$link = $context['url_to_home'].$context['url_to_root'].$this->anchor->get_url();
					$menu[] = Skin::build_mail_button($link, i18n::c('View event details'), TRUE);

					// finalize links
					$message .= Skin::build_mail_menu($menu);

					// threads messages
					$headers = Mailer::set_thread($this->anchor->get_reference());

					// get attachment from the overlay
					$attachments = $this->get_invite_attachments('PUBLISH');

					// post it
					Mailer::notify(Surfer::from(), $recipient, $subject, $message, $headers, $attachments);
				}
			}

			break;

		case 'update':

			// reload the anchor through the cache to reflect the update
			if($reference)
				$this->anchor = Anchors::get($reference, TRUE);

			// no need to notify watchers after the date planned for the event, nor if the event has been initiated
			if(isset($this->attributes['date_stamp']) && ($this->attributes['date_stamp'] > gmstrftime('%Y-%m-%d %H:%M'))
				&& isset($this->attributes['status']) && ($this->attributes['status'] != 'started') && ($this->attributes['status'] != 'stopped')
				&& isset($_REQUEST['notify_watchers']) && ($_REQUEST['notify_watchers'] == 'Y')) {

				// send a confirmation message to participants
				$query = "SELECT * FROM ".SQL::table_name('enrolments')." WHERE (anchor LIKE '".$reference."')";
				$result = SQL::query($query);
				while($item = SQL::fetch($result)) {

					// skip current surfer
					if(Surfer::get_id() && (Surfer::get_id() == $item['user_id']))
						continue;

					// a user registered on this server
					if($item['user_id'] && ($watcher = Users::get($item['user_id']))) {

						// skip banned users
						if($watcher['capability'] == '?')
							continue;

						// ensure this surfer wants to be alerted
						if($watcher['without_alerts'] == 'Y')
							continue;

						// sanity check
						if(!preg_match(VALID_RECIPIENT, $item['user_email']))
							continue;

						// use this email address
						if($watcher['full_name'])
							$recipient = Mailer::encode_recipient($watcher['email'], $watcher['full_name']);
						else
							$recipient = Mailer::encode_recipient($watcher['email'], $watcher['nick_name']);

						// message title
						$subject = sprintf(i18n::c('Updated: %s'), strip_tags($this->anchor->get_title()));

						// headline
						$headline = sprintf(i18n::c('%s has updated %s'), Surfer::get_link(),
							'<a href="'.$context['url_to_home'].$context['url_to_root'].$this->anchor->get_url().'">'.$this->anchor->get_title().'</a>');

						// message to reader
						$message = $this->get_invite_default_message('PUBLISH');

						// assemble main content of this message
						$message = Skin::build_mail_content($headline, $message);

						// a set of links
						$menu = array();

						// call for action
						$link = $context['url_to_home'].$context['url_to_root'].$this->anchor->get_url();
						$menu[] = Skin::build_mail_button($link, i18n::c('View event details'), TRUE);

						// finalize links
						$message .= Skin::build_mail_menu($menu);

						// threads messages
						$headers = Mailer::set_thread($this->anchor->get_reference());

						// get attachment from the overlay
						$attachments = $this->get_invite_attachments('PUBLISH');

						// post it
						Mailer::notify(Surfer::from(), $recipient, $subject, $message, $headers, $attachments);
					}
				}
			}

			// bind one date to this record
			if(isset($this->attributes['date_stamp']) && $this->attributes['date_stamp']) {

				$fields = array();
				$fields['anchor'] = $reference;
				$fields['date_stamp'] = $this->attributes['date_stamp'];

				// there is an existing record
				if($date =& Dates::get_for_anchor($reference)) {

					// update the record
					$fields['id'] = $date['id'];
					if(!$id = Dates::post($fields)) {
						Logger::error(sprintf(i18n::s('Impossible to update date %s'), $this->attributes['date_stamp']));
						return FALSE;
					}

				// create a record instead of raising an error, we are smart y'a'know
				} else {
					if(!$fields['id'] = Dates::post($fields)) {
						Logger::error(i18n::s('Impossible to add an item.'));
						return FALSE;
					}
				}

			}
			break;
		}

		// job done
		return TRUE;

	}

	/**
	 * list dates at some anchor
	 *
	 * @param string type of replaced items (e.g., 'articles')
	 * @param string the anchor to consider (e.g., 'section:123')
	 * @param int page index
	 * @return string to be inserted in resulting web page, or NULL
	 */
	function render($type, $anchor, $page=1) {
		global $context;

		// instead of articles
		if($type != 'articles')
			return NULL;

		// get the containing page
		$container = Anchors::get($anchor);

		// handle dates
		include_once $context['path_to_root'].'dates/dates.php';

		// the maximum number of articles per page
		if(!defined('DATES_PER_PAGE'))
			define('DATES_PER_PAGE', 50);

		// where we are
		$offset = ($page - 1) * DATES_PER_PAGE;

		// should we display all dates, or not?
		$with_past_dates = FALSE;
		if(preg_match('/\bwith_past_dates\b/i', $this->attributes['overlay_parameters']))
			$with_past_dates = TRUE;

		// menu to be displayed at the top
		$menu = array();

		// empowered users can contribute
		if(Articles::allow_creation(NULL, $container)) {
			Skin::define_img('ARTICLES_ADD_IMG', 'articles/add.gif');
			$menu[] = '<div style="display: inline">'.Skin::build_link('articles/edit.php?anchor='.urlencode($anchor), ARTICLES_ADD_IMG.i18n::s('Add an event'), 'span').'</div>';
		}

		// ensure access to past dates
		if(!$with_past_dates && ($items = Dates::list_past_for_anchor($anchor, $offset, DATES_PER_PAGE, 'compact'))) {

			// turn an array to a string
			if(is_array($items))
				$items =& Skin::build_list($items, 'compact');

			// navigation bar
			$bar = array();

			// count the number of dates in this section
			$stats = Dates::stat_past_for_anchor($anchor);
			if($stats['count'] > DATES_PER_PAGE)
				$bar = array_merge($bar, array('_count' => sprintf(i18n::ns('%d date', '%d dates', $stats['count']), $stats['count'])));

			// navigation commands for dates
			if($section = Sections::get(str_replace('section:', '', $anchor))) {
				$home = Sections::get_permalink($section);
				$prefix = Sections::get_url($section['id'], 'navigate', 'articles');
				$bar = array_merge($bar, Skin::navigate($home, $prefix, $stats['count'], DATES_PER_PAGE, $page));
			}

			// display the bar
			if(is_array($bar))
				$items = Skin::build_list($bar, 'menu_bar').$items;

			// in a separate box
			$menu[] = Skin::build_sliding_box(i18n::s('Past dates'), $items, 'past_dates', TRUE);

		}

		// menu displayed towards the top of the page
		$text = Skin::finalize_list($menu, 'menu_bar');

		// build a list of events
		if(preg_match('/\blayout_as_list\b/i', $this->attributes['overlay_parameters'])) {

			// list all dates
			if($with_past_dates) {

				// navigation bar
				$bar = array();

				// count the number of dates in this section
				$stats = Dates::stat_for_anchor($anchor);
				if($stats['count'] > DATES_PER_PAGE)
					$bar = array_merge($bar, array('_count' => sprintf(i18n::ns('%d date', '%d dates', $stats['count']), $stats['count'])));

				// navigation commands for dates
				$section = Sections::get($anchor);
				$home = Sections::get_permalink($section);
				$prefix = Sections::get_url($section['id'], 'navigate', 'articles');
				$bar = array_merge($bar, Skin::navigate($home, $prefix, $stats['count'], DATES_PER_PAGE, $page));

				// display the bar
				if(count($bar))
					$text .= Skin::build_list($bar, 'menu_bar');

				// list one page of dates
				if($items = Dates::list_for_anchor($anchor, $offset, DATES_PER_PAGE, 'family'))
					$text .= $items;

			// display only future dates to regular surfers
			} else {

				// show future dates on first page
				if(($page == 1) && ($items = Dates::list_future_for_anchor($anchor, 0, 500, 'family', TRUE)))
					$text .= $items;

			}

		// deliver a calendar view for current month, plus months around
		} else {

			// show past dates as well
			if($with_past_dates)
				$items = Dates::list_for_anchor($anchor, 0, 500, 'links');

			// only show future dates, and trackback to first of current month
			else
				$items = Dates::list_future_for_anchor($anchor, 0, 500, 'links', TRUE);

			// layout all these dates
			if($items)
				$text .= Dates::build_months($items);
		}

		// integrate this into the page
		return $text;
	}

	/**
	 * a compact list of dates at some anchor
	 *
	 * @param string the anchor to consider (e.g., 'section:123')
	 * @param int maximum number of items
	 * @return array of ($prefix, $label, $suffix, $type, $icon, $hover)
	 */
	function render_list_for_anchor($anchor, $count=7) {
		global $context;

		// we will build a list of dates
		include_once $context['path_to_root'].'dates/dates.php';

		// list past dates as well
		if(preg_match('/\bwith_past_dates\b/i', $this->attributes['overlay_parameters']))
			$items = Dates::list_for_anchor($anchor, 0, $count, 'compact');

		// list only future dates
		else
			$items = Dates::list_future_for_anchor($anchor, 0, $count, 'compact');

		// we return an array
		return $items;
	}

	/**
	 * notify watchers or not?
	 *
	 * This function is used in various scripts to customize notification of watchers.
	 *
	 * @see articles/edit.php
	 * @see articles/publish.php
	 *
	 * @param array if provided, a notification that can be sent to customised recipients
	 * @return boolean always FALSE for events, since notifications are made through enrolment
	 */
	function should_notify_watchers($mail=NULL) {
		global $context;

		// sent notification to all enrolled persons
		if($mail) {

			// list enrolment for this meeting
			$query = "SELECT user_id FROM ".SQL::table_name('enrolments')." WHERE anchor LIKE '".SQL::escape($this->anchor->get_reference())."'";
			if($result = SQL::query($query)) {

				// browse the list
				while($item = SQL::fetch($result)) {

					// a user registered on this server
					if($item['user_id'] && ($watcher = Users::get($item['user_id']))) {

						// skip banned users
						if($watcher['capability'] == '?')
							continue;

						// skip current surfer
						if(Surfer::get_id() && (Surfer::get_id() == $item['user_id']))
							continue;

						// ensure this surfer wants to be alerted
						if($watcher['without_alerts'] != 'Y')
							Users::alert($watcher, $mail);

					}
				}
			}
		}

		// prevent normal cascading of notifications
		return FALSE;
	}

	/**
	 * start the meeting
	 *
	 */
	function start_meeting() {
		global $context;

		// move to the 'started' status
		$fields = array('status' => 'started');
		$this->set_values($fields);

		// track the beginning of the meeting
		include_once $context['path_to_root'].'comments/comments.php';
		if(is_callable(array($this->anchor, 'get_reference'))) {
			$fields = array();
			$fields['anchor'] = $this->anchor->get_reference();
			$fields['description'] = sprintf(i18n::s('%s has started the meeting'), Surfer::get_name());
			$fields['type'] = 'notification';
			Comments::post($fields);
		}

	}

	/**
	 * stop a meeting
	 *
	 */
	function stop_meeting() {
		global $context;

		// move to the 'closed' status
		$fields = array('status' => 'stopped');
		$this->set_values($fields);

		// track the end of the meeting
		include_once $context['path_to_root'].'comments/comments.php';
		if(is_callable(array($this->anchor, 'get_reference'))) {
			$fields = array();
			$fields['anchor'] = $this->anchor->get_reference();
			$fields['description'] = sprintf(i18n::s('%s has stopped the meeting'), Surfer::get_name());
			$fields['type'] = 'notification';
			Comments::post($fields);
		}

	}

	/**
	 * manage potential transition to state 'created'
	 *
	 * This function updates $this->feed_back to report to the end-user
	 */
	function transition_to_created() {
		global $context;

		// we only postpone events that are about to start
		if(($this->attributes['status'] == 'lobby') && ($this->minutes_before_start > 60)) {

			// move to the 'created' status and refresh the page
			$fields = array('status' => 'created');
			if($this->set_values($fields))
				$this->feed_back['reload_this_page'] = TRUE;

		}

	}

	/**
	 * manage potential transition to state 'lobby'
	 *
	 * This function updates $this->feed_back to report to the end-user
	 */
	function transition_to_lobby() {
		global $context;

		// don't step back in workflow
		if(($this->attributes['status'] == 'started') || ($this->attributes['status'] == 'stopped'))
			return;

		// initiate the meeting one hour in advance
		if($this->minutes_before_start <= 60) {

			// move to the 'lobby' status and refresh the page
			$fields = array('status' => 'lobby');
			if($this->set_values($fields))
				$this->feed_back['reload_this_page'] = TRUE;

		}

	}

	/**
	 * manage potential transition to state 'open'
	 *
	 * This function updates $this->feed_back to report to the end-user
	 */
	function transition_to_open() {
		global $context;

		// we don't manage enrolment, at all
		if(!$this->with_enrolment())
			return;

		// no need to open enrolment
		if(!isset($this->attributes['enrolment']) || ($this->attributes['enrolment'] == 'none'))
			return;

		// enrolment has not started yet
		if($this->attributes['status'] == 'created') {

			// any owner can open the event
			if(isset($this->anchor) && $this->anchor->is_owned())
				$this->feed_back['menu'][] = Skin::build_link($this->get_url('open'), i18n::s('Open enrolment'), 'button');

			// others have to wait
			else
				$this->feed_back['status'][] = i18n::s('Enrolment has not been opened yet');

		}
	}

	/**
	 * manage potential transition to state 'started'
	 *
	 * This function updates $this->feed_back to report to the end-user
	 */
	function transition_to_started() {
		global $context;

		// dont step back in workflow
		if($this->attributes['status'] == 'stopped')
			return;

		// don't start too early
		if($this->minutes_before_start > 60)
			return;

		// automatic start
		if(($this->minutes_before_start < 1) && ($this->with_automatic_start())) {

			// move to the 'started' status and refresh the page
			$fields = array('status' => 'started');
			if($this->set_values($fields))
				$this->feed_back['reload_this_page'] = TRUE;

		// manual start
		} elseif(isset($this->anchor) && ($this->anchor->is_owned())) {

			// display the button to start the meeting
			if($this->with_start_button()) {

				// refresh the page if a comment is posted by someone waiting in the lobby
				$lobby = JS_PREFIX
					.'var Lobby = {'."\n"
					."\n"
					.'	url: "'.$context['url_to_home'].$context['url_to_root'].'services/check.php?id='.$this->anchor->get_reference().'",'."\n"
					.'	timestamp: 0,'."\n"
					."\n"
					.'	subscribe: function() {'."\n"
					.'		$.get(Lobby.url, { "timestamp" : this.timestamp },'."\n"
					.'		function(response) {'."\n"
					.'			if(Lobby.timestamp == 0)'."\n"
					.'				Lobby.timestamp = response["timestamp"];'."\n"
					.'			else if(Lobby.timestamp != response["timestamp"])'."\n"
					.'				window.location.reload(true);'."\n"
					.'		}, "json");'."\n"
					.'	}'."\n"
					."\n"
					.'}'."\n"
					."\n"
					.'// reload the page when it changes, or when meeting is started'."\n"
					.'Lobby.subscribe();'."\n"
					.'Lobby.subscribeTimer = setInterval("Lobby.subscribe()", 20000);'."\n"
					.JS_SUFFIX;

				// reload this page and go to a new one, or just follow the link
				if($this->with_new_window())
					$type = 'tee';
				else
					$type = 'button';

				// add the button
				$this->feed_back['commands'][] = Skin::build_link($this->get_url('start'), i18n::s('Start the meeting'), $type).$lobby;

			}

			// else remind the owner to do something
			elseif(is_callable(array($this, 'get_start_status')) && ($status = $this->get_start_status()))
				$this->feed_back['status'][] = $status;

		// surfer is legitimate to attend the event
		} elseif( !$this->with_enrolment() || ($this->attributes['enrolment'] == 'none')
			|| enrolments::get_record($this->anchor->get_reference())) {

			// refresh the page on meeting start
			$this->feed_back['status'][] = '<img alt="*" src="'.$context['url_to_home'].$context['url_to_root'].'skins/_reference/ajax/ajax_spinner.gif" style="vertical-align:-3px" /> '
				.i18n::s('Please wait until the meeting begins')
				.JS_PREFIX
				.'var Lobby = {'."\n"
				."\n"
				.'	url: "'.$context['url_to_home'].$context['url_to_root'].'services/check.php?id='.$this->anchor->get_reference().'",'."\n"
				.'	timestamp: 0,'."\n"
				."\n"
				.'	subscribe: function() {'."\n"
				.'		$.get(Lobby.url, { "timestamp" : this.timestamp },'."\n"
				.'		function(response) {'."\n"
				.'			if(Lobby.timestamp == 0)'."\n"
				.'				Lobby.timestamp = response["timestamp"];'."\n"
				.'			else if(Lobby.timestamp != response["timestamp"])'."\n"
				.'				window.location.reload(true);'."\n"
				.'		}, "json");'."\n"
				.'	}'."\n"
				."\n"
				.'}'."\n"
				."\n"
				.'// reload the page when it changes, or when meeting is started'."\n"
				.'Lobby.subscribe();'."\n"
				.'Lobby.subscribeTimer = setInterval("Lobby.subscribe()", 20000);'."\n"
				.JS_SUFFIX;

		}
	}

	/**
	 * manage potential transition to state 'stopped'
	 *
	 * This function updates $this->feed_back to report to the end-user
	 */
	function transition_to_stopped() {
		global $context;

		// no enrolment at all
		if(!$this->with_enrolment())
			return;

		// no enrolment
		if(isset($this->attributes['enrolment']) && ($this->attributes['enrolment'] == 'none'))
			$this->feed_back['status'][] = i18n::s('Any page visitor can participate');

		// manual enrolment
		if(isset($this->attributes['enrolment']) && ($this->attributes['enrolment'] == 'manual'))
			$this->feed_back['status'][] = i18n::s('Registration is managed by page owner');

		// spread the word
		if(isset($this->anchor) && ($this->anchor->is_owned())) {
			$label = i18n::s('Invite participants');
			$this->feed_back['menu'][] = Skin::build_link($this->anchor->get_url('invite'), $label, 'span');

		}

		// manage enrolment
		if(isset($this->anchor) && ($this->anchor->is_owned())) {
			$label = i18n::s('Manage enrolment');
			if($count = enrolments::count_enrolled($this->anchor->get_reference()))
				$label .= ' ('.$count.')';
			$this->feed_back['menu'][] = Skin::build_link($this->get_url('enroll'), $label, 'span');

		}

		// surfer has applied
		if($enrolment = enrolments::get_record($this->anchor->get_reference())) {

			// registration has been approved
			if(isset($enrolment['approved']) && ($enrolment['approved'] == 'Y'))
				$this->feed_back['status'][] = i18n::s('You have been enrolled');

			// pending confirmation, and meeting has not started yet
			elseif($this->attributes['status'] != 'started')
				$this->feed_back['status'][] = i18n::s('You have asked for an invitation');

			// reload the page in case pending invitation would be validated after the start of the meeting
			else
				$this->feed_back['status'][] = '<img alt="*" src="'.$context['url_to_home'].$context['url_to_root'].'skins/_reference/ajax/ajax_spinner.gif" style="vertical-align:-3px" /> '
					.i18n::s('You have asked for an invitation')
					.JS_PREFIX
					.'function reload_until_enrolment() {'."\n"
					.'	window.location.reload(true);'."\n"
					.'}'."\n"
					.'window.setInterval("reload_until_enrolment()",20000);'."\n"
					.JS_SUFFIX;

		// surfer should express his participation
		} elseif(isset($this->attributes['enrolment']) && ($this->attributes['enrolment'] == 'none')) {

			// until meeting has started
			if(Surfer::get_id() && in_array($this->attributes['status'], array('created', 'open', 'lobby')))
				$this->feed_back['menu'][] = Skin::build_link($this->get_url('apply'), i18n::s('Confirm my participation'), 'button');

		// surfer should ask for an invitation
		} elseif(isset($this->attributes['enrolment']) && ($this->attributes['enrolment'] == 'validate')) {

			// until meeting has stopped
			if(Surfer::get_id() && in_array($this->attributes['status'], array('open', 'lobby', 'started')))
				$this->feed_back['menu'][] = Skin::build_link($this->get_url('apply'), i18n::s('Ask for an invitation'), 'button');

		}

		// display event status to page owner, if any
		if(isset($this->anchor) && ($this->anchor->is_owned())) {
			if(is_callable(array($this, 'get_event_status')) && ($status = $this->get_event_status()))
				$this->feed_back['status'][] = $status;
		}

		// start is mandatory before stop
		if($this->attributes['status'] == 'started') {

			// meeting has been stopped externally
			if(!$this->is_running()) {

				// move to the 'stopped' status and refresh the page
				$fields = array('status' => 'stopped');
				if($this->set_values($fields))
					$this->feed_back['reload_this_page'] = TRUE;

			// automatic stop
			} elseif(($this->minutes_since_stop > 0) && ($this->with_automatic_stop())) {

				// move to the 'stopped' status and refresh the page
				$fields = array('status' => 'stopped');
				if($this->set_values($fields))
					$this->feed_back['reload_this_page'] = TRUE;

			// event owner
			} elseif(isset($this->anchor) && ($this->anchor->is_owned())) {

				// join the meeting
				if($this->with_join_button())
					$this->feed_back['commands'][] = Skin::build_link($this->get_url('join'), i18n::s('Join the meeting'), $this->with_new_window()?'tee':'button');

				// display the button to stop the meeting
				if($this->with_stop_button())
					$this->feed_back['commands'][] = Skin::build_link($this->get_url('stop'), i18n::s('Stop the meeting'),
						$this->with_join_button()?'span':'button');

			// enrolment is not required
			} elseif(isset($this->attributes['enrolment']) && ($this->attributes['enrolment'] == 'none')) {

				// join the meeting
				if($this->with_join_button())
					$this->feed_back['commands'][] = Skin::build_link($this->get_url('join'), i18n::s('Join the meeting'), $this->with_new_window()?'tee':'button');

			// surfer has been fully enrolled
			} elseif(($enrolment = enrolments::get_record($this->anchor->get_reference())) && ($enrolment['approved'] == 'Y')) {

				// join the meeting
				if($this->with_join_button())
					$this->feed_back['commands'][] = Skin::build_link($this->get_url('join'), i18n::s('Join the meeting'), $this->with_new_window()?'tee':'button');

			}
		}

	}

	/**
	 * an event starts on planned date
	 *
	 * @return boolean should be TRUE for external events and for physical meetings, FALSE otherwise
	 */
	function with_automatic_start() {
		return TRUE;
	}

	/**
	 * an event stops on planned date
	 *
	 * @return boolean should be TRUE for physical meetings, FALSE otherwise
	 */
	function with_automatic_stop() {
		return TRUE;
	}

	/**
	 * should we manage enrolment?
	 *
	 * @return boolean TRUE to manage the full list of participants, FALSE otherwise
	 *
	 */
	function with_enrolment() {
		return TRUE;
	}

	/**
	 * should we display a button to join the meeting?
	 *
	 * @return boolean TRUE or FALSE
	 */
	function with_join_button() {

		// we have a meeting URL
		if(is_callable(array($this, 'get_join_url')) && $this->get_join_url())
			return TRUE;

		// nowhere to go
		return FALSE;
	}

	/**
	 * should we open a separate window for the joigning place?
	 *
	 * @return boolean should be TRUE for back-ends that don't allow for coming back, FALSE otherwise
	 */
	function with_new_window() {
		return FALSE;
	}

	/**
	 * should we display a button to start the meeting?
	 *
	 * @return boolean TRUE or FALSE
	 */
	function with_start_button() {

		// we have a starting URL
		if(!$this->with_automatic_start() && is_callable(array($this, 'get_start_url')))
			return TRUE;

		// nowhere to go
		return FALSE;
	}

	/**
	 * should we display a button to stop the meeting?
	 *
	 * @return boolean TRUE or FALSE
	 */
	function with_stop_button() {

		// we can stop
		if(!$this->with_automatic_stop() && is_callable(array($this, 'get_stop_url')))
			return TRUE;

		// nowhere to go
		return FALSE;
	}

}

?>
