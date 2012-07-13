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
	 * @return string HTML text to be displayed, or NULL
	 *
	 * @see skins/layout.php
	**/
	function layout($result) {
		global $context;

		// we return some text
		$text = '';

		// empty list
		if(!SQL::count($result))
			return $text;

		// sanity check
		if(!isset($this->layout_variant))
			$this->layout_variant = '';

		// process all items in the list
		$items = array();
		while($item = SQL::fetch($result)) {

			// one box at a time
			$box = '';

			// get the main anchor
			$anchor = Anchors::get($item['anchor']);

			// we feature only the head of the list, if we are at the origin page
			if(!count($items) && $anchor && is_string($this->layout_variant) && ($this->layout_variant == $anchor->get_reference())) {
				$box .= Codes::render_object('file', $item['id']);

				// no side icon
				$icon = '';

			// we are listing various files from various places
			} else {
				$prefix = $suffix = '';

				// stream the file
				if(Files::is_stream($item['file_name']))
					$url = Files::get_url($item['id'], 'stream', $item['file_name']);

				// else download the file
				else
					$url = Files::get_url($item['id'], 'fetch', $item['file_name']);

				// absolute url
				$url = $context['url_to_home'].$context['url_to_root'].$url;

				// signal restricted and private files
				if($item['active'] == 'N')
					$prefix .= PRIVATE_FLAG;
				elseif($item['active'] == 'R')
					$prefix .= RESTRICTED_FLAG;

				// file title or file name
				$label = Codes::beautify_title($item['title']);
				if(!$label)
					$label = ucfirst(str_replace(array('%20', '-', '_'), ' ', $item['file_name']));

				// show a reference to the file for members
				$hover = i18n::s('Get the file');
				if(Surfer::is_member())
					$hover .= ' [file='.$item['id'].']';

				// flag files uploaded recently
				if($item['create_date'] >= $context['fresh'])
					$suffix .= NEW_FLAG;
				elseif($item['edit_date'] >= $context['fresh'])
					$suffix .= UPDATED_FLAG;

				// one line of text
				$box .= $prefix.Skin::build_link($url, $label, 'basic', $hover).$suffix;

				// side icon
				if($item['thumbnail_url'])
					$icon = $item['thumbnail_url'];

				// or reinforce file type
				else
					$icon = $context['url_to_root'].Files::get_icon_url($item['file_name']);

				// build the complete HTML element
				$icon = '<img src="'.$icon.'" alt="" title="'.encode_field(strip_tags($label)).'" />';

				// make it a clickable link
				$icon = Skin::build_link($url, $icon, 'basic');

			}

			// first line of details
			$details = array();

			// file poster and last action
			if($this->layout_variant != 'no_author')
				$details[] = sprintf(i18n::s('shared by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));
			else
				$details[] = Skin::build_date($item['edit_date']);

			// downloads
			if($item['hits'] > 1)
				$details[] = Skin::build_number($item['hits'], i18n::s('downloads'));

			// file size
			if($item['file_size'] > 1)
				$details[] = Skin::build_number($item['file_size'], i18n::s('bytes'));

			// anchor link
			if($anchor && is_string($this->layout_variant) && ($this->layout_variant != $anchor->get_reference())) {
				$anchor_url = $anchor->get_url();
				$anchor_label = ucfirst($anchor->get_title());
				$details[] = sprintf(i18n::s('in %s'), Skin::build_link($anchor_url, $anchor_label, 'article'));
			}

			$box .= '<p class="details">'.Skin::finalize_list($details, 'menu').'</p>';

			// append details
			$details = array();

			// view the file
			$details[] = Skin::build_link(Files::get_permalink($item), i18n::s('details'), 'basic');

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

				if(!isset($item['assign_id']) || !$item['assign_id'])
					$details[] = Skin::build_link(Files::get_url($item['id'], 'reserve'), i18n::s('reserve'), 'basic', i18n::s('Prevent other persons from changing this file until you update it'));

				// release reservation
				if(isset($item['assign_id']) && $item['assign_id'] && (Surfer::is($item['assign_id']) || (is_object($anchor) && $anchor->is_owned())))
					$details[] = Skin::build_link(Files::get_url($item['id'], 'release'), i18n::s('release reservation'), 'basic', i18n::s('Allow other persons to update this file'));

				if(!isset($item['assign_id']) || !$item['assign_id'] || Surfer::is($item['assign_id']) || (is_object($anchor) && $anchor->is_owned()))
					$details[] = Skin::build_link(Files::get_url($item['id'], 'edit'), i18n::s('update'), 'basic', i18n::s('Share a new version of this file, or change details'));

			}

			// delete the file
			if(Files::allow_deletion($item, $anchor))
				$details[] = Skin::build_link(Files::get_url($item['id'], 'delete'), i18n::s('delete'), 'basic');

			// append details
			if(count($details))
				$box .= '<p class="details">'.Skin::finalize_list($details, 'menu').'</p>';

			// insert item icon
			if($icon) {
				$list = array(array($box, $icon));
				$items[] = Skin::finalize_list($list, 'decorated');

			// put the item in a division
			} else
				$items[] = '<div style="margin: 0 0 1em 0">'.$box.'</div>';

		}

		// stack all items in a single column
		$text = Skin::finalize_list($items, 'rows');

		// end of processing
		SQL::free($result);
		return $text;
	}

}

?>