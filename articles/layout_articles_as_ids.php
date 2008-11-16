<?php
/**
 * layout articles as a set of titles with thumbnails
 *
 * @see articles/articles.php
 *
 * @author Bernard Paques
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_ids extends Layout_interface {

	/**
	 * list articles
	 *
	 * @param resource the SQL result
	 * @return array the list of found ids
	 *
	 * @see skins/layout.php
	**/
	function &layout(&$result) {
		global $context;

		// we return an array of ids
		$output = array();

		// empty list
		if(!SQL::count($result))
			return $text;

		// process all items in the list
		while($item =& SQL::fetch($result))
			$output[] = $item['id'];

		// end of processing
		SQL::free($result);
		return $output;
	}

}

?>