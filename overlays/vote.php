<?php
/**
 * record people decisions
 *
 * This overlay allows surfers to attach at most one decision to one page.
 *
 * To ask for a vote, create a new page with this overlay.
 * Then define the scope of your vote through following attributes:
 * - voters - members, editors, or associates
 * - start date and hour - votes won't be accepted before
 * - end date and hour - votes won't be accepted afterwards
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Vote extends Overlay {

	/**
	 * allow or block operations
	 *
	 * @param string the kind of item to handle
	 * @param string the foreseen operation ('edit', 'new', ...)
	 * @return TRUE if the operation is accepted, FALSE otherwise
	 */
	function allows($type, $action) {
		global $context;

		// we filter only votes
		if($type != 'decision')
			return TRUE;

		// vote is open
		$open = FALSE;

		// no start date
		if(!isset($this->attributes['start_date']) || ($this->attributes['start_date'] <= NULL_DATE)) {

			// no end date either
			if(!isset($this->attributes['end_date']) || ($this->attributes['end_date'] <= NULL_DATE))
				$open = TRUE;

			// vote has not ended yet
			elseif($context['now'] < $this->attributes['end_date'])
				$open = TRUE;

		// vote has started in the past
		} elseif($context['now'] > $this->attributes['start_date']) {

			// no end date
			if(!isset($this->attributes['end_date']) || ($this->attributes['end_date'] <= NULL_DATE))
				$open = TRUE;

				// vote has not ended yet
			elseif($context['now'] < $this->attributes['end_date'])
				$open = TRUE;

		}

		// depending on the action
		switch($action) {

		// new vote
		case 'new':

			// block if this surfer has already voted
			include_once $context['path_to_root'].'decisions/decisions.php';
			if(isset($this->attributes['id']) && ($ballot = Decisions::get_ballot('article:'.$this->attributes['id']))) {
				Logger::error(i18n::s('You have already voted'));
				return FALSE;
			}

			// wait a minute
			if(!$open) {
				Logger::error(i18n::s('Vote is not open'));
				return FALSE;
			}

			break;

		// list ballots
		case 'list':

			// wait a minute
			if($open) {
				Logger::error(i18n::s('You have to wait for end of vote to list ballots'));
				return FALSE;
			}

			break;

		}


		// allowed
		return TRUE;
	}

	/**
	 * build the list of fields for one overlay
	 *
	 * @see overlays/overlay.php
	 *
	 * @param the hosting attributes
	 * @return a list of ($label, $input, $hint)
	 */
	function get_fields($host) {
		global $context;

		// accepted voters
		$label = i18n::s('Voters');
		$input = '<input type="radio" name="voters" value="members"';
		if(!isset($this->attributes['voters']) || ($this->attributes['voters'] == 'members'))
			$input .= ' checked="checked"';
		$input .= ' /> '.i18n::s('All members of the community').BR."\n";
		$input .= '<input type="radio" name="voters" value="editors"';
		if(isset($this->attributes['voters']) && ($this->attributes['voters'] == 'editors'))
			$input .= ' checked="checked"';
		$input .= ' /> '.i18n::s('Editors of this section').BR."\n";
		$input .= '<input type="radio" name="voters" value="associates"';
		if(isset($this->attributes['voters']) && ($this->attributes['voters'] == 'associates'))
			$input .= ' checked="checked"';
		$input .= ' /> '.i18n::s('Associates only').BR."\n";
		$input .= '<input type="radio" name="voters" value="custom"';
		if(isset($this->attributes['voters']) && ($this->attributes['voters'] == 'custom'))
			$input .= ' checked="checked"';
		$input .= ' /> '.i18n::s('Following people:')
			.' <input type="text" name="voter_list"  onfocus="document.main_form.voters[3].checked=\'checked\'" size="40" />'.BR."\n";
		$fields[] = array($label, $input);

		// start date
		$label = i18n::s('Start date');

		// adjust date from UTC time zone to surfer time zone
		$value = '';
		if(isset($this->attributes['start_date']) && ($this->attributes['start_date'] > NULL_DATE))
			$value = Surfer::from_GMT($this->attributes['start_date']);

		$input = '<input type="text" name="start_date" value ="'.encode_field($value).'" size="32" maxlength="64" />';
		$hint = i18n::s('YYYY-MM-AA HH:MM');
		$fields[] = array($label, $input, $hint);

		// end date
		$label = i18n::s('End date');

		// adjust date from UTC time zone to surfer time zone
		$value = '';
		if(isset($this->attributes['end_date']) && ($this->attributes['end_date'] > NULL_DATE))
			$value = Surfer::from_GMT($this->attributes['end_date']);

		$input = '<input type="text" name="end_date" value ="'.encode_field($value).'" size="32" maxlength="64" />';
		$hint = i18n::s('YYYY-MM-AA HH:MM');
		$fields[] = array($label, $input, $hint);

		return $fields;
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

		// description label
		case 'description':
			return i18n::s('Vote description');

		// command to edit an item
		case 'edit_command':
			return i18n::s('Edit this vote');

		// command to add an item
		case 'new_command':
			return i18n::s('Add a vote');

		// page title
		case 'page_title':

			switch($action) {

			case 'edit':
				return i18n::s('Edit a vote');

			case 'delete':
				return i18n::s('Delete a vote');

			case 'new':
				return i18n::s('Add a vote');

			}
		}

		// no match
		return NULL;
	}

	/**
	 * display one vote
	 *
	 * @see overlays/overlay.php
	 *
	 * @param array the hosting record
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_view_text($host=NULL) {
		global $context;

		include_once $context['path_to_root'].'decisions/decisions.php';

		// the text
		$text = '';

		// get ballot
		$vote = NULL;
		if($ballot = Decisions::get_ballot('article:'.$this->attributes['id'])) {

			// link to ballot page
			$text .= '<p>'.Skin::build_link(Decisions::get_url($ballot), i18n::s('View your ballot'), 'shortcut').'</p>';

		// link to vote
		} elseif(Surfer::is_member())
			$vote = ' '.Skin::build_link(Decisions::get_url('article:'.$this->attributes['id'], 'decision'), i18n::s('Express your vote'), 'shortcut').' ';

		// vote is open
		$open = FALSE;

		// no start date
		if(!isset($this->attributes['start_date']) || ($this->attributes['start_date'] <= NULL_DATE)) {

			// no end date either
			if(!isset($this->attributes['end_date']) || ($this->attributes['end_date'] <= NULL_DATE)) {

				$text .= '<p>'.i18n::s('Vote is currently open').$vote.'</p>';

				$open = TRUE;

			// vote has not ended yet
			} elseif($context['now'] < $this->attributes['end_date']) {

				$text .= '<p>'.sprintf(i18n::s('Vote is open until %s'), Skin::build_date($this->attributes['end_date'], 'standalone')).$vote.'</p>';

				$open = TRUE;

			// vote has ended
			} else {
				$text .= '<p>'.sprintf(i18n::s('Vote has ended on %s'), Skin::build_date($this->attributes['end_date'], 'standalone')).'</p>';

			}

		// vote has not started yet
		} elseif($context['now'] < $this->attributes['start_date']) {

			// no end date
			if(!isset($this->attributes['end_date']) || ($this->attributes['end_date'] <= NULL_DATE))
				$text .= '<p>'.sprintf(i18n::s('Vote will start on %s'), Skin::build_date($this->attributes['start_date'], 'standalone')).'</p>';

			// vote has not ended yet
			else
				$text .= '<p>'.sprintf(i18n::s('Vote will take place between %s and %s'), Skin::build_date($this->attributes['start_date'], 'standalone'), Skin::build_date($this->attributes['end_date'], 'standalone')).'</p>';


		// vote has started in the past
		} else {

			// no end date
			if(!isset($this->attributes['end_date']) || ($this->attributes['end_date'] <= NULL_DATE)) {

				$text .= '<p>'.sprintf(i18n::s('Vote has started on %s'), Skin::build_date($this->attributes['start_date'], 'standalone')).$vote.'</p>';

				$open = TRUE;

			// vote has not ended yet
			} elseif($context['now'] < $this->attributes['end_date']) {

				$text .= '<p>'.sprintf(i18n::s('Vote is taking place between %s and %s'), Skin::build_date($this->attributes['start_date'], 'standalone'), Skin::build_date($this->attributes['end_date'], 'standalone')).$vote.'</p>';

				$open = TRUE;

			// vote has ended
			} else
				$text .= '<p>'.sprintf(i18n::s('Vote has ended on %s'), Skin::build_date($this->attributes['end_date'], 'standalone')).'</p>';

		}

		// show results and ballots
		$show = FALSE;

		// vote management
		if(!$open)
			$show = TRUE;

		// decisions for this vote
		list($total, $yes, $no) = Decisions::get_results_for_anchor('article:'.$this->attributes['id']);

		// show results
		if($total) {

			// full results
			if($show) {

				$label = '';

				// total number of votes
				$label = sprintf(i18n::ns('%d vote', '%d votes', $total), $total);

				// count of yes
				if($yes)
					$label .= ', '.sprintf(i18n::ns('%d approval', '%d approvals', $yes), $yes).' ('.(int)($yes*100/$total).'%)';

				// count of no
				if($no)
					$label .= ', '.sprintf(i18n::ns('%d reject', '%d rejects', $no), $no).' ('.(int)($no*100/$total).'%)';

				// a link to ballots
				$text .= '<p>'.Skin::build_link(Decisions::get_url('article:'.$this->attributes['id'], 'list'), $label, 'basic', i18n::s('See ballot papers')).'</p>';

			// on-going vote
			} else
				$text .= sprintf(i18n::ns('%d vote so far', '%d votes so far', $total), $total);

		}

		// voters, only before vote end
		if(!$show) {

			$text .= '<p>';

			if(!isset($this->attributes['voters']) || ($this->attributes['voters'] == 'members'))
				$text .= i18n::s('All members of the community are allowed to vote');

			elseif($this->attributes['voters'] == 'editors')
				$text .= i18n::s('Editors of this section are allowed to vote');

			elseif($this->attributes['voters'] == 'associates')
				$text .= i18n::s('Only associates are allowed to vote');

			elseif($this->attributes['voters'] == 'custom') {
				$text .= i18n::s('Voters:').' ';
				if(!isset($this->attributes['voter_list']) || !trim($this->attributes['voter_list'])) {
					$text .= i18n::s('(to be defined)');
				} else
					$text .= $this->attributes['voter_list'];
			}

			$text .= '</p>';
		}

		$text = '<div class="overlay">'.Codes::beautify($text).'</div>';
		return $text;
	}

	/**
	 * retrieve the content of one modified overlay
	 *
	 * @see overlays/overlay.php
	 *
	 * @param the fields as filled by the end user
	 */
	function parse_fields($fields) {
		global $context;

		$this->attributes['voters'] = isset($fields['voters']) ? $fields['voters'] : '';
		$this->attributes['voter_list'] = isset($fields['voter_list']) ? $fields['voter_list'] : '';
		$this->attributes['start_date'] = isset($fields['start_date']) ? $fields['start_date'] : NULL_DATE;

		// adjust date from surfer time zone to UTC time zone
		if(isset($fields['start_date']) && $fields['start_date'])
			$this->attributes['start_date'] = Surfer::to_GMT($fields['start_date']);

		$this->attributes['end_date'] = isset($fields['end_date']) ? $fields['end_date'] : NULL_DATE;

		// adjust date from surfer time zone to UTC time zone
		if(isset($fields['end_date']) && $fields['end_date'])
			$this->attributes['end_date'] = Surfer::to_GMT($fields['end_date']);
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

		// build the update query
		switch($action) {

		case 'delete':
			include_once $context['path_to_root'].'decisions/decisions.php';
			Decisions::delete_for_anchor($reference);
			break;

		case 'insert':
			break;

		case 'update':
			break;
		}

		return TRUE;
	}

}

?>
