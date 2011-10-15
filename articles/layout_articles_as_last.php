<?php
/**
 * layout articles as topics, including last contribution
 *
 * This script layouts articles as discussed topics, with the last comment attached.
 * The result can be put in a mail message, like Linked In is doing.
 *
 * @see sections/view.php
 *
 * The title of each article is also a link to the article itself.
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_last extends Layout_interface {

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
	 * list articles as topics in a forum
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

		// build a list of articles
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'overlays/overlay.php';
		while($item =& SQL::fetch($result)) {

			// get the related overlay
			$overlay = Overlay::load($item, 'article:'.$item['id']);

			// get the anchor
			$anchor =& Anchors::get($item['anchor']);

			// the url to view this item
			$url = Articles::get_permalink($item);

			// build a title
			if(is_object($overlay))
				$title = Codes::beautify_title($overlay->get_text('title', $item));
			else
				$title = Codes::beautify_title($item['title']);

			// reset everything
			$prefix = $label = $suffix = $icon = '';

			// signal articles to be published
			if(!isset($item['publish_date']) || ($item['publish_date'] <= NULL_DATE) || ($item['publish_date'] > gmstrftime('%Y-%m-%d %H:%M:%S')))
				$prefix .= DRAFT_FLAG;

			// signal restricted and private articles
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG.' ';
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG.' ';

			// flag expired articles, and articles updated recently
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
				$suffix = EXPIRED_FLAG.' ';
			elseif($item['create_date'] >= $context['fresh'])
				$suffix = NEW_FLAG.' ';
			elseif($item['edit_date'] >= $context['fresh'])
				$suffix = UPDATED_FLAG.' ';

			// rating
			if($item['rating_count'] && !(is_object($anchor) && $anchor->has_option('without_rating')))
				$suffix .= ' '.Skin::build_link(Articles::get_url($item['id'], 'rate'), Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])), 'basic');

			// indicate the id in the hovering popup
			$hover = i18n::s('View the page');
			if(Surfer::is_member())
				$hover .= ' [article='.$item['id'].']';

			// use the title as a link to the page
			$title = $prefix.Skin::build_link($url, ucfirst($title), 'basic', $hover).$suffix;

			$teaser = '';

			// the introductory text
			$introduction = '';
			if(is_object($overlay))
				$introduction = $overlay->get_text('introduction', $item);
			elseif($item['introduction'])
				$introduction = $item['introduction'];
			if($introduction)
				$teaser .= Codes::beautify_introduction($introduction);

			// insert overlay data, if any
			if(is_object($overlay))
				$teaser .= $overlay->get_text('list', $item);

			// the creator of this article
			$starter = '';
			if($item['create_name'])
				$starter = Users::get_link($item['create_name'], $item['create_address'], $item['create_id']);

			// the last editor
			$details = '';
			if($item['edit_date']) {

				// find a name, if any
				$user = '';
				if($item['edit_name']) {

					// label the action
					if(isset($item['edit_action']))
						$user .= Anchors::get_action_label($item['edit_action']).' ';

					// name of last editor
					$user .= sprintf(i18n::s('by %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']));
				}

				$details .= $user.' '.Skin::build_date($item['edit_date']);
			}

			// signal locked articles
			if(isset($item['locked']) && ($item['locked'] == 'Y') && Articles::is_owned($item, $anchor))
				$details .= ', '.LOCKED_FLAG;

			// poster details
			if($details)
				$details = '<p class="details">'.$details."</p>\n";

			$comments_link = '';
			if($count = Comments::count_for_anchor('article:'.$item['id']))
				$comments_link = Skin::build_link($context['url_to_root'].$url, sprintf(i18n::ns('%d comment', '%d comments', $count), $count));

			// produce a full table
			$text .= '<table width="100%" cellspacing="0" cellpadding="0" border="0" class="layout" style="margin-top:15px;margin-bottom:10px;border-bottom:1px dotted #ccc">'."\n"
				.'<tbody>'."\n"
				.'<tr>'."\n"
				.'<td style="font-size:13px"><strong>'.$title.'</strong></td>'."\n"
				.'<td style="text-align:right;font-size:10px;white-space:nowrap;width:20%">'."\n"
				.$comments_link."\n"
				.'</td>'."\n"
				.'</tr>'."\n"
				.'<tr>'."\n"
				.'<td colspan="2">'."\n"
				.$teaser
				.'<p class="details" style="margin:3px 0 20px">'.sprintf(i18n::s('Started by %s'), $starter).'</p>'."\n"
				.'</td>'."\n"
				.'</tr>'."\n";

			// get last contribution for this page
			if($comment = Comments::get_newest_for_anchor('article:'.$item['id'])) {

				// last contributor
				$contributor = Users::get_link($comment['create_name'], $comment['create_address'], $comment['create_id']);

				// last contribution
				$contribution = $comment['description'];

				// display signature, but not for notifications
				if($comment['type'] != 'notification')
					$contribution .= Users::get_signature($comment['create_id']);

				$text .= '<tr>'."\n"
					.'<td colspan="2">'."\n"
					.'<div style="border-left:2px solid #ccc;margin:7px 10px 20px 7px;padding-left:10px;font-size:12px">'."\n"
					.ucfirst(trim(Codes::beautify($contribution)))."\n"
					.'<br>'."\n"
					.'<a href="'.$context['url_to_root'].$url.'" style="display: block; margin-top: 10px;">'."\n"
					.i18n::s('More').' &raquo;'."\n"
					.'</a>'."\n"
					.'<span class="details" style="display:block;margin-top:3px">'.sprintf(i18n::s('By %s'), $contributor).'</span>'."\n"
					.'</div>'."\n"
					.'</td>'."\n"
					.'</tr>'."\n";

			}

			$text .= '</tbody>'."\n"
				.'</table>';


		}

		// end of processing
		SQL::free($result);

		// make a sortable table
		return $text;
	}
}

?>