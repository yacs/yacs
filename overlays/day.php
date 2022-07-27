<?php
include_once 'event.php';

/**
 * describe one specific day
 *
 * This overlay streamlines the setup of bare events, such as concerts, etc.
 * The assumption is that most information related to such events will be managed outside
 * the web environment. Therefore, data management is really minimum, and the user
 * interface is simplified to the maximum extend.
 *
 * Transitions to 'started' or to 'stopped' status are provided automatically, based
 * on meeting starting date and time, and on its planned duration.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Day extends Event {


	/**
	 * get parameters for one meeting facility
	 *
	 * @return an array of fields or NULL
	 */
	function get_event_fields() {
		global $context;

		// returned fields
		$fields = array();
		return $fields;
	}

	/**
	 * text to be displayed to page owner
	 *
	 * @see overlays/event.php
	 *
	 * @return string some instructions to page owner
	 */
	function get_event_status() {
		return NULL;
	}

	/**
	 * get form fields to change the day
	 *
	 * @see overlays/overlay.php
	 *
	 * @param array hosting attributes
	 * @return a list of ($label, $input, $hint)
	 */
	function get_fields($host, $field_pos=NULL) {
		global $context;

		$options = '<input type="hidden" name="time_stamp" value="12:00" />'
			.'<input type="hidden" name="duration" value="1440" />';

		// default value is now
		if(!isset($this->attributes['date_stamp']) || ($this->attributes['date_stamp'] <= NULL_DATE))
			$this->attributes['date_stamp'] = gmdate('%Y-%m-%d %H:%M', time() + (Surfer::get_gmt_offset() * 3600));

		// adjust to surfer time zone
		else
			$this->attributes['date_stamp'] = Surfer::from_GMT($this->attributes['date_stamp']);

		// split date from time
		list($date, $time) = explode(' ', $this->attributes['date_stamp']);

		// event time
		$label = i18n::s('Date');
		$input = Skin::build_input_time('date_stamp', $date, 'date').$options;
		$hint = i18n::s('Use format YYYY-MM-DD');
		$fields[] = array($label, $input, $hint);

		// ensure that we do have a date
		Page::insert_script(			
			'func'.'tion validateOnSubmit(container) {'."\n"
			."\n"
			.'	if(!Yacs.trim(container.date_stamp.value)) {'."\n"
			.'		alert("'.i18n::s('Please provide a date.').'");'."\n"
			.'		container.date_stamp.focus();'."\n"
			.'		Yacs.stopWorking();'."\n"
			.'		return false;'."\n"
			.'	}'."\n\n"
			.'	return true;'."\n"
			.'}'."\n"			
			);

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
		return NULL;
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
			$text .= sprintf(i18n::c('%s: %s'), i18n::c('Topic'), Skin::build_link($context['url_to_home'].$context['url_to_root'].$this->anchor->get_url(), Codes::beautify_title($value))).BR;

		// dates
		if(isset($this->attributes['date_stamp']) && $this->attributes['date_stamp'])
			$text .= sprintf(i18n::c('%s: %s'), i18n::c('Date'), Skin::build_date($this->attributes['date_stamp'], 'day')).BR;

		// build a link to the chairman page, if any
		if(isset($this->attributes['chairman']) && ($user = Users::get($this->attributes['chairman'])))
			$text .= sprintf(i18n::c('%s: %s'), i18n::c('Chairman'), Users::get_link($user['full_name'], NULL, $user['id'])).BR;

		// event has been cancelled
		if($method == 'CANCEL')
			$text .= '<div><p>'.i18n::c('Event has been cancelled.').'</p></div>';

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
	 * display a live title
	 *
	 * @see overlays/overlay.php
	 *
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function get_live_title($host=NULL) {

		$text = $host['title'];
		return $text;

	}

	/**
	 * get an input field to capture meeting web address
	 *
	 * @return string to be integrated into the editing form
	 */
	function get_meeting_url_input() {
		global $context;

		return NULL;

	}

	/**
	 * text to be displayed to page owner for the start of the event
	 *
	 * @see overlays/event.php
	 *
	 * @return string some instructions to page owner
	 */
	function get_start_status() {

		// nothing to display to page owner
		return NULL;
	}

	/**
	 * add some tabs
	 *
	 * @see overlays/overlay.php
	 *
	 * @param string the on-going action
	 * @param array the hosting record
	 * @return an empty array
	 */
	function get_tabs($variant='view', $host=NULL) {
		global $context, $local;

		// returned tabs
		$tabs = array();
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
	function get_view_text($host=NULL) {
		global $context;

		$text = '';
		return $text;
	}

	/**
	 * retrieve event specific parameters
	 *
	 * @see overlays/event.php
	 *
	 * @param the fields as filled by the end user
	 */
	function parse_event_fields($fields) {
	}

	/**
	 * an external meeting can be stopped before its planned end
	 *
	 * @return boolean FALSE
	 */
	function with_automatic_stop() {
		return FALSE;
	}

	/**
	 * should we manage enrolment?
	 *
	 * @return boolean TRUE to manage the full list of participants, FALSE otherwise
	 *
	 */
	function with_enrolment() {
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