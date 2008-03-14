<?php
/**
 * layout dates as links to pages
 *
 * @see dates/dates.php
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_dates_as_links extends Layout_interface {

	/**
	 * list dates
	 *
	 * @param resource the SQL result
	 * @return array of $url => ($prefix, $label, $suffix, $type, $icon, $date)
	 *
	 * @see skins/layout.php
	**/
	function &layout(&$result) {
		global $context;

		// we return an array of ($url => $attributes)
		$items = array();

		// empty list
		if(!SQL::count($result))
			return $items;

		// flag dates updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// process all items in the list
		while($item =& SQL::fetch($result)) {

			// the url to use
			$url = Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']);

			// initialize variables
			$prefix = $suffix = '';

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
				$suffix .= NEW_FLAG;

			// build a valid label
			if(isset($item['title']))
				$label = Codes::beautify_title($item['title']);
			else
				$label = Skin::build_date($item['date_stamp'], 'day');

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, 'date', NULL, $item['date_stamp']);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>