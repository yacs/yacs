<?php
/**
 * layout tables
 *
 * This is the default layout for tables.
 *
 * @see tables/index.php
 * @see tables/tables.php
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_tables extends Layout_interface {

	/**
	 * list tables
	 *
	 * Recognize following variants:
	 * - 'no_anchor' to list items attached to one particular anchor
	 *
	 * @param resource the SQL result
	 * @param string a variant, if any
	 * @return string the rendered text
	 *
	 * @see skins/layout.php
	**/
	function &layout(&$result, $variant='full') {
		global $context;

		// we return an array of ($url => $attributes)
		$items = array();

		// empty list
		if(!SQL::count($result))
			return $items;

		// load localized strings
		i18n::bind('tables');

		// flag tables updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// process all items in the list
		while($item =& SQL::fetch($result)) {

			// initialize variables
			$prefix = $suffix = $icon = '';

			// the url to view this item
			$url = Tables::get_url($item['id']);

			// reset the rendering engine between items
			if(is_callable(array('Codes', 'initialize')))
				Codes::initialize($url);

			// flag tables created or updated very recently
			if(isset($item['create_date']) && ($item['create_date'] >= $dead_line))
				$suffix .= NEW_FLAG;
			elseif(isset($item['edit_date']) && ($item['edit_date'] >= $dead_line))
				$suffix .= UPDATED_FLAG;

			// the title
			if($item['title'])
				$label = Skin::strip($item['title'], 10);

			// details
			$details = array();
			if(Surfer::is_associate() && $item['nick_name'])
				$details[] = '"'.$item['nick_name'].'"';
			if(Surfer::is_logged() && $item['edit_name']) {
				$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));
			}
			$suffix .= BR.ucfirst(implode(', ', $details))."\n";

			// the menu bar for associates and poster
			if(Surfer::is_empowered()) {
				$menu = array();
				$menu = array_merge($menu, array( Tables::get_url($item['id'], 'edit') => i18n::s('Edit') ));
				$menu = array_merge($menu, array( Tables::get_url($item['id'], 'delete') => i18n::s('Delete') ));
				$suffix .= ' '.Skin::build_list($menu, 'menu');
			}

			// show an anchor link
			if(($variant != 'no_anchor') && $item['anchor'] && ($anchor = Anchors::get($item['anchor']))) {
				$anchor_url = $anchor->get_url();
				$anchor_label = ucfirst($anchor->get_title());
				$suffix .= BR.sprintf(i18n::s('In %s'), Skin::build_link($anchor_url, $anchor_label));
			}

			// the image id to put as text in the left column
			if($variant == 'no_anchor')
				$icon .= '[table='.$item['id'].']';

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, 'table', $icon);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>