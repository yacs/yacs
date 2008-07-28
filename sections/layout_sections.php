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
	 * - 'references' - like 'full', but urls are references to sections
	 * - 'select' - like 'full', but urls are links to the article editor form - used at articles/edit.php
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

		// sanity check
		if(!isset($this->layout_variant))
			$this->layout_variant = 'full';

		// flag sections updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// we return an array of ($url => $attributes)
		$items = array();

		// process all items in the list
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'files/files.php';
		include_once $context['path_to_root'].'links/links.php';
		while($item =& SQL::fetch($result)) {

			// the url to view this item
			if($this->layout_variant == 'references')
				$url = 'section:'.$item['id'];
			elseif($this->layout_variant == 'select')
				$url = 'articles/edit.php?anchor='.urlencode('section:'.$item['id']);
			else
				$url = Sections::get_url($item['id'], 'view', $item['title'], $item['nick_name']);

			// reset the rendering engine between items
			Codes::initialize($url);

			// initialize variables
			$prefix = $label = $suffix = $icon = '';

			// not too many details on mobiles
			if($this->layout_variant != 'mobile') {

				// flag sections that are draft, dead, or created or updated very recently
				if($item['activation_date'] >= $now)
					$prefix .= DRAFT_FLAG;
				elseif(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $now))
					$prefix .= EXPIRED_FLAG;
				elseif($item['create_date'] >= $dead_line)
					$suffix .= NEW_FLAG;
				elseif($item['edit_date'] >= $dead_line)
					$suffix .= UPDATED_FLAG;

			}

			// signal restricted and private sections
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// the introductory text
			if($item['introduction'])
				$suffix .= ' -&nbsp;'.Codes::beautify($item['introduction'], $item['options']);

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
				if(preg_match('/\barticles_by_title\b/i', $item['options']))
					$order = 'title';
				elseif(preg_match('/\barticles_by_publication\b/i', $item['options']))
					$order = 'publication';
				elseif(preg_match('/\barticles_by_rating\b/i', $item['options']))
					$order = 'rating';
				elseif(preg_match('/\barticles_by_reverse_rank\b/i', $item['options']))
					$order = 'reverse_rank';
				else
					$order = 'date';
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
				$content[Sections::get_url($item['id'], 'view', $item['title'], $item['nick_name'])] = array('', i18n::s('More').MORE_IMG, '', 'more', '', i18n::s('View the section'));

			// append details to the suffix
			if(count($details))
				$suffix .= ' <span class="details">('.implode(', ', $details).')</span>';

			// not if decorated
			if(($this->layout_variant != 'decorated') && ($this->layout_variant != 'references')) {

				// one line per related item
				if(count($content))
					$suffix .= '<div class="details">'.Skin::build_list($content, 'compact')."</div>\n";

			}

			// start the label with family, if any
			$label = '';
			if($item['family'])
				$label = Codes::beautify_title($item['family']).' - ';

			// use the title to label the link
			$label .= Codes::beautify_title($item['title']);

			// the icon to put in the left column
			if($item['thumbnail_url'])
				$icon = $item['thumbnail_url'];

			// some hovering title for this section
			$hover = i18n::s('View the section');

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, 'section', $icon, $hover);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>