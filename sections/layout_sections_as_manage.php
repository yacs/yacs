<?php
/**
 * layout sections
 *
 * @see sections/sections.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_sections_as_manage extends Layout_interface {

	/**
	 * list sections
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

		// flag sections updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// the script used to check all pages at once
		$text .= JS_PREFIX
			.'function cascade_selection_to_all_section_rows(handle) {'."\n"
			.'	var checkers = $$("div#sections_panel input[type=\'checkbox\'].row_selector");'."\n"
			.'	for(var index=0; index < checkers.length; index++) {'."\n"
			.'		checkers[index].checked = handle.checked;'."\n"
			.'	}'."\n"
			.'}'."\n"
			.JS_SUFFIX."\n";

		// table prefix
		$text .= Skin::table_prefix('grid');

		// table headers
		$main = '<input type="checkbox" class="row_selector" onchange="cascade_selection_to_all_section_rows(this);" />';
		$cells = array($main, i18n::s('Page'), i18n::s('Rank'));
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
			$anchor =& Anchors::get($item['anchor']);

			// the url to view this item
			$url =& Sections::get_permalink($item);

			// column to select the row
			$cells[] = '<input type="checkbox" name="selected_sections[]" id="section_selector_'.$count.'" class="row_selector" value="'.$item['id'].'" />';

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

			// flag sections that are dead, or created or updated very recently
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $now))
				$prefix .= EXPIRED_FLAG;
			elseif($item['create_date'] >= $dead_line)
				$suffix .= NEW_FLAG;
			elseif($item['edit_date'] >= $dead_line)
				$suffix .= UPDATED_FLAG;

			// signal restricted and private sections
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// the introductory text
			if($item['introduction'])
				$suffix .= BR.Codes::beautify_introduction($item['introduction']);

			// insert overlay data, if any
			if(is_object($overlay))
				$suffix .= $overlay->get_text('list', $item);

			// append details to the suffix
			$suffix .= BR.'<span class="details">';

			// details
			$details = array();

			// info on related pages
			$stats = Articles::stat_for_anchor('section:'.$item['id']);
			if($stats['count'])
				$details[] = sprintf(i18n::ns('%d page', '%d pages', $stats['count']), $stats['count']);

			// info on related files
			$stats = Files::stat_for_anchor('section:'.$item['id']);
			if($stats['count'])
				$details[] = sprintf(i18n::ns('%d file', '%d files', $stats['count']), $stats['count']);

			// info on related links
			$stats = Links::stat_for_anchor('section:'.$item['id']);
			if($stats['count'])
				$details[] = sprintf(i18n::ns('%d link', '%d links', $stats['count']), $stats['count']);

			// info on related comments
			$stats = Comments::stat_for_anchor('section:'.$item['id']);
			if($stats['count'])
				$details[] = sprintf(i18n::ns('%d comment', '%d comments', $stats['count']), $stats['count']);

			// info on related sections
			$stats = Sections::stat_for_anchor('section:'.$item['id']);
			if($stats['count'])
				$details[] = sprintf(i18n::ns('%d section', '%d sections', $stats['count']), $stats['count']);

			// combine in-line details
			if(count($details))
				$suffix .= ucfirst(trim(implode(', ', $details))).BR;

			// list up to three categories by title, if any, and if not on a mobile
			$anchors = array();
			if($members =& Members::list_categories_by_title_for_member('section:'.$item['id'], 0, 7, 'raw')) {
				foreach($members as $id => $attributes) {

					// add background color to distinguish this category against others
					if(isset($attributes['background_color']) && $attributes['background_color'])
						$attributes['title'] = '<span style="background-color: '.$attributes['background_color'].'; padding: 0 3px 0 3px;">'.$attributes['title'].'</span>';

					$anchors[] = Skin::build_link(Categories::get_permalink($attributes), $attributes['title'], 'basic');
				}
			}
			if(count($anchors))
				$suffix .= sprintf(i18n::s('In %s'), implode(' | ', $anchors)).BR;

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
				$details[] = Skin::build_number($item['hits'], i18n::s('hits'));

			// signal locked sections
			if(isset($item['locked']) && ($item['locked'] == 'Y'))
				$details[] = LOCKED_FLAG;

// 			// rating
// 			if($item['rating_count'] && !(is_object($anchor) && $anchor->has_option('without_rating')))
// 				$details[] = Skin::build_link(Sections::get_url($item['id'], 'rate'), Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])), 'basic');

			// combine in-line details
			if(count($details))
				$suffix .= ucfirst(trim(implode(', ', $details)));

			// end of details
			$suffix .= '</span>';

			// strip empty details
			$suffix = str_replace(BR.'<span class="details"></span>', '', $suffix);
			$suffix = str_replace('<span class="details"></span>', '', $suffix);

			// the icon to put in the left column
			if($item['thumbnail_url'])
				$icon = $item['thumbnail_url'];

			// commands
			$commands = array(Skin::build_link(Sections::get_url($item['id'], 'edit'), i18n::s('edit'), 'basic'),
				Skin::build_link(Sections::get_url($item['id'], 'delete'), i18n::s('delete'), 'basic'));

			// link to this page
			$cells[] = $prefix.Skin::build_link($url, $title, 'section').' - '.Skin::finalize_list($commands, 'menu').$suffix;

			// ranking
			$cells[] = '<input type="text" size="5" name="section_rank_'.$item['id'].'" value="'.$item['rank'].'" onfocus="$(\'section_selector_'.$count.'\').checked = true;" onchange="$(\'act_on_sections\').selectedIndex = 6;" />';

			// append the row
			$text .= Skin::table_row($cells, $count++);
		}

		// select all rows
		$cells = array('<input type="checkbox" class="row_selector" onchange="cascade_selection_to_all_section_rows(this);" />', i18n::s('Select all'), '');
		$text .= Skin::table_row($cells, $count++);

		// table suffix
		$text .= Skin::table_suffix();

		// end of processing
		SQL::free($result);
		return $text;
	}

}

?>