<?php
/**
 * layout articles as slides
 *
 * With this layout each article is considered as being one slide of a structured electronic presentation.
 *
 * Articles are ordered based on rank values.
 *
 * @see sections/view.php
 *
 * @author Bernard Paques
 * @author Thierry Pinelli (ThierryP)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_slides extends Layout_interface {

	/**
	 * the preferred number of items for this layout
	 *
	 * The compact format of this layout allows a high number of items to be listed
	 *
	 * @return int the optimised count of items fro this layout
	 */
	function items_per_page() {
		return 300;
	}

	/**
	 * list articles as a table of content of a manual
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

		// flag articles updated recently
		if(!isset($context['site_revisit_after']) || ($context['site_revisit_after'] < 1))
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));

		// build a list of articles
		$anchor = NULL;
		include_once $context['path_to_root'].'overlays/overlay.php';
		while($item =& SQL::fetch($result)) {

			// get the related overlay, if any
			$overlay = Overlay::load($item);

			// get the main anchor
			$anchor =& Anchors::get($item['anchor']);

			// the url to view this item
			$url =& Articles::get_permalink($item);

			// reset the rendering engine between items
			Codes::initialize($url);

			// use the title to label the link
			if(is_object($overlay) && is_callable(array($overlay, 'get_live_title')))
				$title = $overlay->get_live_title($item);
			else
				$title = ucfirst(Codes::strip(strip_tags($item['title'], '<br><div><img><p><span>')));

			// one additional slide
			$text .= '<div class="slide">'."\n";

			// reset everything
			$prefix = $label = $suffix = $icon = $details = '';

			// signal articles to be published
			if(!isset($item['publish_date']) || ($item['publish_date'] <= NULL_DATE) || ($item['publish_date'] > gmstrftime('%Y-%m-%d %H:%M:%S')))
				$prefix .= DRAFT_FLAG;

// 			// signal locked articles
// 			if(isset($item['locked']) && ($item['locked'] == 'Y'))
// 				$prefix .= LOCKED_FLAG;

			// signal restricted and private articles
			if(isset($item['active']) && ($item['active'] == 'N'))
				$prefix .= PRIVATE_FLAG.' ';
			elseif(isset($item['active']) && ($item['active'] == 'R'))
				$prefix .= RESTRICTED_FLAG.' ';

			// flag articles that have expired
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $now))
				$suffix .= ' '.EXPIRED_FLAG;
//			elseif($item['create_date'] >= $dead_line)
//				$suffix .= ' '.NEW_FLAG;
//			elseif($item['edit_date'] >= $dead_line)
//				$suffix .= ' '.UPDATED_FLAG;

			// allow associates and editors to change the page
			if(Surfer::is_empowered())
				$title .= Skin::build_link($url, MORE_IMG, 'basic');

			// provide a title
			$text .= '<h1>'.$prefix.$title.$suffix.'</h1>'."\n";

			// beginning of slide content
			$text .= '<div class="slidecontent">'."\n";

			// beautify the target page
			if($item['introduction'])
				$text .= Codes::beautify($item['introduction'], $item['options']).BR.BR."\n";
			$text .= Codes::beautify($item['description'], $item['options'])."\n";

			// end of slide content
			$text .= '</div>'."\n";

			// end of this slide
			$text .= '</div>'."\n\n";

		}

		// end of processing
		SQL::free($result);
		return $text;

	}
}

?>