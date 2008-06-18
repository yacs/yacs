<?php
/**
 * layout articles
 *
 * This is the default layout for articles (Decorated).
 *
 * @see articles/index.php
 * @see articles/articles.php
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @author Thierry Pinelli (ThierryP)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles extends Layout_interface {

	/**
	 * list articles
	 *
	 * Accept following variants:
	 * - 'hits', compact plus the number of hits
	 * - 'no_author', for articles in the user page
	 * - 'category:xxx', if the list is displayed at categories/view.php
	 * - 'mobile', for mobile devices
	 * - 'section:xxx', if the list is displayed at sections/view.php
	 *
	 * @param resource the SQL result
	 * @return array of resulting items, or NULL
	 *
	 * @see skins/layout.php
	**/
	function &layout(&$result) {
		global $context;

		// we return an array of ($url => $attributes)
		$items = array();

		// empty list
		if(!SQL::count($result))
			return $items;

		// sanity check
		if(!isset($this->layout_variant))
			$this->layout_variant = 'full';

		// flag articles updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// process all items in the list
		include_once $context['path_to_root'].'categories/categories.php';
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'files/files.php';
		include_once $context['path_to_root'].'links/links.php';
		include_once $context['path_to_root'].'overlays/overlay.php';
		while($item =& SQL::fetch($result)) {

			// get the related overlay, if any
			$overlay = Overlay::load($item);

			// get the main anchor
			$anchor = Anchors::get($item['anchor']);

			// the url to view this item
			$url = Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']);

			// reset the rendering engine between items
			Codes::initialize($url);

			// use the title to label the link
			if(is_object($overlay) && is_callable(array($overlay, 'get_live_title')))
				$title = $overlay->get_live_title($item);
			else
				$title = Codes::beautify_title($item['title']);

			// initialize variables
			$prefix = $suffix = $icon = '';

			// flag sticky pages
			if(($item['rank'] < 10000) && !preg_match('/(compact|hits|mobile)/', $this->layout_variant))
				$prefix .= STICKY_FLAG;

			// not too many details on mobiles
			if($this->layout_variant != 'mobile') {

				// flag articles that are dead, or created or updated very recently
				if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $now))
					$prefix .= EXPIRED_FLAG;
				elseif($item['create_date'] >= $dead_line)
					$suffix .= NEW_FLAG;
				elseif($item['edit_date'] >= $dead_line)
					$suffix .= UPDATED_FLAG;

			}

			// signal articles to be published
			if(($item['publish_date'] <= NULL_DATE) || ($item['publish_date'] > gmstrftime('%Y-%m-%d %H:%M:%S')))
				$prefix .= DRAFT_FLAG;

			// signal restricted and private articles
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// the compact version
			if($this->layout_variant == 'compact') {
				$items[$url] = array($prefix, Skin::strip($title, 30), $suffix, 'basic', NULL);
				continue;
			}

			// with hits
			if($this->layout_variant == 'hits') {
				if($item['hits'] > 1)
					$suffix = ' '.sprintf(i18n::s('%d hits'), $item['hits']);

				$items[$url] = array($prefix, Skin::strip($title, 30), $suffix, 'basic', NULL);
				continue;
			}

			// the introductory text
			if($item['introduction']) {
				$suffix .= ' -&nbsp;'.Codes::strip($item['introduction']);

				// link to description, if any
				if($item['description'])
					$suffix .= ' '.Skin::build_link($url, MORE_IMG, 'more', i18n::s('Read more')).' ';

			}

			// insert overlay data, if any
			if(is_object($overlay))
				$suffix .= $overlay->get_text('list', $item);

			// next line, except if we already are at the beginning of a line
			if($suffix && !preg_match('/<br\s*\/>$/', rtrim($suffix)))
				$suffix .= BR;

			// append details to the suffix
			$suffix .= '<span class="details">';

			// details
			$details = array();

			// display details only at the main index page, and also at anchor pages
			if(($this->layout_variant == 'full') || preg_match('/\w:\d/', $this->layout_variant)) {

				// the author
				if(isset($context['with_author_information']) && ($context['with_author_information'] == 'Y')) {
					if($item['create_name'] != $item['edit_name'])
						$details[] = sprintf(i18n::s('by %s, %s'), $item['create_name'], $item['edit_name']);
					else
						$details[] = sprintf(i18n::s('by %s'), $item['create_name']);
				}

				// the last action
				$details[] = get_action_label($item['edit_action']).' '.Skin::build_date($item['edit_date']);

				// the number of hits
				if(Surfer::is_logged() && ($item['hits'] > 1))
					$details[] = sprintf(i18n::s('%d hits'), $item['hits']);

				// info on related files
				if($count = Files::count_for_anchor('article:'.$item['id'], TRUE))
					$details[] = sprintf(i18n::ns('1 file', '%d files', $count), $count);

				// info on related links
				if($count = Links::count_for_anchor('article:'.$item['id'], TRUE))
					$details[] = sprintf(i18n::ns('1 link', '%d links', $count), $count);

				// info on related comments
				if($count = Comments::count_for_anchor('article:'.$item['id'], TRUE))
					$details[] = sprintf(i18n::ns('1 comment', '%d comments', $count), $count);

				// rating
				if($item['rating_count'] && !(is_object($anchor) && $anchor->has_option('without_rating')))
					$details[] = Skin::build_link(Articles::get_url($item['id'], 'rate'), Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])), 'basic');

				// unusual ranks are signaled to associates
				if(($item['rank'] != 10000) && Surfer::is_empowered())
					$details[] = '{'.$item['rank'].'}';

			}

			// signal locked articles
			if(isset($item['locked']) && ($item['locked'] == 'Y'))
				$details[] = LOCKED_FLAG;

			// at the user page
			if(($this->layout_variant == 'no_author') && Surfer::get_id()) {
				if(Members::check('article:'.$item['id'], 'user:'.Surfer::get_id()))
					$label = i18n::s('Forget');
				else
					$label = i18n::s('Watch');
				$menu = array( 'users/track.php?anchor='.urlencode('article:'.$item['id']) => $label );
				$details [] = Skin::build_list($menu, 'menu');
			}

			// combine in-line details
			if(count($details))
				$suffix .= ucfirst(trim(implode(', ', $details))).BR;

			// links to sections and categories
			$anchors = array();

			// the main anchor link
			if(($this->layout_variant != 'no_anchor') && ($item['anchor'] != $this->layout_variant) && is_object($anchor))
				$anchors[] = Skin::build_link($anchor->get_url(), ucfirst($anchor->get_title()), 'section');

			// list up to three categories by title, if any, and if not on a mobile
