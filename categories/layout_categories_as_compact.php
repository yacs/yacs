<?php
/**
 * layout categories as a compact list
 *
 * @see categories/categories.php
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_categories_as_compact extends Layout_interface {

	/**
	 * list categories
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	 *
	 * @see skins/layout.php
	**/
	function &layout($result) {
		global $context;

		// empty list
		if(!SQL::count($result)) {
			$output = array();
			return $output;
		}

		// we return an array of ($url => $attributes)
		$items = array();

		// process all items in the list
		while($item =& SQL::fetch($result)) {

			// url to view the comment
			$url = Categories::get_url($item['id'], 'view', $item['title']);

			// use the title to label the link
			$label = ucfirst(Skin::strip($item['title'], 20));

			// number of items for this category
			$count = 0;

			// count sections for this category
			$stats = Members::stat_sections_for_anchor('category:'.$item['id']);
			if($stats && is_array($stats) && isset($stats['count']))
				$count += $stats['count'];

			// count articles for this category
			$stats = Members::stat_articles_for_anchor('category:'.$item['id']);
			if($stats && is_array($stats) && isset($stats['count']))
				$count += $stats['count'];

			// format total count of items
			if($count)
				$count = ' ('.$count.')';
			else
				$count = '';

			// list all components for this item
			$items[$url] = array(NULL, $label, $count, 'basic', NULL);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>