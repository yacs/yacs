<?php
/**
 * layout categories as a list of tabs
 *
 * @see categories/categories.php
 *
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_categories_as_tabs extends Layout_interface {

	/**
	 * list categories
	 *
	 * @param resource the SQL result
	 * @return an array of $url => (NULL, $title, NULL, 'section_123', NULL, 'visit this section')
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

		// no hovering label, because nicetitle may kill the effect
		$href_title = '';

		// we return an array of ($url => $attributes)
		$items = array();

		// process all items in the list
		while($item =& SQL::fetch($result)) {

			// the url to view this item
			$url = Categories::get_url($item['id'], 'view', $item['title']);

			// initialize variables
			$prefix = $suffix = '';

			// list all components for this item
			$items[$url] = array($prefix, ucfirst(Skin::strip($item['title'], 30)), $suffix, 'category_'.$item['id'], NULL, $href_title);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>