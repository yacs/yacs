<?php
/**
 * layout articles as a list of thumbnail images
 *
 * @see articles/articles.php
 *
 * @author Bernard Paques
 * @author Thierry Pinelli (ThierryP)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_thumbnails extends Layout_interface {

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

		// process all items in the list
		while($item =& SQL::fetch($result)) {

			// we want to make it visual
			if(!$item['thumbnail_url'])
				continue;

			// get the anchor
			$anchor =& Anchors::get($item['anchor']);

			// the url to view this item
			$url =& Articles::get_permalink($item);

			// reset the rendering engine between items
			Codes::initialize($url);

			// use the skin to shape it -- no title, partner name is in the image itself
			$text .= Skin::build_image('left', $item['thumbnail_url'], '', $url);

		}

		// end of processing
		SQL::free($result);
		return $text;
	}

}

?>