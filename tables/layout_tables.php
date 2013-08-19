<?php
/**
 * layout tables
 *
 * This is the default layout for tables.
 *
 * @see tables/index.php
 * @see tables/tables.php
 *
 * @author Bernard Paques
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
	 * @return array one item per image
	 *
	 * @see layouts/layout.php
	**/
	function layout($result) {
		global $context;

		// we return an array of ($url => $attributes)
		$items = array();

		// empty list
		if(!SQL::count($result))
			return $items;

		if(!isset($this->layout_variant))
			$this->layout_variant = 'no_anchor';

		// process all items in the list
		while($item = SQL::fetch($result)) {

			// get the main anchor
			$anchor = Anchors::get($item['anchor']);

			// initialize variables
			$prefix = $suffix = $icon = '';

			// the url to view this item
			$url = Tables::get_url($item['id']);

			// codes to embed this image
			if($anchor && ($this->focus == $anchor->get_reference())) {

				// codes
				$codes = array();
				$codes[] = '[table='.$item['id'].']';
				$codes[] = '[table.filter='.$item['id'].']';
				$codes[] = '[table.chart='.$item['id'].']';
				$codes[] = '[table.bars='.$item['id'].']';
				$codes[] = '[table.line='.$item['id'].']';

				// integrate codes
				if(!isset($_SESSION['surfer_editor']) || ($_SESSION['surfer_editor'] == 'yacs')) {
					foreach($codes as $code)
						$suffix .= '<a onclick="edit_insert(\'\', \' '.$code.'\');return false;" title="insert" tabindex="2000">'.$code.'</a> ';

				} else
					$suffix .= join(' ', $codes);
				$suffix .= BR;

			}

			// we are listing tables attached to an chor
			if($anchor && ($this->focus == $anchor->get_reference())) {

				$label = '_';

				// the title
				if($item['title'])
					$suffix .= Skin::strip($item['title'], 10);

			// an index of tables
			} else {

				// the title
				if($item['title'])
					$label = Skin::strip($item['title'], 10);

			}

			// flag tables created or updated very recently
			if(isset($item['create_date']) && ($item['create_date'] >= $context['fresh']))
				$suffix .= NEW_FLAG;
			elseif(isset($item['edit_date']) && ($item['edit_date'] >= $context['fresh']))
				$suffix .= UPDATED_FLAG;

			// details
			$details = array();
			if(Surfer::is_associate() && $item['nick_name'])
				$details[] = '"'.$item['nick_name'].'"';
			if(Surfer::is_logged() && $item['edit_name']) {
				$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));
			}

			// the menu bar for associates and poster
			if(Surfer::is_empowered()) {
				$details[] = Skin::build_link(Tables::get_url($item['id'], 'view'), i18n::s('details'), 'basic');
				$details[] = Skin::build_link(Tables::get_url($item['id'], 'edit'), i18n::s('edit'), 'basic');
				$details[] = Skin::build_link(Tables::get_url($item['id'], 'delete'), i18n::s('delete'), 'basic');
			}

			// append details
			if(count($details))
				$suffix .= BR.Skin::finalize_list($details, 'menu');

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, 'table', $icon);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>
