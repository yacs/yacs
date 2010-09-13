<?php
/**
 * layout articles as a set of titles with thumbnails
 *
 * @see articles/articles.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_titles extends Layout_interface {

	/**
	 * list articles
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	 *
	 * @see skins/layout.php
	**/
	function &layout(&$result) {
		global $context;

		// we return some text
		$text = '';

		// empty list
		if(!SQL::count($result))
			return $text;

		// clear flows
		$text .= '<br style="clear: left" />';

		// process all items in the list
		include_once $context['path_to_root'].'overlays/overlay.php';
		while($item =& SQL::fetch($result)) {

			// get the related overlay
			$overlay = Overlay::load($item);

			// the url to view this item
			$url =& Articles::get_permalink($item);

			// use the title to label the link
			if(is_object($overlay))
				$title = Codes::beautify_title($overlay->get_text('title', $item));
			else
				$title = Codes::beautify_title($item['title']);

			// the hovering title
			if($item['introduction'] && ($context['skins_with_details'] == 'Y'))
				$hover = strip_tags(Codes::beautify_introduction($item['introduction']));

			// add a link to the main page
			else
				$hover = i18n::s('View the page');

			// title is a link to the target article
			$title =& Skin::build_link($url, $title, 'basic', $hover);

			// use the thumbnail for this article
			if($icon = trim($item['thumbnail_url'])) {

				// fix relative path
				if(!preg_match('/^(\/|http:|https:|ftp:)/', $icon))
					$icon = $context['url_to_root'].$icon;

				// use parameter of the control panel for this one
				$options = '';
				if(isset($context['classes_for_thumbnail_images']))
					$options = 'class="'.$context['classes_for_thumbnail_images'].'" ';

				// build the complete HTML element
				$icon = '<img src="'.$icon.'" alt="" title="'.encode_field($hover).'" '.$options.' />';

			// use default icon if nothing to display
			} else
				$icon = MAP_IMG;

			// use the image as a link to the target page
			$icon =& Skin::build_link($url, $icon, 'basic', $hover);

			// add a floating box
			$text .= Skin::build_box($title, $icon, 'floating');

		}

		// clear flows
		$text .= '<br style="clear: left" />';

		// end of processing
		SQL::free($result);
		return $text;
	}

}

?>