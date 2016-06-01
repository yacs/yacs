<?php
/**
 * layout categories for search requests
 *
 * @see search.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_categories_as_search extends Layout_interface {

	/**
	 * list categories for search requests
	 *
	 * @param resource the SQL result
	 * @return array of resulting items ($score, $summary), or NULL
	 *
	 * @see layouts/layout.php
	**/
	function layout($result) {
		global $context;

		// we return an array of array($score, $summary)
		$items = array();

		// empty list
		if(!SQL::count($result))
			return $items;

		// process all items in the list
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'links/links.php';
		while($item = SQL::fetch($result)) {

			// one box at a time
			$box = '';

			// get the main anchor
			$anchor = Anchors::get($item['anchor']);

			// url to read the full category
			$url = Categories::get_permalink($item);

			// initialize variables
			$prefix = $suffix = $icon = '';

			// flag categories that are dead, or created or updated very recently
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
				$prefix .= EXPIRED_FLAG;
			elseif($item['create_date'] >= $context['fresh'])
				$suffix .= NEW_FLAG;
			elseif($item['edit_date'] >= $context['fresh'])
				$suffix .= UPDATED_FLAG;

			// signal restricted and private categories
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// use the title to label the link
			$title = Skin::strip($item['title'], 10);

			// details
			$details = array();

			// info on related categories
			$stats = Categories::stat_for_anchor('category:'.$item['id']);
			if($stats['count'])
				$details[] = sprintf(i18n::ns('%d category', '%d categories', $stats['count']), $stats['count']);

			// info on related sections
			if($count = Members::count_sections_for_anchor('category:'.$item['id']))
				$details[] = sprintf(i18n::ns('%d section', '%d sections', $count), $count);

			// info on related articles
			if($count = Members::count_articles_for_anchor('category:'.$item['id']))
				$details[] = sprintf(i18n::ns('%d page', '%d pages', $count), $count);

			// info on related files
			if($count = Files::count_for_anchor('category:'.$item['id'], TRUE))
				$details[] = sprintf(i18n::ns('%d file', '%d files', $count), $count);

			// info on related links
			if($count = Links::count_for_anchor('category:'.$item['id'], TRUE))
				$details[] = sprintf(i18n::ns('%d link', '%d links', $count), $count);

			// info on related comments
			if($count = Comments::count_for_anchor('category:'.$item['id'], TRUE))
				$details[] = sprintf(i18n::ns('%d comment', '%d comments', $count), $count);

			// info on related users
			if($count = Members::count_users_for_anchor('category:'.$item['id']))
				$details[] = sprintf(i18n::ns('%d user', '%d users', $count), $count);

			// the main anchor link
			if(is_object($anchor))
				$details[] = sprintf(i18n::s('in %s'), Skin::build_link($anchor->get_url(), ucfirst($anchor->get_title()), 'category'));

			// append details to the suffix
			if(count($details))
				$suffix .= "\n".'<span '.tag::_class('details').'>('.implode(', ', $details).')</span>';

			// introduction
			if($item['introduction'])
				$suffix .= ' '.Codes::beautify(trim($item['introduction']));

			// item summary
			$box .= $prefix.Skin::build_link($url, $title, 'category').$suffix;

			// put the actual icon in the left column
			if(isset($item['thumbnail_url']) && ($this->layout_variant != 'sidebar'))
				$icon = $item['thumbnail_url'];

			// layout this item
			if($icon) {

				// build the complete HTML element
				$icon = '<img src="'.$icon.'" alt="" title="'.encode_field(strip_tags($title)).'" />';

				// make it a clickable link
				$icon = Skin::build_link($url, $icon, 'basic');

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