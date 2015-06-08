<?php
/**
 * layout comments
 *
 * This is the default layout for comments.
 *
 * @see comments/comments.php
 *
 * @author Bernard Paques
 * @author GnapZ
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_comments extends Layout_interface {

	/**
	 * list comments
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	 *
	 * @see layouts/layout.php
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
			$this->layout_variant = 'full';

		// we return an array of ($url => $attributes)
		$items = array();

		// process all items in the list
		include_once $context['path_to_root'].'comments/comments.php';
		while($item = SQL::fetch($result)) {

			// get the anchor
			$anchor = Anchors::get($item['anchor']);

			// initialize variables
			$prefix = $suffix = '';

			// there is no zoom page for comments
			$label = '_';

			// the icon is a link to comment permalink
			$suffix .= Skin::build_link(Comments::get_url($item['id']), Comments::get_img($item['type']), 'basic', i18n::s('View this comment'));

			// a link to the user profile
			if($item['create_name'])
				$suffix .= ' '.Users::get_link($item['create_name'], $item['create_address'], $item['create_id']);
			else
				$suffix .= ' '.Users::get_link($item['edit_name'], $item['edit_address'], $item['edit_id']);

			$menu = array();

			// the edition date
			if($item['create_date'])
				$menu[] = Skin::build_date($item['create_date']);
			else
				$menu[] = Skin::build_date($item['edit_date']);

			// the menu bar for associates, editors and poster
			if(Comments::allow_modification($anchor, $item)) {
				$menu[] = Skin::build_link(Comments::get_url($item['id'], 'edit'), i18n::s('edit'), 'span');
				$menu[] = Skin::build_link(Comments::get_url($item['id'], 'delete'), i18n::s('delete'), 'span');
			}

			if($menu)
				$suffix .= ' -'.Skin::finalize_list($menu, 'menu');

			// new line
			$suffix .= BR;

			// description
			if($description = ucfirst(trim(Codes::beautify($item['description'].Users::get_signature($item['create_id'])))))
					$suffix .= ' '.$description;

			// url to view the comment
			$url = Comments::get_url($item['id']);

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, 'comment', NULL);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>