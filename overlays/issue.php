<?php
/**
 * describe one issue
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
			$text .= '<li>'.i18n::s('Creation').' '.Skin::build_date($row['create_date'])."</li>\n";

		// the qualification step
		if($row['qualification_date'] && ($row['qualification_date'] > NULL_DATE))
			$text .= '<li>'.i18n::s('Qualification').' '.Skin::build_date($row['qualification_date'])."</li>\n";

		// the reproduction step
		if($row['analysis_date'] && ($row['analysis_date'] > NULL_DATE))
			$text .= '<li>'.i18n::s('Analysis').' '.Skin::build_date($row['analysis_date'])."</li>\n";

		// the solution step
		if($row['resolution_date'] && ($row['resolution_date'] > NULL_DATE))
			$text .= '<li>'.i18n::s('Resolution').' '.Skin::build_date($row['resolution_date'])."</li>\n";

		// the close step
		if($row['close_date'] && ($row['close_date'] > NULL_DATE))
			$text .= '<li>'.i18n::s('Closing').' '.Skin::build_date($row['close_date'])."</li>\n";

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

		$text = Codes::beautify_title($host['title']);

		// live title
		switch($this->attributes['status']) {

		case 'on-going:suspect':
			break;

		case 'cancelled:suspect':
			$text .= ' ['.i18n::s('Solved').']';
			break;

		case 'on-going:problem':
			$text .= ' ['.i18n::s('Reproduced').']';
			break;

		case 'cancelled:problem':
			$text .= ' ['.i18n::s('Cancelled').']';
			break;

		case 'on-going:issue':
			$text .= ' ['.i18n::s('Analyzed').']';
			break;

		case 'cancelled:issue':
			$text .= ' ['.i18n::s('No solution').']';
			break;

		case 'on-going:solution':
			$text .= ' ['.i18n::s('Solved').']';
			break;

		case 'cancelled:solution':
			$text .= ' ['.i18n::s('Non-integrated').']';
			break;

		case 'completed:solution':
			$text .= ' ['.i18n::s('Solved').']';
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

		$options['on-going:suspect']	= issue::get_status_label('on-going:suspect');
		$options['cancelled:suspect']	= issue::get_status_label('cancelled:suspect');
		$options['on-going:problem']	= issue::get_status_label('on-going:problem');
		$options['cancelled:problem']	= issue::get_status_label('cancelled:problem');
		$options['on-going:issue']		= issue::get_status_label('on-going:issue');
		$options['cancelled:issue'] 	= issue::get_status_label('cancelled:issue');
		$options['on-going:solution']	= issue::get_status_label('on-going:solution');
		$options['cancelled:solution']	= issue::get_status_label('cancelled:solution');
		$options['completed:solution']	= issue::get_status_label('completed:issue');

		foreach($options as $value => $label) {
			$content .= '<option value="'.$value.'"';
			if($status == $value)
				$content .= ' selected';
			$content .='>'.$label."</option>\n";
		}
		return $content;
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
			return i18n::s('Cause has been identified');

		case 'cancelled:issue':
			return i18n::s('No solution has been developed');

		case 'on-going:solution':
			return i18n::s('A solution has been made available');

		case 'cancelled:solution':
			return i18n::s('Solution has not been integrated');

		case 'completed:solution':
			return i18n::s('Solution has been fully integrated');
		}
	}

	/**
	 * add some tabs
	 *
	 * Display additional information in panels.
	 *
	 * Accepted action codes:
	 * - 'view' - embedded into the main viewing page
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

		// display items
		if($variant == 'view') {

			// build a link to the solution manager page, if any
			if(!isset($this->attributes['manager']) || !$this->attributes['manager'])
				;
			elseif($user =& Users::get($this->attributes['manager']))
				$tracking .= '<p>'.i18n::s('Solution Manager:').' '.Users::get_link($this->attributes['manager'], NULL, $user['id']).'</p>';
			else
				$tracking .= '<p>'.i18n::s('Solution Manager:').' '.ucfirst($this->attributes['manager']).'</p>';
	
			// the status and history
			$history = '';
			if(isset($host['self_reference']))
				$history = Issue::get_history($host['self_reference']);
			$tracking .= Issue::get_status_label($this->attributes['status']).$history;
	
		// only associates and editors can change the status
		} elseif(($variant == 'edit') && Surfer::is_empowered()) {

			// no solution manager on initial form
			if(isset($host['id'])) {
	
				// solution manager
				if(!isset($this->attributes['manager']) || !$this->attributes['manager'])
					$this->attributes['manager'] = '';
				$tracking .= '<div style="margin-bottom: 1em;">'.i18n::s('Solution Manager')
					.' <input type="text" name="manager" id="manager" value ="'.encode_field($this->attributes['manager']).'" size="25" maxlength="32" />'
					.'<div id="manager_choice" class="autocomplete"></div>'
					.BR.'<span class="small">'.i18n::user('Type some letters of the name and select in the list').'</span></div>';
		
				// append the script used for autocompletion
				$tracking .= '<script type="text/javascript">// <![CDATA['."\n"
					.'// enable autocompletion for user names'."\n"
					.'Event.observe(window, "load", function() { new Ajax.Autocompleter("manager", "manager_choice", "'.$context['url_to_root'].'users/complete.php", { paramName: "q", minChars: 1, frequency: 0.4 }); });'."\n"
					.'// ]]></script>';
	
			}
	
			// step 1 - created
			if(!isset($host['create_date']) || !$host['create_date'])
				$host['create_date'] = gmstrftime('%Y-%m-%d %H:%M:%S');
			$host['create_date'] = Surfer::from_GMT($host['create_date']);
			$tracking .= '<div class="bottom">'.sprintf(i18n::s('Step 1 - Record has been created on %s'), Skin::build_input('create_date', $host['create_date'], 'date_time').' <a onclick="$(\'create_date\').value = \''.$now.'\'" style="cursor: pointer;" class="details">'.i18n::s('now').'</a>').'</div>';

			// step 2 - qualified
			if(isset($this->attributes['qualification_date']))
				$this->attributes['qualification_date'] = Surfer::from_GMT($this->attributes['qualification_date']);
			$tracking .= '<div class="bottom">'.sprintf(i18n::s('Step 2 - Qualification has taken place on %s'), Skin::build_input('qualification_date', isset($this->attributes['qualification_date'])?$this->attributes['qualification_date']:'', 'date_time').' <a onclick="$(\'qualification_date\').value = \''.$now.'\'" style="cursor: pointer;" class="details">'.i18n::s('now').'</a>').'<p>';
			$checked = '';
			if(isset($this->attributes['status']) && ($this->attributes['status'] == 'on-going:problem'))
				$checked = 'checked="checked"';
			$tracking .= '<input type="radio" name="status" value ="on-going:problem" '.$checked.' />&nbsp;'.i18n::s('this has been recognized as a valid problem');
			$checked = '';
			if(isset($this->attributes['status']) && ($this->attributes['status'] == 'cancelled:suspect'))
				$checked = 'checked="checked"';
			$tracking .= BR.'<input type="radio" name="status" value ="cancelled:suspect" '.$checked.' />&nbsp;'.i18n::s('an immediate solution has been found').'</p></div>';

			// step 3 - analyzed
			if(isset($this->attributes['analysis_date']))
				$this->attributes['analysis_date'] = Surfer::from_GMT($this->attributes['analysis_date']);
			$tracking .= '<div class="bottom">'.sprintf(i18n::s('Step 3 - Analysis has ended on %s'), Skin::build_input('analysis_date', isset($this->attributes['analysis_date'])?$this->attributes['analysis_date']:'', 'date_time').' <a onclick="$(\'analysis_date\').value = \''.$now.'\'" style="cursor: pointer;" class="details">'.i18n::s('now').'</a>').'<p>';
			$checked = '';
			if(isset($this->attributes['status']) && ($this->attributes['status'] == 'on-going:issue'))
				$checked = 'checked="checked"';
			$tracking .= '<input type="radio" name="status" value ="on-going:issue" '.$checked.' />&nbsp;'.i18n::s('the issue has been documented and root causes have been identified');
			$checked = '';
			if(isset($this->attributes['status']) && ($this->attributes['status'] == 'cancelled:problem'))
				$checked = 'checked="checked"';
			$tracking .= BR.'<input type="radio" name="status" value ="cancelled:problem" '.$checked.' />&nbsp;'.i18n::s('the problem has not been reproduced').'</p></div>';

			// step 4 - solved
			if(isset($this->attributes['resolution_date']))
				$this->attributes['resolution_date'] = Surfer::from_GMT($this->attributes['resolution_date']);
			$tracking .= '<div class="bottom">'.sprintf(i18n::s('Step 4 - Resolution has been finalized on %s'), Skin::build_input('resolution_date', isset($this->attributes['resolution_date'])?$this->attributes['resolution_date']:'', 'date_time').' <a onclick="$(\'resolution_date\').value = \''.$now.'\'" style="cursor: pointer;" class="details">'.i18n::s('now').'</a>').'<p>';
			$checked = '';
			if(isset($this->attributes['status']) && ($this->attributes['status'] == 'on-going:solution'))
				$checked = 'checked="checked"';
			$tracking .= '<input type="radio" name="status" value ="on-going:solution" '.$checked.' />&nbsp;'.i18n::s('a solution has been made available');
			$checked = '';
			if(isset($this->attributes['status']) && ($this->attributes['status'] == 'cancelled:issue'))
				$checked = 'checked="checked"';
			$tracking .= BR.'<input type="radio" name="status" value ="cancelled:issue" '.$checked.' />&nbsp;'.i18n::s('solution development has been cancelled').'</p></div>';

			// step 5 - closed
			if(isset($this->attributes['close_date']))
				$this->attributes['close_date'] = Surfer::from_GMT($this->attributes['close_date']);
			$tracking .= '<div class="bottom">'.sprintf(i18n::s('Step 5 - Issue has been closed on %s'), Skin::build_input('close_date', isset($this->attributes['close_date'])?$this->attributes['close_date']:'', 'date_time').' <a onclick="$(\'close_date\').value = \''.$now.'\'" style="cursor: pointer;" class="details">'.i18n::s('now').'</a>').'<p>';
			$checked = '';
			if(isset($this->attributes['status']) && ($this->attributes['status'] == 'completed:solution'))
				$checked = 'checked="checked"';
			$tracking .= '<input type="radio" name="status" value ="completed:solution" '.$checked.' />&nbsp;'.i18n::s('solution has been fully integrated');
			$checked = '';
			if(isset($this->attributes['status']) && ($this->attributes['status'] == 'cancelled:solution'))
				$checked = 'checked="checked"';
			$tracking .= BR.'<input type="radio" name="status" value ="cancelled:solution" '.$checked.' />&nbsp;'.i18n::s('solution integration has been cancelled').'</p></div>';

		}

		// finalize this tab
		if($tracking)
			$tabs[] = array('tracking_tab', i18n::user('Tracking'), 'tracking_panel', $tracking);

		// add these tabs
		return $tabs;
	}

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

		$this->attributes['manager'] = isset($fields['manager']) ? $fields['manager'] : '';
		$this->attributes['status'] = isset($fields['status']) ? $fields['status'] : 'on-going:suspect';
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
	 * @param string the action 'insert', 'update' or 'delete'
	 * @param array the hosting record
	 * @return FALSE on error, TRUE otherwise
	 */
	function remember($variant, $host) {
		global $context;

		// save container title as well
		$title = '';
		if(isset($host['title']))
			$title = $host['title'];

		// set default values for this editor
		$this->attributes = Surfer::check_default_editor($this->attributes);

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

			// associates and editors can do what they want
			if(Surfer::is_empowered()) {

				$query = "INSERT INTO ".SQL::table_name('issues')." SET \n"
//					."id=".SQL::escape($host['id']).", \n"
					."anchor='".SQL::escape(isset($host['self_reference']) ? $host['self_reference'] : '')."', \n"
					."anchor_url='".SQL::escape(isset($host['self_url']) ? $host['self_url'] : '')."', \n"
					."status='".SQL::escape($this->attributes['status'])."', \n"
					."title='".SQL::escape($title)."', \n"
					."manager='".SQL::escape($this->attributes['manager'])."', \n"
					."create_name='".SQL::escape(isset($this->attributes['create_name']) ? $this->attributes['create_name'] : $this->attributes['edit_name'])."', \n"
					."create_id='".SQL::escape(isset($this->attributes['create_id']) ? $this->attributes['create_id'] : $this->attributes['edit_id'])."', \n"
					."create_address='".SQL::escape(isset($this->attributes['create_address']) ? $this->attributes['create_address'] : $this->attributes['edit_address'])."', \n"
					."create_date='".SQL::escape(isset($this->attributes['create_date']) ? $this->attributes['create_date'] : $this->attributes['edit_date'])."', \n"
					."edit_name='".SQL::escape($this->attributes['edit_name'])."', \n"
					."edit_id='".SQL::escape($this->attributes['edit_id'])."', \n"
					."edit_address='".SQL::escape($this->attributes['edit_address'])."', \n"
					."edit_action='create', \n"
					."edit_date='".SQL::escape($this->attributes['edit_date'])."', \n"
					."qualification_date='".SQL::escape(isset($this->attributes['qualification_date']) ? $this->attributes['qualification_date'] : NULL_DATE)."', \n"
					."analysis_date='".SQL::escape(isset($this->attributes['analysis_date']) ? $this->attributes['analysis_date'] : NULL_DATE)."', \n"
					."resolution_date='".SQL::escape(isset($this->attributes['resolution_date']) ? $this->attributes['resolution_date'] : NULL_DATE)."', \n"
					."close_date='".SQL::escape(isset($this->attributes['close_date']) ? $this->attributes['close_date'] : NULL_DATE)."'";

			// set minimal values for other surfers
			} else {

				$query = "INSERT INTO ".SQL::table_name('issues')." SET \n"
//					."id=".SQL::escape($host['id']).", \n"
					."anchor='".SQL::escape(isset($host['self_reference']) ? $host['self_reference'] : '')."', \n"
					."anchor_url='".SQL::escape(isset($host['self_url']) ? $host['self_url'] : '')."', \n"
					."status='on-going:suspect', \n"
					."title='".SQL::escape($title)."', \n"
					."create_name='".SQL::escape(isset($this->attributes['create_name']) ? $this->attributes['create_name'] : $this->attributes['edit_name'])."', \n"
					."create_id='".SQL::escape(isset($this->attributes['create_id']) ? $this->attributes['create_id'] : $this->attributes['edit_id'])."', \n"
					."create_address='".SQL::escape(isset($this->attributes['create_address']) ? $this->attributes['create_address'] : $this->attributes['edit_address'])."', \n"
					."create_date='".SQL::escape(isset($this->attributes['create_date']) ? $this->attributes['create_date'] : $this->attributes['edit_date'])."', \n"
					."edit_name='".SQL::escape($this->attributes['edit_name'])."', \n"
					."edit_id='".SQL::escape($this->attributes['edit_id'])."', \n"
					."edit_address='".SQL::escape($this->attributes['edit_address'])."', \n"
					."edit_action='create', \n"
					."edit_date='".SQL::escape($this->attributes['edit_date'])."', \n"
					."qualification_date='".SQL::escape(NULL_DATE)."', \n"
					."analysis_date='".SQL::escape(NULL_DATE)."', \n"
					."resolution_date='".SQL::escape(NULL_DATE)."', \n"
					."close_date='".SQL::escape(NULL_DATE)."'";

			}
			break;

		case 'update':

			// only associates and editors can update the record
			if(Surfer::is_empowered()) {

				$query = "UPDATE ".SQL::table_name('issues')." SET \n"
					."anchor='".SQL::escape(isset($host['self_reference']) ? $host['self_reference'] : '')."', \n"
					."anchor_url='".SQL::escape(isset($host['self_url']) ? $host['self_url'] : '')."', \n"
					."status='".SQL::escape($this->attributes['status'])."', \n"
					."title='".SQL::escape($title)."', \n"
					."manager='".SQL::escape($this->attributes['manager'])."', \n"
					."create_date='".SQL::escape(isset($this->attributes['create_date']) ? $this->attributes['create_date'] : $this->attributes['edit_date'])."', \n"
					."qualification_date='".SQL::escape(isset($this->attributes['qualification_date']) ? $this->attributes['qualification_date'] : NULL_DATE)."', \n"
					."analysis_date='".SQL::escape(isset($this->attributes['analysis_date']) ? $this->attributes['analysis_date'] : NULL_DATE)."', \n"
					."resolution_date='".SQL::escape(isset($this->attributes['resolution_date']) ? $this->attributes['resolution_date'] : NULL_DATE)."', \n"
					."close_date='".SQL::escape(isset($this->attributes['close_date']) ? $this->attributes['close_date'] : NULL_DATE)."', \n";

				switch($this->attributes['status']) {

				// case has been recorded
				case 'on-going:suspect':
					$query .= "create_name='".SQL::escape($this->attributes['edit_name'])."', \n"
						."create_id='".SQL::escape($this->attributes['edit_id'])."', \n"
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

				// track the person who modifies the record
				$query .= "edit_name='".SQL::escape($this->attributes['edit_name'])."', \n"
					."edit_id='".SQL::escape($this->attributes['edit_id'])."', \n"
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
		$fields['manager']		= "VARCHAR(255) DEFAULT '' NOT NULL";						// up to 255 chars
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

		$indexes = array();
		$indexes['PRIMARY KEY'] 	= "(id)";
		$indexes['INDEX analysis_date'] = "(analysis_date)";
		$indexes['INDEX anchor']		= "(anchor)";
		$indexes['INDEX close_date']	= "(close_date)";
		$indexes['INDEX create_date']	= "(create_date)";
		$indexes['INDEX create_id'] = "(create_id)";
		$indexes['INDEX edit_id']	= "(edit_id)";
		$indexes['INDEX edit_date'] = "(edit_date)";
		$indexes['INDEX manager']	= "(manager)";
		$indexes['INDEX qualification_date']	= "(qualification_date)";
		$indexes['INDEX resolution_date']	= "(resolution_date)";
		$indexes['INDEX status']	= "(status)";

		return SQL::setup_table('issues', $fields, $indexes);
	}

}

?>