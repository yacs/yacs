<?php
/**
 * test overlay
 *
 * This sample script is aiming to help test the overlay interface.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Overlay_test extends Overlay {

	/**
	 * text to be inserted aside
	 *
	 * To be overloaded into derived class
	 *
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function get_extra_text($host=NULL) {
		$text = Codes::beautify_extra('[box.extra='.i18n::s('Extra box').' (test)]'
			.'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'
			.'[/box]'
			.'[box.navigation='.i18n::s('Navigation box').' (test)]'
			.'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.'
			.'[/box]');
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
	function get_list_text($host=NULL) {
		$text = '<p>list (test) Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>';
		return $text;
	}

	/**
	 * display a live introduction
	 *
	 * To be overloaded into derived class
	 *
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function get_live_introduction($host=NULL) {
		$text = $host['introduction'].' (test) Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.';
		return $text;
	}

	/**
	 * display a live title
	 *
	 * To be overloaded into derived class
	 *
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function get_live_title($host=NULL) {
		$text = $host['title'].' (test)';
		return $text;
	}

	/**
	 * add some tabbed panels
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
	function get_tabs($variant='view', $host=NULL) {
		$content = '<p>(test)  Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>';
		$output = array(array('test_id', 'Test', 'test_panel', $content));
		return $output;
	}

	/**
	 * text to come after page description
	 *
	 * To be overloaded into derived class
	 *
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function get_trailer_text($host=NULL) {
		$text = '<p>trailer (test) Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>';
		return $text;
	}

	/**
	 * display the content of one overlay in main view panel
	 *
	 * To be overloaded into derived class
	 *
	 * @param array the hosting record, if any
	 * @return some HTML to be inserted into the resulting page
	 */
	function get_view_text($host=NULL) {
		$text = '<p>view (test) Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>';
		return $text;
	}

}

?>