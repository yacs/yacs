<?php
/**
 * layout images as a compact list
 *
 * @see images/images.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_images_as_compact extends Layout_interface {

	/**
	 * list images
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

		// we return an array of ($url => $attributes)
		$items = array();

		// process all items in the list
		while($item = SQL::fetch($result)) {

			// url to view the image
			$url = Images::get_url($item['id']);

			// initialize variables
			$prefix = $suffix = '';

			// flag new images
			if($item['edit_date'] >= $context['fresh'])
				$suffix .= NEW_FLAG;

			// image title or image name
			$label = Skin::strip($item['title'], 10);
			if(!$label) {
				$name_as_title = TRUE;
				$label = ucfirst($item['image_name']);
			}
			$label = str_replace('_', ' ', str_replace('%20', ' ', $label));

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, 'basic', NULL);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>