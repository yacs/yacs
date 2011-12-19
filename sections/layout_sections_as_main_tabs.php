<?php
/**
 * layout sections as a list of tabs
 *
 * This layout should be invoked only once, by the function Page::tabs(),
 * to build top-level tabs of the full site.
 *
 * @see sections/sections.php
 * @see skins/page.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Thierry Pinelli (ThierryP)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_sections_as_main_tabs extends Layout_interface {

	/**
	 * list sections
	 *
	 * @param resource the SQL result
	 * @return an array of $url => (NULL, $title, NULL, 'section_123', NULL, 'visit this section')
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

		// no hovering label, because nicetitle may kill the effect
		$href_title = '';

		// we return an array of ($url => $attributes)
		$items = array();

		// process all items in the list
		while($item = SQL::fetch($result)) {

			// the url to view this item
			$url = Sections::get_permalink($item);

			// initialize variables
			$prefix = $suffix = '';

			// list all components for this item
			$items[$url] = array($prefix, ucfirst(Skin::strip($item['title'], 30)), $suffix, 'section_'.$item['id'], NULL, $href_title);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>