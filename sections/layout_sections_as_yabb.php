<?php
/**
 * layout sections as boards in a yabb forum
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
 * The initial development of YACS forum has been heavily inspired by YABB.
 *
 * @link http://www.yabbforum.com/ Yet Another Bulletin Board
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Thierry Pinelli [email]contact@vdp-digital.com[/email]
 * @author Alexis Raimbault
 * @tester Anatoly
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_sections_as_yabb extends Layout_interface {

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

		// output as a string
		$text = '';

		// build a list of sections
		$family = '';
		$first = TRUE;
		while($item = SQL::fetch($result)) {

			// change the family
			if($item['family'] != $family) {
				$family = $item['family'];

				// close last table only if a section has been already listed
				if(!$first) {
				    $text .= Skin::table_suffix();
				}
				// show the family
				$text .= '<h2><span>'.$family.'&nbsp;</span></h2>'."\n"
					.Skin::table_prefix('yabb')
					.Skin::table_row(array(i18n::s('Board'), 'center='.i18n::s('Topics'), i18n::s('Last post')), 'header');
			} elseif($first) {
			    $text .= Skin::table_prefix('yabb');
			    $text .= Skin::table_row(array(i18n::s('Board'), 'center='.i18n::s('Topics'), i18n::s('Last post')), 'header');
			}

			// done with this case
			$first = FALSE;

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

			// use the title as a link to the page
			$title =& Skin::build_link($url, Codes::beautify_title($item['title']), 'basic', $hover);

			// also use a clickable thumbnail, if any
			if($item['thumbnail_url'])
				$prefix = Skin::build_link($url, '<img src="'.$item['thumbnail_url'].'" alt="" title="'.encode_field($hover).'" class="left_image" />', 'basic', $hover)
					.$prefix;

			// flag sections updated recently
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
				$suffix = EXPIRED_FLAG.' ';
			elseif($item['create_date'] >= $context['fresh'])
				$suffix = NEW_FLAG.' ';
			elseif($item['edit_date'] >= $context['fresh'])
				$suffix = UPDATED_FLAG.' ';

			// board introduction
			if($item['introduction'])
				$suffix .= '<br style="clear: none;" />'.Codes::beautify_introduction($item['introduction']);

			// more details
			$details = '';
			$more = array();

			// board moderators
			if($moderators = Sections::list_editors_by_name($item, 0, 7, 'comma5'))
				$more[] = sprintf(i18n::ns('Moderator: %s', 'Moderators: %s', count($moderators)), $moderators);

			// children boards
			if($children =& Sections::list_by_title_for_anchor('section:'.$item['id'], 0, COMPACT_LIST_SIZE, 'comma'))
				$more[] = sprintf(i18n::ns('Child board: %s', 'Child boards: %s', count($children)), Skin::build_list($children, 'comma'));

			// as a compact list
			if(count($more)) {
				$details .= '<ul class="compact">';
				foreach($more as $list_item) {
					$details .= '<li>'.$list_item.'</li>'."\n";
				}
				$details .= '</ul>'."\n";
			}

			// all details
			if($details)
				$details = BR.'<span class="details">'.$details."</span>\n";

			// count posts here, and in children sections --up to three level of depth
			if($anchors =& Sections::get_children_of_anchor('section:'.$item['id'])) {
				if($anchors2 =& Sections::get_children_of_anchor($anchors))
					$anchors2 = array_merge($anchors2, Sections::get_children_of_anchor($anchors2));

				$anchors = array_merge($anchors, $anchors2);
			}
			$anchors[] = 'section:'.$item['id'];
			if(!$count = Articles::count_for_anchor($anchors))
				$count = 0;

			// get last post
			$last_post = '--';
			$article =& Articles::get_newest_for_anchor($anchors, TRUE);
			if($article['id']) {

				// flag articles updated recently
				if(($article['expiry_date'] > NULL_DATE) && ($article['expiry_date'] <= $context['now']))
					$flag = EXPIRED_FLAG.' ';
				elseif($article['create_date'] >= $context['fresh'])
					$flag = NEW_FLAG.' ';
				elseif($article['edit_date'] >= $context['fresh'])
					$flag = UPDATED_FLAG.' ';
				else
					$flag = '';

				// title
				$last_post = Skin::build_link(Articles::get_permalink($article), Codes::beautify_title($article['title']), 'article');

				// last editor
				if($article['edit_date']) {

					// find a name, if any
					if($article['edit_name']) {

						// label the action
						if(isset($article['edit_action']))
							$action = Anchors::get_action_label($article['edit_action']);
						else
							$action = i18n::s('edited');

						// name of last editor
						$user = sprintf(i18n::s('%s by %s'), $action, Users::get_link($article['edit_name'], $article['edit_address'], $article['edit_id']));
					}

					$last_post .= $flag.BR.'<span class="tiny">'.$user.' '.Skin::build_date($article['edit_date']).'</span>';
				}

			}

			// this is another row of the output
			$text .= Skin::table_row(array($prefix.$title.$suffix.$details, 'center='.$count, $last_post));

		}

		// end of processing
		SQL::free($result);

		$text .= Skin::table_suffix();
		return $text;
	}
}

?>
