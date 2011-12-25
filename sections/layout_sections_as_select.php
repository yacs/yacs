<?php
/**
 * layout sections to select one
 *
 * This is a special layout aiming to target a section for a new post.
 *
 * @see articles/edit.php
 * @see sections/sections.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @author Thierry Pinelli [email]contact@vdp-digital.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_sections_as_select extends Layout_interface {

	/**
	 * list sections
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

		// sanity check
		if(!isset($this->layout_variant))
			$this->layout_variant = 'articles/edit.php?anchor=section:';

		// we return some text
		$text ='';

		// stack of items
		$items = array();

		// process all items in the list
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'links/links.php';
		$family = '';
		while($item = SQL::fetch($result)) {

			// strip locked sections, except to associates and editors
			if(isset($item['locked']) && ($item['locked'] == 'Y') && !Surfer::is_empowered())
				continue;

			// change the family
			if($item['family'] != $family) {

				// flush current stack, if any
				if(count($items))
					$text .= Skin::build_list($items, '2-columns');
				$items = array();

				// show the family
				$family = $item['family'];
				$text .= '<h3 class="family">'.$family.'</h3>'."\n";

			}

			// format one item
			$items = array_merge($items, $this->one($item));

		}

		// flush the stack
		if(count($items))
			$text .= Skin::build_list($items, '2-columns');

		// end of processing
		SQL::free($result);
		return $text;
	}

	/**
	 * format just one item
	 *
	 * This is used within this script, but also to shape sections assigned
	 * to the surfer in the web form for new articles.
	 *
	 * @param array attributes of one item
	 * @return array of ($url => array($prefix, $label, $suffix, ...))
	 *
	 * @see articles/edit.php
	**/
	function &one(&$item) {
		global $context;

		// this function is invoked directly from articles/edit.php
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'links/links.php';

		// sanity check
		if(!isset($this->layout_variant))
			$this->layout_variant = 'articles/edit.php?anchor=section:';

		// initialize variables
		$prefix = $suffix = $icon = '';

		// flag sections that are draft, dead, or created or updated very recently
		if($item['activation_date'] >= $context['now'])
			$prefix .= DRAFT_FLAG;
		elseif(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
			$prefix .= EXPIRED_FLAG;
		elseif($item['create_date'] >= $context['fresh'])
			$suffix .= NEW_FLAG;
		elseif($item['edit_date'] >= $context['fresh'])
			$suffix .= UPDATED_FLAG;

		// signal restricted and private sections
		if($item['active'] == 'N')
			$prefix .= PRIVATE_FLAG;
		elseif($item['active'] == 'R')
			$prefix .= RESTRICTED_FLAG;

		// details
		$details = array();

		// info on related articles
		if($count = Members::count_articles_for_anchor('section:'.$item['id']))
			$details[] = sprintf(i18n::ns('%d page', '%d pages', $count), $count);

		// info on related files
		if($count = Files::count_for_anchor('section:'.$item['id'], TRUE))
			$details[] = sprintf(i18n::ns('%d file', '%d files', $count), $count);

		// info on related links
		if($count = Links::count_for_anchor('section:'.$item['id'], TRUE))
			$details[] = sprintf(i18n::ns('%d link', '%d links', $count), $count);

		// info on related comments
		if($count = Comments::count_for_anchor('section:'.$item['id'], TRUE))
			$details[] = sprintf(i18n::ns('%d comment', '%d comments', $count), $count);

		// append details to the suffix
		if(count($details))
			$suffix .= "\n".'<span class="details">('.implode(', ', $details).')</span>';

		// introduction
		if($item['introduction'])
			$suffix .= ' '.Codes::beautify_introduction(trim($item['introduction']));

		// add a head list of related links
		$subs = array();

		// add sub-sections on index pages
		if($related =& Sections::list_by_title_for_anchor('section:'.$item['id'], 0, 2 * YAHOO_LIST_SIZE, 'raw')) {
			foreach($related as $id => $attributes) {

				// look for sub-sub-sections
				$leaves = array();
				if($children =& Sections::list_by_title_for_anchor('section:'.$id, 0, 50, 'raw')) {
					foreach($children as $child_id => $child_attributes) {
						$child_url = $this->layout_variant.$child_id;
						$leaves[$child_url] = $child_attributes['title'];
					}
				}

				// link for this sub-section
				$url = $this->layout_variant.$id;

				// expose sub-sub-sections as well
				if(count($leaves) > YAHOO_LIST_SIZE)
					$subs[$url] = array('', $attributes['title'], Skin::build_box(i18n::s('More spaces'), Skin::build_list($leaves, 'compact'), 'folded'));

				// expose sub-sub-sections as well
				elseif(count($leaves))
					$subs[$url] = array('', $attributes['title'], Skin::build_list($leaves, 'compact'));

				// a simple clickable label
				else
					$subs[$url] = $attributes['title'];
			}
		}

		// one sub-section per line
		if(count($subs))
			$suffix .= Skin::build_list($subs, 'compact');

		// put the actual icon in the left column
		if(isset($item['thumbnail_url']))
			$icon = $item['thumbnail_url'];

		// only associates and editors can post to a locked section
		if(isset($item['locked']) && ($item['locked'] == 'Y') && !Surfer::is_empowered())
			$url = '_'.$item['id'];

		// url to select a section
		else
			$url = $this->layout_variant.$item['id'];

		// use the title to label the link
		$label = Skin::strip($item['title'], 50);

		// list all components for this item
		$output = array( $url => array($prefix, $label, $suffix, 'section', $icon) );
		return $output;
	}

}

?>