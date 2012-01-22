<?php
/**
 * layout sections as boards in a jive forum
 *
 * This script layouts sections as boards in a discussion forum.
 *
 * The title of each section is also a link to the section itself.
 * A title attribute of the link displays the reference to use to link to the page.
 *
 * Moderators are listed below each board, if any.
 * Moderators of a board are the members who have been explicitly assigned as editors of the related section.
 *
 * The script also lists children boards, if any.
 * This helps to provide a comprehensive view to forum surfers.
 *
 * @see sections/view.php
 *
 * This layout has been heavily inspired by TheServerSide.com.
 *
 * @link http://www.theserverside.com/discussions/index.tss
 *
 * @author Bernard Paques
 * @author Thierry Pinelli [email]contact@vdp-digital.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_sections_as_jive extends Layout_interface {

	/**
	 * list sections as topics in a forum
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	**/
	function layout($result) {
		global $context;

		// empty list
		if(!SQL::count($result)) {
			$output = array();
			return $output;
		}

		// layout in a table
		$text = Skin::table_prefix('wide');

		// 'even' is used for title rows, 'odd' for detail rows
		$class_title = 'odd';
		$class_detail = 'even';

		// build a list of sections
		$family = '';
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'links/links.php';
		while($item = SQL::fetch($result)) {

			// change the family
			if($item['family'] != $family) {
				$family = $item['family'];

				// show the family
				$text .= Skin::table_suffix()
					.'<h2><span>'.$family.'&nbsp;</span></h2>'."\n"
					.Skin::table_prefix('wide');

			}

			// get the related overlay, if any
			$overlay = Overlay::load($item, 'section:'.$item['id']);

			// get the main anchor
			$anchor =& Anchors::get($item['anchor']);

			// reset everything
			$prefix = $label = $suffix = $icon = '';

			// signal restricted and private sections
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// indicate the id in the hovering popup
			$hover = i18n::s('View the section');
			if(Surfer::is_member())
				$hover .= ' [section='.$item['id'].']';

			// the url to view this item
			$url = Sections::get_permalink($item);

			// use the title to label the link
			if(is_object($overlay))
				$title = Codes::beautify_title($overlay->get_text('title', $item));
			else
				$title = Codes::beautify_title($item['title']);

			// use the title as a link to the page
			$title =& Skin::build_link($url, $title, 'basic', $hover);

			// flag sections updated recently
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
				$suffix = EXPIRED_FLAG.' ';
			elseif($item['create_date'] >= $context['fresh'])
				$suffix = NEW_FLAG.' ';
			elseif($item['edit_date'] >= $context['fresh'])
				$suffix = UPDATED_FLAG.' ';

			// this is another row of the output
			$text .= '<tr class="'.$class_title.'"><th>'.$prefix.$title.$suffix.'</th><th>'.i18n::s('Poster').'</th><th>'.i18n::s('Messages').'</th><th>'.i18n::s('Last active').'</th></tr>'."\n";
			$count = 1;

			// get last posts for this board --avoid sticky pages
			if(preg_match('/\barticles_by_([a-z_]+)\b/i', $item['options'], $matches))
				$order = $matches[1];
			else
				$order = 'edition';
			if($articles =& Articles::list_for_anchor_by($order, 'section:'.$item['id'], 0, 5, 'raw', TRUE)) {

				foreach($articles as $id => $article) {

					// get the related overlay, if any
					$article_overlay = Overlay::load($article, 'article:'.$id);

					// flag articles updated recently
					if(($article['expiry_date'] > NULL_DATE) && ($article['expiry_date'] <= $context['now']))
						$flag = EXPIRED_FLAG.' ';
					elseif($article['create_date'] >= $context['fresh'])
						$flag = NEW_FLAG.' ';
					elseif($article['edit_date'] >= $context['fresh'])
						$flag = UPDATED_FLAG.' ';
					else
						$flag = '';

					// use the title to label the link
					if(is_object($article_overlay))
						$title = Codes::beautify_title($article_overlay->get_text('title', $article));
					else
						$title = Codes::beautify_title($article['title']);

					// title
					$title = Skin::build_link(Articles::get_permalink($article), $title, 'article');

					// poster
					$poster = Users::get_link($article['create_name'], $article['create_address'], $article['create_id']);

					// comments
					$comments = Comments::count_for_anchor('article:'.$article['id']);

					// last editor
					$action = '';
					if($article['edit_date']) {

						// label the action
						if(isset($article['edit_action']))
							$action = Anchors::get_action_label($article['edit_action']);
						else
							$action = i18n::s('edited');

						$action = '<span class="details">'.$action.' '.Skin::build_date($article['edit_date']).'</span>';
					}

					// this is another row of the output
					$text .= '<tr class="'.$class_detail.'"><td>'.$title.$flag.'</td><td>'.$poster.'</td><td style="text-align: center;">'.$comments.'</td><td>'.$action.'</td></tr>'."\n";

				}

			}

			// more details
			$details = array();

			// board introduction
			if($item['introduction'])
				$details[] = Codes::beautify_introduction($item['introduction']);

			// indicate the total number of threads here
			if(($count = Articles::count_for_anchor('section:'.$item['id'])) && ($count >= 5))
				$details[] = sprintf(i18n::s('%d threads'), $count).'&nbsp;&raquo;';

			// link to the section index page
			if($details)
				$details = Skin::build_link(Sections::get_permalink($item), join(' -&nbsp;', $details), 'basic');
			else
				$details = '';

			// add a command for new post
			$poster = '';
			if(Surfer::is_empowered())
				$poster = Skin::build_link('articles/edit.php?anchor='.urlencode('section:'.$item['id']), i18n::s('Add a page').'&nbsp;&raquo;', 'basic');

			// insert details in a separate row
			if($details || $poster)
				$text .= '<tr class="'.$class_detail.'"><td colspan="3">'.$details.'</td><td>'.$poster.'</td></tr>'."\n";

			// more details
			$more = array();

			// board moderators
			if($moderators = Sections::list_editors_by_login($item, 0, 7, 'comma5'))
				$more[] = sprintf(i18n::ns('Moderator: %s', 'Moderators: %s', count($moderators)), $moderators);

			// children boards
			if($children =& Sections::list_by_title_for_anchor('section:'.$item['id'], 0, COMPACT_LIST_SIZE, 'compact'))
				$more[] = sprintf(i18n::ns('Child board: %s', 'Child boards: %s', count($children)), Skin::build_list($children, 'comma'));

			// as a compact list
			if(count($more)) {
				$content = '<ul class="compact">';
				foreach($more as $list_item) {
					$content .= '<li>'.$list_item.'</li>'."\n";
				}
				$content .= '</ul>'."\n";

				// insert details in a separate row
				$text .= '<tr class="'.$class_detail.'"><td colspan="4">'.$content.'</td></tr>'."\n";

			}

		}

		// end of processing
		SQL::free($result);

		$text .= Skin::table_suffix();
		return $text;
	}
}

?>
