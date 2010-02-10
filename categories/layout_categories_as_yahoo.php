<?php
/**
 * layout categories as an index page of Yahoo!
 *
 * With this layout up to three sub-items are listed as well.
 * These can be either sub-categories and/or articles, depending of relative availability of both kind of items.
 *
 * @see categories/categories.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_categories_as_yahoo extends Layout_interface {

	/**
	 * list categories
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	 *
	 * @see skins/layout.php
	**/
	function &layout($result) {
		global $context;

		// empty list
		if(!SQL::count($result)) {
			$output = array();
			return $output;
		}

		// flag categories updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// we return an array of ($url => $attributes)
		$items = array();

		// process all items in the list
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'links/links.php';
		while($item =& SQL::fetch($result)) {

			// url to read the full category
			$url =& Categories::get_permalink($item);

			// initialize variables
			$prefix = $suffix = $icon = '';

			// flag categories that are dead, or created or updated very recently
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $now))
				$prefix .= EXPIRED_FLAG;
			elseif($item['create_date'] >= $dead_line)
				$suffix .= NEW_FLAG;
			elseif($item['edit_date'] >= $dead_line)
				$suffix .= UPDATED_FLAG;

			// signal restricted and private categories
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// introduction
			if($item['introduction'])
				$suffix .= ' '.Codes::beautify(trim($item['introduction']));

			// details
			$details = array();

			// count related sub-elements
			$related_count = 0;

			// info on related categories
			$stats = Categories::stat_for_anchor('category:'.$item['id']);
			if($stats['count'])
				$details[] = sprintf(i18n::ns('%d category', '%d categories', $stats['count']), $stats['count']);
			$related_count += $stats['count'];

			// info on related sections
			if($count = Members::count_sections_for_anchor('category:'.$item['id'])) {
				$details[] = sprintf(i18n::ns('%d section', '%d sections', $count), $count);
				$related_count += $count;
			}

			// info on related articles
			if($count = Members::count_articles_for_anchor('category:'.$item['id'])) {
				$details[] = sprintf(i18n::ns('%d page', '%d pages', $count), $count);
				$related_count += $count;
			}

			// info on related files
			if($count = Files::count_for_anchor('category:'.$item['id'], TRUE)) {
				$details[] = sprintf(i18n::ns('%d file', '%d files', $count), $count);
				$related_count += $count;
			}

			// info on related links
			if($count = Links::count_for_anchor('category:'.$item['id'], TRUE)) {
				$details[] = sprintf(i18n::ns('%d link', '%d links', $count), $count);
				$related_count += $count;
			}

			// info on related comments
			if($count = Comments::count_for_anchor('category:'.$item['id'], TRUE)) {
				$details[] = sprintf(i18n::ns('%d comment', '%d comments', $count), $count);
				$related_count += $stats['count'];
			}

			// info on related users
			if($count = Members::count_users_for_anchor('category:'.$item['id']))
				$details[] = sprintf(i18n::ns('%d user', '%d users', $count), $count);

			// append details to the suffix
			if(count($details))
				$suffix .= "\n".'<span class="details">('.implode(', ', $details).')</span>';

			// add a head list of related links
			$details = array();

			// add sub-categories on index pages
			if($related = Categories::list_by_date_for_anchor('category:'.$item['id'], 0, YAHOO_LIST_SIZE, 'compact')) {
				foreach($related as $sub_url => $label) {
					$sub_prefix = $sub_suffix = $sub_hover = '';
					if(is_array($label)) {
						$sub_prefix = $label[0];
						$sub_suffix = $label[2];
						if(@$label[5])
							$sub_hover = $label[5];
						$label = $label[1];
					}
					$details[] = $sub_prefix.Skin::build_link($sub_url, $label, 'basic', $sub_hover).$sub_suffix;
				}
			}

			// add related sections if necessary
			if((count($details) < YAHOO_LIST_SIZE) && ($related =& Members::list_sections_by_title_for_anchor('category:'.$item['id'], 0, YAHOO_LIST_SIZE - count($details), 'compact'))) {
				foreach($related as $sub_url => $label) {
					$sub_prefix = $sub_suffix = $sub_hover = '';
					if(is_array($label)) {
						$sub_prefix = $label[0];
						$sub_suffix = $label[2];
						if(@$label[5])
							$sub_hover = $label[5];
						$label = $label[1];
					}
					$details[] = $sub_prefix.Skin::build_link($sub_url, $label, 'basic', $sub_hover).$sub_suffix;
				}
			}

			// add related articles if necessary
			if((count($details) < YAHOO_LIST_SIZE) && ($related =& Members::list_articles_by_date_for_anchor('category:'.$item['id'], 0, YAHOO_LIST_SIZE - count($details), 'compact'))) {
				foreach($related as $sub_url => $label) {
					$sub_prefix = $sub_suffix = $sub_hover = '';
					if(is_array($label)) {
						$sub_prefix = $label[0];
						$sub_suffix = $label[2];
						if(@$label[5])
							$sub_hover = $label[5];
						$label = $label[1];
					}
					$details[] = $sub_prefix.Skin::build_link($sub_url, $label, 'basic', $sub_hover).$sub_suffix;
				}
			}

			// give me more
			if(count($details) && ($related_count > YAHOO_LIST_SIZE))
				$details[] = Skin::build_link(Categories::get_permalink($item), i18n::s('More').MORE_IMG, 'more', i18n::s('View the category'));

			// layout details
			if(count($details))
				$suffix .= BR."\n&raquo;&nbsp;".'<span class="details">'.implode(', ', $details)."</span>\n";

			// put the actual icon in the left column
			if(isset($item['thumbnail_url']))
				$icon = $item['thumbnail_url'];

			// use the title to label the link
			$label = Skin::strip($item['title'], 50);

			// some hovering title for this category
			$hover = i18n::s('View the category');

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, 'category', $icon, $hover);

		}

		// end of processing
		SQL::free($result);
		$output = Skin::build_list($items, '2-columns');
		return $output;
	}

}

?>