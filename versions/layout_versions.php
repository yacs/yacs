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
	 * @param string a variant, if any
	 * @return string the rendered text
	 *
	 * @see skins/layout.php
	**/
	function &layout(&$result, $variant='full') {
		global $context;

		// empty list
		if(!SQL::count($result)) {
			$output = array();
			return $output;
		}

		// flag versions updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// we return an array of ($url => $attributes)
		$items = array();

		// process all items in the list
		while($item =& SQL::fetch($result)) {

			// initialize variables
			$prefix = $suffix = $icon = '';

			// the url to view this item
			$url = '_'.$item['id']; // Versions::get_url($item['id']);

			// reset the rendering engine between items
			if(is_callable(array('Codes', 'initialize')))
				Codes::initialize($url);

			// version description
			$label = sprintf(i18n::s('edited by %s %s'), ucfirst($item['edit_name']), Skin::build_date($item['edit_date']));

			// the menu bar
			$menu = array();

			// if option 'anonymous_edit', anonymous surfers may view versions
			if(Surfer::is_empowered() || Surfer::is($item['edit_id']))
				$menu = array_merge($menu, array( Versions::get_url($item['id'], 'view') => i18n::s('view') ));

			// authenticated associates and editors may restore a version
			if((Surfer::is_empowered() && Surfer::is_member()) || Surfer::is($item['edit_id']))
				$menu = array_merge($menu, array( Versions::get_url($item['id'], 'restore') => i18n::s('restore') ));

			if(count($menu))
				$suffix .= ' '.Skin::build_list($menu, 'menu');

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, 'version', $icon);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>