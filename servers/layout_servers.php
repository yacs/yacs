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
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_servers extends Layout_interface {

	/**
	 * list servers
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
			$url = Servers::get_url($item['id']);

			// use the title as a label
			$label = Skin::strip($item['title'], 10);

			// flag files uploaded recently
			if($item['edit_date'] >= $context['fresh'])
				$prefix = NEW_FLAG.$prefix;

			// description
			if($item['description'])
				$suffix .= ' '.ucfirst(trim($item['description']));

			// the menu bar for associates and poster
			if(Surfer::is_empowered() || Surfer::is($item['edit_id'])) {
				$menu = array( Servers::get_url($item['id'], 'edit') => i18n::s('Edit'),
					Servers::get_url($item['id'], 'delete') => i18n::s('Delete') );
				$suffix .= ' '.Skin::build_list($menu, 'menu');
			}

			// add a separator
			if($suffix)
				$suffix = ' - '.$suffix;

			// append details to the suffix
			$suffix .= BR.'<span class="details">';

			// details
			$details = array();

			// item poster
			if($item['edit_name'])
				$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

			// the edition date
			$details[] = Skin::build_date($item['edit_date']);

			// all details
			if(count($details))
				$suffix .= ucfirst(implode(', ', $details))."\n";

			// end of details
			$suffix .= '</span>';

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, 'server', $icon);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>