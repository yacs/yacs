<?php
/**
 * layout interface definition
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_interface {

	/**
	 * the preferred order for items
	 *
	 * This can be used to customize SQL requests used to fetch records
	 * that will be passed to layout instance.
	 *
	 * Example of use:
	 * [php]
	 * // get a layout
	 * $layout = new MyOwn_Layout();
	 *
	 * // get records order
	 * if(!$order = $layout->items_order())
	 *    $order = 'publication';
	 *
	 * // query the database
	 * $items =& Articles::list_for_anchor_by($order, $anchor, 0, 10, $layout);
	 * [/php]
	 *
	 * @return string to be used in requests to the database
	 */
	function items_order() {
		return 'edition';
	}

	/**
	 * the preferred number of items for this layout
	 *
	 * @return int the optimised count of items for this layout
	 */
	function items_per_page() {
		return 50;
	}

	/**
	 * layout one set of results
	 *
	 * @param resource the SQL result of some query
	 * @return mixed the rendered text, or an array to be further formatted
	 */
	function layout($result) {
		return 'Obviously, this has not been overlaid';
	}

	/**
	 * the variant for this layout, if any
	 */
	var $layout_variant;

	/**
	 * change the behaviour of this layout
	 *
	 * @param string the variant for this layout, if any
	 * @return void
	 */
	function set_variant($variant = '') {
		$this->layout_variant = $variant;
	}

}

?>