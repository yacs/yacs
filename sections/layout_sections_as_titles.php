<?php
/**
 * layout sections as a set of titles with thumbnails
 *
 * @see sections/sections.php
 *
 * @author Bernard Paques
 * @author Thierry Pinelli [email]contact@vdp-digital.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_sections_as_titles extends Layout_interface {

	/**
	 * the preferred number of items for this layout
	 *
	 * The compact format of this layout allows a high number of items to be listed
	 *
	 * @return int the optimised count of items fro this layout
	 */
	function items_per_page() {
		return 1000;
	}

	/**
	 * list sections
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	 *
	 * @see layouts/layout.php
	**/
	function layout($result) {
		global $context;

		// we return some text
		$text = '';

		// empty list
		if(!SQL::count($result))
			return $text;

		// maximum number of items
		if(isset($this->layout_variant) && ($this->layout_variant > 3))
			$maximum_items = $this->layout_variant;
		elseif(defined('YAHOO_LIST_SIZE'))
			$maximum_items = YAHOO_LIST_SIZE;
		else
			$maximum_items = 7;

		// clear flows
		$text .= '<br style="clear: left" />';

		// process all items in the list
		$family = '';
		while($item = SQL::fetch($result)) {

			// change the family
			if($item['family'] != $family) {
				$family = $item['family'];

				$text .= '<h2><span>'.$family.'&nbsp;</span></h2>'."\n";
			}

			// get the anchor
			$anchor = Anchors::get($item['anchor']);

			// the url to view this item
			$url = Sections::get_permalink($item);

			// initialize variables
			$prefix = $label = $suffix = $icon = $hover = '';

			// signal restricted and private sections
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// flag sections that are draft, dead, or created or updated very recently
			if($item['activation_date'] >= $context['now'])
				$prefix .= DRAFT_FLAG;
			elseif(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
				$prefix .= EXPIRED_FLAG;
			elseif($item['create_date'] >= $context['fresh'])
				$suffix .= NEW_FLAG;
			elseif($item['edit_date'] >= $context['fresh'])
				$suffix .= UPDATED_FLAG;

			// display introduction field on hovering
			if($item['introduction'])
				$hover .= strip_tags(Codes::beautify_introduction($item['introduction']));

			// details and content
			$details = array();
			$content = array();

			// count related sub-elements
			$related_count = 0;

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
							$content[] = $sub_prefix.$label.$sub_suffix;
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
							$content[] = $sub_prefix.$label.$sub_suffix;
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
						$content[] = $sub_prefix.$label.$sub_suffix;
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
						$content[] = $sub_prefix.$label.$sub_suffix;
					}
				}

			}

			// info on related comments
			if($count = Comments::count_for_anchor('section:'.$item['id'], TRUE)) {
				$details[] = sprintf(i18n::ns('%d comment', '%d comments', $count), $count);
				$related_count += $count;
			}

			// info on related sections
			if($count = Sections::count_for_anchor('section:'.$item['id'])) {
				if($count > $maximum_items)
					$details[] = sprintf(i18n::ns('%d section', '%d sections', $count), $count);
				elseif(Surfer::is_empowered())
					$details[] = sprintf(i18n::ns('%d section', '%d sections', $count), $count);
				$related_count += $count;

				// add sub-sections
				if($related =& Sections::list_by_title_for_anchor('section:'.$item['id'], 0, $maximum_items - count($content), 'compact')) {
					foreach($related as $sub_url => $label) {
						$sub_prefix = $sub_suffix = $sub_hover = '';
						if(is_array($label)) {
							$sub_prefix = $label[0];
							$sub_suffix = $label[2];
							if(@$label[5])
								$sub_hover = $label[5];
							$label = $label[1];
						}
						$content[] = $sub_prefix.$label.$sub_suffix;
					}
				}

			}

			// give me more
			if(count($content) && ($related_count > $maximum_items))
				$content[] = '...'.MORE_IMG;

			// layout details
			if(count($content)) {
				$hover .= '<ul><li>'.implode('</li><li>', $content).'</li></ul>';
			}

			// add a link to the main page
			if(!$hover)
				$hover = i18n::s('View the section');

			// use the title to label the link
			$title = Skin::strip($item['title'], 50);

			// new or updated flag
			if($suffix)
				$details[] = $suffix;

			// append details
			if(count($details))
				$title .= BR.'<span class="details">'.implode(', ', $details).'</span>';

			// look for an image
			$icon = '';
			if(isset($item['thumbnail_url']) && $item['thumbnail_url'])
				$icon = $item['thumbnail_url'];
			elseif(is_callable(array($anchor, 'get_bullet_url')))
				$icon = $anchor->get_bullet_url();

			// use the thumbnail for this section
			if($icon) {

				// fix relative path
				if(!preg_match('/^(\/|http:|https:|ftp:)/', $icon))
					$icon = $context['url_to_root'].$icon;

				// use parameter of the control panel for this one
				$options = '';
				if(isset($context['classes_for_thumbnail_images']))
					$options = 'class="'.$context['classes_for_thumbnail_images'].'" ';

				// build the complete HTML element
				$icon = '<img src="'.$icon.'" alt="" title="" '.$options.' />';

			// use default icon if nothing to display
			} else
				$icon = MAP_IMG;

			// use tipsy on hover
			$content = '<a href="'.$context['url_to_root'].$url.'" id="titles_'.$item['id'].'">'.$icon.BR.$prefix.$title.'</a>';
				
			Page::insert_script(
				'$(function() {'."\n"
				.'	$("a#titles_'.$item['id'].'").each(function() {'."\n"
				.'		$(this).tipsy({fallback: \'<div style="text-align: left;">'.str_replace(array("'", "\n"), array('"', '<br />'), $hover).'</div>\','."\n"
				.	'		 html: true,'."\n"
				.	'		 gravity: $.fn.tipsy.autoWE,'."\n"
				.	'		 fade: true,'."\n"
				.	'		 offset: 8,'."\n"
				.	'		 opacity: 1.0});'."\n"
				.'	});'."\n"
				.'});'."\n"
				);

			// add a floating box
			$text .= Skin::build_box(NULL, $content, 'floating');

		}

		// clear flows
		$text .= '<br style="clear: left" />';

		// end of processing
		SQL::free($result);
		return $text;
	}

}

?>
