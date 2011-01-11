<?php
/**
 * layout sections
 *
 * This is the default layout for sections.
 *
 * @see sections/index.php
 * @see sections/sections.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Thierry Pinelli [email]contact@vdp-digital.com[/email]
 * @tester Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_sections extends Layout_interface {

	/**
	 * list sections
	 *
	 * Accept following variants:
	 * - 'full' - include anchor information -- also the default value
	 *
	 * @param resource the SQL result
	 * @return array of resulting items, or NULL
	 *
	 * @see skins/layout.php
	**/
	function &layout(&$result) {
		global $context;

		// we return an array of ($url => $attributes)
		$items = array();

		// empty list
		if(!SQL::count($result))
			return $items;

		// sanity check
		if(!isset($this->layout_variant))
			$this->layout_variant = 'decorated';

		// process all items in the list
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'links/links.php';
		include_once $context['path_to_root'].'overlays/overlay.php';
		while($item =& SQL::fetch($result)) {

			// get the related overlay, if any
			$overlay = Overlay::load($item, 'section:'.$item['id']);

			// get the main anchor
			$anchor =& Anchors::get($item['anchor']);

			// the url to view this item
			$url =& Sections::get_permalink($item);

			// use the title to label the link
			if(is_object($overlay))
				$title = Codes::beautify_title($overlay->get_text('title', $item));
			else
				$title = Codes::beautify_title($item['title']);

			// initialize variables
			$prefix = $suffix = $icon = '';

			// flag sections that are draft, dead, or created or updated very recently
			if($item['activation_date'] >= $context['now'])
				$prefix .= DRAFT_FLAG;
			elseif(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
				$prefix .= EXPIRED_FLAG;
			if($item['create_date'] >= $context['fresh'])
				$suffix .= NEW_FLAG;
			elseif($item['edit_date'] >= $context['fresh'])
				$suffix .= UPDATED_FLAG;

			// signal restricted and private sections
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// the introductory text
			if($item['introduction'])
				$suffix .= ' -&nbsp;'.Codes::beautify_introduction($item['introduction']);

			// details and content
			$details = array();
			$content = array();

			// count related sub-elements
			$related_count = 0;

			// info on related sections
			if($count = Sections::count_for_anchor('section:'.$item['id'])) {
				$details[] = sprintf(i18n::ns('%d section', '%d sections', $count), $count);
				$related_count += $count;

				// add sub-sections
				if($related =& Sections::list_by_title_for_anchor('section:'.$item['id'], 0, YAHOO_LIST_SIZE, 'compact')) {
					foreach($related as $link => $label) {
						$sub_prefix = $sub_suffix = $sub_hover = '';
						if(is_array($label)) {
							$sub_prefix = $label[0];
							$sub_suffix = $label[2];
							if(@$label[5])
								$sub_hover = $label[5];
							$label = $label[1];
						}
						$content[$link] = array($sub_prefix, $label, $sub_suffix, 'basic', '', $sub_hover);
					}
				}

			}

			// info on related articles
			if($count = Articles::count_for_anchor('section:'.$item['id'])) {
				$details[] = sprintf(i18n::ns('%d page', '%d pages', $count), $count);
				$related_count += $count;

				// add related articles if necessary
				if(preg_match('/\barticles_by_([a-z_]+)\b/i', $item['options'], $matches))
					$order = $matches[1];
				else
					$order = 'edition';
				if((count($details) < YAHOO_LIST_SIZE) && ($related =& Articles::list_for_anchor_by($order, 'section:'.$item['id'], 0, YAHOO_LIST_SIZE - count($details), 'compact'))) {
					foreach($related as $link => $label) {
						$sub_prefix = $sub_suffix = $sub_hover = '';
						if(is_array($label)) {
							$sub_prefix = $label[0];
							$sub_suffix = $label[2];
							if(@$label[5])
								$sub_hover = $label[5];
							$label = $label[1];
						}
						$content[$link] = array($sub_prefix, $label, $sub_suffix, 'basic', '', $sub_hover);
					}
				}

			}

			// info on related files
			if($count = Files::count_for_anchor('section:'.$item['id'])) {
				$details[] = sprintf(i18n::ns('%d file', '%d files', $count), $count);
				$related_count += $count;

				// add related files if necessary
				if((count($details) < YAHOO_LIST_SIZE) && ($related = Files::list_by_date_for_anchor('section:'.$item['id'], 0, YAHOO_LIST_SIZE - count($details), 'compact'))) {
					foreach($related as $link => $label) {
						$sub_prefix = $sub_suffix = $sub_hover = '';
						if(is_array($label)) {
							$sub_prefix = $label[0];
							$sub_suffix = $label[2];
							if(@$label[5])
								$sub_hover = $label[5];
							$label = $label[1];
						}
						$content[$link] = array($sub_prefix, $label, $sub_suffix, 'basic', '', $sub_hover);
					}
				}

			}

			// info on related links
			if($count = Links::count_for_anchor('section:'.$item['id'])) {
				$details[] = sprintf(i18n::ns('%d link', '%d links', $count), $count);
				$related_count += $count;

				// add related links if necessary
				if((count($details) < YAHOO_LIST_SIZE) && ($related = Links::list_by_date_for_anchor('section:'.$item['id'], 0, YAHOO_LIST_SIZE - count($details), 'compact'))) {
					foreach($related as $link => $label) {
						$sub_prefix = $sub_suffix = $sub_hover = '';
						if(is_array($label)) {
							$sub_prefix = $label[0];
							$sub_suffix = $label[2];
							if(@$label[5])
								$sub_hover = $label[5];
							$label = $label[1];
						}
						$content[$link] = array($sub_prefix, $label, $sub_suffix, 'basic', '', $sub_hover);
					}
				}

			}

			// info on related comments
			if($count = Comments::count_for_anchor('section:'.$item['id']))
				$details[] = sprintf(i18n::ns('%d comment', '%d comments', $count), $count);

			// give me more
			if(count($details) && ($related_count > YAHOO_LIST_SIZE))
				$content[Sections::get_permalink($item)] = array('', i18n::s('More').MORE_IMG, '', 'more', '', i18n::s('View the section'));

			// append details to the suffix
			if(count($details))
				$suffix .= ' <span class="details">('.implode(', ', $details).')</span>';

			// the main anchor link
			if(is_object($anchor) && (!isset($this->layout_variant) || ($item['anchor'] != $this->layout_variant)))
				$suffix .= ' <span class="details">'.sprintf(i18n::s('in %s'), Skin::build_link($anchor->get_url(), ucfirst($anchor->get_title()), 'section'))."</span>\n";

			// not if decorated
			if(($this->layout_variant != 'decorated') && ($this->layout_variant != 'references')) {

				// one line per related item
				if(count($content))
					$suffix .= '<p class="details">'.Skin::build_list($content, 'compact')."</p>\n";

			}

			// the icon to put in the left column
			if($item['thumbnail_url'])
				$icon = $item['thumbnail_url'];

			// some hovering title for this section
			$hover = i18n::s('View the section');

			// list all components for this item
			$items[$url] = array($prefix, $title, $suffix, 'section', $icon, $hover);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>
