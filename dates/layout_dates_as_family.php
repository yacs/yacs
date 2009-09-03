<?php
/**
 * group events by dates
 *
 * This is used by the overlay overlays/day.php, when parameter 'layout_as_list' is set.
 *
 * @see overlays/day.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_dates_as_family extends Layout_interface {

	/**
	 * list dates by dates...
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

		// flag sections updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// we put everything in a definition list
		$text .= '<dl class="family">'."\n";

		// build a list of sections
		$family = '';
		$rows = array();
		while($item =& SQL::fetch($result)) {

			// change date
			$this_family = Skin::build_date(substr($item['date_stamp'], 0, 10), 'calendar');
			if($this_family != $family) {

				// pop data for previous date
				if(count($rows))
					$text .= '<dd>'.Skin::finalize_list($rows, 'decorated').'</dd>';
				$rows = array();

				// all data for one date
				$family = $this_family;
				$text .= '<dt>'.$family.'</dt>'."\n";
			}

			// url to view the anchor page
			$url =& Articles::get_permalink($item);

			// reset everything
			$prefix = $label = $suffix = $icon = '';

			// signal restricted and private dates/articles
			if(!isset($item['publish_date']) || ($item['publish_date'] <= NULL_DATE))
				$prefix .= DRAFT_FLAG;

			// signal restricted and private dates/articles
			if(!isset($item['active']))
				;
			elseif($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// flag new dates/articles
			if($item['edit_date'] >= $dead_line)
				$suffix = UPDATED_FLAG.' ';

			// build a valid label
			if(isset($item['title']))
				$label = $item['title'];
			else
				$label = Skin::build_date($item['date_stamp'], 'standalone');

			// indicate the id in the hovering popup
			$hover = i18n::s('View the page');

			// use the title as a link to the page
			$title = Skin::build_link($url, Codes::beautify_title($item['title']), 'basic', $hover);

			// also use a clickable thumbnail, if any
			$thumbnail = DECORATED_IMG;
			if($item['thumbnail_url'])
				$thumbnail = Skin::build_link($url, '<img src="'.$item['thumbnail_url'].'" alt="" title="'.encode_field($hover).'" class="left_image" />', 'basic', $hover);

			// board introduction
			if($item['introduction'])
				$suffix .= '<br style="clear: none;" />'.Codes::beautify($item['introduction']);

			// this is another row of the output
			$rows[] = array($prefix.$title.$suffix, $thumbnail, NULL);

		}

		// pop data for last date
		if(count($rows))
			$text .= '<dd>'.Skin::finalize_list($rows, 'decorated').'</dd>';

		// close the list
		$text .= '</dl>';

		// end of processing
		SQL::free($result);
		return $text;
	}
}

?>