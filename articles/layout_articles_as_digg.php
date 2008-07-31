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
	 * @see skins/layout.php
	 */
	function items_order() {
		return 'rating';
	}

	/**
	 * the preferred number of items for this layout
	 *
	 * @return 20
	 *
	 * @see skins/layout.php
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
	 * @see skins/layout.php
	**/
	function &layout(&$result) {
		global $context;

		// we return some text
		$text = '';

		// empty list
		if(!SQL::count($result))
			return $text;

		// sanity check
		if(!isset($this->layout_variant))
			$this->layout_variant = 'full';

		// flag articles updated recently
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));

		// build a list of articles
		$text = '';
		$item_count = 0;
		include_once $context['path_to_root'].'articles/article.php';
		include_once $context['path_to_root'].'categories/categories.php';
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'files/files.php';
		include_once $context['path_to_root'].'overlays/overlay.php';
		while($item =& SQL::fetch($result)) {

			// get the related overlay, if any
			$overlay = Overlay::load($item);

			// get the anchor
			$anchor = Anchors::get($item['anchor']);

			// the url to view this item
			$url =& Articles::get_permalink($item);

			// reset the rendering engine between items
			Codes::initialize($url);

			// make a live title
			if(is_object($overlay) && is_callable(array($overlay, 'get_live_title')))
				$title = $overlay->get_live_title($item);
			else
				$title = Codes::beautify_title($item['title']);

			// next item
			$item_count += 1;

			// section opening
			if($item_count == 1)
				$text .= '<div id="home_north">'."\n";

			// reset everything
			$content = $prefix = $label = $suffix = $icon = '';

			// the icon to put aside
			if($item['thumbnail_url']) {
				$icon = $item['thumbnail_url'];
			} elseif(is_object($anchor)) {
				$icon = $anchor->get_thumbnail_url();
			}
			if($icon)
				$icon = '<a href="'.$context['url_to_root'].$url.'"><img src="'.$icon.'" class="right_image" alt="'.encode_field(i18n::s('View the page')).'" title="'.encode_field(i18n::s('View the page')).'"'.EOT.'</a>';

			// rating
			if($item['rating_count'])
				$rating_label = sprintf(i18n::ns('%s vote', '%s votes', $item['rating_count']), '<span class="big">'.$item['rating_count'].'</span>'.BR);
			else
				$rating_label = i18n::s('No vote');

			// present results
			$digg = '<div class="digg"><div class="votes">'.$rating_label.'</div>';

			// add a link to let surfer rate this item --don't use cookies, this is cached
			$digg .= '<div class="rate">'.Skin::build_link(Articles::get_url($item['id'], 'rate'), i18n::s('Rate it'), 'basic').'</div>';

			// close digg-like area
			$digg .= '</div>';

			// signal articles to be published
			if(($item['publish_date'] <= NULL_DATE) || ($item['publish_date'] > gmstrftime('%Y-%m-%d %H:%M:%S')))
				$prefix .= DRAFT_FLAG;

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

			// the full introductory text
			if($item['introduction'])
				$content .= Codes::beautify($item['introduction'], $item['options']);

			// else ask for a teaser
			else {
				$article =& new Article();
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

			// signal locked articles
			if(isset($item['locked']) && ($item['locked'] == 'Y'))
				$details[] = LOCKED_FLAG;

			// details
			if(count($details))
				$content .= '<p class="tiny follow_up">'.ucfirst(implode(', ', $details)).'</p>';

			// an array of links
			$menu = array();

			// read the article
			$menu = array_merge($menu, array( $url => i18n::s('View the page') ));

			// add a link to let surfer rate this item
			$menu = array_merge($menu, array( Articles::get_url($item['id'], 'rate') => i18n::s('Rate this page') ));

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

			// link to the anchor page
			if(($this->layout_variant != 'no_anchor') && ($item['anchor'] != $this->layout_variant) && is_object($anchor))
				$menu = array_merge($menu, array( $anchor->get_url() => $anchor->get_title() ));

			// list up to three categories by title, if any
			if($items = Members::list_categories_by_title_for_member('article:'.$item['id'], 0, 3, 'raw')) {
				foreach($items as $id => $attributes) {
					$menu = array_merge($menu, array( Categories::get_permalink($attributes) => $attributes['title'] ));
				}
			}

			// append a menu
			$content .= '<p>'.Skin::build_list($menu, 'menu').'</p>';

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