<?php
/**
 * layout articles as a list of tabs
 *
 * This layout is a nice way to present structured documentation.
 *
 * @see articles/articles.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_tabs extends Layout_interface {

	/**
	 * list articles
	 *
	 * @param resource the SQL result
	 * @return a string to be displayed
	 *
	 * @see skins/layout.php
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
		include_once $context['path_to_root'].'overlays/overlay.php';
		while($item = SQL::fetch($result)) {

			// get the main anchor
			$anchor =& Anchors::get($item['anchor']);

			// get the related overlay, if any
			$overlay = Overlay::load($item, 'article:'.$item['id']);

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

			// list files only to people able to change the page
			if(Articles::allow_modification($item, $anchor))
				$embedded = NULL;
			else
				$embedded = Codes::list_embedded($item['description']);

			// build a complete box
			$box = array('bar' => array(), 'text' => '');

			// count the number of files in this article
			if($count = Files::count_for_anchor('article:'.$item['id'], FALSE, $embedded)) {
				if($count > 20)
					$box['bar'] += array('_count' => sprintf(i18n::ns('%d file', '%d files', $count), $count));

				// list files by date (default) or by title (option files_by_title)
				$offset = ($zoom_index - 1) * FILES_PER_PAGE;
				if(Articles::has_option('files_by', $anchor, $item) == 'title')
					$items = Files::list_by_title_for_anchor('article:'.$item['id'], 0, 300, 'article:'.$item['id'], $embedded);
				else
					$items = Files::list_by_date_for_anchor('article:'.$item['id'], 0, 300, 'article:'.$item['id'], $embedded);

				// actually render the html
				if(is_array($items))
					$box['text'] .= Skin::build_list($items, 'decorated');
				elseif(is_string($items))
					$box['text'] .= $items;

				// the command to post a new file
				if(Files::allow_creation($anchor, $item, 'article')) {
					Skin::define_img('FILES_UPLOAD_IMG', 'files/upload.gif');
					$box['bar'] += array('files/edit.php?anchor='.urlencode('article:'.$item['id']) => FILES_UPLOAD_IMG.i18n::s('Add a file'));
				}

			}

			// some files have been attached to this page
			if(($page == 1) && ($count > 1)) {

				// the command to download all files
				$link = 'files/fetch_all.php?anchor='.urlencode('article:'.$item['id']);
				if($count > 20)
					$label = i18n::s('Zip 20 first files');
				else
					$label = i18n::s('Zip all files');
				$box['bar'] += array( $link => $label );

			}

			// there is some box content
			if($box['text'])
				$text .= Skin::build_content('files', i18n::s('Files'), $box['text'], $box['bar']);

			// list of comments
			$title_label = '';
			if(is_object($anchor))
				$title_label = ucfirst($overlay->get_label('list_title', 'comments'));
			if(!$title_label)
				$title_label = i18n::s('Comments');

			// no layout yet
			$layout = NULL;

			// we have a wall, or not
			$reverted = Articles::has_option('comments_as_wall', $anchor, $item);

			// label to create a comment
			$add_label = '';
			if(is_object($overlay))
				$add_label = $overlay->get_label('new_command', 'comments');
			if(!$add_label)
				$add_label = i18n::s('Post a comment');

			// get a layout from anchor
			$layout =& Comments::get_layout($anchor, $item);

			// provide author information to layout
			if(is_object($layout) && isset($item['create_id']) && $item['create_id'])
				$layout->set_variant('user:'.$item['create_id']);

			// the maximum number of comments per page
			if(is_object($layout))
				$items_per_page = $layout->items_per_page();
			else
				$items_per_page = COMMENTS_PER_PAGE;

			// the first comment to list
			$offset = 0;
			if(is_object($layout) && method_exists($layout, 'set_offset'))
				$layout->set_offset($offset);

			// build a complete box
			$box = array('bar' => array(), 'prefix_bar' => array(), 'text' => '');

			// feed the wall
			if(Comments::allow_creation($anchor, $item) && $reverted)
				$box['text'] .= Comments::get_form('article:'.$item['id']);

			// a navigation bar for these comments
			if($count = Comments::count_for_anchor('article:'.$item['id'])) {
				if($count > 20)
					$box['bar'] += array('_count' => sprintf(i18n::ns('%d comment', '%d comments', $count), $count));

				// list comments by date
				$items = Comments::list_by_date_for_anchor('article:'.$item['id'], $offset, $items_per_page, $layout, $reverted);

				// actually render the html
				if(is_array($items))
					$box['text'] .= Skin::build_list($items, 'rows');
				elseif(is_string($items))
					$box['text'] .= $items;

				// navigation commands for comments
				$prefix = Comments::get_url('article:'.$item['id'], 'navigate');
				$box['bar'] = array_merge($box['bar'],
					Skin::navigate(NULL, $prefix, $count, $items_per_page, $zoom_index));
			}

			// new comments are allowed
			if(Comments::allow_creation($anchor, $item) && !$reverted) {
				Skin::define_img('COMMENTS_ADD_IMG', 'comments/add.gif');
				$box['bar'] += array( Comments::get_url('article:'.$item['id'], 'comment') => array('', COMMENTS_ADD_IMG.$add_label, '', 'basic', '', i18n::s('Post a comment')));

				// also feature this command at the top
				if($count > 20)
					$box['prefix_bar'] = array_merge($box['prefix_bar'], array( Comments::get_url('article:'.$item['id'], 'comment') => array('', COMMENTS_ADD_IMG.$add_label, '', 'basic', '', i18n::s('Post a comment'))));

			}

			// ensure that the surfer can change content
			if(Articles::allow_modification($item, $anchor)) {

				// view or modify this section
				$menu = array();
				$box['bar'] += array(Articles::get_permalink($item) => i18n::s('View the page'));
				if(!is_object($overlay) || (!$label = $overlay->get_label('edit_command', 'articles')))
					$label = i18n::s('Edit this page');
				$box['bar'] += array(Articles::get_url($item['id'], 'edit') => $label);

			}

			// show commands
			if(count($box['bar'])) {

				// commands before the box
				$box['text'] = Skin::build_list($box['prefix_bar'], 'menu_bar').$box['text'];

				// append the menu bar at the end
				$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');

			}

			// build a box
			if($box['text']) {

				// put a title if there are other titles or if more than 2048 chars
				$title = '';
				if(preg_match('/(<h1|<h2|<h3|<table|\[title|\[subtitle)/i', $context['text'].$text) || (strlen($context['text'].$text) > 2048))
					$title = $title_label;

				// insert a full box
				$text .= Skin::build_box($title, $box['text'], 'header1', 'comments');
			}

			// assemble the full panel
			$panels[] = array('att'.$item['id'], ucfirst(Skin::strip($item['title'], 30)), 'atc'.$item['id'], $text);

		}

		// there is some box content
		if(trim($box['text']))
			$text .= $box['text'];

		// format tabs
		$text = Skin::build_tabs($panels);

		// end of processing
		SQL::free($result);
		return $text;
	}

}

?>
