<?php
/**
 * layout articles as digg do
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_home_articles_as_digg extends Layout_interface {

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

		// empty list
		if(!SQL::count($result)) {
			$label = i18n::s('No page to display.');
			if(Surfer::is_associate())
				$label .= ' '.sprintf(i18n::s('Use the %s to populate this server.'), Skin::build_link('help/populate.php', i18n::s('Content Assistant'), 'shortcut'));
			$output = '<p>'.$label.'</p>';
			return $output;
		}

		// build a list of articles
		$text = '';
		$item_count = 0;
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'links/links.php';
		while($item = SQL::fetch($result)) {

			// permalink
			$url = Articles::get_permalink($item);

			// get the anchor
			$anchor = Anchors::get($item['anchor']);

			// get the related overlay, if any
			$overlay = Overlay::load($item, 'article:'.$item['id']);

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
				$icon = '<a href="'.$context['url_to_root'].$url.'"><img src="'.$icon.'" class="right_image" alt="'.encode_field(i18n::s('View the page')).'" title="'.encode_field(i18n::s('View the page')).'" /></a>';

			// signal restricted and private articles
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// flag articles updated recently
			if($item['create_date'] >= $context['fresh'])
				$suffix .= ' '.NEW_FLAG;
			elseif($item['edit_date'] >= $context['fresh'])
				$suffix .= ' '.UPDATED_FLAG;

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
			$details[] = Skin::build_date($item['publish_date']);

			// rating
			$rating_label = '';
			if($item['rating_count'])
				$rating_label = Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])).' '.sprintf(i18n::ns('%d rating', '%d ratings', $item['rating_count']), $item['rating_count']).' ';

			// add a link to let surfer rate this item
			if(is_object($anchor) && !$anchor->has_option('without_rating')) {
				if(!$item['rating_count'])
					$rating_label .= i18n::s('Rate this page');
				$rating_label = Skin::build_link(Articles::get_url($item['id'], 'like'), $rating_label, 'basic', i18n::s('Rate this page'));
			}

			// display current rating, and allow for rating
			$details[] = $rating_label;

			// details
			if(count($details))
				$content .= '<p '.tag::_class('details').'>'.ucfirst(implode(', ', $details)).'</p>';

			// the full introductory text
			if($item['introduction'])
				$content .= Codes::beautify($item['introduction'], $item['options']);

			// else ask for a teaser
			elseif(!is_object($overlay)) {
				include_once $context['path_to_root'].'articles/article.php';
				$article = new Article();
				$article->load_by_content($item);
				$content .= $article->get_teaser('teaser');
			}

			// insert overlay data, if any
			if(is_object($overlay))
				$content .= $overlay->get_text('list', $item);

			// an array of links
			$menu = array();

			// rate the article
			$menu = array_merge($menu, array( Articles::get_url($item['id'], 'like') => i18n::s('Rate this page') ));

			// read the article
			$menu = array_merge($menu, array( $url => i18n::s('Read more') ));

			// info on related files
			if($count = Files::count_for_anchor('article:'.$item['id'], TRUE))
				$details[] = Skin::build_link($url.'#_attachments', sprintf(i18n::ns('%d file', '%d files', $count), $count), 'basic');

			// info on related comments
			if($count = Comments::count_for_anchor('article:'.$item['id'], TRUE)) {
				$link = Comments::get_url('article:'.$item['id'], 'list');
				$menu = array_merge($menu, array( $link => sprintf(i18n::ns('%d comment', '%d comments', $count), $count) ));
			}

			// discuss
			if(Comments::allow_creation($item, $anchor))
				$menu = array_merge($menu, array( Comments::get_url('article:'.$item['id'], 'comment') => i18n::s('Discuss') ));

			// info on related links
			if($count = Links::count_for_anchor('article:'.$item['id'], TRUE))
				$menu = array_merge($menu, array( $url.'#_attachments' => sprintf(i18n::ns('%d link', '%d links', $count), $count) ));

			// trackback
			if(Links::allow_trackback())
				$menu = array_merge($menu, array( 'links/trackback.php?anchor='.urlencode('article:'.$item['id']) => i18n::s('Reference this page') ));

			// link to the anchor page
			if(is_object($anchor))
				$menu = array_merge($menu, array( $anchor->get_url() => $anchor->get_title() ));

			// list up to three categories by title, if any
			if($items = Members::list_categories_by_title_for_member('article:'.$item['id'], 0, 3, 'raw')) {
				foreach($items as $id => $attributes) {
					$menu = array_merge($menu, array( Categories::get_permalink($attributes) => $attributes['title'] ));
				}
			}

			// append a menu
			$content .= Skin::build_list($menu, 'menu_bar');

			// insert a complete box
			$text .= Skin::build_box($icon.$prefix.Codes::beautify_title($item['title']).$suffix, $content, 'header1', 'article_'.$item['id']);

			// section closing
			if($item_count == 1)
				$text .= '</div>'."\n";
		}

		// end of processing
		SQL::free($result);

		// add links to archives
		$anchor = Categories::get(i18n::c('monthly'));
		if(isset($anchor['id']) && ($items = Categories::list_by_date_for_anchor('category:'.$anchor['id'], 0, COMPACT_LIST_SIZE, 'compact')))
			$text .= Skin::build_box(i18n::s('Previous pages'), Skin::build_list($items, 'menu_bar'));

		return $text;
	}
}

?>
