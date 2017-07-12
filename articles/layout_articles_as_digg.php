<?php
/**
 * layout articles as digg do
 *
 * @author Bernard Paques
 * @author Thierry Pinelli (ThierryP)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_digg extends Layout_interface {

	/**
	 * the preferred order for items
	 *
	 * @return string to be used in requests to the database
	 *
	 * @see layouts/layout.php
	 */
	function items_order() {
		return 'rating';
	}

	/**
	 * the preferred number of items for this layout
	 *
	 * @return 20
	 *
	 * @see layouts/layout.php
	 */
	function items_per_page() {
		return 20;
	}

	/**
	 * list articles as digg do
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

		// build a list of articles
		$text = '';
		$item_count = 0;
		include_once $context['path_to_root'].'articles/article.php';
		include_once $context['path_to_root'].'comments/comments.php';
		while($item = SQL::fetch($result)) {

			// get the related overlay, if any
			$overlay = Overlay::load($item, 'article:'.$item['id']);

			// get the anchor
			$anchor = Anchors::get($item['anchor']);

			// the url to view this item
			$url = Articles::get_permalink($item);

			// make a live title
			if(is_object($overlay))
				$title = Codes::beautify_title($overlay->get_text('title', $item));
			else
				$title = Codes::beautify_title($item['title']);

			// next item
			$item_count += 1;

			// section opening
			if($item_count == 1)
				$text .= '<div class="newest">'."\n";

			// reset everything
			$content = $prefix = $label = $suffix = $icon = '';

			// the icon to put aside
			if($item['thumbnail_url'])
				$icon = $item['thumbnail_url'];
			elseif(is_callable(array($anchor, 'get_bullet_url')))
				$icon = $anchor->get_bullet_url();
			if($icon)
				$icon = '<a href="'.$context['url_to_root'].$url.'"><img src="'.$icon.'" class="right_image" alt="" title="'.encode_field(i18n::s('View the page')).'" /></a>';

			// rating
			if($item['rating_count'])
				$rating_label = sprintf(i18n::ns('%s vote', '%s votes', $item['rating_count']), '<span class="big">'.$item['rating_count'].'</span>'.BR);
			else
				$rating_label = i18n::s('No vote');

			// present results
			$digg = '<div class="digg"><div class="votes">'.$rating_label.'</div>';

			// a rating has already been registered
			if(isset($_COOKIE['rating_'.$item['id']]))
				Cache::poison();

			// where the surfer can rate this item
			else
				$digg .= '<div class="rate">'.Skin::build_link(Articles::get_url($item['id'], 'like'), i18n::s('Rate it'), 'basic').'</div>';

			// close digg-like area
			$digg .= '</div>';

			// signal articles to be published
			if(($item['publish_date'] <= NULL_DATE) || ($item['publish_date'] > gmstrftime('%Y-%m-%d %H:%M:%S')))
				$prefix .= DRAFT_FLAG;

			// signal restricted and private articles
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

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

			// the full introductory text
			if(is_object($overlay))
				$content .= Codes::beautify_introduction($overlay->get_text('introduction', $item));
			if($item['introduction'])
				$content .= Codes::beautify_introduction($item['introduction']);

			// else ask for a teaser
			else {
				$article = new Article();
				$article->load_by_content($item);
				$content .= $article->get_teaser('teaser');
			}

			// add details
			$details = array();

			// the author
			if(isset($context['with_author_information']) && ($context['with_author_information'] == 'Y')) {
				if($item['edit_name'] == $item['create_name'])
					$details[] = sprintf(i18n::s('by %s'), ucfirst($item['create_name']));
				else
					$details[] = sprintf(i18n::s('by %s, %s'), ucfirst($item['create_name']), ucfirst($item['edit_name']));
			}

			// the publish date
			if(isset($item['publish_date']) && ($item['publish_date'] > NULL_DATE))
				$details[] = Skin::build_date($item['publish_date']);

			// read the article
			$details[] = Skin::build_link($url, i18n::s('View the page'), 'basic');

			// info on related files
			if($count = Files::count_for_anchor('article:'.$item['id'], TRUE))
				$details[] = Skin::build_link($url.'#_attachments', sprintf(i18n::ns('%d file', '%d files', $count), $count), 'basic');

			// info on related comments
			if($count = Comments::count_for_anchor('article:'.$item['id'], TRUE)) {
				$link = Comments::get_url('article:'.$item['id'], 'list');
				$details[] = Skin::build_link($link, sprintf(i18n::ns('%d comment', '%d comments', $count), $count), 'basic');
			}

			// discuss
			if(Comments::allow_creation($item, $anchor))
				$details[] = Skin::build_link(Comments::get_url('article:'.$item['id'], 'comment'), i18n::s('Discuss'), 'basic');

			// info on related links
			if($count = Links::count_for_anchor('article:'.$item['id'], TRUE))
				$details[] = Skin::build_link($url.'#_attachments', sprintf(i18n::ns('%d link', '%d links', $count), $count), 'basic');

			// link to the anchor page
			if(is_object($anchor) && (!isset($this->focus) || ($item['anchor'] != $this->focus)))
				$details[] = Skin::build_link($anchor->get_url(), $anchor->get_title(), 'basic');

			// list categories by title, if any
			if($items = Members::list_categories_by_title_for_member('article:'.$item['id'], 0, 7, 'raw')) {
				foreach($items as $id => $attributes) {

					// add background color to distinguish this category against others
					if(isset($attributes['background_color']) && $attributes['background_color'])
						$attributes['title'] = '<span style="background-color: '.$attributes['background_color'].'; padding: 0 3px 0 3px;">'.$attributes['title'].'</span>';

					$details[] = Skin::build_link(Categories::get_permalink($attributes), $attributes['title'], 'basic');
				}
			}

			// details
			if(count($details))
				$content .= '<p '.tag::_class('details').'>'.ucfirst(implode(' - ', $details)).'</p>';

			// manage layout
			$content = '<div class="digg_content">'.$digg.$content.'</div>';

			// insert a complete box
			$text .= Skin::build_box($prefix.$title.$suffix, $icon.$content, 'header1', 'article_'.$item['id']);

			// section closing
			if($item_count == 1)
				$text .= '</div>'."\n";
		}

		// end of processing
		SQL::free($result);

		return $text;
	}
}

?>
