<?php
/**
 * look for newest headlines in each section
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_sections_as_slashdot extends Layout_interface {

	/**
	 * the preferred number of items for this layout
	 *
	 * @return 50
	 *
	 * @see layouts/layout.php
	 */
	function items_per_page() {
		return 50;
	}

	/**
	 * list articles as slashdot do
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

		// layout in a table
		$text = Skin::table_prefix('wide');

		// 'even' is used for title rows, 'odd' for detail rows
		$class_title = 'odd';
		$class_detail = 'even';

		// build a list of sections
		$family = '';
		include_once $context['path_to_root'].'articles/article.php';
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'links/links.php';
		while($item = SQL::fetch($result)) {

			// change the family
			if($item['family'] != $family) {
				$family = $item['family'];

				// show the family
				$text .= Skin::table_suffix()
					.'<h2><span>'.$family.'&nbsp;</span></h2>'."\n"
					.Skin::table_prefix('wide');

			}

			// document this section
			$content = $prefix = $title = $suffix = $icon = '';
			$menu = array();

			// permalink
			$url = Sections::get_permalink($item);

			// get the anchor
			$anchor = Anchors::get($item['anchor']);

			// get the related overlay, if any
			$overlay = Overlay::load($item, 'section:'.$item['id']);

			// use the title to label the link
			if(is_object($overlay))
				$title = Codes::beautify_title($overlay->get_text('title', $item));
			else
				$title = Codes::beautify_title($item['title']);

			// signal restricted and private sections
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// this is another row of the output
			$text .= '<tr class="'.$class_title.'"><th>'.$prefix.Skin::build_link($url, $title, 'basic', i18n::s('View the section')).$suffix.'</th></tr>'."\n";

			// document most recent page here
			$content = $prefix = $title = $suffix = $icon = '';
			$menu = array();

			// branches of this tree
			$anchors = Sections::get_branch_at_anchor('section:'.$item['id']);

			// get last post
			$article =& Articles::get_newest_for_anchor($anchors, TRUE);
			if($article['id']) {

				// permalink
				$url = Articles::get_permalink($article);

				// get the anchor
				$anchor = Anchors::get($article['anchor']);

				// get the related overlay, if any
				$overlay = Overlay::load($item, 'section:'.$item['id']);

				// use the title to label the link
				if(is_object($overlay))
					$title = Codes::beautify_title($overlay->get_text('title', $article));
				else
					$title = Codes::beautify_title($article['title']);

				// signal restricted and private articles
				if($article['active'] == 'N')
					$prefix .= PRIVATE_FLAG;
				elseif($article['active'] == 'R')
					$prefix .= RESTRICTED_FLAG;

				// the icon to put aside
				if($article['thumbnail_url'])
					$icon = $article['thumbnail_url'];

				// the icon to put aside
				if(!$icon && is_callable(array($anchor, 'get_bullet_url')))
					$icon = $anchor->get_bullet_url();
				if($icon)
					$icon = '<a href="'.$context['url_to_root'].$url.'"><img src="'.$icon.'" class="right_image" alt="" title="'.encode_field(i18n::s('View the page')).'" /></a>';

				// the introductory text
				if($article['introduction'])
					$content .= Codes::beautify_introduction($article['introduction']);

				// else ask for a teaser
				elseif(!is_object($overlay)) {
					$handle = new Article();
					$handle->load_by_content($article);
					$content .= $handle->get_teaser('teaser');
				}

				// insert overlay data, if any
				if(is_object($overlay))
					$content .= $overlay->get_text('list', $article);

				// link to description, if any
				if(trim($article['description']))
					$menu[] = Skin::build_link($url, i18n::s('Read more').MORE_IMG, 'span', i18n::s('View the page'));

				// info on related files
				if($count = Files::count_for_anchor('article:'.$article['id']))
					$menu[] = sprintf(i18n::ns('%d file', '%d files', $count), $count);

				// info on related comments
				if($count = Comments::count_for_anchor('article:'.$article['id']))
					$menu[] = sprintf(i18n::ns('%d comment', '%d comments', $count), $count);

				// discuss
				if(Comments::allow_creation($article, $anchor))
					$menu[] = Skin::build_link(Comments::get_url('article:'.$article['id'], 'comment'), i18n::s('Discuss'), 'span');

				// the main anchor link
				if(is_object($anchor) && (!isset($this->focus) || ($article['anchor'] != $this->focus)))
					$menu[] = Skin::build_link($anchor->get_url(), ucfirst($anchor->get_title()), 'span', i18n::s('View the section'));

				// list up to three categories by title, if any
				if($items =& Members::list_categories_by_title_for_member('article:'.$article['id'], 0, 3, 'raw')) {
					foreach($items as $id => $attributes) {
						$menu[] = Skin::build_link(Categories::get_permalink($attributes), $attributes['title'], 'span');
					}
				}

				// append a menu
				$content .= '<p>'.Skin::finalize_list($menu, 'menu').'</p>';

				// this is another row of the output
				$text .= '<tr class="'.$class_detail.'"><td>'
					.'<h3 class="top"><span>'.Skin::build_link($url, $prefix.$title.$suffix, 'basic', i18n::s('View the page')).'</span></h3>'
					.'<div class="content">'.$icon.$content.'</div>'
					.'</td></tr>'."\n";

			}


		}

		// end of processing
		SQL::free($result);

		$text .= Skin::table_suffix();
		return $text;
	}
}

?>
