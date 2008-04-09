<?php
/**
 * layout articles as newspaper do
 *
 * The three very first articles are displayed side-by-side, with a thumbnail image if one is present.
 *
 * Subsequent articles are listed below an horizontal line, with text only.
 *
 * @link http://www.movabletype.org/docs/mttrackback.html TrackBack Technical Specification
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @tester ThierryP
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_home_articles_as_newspaper extends Layout_interface {

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
	 * @return 11 - One first article, then a row of three, plus 7 other links
	 *
	 * @see skins/layout.php
	 */
	function items_per_page() {
		return 11;
	}

	/**
	 * list articles as newspaper do
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
			$label = i18n::s('No article has been published so far.');
			if(Surfer::is_associate())
				$label .= ' '.sprintf(i18n::s('Use the %s to start to populate this server.'), Skin::build_link('control/populate.php', i18n::s('Content Assistant'), 'shortcut'));
			$output = '<p>'.$label.'</p>';
			return $output;
		}

		// flag articles updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));

		// build a list of articles
		$text = '';
		$others = array();
		$item_count = 0;
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'files/files.php';
		include_once $context['path_to_root'].'links/links.php';
		while($item =& SQL::fetch($result)) {

			// reset the rendering engine between items
			Codes::initialize(Articles::get_url($item['id']));

			// get the anchor
			$anchor = Anchors::get($item['anchor']);

			// next item
			$item_count += 1;

			// section opening
			if($item_count == 1) {
				$text .= '<div id="home_north">'."\n";
			} elseif($item_count == 2) {
				$text .= '<div id="home_south">'."\n";
			}

			// layout first article
			if($item_count == 1) {

				// the icon to put aside
				$icon = '';
				if($item['icon_url']) {
					$icon = $item['icon_url'];
				} elseif(is_object($anchor)) {
					$icon = $anchor->get_icon_url();
				}
				if($icon) {
					$url = Articles::get_url($item['id'], 'view', $item['title']);
					$text .= '<a href="'.$context['url_to_root'].$url.'"><img src="'.$icon.'" class="left_image" alt=""'.EOT.'</a>';
				}

				$text .= $this->layout_first($item, $anchor, $dead_line);

			// layout newest articles
			} elseif($item_count <= 4) {

				// the icon to put aside
				$icon = '';
				if($item['thumbnail_url']) {
					$icon = $item['thumbnail_url'];
				} elseif(is_object($anchor)) {
					$icon = $anchor->get_thumbnail_url();
				}
				if($icon) {
					$url = Articles::get_url($item['id'], 'view', $item['title']);
					$icon = '<a href="'.$context['url_to_root'].$url.'"><img src="'.$icon.'" class="left_image" alt=""'.EOT.'</a>';
				}

				// the style to apply
				switch($item_count) {
				case 2:
					$text .= '<div id="home_west">';
					break;
				case 3:
					$text .= '<div id="home_center">';
					break;
				case 4:
					$text .= '<div id="home_east">';
					break;
				}
				$text .= $icon.$this->layout_newest($item, $anchor).'</div>';

			// layout recent articles
			} else
				$others[Articles::get_url($item['id'], 'view', $item['title'])] = $this->layout_recent($item, $anchor, $dead_line);

			// close #home_north
			if($item_count == 1)
				$text .= '</div>'."\n";

			// extend the #home_south in case of floats
			elseif($item_count == 4)
				$text .= '<p style="clear: left;">&nbsp;</p></div>'."\n";

		}

		// not enough items in the database to fill the south; close it here
		if(($item_count == 2) || ($item_count == 3))
			$text .= '</div>'."\n";

		// build the list of other articles
		if(count($others)) {

			// link to articles index
			$others['articles/'] = array(NULL, i18n::s('All articles'), NULL, 'shortcut');

			// make section box
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
	 * @param object the anchor of this article, if any
	 * @return string the rendered text
	**/
	function layout_first($item, $anchor, $dead_line) {
		global $context;

		// get the related overlay, if any
		include_once $context['path_to_root'].'overlays/overlay.php';
		$overlay = Overlay::load($item);

		// the title
		$title = Codes::beautify_title($item['title']);

		// rating
		if($item['rating_count'] && is_object($anchor) && $anchor->has_option('with_rating'))
			$title .= ' '.Skin::build_link(Articles::get_url($item['id'], 'rate'), Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])), 'basic');

		// pack in a block
		$text = Skin::build_block($title, 'title', 'article_'.$item['id']);

		// introduction
		$text .= '<p>';

		// signal restricted and private articles
		if($item['active'] == 'N')
			$text .= PRIVATE_FLAG.' ';
		elseif($item['active'] == 'R')
			$text .= RESTRICTED_FLAG.' ';

		// flag articles updated recently
		if($item['create_date'] >= $dead_line)
			$text .= ' '.NEW_FLAG;
		elseif($item['edit_date'] >= $dead_line)
			$text .= ' '.UPDATED_FLAG;

		// the author
		$author = '';
		if(isset($context['with_author_information']) && ($context['with_author_information'] == 'Y'))
			$author = sprintf(i18n::s('by %s'), $item['create_name']).' ';

		// date
		$text .= '<span class="details">'.$author.Skin::build_date($item['publish_date']).' - </span>';

		// the introductory text
		if($item['introduction']) {
			$text .= Codes::beautify($item['introduction'], $item['options'])
				.' '.Skin::build_link(Articles::get_url($item['id'], 'view', $item['title']), i18n::s('read more').MORE_IMG, 'basic');
		} elseif(!is_object($overlay))
			$text .= Skin::cap(Codes::beautify($item['description'], $item['options']), 70, Articles::get_url($item['id'], 'view', $item['title']));

		// end of the introduction
		$text .= '</p>'."\n";

		// insert overlay data, if any
		if(is_object($overlay))
			$text .= $overlay->get_text('list', $item);

		// read the article
		$menu = array( Articles::get_url($item['id'], 'view', $item['title']) => i18n::s('Read more') );

		// info on related files
		if($context['with_friendly_urls'] == 'Y')
			$file = 'articles/view.php/'.$item['id'].'/files/1';
		else
			$file = 'articles/view.php?id='.urlencode($item['id']).'&amp;files=1';
		if($count = Files::count_for_anchor('article:'.$item['id']))
			$menu[] = Skin::build_link($file, sprintf(i18n::ns('1&nbsp;file', '%d&nbsp;files', $count), $count), 'basic');

		// info on related comments
		include_once $context['path_to_root'].'comments/comments.php';
		$link = Comments::get_url('article:'.$item['id'], 'list');
		if($count = Comments::count_for_anchor('article:'.$item['id']))
			$menu[] = Skin::build_link($link, sprintf(i18n::ns('1&nbsp;comment', '%d&nbsp;comments', $count), $count), 'basic');

		// discuss
		if(Comments::are_allowed($anchor, $item))
			$menu = array_merge($menu, array( Comments::get_url('article:'.$item['id'], 'comment') => i18n::s('Discuss') ));

		// info on related links
		if($context['with_friendly_urls'] == 'Y')
			$link = 'articles/view.php/'.$item['id'].'/links/1';
		else
			$link = 'articles/view.php?id='.urlencode($item['id']).'&amp;links=1';
		if($count = Links::count_for_anchor('article:'.$item['id']))
			$menu[] = Skin::build_link($link, sprintf(i18n::ns('1&nbsp;link', '%d&nbsp;links', $count), $count), 'basic');

		// trackback
		if($context['with_friendly_urls'] == 'Y')
			$link = 'links/trackback.php/article/'.$item['id'];
		else
			$link = 'links/trackback.php?anchor='.urlencode('article:'.$item['id']);
		$menu = array_merge($menu, array( $link => i18n::s('Trackback') ));

		// link to the anchor page
		if(is_object($anchor))
			$menu = array_merge($menu, array( $anchor->get_url() => $anchor->get_title() ));

		// append a menu
		$text .= Skin::build_list($menu, 'menu_bar');

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
		include_once $context['path_to_root'].'overlays/overlay.php';
		$overlay = Overlay::load($item);

		// the title
		$title = Codes::beautify_title($item['title']);

		// rating
		if($item['rating_count'] && is_object($anchor) && $anchor->has_option('with_rating'))
			$title .= ' '.Skin::build_link(Articles::get_url($item['id'], 'rate'), Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])), 'basic');

		// pack in a block
		$text = Skin::build_block($title, 'subtitle', 'article_'.$item['id']);

		// the introduction
		$text .= '<p style="clear:left">';

		// signal restricted and private articles
		if($item['active'] == 'N')
			$text .= PRIVATE_FLAG.' ';
		elseif($item['active'] == 'R')
			$text .= RESTRICTED_FLAG.' ';

		// flag articles updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));

		if($item['create_date'] >= $dead_line)
			$text .= ' '.NEW_FLAG;
		elseif($item['edit_date'] >= $dead_line)
			$text .= ' '.UPDATED_FLAG;

		// the author
		$author = '';
		if(isset($context['with_author_information']) && ($context['with_author_information'] == 'Y'))
			$author = sprintf(i18n::s('by %s'), $item['create_name']).' ';

		// date
		$text .= '<span class="details">'.$author.Skin::build_date($item['publish_date']).' - </span>';

		// the introductory text
		if($item['introduction'])
			$text .= Codes::beautify($item['introduction'], $item['options']);
		elseif(!is_object($overlay))
			$text .= Skin::cap(Codes::beautify($item['description'], $item['options']), 25, Articles::get_url($item['id'], 'view', $item['title']));

		// end of the introduction
		$text .= '</p>'."\n";

		// insert overlay data, if any
		if(is_object($overlay))
			$text .= $overlay->get_text('list', $item);

		// read this article
		$text .= '<p class="details right">'.Skin::build_link(Articles::get_url($item['id'], 'view', $item['title']), i18n::s('Read this page'), 'basic');

		// discuss
		if(Comments::are_allowed($anchor, $item))
			$text .= BR.Skin::build_link(Comments::get_url('article:'.$item['id'], 'comment'), i18n::s('Discuss'), 'basic');

		// info on related comments
		include_once $context['path_to_root'].'comments/comments.php';
		if($count = Comments::count_for_anchor('article:'.$item['id'])) {
			$link = Comments::get_url('article:'.$item['id'], 'list');
			$text .= ' - '.Skin::build_link($link, sprintf(i18n::ns('1&nbsp;comment', '%d&nbsp;comments', $count), $count), 'basic');
		}

		// trackback
		if($context['with_friendly_urls'] == 'Y')
			$link = 'links/trackback.php/article/'.$item['id'];
		else
			$link = 'links/trackback.php?anchor='.urlencode('article:'.$item['id']);
		$text .= BR.Skin::build_link($link, i18n::s('Trackback'), 'basic');

		// info on related links
		if($context['with_friendly_urls'] == 'Y')
			$link = 'articles/view.php/'.$item['id'].'/links/1';
		else
			$link = 'articles/view.php?id='.urlencode($item['id']).'&amp;links=1';
		if($count = Links::count_for_anchor('article:'.$item['id']))
			$text .= ' - '.Skin::build_link($link, sprintf(i18n::ns('1&nbsp;link', '%d&nbsp;links', $count), $count), 'basic');

		// link to the anchor page
		if(is_object($anchor)) {
			$text .= BR.Skin::build_link($anchor->get_url(), $anchor->get_title(), 'basic');
		}

		// end of details
		$text .= '</p>';

		return $text;
	}

	/**
	 * layout one recent article
	 *
	 * @param array the article
	 * @param object the anchor of this article, if any
	 * @param string the minimum stamp for newest articles
	 * @return an array ($prefix, $label, $suffix)
	**/
	function layout_recent($item, $anchor, $dead_line) {
		global $context;

		// reset everything
		$prefix = $suffix = '';

		// flag articles updated recently
		if($item['create_date'] >= $dead_line)
			$prefix .= ' '.NEW_FLAG;
		elseif($item['edit_date'] >= $dead_line)
			$prefix .= ' '.UPDATED_FLAG;

		// signal restricted and private articles
		if($item['active'] == 'N')
			$prefix .= PRIVATE_FLAG.' ';
		elseif($item['active'] == 'R')
			$prefix .= RESTRICTED_FLAG.' ';

		// rating
		if($item['rating_count'])
			$suffix .= Skin::build_link(Articles::get_url($item['id'], 'rate'), Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])), 'basic');

		// the introductory text
		if(isset($item['introduction']) && $item['introduction'])
			$suffix .= ' -&nbsp;'.Codes::beautify($item['introduction'], $item['options']);
		elseif(isset($item['decription']) && $item['decription'])
			$suffix .= ' -&nbsp;'.Skin::cap(Codes::beautify($item['description'], $item['options']), 25);

		// the author
		$author = '';
		if(isset($context['with_author_information']) && ($context['with_author_information'] == 'Y'))
			$author = sprintf(i18n::s('by %s'), $item['create_name']).' ';

		// date
		$suffix .= '<span class="details"> -&nbsp;'.$author.Skin::build_date($item['publish_date']);

		// count comments
		if($count = Comments::count_for_anchor('article:'.$item['id']))
			$suffix .= ' -&nbsp;'.sprintf(i18n::ns('1&nbsp;comment', '%d&nbsp;comments', $count), $count);

		// end of details
		$suffix .= '</span>';

		// insert an array of links
		return array($prefix, $item['title'], $suffix);
	}
}

?>