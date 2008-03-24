<?php
/**
 * layout home articles as boxesandarrows do
 *
 * The two very first articles are displayed side-by-side, with a thumbnail image if one is present.
 *
 * Subsequent articles are listed below an horizontal line, with text only.
 *
 * @link http://www.boxesandarrows.com/
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @tester Agnes
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_home_articles_as_boxesandarrows extends Layout_interface {

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
	 * @return 9 - two last articles first, plus 7 other pages
	 *
	 * @see skins/layout.php
	 */
	function items_per_page() {
		return 9;
	}

	/**
	 * list articles as boxesandarrows do
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
		include_once $context['path_to_root'].'files/files.php';
		$text = '';
		$item_count = 0;
		while($item =& SQL::fetch($result)) {

			// reset the rendering engine between items
			Codes::initialize(Articles::get_url($item['id']));

			// next item
			$item_count += 1;

			// get the anchor
			$anchor = Anchors::get($item['anchor']);

			// one box per article
			$prefix = $suffix = '';

			// section opening
			if($item_count == 1)
				$text .= Skin::build_block(i18n::s('What\'s New?'), 'title', 'new_articles')."\n".'<div id="home_south">'."\n";
			elseif($item_count == 3)
				$text .= '</div>'.Skin::build_block(i18n::s('Recent Articles'), 'title', 'recent_articles')."\n";

			// layout newest articles
			if($item_count < 3) {

				// style to apply
				switch($item_count) {
				case 1:
					$text .= '<div id="home_west">';
					break;
				case 2:
					$text .= '<div id="home_east">';
					break;
				}

				// the icon to put aside
				$icon = '';
				if($item['thumbnail_url']) {
					$icon = $item['thumbnail_url'];
				} elseif(is_object($anchor)) {
					$icon = $anchor->get_thumbnail_url();
				}
				if($icon) {
					$url = Articles::get_url($item['id'], 'view', $item['title']);
					$text .= '<a href="'.$context['url_to_root'].$url.'" title="'.i18n::s('Read the page').'"><img src="'.$icon.'" class="left_image" alt=""'.EOT.'</a>';
				}

				$text .= $this->layout_newest($item, $anchor).'</div>'."\n";

			// layout recent articles
			} else
				$text .= $this->layout_recent($item, $anchor, $dead_line);

		}

		// extend the #home_south in case of floats
		if(($item_count > 1) && ($item_count < 3))
			$text .= '<p style="clear: left;">&nbsp;</p></div>'."\n";

		// end of processing
		SQL::free($result);

		// link to articles index
		if($item_count > 4) {
			$text .= '<p><b>'.Skin::build_link('articles/', i18n::s('All articles'), 'shortcut').'</b></p>'."\n";
		}

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

		// initialize variables
		$prefix = $suffix = $text = '';

		// get the related overlay, if any
		include_once $context['path_to_root'].'overlays/overlay.php';
		$overlay = Overlay::load($item);

		// help to jump here
		$prefix .= '<a id="article_'.$item['id'].'"></a>';

		// signal restricted and private articles
		if($item['active'] == 'N')
			$prefix .= PRIVATE_FLAG.' ';
		elseif($item['active'] == 'R')
			$prefix .= RESTRICTED_FLAG.' ';

		// rating
		if($item['rating_count']) {
			$suffix .= Skin::build_link(Articles::get_url($item['id'], 'rate'), Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])), 'basic', i18n::s('Rate this page!'));
		}

		// use the title as a link to the page
		$text .= $prefix.'<b>'.Skin::build_link(Articles::get_url($item['id'], 'view', $item['title']), Codes::beautify_title($item['title']), 'basic', i18n::s('Read this page')).'</b>'.$suffix;

		// details
		$details = array();

		// the creator and editor of this article
		if(isset($context['with_author_information']) && ($context['with_author_information'] == 'Y')) {
			if($item['edit_name'] == $item['create_name'])
				$details[] = sprintf(i18n::s('by %s'), ucfirst($item['create_name']));
			else
				$details[] = sprintf(i18n::s('by %s, %s'), ucfirst($item['create_name']), ucfirst($item['edit_name']));
		}

		// poster details
		if(count($details))
			$text .= BR.'<span class="details">'.ucfirst(implode(', ', $details))."</span>\n";

		// the introductory text
		$introduction = '';
		if($item['introduction'])
			$introduction .= Codes::beautify($item['introduction'], $item['options']);
		elseif(!is_object($overlay))
			$introduction .= Skin::cap(Codes::beautify($item['description'], $item['options']), 70);
		if($introduction)
		$text .= '<p>'.$introduction.'</p>'."\n";

		// insert overlay data, if any
		if(is_object($overlay))
			$text .= $overlay->get_text('list', $item);

		// read this article
		$text .= '<p class="details right">'.Skin::build_link(Articles::get_url($item['id'], 'view', $item['title']), i18n::s('Read the page'), 'basic');

		// info on related files
		if(isset($context['with_friendly_urls']) && ($context['with_friendly_urls'] == 'Y'))
			$file = 'articles/view.php/'.$item['id'].'/files/1';
		else
			$file = 'articles/view.php?id='.urlencode($item['id']).'&amp;files=1';
		if($count = Files::count_for_anchor('article:'.$item['id']))
			$text .= ' ('.Skin::build_link($file, sprintf(i18n::ns('1&nbsp;file', '%d&nbsp;files', $count), $count), 'basic').')';

		// link to the anchor page
		if(is_object($anchor))
			$text .= BR.Skin::build_link($anchor->get_url(), $anchor->get_title(), 'basic', i18n::s('More similar pages'));

		// list up to three categories by title, if any
		include_once $context['path_to_root'].'categories/categories.php';
		if($items = Members::list_categories_by_title_for_member('article:'.$item['id'], 0, 3, 'raw')) {
			$text .= BR.i18n::s('See also');
			$first_category = TRUE;
			foreach($items as $id => $attributes) {
				if(!$first_category)
					$text .= ',';
				$text .= ' '.Skin::build_link(Categories::get_url($attributes['id'], 'view', $attributes['title']), $attributes['title'], 'basic', i18n::s('More similar pages'));
				$first_category = FALSE;
			}
		}

		$text .= '</p>';

		return $text;
	}

	/**
	 * layout one recent article
	 *
	 * @param array the article
	 * @param object the anchor of this article, if any
	 * @param string the minimum stamp for newest articles
	 * @return string the rendered text
	**/
	function layout_recent($item, $anchor, $deadline) {
		global $context;

		// get the related overlay, if any
		include_once $context['path_to_root'].'overlays/overlay.php';
		$overlay = Overlay::load($item);

		// reset everything
		$prefix = $suffix = $text = '';

		// signal restricted and private articles
		if($item['active'] == 'N')
			$prefix .= PRIVATE_FLAG.' ';
		elseif($item['active'] == 'R')
			$prefix .= RESTRICTED_FLAG.' ';

		// flag articles updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));

		// flag articles updated recently
		if($item['create_date'] >= $dead_line)
			$suffix .= ' '.NEW_FLAG;
		elseif($item['edit_date'] >= $dead_line)
			$suffix .= ' '.UPDATED_FLAG;

		// rating
		if($item['rating_count'] && is_object($anchor) && $anchor->has_option('with_rating')) {
			$suffix .= Skin::build_link(Articles::get_url($item['id'], 'rate'), Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])), 'basic', i18n::s('Rate this article!'));
		}

		// use the title as a link to the page
		$text .= '<p id="article_'.$item['id'].'">'.$prefix.'<b>'.Skin::build_link(Articles::get_url($item['id'], 'view', $item['title']), Codes::beautify_title($item['title']), 'basic', i18n::s('Read this page')).'</b>'.$suffix;

		// add details
		$details = array();

		// the creator and editor of this article
		if(isset($context['with_author_information']) && ($context['with_author_information'] == 'Y')) {
			if($item['edit_name'] == $item['create_name'])
				$details[] = sprintf(i18n::s('by %s'), ucfirst($item['create_name']));
			else
				$details[] = sprintf(i18n::s('by %s, %s'), ucfirst($item['create_name']), ucfirst($item['edit_name']));
		}

		// details
		if(count($details))
			$text .= ' <span class="details">'.ucfirst(implode(', ', $details))."</span>\n";

		// next paragraph
		$text .= '</p>';

		// the introductory text
		$introduction = '';
		if($item['introduction'])
			$introduction .= Codes::beautify($item['introduction'], $item['options']);
		elseif(!is_object($overlay))
			$introduction .= Skin::cap(Codes::beautify($item['description'], $item['options']), 70);
		if($introduction)
		$text .= '<p>'.$introduction.'</p>'."\n";

		// insert overlay data, if any
		if(is_object($overlay))
			$text .= $overlay->get_text('list', $item);

		// a paragraph for all anchors
		$text .= '<p class="details right">';

		// link to the anchor page
		if(is_object($anchor))
			$text .= Skin::build_link($anchor->get_url(), $anchor->get_title(), 'basic', i18n::s('More similar pages'));

		// list up to three categories by title, if any
		include_once $context['path_to_root'].'categories/categories.php';
		if($items = Members::list_categories_by_title_for_member('article:'.$item['id'], 0, 3, 'raw')) {
			$text .= BR.i18n::s('See also');
			$first_category = TRUE;
			foreach($items as $id => $attributes) {
				if(!$first_category)
					$text .= ',';
				$text .= ' '.Skin::build_link(Categories::get_url($attributes['id'], 'view', $attributes['title']), $attributes['title'], 'basic', i18n::s('More similar pages'));
				$first_category = FALSE;
			}
		}

		// no more anchors to list
		$text .= '</p>';

		return $text;
	}
}

?>