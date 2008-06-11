<?php
/**
 * layout links as a compact list
 *
 * @see links/links.php
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_links_as_compact extends Layout_interface {

	/**
	 * list links
	 *
	 * @param resource the SQL result
	 * @return array of resulting items, or NULL
	 *
	 * @see skins/layout.php
	**/
	function &layout(&$result) {
		global $context;

		// we return an array of ($url => $attributes)
		$items = array();

		// empty list
		if(!SQL::count($result))
			return $items;

		// flag links updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// process all items in the list
		while($item =& SQL::fetch($result)) {

			// url is the link itself
			$url = $item['link_url'];

			// initialize variables
			$prefix = $suffix = '';

			// flag links that are dead, or created or updated very recently
			if($item['edit_date'] >= $dead_line)
				$suffix .= NEW_FLAG;

			// make a label
			$label = Links::clean($item['title'], $item['link_url'], 30);

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, 'basic', NULL);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>