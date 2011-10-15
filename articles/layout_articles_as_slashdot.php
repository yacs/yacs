<?php
/**
 * layout articles as slashdot do
 *
 * @author Bernard Paques
 * @author Thierry Pinelli (ThierryP)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_slashdot extends Layout_interface {

	/**
	 * the preferred number of items for this layout
	 *
	 * @return 10
	 *
	 * @see skins/layout.php
	 */
	function items_per_page() {
		return 10;
	}

	/**
	 * list articles as slashdot do
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
		include_once $context['path_to_root'].'articles/article.php';
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

			// use the title to label the link
			if(is_object($overlay))
				$title = Codes::beautify_title($overlay->get_text('title', $item));
			else
				$title = Codes::beautify_title($item['title']);

			// reset everything
			$content = $prefix = $label = $suffix = $icon = '';

			// the icon to put aside
			if($item['thumbnail_url'])
				$icon = $item['thumbnail_url'];

			if($icon)
				$icon = '<a href="'.$context['url_to_root'].$url.'"><img src="'.$icon.'" class="right_image" alt="'.encode_field(i18n::s('More')).'" title="'.encode_field(i18n::s('More')).'" /></a>';

			// flag sticky pages
			if($item['rank'] < 10000)
				$prefix .= STICKY_FLAG;

			// signal articles to be published
			if(($item['publish_date'] <= NULL_DATE) || ($item['publish_date'] > $context['now']))
				$prefix .= DRAFT_FLAG;

			// signal restricted and private articles
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG.' ';
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG.' ';

			// signal locked articles
			if(isset($item['locked']) && ($item['locked'] == 'Y') && Articles::is_owned($item, $anchor))
				$suffix .= ' '.LOCKED_FLAG;

			// flag articles updated recently
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
				$suffix .= ' '.EXPIRED_FLAG;
			elseif($item['create_date'] >= $context['fresh'])
				$suffix .= ' '.NEW_FLAG;
			elseif($item['edit_date'] >= $context['fresh'])
				$suffix .= ' '.UPDATED_FLAG;

			// rating
			if($item['rating_count'] && !(is_object($anchor) && $anchor->has_option('without_rating')))
				$suffix .= Skin::build_link(Articles::get_url($item['id'], 'rate'), Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])), 'basic');

			// the full introductory text
			if($item['introduction'])
				$content .= Codes::beautify_introduction($item['introduction']);

			// else ask for a teaser
			elseif(!is_object($overlay)) {
				$article = new Article();
				$article->load_by_content($item);
				$content .= $article->get_teaser('teaser');
			}

			// insert overlay data, if any
			if(is_object($overlay))
				$content .= $overlay->get_text('list', $item);

			// add details
			$details = array();

			// the author
			if(isset($context['with_author_information']) && ($context['with_author_information'] == 'Y')) {
				if($item['edit_name'] == $item['create_name'])
					$details[] = sprintf(i18n::s('by %s'), ucfirst($item['create_name']));
				else
					$details[] = sprintf(i18n::s('by %s, %s'), ucfirst($item['create_name']), ucfirst($item['edit_name']));
			}

			// the modification date
			if($item['edit_date'] > NULL_DATE)
				$details[] = Skin::build_date($item['edit_date']);

			// read the article
			$details[] = Skin::build_link($url, i18n::s('More'), 'basic');

			// info on related files
			if($count = Files::count_for_anchor('article:'.$item['id'], TRUE))
				$details[] = Skin::build_link($url.'#files', sprintf(i18n::ns('%d file', '%d files', $count), $count), 'basic');

			// info on related comments
			if($count = Comments::count_for_anchor('article:'.$item['id'], TRUE)) {
				$link = Comments::get_url('article:'.$item['id'], 'list');
				$details[] = Skin::build_link($link, sprintf(i18n::ns('%d comment', '%d comments', $count), $count), 'basic');
			}

			// discuss
			if(Comments::allow_creation($anchor, $item))
				$details[] = Skin::build_link(Comments::get_url('article:'.$item['id'], 'comment'), i18n::s('Discuss'), 'basic');

			// info on related links
			if($count = Links::count_for_anchor('article:'.$item['id'], TRUE))
				$details[] = Skin::build_link($url.'#links', sprintf(i18n::ns('%d link', '%d links', $count), $count), 'basic');

			// list categories by title, if any
			if($items =& Members::list_categories_by_title_for_member('article:'.$item['id'], 0, 7, 'raw')) {
				foreach($items as $id => $attributes) {

					// add background color to distinguish this category against others
					if(isset($attributes['background_color']) && $attributes['background_color'])
						$attributes['title'] = '<span style="background-color: '.$attributes['background_color'].'; padding: 0 3px 0 3px;">'.$attributes['title'].'</span>';

					$details[] = Skin::build_link(Categories::get_permalink($attributes), $attributes['title'], 'basic');
				}
			}

			// details
			if(count($details))
				$content .= '<p class="details" style="margin-top: 2em;">'.ucfirst(implode(' - ', $details)).'</p>';

			// insert a complete box
			$text .= Skin::build_box(Skin::build_link($url, $prefix.$title.$suffix, 'basic', i18n::s('View the page')), $icon.$content, 'header1', 'article_'.$item['id']);

		}

		// end of processing
		SQL::free($result);
		return $text;
	}
}

?>
