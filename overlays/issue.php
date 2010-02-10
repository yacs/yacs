<?php
/**
 * describe one issue
 *
 * @todo add a field to scope the case: cosmetic (issue with the interface), behaviour (functional issue), system-wide (critical issue)
 *
 * This overlay is aiming to track status of various kinds of issue, as per following workflow:
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
 * The issue may be of one of following types:
 * - incident - issue has been submitted by an end-user that has a problem
 * - maintenance - issue has been reported by the support team, because of a planned interruption
 * - improvement - issue is actually a suggestion to improve the service, for example, a new piece of software
 * - development - something new has to be created
 *
 * In the overlay itself, saved along the article, only the last status and the related date are saved.
 * More descriptive data and dates are saved into the table [code]yacs_issues[/code].
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Issue extends Overlay {

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

		// job done
		return $fields;
	}

	/**
	 * build the history for this issue
	 *
	 * @param string anchor for the issue
	 * @return string an unnumbered list of dates
	 */
	function get_history($anchor) {
		global $context;

		// sanity check
		if(!$anchor)
			return NULL;

		$query = "SELECT * FROM ".SQL::table_name('issues')." AS issues "
			." WHERE (issues.anchor LIKE '".SQL::escape($anchor)."')";

		// fetch the first row
		if(!$row =& SQL::query_first($query))
			return NULL;

		// text returned
		$text = '';

		// the creation step
		if($row['create_date'] && ($row['create_date'] > NULL_DATE))
			$text .= '<li>'.sprintf(i18n::s('%s %s by %s'), i18n::s('Submission'), Skin::build_date($row['create_date']), Users::get_link($row['create_name'], $row['create_address'], $row['create_id']))."</li>\n";

		// all steps
		$steps = array('cancelled:suspect', 'on-going:problem', 'cancelled:problem', 'on-going:issue', 'cancelled:issue', 'on-going:solution', 'cancelled:solution', 'completed:solution');

		// the qualification step
		if(in_array($this->attributes['status'], $steps) && $row['qualification_date'] && ($row['qualification_date'] > NULL_DATE))
			$text .= '<li>'.sprintf(i18n::s('%s %s by %s'), i18n::s('Qualification'), Skin::build_date($row['qualification_date']), Users::get_link($row['qualification_name'], $row['qualification_address'], $row['qualification_id']))."</li>\n";

		// remove qualification
		array_shift($steps);
		array_shift($steps);

		// the analysis step
		if(in_array($this->attributes['status'], $steps) && $row['analysis_date'] && ($row['analysis_date'] > NULL_DATE))
			$text .= '<li>'.sprintf(i18n::s('%s %s by %s'), i18n::s('Analyzis'), Skin::build_date($row['analysis_date']), Users::get_link($row['analysis_name'], $row['analysis_address'], $row['analysis_id']))."</li>\n";

		// remove analysis
		array_shift($steps);
		array_shift($steps);

		// the solution step
		if(in_array($this->attributes['status'], $steps) && $row['resolution_date'] && ($row['resolution_date'] > NULL_DATE))
			$text .= '<li>'.sprintf(i18n::s('%s %s by %s'), i18n::s('Resolution'), Skin::build_date($row['resolution_date']), Users::get_link($row['resolution_name'], $row['resolution_address'], $row['resolution_id']))."</li>\n";

		// remove resolution
		array_shift($steps);
		array_shift($steps);

		// the close step
		if(in_array($this->attributes['status'], $steps) && $row['close_date'] && ($row['close_date'] > NULL_DATE))
			$text .= '<li>'.sprintf(i18n::s('%s %s by %s'), i18n::s('Finalization'), Skin::build_date($row['close_date']), Users::get_link($row['close_name'], $row['close_address'], $row['close_id']))."</li>\n";

		if($text)
			return "<ul>".$text."</ul>";
		return NULL;
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
		switch($name) {

		// description label
		case 'description':
			return i18n::s('Issue description');

		// page title
		case 'page_title':

			switch($action) {

			case 'edit':
				return i18n::s('Edit an issue');

			case 'delete':
				return i18n::s('Delete an issue');

			case 'new':
				return i18n::s('Add an issue');

			case 'view':
			default:
				// use the article title as the page title
				return NULL;

			}
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

		// live title
		switch($this->attributes['status']) {

		case 'on-going:suspect':
			break;

		default:
			$text .= ' [';

			switch($this->attributes['status']) {

			case 'on-going:suspect':
			default:
				$text .= i18n::s('Opened');
				break;

			case 'cancelled:suspect':
				$text .= i18n::s('Solved');
				break;

			case 'on-going:problem':
				$text .= i18n::s('Validated');
				break;

			case 'cancelled:problem':
				$text .= i18n::s('Cancelled');
				break;

			case 'on-going:issue':
				$text .= i18n::s('Analyzed');
				break;

			case 'cancelled:issue':
				$text .= i18n::s('No solution');
				break;

			case 'on-going:solution':
				$text .= i18n::s('Solved');
				break;

			case 'cancelled:solution':
				$text .= i18n::s('Patch');
				break;

			case 'completed:solution':
				$text .= i18n::s('Integrated');
				break;

			}

			$text .= ']';
			break;

		}

		// return by reference
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

		// return
		return '<img src="'.$context['url_to_root'].'overlays/issues/percent-'.$meter.'.png" alt="'.$meter.'%"/>';
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
			return i18n::s('Problem has been recorded');

		case 'cancelled:suspect':
			return i18n::s('Immediate solution has been provided');

		case 'on-going:problem':
			return i18n::s('Problem is valid and may be repeated');

		case 'cancelled:problem':
			return i18n::s('No way to analyze the problem');

		case 'on-going:issue':
			return i18n::s('Issue has been documented and cause has been identified');

		case 'cancelled:issue':
			return i18n::s('Resolution has been cancelled');

		case 'on-going:solution':
			return i18n::s('A solution has been made available');

		case 'cancelled:solution':
			return i18n::s('Solution is available separately');

		case 'completed:solution':
			return i18n::s('Change has been fully integrated');
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
			return i18n::s('Opened');

		case 'cancelled:suspect':
		case 'cancelled:problem':
		case 'cancelled:issue':
		case 'cancelled:solution':
		case 'completed:solution':
			return i18n::s('Closed');

		case 'on-going:problem':
		case 'on-going:issue':
		case 'on-going:solution':
			return i18n::s('On-going');
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
		if(($variant == 'edit') && ($anchor = Anchors::get($host['self_reference'])) && $anchor->is_owned()) {

			// type
			if(!isset($this->attributes['type']))
				$this->attributes['type'] = 'incident';
			$tracking .= '<div style="margin-bottom: 1em;">'.i18n::s('Type')
				.' <select name="type" id="type">'.self::get_type_options($this->attributes['type']).'</select>'
				.BR.'<span class="small">'.i18n::s('Select carefully the type of this issue').'</span></div>';

			// for easy detection of type change
			$tracking .= '<input type="hidden" name="previous_type" value="'.$this->attributes['type'].'" />';

			// step 1 - created
			if(!isset($host['create_date']) || !$host['create_date'])
				$host['create_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
			$host['create_date'] = Surfer::from_GMT($host['create_date']);
			$tracking .= '<div class="bottom">'.sprintf(i18n::s('Step 1 - Record has been created on %s'), Skin::build_input('create_date', $host['create_date'], 'date_time').' <a onclick="$(\'create_date\').value = \''.$now.'\'" style="cursor: pointer;" class="details">'.i18n::s('now').'</a>').'</div>';

			// step 2 - qualified
			if(isset($this->attributes['qualification_date']))
				$this->attributes['qualification_date'] = Surfer::from_GMT($this->attributes['qualification_date']);
			$tracking .= '<div style="margin-top: 2em">'.sprintf(i18n::s('Step 2 - Qualification has taken place on %s'), Skin::build_input('qualification_date', isset($this->attributes['qualification_date'])?$this->attributes['qualification_date'] : NULL_DATE, 'date_time').' <a onclick="$(\'qualification_date\').value = \''.$now.'\'" style="cursor: pointer;" class="details">'.i18n::s('now').'</a>').'<p>';
			$checked = '';
			if(isset($this->attributes['status']) && ($this->attributes['status'] == 'on-going:problem'))
				$checked = 'checked="checked"';
			$tracking .= '<input type="radio" name="status" value ="on-going:problem" '.$checked.' />&nbsp;'.$this->get_status_label('on-going:problem');
			$checked = '';
			if(isset($this->attributes['status']) && ($this->attributes['status'] == 'cancelled:suspect'))
				$checked = 'checked="checked"';
			$tracking .= BR.'<input type="radio" name="status" value ="cancelled:suspect" '.$checked.' />&nbsp;'.$this->get_status_label('cancelled:suspect').'</p></div>';

			// step 3 - analyzed
			if(isset($this->attributes['analysis_date']))
				$this->attributes['analysis_date'] = Surfer::from_GMT($this->attributes['analysis_date']);
			$tracking .= '<div style="margin-top: 2em">'.sprintf(i18n::s('Step 3 - Analysis has ended on %s'), Skin::build_input('analysis_date', isset($this->attributes['analysis_date'])?$this->attributes['analysis_date'] : NULL_DATE, 'date_time').' <a onclick="$(\'analysis_date\').value = \''.$now.'\'" style="cursor: pointer;" class="details">'.i18n::s('now').'</a>').'<p>';
			$checked = '';
			if(isset($this->attributes['status']) && ($this->attributes['status'] == 'on-going:issue'))
				$checked = 'checked="checked"';
			$tracking .= '<input type="radio" name="status" value ="on-going:issue" '.$checked.' />&nbsp;'.$this->get_status_label('on-going:issue');
			$checked = '';
			if(isset($this->attributes['status']) && ($this->attributes['status'] == 'cancelled:problem'))
				$checked = 'checked="checked"';
			$tracking .= BR.'<input type="radio" name="status" value ="cancelled:problem" '.$checked.' />&nbsp;'.$this->get_status_label('cancelled:problem').'</p></div>';

			// step 4 - solved
			if(isset($this->attributes['resolution_date']))
				$this->attributes['resolution_date'] = Surfer::from_GMT($this->attributes['resolution_date']);
			$tracking .= '<div style="margin-top: 2em">'.sprintf(i18n::s('Step 4 - Resolution has been finalized on %s'), Skin::build_input('resolution_date', isset($this->attributes['resolution_date'])?$this->attributes['resolution_date'] : NULL_DATE, 'date_time').' <a onclick="$(\'resolution_date\').value = \''.$now.'\'" style="cursor: pointer;" class="details">'.i18n::s('now').'</a>').'<p>';
			$checked = '';
			if(isset($this->attributes['status']) && ($this->attributes['status'] == 'on-going:solution'))
				$checked = 'checked="checked"';
			$tracking .= '<input type="radio" name="status" value ="on-going:solution" '.$checked.' />&nbsp;'.$this->get_status_label('on-going:solution');
			$checked = '';
			if(isset($this->attributes['status']) && ($this->attributes['status'] == 'cancelled:issue'))
				$checked = 'checked="checked"';
			$tracking .= BR.'<input type="radio" name="status" value ="cancelled:issue" '.$checked.' />&nbsp;'.$this->get_status_label('cancelled:issue').'</p></div>';

			// step 5 - closed
			if(isset($this->attributes['close_date']))
				$this->attributes['close_date'] = Surfer::from_GMT($this->attributes['close_date']);
			$tracking .= '<div style="margin-top: 2em">'.sprintf(i18n::s('Step 5 - Issue has been closed on %s'), Skin::build_input('close_date', isset($this->attributes['close_date'])?$this->attributes['close_date'] : NULL_DATE, 'date_time').' <a onclick="$(\'close_date\').value = \''.$now.'\'" style="cursor: pointer;" class="details">'.i18n::s('now').'</a>').'<p>';
			$checked = '';
			if(isset($this->attributes['status']) && ($this->attributes['status'] == 'completed:solution'))
				$checked = 'checked="checked"';
			$tracking .= '<input type="radio" name="status" value ="completed:solution" '.$checked.' />&nbsp;'.$this->get_status_label('completed:solution');
			$checked = '';
			if(isset($this->attributes['status']) && ($this->attributes['status'] == 'cancelled:solution'))
				$checked = 'checked="checked"';
			$tracking .= BR.'<input type="radio" name="status" value ="cancelled:solution" '.$checked.' />&nbsp;'.$this->get_status_label('cancelled:solution').'</p></div>';

			// for easy detection of status change
			$tracking .= '<input type="hidden" name="previous_status" value="'.$this->attributes['status'].'" />';

			// owner
			if(isset($host['owner_id']) && ($user =& Users::get($host['owner_id'])))
				$value = $user['nick_name'];
			else
				$value = '';
			$tracking .= '<div class="bottom">'.i18n::s('Owner')
				.' <input type="text" name="owner" id="owner" value ="'.encode_field($value).'" size="25" maxlength="32" />'
				.'<div id="owner_choice" class="autocomplete"></div>'
				.BR.'<span class="small">'.i18n::s('Type some letters of the name and select in the list').'</span></div>';

			// append the script used for autocompletion
			$tracking .= JS_PREFIX
				.'// enable autocompletion for user names'."\n"
				.'Event.observe(window, "load", function() { new Ajax.Autocompleter("owner", "owner_choice", "'.$context['url_to_root'].'users/complete.php", { paramName: "q", minChars: 1, frequency: 0.4 }); });'."\n"
				.JS_SUFFIX;

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

		default:
		case 'incident':
			return i18n::s('Incident');

		case 'maintenance':
			return i18n::s('Maintenance');

		case 'improvement':
			return i18n::s('Improvement');

		case 'development':
			return i18n::s('Development');

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
		$options['maintenance']	= self::get_type_label('maintenance');
		$options['improvement']	= self::get_type_label('improvement');
		$options['development']	= self::get_type_label('development');

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
			return self::get_type_label('Incident');

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

		// type
		$rows[] = array(i18n::s('Type'), self::get_type_value());

		// the status and history
		$history = '';
		if(isset($host['self_reference']))
			$history = self::get_history($host['self_reference']);
		$rows[] = array(i18n::s('Status'), self::get_status_label($this->attributes['status']).$history);

		// build a link to the owner page, if any
		if(isset($host['owner_id']) && ($user =& Users::get($host['owner_id'])))
			$rows[] = array(i18n::s('Owner'), Users::get_link($user['full_name'], NULL, $user['id']));

		// show progress
		$rows[] = array(i18n::s('Progress'), self::get_progress_value());

		$text = Skin::table(NULL, $rows, 'grid');
		return $text;
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
	 * @return the updated fields
	 */
	function parse_fields($fields) {

		$this->attributes['owner'] = isset($fields['owner']) ? $fields['owner'] : '';
		$this->attributes['previous_status'] = isset($fields['previous_status']) ? $fields['previous_status'] : 'on-going:suspect';
		$this->attributes['previous_type'] = isset($fields['previous_type']) ? $fields['previous_type'] : 'incident';
		$this->attributes['status'] = isset($fields['status']) ? $fields['status'] : 'on-going:suspect';
		$this->attributes['type'] = isset($fields['type']) ? $fields['type'] : 'incident';
		$this->attributes['create_date'] = isset($fields['create_date']) ? Surfer::to_GMT($fields['create_date']) : NULL_DATE;
		$this->attributes['qualification_date'] = isset($fields['qualification_date']) ? Surfer::to_GMT($fields['qualification_date']) : NULL_DATE;
		$this->attributes['analysis_date'] = isset($fields['analysis_date']) ? Surfer::to_GMT($fields['analysis_date']) : NULL_DATE;
		$this->attributes['resolution_date'] = isset($fields['resolution_date']) ? Surfer::to_GMT($fields['resolution_date']) : NULL_DATE;
		$this->attributes['close_date'] = isset($fields['close_date']) ? Surfer::to_GMT($fields['close_date']) : NULL_DATE;

		return $this->attributes;
	}

	/**
	 * remember an action once it's done
	 *
	 * This function saves data into the table [code]yacs_issues[/code].
	 *
	 * @see overlays/overlay.php
	 *
	 * @param string the action 'insert' or 'update' or 'delete'
	 * @param array the hosting record
	 * @return FALSE on error, TRUE otherwise
	 */
	function remember($variant, $host) {
		global $context;

		// if a comment has to be apended as well
		$comments = array();

		// save container title as well
		$title = '';
		if(isset($host['title']))
			$title = $host['title'];

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

		// build the update query
		switch($variant) {

		case 'delete':
			$query = "DELETE FROM ".SQL::table_name('issues')." WHERE anchor LIKE '".$host['self_reference']."'";
			break;

		case 'insert':

			$query = "INSERT INTO ".SQL::table_name('issues')." SET \n"
				."anchor='".SQL::escape(isset($host['self_reference']) ? $host['self_reference'] : '')."', \n"
				."anchor_url='".SQL::escape(isset($host['self_url']) ? $host['self_url'] : '')."', \n"
				."status='".SQL::escape($this->attributes['status'])."', \n"
				."title='".SQL::escape($title)."', \n"
				."type='".SQL::escape($this->attributes['type'])."', \n"
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


				$comments[] = i18n::s('Issue has been created');

			break;

		case 'update':

			// only associates and page owners can update the record
			if(($anchor = Anchors::get($host['self_reference'])) && $anchor->is_owned()) {

				// detect type modification
				if($this->attributes['type'] != $this->attributes['previous_type'])
					$comments[] = sprintf(i18n::s('Type has been changed to "%s"'), $this->attributes['type']);

				// change host owner, if any
				if($this->attributes['owner'] && ($user = Users::get($this->attributes['owner'])) && ($target = Anchors::get($host['self_reference'])) && ($user['id'] != $target->get_value('owner_id'))) {
					$fields = array();
					$fields['owner_id'] = $user['id'];
					$target->set_values($fields);

					Members::assign('user:'.$user['id'], $host['self_reference']);
					Members::assign($host['self_reference'], 'user:'.$user['id']);

					$comments[] = sprintf(i18n::s('Owner has been changed to %s'), Skin::build_link(Users::get_permalink($user), $user['full_name']));
				}

				// update the table of issues
				$query = "UPDATE ".SQL::table_name('issues')." SET \n"
					."anchor='".SQL::escape(isset($host['self_reference']) ? $host['self_reference'] : '')."', \n"
					."anchor_url='".SQL::escape(isset($host['self_url']) ? $host['self_url'] : '')."', \n"
					."status='".SQL::escape($this->attributes['status'])."', \n"
					."title='".SQL::escape($title)."', \n"
					."type='".SQL::escape($this->attributes['type'])."', \n"
					."create_date='".SQL::escape(isset($this->attributes['create_date']) ? $this->attributes['create_date'] : $this->attributes['edit_date'])."', \n"
					."qualification_date='".SQL::escape(isset($this->attributes['qualification_date']) ? $this->attributes['qualification_date'] : NULL_DATE)."', \n"
					."analysis_date='".SQL::escape(isset($this->attributes['analysis_date']) ? $this->attributes['analysis_date'] : NULL_DATE)."', \n"
					."resolution_date='".SQL::escape(isset($this->attributes['resolution_date']) ? $this->attributes['resolution_date'] : NULL_DATE)."', \n"
					."close_date='".SQL::escape(isset($this->attributes['close_date']) ? $this->attributes['close_date'] : NULL_DATE)."', \n";

				// detect status modification
				if($this->attributes['status'] != $this->attributes['previous_status']) {

					// depending of new status
					switch($this->attributes['status']) {

					// case has been recorded
					case 'on-going:suspect':
						$query .= "create_name='".SQL::escape($this->attributes['edit_name'])."', \n"
							."create_id=".SQL::escape($this->attributes['edit_id']).", \n"
							."create_address='".SQL::escape($this->attributes['edit_address'])."', \n";

						$comments[] = i18n::s('Issue has been created');
						break;

					// problem has been validated
					case 'cancelled:suspect':
					case 'on-going:problem':
						$query .= "qualification_name='".SQL::escape($this->attributes['edit_name'])."', \n"
							."qualification_id='".SQL::escape($this->attributes['edit_id'])."', \n"
							."qualification_address='".SQL::escape($this->attributes['edit_address'])."', \n";

						$comments[] = i18n::s('End of qualification');
						break;

					// cause has been identified
					case 'cancelled:problem':
					case 'on-going:issue':
						$query .= "analysis_name='".SQL::escape($this->attributes['edit_name'])."', \n"
							."analysis_id='".SQL::escape($this->attributes['edit_id'])."', \n"
							."analysis_address='".SQL::escape($this->attributes['edit_address'])."', \n";

						$comments[] = i18n::s('End of analysis');
						break;

					// solution has been achieved
					case 'cancelled:issue':
					case 'on-going:solution':
						$query .= "resolution_name='".SQL::escape($this->attributes['edit_name'])."', \n"
							."resolution_id='".SQL::escape($this->attributes['edit_id'])."', \n"
							."resolution_address='".SQL::escape($this->attributes['edit_address'])."', \n";

						$comments[] = i18n::s('End of resolution efforts');
						break;

					// ending the issue
					case 'cancelled:solution':
					case 'completed:solution':
						$query .= "close_name='".SQL::escape($this->attributes['edit_name'])."', \n"
							."close_id='".SQL::escape($this->attributes['edit_id'])."', \n"
							."close_address='".SQL::escape($this->attributes['edit_address'])."', \n";

						$comments[] = i18n::s('Issue has been finalized');
						break;
					}

				}

				// track the person who modifies the record
				$query .= "edit_name='".SQL::escape($this->attributes['edit_name'])."', \n"
					."edit_id=".SQL::escape($this->attributes['edit_id']).", \n"
					."edit_address='".SQL::escape($this->attributes['edit_address'])."', \n"
					."edit_action='update', \n"
					."edit_date='".SQL::escape($this->attributes['edit_date'] ? $this->attributes['edit_date'] : $this->attributes['edit_date'])."' \n"
					." WHERE anchor LIKE '".SQL::escape($host['self_reference'])."'";

			}

			break;
		}

		// execute the query --don't stop on error
		if(isset($query) && $query)
			SQL::query($query);

		// add a comment
		if($comments) {
			include_once $context['path_to_root'].'comments/comments.php';
			$fields = array();
			$fields['anchor'] = $host['self_reference'];
			$fields['description'] = join(BR, $comments);
			$fields['type'] = 'information';
			Comments::post($fields);
		}

		return TRUE;
	}

	/**
	 * create tables for issues
	 *
	 * @see control/setup.php
	 */
	function setup() {
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
		$fields['type']		= "ENUM('incident', 'maintenance', 'improvement', 'development') DEFAULT 'incident' NOT NULL";

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