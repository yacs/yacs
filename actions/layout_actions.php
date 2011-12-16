<?php
/**
 * layout actions
 *
 * This is the default layout for actions.
 *
 * @see actions/index.php
 * @see actions/actions.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_actions extends Layout_interface {

	/**
	 * list actions
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
			$url = Actions::get_url($item['id']);

			// action title
			$label = $item['title'];

			// description
			if($description = ucfirst(trim(Codes::beautify($item['description']))) )
				$suffix .= ' - '.Skin::cap($description, 70);

			// the edition date
			if($item['create_date'])
				$suffix .= '<br/><span class="small">'.Skin::build_date($item['create_date']).'</span>';
			else
				$suffix .= '<br/><span class="small">'.Skin::build_date($item['edit_date']).'</span>';

			// the menu bar for associates and poster
			if(Surfer::is_empowered() || Surfer::is($item['edit_id'])) {
				$menu = array( Actions::get_url($item['id'], 'edit') => i18n::s('edit'),
					Actions::get_url($item['id'], 'delete') => i18n::s('delete') );
				$suffix .= ' '.Skin::build_list($menu, 'menu');
			}

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, 'action', $icon);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>