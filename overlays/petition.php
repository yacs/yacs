<?php
/**
 * achieve large support
 *
 * This overlay allows surfers to attach at most one decision to one page.
 *
 * To create a petition, create a new page with this overlay.
 * Then define the scope of your petition through following attributes:
 * - voters - members, editors, or associates
 * - end date and hour - signatures won't be accepted afterwards
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Petition extends Overlay {

	/**
	 * allow or block operations
	 *
	 * @param string the kind of item to handle
	 * @param string the foreseen operation ('edit', 'new', ...)
	 * @return TRUE if the operation is accepted, FALSE otherwise
	 */
	function allows($type, $action) {
		global $context;

		// we filter only approvals
		if($type != 'approval')
			return TRUE;

		// we filter only new votes
		if($action != 'new')
			return TRUE;

		// block if this surfer has already voted
		if(isset($this->attributes['id']) && Surfer::get_id() && Comments::count_approvals_for_anchor($this->anchor->get_reference(), Surfer::get_id())) {
			Logger::error(i18n::s('You have already signed'));
			return FALSE;
		}

		// vote is open
		$open = FALSE;

		// no end date
		if(!isset($this->attributes['end_date']) || ($this->attributes['end_date'] <= NULL_DATE))
			$open = TRUE;

		// vote has not ended yet
		elseif($context['now'] < $this->attributes['end_date'])
			$open = TRUE;

		// wait a minute
		if(!$open) {
			Logger::error(i18n::s('Petition has been closed.'));
			return FALSE;
		}

		// allowed
		return TRUE;
	}

	/**
	 * text to come in page details
	 *
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_details_text($host=NULL) {
		global $context;

		// feed-back to surfer
		$information = array();

		// no end date
		if(!isset($this->attributes['end_date']) || ($this->attributes['end_date'] <= NULL_DATE)) {

			$information[] = i18n::s('Petition is currently open.');
			$open = TRUE;

		// not ended yet
		} elseif($this->attributes['end_date'] > gmstrftime('%Y-%m-%d %H:%M:%S')) {

			$information[] = sprintf(i18n::s('Petition is open until %s.'), Skin::build_date($this->attributes['end_date'], 'standalone'));
			$open = TRUE;

		// petition is over
		} else {

			$information[] = sprintf(i18n::s('Petition has ended on %s.'), Skin::build_date($this->attributes['end_date'], 'standalone'));
			$open = FALSE;

		}

		// voters, only before vote end
		if($open) {

			if(!isset($this->attributes['voters']) || ($this->attributes['voters'] == 'members'))
				$information[] = i18n::s('All members of the community are allowed to sign.');

			elseif($this->attributes['voters'] == 'editors')
				$information[] = i18n::s('Editors of this section are allowed to sign.');

			elseif($this->attributes['voters'] == 'associates')
				$information[] = i18n::s('Only associates are allowed to sign.');

			elseif($this->attributes['voters'] == 'custom')
				$information[] = sprintf(i18n::s('Allowed: %s'), (isset($this->attributes['voter_list']) && trim($this->attributes['voter_list'])) ? $this->attributes['voter_list'] : i18n::s('(to be defined)') );

		}

		// introduce the petition
		$text = join(' ', $information);
		return $text;
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
		$label = i18n::s('Scope');
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

		case 'edit_command':
			return i18n::s('Edit this petition');

		// command to add an item
		case 'new_command':
			return i18n::s('Add a petition');

		// page title
		case 'page_title':

			switch($action) {

			case 'edit':
				return i18n::s('Edit a petition');

			case 'delete':
				return i18n::s('Delete a petition');

			case 'new':
				return i18n::s('Add a petition');

			}
		}

		// no match
		return NULL;
	}

	/**
	 * text to come after page description
	 *
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_trailer_text($host=NULL) {
		global $context;

		// the text
		$text = '';

		// actually, a menu of commands
		$menu = array();

		// no end date
		if(!isset($this->attributes['end_date']) || ($this->attributes['end_date'] <= NULL_DATE))
			$open = TRUE;

		// not ended yet
		elseif($this->attributes['end_date'] > gmstrftime('%Y-%m-%d %H:%M:%S'))
			$open = TRUE;

		// petition is over
		else
			$open = FALSE;

		// different for each surfer
		Cache::poison();

		// link to vote
		if($open && Surfer::get_id() && Surfer::get_id() && !Comments::count_approvals_for_anchor($this->anchor->get_reference(), Surfer::get_id()))
			$menu[] = Skin::build_link(Comments::get_url($this->anchor->get_reference(), 'approve'), i18n::s('Sign this petition'), 'shortcut');

		$text = Skin::finalize_list($menu, 'menu_bar');
		return $text;
	}

	/**
	 * display the content of one petition
	 *
	 * @see overlays/overlay.php
	 *
	 * @param array the hosting record
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_view_text($host=NULL) {
		$text = '';
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
			break;

		case 'insert':
			break;

		case 'update':
			break;
		}

		// job done
		return TRUE;

	}

}

?>