<?php
/**
 * layout articles as folded boxes in an accordion
 *
 * @see articles/view.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_accordion extends Layout_interface {

	/**
	 * list pages
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	 *
	 * @see skins/layout.php
	**/
	function layout($result) {
		global $context;

		// allow for multiple calls
		static $accordion_id;
		if(!isset($accordion_id))
			$accordion_id = 1;
		else
			$accordion_id++;

		// empty list
		if(!SQL::count($result)) {
			$output = array();
			return $output;
		}

		// the maximum number of items per article
		if(!defined('MAXIMUM_ITEMS_PER_ARTICLE'))
			define('MAXIMUM_ITEMS_PER_ARTICLE', 100);

		// we return plain text
		$text = '';

		// process all items in the list
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'links/links.php';
		include_once $context['path_to_root'].'overlays/overlay.php';
		while($item = SQL::fetch($result)) {

			// get the related overlay, if any
			$overlay = Overlay::load($item, 'article:'.$item['id']);

			// get the main anchor
			$anchor =& Anchors::get($item['anchor']);

			// one box per page
			$box = array('title' => '', 'text' => '');

			// signal articles to be published
			if(!isset($item['publish_date']) || ($item['publish_date'] <= NULL_DATE) || ($item['publish_date'] > gmstrftime('%Y-%m-%d %H:%M:%S')))
				$box['title'] .= DRAFT_FLAG;

			// signal restricted and private articles
			if($item['active'] == 'N')
				$box['title'] .= PRIVATE_FLAG.' ';
			elseif($item['active'] == 'R')
				$box['title'] .= RESTRICTED_FLAG.' ';

			// use the title to label the link
			if(is_object($overlay))
				$box['title'] .= Codes::beautify_title($overlay->get_text('title', $item));
			else
				$box['title'] .= Codes::beautify_title($item['title']);

			// box content
			$elements = array();

			// complement the title with interesting details
			$details = array();

			// info on related files
			if(Articles::has_option('files_by_title', $anchor, $item))
				$items = Files::list_by_title_for_anchor('article:'.$item['id'], 0, MAXIMUM_ITEMS_PER_ARTICLE+1, 'compact');
			else
				$items = Files::list_by_date_for_anchor('article:'.$item['id'], 0, MAXIMUM_ITEMS_PER_ARTICLE+1, 'compact');
			if($items) {

				// mention the number of items in folded title
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
			if($items = Comments::list_by_date_for_anchor('article:'.$item['id'], 0, MAXIMUM_ITEMS_PER_ARTICLE+1, 'compact', Articles::has_option('comments_as_wall', $anchor, $item))) {

				// mention the number of items in folded title
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
			if(Articles::has_option('links_by_title', $anchor, $item))
				$items = Links::list_by_title_for_anchor('article:'.$item['id'], 0, MAXIMUM_ITEMS_PER_ARTICLE+1, 'compact');
			else
				$items = Links::list_by_date_for_anchor('article:'.$item['id'], 0, MAXIMUM_ITEMS_PER_ARTICLE+1, 'compact');
			if($items) {

				// mention the number of items in folded title
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

			// a link to the page
			$elements[] = Skin::build_link(Articles::get_permalink($item), i18n::s('More').MORE_IMG, 'basic', i18n::s('View the page'));

			// complement title
			if(count($details))
				$box['title'] .= ' <span class="details">('.join(', ', $details).')</span>';

			// insert introduction, if any
			if(is_object($overlay))
				$box['text'] .= Skin::build_block($overlay->get_text('introduction', $item), 'introduction');
			elseif(trim($item['introduction']))
				$box['text'] .= Skin::build_block($item['introduction'], 'introduction');

			// no introduction, display article full content
			else {

				// insert overlay data, if any
				if(is_object($overlay))
					$box['text'] .= $overlay->get_text('box', $item);

				// the content of this box
				$box['text'] .= Codes::beautify($item['description'], $item['options']);

			}

			// make a full list
			if(count($elements))
				$box['text'] .= Skin::finalize_list($elements, 'compact');

			// display all tags
			if($item['tags'])
				$box['text'] .= ' <p class="tags" style="margin-bottom: 0">'.Skin::build_tags($item['tags'], 'article:'.$item['id']).'</p>';

			// if we have an icon for this page, use it
			if(isset($item['thumbnail_url']) && $item['thumbnail_url']) {

				// adjust the class
				$class= '';
				if(isset($context['classes_for_thumbnail_images']))
					$class = 'class="'.$context['classes_for_thumbnail_images'].'" ';

				// build the complete HTML element
				$icon = '<img src="'.$item['thumbnail_url'].'" alt="" title="'.encode_field(Codes::beautify_title($item['title'])).'" '.$class.'/>';

				// make it clickable
				$link = Skin::build_link(Articles::get_permalink($item), $icon, 'basic');

				// put this aside
				$box['text'] = '<table class="decorated"><tr>'
					.'<td class="image">'.$link.'</td>'
					.'<td class="content">'.$box['text'].'</td>'
					.'</tr></table>';

			}

			// always make a box
			$text .= Skin::build_accordion_box($box['title'], $box['text'], 'accordion_article_'.$accordion_id);

		}

		// end of processing
		SQL::free($result);
		return $text;
	}

}

?>
