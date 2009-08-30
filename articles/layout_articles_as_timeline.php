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
			return $text;

		// sanity check
		if(!isset($this->layout_variant))
			$this->layout_variant = NULL;

		// flag articles updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// process all items in the list
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'files/files.php';
		include_once $context['path_to_root'].'links/links.php';
		include_once $context['path_to_root'].'overlays/overlay.php';
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
				$title = $overlay->get_live_title($item, $this->layout_variant);
			else
				$title = Codes::beautify_title($item['title']);

			// initialize variables
			$prefix = $suffix = $icon = '';

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

			// insert overlay data, if any
			if(is_object($overlay))
				$suffix .= $overlay->get_text('list', $item, $this->layout_variant);

			// the hovering title
			if($item['introduction'] && ($context['skins_with_details'] == 'Y'))
				$hover = strip_tags(Codes::beautify_introduction($item['introduction']));

			// add a link to the main page
			else
				$hover = i18n::s('View the page');

			// help members to reference this page
			if(Surfer::is_member())
				$hover .= ' [article='.$item['id'].']';

			// add an image if available
			$icon = '';
			if($item['thumbnail_url'])
				$icon = $item['thumbnail_url'];

			// or inherit from the anchor
			elseif(is_object($anchor))
				$icon = $anchor->get_thumbnail_url();

			// format the image
			if($icon)
				$icon = Skin::build_link($url, '<img src="'.$icon.'" />', 'basic', $hover);
			
			// list all components for this item
			if($odd = !$odd)
				$class = ' class="odd"';
			else
				$class = ' class="even"';
				
			// use a table to layout the image properly
			if($icon)
				$text .= '<div'.$class.'><table class="decorated"><tr><td class="image" style="text-align: center">'.$icon.'</td><td class="content">'.$prefix.Skin::build_link($url, Skin::strip($title, 30), 'basic', $hover).$suffix.'</td></tr></table></div>';
			else
				$text .= '<div'.$class.'>'.$prefix.Skin::build_link($url, Skin::strip($title, 30), 'basic', $hover).$suffix.'</div>';

		}

		// end of processing
		SQL::free($result);
		return $text;
	}

}

?>