<?php
/**
 * layout sections as a list of thumbnail images
 *
 * @see sections/sections.php
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author Thierry Pinelli [email]contact@vdp-digital.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_sections_as_thumbnails extends Layout_interface {

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

		// empty list
		if(!SQL::count($result)) {
			$output = array();
			return $output;
		}

		// we return some text
		$text = '';

		// process all items in the list
		while($item =& SQL::fetch($result)) {

			// we want to make it visual
			if(!$item['thumbnail_url'])
				continue;

			// a title for the image --do not force a title
			if(isset($item['title']))
				$title = $item['title'];
			else
				$title = '';

			// the url to view this item
			$url = Sections::get_url($item['id'], 'view', $item['title'], $item['nick_name']);

			// use the skin to shape it
			$text .= Skin::build_image('thumbnail', $item['thumbnail_url'], $title, $url);

		}

		// end of processing
		SQL::free($result);
		return $text;
	}

}

?>