<?php
/**
 * layout files for search requests
 *
 * @see search.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_files_as_search extends Layout_interface {

	/**
	 * list files for search requests
	 *
	 * @param resource the SQL result
	 * @return array of resulting items ($score, $summary), or NULL
	 *
	 * @see skins/layout.php
	**/
	function layout($result) {
		global $context;

		// we return an array of array($score, $summary)
		$items = array();

		// empty list
		if(!SQL::count($result))
			return $items;

		// process all items in the list
		while($item = SQL::fetch($result)) {

			// one box at a time
			$box = '';

			// get the main anchor
			$anchor = Anchors::get($item['anchor']);

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

			// first line of details
			$details = array();

			// file poster and last action
			$details[] = sprintf(i18n::s('shared by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

			// downloads
			if($item['hits'] > 1)
				$details[] = Skin::build_number($item['hits'], i18n::s('downloads'));

			// file size
			if($item['file_size'] > 1)
				$details[] = Skin::build_number($item['file_size'], i18n::s('bytes'));

			// file has been detached
			if(isset($item['assign_id']) && $item['assign_id']) {

				// who has been assigned?
				if(Surfer::is($item['assign_id']))
					$details[] = DRAFT_FLAG.sprintf(i18n::s('reserved by you %s'), Skin::build_date($item['assign_date']));
				else
					$details[] = DRAFT_FLAG.sprintf(i18n::s('reserved by %s %s'), Users::get_link($item['assign_name'], $item['assign_address'], $item['assign_id']), Skin::build_date($item['assign_date']));
			}

			// the main anchor link
			if(is_object($anchor))
				$details[] = sprintf(i18n::s('in %s'), Skin::build_link($anchor->get_url(), ucfirst($anchor->get_title()), 'article'));

			// append details
			if(count($details))
				$box .= '<p class="details">'.Skin::finalize_list($details, 'menu').'</p>';

			// layout this item
			if($icon) {
				$list = array(array($box, $icon));
				$items[] = array($item['score'], Skin::finalize_list($list, 'decorated'));

			// put the item in a division
			} else
				$items[] = array($item['score'], '<div style="margin: 0 0 1em 0">'.$box.'</div>');

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>