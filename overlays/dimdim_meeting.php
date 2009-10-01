<?php
include_once 'generic_meeting.php';

/**
 * meet at DimDim
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class DimDim_Meeting extends Generic_Meeting {

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
		$label = i18n::s('Room');
		if(!isset($this->attributes['account']))
			$this->attributes['account'] = '';
		$input = '<input type="text" name="account" value ="'.encode_field($this->attributes['account']).'" />';
		$hint = sprintf(i18n::s('Enter a valid %s account'), Skin::build_link('http://www.dimdim.com/', 'DimDim', 'basic'));
		$fields[] = array($label, $input, $hint);

		// add these tabs
		return $fields;
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

		// meeting at DimDim
		$text .= Skin::build_link('http://webmeeting.dimdim.com/portal/join.action?meetingRoomName='.$this->attributes['account'].'&displayname='.Surfer::get_name(), i18n::s('Join the meeting'), 'button');

		return $text;
	}

	/**
	 * retrieve meeting specific parameters
	 *
	 * @param the fields as filled by the end user
	 */
	function parse_meeting_fields($fields) {

		// meeting place
		$this->attributes['account'] = isset($fields['account']) ? $fields['account'] : 'demoRoom';

	}

}

?>