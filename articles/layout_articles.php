<?php
/**
 * layout articles
 *
 * This is the default layout for articles.
 *
 * @see articles/index.php
 * @see articles/articles.php
 *
 * @author Bernard Paques
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
			$this->layout_variant = 'decorated';

		// flag articles updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// process all items in the list
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'links/links.php';
		include_once $context['path_to_root'].'overlays/overlay.php';
		while($item =& SQL::fetch($result)) {

			// get the related overlay, if any
			$overlay = Overlay::load($item);

			// get the main anchor
			$anchor =& Anchors::get($item['anchor']);

			// the url to view this item
			$url =& Articles::get_permalink($item);

			// use the title to label the link
			if(is_object($overlay))
				$title = Codes::beautify_title($overlay->get_text('title', $item));
			else
				$title = Codes::beautify_title($item['title']);

			// initialize variables
			$prefix = $suffix = $icon = '';

			// flag sticky pages
			if($item['rank'] < 10000)
				$prefix .= STICKY_FLAG;

			// flag articles that are dead, or created or updated very recently
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $now))
				$prefix .= EXPIRED_FLAG;
			elseif($item['create_date'] >= $dead_line)
				$suffix .= NEW_FLAG;
			elseif($item['edit_date'] >= $dead_line)
				$suffix .= UPDATED_FLAG;

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
					$suffix = ' <span class="details">- '.Skin::build_number($item['hits'], i18n::s('hits')).'</span>';

				$items[$url] = array($prefix, Skin::strip($title, 30), $suffix, 'basic', NULL);
				continue;
			}

			// introduction
			$introduction = '';
			if(is_object($overlay))
				$introduction = $overlay->get_text('introduction', $item);
			else
				$introduction = $item['introduction'];

			// the introductory text
			if($introduction) {
				$suffix .= ' -&nbsp;'.Codes::beautify_introduction($introduction);

				// link to description, if any
				if($item['description'])
					$suffix .= ' '.Skin::build_link($url, MORE_IMG, 'more', i18n::s('View the page')).' ';

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
			if(isset($this->layout_variant) && ($item['anchor'] != $this->layout_variant)) {

				// the author
				if(isset($context['with_author_information']) && ($context['with_author_information'] == 'Y')) {
					if($item['create_name'] != $item['edit_name'])
						$details[] = sprintf(i18n::s('by %s, %s'), $item['create_name'], $item['edit_name']);
					else
						$details[] = sprintf(i18n::s('by %s'), $item['create_name']);
				}

				// the last action
				$details[] = Anchors::get_action_label($item['edit_action']).' '.Skin::build_date($item['edit_date']);

				// the number of hits
				if(Surfer::is_logged() && ($item['hits'] > 1))
					$details[] = Skin::build_number($item['hits'], i18n::s('hits'));

				// info on related files
				if($count = Files::count_for_anchor('article:'.$item['id'], TRUE))
					$details[] = sprintf(i18n::ns('%d file', '%d files', $count), $count);

				// info on related links
				if($count = Links::count_for_anchor('article:'.$item['id'], TRUE))
					$details[] = sprintf(i18n::ns('%d link', '%d links', $count), $count);

				// info on related comments
				if($count = Comments::count_for_anchor('article:'.$item['id'], TRUE))
					$details[] = sprintf(i18n::ns('%d comment', '%d comments', $count), $count);

				// rating
				if($item['rating_count'] && !(is_object($anchor) && $anchor->has_option('without_rating')))
					$details[] = Skin::build_link(Articles::get_url($item['id'], 'rate'), Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])), 'basic');

				// unusual ranks are signaled to associates and owners
				if(($item['rank'] != 10000) && Articles::is_owned($anchor, $item))
					$details[] = '{'.$item['rank'].'}';

			}

			// signal locked articles
			if(isset($item['locked']) && ($item['locked'] == 'Y'))
				$details[] = LOCKED_FLAG;

			// at the user page
			if(($this->layout_variant == 'no_author') && Surfer::get_id()) {
				if(Members::check('article:'.$item['id'], 'user:'.Surfer::get_id()))
					$label = i18n::s('Forget this page');
				else
					$label = i18n::s('Watch this page');
				$menu = array( 'users/track.php?anchor='.urlencode('article:'.$item['id']) => $label );
				$details [] = Skin::build_list($menu, 'menu');
			}

			// combine in-line details
			if(count($details))
				$suffix .= ucfirst(trim(implode(', ', $details))).BR;

			// links to sections and categories
			$anchors = array();

			// the main anchor link
			if(is_object($anchor) && (!isset($this->layout_variant) || ($item['anchor'] != $this->layout_variant)))
				$anchors[] = Skin::build_link($anchor->get_url(), ucfirst($anchor->get_title()), 'section');

			// list section and categories in the suffix
			if(@count($anchors))
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
			elseif(is_object($anchor))
				$icon = $anchor->get_thumbnail_url();

			// list all components for this item
			$items[$url] = array($prefix, $title, $suffix, 'article', $icon);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>