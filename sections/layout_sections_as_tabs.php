<?php
/**
 * layout sections as a list of tabs
 *
 * This layout is a nice way to drive surfers among a small set of sub-sections.
 *
 * @see sections/sections.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_sections_as_tabs extends Layout_interface {

	/**
	 * list sections
	 *
	 * @param resource the SQL result
	 * @return a string to be displayed
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

		// no hovering label
		$href_title = '';

		// we build an array for the skin::build_tabs() function
		$panels = array();

		// process all items in the list
		while($item = SQL::fetch($result)) {

			// get the main anchor
			$anchor = Anchors::get($item['anchor']);

			// get the related overlay, if any
			$overlay = Overlay::load($item, 'section:'.$item['id']);

			// get the overlay for content of this section, if any
			$content_overlay = NULL;
			if(isset($item['content_overlay']))
				$content_overlay = Overlay::bind($item['content_overlay']);

			// panel content
			$text = '';

			// insert anchor prefix
			if(is_object($anchor))
				$text .= $anchor->get_prefix();

			// the introduction text, if any
			if(is_object($overlay))
				$text .= Skin::build_block($overlay->get_text('introduction', $item), 'introduction');
			elseif(isset($item['introduction']) && trim($item['introduction']))
				$text .= Skin::build_block($item['introduction'], 'introduction');

			// get text related to the overlay, if any
			if(is_object($overlay))
				$text .= $overlay->get_text('view', $item);

			// filter description, if necessary
			if(is_object($overlay))
				$description = $overlay->get_text('description', $item);
			else
				$description = $item['description'];

			// the beautified description, which is the actual page body
			if($description) {

				// use adequate label
				if(is_object($overlay) && ($label = $overlay->get_label('description')))
					$text .= Skin::build_block($label, 'title');

				// beautify the target page
				$text .= Skin::build_block($description, 'description', '', $item['options']);

			}

			// delegate rendering to the overlay, where applicable
			if(is_object($content_overlay) && ($overlaid = $content_overlay->render('articles', 'section:'.$item['id'], $zoom_index))) {
				$text .= $overlaid;

			// regular rendering
			} elseif(!isset($item['articles_layout']) || ($item['articles_layout'] != 'none')) {

				// select a layout
				if(!isset($item['articles_layout']) || !$item['articles_layout']) {
					include_once '../articles/layout_articles.php';
					$layout = new Layout_articles();
				} else
				    $layout = Layouts::new_ ($item['articles_layout'], 'article');

				// avoid links to this page
				if(is_object($layout) && is_callable(array($layout, 'set_variant')))
					$layout->set_variant('section:'.$item['id']);

				// the maximum number of articles per page
				if(is_object($layout))
					$items_per_page = $layout->items_per_page();
				else
					$items_per_page = ARTICLES_PER_PAGE;

				// sort and list articles
				$offset = 0;
				if(preg_match('/\barticles_by_([a-z_]+)\b/i', $item['options'], $matches))
					$order = $matches[1];
				elseif(is_callable(array($layout, 'items_order')))
					$order = $layout->items_order();
				else
					$order = 'edition';

				// create a box
				$box = array('top_bar' => array(), 'text' => '', 'bottom_bar' => array());

				// the command to post a new page
				if(Articles::allow_creation($item, $anchor)) {

					Skin::define_img('ARTICLES_ADD_IMG', 'articles/add.gif');
					$url = 'articles/edit.php?anchor='.urlencode('section:'.$item['id']);
					if(is_object($content_overlay) && ($label = $content_overlay->get_label('new_command', 'articles')))
						;
					else
						$label = ARTICLES_ADD_IMG.i18n::s('Add a page');
					$box['top_bar'] += array( $url => $label );

				}

				// list pages under preparation
				$this_section = new section;
				$this_section->load_by_content($item, $anchor);
				if($this_section->is_assigned()) {
					if(($order == 'publication') && ($items =& Articles::list_for_anchor_by('draft', 'section:'.$item['id'], 0, 20, 'compact'))) {
						if(is_array($items))
							$items = Skin::build_list($items, 'compact');
						$box['top_bar'] += array('_draft' => Skin::build_sliding_box(i18n::s('Draft pages'), $items));
					}
				}

				// top menu
				if($box['top_bar'])
					$box['text'] .= Skin::build_list($box['top_bar'], 'menu_bar');

				// get pages
				$items =& Articles::list_for_anchor_by($order, 'section:'.$item['id'], $offset, $items_per_page, $layout);

				// items in the middle
				if(is_array($items) && isset($item['articles_layout']) && ($item['articles_layout'] == 'compact'))
					$box['text'] .= Skin::build_list($items, 'compact');
				elseif(is_array($items))
					$box['text'] .= Skin::build_list($items, 'decorated');
				elseif(is_string($items))
					$box['text'] .= $items;

				// no navigation bar with alistapart
				if(!isset($item['articles_layout']) || ($item['articles_layout'] != 'alistapart')) {

					// count the number of articles in this section
					if($count = Articles::count_for_anchor('section:'.$item['id'])) {
						if($count > 20)
							$box['bottom_bar'] += array('_count' => sprintf(i18n::ns('%d page', '%d pages', $count), $count));

						// navigation commands for articles
						$home = Sections::get_permalink($item);
						$prefix = Sections::get_url($item['id'], 'navigate', 'articles');
						$box['bottom_bar'] += Skin::navigate($home, $prefix, $count, $items_per_page, 1);

					}

				}

				// bottom menu
				if($box['bottom_bar'])
					$box['text'] .= Skin::build_list($box['bottom_bar'], 'menu_bar');

				// there is some box content
				if($box['text'])
					$text .= $box['text'];

			}

			// layout sub-sections
			if(!isset($item['sections_layout']) || ($item['sections_layout'] != 'none')) {

				// select a layout
				if(!isset($item['sections_layout']) || !$item['sections_layout']) {
					include_once 'layout_sections.php';
					$layout = new Layout_sections();
				} else
				    $layout = Layouts::new_ ($item['sections_layout'], 'section');

				// the maximum number of sections per page
				if(is_object($layout))
					$items_per_page = $layout->items_per_page();
				else
					$items_per_page = SECTIONS_PER_PAGE;

				// build a complete box
				$box = array('top_bar' => array(), 'text' => '', 'bottom_bar' => array());

				// the command to add a new section
				if(Sections::allow_creation($item, $anchor)) {
					Skin::define_img('SECTIONS_ADD_IMG', 'sections/add.gif');
					$box['top_bar'] += array('sections/edit.php?anchor='.urlencode('section:'.$item['id']) => SECTIONS_ADD_IMG.i18n::s('Add a section'));
				}

				// top menu
				if($box['top_bar'])
					$box['text'] .= Skin::build_list($box['top_bar'], 'menu_bar');

				// list items by family then title
				$offset = 0 * $items_per_page;
				$items = Sections::list_by_title_for_anchor('section:'.$item['id'], $offset, $items_per_page, $layout, TRUE);

				// actually render the html for the section
				if(is_array($items) && is_string($item['sections_layout']) && ($item['sections_layout'] == 'compact'))
					$box['text'] .= Skin::build_list($items, 'compact');
				elseif(is_array($items))
					$box['text'] .= Skin::build_list($items, 'decorated');
				elseif(is_string($items))
					$box['text'] .= $items;

				// count the number of subsections
				if($count = Sections::count_for_anchor('section:'.$item['id'])) {

					if($count > 20)
						$box['bottom_bar'] = array('_count' => sprintf(i18n::ns('%d section', '%d sections', $count), $count));

					// navigation commands for sections
					$home = Sections::get_permalink($item);
					$prefix = Sections::get_url($item['id'], 'navigate', 'sections');
					$box['bottom_bar'] += Skin::navigate($home, $prefix, $count, $items_per_page, 1);

				}

				// bottom menu
				if($box['bottom_bar'])
					$box['text'] .= Skin::build_list($box['bottom_bar'], 'menu_bar');

				// there is some box content
				if($box['text'])
					$text .= $box['text'];

			}

			// ensure that the surfer can change content
			if(Sections::allow_modification($item, $anchor)) {

				// view or modify this section
				$menu = array();
				$menu[] = Skin::build_link(Sections::get_permalink($item), i18n::s('View the sub-section'), 'span');
				if(!is_object($overlay) || (!$label = $overlay->get_label('edit_command', 'sections')))
					$label = i18n::s('Edit this sub-section');
				$menu[] = Skin::build_link(Sections::get_url($item['id'], 'edit'), $label, 'span');
				$text .= Skin::finalize_list($menu, 'menu_bar');

			}

			// assemble the full panel
			$panels[] = array('stt'.$item['id'], ucfirst(Skin::strip($item['title'], 30)), 'stc'.$item['id'], $text);

		}

		// format tabs
		$text = Skin::build_tabs($panels);

		// end of processing
		SQL::free($result);
		return $text;
	}

}

?>