// 			if($this->layout_variant != 'mobile') {
// 				if($members = Members::list_categories_by_title_for_member('article:'.$item['id'], 0, 3, 'raw')) {
// 					foreach($members as $id => $attributes) {
// 						if($this->layout_variant != 'category:'.$id)
// 							$anchors[] = Skin::build_link(Categories::get_url($attributes['id'], 'view', $attributes['title']), $attributes['title'], 'category');
// 					}
// 				}
// 			}

			// on mobile, the section is a header
			if($this->layout_variant == 'mobile') {
				if(is_object($anchor))
					$prefix = '<b>'.$anchor->get_title().'</b>'.BR."\n".$prefix;

			// else, list section and categories in the suffix
			} elseif(@count($anchors))
				$suffix .= sprintf(i18n::s('In %s'), implode(' | ', $anchors));

			// end of details
			$suffix .= '</span>';

			// strip empty details
			$suffix = str_replace(BR.'<span class="details"></span>', '', $suffix);
			$suffix = str_replace('<span class="details"></span>', '', $suffix);

			// insert a suffix separator
			if(trim($suffix))
				$suffix = ' '.$suffix;

			// the icon to put in the left column
			if($item['thumbnail_url'])
				$icon = $item['thumbnail_url'];

			// or inherit from the anchor
			elseif(is_object($anchor)) {

				// we are listing articles in the anchor page - use the anchor bullet
				if($this->layout_variant == $anchor->get_reference()) {
					$icon = $anchor->get_bullet_url();

				// we are listing articles in a page that has a specific bullet - use it
				} elseif(strpos(':', $this->layout_variant) && ($bulleted = Anchors::get($this->layout_variant))) {
					$icon = $bulleted->get_bullet_url();

				}
			}

			// list all components for this item
			$items[$url] = array($prefix, $title, $suffix, 'article', $icon);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>