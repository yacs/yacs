<?php
/**
 * layout articles as in a manual
 *
 * With this layout each article is considered as being one page of a structured electronic document.
 * This script lists articles like a large table of content, with one line per article.
 *
 * Articles are ordered based on rank values.
 *
 * At the level one, entries are based on &lt;h2&gt; HTML tags.
 * At levels two, three and four, entries are ordinary items (i.e., &lt;li&gt;) of compound unordered lists (&lt;ul&gt;).
 *
 * If some nodes are missing this layout will create dummy entries to maintain the overall structure of the
 * hierarchical tree.
 *
 * Largely inspired from php manual (www.php.net/manual/en).
 *
 * @link http://www.php.net/manual/en/index.php PHP Manual
 * @link http://dev.mysql.com/doc/mysql/en/index.html MySQL Reference Manual
 *
 * @see sections/view.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Thierry Pinelli (ThierryP)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_manual extends Layout_interface {

	/**
	 * the preferred number of items for this layout
	 *
	 * The compact format of this layout allows a high number of items to be listed
	 *
	 * @return int the optimised count of items fro this layout
	 */
	function items_per_page() {
		return 300;
	}

	/**
	 * list articles as a table of content of a manual
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	**/
	function &layout(&$result) {
		global $context;

		// we return some text
		$text = '';

		// empty list
		if(!SQL::count($result))
			return $text;

		// flag articles updated recently
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		if(!isset($context['site_revisit_after']) || ($context['site_revisit_after'] < 1))
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));

		// build a list of articles
		include_once $context['path_to_root'].'links/links.php';
		include_once $context['path_to_root'].'overlays/overlay.php';
		$text .= '<ul class="manual">';
		while($item =& SQL::fetch($result)) {

			// get the related overlay, if any
			$overlay = Overlay::load($item);

			// get the anchor
			$anchor =& Anchors::get($item['anchor']);

			// the url to view this item
			$url =& Articles::get_permalink($item);

			// use the title to label the link
			if(is_object($overlay))
				$title = Codes::beautify_title($overlay->get_text('title', $item));
			else
				$title = Codes::beautify_title($item['title']);

			// reset everything
			$prefix = $label = $suffix = $icon = $details = '';

			// signal articles to be published
			if(!isset($item['publish_date']) || ($item['publish_date'] <= NULL_DATE) || ($item['publish_date'] > gmstrftime('%Y-%m-%d %H:%M:%S')))
				$prefix .= DRAFT_FLAG;

			// signal restricted and private articles
			if(isset($item['active']) && ($item['active'] == 'N'))
				$prefix .= PRIVATE_FLAG.' ';
			elseif(isset($item['active']) && ($item['active'] == 'R'))
				$prefix .= RESTRICTED_FLAG.' ';

			// flag articles updated recently
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $now))
				$suffix .= ' '.EXPIRED_FLAG;
			elseif($item['create_date'] >= $dead_line)
				$suffix .= ' '.NEW_FLAG;
			elseif($item['edit_date'] >= $dead_line)
				$suffix .= ' '.UPDATED_FLAG;

			// attachment details
			$details = array();

			// info on related files
			if($count = Files::count_for_anchor('article:'.$item['id'], TRUE)) {
				Skin::define_img('FILES_LIST_IMG', 'files/list.gif');
				$details[] = FILES_LIST_IMG.sprintf(i18n::ns('%d file', '%d files', $count), $count);
			}

			// info on related links
			if($count = Links::count_for_anchor('article:'.$item['id'], TRUE)) {
				Skin::define_img('LINKS_LIST_IMG', 'links/list.gif');
				$details[] = LINKS_LIST_IMG.sprintf(i18n::ns('%d link', '%d links', $count), $count);
			}

			// rating
			if($item['rating_count'])
				$details[] = Skin::build_link(Articles::get_url($item['id'], 'rate'), Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])), 'basic');

			// describe attachments
			if(count($details))
				$suffix .= ' <span class="details">'.join(', ', $details).'</span>';

			// display all tags
			if($item['tags'])
				$suffix .= ' <span class="details">- '.Skin::build_tags($item['tags'], 'article:'.$item['id']).'</span>';

			// make a link
			$label = $prefix.Skin::build_link($url, $title, 'basic').$suffix;

			// use the title as a link to the page
			$text .= '<li>'.$label."</li>\n";

		}

		$text .= '</ul>'."\n";


		// end of processing
		SQL::free($result);
		return $text;

	}
}

?>