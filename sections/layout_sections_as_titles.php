<?php
/**
 * layout sections as a set of titles with thumbnails
 *
 * @see sections/sections.php
 *
 * @author Bernard Paques
 * @author Thierry Pinelli [email]contact@vdp-digital.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_sections_as_titles extends Layout_interface {

	/**
	 * list sections
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
		$family = '';
		while($item =& SQL::fetch($result)) {

			// change the family
			if($item['family'] != $family) {
				$family = $item['family'];

				$text .= '<br clear="left" /><div class="floating_family">'.$family.'&nbsp;</div>'."\n";
			}

			// get the anchor
			$anchor =& Anchors::get($item['anchor']);

			// the url to view this item
			$url =& Sections::get_permalink($item);

			// use the title to label the link
			$title = Skin::strip($item['title'], 50);

			// the hovering title
			if($item['introduction'] && ($context['skins_with_details'] == 'Y'))
				$hover = strip_tags(Codes::beautify_introduction($item['introduction']));

			// add a link to the main page
			else
				$hover = i18n::s('View the section');

			// title is a link to the target section
			$title =& Skin::build_link($url, $title, 'basic', $hover);

			// look for an image
			$icon = '';
			if(isset($item['thumbnail_url']) && $item['thumbnail_url'])
				$icon = $item['thumbnail_url'];
			elseif(is_object($anchor))
				$icon = $anchor->get_thumbnail_url();

			// use the thumbnail for this section
			if($icon) {

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