<?php
/**
 * layout links
 *
 * This is the default layout for links.
 *
 * @see links/index.php
 * @see links/links.php
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @author GnapZ
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

		// flag links updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// process all items in the list
		while($item =& SQL::fetch($result)) {

			// initialize variables
			$prefix = $suffix = $icon = '';

			// make a label
			$label = Links::clean($item['title'], $item['link_url']);

			// flag links uploaded recently
			if($item['edit_date'] >= $dead_line)
				$prefix = NEW_FLAG.$prefix;

			// the number of clicks
			if($item['hits'])
				$suffix .= ' ('.sprintf(i18n::ns('1 click', '%d clicks', $item['hits']), $item['hits']).') ';

			// description
			if($item['description'])
				$suffix .= ' '.Codes::beautify($item['description']);

			// the menu bar for associates and poster
			if(Surfer::is_empowered() || Surfer::is($item['edit_id'])) {
				$menu = array( 'links/edit.php?id='.$item['id'] => i18n::s('Edit'),
					'links/delete.php?id='.$item['id'] => i18n::s('Delete') );
				$suffix .= ' '.Skin::build_list($menu, 'menu');
			}

			// add a separator
			if($suffix)
				$suffix = ' - '.$suffix;

			// details
			$details = array();

			// item poster
			if($variant != 'no_author') {
				if($item['edit_name'])
					$details[] = sprintf(i18n::s('edited by %s %s'), Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']), Skin::build_date($item['edit_date']));

			} else
				$details[] = get_action_label($item['edit_action']);

			// show an anchor link
			if(($variant != 'no_anchor') && ($variant != 'no_author') && $item['anchor'] && ($anchor = Anchors::get($item['anchor']))) {
				$anchor_url = $anchor->get_url();
				$anchor_label = ucfirst($anchor->get_title());
				$details[] = ' '.sprintf(i18n::s('in %s'), Skin::build_link($anchor_url, $anchor_label, 'article'));
			}

			// append details to the suffix
			if(count($details))
				$suffix .= '<p class="details">'.ucfirst(implode(', ', $details)).'</p>'."\n";

			// build the actual link to check it
			if($variant == 'review')
				$icon = $item['link_url'];

			// url is the link itself -- hack for xhtml compliance
			$url = str_replace('&', '&amp;', $item['link_url']);

			// let the rendering engine guess the type of link
			$link_type = NULL;

			// except if we want to stay within this window
			if(isset($item['link_target']) && ($item['link_target'] == 'I'))
				$link_type = 'internal';

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