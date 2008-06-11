<?php
/**
 * layout sections, and their content, as nodes of a Freemind map
 *
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author Thierry Pinelli [email]contact@vdp-digital.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_sections_as_freemind extends Layout_interface {

	/**
	 * list sections as nodes
	 *
	 * @param resource the SQL result
	 * @return string a set of XML nodes to be integrated into a full Fremind map
	**/
	function &layout(&$result) {
		global $context;

		// we return some text
		$text = '';

		// empty list
		if(!SQL::count($result))
			return $text;

		// default parameter values
		$freemind_section_bgcolor = '';
		if(isset($context['skins_freemind_section_bgcolor']) && $context['skins_freemind_section_bgcolor'])
			$freemind_section_bgcolor = ' BACKGROUND_COLOR="'.$context['skins_freemind_section_bgcolor'].'"';

		$freemind_section_color = '';
		if(isset($context['skins_freemind_section_color']) && $context['skins_freemind_section_color'])
			$freemind_section_color = ' COLOR="'.$context['skins_freemind_section_color'].'"';

		$freemind_section_style = ' STYLE="bubble"';
		if(isset($context['skins_freemind_section_style']) && $context['skins_freemind_section_style'])
			$freemind_section_style = ' STYLE="'.$context['skins_freemind_section_style'].'"';

		// differentiate each section at the top-level of the tree
		$various_attributes = array(
			array( ' STYLE="bubble" ', '<cloud COLOR="#fcf2c5" />'."\n".'<edge COLOR="#cccc00" STYLE="linear" WIDTH="2" />'."\n".'<font NAME="SansSerif" SIZE="12" />'."\n" ),
			array( ' BACKGROUND_COLOR="#f9f5d1" COLOR="#996600" STYLE="bubble"	', '<edge STYLE="sharp_bezier" WIDTH="8" />'."\n".'<font BOLD="true" ITALIC="true" NAME="Dialog" SIZE="14" />'."\n" ),
			array( ' BACKGROUND_COLOR="#feeab8" COLOR="#407c41" STYLE="bubble" ', '<edge COLOR="#ffcc33" STYLE="sharp_bezier" WIDTH="8" />'."\n".'<font BOLD="true" NAME="Comic Sans MS" SIZE="14" />'."\n" ),
			array( ' COLOR="#006699" STYLE="bubble" ', '<edge COLOR="#f67740" STYLE="sharp_linear" WIDTH="6" />'."\n" ),
			array( ' BACKGROUND_COLOR="#ffffff" STYLE="bubble" ', '<edge COLOR="#990099" STYLE="sharp_linear" WIDTH="8" />'."\n".'<font BOLD="true" NAME="SansSerif" SIZE="12" />'."\n" ),
			array( ' BACKGROUND_COLOR="#d5d57f" COLOR="#787805" STYLE="bubble" ', '<edge COLOR="#9e9e05" STYLE="sharp_bezier" WIDTH="8" />'."\n".'<cloud COLOR="#eeeeb4" />'."\n".'<font BOLD="true" ITALIC="true" NAME="SansSerif" SIZE="12" />'."\n" )
			);

		// do not do that at second level and beyond
		static $fuse;
		if(isset($fuse))
			$differentiate = FALSE;
		else
			$differentiate = $fuse = TRUE;

		// flag articles updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// bind a layout engine only once
		include_once $context['path_to_root'].'articles/layout_articles_as_freemind.php';
		$articles_layout = new Layout_articles_as_freemind();

		// process all items in the list
		$count = 0; // number of nodes processed so far
		$stack = 0; // branch depth
		$various_index = 0; // style selector for current section
		$articles_per_page = 10; // to break the list of related articles
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'files/files.php';
		include_once $context['path_to_root'].'links/links.php';
		while($item =& SQL::fetch($result)) {

			// the url to view this item
			$url = Sections::get_url($item['id'], 'view', $item['title'], $item['nick_name']);

			// initialize variables
			$prefix = $suffix = $rating = '';

			// flag expired pages
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $now))
				$prefix .= EXPIRED_FLAG;

			// signal restricted and private articles
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// build a title
			$title = Codes::beautify_title($item['title']);

			// append page introduction ,if any
			if(isset($item['introduction']) && $item['introduction']) {

				// wrap only outside X/HTML tags
				$areas = preg_split('/(<[a-z\/].+?>)/i', trim(Codes::beautify($item['introduction'])), -1, PREG_SPLIT_DELIM_CAPTURE);
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
			if(($item['create_date'] >= $dead_line) || ($item['edit_date'] >= $dead_line))
				$details[] = Skin::build_date($item['edit_date']);

			// content of this section, as Freemind nodes
			$content = Sections::list_by_title_for_anchor('section:'.$item['id'], 0, 50, 'freemind');

			// count related articles, if any
			if($count = Articles::count_for_anchor('section:'.$item['id'])) {
				$details[] = sprintf(i18n::ns('1 page', '%d pages', $count), $count);

				// if there are many articles, append a node to browse the section
				if($count > $articles_per_page) {
					$follow_up_node = '<node '
						.$freemind_section_bgcolor
						.$freemind_section_color
						.$freemind_section_style
						.' TEXT="'.encode_field(utf8::to_hex('<html>'.i18n::s('more pages').'</html>')).'"'
						.' LINK="'.encode_field($context['url_to_home'].$context['url_to_root'].$url).'">'."\n";
					if($differentiate)
						$follow_up_node .= $various_attributes[$various_index][1];
					$follow_up_node .= '</node>'."\n";

					$articles_layout->set_variant($follow_up_node);
				} else
					$articles_layout->set_variant('');

				// preserve natural order of articles
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
				$content .= Articles::list_for_anchor_by($order, 'section:'.$item['id'], 0, 50, $articles_layout);

			}

			// count related files, if any
			if($count = Files::count_for_anchor('section:'.$item['id'], TRUE))
				$details[] = sprintf(i18n::ns('1 file', '%d files', $count), $count);

			// count related comments, if any
			if($count = Comments::count_for_anchor('section:'.$item['id'], TRUE))
				$details[] = sprintf(i18n::ns('1 comment', '%d comments', $count), $count);

			// count related links, if any
			if($count = Links::count_for_anchor('section:'.$item['id'], TRUE))
				$details[] = sprintf(i18n::ns('1 link', '%d links', $count), $count);

			// append details
			if(count($details)) {
				if(trim($suffix))
					$suffix .= BR;
				$suffix .= implode(', ', $details);
			}

			// rating
			if(isset($item['rating_count']) && $item['rating_count'] && is_object($anchor) && $anchor->has_option('with_rating', FALSE))
				$rating = Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count']));

			// link to this section within node if there is no content
			$link = '';
			if(!$content)
				$link = ' LINK="'.encode_field($context['url_to_home'].$context['url_to_root'].$url).'"';

			// else append a node to browse the section
			else {
				$content .= '<node '
					.$freemind_section_bgcolor
					.$freemind_section_color
					.$freemind_section_style
					.' TEXT="'.encode_field(utf8::to_hex('<html>'.i18n::s('browse the section').'</html>')).'"'
					.' LINK="'.encode_field($context['url_to_home'].$context['url_to_root'].$url).'">'."\n";
				if($differentiate)
					$content .= $various_attributes[$various_index][1];
				$content .= '</node>'."\n";
			}


			// attributes of this node
			if($differentiate) {
				$freemind_section_bgcolor = $various_attributes[$various_index][0];
				$freemind_section_color = $freemind_section_style = '';
			}

			// maybe the time to stack items
			if($count && !($count%10)) {
				$text .= '<node '
				.$freemind_section_bgcolor
				.$freemind_section_color
				.$freemind_section_style
				.' TEXT="'.encode_field(utf8::to_hex('<html>'.i18n::s('more pages').'</html>')).'" FOLDED="true">'."\n";
				if($differentiate)
					$text .= $various_attributes[$various_index][1];

				$stack++;
			}
			$count++;

			// this section
			$text .= '<node ID="section_'.$item['id'].'"'
				.$freemind_section_bgcolor
				.$freemind_section_color
				.$freemind_section_style
				.' TEXT="'.encode_field(utf8::to_hex('<html>'.$prefix.$title.$rating.'</html>')).'"'
				.$link
				.' FOLDED="true">';

			// attributes of this node
			if($differentiate)
				$text .= $various_attributes[$various_index][1];

			// add details
			if($suffix)
				$text .= '<hook NAME="accessories/plugins/NodeNote.properties"><text>'.encode_field(utf8::to_hex(strip_tags(preg_replace(array('/<br/i', '/<li/i', '/&nbsp;/'), array("\n<br", "\n- <li", ' '), $suffix)))).'</text></hook>';

			// append other nodes, if any
			$text .= $content;

			// end of this section
			$text .= '</node>'."\n";

			$various_index++;
			if($various_index >= count($various_attributes))
				$various_index = 0;
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