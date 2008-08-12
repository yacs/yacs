<?php
/**
 * layout sections as a compact list
 *
 * @see sections/sections.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Thierry Pinelli [email]contact@vdp-digital.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_sections_as_compact extends Layout_interface {

	/**
	 * the preferred number of items for this layout
	 *
	 * @return int the optimised count of items for this layout
	 *
	 * @see skins/layout.php
	 */
	function items_per_page() {
		return COMPACT_LIST_SIZE;
	}

	/**
	 * list sections
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

		// flag sections updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// process all items in the list
		while($item =& SQL::fetch($result)) {

			// the url to view this item
			$url =& Sections::get_permalink($item);

			// initialize variables
			$prefix = $label = $suffix = '';

			// flag sections that are draft or dead
			if($item['activation_date'] >= $now)
				$prefix .= DRAFT_FLAG;
			elseif(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $now))
				$prefix .= EXPIRED_FLAG;

			// signal restricted and private sections
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// flag items updated recently
			if($item['create_date'] >= $dead_line)
				$suffix .= NEW_FLAG;
			elseif($item['edit_date'] >= $dead_line)
				$suffix .= UPDATED_FLAG;

//			// start the label with family, if any
//			if($item['family'])
//				$label = ucfirst(Skin::strip($item['family'], 30)).' - ';

			// use the title to label the link
			$label .= ucfirst(Skin::strip($item['title'], 30));

			// the hovering title
			if($item['introduction'] && ($context['skins_with_details'] == 'Y'))
				$hover = strip_tags(Codes::strip($item['introduction']));

			// add a link to the main page
			else
				$hover = i18n::s('View the section');

			// help members to reference this page
			if(Surfer::is_member())
				$hover .= ' [section='.$item['id'].']';

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, 'basic', NULL, $hover);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>