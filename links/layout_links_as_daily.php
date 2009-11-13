<?php
/**
 * layout links as blogmarks
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_links_as_daily extends Layout_interface {

	/**
	 * list blogmarks
	 *
	 * @param resource the SQL result
	 * @return string resulting text
	**/
	function &layout(&$result) {
		global $context;

		// we return a string
		$text = '';

		// empty list
		if(!SQL::count($result))
			return $text;

		// flag items updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));

		// start in north
		$in_north = TRUE;

		// define allowed HTML tags for the cover page
		define('ALLOWED_HTML_TAGS', '<a><b><br><h1><h2><h3><i><img><li><ol><p><ul>');

		// build a list of articles
		$box = array();
		$box['content'] = '';
		$previous_date = NULL;
		while($item =& SQL::fetch($result)) {

			// not the same date
			$current_date = substr($item['edit_date'], 0, 10);
			if($previous_date != $current_date) {

				// insert a complete box for the previous date
				if($box['content']) {
					if($in_north)
						$text .= '<div class="newest">'."\n";
					$text .= Skin::build_box($box['title'], $box['content']);
					if($in_north)
						$text .= '</div>'."\n";
					$in_north = FALSE;
				}

				// prepare a box for a new date
				$previous_date = $current_date;
				$box['title'] = Skin::build_date($item['edit_date'], 'no_hour');
				$box['content'] = '';

			}

			$box['content'] .= '<br clear="both" />';

			// time
			$box['content'] .= '<span class="details">'.substr($item['edit_date'], 11, 5).'</span> ';

			// make a label
			$label = Links::clean($item['title'], $item['link_url']);

			$box['content'] .= Skin::build_link($item['link_url'], $label);

			// flag links updated recently
			if($item['edit_date'] >= $dead_line)
				$box['content'] .= ' '.NEW_FLAG;

			// the description
			if(trim($item['description']))
				$box['content'] .= "\n<br/>".Skin::cap(Codes::beautify($item['description']), 500)."\n";

			// the menu bar for associates and poster
			if(Surfer::is_empowered() || Surfer::is($item['edit_id'])) {
				$menu = array( 'links/edit.php?id='.$item['id'] => i18n::s('Edit'),
					'links/delete.php?id='.$item['id'] => i18n::s('Delete') );
				$box['content'] .= ' '.Skin::build_list($menu, 'menu');
			}

			// append details to the suffix
			$box['content'] .= BR.'<span class="details">';

			// details
			$details = array();

			// item poster
			if(Surfer::is_member()) {
				if($item['edit_name'])
					$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

			} else
				$details[] = get_action_label($item['edit_action']);

			// show an anchor link
			if($item['anchor'] && ($anchor =& Anchors::get($item['anchor']))) {
				$anchor_url = $anchor->get_url();
				$anchor_label = ucfirst($anchor->get_title());
				$details[] = sprintf(i18n::s('in %s'), Skin::build_link($anchor_url, $anchor_label));
			}

			// all details
			$box['content'] .= ucfirst(trim(implode(' ', $details)))."\n";

			// end of details
			$box['content'] .= '</span><br/><br/>';

		}

		// close the on-going box
		if($in_north)
			$text .= '<div class="newest">'."\n";
		$text .= Skin::build_box($box['title'], $box['content']);
		if($in_north)
			$text .= '</div>'."\n";

		// end of processing
		SQL::free($result);

		return $text;
	}
}

?>