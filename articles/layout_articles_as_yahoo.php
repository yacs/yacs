<?php
/**
 * layout articles as an index page of Yahoo!
 *
 * With this layout several items are listed as well.
 * These can be either files or links, depending of relative availability of both kind of items.
 *
 * @see articles/articles.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Thierry Pinelli (ThierryP)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_yahoo extends Layout_interface {

	/**
	 * list articles
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

		// the number of related items to display
		if(!defined('YAHOO_LIST_SIZE'))
			define('YAHOO_LIST_SIZE', 3);

		// flag articles updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = strftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));
		$now = strftime('%Y-%m-%d %H:%M:%S');

		// first, build an array of articles
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
				$title = Skin::strip($item['title'], 50);

			// initialize variables
			$prefix = $suffix = $icon = '';

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

			// details
			$details = array();

			// count related sub-elements
			$related_count = 0;

			// info on related files
			if($count = Files::count_for_anchor('article:'.$item['id'], TRUE)) {
				$details[] = sprintf(i18n::ns('1 file', '%d files', $count), $count);
				$related_count += $count;
			}

			// info on related links
			if($count = Links::count_for_anchor('article:'.$item['id'], TRUE)) {
				$details[] = sprintf(i18n::ns('1 link', '%d links', $count), $count);
				$related_count += $count;
			}

			// info on related comments
			if($count = Comments::count_for_anchor('article:'.$item['id'], TRUE))
				$details[] = sprintf(i18n::ns('1 comment', '%d comments', $count), $count);

			// rank, for associates
			if(($item['rank'] != 10000) && Surfer::is_empowered())
				$details[] = '{'.$item['rank'].'}';

			// introduction
			if($item['introduction'])
				$suffix .= ' '.strip_tags(Codes::beautify(trim($item['introduction'])), '<a><br><img><li><ol><ul>');

			// append details to the suffix
			if(count($details))
				$suffix .= ' <span class="details">('.implode(', ', $details).')</span>';

			// add a head list of related links
			$details = array();

			// add related files if necessary
			if((count($details) < YAHOO_LIST_SIZE) && ($related = Files::list_by_date_for_anchor('article:'.$item['id'], 0, YAHOO_LIST_SIZE - count($details), 'compact'))) {
				foreach($related as $related_url => $label) {
					$sub_prefix = $sub_suffix = $sub_hover = '';
					if(is_array($label)) {
						$sub_prefix = $label[0];
						$sub_suffix = $label[2];
						if(@$label[5])
							$sub_hover = $label[5];
						$label = $label[1];
					}
					$details[] = $sub_prefix.Skin::build_link($related_url, $label, 'basic', $sub_hover).$sub_suffix;
				}
			}

			// add related links if necessary
			if((count($details) < YAHOO_LIST_SIZE) && ($related = Links::list_by_date_for_anchor('article:'.$item['id'], 0, YAHOO_LIST_SIZE - count($details), 'compact'))) {
				foreach($related as $related_url => $label) {
					$sub_prefix = $sub_suffix = $sub_hover = '';
					if(is_array($label)) {
						$sub_prefix = $label[0];
						$sub_suffix = $label[2];
						if(@$label[5])
							$sub_hover = $label[5];
						$label = $label[1];
					}
					$details[] = $sub_prefix.Skin::build_link($related_url, $label, 'basic', $sub_hover).$sub_suffix;
				}
			}

			// give me more
			if(count($details) && ($related_count > YAHOO_LIST_SIZE))
				$details[] = Skin::build_link($url, i18n::s('More').MORE_IMG, 'more', i18n::s('This article contains other information of interest'));

			// layout details
			if(count($details))
				$suffix .= BR.'<span class="small">'.YAHOO_ITEM_PREFIX.implode(YAHOO_ITEM_SUFFIX.YAHOO_ITEM_PREFIX, $details).YAHOO_ITEM_SUFFIX."</span>\n";

			// put the actual icon in the left column
			if(isset($item['thumbnail_url']))
				$icon = $item['thumbnail_url'];

			// some hovering title for this article
			$hover = i18n::s('Read the page');

			// list all components for this item --use basic link style to avoid prefix or suffix images, if any
			$items[$url] = array($prefix, $title, $suffix, 'basic', $icon, $hover);

		}

		// end of processing
		SQL::free($result);
		$text =& Skin::build_list($items, '2-columns');
		return $text;
	}

}

?>