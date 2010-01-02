<?php
/**
 * layout sections inline, with content
 *
 * With this layout each section is displayed as a box listing related pages.
 *
 * @see sections/view.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Thierry Pinelli [email]contact@vdp-digital.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_sections_as_inline extends Layout_interface {

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

		// the maximum number of items per section
		if(!defined('MAXIMUM_ITEMS_PER_SECTION'))
			define('MAXIMUM_ITEMS_PER_SECTION', 50);

		// we return plain text
		$text = '';

		// process all items in the list
		include_once $context['path_to_root'].'comments/comments.php';
		while($item =& SQL::fetch($result)) {

			// get the related overlay, if any
			$overlay = Overlay::load($item);

			// get the main anchor
			$anchor =& Anchors::get($item['anchor']);

			// one box per section
			$box = array('title' => '', 'text' => '');

			// box content
			$elements = array();

			// start the label with family, if any
			if($item['family'])
				$box['title'] .= Skin::strip($item['family'], 30).' - ';

			// use the title to label the link
			if(is_object($overlay) && is_callable(array($overlay, 'get_live_title')))
				$box['title'] .= $overlay->get_live_title($item);
			else
				$box['title'] .= Codes::beautify_title($item['title']);

			// the url to view this item
			$url =& Sections::get_permalink($item);

			// add a direct link to the section
			$box['title'] .= '&nbsp;'.Skin::build_link($url, MORE_IMG, 'basic');

			// list related sections, if any
			if($items =& Sections::list_by_title_for_anchor('section:'.$item['id'], 0, MAXIMUM_ITEMS_PER_SECTION+1, 'compact')) {
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

			// info on related articles
			if(preg_match('/\barticles_by_([a-z_]+)\b/i', $item['options'], $matches))
				$order = $matches[1];
			else
				$order = 'edition';
			$items =& Articles::list_for_anchor_by($order, 'section:'.$item['id'], 0, MAXIMUM_ITEMS_PER_SECTION+1, 'compact');

			if(@count($items)) {
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
			if(Sections::has_option('files_by_title', $anchor, $item))
				$items = Files::list_by_title_for_anchor('section:'.$item['id'], 0, MAXIMUM_ITEMS_PER_SECTION+1, 'compact');
			else
				$items = Files::list_by_date_for_anchor('section:'.$item['id'], 0, MAXIMUM_ITEMS_PER_SECTION+1, 'compact');
			if($items) {
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
			if($items = Comments::list_by_date_for_anchor('section:'.$item['id'], 0, MAXIMUM_ITEMS_PER_SECTION+1, 'compact', Sections::has_option('comments_as_wall', $anchor, $item))) {
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

			// signal continuing sections
			if(count($elements) > MAXIMUM_ITEMS_PER_SECTION)
				$elements[] = Skin::build_link(Sections::get_permalink($item), i18n::s('More pages').MORE_IMG, 'basic');

			// help associates to visit empty sections
			elseif(!count($elements) && Surfer::is_associate())
				$elements[] = Skin::build_link(Sections::get_permalink($item), i18n::s('View the section'), 'shortcut');


			// make a full list
			if(count($elements))
				$box['text'] = '<ul><li>'.implode('</li>'."\n".'<li>', $elements).'</li></ul>'."\n";

			// always make a box
			$text .= Skin::build_box($box['title'], $box['text']);

		}

		// end of processing
		SQL::free($result);
		return $text;
	}

}

?>