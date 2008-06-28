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
	function &layout(&$result) {
		global $context;

		// empty list
		if(!SQL::count($result)) {
			$output = array();
			return $output;
		}

		// flag sections updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// layout in a table
		$text = Skin::table_prefix('wide');

		// 'even' is used for title rows, 'odd' for detail rows
		$class_title = 'odd';
		$class_detail = 'even';

		// build a list of sections
		$family = '';
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'files/files.php';
		include_once $context['path_to_root'].'links/links.php';
		while($item =& SQL::fetch($result)) {

			// change the family
			if($item['family'] != $family) {
				$family = $item['family'];

				$text .= '<tr class="'.$class_title.'"><td class="family" colspan="4">'.$family.'&nbsp;</td></tr>'."\n";
			}

			// reset everything
			$prefix = $label = $suffix = $icon = '';

			// signal restricted and private sections
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG.' ';
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG.' ';

			// indicate the id in the hovering popup
			$hover = i18n::s('Read the section');
			if(Surfer::is_member())
				$hover .= ' [section='.$item['id'].']';

			// the url to view this item
			$url = Sections::get_url($item['id'], 'view', $item['title'], $item['nick_name']);

			// use the title as a link to the page
			$title =& Skin::build_link($url, Codes::beautify_title($item['title']), 'basic', $hover);

			// flag sections updated recently
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $now))
				$suffix = EXPIRED_FLAG.' ';
			elseif($item['create_date'] >= $dead_line)
				$suffix = NEW_FLAG.' ';
//			elseif($item['edit_date'] >= $dead_line)
//				$suffix = UPDATED_FLAG.' ';

			// this is another row of the output
			if((isset($stats['count']) && $stats['count']) || Surfer::is_empowered()) {
				$text .= '<tr class="'.$class_title.'"><th>'.$prefix.$title.$suffix.'</th><th>'.i18n::s('Poster').'</th><th>'.i18n::s('Messages').'</th><th>'.i18n::s('Last active').'</th></tr>'."\n";
			}
			$count = 1;

			// get last posts for this board --avoid sticky pages
			if(preg_match('/\barticles_by_title\b/i', $item['options']))
				$order = 'title';
			elseif(preg_match('/\barticles_by_publication\b/i', $item['options']))
				$order = 'publication';
			elseif(preg_match('/\barticles_by_rating\b/i', $item['options']))
				$order = 'rating';
			elseif(preg_match('/\barticles_by_reverse_rank\b/i', $item['options']))
				$order = 'reverse_rank';
			else
				$order = 'date';
			if($articles =& Articles::list_for_anchor_by($order, 'section:'.$item['id'], 0, 5, 'raw', TRUE)) {

				foreach($articles as $id => $article) {

					// flag articles updated recently
					if(($article['expiry_date'] > NULL_DATE) && ($article['expiry_date'] <= $now))
						$flag = EXPIRED_FLAG.' ';
					elseif($article['create_date'] >= $dead_line)
						$flag = NEW_FLAG.' ';
					elseif($article['edit_date'] >= $dead_line)
						$flag = UPDATED_FLAG.' ';
					else
						$flag = '';

					// title
					$title = Skin::build_link(Articles::get_url($article['id'], 'view', $article['nick_name']), Codes::beautify_title($article['title']), 'article');

					// poster
					$poster = Users::get_link($article['create_name'], $article['create_address'], $article['create_id']);

					// comments
					$comments = Comments::count_for_anchor('article:'.$article['id']);

					// last editor
					$action = '';
					if($article['edit_date']) {

						// label the action
						if(isset($article['edit_action']))
							$action = get_action_label($article['edit_action']);
						else
							$action = i18n::s('edited');

						$action = '<span class="details">'.$action.' '.Skin::build_date($article['edit_date']).'</span>';
					}

					// this is another row of the output
					$text .= '<tr class="'.$class_detail.'"><td>'.$title.$flag.'</td><td>'.$poster.'</td><td style="text-align: center;">'.$comments.'</td><td>'.$action.'</td></tr>'."\n";

				}

			}

			// more details
			$details = '';

			// board introduction
			if($item['introduction'])
				$details = Codes::beautify($item['introduction']);

			// indicate the total number of threads here
			if(($count = Articles::count_for_anchor('section:'.$item['id'])) && ($count >= 5))
				$details .= ' -&nbsp;'.sprintf(i18n::s('%d threads'), $count).'&nbsp;&raquo;';

			// link to the section index page
			if($details)
				$details = Skin::build_link(Sections::get_url($item['id'], 'view', $item['title'], $item['nick_name']), $details, 'basic');

			// add a command for new post
			$poster = '';
			if(Surfer::is_empowered())
				$poster = Skin::build_link('articles/edit.php?anchor='.urlencode('section:'.$item['id']), i18n::s('Write a page').'&nbsp;&raquo;', 'basic');

			// insert details in a separate row
			if($details || $poster)
				$text .= '<tr class="'.$class_detail.'"><td colspan="3">'.$details.'</td><td>'.$poster.'</td></tr>'."\n";

			// more details
			$more = array();

			// board moderators
			if($moderators = Members::list_editors_by_name_for_member('section:'.$item['id'], 0, COMPACT_LIST_SIZE, 'compact'))
				$more[] = sprintf(i18n::ns('Moderator: %s', 'Moderators: %s', count($moderators)), Skin::build_list($moderators, 'comma'));

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