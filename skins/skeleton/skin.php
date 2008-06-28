<?php
/**
 * Static functions used to produce HTML code for page components (lists, blocks, etc.)
 * Declare here all things used to build some HTML, but only HTML-related things.
 *
 * This class is an extension of the generic Skin_skeleton implementation ([script]skins/skin_skeleton.php[/script]).
 *
 * Combined with the template ([script]skins/skeleton/template.php[/script]), and with related CSS and image files, it
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

		// the HTML used to prefix an answer
		define('ANSWER_FLAG', '<img src="'.$context['url_to_root'].$context['skin'].'/images/answer.gif" width="23" height="30" alt="" class="left_image" />');

		// the bullet prefix for compact lists
		define('COMPACT_LIST_ITEM_PREFIX', '&raquo;&nbsp;');

		// the title for gadget boxes
		define('GADGET_BOX_TITLE_PREFIX', '<span>');

		// the title for gadget boxes
		define('GADGET_BOX_TITLE_SUFFIX', '</span>');

		// the HTML string used to prefix submenu items [submenu]
		define('MENU_2_PREFIX', '&raquo;&nbsp;');

		// the HTML string appended to submenu items [submenu]
		define('MENU_2_SUFFIX', BR);

		// the HTML used to append to a stripped text
		define('MORE_IMG', '&raquo;<img src="'.$context['url_to_root'].$context['skin'].'/icons/zoom.png" width="15" height="11" alt="" />');

		// the HTML used to prefix a question
		define('QUESTION_FLAG', '<img src="'.$context['url_to_root'].$context['skin'].'/images/question.gif" width="16" height="15" alt="" /> ');

	}
}

?>