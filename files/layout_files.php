<?php
/**
 * layout files
 *
 * This is the default layout for files.
 *
 * @see files/index.php
 * @see files/files.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @tester Marco Pici
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_files extends Layout_interface {

	/**
	 * list files
	 *
	 * Recognize following variants:
	 * - 'section:123' to list items attached to one particular anchor
	 * - 'no_author' to list items attached to one user profile
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

			// stream the file, except for mp3, which benefit from the dewplayer
			if(Files::is_stream($item['file_name']) && !(preg_match('/\.mp3$/i', $item['file_name']) && file_exists($context['path_to_root'].'included/browser/dewplayer.swf')))
				$url = Files::get_url($item['id'], 'stream', $item['file_name']);

			// else download the file
			else
				$url = Files::get_url($item['id'], 'fetch', $item['file_name']);

			// initialize variables
			$prefix = $suffix = $icon = '';

			// provide the dewplayer for mp3 files
			if(preg_match('/\.mp3$/i', $item['file_name']))
				$prefix .= Files::interact($item, 320, 240, '', FALSE).BR;

			// flag files uploaded recently
			if($item['create_date'] >= $context['fresh'])
				$prefix .= NEW_FLAG;
			elseif($item['edit_date'] >= $context['fresh'])
				$prefix .= UPDATED_FLAG;

			// signal restricted and private files
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// file title or file name
			$label = Codes::beautify_title($item['title']);
			if(!$label)
				$label = ucfirst(str_replace(array('%20', '-', '_'), ' ', $item['file_name']));

			// details
			$details = array();

			// file size
			if($item['file_size'] > 1)
				$details[] = Skin::build_number($item['file_size'], i18n::s('bytes'));

			// downloads
			if($item['hits'] > 1)
				$details[] = Skin::build_number($item['hits'], i18n::s('downloads'));

			if(count($details))
				$suffix .= ' '.ucfirst(implode(', ', $details));

			$suffix = ' <span class="details">- '.ucfirst(trim($suffix)).'</span>';

			// append details
			$details = array();

			// anchor link
			if($anchor && is_string($this->layout_variant) && ($this->layout_variant != $anchor->get_reference())) {
				$anchor_url = $anchor->get_url();
				$anchor_label = ucfirst($anchor->get_title());
				$details[] = sprintf(i18n::s('in %s'), Skin::build_link($anchor_url, $anchor_label, 'article'));
			}

			// file poster and last action
			if($this->layout_variant != 'no_author')
				$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));
			else
				$details[] = Skin::build_date($item['edit_date']);

			// file has been detached
			if(isset($item['assign_id']) && $item['assign_id']) {

				// who has been assigned?
				if(Surfer::is($item['assign_id']))
					$details[] = DRAFT_FLAG.sprintf(i18n::s('reserved by you %s'), Skin::build_date($item['assign_date']));
				else
					$details[] = DRAFT_FLAG.sprintf(i18n::s('reserved by %s %s'), Users::get_link($item['assign_name'], $item['assign_address'], $item['assign_id']), Skin::build_date($item['assign_date']));
			}

			// detach or edit the file
			if(Files::allow_modification($anchor, $item)) {

				if(!isset($item['assign_id']) || !$item['assign_id'] || Surfer::is($item['assign_id']) || (is_object($anchor) && $anchor->is_owned()))
					$details[] = Skin::build_link(Files::get_url($item['id'], 'edit'), i18n::s('update'), 'span', i18n::s('Share a new version of this file, or change details'));

				if(!isset($item['assign_id']) || !$item['assign_id'])
					$details[] = Skin::build_link(Files::get_url($item['id'], 'reserve'), i18n::s('reserve'), 'span', i18n::s('Prevent other persons from changing this file until you update it'));

				// release reservation
				if(isset($item['assign_id']) && $item['assign_id'] && (Surfer::is($item['assign_id']) || (is_object($anchor) && $anchor->is_owned())))
					$details[] = Skin::build_link(Files::get_url($item['id'], 'release'), i18n::s('release reservation'), 'span', i18n::s('Allow other persons to update this file'));

			}

			// view the file
			$details[] = Skin::build_link(Files::get_permalink($item), i18n::s('details'), 'span', i18n::s('View file details'));

			// delete the file
			if(Files::allow_deletion($item, $anchor))
				$details[] = Skin::build_link(Files::get_url($item['id'], 'delete'), i18n::s('delete'), 'span', i18n::s('Drop file content'));

			// append the menu, if any
			if(count($details))
				$suffix .= BR.Skin::finalize_list($details, 'menu');

			// description
			if(trim($item['description']))
				$suffix .= Skin::build_box(i18n::s('History'), Codes::beautify($item['description']), 'folded');

			// explicit icon
			if($item['thumbnail_url'])
				$icon = $item['thumbnail_url'];

			// or reinforce file type
			else
				$icon = $context['url_to_root'].Files::get_icon_url($item['file_name']);

			// show a reference to the file for members
			$hover = i18n::s('Get the file');
			if(Surfer::is_member())
				$hover .= ' [file='.$item['id'].']';

			// absolute url
			$url = $context['url_to_home'].$context['url_to_root'].$url;

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, 'file', $icon, $hover);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>