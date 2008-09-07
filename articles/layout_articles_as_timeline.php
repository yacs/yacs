<?php
/**
 * layout articles as a timeline
 *
 * @see articles/articles.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_timeline extends Layout_interface {

	/**
	 * the preferred number of items for this layout
	 *
	 * @return int the optimised count of items for this layout
	 */
	function items_per_page() {
		return 7;
	}

	/**
	 * list articles
	 *
	 * @param resource the SQL result
	 * @return string
	 *
	 * @see skins/layout.php
	**/
	function &layout(&$result) {
		global $context;

		// we return some text
		$text = '';

		// empty list
		if(!SQL::count($result))
			return $items;

		// flag articles updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));

		// process all items in the list
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'files/files.php';
		include_once $context['path_to_root'].'links/links.php';
		include_once $context['path_to_root'].'overlays/overlay.php';
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		$odd = TRUE;
		while($item =& SQL::fetch($result)) {

			// get the related overlay
			$overlay = Overlay::load($item);

			// get the anchor
			$anchor =& Anchors::get($item['anchor']);

			// the url to view this item
			$url =& Articles::get_permalink($item);

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

			// some details
			$details = array();

			// info on related files --optional
			if($count = Files::count_for_anchor('article:'.$item['id'], TRUE))
				$details[] = sprintf(i18n::ns('%d file', '%d files', $count), $count);

			// info on related comments --mandatory
			if($count = Comments::count_for_anchor('article:'.$item['id'], FALSE))
				$details[] = sprintf(i18n::ns('%d comment', '%d comments', $count), $count);

			// info on related links --optional
			if($count = Links::count_for_anchor('article:'.$item['id'], TRUE))
				$details[] = sprintf(i18n::ns('%d link', '%d links', $count), $count);

			// details
			if(count($details))
				$suffix .= ' <span class="details">('.ucfirst(implode(', ', $details)).')</span>';

			// flag popular pages
			if($item['hits'] > 300)
				$suffix .= POPULAR_FLAG;

			// last contribution
			if($item['edit_action'])
				$action = get_action_label($item['edit_action']).' ';
			else
				$action = i18n::s('edited');

			if($item['edit_name'])
				$suffix .= '<br /><span class="details">'.sprintf(i18n::s('%s by %s %s'), $action, Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date'])).'</span>';
			else
				$suffix .= '<br /><span class="details">'.$action.' '.Skin::build_date($item['edit_date']).'</span>';

			// flag articles updated recently
			if($item['create_date'] >= $dead_line)
				$suffix .= NEW_FLAG;
			elseif($item['edit_date'] >= $dead_line)
				$suffix .= UPDATED_FLAG;

			// the hovering title
			if($item['introduction'] && ($context['skins_with_details'] == 'Y'))
				$hover = strip_tags(Codes::strip($item['introduction']));

			// add a link to the main page
			else
				$hover = i18n::s('View the page');

			// help members to reference this page
			if(Surfer::is_member())
				$hover .= ' [article='.$item['id'].']';

			// list all components for this item
			if($odd = !$odd)
				$class = ' class="odd"';
			else
				$class = ' class="even"';
			$text .= '<div'.$class.'>'.$prefix.Skin::build_link($url, Skin::strip($title, 30), 'basic', $hover).$suffix.'</div>';

		}

		// end of processing
		SQL::free($result);
		return $text;
	}

}

?>