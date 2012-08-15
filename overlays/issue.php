<?php
/**
 * describe one issue
 *
 * This overlay is aiming to track status of various kinds of issue:
 * - incident - issue has been submitted by an end-user that has a problem
 * - maintenance - issue has been reported by the support team, because of a planned interruption
 * - patch - issue is actually a suggestion to improve the service, for example, a new piece of software
 * - feature - something new has to be created
 *
 * The overall workflow is the following:
 * [snippet]
 * on-going:suspect (create_date)
 *	 V
 *	 + qualification --> cancelled:suspect (qualification_date)
 *	 V
 * on-going:problem (qualification_date)
 *	 V
 *	 + analysis --> cancelled:problem (analysis_date)
 *	 V
 * on-going:issue (analysis_date)
 *	 V
 *	 + resolution --> cancelled:issue (resolution_date)
 *	 V
 * on-going:solution (resolution_date)
 *	 V
 *	 + integration --> cancelled:solution (close_date)
 *	 V
 * completed:solution (close_date)
 * [/snippet]
 *
 * Transition dates and attributes are recorded the same way in the database. However, labels
 * used to describe transitions depend of the type of the issue. Also, not all steps are required
 * to all types.
 *
 * Label, status, progress, and title complement when type is 'feature':
 * - on-going:suspect (create_date) - Feature request has been submitted - Submitted - 0%
 * - cancelled:suspect (qualification_date) - Immediate solution has been provided - Closed - 100% - Cancelled
 * - on-going:problem (qualification_date) - Feature request is valid - On-going - 20% - Validated
 * - cancelled:problem (analysis_date) - No technical solution has been found - Closed - 100% - Rejected
 * - on-going:issue (analysis_date) - Solution architecture has been documented - On-going - 50% - Analyzed
 * - cancelled:issue (resolution_date) - No resource to work on the software - Closed - 100% - Rejected
 * - on-going:solution (resolution_date) - A developer is working on this - On-going - 80% - Pending
 * - cancelled:solution (close_date) - Software is available separately - Closed - 100% - Released
 * - completed:solution (close_date) - Software has been fully integrated - Closed - 100% - Integrated
 *
 * Label, status, progress, and title complement when type is 'incident':
 * - on-going:suspect (create_date) - Problem has been recorded - Opened - 0%
 * - cancelled:suspect (qualification_date) - Immediate solution has been provided - Closed - 100% - Solved
 * - on-going:problem (qualification_date) - Problem is valid and may be repeated - On-going - 20% - Validated
 * - cancelled:problem (analysis_date) - No way to analyze the problem - Closed - 100% - Cancelled
 * - on-going:issue (analysis_date) - Issue has been documented and cause has been identified - On-going - 50% - Analyzed
 * - cancelled:issue (resolution_date) - No specific solution has been released - Closed - 100% - Solved
 * - on-going:solution (resolution_date) - A solution has been made available - On-going - 80% - Solved
 * - cancelled:solution (close_date) - Solution is available separately - Closed - 100% - Patched
 * - completed:solution (close_date) - Solution has been fully integrated - Closed - 100% - Integrated
 *
 * Label, status, progress, and title complement when type is 'maintenance':
 * - on-going:suspect (create_date) - Change is foreseen - Identified - 0%
 * - cancelled:suspect (qualification_date) - Change can be avoided - Closed - 100% - Cancelled
 * - on-going:problem (qualification_date) - Change request is valid - On-going - 20% - Confirmed
 * - cancelled:problem (analysis_date) - N/A
 * - on-going:issue (analysis_date) - N/A
 * - cancelled:issue (resolution_date) - Change has been cancelled - Closed - 100% - Cancelled
 * - on-going:solution (resolution_date) - Change has been initiated - On-going - 80% - Started
 * - cancelled:solution (close_date) - Partial change has been achieved - Closed - 100% - Terminated
 * - completed:solution (close_date) - Change has been fully completed - Closed - 100% - Completed
 *
 * Label, status, progress, and title complement when type is 'patch':
 * - on-going:suspect (create_date) - Patch has been submittted - Submitted - 0%
 * - cancelled:suspect (qualification_date) - Patch submission is not valid - Closed - 100% - Cancelled
 * - on-going:problem (qualification_date) - Patch submission is valid - On-going - 20% - Validated
 * - cancelled:problem (analysis_date) - Patch submission has been rejected - Closed - 100% - Rejected
 * - on-going:issue (analysis_date) - Patch should be integrated - On-going - 50% - Analyzed
 * - cancelled:issue (resolution_date) - No resource to work on the software - Closed - 100% - Rejected
 * - on-going:solution (resolution_date) - A developer is working on this - On-going - 80% - Pending
 * - cancelled:solution (close_date) - Software is available separately - Closed - 100% - Released
 * - completed:solution (close_date) - Software has been fully integrated - Closed - 100% - Integrated
 *
 * This overlay saves a number of attributes in the side table [code]yacs_issues[/code].
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Issue extends Overlay {

	/**
	 * get radio buttons to change the color
	 *
	 * @param string the current color, if any
	 * @return the HTML to insert in the page
	 */
	function get_color_as_radio_buttons($color='green') {
		global $context;

		$text = '<span style="background-color: '.self::get_color_value('green').';"> <input type="radio" name="color" value="green"';
		if($color == 'green')
			$text .= ' checked="checked"';
		$text .= '/> '.i18n::s('Situation is under control').' </span>'
			.BR
			.'<span style="background-color: '.self::get_color_value('orange').';"> <input type="radio" name="color" value="orange"';
		if($color == 'orange')
			$text .= ' checked="checked"';
		$text .= '/> '.i18n::s('Exceptional effort is required').' </span>'
			.BR
			.'<span style="background-color: '.self::get_color_value('red').';"> <input type="radio" name="color" value="red"';
		if($color == 'red')
			$text .= ' checked="checked"';
		$text .= '/> '.i18n::s('This is our first priority').'&nbsp;</span>';
		return $text;
	}

	/**
	 * get a label for a given color
	 *
	 * @param string the color
	 * @return string the label to display
	 */
	function get_color_label($color) {
		global $context;

		switch($color) {
		case 'green':
		default:
			return '<span style="background-color: '.self::get_color_value('green').';">'.i18n::s('Situation is under control').'</span>';

		case 'orange':
			return '<span style="background-color: '.self::get_color_value('orange').';">'.i18n::s('Exceptional effort is required').'</span>';

		case 'red':
			return '<span style="background-color: '.self::get_color_value('red').';">'.i18n::s('This is our first priority').'</span>';

		}

	}

	/**
	 * get color value
	 *
	 * @param string the color
	 * @return string the hexadecimal value
	 */
	function get_color_value($color) {
		global $context;

		switch($color) {
		case 'green':
		default:
			return '#00FF00';

		case 'orange':
			return '#FAAC58';

		case 'red':
			return '#FA5858';

		}

	}

	/**
	 * streamline the user interface as much as possible
	 */
	function get_edit_as_simple_value() {
		return TRUE;
	}

	/**
	 * build the list of fields for one overlay
	 *
	 * The current status, and the related status date are proposed
	 *
	 * @see overlays/overlay.php
	 *
	 * @param the hosting attributes
	 * @return a list of ($label, $input, $hint)
	 */
	function get_fields($host) {
		global $context;

		// form fields
		$fields = array();

		// capture of initial data
		if(!isset($host['id'])) {
			$this->attributes['type'] = 'incident';

			$label = i18n::s('Workflow');
			$input = '<select name="type" id="type">'.self::get_type_options($this->attributes['type']).'</select>';

			$fields[] = array($label, $input);

		}

		// job done
		return $fields;
	}

	/**
	 * build the history for this issue
	 *
	 * @return string an unnumbered list of dates
	 */
	function get_history() {
		global $context;

		// sanity check
		if(!is_object($this->anchor))
			return NULL;

		$query = "SELECT * FROM ".SQL::table_name('issues')." AS issues "
			." WHERE (issues.anchor LIKE '".SQL::escape($this->anchor->get_reference())."')";

		// fetch the first row
		if(!$row = SQL::query_first($query))
			return NULL;

		// text returned
		$text = '';

		// the creation step
		if($row['create_date'] && ($row['create_date'] > NULL_DATE))
			$text .= self::get_history_item(i18n::s('Submission'), $row['create_date'], $row['create_name'], $row['create_address'], $row['create_id']);

		// all steps
		$steps = array('cancelled:suspect', 'on-going:problem', 'cancelled:problem', 'on-going:issue', 'cancelled:issue', 'on-going:solution', 'cancelled:solution', 'completed:solution');

		// the qualification step
		if(in_array($this->attributes['status'], $steps) && $row['qualification_date'] && ($row['qualification_date'] > NULL_DATE))
			$text .= self::get_history_item(i18n::s('Qualification'), $row['qualification_date'], $row['qualification_name'], $row['qualification_address'], $row['qualification_id']);

		// remove qualification
		array_shift($steps);
		array_shift($steps);

		// the analysis step
		if(in_array($this->attributes['status'], $steps) && $row['analysis_date'] && ($row['analysis_date'] > NULL_DATE))
			$text .= self::get_history_item(i18n::s('Analyzis'), $row['analysis_date'], $row['analysis_name'], $row['analysis_address'], $row['analysis_id']);

		// remove analysis
		array_shift($steps);
		array_shift($steps);

		// the solution step
		if(in_array($this->attributes['status'], $steps) && $row['resolution_date'] && ($row['resolution_date'] > NULL_DATE))
			$text .= self::get_history_item(i18n::s('Action'), $row['resolution_date'], $row['resolution_name'], $row['resolution_address'], $row['resolution_id']);

		// remove resolution
		array_shift($steps);
		array_shift($steps);

		// the close step
		if(in_array($this->attributes['status'], $steps) && $row['close_date'] && ($row['close_date'] > NULL_DATE))
			$text .= self::get_history_item(i18n::s('Finalization'), $row['close_date'], $row['close_name'], $row['close_address'], $row['close_id']);

		if($text)
			return "<ul>".$text."</ul>";
		return NULL;
	}

	/**
	 * build one history item
	 */
	function get_history_item($action, $date, $name, $address, $id) {
		global $context;

		if($name)
			$text = sprintf(i18n::s('%s %s by %s'), $action, Skin::build_date($date), Users::get_link($name, $address, $id));
		else
			$text = sprintf(i18n::s('%s %s'), $action, Skin::build_date($date));

		return '<li>'.$text."</li>\n";
	}

	/**
	 * get an overlaid label
	 *
	 * Accepted action codes:
	 * - 'edit' the title for the modification of an existing object
	 * - 'delete' the title for the deleting form
	 * - 'new' the title for the creation of a new object
	 * - 'view' the title for a displayed object
	 *
	 * @see overlays/overlay.php
	 *
	 * @param string the target label
	 * @param string the on-going action
	 * @return the title to use
	 */
	function get_label($name, $action='view') {
		global $context;

		// the target label
		switch($name.':'.$action) {

		case 'description:articles':
			return i18n::s('Issue description');

		case 'edit_command:articles':
			return i18n::s('Edit this issue');

		case 'new_command:articles':
			return i18n::s('Add an issue');

		case 'page_title:edit':
			return i18n::s('Edit an issue');

		case 'page_title:delete':
			return i18n::s('Delete an issue');

		case 'page_title:new':
			return i18n::s('Add an issue');

		}

		// no match
		return NULL;
	}

	/**
	 * display a live title
	 *
	 * @see overlays/overlay.php
	 *
	 * @param array the hosting record
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_live_title($host=NULL) {
		global $context;

		$text = $host['title'];

		// just created
		if($this->attributes['status'] == 'on-going:suspect')
			return $text;

		$text .= ' [';

		switch($this->attributes['status']) {

		case 'cancelled:suspect':
			switch($this->attributes['type']) {
			case 'feature':
				$text .= i18n::s('Cancelled');
				break;
			case 'incident':
			default:
				$text .= i18n::s('Solved');
				break;
			case 'maintenance':
				$text .= i18n::s('Cancelled');
				break;
			case 'patch':
				$text .= i18n::s('Cancelled');
				break;
			}
			break;

		case 'on-going:problem':
			switch($this->attributes['type']) {
			case 'feature':
				$text .= i18n::s('Validated');
				break;
			case 'incident':
			default:
				$text .= i18n::s('Validated');
				break;
			case 'maintenance':
				$text .= i18n::s('Confirmed');
				break;
			case 'patch':
				$text .= i18n::s('Validated');
				break;
			}
			break;

		case 'cancelled:problem':
			switch($this->attributes['type']) {
			case 'feature':
				$text .= i18n::s('Rejected');
				break;
			case 'incident':
			default:
				$text .= i18n::s('Cancelled');
				break;
			case 'maintenance':
				$text .= 'N/A';
				break;
			case 'patch':
				$text .= i18n::s('Rejected');
				break;
			}
			break;

		case 'on-going:issue':
			switch($this->attributes['type']) {
			case 'feature':
				$text .= i18n::s('Analyzed');
				break;
			case 'incident':
			default:
				$text .= i18n::s('Analyzed');
				break;
			case 'maintenance':
				$text .= 'N/A';
				break;
			case 'patch':
				$text .= i18n::s('Analyzed');
				break;
			}
			break;

		case 'cancelled:issue':
			switch($this->attributes['type']) {
			case 'feature':
				$text .= i18n::s('Rejected');
				break;
			case 'incident':
			default:
				$text .= i18n::s('Solved');
				break;
			case 'maintenance':
				$text .= i18n::s('Cancelled');
				break;
			case 'patch':
				$text .= i18n::s('Rejected');
				break;
			}
			break;

		case 'on-going:solution':
			switch($this->attributes['type']) {
			case 'feature':
				$text .= i18n::s('Pending');
				break;
			case 'incident':
			default:
				$text .= i18n::s('Solved');
				break;
			case 'maintenance':
				$text .= i18n::s('Started');
				break;
			case 'patch':
				$text .= i18n::s('Pending');
				break;
			}
			break;

		case 'cancelled:solution':
			switch($this->attributes['type']) {
			case 'feature':
				$text .= i18n::s('Released');
				break;
			case 'incident':
			default:
				$text .= i18n::s('Patched');
				break;
			case 'maintenance':
				$text .= i18n::s('Terminated');
				break;
			case 'patch':
				$text .= i18n::s('Released');
				break;
			}
			break;

		case 'completed:solution':
			switch($this->attributes['type']) {
			case 'feature':
				$text .= i18n::s('Integrated');
				break;
			case 'incident':
			default:
				$text .= i18n::s('Integrated');
				break;
			case 'maintenance':
				$text .= i18n::s('Completed');
				break;
			case 'patch':
				$text .= i18n::s('Integrated');
				break;
			}
			break;

		}

		$text .= ']';

		// return by reference
		return $text;
	}

	/**
	 * display the content of one overlay in a list
	 *
	 * To be overloaded into derived class
	 *
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_list_text($host=NULL) {

		// show progress
		$text = BR.self::get_progress_value();

		return $text;
	}

	/**
	 * get status as options of a &lt;SELECT&gt; field
	 *
	 * @param string the current status, if any
	 * @return the HTML to insert in the page
	 */
	function get_options($status) {
		global $context;

		$options['on-going:suspect']	= self::get_status_label('on-going:suspect');
		$options['cancelled:suspect']	= self::get_status_label('cancelled:suspect');
		$options['on-going:problem']	= self::get_status_label('on-going:problem');
		$options['cancelled:problem']	= self::get_status_label('cancelled:problem');
		$options['on-going:issue']		= self::get_status_label('on-going:issue');
		$options['cancelled:issue'] 	= self::get_status_label('cancelled:issue');
		$options['on-going:solution']	= self::get_status_label('on-going:solution');
		$options['cancelled:solution']	= self::get_status_label('cancelled:solution');
		$options['completed:solution']	= self::get_status_label('completed:issue');

		foreach($options as $value => $label) {
			$content .= '<option value="'.$value.'"';
			if($status == $value)
				$content .= ' selected';
			$content .='>'.$label."</option>\n";
		}
		return $content;
	}

	/**
	 * provide issue progress
	 *
	 * @param mixed default value
	 * @return string current issue type
	 */
	function get_progress_value($default=NULL) {
		global $context;

		// based on status
		switch($this->attributes['status']) {

		case 'on-going:suspect':
		default:
			$meter = 0;
			break;

		case 'cancelled:suspect':
			$meter = 100;
			break;

		case 'on-going:problem':
			$meter = 20;
			break;

		case 'cancelled:problem':
			$meter = 100;
			break;

		case 'on-going:issue':
			$meter = 50;
			break;

		case 'cancelled:issue':
			$meter = 100;
			break;

		case 'on-going:solution':
			$meter = 80;
			break;

		case 'cancelled:solution':
			$meter = 100;
			break;

		case 'completed:solution':
			$meter = 100;
			break;
		}

		// abnormal case
		if(!isset($this->attributes['color']) || ($meter == 100))
			$extra = '';
		elseif($this->attributes['color'] == 'orange')
			$extra = '-orange';
		elseif($this->attributes['color'] == 'red')
			$extra = '-red';
		else
			$extra = '';

		// return
		return '<img src="'.$context['url_to_root'].'skins/_reference/overlays/percent-'.$meter.$extra.'.png" alt="'.$meter.'%" />';
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
		default:
			return '';

		case 'on-going:suspect':
			switch($this->attributes['type']) {
			case 'feature':
				return i18n::s('Feature request has been submitted');
			case 'incident':
			default:
				return i18n::s('Problem has been recorded');
			case 'maintenance':
				return i18n::s('Change is foreseen');
			case 'patch':
				return i18n::s('Patch has been submitted');
			}

		case 'cancelled:suspect':
			switch($this->attributes['type']) {
			case 'feature':
				return i18n::s('Immediate solution has been provided');
			case 'incident':
			default:
				return i18n::s('Immediate solution has been provided');
			case 'maintenance':
				return i18n::s('Change can be avoided');
			case 'patch':
				return i18n::s('Patch submission is not valid');
			}

		case 'on-going:problem':
			switch($this->attributes['type']) {
			case 'feature':
				return i18n::s('Feature request is valid');
			case 'incident':
			default:
				return i18n::s('Problem is valid and may be repeated');
			case 'maintenance':
				return i18n::s('Change request is valid');
			case 'patch':
				return i18n::s('Patch submission is valid');
			}

		case 'cancelled:problem':
			switch($this->attributes['type']) {
			case 'feature':
				return i18n::s('No technical solution has been found');
			case 'incident':
			default:
				return i18n::s('No way to analyze the problem');
			case 'maintenance':
				return 'N/A';
			case 'patch':
				return i18n::s('Patch submission has been rejected');
			}

		case 'on-going:issue':
			switch($this->attributes['type']) {
			case 'feature':
				return i18n::s('Solution architecture has been documented');
			case 'incident':
			default:
				return i18n::s('Issue has been documented and cause has been identified');
			case 'maintenance':
				return 'N/A';
			case 'patch':
				return i18n::s('Patch should be integrated');
			}

		case 'cancelled:issue':
			switch($this->attributes['type']) {
			case 'feature':
				return i18n::s('No resource to work on the software');
			case 'incident':
			default:
				return i18n::s('No specific solution has been released');
			case 'maintenance':
				return i18n::s('Change has been cancelled');
			case 'patch':
				return i18n::s('No resource to work on the software');
			}

		case 'on-going:solution':
			switch($this->attributes['type']) {
			case 'feature':
				return i18n::s('A developer is working on this');
			case 'incident':
			default:
				return i18n::s('A solution has been made available');
			case 'maintenance':
				return i18n::s('Change has been initiated');
			case 'patch':
				return i18n::s('A developer is working on this');
			}

		case 'cancelled:solution':
			switch($this->attributes['type']) {
			case 'feature':
				return i18n::s('Software is available separately');
			case 'incident':
			default:
				return i18n::s('Solution is available separately');
			case 'maintenance':
				return i18n::s('Partial change has been achieved');
			case 'patch':
				return i18n::s('Software is available separately');
			}

		case 'completed:solution':
			switch($this->attributes['type']) {
			case 'feature':
				return i18n::s('Software has been fully integrated');
			case 'incident':
			default:
				return i18n::s('Solution has been fully integrated');
			case 'maintenance':
				return i18n::s('Change has been fully completed');
			case 'patch':
				return i18n::s('Software has been fully integrated');
			}

		}
	}

	/**
	 * provide status as a string
	 *
	 * @see overlays/overlay.php
	 *
	 * @param mixed default value
	 * @return string current issue type
	 */
	function get_status_value($default=NULL) {
		global $context;

		switch($this->attributes['status']) {

		case 'on-going:suspect':
		default:
			switch($this->attributes['type']) {
			case 'feature':
				return i18n::s('Submitted');
			case 'incident':
			default:
				return i18n::s('Opened');
			case 'maintenance':
				return i18n::s('Identified');
			case 'patch':
				return i18n::s('Submitted');
			}

		case 'on-going:problem':
		case 'on-going:issue':
		case 'on-going:solution':
			return i18n::s('On-going');

		case 'cancelled:suspect':
		case 'cancelled:problem':
		case 'cancelled:issue':
		case 'cancelled:solution':
		case 'completed:solution':
			return i18n::s('Closed');

		}

	}

	/**
	 * add some tabs
	 *
	 * Display additional information in panels.
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
		$tracking = '';

		// only associates and page owners can change the status
		if(($variant == 'edit') && isset($this->anchor) && $this->anchor->is_owned()) {

			// a table of fields
			$fields = array();

			// owner
			$label = i18n::s('Owner');
			if(isset($host['owner_id']) && ($user = Users::get($host['owner_id'])))
				$value = $user['nick_name'];
			else
				$value = '';
			$input = '<input type="text" name="owner" id="owner" value ="'.encode_field($value).'" size="25" maxlength="32" />';
			$hint = i18n::s('Type some letters of the name and select in the list');
			$fields[] = array($label, $input, $hint);

			$tracking .= JS_PREFIX
				.'$(function() { Yacs.autocomplete_names("owner",true); });'."\n" // enable owner autocompletion
				.JS_SUFFIX;

			// priority
			$label = i18n::s('Priority');
			if(!isset($this->attributes['color']))
				$this->attributes['color'] = 'green';
			$input = self::get_color_as_radio_buttons($this->attributes['color']);
			$fields[] = array($label, $input);

			// type
			$label = i18n::s('Workflow');
			if(!isset($this->attributes['type']))
				$this->attributes['type'] = 'incident';
			$input = '<select name="type" id="type">'.self::get_type_options($this->attributes['type']).'</select>';
			$fields[] = array($label, $input);

			// format these fields
			$tracking .= Skin::build_form($fields);
			$fields = array();

			// to represent transitions from one step to the next one
			Skin::define_img('NEXT_STEP', 'overlays/next_step.gif', 'V');

			// status
			if(!isset($this->attributes['status']))
				$this->attributes['status'] = 'on-going:suspect';

			// create_date
			if(!isset($host['create_date']) || !$host['create_date'])
				$host['create_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
			$host['create_date'] = Surfer::from_GMT($host['create_date']);
			if($this->attributes['type'] == 'feature')
				$label = i18n::s('Feature request has been created on %s');
			elseif($this->attributes['type'] == 'patch')
				$label = i18n::s('Patch has been submitted on %s');
			else
				$label = i18n::s('Page has been created on %s');
			$tracking .= '<div class="bottom" style="margin-bottom: 1em;">'.sprintf($label, Skin::build_input('create_date', $host['create_date'], 'date_time').' <a onclick="$(\'#create_date\').val(\''.$now.'\')" style="cursor: pointer;" class="details">'.i18n::s('now').'</a>').'</div>';

			$tracking .= NEXT_STEP;

			// qualification_date
			if(isset($this->attributes['qualification_date']))
				$this->attributes['qualification_date'] = Surfer::from_GMT($this->attributes['qualification_date']);
			$tracking .= '<div style="margin-top: 1em">'.sprintf(i18n::s('Qualification has taken place on %s'), Skin::build_input('qualification_date', isset($this->attributes['qualification_date'])?$this->attributes['qualification_date'] : NULL_DATE, 'date_time').' <a onclick="$(\'#qualification_date\').val(\''.$now.'\')" style="cursor: pointer;" class="details">'.i18n::s('now').'</a>').'<p>';
			$checked = '';
			if(isset($this->attributes['status']) && ($this->attributes['status'] == 'on-going:problem'))
				$checked = 'checked="checked"';
			$tracking .= '<input type="radio" name="status" value ="on-going:problem" '.$checked.' />&nbsp;'.$this->get_status_label('on-going:problem');
			$checked = '';
			if(isset($this->attributes['status']) && ($this->attributes['status'] == 'cancelled:suspect'))
				$checked = 'checked="checked"';
			$tracking .= BR.'<input type="radio" name="status" value ="cancelled:suspect" '.$checked.' />&nbsp;'.$this->get_status_label('cancelled:suspect').'</p></div>';

			$tracking .= NEXT_STEP;

			// analysis_date, except for maintenance cases
			if($this->attributes['type'] != 'maintenance') {

				if(isset($this->attributes['analysis_date']))
					$this->attributes['analysis_date'] = Surfer::from_GMT($this->attributes['analysis_date']);
				$tracking .= '<div style="margin-top: 1em">'.sprintf(i18n::s('Analysis has ended on %s'), Skin::build_input('analysis_date', isset($this->attributes['analysis_date'])?$this->attributes['analysis_date'] : NULL_DATE, 'date_time').' <a onclick="$(\'#analysis_date\').val(\''.$now.'\')" style="cursor: pointer;" class="details">'.i18n::s('now').'</a>').'<p>';
				$checked = '';
				if(isset($this->attributes['status']) && ($this->attributes['status'] == 'on-going:issue'))
					$checked = 'checked="checked"';
				$tracking .= '<input type="radio" name="status" value ="on-going:issue" '.$checked.' />&nbsp;'.$this->get_status_label('on-going:issue');
				$checked = '';
				if(isset($this->attributes['status']) && ($this->attributes['status'] == 'cancelled:problem'))
					$checked = 'checked="checked"';
				$tracking .= BR.'<input type="radio" name="status" value ="cancelled:problem" '.$checked.' />&nbsp;'.$this->get_status_label('cancelled:problem').'</p></div>';

				$tracking .= NEXT_STEP;

			}

			// resolution_date
			if(isset($this->attributes['resolution_date']))
				$this->attributes['resolution_date'] = Surfer::from_GMT($this->attributes['resolution_date']);
			if($this->attributes['type'] == 'feature')
				$label = i18n::s('Assignment has been finalized on %s');
			elseif($this->attributes['type'] == 'maintenance')
				$label = i18n::s('Change has been finalized on %s');
			elseif($this->attributes['type'] == 'patch')
				$label = i18n::s('Assignment has been finalized on %s');
			else
				$label = i18n::s('Resolution has been finalized on %s');
			$tracking .= '<div style="margin-top: 1em">'.sprintf($label, Skin::build_input('resolution_date', isset($this->attributes['resolution_date'])?$this->attributes['resolution_date'] : NULL_DATE, 'date_time').' <a onclick="$(\'#resolution_date\').val(\''.$now.'\')" style="cursor: pointer;" class="details">'.i18n::s('now').'</a>').'<p>';
			$checked = '';
			if(isset($this->attributes['status']) && ($this->attributes['status'] == 'on-going:solution'))
				$checked = 'checked="checked"';
			$tracking .= '<input type="radio" name="status" value ="on-going:solution" '.$checked.' />&nbsp;'.$this->get_status_label('on-going:solution');
			$checked = '';
			if(isset($this->attributes['status']) && ($this->attributes['status'] == 'cancelled:issue'))
				$checked = 'checked="checked"';
			$tracking .= BR.'<input type="radio" name="status" value ="cancelled:issue" '.$checked.' />&nbsp;'.$this->get_status_label('cancelled:issue').'</p></div>';

			$tracking .= NEXT_STEP;

			// close_date
			if(isset($this->attributes['close_date']))
				$this->attributes['close_date'] = Surfer::from_GMT($this->attributes['close_date']);
			$tracking .= '<div style="margin-top: 1em">'.sprintf(i18n::s('Case has been closed on %s'), Skin::build_input('close_date', isset($this->attributes['close_date'])?$this->attributes['close_date'] : NULL_DATE, 'date_time').' <a onclick="$(\'#close_date\').val(\''.$now.'\')" style="cursor: pointer;" class="details">'.i18n::s('now').'</a>').'<p>';
			$checked = '';
			if(isset($this->attributes['status']) && ($this->attributes['status'] == 'completed:solution'))
				$checked = 'checked="checked"';
			$tracking .= '<input type="radio" name="status" value ="completed:solution" '.$checked.' />&nbsp;'.$this->get_status_label('completed:solution');
			$checked = '';
			if(isset($this->attributes['status']) && ($this->attributes['status'] == 'cancelled:solution'))
				$checked = 'checked="checked"';
			$tracking .= BR.'<input type="radio" name="status" value ="cancelled:solution" '.$checked.' />&nbsp;'.$this->get_status_label('cancelled:solution').'</p></div>';

		}

		// finalize this tab
		if($tracking)
			$tabs[] = array('tracking', i18n::s('Tracking'), 'tracking_panel', $tracking);

		// add these tabs
		return $tabs;
	}

	/**
	 * provide type as a string
	 *
	 * @param string type to describe
	 * @return string current issue type
	 */
	function get_type_label($type='incident') {
		global $context;

		switch($type) {

		case 'feature':
			return i18n::s('Feature request');

		default:
		case 'incident':
			return i18n::s('Support request');

		case 'maintenance':
			return i18n::s('Planned change');

		case 'patch':
			return i18n::s('Patch submission');

		}

	}

	/**
	 * get type as options of a &lt;SELECT&gt; field
	 *
	 * @param string the current type, if any
	 * @return the HTML to insert in the page
	 */
	function get_type_options($type) {
		global $context;

		$options = array();
		$options['incident']	= self::get_type_label('incident');
		$options['feature']	= self::get_type_label('feature');
		$options['patch']	= self::get_type_label('patch');
		$options['maintenance']	= self::get_type_label('maintenance');

		$content = '';
		foreach($options as $value => $label) {
			$content .= '<option value="'.$value.'"';
			if($type == $value)
				$content .= ' selected="selected"';
			$content .='>'.$label."</option>\n";
		}
		return $content;
	}

	/**
	 * provide type as a string
	 *
	 * @see overlays/overlay.php
	 *
	 * @param mixed default value
	 * @return string current issue type
	 */
	function get_type_value($default=NULL) {
		global $context;

		if(!isset($this->attributes['type']))
			return self::get_type_label('incident');

		return self::get_type_label($this->attributes['type']);

	}

	/**
	 * provide a value specific to this overlay
	 *
	/**
	 * display content of main panel
	 *
	 * Everything is in a separate panel
	 *
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_view_text($host=NULL) {
		$text = '';

		$rows = array();

		// this page has an explicit owner
		if(isset($host['owner_id']) && ($user = Users::get($host['owner_id']))) {

			// allow for click-to-call
			$click_to_call = Users::get_click_to_call($user);

			// display information on the owner
			$rows[] = array(i18n::s('Owner'), Users::get_link($user['full_name'], NULL, $user['id']).' '.$click_to_call);
		}

		// show progress
		$rows[] = array(i18n::s('Progress'), self::get_progress_value());

		// type
		$rows[] = array(i18n::s('Workflow'), self::get_type_value());

		// the status and history
		$history = self::get_history();
		$rows[] = array(i18n::s('Status'), self::get_status_label($this->attributes['status']).$history);

		$text = Skin::table(NULL, $rows, 'grid');
		return $text;
	}

	/**
	 * initialize this instance
	 *
	 */
	function initialize() {

		$this->attributes['status'] = 'on-going:suspect';
		$this->attributes['type'] = 'incident';

	}

	/**
	 * retrieve the content of one modified overlay
	 *
	 * These are data saved into the piggy-backed overlay field of the hosting record.
	 *
	 * If change is the status affects a previous step of the process, then this is either a simple date
	 * update or some steps have to be cancelled.
	 *
	 * Current and previous step are computed using following table:
	 * - 'on-going:suspect': step 1 - creation
	 * - 'cancelled:suspect': step 2 - qualification
	 * - 'on-going:problem': step 2 - qualification
	 * - 'cancelled:problem': step 3 - analysis
	 * - 'on-going:issue': step 3 - analysis
	 * - 'cancelled:issue': step 4 - resolution
	 * - 'on-going:solution': step 4 - resolution
	 * - 'cancelled:solution': step 5 - close
	 * - 'completed:solution': step 5 - close
	 *
	 * @see overlays/overlay.php
	 *
	 * @param the fields as filled by the end user
	 */
	function parse_fields($fields) {

		$this->attributes['color'] = isset($fields['color']) ? $fields['color'] : 'green';
		$this->attributes['owner'] = isset($fields['owner']) ? $fields['owner'] : '';
		$this->attributes['status'] = isset($fields['status']) ? $fields['status'] : 'on-going:suspect';
		$this->attributes['type'] = isset($fields['type']) ? $fields['type'] : 'incident';
		$this->attributes['create_date'] = isset($fields['create_date']) ? Surfer::to_GMT($fields['create_date']) : NULL_DATE;
		$this->attributes['qualification_date'] = isset($fields['qualification_date']) ? Surfer::to_GMT($fields['qualification_date']) : NULL_DATE;
		$this->attributes['analysis_date'] = isset($fields['analysis_date']) ? Surfer::to_GMT($fields['analysis_date']) : NULL_DATE;
		$this->attributes['resolution_date'] = isset($fields['resolution_date']) ? Surfer::to_GMT($fields['resolution_date']) : NULL_DATE;
		$this->attributes['close_date'] = isset($fields['close_date']) ? Surfer::to_GMT($fields['close_date']) : NULL_DATE;
	}

	/**
	 * remember an action once it's done
	 *
	 * This function saves data into the table [code]yacs_issues[/code].
	 *
	 * @see overlays/overlay.php
	 *
	 * @param string the action 'insert', 'update' or 'delete'
	 * @param array the hosting record
	 * @param string reference of the hosting record (e.g., 'article:123')
	 * @return FALSE on error, TRUE otherwise
	 */
	function remember($action, $host, $reference) {
		global $context;

		// locate anchor on 'insert'
		if($reference)
			$this->anchor = Anchors::get($reference);

		// remember data from the anchor
		$this->attributes['anchor_reference'] = '';
		$this->attributes['anchor_title'] = '';
		$this->attributes['anchor_url'] = '';
		if(is_callable(array($this->anchor, 'get_url'))) {
			$this->attributes['anchor_reference'] = $this->anchor->get_reference();
			$this->attributes['anchor_title'] = $this->anchor->get_title();
			$this->attributes['anchor_url'] = $this->anchor->get_url();
		}

		// set default values for this editor
		Surfer::check_default_editor($this->attributes);

		// default date values
		if(!isset($this->attributes['create_date']) || ($this->attributes['create_date'] <= NULL_DATE))
			$this->attributes['create_date'] = $this->attributes['edit_date'];
		if(!isset($this->attributes['qualification_date']) || ($this->attributes['qualification_date'] <= NULL_DATE))
			$this->attributes['qualification_date'] = NULL_DATE;
		if(!isset($this->attributes['analysis_date']) || ($this->attributes['analysis_date'] <= NULL_DATE))
			$this->attributes['analysis_date'] = NULL_DATE;
		if(!isset($this->attributes['resolution_date']) || ($this->attributes['resolution_date'] <= NULL_DATE))
			$this->attributes['resolution_date'] = NULL_DATE;
		if(!isset($this->attributes['close_date']) || ($this->attributes['close_date'] <= NULL_DATE))
			$this->attributes['close_date'] = NULL_DATE;

		// add a notification to the anchor page
		$comments = array();

		// build the update query
		switch($action) {

		case 'delete':
			$query = "DELETE FROM ".SQL::table_name('issues')." WHERE anchor LIKE '".$this->attributes['anchor_reference']."'";
			break;

		case 'insert':
			$comments[] = i18n::s('Page has been created');

			// set host owner, if any
			if($this->attributes['owner'] && ($user = Users::get($this->attributes['owner'])) && ($user['id'] != Surfer::get_id())) {
				$fields = array();
				$fields['owner_id'] = $user['id'];
				$this->anchor->set_values($fields);

				Members::assign('user:'.$user['id'], $this->anchor->get_reference());
				Members::assign($this->anchor->get_reference(), 'user:'.$user['id']);

				$comments[] = sprintf(i18n::s('Owner has been changed to %s'), Skin::build_link(Users::get_permalink($user), $user['full_name']));
			}

			$query = "INSERT INTO ".SQL::table_name('issues')." SET \n"
				."anchor='".SQL::escape($this->attributes['anchor_reference'])."', \n"
				."anchor_url='".SQL::escape($this->attributes['anchor_url'])."', \n"
				."color='".SQL::escape(isset($this->attributes['color'])?$this->attributes['color']:'green')."', \n"
				."status='".SQL::escape(isset($this->attributes['status'])?$this->attributes['status']:'on-going:suspect')."', \n"
				."title='".SQL::escape($this->attributes['anchor_title'])."', \n"
				."type='".SQL::escape(isset($this->attributes['type'])?$this->attributes['type']:'incident')."', \n"
				."create_name='".SQL::escape(isset($this->attributes['create_name']) ? $this->attributes['create_name'] : $this->attributes['edit_name'])."', \n"
				."create_id=".SQL::escape(isset($this->attributes['create_id']) ? $this->attributes['create_id'] : $this->attributes['edit_id']).", \n"
				."create_address='".SQL::escape(isset($this->attributes['create_address']) ? $this->attributes['create_address'] : $this->attributes['edit_address'])."', \n"
				."create_date='".SQL::escape(isset($this->attributes['create_date']) ? $this->attributes['create_date'] : $this->attributes['edit_date'])."', \n"
				."edit_name='".SQL::escape($this->attributes['edit_name'])."', \n"
				."edit_id=".SQL::escape($this->attributes['edit_id']).", \n"
				."edit_address='".SQL::escape($this->attributes['edit_address'])."', \n"
				."edit_action='create', \n"
				."edit_date='".SQL::escape($this->attributes['edit_date'])."', \n"
				."qualification_date='".SQL::escape(isset($this->attributes['qualification_date']) ? $this->attributes['qualification_date'] : NULL_DATE)."', \n"
				."analysis_date='".SQL::escape(isset($this->attributes['analysis_date']) ? $this->attributes['analysis_date'] : NULL_DATE)."', \n"
				."resolution_date='".SQL::escape(isset($this->attributes['resolution_date']) ? $this->attributes['resolution_date'] : NULL_DATE)."', \n"
				."close_date='".SQL::escape(isset($this->attributes['close_date']) ? $this->attributes['close_date'] : NULL_DATE)."'";
			break;

		case 'update':

			// only associates and page owners can update the record
			if(is_callable(array($this->anchor, 'is_owned')) && $this->anchor->is_owned()) {

				// detect type modification
				if($this->attributes['type'] != $this->snapshot['type'])
					$comments[] = sprintf(i18n::s('Workflow has been changed to "%s"'), $this->get_type_label($this->attributes['type']));

				// detect color modification
				if($this->attributes['color'] != $this->snapshot['color'])
					$comments[] = $this->get_color_label($this->attributes['color']);

				// change host owner, if any
				if($this->attributes['owner'] && ($user = Users::get($this->attributes['owner'])) && ($user['id'] != $this->anchor->get_value('owner_id'))) {
					$fields = array();
					$fields['owner_id'] = $user['id'];
					$this->anchor->set_values($fields);

					Members::assign('user:'.$user['id'], $this->anchor->get_reference());
					Members::assign($this->anchor->get_reference(), 'user:'.$user['id']);

					$comments[] = sprintf(i18n::s('Owner has been changed to %s'), Skin::build_link(Users::get_permalink($user), $user['full_name']));
				}

				// update the table of issues
				$query = "UPDATE ".SQL::table_name('issues')." SET \n"
					."anchor='".SQL::escape($this->attributes['anchor_reference'])."', \n"
					."anchor_url='".SQL::escape($this->attributes['anchor_url'])."', \n"
					."color='".SQL::escape($this->attributes['color'])."', \n"
					."status='".SQL::escape($this->attributes['status'])."', \n"
					."title='".SQL::escape($this->attributes['anchor_title'])."', \n"
					."type='".SQL::escape($this->attributes['type'])."', \n"
					."create_date='".SQL::escape(isset($this->attributes['create_date']) ? $this->attributes['create_date'] : $this->attributes['edit_date'])."', \n"
					."qualification_date='".SQL::escape(isset($this->attributes['qualification_date']) ? $this->attributes['qualification_date'] : NULL_DATE)."', \n"
					."analysis_date='".SQL::escape(isset($this->attributes['analysis_date']) ? $this->attributes['analysis_date'] : NULL_DATE)."', \n"
					."resolution_date='".SQL::escape(isset($this->attributes['resolution_date']) ? $this->attributes['resolution_date'] : NULL_DATE)."', \n"
					."close_date='".SQL::escape(isset($this->attributes['close_date']) ? $this->attributes['close_date'] : NULL_DATE)."', \n";

				// detect status modification
				if($this->attributes['status'] != $this->snapshot['status']) {
					$comments[] = $this->get_status_label($this->attributes['status']);

					// depending of new status
					switch($this->attributes['status']) {

					// case has been recorded --should not happen
					case 'on-going:suspect':
						$query .= "create_name='".SQL::escape($this->attributes['edit_name'])."', \n"
							."create_id=".SQL::escape($this->attributes['edit_id']).", \n"
							."create_address='".SQL::escape($this->attributes['edit_address'])."', \n";
						break;

					// problem has been validated
					case 'cancelled:suspect':
					case 'on-going:problem':
						$query .= "qualification_name='".SQL::escape($this->attributes['edit_name'])."', \n"
							."qualification_id='".SQL::escape($this->attributes['edit_id'])."', \n"
							."qualification_address='".SQL::escape($this->attributes['edit_address'])."', \n";
						break;

					// cause has been identified
					case 'cancelled:problem':
					case 'on-going:issue':
						$query .= "analysis_name='".SQL::escape($this->attributes['edit_name'])."', \n"
							."analysis_id='".SQL::escape($this->attributes['edit_id'])."', \n"
							."analysis_address='".SQL::escape($this->attributes['edit_address'])."', \n";
						break;

					// solution has been achieved
					case 'cancelled:issue':
					case 'on-going:solution':
						$query .= "resolution_name='".SQL::escape($this->attributes['edit_name'])."', \n"
							."resolution_id='".SQL::escape($this->attributes['edit_id'])."', \n"
							."resolution_address='".SQL::escape($this->attributes['edit_address'])."', \n";
						break;

					// ending the issue
					case 'cancelled:solution':
					case 'completed:solution':
						$query .= "close_name='".SQL::escape($this->attributes['edit_name'])."', \n"
							."close_id='".SQL::escape($this->attributes['edit_id'])."', \n"
							."close_address='".SQL::escape($this->attributes['edit_address'])."', \n";
						break;
					}

				}

				// track the person who modifies the record
				$query .= "edit_name='".SQL::escape($this->attributes['edit_name'])."', \n"
					."edit_id=".SQL::escape($this->attributes['edit_id']).", \n"
					."edit_address='".SQL::escape($this->attributes['edit_address'])."', \n"
					."edit_action='update', \n"
					."edit_date='".SQL::escape($this->attributes['edit_date'] ? $this->attributes['edit_date'] : $this->attributes['edit_date'])."' \n"
					." WHERE anchor LIKE '".SQL::escape($this->attributes['anchor_reference'])."'";

			}

			// ensure that this change has been recorded
			if(!$comments)
				$comments[] = i18n::s('Page has been edited');

			break;
		}

		// execute the query --don't stop on error
		if(isset($query) && $query)
			SQL::query($query);

		// add a comment
		if($comments) {
			include_once $context['path_to_root'].'comments/comments.php';
			$fields = array();
			$fields['anchor'] = $this->attributes['anchor_reference'];
			$fields['description'] = join(BR, $comments);
			$fields['type'] = 'notification';
			Comments::post($fields);
		}

		// job done
		return TRUE;

	}

	/**
	 * create tables for issues
	 *
	 * @see control/setup.php
	 */
	public static function setup() {
		global $context;

		$fields = array();
		$fields['id']			= "MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT";
		$fields['analysis_name']= "VARCHAR(128) DEFAULT '' NOT NULL";						// root cause analysis
		$fields['analysis_id']	= "MEDIUMINT DEFAULT 0 NOT NULL";
		$fields['analysis_address'] = "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['analysis_date']= "DATETIME";
		$fields['anchor']		= "VARCHAR(64) DEFAULT 'section:1' NOT NULL";				// up to 64 chars
		$fields['anchor_url']	= "VARCHAR(255) DEFAULT '' NOT NULL";						// up to 255 chars
		$fields['close_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";						// end of issue
		$fields['close_id'] 	= "MEDIUMINT DEFAULT 0 NOT NULL";
		$fields['close_address']= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['close_date']	= "DATETIME";
		$fields['color']		= "ENUM('green', 'orange', 'red') DEFAULT 'green' NOT NULL";
		$fields['create_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";						// lead creation
		$fields['create_id']	= "MEDIUMINT DEFAULT 0 NOT NULL";
		$fields['create_address']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['create_date']	= "DATETIME";
		$fields['edit_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";						// item modification
		$fields['edit_id']		= "MEDIUMINT DEFAULT 0 NOT NULL";
		$fields['edit_address'] = "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_action']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['edit_date']	= "DATETIME";
		$fields['qualification_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";				// qualification
		$fields['qualification_id'] = "MEDIUMINT DEFAULT 0 NOT NULL";
		$fields['qualification_address']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['qualification_date']	= "DATETIME";
		$fields['resolution_name']	= "VARCHAR(128) DEFAULT '' NOT NULL";					// resolution
		$fields['resolution_id']= "MEDIUMINT DEFAULT 0 NOT NULL";
		$fields['resolution_address']	= "VARCHAR(128) DEFAULT '' NOT NULL";
		$fields['resolution_date']	= "DATETIME";
		$fields['status']		= "ENUM('on-going:suspect', 'cancelled:suspect',
			'on-going:problem', 'cancelled:problem',
			'on-going:issue', 'cancelled:issue',
			'on-going:solution', 'cancelled:solution', 'completed:solution') DEFAULT 'on-going:suspect' NOT NULL";
		$fields['title']		= "VARCHAR(255) DEFAULT '' NOT NULL";						// up to 255 chars
		$fields['type']			= "ENUM('feature', 'incident', 'maintenance', 'patch') DEFAULT 'incident' NOT NULL";

		$indexes = array();
		$indexes['PRIMARY KEY'] 	= "(id)";
		$indexes['INDEX analysis_date'] = "(analysis_date)";
		$indexes['INDEX anchor']		= "(anchor)";
		$indexes['INDEX close_date']	= "(close_date)";
		$indexes['INDEX create_date']	= "(create_date)";
		$indexes['INDEX create_id'] = "(create_id)";
		$indexes['INDEX edit_id']	= "(edit_id)";
		$indexes['INDEX edit_date'] = "(edit_date)";
		$indexes['INDEX qualification_date']	= "(qualification_date)";
		$indexes['INDEX resolution_date']	= "(resolution_date)";
		$indexes['INDEX status']	= "(status)";
		$indexes['INDEX type']	= "(type)";

		return SQL::setup_table('issues', $fields, $indexes);
	}

}

?>
