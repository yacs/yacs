<?php
/**
 * layout articles as newspaper do
 *
 * The three very first articles are displayed side-by-side, with a thumbnail image if one is present.
 *
 * Subsequent articles are listed below an horizontal line, with text only.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_newspaper extends Layout_interface {

	/**
	 * the preferred order for items
	 *
	 * @see skins/layout.php
	 *
	 * @return string to be used in requests to the database
	 */
	function items_order() {
		return 'publication';
	}

	/**
	 * the preferred number of items for this layout
	 *
	 * @see skins/layout.php
	 */
	function items_per_page() {
		return 100;
	}

	/**
	 * list articles as newspaper do
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	 *
	 * @see skins/layout.php
	**/
	function layout($result) {
		global $context;

		$text = '';

		// empty list
		if(!SQL::count($result))
			return $text;

		// build a list of articles
		$others = array();
		$item_count = 0;
		include_once $context['path_to_root'].'comments/comments.php';
		while($item = SQL::fetch($result)) {

			// permalink
			$url = Articles::get_permalink($item);

			// next item
			$item_count += 1;

			// section opening
			if($item_count == 1) {
				$text .= '<div class="newest">'."\n";
			} elseif($item_count == 2) {
				$text .= '<div class="recent">'."\n";
			}

			// layout first article
			if($item_count == 1) {
				$text .= $this->layout_first($item);

			// layout newest articles
			} elseif($item_count <= 4) {

				// the style to apply
				switch($item_count) {
				case 2:
					$text .= '<div class="west">';
					break;
				case 3:
					$text .= '<div class="center">';
					break;
				case 4:
					$text .= '<div class="east">';
					break;
				}
				$text .= $this->layout_newest($item).'</div>';

			// layout recent articles
			} else
				$others[ $url ] = $this->layout_recent($item);

			// close newest
			if($item_count == 1)
				$text .= '<br style="clear: left;" /></div>'."\n";

			// extend the recent in case of floats
			elseif($item_count == 4)
				$text .= '<br style="clear: left;" /></div>'."\n";

		}

		// not enough items in the database to fill the south; close it here
		if(($item_count == 2) || ($item_count == 3))
			$text .= '</div>'."\n";

		// build the list of other articles
		if(count($others)) {

			// make box
			$text .= Skin::build_box(i18n::s('Previous pages'), Skin::build_list($others, 'decorated'));
		}

		// end of processing
		SQL::free($result);
		return $text;
	}


	/**
	 * layout one of the newest articles
	 *
	 * @param array the article
	 * @return string the rendered text
	**/
	function layout_first($item) {
		global $context;

		// permalink
		$url = Articles::get_permalink($item);

		// get the related overlay, if any
		$overlay = Overlay::load($item, 'article:'.$item['id']);

		// get the anchor
		$anchor =& Anchors::get($item['anchor']);

		// the icon to put aside
		$icon = '';
		if($item['thumbnail_url'])
			$icon = $item['thumbnail_url'];
		if($icon)
			$icon = '<img src="'.$icon.'" class="left_image" alt="" />';

		// use the title to label the link
		if(is_object($overlay))
			$title = Codes::beautify_title($overlay->get_text('title', $item));
		else
			$title = Codes::beautify_title($item['title']);

		// signal restricted and private articles
		if($item['active'] == 'N')
			$title = PRIVATE_FLAG.$title;
		elseif($item['active'] == 'R')
			$title = RESTRICTED_FLAG.$title;

		// rating
		if($item['rating_count'] && !(is_object($anchor) && $anchor->has_option('without_rating')))
			$title .= ' '.Skin::build_link(Articles::get_url($item['id'], 'like'), Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])), 'basic');

		// pack in a block
		$text = '<h2>'.Skin::build_link($url, $icon.$title, 'basic').'</h2>';

		// display all tags
		if($item['tags'])
			$text .= ' <p class="tags" style="margin: 3px 0;">'.Skin::build_tags($item['tags'], 'article:'.$item['id']).'</p>';

		// the introduction
		$text .= '<div style="margin-top: 3px 0;">';

		// the introductory text
		if(is_object($overlay))
			$text .= Codes::beautify_introduction($overlay->get_text('introduction', $item));
		elseif($item['introduction']) {
			$text .= Codes::beautify_introduction($item['introduction'])
				.' '.Skin::build_link($url, i18n::s('More').MORE_IMG, 'basic');
		} else
			$text .= Skin::cap(Codes::beautify($item['description'], $item['options']), 70, $url);

		// end of the introduction
		$text .= '</div>'."\n";

		// insert overlay data, if any
		if(is_object($overlay))
			$text .= $overlay->get_text('list', $item);

		// other details
		$details = array();

		// the author
		$author = '';
		if(isset($context['with_author_information']) && ($context['with_author_information'] == 'Y'))
			$author = sprintf(i18n::s('by %s'), $item['create_name']).' ';

		// date
		$details[] = $author.Skin::build_date($item['publish_date']);

		// info on related files
		if($count = Files::count_for_anchor('article:'.$item['id'], TRUE))
			$details[] = Skin::build_link($url.'#files', sprintf(i18n::ns('%d file', '%d files', $count), $count), 'basic');

		// info on related comments
		$link = Comments::get_url('article:'.$item['id'], 'list');
		if($count = Comments::count_for_anchor('article:'.$item['id'], TRUE))
			$details[] = Skin::build_link($link, sprintf(i18n::ns('%d comment', '%d comments', $count), $count), 'basic');

		// discuss
		if(Comments::allow_creation($anchor, $item))
			$details[] = Skin::build_link(Comments::get_url('article:'.$item['id'], 'comment'), i18n::s('Discuss'), 'basic');

		// info on related links
		if($count = Links::count_for_anchor('article:'.$item['id'], TRUE))
			$details[] = Skin::build_link($url.'#links', sprintf(i18n::ns('%d link', '%d links', $count), $count), 'basic');

		// append a menu
		$text .= Skin::finalize_list($details, 'menu');

		return $text;
	}

	/**
	 * layout one of the newest articles
	 *
	 * @param array the article
	 * @return string the rendered text
	 */
	function layout_newest($item) {
		global $context;

		// permalink
		$url = Articles::get_permalink($item);

		// get the related overlay, if any
		$overlay = Overlay::load($item, 'article:'.$item['id']);

		// get the anchor
		$anchor =& Anchors::get($item['anchor']);

		// the icon to put aside
		$icon = '';
		if($item['thumbnail_url'])
			$icon = $item['thumbnail_url'];
		if($icon)
			$icon = '<img src="'.$icon.'" class="left_image" alt="" />';

		// use the title to label the link
		if(is_object($overlay))
			$title = Codes::beautify_title($overlay->get_text('title', $item));
		else
			$title = Codes::beautify_title($item['title']);

		// signal restricted and private articles
		if($item['active'] == 'N')
			$title = PRIVATE_FLAG.' '.$title;
		elseif($item['active'] == 'R')
			$title = RESTRICTED_FLAG.' '.$title;

		// rating
		if($item['rating_count'] && !(is_object($anchor) && $anchor->has_option('without_rating')))
			$title .= ' '.Skin::build_link(Articles::get_url($item['id'], 'like'), Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])), 'basic');

		// pack in a block
		$text = '<h3>'.Skin::build_link($url, $icon.$title, 'basic').'</h3>';

		// display all tags
		if($item['tags'])
			$text .= ' <p class="tags" style="margin: 3px 0;">'.Skin::build_tags($item['tags'], 'article:'.$item['id']).'</p>';

		// the introduction
		$text .= '<div style="margin: 3px 0;">';

		// the introductory text
		if(is_object($overlay))
			$text .= Codes::beautify_introduction($overlay->get_text('introduction', $item));
		elseif($item['introduction']) {
			$text .= Codes::beautify_introduction($item['introduction'])
				.' '.Skin::build_link($url, i18n::s('More').MORE_IMG, 'basic');
		} else
			$text .= Skin::cap(Codes::beautify($item['description'], $item['options']), 25, $url);

		// end of the introduction
		$text .= '</div>'."\n";

		// insert overlay data, if any
		if(is_object($overlay))
			$text .= $overlay->get_text('list', $item);

		// other details
		$details = array();

		// the author
		$author = '';
		if(isset($context['with_author_information']) && ($context['with_author_information'] == 'Y'))
			$author = sprintf(i18n::s('by %s'), $item['create_name']).' ';

		// date
		$details[] = $author.Skin::build_date($item['publish_date']);

		// info on related files
		if($count = Files::count_for_anchor('article:'.$item['id'], TRUE))
			$details[] = Skin::build_link($url.'#files', sprintf(i18n::ns('%d file', '%d files', $count), $count), 'basic');

		// info on related comments
		$link = Comments::get_url('article:'.$item['id'], 'list');
		if($count = Comments::count_for_anchor('article:'.$item['id'], TRUE))
			$details[] = Skin::build_link($link, sprintf(i18n::ns('%d comment', '%d comments', $count), $count), 'basic');

		// discuss
		if(Comments::allow_creation($anchor, $item))
			$details[] = Skin::build_link(Comments::get_url('article:'.$item['id'], 'comment'), i18n::s('Discuss'), 'basic');

		// info on related links
		if($count = Links::count_for_anchor('article:'.$item['id'], TRUE))
			$details[] = Skin::build_link($url.'#links', sprintf(i18n::ns('%d link', '%d links', $count), $count), 'basic');

		// append a menu
		$text .= Skin::finalize_list($details, 'menu');

		return $text;
	}

	/**
	 * layout one recent article
	 *
	 * @param array the article
	 * @return an array ($prefix, $label, $suffix)
	**/
	function layout_recent($item) {
		global $context;

		// permalink
		$url = Articles::get_permalink($item);

		// get the related overlay, if any
		$overlay = Overlay::load($item, 'article:'.$item['id']);

		// get the anchor
		$anchor =& Anchors::get($item['anchor']);

		// use the title to label the link
		if(is_object($overlay))
			$title = Codes::beautify_title($overlay->get_text('title', $item));
		else
			$title = Codes::beautify_title($item['title']);

		// reset everything
		$prefix = $suffix = '';

		// signal restricted and private articles
		if($item['active'] == 'N')
			$prefix .= PRIVATE_FLAG;
		elseif($item['active'] == 'R')
			$prefix .= RESTRICTED_FLAG;

		// rating
		if($item['rating_count'])
			$suffix .= Skin::build_link(Articles::get_url($item['id'], 'like'), Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])), 'basic');

		// the introductory text
		$introduction = '';
		if(is_object($overlay))
			$introduction = $overlay->get_text('introduction', $item);
		elseif($item['introduction'])
			$introduction = $item['introduction'];
		if($introduction)
			$suffix .= ' -&nbsp;'.Codes::beautify_introduction($introduction);

		// other details
		$details = array();

		// the author
		$author = '';
		if(isset($context['with_author_information']) && ($context['with_author_information'] == 'Y'))
			$author = sprintf(i18n::s('by %s'), $item['create_name']).' ';

		// date
		$details[] = $author.Skin::build_date($item['publish_date']);

		// info on related files
		if($count = Files::count_for_anchor('article:'.$item['id'], TRUE))
			$details[] = Skin::build_link($url.'#files', sprintf(i18n::ns('%d file', '%d files', $count), $count), 'basic');

		// info on related comments
		$link = Comments::get_url('article:'.$item['id'], 'list');
		if($count = Comments::count_for_anchor('article:'.$item['id'], TRUE))
			$details[] = Skin::build_link($link, sprintf(i18n::ns('%d comment', '%d comments', $count), $count), 'basic');

		// discuss
		if(Comments::allow_creation($anchor, $item))
			$details[] = Skin::build_link(Comments::get_url('article:'.$item['id'], 'comment'), i18n::s('Discuss'), 'basic');

		// info on related links
		if($count = Links::count_for_anchor('article:'.$item['id'], TRUE))
			$details[] = Skin::build_link($url.'#links', sprintf(i18n::ns('%d link', '%d links', $count), $count), 'basic');

		// append a menu
		$suffix .= Skin::finalize_list($details, 'menu');

		// display all tags
		if($item['tags'])
			$suffix .= ' <p class="tags" style="margin-top: 3px;">'.Skin::build_tags($item['tags'], 'article:'.$item['id']).'</p>';

		// insert an array of links
		return array($prefix, $title, $suffix);
	}
}

?>
