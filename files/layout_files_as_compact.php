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
Class Layout_files_as_compact extends Layout_interface {

	/**
	 * list files
	 *
	 * @param resource the SQL result
	 * @return array of resulting items, or NULL
	 *
	 * @see skins/layout.php
	**/
	function &layout(&$result) {
		global $context;

		// we return an array of ($url => $attributes)
		$items = array();

		// empty list
		if(!SQL::count($result))
			return $items;

		// flag files updated recently
		if($context['site_revisit_after'] < 1)
			$context['site_revisit_after'] = 2;
		$dead_line = gmstrftime('%Y-%m-%d %H:%M:%S', mktime(0,0,0,date("m"),date("d")-$context['site_revisit_after'],date("Y")));
		$now = gmstrftime('%Y-%m-%d %H:%M:%S');

		// process all items in the list
		while($item =& SQL::fetch($result)) {

			// play freemind maps and flash files in separate windows
			if(preg_match('/\.(mm|swf)$/i', $item['file_name']))
				$url = Files::get_url($item['id'], 'stream', $item['file_name']);

			// else download the file
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

			// list all components for this item
			$items[$url] = array($prefix, $label, $suffix, 'basic', NULL);

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>