<?php
/**
 * build a cloud of tags
 *
 * @see categories/categories.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_categories_as_cloud extends Layout_interface {

	/**
	 * list categories
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	 *
	 * @see skins/layout.php
	**/
	function layout($result) {
		global $context;

		// empty list
		if(!SQL::count($result)) {
			$output = array();
			return $output;
		}

		// process all items in the list
		$items = array();
		$total = 0;
		$minimum = 10000;
		$maximum = 0;
		while($item = SQL::fetch($result)) {

			// this will be sorted alphabetically
			$items[ $item['title'] ] = array( 'importance' => (int)$item['importance'], 'href' => Categories::get_permalink($item) );

			// assess the scope
			if($minimum > (int)$item['importance'])
				$minimum = (int)$item['importance'];
			if($maximum < (int)$item['importance'])
				$maximum = (int)$item['importance'];

		}

		// end of processing
		SQL::free($result);

		// sort the array alphabetically
		ksort($items);

		// scale items
		$text = '';
		foreach($items as $title => $item) {

			switch((string)ceil( (1 + $item['importance'] - $minimum) * 6 / (1 + $maximum - $minimum) )) {
			default:
			case 1:
				$item['style'] = 'font-size: 0.8em';
				break;
			case 2:
				$item['style'] = 'font-size: 0.9em';
				break;
			case 3:
				$item['style'] = 'font-size: 1.3em';
				break;
			case 4:
				$item['style'] = 'font-size: 1.5em';
				break;
			case 5:
				$item['style'] = 'font-size: 1.7em';
				break;
			case 6:
				$item['style'] = 'font-size: 2em';
				break;
			}

			$text .= '<span style="'.$item['style'].'">'.Skin::build_link($item['href'], $title, 'basic').'</span> ';
		}

		// final packaging
		$text = '<p class="cloud">'.rtrim($text).'</p>';

		// return by reference
		return $text;
	}

}

?>