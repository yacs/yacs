<?php
/**
 * customized layout for articles
 *
 * The two very first articles are displayed side-by-side, with a thumbnail image if one is present.
 *
 * Subsequent articles are listed below an horizontal line, as per decorated layout.
 *
 * @author Bernard Paques
 * @author Thierry Pinelli (ThierryP)
 * @tester Denis Flouriot
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_hardboiled extends Layout_interface {

	/**
	 * the preferred number of items for this layout
	 *
	 * @return 9 - two last articles first, plus 7 other pages
	 *
	 * @see skins/layout.php
	 */
	function items_per_page() {
		return 9;
	}

	/**
	 * list articles
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

		// build a list of articles
		$item_count = 0;
		$items = array();
		include_once $context['path_to_root'].'articles/article.php';
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'links/links.php';
		include_once $context['path_to_root'].'overlays/overlay.php';
		while($item =& SQL::fetch($result)) {

			// next item
			$item_count += 1;

			// get the related overlay, if any
			$overlay = Overlay::load($item);

			// get the anchor
			$anchor =& Anchors::get($item['anchor']);

			// the url to view this item
			$url =& Articles::get_permalink($item);

			// one box per article
			$prefix = $suffix = $icon = '';

			// build a box around two first articles
			if($item_count == 1)
				$text .= '<div class="recent">'."\n";
			elseif($item_count == 3)
				$text .= '</div><br style="clear: left;" />'."\n";

			// layout newest articles
			if($item_count < 3) {

				// style to apply
				switch($item_count) {
				case 1:
					$text .= '<div class="left">';
					break;
				case 2:
					$text .= '<div class="right">';
					break;
				}

				// the icon to put aside
				if($item['thumbnail_url'])
					$icon = $item['thumbnail_url'];
				elseif(is_object($anchor))
					$icon = $anchor->get_thumbnail_url();
				if($icon)
					$text .= '<a href="'.$context['url_to_root'].$url.'" title="'.i18n::s('View the page').'"><img src="'.$icon.'" class="left_image" alt="" /></a>';

				$text .= $this->layout_newest($item, $anchor).'</div>'."\n";

			// layout recent articles
			} else {

				// use the title to label the link
				if(is_object($overlay))
					$title = Codes::beautify_title($overlay->get_text('title', $item));
				else
					$title = Codes::beautify_title($item['title']);

				// flag sticky pages
				if($item['rank'] < 10000)
					$prefix .= STICKY_FLAG;

				// signal locked articles
				if(isset($item['locked']) && ($item['locked'] == 'Y') && Articles::is_owned($item, $anchor))
					$suffix .= ' '.LOCKED_FLAG;

				// flag articles that are dead, or created or updated very recently
				if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
					$prefix .= EXPIRED_FLAG;
				elseif($item['create_date'] >= $context['fresh'])
					$suffix .= ' '.NEW_FLAG;
				elseif($item['edit_date'] >= $context['fresh'])
					$suffix .= ' '.UPDATED_FLAG;

				// signal articles to be published
				if(($item['publish_date'] <= NULL_DATE) || ($item['publish_date'] > gmstrftime('%Y-%m-%d %H:%M:%S')))
					$prefix .= DRAFT_FLAG;

				// signal restricted and private articles
				if($item['active'] == 'N')
					$prefix .= PRIVATE_FLAG;
				elseif($item['active'] == 'R')
					$prefix .= RESTRICTED_FLAG;

				// the introductory text
				$introduction = '';
				if(is_object($overlay))
					$introduction .= Codes::beautify_introduction($overlay->get_text('introduction', $item));
				elseif($item['introduction'])
					$introduction .= Codes::beautify_introduction($item['introduction']);
				else
					$introduction .= Skin::cap(Codes::beautify($item['description'], $item['options']), 70);
				if($introduction) {
					$suffix .= ' -&nbsp;'.$introduction;

					// link to description, if any
					if($item['description'])
						$suffix .= ' '.Skin::build_link($url, MORE_IMG, 'more', i18n::s('View the page')).' ';

				}

				// insert overlay data, if any
				if(is_object($overlay))
					$suffix .= $overlay->get_text('list', $item);

				// next line, except if we already are at the beginning of a line
				if($suffix && !preg_match('/<br\s*\/>$/', rtrim($suffix)))
					$suffix .= BR;

				// append details to the suffix
				$suffix .= '<span class="details">';

				// details
				$details = array();

				// the author
				if(isset($context['with_author_information']) && ($context['with_author_information'] == 'Y')) {
					if($item['create_name'] != $item['edit_name'])
						$details[] = sprintf(i18n::s('by %s, %s'), $item['create_name'], $item['edit_name']);
					else
						$details[] = sprintf(i18n::s('by %s'), $item['create_name']);
				}

				// the last action
				$details[] = Anchors::get_action_label($item['edit_action']).' '.Skin::build_date($item['edit_date']);

				// the number of hits
				if(Surfer::is_logged() && ($item['hits'] > 1))
					$details[] = Skin::build_number($item['hits'], i18n::s('hits'));

				// info on related files
				if($count = Files::count_for_anchor('article:'.$item['id'], TRUE))
					$details[] = sprintf(i18n::ns('%d file', '%d files', $count), $count);

				// info on related links
				if($count = Links::count_for_anchor('article:'.$item['id'], TRUE))
					$details[] = sprintf(i18n::ns('%d link', '%d links', $count), $count);

				// info on related comments
				if($count = Comments::count_for_anchor('article:'.$item['id'], TRUE))
					$details[] = sprintf(i18n::ns('%d comment', '%d comments', $count), $count);

				// rating
				if($item['rating_count'] && !(is_object($anchor) && $anchor->has_option('without_rating')))
					$details[] = Skin::build_link(Articles::get_url($item['id'], 'rate'), Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])), 'basic');

				// combine in-line details
				if(count($details))
					$suffix .= ucfirst(trim(implode(', ', $details)));

				// unusual ranks are signaled to associates
				if(($item['rank'] != 10000) && Articles::is_owned($item, $anchor))
					$suffix .= ' {'.$item['rank'].'} ';

				// list categories by title, if any
				$anchors = array();
				if($members =& Members::list_categories_by_title_for_member('article:'.$item['id'], 0, 5, 'raw')) {
					foreach($members as $id => $attributes) {

						// add background color to distinguish this category against others
						if(isset($attributes['background_color']) && $attributes['background_color'])
							$attributes['title'] = '<span style="background-color: '.$attributes['background_color'].'; padding: 0 3px 0 3px;">'.$attributes['title'].'</span>';

						if($this->layout_variant != 'category:'.$id)
							$anchors[] = Skin::build_link(Categories::get_permalink($attributes), $attributes['title'], 'basic');
					}
				}

				// list section and categories in the suffix
				if(@count($anchors))
					$suffix .= BR.sprintf(i18n::s('In %s'), implode(', ', $anchors));

				// end of details
				$suffix .= '</span>';

				// strip empty details
				$suffix = str_replace(BR.'<span class="details"></span>', '', $suffix);
				$suffix = str_replace('<span class="details"></span>', '', $suffix);

				// the icon to put in the left column
				if($item['thumbnail_url'])
					$icon = $item['thumbnail_url'];

				// or inherit from the anchor
				elseif(is_callable(array($anchor, 'get_bullet_url')))
					$icon = $anchor->get_bullet_url();

				// list all components for this item
				$items[$url] = array($prefix, $title, $suffix, 'article', $icon);

			}

		}

		// extend the #home_south in case of floats
		if(($item_count >= 1) && ($item_count < 3))
			$text .= '<p style="clear: left;">&nbsp;</p></div>'."\n";

		// turn the list to a string
		if(count($items))
			$text .= Skin::build_list($items, 'decorated');

		// end of processing
		SQL::free($result);

		return $text;
	}

	/**
	 * layout one of the newest articles
	 *
	 * @param array the article
	 * @param object the anchor of this article, if any
	 * @return string the rendered text
	**/
	function layout_newest($item, $anchor) {
		global $context;

		// get the related overlay, if any
		$overlay = Overlay::load($item);

		// the url to view this item
		$url =& Articles::get_permalink($item);

		// use the title to label the link
		if(is_object($overlay))
			$title = Codes::beautify_title($overlay->get_text('title', $item));
		else
			$title = Codes::beautify_title($item['title']);

		// initialize variables
		$prefix = $suffix = $text = '';

		// help to jump here
		$prefix .= '<a id="article_'.$item['id'].'"></a>';

		// signal restricted and private articles
		if($item['active'] == 'N')
			$prefix .= PRIVATE_FLAG.' ';
		elseif($item['active'] == 'R')
			$prefix .= RESTRICTED_FLAG.' ';

		// rating
		if($item['rating_count'])
			$suffix .= Skin::build_link(Articles::get_url($item['id'], 'rate'), Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])), 'basic', i18n::s('Rate this page'));

		// use the title as a link to the page
		$text .= '<h3><span>'.$prefix.Skin::build_link($url, $title, 'basic', i18n::s('View the page')).$suffix.'</span></h3>';

		// details
		$details = array();

		// the creator and editor of this article
		if(isset($context['with_author_information']) && ($context['with_author_information'] == 'Y')) {
			if($item['edit_name'] != $item['create_name'])
				$label = sprintf(i18n::s('by %s, %s'), ucfirst($item['create_name']), ucfirst($item['edit_name']));
			else
				$label = sprintf(i18n::s('by %s'), ucfirst($item['create_name']));
			$details[] = $label;
		}

		// poster details
		if(count($details))
			$text .= BR.'<span class="details">'.ucfirst(implode(', ', $details))."</span>\n";

		// the introductory text
		$introduction = '';
		if(is_object($overlay))
			$introduction .= Codes::beautify_introduction($overlay->get_text('introduction', $item));
		elseif($item['introduction'])
			$introduction .= Codes::beautify_introduction($item['introduction']);
		else
			$introduction .= Skin::cap(Codes::beautify($item['description'], $item['options']), 70);
		if($introduction)
		$text .= '<p>'.$introduction.'</p>'."\n";

		// insert overlay data, if any
		if(is_object($overlay))
			$text .= $overlay->get_text('list', $item);

		// read this article
		$text .= '<p class="details">'.Skin::build_link($url, i18n::s('View the page'), 'basic');

		// info on related files
		if($count = Files::count_for_anchor('article:'.$item['id'], TRUE))
			$text .= ' ('.Skin::build_link($url.'#files', sprintf(i18n::ns('%d file', '%d files', $count), $count), 'basic').')';

		// list up to three categories by title, if any
		if($items =& Members::list_categories_by_title_for_member('article:'.$item['id'], 0, 5, 'raw')) {
			$text .= BR;
			$first_category = TRUE;
			foreach($items as $id => $attributes) {

				// add background color to distinguish this category against others
				if(isset($attributes['background_color']) && $attributes['background_color'])
					$attributes['title'] = '<span style="background-color: '.$attributes['background_color'].'; padding: 0 3px 0 3px;">'.$attributes['title'].'</span>';

				if(!$first_category)
					$text .= ',';
				$text .= ' '.Skin::build_link(Categories::get_permalink($attributes), $attributes['title'], 'basic', i18n::s('More pages'));
				$first_category = FALSE;
			}
		}

		$text .= '</p>';

		return $text;
	}

}

?>
