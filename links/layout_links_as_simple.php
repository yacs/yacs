<?php
/**
 * layout links as a compact list
 *
 * This has more than compact, and less than decorated.
 *
 * @see links/links.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_links_as_simple extends Layout_interface {

	/**
	 * list links
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
		while($item = SQL::fetch($result)) {

			// get the main anchor
			$anchor = Anchors::get($item['anchor']);

			// url is the link itself -- hack for xhtml compliance
			$url = str_replace('&', '&amp;', $item['link_url']);

			// initialize variables
			$prefix = $suffix = '';

			// flag links that are dead, or created or updated very recently
			if($item['edit_date'] >= $context['fresh'])
				$suffix = NEW_FLAG;

			// make a label
			$label = Links::clean($item['title'], $item['link_url']);

			// the main anchor link
			if(is_object($anchor))
				$suffix .= ' - <span '.tag::_class('details').'>'.sprintf(i18n::s('in %s'), Skin::build_link($anchor->get_url(), ucfirst($anchor->get_title()))).'</span>';

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, 'basic', NULL);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>