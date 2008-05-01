<?php
/**
 * layout sections as a menu list
 *
 * This is used to list top-most sections as page menu on the front page.
 *
 * @see index.php
 * @see sections/sections.php
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_sections_as_menu extends Layout_interface {

	/**
	 * list sections
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

		// hovering label
		$href_title = i18n::s('View the section');

		// we return an array of ($url => $attributes)
		$items = array();

		// flag sections updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// process all items in the list
		while($item =& SQL::fetch($result)) {

			// the url to view this item
			$url = Sections::get_url($item['id'], 'view', $item['title'], $item['nick_name']);

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