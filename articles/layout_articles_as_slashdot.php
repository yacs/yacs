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
	 * the preferred order for items
	 *
	 * @return string to be used in requests to the database
	 *
	 * @see skins/layout.php
	 */
	function items_order() {
		return 'publication';
	}

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

		// flag articles updated recently
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));

		// build a list of articles
		include_once $context['path_to_root'].'articles/article.php';
		include_once $context['path_to_root'].'categories/categories.php';
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'files/files.php';
		include_once $context['path_to_root'].'links/links.php';
		include_once $context['path_to_root'].'overlays/overlay.php';
		while($item =& SQL::fetch($result)) {

			// get the related overlay
			$overlay = Overlay::load($item);

			// get the anchor
			$anchor = Anchors::get($item['anchor']);

			// the url to view this item
			$url =& Articles::get_permalink($item);

			// reset the rendering engine between items
			Codes::initialize($url);

			// use the title to label the link
			if(is_object($overlay) && is_callable(array($overlay, 'get_live_title')))
				$title = $overlay->get_live_title($item);
			else
				$title = Codes::beautify_title($item['title']);

			// reset everything
			$content = $prefix = $label = $suffix = $icon = '';

			// the icon to put aside
			if($item['thumbnail_url']) {
				$icon = $item['thumbnail_url'];
			} elseif(is_object($anchor)) {
				$icon = $anchor->get_thumbnail_url();
			}
			if($icon)
				$icon = '<a href="'.$context['url_to_root'].$url.'"><img src="'.$icon.'" class="right_image" alt="'.encode_field(i18n::s('More')).'" title="'.encode_field(i18n::s('More')).'"'.EOT.'</a>';

			// signal restricted and private articles
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG.' ';
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG.' ';

			// flag articles updated recently
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $now))
				$suffix .= ' '.EXPIRED_FLAG;
			elseif($item['create_date'] >= $dead_line)
				$suffix .= ' '.NEW_FLAG;
			elseif($item['edit_date'] >= $dead_line)
				$suffix .= ' '.UPDATED_FLAG;

			// rating
			if($item['rating_count'] && !(is_object($anchor) && $anchor->has_option('without_rating')))
				$suffix .= Skin::build_link(Articles::get_url($item['id'], 'rate'), Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])), 'basic');

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

			// details
			if(count($details))
				$content .= '<p class="details">'.ucfirst(implode(', ', $details)).'</p>';

			// the full introductory text
			if($item['introduction'])
				$content .= Codes::beautify($item['introduction'], $item['options']);

			// else ask for a teaser
			elseif(!is_object($overlay)) {
				$article =& new Article();
				$article->load_by_content($item);
				$content .= $article->get_teaser('teaser');
			}

			// insert overlay data, if any
			if(is_object($overlay))
				$content .= $overlay->get_text('list', $item);

			// an array of links
			$menu = array();

			// read the article
			$menu = array_merge($menu, array( $url => i18n::s('More') ));

			// info on related files
			if($count = Files::count_for_anchor('article:'.$item['id'], TRUE))
				$details[] = Skin::build_link($url.'#files', sprintf(i18n::ns('%d file', '%d files', $count), $count), 'basic');

			// info on related comments
			if($count = Comments::count_for_anchor('article:'.$item['id'], TRUE)) {
				$link = Comments::get_url('article:'.$item['id'], 'list');
				$menu = array_merge($menu, array( $link => sprintf(i18n::ns('%d comment', '%d comments', $count), $count) ));
			}

			// discuss
			if(Comments::are_allowed($anchor, $item))
				$menu = array_merge($menu, array( Comments::get_url('article:'.$item['id'], 'comment') => i18n::s('Discuss') ));

			// info on related links
			if($count = Links::count_for_anchor('article:'.$item['id'], TRUE))
				$menu = array_merge($menu, array( $url.'#links' => sprintf(i18n::ns('%d link', '%d links', $count), $count) ));

			// trackback
			if($context['with_friendly_urls'] == 'Y')
				$link = 'links/trackback.php/article/'.$item['id'];
			else
				$link = 'links/trackback.php?anchor='.urlencode('article:'.$item['id']);
			$menu = array_merge($menu, array( $link => i18n::s('Reference this page') ));

			// list up to three categories by title, if any
			if($items = Members::list_categories_by_title_for_member('article:'.$item['id'], 0, 3, 'raw')) {
				foreach($items as $id => $attributes) {
					$menu = array_merge($menu, array( Categories::get_permalink($attributes) => $attributes['title'] ));
				}
			}

			// append a menu
			$content .= Skin::build_list($menu, 'menu_bar');

			// insert a complete box
			$text .= Skin::build_box($icon.$prefix.$title.$suffix, $content, 'header1', 'article_'.$item['id']);

		}

		// end of processing
		SQL::free($result);

		return $text;
	}
}

?>