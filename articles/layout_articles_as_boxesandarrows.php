<?php
/**
 * layout articles as boxesandarrows do
 *
 * @link http://www.boxesandarrows.com/
 *
 * @see articles/index.php
 * @see sections/view.php
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
 * @author Thierry Pinelli (ThierryP)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_boxesandarrows extends Layout_interface {

	/**
	 * list articles as boxesandarrows do
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
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));

		// build a list of articles
		include_once $context['path_to_root'].'overlays/overlay.php';
		while($item =& SQL::fetch($result)) {

			// get the related overlay
			$overlay = Overlay::load($item);

			// get the anchor
			$anchor = Anchors::get($item['anchor']);

			// the url to view this item
			$url = Articles::get_url($item['id'], 'view', $item['title'], $item['nick_name']);

			// reset the rendering engine between items
			Codes::initialize($url);

			// use the title to label the link
			if(is_object($overlay) && is_callable(array($overlay, 'get_live_title')))
				$title = $overlay->get_live_title($item);
			else
				$title = Codes::beautify_title($item['title']);

			// reset everything
			$prefix = $label = $suffix = $icon = '';

			// signal articles to be published
			if(!isset($item['publish_date']) || ($item['publish_date'] <= NULL_DATE) || ($item['publish_date'] > gmstrftime('%Y-%m-%d %H:%M:%S')))
				$prefix .= DRAFT_FLAG;

			// signal restricted and private articles
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG.' ';
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG.' ';

			// signal locked articles
			if(isset($item['locked']) && ($item['locked'] == 'Y'))
				$suffix .= ' '.LOCKED_FLAG;

			// flag articles updated recently
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $now))
				$suffix .= ' '.EXPIRED_FLAG;
			elseif($item['create_date'] >= $dead_line)
				$suffix .= ' '.NEW_FLAG;
			elseif($item['edit_date'] >= $dead_line)
				$suffix .= ' '.UPDATED_FLAG;

			// rating
			if($item['rating_count'] && is_object($anchor) && $anchor->has_option('with_rating'))
				$suffix .= Skin::build_link(Articles::get_url($item['id'], 'rate'), Skin::build_rating_img((int)round($item['rating_sum'] / $item['rating_count'])), 'basic');

			// the side image, if any
			if($item['thumbnail_url']) {
				$icon = $item['thumbnail_url'];
			} elseif(is_object($anchor)) {
				$icon = $anchor->get_thumbnail_url();
			}
			if($icon)
				$text .= '<a href="'.$context['url_to_root'].$url.'"><img src="'.$icon.'" class="right_image" alt="" /></a>';

			// use the title as a link to the page
			$text .= '<h2>'.$prefix.Skin::build_link($url, $title, 'basic').$suffix;

			// add details
			$details = array();

			// the creator of this article, if not the publisher and not within 24hours publication date
			if(isset($context['with_author_information']) && ($context['with_author_information'] == 'Y'))
				$details[] = sprintf(i18n::s('by %s, %s'), $item['create_name'], Skin::build_date($item['create_date']));

			// details
			if(count($details))
				$text .= ' <span class="details">'.ucfirst(implode(', ', $details))."</span>\n";

			// next paragraph
			$text .= '</h2>';

			// the introductory text
			if($item['introduction'])
				$text .= Codes::beautify($item['introduction'], $item['options']);

			// insert overlay data, if any
			if(is_object($overlay))
				$text .= $overlay->get_text('list', $item);

			// build an abstract
			if(!$item['introduction'])
				$text .= Skin::cap(Codes::beautify($item['description'], $item['options']), 50);

			// link to the anchor page, but only at the front page
			if(is_object($anchor) && ($context['skin_variant'] == 'home'))
				$text .= '<p class="details right">'.sprintf(i18n::s('More in %s'), Skin::build_link($anchor->get_url(), $anchor->get_title())).'</p>';

		}

		// end of processing
		SQL::free($result);

		return $text;
	}
}

?>