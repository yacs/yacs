<?php
/**
 * describe a question
 *
 * This overlay is aiming to create FAQ sections.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Question extends Overlay {

	/**
	 * get an overlaid label
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

		case 'description:edit':
		case 'description:new':
			return i18n::s('Context');

		case 'edit_command:articles':
			return i18n::s('Edit the question');

		case 'new_command:articles':
			return i18n::s('Add a question');

		case 'list_title:comments':
			return i18n::s('Contributions');

		case 'page_title:edit':
			return i18n::s('Edit the question');

		case 'page_title:delete':
			return i18n::s('Delete a question');

		case 'page_title:new':
			return i18n::s('Add a question');

		case 'title:edit':
		case 'title:new':
			return i18n::s('Question');

		}

		// no match
		return NULL;
	}

	/**
	 * display content below main panel
	 *
	 * Everything is in a separate panel
	 *
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function get_trailer_text($host=NULL) {
		$text = '';

		// display the following only if at least one comment has been attached to this page
		if(is_object($this->anchor) && !Comments::count_for_anchor($this->anchor->get_reference()))
			return $text;

		// ask the surfer if he has not answered yet, and if the page has not been locked
		$ask = TRUE;
		if(isset($_COOKIE['rating_'.$host['id']]))
			$ask = FALSE;
		elseif(isset($host['locked']) && ($host['locked'] == 'Y'))
			$ask = FALSE;

		// ask the surfer
		if($ask) {
			$text = '<p style="line-height: 2.5em;">'.i18n::s('Has this page been useful to you?')
				.' '.Skin::build_link(Articles::get_url($host['id'], 'like'), i18n::s('Yes'), 'button')
				.' '.Skin::build_link(Articles::get_url($host['id'], 'dislike'), i18n::s('No'), 'button')
				.'</p>';

		// or report on results
		} elseif($host['rating_count']) {
			$text = '<p>'.Skin::build_rating_img((int)round($host['rating_sum'] / $host['rating_count']))
				.' '.sprintf(i18n::ns('%d rating', '%d ratings', $host['rating_count']), $host['rating_count'])
				.'</p>';
		}

		// add a title
		if($text)
			$text = Skin::build_box(i18n::s('Feed-back'), $text);

		// done
		return $text;
	}

	/**
	 * display content below main panel
	 *
	 * Everything is in a separate panel
	 *
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function get_view_text($host=NULL) {
		$text = '';
		return $text;
	}

}

?>
