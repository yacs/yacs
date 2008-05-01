<?php
/**
 * layout articles as a compact list
 *
 * @see articles/articles.php
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @author Thierry Pinelli (ThierryP)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_compact extends Layout_interface {

	/**
	 * the preferred number of items for this layout
	 *
	 * @return int the optimised count of items for this layout
	 */
	function items_per_page() {
		return 100;
	}

	/**
	 * list articles
	 *
	 * @param resource the SQL result
	 * @return array
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

		// flag articles updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));

		// process all items in the list
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'overlays/overlay.php';
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		while($item =& SQL::fetch($result)) {

			// get the related overlay
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

			// initialize variables
			$prefix = $suffix = '';

			// flag articles that are dead, or created or updated very recently
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $now))
				$prefix .= EXPIRED_FLAG;

			// signal articles to be published
			if(($item['publish_date'] <= NULL_DATE) || ($item['publish_date'] > $now))
				$prefix .= DRAFT_FLAG;

			// signal restricted and private articles
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// count related comments, if any
			if($count = Comments::count_for_anchor('article:'.$item['id'], TRUE))
				$suffix .= ' ('.$count.')';

			// flag articles updated recently
			if($item['create_date'] >= $dead_line)
				$suffix .= NEW_FLAG;
			elseif($item['edit_date'] >= $dead_line)
				$suffix .= UPDATED_FLAG;

			// the hovering title
			if($item['introduction'])
				$hover = strip_tags(Codes::strip($item['introduction']));

			// add a link to the main page
			else
				$hover = i18n::s('Read this page');

			// help members to reference this page
			if(Surfer::is_member())
				$hover .= ' [article='.$item['id'].']';

			// list all components for this item
			$items[$url] = array($prefix, Skin::strip($title, 30), $suffix, 'basic', NULL, $hover);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>