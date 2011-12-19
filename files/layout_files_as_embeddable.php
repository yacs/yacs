<?php
/**
 * layout embeddable files
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_files_as_embeddable extends Layout_interface {

	/**
	 * list files
	 *
	 * @param resource the SQL result
	 * @return array of resulting items, or NULL
	 *
	 * @see skins/layout.php
	**/
	function layout($result) {
		global $context;

		// we return an array of ($url => $attributes)
		$items = array();

		// empty list
		if(!SQL::count($result))
			return $items;

		// sanity check
		if(!isset($this->layout_variant))
			$this->layout_variant = '';

		// process all items in the list
		while($item = SQL::fetch($result)) {

			// get the main anchor
			$anchor =& Anchors::get($item['anchor']);

			// initialize variables
			$prefix = $suffix = $icon = '';

			// more details
			$url = Files::get_permalink($item);

			// codes
			$codes = array();

			// files that can be embedded
			if(preg_match('/\.(3gp|flv|gan|m4v|mm|mov|mp4|swf)$/i', $item['file_name']))
				$codes[] = '[embed='.$item['id'].']';

			// link for direct download
			$codes[] = '[file='.$item['id'].']';
			$codes[] = '[download='.$item['id'].']';

			// integrate codes
			if(!isset($_SESSION['surfer_editor']) || ($_SESSION['surfer_editor'] == 'yacs')) {
				foreach($codes as $code)
					$suffix .= '<a onclick="edit_insert(\'\', \' '.$code.'\');return false;" title="insert" tabindex="2000">'.$code.'</a> ';

			} else
				$suffix .= join(' ', $codes);

			$suffix .= BR.'<span class="details">';

			// signal restricted and private files
			if($item['active'] == 'N')
				$suffix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$suffix .= RESTRICTED_FLAG;

			// file title or file name
			$label = Codes::beautify_title($item['title']);
			if(!$label)
				$label = ucfirst(str_replace(array('%20', '-', '_'), ' ', $item['file_name']));
			$suffix .= $label;

			// flag files uploaded recently
			if($item['create_date'] >= $context['fresh'])
				$suffix .= NEW_FLAG;
			elseif($item['edit_date'] >= $context['fresh'])
				$suffix .= UPDATED_FLAG;

			$suffix .= '</span>';

			// details
			$details = array();
			if(Surfer::is_logged() && $item['edit_name']) {
				$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));
			}

			// the menu bar for associates and poster
			if(Surfer::is_empowered()) {
				$details[] = Skin::build_link($url, i18n::s('details'), 'basic');
				$details[] = Skin::build_link(Files::get_url($item['id'], 'edit'), i18n::s('edit'), 'basic');
				$details[] = Skin::build_link(Files::get_url($item['id'], 'delete'), i18n::s('delete'), 'basic');
			}

			// append details
			if(count($details))
				$suffix .= BR.Skin::finalize_list($details, 'menu');

			// explicit icon
			if($item['thumbnail_url'])
				$icon = $item['thumbnail_url'];

			// or reinforce file type
			else
				$icon = $context['url_to_root'].Files::get_icon_url($item['file_name']);

			// list all components for this item
			$items[$url] = array($prefix, '_', $suffix, 'file', $icon);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>
