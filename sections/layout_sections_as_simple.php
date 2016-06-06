<?php
/**
 * layout sections
 *
 * This has more than compact, and less than decorated.
 *
 * @see sections/sections.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_sections_as_simple extends Layout_interface {

	/**
	 * list sections
	 *
	 * @param resource the SQL result
	 * @return array of resulting items, or NULL
	 *
	 * @see layouts/layout.php
	**/
	function layout($result) {
		global $context;

		// we return an array of ($url => $attributes)
		$items = array();

		// empty list
		if(!SQL::count($result))
			return $items;

		// process all items in the list
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'links/links.php';
		while($item = SQL::fetch($result)) {

			// get the related overlay, if any
			$overlay = Overlay::load($item, 'section:'.$item['id']);

			// get the main anchor
			$anchor = Anchors::get($item['anchor']);

			// the url to view this item
			$url = Sections::get_permalink($item);

			// use the title to label the link
			if(is_object($overlay))
				$title = Codes::beautify_title($overlay->get_text('title', $item));
			else
				$title = Codes::beautify_title($item['title']);

			// initialize variables
			$prefix = $suffix = $icon = '';

			// flag sticky pages
			if($item['rank'] < 10000)
				$prefix .= STICKY_FLAG;

			// signal restricted and private sections
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// flag sections that are dead, or created or updated very recently
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
				$prefix .= EXPIRED_FLAG;
			elseif($item['create_date'] >= $context['fresh'])
				$suffix .= NEW_FLAG;
			elseif($item['edit_date'] >= $context['fresh'])
				$suffix .= UPDATED_FLAG;

			// info on related comments
			if($count = Comments::count_for_anchor('section:'.$item['id'], TRUE))
				$suffix .= ' ('.$count.')';

			// details
			$details = array();

			// info on related sections
			if($count = Sections::count_for_anchor('section:'.$item['id']))
				$details[] = sprintf(i18n::ns('%d section', '%d sections', $count), $count);

			// info on related articles
			if($count = Articles::count_for_anchor('section:'.$item['id']))
				$details[] = sprintf(i18n::ns('%d page', '%d pages', $count), $count);

			// info on related files
			if($count = Files::count_for_anchor('section:'.$item['id'], TRUE))
				$details[] = sprintf(i18n::ns('%d file', '%d files', $count), $count);

			// info on related links
			if($count = Links::count_for_anchor('section:'.$item['id'], TRUE))
				$details[] = sprintf(i18n::ns('%d link', '%d links', $count), $count);

			// the main anchor link
			if(is_object($anchor) && (!isset($this->focus) || ($item['anchor'] != $this->focus)))
				$details[] = sprintf(i18n::s('in %s'), Skin::build_link($anchor->get_url(), ucfirst($anchor->get_title()), 'section'));

			// combine in-line details
			if(count($details))
				$suffix .= ' - <span '.tag::_class('details').'>'.trim(implode(', ', $details)).'</span>';

			// list all components for this item
			$items[$url] = array($prefix, $title, $suffix, 'section', $icon);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>
