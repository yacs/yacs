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
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
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
	 * - 'compact' - to build short lists in boxes and sidebars (this is the default)
	 * - '<a valid anchor>' - example: 'section:123' - to list images attached to an anchor page
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

		// load localized strings
		i18n::bind('images');

		// flag images updated recently
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
			$url = Images::get_url($item['id']);

			// reset the rendering engine between items
			if(is_callable(array('Codes', 'initialize')))
				Codes::initialize($url);

			// link to the thumbnail image, if any
			$label = '<span class="small_image"><img src="'.Images::get_thumbnail_href($item).'" title="'.encode_field(strip_tags($item['title'])).'" alt=""/></span>';

			// the menu bar
			$menu = array();

			// change the image
			if(Surfer::is_empowered() || Surfer::is_creator($item['edit_id']))
				$menu = array_merge($menu, array( Images::get_url($item['id'], 'edit') => i18n::s('Edit') ));

			// use the image
			if(Surfer::is_empowered() && Surfer::is_member()) {
				if(preg_match('/\buser\b/', $variant))
					$menu = array_merge($menu, array( Images::get_url($item['id'], 'set_as_thumbnail') => i18n::s('Set as user avatar') ));
				elseif(preg_match('/\b(article|category|section)\b/', $variant)) {
					$menu = array_merge($menu, array( Images::get_url($item['id'], 'set_as_icon') => i18n::s('Set as page icon') ));
					$menu = array_merge($menu, array( Images::get_url($item['id'], 'set_as_thumbnail') => i18n::s('Set as page thumbnail') ));
				}
				if(preg_match('/^(section|category)\b/i', $variant))
					$menu = array_merge($menu, array( Images::get_url($item['id'], 'set_as_bullet') => i18n::s('Set as list bullet') ));
			}

			// delete the image
			if(Surfer::is_empowered() || Surfer::is_creator($item['edit_id']))
				$menu = array_merge($menu, array( Images::get_url($item['id'], 'delete') => i18n::s('Delete') ));

			if(count($menu))
				$suffix .= BR.Skin::build_list($menu, 'menu');

			// the title
			if($item['title'])
				$suffix .= BR.Skin::strip($item['title'], 10)."\n";

			// details
			$details = array();

			// reference to the image
			$details[] = '[image='.$item['id'].']';

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
				$suffix .= BR.ucfirst(implode(', ', $details))."\n";

			// there is an anchor
			if($item['anchor'] && ($anchor = Anchors::get($item['anchor']))) {

				// the image id to put as text in the left column
				if($variant == $anchor->get_reference()) {
					$icon = '<a onclick="javascript:edit_insert(\'\', \' [image='.$item['id'].']\');return false;" title="insert" tabindex="2000">[image='.$item['id'].']</a>'
						.BR.'<a onclick="javascript:edit_insert(\'\', \' [image='.$item['id'].', left]\');return false;" title="insert" tabindex="2000">[image='.$item['id'].',left]</a>'
						.BR.'<a onclick="javascript:edit_insert(\'\', \' [image='.$item['id'].', right]\');return false;" title="insert" tabindex="2000">[image='.$item['id'].',right]</a>'
						.BR.'<a onclick="javascript:edit_insert(\'\', \' [image='.$item['id'].', center]\');return false;" title="insert" tabindex="2000">[image='.$item['id'].',center]</a>';

				// show an anchor link
				} else {
					$anchor_url = $anchor->get_url();
					$anchor_label = ucfirst($anchor->get_title());
					$suffix .= BR.sprintf(i18n::s('In %s'), Skin::build_link($anchor_url, $anchor_label));
				}
			}

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, 'image', $icon);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>