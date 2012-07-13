<?php
/**
 * move an article to another section
 *
 * This behavior adds a command to the page menu to allow for page move to
 * another section.
 *
 * Required parameters include:
 * - section - id or nick name of the target section
 * - label - all following tokens are featured into the link
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
class Move_on_article_access extends Behavior {

	/**
	 * extend the page menu
	 *
	 * @param string script name
	 * @param string target anchor, if any
	 * @param array current menu
	 * @return array updated menu
	 */
	function &add_commands($script, $anchor, $menu=array()) {
		global $context;

		// limit the scope of our check to viewed pages
		if(!preg_match('/articles\/view/', $script))
			return $menu;

		// surfer has to be authenticated
		if(!Surfer::is_logged())
			return $menu;

		// sanity checks
		if(!$anchor)
			Logger::error(i18n::s('No anchor has been found.'));
		elseif(!$target =&  Anchors::get($anchor))
			Logger::error(i18n::s('No anchor has been found.'));

		// which agreement?
		elseif(!$this->parameters)
			Logger::error(sprintf(i18n::s('No parameter has been provided to %s'), 'behaviors/move_on_article_access'));

		// parameters have been validated
		else {

			// look at parent container if possible
			if(!$origin =&  Anchors::get($target->get_parent()))
				$origin = $target;

			// only container editors can proceed
			if($origin->is_assigned() || Surfer::is_associate()) {

				// load target section
				$tokens = explode(' ', $this->parameters, 2);
				if($section = Anchors::get('section:'.$tokens[0])) {

					// make a label
					if(count($tokens) < 2)
						$tokens[1] = sprintf(i18n::s('Move to %s'), $section->get_title());

					// the target link to move the page
					$link = Articles::get_url(str_replace('article:', '', $anchor), 'move', str_replace('section:', '', $section->get_reference()));

					// make a sub-menu
					$menu = array_merge(array($link => array('', $tokens[1], '', 'button')), $menu);
				}
			}
		}

		return $menu;
	}

}

?>