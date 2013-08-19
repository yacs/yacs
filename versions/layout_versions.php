<?php
/**
 * layout versions
 *
 * This is the default layout for versions.
 *
 * @see versions/index.php
 * @see versions/versions.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_versions extends Layout_interface {

	/**
	 * list versions
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	 *
	 * @see layouts/layout.php
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

			// initialize variables
			$prefix = $suffix = $icon = '';

			// the url to view this item
			$url = '_'.$item['id']; // Versions::get_url($item['id']);

			// version description
			$label = sprintf(i18n::s('edited by %s %s'), ucfirst($item['edit_name']), Skin::build_date($item['edit_date']));

			// command to view this version
			$suffix .= ' '.Skin::build_link(Versions::get_url($item['id'], 'view'), i18n::s('compare to current version'), 'button');

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, 'version', $icon);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>