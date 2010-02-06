<?php
/**
 * enhance threads of discussion
 *
 * @see overlays/overlay.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Thread extends Overlay {

	/**
	 * list participants
	 *
	 * @see overlays/overlay.php
	 *
	 * @param array the hosting record
	 * @param mixed additional options
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_list_text($host=NULL, $options=NULL) {
		global $context;

		// we return some text
		$text = '';

		$to_avoid = NULL;
		if($id = Surfer::get_id())
			$to_avoid = 'user:'.$id;

		// page editors, except target surfer
		if($friends =& Members::list_users_by_posts_for_member('article:'.$host['id'], 0, USERS_LIST_SIZE, 'comma', $to_avoid))
			$text = '<p class="details">'.sprintf(i18n::s('with %s'), Skin::build_list($friends, 'comma')).'</p>';

		return $text;

	}

	/**
	 * we are almost invisible in the main panel
	 *
	 * @see overlays/overlay.php
	 *
	 * @param array the hosting record
	 * @return some HTML to be inserted into the resulting page
	 */
	function &get_view_text($host=NULL) {
		global $context;

		$text = '';
		return $text;

	}

}

?>