<?php
/**
 * layout articles as alistapart do
 *
 * @link http://www.movabletype.org/docs/mttrackback.html TrackBack Technical Specification
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @author Thierry Pinelli (ThierryP)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_alistapart extends Layout_interface {

	/**
	 * the preferred number of items for this layout
	 *
	 * @return int 100 - this layout has no navigation bar
	 *
	 * @see skins/layout.php
	 */
	function items_per_page() {
		return 100;
	}

	/**
	 * list articles as alistapart did
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

		// load localized strings
		i18n::bind('articles');

		// current time
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// flag articles updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));

		// build a list of articles
		$text = '';
		$item_count = 0;
		$future = array();
		$others = array();
		include_once $context['path_to_root'].'overlays/overlay.php';
		while($item =& SQL::fetch($result)) {

			// get the related overlay
			$overlay = Overlay::load($item);

			// get the main anchor
			$anchor = Anchors::get($item['anchor']);

			// the url to view this item
			$url = Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']);

			// reset the rendering engine between items
			Codes::initialize($url);

			// build a title
			if(is_object($overlay) && is_callable(array($overlay, 'get_live_title')))
				$title = $overlay->get_live_title($item);
			else
				$title = Codes::beautify_title($item['title']);

			// initialize variables
			$prefix = $suffix = '';

			// signal locked articles
			if(isset($item['locked']) && ($item['locked'] == 'Y'))
				$prefix .= LOCKED_FLAG;

			// signal restricted and private articles
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG.' ';
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG.' ';

			// flag expired articles, and articles updated recently
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $now))
				$suffix = EXPIRED_FLAG.' ';
			elseif($item['create_date'] >= $dead_line)
				$suffix = NEW_FLAG.' ';
			elseif($item['edit_date'] >= $dead_line)
				$suffix = UPDATED_FLAG.' ';

			// list separately articles to be published
			if($item['publish_date'] <= NULL_DATE) {
				$prefix = DRAFT_FLAG.$prefix;
				$future[$url] = array($prefix, $title, $suffix);

			} elseif($item['publish_date'] > $now)
				$future[$url] = array($prefix, $title, $suffix);

			// next item
			else {
				$item_count += 1;

				// layout the newest article
				if($item_count == 1)
					$text .= $this->layout_newest($item);

				// layout recent articles
				else
					$others[$url] = array($prefix, $title, $suffix);

			}

		}

		// build the list of future articles
		if(@count($future))
			$text = Skin::build_box(i18n::s('Pages under preparation'), Skin::build_list($future, 'compact')).$text;

		// build the list of other articles
		if(@count($others))
			$text .= Skin::build_box(i18n::s('Previous pages'), Skin::build_list($others, 'compact'));

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
		$overlay = Overlay::load($item);

		// get the anchor
		$anchor = Anchors::get($item['anchor']);

		// the url to view this item
		$url = Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']);

		// reset the rendering engine between items
		Codes::initialize($url);

		// build a title
		if(is_object($overlay) && is_callable(array($overlay, 'get_live_title')))
			$title = $overlay->get_live_title($item);
		else
			$title = Codes::beautify_title($item['title']);

		// title prefix & suffix
		$text = $prefix = $suffix = '';
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// flag articles updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));

		// link to permalink
		if(Surfer::is_empowered())
			$title =& Skin::build_box_title($title, $url, i18n::s('Permalink'));

		// signal articles to be published
		if(($item['publish_date'] <= NULL_DATE))
			$prefix .= DRAFT_FLAG;

		// draft article
		else if(($item['publish_date'] > NULL_DATE) && ($item['publish_date'] > $now))
			$prefix .= DRAFT_FLAG;

		// signal locked articles
		if(isset($item['locked']) && ($item['locked'] == 'Y'))
			$prefix .= LOCKED_FLAG;

		// signal restricted and private articles
		if($item['active'] == 'N')
			$prefix .= PRIVATE_FLAG.' ';
		elseif($item['active'] == 'R')
			$prefix .= RESTRICTED_FLAG.' ';

		// flag expired article
		if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $now))
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
			$text .= '<img src="'.$icon.'" class="right_image" alt=""'.EOT;
		}

		// article rating, if the anchor allows for it
		if(is_object($anchor) && $anchor->has_option('with_rating')) {

			// report on current rating
			$label = '';
			if($item['rating_count'])
				$label = Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])).' ';
			$label .= i18n::s('Rate this page');

			// allow for rating
			$text .= Skin::build_link(Articles::get_url($item['id'], 'rate'), $label, 'basic');
		}

		// the introduction text, if any
		if($item['introduction'])
			$text .= Skin::build_block($item['introduction'], 'introduction');
		else
			$text .= BR;

		// insert overlay data, if any
		if(is_object($overlay))
			$text .= $overlay->get_text('view', $item);

		// the beautified description, which is the actual page body
		if(trim($item['description'])) {
			$description = Codes::beautify($item['description'], $item['options']);

			// use adequate label
			if(is_object($overlay) && ($label = $overlay->get_label('description')))
				$text .= Skin::build_block($label, 'title').'<p>'.$description."</p>\n";
			else
				$text .= $description."\n";
		}

		//
		// list related files
		//

		// if this surfer is an editor of this article, show hidden files as well
		if(Articles::is_assigned($item['id']) || (is_object($anchor) && $anchor->is_editable()))
			Surfer::empower();

		// build a complete box
		$box['bar'] = array();
		$box['text'] = '';

		// count the number of files in this article
		if($count = Files::count_for_anchor('article:'.$item['id'])) {
			if($count > FILES_PER_PAGE)
				$box['bar'] = array_merge($box['bar'], array('_count' => sprintf(i18n::ns('1&nbsp;file', '%d&nbsp;files', $count), $count)));

			// list files by date (default) or by title (option files_by_title)
			include_once $context['path_to_root'].'files/files.php';
			if(preg_match('/\bfiles_by_title\b/i', $item['options']))
				$items = Files::list_by_title_for_anchor('article:'.$item['id'], 0, FILES_PER_PAGE, 'no_anchor');
			else
				$items = Files::list_by_date_for_anchor('article:'.$item['id'], 0, FILES_PER_PAGE, 'no_anchor');
			if(is_array($items))
				$box['text'] .= Skin::build_list($items, 'decorated');

			// navigation commands for files
			$prefix = Articles::get_url($item['id'], 'navigate', 'files');
			$box['bar'] = array_merge($box['bar'], Skin::navigate($url, $prefix, $count, FILES_PER_PAGE, 0));

			// the command to post a new file, if allowed
			if(Files::are_allowed($anchor, $item)) {
				$link = 'files/edit.php?anchor='.urlencode('article:'.$item['id']);
				$box['bar'] = array_merge($box['bar'], array( $link => i18n::s('Upload a file') ));
			}

			if(is_array($box['bar']) && ($context['skin_variant'] != 'mobile'))
				$box['text'] .= Skin::build_list($box['bar'], 'menu_bar');
		}

		// actually render the html for this box
		if($box['text'])
			$text .= Skin::build_box(i18n::s('Related files'), $box['text'], 'header1', 'files');

		//
		// bottom page menu
		//

		// a page menu
		$menu = array();

		// discuss this page, if the index page can be commented, and comments are accepted at the article level
		include_once $context['path_to_root'].'comments/comments.php';
		if(Comments::are_allowed($anchor, $item))
			$menu = array_merge($menu, array(Comments::get_url('article:'.$item['id'], 'comment') => i18n::s('Add a comment')));

		// info on related comments
		if($count = Comments::count_for_anchor('article:'.$item['id']))
			$menu = array_merge($menu, array(Comments::get_url('article:'.$item['id'], 'list') => sprintf(i18n::ns('1&nbsp;comment', '%d&nbsp;comments', $count), $count)));

		// new links are accepted at the index page and at the article level
		if(is_object($anchor) && $anchor->has_option('with_links')
			 && !($anchor->has_option('no_links') || preg_match('/\bno_links\b/i', $item['options']))) {

			// trackback
			if($context['with_friendly_urls'] == 'Y')
				$link = 'links/trackback.php/article/'.$item['id'];
			else
				$link = 'links/trackback.php?anchor='.urlencode('article:'.$item['id']);
			$menu = array_merge($menu, array($link => i18n::s('Trackback')));

		}

		// info on related links
		if($context['with_friendly_urls'] == 'Y')
			$link = 'articles/view.php/'.$item['id'].'/links/1';
		else
			$link = 'articles/view.php?id='.urlencode($item['id']).'&amp;links=1';
		include_once $context['path_to_root'].'links/links.php';
		if($count = Links::count_for_anchor('article:'.$item['id']))
			$menu = array_merge($menu, array($link => sprintf(i18n::ns('1&nbsp;link', '%d&nbsp;links', $count), $count)));

		// new files are accepted at the index page and at the article level
		if(is_object($anchor) && $anchor->has_option('with_files')
			 && !($anchor->has_option('no_files') || preg_match('/\bno_files\b/i', $item['options']))) {

			// attach a file
			if(Files::are_allowed($anchor, $item)) {
				if($context['with_friendly_urls'] == 'Y')
					$link = 'files/edit.php/article/'.$item['id'];
				else
					$link = 'files/edit.php?anchor='.urlencode('article:'.$item['id']);
				$menu = array_merge($menu, array($link => i18n::s('Upload a file')));
			}

		}

		// modify this page
		if(Surfer::is_empowered())
			$menu = array_merge($menu, array( Articles::get_url($item['id'], 'edit') => i18n::s('Edit the page') ));

		// view permalink
		if(Surfer::is_empowered())
			$menu = array_merge($menu, array( $url => i18n::s('Permalink') ));

		// talk about it
		if(@count($menu))
			$text .= Skin::build_box((strlen($text) > 1024) ? i18n::s('Follow-up') : '', Skin::build_list($menu, 'menu_bar'));

		// returned the formatted content
		return $text;
	}
}

?>