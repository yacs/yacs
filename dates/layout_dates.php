<?php
/**
 * layout dates
 *
 * This is the default layout for dates.
 *
 * @see dates/index.php
 * @see dates/dates.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_dates extends Layout_interface {

	/**
	 * list dates
	 *
	 * Recognize following variants:
	 * - 'no_anchor' to list items attached to one particular anchor
	 * - 'no_author' to list items attached to one user prodate
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

		// we return an array of ($url => $attributes)
		$items = array();

		// process all items in the list
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'links/links.php';
		while($item =& SQL::fetch($result)) {

			// initialize variables
			$prefix = $suffix = $icon = '';

			// the url to view this item
			$url = Articles::get_permalink($item);

			// build a valid label
			$label = Codes::beautify_title($item['title']);

			// the introductory text
			if($item['introduction']) {
				$suffix .= ' -&nbsp;'.Codes::strip($item['introduction']);

				// link to description, if any
				if($item['description'])
					$suffix .= ' '.Skin::build_link($url, MORE_IMG, 'more', i18n::s('View the page')).' ';

			}

			// details
			$details = array();

			// item poster
			if($variant != 'no_author') {
				if($item['edit_name'])
					$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

			} else
				$details[] = Anchors::get_action_label($item['edit_action']);

			// info on related files
			if($count = Files::count_for_anchor('article:'.$item['id'], TRUE))
				$details[] = sprintf(i18n::ns('%d file', '%d files', $count), $count);

			// info on related links
			if($count = Links::count_for_anchor('article:'.$item['id'], TRUE))
				$details[] = sprintf(i18n::ns('%d link', '%d links', $count), $count);

			// info on related comments
			if($count = Comments::count_for_anchor('article:'.$item['id'], TRUE))
				$details[] = sprintf(i18n::ns('%d comment', '%d comments', $count), $count);

			// rating
			if($item['rating_count'])
				$details[] = Skin::build_link(Articles::get_url($item['id'], 'rate'), Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])), 'basic');

			// all details
			if(count($details))
				$suffix .= BR.'<span class="details">'.ucfirst(implode(', ', $details)).'</span>';

			// the icon to put in the left column
			if($item['thumbnail_url'])
				$icon = $item['thumbnail_url'];

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, 'date', $icon, $item['date_stamp']);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>