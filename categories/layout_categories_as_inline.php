<?php
/**
 * layout categories inline with content
 *
 * With this layout each category is displayed as a section box listing up to seven related pages.
 *
 * @see categories/view.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_categories_as_inline extends Layout_interface {

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

		// we return plain text
		$text = '';

		// process all items in the list
		while($item =& SQL::fetch($result)) {

			// one box per category
			$box['title'] = '';
			$box['text'] = '';

			// use the title to label the link
			$box['title'] = Skin::strip($item['title'], 50);

			// list related categories, if any
			if($items = Categories::list_by_date_for_anchor('category:'.$item['id'], 0, COMPACT_LIST_SIZE, 'compact')) {
				foreach($items as $url => $label) {
					if(is_array($label))
						$label = $label[1];
					$box['text'] .= '<li>'.Skin::build_link($url, $label, 'category').'</li>'."\n";
				}
			}

			// info on related sections
			$items =& Members::list_sections_by_title_for_anchor('category:'.$item['id'], 0, COMPACT_LIST_SIZE);
			if($items) {
				foreach($items as $url => $label) {
					if(is_array($label))
						$label = $label[1];
					$box['text'] .= '<li>'.Skin::build_link($url, $label, 'section').'</li>'."\n";
				}
			}

			// info on related articles
			if(isset($item['options']) && preg_match('/\barticles_by_title\b/i', $item['options']))
				$items =& Members::list_articles_by_title_for_anchor('category:'.$item['id'], 0, COMPACT_LIST_SIZE);
			else
				$items =& Members::list_articles_by_date_for_anchor('category:'.$item['id'], 0, COMPACT_LIST_SIZE);
			if($items) {
				foreach($items as $url => $label) {
					if(is_array($label))
						$label = $label[1];
					$box['text'] .= '<li>'.Skin::build_link($url, $label, 'article').'</li>'."\n";
				}
			}

			// info on related files
			include_once $context['path_to_root'].'files/files.php';
			if(isset($item['options']) && preg_match('/\bfiles_by_title\b/i', $item['options']))
				$items = Files::list_by_title_for_anchor('category:'.$item['id'], 0, COMPACT_LIST_SIZE);
			else
				$items = Files::list_by_date_for_anchor('category:'.$item['id'], 0, COMPACT_LIST_SIZE);
			if($items) {
				foreach($items as $url => $label) {
					if(is_array($label))
						$label = $label[1];
					$box['text'] .= '<li>'.Skin::build_link($url, $label, 'file').'</li>'."\n";
				}
			}

			// info on related comments
			include_once $context['path_to_root'].'comments/comments.php';
			if($items = Comments::list_by_date_for_anchor('category:'.$item['id'], 0, COMPACT_LIST_SIZE, 'compact')) {
				foreach($items as $url => $label) {
					if(is_array($label))
						$label = $label[1];
					$box['text'] .= '<li>'.Skin::build_link($url, $label, 'comment').'</li>'."\n";
				}
			}

			// info on related links
			include_once $context['path_to_root'].'links/links.php';
			if(isset($item['options']) && preg_match('/\blinks_by_title\b/i', $item['options']))
				$items = Links::list_by_title_for_anchor('category:'.$item['id'], 0, COMPACT_LIST_SIZE);
			else
				$items = Links::list_by_date_for_anchor('category:'.$item['id'], 0, COMPACT_LIST_SIZE);
			if($items) {
				foreach($items as $url => $label) {
					if(is_array($label))
						$label = $label[1];
					$box['text'] .= '<li>'.Skin::build_link($url, $label).'</li>'."\n";
				}
			}

			// add a direct link to the category
			if(Surfer::is_associate())
				$box['title'] .= '&nbsp;'.Skin::build_link(Categories::get_permalink($item), MORE_IMG, 'basic');

			// make a full list
			if($box['text'])
				$box['text'] = '<ul>'.$box['text'].'</ul>'."\n";

			// always make a box, to let associates visit the category
			$text .= Skin::build_box($box['title'], $box['text']);

		}

		// end of processing
		SQL::free($result);
		return $text;
	}

}

?>