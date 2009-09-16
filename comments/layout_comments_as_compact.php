<?php
/**
 * layout comments as a compact list
 *
 * @see comments/comments.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_comments_as_compact extends Layout_interface {

	/**
	 * list comments
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	 *
	 * @see skins/layout.php
	**/
	function &layout(&$result) {
		global $context;

		// empty list
		if(!SQL::count($result)) {
			$output = array();
			return $output;
		}

		// we return an array of ($url => $attributes)
		$items = array();

		// process all items in the list
		include_once $context['path_to_root'].'comments/comments.php';
		while($item =& SQL::fetch($result)) {

			// url to view the comment
			$url = Comments::get_url($item['id']);

			// initialize variables
			$prefix = $label = $suffix = $icon = '';

			// the title as the label
			if($item['create_name'])
				$label .= ucfirst($item['create_name']).' ';

			// time of creation
			$label .= Skin::build_date($item['create_date']);
			
			// text beginning
			if($text = Skin::strip($item['description'], 10, NULL, NULL))
				$suffix = ' - '.$text;

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, 'comment', $icon);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>