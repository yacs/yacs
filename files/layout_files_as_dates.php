<?php
/**
 * layout files as a compact list
 *
 * @see files/files.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_files_as_dates extends Layout_interface {

	/**
	 * list files
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

			// download the file directly
			$url = Files::get_url($item['id'], 'fetch', $item['file_name']);

			// file title or file name
			$label = Codes::beautify_title($item['title']);
			if(!$label)
				$label = ucfirst(str_replace(array('%20', '-', '_'), ' ', $item['file_name']));

			// initialize variables
			$prefix = $suffix = '';

			$contributor = Users::get_link($item['create_name'], $item['create_address'], $item['create_id']);
			$flag = '';
			if($item['create_date'] >= $context['fresh'])
				$flag = NEW_FLAG;
			elseif($item['edit_date'] >= $context['fresh'])
				$flag = UPDATED_FLAG;

			$suffix .= '<span '.tag::_class('details').'> - '.sprintf(i18n::s('By %s'), $contributor).' '.Skin::build_date($item['create_date']).$flag.'</span>';

			// signal restricted and private files
			if(($item['active'] == 'N') && defined('PRIVATE_FLAG'))
				$prefix .= PRIVATE_FLAG;
			elseif(($item['active'] == 'R') && defined('RESTRICTED_FLAG'))
				$prefix .= RESTRICTED_FLAG;

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, 'file', NULL);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>
