<?php
/**
 * layout links
 *
 * This is the default layout for links.
 *
 * @see links/index.php
 * @see links/links.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Alexis Raimbault
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_links extends Layout_interface {

	/**
	 * list links
	 *
	 * Recognize following variants:
	 * - 'no_anchor' to list items attached to one particular anchor
	 * - 'no_author' to list items attached to one user prolink
	 *
	 * @param resource the SQL result
	 * @param string a variant, if any
	 * @return array of resulting items, or NULL
	 *
	 * @see skins/layout.php
	**/
	function &layout(&$result, $variant='full') {
		global $context;

		// we return an array of ($url => $attributes)
		$items = array();

		// empty list
		if(!SQL::count($result))
			return $items;

		// sanity check
		if(!isset($this->layout_variant))
			$this->layout_variant = 'no_anchor';

		// process all items in the list
		while($item = SQL::fetch($result)) {

			// get the main anchor
			$anchor =& Anchors::get($item['anchor']);

			// initialize variables
			$prefix = $suffix = $icon = '';

			// make a label
			$label = Links::clean($item['title'], $item['link_url']);

			// flag links uploaded recently
			if($item['edit_date'] >= $context['fresh'])
				$prefix = NEW_FLAG.$prefix;

			// the number of clicks
			if($item['hits'] > 1)
				$suffix .= ' ('.Skin::build_number($item['hits'], i18n::s('clicks')).') ';

			// add a separator
			if($suffix)
				$suffix = ' - '.$suffix;

			// details
			$details = array();

			// item poster
			if($item['edit_name'] && ($this->layout_variant != 'no_author')) {
				if(Surfer::is_member()
					|| (!isset($context['content_without_details']) || ($context['content_without_details'] != 'Y'))
					|| (is_object($anchor) && $anchor->has_option('with_details')) )
					$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));
			}

			// show an anchor link
			if(($this->layout_variant != 'no_anchor') && ($this->layout_variant != 'no_author') && $item['anchor'] && ($anchor =& Anchors::get($item['anchor']))) {
				$anchor_url = $anchor->get_url();
				$anchor_label = ucfirst($anchor->get_title());
				$details[] = sprintf(i18n::s('in %s'), Skin::build_link($anchor_url, $anchor_label, 'article'));
			}

			// the menu bar for associates and poster
			if(Surfer::is_empowered() || Surfer::is($item['edit_id'])) {
				$details[] = Skin::build_link('links/edit.php?id='.$item['id'], i18n::s('edit'), 'span');
				$details[] = Skin::build_link('links/delete.php?id='.$item['id'], i18n::s('delete'), 'span');
			}

			// append details to the suffix
			if(count($details))
				$suffix .= BR.Skin::finalize_list($details, 'menu');

			// description
			if($item['description'])
				$suffix .= BR.Codes::beautify($item['description']);

			// build the actual link to check it
			if($this->layout_variant == 'review')
				$icon = $item['link_url'];

			// url is the link itself -- hack for xhtml compliance
			$url = str_replace('&', '&amp;', $item['link_url']);

			// let the rendering engine guess the type of link
			$link_type = NULL;

			// except if we want to stay within this window
			if(isset($item['link_target']) && ($item['link_target'] != 'I'))
				$link_type = 'external';

			// hovering title
			$link_title = NULL;
			if(isset($item['link_title']) && $item['link_title'])
				$link_title = $item['link_title'];

			// pack everything
			$items[$url] = array($prefix, $label, $suffix, $link_type, $icon, $link_title);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>
