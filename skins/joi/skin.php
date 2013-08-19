<?php
/**
 * Static functions used to produce HTML code for page components (lists, blocks, etc.)
 * Declare here all things used to build some HTML, but only HTML-related things.
 *
 * This class is an extension of the generic Skin_skeleton implementation ([script]skins/skin_skeleton.php[/script]).
 *
 * Combined with the template ([script]skins/joi/template.php[/script]), and with related CSS and image files, it
 * contributes to the specific atmosphere of this skin.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Skin extends Skin_Skeleton {

	/**
	 * define constants used with this skin
	 */
	public static function initialize() {
		global $context;

		// ensure extra boxes are displayed in this 2-columns layout
		if(strpos($context['skins_navigation_components'], 'extra') === FALSE)
			$context['skins_navigation_components'] = str_replace('navigation', 'extra navigation', $context['skins_navigation_components']);
	}
}

?>