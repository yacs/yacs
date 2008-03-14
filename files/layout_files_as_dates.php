<?php
/**
 * layout files as a compact list, with hits count
 *
 * @see files/files.php
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_files_as_dates extends Layout_interface {

	/**
	 * list files
	 *
	 * @param resource the SQL result
	 * @return string the rendered text
	 *
	 * @see skins/layout.php
	**/
	function &layout(&$result) {
		global $context;

		// empty list
		if(!SQL::count($result)) {
			$output = array();
			return $output;
		}

		// we return an array of ($url => $attributes)
		$items = array();

		// flag files updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// process all items in the list
		while($item =& SQL::fetch($result)) {

			// view the file page if there is some description
			if(isset($item['description']) && trim($item['description']))
				$url = Files::get_url($item['id'], 'view', $item['file_name']);

			// else download the file directly
			else
				$url = Files::get_url($item['id'], 'fetch', $item['file_name']);

			// initialize variables
			$prefix = $suffix = '';

			// flag files that are dead, or created or updated very recently
			if($item['create_date'] >= $dead_line)
				$suffix .= NEW_FLAG;
			elseif($item['edit_date'] >= $dead_line)
				$suffix .= UPDATED_FLAG;

			// signal restricted and private files
			if($item['active'] == 'N')
				$prefix .= PRIVATE_FLAG;
			elseif($item['active'] == 'R')
				$prefix .= RESTRICTED_FLAG;

			// file title or file name
			$label = Codes::beautify_title($item['title']);
			if(!$label)
				$label = ucfirst(str_replace(array('%20', '-', '_'), ' ', $item['file_name']));

			// with dates
			$suffix .= ' '.Skin::build_date($item['edit_date']);

			// list all components for this item
			$items[$url] = array(NULL, $label, $suffix, 'file', NULL);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>