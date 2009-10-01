<?php
/**
 * manage a meeting
 *
 * A meeting has following successive phases:
 * - preparation
 * - enrolment
 * - meeting
 * - follow-up
 *
 * During preparation the meeting page is under construction, and enrolment is not allowed.
 * A splash message is displayed to alert potential attendees of the meeting.
 *
 * During enrolment (up to two hours before the start of the meeting), the page allows for
 * registration of attendees. The page displays the name of meeting chairman, dates and timing of
 * the meeting. Instructions are provided to join the meeting as well. Page surfers can confirm
 * their attendance.
 *
 * The meeting page can be populated with pre-reading material, if necessary, or with links to
 * related pages. Comments in a wall can be used both to provide news and to answer questions, if any.
 * Page owner is provided an additional button to invite people by e-mail, or to send a reminder to
 * potential attendeees.
 *
 * When the page owner triggers the on-line meeting, a welcome message is displayed until the actual
 * time of the meeting is reached. Then the link to the meeting is displayed, and attendees are
 * encouraged to join the on-line place. Comments in the wall can be used to provide support to
 * meeting attendees if necessary.
 *
 * At the end of the meeting, or when the page owner stops the meeting, a follow-up message is displayed.
 * Comments in the wall can be used to provide additional directions, or to answer after-thought questions.
 *
 * Page owners can manage the transitions through following actions:
 * - activate enrolment
 * - trigger the meeting page
 * - close the meeting
 *
 * Workflow status is captured in one single parameter, with following values:
 * - default - splash message is displayed
 * - 'open' - enrolment message is displayed, audience can confirm attendance
 * - 'lobby' - welcome message is displayed, a count-down is displayed
 * - 'started' - joining message is displayed, actual attendance is captured
 * - 'closed' - follow-up message is displayed
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Generic_Meeting extends Overlay {

	/**
	 * get the default enrolment message
	 *
	 * @param array the hosting record
	 * @return string the message
	 */
	function get_default_enrolment_message($host) {
		global $context;

		return '';
	}

	/**
	 * get the default follow-up message
	 *
	 * @param array the hosting record
	 * @return string the message
	 */
	function get_default_follow_up_message($host) {
		global $context;

		return '';
	}

	/**
	 * get the default induction message
	 *
	 * @param array the hosting record
	 * @return string the message
	 */
	function get_default_induction_message($host) {
		global $context;

		return '';
	}

	/**
	 * get the default lobby message
	 *
	 * @param array the hosting record
	 * @return string the message
	 */
	function get_default_lobby_message($host) {
		global $context;

		return '';
	}

	/**
	 * get the default welcome message
	 *
	 * @param array the hosting record
	 * @return string the message
	 */
	function get_default_welcome_message($host) {
		global $context;

		return '';
	}

	/**
	 * get form fields to change the day
	 *
	 * @see overlays/overlay.php
	 *
	 * @param the hosting attributes
	 * @return a list of ($label, $input, $hint)
	 */
	function get_fields($host) {
		global $context;

		// default value is now
		if(!isset($this->attributes['date_stamp']) || ($this->attributes['date_stamp'] <= NULL_DATE))
			$this->attributes['date_stamp'] = gmstrftime('%Y-%m-%d %H:%M');

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

		// meeting time
		$label = i18n::s('Date');
		$input = Skin::build_input('date_stamp', $date, 'date').$options;
		$hint = i18n::s('Use format YYYY-MM-DD');
		$fields[] = array($label, $input, $hint);

		// duration
		$label = i18n::s('Duration');
		$input = '<select name="duration">';
		if(!isset($this->attributes['duration']) || ($this->attributes['duration'] < 1) || ($this->attributes['duration'] > 4))
			$this->attributes['duration'] = 1;
		$input .= '<option'.(($this->attributes['duration'] == 1)?' selected="selected"':'').'>1</option>';
		$input .= '<option'.(($this->attributes['duration'] == 2)?' selected="selected"':'').'>2</option>';
		$input .= '<option'.(($this->attributes['duration'] == 3)?' selected="selected"':'').'>3</option>';
		$input .= '<option'.(($this->attributes['duration'] == 4)?' selected="selected"':'').'>4</option>';
		$input .= '</select> '.i18n::s('hour(s)');
		$hint = i18n::s('You can extend the duration during the meeting if necessary');
		$fields[] = array($label, $input, $hint);

		return $fields;
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
			return 'meeting:'.$this->attributes['date_stamp'];
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

		$text = Codes::beautify_title($host['title']);

		if(isset($this->attributes['date_stamp']) && ($this->attributes['date_stamp'] > NULL_DATE))
			$text .= ' ['.Skin::build_date($this->attributes['date_stamp'], 'day').']';

		return $text;
	}

	/**
	 * update the countdown
	 *
	 * @param array the hosting record
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_lobby_text($host=NULL) {
		global $context;

		// countdown
		$text = '<p>'.i18n::s('Please wait until the meeting begins.').'</p>';

		return $text;
	}

	/**
	 * get parameters for one meeting facility
	 *
	 * @return an array of fields or NULL
	 */
	function &get_meeting_fields() {
		global $context;

		// returned fields
		$fields = array();

		// meeting room
		$label = i18n::s('Address');
		if(!isset($this->attributes['address']))
			$this->attributes['address'] = '';
		$input = '<input type="text" name="account" value ="'.encode_field($this->attributes['address']).'"  size="45" maxlength="128" />';
		$hint = i18n::s('Paste the address of the meeting place');
		$fields[] = array($label, $input, $hint);

		// add these tabs
		return $fields;
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
			return i18n::s('Meeting preparation');

		case 'open':
			return i18n::s('Enrolment is open');

		case 'lobby':
			return i18n::s('Please wait');

		case 'started':
			return i18n::s('Meeting has started');

		case 'closed':
			return i18n::s('Meeting is over');

		}
	}

	/**
	 * add some tabs
	 *
	 * Manage the meeting in a separate panel
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

		// only associates and editors can change the status
		if(($variant == 'edit') && Surfer::is_empowered()) {

			// step 1 - 'created'
			$checked = '';
			if(!isset($this->attributes['status']) || !$this->attributes['status'] || ($this->attributes['status'] == 'created'))
				$checked = 'checked="checked"';
			$manage .= '<input type="radio" name="status" value ="created" '.$checked.' />&nbsp;'.i18n::s('Meeting preparation');

			// splash message
			$label = i18n::user('Meeting presentation');
			if(!isset($this->attributes['induction_message']))
				$this->attributes['induction_message'] = $this->get_default_induction_message($host);
			$input = '<textarea name="induction_message" rows="2" cols="50">'.encode_field($this->attributes['induction_message']).'</textarea>';
			$hint = i18n::user('Introduce the meeting to your audience, and explain when enrolment will start.');
			$fields[] = array($label, $input, $hint);

			$manage .= Skin::build_form($fields);
			$fields = array();

			// step 2 - 'open'
			$checked = '';
			if(isset($this->attributes['status']) && ($this->attributes['status'] == 'open'))
				$checked = 'checked="checked"';
			$manage .= '<div class="bottom">'
				.'<input type="radio" name="status" value ="open" '.$checked.' />&nbsp;'.i18n::s('Meeting enrolment');

			// splash message
			$label = i18n::user('Enrolment instructions');
			if(!isset($this->attributes['enrolment_message']))
				$this->attributes['enrolment_message'] = $this->get_default_enrolment_message($host);
			$input = '<textarea name="enrolment_message" rows="2" cols="50">'.encode_field($this->attributes['enrolment_message']).'</textarea>';
			$hint = i18n::user('How can people confirm attendance, and how should they prepare themselves?');
			$fields[] = array($label, $input, $hint);

			$manage .= Skin::build_form($fields).'</div>';
			$fields = array();

			// step 3 - 'lobby'
			$checked = '';
			if(isset($this->attributes['status']) && ($this->attributes['status'] == 'lobby'))
				$checked = 'checked="checked"';
			$manage .= '<div class="bottom">'
				.'<input type="radio" name="status" value ="lobby" '.$checked.' />&nbsp;'.i18n::s('Waiting for meeting start');

			// splash message
			$label = i18n::user('Lobby message');
			if(!isset($this->attributes['lobby_message']))
				$this->attributes['lobby_message'] = $this->get_default_lobby_message($host);
			$input = '<textarea name="lobby_message" rows="2" cols="50">'.encode_field($this->attributes['lobby_message']).'</textarea>';
			$hint = i18n::user('The message displayed to attendees before they can join the meeting.');
			$fields[] = array($label, $input, $hint);

			$manage .= Skin::build_form($fields).'</div>';
			$fields = array();

			// step 4 - 'started'
			$checked = '';
			if(isset($this->attributes['status']) && ($this->attributes['status'] == 'started'))
				$checked = 'checked="checked"';
			$manage .= '<div class="bottom">'
				.'<input type="radio" name="status" value ="started" '.$checked.' />&nbsp;'.i18n::s('Meeting is starting');

			// splash message
			$label = i18n::user('Welcome message');
			if(!isset($this->attributes['welcome_message']))
				$this->attributes['welcome_message'] = $this->get_default_welcome_message($host);
			$input = '<textarea name="welcome_message" rows="2" cols="50">'.encode_field($this->attributes['welcome_message']).'</textarea>';
			$hint = i18n::user('The message displayed above joining instructions.');
			$fields[] = array($label, $input, $hint);

			// joining parameters
			$fields = array_merge($fields, $this->get_meeting_fields());

			$manage .= Skin::build_form($fields).'</div>';
			$fields = array();

			// step 5 - 'closed'
			$checked = '';
			if(isset($this->attributes['status']) && ($this->attributes['status'] == 'closed'))
				$checked = 'checked="checked"';
			$manage .= '<div class="bottom">'
				.'<input type="radio" name="status" value ="closed" '.$checked.' />&nbsp;'.i18n::s('Meeting is over');

			// splash message
			$label = i18n::user('Follow-up message');
			if(!isset($this->attributes['follow_up_message']))
				$this->attributes['follow_up_message'] = $this->get_default_follow_up_message($host);
			$input = '<textarea name="follow_up_message" rows="2" cols="50">'.encode_field($this->attributes['follow_up_message']).'</textarea>';
			$hint = i18n::user('Congratulate participants, and drive people to follow-up information or actions.');
			$fields[] = array($label, $input, $hint);

			$manage .= Skin::build_form($fields).'</div>';
			$fields = array();

		}

		// finalize this tab
		if($manage)
			$tabs[] = array('management', i18n::s('Management'), 'management_panel', $manage);

		// add these tabs
		return $tabs;
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


		$text = '';

		switch($this->attributes['status']) {
		case 'created':
		default:
			if(isset($this->attributes['induction_message']))
				$text .= Codes::render($this->attributes['induction_message']);
			break;

		case 'open':

			if(isset($this->attributes['enrolment_message']))
				$text .= Codes::render($this->attributes['enrolment_message']);
			break;

		case 'lobby':
			if(isset($this->attributes['lobby_message']))
				$text .= Codes::render($this->attributes['lobby_message']);

			// the countdown message
			$text .= $this->get_lobby_text($host);

			break;

		case 'started':
			if(isset($this->attributes['welcome_message']))
				$text .= Codes::render($this->attributes['welcome_message']);

			// joining instructions
			$text .= $this->get_welcome_text($host);

			break;

		case 'closed':
			if(isset($this->attributes['follow_up_message']))
				$text .= Codes::render($this->attributes['follow_up_message']);
			break;

		}

		return $text;
	}

	/**
	 * provide joining nstructions
	 *
	 * @param array the hosting record
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_welcome_text($host=NULL) {
		global $context;


		$text = '';

		// join the meeting place
		if(isset($this->attributes['address']) && $this->attributes['address']) {
			$text .= '<p>'.i18n::s('Please click on the button below to join the meeting.').'</p>';
			$text .= Skin::build_link($this->attributes['address'], i18n::s('Join the meeting'), 'button');
		}

		return $text;
	}

	/**
	 * retrieve the content of one modified overlay
	 *
	 * @see overlays/overlay.php
	 *
	 * @param the fields as filled by the end user
	 * @return the updated fields
	 */
	function parse_fields($fields) {

		// date and duration
		$this->attributes['date_stamp'] = isset($fields['date_stamp']) ? $fields['date_stamp'] : '';
		$this->attributes['time_stamp'] = isset($fields['time_stamp']) ? $fields['time_stamp'] : '';
		if($this->attributes['date_stamp'] && $this->attributes['time_stamp'])
			$this->attributes['date_stamp'] .= ' '.$this->attributes['time_stamp'];

		// management status
		$this->attributes['status'] = isset($fields['status']) ? $fields['status'] : 'created';

		// save messages
		$this->attributes['induction_message'] = isset($fields['induction_message']) ? $fields['induction_message'] : '';
		$this->attributes['enrolment_message'] = isset($fields['enrolment_message']) ? $fields['enrolment_message'] : '';
		$this->attributes['lobby_message'] = isset($fields['lobby_message']) ? $fields['lobby_message'] : '';
		$this->attributes['welcome_message'] = isset($fields['welcome_message']) ? $fields['welcome_message'] : '';
		$this->attributes['follow_up_message'] = isset($fields['follow_up_message']) ? $fields['follow_up_message'] : '';

		// meeting specific parameters
		$this->parse_meeting_fields($fields);

		return $this->attributes;
	}

	/**
	 * retrieve meeting specific parameters
	 *
	 * @param the fields as filled by the end user
	 */
	function parse_meeting_fields($fields) {

		// meeting place
		$this->attributes['address'] = isset($fields['address']) ? $fields['address'] : '';

	}

	/**
	 * remember an action once it's done
	 *
	 * To be overloaded into derivated class
	 *
	 * @param string the action 'insert', 'update' or 'delete'
	 * @param array the hosting record
	 * @return FALSE on error, TRUE otherwise
	 */
	function remember($variant, $host) {
		global $context;

		// remember the id of the master record
		$id = $host['id'];

		// set default values for this editor
		Surfer::check_default_editor($this->attributes);

		// we use the existing back-end for dates
		include_once $context['path_to_root'].'dates/dates.php';

		// build the update query
		switch($variant) {

		case 'delete':

			// delete dates for this anchor
			Dates::delete_for_anchor('article:'.$id);

			// also delete related enrolment records
			$query = "DELETE FROM ".SQL::table_name('enrolments')." WHERE id = ".$id;
			SQL::query($query);
			break;

		case 'insert':

			// bind one date to this record
			if(isset($this->attributes['date_stamp']) && $this->attributes['date_stamp']) {

				$fields = array();
				$fields['anchor'] = 'article:'.$id;
				$fields['date_stamp'] = $this->attributes['date_stamp'];

				// update the database
				if(!$fields['id'] = Dates::post($fields)) {
					Logger::error(i18n::s('Impossible to add an item.'));
					return FALSE;
				}

			}
			break;

		case 'update':

			// bind one date to this record
			if(isset($this->attributes['date_stamp']) && $this->attributes['date_stamp']) {

				$fields = array();
				$fields['anchor'] = 'article:'.$id;
				$fields['date_stamp'] = $this->attributes['date_stamp'];

				// there is an existing record
				if($date =& Dates::get_for_anchor('article:'.$id)) {

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
		$container =& Anchors::get($anchor);

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
		if(Articles::are_allowed($container)) {
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
			$section = Sections::get($anchor);
			$home =& Sections::get_permalink($section);
			$prefix = Sections::get_url($section['id'], 'navigate', 'articles');
			$bar = array_merge($bar, Skin::navigate($home, $prefix, $stats['count'], DATES_PER_PAGE, $page));

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
				$home =& Sections::get_permalink($section);
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
	 * create tables for enrolment
	 *
	 * @see control/setup.php
	 */
	function setup() {
		global $context;

		$fields = array();
		$fields['id']			= "MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT";
		$fields['user_id']		= "MEDIUMINT UNSIGNED DEFAULT 0 NOT NULL";						// root cause analysis

		$indexes = array();
		$indexes['PRIMARY KEY'] 	= "(id)";
		$indexes['INDEX user_id'] = "(user_id)";

		return SQL::setup_table('enrolments', $fields, $indexes);
	}

}

?>