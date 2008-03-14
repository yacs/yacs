<?php
/**
 * provide raw content of articles
 *
 * @see articles/articles.php
 *
 * @author Bernard Paques [email]bernard.paques@bigfoot.com[/email]
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_raw extends Layout_interface {

	/**
	 * list articles
	 *
	 * @param resource the SQL result
	 * @return arrayt
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

		// process all items in the list
		while($item =& SQL::fetch($result)) {

			// include all available attributes for this item
			$items[ $item['id'] ] = $item;

		}

		// end of processing
		SQL::free($result);
		return $items;
	}

}

?>