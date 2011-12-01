<?php
/**
 * layout sections as a very compact list
 *
 * @see sections/sections.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Thierry Pinelli [email]contact@vdp-digital.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_sections_as_comma extends Layout_interface {

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

		// we return some text
		$text = '';

		// empty list
		if(!$delta = SQL::count($result))
			return $text;

		// process all items in the list
		$count = 0;
		$items = array();
		while($item =& SQL::fetch($result)) {

			// the url to view this item
			$url = Sections::get_permalink($item);

			// initialize variables
			$prefix = $label = $suffix = '';

			// flag sections that are draft or dead
			if($item['activation_date'] >= $context['now'])
				$prefix .= DRAFT_FLAG;
			elseif(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
				$prefix .= EXPIRED_FLAG;

			// signal restricted and private sections
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// flag items updated recently
			if($item['create_date'] >= $context['fresh'])
				$suffix .= NEW_FLAG;
			elseif($item['edit_date'] >= $context['fresh'])
				$suffix .= UPDATED_FLAG;

//			// start the label with family, if any
//			if($item['family'])
//				$label = ucfirst(Skin::strip($item['family'], 30)).' - ';

			// use the title to label the link
			$label .= ucfirst(Skin::strip($item['title'], 30));

			// the hovering title
			if($item['introduction'] && ($context['skins_with_details'] == 'Y'))
				$hover = strip_tags(Codes::beautify_introduction($item['introduction']));

			// add a link to the main page
			else
				$hover = i18n::s('View the section');

			// help members to reference this page
			if(Surfer::is_member())
				$hover .= ' [section='.$item['id'].']';

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, 'basic', NULL, $hover);

			// limit to one page of results
			if(++$count >= (COMPACT_LIST_SIZE-1))
				break;
		}

		// end of processing
		SQL::free($result);

		// turn this to some text
		$text .= Skin::build_list($items, 'comma');

		// some indications on the number of connections
		if(($delta -= $count) > 0)
			$text .= ', ...';

		return $text;
	}

}

?>