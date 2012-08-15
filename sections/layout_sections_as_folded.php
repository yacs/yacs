<?php
/**
 * layout sections as folded boxes with content
 *
 * With this layout each section is displayed as a folded box listing content (sub-sections, pages, ...).
 *
 * @see sections/view.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Thierry Pinelli [email]contact@vdp-digital.com[/email]
 * @tester Olivier
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_sections_as_folded extends Layout_interface {

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
	 * @see skins/layout.php
	**/
	function layout($result) {
		global $context;

		// empty list
		if(!SQL::count($result)) {
			$output = array();
			return $output;
		}

		// the maximum number of items per section
		if(!defined('MAXIMUM_ITEMS_PER_SECTION'))
			define('MAXIMUM_ITEMS_PER_SECTION', 100);

		// we return plain text
		$text = '';

		// process all items in the list
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'links/links.php';
		$family = '';
		while($item = SQL::fetch($result)) {

			// change the family
			if($item['family'] != $family) {
				$family = $item['family'];

				// show the family
				$text .= '<h2><span>'.$family.'&nbsp;</span></h2>'."\n";

			}

			// get the related overlay, if any
			$overlay = Overlay::load($item, 'section:'.$item['id']);

			// get the main anchor
			$anchor = Anchors::get($item['anchor']);

			// one box per section
			$box = array('title' => '', 'text' => '');

			// box content
			$elements = array();

			// use the title to label the link
			if(is_object($overlay))
				$box['title'] .= Codes::beautify_title($overlay->get_text('title', $item));
			else
				$box['title'] .= Codes::beautify_title($item['title']);

			$details = array();

			// info on related articles
			if(preg_match('/\barticles_by_([a-z_]+)\b/i', $item['options'], $matches))
				$order = $matches[1];
			else
				$order = 'edition';
			$items =& Articles::list_for_anchor_by($order, 'section:'.$item['id'], 0, MAXIMUM_ITEMS_PER_SECTION+1, 'compact');

			if(@count($items)) {

				// mention the number of items in folded title
				$details[] = sprintf(i18n::ns('%d page', '%d pages', count($items)), count($items));

				// add one link per item
				foreach($items as $url => $label) {
					$prefix = $suffix = '';
					if(is_array($label)) {
						$prefix = $label[0];
						$suffix = $label[2];
						$label = $label[1];
					}
					$elements[] = $prefix.Skin::build_link($url, $label, 'article').$suffix;
				}
			}

			// info on related files
			if(Sections::has_option('files_by', $anchor, $item) == 'title')
				$items = Files::list_by_title_for_anchor('section:'.$item['id'], 0, MAXIMUM_ITEMS_PER_SECTION+1, 'compact');
			else
				$items = Files::list_by_date_for_anchor('section:'.$item['id'], 0, MAXIMUM_ITEMS_PER_SECTION+1, 'compact');
			if($items) {

				// mention the number of sections in folded title
				$details[] = sprintf(i18n::ns('%d file', '%d files', count($items)), count($items));

				// add one link per item
				foreach($items as $url => $label) {
					if(is_array($label)) {
						$prefix = $label[0];
						$suffix = $label[2];
						$label = $label[1];
					}
					$elements[] = $prefix.Skin::build_link($url, $label, 'file').$suffix;
				}
			}

			// info on related comments
			if($items = Comments::list_by_date_for_anchor('section:'.$item['id'], 0, MAXIMUM_ITEMS_PER_SECTION+1, 'compact', TRUE)) {

				// mention the number of sections in folded title
				$details[] = sprintf(i18n::ns('%d comment', '%d comments', count($items)), count($items));

				// add one link per item
				foreach($items as $url => $label) {
					$prefix = $suffix = '';
					if(is_array($label)) {
						$prefix = $label[0];
						$suffix = $label[2];
						$label = $label[1];
					}
					$elements[] = $prefix.Skin::build_link($url, $label, 'comment').$suffix;
				}
			}

			// info on related links
			if(Sections::has_option('links_by_title', $anchor, $item))
				$items = Links::list_by_title_for_anchor('section:'.$item['id'], 0, MAXIMUM_ITEMS_PER_SECTION+1, 'compact');
			else
				$items = Links::list_by_date_for_anchor('section:'.$item['id'], 0, MAXIMUM_ITEMS_PER_SECTION+1, 'compact');
			if($items) {

				// mention the number of sections in folded title
				$details[] = sprintf(i18n::ns('%d link', '%d links', count($items)), count($items));

				// add one link per item
				foreach($items as $url => $label) {
					$prefix = $suffix = '';
					if(is_array($label)) {
						$prefix = $label[0];
						$suffix = $label[2];
						$label = $label[1];
					}
					$elements[] = $prefix.Skin::build_link($url, $label).$suffix;
				}
			}

			// list related sections, if any
			if($items =& Sections::list_by_title_for_anchor('section:'.$item['id'], 0, MAXIMUM_ITEMS_PER_SECTION+1, 'compact')) {

				// mention the number of sections in folded title
				$details[] = sprintf(i18n::ns('%d section', '%d sections', count($items)), count($items));

				// add one link per item
				foreach($items as $url => $label) {
					$prefix = $suffix = '';
					if(is_array($label)) {
						$prefix = $label[0];
						$suffix = $label[2];
						$label = $label[1];
					}
					$elements[] = $prefix.Skin::build_link($url, $label, 'section').$suffix;
				}
			}

			// signal continuing sections
			if(count($elements) > MAXIMUM_ITEMS_PER_SECTION)
				$elements[] = Skin::build_link(Sections::get_permalink($item), i18n::s('More pages').MORE_IMG, 'basic');

			// else allow to view the section anyway
			else
				$elements[] = Skin::build_link(Sections::get_permalink($item), i18n::s('View the section'), 'shortcut');

			// complement title
			if(count($details))
				$box['title'] .= ' <span class="details">('.join(', ', $details).')</span>';

			// insert introduction, if any
			if($item['introduction'])
				$box['text'] .= Codes::beautify_introduction($item['introduction']);

			// make a full list
			if(count($elements))
				$box['text'] .= '<ul><li>'.implode('</li>'."\n".'<li>', $elements).'</li></ul>'."\n";

			// if we have an icon for this section, use it
			if(isset($item['thumbnail_url']) && $item['thumbnail_url']) {

				// adjust the class
				$class= '';
				if(isset($context['classes_for_thumbnail_images']))
					$class = 'class="'.$context['classes_for_thumbnail_images'].'" ';

				// build the complete HTML element
				$icon = '<img src="'.$item['thumbnail_url'].'" alt="" title="'.encode_field(Codes::beautify_title($item['title'])).'" '.$class.'/>';

				// make it clickable
				$link = Skin::build_link(Sections::get_permalink($item), $icon, 'basic');

				// put this aside
				$box['text'] = '<table class="decorated"><tr>'
					.'<td class="image">'.$link.'</td>'
					.'<td class="content">'.$box['text'].'</td>'
					.'</tr></table>';

			}

			// always make a box
			$text .= Skin::build_box($box['title'], $box['text'], 'folded');

		}

		// end of processing
		SQL::free($result);
		return $text;
	}

}

?>
