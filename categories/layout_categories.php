<?php
/**
 * layout categories
 *
 * This is the default layout for categories
 *
 * @see categories/categories.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_categories extends Layout_interface {

	/**
	 * list categories
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	 *
	 * @see layouts/layout.php
	**/
	function layout($result) {
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
		while($item = SQL::fetch($result)) {

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
			$label = Skin::strip($item['title'], 10);

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

			// append details to the suffix
			if(count($details))
				$suffix .= "\n".'<span class="details">('.implode(', ', $details).')</span>';

			// introduction
			if($item['introduction'])
				$suffix .= ' '.Codes::beautify(trim($item['introduction']));

			// put the actual icon in the left column
			if(isset($item['thumbnail_url']) && ($this->layout_variant != 'sidebar'))
				$icon = $item['thumbnail_url'];

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, 'category', $icon);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>