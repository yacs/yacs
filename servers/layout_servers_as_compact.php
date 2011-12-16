<?php
/**
 * layout servers
 *
 * This is the default layout for servers.
 *
 * @see servers/index.php
 * @see servers/servers.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_servers_as_compact extends Layout_interface {

	/**
	 * list servers
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

			// initialize variables
			$prefix = $suffix = $icon = '';

			// the url to view this item
			$url = Servers::get_url($item['id']);

			// use the title as a label
			$label = Skin::strip($item['title'], 10);

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, 'server', $icon);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>