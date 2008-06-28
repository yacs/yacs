<?php
/**
 * layout articles
 *
 * @see articles/articles.php
 *
 * @author Christophe Battarel [email]christophe.battarel@altairis.fr[/email]
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_manage extends Layout_interface {

	/**
	 * list articles
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
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

		// flag articles updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// the script used to check all pages at once
		$text .= '<script type="text/javascript">// <![CDATA['."\n"
			.'function cascade_selection_to_all_article_rows(handle) {'."\n"
			.'	var checkers = $$("div#articles_panel input[type=\'checkbox\'].row_selector");'."\n"
			.'	for(var index=0; index < checkers.length; index++) {'."\n"
			.'		checkers[index].checked = handle.checked;'."\n"
			.'	}'."\n"
			.'}'."\n"
			.'// ]]></script>'."\n";

		// table prefix
		$text .= Skin::table_prefix('grid');

		// table headers
		$main = '<input type="checkbox" class="row_selector" onchange="cascade_selection_to_all_article_rows(this);" />';
		$cells = array($main, i18n::s('Page'), i18n::s('Ranking'));
		$text .= Skin::table_row($cells, 'header');

		// process all items in the list
		include_once $context['path_to_root'].'categories/categories.php';
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'files/files.php';
		include_once $context['path_to_root'].'links/links.php';
		include_once $context['path_to_root'].'overlays/overlay.php';

		$count = 0;
		while($item =& SQL::fetch($result)) {
			$cells = array();

			// get the related overlay, if any
			$overlay = Overlay::load($item);

			// get the main anchor
			$anchor = Anchors::get($item['anchor']);

			// the url to view this item
			$url = Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']);

			// reset the rendering engine between items
			Codes::initialize($url);

			// column to select the row
			$cells[] = '<input type="checkbox" name="articles[]" class="row_selector" value="'.$item['id'].'" />';

			// use the title to label the link
			if(is_object($overlay) && is_callable(array($overlay, 'get_live_title')))
				$title = $overlay->get_live_title($item);
			else
				$title = ucfirst(Codes::beautify(strip_tags($item['title'], '<br><div><img><p><span>')));

			// initialize variables
			$prefix = $suffix = $icon = '';

			// flag sticky pages
			if($item['rank'] < 10000)
				$prefix .= STICKY_FLAG;

			// flag articles that are dead, or created or updated very recently
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $now))
				$prefix .= EXPIRED_FLAG;
			elseif($item['create_date'] >= $dead_line)
				$suffix .= NEW_FLAG;
			elseif($item['edit_date'] >= $dead_line)
				$suffix .= UPDATED_FLAG;

			// signal articles to be published
			if(($item['publish_date'] <= NULL_DATE) || ($item['publish_date'] > gmstrftime('%Y-%m-%d %H:%M:%S')))
				$prefix .= DRAFT_FLAG;

			// signal restricted and private articles
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// the introductory text
			if($item['introduction'])
				$suffix .= BR.Codes::beautify($item['introduction'], $item['options']);

			// insert overlay data, if any
			if(is_object($overlay))
				$suffix .= $overlay->get_text('list', $item);

			// append details to the suffix
			$suffix .= BR.'<span class="details">';

			// details
			$details = array();

			// the author
			if(isset($context['with_author_information']) && ($context['with_author_information'] == 'Y')) {
				if($item['create_name'] != $item['edit_name'])
					$details[] = sprintf(i18n::s('by %s, %s'), $item['create_name'], $item['edit_name']);
				else
					$details[] = sprintf(i18n::s('by %s'), $item['create_name']);
			}

			// the last action
			$details[] = get_action_label($item['edit_action']).' '.Skin::build_date($item['edit_date']);

			// the number of hits
			if(Surfer::is_logged() && ($item['hits'] > 1))
				$details[] = sprintf(i18n::s('%d hits'), $item['hits']);

			// info on related files
			$stats = Files::stat_for_anchor('article:'.$item['id']);
			if($stats['count'])
				$details[] = sprintf(i18n::ns('1 file', '%d files', $stats['count']), $stats['count']);

			// info on related links
			$stats = Links::stat_for_anchor('article:'.$item['id']);
			if($stats['count'])
				$details[] = sprintf(i18n::ns('1 link', '%d links', $stats['count']), $stats['count']);

			// info on related comments
			$stats = Comments::stat_for_anchor('article:'.$item['id']);
			if($stats['count'])
				$details[] = sprintf(i18n::ns('1 comment', '%d comments', $stats['count']), $stats['count']);

			// rating
			if($item['rating_count'] && !(is_object($anchor) && $anchor->has_option('without_rating')))
				$details[] = Skin::build_link(Articles::get_url($item['id'], 'rate'), Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])), 'basic');

			// signal locked articles
			if(isset($item['locked']) && ($item['locked'] == 'Y'))
				$details[] = LOCKED_FLAG;

			// combine in-line details
			if(count($details))
				$suffix .= ucfirst(trim(implode(', ', $details)));

			// list up to three categories by title, if any, and if not on a mobile
			$anchors = array();
			if($members = Members::list_categories_by_title_for_member('article:'.$item['id'], 0, 5, 'raw')) {
				foreach($members as $id => $attributes)
					$anchors[] = Skin::build_link(Categories::get_url($attributes['id'], 'view', $attributes['title']), $attributes['title'], 'category');
			}
			if(count($anchors))
				$suffix .= BR.sprintf(i18n::s('In %s'), implode(' | ', $anchors));

			// end of details
			$suffix .= '</span>';

			// strip empty details
			$suffix = str_replace(BR.'<span class="details"></span>', '', $suffix);
			$suffix = str_replace('<span class="details"></span>', '', $suffix);

			// the icon to put in the left column
			if($item['thumbnail_url'])
				$icon = $item['thumbnail_url'];

			// commands
			$commands = array(Skin::build_link(Articles::get_url($item['id'], 'edit'), i18n::s('edit'), 'basic'),
				Skin::build_link(Articles::get_url($item['id'], 'delete'), i18n::s('delete'), 'basic'));

			// link to this page
			$cells[] = $prefix.Skin::build_link($url, $title, 'article').' - '.Skin::finalize_list($commands, 'menu').$suffix;

			// ranking
			$cells[] = '<input type="text" size="5" name="article_rank_'.$item['id'].'" value="'.$item['rank'].'" />';

			// append the row
			$text .= Skin::table_row($cells, $count++);
		}

		// select all rows
		$cells = array('<input type="checkbox" class="row_selector" onchange="cascade_selection_to_all_article_rows(this);" />', i18n::s('Select all pages'), '');
		$text .= Skin::table_row($cells, $count++);

		// table suffix
		$text .= Skin::table_suffix();

		// end of processing
		SQL::free($result);
		return $text;
	}

}

?>