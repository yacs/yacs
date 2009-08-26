<?php
/**
 * layout articles as rows in a table
 *
 * The title of each article is also a link to the article itself.
 * A title attribute of the link displays the reference to use to link to the page.
 *
 * @see sections/view.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Thierry Pinelli (ThierryP)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_table extends Layout_interface {

	/**
	 * list articles as rows in a table
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
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));

		// build a list of articles
		$rows = array();
		include_once $context['path_to_root'].'categories/categories.php';
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'files/files.php';
		include_once $context['path_to_root'].'links/links.php';
		include_once $context['path_to_root'].'overlays/overlay.php';
		while($item =& SQL::fetch($result)) {

			// get the related overlay
			$overlay = Overlay::load($item);

			// get the anchor
			$anchor =& Anchors::get($item['anchor']);

			// the url to view this item
			$url =& Articles::get_permalink($item);

			// reset the rendering engine between items
			Codes::initialize($url);

			// reset everything
			$title = $abstract = $author = '';

			// signal articles to be published
			if(!isset($item['publish_date']) || ($item['publish_date'] <= NULL_DATE) || ($item['publish_date'] > gmstrftime('%Y-%m-%d %H:%M:%S')))
				$title .= DRAFT_FLAG;

			// signal restricted and private articles
			if($item['active'] == 'N')
				$title .= PRIVATE_FLAG.' ';
			elseif($item['active'] == 'R')
				$title .= RESTRICTED_FLAG.' ';

			// indicate the id in the hovering popup
			$hover = i18n::s('View the page');
			if(Surfer::is_member())
				$hover .= ' [article='.$item['id'].']';

			// use the title to label the link
			if(is_object($overlay) && is_callable(array($overlay, 'get_live_title')))
				$label = $overlay->get_live_title($item);
			else
				$label = ucfirst(Codes::beautify_title(strip_tags($item['title'], '<br><div><img><p><span>')));

			// use the title as a link to the page
			$title .= Skin::build_link($url, $label, 'basic', $hover);

			// flag articles updated recently
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $now))
				$title .= EXPIRED_FLAG.' ';
			elseif($item['create_date'] >= $dead_line)
				$title .= NEW_FLAG.' ';
			elseif($item['edit_date'] >= $dead_line)
				$title .= UPDATED_FLAG.' ';

			// the icon
			if($item['thumbnail_url'])
				$abstract .= '<a href="'.$context['url_to_root'].$url.'"><img src="'.$item['thumbnail_url'].'" class="right_image" alt="" /></a>';

			// the introductory text
			if($item['introduction'])
				$abstract .= Codes::beautify_introduction($item['introduction']);

			// insert overlay data, if any
			if(is_object($overlay))
				$abstract .= $overlay->get_text('list', $item);

			// make some abstract out of main text
			if(!$item['introduction'] && ($context['skins_with_details'] == 'Y'))
				$abstract .= Skin::cap(Codes::beautify($item['description'], $item['options']), 50);

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

			// comments
			if($count = Comments::count_for_anchor('article:'.$item['id'], TRUE)) {
				Skin::define_img('COMMENTS_LIST_IMG', 'comments/list.gif');
				$details[] = Skin::build_link(Comments::get_url('article:'.$item['id'], 'list'), COMMENTS_LIST_IMG.sprintf(i18n::ns('%d comment', '%d comments', $count), $count));
			}

			// describe attachments
			if(count($details))
				$abstract .= '<p class="details">'.join(', ', $details).'</p>';

			// anchors
			$anchors = array();
			if($members =& Members::list_categories_by_title_for_member('article:'.$item['id'], 0, 3, 'raw')) {
				foreach($members as $category_id => $attributes) {
					$anchors[] = Skin::build_link(Categories::get_permalink($attributes), $attributes['title'], 'category');
				}
			}
			if(@count($anchors))
				$abstract .= BR.'<span class="details">'.sprintf(i18n::s('Categories: %s'), implode(', ', $anchors)).'</span>';

			// poster name
			if(isset($context['with_author_information']) && ($context['with_author_information'] == 'Y')) {
				if($item['create_name'])
					$author = Users::get_link($item['create_name'], $item['create_address'], $item['create_id']);
				else
					$author = Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']);
			}

			// more details
			$details = array();

			// posting date
			if($item['create_date'])
				$details[] = Skin::build_date($item['create_date']);

			// last modification by creator, and less than 24 hours between creation and last edition
			if(($item['create_date'] > NULL_DATE) && ($item['create_id'] == $item['edit_id'])
					&& (strtotime($item['create_date'])+24*60*60 >= strtotime($item['edit_date'])))
				;

			// publication is the last action
			elseif(($item['publish_date'] > NULL_DATE) && strpos($item['edit_action'], ':publish'))
				;

			// the last action
			else
				$details[] = get_action_label($item['edit_action']).' '.Skin::build_date($item['edit_date']);

			// signal locked articles
			if(isset($item['locked']) && ($item['locked'] == 'Y'))
				$details[] = LOCKED_FLAG;

			// rating
			if($item['rating_count'] && !(is_object($anchor) && $anchor->has_option('without_rating')))
				$details[] = Skin::build_link(Articles::get_url($item['id'], 'rate'), Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])), 'basic');

			// page details
			if(count($details))
				$details = '<p class="details">'.join(', ', $details).'</p>';

			// this is another row of the output -- title, abstract, (author,) details
			if(isset($context['with_author_information']) && ($context['with_author_information'] == 'Y'))
				$cells = array($title, $abstract, $author, $details);
			else
				$cells = array($title, $abstract, $details);

			// append this row
			$rows[] = $cells;

		}

		// end of processing
		SQL::free($result);

		// headers
		if(isset($context['with_author_information']) && ($context['with_author_information'] == 'Y'))
			$headers = array(i18n::s('Topic'), i18n::s('Abstract'), i18n::s('Poster'), i18n::s('Details'));
		else
			$headers = array(i18n::s('Topic'), i18n::s('Abstract'), i18n::s('Details'));

		// return a sortable table
		$text .= Skin::table($headers, $rows, 'grid');
		return $text;
	}
}

?>