<?php
/**
 * layout articles as spray does
 *
 * This layout has been designed to track issues, or similar simple workflows.
 * Some columns are populated by overlays embedded into articles.
 *
 * You can build a nice bug tracking system by combining this layout with the overlay issue.
 *
 * @see overlays/issue.php
 *
 * Final rendering is a table with following columns:
 * - id - the article id, and is also a clickable link to the page
 * - type - type (provided by overlay)
 * - summary - including the title, which is also a clickable link to the page, tags, etc
 * - status - 'on-going', 'closed', etc. (provided by overlay)
 * - update - dates of last modification
 * - progress - a small image to reflect task progession (between 0% and 100%) (provided by overlay)
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_spray extends Layout_interface {

	/**
	 * the preferred number of items for this layout
	 *
	 * @return 50
	 *
	 * @see skins/layout.php
	 */
	function items_per_page() {
		return 50;
	}

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
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'links/links.php';
		include_once $context['path_to_root'].'overlays/overlay.php';
		while($item =& SQL::fetch($result)) {

			// get the related overlay
			$overlay = Overlay::load($item);

			// get the anchor
			$anchor =& Anchors::get($item['anchor']);

			// the url to view this item
			$url =& Articles::get_permalink($item);

			// reset everything
			$id = $type = $summary = $status = $update = $progress = '';

			// link to the page
			$id = Skin::build_link($url, $item['id'], 'basic');

			// type is provided by the overlay
			if(is_object($overlay))
				$type = $overlay->get_value('type', '');

			// signal articles to be published
			if(!isset($item['publish_date']) || ($item['publish_date'] <= NULL_DATE) || ($item['publish_date'] > gmstrftime('%Y-%m-%d %H:%M:%S')))
				$summary .= DRAFT_FLAG;

			// signal restricted and private articles
			if($item['active'] == 'N')
				$summary .= PRIVATE_FLAG.' ';
			elseif($item['active'] == 'R')
				$summary .= RESTRICTED_FLAG.' ';

			// indicate the id in the hovering popup
			$hover = i18n::s('View the page');
			if(Surfer::is_member())
				$hover .= ' [article='.$item['id'].']';

			// use the title to label the link
			if(is_object($overlay))
				$label = Codes::beautify_title($overlay->get_text('title', $item));
			else
				$label = Codes::beautify_title($item['title']);

			// use the title as a link to the page
			$summary .= Skin::build_link($url, $label, 'basic', $hover);

			// signal locked articles
			if(isset($item['locked']) && ($item['locked'] == 'Y'))
				$summary .= ' '.LOCKED_FLAG;

			// flag articles updated recently
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $now))
				$summary .= ' '.EXPIRED_FLAG;
			elseif($item['create_date'] >= $dead_line)
				$summary .= ' '.NEW_FLAG;
			elseif($item['edit_date'] >= $dead_line)
				$summary .= ' '.UPDATED_FLAG;

			// insert overlay data, if any
			if(is_object($overlay))
				$summary .= $overlay->get_text('list', $item);

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

			// combine in-line details
			if(count($details))
				$summary .= ' <span class="details">'.trim(implode(' ', $details)).'</span>';

			// dates
			$summary .= BR.'<span class="details">'.join(BR, Articles::build_dates($anchor, $item)).'</span>';

			// display all tags
			if($item['tags'])
				$summary .= BR.'<span class="tags">'.Skin::build_tags($item['tags'], 'article:'.$item['id']).'</span>';

			// status value
			if(is_object($overlay))
				$status = $overlay->get_value('status', '');

			// progress value
			if(is_object($overlay))
				$progress = $overlay->get_value('progress', '');

			// this is another row of the output
			$cells = array($id, $type, $summary, $status, $progress);

			// append this row
			$rows[] = $cells;

		}

		// end of processing
		SQL::free($result);

		// headers
		$headers = array(i18n::s('Number'), i18n::s('Type'), i18n::s('Information'), i18n::s('Status'), i18n::s('Progress'));

		// return a sortable table
		$text .= Skin::table($headers, $rows, 'grid');
		return $text;
	}
}

?>