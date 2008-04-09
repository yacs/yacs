<?php
/**
 * layout articles as a daily weblog do
 *
 * @todo bug with sidebar and skin::cap()
 * @todo bug with links and skin::cap()
 *
 * This layout is made of dates, followed by section boxes.
 * Each date is written before a horizontal ruler (e.g., &lt;hr&gt;).
 * Each article has its own section box.
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
 * @tester Timster
 * @tester Lasares
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_home_articles_as_daily extends Layout_interface {

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
	 * @return 5, to list the five most recent entries
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
		$box['content'] = '';
		$box['title'] = '';
		include_once $context['path_to_root'].'categories/categories.php';
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'files/files.php';
		include_once $context['path_to_root'].'links/links.php';
		include_once $context['path_to_root'].'overlays/overlay.php';
		while($item =& SQL::fetch($result)) {

			// reset the rendering engine between items
			Codes::initialize(Articles::get_url($item['id']));

			// get the anchor
			$anchor = Anchors::get($item['anchor']);

			// get the related overlay, if any
			$overlay = Overlay::load($item);

			// what's the date today?
			$current_date = substr($item['publish_date'], 0, 10);

			// very first box
			if(!isset($previous_date)) {
				$text .= '<div id="home_north">'."\n";
				$in_north = TRUE;
				$text .= '<p class="date">'.Skin::build_date($item['publish_date'], 'no_hour')."</p>\n";
				$previous_date = $current_date;
			}

			// not the same date
			if($previous_date != $current_date) {
				if($in_north)
					$text .= '</div>'.BR.BR."\n";
				$in_north = FALSE;
				$text .= '<p class="date">'.Skin::build_date($item['publish_date'], 'no_hour')."</p>\n";
				$previous_date = $current_date;
			}

			// make a section box
			$box['title'] = Codes::beautify_title($item['title']);

			// the icon to put aside - never use anchor images
			if($item['icon_url']) {
				$url = Articles::get_url($item['id'], 'view', $item['title']);
				$box['content'] .= '<a href="'.$context['url_to_root'].$url.'"><img src="'.$item['icon_url'].'" class="left_image" alt=""'.EOT.'</a>';
			}

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

			// the creator and editor of this article
			if(isset($context['with_author_information']) && ($context['with_author_information'] == 'Y')) {
				if($item['edit_name'] == $item['create_name'])
					$details[] = sprintf(i18n::s('by %s'), ucfirst($item['create_name']));
				else
					$details[] = sprintf(i18n::s('by %s, %s'), ucfirst($item['create_name']), ucfirst($item['edit_name']));
			}

			// signal restricted and private articles
			if($item['active'] == 'N')
				$details[] = PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$details[] = RESTRICTED_FLAG;

			// link to the anchor page
			if(is_object($anchor))
				$details[] = Skin::build_link($anchor->get_url(), $anchor->get_title(), 'basic');

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
				$box['content'] .= $overlay->get_text('list', $item).BR.BR."\n";

			// the description
			if(trim($item['description']))
				$box['content'] .= Codes::beautify($item['description'], $item['options'])."\n";

			// build a menu
			$menu = array();

			// read the article
			$menu[] = Skin::build_link(Articles::get_url($item['id'], 'view', $item['title']), i18n::s('Permalink'), 'basic');

			// info on related files
			if($context['with_friendly_urls'] == 'Y')
				$file = 'articles/view.php/'.$item['id'].'/files/1';
			else
				$file = 'articles/view.php?id='.urlencode($item['id']).'&amp;files=1';
			if($count = Files::count_for_anchor('article:'.$item['id']))
				$menu[] = Skin::build_link($file, sprintf(i18n::ns('1&nbsp;file', '%d&nbsp;files', $count), $count), 'basic');

			// info on related comments
			if($count = Comments::count_for_anchor('article:'.$item['id'])) {
				$link = Comments::get_url('article:'.$item['id'], 'list');
				$menu[] = Skin::build_link($link, sprintf(i18n::ns('1&nbsp;comment', '%d&nbsp;comments', $count), $count), 'basic');
			}

			// comment
			if(Comments::are_allowed($anchor, $item))
				$menu[] = Skin::build_link(Comments::get_url('article:'.$item['id'], 'comment'), i18n::s('Discuss'), 'basic');

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
			$menu[] = Skin::build_link($link, i18n::s('Trackback'), 'basic');

			// a menu bar, but flushed to the right
			if(count($menu))
				$box['content'] .= '<p class="daily_menu" style="clear: left; text-align: right">'.MENU_PREFIX.implode(MENU_SEPARATOR, $menu).MENU_SUFFIX."</p>\n";

			$text .= Skin::build_box($box['title'], $box['content'], 'header1', 'article_'.$item['id']);
			$box['content'] = '';
			$box['title'] = '';
		}

		// close the on-going box
		if($in_north)
			$text .= '</div>'."\n";

		// end of processing
		SQL::free($result);

		// add links to archives
		$anchor =& Categories::get(i18n::c('monthly'));
		if(isset($anchor['id']) && ($items = Categories::list_by_date_for_anchor('category:'.$anchor['id'], 0, COMPACT_LIST_SIZE, 'compact'))) {
			$text .= '<p class="date">'.i18n::s('Past articles')."</p>\n";
			$tokens = array();
			foreach($items as $url => $attributes)
				$tokens[] = Skin::build_link($url, $attributes[1], 'basic');
			$tokens[] = Skin::build_link(Categories::get_url($anchor['id'], 'view', $anchor['title']), i18n::s('Archives'), 'basic');
			$text .= '<p class="details">'.implode(' ~ ', $tokens)."</p>\n";
		}

		return $text;
	}
}

?>