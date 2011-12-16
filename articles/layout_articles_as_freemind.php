<?php
/**
 * layout articles as nodes of a Freemind map
 *
 * This is a special layout used to build a Freemind map.
 *
 * @see articles/articles.php
 * @see sections/freemind.php
 *
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @author Bernard Paques
 * @author Thierry Pinelli [email]contact@vdp-digital.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_freemind extends Layout_interface {

	/**
	 * list articles
	 *
	 * @param resource the SQL result
	 * @return string a set of XML nodes to be integrated into a full Fremind map
	 *
	 * @see skins/layout.php
	**/
	function &layout(&$result) {
		global $context;

		// we return some text
		$text = '';

		// empty list
		if(!SQL::count($result))
			return $text;

		// default parameter values
		$freemind_article_bgcolor = '';
		if(isset($context['skins_freemind_article_bgcolor']) && $context['skins_freemind_article_bgcolor'])
			$freemind_article_bgcolor = ' BACKGROUND_COLOR="'.$context['skins_freemind_article_bgcolor'].'"';

		$freemind_article_color = '';
		if(isset($context['skins_freemind_article_color']) && $context['skins_freemind_article_color'])
			$freemind_article_color = ' COLOR="'.$context['skins_freemind_article_color'].'"';

		$freemind_article_style = '';
		if(isset($context['skins_freemind_article_style']) && $context['skins_freemind_article_style'])
			$freemind_article_style = ' STYLE="'.$context['skins_freemind_article_style'].'"';

		// process all items in the list
		include_once $context['path_to_root'].'articles/article.php';
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'links/links.php';
		include_once $context['path_to_root'].'overlays/overlay.php';
		$nodes = 0; // number of nodes processed so far
		$stack = 0; // branch depth
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

			// initialize variables
			$prefix = $suffix = $rating = '';

			// flag expired pages
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
				$prefix .= EXPIRED_FLAG;

			// signal articles to be published
			if(($item['publish_date'] <= NULL_DATE) || ($item['publish_date'] > $context['now']))
				$prefix .= DRAFT_FLAG;

			// signal restricted and private articles
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// append page introduction ,if any
			if($item['introduction'] && ($context['skins_with_details'] == 'Y')) {

				// wrap only outside X/HTML tags
				$areas = preg_split('/(<[a-z\/].+?>)/i', trim(Codes::beautify_introduction($item['introduction'])), -1, PREG_SPLIT_DELIM_CAPTURE);
				$index = 0;
				foreach($areas as $area) {
					if((++$index)%2)
						$suffix .= wordwrap($area, 70, BR, 0);
					else
						$suffix .= $area;
				}
			}

			// add other details
			$details = array();

			// flag pages updated recently
			if(($item['create_date'] >= $context['fresh']) || ($item['edit_date'] >= $context['fresh']))
				$details[] = Skin::build_date($item['edit_date']);

			// count related files, if any
			if($count = Files::count_for_anchor('article:'.$item['id'], TRUE))
				$details[] = sprintf(i18n::ns('%d file', '%d files', $count), $count);

			// count related comments, if any
			if($count = Comments::count_for_anchor('article:'.$item['id'], TRUE))
				$details[] = sprintf(i18n::ns('%d comment', '%d comments', $count), $count);

			// count related links, if any
			if($count = Links::count_for_anchor('article:'.$item['id'], TRUE))
				$details[] = sprintf(i18n::ns('%d link', '%d links', $count), $count);

			// append details
			if(count($details)) {
				if(trim($suffix))
					$suffix .= BR;
				$suffix .= implode(', ', $details);
			}

			// rating
			if(isset($item['rating_count']) && $item['rating_count'] && !(is_object($anchor) && $anchor->has_option('without_rating')))
				$rating = Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count']));

			// maybe the time to stack items
			if($nodes && !($nodes%10)) {
				$text .= '<node '
				.$freemind_article_bgcolor
				.$freemind_article_color
				.$freemind_article_style
				.' TEXT="'.encode_field(utf8::to_hex('<html>'.i18n::s('More pages').'</html>')).'" FOLDED="true">'
				.'<edge WIDTH="thin" />'."\n".'<font NAME="SansSerif" SIZE="12" />'."\n";

				$stack++;
			}
			$nodes++;

			// append to the list
			$text .= '<node ID="article_'.$item['id'].'"'
				.$freemind_article_bgcolor
				.$freemind_article_color
				.$freemind_article_style
				.' TEXT="'.encode_field(utf8::to_hex('<html>'.$prefix.$title.$rating.'</html>')).'"'
				.' LINK="'.encode_field($context['url_to_home'].$context['url_to_root'].$url).'">';

			$text .= '<edge WIDTH="thin" />'."\n".'<font NAME="SansSerif" SIZE="12" />'."\n";

			// add details
			if($suffix)
				$text .= '<hook NAME="accessories/plugins/NodeNote.properties"><text>'.encode_field(utf8::to_hex(strip_tags(preg_replace(array('/<br/i', '/<li/i', '/&nbsp;/'), array("\n<br", "\n- <li", ' '), $suffix)))).'</text></hook>';

			// end of this article
			$text .= '</node>'."\n";

			// append the follow-up node, if any
			if(($nodes >= SQL::count($result)) && isset($this->layout_variant))
				$text .= $this->layout_variant;
		}

		// close stacked nodes
		while($stack--)
			$text .= '</node>'."\n";

		// end of processing
		SQL::free($result);
		return $text;
	}

}

?>
