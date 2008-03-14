<?php
/**
 * Static functions used to produce HTML code for page components (lists, blocks, etc.)
 * Declare here all things used to build some HTML, but only HTML-related things.
 *
 * This class is an extension of the generic Skin_skeleton implementation ([script]skins/skin_skeleton.php[/script]).
 *
 * Combined with the template ([script]skins/boxesandarrows/template.php[/script]), and with related CSS and image files, it
 * contributes to the specific atmosphere of this skin.
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
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

		// the bullet used to prefix list items
		define('BULLET_IMG', '<img src="'.$context['url_to_root'].$context['skin'].'/images/art_end.gif" width="8" height="8" alt="" />');

		// the bullet prefix for compact lists
		define('COMPACT_LIST_ITEM_PREFIX', '&raquo;&nbsp;');

		// the bullet used with the [decorated] code; often equivalent to [*]
		define('DECORATED_IMG', '<img src="'.$context['url_to_root'].$context['skin'].'/images/art_end.gif" width="8" height="8" alt="" />');

		// the HTML used to append to a stripped text
		define('MORE_IMG', '<img src="'.$context['url_to_root'].$context['skin'].'/icons/zoom.png" width="15" height="11" alt="" />');

	}
}
?>