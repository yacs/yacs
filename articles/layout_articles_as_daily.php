<?php
/**
 * layout articles as a daily weblog do
 *
 * This layout is made of dates, followed by articles displayed in boxes.
 * Each date is written before a horizontal ruler (e.g., &lt;hr&gt;).
 * Each article has its own box.
 * Post title is used as box title.
 * Box content is made of several components:
 * - a time stamp, followed by a link to the section, plus up to three links to related categories
 * - page introduction, if any
 * - post content, up to some hundred words
 * - a menu bar linking the entry to its permanent reference, to attached files, to comments and to related links;
 * also provides commands to add a new comment or a new link.
 *
 * Largely inspired by [link]http://joi.ito.com/[/link].
 *
 * @link http://joi.ito.com/
 * @link http://www.movabletype.org/docs/mttrackback.html TrackBack Technical Specification
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @author Thierry Pinelli (ThierryP)
 * @tester Timster
 * @tester Lasares
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_daily extends Layout_interface {

	/**
	 * the preferred number of items for this layout
	 *
	 * @return 10, to list the ten most recent entries
	 *
	 * @see skins/layout.php
	 */
	function items_per_page() {
		return 10;
	}

	/**
	 * list articles as a daily weblog do
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

		// flag articles updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));

		// build a list of articles
		$box['content'] = '';
		$box['title'] = '';
		include_once $context['path_to_root'].'categories/categories.php';
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'files/files.php';
		include_once $context['path_to_root'].'links/links.php';
		include_once $context['path_to_root'].'overlays/overlay.php';
		while($item =& SQL::fetch($result)) {

			// get the related overlay, if any
			$overlay = Overlay::load($item);

			// get the anchor
			$anchor = Anchors::get($item['anchor']);

			// the url to view this item
			$url = Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']);

			// reset the rendering engine between items
			Codes::initialize($url);

			// what's the date today?
			if(isset($item['publish_date']) && ($item['publish_date'] > NULL_DATE))
				$current_date = substr($item['publish_date'], 0, 10);
			else
				$current_date = DRAFT_FLAG.i18n::s('not published');

			// very first box
			if(!isset($previous_date)) {
				$text .= '<div id="home_north">'."\n";
				$in_north = TRUE;
				$text .= '<p class="date">'.Skin::build_date($item['publish_date'], 'no_hour')."</p>\n";
				$previous_date = $current_date;
			}

			// not the same publication date
			if($previous_date != $current_date) {
				if($in_north)
					$text .= '</div>'.BR.BR."\n";
				$in_north = FALSE;
				if(isset($item['publish_date']) && ($item['publish_date'] > NULL_DATE))
					$text .= '<p class="date">'.Skin::build_date($item['publish_date'], 'no_hour')."</p>\n";
				$previous_date = $current_date;
			}

			// always flag articles to be published
			if(!isset($item['publish_date']) || ($item['publish_date'] <= NULL_DATE))
				$text .= '<p class="date">'.$current_date."</p>\n";

			// make a live title
			if(is_object($overlay) && is_callable(array($overlay, 'get_live_title')))
				$box['title'] = $overlay->get_live_title($item);
			else
				$box['title'] = Codes::beautify_title($item['title']);

			// the icon to put aside - never use anchor images
			if($item['icon_url'])
				$box['content'] .= '<a href="'.$context['url_to_root'].$url.'"><img src="'.$item['icon_url'].'" class="left_image" alt="" /></a>';

			// details
			$details = array();

			// flag articles updated recently
			if($item['create_date'] >= $dead_line)
				$details[] = NEW_FLAG;
			elseif($item['edit_date'] >= $dead_line)
				$detaisl[] = UPDATED_FLAG;

			// publication hour
			if(isset($item['publish_date']) && ($item['publish_date'] > NULL_DATE))
				$details[] = Skin::build_time($item['publish_date']);

			// signal restricted and private articles
			if($item['active'] == 'N')
				$details[] = PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$details[] = RESTRICTED_FLAG;

			// list up to three categories by title, if any
			if($items = Members::list_categories_by_title_for_member('article:'.$item['id'], 0, 3, 'raw')) {
				foreach($items as $id => $attributes)
					$details[] = Skin::build_link(Categories::get_url($attributes['id'], 'view', $attributes['title']), $attributes['title'], 'basic');
			}

			// rating
			if($item['rating_count'] && is_object($anchor) && $anchor->has_option('with_rating'))
				$details[] = Skin::build_link(Articles::get_url($item['id'], 'rate'), Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])), 'basic');

			// show details
			if(count($details))
				$box['content'] .= '<p class="details">'.implode(' ~ ', $details).'</p>'."\n";

			// the introduction text, if any
			if(trim($item['introduction']))
				$box['content'] .= Skin::build_block($item['introduction'], 'introduction');

			// insert overlay data, if any
			if(is_object($overlay))
				$box['content'] .= $overlay->get_text('list', $item);

			// the description
			if(trim($item['description']))
				$box['content'] .= Codes::beautify($item['description'], $item['options'])."\n";

			// build a menu
			$menu = array();

			// read the article
			$menu[] = Skin::build_link($url, i18n::s('Permalink'), 'basic');

			// info on related files
			if($count = Files::count_for_anchor('article:'.$item['id'], TRUE)) {
				if($context['with_friendly_urls'] == 'Y')
					$file = 'articles/view.php/'.$item['id'].'/files/1';
				else
					$file = 'articles/view.php?id='.urlencode($item['id']).'&amp;files=1';
				$menu[] = Skin::build_link($file, sprintf(i18n::ns('1&nbsp;file', '%d&nbsp;files', $count), $count), 'basic');
			}

			// info on related comments
			if($count = Comments::count_for_anchor('article:'.$item['id']))
				$menu[] = Skin::build_link(Comments::get_url('article:'.$item['id'], 'list'), sprintf(i18n::ns('1&nbsp;comment', '%d&nbsp;comments', $count), $count), 'basic');

			// comment
			if(Comments::are_allowed($anchor, $item))
				$menu[] = Skin::build_link(Comments::get_url('article:'.$item['id'], 'comment'), i18n::s('Discuss'), 'basic');

			// info on related links
			if($count = Links::count_for_anchor('article:'.$item['id'], TRUE)) {
				if($context['with_friendly_urls'] == 'Y')
					$link = 'articles/view.php/'.$item['id'].'/links/1';
				else
					$link = 'articles/view.php?id='.urlencode($item['id']).'&amp;links=1';
				$menu[] = Skin::build_link($link, sprintf(i18n::ns('1&nbsp;link', '%d&nbsp;links', $count), $count), 'basic');
			}

			// trackback
			if($context['with_friendly_urls'] == 'Y')
				$link = 'links/trackback.php/article/'.$item['id'];
			else
				$link = 'links/trackback.php?anchor='.urlencode('article:'.$item['id']);
			$menu[] = Skin::build_link($link, i18n::s('Trackback'), 'basic');

			// a menu bar, but flushed to the right
			if(count($menu))
				$box['content'] .= '<p class="daily_menu" style="clear: left; text-align: right">'.MENU_PREFIX.implode(MENU_SEPARATOR, $menu).MENU_SUFFIX."</p>\n";

			$text .= Skin::build_box($box['title'], $box['content'], 'section', 'article_'.$item['id']);
			$box['content'] = '';
			$box['title'] = '';
		}

		// close the on-going box
		if($in_north)
			$text .= '</div>'."\n";

		// end of processing
		SQL::free($result);
		return $text;
	}
}

?>