<?php
/**
 * provide raw content of images
 *
 * @see images/images.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_images_as_raw extends Layout_interface {

	/**
	 * list images
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	 *
	 * @see skins/layout.php
	**/
	function layout($result) {
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

			// include all available attributes for this item
			$items[ $item['id'] ] = $item;

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>