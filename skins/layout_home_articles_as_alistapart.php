<?php
/**
 * layout articles as alistapart do
 *
 * @link http://www.movabletype.org/docs/mttrackback.html TrackBack Technical Specification
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_home_articles_as_alistapart extends Layout_interface {

	/**
	 * the preferred order for items
	 *
	 * @return string to be used in requests to the database
	 *
	 * @see layouts/layout.php
	 */
	function items_order() {
		return 'publication';
	}

	/**
	 * the preferred number of items for this layout
	 *
	 * @return 8 - the last recent article is displayed entirely, plus 7 other pages
	 *
	 * @see layouts/layout.php
	 */
	function items_per_page() {
		return 8;
	}

	/**
	 * list articles as alistapart did
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
		if(!SQL::count($result)) {
			$output = '<p>'.i18n::s('No page to display.');
			if(Surfer::is_associate())
				$output .= ' '.sprintf(i18n::s('Use the %s to populate this server.'), Skin::build_link('help/populate.php', i18n::s('Content Assistant'), 'shortcut'));
			$output .= '</p>';
			return $output;
		}

		// menu at page bottom
		$this->menu = array();

		// build a list of articles
		$item_count = 0;
		$future = array();
		$others = array();
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'links/links.php';
		while($item = SQL::fetch($result)) {

			// get the related overlay
			$overlay = Overlay::load($item, 'article:'.$item['id']);

			// get the main anchor
			$anchor = Anchors::get($item['anchor']);

			// the url to view this item
			$url = Articles::get_permalink($item);

			// build a title
			if(is_object($overlay))
				$title = Codes::beautify_title($overlay->get_text('title', $item));
			else
				$title = Codes::beautify_title($item['title']);

			// initialize variables
			$prefix = $suffix = '';

			// signal restricted and private articles
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// signal locked articles
			if(isset($item['locked']) && ($item['locked'] == 'Y') && Articles::is_owned($item, $anchor))
				$suffix .= LOCKED_FLAG;

			// flag expired articles, and articles updated recently
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
				$suffix = EXPIRED_FLAG.' ';
			elseif($item['create_date'] >= $context['fresh'])
				$suffix = NEW_FLAG.' ';
			elseif($item['edit_date'] >= $context['fresh'])
				$suffix = UPDATED_FLAG.' ';

			// list separately articles to be published
			if($item['publish_date'] <= NULL_DATE) {
				$prefix = DRAFT_FLAG.$prefix;
				$future[$url] = array($prefix, $title, $suffix);

			} elseif($item['publish_date'] > $context['now'])
				$future[$url] = array($prefix, $title, $suffix);

			// next item
			else {
				$item_count += 1;

				// layout the newest article
				if($item_count == 1) {
					$text .= $this->layout_newest($item);

				// display all tags
				if($item['tags'])
					$context['page_tags'] = Skin::build_tags($item['tags']);

				// layout recent articles
				} else
					$others[$url] = array($prefix, $title, $suffix);

			}

		}

		// build the list of future articles
		if(@count($future))
			$this->menu[] = Skin::build_sliding_box(i18n::s('Pages under preparation'), Skin::build_list($future, 'compact'), NULL, TRUE);

		// build the list of other articles
		if(@count($others))
			$this->menu[] = Skin::build_sliding_box(i18n::s('Previous pages'), Skin::build_list($others, 'compact'), NULL, TRUE);

		// talk about it
		if(@count($this->menu))
			$text .= Skin::build_box((strlen($text) > 1024) ? i18n::s('Follow-up') : '', Skin::finalize_list($this->menu, 'menu_bar'));

		// end of processing
		SQL::free($result);

		return $text;
	}
	/**
	 * layout the newest articles
	 *
	 * caution: this function also updates page title directly, and this makes its call non-cacheable
	 *
	 * @param array the article
	 * @return string the rendered text
	**/
	function layout_newest($item) {
		global $context;

		// get the related overlay, if any
		$overlay = Overlay::load($item, 'article:'.$item['id']);

		// get the anchor
		$anchor = Anchors::get($item['anchor']);

		// the url to view this item
		$url = Articles::get_permalink($item);

		// reset the rendering engine between items
		Codes::initialize($url);

		// build a title
		if(is_object($overlay))
			$title = Codes::beautify_title($overlay->get_text('title', $item));
		else
			$title = Codes::beautify_title($item['title']);

		// title prefix & suffix
		$text = $prefix = $suffix = '';

		// flag articles updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$context['fresh'] = gmdate('Y-m-d H:i:s', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));

		// link to permalink
		if(Surfer::is_empowered())
			$title = Skin::build_box_title($title, $url, i18n::s('Permalink'));

		// signal articles to be published
		if(($item['publish_date'] <= NULL_DATE))
			$prefix .= DRAFT_FLAG;

		// draft article
		else if(($item['publish_date'] > NULL_DATE) && ($item['publish_date'] > $context['now']))
			$prefix .= DRAFT_FLAG;

		// signal restricted and private articles
		if($item['active'] == 'N')
			$prefix .= PRIVATE_FLAG.' ';
		elseif($item['active'] == 'R')
			$prefix .= RESTRICTED_FLAG.' ';

		// signal locked articles
		if(isset($item['locked']) && ($item['locked'] == 'Y') && Articles::is_owned($item, $anchor))
			$suffix .= LOCKED_FLAG;

		// flag expired article
		if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
			$suffix .= EXPIRED_FLAG;

		// update page title directly
		$text .= Skin::build_block($prefix.$title.$suffix, 'title');

		// if this article has a specific icon, use it
		if($item['icon_url'])
			$icon = $item['icon_url'];
		elseif($item['anchor'] && ($anchor = Anchors::get($item['anchor'])))
			$icon = $anchor->get_icon_url();

		// if we have a valid image
		if(preg_match('/(.gif|.jpg|.jpeg|.png)$/i', $icon)) {

			// fix relative path
			if(!preg_match('/^(\/|http:|https:|ftp:)/', $icon))
				$icon = $context['url_to_root'].$icon;

			// flush the image on the right
			$text .= '<img src="'.$icon.'" class="right_image" alt="" />';
		}

		// article rating, if the anchor allows for it
		if(!is_object($anchor) || !$anchor->has_option('without_rating')) {

			// report on current rating
			$label = '';
			if($item['rating_count'])
				$label = Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])).' ';
			$label .= i18n::s('Rate this page');

			// allow for rating
			$text .= Skin::build_link(Articles::get_url($item['id'], 'like'), $label, 'basic');
		}

		// the introduction text, if any
		if(is_object($overlay))
			$text .= Skin::build_block($overlay->get_text('introduction', $item), 'introduction');
		else
			$text .= Skin::build_block($item['introduction'], 'introduction');

		// insert overlay data, if any
		if(is_object($overlay))
			$text .= $overlay->get_text('view', $item);

		// the beautified description, which is the actual page body
		if($item['description']) {

			// use adequate label
			if(is_object($overlay) && ($label = $overlay->get_label('description')))
				$text .= Skin::build_block($label, 'title');

			$text .= Skin::build_block($item['description'], 'description', '', $item['options']);

		}

		//
		// list related files
		//

		// if this surfer is an editor of this article, show hidden files as well
		if(Articles::is_assigned($item['id']) || (is_object($anchor) && $anchor->is_assigned()))
			Surfer::empower();

		// build a complete box
		$box['bar'] = array();
		$box['text'] = '';

		// count the number of files in this article
		if($count = Files::count_for_anchor('article:'.$item['id'])) {
			if($count > 20)
				$box['bar'] += array('_count' => sprintf(i18n::ns('%d file', '%d files', $count), $count));

			// list files by date (default) or by title (option files_by_title)
			if(Articles::has_option('files_by', $anchor, $item) == 'title')
				$items = Files::list_by_title_for_anchor('article:'.$item['id'], 0, FILES_PER_PAGE, 'article:'.$item['id']);
			else
				$items = Files::list_by_date_for_anchor('article:'.$item['id'], 0, FILES_PER_PAGE, 'article:'.$item['id']);
			if(is_array($items))
				$box['text'] .= Skin::build_list($items, 'decorated');

			// navigation commands for files
			$prefix = Articles::get_url($item['id'], 'navigate', 'files');
			$box['bar'] += Skin::navigate($url, $prefix, $count, FILES_PER_PAGE, 0);

			// the command to post a new file, if allowed
			if(Files::allow_creation($item, $anchor, 'article')) {
				$link = 'files/edit.php?anchor='.urlencode('article:'.$item['id']);
				$box['bar'] += array( $link => i18n::s('Add a file') );
			}

			if(is_array($box['bar']))
				$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');
		}

		// actually render the html for this box
		if($box['text'])
			$text .= Skin::build_box(i18n::s('Files'), $box['text'], 'header1', 'files');

		//
		// bottom page menu
		//

		// discuss this page, if the index page can be commented, and comments are accepted at the article level
		if(Comments::allow_creation($item, $anchor))
			$this->menu[] = Skin::build_link(Comments::get_url('article:'.$item['id'], 'comment'), i18n::s('Post a comment'), 'span');

		// info on related comments
		if($count = Comments::count_for_anchor('article:'.$item['id']))
			$this->menu[] = Skin::build_link(Comments::get_url('article:'.$item['id'], 'list'), sprintf(i18n::ns('%d comment', '%d comments', $count), $count), 'span');

		// new links are accepted at the index page and at the article level
		if(Links::allow_trackback())
			$this->menu[] = Skin::build_link('links/trackback.php?anchor='.urlencode('article:'.$item['id']), i18n::s('Reference this page'), 'span');

		// info on related links
		if($count = Links::count_for_anchor('article:'.$item['id']))
			$this->menu[] = Skin::build_link($url.'#_attachments', sprintf(i18n::ns('%d link', '%d links', $count), $count), 'span');

		// new files are accepted at the index page and at the article level
		if(is_object($anchor) && $anchor->has_option('with_files')
			 && !($anchor->has_option('no_files') || preg_match('/\bno_files\b/i', $item['options']))) {

			// add a file
			if(Files::allow_creation($item, $anchor, 'article')) {
				if($context['with_friendly_urls'] == 'Y')
					$link = 'files/edit.php/article/'.$item['id'];
				else
					$link = 'files/edit.php?anchor='.urlencode('article:'.$item['id']);
				$this->menu[] = Skin::build_link($link, i18n::s('Add a file'), 'span');
			}

		}

		// modify this page
		if(Surfer::is_empowered())
			$this->menu[] = Skin::build_link(Articles::get_url($item['id'], 'edit'), i18n::s('Edit'), 'span');

		// view permalink
		if(Surfer::is_empowered())
			$this->menu[] = Skin::build_link($url, i18n::s('Permalink'), 'span');

		// insert overlay data, if any
		if(is_object($overlay))
			$text .= $overlay->get_text('trailer', $item);

		// add trailer information from this item, if any
		if(isset($item['trailer']) && trim($item['trailer']))
			$text .= Codes::beautify($item['trailer']);

		// returned the formatted content
		return $text;
	}
}

?>
