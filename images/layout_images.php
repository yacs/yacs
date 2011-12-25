<?php
/**
 * layout images
 *
 * This is the default layout for images.
 *
 * @see articles/edit.php
 * @see images/index.php
 * @see images/images.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Paddy
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_images extends Layout_interface {

	/**
	 * list images
	 *
	 * Recognize following variants:
	 * - 'compact' - to build short lists in boxes and sidebars
	 * - '<a valid anchor>' - example: 'section:123' - to list images attached to an anchor page
	 *
	 * @param resource the SQL result
	 * @return array one item per image
	 *
	 * @see skins/layout.php
	**/
	function layout($result) {
		global $context;

		// empty list
		if(!SQL::count($result)) {
			$output = array();
			return $output;
		}

		if(!isset($this->layout_variant))
			$this->layout_variant = '';

		// we return an array of ($url => $attributes)
		$items = array();

		// process all items in the list
		while($item = SQL::fetch($result)) {

			// initialize variables
			$prefix = $suffix = $icon = '';

			// the url to view this item
			$url = Images::get_url($item['id']);

			$label = '_';

			// the title
			if($item['title'])
				$suffix .= Skin::strip($item['title'], 10).BR;

			// there is an anchor
			if($item['anchor'] && ($anchor =& Anchors::get($item['anchor']))) {

				// codes to embed this image
				if($this->layout_variant == $anchor->get_reference()) {

					// help to insert in textarea
// 					if(!isset($_SESSION['surfer_editor']) || ($_SESSION['surfer_editor'] == 'yacs'))
// 						$suffix .= '<a onclick="edit_insert(\'\', \' [image='.$item['id'].']\');return false;" title="insert" tabindex="2000">[image='.$item['id'].']</a>'
// 							.' <a onclick="edit_insert(\'\', \' [image='.$item['id'].', left]\');return false;" title="insert" tabindex="2000">[image='.$item['id'].',left]</a>'
// 							.' <a onclick="edit_insert(\'\', \' [image='.$item['id'].', right]\');return false;" title="insert" tabindex="2000">[image='.$item['id'].',right]</a>'
// 							.' <a onclick="edit_insert(\'\', \' [image='.$item['id'].', center]\');return false;" title="insert" tabindex="2000">[image='.$item['id'].',center]</a>';
//
// 					else
						$suffix .= '[image='.$item['id'].']'
							.' [image='.$item['id'].',left]'
							.' [image='.$item['id'].',right]'
							.' [image='.$item['id'].',center]';

					$suffix .= BR;

				// show an anchor link
				} else {
					$anchor_url = $anchor->get_url();
					$anchor_label = ucfirst($anchor->get_title());
					$suffix .= sprintf(i18n::s('In %s'), Skin::build_link($anchor_url, $anchor_label)).BR;
				}
			}

			// details
			$details = array();

			// file name
			if($item['image_name'])
				$details[] = $item['image_name'];

			// file size
			if($item['image_size'] > 1)
				$details[] = number_format($item['image_size']).'&nbsp;'.i18n::s('bytes');

			// poster
			if($item['edit_name'])
				$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

			// append details
			if(count($details))
				$suffix .= '<span class="details">'.ucfirst(implode(', ', $details)).'</span>'.BR;

			// the menu bar
			$menu = array();

			// change the image
			if(Surfer::is_empowered() || Surfer::is($item['edit_id']))
				$menu = array_merge($menu, array( Images::get_url($item['id'], 'edit') => i18n::s('Update this image') ));

			// use the image
			if(Surfer::is_empowered() && Surfer::is_member()) {
				if(preg_match('/\buser\b/', $this->layout_variant))
					$menu = array_merge($menu, array( Images::get_url($item['id'], 'set_as_thumbnail') => i18n::s('Set as profile picture') ));
				elseif(preg_match('/\b(article|category|section)\b/', $this->layout_variant)) {
					$menu = array_merge($menu, array( Images::get_url($item['id'], 'set_as_icon') => i18n::s('Set as page image') ));
					$menu = array_merge($menu, array( Images::get_url($item['id'], 'set_as_thumbnail') => i18n::s('Set as page thumbnail') ));
				}
			}

			// delete the image
			if(Surfer::is_empowered() || Surfer::is($item['edit_id']))
				$menu = array_merge($menu, array( Images::get_url($item['id'], 'delete') => i18n::s('Delete this image') ));

			if(count($menu))
				$suffix .= Skin::build_list($menu, 'menu');

			// link to the thumbnail image, if any
			$icon = '<span class="small_image"><img src="'.Images::get_thumbnail_href($item).'" title="'.encode_field(strip_tags($item['title'])).'" alt="" /></span>';

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, 'image', $icon);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>