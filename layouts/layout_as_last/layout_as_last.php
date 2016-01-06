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
Class Layout_as_last extends Layout_interface {

	/**
	 * the preferred number of items for this layout
	 *
	 * @return 25
	 *
	 * @see layouts/layout.php
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
                
                // type of listed object
		$items_type = $this->listed_type;

		// allow for complete styling
		$text = '<div class="last_articles">';

		// build a list of articles
		while($item = SQL::fetch($result)) {
                    
                        // get the object interface, this may load parent and overlay
			$entity = new $items_type($item);	

			// get the related overlay
			$overlay = $entity->overlay;

			// get the anchor
			$anchor = $entity->anchors;

			// the url to view this item
			$url = $entity->get_permalink($item);
                        
                        $title = Codes::beautify_title($entity->get_title());

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
			elseif($item['create_date'] >= $context['fresh'])
				$suffix .= NEW_FLAG;
			elseif($item['edit_date'] >= $context['fresh'])
				$suffix .= UPDATED_FLAG;

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

			// page starter and date
			if($item['create_name'])
				$details[] = sprintf(i18n::s('Started by %s'), Users::get_link($item['create_name'], $item['create_address'], $item['create_id']))
					.' '.Skin::build_date($item['create_date']);

			// page last modification
			if($item['edit_date'] && ($item['edit_action'] == 'article:update') && $item['edit_name'])
				$details[] = Anchors::get_action_label($item['edit_action'])
					.' '.sprintf(i18n::s('by %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']))
					.' '.Skin::build_date($item['edit_date']);

			// friends
			if($friends = Members::list_users_by_posts_for_anchor('article:'.$item['id'], 0, USERS_LIST_SIZE, 'comma5', $item['create_id']))
				$details[] = sprintf(i18n::s('with %s'), $friends);

			// people details
			if($details)
				$text .= '<p class="details">'.join(', ', $details)."</p>\n";

			// the introductory text
			$introduction = $entity->get_introduction();
		
			if($introduction)
				$text .= '<div style="margin: 1em 0;">'.Codes::beautify_introduction($introduction).'</div>';

			// insert overlay data, if any
			if(is_object($overlay))
				$text .= $overlay->get_text('list', $item);

			// info on related comments
			if(($count = Comments::count_for_anchor('article:'.$item['id'])) > 1)
				$text .= '<div style="margin-top: 1em;"><p class="details">'.sprintf(i18n::s('%d contributions, including:'), $count).'</p></div>';

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
				if($comment['create_date'] >= $context['fresh'])
					$flag = NEW_FLAG;
				elseif($comment['edit_date'] >= $context['fresh'])
					$flag = UPDATED_FLAG;
				$bottom_menu[] = sprintf(i18n::s('By %s'), $contributor).' '.Skin::build_date($comment['create_date']).$flag;

				// offer to reply
				if($entity->allows('creation','comment')) {
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
				if(($count = Files::count_for_anchor('article:'.$item['id'])) > 3)
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
			if(is_object($anchor) && (!isset($this->focus) || ($item['anchor'] != $this->focus)))
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
                
                $this->load_scripts_n_styles();

		// done
		return $text;
	}
}
