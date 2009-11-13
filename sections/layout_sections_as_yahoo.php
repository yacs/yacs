<?php
/**
 * layout sections as an index page of Yahoo!
 *
 * With this layout several sub-items are listed as well.
 * These can be either sub-sections and/or articles, depending of relative availability of both kind of items.
 *
 * @see sections/sections.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Thierry Pinelli [email]contact@vdp-digital.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_sections_as_yahoo extends Layout_interface {

	/**
	 * list sections
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	 *
	 * @see skins/layout.php
	**/
	function &layout(&$result) {
		global $context;

		// empty list
		if(!SQL::count($result)) {
			$output = array();
			return $output;
		}

		// flag sections updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = strftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));
		$now = strftime('%Y-%m-%d %H:%M:%S');

		// we return some text
		$text ='';

		// maximum number of items
		if(isset($this->layout_variant) && ($this->layout_variant > 3))
			$maximum_items = $this->layout_variant;
		elseif(defined('YAHOO_LIST_SIZE'))
			$maximum_items = YAHOO_LIST_SIZE;
		else
			$maximum_items = 7;

		// stack of items
		$items = array();

		// process all items in the list
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'files/files.php';
		include_once $context['path_to_root'].'links/links.php';
		include_once $context['path_to_root'].'overlays/overlay.php';
		$family = '';
		while($item =& SQL::fetch($result)) {

			// change the family
			if($item['family'] != $family) {

				// flush current stack, if any
				if(count($items))
					$text .= Skin::build_list($items, '2-columns');
				$items = array();

				// show the family
				$family = $item['family'];
				$text .= '<h3 class="family">'.$family.'</h3>'."\n";

			}

			// the url to view this item
			$url =& Sections::get_permalink($item);

			// initialize variables
			$prefix = $label = $suffix = $icon = '';

			// signal restricted and private sections
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// flag sections that are draft, dead, or created or updated very recently
			if($item['activation_date'] >= $now)
				$prefix .= DRAFT_FLAG;
			elseif(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $now))
				$prefix .= EXPIRED_FLAG;
			elseif($item['create_date'] >= $dead_line)
				$suffix .= NEW_FLAG;
			elseif($item['edit_date'] >= $dead_line)
				$suffix .= UPDATED_FLAG;

			// details and content
			$details = array();
			$content = array();

			// count related sub-elements
			$related_count = 0;

			// info on related sections
			if($count = Sections::count_for_anchor('section:'.$item['id'])) {
				if($count > $maximum_items)
					$details[] = sprintf(i18n::ns('%d section', '%d sections', $count), $count);
				elseif(Surfer::is_empowered())
					$details[] = sprintf(i18n::ns('%d section', '%d sections', $count), $count);
				$related_count += $count;

				// add sub-sections
				if($related =& Sections::list_by_title_for_anchor('section:'.$item['id'], 0, $maximum_items, 'compact')) {
					foreach($related as $sub_url => $label) {
						$sub_prefix = $sub_suffix = $sub_hover = '';
						if(is_array($label)) {
							$sub_prefix = $label[0];
							$sub_suffix = $label[2];
							if(@$label[5])
								$sub_hover = $label[5];
							$label = $label[1];
						}
						$content[] = $sub_prefix.Skin::build_link($sub_url, $label, 'section', $sub_hover).$sub_suffix;
					}
				}

			}

			// info on related articles
			if($count = Articles::count_for_anchor('section:'.$item['id'])) {
				if($count > $maximum_items)
					$details[] = sprintf(i18n::ns('%d page', '%d pages', $count), $count);
				elseif(Surfer::is_empowered())
					$details[] = sprintf(i18n::ns('%d page', '%d pages', $count), $count);
				$related_count += $count;

				// get the overlay for content of this section, if any
				$content_overlay = NULL;
				if(isset($item['content_overlay']))
					$content_overlay = Overlay::bind($item['content_overlay']);

				// no room to list articles
				if(count($content) >= $maximum_items)
					;

				// delegate rendering to the overlay, where applicable
				elseif(is_object($content_overlay) && is_callable(array($content_overlay, 'render_list_for_anchor'))) {

					if($related = $content_overlay->render_list_for_anchor('section:'.$item['id'], $maximum_items - count($content))) {
						foreach($related as $sub_url => $label) {
							$sub_prefix = $sub_suffix = $sub_hover = '';
							if(is_array($label)) {
								$sub_prefix = $label[0];
								$sub_suffix = $label[2];
								if(@$label[5])
									$sub_hover = $label[5];
								$label = $label[1];
							}
							$content[] = $sub_prefix.Skin::build_link($sub_url, $label, 'basic', $sub_hover).$sub_suffix;
						}
					}

				// regular rendering of related articles
				} else {
					if(preg_match('/\barticles_by_([a-z_]+)\b/i', $item['options'], $matches))
						$order = $matches[1];
					else
						$order = 'edition';
					if($related =& Articles::list_for_anchor_by($order, 'section:'.$item['id'], 0, $maximum_items - count($content), 'compact')) {
						foreach($related as $sub_url => $label) {
							$sub_prefix = $sub_suffix = $sub_hover = '';
							if(is_array($label)) {
								$sub_prefix = $label[0];
								$sub_suffix = $label[2];
								if(@$label[5])
									$sub_hover = $label[5];
								$label = $label[1];
							}
							$content[] = $sub_prefix.Skin::build_link($sub_url, $label, 'article', $sub_hover).$sub_suffix;
						}
					}
				}
			}

			// info on related files
			if($count = Files::count_for_anchor('section:'.$item['id'], TRUE)) {
				$details[] = sprintf(i18n::ns('%d file', '%d files', $count), $count);
				$related_count += $count;

				// add related files if necessary
				if((count($content) < $maximum_items) && ($related = Files::list_by_date_for_anchor('section:'.$item['id'], 0, $maximum_items - count($content), 'compact'))) {
					foreach($related as $sub_url => $label) {
						$sub_prefix = $sub_suffix = $sub_hover = '';
						if(is_array($label)) {
							$sub_prefix = $label[0];
							$sub_suffix = $label[2];
							if(@$label[5])
								$sub_hover = $label[5];
							$label = $label[1];
						}
						$content[] = $sub_prefix.Skin::build_link($sub_url, $label, 'file', $sub_hover).$sub_suffix;
					}
				}

			}

			// info on related links
			if($count = Links::count_for_anchor('section:'.$item['id'], TRUE)) {
				$details[] = sprintf(i18n::ns('%d link', '%d links', $count), $count);
				$related_count += $count;

				// add related links if necessary
				if((count($content) < $maximum_items) && ($related = Links::list_by_date_for_anchor('section:'.$item['id'], 0, $maximum_items - count($content), 'compact'))) {
					foreach($related as $sub_url => $label) {
						$sub_prefix = $sub_suffix = $sub_hover = '';
						if(is_array($label)) {
							$sub_prefix = $label[0];
							$sub_suffix = $label[2];
							if(@$label[5])
								$sub_hover = $label[5];
							$label = $label[1];
						}
						$content[] = $sub_prefix.Skin::build_link($sub_url, $label, 'link', $sub_hover).$sub_suffix;
					}
				}

			}

			// info on related comments
			if($count = Comments::count_for_anchor('section:'.$item['id'], TRUE)) {
				$details[] = sprintf(i18n::ns('%d comment', '%d comments', $count), $count);
				$related_count += $count;
			}

			// rank, for associates
			if(($item['rank'] != 10000) && Surfer::is_empowered())
				$details[] = '{'.$item['rank'].'}';

			// introduction
			if($item['introduction'])
				$suffix .= ' '.Codes::beautify_introduction($item['introduction']);

			// append details to the suffix
			if(count($details))
				$suffix .= ' <span class="details">('.implode(', ', $details).')</span>';

			// give me more
			if(count($content) && ($related_count > $maximum_items))
				$content[] = Skin::build_link(Sections::get_permalink($item), i18n::s('More').MORE_IMG, 'more', i18n::s('View the section'));

			// layout details
			if(count($content))
				$suffix .= BR.YAHOO_ITEM_PREFIX.implode(YAHOO_ITEM_SUFFIX.YAHOO_ITEM_PREFIX, $content).YAHOO_ITEM_SUFFIX."\n";

			// use the title to label the link
			$label = Skin::strip($item['title'], 50);

			// put the actual icon in the left column
			if(isset($item['thumbnail_url']))
				$icon = $item['thumbnail_url'];

			// some hovering title for this section
			$hover = i18n::s('View the section');

			// list all components for this item --use basic link style to avoid prefix or suffix images, if any
			$items[$url] = array($prefix, $label, $suffix, 'basic', $icon, $hover);

		}

		// flush the stack
		if(count($items))
			$text .= Skin::build_list($items, '2-columns');

		// end of processing
		SQL::free($result);
		return $text;
	}

}

?>