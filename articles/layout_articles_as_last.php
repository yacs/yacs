<?php
/**
 * layout articles as topics, including last contribution
 *
 * This script layouts articles as discussed topics, with the last comment attached.
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
	 * @return 25
	 *
	 * @see skins/layout.php
	 */
	function items_per_page() {
		return 20;
	}

	/**
	 * list articles as topics in a forum
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	**/
	function layout($result) {
		global $context;

		// we return some text
		$text = '';

		// empty list
		if(!SQL::count($result))
			return $text;

		// allow for complete styling
		$text = '<div class="last_articles">';

		// build a list of articles
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'links/links.php';
		while($item = SQL::fetch($result)) {

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
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// flag expired articles
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
				$prefix .= EXPIRED_FLAG.' ';

			// signal locked articles
			if(isset($item['locked']) && ($item['locked'] == 'Y') && Articles::is_owned($item, $anchor))
				$suffix .= ' '.LOCKED_FLAG;

			// rating
			if($item['rating_count'] && !(is_object($anchor) && $anchor->has_option('without_rating')))
				$suffix .= ' '.Skin::build_link(Articles::get_url($item['id'], 'like'), Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])), 'basic');

			// indicate the id in the hovering popup
			$hover = i18n::s('View the page');
			if(Surfer::is_member())
				$hover .= ' [article='.$item['id'].']';

			// one box per update
			$text .= '<div class="last_article" >';

			// use the title as a link to the page
			$text .= Skin::build_block($prefix.ucfirst($title).$suffix, 'header1');

			// some details about this page
			$details = array();

			// the creator of this article
			if($item['create_name']) {
				$starter = sprintf(i18n::s('Started by %s'), Users::get_link($item['create_name'], $item['create_address'], $item['create_id']));

				// page has not been modified still page creation
				$date = '';
				if($item['edit_date'] && ($item['edit_action'] != 'article:update')) {
					$date = ' '.Skin::build_date($item['create_date']);

					// flag fresh new pages
					if($item['create_date'] >= $context['fresh'])
						$date .= NEW_FLAG;

				}

				$details[] = $starter.$date;
			}

			// the last editor
			if($item['edit_date'] && !in_array($item['edit_action'], array('article:create', 'comment:create', 'file:create'))) {

				// find a name, if any
				$user = '';
				if($item['edit_name']) {

					// label the action
					if(isset($item['edit_action']))
						$user .= Anchors::get_action_label($item['edit_action']).' ';

					// name of last editor
					$user .= sprintf(i18n::s('by %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']));
				}

				// flag new items
				$flag = '';
				if($item['create_date'] >= $context['fresh'])
					$flag = NEW_FLAG;
				elseif($item['edit_date'] >= $context['fresh'])
					$flag = UPDATED_FLAG;
				$details[] = $user.' '.Skin::build_date($item['edit_date']).$flag;
			}

			// poster details
			if($details)
				$text .= '<p class="details">'.join(', ', $details)."</p>\n";

			// the introductory text
			$introduction = '';
			if(is_object($overlay))
				$introduction = $overlay->get_text('introduction', $item);
			elseif($item['introduction'])
				$introduction = $item['introduction'];
			if($introduction)
				$text .= '<div style="margin: 1em 0;">'.Codes::beautify_introduction($introduction).'</div>';

			// insert overlay data, if any
			if(is_object($overlay))
				$text .= $overlay->get_text('list', $item);

			$top_menu = array();

			// friends
			if($friends =& Members::list_users_by_posts_for_anchor('article:'.$item['id'], 0, USERS_LIST_SIZE, 'comma5', $item['create_id']))
				$top_menu[] = sprintf(i18n::s('with %s'), $friends);

			// info on related comments
			if(($count = Comments::count_for_anchor('article:'.$item['id'], FALSE)) > 1)
				$top_menu[] = sprintf(i18n::s('%d contributions, including:'), $count);

			// top
			if($top_menu)
				$text .= '<div style="margin: 1em 0;">'.ucfirst(trim(Skin::finalize_list($top_menu, 'menu'))).'</div>';

			// avoid first file if mentioned in last contribution
			$file_offset = 0;

			// get last contribution for this page
			if($comment = Comments::get_newest_for_anchor('article:'.$item['id'])) {

				if(preg_match('/\[(download|file)=/', $comment['description']))
					$file_offset++;

				// bars around the last contribution
				$bottom_menu = array();

				// last contributor
				$contributor = Users::get_link($comment['create_name'], $comment['create_address'], $comment['create_id']);
				$flag = '';
				if($item['create_date'] >= $context['fresh'])
					$flag = NEW_FLAG;
				elseif($item['edit_date'] >= $context['fresh'])
					$flag = UPDATED_FLAG;
				$bottom_menu[] = sprintf(i18n::s('By %s'), $contributor).' '.Skin::build_date($comment['create_date']).$flag;

				// offer to reply
				if(Comments::allow_creation($anchor, $item)) {
					$link = Comments::get_url($comment['id'], 'reply');
					$bottom_menu[] = Skin::build_link($link, i18n::s('Reply'), 'basic');
				}

				// gather pieces
				$pieces = array();

				// last contribution, and user signature
				$pieces[] = ucfirst(trim($comment['description'])).Users::get_signature($comment['create_id']);

				// bottom
				if($bottom_menu)
					$pieces[] = '<div style="margin-top: 1em;">'.ucfirst(trim(Skin::finalize_list($bottom_menu, 'menu'))).'</div>';

				// put all pieces together
				$text .= '<div class="last_comment">'."\n"
					.join("\n", $pieces)
					.'</div>'."\n";

			}

			// list more recent files
			if($items = Files::list_by_date_for_anchor('article:'.$item['id'], $file_offset, 3, 'dates')) {

				// more files than listed
				$more = '';
				if(($count = Files::count_for_anchor('article:'.$item['id'], FALSE)) > 3)
					$more = '<span class="details">'.sprintf(i18n::s('%d files, including:'), $count).'</span>';

				if(is_array($items))
					$items = Skin::build_list($items, 'compact');
				$text .= '<div style="margin: 1em 0;">'.$more.$items.'</div>';
			}

			// display all tags
			if($item['tags'])
				$text .= ' <p class="tags">'.Skin::build_tags($item['tags'], 'article:'.$item['id']).'</p>';

			// navigation links
			$menu = array();

			// permalink
			$menu[] = Skin::build_link($url, i18n::s('View the page'), 'span');

			// info on related links
			if($count = Links::count_for_anchor('article:'.$item['id'], TRUE))
				$menu[] = sprintf(i18n::ns('%d link', '%d links', $count), $count);

			// the main anchor link
			if(is_object($anchor) && (!isset($this->layout_variant) || ($item['anchor'] != $this->layout_variant)))
				$menu[] = Skin::build_link($anchor->get_url(), sprintf(i18n::s('in %s'), ucfirst($anchor->get_title())), 'span', i18n::s('View the section'));

			// actually insert details
			$text .= Skin::finalize_list($menu, 'menu_bar');

			// bottom of the box
			$text .= '</div>';

		}

		// close the list of articles
		$text .= '</div>';

		// beautify everything at once
		$text = Codes::beautify($text);

		// end of processing
		SQL::free($result);

		// done
		return $text;
	}
}

?>