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
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
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

		// load localized strings
		i18n::bind('behaviors');

		// limit the scope of our check
		if(($script != 'articles/view.php') && ($script != 'articles/view_as_thread.php'))
			return $menu;

		// sanity check
		if(!$anchor)
			Skin::error(i18n::s('No anchor has been found.'));

		// which agreement?
		elseif(!$this->parameters)
			Skin::error(sprintf(i18n::s('No parameter has been provided to %s'), 'behaviors/move_on_article_access'));

		// parse parameters
		else {
			$tokens = explode(' ', $this->parameters, 2);

			// load target section
			if($section = Anchors::get('section:'.$tokens[0])) {

				// make a label
				if(count($tokens) < 2)
					$tokens[1] = sprintf(i18n::s('Move to %s'), $section->get_title());

				// the target link to move the page
				$link = Articles::get_url(str_replace('article:', '', $anchor), 'move', str_replace('section:', '', $section->get_reference()));

				// make a sub-menu
				$menu = array_merge(array($link => array(NULL, $tokens[1], NULL, 'button')), $menu);
			}
		}

		return $menu;
	}

}

?>