<?php
/**
 * list articles to be managed
 *
 * This is a special layout to feature management commands for each article.
 *
 * @see articles/articles.php
 * @see sections/manage.php
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
	 * @see layouts/layout.php
	**/
	function layout($result) {
		global $context;

		// we return some text
		$text = '';

		// empty list
		if(!SQL::count($result))
			return $text;

		// the script used to check all pages at once
		Page::insert_script(
			'function cascade_selection_to_all_article_rows(handle) {'."\n"
			.'	$("div#articles_panel input[type=\'checkbox\'].row_selector").each('."\n"
			.'		function() { $(this).attr("checked", $(handle).is(":checked"));}'."\n"
			.'	);'."\n"
			.'}'."\n"
			);

		// table prefix
		$text .= Skin::table_prefix('yc-grid');

		// table headers
		$main = '<input type="checkbox" class="row_selector" onclick="cascade_selection_to_all_article_rows(this);" />';
		$cells = array($main, i18n::s('Page'), i18n::s('Rank'));
		$text .= Skin::table_row($cells, 'header');

		// process all items in the list
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'links/links.php';

		$count = 0;
		while($item = SQL::fetch($result)) {
			$cells = array();

			// get the related overlay, if any
			$overlay = Overlay::load($item, 'article:'.$item['id']);

			// get the main anchor
			$anchor = Anchors::get($item['anchor']);

			// the url to view this item
			$url = Articles::get_permalink($item);

			// column to select the row
			$cells[] = '<input type="checkbox" name="selected_articles[]" id="article_selector_'.$count.'" class="row_selector" value="'.$item['id'].'" />';

			// use the title to label the link
			if(is_object($overlay))
				$title = Codes::beautify_title($overlay->get_text('title', $item));
			else
				$title = Codes::beautify_title($item['title']);

			// initialize variables
			$prefix = $suffix = $icon = '';

			// flag sticky pages
			if($item['rank'] < 10000)
				$prefix .= STICKY_FLAG;

			// signal locked articles
			if(isset($item['locked']) && ($item['locked'] == 'Y'))
				$suffix .= ' '.LOCKED_FLAG;

			// flag articles that are dead, or created or updated very recently
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
				$prefix .= EXPIRED_FLAG;
			elseif($item['create_date'] >= $context['fresh'])
				$suffix .= ' '.NEW_FLAG;
			elseif($item['edit_date'] >= $context['fresh'])
				$suffix .= ' '.UPDATED_FLAG;

			// signal articles to be published
			if(($item['publish_date'] <= NULL_DATE) || ($item['publish_date'] > gmdate('Y-m-d H:i:s')))
				$prefix .= DRAFT_FLAG;

			// signal restricted and private articles
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// the introductory text
			if(is_object($overlay))
				$introduction = $overlay->get_text('introduction', $item);
			else
				$introduction = $item['introduction'];
			if($introduction)
				$suffix .= BR.Codes::beautify_introduction($introduction);

			// insert overlay data, if any
			if(is_object($overlay))
				$suffix .= $overlay->get_text('list', $item);

			// append details to the suffix
			$suffix .= BR.'<span '.tag::_class('details').'>';

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
			$details[] = Anchors::get_action_label($item['edit_action']).' '.Skin::build_date($item['edit_date']);

			// the number of hits
			if(Surfer::is_logged() && ($item['hits'] > 1))
				$details[] = Skin::build_number($item['hits'], i18n::s('hits'));

			// info on related files
			$stats = Files::stat_for_anchor('article:'.$item['id']);
			if($stats['count'])
				$details[] = sprintf(i18n::ns('%d file', '%d files', $stats['count']), $stats['count']);

			// info on related links
			$stats = Links::stat_for_anchor('article:'.$item['id']);
			if($stats['count'])
				$details[] = sprintf(i18n::ns('%d link', '%d links', $stats['count']), $stats['count']);

			// info on related comments
			$stats = Comments::stat_for_anchor('article:'.$item['id']);
			if($stats['count'])
				$details[] = sprintf(i18n::ns('%d comment', '%d comments', $stats['count']), $stats['count']);

			// rating
			if($item['rating_count'] && !(is_object($anchor) && $anchor->has_option('without_rating')))
				$details[] = Skin::build_link(Articles::get_url($item['id'], 'like'), Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])), 'basic');

			// combine in-line details
			if(count($details))
				$suffix .= ucfirst(trim(implode(', ', $details)));

			// list up to three categories by title, if any
			$anchors = array();
			if($members = Members::list_categories_by_title_for_member('article:'.$item['id'], 0, 7, 'raw')) {
				foreach($members as $id => $attributes) {

					// add background color to distinguish this category against others
					if(isset($attributes['background_color']) && $attributes['background_color'])
						$attributes['title'] = '<span style="background-color: '.$attributes['background_color'].'; padding: 0 3px 0 3px;">'.$attributes['title'].'</span>';

					$anchors[] = Skin::build_link(Categories::get_permalink($attributes), $attributes['title'], 'basic');
				}
			}
			if(count($anchors))
				$suffix .= BR.sprintf(i18n::s('In %s'), implode(' / ', $anchors));

			// end of details
			$suffix .= '</span>';

			// strip empty details
			$suffix = str_replace(BR.'<span '.tag::_class('details').'></span>', '', $suffix);
			$suffix = str_replace('<span '.tag::_class('details').'></span>', '', $suffix);

			// the icon to put in the left column
			if($item['thumbnail_url'])
				$icon = $item['thumbnail_url'];

			// commands
			$commands = array(Skin::build_link(Articles::get_url($item['id'], 'edit'), i18n::s('edit'), 'basic'),
				Skin::build_link(Articles::get_url($item['id'], 'delete'), i18n::s('delete'), 'basic'));

			// link to this page
			$cells[] = $prefix.Skin::build_link($url, $title, 'article').' - '.Skin::finalize_list($commands, 'menu').$suffix;

			// ranking
			$cells[] = '<input type="text" size="5" name="article_rank_'.$item['id'].'" value="'.$item['rank'].'" onfocus="$(\'#article_selector_'.$count.'\').attr(\'checked\', \'checked\');" onchange="$(\'#act_on_articles\').prop(\'selectedIndex\', 9);" />';

			// append the row
			$text .= Skin::table_row($cells, $count++);
		}

		// select all rows
		$cells = array('<input type="checkbox" class="row_selector" onclick="cascade_selection_to_all_article_rows(this);" />', i18n::s('Select all/none'), '');
		$text .= Skin::table_row($cells, $count++);

		// table suffix
		$text .= Skin::table_suffix();

		// end of processing
		SQL::free($result);
		return $text;
	}

}

?>
