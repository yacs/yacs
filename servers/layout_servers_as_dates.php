<?php
/**
 * layout servers
 *
 * This is the default layout for servers.
 *
 * @see servers/index.php
 * @see servers/servers.php
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_servers_as_dates extends Layout_interface {

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

		// flag servers updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// we return an array of ($url => $attributes)
		$items = array();

		// process all items in the list
		while($item =& SQL::fetch($result)) {

			// the url to view this item
			$url = Servers::get_url($item['id']);

			// use the title as a label
			$label = Skin::strip($item['title'], 10);

			// list all components for this item
			$items[$url] = array('', $label, ' '.Skin::build_date($item['edit_date']), 'server', NULL);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>