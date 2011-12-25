<?php
/**
 * layout articles as topics handled by jive forums
 *
 * With this layout each entry is followed by a link to post a note.
 *
 * @link http://www.jivesoftware.com/products/forums/  Jive Forums
 *
 * @see sections/view.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Thierry Pinelli (ThierryP)
 * @tester Mordread Wallas
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_jive extends Layout_interface {

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

		// start a table
		$text .= Skin::table_prefix('jive');

		// headers
		$text .= Skin::table_row(array(i18n::s('Topic'), i18n::s('Content')), 'header');

		// build a list of articles
		$odd = FALSE;
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'links/links.php';
		include_once $context['path_to_root'].'overlays/overlay.php';
		while($item = SQL::fetch($result)) {

			// get the related overlay, if any
			$overlay = Overlay::load($item, 'article:'.$item['id']);

			// get the anchor
			$anchor =& Anchors::get($item['anchor']);

			// the url to view this item
			$url = Articles::get_permalink($item);

			// use the title to label the link
			if(is_object($overlay))
				$title = Codes::beautify_title($overlay->get_text('title', $item));
			else
				$title = Codes::beautify_title($item['title']);

			// one row per article
			$text .= '<tr class="'.($odd?'odd':'even').'"><td>';
			$odd = ! $odd;

			// signal articles to be published
			if(!isset($item['publish_date']) || ($item['publish_date'] <= NULL_DATE) || ($item['publish_date'] > gmstrftime('%Y-%m-%d %H:%M:%S')))
				$text .= DRAFT_FLAG;

			// signal restricted and private articles
			if($item['active'] == 'N')
				$text .= PRIVATE_FLAG.' ';
			elseif($item['active'] == 'R')
				$text .= RESTRICTED_FLAG.' ';

			// use the title as a link to the page
			$text .= Skin::build_link($url, '<strong>'.$title.'</strong>', 'basic');

			// signal locked articles
			if(isset($item['locked']) && ($item['locked'] == 'Y') && Articles::is_owned($item, $anchor))
				$text .= ' '.LOCKED_FLAG;

			// flag articles updated recently
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
				$text .= ' '.EXPIRED_FLAG;
			elseif($item['create_date'] >= $context['fresh'])
				$text .= ' '.NEW_FLAG;
			elseif($item['edit_date'] >= $context['fresh'])
				$text .= ' '.UPDATED_FLAG;

			// add details, if any
			$details = array();

			// poster name
			if(isset($context['with_author_information']) && ($context['with_author_information'] == 'Y')) {
				if($item['create_name'])
					$details[] = sprintf(i18n::s('posted by %s %s'), Users::get_link($item['create_name'], $item['create_address'], $item['create_id']), Skin::build_date($item['create_date']));
			}

			// last update
			$details[] = sprintf(i18n::s('Updated %s'), Skin::build_date($item['edit_date']));

			// add details to the title
			if(count($details))
				$text .= '<p class="details" style="margin: 3px 0">'.join(', ', $details).'</p>';

			// display all tags
			if($item['tags'])
				$text .= '<p class="tags">'.Skin::build_tags($item['tags'], 'article:'.$item['id']).'</p>';

			// next cell for the content
			$text .= '</td><td width="70%">';

			// the content to be displayed
			$content = '';

			// rating
			if($item['rating_count'] && !(is_object($anchor) && $anchor->has_option('without_rating')))
				$content .= Skin::build_link(Articles::get_url($item['id'], 'rate'), Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])), 'basic');

			// the introductory text
			if(is_object($overlay))
				$content .= Codes::beautify_introduction($overlay->get_text('introduction', $item));
			else
				$content .= Codes::beautify_introduction($item['introduction']);

			// insert overlay data, if any
			if(is_object($overlay))
				$content .= $overlay->get_text('list', $item);

			// the description
			$content .= Skin::build_block($item['description'], 'description', '', $item['options']);

			// attachment details
			$details = array();

			// info on related files
			if($count = Files::count_for_anchor('article:'.$item['id'], TRUE)) {
				Skin::define_img('FILES_LIST_IMG', 'files/list.gif');
				$details[] = Skin::build_link($url.'#files', FILES_LIST_IMG.sprintf(i18n::ns('%d file', '%d files', $count), $count), 'span');
			}

			// info on related links
			if($count = Links::count_for_anchor('article:'.$item['id'], TRUE)) {
				Skin::define_img('LINKS_LIST_IMG', 'links/list.gif');
				$details[] = LINKS_LIST_IMG.sprintf(i18n::ns('%d link', '%d links', $count), $count);
			}

			// the command to reply
			if(Comments::allow_creation($anchor, $item)) {
				Skin::define_img('COMMENTS_ADD_IMG', 'comments/add.gif');
				$details[] = Skin::build_link(Comments::get_url('article:'.$item['id'], 'comment'), COMMENTS_ADD_IMG.i18n::s('Post a comment'), 'span');
			}

			// count replies
			if($count = Comments::count_for_anchor('article:'.$item['id'], TRUE))
				$details[] = Skin::build_link(Comments::get_url('article:'.$item['id'], 'list'), sprintf(i18n::ns('%d comment', '%d comments', $count), $count), 'span');

			// describe attachments
			$content .= Skin::finalize_list($details, 'menu_bar');

			// end the row
			$text .= $content.'</td></tr>';

		}

		// end of processing
		SQL::free($result);

		// return the table
		$text .= Skin::table_suffix();
		return $text;
	}
}

?>
