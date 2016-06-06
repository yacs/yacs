<?php
/**
 * layout articles to select one
 *
 * This is a special layout aiming to select a template for a new post.
 *
 * @see articles/edit.php
 *
 * @author Bernard Paques
 * @author Thierry Pinelli (ThierryP)
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 */
Class Layout_articles_as_select extends Layout_interface {

	/**
	 * list sections
	 *
	 * @param resource the SQL result
	 * @return an array of items
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
		include_once $context['path_to_root'].'comments/comments.php';
		include_once $context['path_to_root'].'links/links.php';
		while($item = SQL::fetch($result))
			$items = array_merge($items, Layout_articles_as_select::one($item));

		// end of processing
		SQL::free($result);
		return $items;
	}

	/**
	 * format just one item
	 *
	 * @param array attributes of one item
	 * @return array of ($url => array($prefix, $label, $suffix, ...))
	 *
	 * @see articles/edit.php
	**/
	function one(&$item) {
		global $context;

		// initialize variables
		$prefix = $suffix = $icon = '';

		// flag sections that are created or updated very recently
		if(($item['expiry_date'] > NULL_DATE) && ($item['expiry_date'] <= $context['now']))
			$prefix .= EXPIRED_FLAG;
		elseif($item['create_date'] >= $context['fresh'])
			$suffix .= NEW_FLAG;
		elseif($item['edit_date'] >= $context['fresh'])
			$suffix .= UPDATED_FLAG;

		// signal restricted and private articles
		if($item['active'] == 'N')
			$prefix .= PRIVATE_FLAG;
		elseif($item['active'] == 'R')
			$prefix .= RESTRICTED_FLAG;

		// details
		$details = array();

		// info on related files
		if($count = Files::count_for_anchor('article:'.$item['id'], TRUE))
			$details[] = sprintf(i18n::ns('%d file', '%d files', $count), $count);

		// info on related links
		if($count = Links::count_for_anchor('article:'.$item['id'], TRUE))
			$details[] = sprintf(i18n::ns('%d link', '%d links', $count), $count);

		// info on related comments
		if($count = Comments::count_for_anchor('article:'.$item['id'], TRUE))
			$details[] = sprintf(i18n::ns('%d comment', '%d comments', $count), $count);

		// append details to the suffix
		if(count($details))
			$suffix .= "\n".'<span '.tag::_class('details').'>('.implode(', ', $details).')</span>';

		// introduction
		if($item['introduction'])
			$suffix .= ' '.Codes::beautify_introduction($item['introduction']);

		// add a head list of related links
		$subs = array();

		// put the actual icon in the left column
		if(isset($item['thumbnail_url']))
			$icon = $item['thumbnail_url'];

		// url to select this article
		$url = 'articles/edit.php?template='.$item['id'];

		// use the title to label the link
		$label = Skin::strip($item['title'], 50);

		// list all components for this item
		$output = array( $url => array($prefix, $label, $suffix, 'article', $icon, i18n::s('Select this model')) );
		return $output;
	}

}

?>
