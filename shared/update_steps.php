<?php
/**
 * chain the steps of a software update
 *
 * After scripts/update.php, the server stays switched off while the webmaster
 * goes through hooks, database, one-time scripts, style sheets and javascript
 * libraries. Each step ends with a button to the next one, so that none is forgotten:
 * scripts/update.php, control/scan.php, control/setup.php, scripts/run_once.php,
 * then the steps listed here, and finally control/switch.php.
 *
 * The file parameters/switch.off tells that an update is going on.
 *
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */

Class Update_steps {

	/**
	 * is an update going on?
	 *
	 * @return boolean TRUE if the server has been switched off
	 */
	public static function is_running() {
		global $context;

		return file_exists($context['path_to_root'].'parameters/switch.off');
	}

	/**
	 * the script of the step following the current one
	 *
	 * @param string the current step: 'run_once', 'build_css', 'jsmin' or 'staging'
	 * @return string the path of the next script, from the root of the server
	 */
	public static function script_after($current) {
		global $context;

		switch($current) {
		case 'run_once':
			return 'tools/build_css.php';
		case 'build_css':
			return 'tools/jsmin.php';
		case 'jsmin':
			if(file_exists($context['path_to_root'].'scripts/staging/footprints.php'))
				return 'control/purge.php';
		}
		return 'control/switch.php';
	}

	/**
	 * build the button to the step following the current one
	 *
	 * @param string the current step: 'run_once', 'build_css', 'jsmin' or 'staging'
	 * @return string the HTML to append to the page, or an empty string out of an update
	 */
	public static function next($current) {

		if(!Update_steps::is_running())
			return '';

		switch($script = Update_steps::script_after($current)) {

		// style sheets of the skin, layouts and overlays
		case 'tools/build_css.php':
			return Update_steps::build_form($script, 'confirm', i18n::s('Build style sheets'));

		// javascript libraries used in production mode
		case 'tools/jsmin.php':
			return Update_steps::build_form($script, 'confirm', i18n::s('Compress javascript libraries'));

		// files left in scripts/staging
		case 'control/purge.php':
			return Update_steps::build_form($script, 'staging', i18n::s('Delete staged files'));

		// the last step asks for confirmation
		default:
			return Skin::build_list(array($script => i18n::s('Switch the server on')), 'menu_bar');
		}
	}

	/**
	 * a form that posts the action of the next step
	 *
	 * @param string the target script, from the root of the server
	 * @param string the action to post
	 * @param string the label of the button
	 * @return string the HTML of the form
	 */
	private static function build_form($script, $action, $label) {
		global $context;

		return Skin::build_block('<form method="post" action="'.$context['url_to_root'].$script.'"><p class="assistant_bar">'
			.Skin::build_submit_button($label)
			.'<input type="hidden" name="action" value="'.$action.'" />'
			.'</p></form>', 'bottom');
	}

}
