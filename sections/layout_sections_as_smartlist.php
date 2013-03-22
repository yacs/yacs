<?php
/**
 * Layout sections as a unordered list
 * with icon (thumbs) title and introduction
 *
 * @see skins/page.php, skin/skin_skeleton.php
 *
 * Created to implement horizontal drop down menu (improved tabs feature)
 * within tabs, but could be used to list sections everywhere.
 *
 * You can specify 'no_icon' or 'no_intro' parameters to the layout
 *
 * Yacs Lasares RC4 or more required
 *
 * @author Alexis Raimbault
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_sections_as_smartlist extends Layout_interface {

	/**
	 * the preferred number of items for this layout
	 *
	 * @return int the optimised count of items for this layout
	 *
	 * @see skins/layout.php
	 */
	function items_per_page() {
		return COMPACT_LIST_SIZE;
	}

	/**
	 * list sections
	 *
	 *  Accept following variants (you can mix them):
	 *  - 'no_icon', not to show icons of sections
	 *  - 'no_intro', not to show intro of sections
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

		// getting variants
		$show_icon = $show_intro = TRUE;
		if (preg_match('/no_icon/', $this->layout_variant)) $show_icon = FALSE;
		if (preg_match('/no_intro/', $this->layout_variant)) $show_intro = FALSE;

		// we calculate an array of ($url => $attributes)
		$items = array();

		// process all items in the list
		include_once $context['path_to_root'].'overlays/overlay.php';
		while($item = SQL::fetch($result)) {

			// get the related overlay, if any
			$overlay = Overlay::load($item,'section:'.$item['id']);

			// the url to view this item
			$url = Sections::get_permalink($item);

			// initialize variables
			$prefix = $label = $suffix = $icon = '';

			// flag sections that are draft or dead
			if($item['activation_date'] >= $context['now'])
				$prefix .= DRAFT_FLAG;
			elseif(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
				$prefix .= EXPIRED_FLAG;

			// signal restricted and private sections
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// flag items updated recently
			if($item['create_date'] >= $context['fresh'])
				$suffix .= NEW_FLAG;
			elseif($item['edit_date'] >= $context['fresh'])
				$suffix .= UPDATED_FLAG;

			// use the title to label the link
			if(is_object($overlay))
				$label = Codes::beautify_title($overlay->get_text('title', $item));
			else
				$label = Codes::beautify_title($item['title']);

			// strip label and uppercase first letter
			$label = ucfirst(Skin::strip($label, 4));


			// get introduction
			if($show_intro) {
      			$introduction = '';
      			if(is_object($overlay))
      				$introduction = $overlay->get_text('introduction', $item);
      			else
      				$introduction = $item['introduction'];

      			// the introductory text, strip to 10 words, preserve Yacs Code
      			if($introduction)
      				$suffix .= BR.'<span class="details">'
      					.Codes::beautify_introduction(Skin::strip($introduction,10,NULL,NULL,TRUE))
      					.'</span>';
      	}

			// the icon
			if(($item['thumbnail_url']) && $show_icon)
				$icon = $item['thumbnail_url'];

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, 'basic', $icon, NULL);

		}

		// end of processing
		SQL::free($result);

		//prepare HTML result, give default icon if required, provide callback function for final rendering
		$text =& Skin::build_list($items,'dropmenu',($show_icon)?DECORATED_IMG:NULL, FALSE, 'Layout_sections_as_smartlist::finalize_list');
		return $text;
	}


	/**
	* Finalize list rendering
	*
	* to be called back from skin::build_list
	* @return text the html rendering of drop down menu list
	*
	* @param array of items
	* @param string list variant (not used here)
	*/
	public static function finalize_list($list, $variant='') {
		global $context;

		$text = '';

		if($list) {
				$first = TRUE;
				foreach($list as $label) {

					$class = '';
					if($first) {
						$class = ' class="first"';
						$first = FALSE;
					}

					$icon = '';
					if(is_array($label))
						list($label, $icon) = $label;

					$text .= '<li'.$class.'><div class="icon">'.$icon.'</div><div class="label">'.$label.'</div></li>'."\n";
				}

			$text = '<ul class="smartlist">'."\n".$text.'</ul>'."\n";
		}

		return $text;

	}

}

?>