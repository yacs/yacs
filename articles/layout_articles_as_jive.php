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

		// start a table
		$text .= Skin::table_prefix('jive');

		// headers
		$text .= Skin::table_row(array(i18n::s('Topic'), i18n::s('Content')), 'header');

		// build a list of articles
		$odd = FALSE;
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'files/files.php';
		include_once $context['path_to_root'].'links/links.php';
		include_once $context['path_to_root'].'overlays/overlay.php';
		while($item =& SQL::fetch($result)) {

			// get the related overlay, if any
			$overlay = Overlay::load($item);

			// get the anchor
			$anchor =& Anchors::get($item['anchor']);

			// the url to view this item
			$url =& Articles::get_permalink($item);

			// reset the rendering engine between items
			Codes::initialize($url);

			// use the title to label the link
			if(is_object($overlay) && is_callable(array($overlay, 'get_live_title')))
				$title = $overlay->get_live_title($item);
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
			$text .= Skin::build_link($url, $title, 'basic');

			// flag articles updated recently
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $now))
				$text .= EXPIRED_FLAG.' ';
			elseif($item['create_date'] >= $dead_line)
				$text .= NEW_FLAG.' ';
			elseif($item['edit_date'] >= $dead_line)
				$text .= UPDATED_FLAG.' ';

			// add details, if any
			$details = array();

			// poster name
			if(isset($context['with_author_information']) && ($context['with_author_information'] == 'Y')) {
				if($item['create_name'])
					$details[] = sprintf(i18n::s('posted by %s %s'), Users::get_link($item['create_name'], $item['create_address'], $item['create_id']), Skin::build_date($item['create_date']));
			}

			// last update
			$details[] = sprintf(i18n::s('Updated %s'), Skin::build_date($item['edit_date']));

			// signal locked articles
			if(isset($item['locked']) && ($item['locked'] == 'Y'))
				$details[] = LOCKED_FLAG;

			// add details to the title
			if(count($details))
				$text .= '<p class="details">'.join(', ', $details).'</p>';

			// next cell for the content
			$text .= '</td><td width="70%">';

			// the content to be displayed
			$content = '';

			// rating
			if($item['rating_count'] && !(is_object($anchor) && $anchor->has_option('without_rating')))
				$content .= Skin::build_link(Articles::get_url($item['id'], 'rate'), Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])), 'basic');

			// the introductory text
			$content .= Codes::beautify_introduction($item['introduction']);

			// insert overlay data, if any
			if(is_object($overlay))
				$content .= $overlay->get_text('list', $item);

			// the description
			$content .= Skin::build_block($item['description'], 'description', '', $item['options']);

			// attachment details
			$details = array();

			// info on related files
			if($count = Files::count_for_anchor('article:'.$item['id'], TRUE))
				$details[] = Skin::build_link($url.'#files', FILE_TOOL_IMG.' '.sprintf(i18n::ns('%d file', '%d files', $count), $count), 'basic');

			// info on related links
			if($count = Links::count_for_anchor('article:'.$item['id'], TRUE))
				$details[] = LINK_TOOL_IMG.' '.sprintf(i18n::ns('%d link', '%d links', $count), $count);

			// more commands
			$content .= '<p class="jive_menu">';

			// describe attachments
			if(count($details))
				$content .= join(', ', $details).BR;

			// the command to reply
			if(Comments::are_allowed($anchor, $item)) {
				Skin::define_img('COMMENTS_ADD_IMG', 'comments/add.png');
				$content .= Skin::build_link(Comments::get_url('article:'.$item['id'], 'comment'), COMMENTS_ADD_IMG.i18n::s('Post a comment'), 'basic');
			}

			// count replies
			if($count = Comments::count_for_anchor('article:'.$item['id'], TRUE))
				$content .= ' ('.Skin::build_link(Comments::get_url('article:'.$item['id'], 'list'), sprintf(i18n::ns('%d comment', '%d comments', $count), $count), 'basic').') ';

// would require a cache per user
//			// the command to watch this topic
//			if(Surfer::get_id() && ($item['create_id'] != Surfer::get_id()) && ($item['publish_id'] != Surfer::get_id())) {
//				if(!Members::check('article:'.$item['id'], 'user:'.Surfer::get_id())) {
//					if($context['with_friendly_urls'] == 'Y')
//						$link = 'users/track.php/article/'.$item['id'];
//					else
//						$link = 'users/track.php?article='.$item['id'];
//					Skin::define_img('WATCH_TOOL_IMG', 'icons/tools/watch.gif');
//					$content .= ' '.WATCH_TOOL_IMG.sprintf(i18n::s('%s this topic'), Skin::build_link($link, i18n::s('Watch')))."\n";
//				}
//			}

			// end the row
			$text .= $content.'</p></td></tr>';

		}

		// end of processing
		SQL::free($result);

		// return the table
		$text .= Skin::table_suffix();
		return $text;
	}
}

?>