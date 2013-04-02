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
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Skin extends Skin_Skeleton {

	/**
	 * define constants used with this skin
	 */
	public static function initialize() {
		global $context;

		// we are HTML5
		define('SKIN_HTML5', TRUE);

		// add a empty span to tabs in order to justify the tabs (css tricks)
		define('TABS_SUFFIX','<span class="finish-tabs"></span>');

		$options = '';
		if(isset($context['classes_for_thumbnail_images']))
			$options = 'class="'.$context['classes_for_thumbnail_images'].'" ';

		// the img tag used with the [decorated] code; either a decorating icon, or equivalent to the bullet
		Skin::define_img('DECORATED_IMG', 'layouts/decorated.png', '', '*', $options);

	}


}

?>