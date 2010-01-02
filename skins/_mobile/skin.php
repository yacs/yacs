<?php
/**
 * Static functions used to produce HTML code for page components (lists, blocks, etc.)
 * Declare here all things used to build some HTML, but only HTML-related things.
 *
 * This class is an extension of the generic Skin_skeleton implementation ([script]skins/skin_skeleton.php[/script]).
 *
 * Combined with the template ([script]skins/digital/template.php[/script]), and with related CSS and image files, it
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
	function initialize() {
		global $context;

		// we are XHTML
		define('BR', '<br />');
		define('EOT', ' />');

		// the bullet prefix for compact lists
		define('COMPACT_LIST_ITEM_PREFIX', '&raquo;&nbsp;');

		// the HTML string used to prefix submenu items [submenu]
		define('MENU_2_PREFIX', '&raquo;&nbsp;');

		// the HTML string appended to submenu items [submenu]
		define('MENU_2_SUFFIX', BR);

	}
}

?>