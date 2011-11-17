<?php
/**
 * layout articles as rows in a table
 *
 * The title of each article is also a link to the article itself.
 * A title attribute of the link displays the reference to use to link to the page.
 *
 * @see sections/view.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Thierry Pinelli (ThierryP)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_table extends Layout_interface {

	/**
	 * list articles as rows in a table
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	**/
	function &layout(&$result) {
		global $context;

		// we return some text
		$text = '';

		// empty list
		if(!SQL::count($result))
			return $text;

		// build a list of articles
		$rows = array();
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'links/links.php';
		include_once $context['path_to_root'].'overlays/overlay.php';
		while($item =& SQL::fetch($result)) {

			// get the related overlay
			$overlay = Overlay::load($item, 'article:'.$item['id']);

			// get the anchor
			$anchor =& Anchors::get($item['anchor']);

			// the url to view this item
			$url = Articles::get_permalink($item);

			// reset everything
			$title = $abstract = $author = '';

			// signal articles to be published
			if(!isset($item['publish_date']) || ($item['publish_date'] <= NULL_DATE) || ($item['publish_date'] > gmstrftime('%Y-%m-%d %H:%M:%S')))
				$title .= DRAFT_FLAG;

			// signal restricted and private articles
			if($item['active'] == 'N')
				$title .= PRIVATE_FLAG.' ';
			elseif($item['active'] == 'R')
				$title .= RESTRICTED_FLAG.' ';

			// indicate the id in the hovering popup
			$hover = i18n::s('View the page');
			if(Surfer::is_member())
				$hover .= ' [article='.$item['id'].']';

			// use the title to label the link
			if(is_object($overlay))
				$label = Codes::beautify_title($overlay->get_text('title', $item));
			else
				$label = Codes::beautify_title($item['title']);

			// use the title as a link to the page
			$title .= Skin::build_link($url, $label, 'basic', $hover);

			// signal locked articles
			if(isset($item['locked']) && ($item['locked'] == 'Y') && Articles::is_owned($item, $anchor))
				$title .= ' '.LOCKED_FLAG;

			// flag articles updated recently
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
				$title .= ' '.EXPIRED_FLAG;
			elseif($item['create_date'] >= $context['fresh'])
				$title .= ' '.NEW_FLAG;
			elseif($item['edit_date'] >= $context['fresh'])
				$title .= ' '.UPDATED_FLAG;

			// the icon
			if($item['thumbnail_url'])
				$abstract .= '<a href="'.$context['url_to_root'].$url.'"><img src="'.$item['thumbnail_url'].'" class="right_image" alt="" /></a>';

			// the introductory text
			if(is_object($overlay))
				$abstract .= Codes::beautify_introduction($overlay->get_text('introduction', $item));
			elseif($item['introduction'])
				$abstract .= Codes::beautify_introduction($item['introduction']);

			// insert overlay data, if any
			if(is_object($overlay))
				$abstract .= $overlay->get_text('list', $item);

			// make some abstract out of main text
			if(!$item['introduction'] && ($context['skins_with_details'] == 'Y'))
				$abstract .= Skin::cap(Codes::beautify($item['description'], $item['options']), 50);

			// attachment details
			$details = array();

			// info on related files
			if($count = Files::count_for_anchor('article:'.$item['id'], TRUE)) {
				Skin::define_img('FILES_LIST_IMG', 'files/list.gif');
				$details[] = FILES_LIST_IMG.sprintf(i18n::ns('%d file', '%d files', $count), $count);
			}

			// info on related links
			if($count = Links::count_for_anchor('article:'.$item['id'], TRUE)) {
				Skin::define_img('LINKS_LIST_IMG', 'links/list.gif');
				$details[] = LINKS_LIST_IMG.sprintf(i18n::ns('%d link', '%d links', $count), $count);
			}

			// comments
			if($count = Comments::count_for_anchor('article:'.$item['id'], TRUE)) {
				Skin::define_img('COMMENTS_LIST_IMG', 'comments/list.gif');
				$details[] = Skin::build_link(Comments::get_url('article:'.$item['id'], 'list'), COMMENTS_LIST_IMG.sprintf(i18n::ns('%d comment', '%d comments', $count), $count));
			}

			// describe attachments
			if(count($details))
				$abstract .= '<p class="details">'.join(', ', $details).'</p>';

			// anchors
			$anchors = array();
			if($members =& Members::list_categories_by_title_for_member('article:'.$item['id'], 0, 7, 'raw')) {
				foreach($members as $category_id => $attributes) {

					// add background color to distinguish this category against others
					if(isset($attributes['background_color']) && $attributes['background_color'])
						$attributes['title'] = '<span style="background-color: '.$attributes['background_color'].'; padding: 0 3px 0 3px;">'.$attributes['title'].'</span>';

					$anchors[] = Skin::build_link(Categories::get_permalink($attributes), $attributes['title'], 'basic');
				}
			}
			if(@count($anchors))
				$abstract .= '<p class="tags" style="margin: 3px 0">'.implode(' ', $anchors).'</p>';

			// poster name
			if(isset($context['with_author_information']) && ($context['with_author_information'] == 'Y')) {
				if($item['create_name'])
					$author = Users::get_link($item['create_name'], $item['create_address'], $item['create_id']);
				else
					$author = Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']);
			}

			// more details
			$details =& Articles::build_dates($anchor, $item);

			// rating
			if($item['rating_count'] && !(is_object($anchor) && $anchor->has_option('without_rating')))
				$details[] = Skin::build_link(Articles::get_url($item['id'], 'rate'), Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])), 'basic');

			// page details
			if(count($details))
				$details = '<p class="details">'.join(', ', $details).'</p>';

			// this is another row of the output -- title, abstract, (author,) details
			if(isset($context['with_author_information']) && ($context['with_author_information'] == 'Y'))
				$cells = array($title, $abstract, $author, $details);
			else
				$cells = array($title, $abstract, $details);

			// append this row
			$rows[] = $cells;

		}

		// end of processing
		SQL::free($result);

		// headers
		if(isset($context['with_author_information']) && ($context['with_author_information'] == 'Y'))
			$headers = array(i18n::s('Topic'), i18n::s('Abstract'), i18n::s('Poster'), i18n::s('Details'));
		else
			$headers = array(i18n::s('Topic'), i18n::s('Abstract'), i18n::s('Details'));

		// return a sortable table
		$text .= Skin::table($headers, $rows, 'grid');
		return $text;
	}
}

?>
