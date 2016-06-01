<?php
/**
 * layout sections to display related rights of the surfer
 *
 * This layout has been designed to help users to understand their rights on listed pages.
 *
 * Final rendering is a table with following columns:
 * - item - including the title, which is also a clickable link to the page, tags, etc
 * - update - dates of last modification
 * - owner - with a checkbox if the surfer is an owner of this item
 * - editor - with a checkbox if the surfer is an editor of this item
 * - watcher - with a checkbox if the surfer is a watcher of this item
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_sections_as_rights extends Layout_interface {

	/**
	 * the preferred number of items for this layout
	 *
	 * @return 50
	 *
	 * @see layouts/layout.php
	 */
	function items_per_page() {
		return 50;
	}

	/**
	 * list sections as rows in a table
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	**/
	function layout($result) {
		global $context;

		// we return some text
		$text = '';

		// empty list
		if(!SQL::count($result))
			return $text;

		// we list pages for one surfer
		// sanity check
		if(!isset($this->focus))
			$this->focus = Surfer::get_id();

		// build a list of sections
		Skin::define_img('CHECKED_IMG', 'ajax/accept.png', '*');
		$rows = array();
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'links/links.php';
		while($item = SQL::fetch($result)) {

			// get the related overlay
			$overlay = Overlay::load($item, 'section:'.$item['id']);

			// get the anchor
			$anchor = Anchors::get($item['anchor']);

			// the url to view this item
			$url = sections::get_permalink($item);

			// reset everything
			$summary = $update = $owner = $editor = $watcher = '';

			if($item['activation_date'] >= $context['now'])
				$summary .= DRAFT_FLAG;
			elseif(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
				$summary .= EXPIRED_FLAG;

			// signal restricted and private sections
			if($item['active'] == 'N')
				$summary .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$summary .= RESTRICTED_FLAG;

			// indicate the id in the hovering popup
			$hover = i18n::s('View the page');
			if(Surfer::is_member())
				$hover .= ' [section='.$item['id'].']';

			// use the title to label the link
			if(is_object($overlay))
				$label = Codes::beautify_title($overlay->get_text('title', $item));
			else
				$label = Codes::beautify_title($item['title']);

			// use the title as a link to the page
			$summary .= Skin::build_link($url, $label, 'basic', $hover);

			// signal locked sections
			if(isset($item['locked']) && ($item['locked'] == 'Y') && Sections::is_owned($item, $anchor))
				$summary .= ' '.LOCKED_FLAG;

			// flag sections updated recently
			if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
				$summary .= ' '.EXPIRED_FLAG;
			elseif($item['create_date'] >= $context['fresh'])
				$summary .= ' '.NEW_FLAG;
			elseif($item['edit_date'] >= $context['fresh'])
				$summary .= ' '.UPDATED_FLAG;

			// insert overlay data, if any
			if(is_object($overlay))
				$summary .= $overlay->get_text('list', $item);

			// attachment details
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

			// comments
			if($count = Comments::count_for_anchor('section:'.$item['id'], TRUE))
				$details[] = sprintf(i18n::ns('%d comment', '%d comments', $count), $count);

			// the main anchor link
			if(is_object($anchor) && (!isset($this->focus) || ($item['anchor'] != $this->focus)))
				$details[] = sprintf(i18n::s('in %s'), Skin::build_link($anchor->get_url(), ucfirst($anchor->get_title()), 'basic'));

			// combine in-line details
			if(count($details))
				$summary .= BR.'<span '.tag::_class('details').'>'.trim(implode(' &middot; ', $details)).'</span>';

			// display all tags
			if($item['tags'])
				$summary .= BR.tag::_('span', tag::_class('tags'), Skin::build_tags($item['tags']));

			// watcher
			if(Sections::is_watched($item['id'], $this->focus))
				$watcher = CHECKED_IMG;

			// editor
			if(Sections::is_editable($anchor, $item, $this->focus, TRUE))
				$editor = CHECKED_IMG;

			// owner
			if(Sections::is_owned($item, NULL, TRUE, $this->focus))
				$owner = CHECKED_IMG;

			// this is another row of the output
			$cells = array($summary, $watcher, $editor, $owner);

			// append this row
			$rows[] = $cells;

		}

		// end of processing
		SQL::free($result);

		// headers
		$headers = array(i18n::s('Section'), i18n::s('Watcher'), i18n::s('Editor'), i18n::s('Owner'));

		// return a sortable table
		$text .= Skin::table($headers, $rows, 'yc-grid');
		return $text;
	}
}

?>
