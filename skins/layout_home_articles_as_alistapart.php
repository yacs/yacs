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
	 * @see skins/layout.php
	 */
	function items_order() {
		return 'publication';
	}

	/**
	 * the preferred number of items for this layout
	 *
	 * @return 8 - the last recent article is displayed entirely, plus 7 other pages
	 *
	 * @see skins/layout.php
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
	 * @see skins/layout.php
	**/
	function &layout(&$result) {
		global $context;

		// empty list
		if(!SQL::count($result)) {
			$output = '<p>'.i18n::s('No article has been published so far.');
			if(Surfer::is_associate())
				$output .= ' '.sprintf(i18n::s('Use the %s to populate this server.'), Skin::build_link('help/populate.php', i18n::s('Content Assistant'), 'shortcut'));
			$output .= '</p>';
			return $output;
		}

		// flag articles updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));

		// build a list of articles
		$text = '';
		$item_count = 0;
		$others = array();
		include_once $context['path_to_root'].'comments/comments.php';
		while($item =& SQL::fetch($result)) {

			// next item
			$item_count += 1;

			// layout the newest article
			if($item_count == 1)
				$text .= $this->layout_newest($item);

			// layout recent articles
			else
				$others[Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name'])] = $item['title'];

		}

		// a link to the index pages for articles
		$others['articles/'] = i18n::s('All pages').MORE_IMG;

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
	 * @param array the article
	 * @return string the rendered text
	**/
	function layout_newest($item) {
		global $context;

		// permalink
		$url = Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']);

		// reset the rendering engine between items
		Codes::initialize($url);

		// the title
		$text = Skin::build_block($item['title'], 'page_title', 'article_'.$item['id']);

		// get the anchor
		$anchor = Anchors::get($item['anchor']);

		// get the related overlay, if any
		include_once $context['path_to_root'].'overlays/overlay.php';
		$overlay = Overlay::load($item);

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

		// the author
		$author = '';
		if(isset($context['with_author_information']) && ($context['with_author_information'] == 'Y'))
			$author = sprintf(i18n::s('by %s'), $item['create_name']).' ';

		// date and issue number
		$text .= '<p class="details">'.$author.Skin::build_date($item['publish_date']).' - Issue No. '.Skin::build_link($url, $item['id'])."</p>\n";

		// article rating, if the anchor allows for it
		if(is_object($anchor) && !$anchor->has_option('without_rating')) {

			// report on current rating
			if($item['rating_count'])
				$label = sprintf(i18n::s('Rating: %s'), Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])));
			else
				$label = i18n::s('Rate this page');

			// a link to let surfers rate this page
			$text = Skin::build_link(Articles::get_url($item['id'], 'rate'), $label, 'basic').BR;

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

			// use adequate label
			if(is_object($overlay) && ($label = $overlay->get_label('description')))
				$text .= Skin::build_block($label, 'title');

			$text .= '<div class="description">'.Codes::beautify($item['description'], $item['options'])."</div>\n";

		}

		// additional commands
		$menu = array();

		// discuss this page, if comments have been activated at the index page, and if they are allowed here
		include_once $context['path_to_root'].'comments/comments.php';
		if(is_object($anchor) && $anchor->has_option('with_comments') && Comments::are_allowed($anchor, $item))
			$menu = array_merge($menu, array(Comments::get_url('article:'.$item['id'], 'comment') => i18n::s('Add a comment')));

		// info on related comments
		if($count = Comments::count_for_anchor('article:'.$item['id'])) {
			$link = Comments::get_url('article:'.$item['id'], 'list');
			$menu = array_merge($menu, array($link => sprintf(i18n::ns('%d comment', '%d comments', $count), $count)));
		}

		// trackback
		if($context['with_friendly_urls'] == 'Y')
			$link = 'links/trackback.php/article/'.$item['id'];
		else
			$link = 'links/trackback.php?anchor='.urlencode('article:'.$item['id']);
		$menu = array_merge($menu, array($link => i18n::s('Reference this page')));

		// info on related links
		include_once $context['path_to_root'].'links/links.php';
		if($count = Links::count_for_anchor('article:'.$item['id']))
			$menu = array_merge($menu, array($url.'#links' => sprintf(i18n::ns('%d link', '%d links', $count), $count)));

		// attach a file
		if(Surfer::is_member() && Surfer::may_upload()) {
			if($context['with_friendly_urls'] == 'Y')
				$link = 'files/edit.php/article/'.$item['id'];
			else
				$link = 'files/edit.php?anchor='.urlencode('article:'.$item['id']);
			$menu = array_merge($menu, array($link => i18n::s('Upload a file')));
		}

		// see files attached to this article
		include_once $context['path_to_root'].'files/files.php';
		if($count = Files::count_for_anchor('article:'.$item['id']))
			$menu = array_merge($menu, array($url.'#files' => sprintf(i18n::ns('%d file', '%d files', $count), $count)));

		// modify this page
		if(Surfer::is_associate())
			$menu = array_merge($menu, array( Articles::get_url($item['id'], 'edit') => i18n::s('Edit this page') ));

		// talk about it
		if(@count($menu))
			$text .= Skin::build_box(i18n::s('Contribute'), Skin::build_list($menu, 'menu_bar'));

		// returned the formatted content
		return $text;
	}
}

?>