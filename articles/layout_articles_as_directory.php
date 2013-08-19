<?php
/**
 * layout articles as in an alphabetical index
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_directory extends Layout_interface {

	/**
	 * the preferred number of items for this layout
	 *
	 * The compact format of this layout allows a high number of items to be listed
	 *
	 * @return int the optimised count of items fro this layout
	 */
	function items_per_page() {
		return 1000;
	}

	/**
	 * list articles as an index
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	**/
	function layout($result) {
		global $context;

		// we return some text
		$text = '';

		// referenced items
		$letters = array();

		// empty list
		if(!SQL::count($result))
			return $text;

		// build a list of articles
		include_once $context['path_to_root'].'links/links.php';
		while($item = SQL::fetch($result)) {

			// get the related overlay, if any
			$overlay = Overlay::load($item, 'article:'.$item['id']);

			// get the anchor
			$anchor = Anchors::get($item['anchor']);

			// the url to view this item
			$url = Articles::get_permalink($item);

			// use the title to label the link
			if(is_object($overlay))
				$title = Codes::beautify_title($overlay->get_text('title', $item));
			else
				$title = Codes::beautify_title($item['title']);

			// reset everything
			$prefix = $label = $suffix = $icon = $details = '';

			// signal articles to be published
			if(!isset($item['publish_date']) || ($item['publish_date'] <= NULL_DATE) || ($item['publish_date'] > gmstrftime('%Y-%m-%d %H:%M:%S')))
				$prefix .= DRAFT_FLAG;

			// signal restricted and private articles
			if(isset($item['active']) && ($item['active'] == 'N'))
				$prefix .= PRIVATE_FLAG;
			elseif(isset($item['active']) && ($item['active'] == 'R'))
				$prefix .= RESTRICTED_FLAG;

			// flag articles updated recently
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
				$suffix .= ' '.EXPIRED_FLAG;
			elseif($item['create_date'] >= $context['fresh'])
				$suffix .= ' '.NEW_FLAG;
			elseif($item['edit_date'] >= $context['fresh'])
				$suffix .= ' '.UPDATED_FLAG;

			// make a link
			$label = $prefix.Skin::build_link($url, $title, 'basic').$suffix;

			// the associated letter
			$letter = strtoupper(ltrim($title[0]));
			if(($letter < 'A') || ($letter > 'Z'))
				$letter = '#';

			// a new entry for this letter
			if(!isset($letters[ $letter ]))
				$letters[ $letter ] = array();
			$letters[ $letter ][] = $label;

		}

		// mention all letters at the top
		$bar = array();

		// all potential letters, in expected order
		$all = '#ABCDEFGHIJKLMNOPQRSTUVWXYZ';

		for($index = 0; $index < strlen($all); $index++) {

			$letter = $all[ $index ];

			// some content for this letter
			if(isset($letters[ $letter ])) {

				// internal link to the right place
				$bar[] = Skin::build_link('#letter'.$letter, $letter, 'span');

				// actual content for this letter
				$content = '<ul class="index"><li>'.join('</li><li>', $letters[ $letter ]).'</li></ul>';

				// content displayed in the page
				$text .= Skin::build_header_box($letter, $content, 'letter'.$letter);

			// no content for this letter
			} else
				$bar[] = $letter;

		}

		// insert local links at the top
		$text = Skin::finalize_list($bar, 'menu_bar').$text;

		// end of processing
		SQL::free($result);
		return $text;

	}
}

?>